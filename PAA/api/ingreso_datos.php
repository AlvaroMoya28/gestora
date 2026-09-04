<?php
/**
 * Endpoint del módulo Ingreso de Datos.
 *
 * Cubre el proceso completo de preparación de una sede, en cuatro pasos:
 *
 *   1. Elegir la sede                     → accion=sedes / accion=sede
 *   2. Folletos (de las aulas y del       → accion=guardarFolletos
 *      coordinador, en una sola pantalla)   accion=guardarCoordinador
 *   3. Armado de las tulas                → accion=guardarTulas
 *   4. Marchamos de cada tula             → accion=guardarMarchamos
 *
 * Los folletos del coordinador tenían pantalla propia y eran dos campos. Se
 * unieron con los de las aulas porque se escriben de corrido y separarlos
 * costaba un «guardar y seguir» por sede, trescientas diecisiete veces.
 *
 * BORRADORES
 * Cualquiera de los dos guardados de folletos acepta `borrador`. Con esa
 * bandera escribe igual pero no marca el paso como hecho: es lo que usa el
 * guardado automático mientras la persona teclea. Sin la distinción, una sede a
 * medio llenar aparecería como terminada en la lista.
 *
 * BLOQUEOS
 * Varias personas trabajan a la vez y todas entran con la MISMA cuenta. Ver el
 * encabezado de schema/020_bloqueos_sede.sql: lo que separa a una de otra es la
 * sesión del navegador, no el usuario.
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/sedes_datos.php';

$yo = exigir_modulo_api("ingresoDatos");

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// MINUTOS_BLOQUEO ya queda definida en includes/sesion.php (cargada acá vía
// includes/modulos.php); ver ese archivo para el porqué del valor.

$accion = param('accion', param('action', 'sedes'));

try {
    switch ($accion) {
        case 'sedes':              listar_sedes();        break;
        case 'sede':               detalle_sede();        break;
        case 'buscarFolleto':      buscar_folleto_accion(); break;
        case 'guardarFolletos':    guardar_folletos();    break;
        case 'guardarCoordinador': guardar_coordinador(); break;
        case 'guardarTulas':       guardar_tulas();       break;
        case 'guardarMarchamos':   guardar_marchamos();   break;
        case 'borrarAvance':       borrar_avance();       break;

        // Observaciones: se agregan en cualquier paso y se acumulan
        case 'agregarObservacion': agregar_observacion(); break;
        case 'borrarObservacion':  borrar_observacion();  break;

        // Materiales de adecuación
        case 'marcarMaterialesAdecuacion':  marcar_materiales_adecuacion();  break;
        case 'guardarMaterialesAdecuacion': guardar_materiales_adecuacion(); break;

        // Trabajo en paralelo
        case 'bloqueos':           obtener_bloqueos();    break;
        case 'bloquearSede':       bloquear_sede($yo);    break;
        case 'desbloquearSede':    desbloquear_sede();    break;

        // Ranking amistoso
        case 'ranking':            ranking_ingreso();     break;

        // Edición de datos base (cantidad de aulas, estudiantes por aula,
        // fórmula, tipo), con desbloqueo de supervisor. Ver el encabezado de
        // includes/sesion.php sobre autorizar_edicion_base().
        case 'estadoEdicionBase':    estado_edicion_base_accion();    break;
        case 'autorizarEdicionBase': autorizar_edicion_base_accion(); break;
        case 'autorizarPropioBase':  autorizar_propio_base_accion();  break;
        case 'bloquearEdicionBase':  bloquear_edicion_base_accion();  break;
        case 'editarAulaBase':       editar_aula_base();              break;
        case 'agregarAulaBase':      agregar_aula_base();             break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('ingreso_datos', $e);
}

/** Dónde quedó (o no) un folleto — ver buscar_folleto() en sedes_datos.php. */
function buscar_folleto_accion(): void
{
    $conv = convocatoria_exigida();
    exigir(['folleto']);

    $codigo = param('folleto');
    if ($codigo === '') {
        responder_json(['success' => false, 'error' => 'Falta el código del folleto'], 400);
    }

    responder_json(['success' => true, 'resultados' => buscar_folleto($conv, $codigo)]);
}

// ------------------------------------------------------------------
// Paso 1 · Sedes
// ------------------------------------------------------------------

function listar_sedes(): void
{
    $conv  = convocatoria_exigida();
    $sedes = sedes_con_estado($conv);

    // Quién tiene tomada cada sede, para pintarlo junto a la sede misma y no
    // en una consulta aparte que puede llegar tarde.
    $bloqueos = obtener_bloqueos_activos($conv);

    foreach ($sedes as &$s) {
        $s['ocupadaPor'] = $bloqueos[$s['codigo']]['quien'] ?? null;
        $s['ocupadaSesion'] = $bloqueos[$s['codigo']]['sesion'] ?? null;
    }
    unset($s);

    responder_json([
        'success'        => true,
        'sedes'          => $sedes,
        'bloqueos'       => $bloqueos,
        'sinBloqueos'    => !hay_tabla_de_bloqueos(),
        'total'          => count($sedes),
        'convocatoriaId' => $conv,
    ]);
}

function detalle_sede(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);

    responder_json(['success' => true, 'edicionBase' => estado_edicion_base()] + datos_de_sede($sedeId, $conv));
}

// ------------------------------------------------------------------
// Edición de datos base, con desbloqueo de supervisor
// ------------------------------------------------------------------
//
// Ingreso de Datos trabaja sobre lo que ya trajo el import: número de aula,
// cuántos estudiantes le tocan, la fórmula, si es de adecuación. Cuando eso
// viene mal (faltó un aula, cambió la cantidad de gente), hasta ahora la
// única puerta era Traslado de Estudiantes — un módulo de emergencia pensado
// para mover gente entre sedes completas, no para corregir un dato suelto.
//
// Acá se corrige directo, pero detrás de una llave: como la caja del
// supermercado, alguien autorizado (administración, desarrollo, o una
// persona puntual marcada en usuarios.autoriza_datos_base — ver
// schema/045_autorizacion_datos_base.sql) pone su clave y desbloquea la
// edición en ESTA sesión del navegador por unos minutos (ver
// MINUTOS_AUTORIZACION_DATOS_BASE en includes/sesion.php). Quien está
// digitando sigue siendo quien es: la bitácora de cada cambio lleva su
// nombre Y el de quien autorizó.

/** Si la cuenta CONECTADA (no una de afuera) ya tiene el permiso, sin pedirle clave otra vez. */
function puede_auto_autorizar_base(array $yo): bool
{
    $stmt = db()->prepare('SELECT rol, autoriza_datos_base FROM usuarios WHERE id = ?');
    $stmt->execute([(int) $yo['id']]);
    $fila = $stmt->fetch();

    return $fila !== false && autoriza_datos_base($fila);
}

/** Lo que la pantalla necesita para dibujar el candado: abierto o cerrado, y por quién. */
function estado_edicion_base(): array
{
    $yo = usuario_actual();

    return [
        'autorizada'         => edicion_base_autorizada(),
        'autorizadoPor'      => $_SESSION['edicion_base_autorizado_por'] ?? null,
        'hasta'              => $_SESSION['edicion_base_hasta'] ?? null,
        'puedeAutoAutorizar' => $yo !== null && puede_auto_autorizar_base($yo),
    ];
}

