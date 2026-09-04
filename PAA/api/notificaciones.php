<?php
/**
 * Centro de notificaciones: un resumen de lo que necesita atención en el
 * sistema, sin tener que entrar módulo por módulo a revisar. No es un módulo
 * del catálogo (no se "abre"), es un panel flotante que se incluye en cada
 * página — ver includes/notificaciones.php.
 *
 *   GET accion=resumen → todo lo pendiente, ya priorizado
 *
 * Lo pide cualquiera con sesión, pero no todos ven lo mismo: lo operativo
 * (tickets, mantenimientos, sondeos, cuentas bloqueadas) es solo para
 * administración y desarrollo; los cambios recientes de Situaciones
 * Especiales y Horarios los ve todo el mundo —reemplaza el correo de aviso
 * que se mandaba antes al crear una situación—.
 *
 * Solo lectura a propósito: no hay nada que guardar acá, y así nunca queda
 * registrada en la bitácora (ver ACCIONES_DE_LECTURA en includes/bitacora.php),
 * que es justo lo que había que evitar después de lo que pasó con el chat.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/peticion.php';

$yo = exigir_login_api();

exigir_metodo(['GET']);

const MESES_MANTENIMIENTO_OFICINA = 6;
const DIAS_AVISO_MANTENIMIENTO_OFICINA = 30;
const MESES_SONDEO_FUNCIONARIO = 1;
const DIAS_AVISO_SONDEO_FUNCIONARIO = 7;
const DIAS_CAMBIOS_RECIENTES = 7;
const AVISOS_POR_PAGINA = 50;
const LIMITE_ITEM_COMPLETO = 200; // "Ver todas las notificaciones": sin el tope de 8 de la campanita

const ETIQUETAS_TIPO_SITUACION_NOTIF = [
    'vacaciones'     => 'Vacaciones',
    'incapacidad'    => 'Incapacidad',
    'reunion'        => 'Reunión',
    'cambio_horario' => 'Cambio de horario',
    'trabajo_remoto' => 'Trabajo remoto',
    'cita_medica'    => 'Cita médica',
    'permiso'        => 'Permiso',
    'otro'           => 'Otro',
];

$accion = param('accion', param('action', 'resumen'));

try {
    switch ($accion) {
        case 'resumen': resumen($yo);       break;
        case 'avisos':  avisos_pagina($yo); break;
        case 'todo':    todo_completo($yo); break;
        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('notificaciones', $e);
}

/**
 * "Ver todas las notificaciones" (Notificaciones/index.php): el historial
 * completo de avisos del sistema, paginado. La campanita solo muestra los
 * últimos — acá está el resto, para cuando alguien quiere repasar qué se
 * avisó antes.
 */
function avisos_pagina(array $yo): void
{
    if ($yo['rol'] === ROL_EXTRAORDINARIO) {
        responder_json(['success' => false, 'error' => 'No tenés permiso para esto'], 403);
    }

    $pagina = max(1, param_int('pagina', 1));
    $total = (int) db()->query('SELECT COUNT(*) FROM anuncios_sistema')->fetchColumn();

    $stmt = db()->prepare('
        SELECT id, titulo, mensaje, nivel, creado_por, creado_en
        FROM anuncios_sistema
        ORDER BY creado_en DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, AVISOS_POR_PAGINA, PDO::PARAM_INT);
    $stmt->bindValue(2, ($pagina - 1) * AVISOS_POR_PAGINA, PDO::PARAM_INT);
    $stmt->execute();

    responder_json([
        'success'    => true,
        'avisos'     => $stmt->fetchAll(),
        'total'      => $total,
        'pagina'     => $pagina,
        'porPagina'  => AVISOS_POR_PAGINA,
        'totalPaginas' => (int) max(1, ceil($total / AVISOS_POR_PAGINA)),
    ]);
}

/**
 * "Ver todas las notificaciones" (Notificaciones/index.php), la parte que no
 * es el historial de avisos: los mismos grupos que arma la campanita
 * (tickets, situaciones, horarios, cronograma...) pero sin el tope de 8
 * items por grupo — acá sí hay espacio en la pantalla para verlos todos.
 * Son datos en vivo, no un historial paginado: no tienen "página 2".
 */
