<?php
/**
 * Endpoint del panel de Administración (centro de herramientas).
 *
 * Reemplaza lo que Administracion.html hacía en el navegador: descargar el CSV
 * de tulas, el CSV de coordinadores y hacer ping a dos Apps Script para
 * mostrar el estado del sistema. Eran cuatro peticiones a servicios externos
 * en cada carga, y los contadores se calculaban recorriendo el CSV entero.
 *
 * Ahora es una sola consulta por indicador contra la base.
 *
 *   GET accion=resumen  → contadores generales (por defecto)
 *   GET accion=estado   → salud del sistema: base, tablas, última actividad
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/sesion.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_rol_api(ROLES_ADMIN);


exigir_metodo(['GET']);

$accion = param('accion', param('action', 'resumen'));

try {
    switch ($accion) {
        case 'resumen': resumen(); break;
        case 'estado':  estado();  break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('administracion', $e);
}

// ------------------------------------------------------------------

function resumen(): void
{
    $convocatoriaId = convocatoria_actual();

    $convocatoria = null;
    if ($convocatoriaId !== null) {
        $stmt = db()->prepare('SELECT id, nombre, fecha_aplicacion FROM convocatorias WHERE id = ?');
        $stmt->execute([$convocatoriaId]);
        $convocatoria = $stmt->fetch() ?: null;
    }

    // Tulas por estado. Una sola pasada agrupada en vez de contar en el cliente.
    //
    // Nota sobre los filtros de convocatoria en este archivo: se arma la
    // cláusula en PHP en vez de escribir `(:conv IS NULL OR ... = :conv)`,
    // porque PDO usa consultas preparadas reales y ahí un placeholder nombrado
    // no se puede repetir dentro de la misma consulta.
    $filtro = $convocatoriaId !== null ? 'WHERE convocatoria_id = :conv' : '';
    $parametros = $convocatoriaId !== null ? ['conv' => $convocatoriaId] : [];

    $stmt = db()->prepare("
        SELECT estado, COUNT(*) AS cuantas
        FROM aulas
        {$filtro}
        GROUP BY estado
    ");
    $stmt->execute($parametros);

    $tulas = ['disponible' => 0, 'prestado' => 0, 'devuelto' => 0];
    foreach ($stmt->fetchAll() as $f) {
        $tulas[$f['estado']] = (int) $f['cuantas'];
    }
    $tulas['total'] = array_sum($tulas);

    // Cada subconsulta necesita su propio nombre de parámetro, por lo mismo.
    $f1 = $convocatoriaId !== null ? 'WHERE convocatoria_id = :conv1' : '';
    $f2 = $convocatoriaId !== null ? 'WHERE convocatoria_id = :conv2' : '';
    $f3 = $convocatoriaId !== null ? 'WHERE convocatoria_id = :conv3' : '';
    $f4 = $convocatoriaId !== null ? 'WHERE convocatoria_id = :conv4' : '';
    // Las sedes también son por convocatoria, así que el contador general no
    // debe sumar las de todos los años.
    $f5 = $convocatoriaId !== null ? 'AND convocatoria_id = :conv5' : '';

    $stmt = db()->prepare("
        SELECT
            (SELECT COUNT(*) FROM sedes WHERE activo = 1 {$f5}) AS sedes,
            (SELECT COUNT(*) FROM personas p INNER JOIN areas a ON a.id = p.area_id WHERE p.activo = 1 AND p.oculto = 0 AND a.nombre <> 'IIP') AS personas,
            (SELECT COUNT(DISTINCT sede_id) FROM aulas {$f1}) AS sedes_con_aulas,
            (SELECT COUNT(*) FROM marchamos {$f2}) AS marchamos,
            (SELECT COUNT(*) FROM actas_revision {$f3}) AS actas,
            (SELECT COUNT(*) FROM activos WHERE activo = 1) AS activos,
            (SELECT COUNT(*) FROM activos WHERE activo = 1 AND disponible = 0) AS activos_prestados,
            (SELECT COALESCE(SUM(presupuesto), 0) FROM sobresueldos {$f4}) AS presupuesto_total
    ");
    $stmt->execute($convocatoriaId !== null ? [
        'conv1' => $convocatoriaId,
        'conv2' => $convocatoriaId,
        'conv3' => $convocatoriaId,
        'conv4' => $convocatoriaId,
        'conv5' => $convocatoriaId,
    ] : []);
    $c = $stmt->fetch();

    // Sedes con tulas todavía sin devolver: es el dato que se mira el día de
    // la aplicación, y en la página vieja había que contarlo a ojo.
    $filtroSedes = $convocatoriaId !== null ? 'WHERE a.convocatoria_id = :conv' : '';

    $stmt = db()->prepare("
        SELECT s.codigo AS sede, s.nombre,
               SUM(a.estado = 'prestado')   AS prestadas,
               SUM(a.estado = 'devuelto')   AS devueltas,
               COUNT(*)                     AS total
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        {$filtroSedes}
        GROUP BY s.id, s.codigo, s.nombre
        HAVING prestadas > 0
        ORDER BY prestadas DESC
        LIMIT 20
    ");
    $stmt->execute($parametros);
    $sedesPendientes = $stmt->fetchAll();

    responder_json([
        'success'      => true,
        'convocatoria' => $convocatoria,
        'tulas'        => $tulas,
        'contadores'   => [
            'sedes'             => (int) $c['sedes'],
            'personas'          => (int) $c['personas'],
            'sedesConAulas'     => (int) $c['sedes_con_aulas'],
            'marchamos'         => (int) $c['marchamos'],
            'actas'             => (int) $c['actas'],
            'activos'           => (int) $c['activos'],
            'activosPrestados'  => (int) $c['activos_prestados'],
            'presupuestoTotal'  => (int) $c['presupuesto_total'],
        ],
        'sedesPendientes' => $sedesPendientes,
        'actualizado'     => date('c'),
    ]);
}

/**
 * Salud del sistema.
 *
 * La página vieja mostraba si los Apps Script respondían. Ese chequeo ya no
 * aplica: lo que importa ahora es si la base responde y si los módulos tienen
 * datos cargados, que es la causa habitual de que una página se vea vacía.
 */
function estado(): void
{
    $tablas = ['sedes', 'personas', 'convocatorias', 'aulas', 'marchamos',
               'activos', 'prestamos', 'materiales', 'actas_revision',
               'sobresueldos', 'postulantes'];

    $conteos = [];
    foreach ($tablas as $tabla) {
        try {
            // El nombre viene de la lista fija de arriba, nunca de la petición:
            // interpolarlo es seguro acá y no hay forma de parametrizarlo.
            $conteos[$tabla] = (int) db()->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
        } catch (Throwable $e) {
            // Tabla que aún no existe: la migración correspondiente no se aplicó.
            $conteos[$tabla] = null;
        }
    }

    $ultimoMovimiento = db()->query("
        SELECT MAX(registrado_en) FROM movimientos_tula
    ")->fetchColumn();

    $ultimoCorreo = db()->query("
        SELECT MAX(enviado_en) FROM correos_enviados WHERE exitoso = 1
    ")->fetchColumn();

    responder_json([
        'success'          => true,
        'baseDeDatos'      => 'conectada',
        'tablas'           => $conteos,
        'ultimoMovimiento' => $ultimoMovimiento,
        'ultimoCorreo'     => $ultimoCorreo,
        'php'              => PHP_VERSION,
        'actualizado'      => date('c'),
    ]);
}
