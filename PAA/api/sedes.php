<?php
/**
 * Endpoint del catálogo de sedes.
 *
 * Reemplaza la lectura del Sheet 171wt_c… que Sedes.html hacía a través de
 * opensheet.elk.sh (y de opensheet.vercel.app como respaldo). Además de ser
 * más rápido, quita del medio dos servicios de terceros por los que pasaban
 * los datos de contacto de cada centro educativo.
 *
 * Ya no es solo lectura: además del catálogo de contacto (que llenaba el
 * CSV) la tabla `sedes` carga columnas que solo entraban por la importación
 * anual (turno, coordinador, fechas de folletos, etc. — ver schema/017,
 * 024, 026 y 029). Nadie tenía dónde corregir un dato suelto sin entrar a
 * phpMyAdmin; `guardar()` es esa pantalla.
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/exportar_excel.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("sedes");

exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

// Columnas que se pueden tocar desde el formulario de edición. `id`,
// `convocatoria_id`, `creado_en` y `actualizado_en` quedan afuera a
// propósito: la convocatoria de una sede no se cambia editando texto, y las
// fechas de auditoría las pone la base sola.
const SEDES_CAMPOS_TEXTO = [
    'codigo', 'nombre', 'turno', 'numero_aplicacion',
    'coordinador_nombre', 'coordinador_correo', 'coordinador_cedula', 'coordinador_universidad',
    'director', 'contacto_conserje', 'telefono', 'correo', 'direccion',
    'provincia', 'canton', 'distrito',
    'coord_folleto_desde', 'coord_folleto_hasta',
    'coord_folleto_adec_desde', 'coord_folleto_adec_hasta',
    'estado_dotacion',
    'descripcion', 'imagen_url',
];
const SEDES_CAMPOS_BOOLEANOS = ['en_gam', 'coord_sin_folletos', 'materiales_adecuacion', 'activo'];

// Contadores de puestos que llegan del padrón externo (ver schema/039). Se
// guardan tal cual: recalcularlos con datos propios daría un número distinto
// al que usa ese sistema para decidir si una sede ya está completa.
const SEDES_CAMPOS_ENTEROS_DOTACION = [
    'total_puestos', 'minimo_una', 'aula_nombrados', 'apoyo_nombrados',
    'puestos_ocupados', 'puestos_libres', 'una_nombrados', 'una_faltan',
];

// Columnas del padrón externo que trae el Excel de dotación (ver
// procesar_dotacion). El orden importa: es el orden real de sus columnas.
const DOTACION_COLUMNAS = [
    'id_interno', 'sede', 'nombre_sede', 'fecha_examen', 'fecha_convocatoria',
    'convocatoria', 'turno', 'aulas', 'apoyo_requerido', 'total_puestos',
    'minimo_una', 'aula_nombrados', 'apoyo_nombrados', 'puestos_ocupados',
    'puestos_libres', 'una_nombrados', 'una_faltan', 'estado',
    'coordinador_nombre', 'coordinador_correo', 'coordinador_cedula',
    'coordinador_universidad', 'sedes_de_ese_coordinador',
    'fecha_importacion', 'ultima_actualizacion',
];

$accion = param('accion', 'listar');

try {
    switch ($accion) {
        case 'listar':           listar();               break;
        case 'guardar':          guardar($yo);           break;
        case 'exportar':         exportar();             break;
        case 'exportarJson':     exportar_json();        break;
        case 'analizarDotacion': analizar_dotacion();    break;
        case 'importarDotacion': importar_dotacion($yo); break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('sedes', $e, 'No se pudo procesar la solicitud');
}

// ------------------------------------------------------------------

function fila_sede_completa(array $f): array
{
    return [
        'id'                       => (int) $f['id'],
        'convocatoria_id'          => (int) $f['convocatoria_id'],
        'codigo'                   => $f['codigo'],
        'codigo_externo'           => $f['codigo_externo'] !== null ? (int) $f['codigo_externo'] : null,
        'nombre'                   => $f['nombre'],
        'turno'                    => $f['turno'] ?? '',
        'numero_aplicacion'        => $f['numero_aplicacion'] ?? '',
        'fecha_examen'             => $f['fecha_examen'],
        'aulas_previstas'          => (int) $f['aulas_previstas'],
        'apoyo_requerido'          => (int) $f['apoyo_requerido'],
        'total_puestos'            => (int) $f['total_puestos'],
        'minimo_una'               => (int) $f['minimo_una'],
        'aula_nombrados'           => (int) $f['aula_nombrados'],
        'apoyo_nombrados'          => (int) $f['apoyo_nombrados'],
        'puestos_ocupados'         => (int) $f['puestos_ocupados'],
        'puestos_libres'           => (int) $f['puestos_libres'],
        'una_nombrados'            => (int) $f['una_nombrados'],
        'una_faltan'               => (int) $f['una_faltan'],
        'estado_dotacion'          => $f['estado_dotacion'] ?? '',
        'coordinador_nombre'       => $f['coordinador_nombre'] ?? '',
        'coordinador_correo'       => $f['coordinador_correo'] ?? '',
        'coordinador_cedula'       => $f['coordinador_cedula'] ?? '',
        'coordinador_universidad'  => $f['coordinador_universidad'] ?? '',
        'director'                 => $f['director'] ?? '',
        'contacto_conserje'        => $f['contacto_conserje'] ?? '',
        'telefono'                 => $f['telefono'] ?? '',
        'correo'                   => $f['correo'] ?? '',
        'direccion'                => $f['direccion'] ?? '',
        'provincia'                => $f['provincia'] ?? '',
        'canton'                   => $f['canton'] ?? '',
        'distrito'                 => $f['distrito'] ?? '',
        'en_gam'                   => (bool) $f['en_gam'],
        'coord_folleto_desde'      => $f['coord_folleto_desde'] ?? '',
        'coord_folleto_hasta'      => $f['coord_folleto_hasta'] ?? '',
        'coord_folleto_adec_desde' => $f['coord_folleto_adec_desde'] ?? '',
        'coord_folleto_adec_hasta' => $f['coord_folleto_adec_hasta'] ?? '',
        'coord_sin_folletos'       => (bool) $f['coord_sin_folletos'],
        // Fórmulas de examen distintas entre las aulas de la sede (columna
        // `aulas.formula`, cargada con la importación anual) — de solo
        // lectura acá: se edita aula por aula desde Ingreso de Datos.
        'formulas'                 => $f['formulas'] ?? '',
        'materiales_adecuacion'    => (bool) $f['materiales_adecuacion'],
        'descripcion'              => $f['descripcion'] ?? '',
        'imagen_url'               => $f['imagen_url'] ?? '',
        'activo'                   => (bool) $f['activo'],
        'actualizado_en'           => $f['actualizado_en'],
    ];
}

/** Todas las sedes de la convocatoria actual (o la indicada), con todas sus columnas. */
function listar(): void
{
    $busqueda = param('q');

    $convocatoriaId = param_int('convocatoria_id') ?: convocatoria_actual();

    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay ninguna convocatoria activa'], 409);
    }

    // Por defecto solo las activas: la pantalla es para gestionar la
    // convocatoria en curso, no un archivo de sedes dadas de baja.
    $incluirInactivas = param('incluir_inactivas') === '1';

    $sql = "SELECT *,
        (SELECT GROUP_CONCAT(DISTINCT NULLIF(a.formula, '') SEPARATOR ', ')
           FROM aulas a WHERE a.sede_id = sedes.id AND a.convocatoria_id = sedes.convocatoria_id) AS formulas
        FROM sedes WHERE convocatoria_id = :conv";
    $parametros = ['conv' => $convocatoriaId];

    if (!$incluirInactivas) {
        $sql .= " AND activo = 1";
    }

    if ($busqueda !== '') {
        $sql .= " AND (
            CONCAT_WS(' ', nombre, direccion, director, codigo, coordinador_nombre, turno) LIKE :q
            OR EXISTS (
                SELECT 1 FROM aulas a2
                WHERE a2.sede_id = sedes.id AND a2.convocatoria_id = sedes.convocatoria_id
                  AND a2.formula LIKE :q2
            )
        )";
        $parametros['q']  = '%' . $busqueda . '%';
        $parametros['q2'] = '%' . $busqueda . '%';
    }

    $sql .= " ORDER BY nombre";

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    responder_json([
        'success'     => true,
        'sedes'       => array_map('fila_sede_completa', $filas),
        'total'       => count($filas),
        'actualizado' => date('c'),
    ]);
}

