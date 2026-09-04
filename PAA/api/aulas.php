<?php
/**
 * Endpoint de aulas y tulas.
 *
 * Reemplaza a la vez:
 *   · la lectura del CSV público del Sheet 1KEcA3… que hacían Tulas.html,
 *     AsignacionMarchamos.html, RevisionAulas.html y Administracion.html
 *   · el Apps Script Tulas_AppScript.gs (actualizarEstados y enviarComprobante)
 *
 * ACCIONES
 *   GET  (sin acción)         → listado de aulas de la convocatoria
 *   POST accion=actualizarEstados → marca tulas como prestadas o devueltas
 *   POST accion=enviarComprobante → manda el comprobante al coordinador de la sede
 *
 * LO QUE SE PRESERVA DEL APPS SCRIPT, Y POR QUÉ
 *   1. Lista blanca de estados: ahora la impone el ENUM de la tabla.
 *   2. La fila tiene que existir: se busca por código de barras o por
 *      sede + aula, y si no aparece se rechaza en vez de crear nada.
 *   3. El destinatario del correo NUNCA viene del cliente: se recibe el número
 *      de sede y el correo se resuelve contra la base (includes/correo.php).
 *      Si se aceptara la dirección, esto sería un relay de correo abierto a
 *      nombre de la UCR.
 *   4. Tope diario de envíos, por si alguien intenta abusar del endpoint.
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("tulas");

require_once __DIR__ . '/../includes/correo.php';

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

$accion = param('accion', param('action'));

try {
    switch ($accion) {
        case '':
        case 'listar':
            listar_aulas();
            break;

        case 'actualizarEstados':
            actualizar_estados();
            break;

        case 'enviarComprobante':
            enviar_comprobante();
            break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('aulas', $e);
}

// ------------------------------------------------------------------

/**
 * Listado de aulas de una convocatoria.
 *
 * Devuelve los mismos campos que el JS de Tulas.html sacaba del CSV, con los
 * mismos nombres, para que el render de la página no cambie. La diferencia es
 * que los marchamos ya vienen resueltos y el estado es un valor controlado.
 */
