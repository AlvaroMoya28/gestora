<?php
/**
 * Aplica las migraciones pendientes de schema/ en orden numérico.
 *
 *   php scripts/migrar.php           → aplica lo pendiente
 *   php scripts/migrar.php --estado  → solo muestra qué falta, no toca nada
 *
 * Lleva la cuenta en la tabla `migraciones`, así que una migración ya aplicada
 * nunca se vuelve a correr. Esto es lo que permite que el servidor y cualquier
 * copia local terminen con exactamente el mismo esquema.
 *
 * Antes de aplicar cualquier cosa hace un respaldo automático.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/db.php';

$soloEstado = in_array('--estado', $argv, true);
$dirSchema  = $raiz . '/schema';

$pdo = db();

// Bitácora de migraciones. Se crea sola la primera vez.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migraciones (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        archivo     VARCHAR(255) NOT NULL UNIQUE,
        aplicada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$aplicadas = $pdo->query('SELECT archivo FROM migraciones')->fetchAll(PDO::FETCH_COLUMN);

$archivos = glob($dirSchema . '/*.sql') ?: [];
sort($archivos);

$pendientes = array_values(array_filter(
    $archivos,
    static fn(string $ruta): bool => !in_array(basename($ruta), $aplicadas, true)
));

echo "Migraciones aplicadas: " . count($aplicadas) . "\n";
echo "Migraciones pendientes: " . count($pendientes) . "\n";

foreach ($pendientes as $ruta) {
    echo "  · " . basename($ruta) . "\n";
}

if ($soloEstado || count($pendientes) === 0) {
    if (count($pendientes) === 0) {
        echo "\nLa base está al día.\n";
    }
    exit(0);
}

// Respaldo antes de tocar el esquema
echo "\nRespaldando antes de migrar...\n";
passthru(sprintf(
    'php %s %s',
    escapeshellarg(__DIR__ . '/respaldar_bd.php'),
    escapeshellarg('previo a aplicar ' . count($pendientes) . ' migración(es)')
), $codigoRespaldo);

if ($codigoRespaldo !== 0) {
    exit("\nEl respaldo falló. No se aplica ninguna migración.\n");
}

// Se guarda cuál fue el respaldo recién creado, para poder nombrarlo exacto
// si una migración falla y hay que revertir.
$respaldos = glob($raiz . '/backups/gestionpaa_*.sql') ?: [];
sort($respaldos);
$destinoRespaldo = $respaldos
    ? 'backups/' . basename(end($respaldos))
    : null;

foreach ($pendientes as $ruta) {
    $nombre = basename($ruta);
    echo "\nAplicando {$nombre}...\n";

    $sql = file_get_contents($ruta);
    if ($sql === false) {
        exit("ERROR: no se pudo leer {$nombre}\n");
    }

    try {
        // Sin transacción a propósito: MySQL/MariaDB hace COMMIT implícito en
        // cada CREATE/ALTER/DROP, así que envolver una migración con DDL en una
        // transacción no la hace atómica — solo provoca un error al cerrarla.
        // La red de seguridad real es el respaldo que se hizo arriba.
        $pdo->exec($sql);

        $stmt = $pdo->prepare('INSERT INTO migraciones (archivo) VALUES (?)');
        $stmt->execute([$nombre]);

        echo "OK  →  {$nombre}\n";

    } catch (Throwable $e) {
        echo "ERROR en {$nombre}: " . $e->getMessage() . "\n";
        echo "Se detuvo la migración. La base puede haber quedado a medias.\n";
        echo "Para volver al estado previo:\n";
        echo "  php scripts/restaurar_bd.php " . ($destinoRespaldo ?? 'backups/<el-último>.sql') . "\n";
        exit(1);
    }
}

// Respaldo del resultado, para tener el "después" y no solo el "antes"
echo "\nRespaldando el resultado...\n";
passthru(sprintf(
    'php %s %s',
    escapeshellarg(__DIR__ . '/respaldar_bd.php'),
    escapeshellarg('después de aplicar migraciones')
));

echo "\nListo.\n";
