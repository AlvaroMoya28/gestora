<?php
/**
 * Endpoint del módulo Equipo de Funcionarios: qué computadora, teclado, UPS,
 * etc. tiene asignado cada funcionario del PAA, y el sondeo mensual de si ese
 * equipo sigue bien o necesitó arreglos. Distinto de api/inventario_oficina.php,
 * que es el equipo fijo de la oficina sin dueño particular.
 *
 *   GET  accion=listar           → funcionarios con resumen de equipo y sondeo
 *   GET  accion=detalle          → equipo, historial y sondeos de una persona
 *   POST accion=crearEquipo      → asignarle un equipo a una persona
 *   POST accion=actualizarEquipo → editar un equipo asignado
 *   POST accion=retirarEquipo    → quitarle un equipo a una persona
 *   POST accion=reactivarEquipo  → deshacer un retiro
 *   POST accion=registrarSondeo  → anota el sondeo mensual de una persona
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo_api('equipo_funcionarios');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// Cada cuántos meses toca sondear a un funcionario, y con cuántos días de
// anticipación se avisa que ya se acerca.
const MESES_ENTRE_SONDEOS = 1;
const DIAS_AVISO_SONDEO   = 7;

// Solo sugerencias en el selector — el campo es texto libre.
const TIPOS_SUGERIDOS_FUNCIONARIO = [
    'Computadora', 'Laptop', 'Monitor', 'Teclado', 'Mouse', 'UPS',
    'Impresora', 'Audífonos', 'Cable', 'Otro',
];

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':           listar();                break;
        case 'detalle':          detalle();               break;
        case 'crearEquipo':      crear_equipo($yo);       break;
        case 'actualizarEquipo': actualizar_equipo($yo);  break;
        case 'retirarEquipo':    retirar_equipo($yo);     break;
        case 'reactivarEquipo':  reactivar_equipo($yo);   break;
        case 'registrarSondeo':  registrar_sondeo($yo);   break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('equipo_funcionarios', $e);
}

// ==================================================================
// Sondeo: cálculo del contador
// ==================================================================

/**
 * Cuándo toca el próximo sondeo y qué tan urgente está.
 *
 * Si nunca se le ha hecho uno, la base es la fecha en que se le asignó su
 * primer equipo activo — así a alguien recién equipado no le "vence" el
 * sondeo el primer día, y a alguien que tiene equipo de siempre y nunca se
 * le sondeó, sí empieza a avisar.
 */
function proximo_sondeo(?string $ultimoSondeo, string $baseSiNunca): array
{
    $base    = $ultimoSondeo ?: substr($baseSiNunca, 0, 10);
    $proximo = date('Y-m-d', strtotime($base . ' + ' . MESES_ENTRE_SONDEOS . ' months'));
    $dias    = (int) floor((strtotime($proximo) - strtotime(date('Y-m-d'))) / 86400);

    return [
        'proximoSondeo'  => $proximo,
        'diasParaSondeo' => $dias,
        'sondeoVencido'  => $dias <= 0,
        'sondeoPorVencer' => $dias > 0 && $dias <= DIAS_AVISO_SONDEO,
    ];
}

// ==================================================================
// Lectura
// ==================================================================

