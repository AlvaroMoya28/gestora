# Despliegue — esquema y backend de los ocho módulos

Pasos en orden para dejar corriendo en el servidor lo que se agregó el
2026-08-21. Leelo completo antes de empezar: hay un paso que **renombra una
tabla existente**.

Todo se corre conectada a la **VPN GlobalProtect**.

> **⚠️ Dos terminales, no una**
>
> Cada bloque de comandos está marcado con dónde va:
>
> * 💻 **En tu máquina** — PowerShell de Windows. El prompt dice algo como
>   `PS C:\Users\PAA-ADIC\Desktop\paa>`.
> * 🖥️ **En el servidor** — la sesión SSH. El prompt dice
>   `gestionpaa@acceso01:~$`.
>
> Confundirlas da `No existe el fichero o el directorio`, porque la ruta local
> no existe en el servidor. Conviene tener las dos ventanas abiertas a la vez.

---

## Resumen de lo que va a pasar

1. Se respalda la base (automático, pero conviene uno manual antes).
2. Se aplican 7 migraciones nuevas. Una de ellas renombra `coordinadores` a
   `personas` — los datos se conservan, pero el endpoint viejo dejaría de
   funcionar si no se suben también los archivos PHP.
3. Se importan los datos de los Google Sheets.
4. Se marca la convocatoria activa.
5. Se verifica.

> **El orden importa:** primero los archivos, después las migraciones. Si se
> aplica `003_personas.sql` con el `api/coordinadores.php` viejo arriba, el
> directorio queda caído hasta que se suban los archivos nuevos.

---

## Paso 0 — Exportar los Sheets a CSV

De cada Sheet: **Archivo → Descargar → Valores separados por comas (.csv)**.

| Guardar como | Sheet | Pestaña |
| --- | --- | --- |
| `sedes.csv` | `171wt_clBFunAZCJnta3ke3IOL5ihQ3lsfqwm0-5RS90` | Hoja1 |
| `aulas.csv` | `1KEcA3znFL3SEkZD7j4OK7sN3FcrNpDSuZbjaWHw5IOs` | primera (gid=0) |
| `activos.csv` | `1Q_EQc1eoTmBdeJ5QDkK2Ri2r8qG9rj-9mr5whu-0b7s` | Activos |
| `personal.csv` | `1Q_EQc1eoTmBdeJ5QDkK2Ri2r8qG9rj-9mr5whu-0b7s` | Personal |
| `prestamos.csv` | `1Q_EQc1eoTmBdeJ5QDkK2Ri2r8qG9rj-9mr5whu-0b7s` | Prestamos *(opcional, es historial)* |
| `materiales.csv` | `15ccOHrLEHLYM2rQAgJ72Eupfv4S3-_2jdm4zenlGE1k` | Materiales |
| `sobresueldos.csv` | `1y8ledjEgvovukaSi81Vy3pPmrUb_nt2Jelyrig4pXwY` | Hoja 1 |
| `hospedajes.csv` | `1G_jrXKdI8KrfjANZxf86pwatBgMWs_PS-c61vGG_h_g` | primera |
| `postulantes.csv` | el XLSX que se sube hoy a Asignación de Coordinadores | — |

No hay que tocar los encabezados: el importador los normaliza y acepta las
variantes que traen los Sheets (`Cordinador`/`Coordinador`, `SEDE`/`sede`,
`TOTAL FOLLETOS COORD.`…).

---

## Paso 1 — Respaldo manual

🖥️ **En el servidor:**

```bash
ssh gestionpaa@172.16.49.116
cd ~/public_html/web
php scripts/respaldar_bd.php "antes de migraciones 002-008"
```

Anotá el nombre del archivo que imprime: es a donde se vuelve si algo sale mal.

---

## Paso 2 — Subir los archivos

Va por paquete, que es como se despliega en este proyecto (la máquina de
desarrollo no tiene `rsync`).

💻 **En tu máquina**, desde la carpeta que contiene `PAA/`:

