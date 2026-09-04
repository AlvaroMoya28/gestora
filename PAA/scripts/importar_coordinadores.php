<?php
/**
 * Carga las personas coordinadoras de cada sede.
 *
 *   php scripts/importar_coordinadores.php archivo.xls            → muestra qué haría
 *   php scripts/importar_coordinadores.php archivo.xls --aplicar  → lo escribe
 *
 * SOBRE EL ARCHIVO
 * El «.xls» que exporta el sistema de coordinadores NO es un Excel: es una
 * tabla HTML con esa extensión. Se lee tal cual, sin convertir nada, porque
 * pedirle a alguien que lo abra y lo guarde como CSV agrega un paso donde se
 * pierden los acentos y se reformatean los códigos de sede.
 *
 * Columnas esperadas:
 *   Sede | Descripción | Fecha | Convocatoria | Turno | Aulas | Persona coordinadora | Correo
 *
 * DÓNDE SE GUARDA
 * En `aulas.coordinador_nombre` y `aulas.coordinador_correo`, para todas las
 * aulas de la sede. El coordinador es de la sede, no del aula, pero el dato
 * vive en aulas porque así venía del Sheet original y así lo leen los módulos.
 *
 * NO se crean filas en `personas`. Ese es el directorio del personal del PAA y
 * estas son, en su mayoría, personas de otras dependencias contratadas para la
 * aplicación: meterlas ahí las pondría en Funcionarios PAA y en los selectores
 * de préstamo de equipo, donde no corresponden. Si el correo YA existe en el
 * directorio, se enlaza `persona_id`, que es lo que hace que el sistema use el
 * nombre y el correo actualizados de esa persona en vez de la copia del
 * archivo.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/peticion.php';

$argumentos = array_slice($argv, 1);
$aplicar    = in_array('--aplicar', $argumentos, true);
$ruta       = null;

foreach ($argumentos as $a) {
    if (!str_starts_with($a, '--')) {
        $ruta = $a;
        break;
    }
}

if ($ruta === null) {
    exit("Uso: php scripts/importar_coordinadores.php archivo.xls [--aplicar]\n");
}

if (!is_file($ruta)) {
    exit("No existe el archivo: {$ruta}\n");
}

// ------------------------------------------------------------------
// Lectura
// ------------------------------------------------------------------

$filas = leer_tabla($ruta);

if ($filas === []) {
    exit("No se encontró ninguna fila de datos en el archivo.\n");
}

echo "=== Coordinadores por sede ===\n\n";
printf("  Archivo:  %s\n", basename($ruta));
printf("  Filas:    %d\n", count($filas));
printf("  Personas: %d distintas\n\n", count(array_unique(array_column($filas, 'correo'))));

$pdo = db();

$conv = convocatoria_actual();
if ($conv === null) {
    exit("No hay ninguna convocatoria activa. Marcá una en la tabla `convocatorias`.\n");
}

$stmt = $pdo->prepare('SELECT nombre FROM convocatorias WHERE id = ?');
$stmt->execute([$conv]);
printf("  Convocatoria activa: %s (id %d)\n\n", (string) $stmt->fetchColumn(), $conv);

// ------------------------------------------------------------------
// Cruce con la base
// ------------------------------------------------------------------

// Las sedes de la convocatoria, por código.
$stmt = $pdo->prepare('SELECT codigo, id FROM sedes WHERE convocatoria_id = ?');
$stmt->execute([$conv]);
$sedes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Cuántas aulas tiene cada sede, para contrastar con lo que dice el archivo.
$stmt = $pdo->prepare('
    SELECT sede_id, COUNT(*) FROM aulas WHERE convocatoria_id = ? GROUP BY sede_id
');
$stmt->execute([$conv]);
$aulasPorSede = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Personas del directorio que ya existen, por correo en minúsculas.
$personas = [];
foreach ($pdo->query("SELECT id, LOWER(correo) AS correo FROM personas WHERE correo <> ''") as $p) {
    $personas[$p['correo']] = (int) $p['id'];
}

$sinSede    = [];
$difAulas   = [];
$enlazadas  = 0;
$aplicadas  = 0;
$aulasTocadas = 0;

$actualizar = $pdo->prepare('
    UPDATE aulas
    SET coordinador_nombre = ?, coordinador_correo = ?, persona_id = COALESCE(?, persona_id)
    WHERE sede_id = ? AND convocatoria_id = ?
');

if ($aplicar) {
    $pdo->beginTransaction();
}

foreach ($filas as $f) {
    $codigo = $f['sede'];

    if (!isset($sedes[$codigo])) {
        $sinSede[] = $codigo;
        continue;
    }

    $sedeId    = (int) $sedes[$codigo];
    $personaId = $personas[mb_strtolower($f['correo'])] ?? null;

    if ($personaId !== null) {
        $enlazadas++;
    }

    // El archivo trae cuántas aulas espera. Si no coincide con la base, algo
    // se desincronizó entre los dos sistemas y conviene saberlo ANTES de la
    // aplicación, no el día que falten folletos.
    $enBase = (int) ($aulasPorSede[$sedeId] ?? 0);
    if ($f['aulas'] > 0 && $enBase !== $f['aulas']) {
        $difAulas[] = "sede {$codigo}: el archivo dice {$f['aulas']} aula(s), la base tiene {$enBase}";
    }

    if ($aplicar) {
        $actualizar->execute([$f['nombre'], $f['correo'], $personaId, $sedeId, $conv]);
        $aulasTocadas += $actualizar->rowCount();
    }

    $aplicadas++;
}

if ($aplicar) {
    $pdo->commit();
}

// ------------------------------------------------------------------
// Resultado
// ------------------------------------------------------------------

printf("  Sedes que calzan con la base: %d de %d\n", $aplicadas, count($filas));
printf("  Enlazadas al directorio:      %d\n", $enlazadas);

if ($aplicar) {
    printf("  Aulas actualizadas:           %d\n", $aulasTocadas);
}

// Sedes de la base que quedaron sin coordinador en el archivo.
$conCoordinador = array_flip(array_column($filas, 'sede'));
$sinCoordinador = array_values(array_diff(array_keys($sedes), array_keys($conCoordinador)));

if ($sinCoordinador !== []) {
    printf("\n  ⚠️  %d sede(s) de la base NO vienen en el archivo:\n     %s\n",
        count($sinCoordinador), implode(', ', $sinCoordinador));
    echo "     Se quedan con el coordinador que tuvieran; no se les borra nada.\n";
}

if ($sinSede !== []) {
    printf("\n  ⚠️  %d sede(s) del archivo NO existen en esta convocatoria:\n     %s\n",
        count($sinSede), implode(', ', $sinSede));
    echo "     Reviså que las sedes cargadas sean las del mismo año.\n";
}

if ($difAulas !== []) {
    printf("\n  ⚠️  %d sede(s) con distinta cantidad de aulas:\n", count($difAulas));
    foreach (array_slice($difAulas, 0, 15) as $d) {
        echo "     · {$d}\n";
    }
    if (count($difAulas) > 15) {
        printf("     … y %d más\n", count($difAulas) - 15);
    }
}

if (!$aplicar) {
    echo "\n  Nada se escribió todavía. Para aplicarlo:\n\n";
    echo "    php scripts/importar_coordinadores.php " . escapeshellarg($ruta) . " --aplicar\n";
} else {
    echo "\n  Listo. Los coordinadores ya aparecen en Ingreso de Datos, en\n";
    echo "  Revisión y en el acta de cada sede.\n";
}

// ==================================================================

/**
 * Saca las filas de datos de la tabla HTML.
 *
 * Se descartan las que no tengan las ocho celdas o cuyo código de sede no sea
 * un número: al final del archivo vienen los totales («Sedes con coordinador»,
 * «Generado el…») como si fueran filas más, y sin este filtro entrarían al
 * cruce y aparecerían como sedes inexistentes.
 */
