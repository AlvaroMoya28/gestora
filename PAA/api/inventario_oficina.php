<?php
/**
 * Endpoint del módulo Inventario de Oficina: el equipo fijo de la oficina
 * (computadoras, laptops, periféricos, cables...), no el que se presta a las
 * sedes — eso es api/activos.php.
 *
 *   GET  accion=listar                 → todo el inventario (activo, o con ?incluirBaja=1 también las bajas),
 *                                         incluyendo — sólo para mostrar — lo que hay en Activos de Préstamo
 *   POST accion=crear                  → alta manual de un equipo (si el código ya existe, responde 409 con
 *                                         los datos de ese equipo para que el frontend ofrezca sumar cantidad)
 *   POST accion=sumarCantidad          → suma unidades a un equipo existente con ese código
 *   POST accion=actualizar             → editar un equipo existente
 *   POST accion=darDeBaja              → marcar un equipo como dado de baja
 *   POST accion=reactivar              → deshacer una baja
 *   POST accion=registrarMantenimiento → anota un mantenimiento y actualiza la fecha
 *   GET  accion=historialMantenimiento → historial de mantenimientos de un equipo
 *   GET  accion=plantilla              → descarga la plantilla para carga masiva
 *   POST accion=analizar               → analiza un archivo subido (CSV/XLSX), sin guardar
 *   POST accion=cargar                 → aplica la carga masiva de verdad
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/tabla_archivo.php';

$yo = exigir_modulo_api('inventario_oficina');

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// Cada cuántos meses toca mantenimiento, y con cuántos días de anticipación
// se avisa que ya se acerca (para no enterarse el mismo día que vence).
const MESES_ENTRE_MANTENIMIENTOS = 6;
const DIAS_AVISO_MANTENIMIENTO   = 30;

// Solo sugerencias en el selector — el campo es texto libre, así que "Otro"
// más lo que se escriba cubre cualquier cosa que no esté en la lista.
const TIPOS_SUGERIDOS = [
    'Computadora', 'Laptop', 'Monitor', 'Teclado', 'Mouse', 'Impresora',
    'Lectora', 'Router/Switch', 'Cable', 'Otro',
];

// Dónde vive físicamente todo lo que entra a este módulo: es "Inventario de
// Oficina", así que la ubicación no se pregunta, se completa sola.
const UBICACION_FIJA = 'Oficina de Informática';

// No es lo mismo que `estado` (activo/baja): algo dañado sigue en el
// inventario y hay que poder anotarlo así, sin tener que darlo de baja.
const CONDICIONES_VALIDAS = ['bueno', 'regular', 'danado'];

function condicion_valida(string $valor): string
{
    return in_array($valor, CONDICIONES_VALIDAS, true) ? $valor : 'bueno';
}

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':                 listar();                     break;
        case 'crear':                  crear($yo);                   break;
        case 'sumarCantidad':          sumar_cantidad();              break;
        case 'actualizar':             actualizar();                 break;
        case 'darDeBaja':              dar_de_baja();                break;
        case 'reactivar':              reactivar();                  break;
        case 'registrarMantenimiento': registrar_mantenimiento($yo); break;
        case 'historialMantenimiento': historial_mantenimiento();    break;
        case 'plantilla':              plantilla();                  break;
        case 'analizar':               analizar_archivo();           break;
        case 'cargar':                 cargar_equipos($yo);          break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('inventario_oficina', $e);
}

// ==================================================================
// Mantenimiento
// ==================================================================

/** Por defecto se marcan solas las computadoras y laptops; lo demás no. */
function requiere_mantenimiento_por_tipo(string $tipo): bool
{
    return in_array(mb_strtolower(trim($tipo), 'UTF-8'), ['computadora', 'laptop'], true);
}

/**
 * Cuándo toca el próximo mantenimiento y qué tan urgente está.
 *
 * Si nunca se le ha dado uno, la base es la fecha en que se dio de alta el
 * equipo — así un equipo recién comprado no aparece "vencido" desde el
 * primer día, y uno viejo que nunca se registró sí empieza a avisar.
 */
