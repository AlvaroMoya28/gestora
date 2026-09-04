<?php
/**
 * Elimina las sedes dadas de baja y todo lo que cuelga de ellas.
 *
 *   php scripts/limpiar_sedes_de_baja.php              → muestra qué se borraría
 *   php scripts/limpiar_sedes_de_baja.php --confirmar  → lo borra de verdad
 *
 * POR QUÉ EXISTE
 * Al reemplazar el catálogo de sedes (importar_csv.php --reemplazar), las que
 * tienen datos asociados no se pueden borrar: hacerlo arrastraría aulas,
 * tickets y actas. Por eso quedan dadas de baja (activo = 0), invisibles para
 * el sistema pero presentes en la tabla.
 *
 * Eso está bien cuando esos datos importan. Pero si vienen de una importación
 * anterior que ya no sirve —el caso típico: se cargó una base de aulas vieja y
 * después llegó la definitiva— esas filas solo estorban: aparecen sumadas en
 * los contadores y en los listados de la convocatoria.
 *
 * Este script hace esa limpieza en el orden correcto, de adentro hacia afuera,
 * para no chocar con las llaves foráneas.
 *
 * SIEMPRE respalda antes de borrar.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/db.php';

$confirmar = in_array('--confirmar', $argv, true);
$pdo = db();

// ------------------------------------------------------------------
// Qué hay
// ------------------------------------------------------------------
$sedes = $pdo->query('SELECT id, codigo, nombre FROM sedes WHERE activo = 0')->fetchAll();

if ($sedes === []) {
    exit("No hay sedes dadas de baja. Nada que limpiar.\n");
}

$ids = array_map(static fn(array $s): int => (int) $s['id'], $sedes);
$marcadores = implode(',', array_fill(0, count($ids), '?'));

/** Cuenta las filas de una tabla que apuntan a esas sedes. */
$contar = static function (string $tabla) use ($pdo, $marcadores, $ids): int {
    try {
        // El nombre de la tabla sale de la lista fija de abajo, nunca de la
        // línea de comandos: no hay forma de inyectar nada acá.
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tabla} WHERE sede_id IN ({$marcadores})");
        $stmt->execute($ids);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;   // la tabla todavía no existe
    }
};

$dependientes = [
    'aulas'             => $contar('aulas'),
    'tickets'           => $contar('tickets'),
    'actas_revision'    => $contar('actas_revision'),
    'sobresueldos'      => $contar('sobresueldos'),
    'hospedajes'        => $contar('hospedajes'),
    'asignaciones_sede' => $contar('asignaciones_sede'),
];

echo "Sedes dadas de baja: " . count($sedes) . "\n\n";
echo "Datos que dependen de ellas y también se borrarían:\n";
foreach ($dependientes as $tabla => $cuantas) {
    printf("  %-20s %6d\n", $tabla, $cuantas);
}

// Los marchamos y los movimientos cuelgan de las aulas, así que se van con
// ellas automáticamente (ON DELETE CASCADE). Se cuentan para que quede claro.
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM marchamos m
        INNER JOIN aulas a ON a.id = m.aula_id
        WHERE a.sede_id IN ({$marcadores})
    ");
    $stmt->execute($ids);
    $marchamos = (int) $stmt->fetchColumn();
    if ($marchamos > 0) {
        printf("  %-20s %6d  (se van con las aulas)\n", 'marchamos', $marchamos);
    }
} catch (Throwable $e) { /* tabla ausente */ }

echo "\nPrimeras sedes de la lista:\n";
foreach (array_slice($sedes, 0, 8) as $s) {
    echo "  {$s['codigo']} — {$s['nombre']}\n";
}
if (count($sedes) > 8) {
    echo "  ... y " . (count($sedes) - 8) . " más\n";
}

if (!$confirmar) {
    echo "\n";
    echo "Esto fue solo una vista previa: NO se borró nada.\n";
    echo "Para hacerlo de verdad:\n\n";
    echo "  php scripts/respaldar_bd.php \"antes de limpiar sedes de baja\"\n";
    echo "  php scripts/limpiar_sedes_de_baja.php --confirmar\n";
    exit(0);
}

// ------------------------------------------------------------------
// Borrar, de adentro hacia afuera
// ------------------------------------------------------------------
echo "\nBorrando...\n";

$pdo->beginTransaction();

try {
    // Orden: primero lo que apunta a las sedes, al final las sedes.
    foreach (['tickets', 'acta_materiales_huerfanos', 'actas_revision',
              'sobresueldos', 'hospedajes', 'asignaciones_sede', 'aulas'] as $tabla) {

        if ($tabla === 'acta_materiales_huerfanos') {
            // Los materiales de un acta cuelgan del acta (ON DELETE CASCADE),
            // así que no hace falta tocarlos aparte.
            continue;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM {$tabla} WHERE sede_id IN ({$marcadores})");
            $stmt->execute($ids);
            if ($stmt->rowCount() > 0) {
                printf("  %-20s %6d borradas\n", $tabla, $stmt->rowCount());
            }
        } catch (Throwable $e) {
            echo "  {$tabla}: se omite ({$e->getMessage()})\n";
        }
    }

    $stmt = $pdo->prepare("DELETE FROM sedes WHERE id IN ({$marcadores})");
    $stmt->execute($ids);
    printf("  %-20s %6d borradas\n", 'sedes', $stmt->rowCount());

    $pdo->commit();

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "No se borró nada: la transacción se revirtió completa.\n";
    exit(1);
}

// ------------------------------------------------------------------
// Cómo quedó
// ------------------------------------------------------------------
echo "\n=== Estado final ===\n";
printf("  sedes activas: %d\n", (int) $pdo->query('SELECT COUNT(*) FROM sedes WHERE activo = 1')->fetchColumn());
printf("  sedes de baja: %d\n", (int) $pdo->query('SELECT COUNT(*) FROM sedes WHERE activo = 0')->fetchColumn());
printf("  aulas:         %d\n", (int) $pdo->query('SELECT COUNT(*) FROM aulas')->fetchColumn());
echo "\nListo.\n";
