<?php
/**
 * Ver y cambiar la convocatoria activa.
 *
 *   php scripts/convocatoria.php                          → lista las convocatorias
 *   php scripts/convocatoria.php --activar="PAA 2026"     → la marca como activa
 *   php scripts/convocatoria.php --activar="PAA 2026" --fecha=2026-10-03
 *
 * POR QUÉ EXISTE
 * Las páginas muestran la convocatoria marcada como activa. Si ninguna lo
 * está, el sistema responde "no hay convocatoria activa" y las pantallas se
 * ven vacías — un módulo entero caído por una bandera en cero.
 *
 * Cambiar esa bandera a mano por phpMyAdmin funciona, pero es fácil dejar dos
 * activas a la vez (y entonces cuál se muestra depende del orden de la
 * consulta). Acá se apagan todas antes de encender la que toca.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

require_once dirname(__DIR__) . '/includes/db.php';

$activar = null;
$fecha   = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--activar=')) {
        $activar = trim(substr($arg, strlen('--activar=')), " \"'");
    }
    if (str_starts_with($arg, '--fecha=')) {
        $fecha = trim(substr($arg, strlen('--fecha=')), " \"'");
    }
}

$pdo = db();

// ------------------------------------------------------------------
// Activar
// ------------------------------------------------------------------
if ($activar !== null) {
    $stmt = $pdo->prepare('SELECT id FROM convocatorias WHERE nombre = ?');
    $stmt->execute([$activar]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        echo "No existe ninguna convocatoria llamada \"{$activar}\".\n\n";
        echo "Las que hay son:\n";
        foreach ($pdo->query('SELECT nombre FROM convocatorias ORDER BY id') as $c) {
            echo "  · {$c['nombre']}\n";
        }
        exit(1);
    }

    if ($fecha !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        exit("La fecha debe ir como AAAA-MM-DD, por ejemplo --fecha=2026-10-03\n");
    }

    $pdo->beginTransaction();

    // Primero se apagan todas: así nunca quedan dos activas.
    $pdo->exec('UPDATE convocatorias SET activa = 0');
    $pdo->prepare('UPDATE convocatorias SET activa = 1 WHERE id = ?')->execute([(int) $id]);

    if ($fecha !== null) {
        $pdo->prepare('UPDATE convocatorias SET fecha_aplicacion = ? WHERE id = ?')
            ->execute([$fecha, (int) $id]);
    }

    $pdo->commit();

    echo "Convocatoria activa: {$activar}\n\n";
}

// ------------------------------------------------------------------
// Listar (siempre, para poder comprobar el resultado)
// ------------------------------------------------------------------
$filas = $pdo->query("
    SELECT c.id, c.nombre, c.fecha_aplicacion, c.activa,
           (SELECT COUNT(*) FROM aulas a WHERE a.convocatoria_id = c.id) AS aulas,
           (SELECT COUNT(DISTINCT a.sede_id) FROM aulas a WHERE a.convocatoria_id = c.id) AS sedes
    FROM convocatorias c
    ORDER BY c.id
")->fetchAll();

if ($filas === []) {
    echo "No hay ninguna convocatoria cargada.\n";
    echo "Se crean al importar aulas:\n";
    echo "  php scripts/importar_csv.php aulas archivo.csv --convocatoria=\"PAA 2026\"\n";
    exit(0);
}

printf("%-4s %-28s %-12s %-8s %8s %7s\n", 'ID', 'NOMBRE', 'FECHA', 'ACTIVA', 'AULAS', 'SEDES');
echo str_repeat('-', 74) . "\n";

foreach ($filas as $f) {
    printf(
        "%-4s %-28s %-12s %-8s %8s %7s\n",
        $f['id'],
        mb_substr($f['nombre'], 0, 28),
        $f['fecha_aplicacion'] ?? '—',
        $f['activa'] ? '  SÍ' : '  no',
        $f['aulas'],
        $f['sedes']
    );
}

$activas = array_filter($filas, static fn(array $f): bool => (int) $f['activa'] === 1);

echo "\n";
if ($activas === []) {
    echo "⚠️  Ninguna está activa: las páginas van a responder\n";
    echo "    \"no hay convocatoria activa\". Activá una con:\n";
    echo "      php scripts/convocatoria.php --activar=\"" . $filas[0]['nombre'] . "\"\n";
} else {
    echo "Todo en orden.\n";
}
