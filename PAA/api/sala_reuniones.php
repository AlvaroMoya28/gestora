<?php
/**
 * Endpoint del módulo Sala de Reuniones (solicitud y préstamo de la sala).
 *
 *   GET  accion=listar        → solicitudes (propias o todas, según la vista)
 *   GET  accion=reglamento    → el reglamento de uso vigente
 *   GET  accion=ocupacion     → lo ya reservado de una fecha, para ver si choca
 *   POST accion=crear         → registra una solicitud y avisa a administración
 *   POST accion=resolver      → aprueba o rechaza y le avisa a quien pidió
 *   POST accion=cancelar      → quien pidió da marcha atrás; administración también
 *   POST accion=equipo        → marca el retiro/devolución del teclado y el mouse
 *   POST accion=guardarReglamento → administración corrige el texto del reglamento
 *
 * Este archivo y `SalaReuniones/index.php` son el módulo completo, más su
 * migración `schema/043_sala_reuniones.sql`.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/correo.php';

$yo = exigir_modulo_api('sala_reuniones');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

const ESTADOS_SALA = ['pendiente', 'aprobada', 'rechazada', 'cancelada'];

/**
 * Horario en el que se puede PEDIR la sala por el formulario.
 *
 * La sala se cierra a las 4 p.m., de lunes a viernes. Esto se aplica a las
 * solicitudes normales (crear()) — no a lo que administración anota a mano en
 * crear_manual(), porque ahí puede haber un arreglo un sábado o un bloqueo
 * después de esa hora, y administración no necesita pedirse permiso a sí
 * misma para anotar la realidad.
 */
const SALA_HORA_APERTURA = '07:00:00';
const SALA_HORA_CIERRE   = '16:00:00';

/**
 * A quién se le avisa de cada solicitud nueva.
 *
 * Misma lista que api/tickets.php y por la misma razón: son direcciones fijas
 * del personal de informática, no vienen del cliente. Ver el encabezado de
 * includes/correo.php sobre por qué el destinatario nunca puede venir de un
 * POST.
 */
const AVISAR_A_SALA = ['andrei.fallas@ucr.ac.cr', 'alvaro.moyaarrieta@ucr.ac.cr'];

/**
 * La cuenta de correo con la que sale este módulo.
 *
 * Si `includes/config.php` no define `sala_reuniones` dentro de
 * `correo_cuentas`, cae a la general (o a la configuración vieja
 * `correo_smtp`, si el servidor todavía la tiene). Si no hay ninguna, el envío
 * falla con mensaje claro y queda registrado — pero la solicitud igual se
 * guarda: perder la reserva porque no salió un correo sería peor.
 */
const MODULO_CORREO_SALA = 'sala_reuniones';

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':            listar();             break;
        case 'reglamento':        reglamento();         break;
        case 'ocupacion':         ocupacion();          break;
        case 'calendario':        calendario();         break;
        case 'crear':             crear();              break;
        case 'crearManual':       crear_manual();       break;
        case 'resolver':          resolver();           break;
        case 'cancelar':          cancelar();           break;
        case 'equipo':            equipo();             break;
        case 'guardarReglamento': guardar_reglamento(); break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('sala_reuniones', $e);
}

// ------------------------------------------------------------------
// Quién es quién
// ------------------------------------------------------------------

/**
 * Qué pantalla está usando quien pide esto: 'admin' o 'funcionario'.
 *
 * Igual que en api/tickets.php: un funcionario siempre ve la suya y
 * administración siempre la de administración, sin forma de cambiarlo con un
 * parámetro. La excepción es `desarrollador`, que necesita poder probar las
 * dos pantallas — para ese rol, y solo para ese, manda el parámetro `vista`.
 */
function vista_activa(array $yo): string
{
    $esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);

    if (($yo['rolReal'] ?? $yo['rol']) === ROL_DESARROLLADOR) {
        $vista = param('vista');
        if ($vista === 'funcionario' || $vista === 'admin') {
            return $vista;
        }
    }

    return $esAdmin ? 'admin' : 'funcionario';
}

/** Corta la operación si quien la pide no está en la pantalla de administración. */
function exigir_admin(array $yo): void
{
    if (vista_activa($yo) !== 'admin') {
        responder_json([
            'success' => false,
            'error'   => 'Solo administración puede hacer esto.',
        ], 403);
    }
}

// ------------------------------------------------------------------
// Lecturas
// ------------------------------------------------------------------