function proximo_mantenimiento(?string $ultimoMantenimiento, string $creadoEn): array
{
    $base    = $ultimoMantenimiento ?: substr($creadoEn, 0, 10);
    $proximo = date('Y-m-d', strtotime($base . ' + ' . MESES_ENTRE_MANTENIMIENTOS . ' months'));
    $dias    = (int) floor((strtotime($proximo) - strtotime(date('Y-m-d'))) / 86400);

    return [
        'proximoMantenimiento'   => $proximo,
        'diasParaMantenimiento'  => $dias,
        'mantenimientoVencido'   => $dias <= 0,
        'mantenimientoPorVencer' => $dias > 0 && $dias <= DIAS_AVISO_MANTENIMIENTO,
    ];
}

// ==================================================================
// Lectura
// ==================================================================

function listar(): void
{
    $incluirBaja = param('incluirBaja') === '1';

    $sql = "
        SELECT id, codigo, tipo, nombre, cantidad, condicion, marca, modelo, serie, ubicacion,
               responsable, observaciones, requiere_mantenimiento,
               ultimo_mantenimiento, estado, motivo_baja, baja_en,
               creado_por, creado_en
        FROM equipo_oficina
    ";
    if (!$incluirBaja) {
        $sql .= " WHERE estado = 'activo'";
    }
    $sql .= ' ORDER BY tipo, nombre';

    $filas = db()->query($sql)->fetchAll();

    $vencidos  = 0;
    $porVencer = 0;

    $equipos = array_map(static function (array $f) use (&$vencidos, &$porVencer): array {
        $sinMantenimiento = [
            'proximoMantenimiento' => null, 'diasParaMantenimiento' => null,
            'mantenimientoVencido' => false, 'mantenimientoPorVencer' => false,
        ];
        $mant = ((bool) $f['requiere_mantenimiento'])
            ? proximo_mantenimiento($f['ultimo_mantenimiento'], $f['creado_en'])
            : $sinMantenimiento;

        if ($f['estado'] === 'activo' && (bool) $f['requiere_mantenimiento']) {
            if ($mant['mantenimientoVencido']) {
                $vencidos++;
            } elseif ($mant['mantenimientoPorVencer']) {
                $porVencer++;
            }
        }

        return [
            'id'                    => (int) $f['id'],
            'codigo'                => $f['codigo'],
            'tipo'                  => $f['tipo'],
            'nombre'                => $f['nombre'],
            'cantidad'              => (int) $f['cantidad'],
            'condicion'             => $f['condicion'] ?? 'bueno',
            'marca'                 => $f['marca'] ?? '',
            'modelo'                => $f['modelo'] ?? '',
            'serie'                 => $f['serie'] ?? '',
            'ubicacion'             => $f['ubicacion'] ?? '',
            'responsable'           => $f['responsable'] ?? '',
            'observaciones'         => $f['observaciones'] ?? '',
            'requiereMantenimiento' => (bool) $f['requiere_mantenimiento'],
            'ultimoMantenimiento'   => $f['ultimo_mantenimiento'],
            'estado'                => $f['estado'],
            'motivoBaja'            => $f['motivo_baja'] ?? '',
            'bajaEn'                => $f['baja_en'],
            'creadoPor'             => $f['creado_por'] ?? '',
            'creadoEn'              => $f['creado_en'],
            'origen'                => 'oficina',
        ] + $mant;
    }, $filas);

    foreach (equipos_de_prestamo($incluirBaja) as $eq) {
        if ($eq['estado'] === 'activo' && $eq['requiereMantenimiento']) {
            if ($eq['mantenimientoVencido']) {
                $vencidos++;
            } elseif ($eq['mantenimientoPorVencer']) {
                $porVencer++;
            }
        }
        $equipos[] = $eq;
    }

    responder_json([
        'success'              => true,
        'equipos'              => $equipos,
        'total'                => count($equipos),
        'resumenMantenimiento' => ['vencidos' => $vencidos, 'porVencer' => $porVencer],
        'tiposSugeridos'       => tipos_sugeridos_con_usados(),
        'nombresSugeridos'     => nombres_sugeridos_por_tipo(),
    ]);
}