function listar_aulas(): void
{
    $convocatoriaId = convocatoria_actual();

    if ($convocatoriaId === null) {
        responder_json([
            'success' => false,
            'error'   => 'No hay ninguna convocatoria activa. Marcá una en la tabla convocatorias.',
        ], 409);
    }

    $sedeCodigo = param('sede');

    // Cada fila es un AULA, pero los datos del bulto (número de tula,
    // marchamos y estado) salen de : desde la migración 014 la tula es
    // una entidad propia que agrupa aulas, y es a ella a la que se le ponen
    // los marchamos y la que sale y entra de la bodega.
    $sql = "
        SELECT
            a.id,
            s.codigo               AS id_sede,
            s.nombre               AS nombre_sede,
            c.nombre               AS convocatoria,
            c.fecha_aplicacion     AS fecha,
            a.numero_aula, a.formula, a.asignados,
            a.folleto_desde, a.folleto_hasta, a.folletos_sueltos, a.grupo_embalaje,
            s.coord_folleto_desde AS coord_desde, s.coord_folleto_hasta AS coord_hasta,
            a.personas_por_aula, a.total_folletos, a.total_folletos_coord,
            a.total_cajas, a.total_aulas, a.adecuaciones, a.es_adecuacion, a.turno,
            COALESCE(p.nombre, a.coordinador_nombre) AS coordinador,
            COALESCE(p.correo, a.coordinador_correo) AS correo_coordinador,
            t.id       AS tula_id,
            t.numero   AS tula_numero,
            t.estado   AS estado,
            t.codigo_barras,
            t.lleva_folletos_coord,
            MAX(CASE WHEN m.posicion = 1 THEN m.codigo END) AS marchamo1,
            MAX(CASE WHEN m.posicion = 2 THEN m.codigo END) AS marchamo2
        FROM aulas a
        INNER JOIN sedes s         ON s.id = a.sede_id
        INNER JOIN convocatorias c ON c.id = a.convocatoria_id
        LEFT  JOIN personas p      ON p.id = a.persona_id
        LEFT  JOIN tula_aulas ta   ON ta.aula_id = a.id
        LEFT  JOIN tulas t         ON t.id = ta.tula_id
        LEFT  JOIN marchamos m     ON m.tula_id = t.id
        WHERE a.convocatoria_id = :conv
    ";
    $parametros = ['conv' => $convocatoriaId];

    if ($sedeCodigo !== '') {
        $sql .= " AND s.codigo = :sede";
        $parametros['sede'] = $sedeCodigo;
    }

    $sql .= "
        GROUP BY a.id, s.codigo, s.nombre, c.nombre, c.fecha_aplicacion,
                 a.numero_aula, a.formula, a.asignados, a.folleto_desde, a.folleto_hasta,
                 a.folletos_sueltos, a.grupo_embalaje, s.coord_folleto_desde, s.coord_folleto_hasta,
                 a.personas_por_aula, a.total_folletos, a.total_folletos_coord,
                 a.total_cajas, a.total_aulas, a.adecuaciones, a.es_adecuacion, a.turno,
                 p.nombre, a.coordinador_nombre, p.correo, a.coordinador_correo,
                 t.id, t.numero, t.estado, t.codigo_barras, t.lleva_folletos_coord
        ORDER BY CAST(s.codigo AS UNSIGNED), t.numero,
                 CAST(a.numero_aula AS UNSIGNED), a.numero_aula
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    $tulas = array_map(static function (array $f): array {
        return [
            'id'                    => (int) $f['id'],
            'idSede'                => $f['id_sede'],
            'NombreSede'            => $f['nombre_sede'],
            'convocatoria'          => $f['convocatoria'],
            'fechaAplicacion'       => $f['fecha'] ?? '',
            'aula'                  => $f['numero_aula'],
            'formula'               => $f['formula'] ?? '',
            'asignados'             => (int) $f['asignados'],
            'desde'                 => $f['folleto_desde'] ?? '',
            'hasta'                 => $f['folleto_hasta'] ?? '',
            'folletosSueltos'       => $f['folletos_sueltos'] ?? '',
            'grupoEmbalaje'         => $f['grupo_embalaje'] ?? '',
            'desdeCoord'            => $f['coord_desde'] ?? '',
            'hastaCoord'            => $f['coord_hasta'] ?? '',
            'personasPorAula'       => $f['personas_por_aula'] ?? '',
            'cantidadFolletosTotal' => (int) $f['total_folletos'],
            'totalFolletosCoord'    => $f['total_folletos_coord'] ?? '',
            'totalCajas'            => $f['total_cajas'] ?? '0',
            'totalAulas'            => $f['total_aulas'] ?? '',
            'adecuaciones'          => $f['adecuaciones'] ?? '',
            'esAdecuacion'          => (bool) ($f['es_adecuacion'] ?? 0),
            'turno'                 => $f['turno'] ?? '',
            // Un aula sin tula todavía no fue embalada: se muestra como
            // disponible en vez de dejar el campo vacío.
            'estado'                => $f['estado'] ?? 'disponible',
            'codigoBarras'          => $f['codigo_barras'] ?? '',
            'coordinador'           => $f['coordinador'] ?? '',
            'correoCoordinador'     => $f['correo_coordinador'] ?? '',
            'marchamo1'             => $f['marchamo1'] ?? '',
            'marchamo2'             => $f['marchamo2'] ?? '',

            // Datos del bulto al que pertenece el aula.
            'tulaId'                => $f['tula_id'] !== null ? (int) $f['tula_id'] : null,
            'tulaNumero'            => $f['tula_numero'] !== null ? (int) $f['tula_numero'] : null,
            'llevaCoord'            => (bool) ($f['lleva_folletos_coord'] ?? 0),

            'idUnico'               => $f['id_sede'] . '-' . $f['numero_aula'],
        ];
    }, $stmt->fetchAll());

    responder_json([
        'success'        => true,
        'tulas'          => $tulas,
        'total'          => count($tulas),
        'convocatoriaId' => $convocatoriaId,
        'actualizado'    => date('c'),
    ]);
}