/**
 * Listado de solicitudes.
 *
 * En vista funcionario se fuerza a las propias sin importar qué filtros mande
 * el cliente: una reserva ajena dice con quién se reúne alguien y para qué.
 */
function listar(): void
{
    $yo = usuario_actual();
    $esAdmin = vista_activa($yo) === 'admin';

    $condiciones = [];
    $parametros  = [];

    if (!$esAdmin) {
        $condiciones[] = 'r.solicitante_usuario_id = :yo';
        $parametros['yo'] = (int) $yo['id'];
    }

    $estado = param('estado');
    if ($estado !== '' && in_array($estado, ESTADOS_SALA, true)) {
        $condiciones[] = 'r.estado = :estado';
        $parametros['estado'] = $estado;
    }

    $desde = param('desde');
    if (fecha_valida($desde)) {
        $condiciones[] = 'r.fecha >= :desde';
        $parametros['desde'] = $desde;
    }

    $hasta = param('hasta');
    if (fecha_valida($hasta)) {
        $condiciones[] = 'r.fecha <= :hasta';
        $parametros['hasta'] = $hasta;
    }

    $q = trim(param('q'));
    if ($q !== '') {
        $condiciones[] = '(r.folio LIKE :q OR r.motivo LIKE :q OR r.solicitante_nombre LIKE :q)';
        $parametros['q'] = '%' . $q . '%';
    }

    $sql = 'SELECT r.* FROM sala_reservas r';
    if ($condiciones !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condiciones);
    }
    // Lo próximo primero: una reserva de mañana importa más que una de hace un
    // mes, y las pendientes son justo las que hay que resolver.
    $sql .= ' ORDER BY r.fecha DESC, r.hora_inicio DESC LIMIT 400';

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    $reservas = array_map('fila_reserva', $stmt->fetchAll());

    responder_json([
        'success'    => true,
        'vista'      => $esAdmin ? 'admin' : 'funcionario',
        'puedeElegirVista' => (usuario_actual()['rolReal'] ?? '') === ROL_DESARROLLADOR,
        'reservas'   => $reservas,
        'pendientes' => (int) db()->query(
            "SELECT COUNT(*) FROM sala_reservas WHERE estado = 'pendiente'"
        )->fetchColumn(),
    ]);
}

/** El reglamento vigente. Lo lee cualquiera; solo administración lo cambia. */
function reglamento(): void
{
    $fila = db()->query('SELECT texto, actualizado_por, actualizado_en FROM sala_reglamento WHERE id = 1')->fetch();

    responder_json([
        'success'    => true,
        'texto'      => $fila['texto'] ?? '',
        'actualizado' => $fila === false ? null : [
            'por' => $fila['actualizado_por'],
            'en'  => $fila['actualizado_en'],
        ],
    ]);
}

/**
 * Lo que ya está reservado un día, para que quien solicita vea si choca antes
 * de mandar la solicitud.
 *
 * A propósito NO devuelve el motivo ni quién pidió: para saber si la sala está
 * libre alcanza con la franja horaria, y esto lo consulta cualquiera.
 */
