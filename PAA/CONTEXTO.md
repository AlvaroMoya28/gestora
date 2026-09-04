# Migración del sistema PAA — Contexto del proyecto

> Documento de traspaso. Contiene todo lo necesario para retomar el trabajo
> desde cero en una sesión nueva.

---

## 1. Qué estamos haciendo

Migrar el sistema de administración del **Programa Permanente de la Prueba de
Aptitud Académica (PAA)** de la Universidad de Costa Rica.

**Origen:** https://www.paa.iip.ucr.ac.cr/administracion/ — un sitio WordPress
(Elementor) protegido por contraseña, cuya página de "Herramientas" redirige a
varias páginas HTML sueltas. Cada una de esas páginas lee y escribe en su
**propio Google Sheet**, mediante **proyectos de Apps Script independientes**
(uno por herramienta, no hay un backend central).

**Destino:** un hosting institucional propio (Debian + PHP + MariaDB) con una
**base de datos formal y relacional**, reemplazando por completo Google Sheets
y Apps Script.

### Objetivos acordados

1. Nada de HTML suelto leyendo CSV de Google: backend en **PHP + PDO** contra MariaDB.
2. Ir **módulo por módulo**, de forma ordenada, sin apurar.
3. **Tablas núcleo reutilizables** entre módulos. Ejemplo: una sola tabla de
   coordinadores que sirva a varias herramientas, en vez de repetir los datos
   en cada Sheet como pasa hoy.
4. Mantener el diseño visual existente (es bueno), cambiando solo la capa de datos.

---

## 2. Acceso al servidor

> ⚠️ **La IP es privada (172.16.x.x): solo se alcanza desde la red UCR.**
> Hay que estar conectada a la VPN institucional **GlobalProtect**
> (portal `acceso.ucr.ac.cr`, credenciales institucionales sin `@ucr.ac.cr`)
> o físicamente en el campus. Sin VPN, la conexión falla siempre.

### SSH / SFTP

| Dato | Valor |
|---|---|
| Host | `172.16.49.116` |
| Puerto | `22` |
| Usuario | `gestionpaa` |
| Dominio | `gestionpaa.ucr.ac.cr` (⚠️ **todavía no existe en el DNS**) |
| Sistema | Debian GNU/Linux 6.1 (hostname `acceso01`) |
| **Raíz web** | `~/public_html/web` |
| Ruta absoluta | `/mnt/glusterfs/cms-data/gestionpaa/public_html/web` |
| PHP | 8.4.24 CLI, con `pdo_mysql`, `curl`, `mbstring`, `json` |
| Clientes MySQL | `mysql` y `mysqldump` disponibles |

⚠️ `acceso01` es el **contenedor de acceso**: da terminal y sistema de
archivos, pero **no corre el servidor web ni tiene DNS**. No resuelve
`gestionpaa.ucr.ac.cr` ni ningún dominio externo, así que desde ahí no se puede
comprobar nada por HTTP. Las verificaciones web hay que correrlas desde una
máquina con VPN.

⚠️ **Ojo con las contraseñas: el documento del Centro de Informática trae dos
distintas.** Una es de SSH/SFTP y otra es de la base de datos. La que va en
`includes/config.php` es la **de la base**. Confundirlas da
`Access denied for user 'gestionpaa'@'acceso01'` en PDO mientras el cliente
`mysql` sí conecta — que es exactamente el síntoma que despista.

```bash
ssh gestionpaa@172.16.49.116
```

### Base de datos (MariaDB)

| Dato | Valor |
|---|---|
| Nombre BD | `gestionpaa` |
| Usuario | `gestionpaa` |
| Host | `servicio_bd_haproxy_bd` |
| Puerto | `3306` |
| phpMyAdmin | https://hospedaje-web.ucr.ac.cr |

Las contraseñas están en el documento de credenciales del Centro de
Informática y en `includes/config.php` (que **no** se versiona).

### Soporte

Centro de Informática UCR — tel. 2511-5000 — ci5000@ucr.ac.cr
Documentación: https://git.ucr.ac.cr/web-institucional/documentacion-uso/

---

## 3. Estado actual