/** Guarda los cambios de una sede existente. No crea sedes nuevas. */
function guardar(array $yo): void
{
    exigir(['id', 'codigo', 'nombre']);

    $id = param_int('id');

    $stmt = db()->prepare('SELECT id FROM sedes WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() === false) {
        responder_json(['success' => false, 'error' => 'Esa sede ya no existe'], 404);
    }

    $codigo = trim(param('codigo'));
    $nombre = trim(param('nombre'));

    if ($codigo === '' || $nombre === '') {
        responder_json(['success' => false, 'error' => 'El código y el nombre no pueden quedar vacíos'], 422);
    }

    $valores = ['codigo' => $codigo, 'nombre' => $nombre];

    foreach (SEDES_CAMPOS_TEXTO as $campo) {
        if ($campo === 'codigo' || $campo === 'nombre') {
            continue;
        }
        $valor = trim(param($campo));
        $valores[$campo] = $valor !== '' ? $valor : null;
    }

    foreach (SEDES_CAMPOS_BOOLEANOS as $campo) {
        $valores[$campo] = param($campo) === '1' ? 1 : 0;
    }

    $codigoExterno = trim(param('codigo_externo'));
    $valores['codigo_externo'] = $codigoExterno !== '' ? (int) $codigoExterno : null;

    $fechaExamen = trim(param('fecha_examen'));
    if ($fechaExamen !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaExamen)) {
        responder_json(['success' => false, 'error' => 'La fecha de examen no tiene un formato válido'], 422);
    }
    $valores['fecha_examen'] = $fechaExamen !== '' ? $fechaExamen : null;

    $valores['aulas_previstas'] = max(0, param_int('aulas_previstas'));
    $valores['apoyo_requerido'] = max(0, param_int('apoyo_requerido'));

    foreach (SEDES_CAMPOS_ENTEROS_DOTACION as $campo) {
        $valores[$campo] = max(0, param_int($campo));
    }

    $columnas = array_merge(SEDES_CAMPOS_TEXTO, SEDES_CAMPOS_BOOLEANOS, SEDES_CAMPOS_ENTEROS_DOTACION, [
        'codigo_externo', 'fecha_examen', 'aulas_previstas', 'apoyo_requerido',
    ]);

    $set = implode(', ', array_map(static fn(string $c): string => "{$c} = :{$c}", $columnas));

    try {
        $stmt = db()->prepare("UPDATE sedes SET {$set} WHERE id = :id");
        $stmt->execute($valores + ['id' => $id]);
    } catch (PDOException $e) {
        // uq_sede_convocatoria (convocatoria_id, codigo)
        if ((int) $e->getCode() === 23000 || str_contains($e->getMessage(), 'uq_sede_convocatoria')) {
            responder_json([
                'success' => false,
                'error'   => "Ya existe otra sede con el código «{$codigo}» en esta convocatoria",
            ], 409);
        }
        throw $e;
    }

    $stmt = db()->prepare('SELECT * FROM sedes WHERE id = ?');
    $stmt->execute([$id]);

    responder_json(['success' => true, 'sede' => fila_sede_completa($stmt->fetch())]);
}