/**
 * Los tipos fijos de siempre, más los que ya se escribieron a mano en algún
 * equipo ("Otro" + texto libre) — así lo que alguien tipeó una vez queda
 * disponible como sugerencia la próxima, sin tener que tocar código.
 */
function tipos_sugeridos_con_usados(): array
{
    $usados = db()->query("
        SELECT DISTINCT tipo FROM equipo_oficina WHERE tipo IS NOT NULL AND tipo <> '' ORDER BY tipo
    ")->fetchAll(PDO::FETCH_COLUMN);

    $todos = array_unique(array_merge(
        array_diff(TIPOS_SUGERIDOS, ['Otro']),
        $usados
    ));
    sort($todos, SORT_NATURAL | SORT_FLAG_CASE);
    $todos[] = 'Otro';

    return array_values($todos);
}

/** Nombres ya usados, agrupados por tipo — para autocompletar "Cable", "Mouse USB", etc. */
function nombres_sugeridos_por_tipo(): array
{
    $filas = db()->query("
        SELECT DISTINCT tipo, nombre FROM equipo_oficina
        WHERE tipo IS NOT NULL AND tipo <> '' AND nombre IS NOT NULL AND nombre <> ''
        ORDER BY tipo, nombre
    ")->fetchAll();

    $porTipo = [];
    foreach ($filas as $f) {
        $porTipo[$f['tipo']][] = $f['nombre'];
    }
    return $porTipo;
}

/**
 * Lo que hay en Activos de Préstamo (tabla `activos`), mostrado también acá
 * porque también es cosa de la oficina — de sólo lectura: se gestiona desde
 * el módulo "Activos de Préstamo", esto es nada más para verlo todo junto.
 */
function equipos_de_prestamo(bool $incluirBaja): array
{
    $sql = "
        SELECT a.id, a.codigo, a.nombre, a.condicion, a.serie_placa, a.imei, a.accesorios, a.estado_obs,
               a.disponible, a.activo, a.requiere_mantenimiento, a.ultimo_mantenimiento, a.creado_en,
               p.funcionario AS prestado_a
        FROM activos a
        LEFT JOIN prestamos p ON p.activo_id = a.id AND p.estado = 'prestado'
    ";
    if (!$incluirBaja) {
        $sql .= ' WHERE a.activo = 1';
    }
    $sql .= ' ORDER BY a.nombre, a.codigo';

    $sinMantenimiento = [
        'proximoMantenimiento' => null, 'diasParaMantenimiento' => null,
        'mantenimientoVencido' => false, 'mantenimientoPorVencer' => false,
    ];

    return array_map(static function (array $f) use ($sinMantenimiento): array {
        $mant = ((bool) $f['requiere_mantenimiento'])
            ? proximo_mantenimiento($f['ultimo_mantenimiento'], $f['creado_en'])
            : $sinMantenimiento;

        return [
            'id'                    => 'prestamo-' . $f['id'],
            'idReal'                => (int) $f['id'],
            'codigo'                => $f['codigo'],
            'tipo'                  => 'Activo de préstamo',
            'nombre'                => $f['nombre'],
            'cantidad'              => 1,
            'condicion'             => $f['condicion'] ?? 'bueno',
            'marca'                 => '',
            'modelo'                => '',
            'serie'                 => $f['serie_placa'] ?? '',
            'imei'                  => $f['imei'] ?? '',
            'accesorios'            => $f['accesorios'] ?? '',
            'estadoObs'             => $f['estado_obs'] ?? '',
            'ubicacion'             => UBICACION_FIJA,
            'responsable'           => $f['prestado_a'] ?? '',
            'observaciones'         => trim(($f['estado_obs'] ?? '') . ($f['accesorios'] ? ' · Accesorios: ' . $f['accesorios'] : '')),
            'requiereMantenimiento' => (bool) $f['requiere_mantenimiento'],
            'ultimoMantenimiento'   => $f['ultimo_mantenimiento'],
            'estado'                => ((int) $f['activo']) === 1 ? 'activo' : 'baja',
            'motivoBaja'            => '',
            'bajaEn'                => null,
            'creadoPor'             => '',
            'creadoEn'              => $f['creado_en'],
            'origen'                => 'prestamo',
            'disponible'            => (bool) $f['disponible'],
        ] + $mant;
    }, db()->query($sql)->fetchAll());
}

function historial_mantenimiento(): void
{
    exigir(['id']);
    $id = param_int('id');

    $stmt = db()->prepare('
        SELECT fecha, realizado_por, observaciones, creado_en
        FROM equipo_oficina_mantenimientos
        WHERE equipo_id = ?
        ORDER BY fecha DESC, id DESC
    ');
    $stmt->execute([$id]);

    responder_json(['success' => true, 'historial' => $stmt->fetchAll()]);
}

// ==================================================================
// Alta, edición, baja
// ==================================================================

function crear(array $yo): void
{
    exigir(['codigo', 'tipo', 'nombre']);

    $codigo   = param('codigo');
    $tipo     = param('tipo');
    $nombre   = param('nombre');
    $cantidad = max(1, param_int('cantidad') ?: 1);

    if ($codigo === '' || $tipo === '' || $nombre === '') {
        responder_json(['success' => false, 'error' => 'Faltan el código, el tipo o el nombre'], 400);
    }

    // El mismo código puede representar muchas unidades iguales (cables,
    // mouses, folletos...) que no necesitan identificarse una por una. En
    // vez de rechazar el alta, se avisa y se ofrece sumar cantidad al
    // equipo que ya existe con ese código (ver accion=sumarCantidad).
    $stmt = db()->prepare('SELECT id, tipo, nombre, cantidad, estado FROM equipo_oficina WHERE codigo = ?');
    $stmt->execute([$codigo]);
    $existente = $stmt->fetch();

    if ($existente !== false) {
        responder_json([
            'success'   => false,
            'yaExiste'  => true,
            'error'     => $existente['estado'] === 'activo'
                ? "Ya existe el código {$codigo}: {$existente['nombre']} (cantidad actual: {$existente['cantidad']}). "
                  . 'Usá "Sumar cantidad" en vez de crear uno nuevo.'
                : "Ya existe el código {$codigo} pero está dado de baja: {$existente['nombre']}. Reactivalo si corresponde.",
            'existente' => [
                'id'       => (int) $existente['id'],
                'tipo'     => $existente['tipo'],
                'nombre'   => $existente['nombre'],
                'cantidad' => (int) $existente['cantidad'],
                'estado'   => $existente['estado'],
            ],
        ], 409);
    }

    $requiereMant = param('requiereMantenimiento') !== ''
        ? param('requiereMantenimiento') === '1'
        : requiere_mantenimiento_por_tipo($tipo);

    db()->prepare('
        INSERT INTO equipo_oficina
            (codigo, tipo, nombre, cantidad, condicion, marca, modelo, serie, ubicacion, responsable,
             observaciones, requiere_mantenimiento, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        $codigo, $tipo, $nombre, $cantidad, condicion_valida(param('condicion')),
        param('marca') ?: null, param('modelo') ?: null, param('serie') ?: null,
        UBICACION_FIJA, param('responsable') ?: null, param('observaciones') ?: null,
        $requiereMant ? 1 : 0, $yo['nombre'],
    ]);

    bitacora_apuntar('inventario_oficina', 'crear', "codigo={$codigo} nombre={$nombre} cantidad={$cantidad}");

    responder_json(['success' => true, 'id' => (int) db()->lastInsertId()]);
}

function sumar_cantidad(): void
{
    exigir(['id', 'cantidad']);
    $id       = param_int('id');
    $cantidad = param_int('cantidad');

    if ($cantidad < 1) {
        responder_json(['success' => false, 'error' => 'La cantidad a sumar debe ser al menos 1'], 400);
    }

    $stmt = db()->prepare("UPDATE equipo_oficina SET cantidad = cantidad + ? WHERE id = ? AND estado = 'activo'");
    $stmt->execute([$cantidad, $id]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'Ese equipo no existe o está dado de baja'], 404);
    }

    bitacora_apuntar('inventario_oficina', 'sumarCantidad', "id={$id} +{$cantidad}");

    responder_json(['success' => true]);
}

function actualizar(): void
{
    exigir(['id', 'codigo', 'tipo', 'nombre']);
    $id = param_int('id');

    $stmt = db()->prepare('SELECT id FROM equipo_oficina WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch() === false) {
        responder_json(['success' => false, 'error' => 'Ese equipo no existe'], 404);
    }

    $codigo = param('codigo');
    $tipo   = param('tipo');
    $nombre = param('nombre');

    if ($codigo === '' || $tipo === '' || $nombre === '') {
        responder_json(['success' => false, 'error' => 'Faltan el código, el tipo o el nombre'], 400);
    }

    $choque = db()->prepare('SELECT id FROM equipo_oficina WHERE codigo = ? AND id <> ?');
    $choque->execute([$codigo, $id]);
    if ($choque->fetch() !== false) {
        responder_json(['success' => false, 'error' => "Ya existe otro equipo con el código {$codigo}"], 409);
    }

    db()->prepare('
        UPDATE equipo_oficina SET
            codigo = ?, tipo = ?, nombre = ?, cantidad = ?, condicion = ?, marca = ?, modelo = ?, serie = ?,
            ubicacion = ?, responsable = ?, observaciones = ?, requiere_mantenimiento = ?
        WHERE id = ?
    ')->execute([
        $codigo, $tipo, $nombre, max(1, param_int('cantidad') ?: 1), condicion_valida(param('condicion')),
        param('marca') ?: null, param('modelo') ?: null, param('serie') ?: null,
        UBICACION_FIJA, param('responsable') ?: null, param('observaciones') ?: null,
        param('requiereMantenimiento') === '1' ? 1 : 0,
        $id,
    ]);

    bitacora_apuntar('inventario_oficina', 'actualizar', "id={$id} codigo={$codigo}");

    responder_json(['success' => true]);
}