function listar(): void
{
    $personas = db()->query("
        SELECT p.id, p.nombre, p.correo, a.nombre AS area
        FROM personas p
        INNER JOIN areas a ON a.id = p.area_id
        WHERE p.activo = 1 AND p.oculto = 0 AND a.nombre <> 'IIP'
        ORDER BY a.orden, p.nombre
    ")->fetchAll();

    $equipoPorPersona = [];
    foreach (db()->query("
        SELECT persona_id, COUNT(*) AS total, MIN(creado_en) AS primer_equipo
        FROM equipo_funcionario
        WHERE estado = 'activo'
        GROUP BY persona_id
    ")->fetchAll() as $f) {
        $equipoPorPersona[(int) $f['persona_id']] = $f;
    }

    $ultimoSondeoPorPersona = [];
    foreach (db()->query('
        SELECT persona_id, MAX(fecha) AS ultima_fecha
        FROM equipo_funcionario_sondeos
        GROUP BY persona_id
    ')->fetchAll() as $f) {
        $ultimoSondeoPorPersona[(int) $f['persona_id']] = $f['ultima_fecha'];
    }

    $vencidos  = 0;
    $porVencer = 0;

    $lista = array_map(static function (array $p) use ($equipoPorPersona, $ultimoSondeoPorPersona, &$vencidos, &$porVencer): array {
        $id     = (int) $p['id'];
        $equipo = $equipoPorPersona[$id] ?? null;

        $sinContador = [
            'proximoSondeo' => null, 'diasParaSondeo' => null,
            'sondeoVencido' => false, 'sondeoPorVencer' => false,
        ];
        $contador = $equipo !== null
            ? proximo_sondeo($ultimoSondeoPorPersona[$id] ?? null, $equipo['primer_equipo'])
            : $sinContador;

        if ($equipo !== null) {
            if ($contador['sondeoVencido']) {
                $vencidos++;
            } elseif ($contador['sondeoPorVencer']) {
                $porVencer++;
            }
        }

        return [
            'id'            => $id,
            'nombre'        => $p['nombre'],
            'correo'        => $p['correo'] ?? '',
            'area'          => $p['area'],
            'totalEquipo'   => $equipo !== null ? (int) $equipo['total'] : 0,
            'ultimoSondeo'  => $ultimoSondeoPorPersona[$id] ?? null,
        ] + $contador;
    }, $personas);

    responder_json([
        'success'         => true,
        'funcionarios'    => $lista,
        'total'           => count($lista),
        'resumenSondeo'   => ['vencidos' => $vencidos, 'porVencer' => $porVencer],
        'tiposSugeridos'  => TIPOS_SUGERIDOS_FUNCIONARIO,
    ]);
}

function persona_o_404(int $id): array
{
    $stmt = db()->prepare("
        SELECT p.id, p.nombre, p.correo, a.nombre AS area
        FROM personas p
        INNER JOIN areas a ON a.id = p.area_id
        WHERE p.id = ? AND p.activo = 1 AND p.oculto = 0 AND a.nombre <> 'IIP'
    ");
    $stmt->execute([$id]);
    $persona = $stmt->fetch();
    if ($persona === false) {
        responder_json(['success' => false, 'error' => 'Esa persona no existe'], 404);
    }
    return $persona;
}

function detalle(): void
{
    exigir(['personaId']);
    $personaId = param_int('personaId');
    $persona   = persona_o_404($personaId);

    $equipos = db()->prepare("
        SELECT id, tipo, nombre, marca, modelo, serie, observaciones,
               fecha_asignacion, estado, motivo_retiro, retirado_en, creado_en
        FROM equipo_funcionario
        WHERE persona_id = ?
        ORDER BY (estado = 'activo') DESC, tipo, nombre
    ");
    $equipos->execute([$personaId]);

    $historial = db()->prepare("
        SELECT h.tipo_evento, h.descripcion, h.realizado_por, h.creado_en, e.nombre AS equipo_nombre
        FROM equipo_funcionario_historial h
        LEFT JOIN equipo_funcionario e ON e.id = h.equipo_id
        WHERE h.persona_id = ?
        ORDER BY h.creado_en DESC, h.id DESC
    ");
    $historial->execute([$personaId]);

    $sondeos = db()->prepare('
        SELECT fecha, resultado, observaciones, realizado_por, creado_en
        FROM equipo_funcionario_sondeos
        WHERE persona_id = ?
        ORDER BY fecha DESC, id DESC
    ');
    $sondeos->execute([$personaId]);
    $listaSondeos = $sondeos->fetchAll();

    $equipoActivoMasViejo = db()->prepare("
        SELECT MIN(creado_en) AS m FROM equipo_funcionario WHERE persona_id = ? AND estado = 'activo'
    ");
    $equipoActivoMasViejo->execute([$personaId]);
    $baseSiNunca = $equipoActivoMasViejo->fetch()['m'] ?? null;

    $contador = $baseSiNunca !== null
        ? proximo_sondeo($listaSondeos[0]['fecha'] ?? null, $baseSiNunca)
        : ['proximoSondeo' => null, 'diasParaSondeo' => null, 'sondeoVencido' => false, 'sondeoPorVencer' => false];

    responder_json([
        'success'   => true,
        'persona'   => $persona,
        'equipos'   => $equipos->fetchAll(),
        'historial' => $historial->fetchAll(),
        'sondeos'   => $listaSondeos,
        'contador'  => $contador,
    ]);
}

// ==================================================================
// Equipo: alta, edición, retiro
// ==================================================================

function crear_equipo(array $yo): void
{
    exigir(['personaId', 'tipo', 'nombre']);
    $personaId = param_int('personaId');
    persona_o_404($personaId);

    $tipo   = param('tipo');
    $nombre = param('nombre');
    if ($tipo === '' || $nombre === '') {
        responder_json(['success' => false, 'error' => 'Faltan el tipo o el nombre del equipo'], 400);
    }

    $fechaAsignacion = param('fechaAsignacion') ?: date('Y-m-d');

    db()->prepare('
        INSERT INTO equipo_funcionario
            (persona_id, tipo, nombre, marca, modelo, serie, observaciones, fecha_asignacion, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        $personaId, $tipo, $nombre,
        param('marca') ?: null, param('modelo') ?: null, param('serie') ?: null,
        param('observaciones') ?: null, $fechaAsignacion, $yo['nombre'],
    ]);
    $equipoId = (int) db()->lastInsertId();

    registrar_evento($personaId, $equipoId, 'equipo_agregado', "Se agregó {$tipo}: {$nombre}", $yo['nombre']);

    bitacora_apuntar('equipo_funcionarios', 'crearEquipo', "personaId={$personaId} equipo={$nombre}");

    responder_json(['success' => true, 'id' => $equipoId]);
}

function equipo_de_persona_o_404(int $id, int $personaId): array
{
    $stmt = db()->prepare('SELECT * FROM equipo_funcionario WHERE id = ? AND persona_id = ?');
    $stmt->execute([$id, $personaId]);
    $equipo = $stmt->fetch();
    if ($equipo === false) {
        responder_json(['success' => false, 'error' => 'Ese equipo no existe'], 404);
    }
    return $equipo;
}

function actualizar_equipo(array $yo): void
{
    exigir(['id', 'personaId', 'tipo', 'nombre']);
    $id        = param_int('id');
    $personaId = param_int('personaId');
    $anterior  = equipo_de_persona_o_404($id, $personaId);

    $tipo   = param('tipo');
    $nombre = param('nombre');
    if ($tipo === '' || $nombre === '') {
        responder_json(['success' => false, 'error' => 'Faltan el tipo o el nombre del equipo'], 400);
    }

    db()->prepare('
        UPDATE equipo_funcionario SET
            tipo = ?, nombre = ?, marca = ?, modelo = ?, serie = ?, observaciones = ?, fecha_asignacion = ?
        WHERE id = ?
    ')->execute([
        $tipo, $nombre,
        param('marca') ?: null, param('modelo') ?: null, param('serie') ?: null,
        param('observaciones') ?: null, param('fechaAsignacion') ?: $anterior['fecha_asignacion'],
        $id,
    ]);

    registrar_evento($personaId, $id, 'equipo_editado', "Se editó {$tipo}: {$anterior['nombre']} → {$nombre}", $yo['nombre']);

    bitacora_apuntar('equipo_funcionarios', 'actualizarEquipo', "id={$id} personaId={$personaId}");

    responder_json(['success' => true]);
}

function retirar_equipo(array $yo): void
{
    exigir(['id', 'personaId', 'motivo']);
    $id        = param_int('id');
    $personaId = param_int('personaId');
    $motivo    = param('motivo');
    $equipo    = equipo_de_persona_o_404($id, $personaId);

    if ($motivo === '') {
        responder_json(['success' => false, 'error' => 'Hace falta indicar el motivo del retiro'], 400);
    }
    if ($equipo['estado'] !== 'activo') {
        responder_json(['success' => false, 'error' => 'Ese equipo ya estaba retirado'], 409);
    }

    db()->prepare("
        UPDATE equipo_funcionario SET estado = 'retirado', motivo_retiro = ?, retirado_en = NOW()
        WHERE id = ?
    ")->execute([$motivo, $id]);

    registrar_evento($personaId, $id, 'equipo_retirado', "Se retiró {$equipo['tipo']}: {$equipo['nombre']} ({$motivo})", $yo['nombre']);

    bitacora_apuntar('equipo_funcionarios', 'retirarEquipo', "id={$id} personaId={$personaId} motivo={$motivo}");

    responder_json(['success' => true]);
}

function reactivar_equipo(array $yo): void
{
    exigir(['id', 'personaId']);
    $id        = param_int('id');
    $personaId = param_int('personaId');
    $equipo    = equipo_de_persona_o_404($id, $personaId);

    if ($equipo['estado'] !== 'retirado') {
        responder_json(['success' => false, 'error' => 'Ese equipo no estaba retirado'], 409);
    }

    db()->prepare("
        UPDATE equipo_funcionario SET estado = 'activo', motivo_retiro = NULL, retirado_en = NULL
        WHERE id = ?
    ")->execute([$id]);

    registrar_evento($personaId, $id, 'equipo_reactivado', "Se reactivó {$equipo['tipo']}: {$equipo['nombre']}", $yo['nombre']);

    bitacora_apuntar('equipo_funcionarios', 'reactivarEquipo', "id={$id} personaId={$personaId}");

    responder_json(['success' => true]);
}

// ==================================================================
// Sondeo mensual
// ==================================================================

function registrar_sondeo(array $yo): void
{
    exigir(['personaId', 'resultado']);
    $personaId = param_int('personaId');
    persona_o_404($personaId);

    $resultado = param('resultado');
    if (!in_array($resultado, ['bien', 'arreglos'], true)) {
        responder_json(['success' => false, 'error' => 'El resultado debe ser "bien" o "arreglos"'], 400);
    }

    $fecha         = param('fecha') ?: date('Y-m-d');
    $observaciones = param('observaciones');

    db()->prepare('
        INSERT INTO equipo_funcionario_sondeos (persona_id, fecha, resultado, observaciones, realizado_por)
        VALUES (?, ?, ?, ?, ?)
    ')->execute([$personaId, $fecha, $resultado, $observaciones ?: null, $yo['nombre']]);

    $texto = $resultado === 'bien'
        ? 'Sondeo mensual: el equipo está bien'
        : 'Sondeo mensual: se necesitaron arreglos';
    if ($observaciones !== '') {
        $texto .= " — {$observaciones}";
    }
    registrar_evento($personaId, null, 'sondeo', $texto, $yo['nombre']);

    bitacora_apuntar('equipo_funcionarios', 'registrarSondeo', "personaId={$personaId} resultado={$resultado}");

    responder_json(['success' => true]);
}

// ==================================================================
// Bitácora
// ==================================================================

function registrar_evento(int $personaId, ?int $equipoId, string $tipoEvento, string $descripcion, string $realizadoPor): void
{
    db()->prepare('
        INSERT INTO equipo_funcionario_historial (persona_id, equipo_id, tipo_evento, descripcion, realizado_por)
        VALUES (?, ?, ?, ?, ?)
    ')->execute([$personaId, $equipoId, $tipoEvento, $descripcion, $realizadoPor]);
}
