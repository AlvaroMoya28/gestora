# Sistema PAA — UCR

Migración del sistema de administración del Programa Permanente de la Prueba de
Aptitud Académica, desde Google Sheets + Apps Script hacia PHP + MariaDB.

📄 **Leé [`CONTEXTO.md`](CONTEXTO.md) primero** — contexto completo, accesos,
convenciones y orden de migración.
📝 Todo cambio se anota en [`CHANGELOG.md`](CHANGELOG.md).
🚀 Para poner en el servidor lo que está pendiente: [`DESPLIEGUE.md`](DESPLIEGUE.md).

## Estructura

El contenido de esta carpeta **es** la raíz del sitio web.

```text
paa/                             ← raíz del repositorio
├── PAA/                         ← ESTE proyecto: el sistema nuevo
│   ├── includes/    🔒 Conexión a BD y helpers compartidos
│   │   ├── config.php       credenciales (NO se versiona)
│   │   ├── config.example.php
│   │   ├── db.php           conexión PDO + responder_json
│   │   ├── peticion.php     entrada, método, convocatoria activa
│   │   └── correo.php       envío con destinatario resuelto en el servidor
│   ├── api/         🌐 Endpoints JSON, uno por módulo
│   ├── schema/      🔒 Migraciones SQL numeradas (001…010)
│   ├── backups/     🔒 Respaldos de la base + REGISTRO.md
│   ├── scripts/     🔒 Respaldo, restauración, migración, importación, verificación
│   ├── Coordinadores_Directorio/   🌐 Página del módulo
│   ├── Sedes/                      🌐 Página del módulo
│   ├── CreacionTickets/            🌐 Página del módulo
│   ├── CONTEXTO.md          contexto completo del proyecto
│   ├── CHANGELOG.md         bitácora de cambios
│   ├── DESPLIEGUE.md        cómo subir cambios al servidor
│   ├── MANUAL_ASISTENTE.md  guía para quien desarrolla un módulo
│   └── ACCESOS_ASISTENTE.md cómo dar y limitar accesos
└── sistema_anterior/            ← el sistema viejo de Apps Script
```

🔒 = bloqueada por `.htaccess`, no debe servirse por HTTP
🌐 = pública

### `sistema_anterior/`

Son los `.html` y `.gs` originales, tal como funcionaban en Google Apps Script.
**No se despliegan ni forman parte del sitio**: quedan como referencia mientras
se portan las páginas que faltan, porque cada `index.php` nuevo se escribe a
partir de su `.html` original.

Cuando los diez módulos estén migrados y en uso, esa carpeta se puede eliminar:
su contenido sigue en el historial de git y en las ramas `main` y `develop`.

## Puesta en marcha

```bash
cp includes/config.example.php includes/config.php
# editar includes/config.php con la clave real de la BD

php scripts/migrar.php --estado    # ver qué falta aplicar
php scripts/migrar.php             # aplicar (respalda antes y después)
```

## Operaciones diarias

```bash
php scripts/respaldar_bd.php "motivo"                    # respaldar
php scripts/restaurar_bd.php backups/gestionpaa_....sql  # restaurar
php scripts/migrar.php                                   # aplicar migraciones
php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
```

### Cargar datos desde los Google Sheets

Mientras haya módulos sin migrar, su Sheet sigue siendo la fuente. Para traer su
contenido a la base:

```bash
php scripts/importar_csv.php --lista                  # ver los destinos
php scripts/importar_csv.php sedes archivo.csv
php scripts/importar_csv.php aulas archivo.csv --convocatoria="PAA 2026"
```

Es idempotente: actualiza por clave natural en vez de duplicar, así que se puede
volver a correr cada vez que el Sheet cambie.

## Despliegue

Requiere estar en la red UCR (VPN GlobalProtect, portal `acceso.ucr.ac.cr`).
La raíz web es **`~/public_html/web`**.

La máquina de desarrollo no tiene `rsync`, así que va por paquete + `scp`.
Desde la carpeta que contiene `PAA/`:

```bash
tar --exclude=.git --exclude=config.php -czf paa_sitio.tar.gz PAA
scp paa_sitio.tar.gz gestionpaa@172.16.49.116:~/
```

Y en el servidor:

```bash
cd ~ && tar -xzf paa_sitio.tar.gz
cp -r ~/PAA/. ~/public_html/web/    # el /. copia también los .htaccess
```

Para uno o dos archivos sueltos alcanza con `scp` directo:

```bash
scp index.php gestionpaa@172.16.49.116:~/public_html/web/
```

⚠️ Los comandos `tar`/`scp` van en la terminal **de tu máquina**; los `php` y
`cp`, en la **sesión SSH**. Si el prompt dice `gestionpaa@acceso01:~$` estás en
el servidor.

Después de desplegar, **siempre**:

```bash
php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
```

⚠️ Correlo desde una máquina con VPN, **no** desde `acceso01`: ese contenedor
no tiene DNS y no resuelve el dominio.

## Reglas del proyecto

1. Respaldar antes de cualquier cambio a la base (los scripts lo hacen solos).
2. Todo cambio de esquema va como migración numerada en `schema/`, nunca a mano
   en phpMyAdmin.
3. Todo cambio se anota en `CHANGELOG.md`.
4. Las credenciales solo viven en `includes/config.php`.
5. PDO con consultas preparadas siempre.

## Módulos

| Módulo | Base | Endpoint | Página |
| --- | --- | --- | --- |
| Coordinadores_Directorio | ✅ | ✅ | ✅ |
| Sedes | ✅ | ✅ | ✅ |
| Tulas | ✅ | ✅ | ⏳ |
| AsignacionMarchamos | ✅ | ✅ | ⏳ |
| RevisionAulas | ✅ | ✅ | ⏳ |
| Activos | ✅ | ✅ | ⏳ |
| SobreSueldos | ✅ | ✅ | ⏳ |
| AsignacionCoordinadores | ✅ | ✅ | ⏳ |
| Administracion | ✅ | ✅ | ⏳ |
| CreacionTickets · Prueba | — | — | — |
| PaginaPrincipal | al final (contenido estático) | | |

⏳ = la página sigue apuntando a Google Sheets. **Mientras siga así, ese Sheet
es la fuente de verdad del módulo** y hay que reimportar antes de confiar en los
datos de la base.

### Cómo portar una página

Es lo que se hizo con `Sedes/`:

1. Copiar el `.html` original a `<Modulo>/index.php`.
2. Cambiar las constantes `SHEET_ID` / `CSV_URL` / `APPS_SCRIPT_URL` por
   `../api/<modulo>.php`.
3. Reemplazar el parseo de CSV por `await respuesta.json()`.
4. Los POST al Apps Script pasan a ser POST al endpoint con `accion=…`.
5. Probar la página completa antes de marcarla `disponible` en `index.php`.