**Desplegado el 2026-08-20.** El sistema está en `~/public_html/web` y la
migración `001` aplicada: 11 áreas, 19 coordinadores, 22 teléfonos, verificados
contra la base. El endpoint `api/coordinadores.php` responde JSON correcto con
acentos.

- **Módulo 1 (Coordinadores_Directorio): ✅ desplegado y funcionando.**
- Portada `index.php` en la raíz, con las herramientas listadas.
- MariaDB 10.11.18 · PHP 8.4.24.

**Actualizado el 2026-08-21 — esquema y backend completos.**

Se diseñó de una vez la capa de datos de todos los módulos (migraciones `002` a
`008`) y se escribió el endpoint de cada uno. Motivo: al mapear las fuentes
apareció que **cuatro herramientas leen el mismo Sheet** (`1KEcA3…`), cada una
interpretándolo a su manera; diseñar sus tablas por separado habría vuelto a
crear cuatro copias del mismo dato.

| Módulo | Base | Endpoint | Página |
| --- | --- | --- | --- |
| Coordinadores_Directorio | ✅ | ✅ | ✅ |
| Sedes | ✅ | ✅ | ✅ |
| Tulas | ✅ | ✅ | ⏳ |
| Asignación de Marchamos | ✅ | ✅ | ⏳ |
| Revisión de Aulas | ✅ | ✅ | ⏳ |
| Activos | ✅ | ✅ | ⏳ |
| Sobresueldos | ✅ | ✅ | ⏳ |
| Asignación de Coordinadores | ✅ | ✅ | ⏳ |
| Administración | ✅ | ✅ | ⏳ |
| Creación de Tickets | — | — | — |
| Prueba | — | — | — |

⏳ = la página sigue apuntando a Google Sheets. **Mientras eso siga así, los
Sheets continúan siendo la fuente de verdad de esos módulos y hay que volver a
importar antes de usar los datos migrados**, o las dos copias se separan.

Pasos para aplicar todo esto en el servidor: **`DESPLIEGUE.md`**.

### 🚨 Bloqueante antes de que el sitio salga a producción

**`gestionpaa.ucr.ac.cr` todavía no existe en el DNS y el contenedor web no
responde** — ni por dominio ni por IP (`curl` devuelve `000`, sin conexión TCP).
Hay que pedir la publicación al Centro de Informática.

Como consecuencia, **nunca se pudo comprobar si los `.htaccess` funcionan.**
Hoy no hay riesgo porque nadie alcanza el sitio, pero `backups/` ya contiene
dumps con los nombres, correos y teléfonos de las 19 personas.

**El día que publiquen el dominio, ANTES de darlo por bueno:**

```bash
php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
```

(Correrlo desde una máquina con VPN, **no** desde `acceso01`, que no tiene DNS.)

Si alguna ruta privada responde 200, mover `backups/` fuera de `public_html/`
de inmediato. La alternativa segura —y que no depende de acordarse de esto— es
sacar `backups/` de la raíz web ya mismo, a `~/backups_gestionpaa`, ajustando
la ruta en los scripts. Quedó a decisión de la usuaria.

### Otros pendientes

- Pedir al Centro de Informática el **cambio de ambas contraseñas** (SSH y base
  de datos): salieron de su canal durante el despliegue.
- Opcional: activar el respaldo automático diario (ver `backups/README.md`).

---

## 4. Arquitectura y convenciones

### Estructura de archivos

**El contenido de `PAA/` es la raíz del sitio web.** Lo que está
dentro se sirve por HTTP, salvo las carpetas bloqueadas por `.htaccess`.

```
PAA/
├── includes/        🔒 config.php (credenciales, NO versionar)
│   │                   config.example.php (plantilla)
│   └                   db.php (conexión PDO + responder_json)
├── api/             🌐 endpoints JSON, uno por módulo
├── schema/          🔒 migraciones SQL numeradas (001_, 002_, ...)
├── backups/         🔒 respaldos .sql + REGISTRO.md
├── scripts/         🔒 respaldar_bd, restaurar_bd, migrar, verificar_seguridad
├── <Modulo>/        🌐 página de cada módulo (index.php)
├── CONTEXTO.md
├── CHANGELOG.md
└── README.md
```

🔒 = bloqueada por `.htaccess` · 🌐 = pública

