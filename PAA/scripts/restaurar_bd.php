<?php
/**
 * Restaura la base de datos desde un respaldo.
 *
 *   php scripts/restaurar_bd.php backups/gestionpaa_2026-08-20_143000.sql
 *
 * ⚠️ SOBRESCRIBE los datos actuales. Antes de restaurar, el script hace un
 * respaldo automático del estado presente, para que un error de restauración
 * no sea un camino de una sola vía.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz       = dirname(__DIR__);
$rutaConfig = $raiz . '/includes/config.php';

if (!is_file($rutaConfig)) {
    exit("ERROR: falta includes/config.php.\n");
}

$archivo = $argv[1] ?? '';
if ($archivo === '') {
    exit("Uso: php scripts/restaurar_bd.php <archivo.sql>\n");
}
if (!is_file($archivo)) {
    exit("ERROR: no existe el archivo {$archivo}\n");
}

require_once $raiz . '/includes/opciones_cliente.php';

$cfg = require $rutaConfig;

echo "Se va a RESTAURAR '{$cfg['db_name']}' desde:\n  {$archivo}\n";
echo "Esto reemplaza los datos actuales. Escribí SI para continuar: ";

$respuesta = trim((string) fgets(STDIN));
if ($respuesta !== 'SI') {
    exit("Cancelado.\n");
}

// Red de seguridad: respaldo del estado actual antes de pisarlo
echo "\nRespaldando el estado actual antes de restaurar...\n";
passthru(sprintf(
    'php %s %s',
    escapeshellarg(__DIR__ . '/respaldar_bd.php'),
    escapeshellarg('previo a restaurar desde ' . basename($archivo))
), $codigoRespaldo);

if ($codigoRespaldo !== 0) {
    exit("\nEl respaldo previo falló. No se restaura nada.\n");
}

$archivoOpciones = crear_opciones_cliente($cfg, 'paarest');

$comando = sprintf(
    'mysql --defaults-extra-file=%s --default-character-set=utf8mb4 %s < %s 2>&1',
    escapeshellarg($archivoOpciones),
    escapeshellarg($cfg['db_name']),
    escapeshellarg($archivo)
);

echo "\nRestaurando...\n";
exec($comando, $salida, $codigo);
unlink($archivoOpciones);

if ($codigo !== 0) {
    echo "ERROR al restaurar (código {$codigo}):\n";
    echo implode("\n", array_slice($salida, 0, 10)) . "\n";
    exit(1);
}

echo "OK  →  base de datos restaurada desde " . basename($archivo) . "\n";
