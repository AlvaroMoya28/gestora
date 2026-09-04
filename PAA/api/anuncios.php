<?php
/**
 * Endpoint del módulo Anuncios: correo a los funcionarios del PAA.
 *
 *   GET  accion=destinatarios → la lista de funcionarios (mismo directorio de "Funcionarios PAA")
 *   POST accion=enviar        → manda el correo a todos menos los que se excluyan
 *   POST accion=enviarPrueba  → manda el borrador a los desarrolladores, para probar
 *   GET  accion=historial     → anuncios mandados antes, agrupados por referencia
 *
 * POR QUÉ EL UNIVERSO ES EL MISMO QUE "FUNCIONARIOS PAA"
 * No se inventa una lista aparte: es la misma consulta que arma ese
 * directorio (personas activas y no ocultas). Así, alguien que ya no
 * aparece ahí (de baja, o Personal Extraordinario / desarrollo, que se
 * ocultan a propósito — ver api/usuarios.php) tampoco recibe anuncios, sin
 * tener que mantener dos listas sincronizadas.
 *
 * Solo administración y desarrollo: mandar correo a todo el personal de
 * una vez tiene demasiado alcance como para dejarlo como excepción
 * individual (ver 'otorgable' en includes/modulos.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/correo.php';

$yo = exigir_modulo_api('anuncios');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// Peso máximo de los adjuntos, ya decodificados. Base64 le suma ~33% al
// viajar, así que 5 MB reales rondan los 6.7 MB de cuerpo de la petición —
// se queda por debajo del post_max_size típico (8 MB) de la mayoría de
// hostings sin tener que pedir que lo suban.
const MAX_ADJUNTOS_BYTES = 5 * 1024 * 1024;

$accion = param('accion', param('action', 'destinatarios'));

try {
    switch ($accion) {
        case 'destinatarios': destinatarios();      break;
        case 'enviar':        enviar_anuncio($yo);  break;
        case 'enviarPrueba':  enviar_prueba($yo);   break;
        case 'historial':     historial();          break;
        case 'publicarAviso': publicar_aviso($yo);  break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('anuncios', $e);
}

// ------------------------------------------------------------------

/** El mismo universo que ve el directorio de Funcionarios PAA. */
function destinatarios(): void
{
    $sql = "
        SELECT c.id, c.nombre, c.correo, a.nombre AS area, a.orden AS area_orden
        FROM personas c
        INNER JOIN areas a ON a.id = c.area_id
        WHERE c.activo = 1 AND c.oculto = 0 AND a.nombre <> 'IIP'
        ORDER BY a.orden, c.nombre
    ";

    $personas = array_map(static fn(array $f): array => [
        'id'     => (int) $f['id'],
        'nombre' => $f['nombre'],
        'correo' => $f['correo'] ?? '',
        'area'   => $f['area'],
    ], db()->query($sql)->fetchAll());

    responder_json(['success' => true, 'personas' => $personas, 'total' => count($personas)]);
}

/**
 * Manda el anuncio a todos los destinatarios, menos los excluidos.
 *
 * Se decodifican y se pesan los adjuntos ANTES de mandar nada: mejor
 * avisar de una vez que el envío número 40 de 80 rebote a mitad de lista
 * porque uno de los archivos pesaba de más.
 */
