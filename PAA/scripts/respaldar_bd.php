<?php
/**
 * Respaldo de la base de datos gestionpaa.
 *
 *   php scripts/respaldar_bd.php ["motivo del respaldo"]
 *
 * Guarda un dump completo con fecha y hora en backups/, más una entrada en
 * backups/REGISTRO.md diciendo por qué se hizo.
 *
 * REGLA DEL PROYECTO: se corre ANTES de aplicar cualquier migración y DESPUÉS
 * de cargar datos nuevos. Un respaldo que nadie hizo no sirve de nada.
 *
 * La contraseña nunca va en la línea de comandos (sería visible con `ps`):
 * se pasa por un archivo temporal de opciones con permisos 0600.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz       = dirname(__DIR__);
$rutaConfig = $raiz . '/includes/config.php';
$dirBackups = $raiz . '/backups';

if (!is_file($rutaConfig)) {
    exit("ERROR: falta includes/config.php. Copiá config.example.php y completá las credenciales.\n");
}

require_once $raiz . '/includes/opciones_cliente.php';

$cfg    = require $rutaConfig;
$motivo = $argv[1] ?? 'respaldo manual';

if (!is_dir($dirBackups) && !mkdir($dirBackups, 0750, true)) {
    exit("ERROR: no se pudo crear la carpeta backups/\n");
}

// El sello es por segundo, y migrar.php respalda dos veces (antes y después)
// casi al mismo tiempo. Sin este sufijo, el segundo respaldo sobrescribía al
// primero — justo el "antes" que sirve para revertir.
$sello   = date('Y-m-d_His');
$destino = sprintf('%s/gestionpaa_%s.sql', $dirBackups, $sello);

$n = 2;
while (file_exists($destino)) {
    $destino = sprintf('%s/gestionpaa_%s-%d.sql', $dirBackups, $sello, $n);
    $n++;
}

$nombreArchivo = basename($destino);

// Archivo de opciones temporal: mysqldump lee de aquí la clave, así no queda
// expuesta en la lista de procesos del servidor.
$archivoOpciones = crear_opciones_cliente($cfg, 'paadump');

$comando = sprintf(
    'mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers --events '
    . '--default-character-set=utf8mb4 %s 2>&1',
    escapeshellarg($archivoOpciones),
    escapeshellarg($cfg['db_name'])
);

echo "Respaldando {$cfg['db_name']}...\n";

exec($comando, $salida, $codigo);
unlink($archivoOpciones);   // se borra pase lo que pase

if ($codigo !== 0) {
    echo "ERROR: mysqldump falló (código {$codigo}).\n";
    echo implode("\n", array_slice($salida, 0, 10)) . "\n";
    exit(1);
}

$contenido = "-- Respaldo de {$cfg['db_name']}\n"
    . '-- Fecha: ' . date('Y-m-d H:i:s') . "\n"
    . "-- Motivo: {$motivo}\n\n"
    . implode("\n", $salida) . "\n";

file_put_contents($destino, $contenido);
chmod($destino, 0640);

$kb = round(filesize($destino) / 1024, 1);
echo "OK  →  backups/{$nombreArchivo}  ({$kb} KB)\n";

// Registro legible de qué se respaldó y por qué
$registro = $dirBackups . '/REGISTRO.md';
if (!is_file($registro)) {
    file_put_contents($registro, "# Registro de respaldos\n\n| Fecha | Archivo | Tamaño | Motivo |\n|---|---|---|---|\n");
}
file_put_contents($registro, sprintf(
    "| %s | `%s` | %s KB | %s |\n",
    date('Y-m-d H:i'),
    $nombreArchivo,
    $kb,
    str_replace('|', '\\|', $motivo)
), FILE_APPEND);

echo "Registrado en backups/REGISTRO.md\n";