Cada módulo nuevo agrega **un endpoint en `api/`**, **una carpeta con su
página**, y **una migración en `schema/`**. La conexión y los helpers se
comparten desde `includes/`.

### Respaldos y migraciones — reglas del proyecto

1. **Respaldar antes de cualquier cambio a la base.** `scripts/migrar.php` y
   `scripts/restaurar_bd.php` lo hacen solos; los cambios manuales en
   phpMyAdmin hay que respaldarlos a mano primero.
2. **Todo cambio de esquema va como migración numerada en `schema/`**, nunca
   escrito a mano directamente en phpMyAdmin. La tabla `migraciones` lleva la
   cuenta de lo aplicado, así el servidor y cualquier copia local terminan con
   el mismo esquema.
3. **Todo cambio se anota en `CHANGELOG.md`**: qué, por qué, qué archivos, si
   requiere migración o despliegue.
4. Las credenciales solo viven en `includes/config.php`.

```bash
php scripts/respaldar_bd.php "motivo"    # respaldo manual
php scripts/migrar.php --estado          # ver pendientes
php scripts/migrar.php                   # aplicar (respalda antes y después)
php scripts/restaurar_bd.php backups/gestionpaa_....sql
php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
```


### Entorno de desarrollo local

Se puede correr una copia de la base en la máquina de desarrollo. En
`includes/config.php` están `db_socket` y `db_port` justo para eso:

```php
'db_socket' => '/ruta/al/mysqld.sock',   // ignora db_host si está presente
```

Levantar el sitio localmente:

```bash
php -S 127.0.0.1:8000
```

⚠️ El servidor incorporado de PHP **no lee los `.htaccess`**, así que en local
las carpetas privadas SÍ quedan accesibles. Es solo para desarrollo; nunca
usarlo de cara a internet.

### ⚠️ Riesgo conocido: respaldos dentro de la carpeta web

`backups/` vive dentro de la raíz del sitio por decisión de la usuaria (todo
junto en `PAA/`). Los `.htaccess` la bloquean, **pero `.htaccess`
solo funciona en Apache con `AllowOverride` habilitado**. Si el hosting usa
nginx, o Apache los ignora, los dumps quedan descargables por cualquiera —
y contienen todos los datos personales del personal.

Por eso existe `scripts/verificar_seguridad.php`: **hay que correrlo después de
cada despliegue.** Si reporta algo expuesto, mover `backups/` fuera de la raíz
web de inmediato.

### Convenciones de código

- **PHP con PDO y consultas preparadas siempre.** Nada de `mysqli` ni de
  concatenar SQL.
- Los endpoints responden **JSON uniforme**: `{success: bool, ...}` o
  `{success: false, error: "..."}`.
- Los errores reales van a `error_log()`, **nunca al navegador** (un mensaje de
  PDO puede filtrar nombres de tablas o credenciales).
- Nombres de tablas y columnas **en español, snake_case, plural para tablas**.
- Toda tabla: `InnoDB`, `utf8mb4`, `id INT AUTO_INCREMENT PRIMARY KEY`,
  y `creado_en` / `actualizado_en` cuando aplique.
- Preferir `activo TINYINT(1)` sobre borrar filas, para no romper referencias
  históricas de otros módulos.
- El JS escapa siempre antes de insertar en el DOM (función `escapar()`).
- Comentarios en español explicando **por qué**, no qué.

---

## 5. Esquema de base de datos

### Catálogos núcleo

```sql
areas
  id, nombre (UNIQUE), orden, creado_en

personas                        -- se llamaba `coordinadores` hasta la migración 003
  id, area_id → areas.id, nombre, correo (UNIQUE), extension,
  activo, puede_retirar_activos, creado_en, actualizado_en

persona_telefonos               -- se llamaba `coordinador_telefonos`
  id, persona_id → personas.id (ON DELETE CASCADE), telefono

sedes                           -- 002
  id, codigo (UNIQUE), nombre, director, contacto_conserje, telefono,
  correo, direccion, descripcion, imagen_url, activo

convocatorias                   -- 004
  id, nombre (UNIQUE), fecha_aplicacion, activa
```

