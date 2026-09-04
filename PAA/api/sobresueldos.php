<?php
/**
 * Endpoint del módulo Sobresueldos.
 *
 * Reemplaza a SobreSueldos_AppScript.gs y al cruce de tres Google Sheets que
 * la página hacía en el navegador (presupuesto, coordinadores y hospedaje).
 *
 *   GET  accion=getSedes      → presupuesto por sede, con coordinador y hospedaje
 *   POST accion=guardarSede   → guarda una sede
 *   POST accion=guardarTodas  → guarda varias de una vez
 *
 * EL CAMBIO MÁS IMPORTANTE: SE ACABÓ EL rowIndex
 * El Apps Script escribía con getRange(rowIndex, 4, 1, 12), es decir,
 * identificaba la sede por su NÚMERO DE FILA en el Sheet. Bastaba con que
 * alguien insertara o borrara una fila para que el presupuesto se guardara en
 * la sede equivocada, sin ningún aviso. Acá se identifica por sede y
 * convocatoria, que es lo que la fila representaba.
 *
 * Se mantienen los límites de longitud del texto y la validación numérica que
 * hacía valoresValidados_().
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("sobresueldos");


exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// El desglose es el campo más largo; nada debería pasarse de esto.
const LIMITE_TEXTO = 5000;

$accion = param('accion', param('action', 'getSedes'));

try {
    switch ($accion) {
        case 'getSedes':      get_sedes();      break;
        case 'guardarSede':   guardar_sede();   break;
        case 'guardarTodas':  guardar_todas();  break;
        case 'coordinadores': coordinadores();  break;
        case 'hospedaje':
        case 'tarifasHospedaje': tarifas_hospedaje(); break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('sobresueldos', $e);
}

// ------------------------------------------------------------------

function get_sedes(): void
{
    $convocatoriaId = convocatoria_actual();

    if ($convocatoriaId === null) {
        responder_json([
            'success' => false,
            'ok'      => false,
            'error'   => 'No hay ninguna convocatoria activa',
        ], 409);
    }

    // El LEFT JOIN con sedes (y no al revés) hace que aparezcan también las
    // sedes que todavía no tienen presupuesto cargado: en el Sheet había que
    // acordarse de agregar la fila a mano.
    //
    // El mismo id va tres veces con nombres distintos (:conv1, :conv2, :conv3)
    // porque PDO está configurado con consultas preparadas reales, y ahí un
    // placeholder nombrado no se puede repetir.
    $stmt = db()->prepare("
        SELECT
            s.id            AS sede_id,
            s.codigo        AS sede,
            s.nombre        AS nombre_sede,
            ss.id           AS sobresueldo_id,
            ss.descripcion, ss.aulas,
            ss.coord_regular, ss.coord_adecuacion,
            ss.aplicador, ss.apoyo, ss.una, ss.ucr, ss.servicio_conserjeria,
            ss.dia1, ss.dia2, ss.dia3, ss.presupuesto, ss.desglose,
            ss.actualizado_en
            -- Acá había dos subconsultas que sumaban el hospedaje de cada sede
            -- contra la tabla `hospedajes`. Esa tabla se eliminó en la
            -- migración 012: el Sheet de hospedaje no era un registro de
            -- gastos por sede sino un catálogo de tarifas por ubicación, que
            -- la página consulta aparte (acción tarifasHospedaje).
        FROM sedes s
        LEFT JOIN sobresueldos ss
               ON ss.sede_id = s.id AND ss.convocatoria_id = :conv1
        WHERE s.activo = 1 AND s.convocatoria_id = :conv4
        ORDER BY CAST(s.codigo AS UNSIGNED), s.codigo
    ");
    $stmt->execute([
        'conv1' => $convocatoriaId,
        'conv4' => $convocatoriaId,
    ]);

    $sedes = array_map(static function (array $f): array {
        return [
            // La pantalla identifica cada fila con `rowIndex`: era el número de
            // fila del Sheet y lo usa para los ids del DOM y para decir qué
            // guardar. Se le pasa el código de la sede, que es estable (el
            // número de fila cambiaba al insertar filas y hacía que se guardara
            // el presupuesto en la sede equivocada) y también numérico, así que
            // el parseInt que hace la página sigue funcionando.
            'rowIndex'     => $f['sede'],

            // Las mismas claves que traía el Sheet, para que la página no
            // tenga que cambiar cómo las lee.
            'SEDE_EXAMEN'  => $f['sede'],
            'DESCRIPCION'  => $f['nombre_sede'],
            'AULAS'        => (string) ($f['aulas'] ?? 0),
            'COORD_REGULAR'=> $f['coord_regular'] ?? '',
            'COORD_ADECUACION' => $f['coord_adecuacion'] ?? '',
            'APLICADOR'    => $f['aplicador'] ?? '',
            'APOYO'        => $f['apoyo'] ?? '',
            'UNA'          => $f['una'] ?? '',
            'UCR'          => $f['ucr'] ?? '',
            'CONSERJE'     => $f['servicio_conserjeria'] ?? '',
            'DIA1'         => $f['dia1'] ?? '',
            'DIA2'         => $f['dia2'] ?? '',
            'DIA3'         => $f['dia3'] ?? '',
            'PRESUPUESTO'  => (string) ($f['presupuesto'] ?? 0),
            'DESGLOSE'     => $f['desglose'] ?? '',

            'sedeId'       => (int) $f['sede_id'],
            'sede'         => $f['sede'],
            'nombreSede'   => $f['nombre_sede'],
            'descripcion'  => $f['descripcion'] ?? '',
            'aulas'        => (int) ($f['aulas'] ?? 0),
            'coordinador'  => $f['coord_regular'] ?? '',
            'coordAdecuacion' => $f['coord_adecuacion'] ?? '',
            'aplicador'    => $f['aplicador'] ?? '',
            'apoyo'        => $f['apoyo'] ?? '',
            'una'          => $f['una'] ?? '',
            'ucr'          => $f['ucr'] ?? '',
            'conserje'     => $f['servicio_conserjeria'] ?? '',
            'dia1'         => $f['dia1'] ?? '',
            'dia2'         => $f['dia2'] ?? '',
            'dia3'         => $f['dia3'] ?? '',
            'presupuesto'  => (int) ($f['presupuesto'] ?? 0),
            'desglose'     => $f['desglose'] ?? '',
            'actualizado'  => $f['actualizado_en'] ?? null,
        ];
    }, $stmt->fetchAll());

    responder_json([
        'success' => true,
        'ok'      => true,   // el frontend viejo lee "ok"
        'sedes'   => $sedes,
        'total'   => count($sedes),
    ]);
}

/**
 * Personal para el selector de coordinador de cada sede.
 *
 * Antes salía de un tercer Google Sheet (1mOILkUx…) con columnas Nombre,
 * Apellido1, Apellido2 y EmailUCR. Ahora sale de `personas`, el mismo catálogo
 * del Directorio de Funcionarios: era otra copia de la misma gente.
 *
 * Se responde con las claves que espera la página. El nombre no se parte en
 * nombre y apellidos porque en la base está completo y partirlo por espacios
 * daría resultados equivocados con los apellidos compuestos.
 */
