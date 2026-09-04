<?php
/**
 * Chat de soporte entre cada funcionario (o Personal Extraordinario) y
 * administración.
 *
 * NO es un canal único: cada persona con cuenta propia tiene su propia
 * conversación con "administración" en conjunto — cualquier administrador o
 * desarrollador puede contestarle, y del otro lado se ve igual sin importar
 * cuál de ellos escribió. La conversación se identifica por el `usuario_id`
 * de quien NO administra (el funcionario o la cuenta de Personal
 * Extraordinario), así que esa persona nunca elige con quién habla: siempre
 * es la misma, la de "administración".
 *
 *   GET  accion=listar         → mensajes de una conversación
 *   GET  accion=conversaciones → (solo admin/dev) la lista de conversaciones, como una bandeja
 *   GET  accion=noLeidos       → cuántos mensajes nuevos tiene quien pregunta
 *   POST accion=enviar         → manda un mensaje
 *   POST accion=marcarVisto    → marca una conversación como leída hasta ahora
 *   POST accion=borrarConversacion → (solo admin/dev) borra toda la conversación
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/bitacora.php';

// El chat es para todo el que tiene sesión: no hace falta un módulo aparte
// en el catálogo, porque no es una pantalla que se "abra", es la manera de
// pedir ayuda desde cualquier pantalla.
$yo = exigir_rol_api([ROL_FUNCIONARIO, ROL_EXTRAORDINARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR]);
$esAdminYo = in_array($yo['rol'], ROLES_ADMIN, true);

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

const CHAT_LIMITE = 200;

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':         chat_listar($yo, $esAdminYo);       break;
        case 'conversaciones': chat_conversaciones($yo, $esAdminYo); break;
        case 'noLeidos':       chat_no_leidos($yo, $esAdminYo);    break;
        case 'enviar':         chat_enviar($yo, $esAdminYo);       break;
        case 'marcarVisto':    chat_marcar_visto($yo, $esAdminYo); break;
        case 'borrarConversacion': chat_borrar_conversacion($yo, $esAdminYo); break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('chat', $e);
}

// ------------------------------------------------------------------

/**
 * A qué conversación se refiere la petición.
 *
 * Un funcionario o Personal Extraordinario solo tiene UNA: la suya, y no
 * puede pedir otra por más que la mande en el parámetro — por eso se ignora
 * lo que venga y se fuerza su propio id. Un administrador sí tiene que decir
 * con cuál de todas está hablando.
 */
