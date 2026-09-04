<?php
/**
 * Endpoint del módulo Cronograma.
 *
 * Cualquiera con acceso al módulo puede consultar los hitos (administración,
 * desarrollo y funcionarios); solo coordinación académica y administrativa
 * (Karol y Jenny) pueden crearlos, editarlos o borrarlos — igual que en
 * pantalla —, además de desarrollo, que siempre tiene acceso completo al
 * sistema como respaldo.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/sedes_datos.php'; // convocatoria_exigida()
require_once __DIR__ . '/../includes/exportar_excel.php';

$yo = exigir_modulo_api('cronograma');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// Coordinación académica (Karol Jiménez) y administrativa (Jenny Bolaños):
// las dos personas responsables del proceso, no todo el rol administrador.
const USUARIOS_EDITORES_CRONOGRAMA = [21, 27];

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':
            cronograma_listar();
            break;

        case 'listarconvocatorias':
            cronograma_convocatorias();
            break;

        case 'exportar':
            cronograma_exportar();
            break;

        case 'crear':
            cronograma_exigir_editor($yo);
            cronograma_crear($yo);
            break;

        case 'actualizar':
            cronograma_exigir_editor($yo);
            cronograma_actualizar();
            break;

        case 'eliminar':
            cronograma_exigir_editor($yo);
            cronograma_eliminar();
            break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('cronograma', $e, 'No se pudo procesar el cronograma');
}

function cronograma_puede_editar(array $yo): bool
{
    return $yo['rol'] === ROL_DESARROLLADOR || in_array($yo['id'], USUARIOS_EDITORES_CRONOGRAMA, true);
}

function cronograma_exigir_editor(array $yo): void
{
    if (!cronograma_puede_editar($yo)) {
        responder_json(['success' => false, 'error' => 'Solo coordinación académica y administrativa pueden editar el cronograma'], 403);
    }
}

/** Convocatoria a mostrar: la que venga por parámetro o, si no, la activa. */
function cronograma_convocatoria(): int
{
    $id = param_int('convocatoria_id');
    return $id > 0 ? $id : convocatoria_exigida();
}

function cronograma_listar(): void
{
    $conv = cronograma_convocatoria();

    $stmt = db()->prepare("
        SELECT id, titulo, descripcion, fecha_inicio, fecha_fin, color
        FROM hitos_proceso
        WHERE convocatoria_id = ?
        ORDER BY fecha_inicio, id
    ");
    $stmt->execute([$conv]);
    $filas = $stmt->fetchAll();

    responder_json([
        'success'        => true,
        'convocatoriaId' => $conv,
        'hitos'          => array_map(static fn (array $h): array => [
            'id'          => (int) $h['id'],
            'titulo'      => $h['titulo'],
            'descripcion' => $h['descripcion'] ?? '',
            'fechaInicio' => $h['fecha_inicio'],
            'fechaFin'    => $h['fecha_fin'],
            'color'       => $h['color'],
        ], $filas),
    ]);
}

function cronograma_exportar(): void
{
    $conv = cronograma_convocatoria();

    $stmt = db()->prepare("
        SELECT titulo, descripcion, fecha_inicio, fecha_fin
        FROM hitos_proceso
        WHERE convocatoria_id = ?
        ORDER BY fecha_inicio, id
    ");
    $stmt->execute([$conv]);

    $filas = array_map(static fn(array $h): array => [
        'Título'         => $h['titulo'],
        'Fecha de inicio' => $h['fecha_inicio'],
        'Fecha de fin'    => $h['fecha_fin'] ?? '',
        'Descripción'     => $h['descripcion'] ?? '',
    ], $stmt->fetchAll());

    exportar_excel_html('cronograma', ['Título', 'Fecha de inicio', 'Fecha de fin', 'Descripción'], $filas);
}

/** Para el selector de año: qué convocatorias existen y cuál es la activa. */
function cronograma_convocatorias(): void
{
    $filas = db()->query("
        SELECT id, nombre, activa
        FROM convocatorias
        ORDER BY id DESC
    ")->fetchAll();

    responder_json([
        'success'        => true,
        'convocatorias'  => array_map(static fn (array $c): array => [
            'id'     => (int) $c['id'],
            'nombre' => $c['nombre'],
            'activa' => (bool) $c['activa'],
        ], $filas),
    ]);
}

function cronograma_validar_fechas(string $inicio, string $fin): void
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

function cronograma_crear(array $yo): void
{
    exigir(['titulo', 'fecha_inicio']);

    $conv        = cronograma_convocatoria();
    $titulo      = param('titulo');
    $descripcion = param('descripcion');
    $fechaInicio = param('fecha_inicio');
    $fechaFin    = param('fecha_fin');
    $color       = param('color', 'azul');

    cronograma_validar_fechas($fechaInicio, $fechaFin);

    $stmt = db()->prepare("
        INSERT INTO hitos_proceso
            (convocatoria_id, titulo, descripcion, fecha_inicio, fecha_fin, color, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $conv,
        $titulo,
        $descripcion !== '' ? $descripcion : null,
        $fechaInicio,
        $fechaFin !== '' ? $fechaFin : null,
        $color !== '' ? $color : 'azul',
        $yo['nombre'],
    ]);

    responder_json(['success' => true, 'id' => (int) db()->lastInsertId()]);
}

function cronograma_actualizar(): void
{
    exigir(['id', 'titulo', 'fecha_inicio']);

    $id          = param_int('id');
    $titulo      = param('titulo');
    $descripcion = param('descripcion');
    $fechaInicio = param('fecha_inicio');
    $fechaFin    = param('fecha_fin');
    $color       = param('color', 'azul');

    cronograma_validar_fechas($fechaInicio, $fechaFin);

    $stmt = db()->prepare("
        UPDATE hitos_proceso
        SET titulo = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, color = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $titulo,
        $descripcion !== '' ? $descripcion : null,
        $fechaInicio,
        $fechaFin !== '' ? $fechaFin : null,
        $color !== '' ? $color : 'azul',
        $id,
    ]);

    if ($stmt->rowCount() === 0 && !existe_hito($id)) {
        responder_json(['success' => false, 'error' => 'El hito no existe'], 404);
    }

    responder_json(['success' => true]);
}

function cronograma_eliminar(): void
{
    exigir(['id']);

    $id = param_int('id');

    $stmt = db()->prepare('DELETE FROM hitos_proceso WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'El hito no existe'], 404);
    }

    responder_json(['success' => true]);
}

function existe_hito(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM hitos_proceso WHERE id = ?');
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}