**Por qué `coordinadores` pasó a llamarse `personas` (migración 003):** la hoja
"Personal" de Activos tiene la misma estructura y las mismas personas cargadas
por segunda vez. Darle tabla propia habría repetido el problema que la
migración viene a resolver. Se hizo temprano, cuando solo un endpoint dependía
de esa tabla. `RENAME TABLE` conserva datos, índices y llaves foráneas.
La herramienta sigue llamándose "Coordinadores" para la gente del PAA; lo que
cambió es la tabla de abajo.

### Tablas por módulo

```sql
-- 004 · Tulas, Marchamos, Revisión de Aulas, Administración
aulas               id, convocatoria_id, sede_id, numero_aula, formula, asignados,
                    folleto_desde/hasta, grupo_embalaje, coord_desde/hasta,
                    personas_por_aula, total_folletos, total_folletos_coord,
                    total_cajas, total_aulas, adecuaciones, turno,
                    coordinador_nombre, coordinador_correo, persona_id,
                    codigo_barras, estado ENUM('disponible','prestado','devuelto')
                    UNIQUE (convocatoria_id, sede_id, numero_aula)
                    UNIQUE (convocatoria_id, codigo_barras)
marchamos           id, aula_id, convocatoria_id, posicion (1|2), codigo
                    UNIQUE (aula_id, posicion) · UNIQUE (convocatoria_id, codigo)
movimientos_tula    id, aula_id, tipo, estado_resultante, responsable, registrado_en
correos_enviados    id, modulo, referencia, destinatario, asunto, exitoso, enviado_en

-- 005 · Activos
activos             id, codigo (UNIQUE), nombre, serie_placa, imei, accesorios,
                    estado_obs, disponible, activo
prestamos           id, boleta (UNIQUE), activo_id, persona_id, funcionario,
                    observacion, estado, prestado_en, devuelto_en
secuencia_boletas   fila única con el último número de boleta

-- 006 · Revisión de Aulas
materiales          id, nombre (UNIQUE), unidad, orden, activo
actas_revision      id, folio (UNIQUE), convocatoria_id, sede_id, aulas_revisadas,
                    revisado_por, observaciones, acta_html, enviado_a, enviado_en
acta_materiales     id, acta_id, material_id, cantidad

-- 007 · Sobresueldos
sobresueldos        id, convocatoria_id, sede_id, descripcion, aulas,
                    coord_regular, coord_adecuacion, aplicador, apoyo, una, ucr,
                    servicio_conserjeria, dia1..dia3, presupuesto, desglose
                    UNIQUE (convocatoria_id, sede_id)
hospedajes          id, convocatoria_id, sede_id, persona_id, lugar, noches, monto

-- 008 · Asignación de Coordinadores
postulantes         id, nombre, identificacion (UNIQUE), correo, telefono,
                    provincia, canton, distrito, en_gam, grado_academico,
                    lugar_trabajo, anios_experiencia, disponible, elegible
asignaciones_sede   id, convocatoria_id, sede_id, postulante_id | persona_id,
                    puntaje, criterio, manual
                    UNIQUE (convocatoria_id, sede_id)
```

### Reglas que ahora impone la base, y que antes vivían en código

| Regla | Antes | Ahora |
| --- | --- | --- |
| Estados de tula válidos | lista blanca comprobada en JS/Apps Script | `ENUM` de la columna |
| Un marchamo no se repite | índice armado en memoria leyendo la hoja entera | `UNIQUE (convocatoria_id, codigo)` |
| Una fila de presupuesto por sede | número de fila del Sheet (`rowIndex`) | `UNIQUE (convocatoria_id, sede_id)` |
| Número de boleta | "última fila de la hoja" | `secuencia_boletas` dentro de la transacción |
| El aula existe antes de tocarla | búsqueda manual fila por fila | llave foránea |

**Decisiones y por qué (módulo 1, siguen vigentes):**

- `areas` se separó porque los nombres de área se repiten y otros módulos van a
  querer referenciarlas sin volver a escribirlas a mano.
- `persona_telefonos` es tabla aparte porque varias personas tienen dos
  números (en el Sheet venían como `"8874-0425 / 4701-0551"`, y el frontend
  tenía que partir esa cadena en cada render).
- `activo` permite dar de baja a alguien sin borrar el registro, importante
  cuando otros módulos referencien su `id`.