function estado_edicion_base_accion(): void
{
    responder_json(['success' => true] + estado_edicion_base());
}

/** Alguien autorizado escribe SU clave para desbloquear esta sesión. */
function autorizar_edicion_base_accion(): void
{
    exigir(['usuario', 'clave']);

    $resultado = autorizar_edicion_base(param('usuario'), param('clave'));

    if (!$resultado['ok']) {
        // 403 y no 400: la clave puede estar perfectamente bien escrita y aun
        // así no alcanzar — es un problema de permiso, no de formato.
        responder_json(['success' => false, 'error' => $resultado['error']], 403);
    }

    bitacora_apuntar('ingreso_datos', 'autorizarEdicionBase', "autorizo={$resultado['autorizadoPor']}");

    responder_json(['success' => true] + estado_edicion_base());
}

/** La cuenta ya conectada se autoriza a sí misma, sin volver a pedirle la clave que ya puso al entrar. */
function autorizar_propio_base_accion(): void
{
    $yo = usuario_actual();

    if ($yo === null || !puede_auto_autorizar_base($yo)) {
        responder_json(['success' => false, 'error' => 'Tu cuenta no tiene permiso para autorizar este cambio.'], 403);
    }

    $_SESSION['edicion_base_hasta']          = time() + MINUTOS_AUTORIZACION_DATOS_BASE * 60;
    $_SESSION['edicion_base_autorizado_por'] = $yo['nombre'];

    bitacora_apuntar('ingreso_datos', 'autorizarEdicionBase', 'autoautorizado');

    responder_json(['success' => true] + estado_edicion_base());
}

function bloquear_edicion_base_accion(): void
{
    bloquear_edicion_base();
    responder_json(['success' => true] + estado_edicion_base());
}

