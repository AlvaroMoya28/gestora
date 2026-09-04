<?php
/**
 * Consulta de la bitácora: ingresos, actividad y estadísticas.
 *
 * Solo la administración. Acá se ve a qué hora entra cada persona y qué tocó,
 * que es información sobre gente, no sobre sedes.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/peticion.php';

$yo = exigir_rol_api(ROLES_ADMIN);

exigir_metodo(['GET', 'POST']);

/** Filas por página. Fijo acá y no por parámetro: evita que alguien pida 100000. */
const POR_PAGINA = 50;

$accion = param('accion', param('action', 'ingresos'));

try {
    switch ($accion) {
        case 'ingresos':   listar_ingresos();   break;
        case 'actividad':  listar_actividad();  break;
        case 'resumen':    resumen();           break;
        case 'enLinea':    en_linea();          break;
        case 'limpiar':    limpiar($yo);        break;

        default:
            responder_json(['success' => false, 'error' => "Acción desconocida: {$accion}"], 400);
    }
} catch (Throwable $e) {
    fallo('bitacora', $e);
}

// ------------------------------------------------------------------
// Ingresos al sistema
// ------------------------------------------------------------------

function listar_ingresos(): void
{
    [$where, $parametros] = filtros_ingresos();

    $total = (int) consulta_valor(
        "SELECT COUNT(*) FROM ingresos i {$where}",
        $parametros
    );

    $pagina = pagina_pedida($total);

    // El nombre se saca por LEFT JOIN, pero se cae a usuario_texto: los
    // intentos con usuarios que no existen no tienen fila en `usuarios` y son
    // justamente los que interesa ver.
    $sql = "
        SELECT i.id, i.usuario_texto, i.exitoso, i.ip, i.ocurrido_en,
               p.nombre AS nombre, u.rol
        FROM ingresos i
        LEFT JOIN usuarios u ON u.id = i.usuario_id
        LEFT JOIN personas p ON p.id = u.persona_id
        {$where}
        ORDER BY i.ocurrido_en DESC, i.id DESC
        LIMIT " . POR_PAGINA . " OFFSET " . (($pagina - 1) * POR_PAGINA);

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    $filas = array_map(static fn(array $f): array => [
        'id'        => (int) $f['id'],
        'usuario'   => $f['usuario_texto'],
        'nombre'    => $f['nombre'],
        'rol'       => $f['rol'],
        'exitoso'   => (int) $f['exitoso'] === 1,
        'ip'        => $f['ip'],
        'fecha'     => $f['ocurrido_en'],
    ], $stmt->fetchAll());

    responder_json([
        'success'     => true,
        'filas'       => $filas,
        'total'       => $total,
        'pagina'      => $pagina,
        'porPagina'   => POR_PAGINA,
        'totalPaginas' => max(1, (int) ceil($total / POR_PAGINA)),
    ]);
}

function filtros_ingresos(): array
{
    $condiciones = [];
    $parametros  = [];

    if (($usuario = param('usuario')) !== '') {
        $condiciones[] = 'i.usuario_texto LIKE :usuario';
        $parametros['usuario'] = '%' . $usuario . '%';
    }

    // 'si' / 'no' y no un booleano: en la URL, exitoso=0 y "sin filtro" se
    // confunden, porque los dos llegan como cadena vacía o como "0".
    $resultado = param('resultado');
    if ($resultado === 'si' || $resultado === 'no') {
        $condiciones[] = 'i.exitoso = :exitoso';
        $parametros['exitoso'] = $resultado === 'si' ? 1 : 0;
    }

    agregar_rango_fechas($condiciones, $parametros, 'i.ocurrido_en');

    return [
        $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones),
        $parametros,
    ];
}

// ------------------------------------------------------------------
// Actividad (lo que se hizo)
// ------------------------------------------------------------------