- El endpoint ordena **alfabético por nombre dentro de cada área**
  (`ORDER BY a.orden, c.nombre`). El Sheet traía otro orden dentro de tres
  áreas, pero se confirmó con la usuaria que era arbitrario, no jerárquico.
  Alfabético es más predecible para buscar a alguien y no obliga a mantener
  un número de orden a mano cuando entre personal nuevo. **No revertir a
  "orden del Sheet" sin volver a preguntar.**

Datos migrados: **19 personas en 11 áreas** (el contenido completo del Sheet
`15ccOHrLEHLYM2rQAgJ72Eupfv4S3-_2jdm4zenlGE1k`, pestaña "Hoja 1").

---

## 6. Inventario de módulos y orden de migración

Estructura del proyecto original (carpetas con sus archivos):

| Carpeta | Archivos | Estado |
|---|---|---|
| `Coordinadores_Directorio` | `.html` | ✅ **Desplegado** y funcionando |
| `Sedes` | `.html` | ✅ **Migrado** (base + endpoint + página) |
| `Tulas` | `.html` + `.gs` | Base y endpoint listos · falta la página |
| `AsignacionMarchamos` | `.html` | Base y endpoint listos · falta la página |
| `RevisionAulas` | `.html` + `.gs` | Base y endpoint listos · falta la página |
| `Activos` | `.html` + `.gs` | Base y endpoint listos · falta la página |
| `SobreSueldos` | `.html` + `.gs` | Base y endpoint listos · falta la página |
| `AsignacionCoordinadores` | `.html` | Base y endpoint listos · falta la página |
| `Administracion` | `.html` | Endpoint listo · falta la página |
| `CreacionTickets` | `.html` + `.gs` | Pendiente (el `.gs` está vacío: `myFunction()`) |
| `Prueba` | `Prueba.html`, `Prueba_Estudiante.html` + `.gs` | Pendiente |
| `PaginaPrincipal` | `Inicio`, `Contactenos`, `EncabezadoMenu`, `Footer`, `Folletos`, `Publicaciones`, `Rostros`, `PreguntasFrecuentes`, `CalculoPromedio` | Al final (contenido estático) |

**Orden acordado:** primero los catálogos base (Coordinadores → Sedes), luego
las herramientas que dependen de ellos, y de último la página pública.

---

## 7. Lo que ya se aprendió del sistema viejo

### `Coordinadores_Directorio.html` (original)

Puramente de **lectura**. Descargaba el Sheet como CSV público vía
`gviz/tq?tqx=out:csv`, lo parseaba en JS y lo pintaba agrupado por área. No
tenía Apps Script propio: la edición se hacía directamente en el Sheet.
Buscaba las columnas **por nombre** para tolerar reordenamientos.

Columnas del Sheet: `ÁREA`, `PERSONA`, `CORREO`, `TELÉFONO`, `EXTENSIÓN`.

### Apps Script de "Tulas"

✅ **Pregunta resuelta el 2026-08-21: Tulas y Activos son módulos distintos.**
Lo confirma el código, sin necesidad de preguntar:

- **Tulas** usa el Sheet `1KEcA3znFL3SEkZD7j4OK7sN3FcrNpDSuZbjaWHw5IOs`, el
  mismo que leen `AsignacionMarchamos.html`, `RevisionAulas.html` y
  `Administracion.html`. Son aulas de examen por sede y convocatoria; la "tula"
  es el bulto de material que le corresponde a cada aula.
- **Activos** usa otro Sheet (`1Q_EQc1eoTmBdeJ5QDkK2Ri2r8qG9rj-9mr5whu-0b7s`)
  con las hojas `Activos`, `Personal` y `Prestamos`: es préstamo de equipo
  (código, serie/placa, IMEI, accesorios) a funcionarios, con boleta.

El parecido está en el verbo —las dos cosas se prestan y se devuelven— pero son
inventarios distintos, con ciclos de vida distintos. Quedaron como tablas
separadas: `aulas` + `movimientos_tula` por un lado, `activos` + `prestamos`
por el otro.

Lo que hace, y que **hay que preservar** al migrarlo:

1. **`actualizarEstados`** — marca tulas como `disponible` / `prestado`.
   Valida contra lista blanca de estados y que la fila exista (por código de
   barras, o por sede + aula).
