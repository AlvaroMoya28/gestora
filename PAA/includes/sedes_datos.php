<?php
/**
 * Consultas de sedes compartidas por Ingreso de Datos y Revisión.
 *
 * POR QUÉ ESTÁN ACÁ Y NO EN CADA ENDPOINT
 * Los dos módulos leen exactamente los mismos datos: uno para llenarlos y el
 * otro para imprimirlos. Si cada endpoint tuviera su copia de estas consultas,
 * bastaría con tocar una para que el acta dejara de coincidir con lo que se
 * capturó — y esa diferencia aparecería recién sobre el papel ya firmado.
 *
 * Las funciones devuelven arreglos en vez de responder JSON: quien llama
 * decide qué hacer con ellos.
 */

declare(strict_types=1);

require_once __DIR__ . '/peticion.php';

/**
 * Todas las sedes de la convocatoria, con el avance de cada una.
 *
 * El avance no se guarda en ninguna columna: se deduce de si los datos están
 * completos. Así no hay dos verdades que puedan discrepar — una sede está
 * completa porque tiene los datos, no porque alguien marcó una casilla.
 */
function sedes_con_estado(int $conv): array
{
    $stmt = db()->prepare("
        SELECT
            s.id, s.codigo, s.nombre,
            s.coord_folleto_desde, s.coord_folleto_hasta, s.coord_sin_folletos,
            COUNT(a.id)                                          AS aulas,
            SUM(a.es_adecuacion = 1)                             AS aulas_adecuacion,
            SUM(a.folleto_desde IS NOT NULL AND a.folleto_desde <> '') AS aulas_con_folletos,
            SUM(a.asignados)                                     AS estudiantes,
            GROUP_CONCAT(DISTINCT NULLIF(a.formula, '') SEPARATOR ', ')  AS formulas,
            MAX(COALESCE(p.nombre, a.coordinador_nombre, s.coordinador_nombre)) AS coordinador,
            MAX(COALESCE(p.correo, a.coordinador_correo, s.coordinador_correo)) AS correo_coordinador,
            MAX(a.turno)                                         AS turno,
            MAX(c.fecha_aplicacion)                              AS fecha,
            (SELECT COUNT(*) FROM tulas t
              WHERE t.sede_id = s.id AND t.convocatoria_id = :conv2) AS tulas,
            (SELECT COUNT(*) FROM tulas t
               INNER JOIN marchamos m ON m.tula_id = t.id
              WHERE t.sede_id = s.id AND t.convocatoria_id = :conv3) AS marchamos
        FROM sedes s
        LEFT  JOIN aulas a         ON a.sede_id = s.id AND a.convocatoria_id = s.convocatoria_id
        LEFT  JOIN convocatorias c ON c.id = s.convocatoria_id
        LEFT  JOIN personas p      ON p.id = a.persona_id
        WHERE s.activo = 1 AND s.convocatoria_id = :conv1
        GROUP BY s.id, s.codigo, s.nombre, s.coord_folleto_desde, s.coord_folleto_hasta, s.coord_sin_folletos
        ORDER BY CAST(s.codigo AS UNSIGNED), s.codigo
    ");
    $stmt->execute(['conv1' => $conv, 'conv2' => $conv, 'conv3' => $conv]);

    return array_map(static function (array $f): array {
        $aulas   = (int) $f['aulas'];
        $conFoll = (int) $f['aulas_con_folletos'];
        $tulas   = (int) $f['tulas'];

        // Cada paso se da por hecho cuando sus datos están completos.
        $paso2 = $aulas > 0 && $conFoll >= $aulas;
        $paso3 = ($f['coord_folleto_desde'] !== null && $f['coord_folleto_desde'] !== '')
            || (bool) $f['coord_sin_folletos'];
        $paso4 = $tulas > 0;
        $paso5 = $tulas > 0 && (int) $f['marchamos'] >= $tulas * 2;

        $pasos    = [$paso2, $paso3, $paso4, $paso5];
        $completa = $paso2 && $paso3 && $paso4 && $paso5;

        // Tres estados y no dos: «sin empezar» y «a medias» se atienden de
        // forma distinta —una hay que arrancarla, la otra hay que terminarla—
        // y juntarlas en «pendiente» esconde cuál es cuál.
        $estado = $completa
            ? 'completa'
            : (in_array(true, $pasos, true) ? 'proceso' : 'sin_empezar');

        return [
            'id'                => (int) $f['id'],
            'codigo'            => $f['codigo'],
            'nombre'            => $f['nombre'],
            'aulas'             => $aulas,
            'aulasAdecuacion'   => (int) $f['aulas_adecuacion'],
            'aulasConFolletos'  => $conFoll,
            'estudiantes'       => (int) $f['estudiantes'],
            'coordinador'       => $f['coordinador'] ?? '',
            'correoCoordinador' => $f['correo_coordinador'] ?? '',
            'formulas'          => $f['formulas'] ?? '',
            'turno'             => $f['turno'] ?? '',
            'fecha'             => $f['fecha'] ?? '',
            'tulas'             => $tulas,
            'marchamos'         => (int) $f['marchamos'],
            'pasos'             => $pasos,
            'completa'          => $completa,
            'estado'            => $estado,
        ];
    }, $stmt->fetchAll());
}

/**
 * Todo lo capturado de una sede: sus datos, sus aulas y sus tulas.
 *
 * @return array{sede: array, aulas: array, tulas: array}
 */
function datos_de_sede(int $sedeId, int $conv): array
{
    $stmt = db()->prepare("
        SELECT s.id, s.codigo, s.nombre, s.direccion,
               s.coordinador_nombre, s.coordinador_correo,
               s.coord_folleto_desde, s.coord_folleto_hasta,
               s.coord_folleto_adec_desde, s.coord_folleto_adec_hasta,
               s.coord_sin_folletos, s.materiales_adecuacion,
               c.nombre AS convocatoria, c.fecha_aplicacion
        FROM sedes s
        INNER JOIN convocatorias c ON c.id = ?
        WHERE s.id = ?
    ");
    $stmt->execute([$conv, $sedeId]);
    $sede = $stmt->fetch();

    if ($sede === false) {
        return ['sede' => [], 'aulas' => [], 'tulas' => []];
    }

    // Aulas, con la tula en la que ya estén metidas
    $stmt = db()->prepare("
        SELECT a.id, a.numero_aula, a.asignados, a.formula, a.turno,
               a.folleto_desde, a.folleto_hasta, a.es_adecuacion,
               a.folleto_adec_desde, a.folleto_adec_hasta, a.folletos_sueltos,
               a.adecuaciones, a.total_folletos, a.total_folletos_coord,
               COALESCE(p.nombre, a.coordinador_nombre) AS coordinador,
               COALESCE(p.correo, a.coordinador_correo) AS correo_coordinador,
               ta.tula_id
        FROM aulas a
        LEFT JOIN personas p    ON p.id = a.persona_id
        LEFT JOIN tula_aulas ta ON ta.aula_id = a.id
        WHERE a.sede_id = ? AND a.convocatoria_id = ?
        ORDER BY a.es_adecuacion, CAST(a.numero_aula AS UNSIGNED), a.numero_aula
    ");
    $stmt->execute([$sedeId, $conv]);

    $filas = $stmt->fetchAll();

    $aulas = array_map(static fn(array $a): array => [
        'id'            => (int) $a['id'],
        'numero'        => $a['numero_aula'],
        'asignados'     => (int) $a['asignados'],
        'formula'       => $a['formula'] ?? '',
        'turno'         => $a['turno'] ?? '',
        'desde'         => $a['folleto_desde'] ?? '',
        'hasta'         => $a['folleto_hasta'] ?? '',
        'esAdecuacion'  => (bool) $a['es_adecuacion'],
        // Folletos de adecuación MEZCLADOS dentro de esta aula normal — no es
        // lo mismo que `esAdecuacion` (el aula entera). No siempre viene.
        'adecDesde'     => $a['folleto_adec_desde'] ?? '',
        'adecHasta'     => $a['folleto_adec_hasta'] ?? '',
        // Folletos que no entran en el rango consecutivo — ver schema/046.
        'folletosSueltos' => $a['folletos_sueltos'] ?? '',
        'adecuaciones'  => $a['adecuaciones'] ?? '',
        'totalFolletos' => (int) $a['total_folletos'],
        'coordinador'   => $a['coordinador'] ?? '',
        // Se expone además del nombre porque el acta se manda a esta
        // dirección. Antes solo salía el nombre y la clave del correo tenía
        // el nombre adentro: nunca se notó porque el envío lo tomaba de la
        // otra consulta, pero era un dato mal etiquetado esperando a romperse.
        'correo'        => $a['correo_coordinador'] ?? '',
        'tulaId'        => $a['tula_id'] !== null ? (int) $a['tula_id'] : null,
    ], $filas);

    $coordinador = $aulas[0]['coordinador'] ?? '';
    if ($coordinador === '') {
        $coordinador = $sede['coordinador_nombre'] ?? '';
    }

    $correoCoordinador = $aulas[0]['correo'] ?? '';
    if ($correoCoordinador === '') {
        $correoCoordinador = $sede['coordinador_correo'] ?? '';
    }

    return [
        'sede' => [
            'id'                => (int) $sede['id'],
            'codigo'            => $sede['codigo'],
            'nombre'            => $sede['nombre'],
            'direccion'         => $sede['direccion'] ?? '',
            'convocatoria'      => $sede['convocatoria'],
            'fecha'             => $sede['fecha_aplicacion'] ?? '',
            'coordDesde'        => $sede['coord_folleto_desde'] ?? '',
            'coordHasta'        => $sede['coord_folleto_hasta'] ?? '',
            // Folletos de adecuación mezclados en el rango del coordinador —
            // igual que en las aulas, no siempre vienen.
            'coordAdecDesde'    => $sede['coord_folleto_adec_desde'] ?? '',
            'coordAdecHasta'    => $sede['coord_folleto_adec_hasta'] ?? '',
            'coordSinFolletos'  => (bool) $sede['coord_sin_folletos'],
            'folletosCoord'     => folletos_del_coordinador($filas),
            'turno'             => $aulas[0]['turno'] ?? '',
            'coordinador'       => $coordinador,
            'correoCoordinador' => $correoCoordinador,
            'observaciones'     => observaciones_de_sede($sedeId, $conv),
            'tieneMaterialesAdecuacion' => (bool) $sede['materiales_adecuacion'],
            'materialesAdecuacion'      => materiales_adecuacion_de_sede($sedeId, $conv),
        ],
        'aulas' => $aulas,
        'tulas' => tulas_de_sede($sedeId, $conv),
    ];
}

/**
 * Cuántos folletos le tocan al coordinador de la sede, según la base.
 *
 * El dato viene de la columna «TOTAL FOLLETOS COORD.» del Sheet original, que
 * lo repetía en la fila de CADA aula aunque sea de la sede. Se toma el primer
 * valor que traiga algo: si dos filas de la misma sede dijeran cosas distintas
 * eso ya sería un problema del archivo de origen, no algo que esta función
 * pueda arreglar promediando.
 *
 * Se limpia de texto porque la columna es VARCHAR y en los datos viejos
 * aparece a veces como «9 folletos» en vez de «9».
 *
 * Devuelve 0 cuando no hay dato, que es distinto de «son cero»: la pantalla lo
 * dice con esas palabras en vez de proponer un rango inventado.
 */
function folletos_del_coordinador(array $filasDeAulas): int
{
    foreach ($filasDeAulas as $f) {
        $n = (int) preg_replace('/\D+/', '', (string) ($f['total_folletos_coord'] ?? ''));

        if ($n > 0) {
            return $n;
        }
    }

    return 0;
}

/**
 * Las observaciones que se fueron anotando de una sede, más viejas primero.
 *
 * Se agregan desde cualquier paso de Ingreso de Datos y se acumulan — así
 * que para cuando llegan a Revisión ya traen todo lo que fue quedando
 * anotado durante el embalaje, no solo lo último. Cada una se puede borrar
 * individualmente por si se escribió por error (ver borrar_observacion()
 * en api/ingreso_datos.php), por eso se expone el id.
 */
function observaciones_de_sede(int $sedeId, int $conv): array
{
    $stmt = db()->prepare("
        SELECT id, quien, texto, creado_en
        FROM sede_observaciones
        WHERE sede_id = ? AND convocatoria_id = ?
        ORDER BY creado_en, id
    ");
    $stmt->execute([$sedeId, $conv]);

    return array_map(static fn(array $o): array => [
        'id'      => (int) $o['id'],
        'quien'   => $o['quien'],
        'texto'   => $o['texto'],
        'fecha'   => $o['creado_en'],
    ], $stmt->fetchAll());
}

/**
 * Una fila por cada aula "de adecuación" de la sede, con su equipo (vacío si
 * todavía no se llenó). Eso incluye DOS casos, no uno solo:
 *   - el aula entera es de adecuación (`es_adecuacion`), o
 *   - es un aula regular que trae folletos de adecuación MEZCLADOS
 *     (`folleto_adec_desde`, ver el checkbox "trae folletos de adecuación
 *     mezclados" del paso de Folletos).
 * Quedarse solo con la primera dejaba afuera exactamente las aulas que el
 * personal ya venía marcando en Folletos, y la tabla salía vacía aunque sí
 * hubiera adecuación en la sede.
 *
 * Se listan aunque la sede tenga el interruptor apagado: así, si alguien lo
 * prende después, ya sabe cuáles aulas le tocan sin tener que ir a revisar
 * folletos.
 */
function materiales_adecuacion_de_sede(int $sedeId, int $conv): array
{
    $stmt = db()->prepare("
        SELECT a.id AS aula_id, a.numero_aula,
               m.calc_simple, m.calc_parlante, m.atril, m.mesa, m.pizarras,
               m.audifonos, m.tableta, m.computadora, m.diccionario, m.lampara
        FROM aulas a
        LEFT JOIN aula_materiales_adecuacion m ON m.aula_id = a.id
        WHERE a.sede_id = ? AND a.convocatoria_id = ?
          AND (a.es_adecuacion = 1 OR (a.folleto_adec_desde IS NOT NULL AND a.folleto_adec_desde <> ''))
        ORDER BY CAST(a.numero_aula AS UNSIGNED), a.numero_aula
    ");
    $stmt->execute([$sedeId, $conv]);

    return array_map(static fn(array $m): array => [
        'aulaId'      => (int) $m['aula_id'],
        'numero'      => $m['numero_aula'],
        'calcSimple'  => $m['calc_simple'] ?? '',
        'calcParlante' => $m['calc_parlante'] ?? '',
        'atril'       => $m['atril'] ?? '',
        'mesa'        => $m['mesa'] ?? '',
        'pizarras'    => $m['pizarras'] ?? '',
        'audifonos'   => $m['audifonos'] ?? '',
        'tableta'     => $m['tableta'] ?? '',
        'computadora' => $m['computadora'] ?? '',
        'diccionario' => $m['diccionario'] ?? '',
        'lampara'     => $m['lampara'] ?? '',
    ], $stmt->fetchAll());
}

/**
 * Lo mismo que datos_de_sede(), pero para muchas sedes de una vez.
 *
 * POR QUÉ NO ES UN BUCLE SOBRE datos_de_sede()
 * Esa función hace varias consultas. Con las 317 sedes de una convocatoria
 * eso son más de mil viajes a la base, que tardan lo suficiente como para
 * agotar el tiempo máximo de ejecución de PHP a mitad del lote. Acá se traen
 * todas las filas de golpe —seis consultas en total, sin importar si son
 * diez sedes o trescientas— y se arma la estructura en memoria.
 *
 * @param string[] $codigos Números de sede.
 * @return array Lista de { sede, aulas, tulas } en el mismo orden pedido.
 */
function datos_de_varias_sedes(array $codigos, int $conv): array
{
    if ($codigos === []) {
        return [];
    }

    $marcas = implode(',', array_fill(0, count($codigos), '?'));

    // ---- 1. Las sedes ----
    $stmt = db()->prepare("
        SELECT s.id, s.codigo, s.nombre, s.direccion,
               s.coordinador_nombre, s.coordinador_correo,
               s.coord_folleto_desde, s.coord_folleto_hasta,
               s.coord_folleto_adec_desde, s.coord_folleto_adec_hasta,
               s.coord_sin_folletos, s.materiales_adecuacion,
               c.nombre AS convocatoria, c.fecha_aplicacion
        FROM sedes s
        INNER JOIN convocatorias c ON c.id = ?
        WHERE s.convocatoria_id = ? AND s.codigo IN ({$marcas})
    ");
    $stmt->execute(array_merge([$conv, $conv], array_values($codigos)));

    $sedes = [];
    foreach ($stmt->fetchAll() as $s) {
        $sedes[(int) $s['id']] = $s;
    }

    if ($sedes === []) {
        return [];
    }

    $ids = array_keys($sedes);
    $marcasId = implode(',', array_fill(0, count($ids), '?'));

    // ---- 2. Las observaciones de todas ----
    $stmt = db()->prepare("
        SELECT sede_id, quien, texto, creado_en
        FROM sede_observaciones
        WHERE sede_id IN ({$marcasId}) AND convocatoria_id = ?
        ORDER BY sede_id, creado_en, id
    ");
    $stmt->execute(array_merge($ids, [$conv]));

    $observacionesPorSede = [];
    foreach ($stmt->fetchAll() as $o) {
        $observacionesPorSede[(int) $o['sede_id']][] = [
            'quien' => $o['quien'],
            'texto' => $o['texto'],
            'fecha' => $o['creado_en'],
        ];
    }

    // ---- 3. Los materiales de adecuación de todas ----
    $stmt = db()->prepare("
        SELECT a.sede_id, a.id AS aula_id, a.numero_aula,
               m.calc_simple, m.calc_parlante, m.atril, m.mesa, m.pizarras,
               m.audifonos, m.tableta, m.computadora, m.diccionario, m.lampara
        FROM aulas a
        LEFT JOIN aula_materiales_adecuacion m ON m.aula_id = a.id
        WHERE a.sede_id IN ({$marcasId}) AND a.convocatoria_id = ?
          AND (a.es_adecuacion = 1 OR (a.folleto_adec_desde IS NOT NULL AND a.folleto_adec_desde <> ''))
        ORDER BY a.sede_id, CAST(a.numero_aula AS UNSIGNED), a.numero_aula
    ");
    $stmt->execute(array_merge($ids, [$conv]));

    $materialesPorSede = [];
    foreach ($stmt->fetchAll() as $m) {
        $materialesPorSede[(int) $m['sede_id']][] = [
            'aulaId'       => (int) $m['aula_id'],
            'numero'       => $m['numero_aula'],
            'calcSimple'   => $m['calc_simple'] ?? '',
            'calcParlante' => $m['calc_parlante'] ?? '',
            'atril'        => $m['atril'] ?? '',
            'mesa'         => $m['mesa'] ?? '',
            'pizarras'     => $m['pizarras'] ?? '',
            'audifonos'    => $m['audifonos'] ?? '',
            'tableta'      => $m['tableta'] ?? '',
            'computadora'  => $m['computadora'] ?? '',
            'diccionario'  => $m['diccionario'] ?? '',
            'lampara'      => $m['lampara'] ?? '',
        ];
    }

    // ---- 4. Todas las aulas ----
    $stmt = db()->prepare("
        SELECT a.sede_id, a.id, a.numero_aula, a.asignados, a.formula, a.turno,
               a.folleto_desde, a.folleto_hasta, a.es_adecuacion,
               a.folleto_adec_desde, a.folleto_adec_hasta, a.folletos_sueltos,
               a.adecuaciones, a.total_folletos,
               COALESCE(p.nombre, a.coordinador_nombre) AS coordinador,
               COALESCE(p.correo, a.coordinador_correo) AS correo_coordinador,
               ta.tula_id
        FROM aulas a
        LEFT JOIN personas p    ON p.id = a.persona_id
        LEFT JOIN tula_aulas ta ON ta.aula_id = a.id
        WHERE a.sede_id IN ({$marcasId}) AND a.convocatoria_id = ?
        ORDER BY a.sede_id, a.es_adecuacion,
                 CAST(a.numero_aula AS UNSIGNED), a.numero_aula
    ");
    $stmt->execute(array_merge($ids, [$conv]));

    $aulasPorSede = [];
    foreach ($stmt->fetchAll() as $a) {
        $aulasPorSede[(int) $a['sede_id']][] = [
            'id'            => (int) $a['id'],
            'numero'        => $a['numero_aula'],
            'asignados'     => (int) $a['asignados'],
            'formula'       => $a['formula'] ?? '',
            'turno'         => $a['turno'] ?? '',
            'desde'         => $a['folleto_desde'] ?? '',
            'hasta'         => $a['folleto_hasta'] ?? '',
            'esAdecuacion'  => (bool) $a['es_adecuacion'],
            'adecDesde'     => $a['folleto_adec_desde'] ?? '',
            'adecHasta'     => $a['folleto_adec_hasta'] ?? '',
            'folletosSueltos' => $a['folletos_sueltos'] ?? '',
            'adecuaciones'  => $a['adecuaciones'] ?? '',
            'totalFolletos' => (int) $a['total_folletos'],
            'coordinador'   => $a['coordinador'] ?? '',
            'correo'        => $a['correo_coordinador'] ?? '',
            'tulaId'        => $a['tula_id'] !== null ? (int) $a['tula_id'] : null,
        ];
    }

    // ---- 5. Todas las tulas, con sus marchamos ----
    $stmt = db()->prepare("
        SELECT t.sede_id, t.id, t.numero, t.lleva_folletos_coord, t.estado,
               t.codigo_barras,
               MAX(CASE WHEN m.posicion = 1 THEN m.codigo END) AS marchamo1,
               MAX(CASE WHEN m.posicion = 2 THEN m.codigo END) AS marchamo2
        FROM tulas t
        LEFT JOIN marchamos m ON m.tula_id = t.id
        WHERE t.sede_id IN ({$marcasId}) AND t.convocatoria_id = ?
        GROUP BY t.sede_id, t.id, t.numero, t.lleva_folletos_coord, t.estado,
                 t.codigo_barras
        ORDER BY t.sede_id, t.numero
    ");
    $stmt->execute(array_merge($ids, [$conv]));
    $filasTulas = $stmt->fetchAll();

    // ---- 6. Las aulas que van dentro de cada tula ----
    $aulasPorTula = [];

    if ($filasTulas !== []) {
        $idsTula = array_column($filasTulas, 'id');
        $marcasTula = implode(',', array_fill(0, count($idsTula), '?'));

        $stmt = db()->prepare("
            SELECT ta.tula_id, a.id, a.numero_aula, a.asignados,
                   a.folleto_desde, a.folleto_hasta, a.es_adecuacion
            FROM tula_aulas ta
            INNER JOIN aulas a ON a.id = ta.aula_id
            WHERE ta.tula_id IN ({$marcasTula})
            ORDER BY ta.orden, CAST(a.numero_aula AS UNSIGNED)
        ");
        $stmt->execute($idsTula);

        foreach ($stmt->fetchAll() as $a) {
            $aulasPorTula[(int) $a['tula_id']][] = [
                'id'           => (int) $a['id'],
                'numero'       => $a['numero_aula'],
                'asignados'    => (int) $a['asignados'],
                'desde'        => $a['folleto_desde'] ?? '',
                'hasta'        => $a['folleto_hasta'] ?? '',
                'esAdecuacion' => (bool) $a['es_adecuacion'],
            ];
        }
    }

    $tulasPorSede = [];
    foreach ($filasTulas as $t) {
        $suyas = $aulasPorTula[(int) $t['id']] ?? [];

        $tulasPorSede[(int) $t['sede_id']][] = [
            'id'           => (int) $t['id'],
            'numero'       => (int) $t['numero'],
            'llevaCoord'   => (bool) $t['lleva_folletos_coord'],
            'estado'       => $t['estado'],
            'codigoBarras' => $t['codigo_barras'] ?? '',
            'marchamo1'    => $t['marchamo1'] ?? '',
            'marchamo2'    => $t['marchamo2'] ?? '',
            'aulas'        => $suyas,
            'estudiantes'  => array_sum(array_column($suyas, 'asignados')),
        ];
    }

    // ---- Se arma en el orden en que se pidieron ----
    $porCodigo = [];
    foreach ($sedes as $id => $s) {
        $porCodigo[(string) $s['codigo']] = $id;
    }

    $resultado = [];

    foreach ($codigos as $codigo) {
        $id = $porCodigo[(string) $codigo] ?? null;

        // Una sede que no aparece se salta en silencio: el lote se pidió desde
        // una lista que el servidor mismo entregó, así que si algo no calza es
        // que se dio de baja mientras tanto. Cortar el lote entero por eso
        // dejaría a la persona sin ninguna acta.
        if ($id === null) {
            continue;
        }

        $s     = $sedes[$id];
        $aulas = $aulasPorSede[$id] ?? [];

        $coordinador = $aulas[0]['coordinador'] ?? '';
        if ($coordinador === '') {
            $coordinador = $s['coordinador_nombre'] ?? '';
        }

        $correoCoordinador = $aulas[0]['correo'] ?? '';
        if ($correoCoordinador === '') {
            $correoCoordinador = $s['coordinador_correo'] ?? '';
        }

        $resultado[] = [
            'sede' => [
                'id'                => $id,
                'codigo'            => $s['codigo'],
                'nombre'            => $s['nombre'],
                'direccion'         => $s['direccion'] ?? '',
                'convocatoria'      => $s['convocatoria'],
                'fecha'             => $s['fecha_aplicacion'] ?? '',
                'coordDesde'        => $s['coord_folleto_desde'] ?? '',
                'coordHasta'        => $s['coord_folleto_hasta'] ?? '',
                'coordAdecDesde'    => $s['coord_folleto_adec_desde'] ?? '',
                'coordAdecHasta'    => $s['coord_folleto_adec_hasta'] ?? '',
                'coordSinFolletos'  => (bool) $s['coord_sin_folletos'],
                'turno'             => $aulas[0]['turno'] ?? '',
                'coordinador'       => $coordinador,
                'correoCoordinador' => $correoCoordinador,
                'observaciones'     => $observacionesPorSede[$id] ?? [],
                'tieneMaterialesAdecuacion' => (bool) $s['materiales_adecuacion'],
                'materialesAdecuacion'      => $materialesPorSede[$id] ?? [],
            ],
            'aulas' => $aulas,
            'tulas' => $tulasPorSede[$id] ?? [],
        ];
    }

    return $resultado;
}

/** Las tulas de una sede con sus aulas y sus marchamos. */
function tulas_de_sede(int $sedeId, int $conv): array
{
    $stmt = db()->prepare("
        SELECT t.id, t.numero, t.lleva_folletos_coord, t.estado, t.codigo_barras,
               MAX(CASE WHEN m.posicion = 1 THEN m.codigo END) AS marchamo1,
               MAX(CASE WHEN m.posicion = 2 THEN m.codigo END) AS marchamo2
        FROM tulas t
        LEFT JOIN marchamos m ON m.tula_id = t.id
        WHERE t.sede_id = ? AND t.convocatoria_id = ?
        GROUP BY t.id, t.numero, t.lleva_folletos_coord, t.estado, t.codigo_barras
        ORDER BY t.numero
    ");
    $stmt->execute([$sedeId, $conv]);
    $tulas = $stmt->fetchAll();

    if ($tulas === []) {
        return [];
    }

    // Las aulas de todas las tulas en una sola consulta, para no repetir una
    // por cada bulto.
    $ids = array_column($tulas, 'id');
    $marcas = implode(',', array_fill(0, count($ids), '?'));

    $stmt = db()->prepare("
        SELECT ta.tula_id, a.id, a.numero_aula, a.asignados,
               a.folleto_desde, a.folleto_hasta, a.es_adecuacion
        FROM tula_aulas ta
        INNER JOIN aulas a ON a.id = ta.aula_id
        WHERE ta.tula_id IN ({$marcas})
        ORDER BY ta.orden, CAST(a.numero_aula AS UNSIGNED)
    ");
    $stmt->execute($ids);

    $porTula = [];
    foreach ($stmt->fetchAll() as $a) {
        $porTula[(int) $a['tula_id']][] = [
            'id'           => (int) $a['id'],
            'numero'       => $a['numero_aula'],
            'asignados'    => (int) $a['asignados'],
            'desde'        => $a['folleto_desde'] ?? '',
            'hasta'        => $a['folleto_hasta'] ?? '',
            'esAdecuacion' => (bool) $a['es_adecuacion'],
        ];
    }

    return array_map(static function (array $t) use ($porTula): array {
        $aulas = $porTula[(int) $t['id']] ?? [];

        return [
            'id'            => (int) $t['id'],
            'numero'        => (int) $t['numero'],
            'llevaCoord'    => (bool) $t['lleva_folletos_coord'],
            'estado'        => $t['estado'],
            'codigoBarras'  => $t['codigo_barras'] ?? '',
            'marchamo1'     => $t['marchamo1'] ?? '',
            'marchamo2'     => $t['marchamo2'] ?? '',
            'aulas'         => $aulas,
            'estudiantes'   => array_sum(array_column($aulas, 'asignados')),
        ];
    }, $tulas);
}

/** Catálogo de materiales del acta. */
function materiales_del_acta(): array
{
    return db()->query("
        SELECT id, nombre, unidad, orden FROM materiales
        WHERE activo = 1 ORDER BY orden, nombre
    ")->fetchAll();
}

// ------------------------------------------------------------------
// Buscador de folletos
// ------------------------------------------------------------------
//
// Compartido por Ingreso de Datos (donde se cargan los rangos) y Revisión
// (donde se consultan): "¿en qué sede quedó el folleto 35192?". Un folleto
// no siempre es un número solo —hay series con letras, como en adecuación—
// así que se parte en prefijo/sufijo (la "serie") + número, igual que hace
// conflicto_rango_folleto() en api/ingreso_datos.php al validar que dos
// sedes no se pisen un rango.

/**
 * Divide un código de folleto en la serie (todo lo que no es dígito) y el
 * número, o null si no tiene ningún dígito. Se toma la ÚLTIMA tanda de
 * dígitos: en «AD1234» lo que cuenta es 1234.
 */
function partir_codigo_folleto(string $codigo): ?array
{
    if (!preg_match('/^(.*?)(\d+)(\D*)$/', trim($codigo), $m)) {
        return null;
    }

    return ['numero' => (int) $m[2], 'serie' => $m[1] . ' ' . $m[3]];
}

/**
 * Busca un folleto por su código en las cuatro numeraciones que puede
 * ocupar: las aulas normales, las de adecuación, el coordinador, y los de
 * adecuación mezclados en el rango del coordinador. Devuelve todos los
 * rangos (de cualquier sede) donde el número cae adentro y la serie coincide
 * — normalmente uno solo, pero se listan todos por si hay un rango mal
 * cargado que se traslapa.
 */
/**
 * Los códigos de una lista de "folletos sueltos" (ver schema/046), como texto
 * libre separado por coma, punto y coma o salto de línea.
 */
function codigos_sueltos(string $texto): array
{
    $partes = preg_split('/[,;\r\n]+/', $texto) ?: [];
    return array_values(array_filter(array_map('trim', $partes), static fn(string $c): bool => $c !== ''));
}

function buscar_folleto(int $conv, string $codigoBuscado): array
{
    $buscado = partir_codigo_folleto($codigoBuscado);
    if ($buscado === null) {
        return [];
    }

    $filas = db()->prepare("
        SELECT s.codigo AS sede_codigo, s.nombre AS sede_nombre,
               a.numero_aula AS aula, 'aula' AS tipo,
               a.folleto_desde AS `desde`, a.folleto_hasta AS hasta
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = :conv1
          AND a.folleto_desde IS NOT NULL AND a.folleto_desde <> ''
          AND a.folleto_hasta IS NOT NULL AND a.folleto_hasta <> ''

        UNION ALL

        SELECT s.codigo, s.nombre, a.numero_aula, 'aula_adecuacion',
               a.folleto_adec_desde, a.folleto_adec_hasta
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = :conv2
          AND a.folleto_adec_desde IS NOT NULL AND a.folleto_adec_desde <> ''
          AND a.folleto_adec_hasta IS NOT NULL AND a.folleto_adec_hasta <> ''

        UNION ALL

        SELECT s.codigo, s.nombre, NULL, 'coordinador',
               s.coord_folleto_desde, s.coord_folleto_hasta
        FROM sedes s
        WHERE s.convocatoria_id = :conv3
          AND s.coord_folleto_desde IS NOT NULL AND s.coord_folleto_desde <> ''
          AND s.coord_folleto_hasta IS NOT NULL AND s.coord_folleto_hasta <> ''

        UNION ALL

        SELECT s.codigo, s.nombre, NULL, 'coordinador_adecuacion',
               s.coord_folleto_adec_desde, s.coord_folleto_adec_hasta
        FROM sedes s
        WHERE s.convocatoria_id = :conv4
          AND s.coord_folleto_adec_desde IS NOT NULL AND s.coord_folleto_adec_desde <> ''
          AND s.coord_folleto_adec_hasta IS NOT NULL AND s.coord_folleto_adec_hasta <> ''
    ");
    $filas->execute(['conv1' => $conv, 'conv2' => $conv, 'conv3' => $conv, 'conv4' => $conv]);

    $resultados = [];

    foreach ($filas->fetchAll() as $f) {
        $d = partir_codigo_folleto((string) $f['desde']);
        $h = partir_codigo_folleto((string) $f['hasta']);

        if ($d === null || $h === null || $d['serie'] !== $h['serie'] || $d['serie'] !== $buscado['serie']) {
            continue;
        }

        $desdeN = min($d['numero'], $h['numero']);
        $hastaN = max($d['numero'], $h['numero']);

        if ($buscado['numero'] >= $desdeN && $buscado['numero'] <= $hastaN) {
            $resultados[] = [
                'sedeCodigo' => $f['sede_codigo'],
                'sedeNombre' => $f['sede_nombre'],
                'aula'       => $f['aula'],
                'tipo'       => $f['tipo'],
                'desde'      => $f['desde'],
                'hasta'      => $f['hasta'],
            ];
        }
    }

    // Folletos sueltos: no entran en ningún rango, así que se buscan aparte,
    // uno por uno dentro de la lista de cada aula que tenga alguno.
    $sueltos = db()->prepare("
        SELECT s.codigo AS sede_codigo, s.nombre AS sede_nombre,
               a.numero_aula AS aula, a.folletos_sueltos AS sueltos
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = ?
          AND a.folletos_sueltos IS NOT NULL AND a.folletos_sueltos <> ''
    ");
    $sueltos->execute([$conv]);

    foreach ($sueltos->fetchAll() as $f) {
        foreach (codigos_sueltos((string) $f['sueltos']) as $codigo) {
            $c = partir_codigo_folleto($codigo);
            if ($c !== null && $c['serie'] === $buscado['serie'] && $c['numero'] === $buscado['numero']) {
                $resultados[] = [
                    'sedeCodigo' => $f['sede_codigo'],
                    'sedeNombre' => $f['sede_nombre'],
                    'aula'       => $f['aula'],
                    'tipo'       => 'aula_suelto',
                    'desde'      => $codigo,
                    'hasta'      => $codigo,
                ];
            }
        }
    }

    return $resultados;
}

// ------------------------------------------------------------------
// Guardas
// ------------------------------------------------------------------

function convocatoria_exigida(): int
{
    $conv = convocatoria_actual();

    if ($conv === null) {
        responder_json([
            'success' => false,
            'error'   => 'No hay ninguna convocatoria activa',
        ], 409);
    }

    return $conv;
}

function sede_exigida(string $codigo, int $conv): int
{
    $id = id_sede_por_codigo($codigo, $conv);

    if ($id === null) {
        responder_json([
            'success' => false,
            'error'   => "La sede {$codigo} no existe en esta convocatoria",
        ], 404);
    }

    return $id;
}