function chat_conversacion_pedida(array $yo, bool $esAdminYo): int
{
    if (!$esAdminYo) {
        return (int) $yo['id'];
    }

    $id = param_int('conversacion_id', 0);
    if ($id <= 0) {
        responder_json(['success' => false, 'error' => 'Falta indicar con quién es la conversación'], 400);
    }

    // No tiene sentido "conversar" con otro administrador: esto es soporte a
    // funcionarios y personal extraordinario, no un chat interno.
    //
    // Desarrollador es la excepción: cuando un dev prueba el chat simulando
    // otro rol (ver simular_rol()), el mensaje queda ligado a su cuenta REAL
    // (rol='desarrollador' en la base, la simulación no cambia eso) — sin
    // esta excepción esa conversación de prueba quedaba huérfana, "no existe"
    // para quien administra, ni siquiera para el propio dev que la escribió.
    $stmt = db()->prepare('SELECT rol FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $rol = $stmt->fetchColumn();

    if (!in_array($rol, [ROL_FUNCIONARIO, ROL_EXTRAORDINARIO, ROL_DESARROLLADOR], true)) {
        responder_json(['success' => false, 'error' => 'Esa conversación no existe'], 404);
    }

    return $id;
}

function chat_listar(array $yo, bool $esAdminYo): void
{
    $conversacionId = chat_conversacion_pedida($yo, $esAdminYo);
    $despuesDe = param_int('despues_de', 0);

    if ($despuesDe > 0) {
        $stmt = db()->prepare('
            SELECT id, es_admin, autor, mensaje, creado_en
            FROM chat_mensajes
            WHERE conversacion_id = ? AND id > ?
            ORDER BY id ASC
            LIMIT ' . CHAT_LIMITE . '
        ');
        $stmt->execute([$conversacionId, $despuesDe]);
    } else {
        // Sin punto de partida, se trae la cola: lo último es lo que importa
        // al abrir el panel, no el principio de la historia.
        $stmt = db()->prepare('
            SELECT id, es_admin, autor, mensaje, creado_en
            FROM (
                SELECT id, es_admin, autor, mensaje, creado_en
                FROM chat_mensajes
                WHERE conversacion_id = ?
                ORDER BY id DESC
                LIMIT ' . CHAT_LIMITE . '
            ) recientes
            ORDER BY id ASC
        ');
        $stmt->execute([$conversacionId]);
    }

    // "Mío" no es "yo, esta persona": del lado de administración es
    // "cualquier administrador", porque de ese lado se contesta como equipo.
    $mensajes = array_map(static fn(array $m): array => [
        'id'      => (int) $m['id'],
        'mio'     => ((bool) $m['es_admin']) === $esAdminYo,
        'autor'   => $m['autor'],
        'mensaje' => $m['mensaje'],
        'fecha'   => $m['creado_en'],
    ], $stmt->fetchAll());

    responder_json(['success' => true, 'conversacionId' => $conversacionId, 'mensajes' => $mensajes]);
}

/** Solo para administración: la bandeja con una fila por conversación. */
function chat_conversaciones(array $yo, bool $esAdminYo): void
{
    if (!$esAdminYo) {
        responder_json(['success' => false, 'error' => 'No tenés permiso para esto'], 403);
    }

    $stmt = db()->prepare('
        SELECT
            m.conversacion_id,
            COALESCE(p.nombre, u.usuario) AS nombre,
            u.rol,
            MAX(m.creado_en) AS ultimo_en,
            (SELECT mensaje FROM chat_mensajes m2
              WHERE m2.conversacion_id = m.conversacion_id
              ORDER BY m2.id DESC LIMIT 1) AS ultimo_mensaje,
            SUM(m.es_admin = 0 AND m.creado_en > COALESCE(v.visto_en, "1970-01-01")) AS no_leidos
        FROM chat_mensajes m
        INNER JOIN usuarios u  ON u.id = m.conversacion_id
        LEFT  JOIN personas p  ON p.id = u.persona_id
        LEFT  JOIN chat_visto v ON v.usuario_id = ? AND v.conversacion_id = m.conversacion_id
        GROUP BY m.conversacion_id, p.nombre, u.usuario, u.rol
        ORDER BY ultimo_en DESC
    ');
    $stmt->execute([$yo['id']]);

    $conversaciones = array_map(static fn(array $f): array => [
        'conversacionId' => (int) $f['conversacion_id'],
        'nombre'         => $f['nombre'],
        'rol'            => $f['rol'],
        'ultimoMensaje'  => $f['ultimo_mensaje'] ?? '',
        'ultimoEn'       => $f['ultimo_en'],
        'noLeidos'       => (int) $f['no_leidos'],
    ], $stmt->fetchAll());

    responder_json(['success' => true, 'conversaciones' => $conversaciones]);
}

/**
 * Cuántos mensajes nuevos tiene quien pregunta.
 *
 * Para un funcionario o Personal Extraordinario es de SU conversación. Para
 * administración es la suma de todas las conversaciones — es lo que infla la
 * insignia roja del botón mientras el panel está cerrado.
 */
function chat_no_leidos(array $yo, bool $esAdminYo): void
{
    if ($esAdminYo) {
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM chat_mensajes m
            LEFT JOIN chat_visto v ON v.usuario_id = ? AND v.conversacion_id = m.conversacion_id
            WHERE m.es_admin = 0 AND m.creado_en > COALESCE(v.visto_en, "1970-01-01")
        ');
        $stmt->execute([$yo['id']]);
    } else {
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM chat_mensajes m
            LEFT JOIN chat_visto v ON v.usuario_id = ? AND v.conversacion_id = ?
            WHERE m.conversacion_id = ? AND m.es_admin = 1 AND m.creado_en > COALESCE(v.visto_en, "1970-01-01")
        ');
        $stmt->execute([$yo['id'], $yo['id'], $yo['id']]);
    }

    responder_json(['success' => true, 'noLeidos' => (int) $stmt->fetchColumn()]);
}

function chat_enviar(array $yo, bool $esAdminYo): void
{
    exigir(['mensaje']);

    $mensaje = trim(param('mensaje'));
    if ($mensaje === '') {
        responder_json(['success' => false, 'error' => 'El mensaje está vacío'], 400);
    }

    $conversacionId = chat_conversacion_pedida($yo, $esAdminYo);

    // Mismo mecanismo que la bitácora: si viene `quien` (cuentas compartidas
    // como Personal Extraordinario), el autor queda como "Personal
    // Extraordinario (Floribeth)" en vez de la cuenta sola.
    $autor = usuario_texto_con_quien($yo['usuario'] ?? null, $yo['nombre'] ?? null) ?? $yo['nombre'];

    // Un desarrollador probando el sistema como otro rol (ver simular_rol())
    // manda mensajes de prueba, no reportes reales: se anota entre paréntesis
    // para que quien administra sepa que no es un funcionario de verdad.
    if (!empty($yo['simulando'])) {
        $autor .= ' (' . nombre_del_rol($yo['rol']) . ')';
    }

    $stmt = db()->prepare('
        INSERT INTO chat_mensajes (conversacion_id, usuario_id, es_admin, autor, mensaje)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $conversacionId,
        $yo['id'],
        $esAdminYo ? 1 : 0,
        mb_substr($autor, 0, 100),
        mb_substr($mensaje, 0, 2000),
    ]);

    // Mandar un mensaje ya cuenta como haber visto la conversación hasta
    // ahora: no tendría sentido que a quien lo escribió le apareciera como
    // no leído lo que acaba de escribir.
    chat_guardar_visto((int) $yo['id'], $conversacionId);

    bitacora_apuntar('chat', 'enviar', mb_substr($mensaje, 0, 60));

    responder_json([
        'success'        => true,
        'id'             => (int) db()->lastInsertId(),
        'conversacionId' => $conversacionId,
    ]);
}

function chat_marcar_visto(array $yo, bool $esAdminYo): void
{
    $conversacionId = chat_conversacion_pedida($yo, $esAdminYo);
    chat_guardar_visto((int) $yo['id'], $conversacionId);
    responder_json(['success' => true]);
}

/**
 * Borra una conversación completa: todos sus mensajes y el registro de
 * "visto". Solo administración, y solo tiene sentido desde la bandeja —
 * un funcionario o Personal Extraordinario no puede borrarse su propia
 * conversación con soporte.
 */
function chat_borrar_conversacion(array $yo, bool $esAdminYo): void
{
    if (!$esAdminYo) {
        responder_json(['success' => false, 'error' => 'No tenés permiso para esto'], 403);
    }

    $conversacionId = chat_conversacion_pedida($yo, $esAdminYo);

    db()->prepare('DELETE FROM chat_mensajes WHERE conversacion_id = ?')->execute([$conversacionId]);
    db()->prepare('DELETE FROM chat_visto WHERE conversacion_id = ?')->execute([$conversacionId]);

    bitacora_apuntar('chat', 'borrarConversacion', "conversacion={$conversacionId}");

    responder_json(['success' => true]);
}

function chat_guardar_visto(int $usuarioId, int $conversacionId): void
{
    db()->prepare('
        INSERT INTO chat_visto (usuario_id, conversacion_id, visto_en) VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE visto_en = NOW()
    ')->execute([$usuarioId, $conversacionId]);
}