function todo_completo(array $yo): void
{
    if ($yo['rol'] === ROL_EXTRAORDINARIO) {
        responder_json(['success' => false, 'error' => 'No tenés permiso para esto'], 403);
    }

    $grupos = [];
    if (in_array($yo['rol'], ROLES_ADMIN, true)) {
        grupos_operativos($grupos, LIMITE_ITEM_COMPLETO);
    }
    grupos_situaciones_horarios($grupos, LIMITE_ITEM_COMPLETO);

    usort($grupos, static function (array $a, array $b): int {
        $peso = ['alta' => 0, 'media' => 1, 'baja' => 2];
        return $peso[$a['urgencia']] <=> $peso[$b['urgencia']];
    });

    responder_json(['success' => true, 'grupos' => $grupos]);
}

/** Días entre hoy y una fecha futura (negativo si ya pasó). */
function dias_hasta(string $fecha): int
{
    return (int) floor((strtotime($fecha) - strtotime(date('Y-m-d'))) / 86400);
}

function resumen(array $yo): void
{
    $grupos = [];
    $esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);

    // El personal extraordinario no recibe notificaciones. Su único módulo es
    // Ingreso de Datos, justamente porque es el único donde no se ve ningún
    // dato de personas; las notificaciones le estaban entregando lo contrario
    // —quién está de vacaciones, quién tiene incapacidad o cita médica, y los
    // cambios de horario del personal— y encima enlazaban a módulos que su
    // cuenta no puede abrir.
    if ($yo['rol'] === ROL_EXTRAORDINARIO) {
        responder_json(['success' => true, 'grupos' => [], 'totalAlta' => 0, 'totalItems' => 0]);
        return;
    }

    if ($esAdmin) {
        grupos_operativos($grupos, 8);
    }

    grupos_situaciones_horarios($grupos, 8);

    // Orden final: lo urgente primero.
    usort($grupos, static function (array $a, array $b): int {
        $peso = ['alta' => 0, 'media' => 1, 'baja' => 2];
        return $peso[$a['urgencia']] <=> $peso[$b['urgencia']];
    });

    $totalAlta = 0;
    foreach ($grupos as $g) {
        if ($g['urgencia'] === 'alta') {
            $totalAlta += count(array_filter($g['items'], static fn($i) => $i['nivel'] === 'alta'));
        }
    }

    responder_json([
        'success'    => true,
        'grupos'     => $grupos,
        'totalAlta'  => $totalAlta,
        'totalItems' => array_sum(array_map(static fn($g) => count($g['items']), $grupos)),
    ]);
}

/**
 * Lo que solo le importa a administración y desarrollo: operativo del día a
 * día. $limite es cuántos items entran por grupo — 8 para la campanita, más
 * para "Ver todas las notificaciones" (ver todo_completo()).
 */