function leer_tabla(string $ruta): array
{
    $html = file_get_contents($ruta);

    if ($html === false) {
        exit("No se pudo leer el archivo.\n");
    }

    // El archivo viene con BOM; si no se quita, el primer valor arrastra
    // caracteres invisibles y el código de sede deja de calzar.
    $html = preg_replace('/^\xEF\xBB\xBF/', '', $html) ?? $html;

    $filas = [];

    preg_match_all('#<tr[^>]*>([\s\S]*?)</tr>#i', $html, $todas);

    foreach ($todas[1] as $fila) {
        preg_match_all('#<td[^>]*>([\s\S]*?)</td>#i', $fila, $celdas);

        if (count($celdas[1]) < 8) {
            continue;
        }

        $v = array_map(static function (string $c): string {
            $texto = strip_tags($c);
            $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // &nbsp; sobrevive a la decodificación como byte 0xA0, que no es
            // un espacio normal y arruina las comparaciones.
            return trim(str_replace("\xC2\xA0", ' ', $texto));
        }, $celdas[1]);

        if (!ctype_digit($v[0]) || $v[6] === '' || $v[7] === '') {
            continue;
        }

        $filas[] = [
            'sede'   => $v[0],
            'fecha'  => $v[2],
            'turno'  => $v[4],
            'aulas'  => (int) $v[5],
            'nombre' => $v[6],
            'correo' => mb_strtolower($v[7]),
        ];
    }

    return $filas;
}
