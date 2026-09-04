<?php
/**
 * Endpoint del módulo Horarios.
 *
 * El horario es semanal RECURRENTE (lunes a viernes), no atado a una fecha:
 * es "la semana típica" de la persona. Cada quien administra el propio —el
 * dueño sale de la sesión—; administración y desarrollo pueden corregir
 * cualquiera. Consultar el de otra persona para una semana concreta (desde
 * Funcionarios PAA) le superpone encima las situaciones especiales de esos
 * días puntuales.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/exportar_excel.php';

$yo = exigir_modulo_api('horarios');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

const DIAS_SEMANA = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':
            horarios_listar($yo);
            break;

        case 'guardarDia':
            horarios_guardar_dia($yo);
            break;

        case 'verHorario':
            horarios_ver_de_persona();
            break;

        case 'personas':
            horarios_listar_personas($yo);
            break;

        case 'exportar':
            horarios_exportar();
            break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('horarios', $e, 'No se pudo procesar la solicitud');
}

/** Lunes a viernes de la semana que contiene $fechaRef. */
function fechas_semana(string $fechaRef): array
{
    $t = strtotime($fechaRef);
    if ($t === false) {
        $t = time();
    }
    $diaIso = (int) date('N', $t); // 1 = lunes ... 7 = domingo
    $lunes = strtotime('-' . ($diaIso - 1) . ' days', $t);

    $resultado = [];
    foreach (DIAS_SEMANA as $i => $nombre) {
        $resultado[$nombre] = date('Y-m-d', strtotime("+{$i} days", $lunes));
    }
    return $resultado;
}