function grupos_operativos(array &$grupos, int $limite): void
{
    // ================= TICKETS =================
    // Solo los que todavía no se han atendido. Uno que ya se puso "en
    // proceso" ya fue visto y asignado, así que dejar de notificar sobre él
    // — si no, reaparece a cada rato aunque ya se esté trabajando en él.
    $tickets = db()->query("
        SELECT folio, titulo, prioridad, estado, reportado_por, creado_en
        FROM tickets
        WHERE estado = 'abierto'
        ORDER BY FIELD(prioridad, 'critica', 'alta', 'media', 'baja'), creado_en ASC
        LIMIT {$limite}
    ")->fetchAll();

    $totalAbiertos = (int) db()->query(
        "SELECT COUNT(*) FROM tickets WHERE estado = 'abierto'"
    )->fetchColumn();
    $totalEnProceso = (int) db()->query(
        "SELECT COUNT(*) FROM tickets WHERE estado = 'en_proceso'"
    )->fetchColumn();

    if ($totalAbiertos > 0) {
        $grupos[] = [
            'clave'    => 'tickets',
            'titulo'   => 'Tickets pendientes',
            'icono'    => 'fa-headset',
            'urgencia' => 'alta',
            'resumen'  => "{$totalAbiertos} sin atender" . ($totalEnProceso > 0 ? " · {$totalEnProceso} en proceso" : ''),
            'enlace'   => 'CreacionTickets/',
            'items'    => array_map(static fn(array $t): array => [
                'id'     => "tickets:{$t['folio']}",
                'texto'  => "{$t['folio']} — {$t['titulo']}",
                'detalle' => 'Sin atender'
                           . ($t['prioridad'] ? " · prioridad {$t['prioridad']}" : '')
                           . " · reportó {$t['reportado_por']}",
                'nivel'  => 'alta',
            ], $tickets),
        ];
    }

    // ================= SALA DE REUNIONES: PENDIENTES =================
    $salaPendientes = db()->query("
        SELECT folio, solicitante_nombre, fecha, hora_inicio, hora_fin, motivo, creado_en
        FROM sala_reservas
        WHERE estado = 'pendiente'
        ORDER BY fecha, hora_inicio
        LIMIT {$limite}
    ")->fetchAll();

    if ($salaPendientes !== []) {
        $grupos[] = [
            'clave'    => 'sala_reuniones',
            'titulo'   => 'Sala de reuniones por resolver',
            'icono'    => 'fa-door-open',
            'urgencia' => 'media',
            'resumen'  => count($salaPendientes) . ' pendiente(s) de aprobar o rechazar',
            'enlace'   => 'SalaReuniones/',
            'items'    => array_map(static function (array $s): array {
                $dias = dias_hasta($s['fecha']);
                return [
                    'id'      => "sala:{$s['folio']}",
                    'texto'   => "{$s['folio']} — {$s['solicitante_nombre']}",
                    'detalle' => date('d/m', strtotime($s['fecha'])) . ' de '
                               . substr($s['hora_inicio'], 0, 5) . ' a ' . substr($s['hora_fin'], 0, 5)
                               . " · {$s['motivo']}",
                    'nivel'   => $dias <= 1 ? 'alta' : 'media',
                ];
            }, $salaPendientes),
        ];
    }

    // ================= MANTENIMIENTO: INVENTARIO DE OFICINA =================
    $equipos = db()->query("
        SELECT id, nombre, tipo, ultimo_mantenimiento, creado_en
        FROM equipo_oficina
        WHERE estado = 'activo' AND requiere_mantenimiento = 1
    ")->fetchAll();

    $vencidosEq = [];
    $porVencerEq = [];
    foreach ($equipos as $eq) {
        $base = $eq['ultimo_mantenimiento'] ?: substr($eq['creado_en'], 0, 10);
        $proximo = date('Y-m-d', strtotime($base . " + " . MESES_MANTENIMIENTO_OFICINA . " months"));
        $dias = dias_hasta($proximo);
        $fila = ['id' => (int) $eq['id'], 'texto' => "{$eq['tipo']}: {$eq['nombre']}", 'dias' => $dias];
        if ($dias <= 0) {
            $vencidosEq[] = $fila;
        } elseif ($dias <= DIAS_AVISO_MANTENIMIENTO_OFICINA) {
            $porVencerEq[] = $fila;
        }
    }

    if ($vencidosEq !== [] || $porVencerEq !== []) {
        $items = array_merge(
            array_map(static fn(array $f): array => [
                'id' => "mant:{$f['id']}",
                'texto' => $f['texto'],
                'detalle' => $f['dias'] === 0 ? 'Vence hoy' : ('Vencido hace ' . abs($f['dias']) . ' día(s)'),
                'nivel' => 'alta',
            ], $vencidosEq),
            array_map(static fn(array $f): array => [
                'id' => "mant:{$f['id']}",
                'texto' => $f['texto'],
                'detalle' => "Vence en {$f['dias']} día(s)",
                'nivel' => 'media',
            ], $porVencerEq)
        );
        $grupos[] = [
            'clave'    => 'mantenimiento_oficina',
            'titulo'   => 'Mantenimiento de equipo de oficina',
            'icono'    => 'fa-screwdriver-wrench',
            'urgencia' => $vencidosEq !== [] ? 'alta' : 'media',
            'resumen'  => count($vencidosEq) . ' vencido(s) · ' . count($porVencerEq) . ' por vencer',
            'enlace'   => 'InventarioOficina/',
            'items'    => array_slice($items, 0, $limite),
        ];
    }

    // ================= SONDEO: EQUIPO DE FUNCIONARIOS =================
    $personasConEquipo = db()->query("
        SELECT p.id, p.nombre, MIN(e.creado_en) AS primer_equipo
        FROM equipo_funcionario e
        INNER JOIN personas p ON p.id = e.persona_id
        WHERE e.estado = 'activo'
        GROUP BY p.id, p.nombre
    ")->fetchAll();

    $ultimoSondeo = [];
    foreach (db()->query('SELECT persona_id, MAX(fecha) AS f FROM equipo_funcionario_sondeos GROUP BY persona_id')->fetchAll() as $s) {
        $ultimoSondeo[(int) $s['persona_id']] = $s['f'];
    }

    $vencidosSondeo = [];
    $porVencerSondeo = [];
    foreach ($personasConEquipo as $p) {
        $base = $ultimoSondeo[(int) $p['id']] ?? substr($p['primer_equipo'], 0, 10);
        $proximo = date('Y-m-d', strtotime($base . " + " . MESES_SONDEO_FUNCIONARIO . " months"));
        $dias = dias_hasta($proximo);
        $fila = ['id' => (int) $p['id'], 'texto' => $p['nombre'], 'dias' => $dias];
        if ($dias <= 0) {
            $vencidosSondeo[] = $fila;
        } elseif ($dias <= DIAS_AVISO_SONDEO_FUNCIONARIO) {
            $porVencerSondeo[] = $fila;
        }
    }

    if ($vencidosSondeo !== [] || $porVencerSondeo !== []) {
        $items = array_merge(
            array_map(static fn(array $f): array => [
                'id' => "sondeo:{$f['id']}",
                'texto' => $f['texto'],
                'detalle' => $f['dias'] === 0 ? 'Sondeo vence hoy' : ('Sondeo vencido hace ' . abs($f['dias']) . ' día(s)'),
                'nivel' => 'alta',
            ], $vencidosSondeo),
            array_map(static fn(array $f): array => [
                'id' => "sondeo:{$f['id']}",
                'texto' => $f['texto'],
                'detalle' => "Sondeo en {$f['dias']} día(s)",
                'nivel' => 'media',
            ], $porVencerSondeo)
        );
        $grupos[] = [
            'clave'    => 'sondeo_funcionarios',
            'titulo'   => 'Sondeo de equipo de funcionarios',
            'icono'    => 'fa-clipboard-check',
            'urgencia' => $vencidosSondeo !== [] ? 'alta' : 'media',
            'resumen'  => count($vencidosSondeo) . ' vencido(s) · ' . count($porVencerSondeo) . ' por vencer',
            'enlace'   => 'EquipoFuncionarios/',
            'items'    => array_slice($items, 0, $limite),
        ];
    }

    // ================= CUENTAS BLOQUEADAS =================
    $bloqueadas = db()->query("
        SELECT u.usuario, COALESCE(p.nombre, u.usuario) AS nombre, u.bloqueado_hasta
        FROM usuarios u
        LEFT JOIN personas p ON p.id = u.persona_id
        WHERE u.bloqueado_hasta IS NOT NULL AND u.bloqueado_hasta > NOW()
        ORDER BY u.bloqueado_hasta DESC
        LIMIT {$limite}
    ")->fetchAll();

    if ($bloqueadas !== []) {
        $grupos[] = [
            'clave'    => 'cuentas_bloqueadas',
            'titulo'   => 'Cuentas bloqueadas por intentos fallidos',
            'icono'    => 'fa-lock',
            'urgencia' => 'media',
            'resumen'  => count($bloqueadas) . ' cuenta(s) bloqueada(s) ahora mismo',
            'enlace'   => 'Usuarios/',
            'items'    => array_map(static fn(array $b): array => [
                'id'      => "bloq:{$b['usuario']}",
                'texto'   => $b['nombre'] . ' (' . $b['usuario'] . ')',
                'detalle' => 'Bloqueada hasta las ' . date('H:i', strtotime($b['bloqueado_hasta'])),
                'nivel'   => 'baja',
            ], $bloqueadas),
        ];
    }
}

/**
 * Lo que le importa a todo el mundo: cronograma, anuncios por fecha y
 * cambios recientes de horarios/situaciones. Mismo $limite que
 * grupos_operativos().
 */
function grupos_situaciones_horarios(array &$grupos, int $limite): void
{
    // ================= AVISOS DEL SISTEMA =================
    // Los que se publican desde Anuncios (con o sin correo — ver
    // api/anuncios.php). "Ver todas" lleva al historial completo, paginado.
    $avisos = db()->query("
        SELECT id, titulo, mensaje, nivel, creado_en
        FROM anuncios_sistema
        WHERE creado_en >= DATE_SUB(NOW(), INTERVAL " . DIAS_CAMBIOS_RECIENTES . " DAY)
        ORDER BY creado_en DESC
        LIMIT {$limite}
    ")->fetchAll();

    if ($avisos !== []) {
        $items = array_map(static fn(array $a): array => [
            'id'      => "aviso:{$a['id']}",
            'texto'   => $a['titulo'],
            'detalle' => mb_strimwidth(str_replace(["\r\n", "\n"], ' ', $a['mensaje']), 0, 90, '…'),
            'nivel'   => $a['nivel'],
        ], $avisos);

        $tieneAlta = in_array('alta', array_column($items, 'nivel'), true);

        $grupos[] = [
            'clave'    => 'avisos_sistema',
            'titulo'   => 'Avisos del sistema',
            'icono'    => 'fa-bullhorn',
            'urgencia' => $tieneAlta ? 'alta' : 'media',
            'resumen'  => count($items) . ' en los últimos ' . DIAS_CAMBIOS_RECIENTES . ' días',
            'enlace'   => 'Notificaciones/',
            'items'    => $items,
        ];
    }

    // ================= CRONOGRAMA: HITOS PRÓXIMOS =================
    $conv = convocatoria_actual();

    if ($conv !== null) {
        $hitos = db()->prepare("
            SELECT id, titulo, fecha_inicio, fecha_fin
            FROM hitos_proceso
            WHERE convocatoria_id = ?
              AND COALESCE(fecha_fin, fecha_inicio) >= CURDATE()
              AND fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
            ORDER BY fecha_inicio
            LIMIT {$limite}
        ");
        $hitos->execute([$conv]);
        $hitos = $hitos->fetchAll();

        if ($hitos !== []) {
            $items = array_map(static function (array $h): array {
                $dias = dias_hasta($h['fecha_inicio']);
                $enCurso = $h['fecha_fin'] !== null && $h['fecha_inicio'] <= date('Y-m-d') && $h['fecha_fin'] >= date('Y-m-d');

                if ($enCurso) {
                    $detalle = 'En curso hasta el ' . date('d/m', strtotime($h['fecha_fin']));
                    $nivel = 'alta';
                } elseif ($dias <= 0) {
                    $detalle = 'Es hoy';
                    $nivel = 'alta';
                } else {
                    $detalle = "En {$dias} día(s) · " . date('d/m', strtotime($h['fecha_inicio']));
                    $nivel = $dias <= 3 ? 'alta' : 'media';
                }

                return ['id' => "hito:{$h['id']}", 'texto' => $h['titulo'], 'detalle' => $detalle, 'nivel' => $nivel];
            }, $hitos);

            $tieneAlta = in_array('alta', array_column($items, 'nivel'), true);

            $grupos[] = [
                'clave'    => 'cronograma',
                'titulo'   => 'Próximos hitos del proceso',
                'icono'    => 'fa-calendar-days',
                'urgencia' => $tieneAlta ? 'alta' : 'media',
                'resumen'  => count($items) . ' en las próximas dos semanas',
                'enlace'   => 'Cronograma/',
                'items'    => $items,
            ];
        }
    }

    // ================= ANUNCIOS POR FECHA PRÓXIMOS =================
    $anuncios = db()->query("
        SELECT id, titulo, fecha_inicio, fecha_fin
        FROM situaciones_anuncios
        WHERE COALESCE(fecha_fin, fecha_inicio) >= CURDATE()
          AND fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ORDER BY fecha_inicio
        LIMIT {$limite}
    ")->fetchAll();

    if ($anuncios !== []) {
        $items = array_map(static function (array $h): array {
            $dias = dias_hasta($h['fecha_inicio']);
            $enCurso = $h['fecha_fin'] !== null && $h['fecha_inicio'] <= date('Y-m-d') && $h['fecha_fin'] >= date('Y-m-d');

            if ($enCurso) {
                $detalle = 'En curso hasta el ' . date('d/m', strtotime($h['fecha_fin']));
                $nivel = 'alta';
            } elseif ($dias <= 0) {
                $detalle = 'Es hoy';
                $nivel = 'alta';
            } else {
                $detalle = "En {$dias} día(s) · " . date('d/m', strtotime($h['fecha_inicio']));
                $nivel = $dias <= 3 ? 'alta' : 'media';
            }

            return ['id' => "anuncio:{$h['id']}", 'texto' => $h['titulo'], 'detalle' => $detalle, 'nivel' => $nivel];
        }, $anuncios);

        $tieneAlta = in_array('alta', array_column($items, 'nivel'), true);

        $grupos[] = [
            'clave'    => 'situaciones_anuncios',
            'titulo'   => 'Próximos anuncios por fecha',
            'icono'    => 'fa-calendar-days',
            'urgencia' => $tieneAlta ? 'alta' : 'media',
            'resumen'  => count($items) . ' en las próximas dos semanas',
            'enlace'   => 'Situaciones/',
            'items'    => $items,
        ];
    }

    // ================= SITUACIONES ESPECIALES RECIÉN AGREGADAS =================
    // Reemplaza el correo que se mandaba antes al crear una: ahora se avisa acá.
    $situacionesNuevas = db()->query("
        SELECT id, nombre, tipo, descripcion, fecha_inicio, fecha_fin, creado_en
        FROM situaciones_especiales
        WHERE creado_en >= DATE_SUB(NOW(), INTERVAL " . DIAS_CAMBIOS_RECIENTES . " DAY)
        ORDER BY creado_en DESC
        LIMIT {$limite}
    ")->fetchAll();

    if ($situacionesNuevas !== []) {
        $grupos[] = [
            'clave'    => 'situaciones_cambios',
            'titulo'   => 'Situaciones especiales recientes',
            'icono'    => 'fa-calendar-check',
            'urgencia' => 'baja',
            'resumen'  => count($situacionesNuevas) . ' en los últimos ' . DIAS_CAMBIOS_RECIENTES . ' días',
            'enlace'   => 'Situaciones/',
            'items'    => array_map(static function (array $s): array {
                $etiqueta = ETIQUETAS_TIPO_SITUACION_NOTIF[$s['tipo']] ?? $s['tipo'];
                $rango = ($s['fecha_fin'] && $s['fecha_fin'] !== $s['fecha_inicio'])
                    ? date('d/m', strtotime($s['fecha_inicio'])) . ' – ' . date('d/m', strtotime($s['fecha_fin']))
                    : date('d/m', strtotime($s['fecha_inicio']));
                return [
                    'id'      => "situ:{$s['id']}",
                    'texto'   => "{$s['nombre']} — {$etiqueta}",
                    'detalle' => $rango,
                    'nivel'   => 'baja',
                ];
            }, $situacionesNuevas),
        ];
    }

    // ================= HORARIOS RECIÉN MODIFICADOS =================
    $horariosNuevos = db()->query("
        SELECT h.usuario_id, p.nombre, h.dia_semana, MAX(h.actualizado_en) AS ultimo_cambio
        FROM horarios_franjas h
        INNER JOIN usuarios u ON u.id = h.usuario_id
        INNER JOIN personas p ON p.id = u.persona_id
        GROUP BY h.usuario_id, h.dia_semana, p.nombre
        HAVING ultimo_cambio >= DATE_SUB(NOW(), INTERVAL " . DIAS_CAMBIOS_RECIENTES . " DAY)
        ORDER BY ultimo_cambio DESC
        LIMIT {$limite}
    ")->fetchAll();

    if ($horariosNuevos !== []) {
        $diasLegibles = [
            'lunes' => 'lunes', 'martes' => 'martes', 'miercoles' => 'miércoles',
            'jueves' => 'jueves', 'viernes' => 'viernes',
        ];
        $grupos[] = [
            'clave'    => 'horarios_cambios',
            'titulo'   => 'Horarios recién modificados',
            'icono'    => 'fa-clock',
            'urgencia' => 'baja',
            'resumen'  => count($horariosNuevos) . ' en los últimos ' . DIAS_CAMBIOS_RECIENTES . ' días',
            'enlace'   => 'Horarios/',
            'items'    => array_map(static fn(array $h): array => [
                'id'      => "horario:{$h['usuario_id']}:{$h['dia_semana']}",
                'texto'   => $h['nombre'],
                'detalle' => 'Cambió su ' . ($diasLegibles[$h['dia_semana']] ?? $h['dia_semana']),
                'nivel'   => 'baja',
            ], $horariosNuevos),
        ];
    }
}