function ocupacion(): void
{
    $fecha = param('fecha');

    if (!fecha_valida($fecha)) {
        responder_json(['success' => false, 'error' => 'Fecha inválida'], 400);
    }

    $stmt = db()->prepare("
        SELECT hora_inicio, hora_fin, estado
        FROM sala_reservas
        WHERE fecha = ? AND estado IN ('pendiente', 'aprobada')
        ORDER BY hora_inicio
    ");
    $stmt->execute([$fecha]);

    responder_json(['success' => true, 'fecha' => $fecha, 'franjas' => $stmt->fetchAll()]);
}

/**
 * Un mes completo de reservas y bloqueos, para el calendario.
 *
 * Es SOLO PARA VER: acá no se reserva nada, aunque un día salga libre en el
 * calendario la solicitud igual tiene que pasar por aprobación — puede haber
 * algo que el calendario no cuenta (ver crear()/resolver()).
 *
 * En vista funcionario se devuelve la franja sin motivo ni quién la pidió,
 * mismo criterio que ocupacion(). Administración sí ve el detalle completo,
 * porque desde ahí decide.
 */
function calendario(): void
{
    $yo = usuario_actual();
    $esAdmin = vista_activa($yo) === 'admin';

    $anio = param_int('anio', (int) date('Y'));
    $mes  = param_int('mes', (int) date('n'));
    if ($mes < 1 || $mes > 12) {
        $mes = (int) date('n');
    }

    $desde = sprintf('%04d-%02d-01', $anio, $mes);
    $hasta = date('Y-m-t', strtotime($desde));

    $stmt = db()->prepare("
        SELECT *
        FROM sala_reservas
        WHERE fecha BETWEEN ? AND ?
          AND estado IN ('pendiente', 'aprobada')
        ORDER BY fecha, hora_inicio
    ");
    $stmt->execute([$desde, $hasta]);

    $porDia = [];
    foreach ($stmt->fetchAll() as $f) {
        $item = $esAdmin
            ? fila_reserva($f)
            : [
                'horaInicio' => substr((string) $f['hora_inicio'], 0, 5),
                'horaFin'    => substr((string) $f['hora_fin'], 0, 5),
                'tipo'       => $f['tipo'],
                'estado'     => $f['estado'],
              ];
        $porDia[$f['fecha']][] = $item;
    }

    responder_json(['success' => true, 'anio' => $anio, 'mes' => $mes, 'dias' => $porDia]);
}

// ------------------------------------------------------------------
// Escrituras
// ------------------------------------------------------------------

/** Registra una solicitud nueva y le avisa a administración. */
function crear(): void
{
    $yo = usuario_actual();

    exigir(['fecha', 'hora_inicio', 'hora_fin', 'motivo']);

    $fecha  = param('fecha');
    $inicio = normalizar_hora(param('hora_inicio'));
    $fin    = normalizar_hora(param('hora_fin'));
    $motivo = trim(param('motivo'));

    if (!fecha_valida($fecha)) {
        responder_json(['success' => false, 'error' => 'La fecha no es válida.'], 400);
    }
    if ($fecha < date('Y-m-d')) {
        responder_json(['success' => false, 'error' => 'No se puede reservar una fecha que ya pasó.'], 400);
    }
    if ($inicio === null || $fin === null) {
        responder_json(['success' => false, 'error' => 'La hora no es válida.'], 400);
    }
    if ($fin <= $inicio) {
        responder_json(['success' => false, 'error' => 'La hora de fin tiene que ser posterior a la de inicio.'], 400);
    }
    if ($motivo === '') {
        responder_json(['success' => false, 'error' => 'Hay que decir para qué se necesita la sala.'], 400);
    }

    // La sala se presta de lunes a viernes (date('N') da 1=lunes .. 7=domingo)
    // y se cierra a las 4 p.m. — a esa hora ya no hay a quién dejarle la
    // llave. No aplica a lo que administración anota a mano (crear_manual()).
    if ((int) date('N', strtotime($fecha)) > 5) {
        responder_json(['success' => false, 'error' => 'La sala solo se presta de lunes a viernes.'], 400);
    }
    if ($inicio < SALA_HORA_APERTURA || $fin > SALA_HORA_CIERRE) {
        responder_json([
            'success' => false,
            'error'   => 'La sala solo se presta de 7:00 a.m. a 4:00 p.m. — a esa hora se cierra.',
        ], 400);
    }

    // Choque con una reserva YA APROBADA: eso no se manda a revisión, se corta
    // acá con el horario ocupado a la vista, para que la persona proponga otro
    // en vez de esperar días por un rechazo previsible. Contra una pendiente sí
    // se deja pasar: decidir cuál de las dos vale le toca a administración.
    $choque = reserva_que_choca($fecha, $inicio, $fin, ['aprobada']);
    if ($choque !== null) {
        responder_json([
            'success'  => false,
            'error'    => 'La sala ya está apartada ese día de '
                        . substr($choque['hora_inicio'], 0, 5) . ' a ' . substr($choque['hora_fin'], 0, 5)
                        . '. Probá con otro horario.',
            'ocupada'  => $choque,
        ], 409);
    }

    $hoy = date('Ymd');
    $stmt = db()->prepare('SELECT COUNT(*) FROM sala_reservas WHERE folio LIKE ?');
    $stmt->execute(["SR-{$hoy}-%"]);
    $folio = sprintf('SR-%s-%03d', $hoy, ((int) $stmt->fetchColumn()) + 1);

    $personas = param_int('personas', 1);
    if ($personas < 1)   { $personas = 1; }
    if ($personas > 100) { $personas = 100; }

    $stmt = db()->prepare("
        INSERT INTO sala_reservas
            (folio, solicitante_usuario_id, solicitante_persona_id,
             solicitante_nombre, solicitante_correo,
             fecha, hora_inicio, hora_fin,
             motivo, informacion_adicional, personas, requiere_proyector, estado)
        VALUES
            (:folio, :usuario, :persona,
             :nombre, :correo,
             :fecha, :inicio, :fin,
             :motivo, :extra, :personas, :proyector, 'pendiente')
    ");
    $stmt->execute([
        'folio'     => $folio,
        'usuario'   => (int) $yo['id'],
        'persona'   => ((int) ($yo['persona_id'] ?? 0)) > 0 ? (int) $yo['persona_id'] : null,
        'nombre'    => $yo['nombre'],
        'correo'    => ($yo['correo'] ?? '') !== '' ? $yo['correo'] : null,
        'fecha'     => $fecha,
        'inicio'    => $inicio,
        'fin'       => $fin,
        'motivo'    => $motivo,
        'extra'     => trim(param('informacion_adicional')) !== '' ? trim(param('informacion_adicional')) : null,
        'personas'  => $personas,
        'proyector' => param('requiere_proyector') === '1' ? 1 : 0,
    ]);

    $id = (int) db()->lastInsertId();

    avisar_solicitud_nueva($folio, $yo['nombre'], $fecha, $inicio, $fin, $motivo);

    responder_json(['success' => true, 'folio' => $folio, 'id' => $id], 201);
}

/**
 * Administración anota algo directo en el calendario, sin pasar por
 * aprobación (queda 'aprobada' desde que se crea).
 *
 * Dos casos:
 *   · tipo=reserva: alguien pidió la sala POR FUERA del sistema (llegó
 *     presencial, llamó, etc.) y administración lo deja anotado para que no
 *     se choque con otra cosa.
 *   · tipo=bloqueo: la sala no se puede usar (arreglos, por ejemplo). No hay
 *     a quién avisarle nada — es información, no una solicitud de nadie.
 *
 * A propósito NO se valida el horario de 7 a.m. a 4 p.m. de lunes a viernes:
 * un arreglo puede caer un sábado o después de esa hora, y administración
 * está anotando la realidad, no pidiéndose permiso a sí misma.
 */
function crear_manual(): void
{
    $yo = usuario_actual();
    exigir_admin($yo);

    exigir(['fecha', 'hora_inicio', 'hora_fin', 'motivo']);

    $fecha  = param('fecha');
    $inicio = normalizar_hora(param('hora_inicio'));
    $fin    = normalizar_hora(param('hora_fin'));
    $motivo = trim(param('motivo'));
    $tipo   = param('tipo') === 'bloqueo' ? 'bloqueo' : 'reserva';
    $nombre = trim(param('solicitante_nombre'));

    if (!fecha_valida($fecha)) {
        responder_json(['success' => false, 'error' => 'La fecha no es válida.'], 400);
    }
    if ($inicio === null || $fin === null) {
        responder_json(['success' => false, 'error' => 'La hora no es válida.'], 400);
    }
    if ($fin <= $inicio) {
        responder_json(['success' => false, 'error' => 'La hora de fin tiene que ser posterior a la de inicio.'], 400);
    }
    if ($motivo === '') {
        responder_json(['success' => false, 'error' => 'Hay que indicar el motivo.'], 400);
    }

    // Mismo chequeo de choque que una solicitud normal, contra lo que ya está
    // aprobado (reservas y bloqueos por igual).
    $choque = reserva_que_choca($fecha, $inicio, $fin, ['aprobada']);
    if ($choque !== null) {
        responder_json([
            'success' => false,
            'error'   => 'Ya hay algo agendado ese día de '
                       . substr($choque['hora_inicio'], 0, 5) . ' a ' . substr($choque['hora_fin'], 0, 5)
                       . ' (' . $choque['folio'] . ').',
            'ocupada' => $choque,
        ], 409);
    }

    $hoy = date('Ymd');
    $stmt = db()->prepare('SELECT COUNT(*) FROM sala_reservas WHERE folio LIKE ?');
    $stmt->execute(["SR-{$hoy}-%"]);
    $folio = sprintf('SR-%s-%03d', $hoy, ((int) $stmt->fetchColumn()) + 1);

    $stmt = db()->prepare("
        INSERT INTO sala_reservas
            (folio, solicitante_usuario_id, solicitante_persona_id, solicitante_nombre, solicitante_correo,
             fecha, hora_inicio, hora_fin, motivo, informacion_adicional, personas, requiere_proyector,
             estado, origen, tipo, resuelto_por, resuelto_en)
        VALUES
            (:folio, NULL, NULL, :nombre, NULL,
             :fecha, :inicio, :fin, :motivo, :extra, 1, 0,
             'aprobada', 'manual', :tipo, :quien, NOW())
    ");
    $stmt->execute([
        'folio'   => $folio,
        'nombre'  => $nombre !== '' ? $nombre : ($tipo === 'bloqueo' ? 'No disponible' : 'Reserva anotada por administración'),
        'fecha'   => $fecha,
        'inicio'  => $inicio,
        'fin'     => $fin,
        'motivo'  => $motivo,
        'extra'   => trim(param('informacion_adicional')) !== '' ? trim(param('informacion_adicional')) : null,
        'tipo'    => $tipo,
        'quien'   => $yo['nombre'],
    ]);

    responder_json(['success' => true, 'folio' => $folio, 'id' => (int) db()->lastInsertId()], 201);
}

/** Aprueba o rechaza una solicitud y le avisa por correo a quien la pidió. */
function resolver(): void
{
    $yo = usuario_actual();
    exigir_admin($yo);

    $id       = param_int('id');
    $decision = param('decision');   // 'aprobada' | 'rechazada'
    $nota     = trim(param('respuesta_motivo'));

    if (!in_array($decision, ['aprobada', 'rechazada'], true)) {
        responder_json(['success' => false, 'error' => 'Decisión inválida'], 400);
    }

    $reserva = buscar_reserva($id);

    if ($reserva['estado'] !== 'pendiente') {
        responder_json([
            'success' => false,
            'error'   => 'Esa solicitud ya está ' . etiqueta_estado($reserva['estado']) . '.',
        ], 409);
    }

    // Un rechazo se explica. Aprobar sin nota está bien: la aprobación ya dice
    // todo lo que hace falta saber.
    if ($decision === 'rechazada' && $nota === '') {
        responder_json([
            'success' => false,
            'error'   => 'Hay que decir por qué se rechaza: quien pidió la sala necesita saberlo para proponer otra cosa.',
        ], 400);
    }

    // Al aprobar sí se vuelve a revisar el choque: entre que entró la solicitud
    // y este momento se pudo haber aprobado otra para la misma franja.
    if ($decision === 'aprobada') {
        $choque = reserva_que_choca(
            $reserva['fecha'], $reserva['hora_inicio'], $reserva['hora_fin'], ['aprobada'], $id
        );
        if ($choque !== null) {
            responder_json([
                'success' => false,
                'error'   => 'No se puede aprobar: la sala ya está apartada ese día de '
                           . substr($choque['hora_inicio'], 0, 5) . ' a ' . substr($choque['hora_fin'], 0, 5)
                           . ' (' . $choque['folio'] . ').',
            ], 409);
        }
    }

    $stmt = db()->prepare("
        UPDATE sala_reservas
        SET estado = :estado, respuesta_motivo = :nota,
            resuelto_por = :quien, resuelto_en = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'estado' => $decision,
        'nota'   => $nota !== '' ? $nota : null,
        'quien'  => $yo['nombre'],
        'id'     => $id,
    ]);

    $correo = avisar_decision($reserva, $decision, $nota, $yo['nombre']);

    responder_json([
        'success' => true,
        'estado'  => $decision,
        // Si el correo no salió se dice acá y no se esconde: la decisión quedó
        // guardada igual, pero quien la tomó tiene que saber que a la persona
        // hay que avisarle de otra forma.
        'correo'  => $correo,
    ]);
}

/**
 * Cancela una reserva.
 *
 * Quien la pidió puede cancelar la suya mientras no haya pasado; administración
 * puede cancelar cualquiera, incluso una ya aprobada (por ejemplo si la sala se
 * necesita para otra cosa).
 */
function cancelar(): void
{
    $yo = usuario_actual();
    $id = param_int('id');

    $reserva = buscar_reserva($id);
    $esAdmin = vista_activa($yo) === 'admin';

    if (!$esAdmin && (int) $reserva['solicitante_usuario_id'] !== (int) $yo['id']) {
        responder_json(['success' => false, 'error' => 'Esa solicitud no es tuya.'], 403);
    }

    if (in_array($reserva['estado'], ['cancelada', 'rechazada'], true)) {
        responder_json([
            'success' => false,
            'error'   => 'Esa solicitud ya está ' . etiqueta_estado($reserva['estado']) . '.',
        ], 409);
    }

    $nota = trim(param('respuesta_motivo'));

    $stmt = db()->prepare("
        UPDATE sala_reservas
        SET estado = 'cancelada', respuesta_motivo = :nota,
            resuelto_por = :quien, resuelto_en = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'nota'  => $nota !== '' ? $nota : null,
        'quien' => $yo['nombre'],
        'id'    => $id,
    ]);

    // Si la cancela administración, quien pidió la sala tiene que enterarse.
    // Si la cancela la propia persona, no hace falta escribirle a sí misma;
    // se avisa a informática, que es quien tiene la sala apartada.
    if ($esAdmin && (int) $reserva['solicitante_usuario_id'] !== (int) $yo['id']) {
        avisar_decision($reserva, 'cancelada', $nota, $yo['nombre']);
    } else {
        avisar_cancelacion_propia($reserva, $yo['nombre']);
    }

    responder_json(['success' => true]);
}

/**
 * Marca el retiro o la devolución del teclado y el mouse del proyector.
 *
 * La sala tiene proyector fijo, pero el teclado y el mouse viven en la Oficina
 * de Informática. Esto es el control de que salieron y volvieron.
 */
function equipo(): void
{
    $yo = usuario_actual();
    exigir_admin($yo);

    $id  = param_int('id');
    $que = param('que');   // 'retirado' | 'devuelto' | 'deshacer'

    $reserva = buscar_reserva($id);

    if ($reserva['estado'] !== 'aprobada') {
        responder_json([
            'success' => false,
            'error'   => 'El equipo solo se entrega con la reserva aprobada.',
        ], 409);
    }

    if ($que === 'retirado') {
        $stmt = db()->prepare("
            UPDATE sala_reservas
            SET equipo_retirado_en = NOW(), equipo_retirado_por = :quien,
                equipo_devuelto_en = NULL, equipo_devuelto_por = NULL
            WHERE id = :id
        ");
        $stmt->execute(['quien' => $yo['nombre'], 'id' => $id]);

    } elseif ($que === 'devuelto') {
        if ($reserva['equipo_retirado_en'] === null) {
            responder_json([
                'success' => false,
                'error'   => 'Todavía no está registrado que se hayan retirado.',
            ], 409);
        }
        $stmt = db()->prepare("
            UPDATE sala_reservas
            SET equipo_devuelto_en = NOW(), equipo_devuelto_por = :quien
            WHERE id = :id
        ");
        $stmt->execute(['quien' => $yo['nombre'], 'id' => $id]);

    } elseif ($que === 'deshacer') {
        // Para corregir un clic equivocado sin tener que tocar la base.
        db()->prepare("
            UPDATE sala_reservas
            SET equipo_retirado_en = NULL, equipo_retirado_por = NULL,
                equipo_devuelto_en = NULL, equipo_devuelto_por = NULL
            WHERE id = ?
        ")->execute([$id]);

    } else {
        responder_json(['success' => false, 'error' => 'Operación inválida'], 400);
    }

    responder_json(['success' => true]);
}

/** Administración corrige el texto del reglamento. */
function guardar_reglamento(): void
{
    $yo = usuario_actual();
    exigir_admin($yo);

    $texto = trim(param('texto'));

    if ($texto === '') {
        responder_json(['success' => false, 'error' => 'El reglamento no puede quedar vacío.'], 400);
    }

    $stmt = db()->prepare("
        INSERT INTO sala_reglamento (id, texto, actualizado_por)
        VALUES (1, :texto, :quien)
        ON DUPLICATE KEY UPDATE texto = VALUES(texto), actualizado_por = VALUES(actualizado_por)
    ");
    $stmt->execute(['texto' => $texto, 'quien' => $yo['nombre']]);

    responder_json(['success' => true]);
}

// ------------------------------------------------------------------
// Correos
// ------------------------------------------------------------------

/** Aviso a informática de que entró una solicitud nueva. */
function avisar_solicitud_nueva(
    string $folio, string $quien, string $fecha, string $inicio, string $fin, string $motivo
): void {
    $asunto = "Solicitud de sala {$folio} · " . fecha_larga($fecha);
    $cuerpo = '<p>Entró una solicitud de préstamo de la sala de reuniones.</p>'
        . correo_campo('Folio', $folio)
        . correo_campo('Solicita', $quien)
        . correo_campo('Cuándo', fecha_larga($fecha) . ', de ' . substr($inicio, 0, 5) . ' a ' . substr($fin, 0, 5))
        . correo_campo('Motivo', $motivo)
        . '<p>Queda pendiente de aprobación en el módulo Sala de Reuniones.</p>';

    $html = plantilla_correo($asunto, $cuerpo);

    foreach (AVISAR_A_SALA as $destinatario) {
        enviar_correo_html(MODULO_CORREO_SALA, $destinatario, $asunto, $html, $folio);
    }
}

/**
 * Le avisa a quien pidió la sala qué se decidió.
 *
 * @return array{enviado: bool, error?: string} para poder decirlo en pantalla
 *         si no salió, en vez de dar por hecho que la persona se enteró.
 */
function avisar_decision(array $reserva, string $decision, string $nota, string $quienResolvio): array
{
    $correo = (string) ($reserva['solicitante_correo'] ?? '');

    // Si la cuenta no tenía correo guardado se busca en el catálogo de
    // personas, nunca en lo que mande el cliente (ver includes/correo.php).
    if ($correo === '' && ($reserva['solicitante_nombre'] ?? '') !== '') {
        $correo = (string) (correo_de_persona($reserva['solicitante_nombre']) ?? '');
    }

    if ($correo === '') {
        return ['enviado' => false, 'error' => 'La cuenta no tiene un correo registrado.'];
    }

    $cuando = fecha_larga($reserva['fecha'])
            . ', de ' . substr($reserva['hora_inicio'], 0, 5)
            . ' a ' . substr($reserva['hora_fin'], 0, 5);

    if ($decision === 'aprobada') {
        $asunto = "Sala de reuniones aprobada · {$reserva['folio']}";
        $encabezado = '<p>Tu solicitud de la sala de reuniones quedó '
            . correo_insignia('Aprobada', '#10b981') . '.</p>';
        $cierre = '<p><strong>Antes de la reunión:</strong> pasá por la Oficina de Informática a retirar '
            . 'el teclado y el mouse del proyector. Al terminar hay que devolverlos ahí mismo.</p>'
            . '<p>Si al final no vas a usar la sala, cancelá la reserva en el sistema para que quede libre.</p>';
    } elseif ($decision === 'rechazada') {
        $asunto = "Sala de reuniones no disponible · {$reserva['folio']}";
        $encabezado = '<p>Tu solicitud de la sala de reuniones quedó '
            . correo_insignia('Rechazada', '#ef4444') . '.</p>';
        $cierre = '<p>Podés volver a solicitarla para otra fecha u horario desde el sistema.</p>';
    } else {
        $asunto = "Sala de reuniones cancelada · {$reserva['folio']}";
        $encabezado = '<p>Se canceló tu reserva de la sala de reuniones.</p>';
        $cierre = '<p>Podés volver a solicitarla para otra fecha u horario desde el sistema.</p>';
    }

    $cuerpo = $encabezado
        . correo_campo('Folio', $reserva['folio'])
        . correo_campo('Cuándo', $cuando)
        . correo_campo('Motivo de la solicitud', (string) $reserva['motivo'])
        . ($nota !== '' ? correo_campo('Nota de administración', $nota) : '')
        . $cierre;

    $resultado = enviar_correo_html(
        MODULO_CORREO_SALA,
        $correo,
        $asunto,
        plantilla_correo($asunto, $cuerpo, 'Resuelto por ' . $quienResolvio),
        (string) $reserva['folio']
    );

    return $resultado['success']
        ? ['enviado' => true]
        : ['enviado' => false, 'error' => $resultado['error'] ?? 'No se pudo enviar el correo.'];
}

/** Aviso a informática de que alguien soltó su reserva. */
function avisar_cancelacion_propia(array $reserva, string $quien): void
{
    $asunto = "Sala liberada · {$reserva['folio']}";
    $cuerpo = '<p>Se canceló una reserva de la sala de reuniones. La sala queda libre en esa franja.</p>'
        . correo_campo('Folio', (string) $reserva['folio'])
        . correo_campo('Canceló', $quien)
        . correo_campo('Era para', fecha_larga($reserva['fecha'])
            . ', de ' . substr($reserva['hora_inicio'], 0, 5)
            . ' a ' . substr($reserva['hora_fin'], 0, 5));

    $html = plantilla_correo($asunto, $cuerpo);

    foreach (AVISAR_A_SALA as $destinatario) {
        enviar_correo_html(MODULO_CORREO_SALA, $destinatario, $asunto, $html, (string) $reserva['folio']);
    }
}

// ------------------------------------------------------------------
// Apoyo
// ------------------------------------------------------------------

/** Una reserva por id, o 404 si no existe. */
function buscar_reserva(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM sala_reservas WHERE id = ?');
    $stmt->execute([$id]);
    $reserva = $stmt->fetch();

    if ($reserva === false) {
        responder_json(['success' => false, 'error' => 'La solicitud no existe.'], 404);
    }

    return $reserva;
}

/**
 * ¿Hay otra reserva que pisa esta franja?
 *
 * Dos franjas se solapan cuando cada una empieza antes de que termine la otra.
 * Se compara así y no por "está dentro de" para que también cuente el caso de
 * una reserva que envuelve a la otra por completo.
 *
 * @param string[] $estados Qué estados cuentan como ocupada.
 * @param int      $excepto Id a ignorar (la propia reserva, al re-verificar).
 */
function reserva_que_choca(
    string $fecha, string $inicio, string $fin, array $estados, int $excepto = 0
): ?array {
    $marcadores = implode(',', array_fill(0, count($estados), '?'));

    $sql = "
        SELECT id, folio, hora_inicio, hora_fin, estado
        FROM sala_reservas
        WHERE fecha = ?
          AND estado IN ({$marcadores})
          AND hora_inicio < ?
          AND hora_fin > ?
          AND id <> ?
        ORDER BY hora_inicio
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge([$fecha], $estados, [$fin, $inicio, $excepto]));

    $fila = $stmt->fetch();

    return $fila === false ? null : $fila;
}

/** ¿"2026-09-15" es una fecha real y no "2026-02-31"? */
function fecha_valida(string $fecha): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return false;
    }

    [$a, $m, $d] = array_map('intval', explode('-', $fecha));

    return checkdate($m, $d, $a);
}

/** "9:30" o "09:30" o "09:30:00" → "09:30:00". null si no es una hora. */
function normalizar_hora(string $hora): ?string
{
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($hora), $m)) {
        return null;
    }

    $h = (int) $m[1];
    $i = (int) $m[2];

    if ($h > 23 || $i > 59) {
        return null;
    }

    return sprintf('%02d:%02d:00', $h, $i);
}

/** "2026-09-15" → "martes 15 de setiembre de 2026". */
function fecha_larga(string $iso): string
{
    static $dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    // "setiembre" y no "septiembre": es la forma que usa el resto del sistema.
    static $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                     'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];

    $t = strtotime($iso);
    if ($t === false) {
        return $iso;
    }

    return $dias[(int) date('w', $t)] . ' ' . (int) date('j', $t)
         . ' de ' . $meses[(int) date('n', $t)] . ' de ' . date('Y', $t);
}

/** Cómo se lee un estado en una frase. */
function etiqueta_estado(string $estado): string
{
    return [
        'pendiente' => 'pendiente',
        'aprobada'  => 'aprobada',
        'rechazada' => 'rechazada',
        'cancelada' => 'cancelada',
    ][$estado] ?? $estado;
}

/** Una fila de la base tal como la espera el cliente. */
function fila_reserva(array $f): array
{
    return [
        'id'        => (int) $f['id'],
        'folio'     => $f['folio'],
        'solicitanteId'     => (int) $f['solicitante_usuario_id'],
        'solicitante'       => $f['solicitante_nombre'],
        'solicitanteCorreo' => $f['solicitante_correo'],
        'fecha'      => $f['fecha'],
        'fechaLarga' => fecha_larga($f['fecha']),
        'horaInicio' => substr((string) $f['hora_inicio'], 0, 5),
        'horaFin'    => substr((string) $f['hora_fin'], 0, 5),
        'motivo'     => $f['motivo'],
        'informacionAdicional' => $f['informacion_adicional'],
        'personas'   => (int) $f['personas'],
        'requiereProyector' => (int) $f['requiere_proyector'] === 1,
        'estado'     => $f['estado'],
        'origen'     => $f['origen'],   // 'solicitud' | 'manual'
        'tipo'       => $f['tipo'],     // 'reserva' | 'bloqueo'
        'respuesta'  => $f['respuesta_motivo'],
        'resueltoPor' => $f['resuelto_por'],
        'resueltoEn'  => $f['resuelto_en'],
        'equipo' => [
            'retiradoEn'  => $f['equipo_retirado_en'],
            'retiradoPor' => $f['equipo_retirado_por'],
            'devueltoEn'  => $f['equipo_devuelto_en'],
            'devueltoPor' => $f['equipo_devuelto_por'],
        ],
        'creadoEn' => $f['creado_en'],
    ];
}
