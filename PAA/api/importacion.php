<?php
/**
 * Importación anual y borrado de convocatorias.
 *
 *   accion=convocatorias  → las que existen, con cuántos datos tiene cada una
 *   accion=analizar       → lee el JSON y dice qué haría, SIN escribir nada
 *   accion=importar       → lo aplica
 *   accion=borrarAnio     → elimina una convocatoria entera
 *   accion=historial      → importaciones anteriores
 *
 * REGLA DE FONDO: NUNCA SE PISAN DATOS DE EMBALAJE
 * El JSON dice cuántas aulas tiene cada sede, pero no cuántos estudiantes van
 * en cada una ni qué folletos les tocan: eso lo captura el personal desde
 * Ingreso de Datos y vale semanas de trabajo. Por eso las aulas solo se CREAN
 * cuando faltan, nunca se actualizan ni se borran, y el importador reporta las
 * diferencias en vez de resolverlas por su cuenta.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo_api("importacion");

exigir_metodo(['GET', 'POST']);

/**
 * Tope del archivo.
 *
 * El de 2026 pesa 545 KB con 317 sedes y 875 personas. 8 MB deja margen de
 * sobra y corta a tiempo si alguien sube por error algo que no es esto.
 */
const MAX_BYTES_JSON = 8 * 1024 * 1024;

$accion = param('accion', param('action', 'convocatorias'));