```powershell
cd C:\Users\PAA-ADIC\Desktop\paa

# Arma el paquete. --exclude=config.php es importante: las credenciales del
# servidor no deben viajar ni sobrescribirse.
tar --exclude=.git --exclude=config.php -czf paa_sitio.tar.gz PAA

scp paa_sitio.tar.gz gestionpaa@172.16.49.116:~/
```

🖥️ **En el servidor**, descomprimir y copiar sobre la raíz web:

```bash
cd ~
tar -xzf paa_sitio.tar.gz
cp -r ~/PAA/. ~/public_html/web/    # el /. copia también los .htaccess
```

> `cp` sobrescribe los archivos que cambiaron y deja intactos
> `includes/config.php` y los dumps de `backups/`, que no vienen en el paquete.

Comprobar que ningún archivo PHP tenga error de sintaxis **antes** de migrar:

```bash
cd ~/public_html/web
find api includes scripts -name '*.php' -exec php -l {} \; | grep -v "No syntax errors"
```

Si no imprime nada, están todos bien.

### Los CSV van aparte

Traen datos personales, así que no van a la raíz web sino a una carpeta suelta
del home.

💻 **En tu máquina** (cuando tengas los CSV del paso 0):

```powershell
scp C:\ruta\a\los\csv\*.csv gestionpaa@172.16.49.116:~/csv_import/
```

🖥️ **En el servidor**, creá la carpeta antes:

```bash
mkdir -p ~/csv_import
```

---

> **Los pasos 3 a 7 se corren todos 🖥️ en el servidor**, desde
> `~/public_html/web`. El paso 8 vuelve a 💻 tu máquina, porque `acceso01` no
> tiene DNS y no puede consultar el sitio por HTTP.

## Paso 3 — Agregar el remitente de correo a la configuración

```bash
nano includes/config.php
```

Agregar antes del `];` final:

```php
    'correo_remitente' => 'PAA UCR <no-responder@gestionpaa.ucr.ac.cr>',
```

> Si el servidor no tiene un MTA que acepte ese dominio, `mail()` va a fallar en
> silencio. Se nota en la tabla `correos_enviados`: las filas quedan con
> `exitoso = 0`. En ese caso hay que pedirle al Centro de Informática el relay
> SMTP institucional.

---

## Paso 4 — Aplicar las migraciones

```bash
php scripts/migrar.php --estado     # debe listar 002 a 008 como pendientes
php scripts/migrar.php              # respalda solo, aplica, y vuelve a respaldar
```

Si alguna falla, el script dice exactamente con qué comando volver atrás.

Comprobar el renombre:

```bash
mysql --defaults-file=<(echo) -e "SHOW TABLES" 2>/dev/null || \
php -r 'require "includes/db.php"; foreach (db()->query("SHOW TABLES") as $t) echo implode("",$t),"\n";'
```

Tiene que aparecer `personas` (no `coordinadores`) y las tablas nuevas.

**Verificar que no se perdió nadie:**

```bash
php -r 'require "includes/db.php";
  echo "personas: ", db()->query("SELECT COUNT(*) FROM personas")->fetchColumn(), "\n";
  echo "teléfonos: ", db()->query("SELECT COUNT(*) FROM persona_telefonos")->fetchColumn(), "\n";'
```

Debe decir **19** y **22**. Si no, restaurá el respaldo del paso 1 y pará acá.

---

## Paso 5 — Importar los datos

El orden importa: sedes y personal primero, porque los demás los referencian.

```bash
cd ~/public_html/web

php scripts/importar_csv.php sedes     ~/csv_import/sedes.csv
php scripts/importar_csv.php personal  ~/csv_import/personal.csv
php scripts/importar_csv.php materiales ~/csv_import/materiales.csv
php scripts/importar_csv.php activos   ~/csv_import/activos.csv

# Los que dependen de una convocatoria: usá el nombre real que trae el Sheet
php scripts/importar_csv.php aulas        ~/csv_import/aulas.csv        --convocatoria="PAA 2026"
php scripts/importar_csv.php sobresueldos ~/csv_import/sobresueldos.csv --convocatoria="PAA 2026"
php scripts/importar_csv.php hospedajes   ~/csv_import/hospedajes.csv   --convocatoria="PAA 2026"
php scripts/importar_csv.php postulantes  ~/csv_import/postulantes.csv  --convocatoria="PAA 2026"

# Opcional, al final: necesita que los activos ya estén importados
php scripts/importar_csv.php prestamos ~/csv_import/prestamos.csv
```

