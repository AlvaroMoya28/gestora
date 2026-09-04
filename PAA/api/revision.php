<?php
/**
 * Endpoint del módulo Revisión.
 *
 * Revisión NO captura nada: lee lo que el personal de bodega ya guardó desde
 * Ingreso de Datos y con eso levanta las actas.
 *
 * La separación es deliberada. Quien embala mete los datos y no emite
 * documentos; el acta se firma sobre lo que quedó registrado, no sobre lo que
 * hay en una pantalla a medio llenar. Además evita que un comprobante salga de
 * una sede que todavía está en proceso sin que nadie se dé cuenta.
 *
 *   accion=sedes         → lista con el avance y los tres totales
 *   accion=sede          → todo lo capturado de una sede
 *   accion=varias        → lo mismo para un grupo, en una sola consulta
 *   accion=materiales    → catálogo de la lista de chequeo
 *   accion=buscarFolleto → en qué sede/aula quedó un folleto (ver includes/sedes_datos.php)
 *   accion=enviarActa    → guarda el acta y la manda al coordinador
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/sedes_datos.php';
require_once __DIR__ . '/../includes/correo.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("revision");

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

$accion = param('accion', param('action', 'sedes'));

try {
    switch ($accion) {
        case 'sedes':      listar_sedes();      break;
        case 'sede':       detalle_sede();      break;
        case 'varias':     detalle_varias();    break;
        case 'materiales': listar_materiales(); break;
        case 'buscarFolleto': buscar_folleto_accion(); break;
        case 'enviarActa': enviar_acta($yo);    break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('revision', $e);
}

// ------------------------------------------------------------------
// Consulta
// ------------------------------------------------------------------

/**
 * Sedes de la convocatoria con su avance, y los totales de cada estado.
 *
 * Los totales se cuentan en el servidor y no en el navegador aunque este
 * reciba la lista completa: así siguen siendo correctos cuando alguien tiene
 * un filtro puesto, que es cuando más se miran.
 */
function listar_sedes(): void
{
    $conv  = convocatoria_exigida();
    $sedes = sedes_con_estado($conv);

    $totales = ['completa' => 0, 'proceso' => 0, 'sin_empezar' => 0];

    foreach ($sedes as $s) {
        $totales[$s['estado']]++;
    }

    responder_json([
        'success'        => true,
        'sedes'          => $sedes,
        'totales'        => $totales,
        'total'          => count($sedes),
        'convocatoriaId' => $conv,
    ]);
}

function detalle_sede(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);

    responder_json(['success' => true] + datos_de_sede($sedeId, $conv));
}

/**
 * Los datos completos de varias sedes, para la impresión en lote.
 *
 * Se resuelve en una sola petición y no en una por sede: con trescientas
 * sedes, trescientos viajes al servidor tardan lo suficiente como para que el
 * navegador bloquee la ventana emergente por inactividad.
 *
 * Puede pedirse la convocatoria entera. El armado va en cuatro consultas
 * fijas (ver datos_de_varias_sedes), así que el tiempo no crece con la
 * cantidad de sedes como lo haría un bucle.
 */
function detalle_varias(): void
{
    $conv = convocatoria_exigida();

    $codigos = entrada()['sedes'] ?? [];

    if (!is_array($codigos) || $codigos === []) {
        responder_json(['success' => false, 'error' => 'No se indicó ninguna sede'], 400);
    }

    // Se sube el tiempo disponible: aunque el armado ahora es rápido, el
    // volumen de una convocatoria completa no tiene por qué caber en los 30
    // segundos por defecto, y quedarse a mitad no deja ni un acta.
    if (function_exists('set_time_limit')) {
        @set_time_limit(180);
    }

    $actas = datos_de_varias_sedes(array_map('strval', $codigos), $conv);

    responder_json([
        'success' => true,
        'actas'   => $actas,
        'total'   => count($actas),
    ]);
}

function listar_materiales(): void
{
    responder_json(['success' => true, 'materiales' => materiales_del_acta()]);
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
// Acta
// ------------------------------------------------------------------

/** Guarda el acta y la manda al coordinador de la sede. */
function enviar_acta(array $yo): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $codigo = param('sede');
    $sedeId = sede_exigida($codigo, $conv);
    $html   = (string) (entrada()['actaHTML'] ?? entrada()['html'] ?? '');

    if (trim($html) === '') {
        responder_json(['success' => false, 'error' => 'Falta el contenido del acta'], 400);
    }

    // El destinatario NO viene del cliente: se resuelve contra la base. Ver el
    // encabezado de includes/correo.php.
    $destinatario = correo_coordinador_de_sede($codigo, $conv);

    if ($destinatario === null) {
        responder_json([
            'success' => false,
            'error'   => "La sede {$codigo} no tiene correo de coordinador registrado",
        ], 422);
    }

    db()->beginTransaction();

    $folio = param('folio');
    if ($folio !== '') {
        $stmt = db()->prepare('SELECT 1 FROM actas_revision WHERE folio = ?');
        $stmt->execute([$folio]);
        if ($stmt->fetchColumn() !== false) {
            $folio = '';
        }
    }

    if ($folio === '') {
        $hoy = date('Ymd');
        $stmt = db()->prepare('SELECT COUNT(*) FROM actas_revision WHERE folio LIKE ?');
        $stmt->execute(["RA-{$hoy}-%"]);
        $folio = sprintf('RA-%s-%03d', $hoy, ((int) $stmt->fetchColumn()) + 1);
    }

    db()->prepare("
        INSERT INTO actas_revision
            (folio, convocatoria_id, sede_id, aulas_revisadas, revisado_por,
             observaciones, acta_html, enviado_a)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $folio, $conv, $sedeId,
        param_int('aulasRevisadas'),
        param('revisadoPor') ?: $yo['nombre'],
        param('observaciones') ?: null,
        $html,
        $destinatario,
    ]);

    $actaId = (int) db()->lastInsertId();
    db()->commit();

    // El acta queda guardada aunque el correo falle: se levantó y se firmó
    // igual. El envío se marca aparte, y así se puede reintentar sin generar
    // un folio nuevo.
    $resultado = enviar_correo_html(
        'revision',
        $destinatario,
        param('asunto', "Acta de revisión · Sede {$codigo}"),
        $html,
        $folio
    );

    if ($resultado['success']) {
        db()->prepare('UPDATE actas_revision SET enviado_en = NOW() WHERE id = ?')->execute([$actaId]);
    }

    responder_json([
        'success'      => $resultado['success'],
        'folio'        => $folio,
        'destinatario' => $destinatario,
        'guardada'     => true,
        'error'        => $resultado['error'] ?? null,
    ], $resultado['success'] ? 200 : 502);
}