function horarios_franjas_de(int $usuarioId): array
{
    $stmt = db()->prepare("
        SELECT dia_semana, hora_inicio, hora_fin, modalidad
        FROM horarios_franjas
        WHERE usuario_id = ?
        ORDER BY dia_semana, hora_inicio
    ");
    $stmt->execute([$usuarioId]);

    $porDia = array_fill_keys(DIAS_SEMANA, []);
    foreach ($stmt->fetchAll() as $f) {
        $porDia[$f['dia_semana']][] = [
            'horaInicio' => substr($f['hora_inicio'], 0, 5),
            'horaFin'    => substr($f['hora_fin'], 0, 5),
            'modalidad'  => $f['modalidad'],
        ];
    }
    return $porDia;
}

/** Las situaciones del usuario que caen en esa semana, agrupadas por día. */
function horarios_situaciones_semana(int $usuarioId, array $fechasSemana): array
{
    $desde = reset($fechasSemana);
    $hasta = end($fechasSemana);

    $stmt = db()->prepare("
        SELECT tipo, descripcion, fecha_inicio, fecha_fin
        FROM situaciones_especiales
        WHERE usuario_id = ?
          AND COALESCE(fecha_fin, fecha_inicio) >= ?
          AND fecha_inicio <= ?
    ");
    $stmt->execute([$usuarioId, $desde, $hasta]);
    $situaciones = $stmt->fetchAll();

    $porDia = array_fill_keys(DIAS_SEMANA, []);
    foreach ($fechasSemana as $dia => $fecha) {
        foreach ($situaciones as $s) {
            $fin = $s['fecha_fin'] ?: $s['fecha_inicio'];
            if ($fecha >= $s['fecha_inicio'] && $fecha <= $fin) {
                $porDia[$dia][] = [
                    'tipo'        => $s['tipo'],
                    'descripcion' => $s['descripcion'] ?? '',
                ];
            }
        }
    }
    return $porDia;
}

function horarios_listar(array $yo): void
{
    $fechaRef = param('fecha') ?: date('Y-m-d');
    $fechasSemana = fechas_semana($fechaRef);

    responder_json([
        'success'      => true,
        'dias'         => horarios_franjas_de($yo['id']),
        'situaciones'  => horarios_situaciones_semana($yo['id'], $fechasSemana),
        'fechasSemana' => $fechasSemana,
    ]);
}

/** Reemplaza de una vez todas las franjas de un día: más simple que editar una por una. */
function horarios_guardar_dia(array $yo): void
{
    exigir(['dia_semana']);

    $diaSemana = param('dia_semana');
    if (!in_array($diaSemana, DIAS_SEMANA, true)) {
        responder_json(['success' => false, 'error' => 'Día inválido'], 400);
    }

    $usuarioIdParam = param_int('usuario_id');
    $usuarioId = $usuarioIdParam > 0 ? $usuarioIdParam : $yo['id'];
    if ($usuarioId !== $yo['id'] && !in_array($yo['rol'], ROLES_ADMIN, true)) {
        responder_json(['success' => false, 'error' => 'Solo podés editar tu propio horario'], 403);
    }

    $franjasCrudo = entrada()['franjas'] ?? '[]';
    $franjas = is_array($franjasCrudo) ? $franjasCrudo : json_decode((string) $franjasCrudo, true);
    if (!is_array($franjas)) {
        responder_json(['success' => false, 'error' => 'Franjas inválidas'], 400);
    }

    $limpias = [];
    foreach ($franjas as $f) {
        $inicio = (string) ($f['horaInicio'] ?? '');
        $fin = (string) ($f['horaFin'] ?? '');
        $modalidad = (string) ($f['modalidad'] ?? '');

        if (!preg_match('/^\d{2}:\d{2}$/', $inicio) || !preg_match('/^\d{2}:\d{2}$/', $fin)) {
            responder_json(['success' => false, 'error' => 'Hay una franja con hora inválida'], 400);
        }
        if ($fin <= $inicio) {
            responder_json(['success' => false, 'error' => 'La hora de fin debe ser posterior a la de inicio'], 400);
        }
        if (!in_array($modalidad, ['presencial', 'virtual'], true)) {
            responder_json(['success' => false, 'error' => 'Modalidad inválida'], 400);
        }

        $limpias[] = [$inicio, $fin, $modalidad];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM horarios_franjas WHERE usuario_id = ? AND dia_semana = ?')
            ->execute([$usuarioId, $diaSemana]);

        $ins = $pdo->prepare("
            INSERT INTO horarios_franjas (usuario_id, dia_semana, hora_inicio, hora_fin, modalidad)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($limpias as [$inicio, $fin, $modalidad]) {
            $ins->execute([$usuarioId, $diaSemana, $inicio, $fin, $modalidad]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    responder_json(['success' => true]);
}

const DIAS_SEMANA_LEGIBLES = [
    'lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles',
    'jueves' => 'Jueves', 'viernes' => 'Viernes',
];
const MODALIDADES_LEGIBLES = ['presencial' => 'Presencial', 'virtual' => 'Virtual'];

/** El horario semanal de todo el personal activo, en una sola hoja. */
function horarios_exportar(): void
{
    $stmt = db()->query("
        SELECT p.nombre, h.dia_semana, h.hora_inicio, h.hora_fin, h.modalidad
        FROM horarios_franjas h
        INNER JOIN usuarios u ON u.id = h.usuario_id
        INNER JOIN personas p ON p.id = u.persona_id
        WHERE u.activo = 1
        ORDER BY p.nombre, h.dia_semana, h.hora_inicio
    ");

    $filas = array_map(static fn(array $f): array => [
        'Nombre'      => $f['nombre'],
        'Día'         => DIAS_SEMANA_LEGIBLES[$f['dia_semana']] ?? $f['dia_semana'],
        'Hora inicio' => substr($f['hora_inicio'], 0, 5),
        'Hora fin'    => substr($f['hora_fin'], 0, 5),
        'Modalidad'   => MODALIDADES_LEGIBLES[$f['modalidad']] ?? $f['modalidad'],
    ], $stmt->fetchAll());

    exportar_excel_html('horarios', ['Nombre', 'Día', 'Hora inicio', 'Hora fin', 'Modalidad'], $filas);
}

/** El horario de una persona del directorio (Funcionarios PAA), para una semana dada. */
function horarios_ver_de_persona(): void
{
    exigir(['persona_id']);

    $personaId = param_int('persona_id');
    $fechaRef = param('fecha') ?: date('Y-m-d');

    $stmt = db()->prepare("
        SELECT u.id AS usuario_id, p.nombre
        FROM personas p
        LEFT JOIN usuarios u ON u.persona_id = p.id AND u.activo = 1
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$personaId]);
    $fila = $stmt->fetch();

    if (!$fila) {
        responder_json(['success' => false, 'error' => 'Persona no encontrada'], 404);
    }

    if ($fila['usuario_id'] === null) {
        responder_json([
            'success'     => true,
            'nombre'      => $fila['nombre'],
            'tieneCuenta' => false,
        ]);
    }

    $usuarioId = (int) $fila['usuario_id'];
    $fechasSemana = fechas_semana($fechaRef);

    responder_json([
        'success'      => true,
        'nombre'       => $fila['nombre'],
        'tieneCuenta'  => true,
        'usuarioId'    => $usuarioId,
        'dias'         => horarios_franjas_de($usuarioId),
        'situaciones'  => horarios_situaciones_semana($usuarioId, $fechasSemana),
        'fechasSemana' => $fechasSemana,
    ]);
}

/**
 * El personal con cuenta activa, para que administración elija a quién
 * verle/editarle el horario. Sin cuenta no hay dónde guardar franjas, así
 * que no tiene sentido ofrecerlo en el selector.
 */
function horarios_listar_personas(array $yo): void
{
    if (!in_array($yo['rol'], ROLES_ADMIN, true)) {
        responder_json(['success' => false, 'error' => 'No tenés permiso para esto'], 403);
    }

    $stmt = db()->query("
        SELECT p.id AS persona_id, p.nombre
        FROM personas p
        INNER JOIN usuarios u ON u.persona_id = p.id
        WHERE u.activo = 1
        ORDER BY p.nombre
    ");

    responder_json(['success' => true, 'personas' => $stmt->fetchAll()]);
}