function enviar_anuncio(array $yo): void
{
    exigir(['asunto', 'cuerpo']);

    $asunto = trim(param('asunto'));
    $cuerpo = trim(param('cuerpo'));

    if ($asunto === '' || $cuerpo === '') {
        responder_json(['success' => false, 'error' => 'Falta el asunto o el cuerpo del correo'], 400);
    }

    $entrada   = entrada();
    $excluidos = array_map('intval', (array) ($entrada['excluidos'] ?? []));
    $adjuntos  = decodificar_adjuntos($entrada);
    $notificar = param('notificar') === '1';
    $nivel     = param('nivel', 'media');

    $sql = "
        SELECT p.id, p.nombre, p.correo
        FROM personas p
        INNER JOIN areas a ON a.id = p.area_id
        WHERE p.activo = 1 AND p.oculto = 0 AND a.nombre <> 'IIP'
          AND p.correo IS NOT NULL AND p.correo <> ''
    ";
    $personas = db()->query($sql)->fetchAll();

    $referencia = 'ANU-' . date('Ymd-His');
    $cuerpoHtml = plantilla_anuncio($asunto, $cuerpo, $yo['nombre']);

    $enviados = 0;
    $fallidos = [];

    foreach ($personas as $p) {
        if (in_array((int) $p['id'], $excluidos, true)) {
            continue;
        }

        $resultado = enviar_correo_html('anuncios', $p['correo'], $asunto, $cuerpoHtml, $referencia, $adjuntos);

        if ($resultado['success']) {
            $enviados++;
        } else {
            $fallidos[] = [
                'nombre' => $p['nombre'],
                'correo' => $p['correo'],
                'error'  => $resultado['error'] ?? 'error desconocido',
            ];
        }
    }

    bitacora_apuntar(
        'anuncios',
        'enviar',
        "referencia={$referencia} asunto=" . mb_substr($asunto, 0, 60) . " enviados={$enviados} fallidos=" . count($fallidos)
    );

    if ($notificar) {
        crear_anuncio_sistema($yo, $asunto, $cuerpo, $nivel);
    }

    responder_json([
        'success'    => true,
        'referencia' => $referencia,
        'enviados'   => $enviados,
        'fallidos'   => $fallidos,
    ]);
}

/**
 * Publica un aviso SOLO en la campanita de notificaciones (ver
 * includes/notificaciones.php), sin mandar ningún correo. Para lo que no
 * necesita llegar al buzón de cada quien — un mantenimiento del sistema, un
 * cambio de módulo — pero sí que se note al entrar.
 */
function publicar_aviso(array $yo): void
{
    exigir(['asunto', 'cuerpo']);

    $asunto = trim(param('asunto'));
    $cuerpo = trim(param('cuerpo'));
    $nivel  = param('nivel', 'media');

    if ($asunto === '' || $cuerpo === '') {
        responder_json(['success' => false, 'error' => 'Falta el título o el mensaje del aviso'], 400);
    }

    $id = crear_anuncio_sistema($yo, $asunto, $cuerpo, $nivel);

    responder_json(['success' => true, 'id' => $id]);
}

/** Inserta el aviso; lo reutilizan enviar_anuncio() y publicar_aviso(). */
function crear_anuncio_sistema(array $yo, string $titulo, string $mensaje, string $nivel): int
{
    if (!in_array($nivel, ['alta', 'media', 'baja'], true)) {
        $nivel = 'media';
    }

    db()->prepare('
        INSERT INTO anuncios_sistema (titulo, mensaje, nivel, creado_por)
        VALUES (?, ?, ?, ?)
    ')->execute([
        mb_substr($titulo, 0, 200),
        $mensaje,
        $nivel,
        $yo['nombre'],
    ]);

    $id = (int) db()->lastInsertId();

    bitacora_apuntar('anuncios', 'publicarAviso', "id={$id} nivel={$nivel} titulo=" . mb_substr($titulo, 0, 60));

    return $id;
}

/**
 * Decodifica y pesa los adjuntos que vienen en el cuerpo de la petición.
 * Se hace ANTES de mandar nada: mejor avisar de una vez que un envío
 * rebote a mitad de una lista larga porque un archivo pesaba de más.
 *
 * @return array{nombre: string, tipo: string, contenido: string}[]
 */
function decodificar_adjuntos(array $entrada): array
{
    $adjuntos  = [];
    $pesoTotal = 0;

    foreach ((array) ($entrada['adjuntos'] ?? []) as $a) {
        $nombre = mb_substr((string) ($a['nombre'] ?? 'adjunto'), 0, 150);
        $tipo   = (string) ($a['tipo'] ?? 'application/octet-stream');
        $base64 = (string) ($a['contenido'] ?? '');

        $binario = base64_decode($base64, true);
        if ($binario === false) {
            responder_json(['success' => false, 'error' => "El adjunto «{$nombre}» no se pudo leer"], 400);
        }

        $pesoTotal += strlen($binario);
        if ($pesoTotal > MAX_ADJUNTOS_BYTES) {
            responder_json([
                'success' => false,
                'error'   => 'Los adjuntos pesan demasiado entre todos (máximo 5 MB en total)',
            ], 400);
        }

        $adjuntos[] = ['nombre' => $nombre, 'tipo' => $tipo, 'contenido' => $binario];
    }

    return $adjuntos;
}