function dar_de_baja(): void
{
    exigir(['id', 'motivo']);
    $id     = param_int('id');
    $motivo = param('motivo');

    if ($motivo === '') {
        responder_json(['success' => false, 'error' => 'Hace falta indicar el motivo de la baja'], 400);
    }

    $stmt = db()->prepare("
        UPDATE equipo_oficina SET estado = 'baja', motivo_baja = ?, baja_en = NOW()
        WHERE id = ? AND estado = 'activo'
    ");
    $stmt->execute([$motivo, $id]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'Ese equipo no existe o ya estaba de baja'], 404);
    }

    bitacora_apuntar('inventario_oficina', 'darDeBaja', "id={$id} motivo={$motivo}");

    responder_json(['success' => true]);
}

function reactivar(): void
{
    exigir(['id']);
    $id = param_int('id');

    $stmt = db()->prepare("
        UPDATE equipo_oficina SET estado = 'activo', motivo_baja = NULL, baja_en = NULL
        WHERE id = ? AND estado = 'baja'
    ");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        responder_json(['success' => false, 'error' => 'Ese equipo no existe o no estaba de baja'], 404);
    }

    bitacora_apuntar('inventario_oficina', 'reactivar', "id={$id}");

    responder_json(['success' => true]);
}

function registrar_mantenimiento(array $yo): void
{
    exigir(['id']);
    $id            = param_int('id');
    $fecha         = param('fecha') ?: date('Y-m-d');
    $observaciones = param('observaciones');

    $stmt = db()->prepare('SELECT id FROM equipo_oficina WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch() === false) {
        responder_json(['success' => false, 'error' => 'Ese equipo no existe'], 404);
    }

    db()->prepare('
        INSERT INTO equipo_oficina_mantenimientos (equipo_id, fecha, realizado_por, observaciones)
        VALUES (?, ?, ?, ?)
    ')->execute([$id, $fecha, $yo['nombre'], $observaciones ?: null]);

    // Se recalcula desde el historial (el máximo de las fechas), no se pisa
    // directo con $fecha: si alguna vez se anota un mantenimiento atrasado a
    // mano, la fecha "actual" sigue siendo la más reciente de verdad.
    db()->prepare('
        UPDATE equipo_oficina
        SET ultimo_mantenimiento = (
                SELECT MAX(fecha) FROM equipo_oficina_mantenimientos WHERE equipo_id = ?
            ),
            requiere_mantenimiento = 1
        WHERE id = ?
    ')->execute([$id, $id]);

    bitacora_apuntar('inventario_oficina', 'registrarMantenimiento', "id={$id} fecha={$fecha}");

    responder_json(['success' => true]);
}

// ==================================================================
// Plantilla y carga masiva
// ==================================================================

/**
 * Qué columna del archivo corresponde a cada campo. El motor de
 * reconocimiento (normalizar_encabezado(), en includes/tabla_archivo.php) es
 * genérico; lo que cambia módulo a módulo es esta tabla de alias.
 */
const COLUMNAS_EQUIPO = [
    'codigo'        => ['codigo', 'cod', 'etiqueta', 'numeroactivo', 'idactivo', 'placa', 'activo'],
    'tipo'          => ['tipo', 'categoria', 'clase', 'tipoequipo'],
    'nombre'        => ['nombre', 'descripcion', 'equipo', 'articulo', 'nombreequipo'],
    'cantidad'      => ['cantidad', 'cant', 'unidades', 'existencias'],
    'marca'         => ['marca'],
    'modelo'        => ['modelo'],
    'serie'         => ['serie', 'numeroserie', 'nserie', 'noserie'],
    'ubicacion'     => ['ubicacion', 'lugar', 'oficina', 'sitio'],
    'responsable'   => ['responsable', 'asignadoa', 'encargado', 'usuario'],
    'observaciones' => ['observaciones', 'obs', 'notas', 'comentarios'],
];

/** Sin esto no hay nada que dar de alta: ver crear(). */
const COLUMNAS_OBLIGATORIAS_EQUIPO = ['codigo', 'tipo', 'nombre'];

/** Igual que mapear_columnas() de includes/tabla_archivo.php, con el alias de este módulo. */
function mapear_columnas_equipo(array $columnas): array
{
    $porNormal = [];
    foreach ($columnas as $columna) {
        $normal = normalizar_encabezado((string) $columna);
        if ($normal !== '' && !isset($porNormal[$normal])) {
            $porNormal[$normal] = (string) $columna;
        }
    }

    $mapa = [];

    foreach (COLUMNAS_EQUIPO as $campo => $alias) {
        foreach ($alias as $a) {
            if (isset($porNormal[$a])) {
                $mapa[$campo] = $porNormal[$a];
                continue 2;
            }
        }
    }

    foreach (COLUMNAS_EQUIPO as $campo => $alias) {
        if (isset($mapa[$campo])) {
            continue;
        }
        foreach ($porNormal as $normal => $original) {
            if (in_array($original, $mapa, true)) {
                continue;
            }
            foreach ($alias as $a) {
                if (mb_strlen($a) >= 5 && str_contains($normal, $a)) {
                    $mapa[$campo] = $original;
                    continue 3;
                }
            }
        }
    }

    return $mapa;
}

/** El valor de una columna del archivo, para una fila y un campo mapeado. */
function celda(array $fila, array $mapa, string $campo): string
{
    return isset($mapa[$campo]) ? trim((string) ($fila[$mapa[$campo]] ?? '')) : '';
}

/** Los códigos que ya existen, con su estado — para saber qué es alta y qué es edición. */
function equipos_existentes_por_codigo(): array
{
    $mapa = [];
    foreach (db()->query('SELECT codigo, estado FROM equipo_oficina')->fetchAll() as $f) {
        $mapa[$f['codigo']] = $f['estado'];
    }
    return $mapa;
}

/** Traduce los errores de subida de PHP y entrega el archivo ya leído. */
function leer_subida_equipo(): array
{
    $archivo = $_FILES['archivo'] ?? null;
    if (!is_array($archivo)) {
        responder_json(['success' => false, 'error' => 'No llegó ningún archivo.'], 400);
    }

    $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $mensaje = match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'El archivo pesa más de lo que el servidor acepta.',
            UPLOAD_ERR_PARTIAL =>
                'La subida se cortó a la mitad. Probá de nuevo.',
            UPLOAD_ERR_NO_FILE =>
                'No se eligió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE =>
                'El servidor no pudo guardar el archivo temporal.',
            default => 'No se pudo recibir el archivo.',
        };
        responder_json(['success' => false, 'error' => $mensaje], 400);
    }

    if (!is_uploaded_file((string) $archivo['tmp_name'])) {
        responder_json(['success' => false, 'error' => 'El archivo no llegó por una subida válida.'], 400);
    }

    try {
        $lectura = leer_archivo_tabular((string) $archivo['tmp_name'], (string) $archivo['name']);
    } catch (RuntimeException $e) {
        responder_json(['success' => false, 'error' => $e->getMessage()], 422);
    }

    if ($lectura['filas'] === []) {
        responder_json([
            'success' => false,
            'error'   => 'El archivo no tiene ninguna fila de datos debajo de los encabezados.',
        ], 422);
    }

    return $lectura;
}