function exportar(): void
{
    $convocatoriaId = param_int('convocatoria_id') ?: convocatoria_actual();

    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay ninguna convocatoria activa'], 409);
    }

    $stmt = db()->prepare('SELECT * FROM sedes WHERE convocatoria_id = ? ORDER BY nombre');
    $stmt->execute([$convocatoriaId]);

    $columnas = [
        'Código', 'Código externo', 'Nombre', 'Turno', 'N.º aplicación', 'Fecha examen',
        'Aulas previstas', 'Apoyo requerido',
        'Total puestos', 'Mínimo una', 'Aula nombrados', 'Apoyo nombrados',
        'Puestos ocupados', 'Puestos libres', 'Una nombrados', 'Una faltan', 'Estado dotación',
        'Coordinador', 'Correo coordinador', 'Cédula coordinador', 'Universidad coordinador',
        'Director', 'Contacto conserje', 'Teléfono', 'Correo', 'Dirección',
        'Provincia', 'Cantón', 'Distrito', 'GAM',
        'Folleto coord. desde', 'Folleto coord. hasta',
        'Folleto adec. desde', 'Folleto adec. hasta', 'Sin folleto coord.',
        'Materiales adecuación', 'Activa',
    ];

    $filas = array_map(static function (array $f) use ($columnas): array {
        $valores = [
            $f['codigo'], $f['codigo_externo'] ?? '', $f['nombre'], $f['turno'] ?? '',
            $f['numero_aplicacion'] ?? '', $f['fecha_examen'] ?? '',
            (int) $f['aulas_previstas'], (int) $f['apoyo_requerido'],
            (int) $f['total_puestos'], (int) $f['minimo_una'], (int) $f['aula_nombrados'], (int) $f['apoyo_nombrados'],
            (int) $f['puestos_ocupados'], (int) $f['puestos_libres'], (int) $f['una_nombrados'], (int) $f['una_faltan'],
            $f['estado_dotacion'] ?? '',
            $f['coordinador_nombre'] ?? '', $f['coordinador_correo'] ?? '',
            $f['coordinador_cedula'] ?? '', $f['coordinador_universidad'] ?? '',
            $f['director'] ?? '', $f['contacto_conserje'] ?? '', $f['telefono'] ?? '',
            $f['correo'] ?? '', $f['direccion'] ?? '',
            $f['provincia'] ?? '', $f['canton'] ?? '', $f['distrito'] ?? '',
            $f['en_gam'] ? 'Sí' : 'No',
            $f['coord_folleto_desde'] ?? '', $f['coord_folleto_hasta'] ?? '',
            $f['coord_folleto_adec_desde'] ?? '', $f['coord_folleto_adec_hasta'] ?? '',
            $f['coord_sin_folletos'] ? 'Sí' : 'No',
            $f['materiales_adecuacion'] ? 'Sí' : 'No',
            $f['activo'] ? 'Sí' : 'No',
        ];
        return array_combine($columnas, $valores);
    }, $stmt->fetchAll());

    exportar_excel_html('sedes', $columnas, $filas);
}