function listar_actividad(): void
{
    $condiciones = [];
    $parametros  = [];

    if (($usuario = param('usuario')) !== '') {
        $condiciones[] = 'a.usuario_texto LIKE :usuario';
        $parametros['usuario'] = '%' . $usuario . '%';
    }

    if (($modulo = param('modulo')) !== '') {
        $condiciones[] = 'a.modulo = :modulo';
        $parametros['modulo'] = $modulo;
    }

    agregar_rango_fechas($condiciones, $parametros, 'a.ocurrido_en');

    $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);

    $total = (int) consulta_valor("SELECT COUNT(*) FROM actividad a {$where}", $parametros);
    $pagina = pagina_pedida($total);

    $sql = "
        SELECT a.id, a.usuario_texto, a.modulo, a.accion, a.detalle,
               a.exitoso, a.ip, a.ocurrido_en, p.nombre AS nombre
        FROM actividad a
        LEFT JOIN usuarios u ON u.id = a.usuario_id
        LEFT JOIN personas p ON p.id = u.persona_id
        {$where}
        ORDER BY a.ocurrido_en DESC, a.id DESC
        LIMIT " . POR_PAGINA . " OFFSET " . (($pagina - 1) * POR_PAGINA);

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    $filas = array_map(static fn(array $f): array => [
        'id'      => (int) $f['id'],
        'usuario' => $f['usuario_texto'],
        'nombre'  => $f['nombre'],
        'modulo'  => $f['modulo'],
        'accion'  => $f['accion'],
        'detalle' => $f['detalle'],
        'exitoso' => (int) $f['exitoso'] === 1,
        'ip'      => $f['ip'],
        'fecha'   => $f['ocurrido_en'],
    ], $stmt->fetchAll());

    responder_json([
        'success'      => true,
        'filas'        => $filas,
        'total'        => $total,
        'pagina'       => $pagina,
        'porPagina'    => POR_PAGINA,
        'totalPaginas' => max(1, (int) ceil($total / POR_PAGINA)),
        'modulos'      => db()->query('SELECT DISTINCT modulo FROM actividad ORDER BY modulo')
                              ->fetchAll(PDO::FETCH_COLUMN),
    ]);
}

// ------------------------------------------------------------------
// Resumen
// ------------------------------------------------------------------