/** Descarga la plantilla en blanco, con una fila de ejemplo. */
function plantilla(): void
{
    $archivo = 'plantilla_inventario_oficina_' . date('Ymd') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Cache-Control: no-store');

    $columnas = ['Código', 'Tipo', 'Nombre', 'Cantidad', 'Marca', 'Modelo', 'Serie', 'Responsable', 'Observaciones'];
    $ejemplo  = [
        'EQ-001', 'Laptop', 'Laptop Dell Latitude 5420', '1', 'Dell', 'Latitude 5420',
        'ABC123XYZ', 'Karla Ejemplo Solís', 'Cargador incluido',
    ];

    echo "\xEF\xBB\xBF";
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>td { mso-number-format:"\@"; } th { background:#e8f0fe; font-weight:bold; }</style>';
    echo '</head><body>';
    echo '<p>Tipo sugerido: Computadora, Laptop, Monitor, Teclado, Mouse, Impresora, '
       . 'Lectora, Router/Switch, Cable u Otro — pero puede escribir cualquier otro texto.</p>';
    echo '<table border="1">';
    echo '<tr>' . implode('', array_map(
        static fn(string $c): string => '<th>' . htmlspecialchars($c) . '</th>', $columnas
    )) . '</tr>';
    echo '<tr>' . implode('', array_map(
        static fn(string $c): string => '<td>' . htmlspecialchars($c) . '</td>', $ejemplo
    )) . '</tr>';
    echo '</table></body></html>';
    exit;
}

/** Analiza el archivo subido y cuenta qué pasaría, sin escribir nada. */
function analizar_archivo(): void
{
    $subida = leer_subida_equipo();
    $mapa   = mapear_columnas_equipo($subida['columnas']);

    $faltantes = array_diff(COLUMNAS_OBLIGATORIAS_EQUIPO, array_keys($mapa));
    if ($faltantes !== []) {
        responder_json([
            'success' => false,
            'error'   => 'No se reconocieron estas columnas obligatorias: ' . implode(', ', $faltantes)
                       . '. Columnas del archivo: ' . implode(', ', $subida['columnas']),
        ], 422);
    }

    $existentes = equipos_existentes_por_codigo();

    $nuevos = 0; $actualizados = 0; $deBaja = 0; $sinDatos = 0; $repetidos = 0;
    $vistos = [];

    foreach ($subida['filas'] as $fila) {
        $codigo = celda($fila, $mapa, 'codigo');
        $tipo   = celda($fila, $mapa, 'tipo');
        $nombre = celda($fila, $mapa, 'nombre');

        if ($codigo === '' || $tipo === '' || $nombre === '') {
            $sinDatos++;
            continue;
        }
        if (isset($vistos[$codigo])) {
            $repetidos++;
            continue;
        }
        $vistos[$codigo] = true;

        if (isset($existentes[$codigo])) {
            if ($existentes[$codigo] === 'baja') {
                $deBaja++;
            } else {
                $actualizados++;
            }
        } else {
            $nuevos++;
        }
    }

    responder_json([
        'success'   => true,
        'formato'   => $subida['formato'],
        'columnas'  => $subida['columnas'],
        'mapa'      => $mapa,
        'ignoradas' => array_values(array_diff($subida['columnas'], array_values($mapa))),
        'conteos'   => [
            'filas'        => count($subida['filas']),
            'validas'      => $nuevos + $actualizados,
            'nuevos'       => $nuevos,
            'actualizados' => $actualizados,
            'deBaja'       => $deBaja,
            'sinDatos'     => $sinDatos,
            'repetidas'    => $repetidos,
        ],
        'muestra' => array_slice($subida['filas'], 0, 8),
    ]);
}

/**
 * Aplica la carga masiva de verdad. Vuelve a leer y a mapear el archivo desde
 * cero (no confía en lo que analizar_archivo() le mostró al navegador),
 * igual que el resto de las cargas masivas del sistema.
 */
function cargar_equipos(array $yo): void
{
    $subida = leer_subida_equipo();
    $mapa   = mapear_columnas_equipo($subida['columnas']);

    $faltantes = array_diff(COLUMNAS_OBLIGATORIAS_EQUIPO, array_keys($mapa));
    if ($faltantes !== []) {
        responder_json([
            'success' => false,
            'error'   => 'No se reconocieron estas columnas obligatorias: ' . implode(', ', $faltantes),
        ], 422);
    }

    $existentes = equipos_existentes_por_codigo();

    $nuevos = 0; $actualizados = 0; $descartados = 0;
    $vistos = [];

    db()->beginTransaction();

    try {
        $guardar = db()->prepare('
            INSERT INTO equipo_oficina
                (codigo, tipo, nombre, cantidad, marca, modelo, serie, ubicacion, responsable,
                 observaciones, requiere_mantenimiento, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                tipo = VALUES(tipo), nombre = VALUES(nombre), cantidad = VALUES(cantidad),
                marca = VALUES(marca), modelo = VALUES(modelo), serie = VALUES(serie),
                responsable = VALUES(responsable), observaciones = VALUES(observaciones)
        ');

        foreach ($subida['filas'] as $fila) {
            $codigo = celda($fila, $mapa, 'codigo');
            $tipo   = celda($fila, $mapa, 'tipo');
            $nombre = celda($fila, $mapa, 'nombre');

            if ($codigo === '' || $tipo === '' || $nombre === '' || isset($vistos[$codigo])) {
                $descartados++;
                continue;
            }

            // Un equipo dado de baja no se reactiva solo porque una planilla
            // vieja lo vuelva a traer: si hace falta, se reactiva a mano
            // desde la pantalla, a propósito.
            if (($existentes[$codigo] ?? null) === 'baja') {
                $descartados++;
                continue;
            }

            $vistos[$codigo] = true;

            $guardar->execute([
                $codigo, $tipo, $nombre,
                max(1, (int) (celda($fila, $mapa, 'cantidad') ?: '1')),
                celda($fila, $mapa, 'marca') ?: null,
                celda($fila, $mapa, 'modelo') ?: null,
                celda($fila, $mapa, 'serie') ?: null,
                UBICACION_FIJA,
                celda($fila, $mapa, 'responsable') ?: null,
                celda($fila, $mapa, 'observaciones') ?: null,
                requiere_mantenimiento_por_tipo($tipo) ? 1 : 0,
                $yo['nombre'],
            ]);

            if (isset($existentes[$codigo])) {
                $actualizados++;
            } else {
                $nuevos++;
            }
        }

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    bitacora_apuntar(
        'inventario_oficina', 'cargar',
        "nuevos={$nuevos} actualizados={$actualizados} descartados={$descartados}"
    );

    responder_json([
        'success'      => true,
        'mensaje'      => "Se cargaron {$nuevos} equipo(s) nuevo(s) y se actualizaron {$actualizados}."
                         . ($descartados > 0 ? " Se descartaron {$descartados} fila(s)." : ''),
        'nuevos'       => $nuevos,
        'actualizados' => $actualizados,
        'descartados'  => $descartados,
    ]);
}