Cada corrida imprime **insertados / actualizados / omitidos**. Los omitidos son
filas sin los datos mínimos (por ejemplo, sin código de sede): revisalos en el
Sheet si el número no cuadra.

> Si aparece `⚠ marchamo duplicado en el Sheet, omitido`, **no es un error del
> importador**: es un código de marchamo puesto en dos aulas distintas. Antes
> pasaba inadvertido; ahora la base no lo permite. Hay que corregirlo en el
> Sheet y volver a correr la importación (es idempotente, se puede repetir).

Se puede volver a correr cualquier importación cuantas veces haga falta:
actualiza en vez de duplicar.

---

## Paso 6 — Marcar la convocatoria activa

Los endpoints usan la convocatoria marcada como activa cuando la página no pide
una en concreto.

```bash
php -r 'require "includes/db.php";
  db()->exec("UPDATE convocatorias SET activa = 0");
  db()->prepare("UPDATE convocatorias SET activa = 1 WHERE nombre = ?")->execute(["PAA 2026"]);
  foreach (db()->query("SELECT id, nombre, activa FROM convocatorias") as $c)
      echo $c["id"], " ", $c["nombre"], " activa=", $c["activa"], "\n";'
```

---

## Paso 7 — Borrar los CSV del servidor

Traen nombres, correos y teléfonos. No tienen por qué quedarse ahí.

```bash
rm -rf ~/csv_import
```

---

## Paso 8 — Verificar

Desde una máquina con VPN (no desde `acceso01`, que no tiene DNS):

```bash
php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
```

Y a mano, en el navegador:

| Ruta | Qué se espera |
| --- | --- |
| `/` | la portada, con Sedes y Coordinadores clicables |
| `/Coordinadores_Directorio/` | las 19 personas agrupadas por área (comprueba que el renombre no rompió nada) |
| `/Sedes/` | el catálogo de sedes |
| `/api/sedes.php` | JSON con `"success": true` |
| `/api/aulas.php` | JSON con las tulas de la convocatoria activa |
| `/api/administracion.php?accion=estado` | conteo de filas por tabla |
| `/includes/config.php` | **403 o 404** — si muestra contenido, hay un problema serio |
| `/backups/` | **403 o 404** |
| `/schema/002_sedes.sql` | **403 o 404** |

Mientras el dominio no exista en el DNS, esto solo se puede probar cuando el
Centro de Informática lo publique. Ver el bloqueante en `CONTEXTO.md` §3.

---

## Si algo sale mal

```bash
php scripts/restaurar_bd.php backups/gestionpaa_<el-del-paso-1>.sql
```

Eso deja la base como estaba antes de todo. Los archivos PHP nuevos no molestan
con el esquema viejo salvo `api/coordinadores.php`, que consulta `personas`: si
se revierte la base, hay que volver a poner la versión anterior de ese archivo
(está en el historial de git del proyecto).

---

## Después del despliegue

Las páginas de Tulas, Marchamos, Revisión de Aulas, Activos, SobreSueldos,
Asignación de Coordinadores y Administración **siguen apuntando a Google Sheets
y a los Apps Script**. Eso es a propósito: mientras no se porten, hay que
mantener los Sheets como fuente y **volver a importar antes de cada uso serio**
de los datos migrados, o las dos copias se separan.

El orden sugerido para portarlas está en `CONTEXTO.md` §6. El procedimiento es
el mismo que se usó con Sedes:

1. Copiar el `.html` original a `<Modulo>/index.php`.
2. Reemplazar las constantes `CSV_URL` / `SHEET_ID` / `APPS_SCRIPT_URL` por la
   ruta del endpoint (`../api/<modulo>.php`).
3. Cambiar el parseo de CSV por `await respuesta.json()`.
4. Ajustar los envíos: donde antes iba un POST al Apps Script, ahora va el
   mismo POST al endpoint con `accion=…`.
5. Probar la página completa antes de marcarla como disponible en `index.php`.