/**
 * Exporta el mismo JSON que trae la importación anual (ver api/importacion.php),
 * pero armado con lo que hoy tiene el sistema.
 *
 * PARA QUÉ SIRVE
 * Es la vuelta de la importación: ese archivo entra desde el sistema de
 * coordinaciones, pero acá adentro los datos se corrigen (una sede que cambió
 * de turno, un coordinador que cambió de correo desde Sedes, aulas de más que
 * se agregaron desde Ingreso de Datos). Este export deja eso en el mismo
 * formato que `analizar`/`importar` esperan, para llevarlo de vuelta al otro
 * sistema o para tenerlo como respaldo del año.
 */
function exportar_json(): void
{
    $convocatoriaId = param_int('convocatoria_id') ?: convocatoria_actual();

    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay ninguna convocatoria activa'], 409);
    }

    $stmt = db()->prepare('
        SELECT s.*, (
            SELECT COUNT(*) FROM aulas a WHERE a.sede_id = s.id AND a.convocatoria_id = s.convocatoria_id
        ) AS aulas_reales
        FROM sedes s WHERE s.convocatoria_id = ? ORDER BY s.nombre
    ');
    $stmt->execute([$convocatoriaId]);

    $sedes = array_map(static function (array $f): array {
        return [
            'sede_id'             => $f['codigo_externo'] !== null ? (int) $f['codigo_externo'] : null,
            'sede_examen'         => $f['codigo'],
            'descripcion'         => $f['nombre'],
            'turno'               => $f['turno'] ?? '',
            'codigo_convocatoria' => $f['numero_aplicacion'] ?? '',
            'fecha_examen'        => $f['fecha_examen'] ?? '',
            // Lo real capturado gana sobre lo previsto: si ya se agregaron
            // aulas de más desde Ingreso de Datos, el archivo debe reflejarlas
            // o la próxima importación las volvería a proponer como nuevas.
            'cantidad_aulas'      => max((int) $f['aulas_previstas'], (int) $f['aulas_reales']),
            'apoyo_requerido'     => (int) $f['apoyo_requerido'],
            'coordinador_nombre'  => $f['coordinador_nombre'] ?? '',
            'coordinador_correo'  => $f['coordinador_correo'] ?? '',
        ];
    }, $stmt->fetchAll());

    $personas = array_map(static function (array $p): array {
        $si = static fn($v): string => ((int) $v === 1) ? 'si' : 'no';
        return [
            'correo'              => $p['correo'],
            'nombre'              => $p['nombre'],
            'cedula'              => $p['cedula'] ?? '',
            'universidad'         => $p['universidad'] ?? '',
            'aprobo_coordinacion' => $si($p['aprobo_coordinacion']),
            'aprobo_apoyo'        => $si($p['aprobo_apoyo']),
            'aprobo_aula'         => $si($p['aprobo_aula']),
            'rol_actual'          => $p['rol_actual'] ?? '',
        ];
    }, db()->query('SELECT * FROM padron_personas ORDER BY nombre')->fetchAll());

    $stmt = db()->prepare('
        SELECT s.codigo AS sede_examen, pp.correo, aa.tipo, aa.numero_puesto
        FROM asignaciones_aplicadores aa
        INNER JOIN sedes s ON s.id = aa.sede_id
        INNER JOIN padron_personas pp ON pp.id = aa.padron_id
        WHERE aa.convocatoria_id = ?
        ORDER BY s.codigo, aa.tipo, aa.numero_puesto
    ');
    $stmt->execute([$convocatoriaId]);

    $aplicadores = array_map(static fn(array $a): array => [
        'sede_examen'   => $a['sede_examen'],
        'correo'        => $a['correo'],
        'tipo'          => $a['tipo'],
        'numero_puesto' => (int) $a['numero_puesto'],
    ], $stmt->fetchAll());

    $datos = [
        'sedes'       => $sedes,
        'personas'    => $personas,
        'aplicadores' => $aplicadores,
        'resumen'     => [
            ['concepto' => 'generado',      'valor' => date('Y-m-d H:i')],
            ['concepto' => 'origen',        'valor' => 'Gestión PAA (exportación desde Sedes)'],
            ['concepto' => 'sedes',         'valor' => (string) count($sedes)],
            ['concepto' => 'personas',      'valor' => (string) count($personas)],
            ['concepto' => 'asignaciones',  'valor' => (string) count($aplicadores)],
        ],
    ];

    $nombre = 'sedes_' . date('Y-m-d') . '.json';

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ------------------------------------------------------------------
// Actualización desde el Excel del padrón externo
// ------------------------------------------------------------------

/**
 * Analiza (sin escribir) o aplica el Excel de dotación de sedes.
 *
 * El archivo es el mismo truco de "tabla HTML con extensión .xls" que usa
 * exportar_excel_html(), así que se lee con una búsqueda de <tr>/<td> y no
 * con una librería de Excel real (acá no hay Composer). Solo ACTUALIZA
 * sedes que ya existen (matcheadas por código dentro de la convocatoria
 * actual): nunca inserta ni borra. Un código del archivo que no calza con
 * ninguna sede se reporta como huérfano y no se toca.
 *
 * @return array{convocatoriaId:int, total:int, encontradas:int, conCambios:int,
 *               sinCambios:int, huerfanas:string[], cambios:array}
 */
function procesar_dotacion(string $rutaArchivo, bool $aplicar): array
{
    $convId = convocatoria_actual();
    if ($convId === null) {
        responder_json(['success' => false, 'error' => 'No hay ninguna convocatoria activa'], 409);
    }

    $html = file_get_contents($rutaArchivo);
    if ($html === false) {
        responder_json(['success' => false, 'error' => 'No se pudo leer el archivo subido'], 400);
    }
    $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $filasMatch);
    $filas = $filasMatch[1];

    if (count($filas) < 2) {
        responder_json(['success' => false, 'error' => 'El archivo no tiene filas de datos (¿es el Excel correcto?)'], 422);
    }

    $celdas = static function (string $filaHtml): array {
        preg_match_all('/<t[hd][^>]*>(.*?)<\/t[hd]>/si', $filaHtml, $m);
        return array_map(static function (string $c): string {
            $c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(strip_tags($c));
        }, $m[1]);
    };

    $encabezado = $celdas($filas[0]);
    if (count($encabezado) !== count(DOTACION_COLUMNAS)) {
        responder_json([
            'success' => false,
            'error'   => 'El archivo tiene ' . count($encabezado) . ' columnas y se esperaban '
                       . count(DOTACION_COLUMNAS) . '. ¿Es el Excel de dotación de sedes?',
        ], 422);
    }

    $buscar = db()->prepare('SELECT * FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $actualizar = db()->prepare('
        UPDATE sedes SET
            codigo_externo = ?, turno = ?, fecha_examen = ?,
            aulas_previstas = ?, apoyo_requerido = ?,
            total_puestos = ?, minimo_una = ?, aula_nombrados = ?, apoyo_nombrados = ?,
            puestos_ocupados = ?, puestos_libres = ?, una_nombrados = ?, una_faltan = ?,
            estado_dotacion = ?,
            coordinador_nombre = ?, coordinador_correo = ?, coordinador_cedula = ?, coordinador_universidad = ?
        WHERE id = ?
    ');

    $encontradas = 0;
    $huerfanas = [];
    $sinCambios = 0;
    $cambios = [];

    if ($aplicar) {
        db()->beginTransaction();
    }

    for ($i = 1; $i < count($filas); $i++) {
        $c = $celdas($filas[$i]);
        if (count($c) < count(DOTACION_COLUMNAS)) {
            continue;
        }
        $fila = array_combine(DOTACION_COLUMNAS, array_slice($c, 0, count(DOTACION_COLUMNAS)));

        $codigo = trim($fila['sede']);
        if ($codigo === '') {
            continue;
        }

        $buscar->execute([$convId, $codigo]);
        $sedeActual = $buscar->fetch();

        if (!$sedeActual) {
            $huerfanas[] = "{$codigo} ({$fila['nombre_sede']})";
            continue;
        }

        $encontradas++;

        $fechaExamen = trim($fila['fecha_examen']);
        if ($fechaExamen !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaExamen)) {
            $fechaExamen = '';
        }

        $nuevos = [
            'codigo_externo'          => $fila['id_interno'] !== '' ? (int) $fila['id_interno'] : null,
            'turno'                   => $fila['turno'] !== '' ? $fila['turno'] : null,
            'fecha_examen'            => $fechaExamen !== '' ? substr($fechaExamen, 0, 10) : null,
            'aulas_previstas'         => (int) $fila['aulas'],
            'apoyo_requerido'         => (int) $fila['apoyo_requerido'],
            'total_puestos'           => (int) $fila['total_puestos'],
            'minimo_una'              => (int) $fila['minimo_una'],
            'aula_nombrados'          => (int) $fila['aula_nombrados'],
            'apoyo_nombrados'         => (int) $fila['apoyo_nombrados'],
            'puestos_ocupados'        => (int) $fila['puestos_ocupados'],
            'puestos_libres'          => (int) $fila['puestos_libres'],
            'una_nombrados'           => (int) $fila['una_nombrados'],
            'una_faltan'              => (int) $fila['una_faltan'],
            'estado_dotacion'         => $fila['estado'] !== '' ? $fila['estado'] : null,
            'coordinador_nombre'      => $fila['coordinador_nombre'] !== '' ? $fila['coordinador_nombre'] : null,
            'coordinador_correo'      => $fila['coordinador_correo'] !== '' ? strtolower($fila['coordinador_correo']) : null,
            'coordinador_cedula'      => $fila['coordinador_cedula'] !== '' ? $fila['coordinador_cedula'] : null,
            'coordinador_universidad' => $fila['coordinador_universidad'] !== '' ? $fila['coordinador_universidad'] : null,
        ];

        $diff = [];
        foreach ($nuevos as $campo => $valor) {
            if ((string) $sedeActual[$campo] !== (string) $valor) {
                $diff[$campo] = ['antes' => $sedeActual[$campo], 'despues' => $valor];
            }
        }

        if ($diff === []) {
            $sinCambios++;
            continue;
        }

        $cambios[] = ['codigo' => $codigo, 'nombre' => $sedeActual['nombre'], 'diff' => $diff];

        if ($aplicar) {
            $actualizar->execute([
                $nuevos['codigo_externo'], $nuevos['turno'], $nuevos['fecha_examen'],
                $nuevos['aulas_previstas'], $nuevos['apoyo_requerido'],
                $nuevos['total_puestos'], $nuevos['minimo_una'], $nuevos['aula_nombrados'], $nuevos['apoyo_nombrados'],
                $nuevos['puestos_ocupados'], $nuevos['puestos_libres'], $nuevos['una_nombrados'], $nuevos['una_faltan'],
                $nuevos['estado_dotacion'],
                $nuevos['coordinador_nombre'], $nuevos['coordinador_correo'],
                $nuevos['coordinador_cedula'], $nuevos['coordinador_universidad'],
                $sedeActual['id'],
            ]);
        }
    }

    if ($aplicar) {
        db()->commit();
    }

    return [
        'convocatoriaId' => $convId,
        'total'          => count($filas) - 1,
        'encontradas'    => $encontradas,
        'conCambios'     => count($cambios),
        'sinCambios'     => $sinCambios,
        'huerfanas'      => $huerfanas,
        'cambios'        => $cambios,
    ];
}

function archivo_subido(): string
{
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        responder_json(['success' => false, 'error' => 'No se recibió ningún archivo'], 400);
    }
    return $_FILES['archivo']['tmp_name'];
}

/** Solo dice qué cambiaría. No escribe nada. */
function analizar_dotacion(): void
{
    $resultado = procesar_dotacion(archivo_subido(), false);
    responder_json(['success' => true] + $resultado);
}

/** Aplica lo mismo que analizarDotacion ya mostró. */
function importar_dotacion(array $yo): void
{
    $resultado = procesar_dotacion(archivo_subido(), true);
    responder_json(['success' => true] + $resultado);
}
