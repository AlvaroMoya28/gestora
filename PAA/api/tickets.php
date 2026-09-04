<?php
/**
 * Endpoint del módulo Creación de Tickets (reporte de averías).
 *
 *   GET  accion=listar          → tickets, con filtros opcionales
 *   GET  accion=catalogos       → categorías para el selector del formulario
 *   GET  accion=ver             → un ticket con su historial y los datos de contacto
 *   POST accion=crear           → registra un ticket y devuelve su folio
 *   POST accion=comentar        → agrega un comentario, opcionalmente con cambio de estado
 *   POST accion=cambiarEstado   → cambia el estado del ticket
 *   POST accion=asignarPrioridad → fija la prioridad (solo administración)
 *   POST accion=limpiarCerrados → borra todos los tickets cerrados (solo administración)
 *
 * Este archivo y `CreacionTickets/index.php` son el módulo completo. Quien lo
 * esté desarrollando puede trabajar solo en estos dos archivos y en su
 * migración `schema/010_tickets.sql` (más los ajustes en archivos nuevos
 * numerados, como `021_tickets_prioridad_opcional.sql`): lo demás del sistema
 * no hace falta tocarlo, y de hecho no debería (ver MANUAL_ASISTENTE.md).
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/correo.php';

// Cualquier persona con cuenta puede reportar y consultar averías; qué ve y
// qué puede cambiar depende de su rol (ver vista_activa()).
$yo = exigir_modulo_api("tickets");


exigir_metodo(['GET', 'POST']);

// Ficha CSRF obligatoria en todo POST del módulo — ver includes/peticion.php.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// Valores admitidos. Se declaran acá para poder validar antes de tocar la base
// y devolver un mensaje claro, en vez de dejar que falle el ENUM.
const ESTADOS_TICKET     = ['abierto', 'en_proceso', 'resuelto', 'cerrado'];
const PRIORIDADES_TICKET = ['baja', 'media', 'alta', 'critica'];
const POR_PAGINA_TICKETS = 20;

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':          listar();           break;
        case 'catalogos':       catalogos();        break;
        case 'ver':              ver();              break;
        case 'crear':            crear();            break;
        case 'comentar':         comentar();         break;
        case 'cambiarEstado':    cambiar_estado();   break;
        case 'asignarPrioridad': asignar_prioridad(); break;
        case 'limpiarCerrados':  limpiar_cerrados();  break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('tickets', $e);
}

// ------------------------------------------------------------------

/**
 * Qué pantalla está usando quien pide esto: 'admin' o 'funcionario'.
 *
 * Un funcionario siempre ve la suya y un administrador siempre ve la de
 * administración; no hay forma de elegir mandando otro parámetro. La única
 * excepción es el rol `desarrollador`, que necesita poder probar las dos
 * pantallas: para ese rol (y solo para ese rol) el parámetro `vista` decide.
 */
function vista_activa(array $yo): string
{
    $esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);

    if ($yo['rol'] === ROL_DESARROLLADOR) {
        $vista = param('vista');
        if ($vista === 'funcionario' || $vista === 'admin') {
            return $vista;
        }
    }

    return $esAdmin ? 'admin' : 'funcionario';
}

/**
 * Listado de tickets.
 *
 * Filtros opcionales: estado, prioridad, categoria_id, q (texto),
 * orden (recientes|antiguos).
 *
 * En vista funcionario, se fuerza a los tickets propios sin importar qué
 * filtros mande el cliente: un funcionario no debe poder ver averías ajenas
 * pidiendo otro `q` o `estado` a mano.
 */
