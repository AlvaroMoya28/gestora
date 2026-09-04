<?php
/**
 * Endpoint del módulo Situaciones Especiales.
 *
 * Dos cosas viven en el mismo calendario:
 *
 *   - Situaciones: de cada persona (vacaciones, incapacidad, etc.). Cualquiera
 *     con acceso ve todas, para saber quién está fuera y cuándo; cada quien
 *     crea, edita y borra las suyas —el dueño sale de la sesión, no de un
 *     parámetro—; administración y desarrollo pueden tocar las de cualquiera.
 *     Al crear una, aparece en el panel de Notificaciones para todo el
 *     personal (api/notificaciones.php) — no se manda correo.
 *
 *   - Anuncios por fecha: los hitos del proceso (antes el módulo Cronograma
 *     aparte, que se retiró por duplicar este calendario). Los ve cualquiera
 *     con acceso; solo administración y desarrollo los crean, editan o borran.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/exportar_excel.php';

$yo = exigir_modulo_api('situaciones');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

const TIPOS_SITUACION = [
    'vacaciones', 'incapacidad', 'reunion', 'cambio_horario',
    'trabajo_remoto', 'cita_medica', 'permiso', 'otro',
];

const ETIQUETAS_TIPO_SITUACION = [
    'vacaciones'     => 'Vacaciones',
    'incapacidad'    => 'Incapacidad',
    'reunion'        => 'Reunión',
    'cambio_horario' => 'Cambio de horario',
    'trabajo_remoto' => 'Trabajo remoto',
    'cita_medica'    => 'Cita médica',
    'permiso'        => 'Permiso',
    'otro'           => 'Otro',
];

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':
            situaciones_listar();
            break;

        case 'crear':
            situaciones_crear($yo);
            break;

        case 'actualizar':
            situaciones_actualizar($yo);
            break;

        case 'eliminar':
            situaciones_eliminar($yo);
            break;

        case 'crearAnuncio':
            anuncios_exigir_admin($yo);
            anuncios_crear($yo);
            break;

        case 'actualizarAnuncio':
            anuncios_exigir_admin($yo);
            anuncios_actualizar();
            break;

        case 'eliminarAnuncio':
            anuncios_exigir_admin($yo);
            anuncios_eliminar();
            break;

        case 'exportar':
            situaciones_exportar();
            break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('situaciones', $e, 'No se pudo procesar la solicitud');
}

// ------------------------------------------------------------------
// Situaciones de cada persona
// ------------------------------------------------------------------

/** ¿Puede esta persona tocar una situación de este dueño? Suya, o es administración/desarrollo. */
function situaciones_puede_editar(array $yo, int $usuarioIdDueno): bool
{
    return $usuarioIdDueno === $yo['id'] || in_array($yo['rol'], ROLES_ADMIN, true);
}

function situaciones_listar(): void
{
    $situaciones = db()->query("
        SELECT id, usuario_id, nombre, tipo, descripcion, fecha_inicio, fecha_fin
        FROM situaciones_especiales
        ORDER BY fecha_inicio, id
    ")->fetchAll();

    $anuncios = db()->query("
        SELECT id, titulo, descripcion, fecha_inicio, fecha_fin
        FROM situaciones_anuncios
        ORDER BY fecha_inicio, id
    ")->fetchAll();

    responder_json([
        'success'      => true,
        'situaciones'  => array_map(static fn (array $s): array => [
            'id'          => (int) $s['id'],
            'usuarioId'   => (int) $s['usuario_id'],
            'nombre'      => $s['nombre'],
            'tipo'        => $s['tipo'],
            'descripcion' => $s['descripcion'] ?? '',
            'fechaInicio' => $s['fecha_inicio'],
            'fechaFin'    => $s['fecha_fin'],
        ], $situaciones),
        'anuncios'     => array_map(static fn (array $a): array => [
            'id'          => (int) $a['id'],
            'titulo'      => $a['titulo'],
            'descripcion' => $a['descripcion'] ?? '',
            'fechaInicio' => $a['fecha_inicio'],
            'fechaFin'    => $a['fecha_fin'],
        ], $anuncios),
    ]);
}

function situaciones_exportar(): void
{
    $situaciones = db()->query("
        SELECT nombre, tipo, descripcion, fecha_inicio, fecha_fin
        FROM situaciones_especiales
        ORDER BY fecha_inicio, id
    ")->fetchAll();

    $filas = array_map(static fn(array $s): array => [
        'Funcionario'    => $s['nombre'],
        'Tipo'           => ETIQUETAS_TIPO_SITUACION[$s['tipo']] ?? $s['tipo'],
        'Fecha de inicio' => $s['fecha_inicio'],
        'Fecha de fin'    => $s['fecha_fin'] ?? '',
        'Descripción'     => $s['descripcion'] ?? '',
    ], $situaciones);

    exportar_excel_html('situaciones_especiales', ['Funcionario', 'Tipo', 'Fecha de inicio', 'Fecha de fin', 'Descripción'], $filas);
}

function situaciones_validar(string $tipo, string $inicio, string $fin): void
{
    if (!in_array($tipo, TIPOS_SITUACION, true)) {
        responder_json(['success' => false, 'error' => 'Tipo de situación inválido'], 400);
    }
    validar_rango_fechas($inicio, $fin);
}

function validar_rango_fechas(string $inicio, string $fin): void
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
        responder_json(['success' => false, 'error' => 'La fecha de inicio no es válida'], 400);
    }
    if ($fin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
        responder_json(['success' => false, 'error' => 'La fecha de fin no es válida'], 400);
    }
    if ($fin !== '' && $fin < $inicio) {
        responder_json(['success' => false, 'error' => 'La fecha de fin no puede ser anterior a la de inicio'], 400);
    }
}

function situaciones_crear(array $yo): void
{
    exigir(['tipo', 'fecha_inicio']);

    $tipo        = param('tipo');
    $descripcion = param('descripcion');
    $fechaInicio = param('fecha_inicio');
    $fechaFin    = param('fecha_fin');

    situaciones_validar($tipo, $fechaInicio, $fechaFin);

    $stmt = db()->prepare("
        INSERT INTO situaciones_especiales
            (usuario_id, nombre, tipo, descripcion, fecha_inicio, fecha_fin)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $yo['id'],
        $yo['nombre'],
        $tipo,
        $descripcion !== '' ? $descripcion : null,
        $fechaInicio,
        $fechaFin !== '' ? $fechaFin : null,
    ]);

    responder_json(['success' => true, 'id' => (int) db()->lastInsertId()]);
}