/**
 * Manda el borrador tal cual está a los desarrolladores, para probar cómo
 * se ve antes de mandarlo de verdad a todo el personal. No cuenta como
 * "el" envío: usa su propia referencia (PRUEBA-...) y el asunto queda
 * marcado, para que no se confunda con un anuncio real en el historial.
 *
 * Se busca por rol y no por el directorio: los desarrolladores están
 * ocultos ahí a propósito (ver api/usuarios.php), así que no aparecen entre
 * los destinatarios normales aunque se les mande esta prueba igual.
 */
function enviar_prueba(array $yo): void
{
    exigir(['asunto', 'cuerpo']);

    $asunto = trim(param('asunto'));
    $cuerpo = trim(param('cuerpo'));

    if ($asunto === '' || $cuerpo === '') {
        responder_json(['success' => false, 'error' => 'Falta el asunto o el cuerpo del correo'], 400);
    }

    $adjuntos = decodificar_adjuntos(entrada());

    $stmt = db()->query("
        SELECT DISTINCT p.correo
        FROM usuarios u
        INNER JOIN personas p ON p.id = u.persona_id
        WHERE u.rol = 'desarrollador' AND p.correo IS NOT NULL AND p.correo <> ''
    ");
    $correos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($correos === []) {
        responder_json(['success' => false, 'error' => 'No hay ningún desarrollador con correo registrado'], 404);
    }

    $asuntoPrueba = '[PRUEBA] ' . $asunto;
    $referencia   = 'PRUEBA-' . date('Ymd-His');
    $cuerpoHtml   = plantilla_anuncio($asuntoPrueba, $cuerpo, $yo['nombre']);

    $enviados = 0;
    $fallidos = [];

    foreach ($correos as $correo) {
        $resultado = enviar_correo_html('anuncios', $correo, $asuntoPrueba, $cuerpoHtml, $referencia, $adjuntos);

        if ($resultado['success']) {
            $enviados++;
        } else {
            $fallidos[] = ['correo' => $correo, 'error' => $resultado['error'] ?? 'error desconocido'];
        }
    }

    bitacora_apuntar('anuncios', 'enviarPrueba', "referencia={$referencia} enviados={$enviados}");

    responder_json(['success' => true, 'enviados' => $enviados, 'fallidos' => $fallidos]);
}

/**
 * El cuerpo lo escribe la persona en un cuadro de texto plano, no un editor
 * HTML: cada salto de línea que puso tiene que verse tal cual en el correo,
 * de ahí el nl2br. Se escapa antes por la misma razón que el resto del
 * sistema no confía en texto libre: que alguien escriba una etiqueta en el
 * cuerpo no tiene por qué convertirse en HTML de verdad.
 */
function plantilla_anuncio(string $asunto, string $cuerpoTexto, string $autor): string
{
    $cuerpoSeguro = nl2br(htmlspecialchars($cuerpoTexto, ENT_QUOTES, 'UTF-8'));

    return plantilla_correo($asunto, $cuerpoSeguro, 'Enviado desde el sistema PAA por ' . $autor);
}

/** Anuncios anteriores, agrupados por referencia (una fila por envío individual en correos_enviados). */
function historial(): void
{
    $stmt = db()->query("
        SELECT referencia, asunto,
               COUNT(*) AS total,
               SUM(exitoso = 1) AS exitosos,
               MIN(enviado_en) AS enviado_en
        FROM correos_enviados
        WHERE modulo = 'anuncios'
        GROUP BY referencia, asunto
        ORDER BY enviado_en DESC
        LIMIT 30
    ");

    $campanas = array_map(static fn(array $c): array => [
        'referencia' => $c['referencia'],
        'asunto'     => $c['asunto'],
        'total'      => (int) $c['total'],
        'exitosos'   => (int) $c['exitosos'],
        'enviadoEn'  => $c['enviado_en'],
    ], $stmt->fetchAll());

    responder_json(['success' => true, 'campanas' => $campanas]);
}