function listar(): void
{
    $yo = usuario_actual();

    $soloPropios = vista_activa($yo) === 'funcionario';

    $sql = "
        SELECT t.id, t.folio, t.titulo, t.descripcion, t.prioridad, t.estado,
               t.reportado_por, t.creado_en, t.actualizado_en, t.resuelto_en,
               c.nombre AS categoria, c.icono AS categoria_icono,
               p.nombre AS asignado_a,
               (SELECT COUNT(*) FROM ticket_comentarios tc WHERE tc.ticket_id = t.id) AS comentarios
        FROM tickets t
        INNER JOIN ticket_categorias c ON c.id = t.categoria_id
        LEFT  JOIN personas p          ON p.id = t.asignado_persona_id
    ";

    // Las condiciones se acumulan en un arreglo para no terminar con un
    // WHERE 1=1 pegado a mano.
    $condiciones = [];
    $parametros  = [];

    if ($soloPropios) {
        $condiciones[] = 't.reportado_persona_id = :yo';
        $parametros['yo'] = (int) $yo['persona_id'];
    }

    $estado = param('estado');
    if ($estado !== '' && in_array($estado, ESTADOS_TICKET, true)) {
        $condiciones[] = 't.estado = :estado';
        $parametros['estado'] = $estado;
    } else {
        // Sin pedir un estado puntual, los cerrados no estorban la lista: son
        // los que ya no hace falta mirar. Tocando la pastilla "Cerrados" (que
        // manda estado=cerrado) se ven igual.
        $condiciones[] = "t.estado <> 'cerrado'";
    }

    $prioridad = param('prioridad');
    if ($prioridad !== '' && in_array($prioridad, PRIORIDADES_TICKET, true)) {
        $condiciones[] = 't.prioridad = :prioridad';
        $parametros['prioridad'] = $prioridad;
    }

    $categoria = param_int('categoria_id');
    if ($categoria > 0) {
        $condiciones[] = 't.categoria_id = :categoria';
        $parametros['categoria'] = $categoria;
    }

    $busqueda = param('q');
    if ($busqueda !== '') {
        // Un solo placeholder sobre los campos concatenados: PDO usa consultas
        // preparadas reales y ahí un nombre no se puede repetir.
        $condiciones[] = "CONCAT_WS(' ', t.folio, t.titulo, t.descripcion, t.reportado_por) LIKE :q";
        $parametros['q'] = '%' . $busqueda . '%';
    }

    $whereSql = $condiciones !== [] ? ' WHERE ' . implode(' AND ', $condiciones) : '';
    $sql .= $whereSql;

    $stmtTotal = db()->prepare("SELECT COUNT(*) FROM tickets t {$whereSql}");
    $stmtTotal->execute($parametros);
    $total = (int) $stmtTotal->fetchColumn();

    $porPagina    = POR_PAGINA_TICKETS;
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina       = max(1, min($totalPaginas, param_int('pagina', 1)));
    $offset       = ($pagina - 1) * $porPagina;

    // La fecha manda: es lo que elige "Orden" en la pantalla, y tenía que
    // notarse al elegirlo. Estado y prioridad quedan como desempate para las
    // pocas veces que dos tickets caen en el mismo instante (los de prueba,
    // por ejemplo, se cargaron todos con la misma hora) — así entre iguales
    // siguen apareciendo primero los abiertos y los críticos, pero ya no
    // tapan el orden por fecha que la persona pidió ver.
    $direccionFecha = param('orden') === 'antiguos' ? 'ASC' : 'DESC';
    $sql .= "
        ORDER BY
            t.creado_en {$direccionFecha},
            FIELD(t.estado, 'abierto', 'en_proceso', 'resuelto', 'cerrado'),
            (t.prioridad IS NULL),
            FIELD(t.prioridad, 'critica', 'alta', 'media', 'baja')
        LIMIT {$porPagina} OFFSET {$offset}
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
    $tickets = $stmt->fetchAll();

    // Contadores para las pastillas de la interfaz, dentro del mismo alcance
    // (todos, o solo los propios si es la vista de funcionario).
    if ($soloPropios) {
        $stmtResumen = db()->prepare(
            'SELECT estado, COUNT(*) AS cuantos FROM tickets WHERE reportado_persona_id = ? GROUP BY estado'
        );
        $stmtResumen->execute([(int) $yo['persona_id']]);
        $resumen = $stmtResumen->fetchAll();
    } else {
        $resumen = db()->query(
            'SELECT estado, COUNT(*) AS cuantos FROM tickets GROUP BY estado'
        )->fetchAll();
    }

    $contadores = ['abierto' => 0, 'en_proceso' => 0, 'resuelto' => 0, 'cerrado' => 0];
    foreach ($resumen as $r) {
        $contadores[$r['estado']] = (int) $r['cuantos'];
    }

    responder_json([
        'success'      => true,
        'vista'        => $soloPropios ? 'funcionario' : 'admin',
        'tickets'      => $tickets,
        'total'        => $total,
        'pagina'       => $pagina,
        'porPagina'    => $porPagina,
        'totalPaginas' => $totalPaginas,
        'contadores'   => $contadores,
        'actualizado'  => date('c'),
    ]);
}