try {
    switch ($accion) {
        case 'convocatorias': listar_convocatorias(); break;
        case 'analizar':      analizar();             break;
        case 'importar':      importar($yo);          break;
        case 'renombrar':     renombrar();            break;
        case 'activar':       activar();              break;
        case 'borrarAnio':    borrar_anio($yo);       break;
        case 'historial':     historial();            break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('importacion', $e);
}

// ------------------------------------------------------------------
// Convocatorias
// ------------------------------------------------------------------

/**
 * Las convocatorias con el volumen de datos que cuelga de cada una.
 *
 * Los conteos son lo que se va a perder al borrar: mostrarlos ANTES es lo que
 * convierte «borrar 2025» en una decisión informada.
 */
function listar_convocatorias(): void
{
    $filas = db()->query("
        SELECT c.id, c.nombre, c.fecha_aplicacion, c.activa,
               (SELECT COUNT(*) FROM sedes s WHERE s.convocatoria_id = c.id) AS sedes,
               (SELECT COUNT(*) FROM aulas a WHERE a.convocatoria_id = c.id) AS aulas,
               (SELECT COUNT(*) FROM tulas t WHERE t.convocatoria_id = c.id) AS tulas,
               (SELECT COUNT(*) FROM asignaciones_aplicadores x
                 WHERE x.convocatoria_id = c.id) AS asignaciones
        FROM convocatorias c
        ORDER BY c.nombre DESC
    ")->fetchAll();

    responder_json([
        'success'       => true,
        'convocatorias' => array_map(static fn(array $c): array => [
            'id'           => (int) $c['id'],
            'nombre'       => $c['nombre'],
            'fecha'        => $c['fecha_aplicacion'],
            'activa'       => (int) $c['activa'] === 1,
            'sedes'        => (int) $c['sedes'],
            'aulas'        => (int) $c['aulas'],
            'tulas'        => (int) $c['tulas'],
            'asignaciones' => (int) $c['asignaciones'],
        ], $filas),
        'padron' => (int) db()->query('SELECT COUNT(*) FROM padron_personas')->fetchColumn(),
    ]);
}

/**
 * Cambia el nombre de una convocatoria.
 *
 * POR QUÉ HACE FALTA PODER HACERLO
 * El nombre NO es una etiqueta: es con lo que el importador reconoce el año.
 * Deduce «2026» de las fechas del archivo y busca una convocatoria que se
 * llame así. Si la que ya tiene las sedes se llama «PAA 2026» o «Convocatoria
 * 2026», no la encuentra, crea otra, y termina con las sedes del mismo año
 * repartidas en dos convocatorias — cada módulo mostrando la mitad.
 *
 * Renombrarla es lo que evita eso, y es más seguro que mover 317 sedes después.
 */
function renombrar(): void
{
    exigir_metodo(['POST']);

    if (!csrf_valido(param('csrf'))) {
        responder_json(['success' => false, 'error' => 'Ficha de seguridad vencida. Recargá la página.'], 403);
    }

    $id     = param_int('convocatoria');
    $nombre = trim(param('nombre'));

    if ($nombre === '') {
        responder_json(['success' => false, 'error' => 'El nombre no puede quedar vacío'], 400);
    }

    if (mb_strlen($nombre) > 100) {
        responder_json(['success' => false, 'error' => 'El nombre es demasiado largo'], 400);
    }

    $stmt = db()->prepare('SELECT nombre FROM convocatorias WHERE id = ?');
    $stmt->execute([$id]);
    $anterior = $stmt->fetchColumn();

    if ($anterior === false) {
        responder_json(['success' => false, 'error' => 'Esa convocatoria no existe'], 404);
    }

    // Dos convocatorias con el mismo nombre dejarían al importador sin saber
    // en cuál de las dos escribir.
    $stmt = db()->prepare('SELECT id FROM convocatorias WHERE nombre = ? AND id <> ?');
    $stmt->execute([$nombre, $id]);

    if ($stmt->fetchColumn() !== false) {
        responder_json([
            'success' => false,
            'error'   => "Ya hay otra convocatoria llamada «{$nombre}».",
        ], 409);
    }

    db()->prepare('UPDATE convocatorias SET nombre = ? WHERE id = ?')->execute([$nombre, $id]);

    responder_json([
        'success' => true,
        'mensaje' => "«{$anterior}» ahora se llama «{$nombre}».",
    ]);
}

/**
 * Marca cuál es la convocatoria activa.
 *
 * Es la que abren por defecto todos los módulos. Solo puede haber una, así que
 * primero se apagan todas y después se prende la elegida: hacerlo al revés
 * dejaría dos activas por un instante, y cualquier consulta que cayera en ese
 * momento tomaría la que le tocara.
 */
function activar(): void
{
    exigir_metodo(['POST']);

    if (!csrf_valido(param('csrf'))) {
        responder_json(['success' => false, 'error' => 'Ficha de seguridad vencida. Recargá la página.'], 403);
    }

    $id = param_int('convocatoria');

    $stmt = db()->prepare('SELECT nombre FROM convocatorias WHERE id = ?');
    $stmt->execute([$id]);
    $nombre = $stmt->fetchColumn();

    if ($nombre === false) {
        responder_json(['success' => false, 'error' => 'Esa convocatoria no existe'], 404);
    }

    db()->beginTransaction();

    try {
        db()->query('UPDATE convocatorias SET activa = 0');
        db()->prepare('UPDATE convocatorias SET activa = 1 WHERE id = ?')->execute([$id]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    responder_json([
        'success' => true,
        'mensaje' => "«{$nombre}» quedó como la convocatoria activa. Todos los módulos "
                   . 'trabajan ahora sobre ese año.',
    ]);
}

// ------------------------------------------------------------------
// Lectura del archivo
// ------------------------------------------------------------------

/**
 * Toma el JSON de la petición y comprueba que sea lo que dice ser.
 *
 * Llega como texto en el cuerpo y no como subida de archivo: así no depende de
 * los límites de upload de PHP en el servidor, que no controlamos.
 */
function leer_json(): array
{
    $crudo = (string) (entrada()['contenido'] ?? '');

    if (trim($crudo) === '') {
        responder_json(['success' => false, 'error' => 'No llegó ningún archivo'], 400);
    }

    if (strlen($crudo) > MAX_BYTES_JSON) {
        responder_json([
            'success' => false,
            'error'   => 'El archivo pesa más de ' . (MAX_BYTES_JSON / 1024 / 1024) . ' MB. ¿Es el archivo correcto?',
        ], 413);
    }

    $datos = json_decode($crudo, true);

    if (!is_array($datos)) {
        responder_json([
            'success' => false,
            'error'   => 'El archivo no es un JSON válido: ' . json_last_error_msg(),
        ], 422);
    }

    if (!isset($datos['sedes']) || !is_array($datos['sedes']) || $datos['sedes'] === []) {
        responder_json([
            'success' => false,
            'error'   => 'El archivo no trae el bloque «sedes». ¿Es la exportación completa del sistema de coordinaciones?',
        ], 422);
    }

    return $datos;
}

/**
 * El año al que pertenece el archivo, sacado de las fechas de examen.
 *
 * Se deduce y no se pregunta porque teclear el año a mano es exactamente el
 * error que dejaría las sedes de un año encima de las de otro. Si el archivo
 * mezclara años, se avisa y no se adivina.
 */
function anios_del_archivo(array $sedes): array
{
    $anios = [];

    foreach ($sedes as $s) {
        $fecha = (string) ($s['fecha_examen'] ?? '');
        if (preg_match('/^(\d{4})-/', $fecha, $m) === 1) {
            $anios[$m[1]] = ($anios[$m[1]] ?? 0) + 1;
        }
    }

    krsort($anios);

    return $anios;
}

// ------------------------------------------------------------------
// Saneo de cada bloque del archivo
// ------------------------------------------------------------------
//
// Estas tres funciones son la ÚNICA sanitización de sede/persona/asignación
// del archivo. analizar() las usa para comparar contra lo que hay, e
// importar_sedes()/importar_padron()/importar_asignaciones() las usan para
// escribir. Si vivieran duplicadas, un cambio en una y no en la otra haría
// que el análisis mostrara un cambio que después no pasa (o al revés).

/** Los campos de una sede tal como quedarían en la base. */
function campos_sede_desde_json(array $s, string $codigo): array
{
    return [
        'codigo_externo'    => $s['sede_id'] ?? null,
        'nombre'            => mb_substr(trim((string) ($s['descripcion'] ?? $codigo)), 0, 200),
        'turno'             => mb_substr(trim((string) ($s['turno'] ?? '')), 0, 50) ?: null,
        'numero_aplicacion' => mb_substr(trim((string) ($s['codigo_convocatoria'] ?? '')), 0, 10) ?: null,
        'fecha_examen'      => $s['fecha_examen'] ?? null,
        'aulas_previstas'   => (int) ($s['cantidad_aulas'] ?? 0),
        'apoyo_requerido'   => (int) ($s['apoyo_requerido'] ?? 0),
        'coordinador_nombre' => mb_substr(trim((string) ($s['coordinador_nombre'] ?? '')), 0, 150) ?: null,
        'coordinador_correo' => mb_substr(mb_strtolower(trim((string) ($s['coordinador_correo'] ?? ''))), 0, 150) ?: null,
    ];
}

/** Los campos de una persona del padrón, o null si el correo no sirve para identificarla. */
function campos_persona_desde_json(array $p): ?array
{
    $correo = mb_strtolower(trim((string) ($p['correo'] ?? '')));

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $si = static fn($v): int => mb_strtolower(trim((string) $v)) === 'si' ? 1 : 0;

    return [
        'correo'              => $correo,
        'nombre'              => mb_substr(trim((string) ($p['nombre'] ?? $correo)), 0, 150),
        'cedula'              => mb_substr(trim((string) ($p['cedula'] ?? '')), 0, 30) ?: null,
        'universidad'         => mb_substr(trim((string) ($p['universidad'] ?? '')), 0, 20) ?: null,
        'aprobo_coordinacion' => $si($p['aprobo_coordinacion'] ?? ''),
        'aprobo_apoyo'        => $si($p['aprobo_apoyo'] ?? ''),
        'aprobo_aula'         => $si($p['aprobo_aula'] ?? ''),
        'rol_actual'          => mb_substr(trim((string) ($p['rol_actual'] ?? '')), 0, 40) ?: null,
    ];
}

/** Sede + puesto + persona de una asignación, o null si al archivo le falta algo esencial. */
function campos_asignacion_desde_json(array $a): ?array
{
    $codigo = trim((string) ($a['sede_examen'] ?? ''));
    $correo = mb_strtolower(trim((string) ($a['correo'] ?? '')));
    $tipo   = (string) ($a['tipo'] ?? '');

    if ($codigo === '' || $correo === '' || !in_array($tipo, ['aula', 'apoyo'], true)) {
        return null;
    }

    return [
        'codigo'        => $codigo,
        'correo'        => $correo,
        'tipo'          => $tipo,
        'numero_puesto' => (int) ($a['numero_puesto'] ?? 0),
    ];
}

// ------------------------------------------------------------------
// Análisis (en seco)
// ------------------------------------------------------------------

/**
 * Dice qué pasaría, sin tocar nada.
 *
 * Este paso no es un lujo: el archivo viene de otro sistema que nadie de acá
 * controla, y una carga a ciegas puede duplicar sedes o dejar el año en un
 * estado a medias. Que se vea antes el número de sedes nuevas y de aulas que
 * NO calzan es lo que permite parar a tiempo.
 */
function analizar(): void
{
    $datos = leer_json();
    $anios = anios_del_archivo($datos['sedes']);

    $conv = null;
    if (count($anios) === 1) {
        $anio = (string) array_key_first($anios);
        $stmt = db()->prepare('SELECT id, nombre, activa FROM convocatorias WHERE nombre = ?');
        $stmt->execute([$anio]);
        $conv = $stmt->fetch() ?: null;
    }

    // Qué sedes ya están y cuáles serían nuevas.
    $nuevas = 0;
    $existentes = 0;
    $sedesSinCambios = 0;
    $difAulas = [];
    $cambiosSedes = [];

    $personasNuevas = 0;
    $personasConCambios = 0;
    $personasSinCambios = 0;
    $diferenciasPersonas = [];

    $asignacionesNuevas = 0;
    $asignacionesQuitadas = 0;
    $asignacionesIguales = 0;

    if ($conv !== null) {
        $convId = (int) $conv['id'];

        // ---- Sedes: comparación campo por campo, no solo si existe --------
        // Antes esto solo decía "existe o no existe" y avisaba de las aulas.
        // El resto (turno, apoyo, coordinador, número de aplicación...) se
        // actualiza igual al importar, pero nadie lo veía venir.
        $stmt = db()->prepare('SELECT * FROM sedes WHERE convocatoria_id = ?');
        $stmt->execute([$convId]);
        $enBase = [];
        foreach ($stmt->fetchAll() as $fila) {
            $enBase[$fila['codigo']] = $fila;
        }

        $stmt = db()->prepare('SELECT sede_id, COUNT(*) FROM aulas WHERE convocatoria_id = ? GROUP BY sede_id');
        $stmt->execute([$convId]);
        $aulasPorSede = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($datos['sedes'] as $s) {
            $codigo = (string) ($s['sede_examen'] ?? '');

            if (!isset($enBase[$codigo])) {
                $nuevas++;
                continue;
            }

            $existentes++;
            $filaActual = $enBase[$codigo];

            $tiene  = (int) ($aulasPorSede[(int) $filaActual['id']] ?? 0);
            $espera = (int) ($s['cantidad_aulas'] ?? 0);
            if ($tiene !== $espera) {
                $difAulas[] = "sede {$codigo}: el archivo dice {$espera}, la base tiene {$tiene}";
            }

            $diff = [];
            foreach (campos_sede_desde_json($s, $codigo) as $campo => $valor) {
                if ((string) ($filaActual[$campo] ?? '') !== (string) ($valor ?? '')) {
                    $diff[$campo] = ['antes' => $filaActual[$campo], 'despues' => $valor];
                }
            }

            if ($diff === []) {
                $sedesSinCambios++;
                continue;
            }

            $cambiosSedes[] = ['codigo' => $codigo, 'nombre' => $filaActual['nombre'], 'diff' => $diff];
        }

        // ---- Padrón: comparación campo por campo ---------------------------
        $padronBase = [];
        foreach (db()->query('SELECT * FROM padron_personas')->fetchAll() as $fila) {
            $padronBase[$fila['correo']] = $fila;
        }

        foreach ($datos['personas'] ?? [] as $p) {
            $campos = campos_persona_desde_json($p);
            if ($campos === null) {
                continue;
            }

            $correo = $campos['correo'];
            unset($campos['correo']);

            if (!isset($padronBase[$correo])) {
                $personasNuevas++;
                continue;
            }

            $filaActual = $padronBase[$correo];
            $diff = [];
            foreach ($campos as $campo => $valor) {
                if ((string) ($filaActual[$campo] ?? '') !== (string) ($valor ?? '')) {
                    $diff[$campo] = ['antes' => $filaActual[$campo], 'despues' => $valor];
                }
            }

            if ($diff === []) {
                $personasSinCambios++;
                continue;
            }

            $personasConCambios++;
            // Con 875 personas un archivo real puede traer cientos de cambios
            // menores (universidad, aprobó tal cosa). El detalle se corta acá
            // y el contador de arriba sigue siendo el número real.
            if (count($diferenciasPersonas) < 200) {
                $diferenciasPersonas[] = ['correo' => $correo, 'nombre' => $filaActual['nombre'], 'diff' => $diff];
            }
        }

        // ---- Asignaciones: se reemplazan enteras, así que lo que importa
        // es qué puesto queda distinto, no solo cuántas hay -------------------
        $stmt = db()->prepare('
            SELECT s.codigo, aa.tipo, aa.numero_puesto, pp.correo
            FROM asignaciones_aplicadores aa
            INNER JOIN sedes s ON s.id = aa.sede_id
            INNER JOIN padron_personas pp ON pp.id = aa.padron_id
            WHERE aa.convocatoria_id = ?
        ');
        $stmt->execute([$convId]);
        $asigBase = [];
        foreach ($stmt->fetchAll() as $fila) {
            $asigBase["{$fila['codigo']}|{$fila['tipo']}|{$fila['numero_puesto']}"] = $fila['correo'];
        }

        $asigArchivo = [];
        foreach ($datos['aplicadores'] ?? [] as $a) {
            $campos = campos_asignacion_desde_json($a);
            if ($campos === null) {
                continue;
            }
            $asigArchivo["{$campos['codigo']}|{$campos['tipo']}|{$campos['numero_puesto']}"] = $campos['correo'];
        }

        foreach ($asigArchivo as $clave => $correo) {
            if (!isset($asigBase[$clave]) || $asigBase[$clave] !== $correo) {
                $asignacionesNuevas++;
            } else {
                $asignacionesIguales++;
            }
        }
        foreach ($asigBase as $clave => $correo) {
            if (!isset($asigArchivo[$clave])) {
                $asignacionesQuitadas++;
            }
        }
    } else {
        $nuevas = count($datos['sedes']);
    }

    responder_json([
        'success' => true,
        'anios'   => $anios,
        'convocatoria' => $conv === null ? null : [
            'id'     => (int) $conv['id'],
            'nombre' => $conv['nombre'],
            'activa' => (int) $conv['activa'] === 1,
        ],
        'conteos' => [
            'sedes'         => count($datos['sedes']),
            'sedesNuevas'   => $nuevas,
            'sedesExistentes' => $existentes,
            'sedesConCambios' => count($cambiosSedes),
            'sedesSinCambios' => $sedesSinCambios,
            'aulasPrevistas'  => array_sum(array_column($datos['sedes'], 'cantidad_aulas')),
            'coordinadores' => count($datos['coordinadores'] ?? []),
            'padron'        => count($datos['personas'] ?? []),
            'padronNuevas'  => $personasNuevas,
            'padronConCambios' => $personasConCambios,
            'padronSinCambios' => $personasSinCambios,
            'aplicadores'   => count($datos['aplicadores'] ?? []),
            'asignacionesNuevas'   => $asignacionesNuevas,
            'asignacionesQuitadas' => $asignacionesQuitadas,
            'asignacionesIguales'  => $asignacionesIguales,
            'sinCoordinador' => count(array_filter(
                $datos['sedes'],
                static fn(array $s): bool => trim((string) ($s['coordinador_correo'] ?? '')) === ''
            )),
        ],
        'diferenciasAulas'    => $difAulas,
        'cambiosSedes'        => $cambiosSedes,
        'diferenciasPersonas' => $diferenciasPersonas,
        'resumenArchivo'      => $datos['resumen'] ?? [],
    ]);
}

// ------------------------------------------------------------------
// Importación
// ------------------------------------------------------------------

function importar(array $yo): void
{
    exigir_metodo(['POST']);

    if (!csrf_valido(param('csrf'))) {
        responder_json(['success' => false, 'error' => 'Ficha de seguridad vencida. Recargá la página.'], 403);
    }

    $datos = leer_json();
    $anios = anios_del_archivo($datos['sedes']);

    if (count($anios) !== 1) {
        responder_json([
            'success' => false,
            'error'   => $anios === []
                ? 'Ninguna sede del archivo trae fecha de examen, así que no se sabe a qué año pertenece.'
                : 'El archivo mezcla ' . count($anios) . ' años (' . implode(', ', array_keys($anios))
                  . '). Hay que separarlos antes de importar.',
        ], 422);
    }

    $anio = (string) array_key_first($anios);

    // Con 317 sedes son cientos de sentencias: el tiempo por defecto puede
    // quedarse corto y una importación a medias es peor que ninguna.
    if (function_exists('set_time_limit')) {
        @set_time_limit(300);
    }

    db()->beginTransaction();

    try {
        $conv = convocatoria_para($anio, $datos['sedes']);
        $r = ['sedesNuevas' => 0, 'sedesActualizadas' => 0, 'aulasCreadas' => 0,
              'padronNuevas' => 0, 'padronActualizadas' => 0, 'asignaciones' => 0];

        importar_sedes($datos['sedes'], $conv, $r);
        importar_padron($datos['personas'] ?? [], $r);
        importar_asignaciones($datos['aplicadores'] ?? [], $conv, $r);

        db()->prepare("
            INSERT INTO importaciones
                (convocatoria_id, archivo, generado_en, sedes_nuevas, sedes_actualizadas,
                 aulas_creadas, padron_nuevas, padron_actualizadas, asignaciones,
                 resumen_archivo, importado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $conv,
            mb_substr(param('archivo', 'sin nombre'), 0, 255),
            generado_del_archivo($datos),
            $r['sedesNuevas'], $r['sedesActualizadas'], $r['aulasCreadas'],
            $r['padronNuevas'], $r['padronActualizadas'], $r['asignaciones'],
            json_encode($datos['resumen'] ?? [], JSON_UNESCAPED_UNICODE),
            $yo['usuario'],
        ]);

        db()->commit();

    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    responder_json([
        'success'     => true,
        'anio'        => $anio,
        'resultado'   => $r,
        'mensaje'     => "Importación del año {$anio} completada.",
    ]);
}

/** El id de la convocatoria del año, creándola si no existe. */
function convocatoria_para(string $anio, array $sedes): int
{
    $stmt = db()->prepare('SELECT id FROM convocatorias WHERE nombre = ?');
    $stmt->execute([$anio]);
    $id = $stmt->fetchColumn();

    if ($id !== false) {
        return (int) $id;
    }

    // La fecha de la convocatoria es la primera de examen: es la que usan los
    // módulos para rotular el acta.
    $fechas = array_filter(array_column($sedes, 'fecha_examen'));
    sort($fechas);

    // La nueva NO se marca activa: cambiar de año en medio de una aplicación
    // dejaría a todo el mundo mirando sedes que todavía no existen. Se activa
    // a mano cuando corresponda.
    db()->prepare('INSERT INTO convocatorias (nombre, fecha_aplicacion, activa) VALUES (?, ?, 0)')
        ->execute([$anio, $fechas[0] ?? null]);

    return (int) db()->lastInsertId();
}

function generado_del_archivo(array $datos): ?string
{
    foreach ($datos['resumen'] ?? [] as $r) {
        if (($r['concepto'] ?? '') === 'generado') {
            return mb_substr((string) $r['valor'], 0, 40);
        }
    }

    return null;
}

/**
 * Sedes y sus aulas.
 *
 * Las aulas solo se crean cuando faltan. Ver el encabezado del archivo: lo que
 * el personal capturó en cada aula no se toca nunca.
 */
function importar_sedes(array $sedes, int $conv, array &$r): void
{
    $buscar = db()->prepare('SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?');

    $insertar = db()->prepare('
        INSERT INTO sedes
            (convocatoria_id, codigo, codigo_externo, nombre, turno, numero_aplicacion,
             fecha_examen, aulas_previstas, apoyo_requerido,
             coordinador_nombre, coordinador_correo, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ');

    $actualizar = db()->prepare('
        UPDATE sedes
        SET codigo_externo = ?, nombre = ?, turno = ?, numero_aplicacion = ?,
            fecha_examen = ?, aulas_previstas = ?, apoyo_requerido = ?,
            coordinador_nombre = ?, coordinador_correo = ?
        WHERE id = ?
    ');

    $contarAulas = db()->prepare('SELECT COUNT(*) FROM aulas WHERE sede_id = ? AND convocatoria_id = ?');

    $insAula = db()->prepare('
        INSERT INTO aulas (convocatoria_id, sede_id, numero_aula, turno,
                           coordinador_nombre, coordinador_correo)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    // El coordinador también se refleja en las aulas que ya existen: es de ahí
    // de donde lo leen el acta y los módulos desde antes de esta migración.
    $aulaCoord = db()->prepare('
        UPDATE aulas SET coordinador_nombre = ?, coordinador_correo = ?
        WHERE sede_id = ? AND convocatoria_id = ?
    ');

    foreach ($sedes as $s) {
        $codigo = trim((string) ($s['sede_examen'] ?? ''));

        if ($codigo === '') {
            continue;
        }

        $campos = array_values(campos_sede_desde_json($s, $codigo));
        // Orden: codigo_externo, nombre, turno, numero_aplicacion, fecha_examen,
        // aulas_previstas, apoyo_requerido, coordinador_nombre, coordinador_correo
        // — el mismo de la sentencia de abajo. Ver campos_sede_desde_json().

        $buscar->execute([$conv, $codigo]);
        $sedeId = $buscar->fetchColumn();

        if ($sedeId === false) {
            $insertar->execute(array_merge([$conv, $codigo], $campos));
            $sedeId = (int) db()->lastInsertId();
            $r['sedesNuevas']++;
        } else {
            $sedeId = (int) $sedeId;
            $actualizar->execute(array_merge($campos, [$sedeId]));
            $r['sedesActualizadas']++;
        }

        // ---- Aulas ----
        $contarAulas->execute([$sedeId, $conv]);
        $tiene   = (int) $contarAulas->fetchColumn();
        $previstas = (int) ($s['cantidad_aulas'] ?? 0);

        // Solo se agregan las que falten. Si la base tiene MÁS de las
        // previstas no se quita ninguna: puede ser un aula de adecuación que
        // se abrió después y que el otro sistema no conoce.
        for ($n = $tiene + 1; $n <= $previstas; $n++) {
            $insAula->execute([$conv, $sedeId, (string) $n, $campos[2], $campos[7], $campos[8]]);
            $r['aulasCreadas']++;
        }

        if ($tiene > 0) {
            $aulaCoord->execute([$campos[7], $campos[8], $sedeId, $conv]);
        }
    }
}

/** El padrón de personas aplicadoras. */
function importar_padron(array $personas, array &$r): void
{
    if ($personas === []) {
        return;
    }

    $buscar = db()->prepare('SELECT id FROM padron_personas WHERE correo = ?');

    $insertar = db()->prepare('
        INSERT INTO padron_personas
            (correo, nombre, cedula, universidad,
             aprobo_coordinacion, aprobo_apoyo, aprobo_aula, rol_actual)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $actualizar = db()->prepare('
        UPDATE padron_personas
        SET nombre = ?, cedula = ?, universidad = ?,
            aprobo_coordinacion = ?, aprobo_apoyo = ?, aprobo_aula = ?, rol_actual = ?
        WHERE id = ?
    ');

    foreach ($personas as $p) {
        $datos = campos_persona_desde_json($p);
        if ($datos === null) {
            continue;
        }

        $correo = $datos['correo'];
        unset($datos['correo']);
        // Orden restante: nombre, cedula, universidad, aprobo_coordinacion,
        // aprobo_apoyo, aprobo_aula, rol_actual — el mismo de las sentencias.
        $campos = array_values($datos);

        $buscar->execute([$correo]);
        $id = $buscar->fetchColumn();

        if ($id === false) {
            $insertar->execute(array_merge([$correo], $campos));
            $r['padronNuevas']++;
        } else {
            $actualizar->execute(array_merge($campos, [(int) $id]));
            $r['padronActualizadas']++;
        }
    }
}

/**
 * Quién quedó en cada puesto.
 *
 * Se reemplazan las de la convocatoria en vez de acumularse: si a alguien lo
 * quitaron de una sede, el archivo nuevo simplemente ya no lo trae, y sumar
 * sin borrar lo dejaría nombrado para siempre.
 */
function importar_asignaciones(array $aplicadores, int $conv, array &$r): void
{
    db()->prepare('DELETE FROM asignaciones_aplicadores WHERE convocatoria_id = ?')
        ->execute([$conv]);

    if ($aplicadores === []) {
        return;
    }

    $sede = db()->prepare('SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $persona = db()->prepare('SELECT id FROM padron_personas WHERE correo = ?');

    $insertar = db()->prepare('
        INSERT INTO asignaciones_aplicadores
            (convocatoria_id, sede_id, padron_id, tipo, numero_puesto)
        VALUES (?, ?, ?, ?, ?)
    ');

    foreach ($aplicadores as $a) {
        $datos = campos_asignacion_desde_json($a);
        if ($datos === null) {
            continue;
        }

        $sede->execute([$conv, $datos['codigo']]);
        $sedeId = $sede->fetchColumn();

        $persona->execute([$datos['correo']]);
        $padronId = $persona->fetchColumn();

        // Una asignación a una sede o una persona que no vinieron en el mismo
        // archivo se salta: es un dato incompleto, no un motivo para abortar
        // una importación de 317 sedes.
        if ($sedeId === false || $padronId === false) {
            continue;
        }

        try {
            $insertar->execute([$conv, (int) $sedeId, (int) $padronId,
                                $datos['tipo'], $datos['numero_puesto']]);
            $r['asignaciones']++;
        } catch (PDOException $e) {
            // Puesto repetido dentro del propio archivo: se queda el primero.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }
    }
}

// ------------------------------------------------------------------
// Borrado de un año
// ------------------------------------------------------------------

/**
 * Elimina una convocatoria y todo lo que cuelga de ella.
 *
 * Va por pasos explícitos y en orden de dependencia en vez de por cascada: un
 * DELETE en cascada sobre años anteriores es demasiado fácil de disparar sin
 * querer, y acá no hay vuelta atrás.
 */
function borrar_anio(array $yo): void
{
    exigir_metodo(['POST']);

    if (!csrf_valido(param('csrf'))) {
        responder_json(['success' => false, 'error' => 'Ficha de seguridad vencida. Recargá la página.'], 403);
    }

    $id = param_int('convocatoria');

    $stmt = db()->prepare('SELECT id, nombre, activa FROM convocatorias WHERE id = ?');
    $stmt->execute([$id]);
    $conv = $stmt->fetch();

    if ($conv === false) {
        responder_json(['success' => false, 'error' => 'Esa convocatoria no existe'], 404);
    }

    // Sin esto el sistema entero se queda sin punto de referencia y todos los
    // módulos empiezan a responder «no hay ninguna convocatoria activa».
    if ((int) $conv['activa'] === 1) {
        responder_json([
            'success' => false,
            'error'   => "«{$conv['nombre']}» es la convocatoria activa. Activá otra antes de borrarla.",
        ], 409);
    }

    if (param('confirmacion') !== $conv['nombre']) {
        responder_json([
            'success' => false,
            'error'   => "Para borrarla hay que escribir exactamente «{$conv['nombre']}».",
        ], 400);
    }

    if (function_exists('set_time_limit')) {
        @set_time_limit(300);
    }

    db()->beginTransaction();

    try {
        $borradas = [];

        // ORDEN: de lo más dependiente a lo menos. Una llave foránea apuntando
        // a algo ya borrado aborta la transacción entera, así que el orden de
        // esta lista no es estético.
        //
        // Se contrastó contra TODAS las tablas que referencian `sedes` o
        // `convocatorias` en el esquema. Si mañana se agrega una tabla nueva
        // con esa referencia, hay que sumarla acá o el borrado del año va a
        // fallar con un error de llave foránea.
        foreach ([
            // Cuelgan de un padre que se borra más abajo: hay que llegar a
            // ellas por JOIN porque no tienen convocatoria_id propia.
            'acta_materiales'          => 'DELETE am FROM acta_materiales am
                                             INNER JOIN actas_revision ar ON ar.id = am.acta_id
                                             WHERE ar.convocatoria_id = ?',
            'ticket_comentarios'       => 'DELETE tc FROM ticket_comentarios tc
                                             INNER JOIN tickets t ON t.id = tc.ticket_id
                                             WHERE t.convocatoria_id = ?',
            'marchamos'                => 'DELETE FROM marchamos WHERE convocatoria_id = ?',
            'movimientos_tula'         => 'DELETE m FROM movimientos_tula m
                                             INNER JOIN tulas t ON t.id = m.tula_id
                                             WHERE t.convocatoria_id = ?',
            'tula_aulas'               => 'DELETE ta FROM tula_aulas ta
                                             INNER JOIN tulas t ON t.id = ta.tula_id
                                             WHERE t.convocatoria_id = ?',
            'tulas'                    => 'DELETE FROM tulas WHERE convocatoria_id = ?',
            // Los estudiantes y su historial de traslados. El detalle va antes
            // que la cabecera, y las dos antes que `estudiantes`, que a su vez
            // tiene llave foránea a `sedes` — por eso todo esto va acá arriba y
            // no al final.
            'traslado_detalle'         => 'DELETE td FROM traslado_detalle td
                                             INNER JOIN traslados t ON t.id = td.traslado_id
                                             WHERE t.convocatoria_id = ?',
            'traslados'                => 'DELETE FROM traslados WHERE convocatoria_id = ?',
            'estudiantes'              => 'DELETE FROM estudiantes WHERE convocatoria_id = ?',
            'cargas_estudiantes'       => 'DELETE FROM cargas_estudiantes WHERE convocatoria_id = ?',
            'asignaciones_aplicadores' => 'DELETE FROM asignaciones_aplicadores WHERE convocatoria_id = ?',
            'asignaciones_sede'        => 'DELETE FROM asignaciones_sede WHERE convocatoria_id = ?',
            'actas_revision'           => 'DELETE FROM actas_revision WHERE convocatoria_id = ?',
            'revisiones_sede'          => 'DELETE FROM revisiones_sede WHERE convocatoria_id = ?',
            'tickets'                  => 'DELETE FROM tickets WHERE convocatoria_id = ?',
            'sobresueldos'             => 'DELETE FROM sobresueldos WHERE convocatoria_id = ?',
            'aulas'                    => 'DELETE FROM aulas WHERE convocatoria_id = ?',
            'sedes'                    => 'DELETE FROM sedes WHERE convocatoria_id = ?',
            'importaciones'            => 'DELETE FROM importaciones WHERE convocatoria_id = ?',
        ] as $tabla => $sql) {
            try {
                $stmt = db()->prepare($sql);
                $stmt->execute([$id]);
                $borradas[$tabla] = $stmt->rowCount();
            } catch (PDOException $e) {
                // Una tabla que todavía no existe en este servidor (migración
                // pendiente) no debe impedir limpiar el resto.
                if (!str_contains($e->getMessage(), 'exist')) {
                    throw $e;
                }
                $borradas[$tabla] = 0;
            }
        }

        // Los postulantes NO se borran: se desligan del año.
        //
        // Es el fondo de gente que se ofreció a coordinar, y la misma persona
        // vuelve a postularse de una convocatoria a otra. Borrarla junto con el
        // año perdería su historial por una limpieza que no la buscaba. La
        // columna es anulable justamente para esto.
        $stmt = db()->prepare('UPDATE postulantes SET convocatoria_id = NULL WHERE convocatoria_id = ?');
        $stmt->execute([$id]);
        $borradas['postulantes_desligados'] = $stmt->rowCount();

        db()->prepare('DELETE FROM convocatorias WHERE id = ?')->execute([$id]);

        db()->commit();

    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    // Después del borrado, para que no se borre a sí mismo.
    bitacora_apuntar('importacion', 'borrarAnio',
        "convocatoria {$conv['nombre']} · " . array_sum($borradas) . ' filas');

    responder_json([
        'success'  => true,
        'borradas' => $borradas,
        'mensaje'  => "Se eliminó la convocatoria «{$conv['nombre']}» y sus "
                    . array_sum($borradas) . ' fila(s) asociadas.',
    ]);
}

// ------------------------------------------------------------------

function historial(): void
{
    $filas = db()->query("
        SELECT i.*, c.nombre AS convocatoria
        FROM importaciones i
        INNER JOIN convocatorias c ON c.id = i.convocatoria_id
        ORDER BY i.importado_en DESC
        LIMIT 25
    ")->fetchAll();

    responder_json([
        'success'      => true,
        'importaciones' => array_map(static fn(array $i): array => [
            'convocatoria' => $i['convocatoria'],
            'archivo'      => $i['archivo'],
            'generado'     => $i['generado_en'],
            'sedesNuevas'  => (int) $i['sedes_nuevas'],
            'sedesActualizadas' => (int) $i['sedes_actualizadas'],
            'aulasCreadas' => (int) $i['aulas_creadas'],
            'padron'       => (int) $i['padron_nuevas'] + (int) $i['padron_actualizadas'],
            'asignaciones' => (int) $i['asignaciones'],
            'por'          => $i['importado_por'],
            'fecha'        => $i['importado_en'],
        ], $filas),
    ]);
}