function coordinadores(): void
{
    $stmt = db()->query("
        SELECT p.id, p.nombre, p.correo
        FROM personas p
        INNER JOIN areas a ON a.id = p.area_id
        WHERE p.activo = 1 AND p.oculto = 0 AND a.nombre <> 'IIP'
        ORDER BY p.nombre
    ");

    $lista = array_map(static function (array $f): array {
        return [
            'Nombre'         => $f['nombre'],
            'Apellido1'      => '',
            'Apellido2'      => '',
            'EmailUCR'       => $f['correo'] ?? '',
            'Número de ID'   => (string) $f['id'],
            'nombreCompleto' => $f['nombre'],
            'email'          => $f['correo'] ?? '',
            'id'             => (string) $f['id'],
        ];
    }, $stmt->fetchAll());

    responder_json(['success' => true, 'ok' => true, 'coordinadores' => $lista]);
}

/**
 * Catálogo de tarifas de hospedaje por provincia, cantón y distrito.
 *
 * La página lo usa para calcular el presupuesto: se elige la ubicación en tres
 * selectores y de ahí sale el monto. Ver schema/012_tarifas_hospedaje.sql.
 */
function tarifas_hospedaje(): void
{
    $stmt = db()->query("
        SELECT provincia, canton, distrito, tarifa
        FROM tarifas_hospedaje
        ORDER BY provincia, canton, distrito
    ");

    $tarifas = array_map(static function (array $f): array {
        return [
            'Provincia' => $f['provincia'],
            'Canton'    => $f['canton'],
            'Distrito'  => $f['distrito'],
            'Tarifa'    => (string) $f['tarifa'],
        ];
    }, $stmt->fetchAll());

    responder_json(['success' => true, 'ok' => true, 'tarifas' => $tarifas]);
}

/**
 * Valida y recorta los campos, como hacía valoresValidados_ en Apps Script.
 *
 * @return array<string, string|int|null>
 */
function valores_validados(array $d): array
{
    $texto = static function ($v): ?string {
        $s = trim((string) ($v ?? ''));
        if ($s === '') {
            return null;
        }
        // Recorte duro: protege la columna y, sobre todo, evita que alguien
        // use el campo como depósito de contenido arbitrario.
        return mb_substr($s, 0, LIMITE_TEXTO);
    };

    return [
        'descripcion' => $texto($d['descripcion'] ?? null),
        'aulas'       => (int) ($d['aulas'] ?? 0),
        'coord'       => $texto($d['coordinador'] ?? $d['coord_regular'] ?? null),
        'coord_adec'  => $texto($d['coordAdecuacion'] ?? $d['coord_adecuacion'] ?? null),
        'aplicador'   => $texto($d['aplicador'] ?? null),
        'apoyo'       => $texto($d['apoyo'] ?? null),
        'una'         => $texto($d['una'] ?? null),
        'ucr'         => $texto($d['ucr'] ?? null),
        'conserje'    => $texto($d['conserje'] ?? $d['servicio_conserjeria'] ?? null),
        'dia1'        => $texto($d['dia1'] ?? null),
        'dia2'        => $texto($d['dia2'] ?? null),
        'dia3'        => $texto($d['dia3'] ?? null),
        'presupuesto' => (int) preg_replace('/[^0-9\-]/', '', (string) ($d['presupuesto'] ?? '0')),
        'desglose'    => $texto($d['desglose'] ?? null),
    ];
}

/** Guarda (o crea) la fila de presupuesto de una sede. */
function guardar_una(int $convocatoriaId, string $codigoSede, array $datos): bool
{
    // La sede se busca dentro de la convocatoria que se está guardando: el
    // mismo código pertenece a otra sede en otro año.
    $sedeId = id_sede_por_codigo($codigoSede, $convocatoriaId);

    if ($sedeId === null) {
        return false;
    }

    $v = valores_validados($datos);

    $stmt = db()->prepare("
        INSERT INTO sobresueldos (
            convocatoria_id, sede_id, descripcion, aulas,
            coord_regular, coord_adecuacion, aplicador, apoyo, una, ucr,
            servicio_conserjeria, dia1, dia2, dia3, presupuesto, desglose
        ) VALUES (
            :conv, :sede, :descripcion, :aulas,
            :coord, :coord_adec, :aplicador, :apoyo, :una, :ucr,
            :conserje, :dia1, :dia2, :dia3, :presupuesto, :desglose
        )
        ON DUPLICATE KEY UPDATE
            descripcion = VALUES(descripcion),
            aulas = VALUES(aulas),
            coord_regular = VALUES(coord_regular),
            coord_adecuacion = VALUES(coord_adecuacion),
            aplicador = VALUES(aplicador),
            apoyo = VALUES(apoyo),
            una = VALUES(una),
            ucr = VALUES(ucr),
            servicio_conserjeria = VALUES(servicio_conserjeria),
            dia1 = VALUES(dia1),
            dia2 = VALUES(dia2),
            dia3 = VALUES(dia3),
            presupuesto = VALUES(presupuesto),
            desglose = VALUES(desglose)
    ");

    $stmt->execute(array_merge($v, [
        'conv' => $convocatoriaId,
        'sede' => (int) $sedeId,
    ]));

    return true;
}

function guardar_sede(): void
{
    // La página manda `rowIndex`, que ahora contiene el código de la sede
    // (ver el comentario en get_sedes). Se acepta cualquiera de los dos.
    $codigoSede = param('sede', param('rowIndex'));

    if ($codigoSede === '') {
        responder_json(['success' => false, 'ok' => false, 'error' => 'Falta la sede'], 400);
    }

    $convocatoriaId = convocatoria_actual();
    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay convocatoria activa'], 409);
    }

    $datos = entrada()['datos'] ?? entrada();

    if (!guardar_una($convocatoriaId, $codigoSede, is_array($datos) ? $datos : [])) {
        responder_json([
            'success' => false,
            'ok'      => false,
            'error'   => "La sede {$codigoSede} no existe en esta convocatoria",
        ], 404);
    }

    responder_json(['success' => true, 'ok' => true, 'mensaje' => 'Sede guardada']);
}

function guardar_todas(): void
{
    $convocatoriaId = convocatoria_actual();
    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay convocatoria activa'], 409);
    }

    $sedes = entrada()['sedes'] ?? [];
    if (!is_array($sedes) || $sedes === []) {
        responder_json(['success' => false, 'error' => 'No se recibió ninguna sede'], 400);
    }

    $guardadas = 0;
    $omitidas  = [];

    db()->beginTransaction();

    foreach ($sedes as $s) {
        $codigo = trim((string) ($s['sede'] ?? ''));
        $datos  = $s['datos'] ?? $s;

        if ($codigo === '' || !guardar_una($convocatoriaId, $codigo, is_array($datos) ? $datos : [])) {
            $omitidas[] = $codigo !== '' ? $codigo : '(sin código)';
            continue;
        }

        $guardadas++;
    }

    db()->commit();

    responder_json([
        'success'  => true,
        'ok'       => true,
        'mensaje'  => "{$guardadas} sedes guardadas",
        'omitidas' => $omitidas,
    ]);
}
