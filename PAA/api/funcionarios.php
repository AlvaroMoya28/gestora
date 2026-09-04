<?php
/**
 * Endpoint del directorio de funcionarios PAA.
 *
 * GET  → lista personas activas con teléfonos
 * POST → acciones de mantenimiento:
 *         - accion=crear
 *         - accion=eliminar (borrado lógico)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/exportar_excel.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("directorio");


exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && param('accion') === '') {
        responder_json(listar_funcionarios());
    }

    $accion = mb_strtolower(param('accion'), 'UTF-8');

    if ($accion === 'exportar') {
        exportar_funcionarios();
    }

    if ($accion === 'crear') {
        crear_funcionario();
    }

    if ($accion === 'eliminar') {
        eliminar_funcionario();
    }

    responder_json(['success' => false, 'error' => 'Acción no soportada'], 400);
} catch (Throwable $e) {
    fallo('coordinadores', $e, 'No se pudo procesar la solicitud del directorio');
}

function listar_funcionarios(): array
{
    $sql = "
        SELECT
            a.nombre  AS area,
            a.orden   AS area_orden,
            c.id      AS id,
            c.nombre  AS nombre,
            c.correo  AS correo,
            c.extension AS extension,
            GROUP_CONCAT(t.telefono ORDER BY t.id SEPARATOR '|') AS telefonos
        FROM personas c
        INNER JOIN areas a ON a.id = c.area_id
        LEFT  JOIN persona_telefonos t ON t.persona_id = c.id
        WHERE c.activo = 1 AND c.oculto = 0
        GROUP BY c.id, a.nombre, a.orden, c.nombre, c.correo, c.extension
        ORDER BY a.orden, c.nombre
    ";

    $filas = db()->query($sql)->fetchAll();

    $personas = array_map(static function (array $f): array {
        return [
            'id'        => (int) $f['id'],
            'area'      => $f['area'],
            'nombre'    => $f['nombre'],
            'correo'    => $f['correo'] ?? '',
            'extension' => $f['extension'] ?? '',
            'telefonos' => $f['telefonos'] !== null && $f['telefonos'] !== ''
                ? explode('|', $f['telefonos'])
                : [],
        ];
    }, $filas);

    $areas = db()->query('SELECT nombre FROM areas ORDER BY orden, nombre')->fetchAll(PDO::FETCH_COLUMN);

    return [
        'success'      => true,
        'personas'     => $personas,
        'areas'        => $areas,
        'total'        => count($personas),
        'actualizado'  => date('c'),
    ];
}

function exportar_funcionarios(): void
{
    $sql = "
        SELECT
            a.nombre AS area, a.orden AS area_orden,
            c.nombre, c.correo, c.extension,
            GROUP_CONCAT(t.telefono ORDER BY t.id SEPARATOR ', ') AS telefonos
        FROM personas c
        INNER JOIN areas a ON a.id = c.area_id
        LEFT  JOIN persona_telefonos t ON t.persona_id = c.id
        WHERE c.activo = 1 AND c.oculto = 0
        GROUP BY c.id, a.nombre, a.orden, c.nombre, c.correo, c.extension
        ORDER BY a.orden, c.nombre
    ";

    $filas = array_map(static fn(array $f): array => [
        'Área'      => $f['area'],
        'Nombre'    => $f['nombre'],
        'Correo'    => $f['correo'] ?? '',
        'Extensión' => $f['extension'] ?? '',
        'Teléfono'  => $f['telefonos'] ?? '',
    ], db()->query($sql)->fetchAll());

    exportar_excel_html('funcionarios_paa', ['Área', 'Nombre', 'Correo', 'Extensión', 'Teléfono'], $filas);
}

function crear_funcionario(): void
{
    $nombre = param('nombre');
    $area = param('area');
    $correo = param('correo');
    $extension = param('extension');

    if ($nombre === '' || $area === '' || $correo === '' || $extension === '') {
        responder_json([
            'success' => false,
            'error' => 'Faltan datos obligatorios: nombre, área, correo y extensión',
        ], 400);
    }

    if (mb_strlen($nombre, 'UTF-8') > 150 || mb_strlen($area, 'UTF-8') > 100 || mb_strlen($correo, 'UTF-8') > 150) {
        responder_json(['success' => false, 'error' => 'Uno o más campos exceden el largo permitido'], 400);
    }

    if (mb_strlen($extension, 'UTF-8') > 20) {
        responder_json(['success' => false, 'error' => 'La extensión es demasiado larga'], 400);
    }

    if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        responder_json(['success' => false, 'error' => 'El correo no tiene un formato válido'], 400);
    }

    $telefonos = leer_telefonos_entrada();
    if (count($telefonos) === 0) {
        responder_json(['success' => false, 'error' => 'Debe indicar al menos un teléfono'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $areaId = id_area($area);

        $stmtPersona = $pdo->prepare(
            'INSERT INTO personas (area_id, nombre, correo, extension, activo, puede_retirar_activos)
             VALUES (?, ?, ?, ?, 1, 1)'
        );
        $stmtPersona->execute([$areaId, $nombre, $correo, $extension]);

        $personaId = (int) $pdo->lastInsertId();

        $stmtTelefono = $pdo->prepare('INSERT INTO persona_telefonos (persona_id, telefono) VALUES (?, ?)');
        foreach ($telefonos as $telefono) {
            $stmtTelefono->execute([$personaId, $telefono]);
        }

        $pdo->commit();

        responder_json([
            'success' => true,
            'mensaje' => 'Funcionario creado correctamente',
            'id' => $personaId,
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (($e->errorInfo[0] ?? '') === '23000') {
            responder_json([
                'success' => false,
                'error' => 'Ya existe un funcionario con ese correo',
            ], 409);
        }

        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function eliminar_funcionario(): void
{
    $id = param_int('id');
    if ($id <= 0) {
        responder_json(['success' => false, 'error' => 'ID inválido'], 400);
    }

    $stmt = db()->prepare('UPDATE personas SET activo = 0 WHERE id = ? AND activo = 1');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'No se encontró el funcionario activo solicitado'], 404);
    }

    responder_json([
        'success' => true,
        'mensaje' => 'Funcionario eliminado del directorio',
    ]);
}

function id_area(string $nombre): int
{
    $nombre = trim($nombre);
    $stmt = db()->prepare('SELECT id FROM areas WHERE nombre = ? LIMIT 1');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();

    if ($id !== false) {
        return (int) $id;
    }

    $orden = (int) db()->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM areas')->fetchColumn();
    $crear = db()->prepare('INSERT INTO areas (nombre, orden) VALUES (?, ?)');
    $crear->execute([$nombre, $orden]);

    return (int) db()->lastInsertId();
}

function leer_telefonos_entrada(): array
{
    $datos = entrada();
    $telefonos = [];

    if (isset($datos['telefonos']) && is_array($datos['telefonos'])) {
        $telefonos = $datos['telefonos'];
    } else {
        $crudo = param('telefonos');
        if ($crudo !== '') {
            $telefonos = preg_split('/[\n,;]+/', $crudo) ?: [];
        }
    }

    $limpios = [];
    foreach ($telefonos as $telefono) {
        $valor = trim((string) $telefono);
        if ($valor === '') {
            continue;
        }
        if (mb_strlen($valor, 'UTF-8') > 20) {
            responder_json(['success' => false, 'error' => 'Uno de los teléfonos supera el largo permitido'], 400);
        }
        $limpios[] = $valor;
    }

    return array_values(array_unique($limpios));
}
