# Respaldos de la base de datos

Los archivos `gestionpaa_AAAA-MM-DD_HHMMSS.sql` de esta carpeta son dumps
completos de la base. `REGISTRO.md` lleva la lista con el motivo de cada uno.

## Crear un respaldo

```bash
php scripts/respaldar_bd.php "motivo del respaldo"
```

## Restaurar

```bash
php scripts/restaurar_bd.php backups/gestionpaa_2026-08-20_143000.sql
```

Pide confirmación y respalda el estado actual antes de sobrescribir.

## Cuándo se respalda

- Automáticamente, antes y después de cada `php scripts/migrar.php`
- Automáticamente, antes de cada restauración
- A mano, antes de cualquier cambio manual de datos en phpMyAdmin

## ⚠️ Estos archivos son sensibles

Un dump contiene **todos** los datos del sistema: nombres, correos, teléfonos
del personal. La carpeta está bloqueada por `.htaccess`, pero eso solo funciona
en Apache con `AllowOverride` habilitado.

Después de cada despliegue, confirmá que el bloqueo sirve:

```bash
php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
```

Si algún respaldo responde 200, movelos fuera de la carpeta web de inmediato.

## Respaldo automático diario (opcional)

En el servidor, `crontab -e`:

```
0 2 * * * cd /ruta/a/PAA && php scripts/respaldar_bd.php "automático diario" >> backups/cron.log 2>&1
```