/** Todo lo que necesitan los selectores del formulario, en una sola llamada. */
function catalogos(): void
{
    $yo = usuario_actual();
    $esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);

    $categorias = db()->query("
        SELECT id, nombre, icono FROM ticket_categorias
        WHERE activo = 1 ORDER BY orden, nombre
    ")->fetchAll();

    // Solo administración/desarrollo la necesita: es para el selector de "a
    // nombre de quién" al crear un ticket presencial (ver crear()). Para
    // cualquier otro rol sería una lista que nunca se usa.
    $usuarios = [];
    if ($esAdmin) {
        $usuarios = array_map(static fn(array $u): array => [
            'id'     => (int) $u['id'],
            'nombre' => $u['nombre'],
            'rol'    => $u['rol'],
        ], db()->query("
            SELECT u.id, COALESCE(p.nombre, u.usuario) AS nombre, u.rol
            FROM usuarios u
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE u.activo = 1 AND u.rol IN ('" . ROL_FUNCIONARIO . "', '" . ROL_EXTRAORDINARIO . "', '" . ROL_ADMINISTRADOR . "')
            ORDER BY nombre
        ")->fetchAll());
    }

    responder_json([
        'success'     => true,
        'categorias'  => $categorias,
        'estados'     => ESTADOS_TICKET,
        'prioridades' => PRIORIDADES_TICKET,
        'usuarios'    => $usuarios,
        'yo'          => [
            'nombre'          => $yo['nombre'],
            'rol'             => $yo['rol'],
            'esAdmin'         => $esAdmin,
            'esDesarrollador' => $yo['rol'] === ROL_DESARROLLADOR,
        ],
    ]);
}

/** Un ticket con su historial completo y los datos de contacto de reportante/asignado. */
function ver(): void
{
    $yo = usuario_actual();

    $folio = param('folio');
    $id    = param_int('id');

    if ($folio === '' && $id === 0) {
        responder_json(['success' => false, 'error' => 'Falta el folio o el id'], 400);
    }

    // Los teléfonos son varios por persona (persona_telefonos): se traen con
    // GROUP_CONCAT, igual que en Funcionarios, y se separan en la pantalla.
    $sql = "
        SELECT t.*,
               c.nombre AS categoria, c.icono AS categoria_icono,
               rp.nombre     AS reportado_nombre_real,
               rp.correo     AS reportado_correo_real,
               rp.extension  AS reportado_extension,
               GROUP_CONCAT(DISTINCT rt.telefono ORDER BY rt.id SEPARATOR '|') AS reportado_telefonos,
               ap.nombre     AS asignado_a,
               ap.correo     AS asignado_correo,
               ap.extension  AS asignado_extension,
               GROUP_CONCAT(DISTINCT at.telefono ORDER BY at.id SEPARATOR '|') AS asignado_telefonos
        FROM tickets t
        INNER JOIN ticket_categorias c   ON c.id = t.categoria_id
        LEFT  JOIN personas rp           ON rp.id = t.reportado_persona_id
        LEFT  JOIN persona_telefonos rt  ON rt.persona_id = rp.id
        LEFT  JOIN personas ap           ON ap.id = t.asignado_persona_id
        LEFT  JOIN persona_telefonos at  ON at.persona_id = ap.id
        WHERE " . ($folio !== '' ? 't.folio = ?' : 't.id = ?') . "
        GROUP BY t.id
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$folio !== '' ? $folio : $id]);
    $ticket = $stmt->fetch();

    if ($ticket === false) {
        responder_json(['success' => false, 'error' => 'Ticket no encontrado'], 404);
    }

    if (vista_activa($yo) === 'funcionario'
        && (int) $ticket['reportado_persona_id'] !== (int) $yo['persona_id']
    ) {
        responder_json(['success' => false, 'error' => 'Ese ticket no te pertenece'], 403);
    }

    $ticket['contacto_reportado'] = [
        'nombre'    => $ticket['reportado_nombre_real'] ?: $ticket['reportado_por'],
        'correo'    => $ticket['reportado_correo_real'] ?: $ticket['reportado_correo'],
        'extension' => $ticket['reportado_extension'],
        'telefonos' => $ticket['reportado_telefonos'] ? explode('|', $ticket['reportado_telefonos']) : [],
    ];

    $ticket['contacto_asignado'] = $ticket['asignado_a'] ? [
        'nombre'    => $ticket['asignado_a'],
        'correo'    => $ticket['asignado_correo'],
        'extension' => $ticket['asignado_extension'],
        'telefonos' => $ticket['asignado_telefonos'] ? explode('|', $ticket['asignado_telefonos']) : [],
    ] : null;

    $stmt = db()->prepare("
        SELECT autor, comentario, estado_nuevo, creado_en
        FROM ticket_comentarios
        WHERE ticket_id = ?
        ORDER BY creado_en
    ");
    $stmt->execute([(int) $ticket['id']]);

    responder_json([
        'success'     => true,
        'ticket'      => $ticket,
        'comentarios' => $stmt->fetchAll(),
        'puedeCerrar' => vista_activa($yo) === 'admin' || (int) $ticket['reportado_persona_id'] === (int) $yo['persona_id'],
        'esAdmin'     => vista_activa($yo) === 'admin',
    ]);
}

/**
 * Registra un ticket nuevo.
 *
 * Quien reporta ya está logueado, así que no se le vuelve a pedir su nombre
 * ni su correo: se toman de la sesión. Tampoco se pide prioridad: la fija
 * después quien administra, según qué tan urgente sea.
 *
 * El folio se arma acá, con la fecha y un correlativo del día: TK-20260821-001.
 */
function crear(): void
{
    $yo = usuario_actual();

    exigir(['titulo', 'descripcion']);

    $categoria = param_int('categoria_id');

    // Categoría: si no viene o no existe, se usa "Otro" en vez de rechazar el
    // reporte. Perder el reporte de una avería por un selector mal llenado
    // sería peor que clasificarlo mal.
    if ($categoria === 0) {
        $categoria = (int) db()->query(
            "SELECT id FROM ticket_categorias WHERE nombre = 'Otro' LIMIT 1"
        )->fetchColumn();
    }

    // Administración y desarrollo pueden reportar A NOMBRE DE otra persona
    // (por ejemplo, alguien que llegó presencial y no pudo hacerlo desde su
    // cuenta): en ese caso el ticket queda a nombre de quien se seleccionó,
    // no de quien lo está tecleando. Para cualquier otro rol se ignora el
    // parámetro — no puede reportar a nombre de nadie más.
    $reportante = $yo;
    $usuarioId  = param_int('usuario_id');
    if ($usuarioId > 0 && in_array($yo['rol'], ROLES_ADMIN, true)) {
        $stmt = db()->prepare("
            SELECT u.rol, COALESCE(p.nombre, u.usuario) AS nombre, p.correo, u.persona_id
            FROM usuarios u
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE u.id = ? AND u.activo = 1
        ");
        $stmt->execute([$usuarioId]);
        $otro = $stmt->fetch();

        if ($otro !== false && in_array($otro['rol'], [ROL_FUNCIONARIO, ROL_EXTRAORDINARIO, ROL_ADMINISTRADOR], true)) {
            $reportante = [
                'nombre'      => $otro['nombre'],
                'correo'      => $otro['correo'] ?? '',
                'persona_id'  => $otro['persona_id'],
            ];
        }
    }

    $personaId = (int) ($reportante['persona_id'] ?? 0) > 0 ? (int) $reportante['persona_id'] : null;

    db()->beginTransaction();

    $hoy = date('Ymd');
    $stmt = db()->prepare('SELECT COUNT(*) FROM tickets WHERE folio LIKE ?');
    $stmt->execute(["TK-{$hoy}-%"]);
    $folio = sprintf('TK-%s-%03d', $hoy, ((int) $stmt->fetchColumn()) + 1);

    $stmt = db()->prepare("
        INSERT INTO tickets (folio, categoria_id,
                             titulo, descripcion, prioridad, estado,
                             reportado_por, reportado_correo, reportado_persona_id)
        VALUES (:folio, :categoria,
                :titulo, :descripcion, NULL, 'abierto',
                :reportado, :correo, :persona)
    ");
    $stmt->execute([
        'folio'       => $folio,
        'categoria'   => $categoria,
        'titulo'      => param('titulo'),
        'descripcion' => param('descripcion'),
        'reportado'   => $reportante['nombre'],
        'correo'      => ($reportante['correo'] ?? '') !== '' ? $reportante['correo'] : null,
        'persona'     => $personaId,
    ]);

    $ticketId = (int) db()->lastInsertId();

    db()->commit();

    notificar_ticket_nuevo($folio, param('titulo'), $reportante['nombre']);

    responder_json([
        'success' => true,
        'folio'   => $folio,
        'id'      => $ticketId,
    ], 201);
}

/** Aviso al encargado de soporte (y a quien da seguimiento) de que entró un ticket nuevo. */
function notificar_ticket_nuevo(string $folio, string $titulo, string $reportadoPor): void
{
    $asunto = "Nuevo ticket {$folio}: {$titulo}";
    $cuerpo = '<p>Llegó un ticket nuevo al sistema PAA.</p>'
        . correo_campo('Folio', $folio)
        . correo_campo('Título', $titulo)
        . correo_campo('Reportado por', $reportadoPor)
        . '<p>Podés revisarlo desde el módulo de Tickets en el sistema.</p>';
    $cuerpoHtml = plantilla_correo($asunto, $cuerpo);

    foreach (['andrei.fallas@ucr.ac.cr', 'alvaro.moyaarrieta@ucr.ac.cr'] as $destinatario) {
        enviar_correo_html('tickets', $destinatario, $asunto, $cuerpoHtml, $folio);
    }
}

function comentar(): void
{
    $yo = usuario_actual();

    exigir(['folio', 'comentario']);

    $ticket = ticket_por_folio(param('folio'));
    if ($ticket === null) {
        responder_json(['success' => false, 'error' => 'Ticket no encontrado'], 404);
    }

    // Un comentario puede venir acompañado de un cambio de estado; se guardan
    // juntos para que el historial cuente qué pasó y por qué.
    $estadoNuevo = param('estado');
    if ($estadoNuevo !== '') {
        if (!in_array($estadoNuevo, ESTADOS_TICKET, true)) {
            responder_json(['success' => false, 'error' => 'Estado inválido'], 400);
        }
        verificar_permiso_estado($ticket, $yo, $estadoNuevo);
    }

    db()->beginTransaction();

    db()->prepare("
        INSERT INTO ticket_comentarios (ticket_id, autor, persona_id, comentario, estado_nuevo)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        (int) $ticket['id'],
        $yo['nombre'],
        (int) $yo['persona_id'] > 0 ? (int) $yo['persona_id'] : null,
        param('comentario'),
        $estadoNuevo !== '' ? $estadoNuevo : null,
    ]);

    if ($estadoNuevo !== '') {
        aplicar_estado((int) $ticket['id'], $estadoNuevo);
    }

    db()->commit();

    if ($estadoNuevo !== '' && $estadoNuevo !== $ticket['estado']) {
        notificar_cambio_estado($ticket, $estadoNuevo);
    }

    responder_json(['success' => true]);
}

function cambiar_estado(): void
{
    $yo = usuario_actual();

    exigir(['folio', 'estado']);

    $estado = param('estado');
    if (!in_array($estado, ESTADOS_TICKET, true)) {
        responder_json(['success' => false, 'error' => 'Estado inválido'], 400);
    }

    $ticket = ticket_por_folio(param('folio'));
    if ($ticket === null) {
        responder_json(['success' => false, 'error' => 'Ticket no encontrado'], 404);
    }

    verificar_permiso_estado($ticket, $yo, $estado);

    db()->beginTransaction();

    aplicar_estado((int) $ticket['id'], $estado);

    // Todo cambio de estado deja rastro, aunque nadie escriba un comentario.
    db()->prepare("
        INSERT INTO ticket_comentarios (ticket_id, autor, persona_id, comentario, estado_nuevo)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        (int) $ticket['id'],
        $yo['nombre'],
        (int) $yo['persona_id'] > 0 ? (int) $yo['persona_id'] : null,
        'Cambio de estado a ' . texto_estado($estado),
        $estado,
    ]);

    db()->commit();

    if ($estado !== $ticket['estado']) {
        notificar_cambio_estado($ticket, $estado);
    }

    responder_json(['success' => true, 'estado' => $estado]);
}

/** Fija (o quita) la prioridad de un ticket. Solo administración. */
function asignar_prioridad(): void
{
    $yo = usuario_actual();

    if (vista_activa($yo) !== 'admin') {
        responder_json(['success' => false, 'error' => 'No tenés permiso para asignar prioridad'], 403);
    }

    exigir(['folio']);

    $prioridad = param('prioridad');
    if ($prioridad !== '' && !in_array($prioridad, PRIORIDADES_TICKET, true)) {
        responder_json(['success' => false, 'error' => 'Prioridad inválida'], 400);
    }

    $ticket = ticket_por_folio(param('folio'));
    if ($ticket === null) {
        responder_json(['success' => false, 'error' => 'Ticket no encontrado'], 404);
    }

    db()->prepare('UPDATE tickets SET prioridad = ? WHERE id = ?')
        ->execute([$prioridad !== '' ? $prioridad : null, (int) $ticket['id']]);

    responder_json(['success' => true, 'prioridad' => $prioridad !== '' ? $prioridad : null]);
}

/**
 * Borra de una sola vez todos los tickets cerrados.
 *
 * Es un borrado real, no un archivado: los comentarios de cada uno se van
 * con él (ON DELETE CASCADE en ticket_comentarios, ver schema/010_tickets.sql).
 * Solo administración lo puede hacer, y el propio folio del ticket ya no
 * sirve para nada como comprobante después de esto — es intencional, es
 * para limpiar la tabla, no para archivar historial.
 */
function limpiar_cerrados(): void
{
    $yo = usuario_actual();

    if (vista_activa($yo) !== 'admin') {
        responder_json(['success' => false, 'error' => 'No tenés permiso para limpiar tickets'], 403);
    }

    $borrados = (int) db()->exec("DELETE FROM tickets WHERE estado = 'cerrado'");

    bitacora_apuntar('tickets', 'limpiarCerrados', "borrados={$borrados}");

    responder_json([
        'success'  => true,
        'borrados' => $borrados,
        'mensaje'  => $borrados > 0
            ? "Se borraron {$borrados} ticket(s) cerrado(s)."
            : 'No había ningún ticket cerrado para borrar.',
    ]);
}

/** Busca un ticket por folio con los campos que hacen falta para validar permisos y notificar. */
function ticket_por_folio(string $folio): ?array
{
    $stmt = db()->prepare('
        SELECT id, folio, estado, reportado_persona_id, reportado_correo, reportado_por
        FROM tickets WHERE folio = ?
    ');
    $stmt->execute([$folio]);
    $ticket = $stmt->fetch();

    return $ticket === false ? null : $ticket;
}

/**
 * Revienta la petición (403) si quien pide el cambio no tiene permiso para
 * moverlo a ese estado.
 *
 * Administración puede mover un ticket a cualquier estado. Un funcionario
 * solo puede cerrar SUS PROPIOS tickets — no puede ponerlos en proceso ni
 * marcarlos como resueltos, eso le corresponde a quien administra.
 */
function verificar_permiso_estado(array $ticket, array $yo, string $estadoNuevo): void
{
    if (vista_activa($yo) === 'admin') {
        return;
    }

    if ($estadoNuevo !== 'cerrado') {
        responder_json([
            'success' => false,
            'error'   => 'Solo un administrador puede mover el ticket a ese estado',
        ], 403);
    }

    if ((int) $ticket['reportado_persona_id'] !== (int) $yo['persona_id']) {
        responder_json(['success' => false, 'error' => 'Solo podés cerrar tus propios tickets'], 403);
    }
}

/** Aplica el estado y sella la fecha de resolución cuando corresponde. */
function aplicar_estado(int $ticketId, string $estado): void
{
    // resuelto_en se llena la primera vez que se resuelve y no se borra si
    // después se reabre: interesa saber cuándo se dio por resuelto.
    db()->prepare("
        UPDATE tickets
        SET estado = :estado,
            resuelto_en = CASE
                WHEN :estado2 IN ('resuelto','cerrado') AND resuelto_en IS NULL THEN NOW()
                ELSE resuelto_en
            END
        WHERE id = :id
    ")->execute([
        'estado'  => $estado,
        'estado2' => $estado,
        'id'      => $ticketId,
    ]);
}

/**
 * Avisa por correo a quien reportó que su ticket cambió de estado.
 *
 * El destinatario sale de la base (columna guardada al crear el ticket, o
 * el catálogo de personas si hiciera falta), nunca del request: es la regla
 * de includes/correo.php.
 */
function notificar_cambio_estado(array $ticket, string $estadoNuevo): void
{
    $destinatario = $ticket['reportado_correo'] ?? null;

    if (($destinatario === null || $destinatario === '') && $ticket['reportado_persona_id']) {
        $stmt = db()->prepare('SELECT correo FROM personas WHERE id = ?');
        $stmt->execute([(int) $ticket['reportado_persona_id']]);
        $destinatario = $stmt->fetchColumn() ?: null;
    }

    if (!$destinatario) {
        return;
    }

    $texto = texto_estado($estadoNuevo);
    $asunto = "Tu ticket {$ticket['folio']} cambió a {$texto}";
    $colorEstado = match ($estadoNuevo) {
        'resuelto', 'cerrado' => '#10b981',
        'en_proceso'          => '#f59e0b',
        default               => '#0055a4',
    };
    $cuerpo = '<p>El ticket que reportaste cambió de estado.</p>'
        . correo_campo('Folio', $ticket['folio'])
        . '<div style="margin-bottom:12px;">' . correo_insignia($texto, $colorEstado) . '</div>'
        . '<p>Podés revisar el detalle y el historial completo desde el sistema PAA.</p>';
    $cuerpoHtml = plantilla_correo($asunto, $cuerpo);

    enviar_correo_html('tickets', $destinatario, $asunto, $cuerpoHtml, $ticket['folio']);
}

function texto_estado(string $estado): string
{
    return match ($estado) {
        'abierto'    => 'Abierto',
        'en_proceso' => 'En proceso',
        'resuelto'   => 'Resuelto',
        'cerrado'    => 'Cerrado',
        default      => $estado,
    };
}