2. **`enviarComprobante`** — manda el comprobante por correo. **Nunca acepta el
   destinatario desde el cliente**: recibe el número de sede y resuelve los
   correos contra la base. Esto es deliberado — si aceptara la dirección, sería
   un relay de correo abierto a nombre de la UCR, ideal para phishing.
3. Topes diarios: 80 correos, 3000 escrituras (por las cuotas de Gmail).
4. Sanitiza el HTML del comprobante antes de enviarlo.

Al migrar a PHP: los topes de Gmail dejan de aplicar, pero **la regla de no
aceptar el destinatario del cliente se mantiene**, y hace falta pensar en
autenticación real (hoy no hay ninguna: las páginas son públicas).

### Deuda técnica del sistema viejo a resolver

- **No hay autenticación.** Las páginas son públicas y los endpoints de Apps
  Script también. La protección era solo la contraseña de WordPress al
  entrar. Hay que definir un login real en el sistema nuevo.
- Datos duplicados entre Sheets (los coordinadores aparecen en varios).
- Sin integridad referencial: todo son cadenas de texto sueltas.
- Un proyecto de Apps Script por herramienta, imposible de mantener.

---

## 8. Preguntas abiertas

1. ~~¿"Tulas" corresponde al módulo `Activos`?~~ **Resuelto: son módulos
   distintos, con Sheets e inventarios distintos.** Ver §7.
2. ¿Qué esquema de autenticación se quiere? (login propio, o integración con
   cuenta institucional UCR)
   **Cada vez más urgente:** el sistema nuevo ya tiene endpoints que **escriben**
   (`api/aulas.php`, `api/marchamos.php`, `api/activos.php`,
   `api/sobresueldos.php`) y hoy no hay nada que impida llamarlos. Mientras el
   dominio no exista en el DNS nadie los alcanza, pero **esto hay que resolverlo
   antes de que el sitio se publique**, no después.
3. ¿Cuándo hace falta el panel CRUD? Se acordó **solo lectura por ahora**:
   los datos se editan por phpMyAdmin mientras se migran los módulos.
4. ~~¿Ruta del directorio público del hosting?~~ **Resuelto: `~/public_html/web`.**
5. ¿Cuándo publica el Centro de Informática el dominio `gestionpaa.ucr.ac.cr`?
   Hasta que eso pase no se puede verificar el bloqueo de las carpetas privadas.

---

## 9. Cómo seguir

**El esquema y los endpoints de todos los módulos ya están escritos.** Lo que
queda es portar las páginas, de a una. Para cada una:

1. Copiar el `.html` original a `<Modulo>/index.php`.
2. Cambiar las constantes de datos (`SHEET_ID`, `CSV_URL`, `APPS_SCRIPT_URL`)
   por la ruta del endpoint: `../api/<modulo>.php`.
3. Reemplazar el parseo de CSV por `await respuesta.json()`. El endpoint
   devuelve los campos **con los mismos nombres** que usaba el JS, justamente
   para que el render no cambie.
4. Los POST al Apps Script pasan a ser POST al endpoint con `accion=…`.
5. Probar la página entera y recién entonces marcarla `disponible` en
   `index.php`.
6. Anotar en `CHANGELOG.md` y desplegar (`DESPLIEGUE.md`).

Orden sugerido, por dependencias y por uso: **Tulas → Asignación de Marchamos →
Revisión de Aulas → Activos → SobreSueldos → Asignación de Coordinadores →
Administración**. Los cuatro primeros comparten la tabla `aulas`, así que
conviene hacerlos seguidos mientras el modelo está fresco.

Para un módulo **nuevo** (Creación de Tickets, Prueba), el flujo original sigue
valiendo:

1. La usuaria pasa el `.html` actual + los datos del Sheet + el `.gs` si tiene.
2. Se diseña el esquema, reutilizando tablas núcleo existentes cuando aplique,
   y se muestra la propuesta **antes** de crear nada.
3. Se escribe `schema/00N_modulo.sql` (solo DDL; los datos van por el importador).
4. Se escribe `api/modulo.php` y `Modulo/index.php`.
5. Se despliega según `DESPLIEGUE.md`.