/**
 * Marca tulas como prestadas o devueltas.
 *
 * Acepta un arreglo `actualizaciones`, igual que actualizarEstados_ del Apps
 * Script: cada elemento identifica el aula por `codigoBarras`, o por `sede` +
 * `aula`, y trae el `estado` nuevo.
 *
 * Cada cambio deja además una fila en movimientos_tula. El Sheet solo guardaba
 * el estado actual, así que al devolver una tula se perdía el registro de
 * cuándo había salido.
 */
function actualizar_estados(): void
{
    $convocatoriaId = convocatoria_actual();
    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay convocatoria activa'], 409);
    }

    $datos = entrada();
    $actualizaciones = $datos['actualizaciones'] ?? null;

    // También se admite una sola actualización suelta, que es como la manda
    // la página al escanear un código.
    if (!is_array($actualizaciones)) {
        $actualizaciones = [[
            'codigoBarras' => param('codigoBarras'),
            'sede'         => param('sede'),
            'aula'         => param('aula'),
            'estado'       => param('estado'),
            'responsable'  => param('responsable'),
        ]];
    }

    if ($actualizaciones === []) {
        responder_json(['success' => false, 'error' => 'No se recibió ninguna actualización'], 400);
    }

    $ESTADOS = ['disponible', 'prestado', 'devuelto'];

    // Lo que sale y entra de la bodega es la TULA, no el aula: por eso el
    // estado vive en `tulas` desde la migración 014. Se puede identificar por
    // el código de barras del bulto o por sede + aula, en cuyo caso se busca
    // la tula que contiene esa aula.
    $buscarPorCodigo = db()->prepare("
        SELECT t.id, t.estado FROM tulas t
        WHERE t.convocatoria_id = ? AND t.codigo_barras = ?
    ");
    $buscarPorSedeAula = db()->prepare("
        SELECT t.id, t.estado
        FROM tulas t
        INNER JOIN tula_aulas ta ON ta.tula_id = t.id
        INNER JOIN aulas a       ON a.id = ta.aula_id
        INNER JOIN sedes s       ON s.id = t.sede_id
        WHERE t.convocatoria_id = ? AND s.codigo = ? AND a.numero_aula = ?
        LIMIT 1
    ");
    // También se acepta el marchamo: en la práctica es lo que está a mano
    // pegado al bulto cuando llega de vuelta.
    $buscarPorMarchamo = db()->prepare("
        SELECT t.id, t.estado
        FROM marchamos m INNER JOIN tulas t ON t.id = m.tula_id
        WHERE m.convocatoria_id = ? AND m.codigo = ?
        LIMIT 1
    ");
    $actualizar = db()->prepare('UPDATE tulas SET estado = ? WHERE id = ?');
    $registrar  = db()->prepare("
        INSERT INTO movimientos_tula (tula_id, tipo, estado_resultante, responsable, observacion)
        VALUES (?, ?, ?, ?, ?)
    ");

    $aplicadas = [];
    $rechazadas = [];

    db()->beginTransaction();

    foreach ($actualizaciones as $upd) {
        $codigoBarras = trim((string) ($upd['codigoBarras'] ?? $upd['codigo_barras'] ?? ''));
        $sede         = trim((string) ($upd['sede'] ?? $upd['idSede'] ?? ''));
        $aula         = trim((string) ($upd['aula'] ?? $upd['numero_aula'] ?? ''));
        $estado       = strtolower(trim((string) ($upd['estado'] ?? '')));
        $responsable  = trim((string) ($upd['responsable'] ?? ''));

        // Estado fuera de la lista blanca: se rechaza sin tocar nada.
        if (!in_array($estado, $ESTADOS, true)) {
            $rechazadas[] = ['identificador' => $codigoBarras ?: "{$sede}-{$aula}",
                             'motivo' => "Estado inválido: {$estado}"];
            continue;
        }

        if ($codigoBarras !== '') {
            $buscarPorCodigo->execute([$convocatoriaId, $codigoBarras]);
            $fila = $buscarPorCodigo->fetch();

            // Si no era el código del bulto, se prueba como marchamo: es lo
            // que se escanea cuando la tula ya está sellada.
            if ($fila === false) {
                $buscarPorMarchamo->execute([$convocatoriaId, $codigoBarras]);
                $fila = $buscarPorMarchamo->fetch();
            }
        } elseif ($sede !== '' && $aula !== '') {
            $buscarPorSedeAula->execute([$convocatoriaId, $sede, $aula]);
            $fila = $buscarPorSedeAula->fetch();
        } else {
            $rechazadas[] = ['identificador' => '(sin datos)',
                             'motivo' => 'Falta el código de barras o la sede y el aula'];
            continue;
        }

        // La fila tiene que existir: no se crea nada desde acá.
        if ($fila === false) {
            $rechazadas[] = ['identificador' => $codigoBarras ?: "{$sede}-{$aula}",
                             'motivo' => 'No existe esa tula en la convocatoria'];
            continue;
        }

        $actualizar->execute([$estado, $fila['id']]);

        $registrar->execute([
            $fila['id'],
            $estado === 'prestado' ? 'salida' : 'entrada',
            $estado,
            $responsable !== '' ? $responsable : null,
            null,
        ]);

        $aplicadas[] = [
            'id'            => (int) $fila['id'],
            'identificador' => $codigoBarras ?: "{$sede}-{$aula}",
            'estadoPrevio'  => $fila['estado'],
            'estado'        => $estado,
        ];
    }

    db()->commit();

    responder_json([
        'success'    => true,
        'aplicadas'  => $aplicadas,
        'rechazadas' => $rechazadas,
        'total'      => count($aplicadas),
    ]);
}

/**
 * Envía el comprobante de la sede a su coordinador.
 *
 * El cliente manda el NÚMERO DE SEDE y el HTML del comprobante; nunca la
 * dirección de correo. Esa es la regla que hacía segura la versión de Apps
 * Script y que se mantiene igual acá.
 */
function enviar_comprobante(): void
{
    exigir(['sede']);

    $sede = param('sede');
    // Tulas/index.php manda el HTML como `actaHTML`; se aceptan los otros dos
    // nombres también porque ya eran parte del contrato del endpoint.
    $html = (string) (entrada()['actaHTML'] ?? entrada()['comprobanteHTML'] ?? entrada()['html'] ?? '');

    if (trim($html) === '') {
        responder_json(['success' => false, 'error' => 'Falta el contenido del comprobante'], 400);
    }

    $convocatoriaId = convocatoria_actual();
    $destinatario = correo_coordinador_de_sede($sede, $convocatoriaId);

    if ($destinatario === null) {
        responder_json([
            'success' => false,
            'error'   => "La sede {$sede} no tiene correo de coordinador registrado",
        ], 422);
    }

    $folio = param('folio');
    $asunto = param('asunto', 'Comprobante de tulas · Sede ' . $sede);

    // Mientras el módulo no tenga cuenta institucional (ver el porqué en
    // includes/correo.php:196), "Enviar comprobante" no puede mandar nada
    // todavía. En vez de que el botón falle entero —y con eso deje también
    // sin marcar las tulas como entregadas, que es lo que hace
    // actualizarEstadosEnGoogleSheets() después de un envío exitoso—, se
    // avisa que no hubo correo pero se deja seguir como si fuera "solo
    // actualizar estado". Apenas llegue la cuenta, esto vuelve a mandar de
    // verdad sin tocar nada más.
    if (cuenta_de_correo('tulas') === null) {
        responder_json([
            'success'       => true,
            'folio'         => $folio,
            'correoEnviado' => false,
            'aviso'         => 'El comprobante no se envió por correo: todavía no hay cuenta '
                              . 'institucional configurada para Tulas. Los estados se actualizan igual.',
            'destinatario'  => $destinatario,
            'destinatarios' => [$destinatario],
        ]);
    }

    $resultado = enviar_correo_html('tulas', $destinatario, $asunto, $html, $folio !== '' ? $folio : $sede);

    if (!$resultado['success']) {
        responder_json($resultado, 502);
    }

    responder_json([
        'success'       => true,
        'folio'         => $folio,
        'correoEnviado' => true,
        // Se devuelve para que la página confirme a quién se envió. Es
        // información que quien está en el mostrador ya tiene delante.
        // `destinatarios` (arreglo) es lo que lee Tulas/index.php para contar
        // a cuántos se envió; `destinatario` se deja igual por compatibilidad.
        'destinatario'  => $destinatario,
        'destinatarios' => [$destinatario],
    ]);
}