function situaciones_actualizar(array $yo): void
{
    exigir(['id', 'tipo', 'fecha_inicio']);

    $id = param_int('id');

    $dueno = situaciones_dueno($id);
    if ($dueno === null) {
        responder_json(['success' => false, 'error' => 'La situación no existe'], 404);
    }
    if (!situaciones_puede_editar($yo, $dueno)) {
        responder_json(['success' => false, 'error' => 'Solo podés editar tus propias situaciones'], 403);
    }

    $tipo        = param('tipo');
    $descripcion = param('descripcion');
    $fechaInicio = param('fecha_inicio');
    $fechaFin    = param('fecha_fin');

    situaciones_validar($tipo, $fechaInicio, $fechaFin);

    $stmt = db()->prepare("
        UPDATE situaciones_especiales
        SET tipo = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $tipo,
        $descripcion !== '' ? $descripcion : null,
        $fechaInicio,
        $fechaFin !== '' ? $fechaFin : null,
        $id,
    ]);

    responder_json(['success' => true]);
}

function situaciones_eliminar(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');

    $dueno = situaciones_dueno($id);
    if ($dueno === null) {
        responder_json(['success' => false, 'error' => 'La situación no existe'], 404);
    }
    if (!situaciones_puede_editar($yo, $dueno)) {
        responder_json(['success' => false, 'error' => 'Solo podés borrar tus propias situaciones'], 403);
    }

    db()->prepare('DELETE FROM situaciones_especiales WHERE id = ?')->execute([$id]);

    responder_json(['success' => true]);
}

function situaciones_dueno(int $id): ?int
{
    $stmt = db()->prepare('SELECT usuario_id FROM situaciones_especiales WHERE id = ?');
    $stmt->execute([$id]);
    $valor = $stmt->fetchColumn();
    return $valor === false ? null : (int) $valor;
}

// ------------------------------------------------------------------
// Anuncios por fecha (antes, el módulo Cronograma)
// ------------------------------------------------------------------

function anuncios_exigir_admin(array $yo): void
{
    if (!in_array($yo['rol'], ROLES_ADMIN, true)) {
        responder_json(['success' => false, 'error' => 'Solo administración puede editar los anuncios'], 403);
    }
}

function anuncios_crear(array $yo): void
{
    exigir(['titulo', 'fecha_inicio']);

    $titulo      = param('titulo');
    $descripcion = param('descripcion');
    $fechaInicio = param('fecha_inicio');
    $fechaFin    = param('fecha_fin');

    validar_rango_fechas($fechaInicio, $fechaFin);

    $stmt = db()->prepare("
        INSERT INTO situaciones_anuncios (titulo, descripcion, fecha_inicio, fecha_fin, creado_por)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $titulo,
        $descripcion !== '' ? $descripcion : null,
        $fechaInicio,
        $fechaFin !== '' ? $fechaFin : null,
        $yo['nombre'],
    ]);

    responder_json(['success' => true, 'id' => (int) db()->lastInsertId()]);
}

function anuncios_actualizar(): void
{
    exigir(['id', 'titulo', 'fecha_inicio']);

    $id          = param_int('id');
    $titulo      = param('titulo');
    $descripcion = param('descripcion');
    $fechaInicio = param('fecha_inicio');
    $fechaFin    = param('fecha_fin');

    validar_rango_fechas($fechaInicio, $fechaFin);

    $stmt = db()->prepare("
        UPDATE situaciones_anuncios
        SET titulo = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $titulo,
        $descripcion !== '' ? $descripcion : null,
        $fechaInicio,
        $fechaFin !== '' ? $fechaFin : null,
        $id,
    ]);

    if ($stmt->rowCount() === 0 && !existe_anuncio($id)) {
        responder_json(['success' => false, 'error' => 'El anuncio no existe'], 404);
    }

    responder_json(['success' => true]);
}

function anuncios_eliminar(): void
{
    exigir(['id']);

    $id = param_int('id');

    $stmt = db()->prepare('DELETE FROM situaciones_anuncios WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'El anuncio no existe'], 404);
    }

    responder_json(['success' => true]);
}

function existe_anuncio(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM situaciones_anuncios WHERE id = ?');
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}