/** Corrige número, estudiantes, fórmula o tipo de un aula ya cargada. */
function editar_aula_base(): void
{
    if (!edicion_base_autorizada()) {
        responder_json([
            'success' => false,
            'error'   => 'La edición de datos base no está desbloqueada. Pedile a alguien autorizado que ponga su clave.',
        ], 403);
    }

    exigir(['sede', 'aula']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $aulaId = param_int('aula');

    $stmt = db()->prepare('
        SELECT id, sede_id, convocatoria_id, numero_aula, asignados, formula, es_adecuacion
        FROM aulas WHERE id = ?
    ');
    $stmt->execute([$aulaId]);
    $aula = $stmt->fetch();

    if ($aula === false || (int) $aula['sede_id'] !== $sedeId || (int) $aula['convocatoria_id'] !== $conv) {
        responder_json(['success' => false, 'error' => 'Esa aula no pertenece a esta sede.'], 404);
    }

    $numero       = trim(param('numero', $aula['numero_aula']));
    $asignados    = param_int('asignados', (int) $aula['asignados']);
    $formula      = trim(param('formula', (string) $aula['formula']));
    $esAdecuacion = param('esAdecuacion', ((int) $aula['es_adecuacion']) === 1 ? '1' : '0') === '1';

    if ($numero === '') {
        responder_json(['success' => false, 'error' => 'El número de aula no puede quedar vacío.'], 400);
    }
    if ($asignados < 0 || $asignados > 2000) {
        responder_json(['success' => false, 'error' => 'La cantidad de estudiantes no es válida.'], 400);
    }

    $stmt = db()->prepare('
        SELECT id FROM aulas
        WHERE sede_id = ? AND convocatoria_id = ? AND numero_aula = ? AND id <> ?
    ');
    $stmt->execute([$sedeId, $conv, $numero, $aulaId]);
    if ($stmt->fetch() !== false) {
        responder_json(['success' => false, 'error' => "Ya existe un aula {$numero} en esta sede."], 409);
    }

    db()->prepare('
        UPDATE aulas SET numero_aula = ?, asignados = ?, formula = ?, es_adecuacion = ?
        WHERE id = ?
    ')->execute([$numero, $asignados, $formula !== '' ? $formula : null, $esAdecuacion ? 1 : 0, $aulaId]);

    $quienAutorizo = $_SESSION['edicion_base_autorizado_por'] ?? '?';
    bitacora_apuntar(
        'ingreso_datos',
        'editarAulaBase',
        "sede={$sedeId} aula={$aulaId} numero={$numero} asignados={$asignados} "
            . "formula={$formula} adecuacion=" . ($esAdecuacion ? 'si' : 'no') . " autorizo={$quienAutorizo}"
    );

    responder_json(['success' => true, 'edicionBase' => estado_edicion_base()] + datos_de_sede($sedeId, $conv));
}

/** Agrega una aula nueva a la sede — "un aula más" que no venía en la base. */
function agregar_aula_base(): void
{
    if (!edicion_base_autorizada()) {
        responder_json([
            'success' => false,
            'error'   => 'La edición de datos base no está desbloqueada. Pedile a alguien autorizado que ponga su clave.',
        ], 403);
    }

    exigir(['sede', 'numero']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);

    $numero       = trim(param('numero'));
    $asignados    = param_int('asignados', 0);
    $formula      = trim(param('formula'));
    $esAdecuacion = param('esAdecuacion') === '1';

    if ($numero === '') {
        responder_json(['success' => false, 'error' => 'Hay que ponerle un número al aula nueva.'], 400);
    }
    if ($asignados < 0 || $asignados > 2000) {
        responder_json(['success' => false, 'error' => 'La cantidad de estudiantes no es válida.'], 400);
    }

    $stmt = db()->prepare('SELECT id FROM aulas WHERE sede_id = ? AND convocatoria_id = ? AND numero_aula = ?');
    $stmt->execute([$sedeId, $conv, $numero]);
    if ($stmt->fetch() !== false) {
        responder_json(['success' => false, 'error' => "Ya existe un aula {$numero} en esta sede."], 409);
    }

    db()->prepare('
        INSERT INTO aulas (convocatoria_id, sede_id, numero_aula, asignados, formula, es_adecuacion)
        VALUES (?, ?, ?, ?, ?, ?)
    ')->execute([$conv, $sedeId, $numero, $asignados, $formula !== '' ? $formula : null, $esAdecuacion ? 1 : 0]);

    $aulaId = (int) db()->lastInsertId();

    $quienAutorizo = $_SESSION['edicion_base_autorizado_por'] ?? '?';
    bitacora_apuntar(
        'ingreso_datos',
        'agregarAulaBase',
        "sede={$sedeId} aula={$aulaId} numero={$numero} asignados={$asignados} autorizo={$quienAutorizo}"
    );

    responder_json(['success' => true, 'edicionBase' => estado_edicion_base()] + datos_de_sede($sedeId, $conv), 201);
}

/**
 * Agrega una observación a la sede. Se acumula: no reemplaza las que ya
 * había, y queda disponible desde cualquier paso (el panel es el mismo en
 * los cuatro) y en el acta de Revisión.
 */
function agregar_observacion(): void
{
    exigir(['sede', 'texto']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $texto  = trim(param('texto'));

    if ($texto === '') {
        responder_json(['success' => false, 'error' => 'La observación está vacía'], 400);
    }

    $quien = trim(param('quien'));

    db()->prepare('
        INSERT INTO sede_observaciones (sede_id, convocatoria_id, quien, texto)
        VALUES (?, ?, ?, ?)
    ')->execute([$sedeId, $conv, mb_substr($quien, 0, 80), mb_substr($texto, 0, 500)]);

    bitacora_apuntar('ingreso_datos', 'agregarObservacion', "sede={$sedeId} texto=" . mb_substr($texto, 0, 60));

    responder_json(['success' => true, 'observaciones' => observaciones_de_sede($sedeId, $conv)]);
}

/** Borra una observación puntual, por si se escribió algo por error. */
function borrar_observacion(): void
{
    exigir(['sede', 'id']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $id     = param_int('id');

    $stmt = db()->prepare('
        DELETE FROM sede_observaciones WHERE id = ? AND sede_id = ? AND convocatoria_id = ?
    ');
    $stmt->execute([$id, $sedeId, $conv]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'No se encontró esa observación'], 404);
    }

    bitacora_apuntar('ingreso_datos', 'borrarObservacion', "sede={$sedeId} id={$id}");

    responder_json(['success' => true, 'observaciones' => observaciones_de_sede($sedeId, $conv)]);
}

/** Prende o apaga el interruptor "esta sede tiene materiales de adecuación". */
function marcar_materiales_adecuacion(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $activo = param('activo') !== '' && param('activo') !== '0';

    db()->prepare('UPDATE sedes SET materiales_adecuacion = ? WHERE id = ?')
        ->execute([$activo ? 1 : 0, $sedeId]);

    bitacora_apuntar(
        'ingreso_datos',
        'marcarMaterialesAdecuacion',
        "sede={$sedeId} activo=" . ($activo ? '1' : '0')
    );

    responder_json([
        'success'               => true,
        'activo'                => $activo,
        'materialesAdecuacion'  => materiales_adecuacion_de_sede($sedeId, $conv),
    ]);
}

/**
 * Guarda el equipo de cada aula de adecuación de la sede.
 *
 * Todo llega como texto (VARCHAR en la base, ver schema/029): la mayoría son
 * cantidades, pero el personal a veces necesita anotar algo que no es un
 * número, y forzar un tipo numérico se lo impediría.
 */
function guardar_materiales_adecuacion(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $filas  = entrada()['filas'] ?? null;

    if (!is_array($filas)) {
        responder_json(['success' => false, 'error' => 'No se recibieron los materiales'], 400);
    }

    // Solo se guardan aulas de adecuación que de verdad son de esta sede: el
    // cliente manda el id del aula, pero el servidor no confía en que además
    // diga la verdad sobre a quién pertenece.
    $stmtValidas = db()->prepare("
        SELECT id FROM aulas
        WHERE sede_id = ? AND convocatoria_id = ?
          AND (es_adecuacion = 1 OR (folleto_adec_desde IS NOT NULL AND folleto_adec_desde <> ''))
    ");
    $stmtValidas->execute([$sedeId, $conv]);
    $aulasValidas = array_flip($stmtValidas->fetchAll(PDO::FETCH_COLUMN));

    $upsert = db()->prepare('
        INSERT INTO aula_materiales_adecuacion
            (aula_id, sede_id, convocatoria_id, calc_simple, calc_parlante,
             atril, mesa, pizarras, audifonos, tableta, computadora, diccionario, lampara)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            calc_simple = VALUES(calc_simple), calc_parlante = VALUES(calc_parlante),
            atril = VALUES(atril), mesa = VALUES(mesa), pizarras = VALUES(pizarras),
            audifonos = VALUES(audifonos), tableta = VALUES(tableta),
            computadora = VALUES(computadora), diccionario = VALUES(diccionario),
            lampara = VALUES(lampara)
    ');

    $guardadas = 0;
    foreach ($filas as $f) {
        $aulaId = (int) ($f['aulaId'] ?? 0);
        if (!isset($aulasValidas[$aulaId])) {
            continue;
        }

        $valor = static function (string $clave) use ($f): ?string {
            $v = trim((string) ($f[$clave] ?? ''));
            return $v !== '' ? mb_substr($v, 0, 80) : null;
        };

        $upsert->execute([
            $aulaId, $sedeId, $conv,
            $valor('calcSimple'), $valor('calcParlante'), $valor('atril'), $valor('mesa'),
            $valor('pizarras'), $valor('audifonos'), $valor('tableta'), $valor('computadora'),
            $valor('diccionario'), $valor('lampara'),
        ]);
        $guardadas++;
    }

    bitacora_apuntar('ingreso_datos', 'guardarMaterialesAdecuacion', "sede={$sedeId} aulas={$guardadas}");

    responder_json(['success' => true, 'materialesAdecuacion' => materiales_adecuacion_de_sede($sedeId, $conv)]);
}

// ------------------------------------------------------------------
// Paso 2 · Folletos por aula
// ------------------------------------------------------------------

// partir_codigo_folleto() vive en includes/sedes_datos.php: la usa también
// el buscador de folletos, compartido con Revisión.

/**
 * Revisa que un rango de folletos no se traslape con el de OTRA sede de la
 * misma convocatoria (ni con sus aulas ni con su coordinador). Dentro de la
 * misma sede no se compara: el reparto entre sus propias aulas ya lo resuelve
 * el cliente.
 *
 * Solo compiten los rangos de la misma serie (mismo prefijo y sufijo): las
 * aulas de adecuación numeran aparte de las normales, y una serie sin
 * números no se puede comparar, así que esos casos no generan conflicto acá.
 */
function conflicto_rango_folleto(int $conv, int $sedeId, string $desde, string $hasta): ?string
{
    $d = partir_codigo_folleto($desde);
    $h = partir_codigo_folleto($hasta);

    if ($d === null || $h === null || $d['serie'] !== $h['serie']) {
        return null;
    }

    $desdeN = min($d['numero'], $h['numero']);
    $hastaN = max($d['numero'], $h['numero']);

    $filas = db()->prepare("
        SELECT s.codigo AS sede, a.numero_aula AS aula, a.folleto_desde AS `desde`, a.folleto_hasta AS hasta
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = :conv AND a.sede_id <> :sede
          AND a.folleto_desde IS NOT NULL AND a.folleto_desde <> ''
          AND a.folleto_hasta IS NOT NULL AND a.folleto_hasta <> ''

        UNION ALL

        -- Los de adecuación mezclados DENTRO de un aula normal: son otra
        -- numeración, pero igual pueden chocar con la de otra sede.
        SELECT s.codigo AS sede, a.numero_aula AS aula,
               a.folleto_adec_desde AS `desde`, a.folleto_adec_hasta AS hasta
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = :conv3 AND a.sede_id <> :sede3
          AND a.folleto_adec_desde IS NOT NULL AND a.folleto_adec_desde <> ''
          AND a.folleto_adec_hasta IS NOT NULL AND a.folleto_adec_hasta <> ''

        UNION ALL

        SELECT s.codigo AS sede, NULL AS aula, s.coord_folleto_desde AS `desde`, s.coord_folleto_hasta AS hasta
        FROM sedes s
        WHERE s.convocatoria_id = :conv2 AND s.id <> :sede2
          AND s.coord_folleto_desde IS NOT NULL AND s.coord_folleto_desde <> ''
          AND s.coord_folleto_hasta IS NOT NULL AND s.coord_folleto_hasta <> ''

        UNION ALL

        -- Los de adecuación mezclados en el rango del coordinador.
        SELECT s.codigo AS sede, NULL AS aula,
               s.coord_folleto_adec_desde AS `desde`, s.coord_folleto_adec_hasta AS hasta
        FROM sedes s
        WHERE s.convocatoria_id = :conv4 AND s.id <> :sede4
          AND s.coord_folleto_adec_desde IS NOT NULL AND s.coord_folleto_adec_desde <> ''
          AND s.coord_folleto_adec_hasta IS NOT NULL AND s.coord_folleto_adec_hasta <> ''
    ");
    $filas->execute([
        'conv' => $conv, 'sede' => $sedeId,
        'conv2' => $conv, 'sede2' => $sedeId,
        'conv3' => $conv, 'sede3' => $sedeId,
        'conv4' => $conv, 'sede4' => $sedeId,
    ]);

    foreach ($filas->fetchAll() as $f) {
        $od = partir_codigo_folleto($f['desde']);
        $oh = partir_codigo_folleto($f['hasta']);

        if ($od === null || $oh === null || $od['serie'] !== $oh['serie'] || $od['serie'] !== $d['serie']) {
            continue;
        }

        $oDesdeN = min($od['numero'], $oh['numero']);
        $oHastaN = max($od['numero'], $oh['numero']);

        if ($desdeN <= $oHastaN && $oDesdeN <= $hastaN) {
            $donde = $f['aula'] !== null ? "el aula {$f['aula']}" : 'el coordinador';
            return "El rango {$desde}–{$hasta} se traslapa con la sede {$f['sede']} "
                 . "({$donde}, {$f['desde']}–{$f['hasta']}).";
        }
    }

    return null;
}

/**
 * Un folleto suelto (fuera del rango consecutivo — ver schema/046) no puede
 * caer DENTRO de un rango de otra sede, ni repetir otro suelto ya anotado en
 * otra sede. Mismo criterio que conflicto_rango_folleto(), pero para un solo
 * número en vez de un rango.
 */
function conflicto_suelto_folleto(int $conv, int $sedeId, string $codigo): ?string
{
    $c = partir_codigo_folleto($codigo);
    if ($c === null) {
        return null;
    }

    $rangos = db()->prepare("
        SELECT s.codigo AS sede, a.numero_aula AS aula, a.folleto_desde AS `desde`, a.folleto_hasta AS hasta
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = :conv AND a.sede_id <> :sede
          AND a.folleto_desde IS NOT NULL AND a.folleto_desde <> ''
          AND a.folleto_hasta IS NOT NULL AND a.folleto_hasta <> ''

        UNION ALL

        SELECT s.codigo, a.numero_aula, a.folleto_adec_desde, a.folleto_adec_hasta
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = :conv2 AND a.sede_id <> :sede2
          AND a.folleto_adec_desde IS NOT NULL AND a.folleto_adec_desde <> ''
          AND a.folleto_adec_hasta IS NOT NULL AND a.folleto_adec_hasta <> ''

        UNION ALL

        SELECT s.codigo, NULL, s.coord_folleto_desde, s.coord_folleto_hasta
        FROM sedes s
        WHERE s.convocatoria_id = :conv3 AND s.id <> :sede3
          AND s.coord_folleto_desde IS NOT NULL AND s.coord_folleto_desde <> ''
          AND s.coord_folleto_hasta IS NOT NULL AND s.coord_folleto_hasta <> ''

        UNION ALL

        SELECT s.codigo, NULL, s.coord_folleto_adec_desde, s.coord_folleto_adec_hasta
        FROM sedes s
        WHERE s.convocatoria_id = :conv4 AND s.id <> :sede4
          AND s.coord_folleto_adec_desde IS NOT NULL AND s.coord_folleto_adec_desde <> ''
          AND s.coord_folleto_adec_hasta IS NOT NULL AND s.coord_folleto_adec_hasta <> ''
    ");
    $rangos->execute([
        'conv' => $conv, 'sede' => $sedeId,
        'conv2' => $conv, 'sede2' => $sedeId,
        'conv3' => $conv, 'sede3' => $sedeId,
        'conv4' => $conv, 'sede4' => $sedeId,
    ]);

    foreach ($rangos->fetchAll() as $f) {
        $od = partir_codigo_folleto((string) $f['desde']);
        $oh = partir_codigo_folleto((string) $f['hasta']);

        if ($od === null || $oh === null || $od['serie'] !== $oh['serie'] || $od['serie'] !== $c['serie']) {
            continue;
        }

        $oDesdeN = min($od['numero'], $oh['numero']);
        $oHastaN = max($od['numero'], $oh['numero']);

        if ($c['numero'] >= $oDesdeN && $c['numero'] <= $oHastaN) {
            $donde = $f['aula'] !== null ? "el aula {$f['aula']}" : 'el coordinador';
            return "El folleto {$codigo} ya está dentro del rango de la sede {$f['sede']} "
                 . "({$donde}, {$f['desde']}–{$f['hasta']}).";
        }
    }

    $otrosSueltos = db()->prepare("
        SELECT s.codigo AS sede, a.numero_aula AS aula, a.folletos_sueltos AS sueltos
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        WHERE a.convocatoria_id = ? AND a.sede_id <> ?
          AND a.folletos_sueltos IS NOT NULL AND a.folletos_sueltos <> ''
    ");
    $otrosSueltos->execute([$conv, $sedeId]);

    foreach ($otrosSueltos->fetchAll() as $f) {
        foreach (codigos_sueltos((string) $f['sueltos']) as $otroCodigo) {
            $o = partir_codigo_folleto($otroCodigo);
            if ($o !== null && $o['serie'] === $c['serie'] && $o['numero'] === $c['numero']) {
                return "El folleto {$codigo} ya está anotado como suelto en la sede {$f['sede']} "
                     . "(aula {$f['aula']}).";
            }
        }
    }

    return null;
}

function guardar_folletos(): void
{
    $conv   = convocatoria_exigida();
    $sedeId = (int) param('sede_id');
    $aulas  = entrada()['aulas'] ?? null;

    if (!is_array($aulas) || $aulas === []) {
        responder_json(['success' => false, 'error' => 'No se recibió ningún aula'], 400);
    }

    $esBorrador = param('borrador') !== '';

    // El traslape entre sedes se revisa solo en el guardado final: en un
    // borrador (cada segundo mientras se teclea) frenaría a mitad de un
    // número a medio escribir, sin que la persona esté pidiendo guardar de
    // verdad todavía.
    if (!$esBorrador && $sedeId > 0) {
        foreach ($aulas as $a) {
            foreach ([['desde', 'hasta'], ['adecDesde', 'adecHasta']] as [$claveDesde, $claveHasta]) {
                $desde = trim((string) ($a[$claveDesde] ?? ''));
                $hasta = trim((string) ($a[$claveHasta] ?? ''));

                if ($desde === '' || $hasta === '') {
                    continue;
                }

                $conflicto = conflicto_rango_folleto($conv, $sedeId, $desde, $hasta);
                if ($conflicto !== null) {
                    responder_json(['success' => false, 'error' => $conflicto], 409);
                }
            }

            // Folletos sueltos (fuera del rango — ver schema/046): cada
            // código se revisa por separado, no como par desde/hasta.
            foreach (codigos_sueltos((string) ($a['sueltos'] ?? '')) as $codigo) {
                $conflicto = conflicto_suelto_folleto($conv, $sedeId, $codigo);
                if ($conflicto !== null) {
                    responder_json(['success' => false, 'error' => $conflicto], 409);
                }
            }
        }
    }

    $stmt = db()->prepare("
        UPDATE aulas
        SET folleto_desde = ?, folleto_hasta = ?,
            folleto_adec_desde = ?, folleto_adec_hasta = ?,
            folletos_sueltos = ?
        WHERE id = ? AND convocatoria_id = ?
    ");

    $guardadas = 0;
    db()->beginTransaction();

    foreach ($aulas as $a) {
        $id = (int) ($a['id'] ?? 0);
        if ($id === 0) {
            continue;
        }

        // Se guarda ya normalizado (un código por línea) para que se lea
        // igual de simple al volver a abrir la sede, sin importar si se
        // escribió separado por coma, punto y coma o Enter.
        $sueltos = implode("\n", codigos_sueltos((string) ($a['sueltos'] ?? '')));

        $stmt->execute([
            trim((string) ($a['desde'] ?? '')) ?: null,
            trim((string) ($a['hasta'] ?? '')) ?: null,
            trim((string) ($a['adecDesde'] ?? '')) ?: null,
            trim((string) ($a['adecHasta'] ?? '')) ?: null,
            $sueltos !== '' ? $sueltos : null,
            $id,
            $conv,
        ]);
        $guardadas++;
    }

    // Un borrador escribe igual pero no marca el paso. La diferencia importa:
    // el avance de la lista de sedes se calcula de los datos, y un guardado
    // automático a mitad de tecleo no debería hacer que la sede se vea
    // adelantada de lo que está.
    //
    // Tampoco entra a la bitácora: el navegador guarda solo cada vez que la
    // persona deja de teclear un segundo, y eso son decenas de filas por sede
    // que taparían el registro de lo que de verdad pasó.
    if (!$esBorrador) {
        marcar_paso($conv, $sedeId, 2);
    } else {
        bitacora_ya_apuntado(true);
    }

    db()->commit();

    responder_json(['success' => true, 'guardadas' => $guardadas]);
}

// ------------------------------------------------------------------
// Paso 3 · Folletos del coordinador
// ------------------------------------------------------------------

function guardar_coordinador(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);

    $desde = trim(param('desde'));
    $hasta = trim(param('hasta'));
    // Igual que en las aulas: el coordinador también puede traer, dentro de
    // sus folletos, un grupo con numeración de adecuación aparte.
    $adecDesde = trim(param('adecDesde'));
    $adecHasta = trim(param('adecHasta'));
    $esBorrador = param('borrador') !== '';

    // Hay sedes donde el coordinador simplemente no recibe folletos aparte:
    // no es un dato que falte, es que no existe. Sin esta marca, esas sedes
    // se quedaban en "proceso" para siempre porque el paso 3 nunca tenía
    // nada que mostrar como completo.
    $sinFolletos = param('sinFolletos') !== '';

    if (!$esBorrador && !$sinFolletos) {
        foreach ([[$desde, $hasta], [$adecDesde, $adecHasta]] as [$d, $h]) {
            if ($d === '' || $h === '') {
                continue;
            }

            $conflicto = conflicto_rango_folleto($conv, $sedeId, $d, $h);
            if ($conflicto !== null) {
                responder_json(['success' => false, 'error' => $conflicto], 409);
            }
        }
    }

    db()->prepare("
        UPDATE sedes
        SET coord_folleto_desde = ?, coord_folleto_hasta = ?,
            coord_folleto_adec_desde = ?, coord_folleto_adec_hasta = ?,
            coord_sin_folletos = ?
        WHERE id = ?
    ")->execute([
        $sinFolletos ? null : ($desde ?: null),
        $sinFolletos ? null : ($hasta ?: null),
        $sinFolletos ? null : ($adecDesde ?: null),
        $sinFolletos ? null : ($adecHasta ?: null),
        $sinFolletos ? 1 : 0,
        $sedeId,
    ]);

    if (!$esBorrador) {
        marcar_paso($conv, $sedeId, 3);
    } else {
        bitacora_ya_apuntado(true);   // ver guardar_folletos()
    }

    responder_json(['success' => true]);
}

// ------------------------------------------------------------------
// Paso 4 · Armado de las tulas
// ------------------------------------------------------------------

function guardar_tulas(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $tulas  = entrada()['tulas'] ?? null;

    if (!is_array($tulas)) {
        responder_json(['success' => false, 'error' => 'No se recibió el armado'], 400);
    }

    $vistas = [];
    foreach ($tulas as $t) {
        foreach ((array) ($t['aulas'] ?? []) as $aulaId) {
            $aulaId = (int) $aulaId;
            if (isset($vistas[$aulaId])) {
                responder_json([
                    'success' => false,
                    'error'   => 'Hay un aula puesta en dos tulas distintas',
                ], 409);
            }
            $vistas[$aulaId] = true;
        }
    }

    db()->beginTransaction();

    $stmt = db()->prepare("
        SELECT t.numero, m.posicion, m.codigo
        FROM tulas t INNER JOIN marchamos m ON m.tula_id = t.id
        WHERE t.sede_id = ? AND t.convocatoria_id = ?
    ");
    $stmt->execute([$sedeId, $conv]);

    $previos = [];
    foreach ($stmt->fetchAll() as $m) {
        $previos[(int) $m['numero']][(int) $m['posicion']] = $m['codigo'];
    }

    db()->prepare('DELETE FROM tulas WHERE sede_id = ? AND convocatoria_id = ?')
        ->execute([$sedeId, $conv]);

    $insTula = db()->prepare("
        INSERT INTO tulas (convocatoria_id, sede_id, numero, lleva_folletos_coord, codigo_barras)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insAula = db()->prepare('INSERT INTO tula_aulas (tula_id, aula_id, orden) VALUES (?, ?, ?)');
    $insMar  = db()->prepare("
        INSERT INTO marchamos (tula_id, convocatoria_id, posicion, codigo)
        VALUES (?, ?, ?, ?)
    ");

    $creadas = 0;

    foreach ($tulas as $i => $t) {
        $numero = (int) ($t['numero'] ?? ($i + 1));
        $codigo = param('sede') . '-T' . $numero;

        $insTula->execute([
            $conv, $sedeId, $numero,
            !empty($t['llevaCoord']) ? 1 : 0,
            $codigo,
        ]);
        $tulaId = (int) db()->lastInsertId();

        foreach (array_values((array) ($t['aulas'] ?? [])) as $orden => $aulaId) {
            $insAula->execute([$tulaId, (int) $aulaId, $orden]);
        }

        foreach ($previos[$numero] ?? [] as $posicion => $codigoMarchamo) {
            try {
                $insMar->execute([$tulaId, $conv, $posicion, $codigoMarchamo]);
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        $creadas++;
    }

    marcar_paso($conv, $sedeId, 4);
    db()->commit();

    responder_json(['success' => true, 'tulas' => $creadas]);
}

// ------------------------------------------------------------------
// Paso 5 · Marchamos
// ------------------------------------------------------------------

function guardar_marchamos(): void
{
    $conv = convocatoria_exigida();
    $lista = entrada()['marchamos'] ?? null;

    if (!is_array($lista) || $lista === []) {
        responder_json(['success' => false, 'error' => 'No se recibió ningún marchamo'], 400);
    }

    $borrar = db()->prepare('DELETE FROM marchamos WHERE tula_id = ? AND posicion = ?');
    $poner  = db()->prepare("
        INSERT INTO marchamos (tula_id, convocatoria_id, posicion, codigo)
        VALUES (?, ?, ?, ?)
    ");
    $donde = db()->prepare("
        SELECT s.codigo AS sede, t.numero
        FROM marchamos m
        INNER JOIN tulas t ON t.id = m.tula_id
        INNER JOIN sedes s ON s.id = t.sede_id
        WHERE m.convocatoria_id = ? AND m.codigo = ?
    ");

    // Qué sede es cada tula: hace falta para marcar el paso 5 al final, y las
    // tulas de esta lista casi siempre son todas de la misma sede, pero no se
    // asume — se saca de la base, no del cliente.
    $tulaIds = array_values(array_unique(array_filter(
        array_map(static fn(array $m): int => (int) ($m['tulaId'] ?? 0), $lista)
    )));
    $sedesPorTula = [];
    if ($tulaIds !== []) {
        $marcas = implode(',', array_fill(0, count($tulaIds), '?'));
        $stmtSedes = db()->prepare("SELECT id, sede_id FROM tulas WHERE id IN ({$marcas})");
        $stmtSedes->execute($tulaIds);
        foreach ($stmtSedes->fetchAll() as $t) {
            $sedesPorTula[(int) $t['id']] = (int) $t['sede_id'];
        }
    }
    $sedesTocadas = array_unique(array_values($sedesPorTula));

    $aplicados = 0;
    $rechazados = [];

    db()->beginTransaction();

    foreach ($lista as $m) {
        $tulaId = (int) ($m['tulaId'] ?? 0);
        if ($tulaId === 0) {
            continue;
        }

        foreach ([1 => 'marchamo1', 2 => 'marchamo2'] as $posicion => $clave) {
            $codigo = trim((string) ($m[$clave] ?? ''));

            $borrar->execute([$tulaId, $posicion]);

            // Vacío es una decisión, no un dato que falte todavía: si alguien
            // borra un código y guarda, es porque se equivocó y quiere dejar
            // la tula sin ese marchamo por ahora. $borrar ya lo quitó; acá no
            // hay nada que insertar.
            if ($codigo === '') {
                continue;
            }

            try {
                $poner->execute([$tulaId, $conv, $posicion, $codigo]);
                $aplicados++;
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                $donde->execute([$conv, $codigo]);
                $ocupado = $donde->fetch();

                $rechazados[] = [
                    'tulaId' => $tulaId,
                    'codigo' => $codigo,
                    'motivo' => $ocupado !== false
                        ? "Ya está en la sede {$ocupado['sede']}, tula {$ocupado['numero']}"
                        : 'Ese marchamo ya está asignado',
                ];
            }
        }
    }

    db()->commit();

    // Paso 5 de cada sede tocada. marcar_paso() de paso anota quién participó
    // (ranking amistoso) y revisa si con este guardado la sede quedó completa.
    foreach ($sedesTocadas as $sedeTocada) {
        marcar_paso($conv, $sedeTocada, 5);
    }

    responder_json([
        'success'    => true,
        'aplicados'  => $aplicados,
        'rechazados' => $rechazados,
    ]);
}

// ------------------------------------------------------------------
// Borrar el avance
// ------------------------------------------------------------------

function borrar_avance(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $codigo = param('sede');
    $sedeId = sede_exigida($codigo, $conv);

    $stmt = db()->prepare("
        SELECT COUNT(*) FROM tulas
        WHERE sede_id = ? AND convocatoria_id = ? AND estado <> 'disponible'
    ");
    $stmt->execute([$sedeId, $conv]);
    $enMovimiento = (int) $stmt->fetchColumn();

    if ($enMovimiento > 0) {
        responder_json([
            'success' => false,
            'error'   => "No se puede borrar: {$enMovimiento} tula(s) de esta sede ya "
                       . "salieron de la bodega. Primero hay que registrarlas de vuelta "
                       . "en el módulo de Tulas.",
        ], 409);
    }

    db()->beginTransaction();

    $stmt = db()->prepare('DELETE FROM tulas WHERE sede_id = ? AND convocatoria_id = ?');
    $stmt->execute([$sedeId, $conv]);
    $tulasBorradas = $stmt->rowCount();

    $stmt = db()->prepare("
        UPDATE aulas SET folleto_desde = NULL, folleto_hasta = NULL
        WHERE sede_id = ? AND convocatoria_id = ?
    ");
    $stmt->execute([$sedeId, $conv]);
    $aulasLimpiadas = $stmt->rowCount();

    db()->prepare("
        UPDATE sedes SET coord_folleto_desde = NULL, coord_folleto_hasta = NULL
        WHERE id = ?
    ")->execute([$sedeId]);

    db()->prepare('DELETE FROM revisiones_sede WHERE sede_id = ? AND convocatoria_id = ?')
        ->execute([$sedeId, $conv]);

    // Las observaciones son parte del avance: si se borra todo lo demás para
    // arrancar de cero, dejarlas sería mostrar notas de un embalaje que ya
    // no existe.
    db()->prepare('DELETE FROM sede_observaciones WHERE sede_id = ? AND convocatoria_id = ?')
        ->execute([$sedeId, $conv]);

    // Liberar bloqueo si existe
    desbloquear_sede_interno($conv, $sedeId);

    db()->commit();

    responder_json([
        'success' => true,
        'mensaje' => "Avance borrado: {$tulasBorradas} tula(s) eliminadas y "
                   . "{$aulasLimpiadas} aula(s) sin folletos. La sede vuelve a estar sin empezar.",
        'tulas'   => $tulasBorradas,
        'aulas'   => $aulasLimpiadas,
    ]);
}

// ------------------------------------------------------------------
// NUEVO: Sistema de segmentos y bloqueos
// ------------------------------------------------------------------

/** Quién tiene tomada cada sede en este momento. */
function obtener_bloqueos(): void
{
    $conv = convocatoria_exigida();

    responder_json([
        'success'     => true,
        'bloqueos'    => obtener_bloqueos_activos($conv),
        'sinBloqueos' => !hay_tabla_de_bloqueos(),
    ]);
}

/**
 * Toma una sede, o avisa quién la tiene.
 *
 * SIRVE TAMBIÉN DE LATIDO: el navegador la vuelve a llamar cada medio minuto
 * mientras la sede está abierta, y cada llamada refresca `visto_en`. Así un
 * bloqueo vivo nunca vence y uno abandonado vence solo.
 *
 * NO ES UN CANDADO DURO. Con `forzar` se entra igual a una sede tomada, porque
 * a veces la persona ya no está. Lo que no se puede es entrar sin enterarse.
 */
function bloquear_sede(array $yo): void
{
    exigir(['sede', 'sesion']);

    // Sin la tabla no hay avisos de ocupación, pero se entra a la sede igual:
    // el trabajo es meter los datos, no saber quién está dónde.
    if (!hay_tabla_de_bloqueos()) {
        bitacora_ya_apuntado(true);
        responder_json(['success' => true, 'sinBloqueos' => true]);
    }

    $conv     = convocatoria_exigida();
    $codigo   = param('sede');
    $sedeId   = sede_exigida($codigo, $conv);
    $sesion   = param('sesion');
    $quien    = param('quien') ?: $yo['nombre'];
    $segmento = max(1, param_int('segmento', 1));
    $forzar   = param('forzar') !== '';

    // La identidad NO la decide el cliente por su cuenta: la sesión es un valor
    // opaco que el navegador genera, pero el usuario sale de la sesión de PHP.
    // Si viniera del cliente, cualquiera podría escribir el nombre de otro.
    $usuario = (string) ($yo['usuario'] ?? $yo['nombre']);

    $duenio = obtener_bloqueos_activos($conv)[$codigo] ?? null;
    $ajeno  = $duenio !== null && $duenio['sesion'] !== $sesion;

    if ($ajeno && !$forzar) {
        responder_json([
            'success'    => false,
            'ocupada'    => true,
            'ocupadaPor' => $duenio['quien'],
            'desdeHace'  => $duenio['hace'],
            'error'      => sprintf(
                'La sede %s la está trabajando %s (última señal hace %d minuto(s)). '
                . 'Hablá con esa persona antes de entrar: si los dos escriben, el segundo '
                . 'pisa lo del primero.',
                $codigo, $duenio['quien'], $duenio['hace']
            ),
        ], 409);
    }

    // Un solo INSERT ... ON DUPLICATE KEY y no leer-y-después-escribir: entre
    // dos personas apretando a la vez, lo segundo deja pasar a las dos.
    db()->prepare('
        INSERT INTO sedes_bloqueos
            (convocatoria_id, sede_id, sesion, quien, usuario, segmento)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            sesion = VALUES(sesion), quien = VALUES(quien),
            usuario = VALUES(usuario), segmento = VALUES(segmento),
            visto_en = NOW()
    ')->execute([$conv, $sedeId, $sesion, mb_substr($quien, 0, 80), $usuario, $segmento]);

    // Esta misma acción sirve de latido, y el navegador la llama cada treinta
    // segundos mientras la sede está abierta. Registrar cada latido llenaría la
    // bitácora de ruido y enterraría justo lo que se quiere encontrar ahí.
    //
    // Se anota cuando la sede CAMBIA de manos —que es el hecho— y se silencia
    // cuando es la misma persona diciendo que sigue ahí.
    if ($duenio === null || $duenio['sesion'] !== $sesion) {
        bitacora_apuntar(
            'ingreso_datos',
            $ajeno ? 'forzarSede' : 'tomarSede',
            $ajeno
                ? "sede={$codigo} la tenia={$duenio['quien']} la tomo={$quien}"
                : "sede={$codigo} quien={$quien}"
        );
    } else {
        bitacora_ya_apuntado(true);
    }

    responder_json(['success' => true, 'forzado' => $ajeno]);
}

/**
 * Suelta una sede.
 *
 * Solo la suelta quien la tenía: si alguien más entró forzando, el bloqueo ya
 * es de esa persona y no hay que quitárselo al salir.
 */
function desbloquear_sede(): void
{
    exigir(['sede', 'sesion']);

    if (!hay_tabla_de_bloqueos()) {
        bitacora_ya_apuntado(true);
        responder_json(['success' => true, 'sinBloqueos' => true]);
    }

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);

    db()->prepare('
        DELETE FROM sedes_bloqueos
        WHERE convocatoria_id = ? AND sede_id = ? AND sesion = ?
    ')->execute([$conv, $sedeId, param('sesion')]);

    responder_json(['success' => true]);
}

// ------------------------------------------------------------------
// Funciones internas de bloqueo
// ------------------------------------------------------------------

/**
 * ¿Existe la tabla de bloqueos?
 *
 * POR QUÉ SE PREGUNTA EN VEZ DE DARLO POR HECHO
 * Saber quién trabaja en cada sede es una comodidad; meter los datos es el
 * trabajo. Cuando la tabla faltaba —porque su migración no se había corrido—
 * la consulta reventaba dentro de `listar_sedes`, y el módulo entero dejaba de
 * abrir: ni lista de sedes, ni captura, nada. Una función accesoria tumbando a
 * la principal.
 *
 * Ahora, si la tabla no está, el módulo funciona sin avisos de ocupación y lo
 * dice en pantalla. Se pierde la comodidad, no el día de trabajo.
 */
function hay_tabla_de_bloqueos(): bool
{
    static $hay = null;

    if ($hay !== null) {
        return $hay;
    }

    try {
        // Se pide una columna de la forma nueva, no `1`.
        //
        // Preguntar solo por la tabla no sirve: en algunos servidores existía
        // con otra forma —creada a mano, con `usuario` y `timestamp` en vez de
        // `sesion` y `visto_en`— y la comprobación pasaba mientras las
        // consultas de verdad seguían fallando. Lo que hay que comprobar es si
        // la tabla sirve, no si el nombre está ocupado.
        db()->query('SELECT sesion, quien, visto_en FROM sedes_bloqueos LIMIT 1');
        $hay = true;
    } catch (PDOException $e) {
        error_log('[ingreso_datos] `sedes_bloqueos` no existe o está desactualizada: '
                . 'falta correr php scripts/migrar.php');
        $hay = false;
    }

    return $hay;
}

/**
 * Los bloqueos con señales de vida recientes, por código de sede.
 *
 * Los vencidos no se borran acá: se ignoran. Borrarlos en una consulta de
 * lectura convertiría cada refresco de pantalla en una escritura, y con varias
 * personas mirando la lista a la vez eso es tráfico de sobra. Se limpian solos
 * cuando alguien vuelve a tomar la sede.
 */
function obtener_bloqueos_activos(int $conv): array
{
    if (!hay_tabla_de_bloqueos()) {
        return [];
    }

    $stmt = db()->prepare('
        SELECT s.codigo, b.sesion, b.quien, b.usuario, b.segmento,
               TIMESTAMPDIFF(MINUTE, b.visto_en, NOW()) AS hace
        FROM sedes_bloqueos b
        INNER JOIN sedes s ON s.id = b.sede_id
        WHERE b.convocatoria_id = ?
          AND b.visto_en > DATE_SUB(NOW(), INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE)
    ');
    $stmt->execute([$conv]);

    $activos = [];

    foreach ($stmt->fetchAll() as $f) {
        $activos[(string) $f['codigo']] = [
            'sesion'   => $f['sesion'],
            'quien'    => $f['quien'],
            'segmento' => (int) $f['segmento'],
            'hace'     => (int) $f['hace'],
        ];
    }

    return $activos;
}

/** Suelta una sede sin mirar de quién era. Para cuando se borra su avance. */
function desbloquear_sede_interno(int $conv, int $sedeId): void
{
    db()->prepare('
        DELETE FROM sedes_bloqueos WHERE convocatoria_id = ? AND sede_id = ?
    ')->execute([$conv, $sedeId]);
}

// ------------------------------------------------------------------
// Utilidades
// ------------------------------------------------------------------

function marcar_paso(int $conv, int $sedeId, int $paso): void
{
    if ($sedeId === 0) {
        return;
    }

    db()->prepare("
        INSERT INTO revisiones_sede (convocatoria_id, sede_id, paso)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE paso = GREATEST(paso, VALUES(paso))
    ")->execute([$conv, $sedeId, $paso]);

    // Ranking amistoso (ver schema/025_ranking_ingreso.sql): cada guardado
    // final dice quién le puso la mano a esta sede, y de paso se revisa si
    // con esto quedó completa para repartir el logro.
    $quien = trim(param('quien'));
    if ($quien !== '') {
        ingreso_registrar_participacion($conv, $sedeId, $quien);
    }
    ingreso_intentar_logro($conv, $sedeId);
}

// ------------------------------------------------------------------
// Ranking amistoso
// ------------------------------------------------------------------

/** Anota que esta persona trabajó en esta sede. Se actualiza en cada guardado, no solo la primera vez. */
function ingreso_registrar_participacion(int $conv, int $sedeId, string $quien): void
{
    db()->prepare('
        INSERT INTO ingreso_participacion (sede_id, convocatoria_id, quien, ultima_en)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE ultima_en = NOW()
    ')->execute([$sedeId, $conv, mb_substr($quien, 0, 80)]);
}

/**
 * Si esta sede acaba de quedar completa y todavía no tiene logro registrado,
 * reparte el crédito en partes iguales entre quienes participaron.
 *
 * El estado de "completa" se recalcula acá con la misma regla que
 * sedes_con_estado() (ver includes/sedes_datos.php) en vez de reutilizarla,
 * porque esa función trae TODAS las sedes de la convocatoria de una vez —
 * pedirle una sola sede en medio de cada guardado sería recalcular 316 de
 * más por cada una que sí importa.
 */
function ingreso_intentar_logro(int $conv, int $sedeId): void
{
    $stmtLogro = db()->prepare('SELECT COUNT(*) FROM ingreso_logros WHERE sede_id = ? AND convocatoria_id = ?');
    $stmtLogro->execute([$sedeId, $conv]);
    if ((int) $stmtLogro->fetchColumn() > 0) {
        return; // ya se repartió el crédito de esta sede
    }

    $stmt = db()->prepare("
        SELECT
            COUNT(a.id) AS aulas,
            SUM(a.folleto_desde IS NOT NULL AND a.folleto_desde <> '') AS aulas_con_folletos,
            (SELECT coord_folleto_desde FROM sedes WHERE id = :sede1) AS coord_desde,
            (SELECT coord_sin_folletos FROM sedes WHERE id = :sede1b) AS coord_sin_folletos,
            (SELECT COUNT(*) FROM tulas t
              WHERE t.sede_id = :sede2 AND t.convocatoria_id = :conv2) AS tulas,
            (SELECT COUNT(*) FROM tulas t
               INNER JOIN marchamos m ON m.tula_id = t.id
              WHERE t.sede_id = :sede3 AND t.convocatoria_id = :conv3) AS marchamos
        FROM aulas a
        WHERE a.sede_id = :sede4 AND a.convocatoria_id = :conv4
    ");
    $stmt->execute([
        'sede1' => $sedeId, 'sede1b' => $sedeId,
        'sede2' => $sedeId, 'conv2' => $conv,
        'sede3' => $sedeId, 'conv3' => $conv,
        'sede4' => $sedeId, 'conv4' => $conv,
    ]);
    $f = $stmt->fetch();

    if ($f === false) {
        return;
    }

    $aulas    = (int) $f['aulas'];
    $conFoll  = (int) $f['aulas_con_folletos'];
    $tulas    = (int) $f['tulas'];
    $marchamos = (int) $f['marchamos'];

    $completa = $aulas > 0 && $conFoll >= $aulas
        && (($f['coord_desde'] !== null && $f['coord_desde'] !== '') || (bool) $f['coord_sin_folletos'])
        && $tulas > 0
        && $marchamos >= $tulas * 2;

    if (!$completa) {
        return;
    }

    $participantes = db()->prepare('SELECT quien FROM ingreso_participacion WHERE sede_id = ? AND convocatoria_id = ?');
    $participantes->execute([$sedeId, $conv]);
    $quienes = $participantes->fetchAll(PDO::FETCH_COLUMN);

    // Nadie escribió su nombre en "Soy: ___" mientras se trabajó esta sede:
    // no hay a quién anotarle el logro. No es un error, simplemente no
    // participa del ranking.
    if ($quienes === []) {
        return;
    }

    $fraccion = round(1 / count($quienes), 4);
    $insertar = db()->prepare('
        INSERT INTO ingreso_logros (sede_id, convocatoria_id, quien, fraccion)
        VALUES (?, ?, ?, ?)
    ');
    foreach ($quienes as $quien) {
        $insertar->execute([$sedeId, $conv, $quien, $fraccion]);
    }
}

/**
 * El panel de ranking: totales, hoy, récord del día y quién tiene algo en
 * proceso ahora mismo.
 *
 * A propósito NO filtra por convocatoria activa: son datos generales, de
 * todo lo que se ha completado alguna vez. Filtrar por convocatoria activa
 * escondía el ranking entero cada vez que esa marca quedaba mal puesta (ya
 * pasó una vez, ver el arreglo de sedes duplicadas) — un juego amistoso no
 * necesita esa precisión, y así deja de depender de que nadie se equivoque
 * marcando cuál convocatoria es la activa.
 */
function ranking_ingreso(): void
{
    $totales = db()->query('
        SELECT quien, SUM(fraccion) AS total, COUNT(DISTINCT sede_id) AS sedes
        FROM ingreso_logros
        GROUP BY quien ORDER BY total DESC LIMIT 15
    ');

    $hoy = db()->query('
        SELECT quien, SUM(fraccion) AS total, COUNT(DISTINCT sede_id) AS sedes
        FROM ingreso_logros
        WHERE DATE(completada_en) = CURDATE()
        GROUP BY quien ORDER BY total DESC LIMIT 15
    ');

    $record = db()->query('
        SELECT quien, DATE(completada_en) AS dia, SUM(fraccion) AS total
        FROM ingreso_logros
        GROUP BY quien, DATE(completada_en)
        ORDER BY total DESC LIMIT 1
    ');
    $recordFila = $record->fetch();

    // "En proceso ahora": de las sedes donde cada quien dejó su nombre, las
    // que todavía no tienen logro registrado (o sea, no están completas).
    $sedesCompletas = array_flip(
        db()->query('SELECT DISTINCT sede_id FROM ingreso_logros')->fetchAll(PDO::FETCH_COLUMN)
    );

    $participacion = db()->query('SELECT DISTINCT sede_id, quien FROM ingreso_participacion');

    $enProceso = [];
    foreach ($participacion->fetchAll() as $p) {
        if (!isset($sedesCompletas[(int) $p['sede_id']])) {
            $enProceso[$p['quien']] = ($enProceso[$p['quien']] ?? 0) + 1;
        }
    }
    arsort($enProceso);

    responder_json([
        'success' => true,
        'totales' => array_map(static fn(array $f): array => [
            'quien' => $f['quien'], 'total' => round((float) $f['total'], 2), 'sedes' => (int) $f['sedes'],
        ], $totales->fetchAll()),
        'hoy' => array_map(static fn(array $f): array => [
            'quien' => $f['quien'], 'total' => round((float) $f['total'], 2), 'sedes' => (int) $f['sedes'],
        ], $hoy->fetchAll()),
        'record' => $recordFila ? [
            'quien' => $recordFila['quien'],
            'dia'   => $recordFila['dia'],
            'total' => round((float) $recordFila['total'], 2),
        ] : null,
        'enProceso' => array_map(
            static fn(string $quien, int $sedes): array => ['quien' => $quien, 'sedes' => $sedes],
            array_keys($enProceso), array_values($enProceso)
        ),
    ]);
}