function resumen(): void
{
    $dias = max(1, min(365, param_int('dias', 30)));

    responder_json([
        'success' => true,
        'dias'    => $dias,

        'totales' => [
            'ingresos'      => (int) consulta_valor('SELECT COUNT(*) FROM ingresos'),
            'ingresosHoy'   => (int) consulta_valor(
                'SELECT COUNT(*) FROM ingresos WHERE exitoso = 1 AND DATE(ocurrido_en) = CURDATE()'
            ),
            'fallidos'      => (int) consulta_valor(
                'SELECT COUNT(*) FROM ingresos WHERE exitoso = 0 AND ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)',
                [$dias]
            ),
            'acciones'      => (int) consulta_valor(
                'SELECT COUNT(*) FROM actividad WHERE ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)',
                [$dias]
            ),
            'activos'       => (int) consulta_valor(
                'SELECT COUNT(DISTINCT usuario_id) FROM ingresos
                 WHERE exitoso = 1 AND ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)',
                [$dias]
            ),
        ],

        // Quiénes entran más.
        'topUsuarios' => consulta_filas("
            SELECT i.usuario_texto AS usuario, p.nombre, COUNT(*) AS veces,
                   MAX(i.ocurrido_en) AS ultimo
            FROM ingresos i
            LEFT JOIN usuarios u ON u.id = i.usuario_id
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE i.exitoso = 1
              AND i.ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY i.usuario_texto, p.nombre
            ORDER BY veces DESC
            LIMIT 10
        ", [$dias]),

        // Qué módulos se usan más.
        'topModulos' => consulta_filas("
            SELECT modulo, COUNT(*) AS veces
            FROM actividad
            WHERE ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY modulo
            ORDER BY veces DESC
            LIMIT 10
        ", [$dias]),

        // Quiénes trabajan más (acciones, no ingresos).
        'topActivos' => consulta_filas("
            SELECT a.usuario_texto AS usuario, p.nombre, COUNT(*) AS veces
            FROM actividad a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE a.ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY a.usuario_texto, p.nombre
            ORDER BY veces DESC
            LIMIT 10
        ", [$dias]),

        // Serie diaria para el gráfico. Los días sin movimiento no aparecen:
        // los completa el navegador, que es donde se sabe qué rango se dibuja.
        'porDia' => consulta_filas("
            SELECT DATE(ocurrido_en) AS dia,
                   SUM(exitoso = 1) AS exitosos,
                   SUM(exitoso = 0) AS fallidos
            FROM ingresos
            WHERE ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(ocurrido_en)
            ORDER BY dia
        ", [$dias]),

        // A qué hora se trabaja.
        'porHora' => consulta_filas("
            SELECT HOUR(ocurrido_en) AS hora, COUNT(*) AS veces
            FROM actividad
            WHERE ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY HOUR(ocurrido_en)
            ORDER BY hora
        ", [$dias]),
    ]);
}

// ------------------------------------------------------------------
// Quién está en línea
// ------------------------------------------------------------------

/**
 * Quién parece estar trabajando ahora mismo.
 *
 * No hay un latido de "tengo la página abierta" para todo el sistema (solo
 * existe uno acotado a una sede dentro de Ingreso de Datos, ver
 * schema/020_bloqueos_sede.sql). En vez de eso, "en línea" se aproxima con
 * quien tuvo una fila en `ingresos` o `actividad` en los últimos minutos: es
 * lo mismo que se ve mirando por encima del hombro a alguien trabajando, y no
 * exige agregar un mecanismo nuevo para algo que ya se está registrando.
 */
function en_linea(): void
{
    $minutos = max(1, min(60, param_int('minutos', 5)));

    // SUBSTRING_INDEX sobre un GROUP_CONCAT ordenado es el truco de siempre
    // para quedarse con "el primero del grupo" sin funciones de ventana: acá
    // se usa para saber en qué módulo estuvo la acción MÁS RECIENTE de cada
    // persona, no todos los que tocó en la ventana de tiempo. Los nombres de
    // módulo son el basename de un endpoint (sin comas), así que una coma
    // alcanza como separador.
    $filas = consulta_filas("
        SELECT usuario, nombre, rol, MAX(ultimo) AS ultimo,
               SUBSTRING_INDEX(GROUP_CONCAT(modulo ORDER BY ultimo DESC SEPARATOR ','), ',', 1) AS modulo
        FROM (
            SELECT i.usuario_texto AS usuario, p.nombre AS nombre, u.rol AS rol,
                   i.ocurrido_en AS ultimo, 'inicio de sesión' AS modulo
            FROM ingresos i
            LEFT JOIN usuarios u ON u.id = i.usuario_id
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE i.exitoso = 1 AND i.ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? MINUTE)

            UNION ALL

            SELECT a.usuario_texto AS usuario, p.nombre AS nombre, u.rol AS rol,
                   a.ocurrido_en AS ultimo, a.modulo AS modulo
            FROM actividad a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE a.ocurrido_en >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ) reciente
        WHERE usuario IS NOT NULL
        GROUP BY usuario, nombre, rol
        ORDER BY ultimo DESC
    ", [$minutos, $minutos]);

    responder_json([
        'success'  => true,
        'minutos'  => $minutos,
        'personas' => array_map(static fn(array $f): array => [
            'usuario' => $f['usuario'],
            'nombre'  => $f['nombre'],
            'rol'     => $f['rol'],
            'modulo'  => $f['modulo'],
            'ultimo'  => $f['ultimo'],
        ], $filas),
    ]);
}

// ------------------------------------------------------------------
// Limpieza
// ------------------------------------------------------------------

/**
 * Borra registros viejos.
 *
 * Se puede limitar por antigüedad (lo normal) o vaciar del todo. En los dos
 * casos queda constancia de quién limpió: ver el comentario al final de
 * schema/015_bitacora.sql.
 */
function limpiar(array $yo): void
{
    exigir_metodo(['POST']);

    if (!csrf_valido(param('csrf'))) {
        responder_json(['success' => false, 'error' => 'Ficha de seguridad vencida. Recargá la página.'], 403);
    }

    $que  = param('que', 'ambas');           // 'ingresos' | 'actividad' | 'ambas'
    $dias = param_int('dias', 90);

    // dias = 0 vacía la tabla. Se exige escribir la palabra completa para
    // eso: es un borrado que no se puede deshacer.
    if ($dias === 0 && mb_strtoupper(param('confirmacion')) !== 'BORRAR TODO') {
        responder_json([
            'success' => false,
            'error'   => 'Para vaciar la bitácora completa hay que escribir «BORRAR TODO».',
        ], 400);
    }

    $condicion = $dias > 0 ? 'WHERE ocurrido_en < DATE_SUB(NOW(), INTERVAL ? DAY)' : '';
    $parametros = $dias > 0 ? [$dias] : [];

    $borradas = ['ingresos' => 0, 'actividad' => 0];

    if ($que === 'ingresos' || $que === 'ambas') {
        $stmt = db()->prepare("DELETE FROM ingresos {$condicion}");
        $stmt->execute($parametros);
        $borradas['ingresos'] = $stmt->rowCount();
    }

    if ($que === 'actividad' || $que === 'ambas') {
        $stmt = db()->prepare("DELETE FROM actividad {$condicion}");
        $stmt->execute($parametros);
        $borradas['actividad'] = $stmt->rowCount();
    }

    // DESPUÉS del borrado, para que no se borre a sí misma.
    bitacora_apuntar(
        'bitacora',
        'limpiar',
        sprintf(
            '%s · %s · %d ingresos y %d acciones',
            $que,
            $dias > 0 ? "anteriores a {$dias} días" : 'TODO',
            $borradas['ingresos'],
            $borradas['actividad']
        )
    );

    responder_json([
        'success'  => true,
        'borradas' => $borradas,
        'mensaje'  => sprintf(
            'Se eliminaron %d ingreso(s) y %d acción(es). La limpieza quedó registrada a nombre de %s.',
            $borradas['ingresos'],
            $borradas['actividad'],
            $yo['usuario']
        ),
    ]);
}

// ------------------------------------------------------------------
// Auxiliares
// ------------------------------------------------------------------

/** Página pedida, acotada a lo que existe. */
function pagina_pedida(int $total): int
{
    $ultima = max(1, (int) ceil($total / POR_PAGINA));

    return max(1, min($ultima, param_int('pagina', 1)));
}

/**
 * Agrega el filtro de fechas si vinieron.
 *
 * La fecha "hasta" se compara contra el día siguiente en vez de usar <=: si no,
 * "hasta el 20" dejaría fuera todo lo del día 20 después de medianoche, porque
 * la columna guarda hora.
 */
function agregar_rango_fechas(array &$condiciones, array &$parametros, string $columna): void
{
    if (($desde = param('desde')) !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) === 1) {
        $condiciones[] = "{$columna} >= :desde";
        $parametros['desde'] = $desde . ' 00:00:00';
    }

    if (($hasta = param('hasta')) !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) === 1) {
        $condiciones[] = "{$columna} < :hasta";
        $parametros['hasta'] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
    }
}

function consulta_valor(string $sql, array $parametros = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    return $stmt->fetchColumn();
}

function consulta_filas(string $sql, array $parametros = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    return $stmt->fetchAll();
}
