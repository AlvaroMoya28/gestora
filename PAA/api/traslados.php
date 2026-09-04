<?php
/**
 * Traslado de estudiantes entre sedes.
 *
 * PARA QUÉ EXISTE
 * Una sede avisó que no presta las instalaciones para el turno del domingo por
 * la mañana, con las aulas ya asignadas y los estudiantes ya distribuidos. Hay
 * que moverlos a otra sede que, además, tiene aulas de otra capacidad: donde
 * iban 30 ahora van 25. Eso no es reasignar un aula, es redistribuir a toda la
 * población de la sede.
 *
 * LA REGLA QUE MANDA SOBRE TODO LO DEMÁS
 * El número de fórmula NO se toca. Nunca. Es lo que identifica al estudiante y
 * lo que está impreso en su documentación. Un traslado cambia tres cosas —sede,
 * aula y turno— y deja el resto de la fila exactamente como estaba.
 *
 *   accion=estado      → convocatoria, cuántos estudiantes hay cargados
 *   accion=sedes       → sedes con su cantidad de estudiantes y sus turnos
 *   accion=turnos      → turnos de una sede, con aulas y conteos
 *   accion=origen      → los estudiantes de una sede y turno, por aula
 *   accion=simulacion  → qué pasaría, con los choques detectados. NO escribe
 *   accion=trasladar   → lo aplica
 *   accion=revertir    → deshace un traslado
 *   accion=historial   → traslados anteriores
 *   accion=analizar    → lee el archivo de estudiantes y dice qué haría
 *   accion=cargar      → guarda el padrón de estudiantes
 *   accion=exportar    → baja el Excel (no responde JSON)
 *
 * NADA SE ESCRIBE SIN SIMULACIÓN PREVIA, y la simulación no es de adorno:
 * `trasladar` vuelve a validar todo por su cuenta. Entre que alguien mira la
 * pantalla y aprieta el botón puede haber pasado otra carga, y una simulación
 * no es un permiso.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/tabla_archivo.php';

$yo = exigir_modulo_api("traslados");

exigir_metodo(['GET', 'POST']);

/** Cuántos estudiantes se muestran en pantalla. El resto se cuenta, no se manda. */
const MUESTRA_ESTUDIANTES = 400;

/** Filas por INSERT al cargar el padrón. */
const LOTE_INSERCION = 400;

$accion = param('accion', param('action', 'estado'));

try {
    switch ($accion) {
        case 'estado':     estado();          break;
        case 'sedes':      listar_sedes();    break;
        case 'turnos':            listar_turnos();            break;
        case 'turnosConvocatoria': listar_turnos_convocatoria(); break;
        case 'origen':     ver_origen();      break;
        case 'simulacion': simulacion();      break;
        case 'trasladar':  trasladar($yo);    break;
        case 'revertir':   revertir($yo);     break;
        case 'historial':  historial();       break;
        case 'analizar':   analizar_archivo(); break;
        case 'cargar':     cargar_padron($yo); break;
        case 'exportar':   exportar();        break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('traslados', $e);
}

// ==================================================================
// Consulta
// ==================================================================

function estado(): void
{
    $conv = convocatoria_exigida_traslados();

    $stmt = db()->prepare('SELECT nombre, fecha_aplicacion FROM convocatorias WHERE id = ?');
    $stmt->execute([$conv]);
    $c = $stmt->fetch();

    $stmt = db()->prepare('
        SELECT COUNT(*) AS estudiantes,
               COUNT(DISTINCT sede_codigo) AS sedes,
               SUM(trasladado) AS trasladados
        FROM estudiantes WHERE convocatoria_id = ?
    ');
    $stmt->execute([$conv]);
    $t = $stmt->fetch();

    $stmt = db()->prepare('
        SELECT archivo, filas_leidas, nuevos, actualizados, cargado_por, cargado_en
        FROM cargas_estudiantes WHERE convocatoria_id = ?
        ORDER BY id DESC LIMIT 1
    ');
    $stmt->execute([$conv]);
    $ultima = $stmt->fetch();

    $stmt = db()->prepare('
        SELECT COUNT(*) FROM traslados WHERE convocatoria_id = ? AND revertido_en IS NULL
    ');
    $stmt->execute([$conv]);

    responder_json([
        'success'        => true,
        'convocatoriaId' => $conv,
        'convocatoria'   => $c['nombre'] ?? '',
        'fecha'          => $c['fecha_aplicacion'] ?? null,
        'estudiantes'    => (int) $t['estudiantes'],
        'sedesConDatos'  => (int) $t['sedes'],
        'trasladados'    => (int) $t['trasladados'],
        'traslados'      => (int) $stmt->fetchColumn(),
        'ultimaCarga'    => $ultima === false ? null : [
            'archivo'      => $ultima['archivo'],
            'filas'        => (int) $ultima['filas_leidas'],
            'nuevos'       => (int) $ultima['nuevos'],
            'actualizados' => (int) $ultima['actualizados'],
            'por'          => $ultima['cargado_por'],
            'fecha'        => $ultima['cargado_en'],
        ],
    ]);
}

/**
 * Las sedes de la convocatoria con cuántos estudiantes tiene cada una.
 *
 * Se cuenta por `sede_codigo` y no por `sede_id` a propósito: si un archivo
 * trae una sede que no está en la base, esos estudiantes igual tienen que
 * aparecer en algún lado en vez de desaparecer del conteo.
 */
function listar_sedes(): void
{
    $conv = convocatoria_exigida_traslados();

    $stmt = db()->prepare('
        SELECT s.id, s.codigo, s.nombre, s.turno, s.fecha_examen, s.activo,
               (SELECT COUNT(*) FROM aulas a
                 WHERE a.sede_id = s.id AND a.convocatoria_id = s.convocatoria_id) AS aulas
        FROM sedes s
        WHERE s.convocatoria_id = ?
        ORDER BY CAST(s.codigo AS UNSIGNED), s.codigo
    ');
    $stmt->execute([$conv]);
    $sedes = $stmt->fetchAll();

    $stmt = db()->prepare("
        SELECT sede_codigo,
               COUNT(*) AS estudiantes,
               COUNT(DISTINCT numero_aula) AS aulas_con_gente,
               COUNT(DISTINCT COALESCE(turno, '')) AS turnos
        FROM estudiantes WHERE convocatoria_id = ?
        GROUP BY sede_codigo
    ");
    $stmt->execute([$conv]);

    $conteo = [];
    foreach ($stmt->fetchAll() as $f) {
        $conteo[(string) $f['sede_codigo']] = $f;
    }

    $lista = array_map(static function (array $s) use (&$conteo): array {
        $c = $conteo[(string) $s['codigo']] ?? null;
        unset($conteo[(string) $s['codigo']]);

        return [
            'id'            => (int) $s['id'],
            'codigo'        => (string) $s['codigo'],
            'nombre'        => (string) $s['nombre'],
            'turno'         => $s['turno'],
            'fecha'         => $s['fecha_examen'],
            'activo'        => (int) $s['activo'] === 1,
            'aulas'         => (int) $s['aulas'],
            'estudiantes'   => $c !== null ? (int) $c['estudiantes'] : 0,
            'aulasConGente' => $c !== null ? (int) $c['aulas_con_gente'] : 0,
            'enBase'        => true,
        ];
    }, $sedes);

    // Códigos que traía el archivo y no existen como sede. Se muestran porque
    // son un problema que hay que ver, no uno que haya que esconder.
    foreach ($conteo as $codigo => $c) {
        $lista[] = [
            'id' => null, 'codigo' => (string) $codigo,
            'nombre' => '(no está en el catálogo de sedes)',
            'turno' => null, 'fecha' => null, 'activo' => false, 'aulas' => 0,
            'estudiantes' => (int) $c['estudiantes'],
            'aulasConGente' => (int) $c['aulas_con_gente'],
            'enBase' => false,
        ];
    }

    responder_json(['success' => true, 'sedes' => $lista, 'convocatoriaId' => $conv]);
}

/** Los turnos que tiene una sede según sus estudiantes. */
function listar_turnos(): void
{
    exigir(['sede']);

    $conv = convocatoria_exigida_traslados();

    $stmt = db()->prepare("
        SELECT COALESCE(turno, '') AS turno,
               COUNT(*) AS estudiantes,
               COUNT(DISTINCT numero_aula) AS aulas
        FROM estudiantes
        WHERE convocatoria_id = ? AND sede_codigo = ?
        GROUP BY COALESCE(turno, '')
        ORDER BY turno
    ");
    $stmt->execute([$conv, param('sede')]);

    responder_json([
        'success' => true,
        'turnos'  => array_map(static fn(array $t): array => [
            'turno'       => (string) $t['turno'],
            'estudiantes' => (int) $t['estudiantes'],
            'aulas'       => (int) $t['aulas'],
        ], $stmt->fetchAll()),
    ]);
}

/**
 * Todos los turnos que existen en la convocatoria, en cualquier sede.
 *
 * Para elegir el turno de destino de una lista en vez de escribirlo a mano:
 * un turno como «3-M-DOMINGO» es el mismo horario en las 317 sedes, así que
 * aunque la sede de destino todavía no tenga a nadie en ese turno, ya existe
 * en otras y no hay por qué arriesgarse a escribirlo distinto por un espacio
 * o una mayúscula.
 */
function listar_turnos_convocatoria(): void
{
    $conv = convocatoria_exigida_traslados();

    $stmt = db()->prepare("
        SELECT turno, COUNT(*) AS estudiantes, COUNT(DISTINCT sede_codigo) AS sedes
        FROM estudiantes
        WHERE convocatoria_id = ? AND turno IS NOT NULL AND turno <> ''
        GROUP BY turno
        ORDER BY turno
    ");
    $stmt->execute([$conv]);

    responder_json([
        'success' => true,
        'turnos'  => array_map(static fn(array $t): array => [
            'turno'       => (string) $t['turno'],
            'estudiantes' => (int) $t['estudiantes'],
            'sedes'       => (int) $t['sedes'],
        ], $stmt->fetchAll()),
    ]);
}

/** Los estudiantes de una sede y turno, agrupados por aula. */
function ver_origen(): void
{
    exigir(['sede']);

    $conv  = convocatoria_exigida_traslados();
    $sede  = param('sede');
    $turno = param('turno');
    $todos = param('todosLosTurnos') !== '';

    [$filtro, $valores] = filtro_turno($conv, $sede, $turno, $todos);

    $stmt = db()->prepare("
        SELECT numero_aula, COUNT(*) AS estudiantes
        FROM estudiantes WHERE {$filtro}
        GROUP BY numero_aula
        ORDER BY CAST(numero_aula AS UNSIGNED), numero_aula
    ");
    $stmt->execute($valores);
    $aulas = $stmt->fetchAll();

    $stmt = db()->prepare("
        SELECT id, formula, nombre, identificacion, numero_aula, turno
        FROM estudiantes WHERE {$filtro}
        ORDER BY CAST(numero_aula AS UNSIGNED), numero_aula, identificacion
        LIMIT " . MUESTRA_ESTUDIANTES
    );
    $stmt->execute($valores);

    $total = array_sum(array_map(static fn(array $a): int => (int) $a['estudiantes'], $aulas));

    responder_json([
        'success' => true,
        'total'   => $total,
        'aulas'   => array_map(static fn(array $a): array => [
            'aula'        => (string) $a['numero_aula'],
            'estudiantes' => (int) $a['estudiantes'],
        ], $aulas),
        'muestra'  => $stmt->fetchAll(),
        'recortada' => $total > MUESTRA_ESTUDIANTES,
    ]);
}

// ==================================================================
// Simulación
// ==================================================================

/**
 * Qué pasaría si se hiciera el traslado. No escribe absolutamente nada.
 *
 * Devuelve la distribución aula por aula y, sobre todo, la lista de choques.
 * Un choque no siempre impide seguir: hay `errores`, que bloquean, y `avisos`,
 * que solo hay que haber leído. Mezclarlos haría que se ignoren los dos.
 */
function simulacion(): void
{
    $plan = armar_plan();

    responder_json(['success' => true] + $plan);
}

/**
 * El cálculo completo del traslado, compartido por la simulación y por la
 * aplicación.
 *
 * Está en una sola función porque son la misma cuenta: si estuviera escrita
 * dos veces, tarde o temprano la pantalla mostraría una distribución y la base
 * guardaría otra.
 */
function armar_plan(): array
{
    $conv = convocatoria_exigida_traslados();

    $sedeOrigen = param('sedeOrigen');
    $turno      = param('turnoOrigen');
    $todos      = param('todosLosTurnos') !== '';
    $capacidad  = param_int('capacidad');
    $nueva      = param('sedeNueva') !== '';

    $errores = [];
    $avisos  = [];

    if ($sedeOrigen === '') {
        responder_json(['success' => false, 'error' => 'Falta la sede de origen'], 400);
    }

    // Se puede decir «25 por aula» o «tengo 12 aulas». La segunda forma es la
    // que hace falta cuando el límite es el edificio y no el criterio.
    if (param('reparto', 'capacidad') !== 'aulas' && ($capacidad < 1 || $capacidad > 500)) {
        $errores[] = 'La cantidad de estudiantes por aula tiene que estar entre 1 y 500.';
        $capacidad = max(1, min(500, $capacidad));
    }

    // La sede de origen tiene que estar en el catálogo, no solo en el archivo.
    //
    // Puede no estarlo: la lista de sedes muestra a propósito los códigos que
    // traía el padrón y no existen como sede, para que se vean. Si se eligiera
    // uno de esos como origen, el traslado se quedaría sin `sede_origen_id` y
    // reventaría contra la llave foránea a mitad de la escritura, con el error
    // de PDO en pantalla y sin decir qué pasó.
    if ($sedeOrigen !== '' && id_de_sede($conv, $sedeOrigen) === null) {
        $errores[] = "La sede {$sedeOrigen} tiene estudiantes cargados pero no está en el "
                   . 'catálogo de sedes de esta convocatoria. Cargala primero desde '
                   . 'Importación de Datos.';
    }

    // ---------- la sede destino ----------
    $destino = null;

    if ($nueva) {
        $codigoNuevo = param('codigoNuevo');
        $nombreNuevo = param('nombreNuevo');

        if ($codigoNuevo === '' || $nombreNuevo === '') {
            $errores[] = 'Para crear una sede hacen falta el código y el nombre.';
        } elseif (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $codigoNuevo)) {
            $errores[] = 'El código de la sede solo puede tener letras, números y guiones.';
        } else {
            $stmt = db()->prepare('SELECT id, nombre FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
            $stmt->execute([$conv, $codigoNuevo]);
            $choque = $stmt->fetch();

            if ($choque !== false) {
                $errores[] = "El código {$codigoNuevo} ya lo usa «{$choque['nombre']}» en esta convocatoria. "
                           . 'Elegí otro, o usá esa sede como destino en vez de crear una.';
            }
        }

        $destino = [
            'id' => null, 'codigo' => $codigoNuevo, 'nombre' => $nombreNuevo,
            'aulas' => 0, 'estudiantes' => 0, 'nueva' => true,
        ];
    } else {
        $codigoDestino = param('sedeDestino');

        if ($codigoDestino === '') {
            responder_json(['success' => false, 'error' => 'Falta la sede de destino'], 400);
        }

        $stmt = db()->prepare('SELECT id, nombre, turno FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
        $stmt->execute([$conv, $codigoDestino]);
        $s = $stmt->fetch();

        if ($s === false) {
            $errores[] = "La sede {$codigoDestino} no existe en esta convocatoria.";
            $destino = ['id' => null, 'codigo' => $codigoDestino, 'nombre' => '', 'nueva' => false];
        } else {
            $destino = [
                'id' => (int) $s['id'], 'codigo' => $codigoDestino,
                'nombre' => (string) $s['nombre'], 'turnoSede' => $s['turno'], 'nueva' => false,
            ];
        }
    }

    // Trasladar dentro de la misma sede sí se permite: es exactamente el caso
    // de reempacar un turno (por ejemplo, pasar de 30 a 25 por aula). No hay
    // colisión real porque los estudiantes que se están moviendo son los mismos
    // que liberan las aulas de origen — no le "caen encima" a nadie más, ver el
    // ajuste de aulas_ocupadas() más abajo.

    // El turno en el destino.
    //
    // Si no se escribe ninguno y se está moviendo UN turno, se mantiene ese.
    // Pero si se están moviendo TODOS los turnos de la sede, no hay «ese
    // turno» que mantener: poner el vacío les borraría el turno a todos de una
    // vez, y con él la única forma de volver a separarlos. En ese caso cada
    // quien conserva el suyo.
    $turnoExplicito = param('turnoDestino');
    $conservarTurno = $turnoExplicito === '' && $todos;

    $turnoDestino = $turnoExplicito !== ''
        ? $turnoExplicito
        : ($todos ? null : ($turno !== '' ? $turno : null));

    // ---------- los estudiantes a mover ----------
    [$filtro, $valores] = filtro_turno($conv, $sedeOrigen, $turno, $todos);

    // El orden manda: es el que decide en qué aula queda cada quien. Se agrupa
    // por el aula que tenían para que la gente que estaba junta siga junta, y
    // dentro de cada una por fórmula, que es un orden estable. Sin un orden
    // determinado, dos simulaciones seguidas darían repartos distintos.
    $stmt = db()->prepare("
        SELECT id, identificacion, formula, nombre, numero_aula, turno
        FROM estudiantes WHERE {$filtro}
        ORDER BY CAST(numero_aula AS UNSIGNED), numero_aula, identificacion
    ");
    $stmt->execute($valores);
    $gente = $stmt->fetchAll();

    $total = count($gente);

    if ($total === 0) {
        $errores[] = 'No hay ningún estudiante en esa sede'
                   . ($todos || $turno === '' ? '.' : " para el turno «{$turno}».");
    }

    // ---------- cuántas aulas y de qué tamaño ----------
    $modoReparto = param('reparto', 'capacidad');

    $tamanos         = tamanos_de_aula($total, $modoReparto, $capacidad, param_int('aulasDestino'));
    $aulasNecesarias = count($tamanos);

    if ($modoReparto === 'aulas') {
        if (param_int('aulasDestino') < 1) {
            $errores[] = 'Poné cuántas aulas hay disponibles en la sede de destino.';
        }

        // En este modo la capacidad no la escribe nadie: sale de la cuenta.
        $capacidad = $tamanos !== [] ? max($tamanos) : 0;
    }

    // Si el destino es la misma sede de origen, los propios estudiantes que se
    // van a mover no cuentan como "ocupando" un aula: la van a dejar libre en
    // esta misma operación. Se excluyen por id (no por número de aula) porque
    // un mismo número puede pertenecer a otro turno que sí se queda.
    $idsQueSeMueven = $destino['codigo'] === $sedeOrigen
        ? array_map(static fn(array $e): int => (int) $e['id'], $gente)
        : [];

    $ocupadas       = aulas_ocupadas($conv, $destino['codigo'], $idsQueSeMueven);
    $modoNumeracion = param('numeracion', 'continuar');

    $inicial = 1;

    if ($modoNumeracion === 'continuar' && $ocupadas !== []) {
        $inicial = max($ocupadas) + 1;
    } elseif ($modoNumeracion === 'desde') {
        $inicial = max(1, param_int('aulaInicial', 1));
    }

    // Solo tiene sentido si de verdad se van a ocupar aulas: sin estudiantes,
    // range() igual devolvería el aula inicial y marcaría un choque inventado
    // encima del error real, que es que no hay a quién mover.
    $pisadas = $aulasNecesarias > 0
        ? array_values(array_intersect(range($inicial, $inicial + $aulasNecesarias - 1), $ocupadas))
        : [];

    if ($pisadas !== []) {
        $errores[] = 'En la sede de destino ya hay estudiantes en las aulas '
                   . implode(', ', array_slice($pisadas, 0, 10))
                   . (count($pisadas) > 10 ? ' y otras' : '')
                   . '. Elegí «continuar la numeración» o cambiá el aula inicial.';
    }

    // ---------- el reparto ----------
    $aulaDe  = aulas_por_posicion($tamanos, $inicial);
    $reparto = [];

    foreach (array_values($gente) as $i => $e) {
        $aula = $aulaDe[$i] ?? (string) $inicial;

        $reparto[$aula] ??= ['aula' => $aula, 'estudiantes' => 0, 'desde' => []];
        $reparto[$aula]['estudiantes']++;
        $reparto[$aula]['desde'][(string) $e['numero_aula']] = true;
    }

    $distribucion = array_values(array_map(static function (array $r): array {
        $desde = array_keys($r['desde']);
        sort($desde, SORT_NATURAL);

        return [
            'aula'        => $r['aula'],
            'estudiantes' => $r['estudiantes'],
            'desde'       => $desde,
        ];
    }, $reparto));

    // ---------- avisos ----------
    if ($total > 0 && $capacidad > 0) {
        $ultima = end($distribucion);

        if ($ultima !== false && $ultima['estudiantes'] < $capacidad / 2 && count($distribucion) > 1) {
            $avisos[] = "La última aula queda con {$ultima['estudiantes']} estudiante(s) de "
                      . "{$capacidad}. Si preferís aulas más parejas, bajá la capacidad.";
        }
    }

    if ($destino['id'] !== null) {
        // Aulas del destino donde ya se capturaron folletos. Cambiarles la
        // cantidad de asignados después de embalar es exactamente el tipo de
        // cosa que nadie nota hasta el día de la aplicación.
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM aulas
            WHERE convocatoria_id = ? AND sede_id = ? AND total_folletos > 0
        ');
        $stmt->execute([$conv, $destino['id']]);
        $conFolletos = (int) $stmt->fetchColumn();

        if ($conFolletos > 0) {
            $avisos[] = "La sede de destino ya tiene {$conFolletos} aula(s) con folletos "
                      . 'capturados en Ingreso de Datos. Las nuevas se agregan aparte, pero '
                      . 'conviene avisarle a bodega.';
        }
    }

    // El archivo trae el nombre de la sede y la fecha en su propio formato, y
    // los dos hay que cambiarlos al trasladar. Si no hay de dónde copiarlos, el
    // Excel exportado saldría con la sede nueva en una columna y el nombre del
    // colegio viejo en la de al lado, que es peor que no exportarlo.
    if ($total > 0 && $destino['codigo'] !== '') {
        $ref = referencia_de_sede($conv, $destino['codigo'], $conservarTurno ? null : $turnoDestino);

        if (!$ref['tieneNombre']) {
            $avisos[] = 'La sede de destino todavía no tiene ningún estudiante, así que no hay '
                      . 'de dónde copiar cómo escribe su nombre el otro sistema. En el Excel esa '
                      . 'columna va a quedar con el nombre de la sede anterior: hay que '
                      . 'corregirla a mano antes de subirlo.';
        }

        if (!$conservarTurno && $turnoDestino !== null && !$ref['tieneFecha']) {
            $avisos[] = "No hay ningún estudiante en el turno «{$turnoDestino}» en toda la "
                      . 'convocatoria, así que no se puede deducir la fecha y la hora de ese '
                      . 'turno. Revisá esa columna en el Excel.';
        }
    }

    $sinTurno = 0;
    foreach ($gente as $e) {
        if (trim((string) $e['turno']) === '') {
            $sinTurno++;
        }
    }

    if ($sinTurno > 0 && $sinTurno < $total) {
        $avisos[] = "{$sinTurno} de los {$total} estudiantes no tienen turno registrado.";
    }

    // ---------- la sede origen después ----------
    $stmt = db()->prepare('SELECT COUNT(*) FROM estudiantes WHERE convocatoria_id = ? AND sede_codigo = ?');
    $stmt->execute([$conv, $sedeOrigen]);
    $enOrigen = (int) $stmt->fetchColumn();

    return [
        'convocatoriaId' => $conv,
        'origen' => [
            'codigo'   => $sedeOrigen,
            'nombre'   => nombre_de_sede($conv, $sedeOrigen),
            'turno'    => $todos ? '(todos)' : $turno,
            'quedan'   => $enOrigen - $total,
        ],
        'destino'         => $destino,
        'turnoDestino'    => $turnoDestino,
        'conservarTurno'  => $conservarTurno,
        'capacidad'       => $capacidad,
        'total'           => $total,
        'aulasNecesarias' => $aulasNecesarias,
        'aulaInicial'     => $inicial,
        // El tamaño de cada aula viaja con el plan para que la aplicación
        // reparta exactamente igual que la simulación, en vez de repetir la
        // cuenta y arriesgarse a que las dos se separen.
        'tamanos'         => $tamanos,
        'modoReparto'     => $modoReparto,
        'aulasOcupadas'   => $ocupadas,
        'distribucion'    => $distribucion,
        'errores'         => $errores,
        'avisos'          => $avisos,
        'sePuede'         => $errores === [],
        // Los ids en el orden del reparto. La aplicación los vuelve a calcular
        // por su cuenta; van acá solo para que la pantalla pueda mostrar la
        // muestra sin pedirlos otra vez.
        'muestra'         => array_slice($gente, 0, 60),
    ];
}

/**
 * Cuántos estudiantes van en cada aula nueva.
 *
 * DOS FORMAS DE DECIR LO MISMO, Y NO SON INTERCAMBIABLES
 *   · «25 por aula» — el criterio lo pone la norma. Se abren las aulas que
 *     hagan falta y la última queda con lo que sobre.
 *   · «tengo 12 aulas» — el criterio lo pone el edificio. Los estudiantes se
 *     reparten lo más parejo posible entre esas 12, y las que sobran del
 *     reparto entero se distribuyen de a uno en las primeras.
 *
 * La segunda hace falta cuando la sede de destino tiene las aulas que tiene y
 * no se pueden inventar más. Sin ella habría que ir probando capacidades hasta
 * que el número de aulas diera justo.
 *
 * @return int[] la cantidad de cada aula, en orden.
 */
function tamanos_de_aula(int $total, string $modo, int $capacidad, int $aulasPedidas): array
{
    if ($total <= 0) {
        return [];
    }

    if ($modo === 'aulas') {
        $n = max(1, $aulasPedidas);

        $base  = intdiv($total, $n);
        $resto = $total % $n;

        $tamanos = [];
        for ($k = 0; $k < $n; $k++) {
            $tamanos[] = $base + ($k < $resto ? 1 : 0);
        }

        // Si piden más aulas que estudiantes, las de sobra quedarían en cero.
        // No se crean: un aula vacía en el acta es una pregunta el día de la
        // aplicación.
        return array_values(array_filter($tamanos, static fn(int $t): bool => $t > 0));
    }

    $capacidad = max(1, $capacidad);
    $n         = (int) ceil($total / $capacidad);

    $tamanos = array_fill(0, $n, $capacidad);
    $tamanos[$n - 1] = $total - $capacidad * ($n - 1);

    return $tamanos;
}

/**
 * Para cada posición de la lista ordenada, en qué aula cae.
 *
 * Se precalcula en vez de recalcularlo dentro del bucle porque lo recorren dos
 * veces —la simulación y la aplicación— y tienen que dar exactamente lo mismo.
 *
 * @return string[]
 */
function aulas_por_posicion(array $tamanos, int $inicial): array
{
    $mapa = [];

    foreach ($tamanos as $k => $cuantos) {
        for ($j = 0; $j < $cuantos; $j++) {
            $mapa[] = (string) ($inicial + $k);
        }
    }

    return $mapa;
}

/**
 * Cómo escribe el otro sistema el nombre y la fecha de una sede.
 *
 * POR QUÉ SE COPIA DE OTRO ESTUDIANTE Y NO SE ARMA ACÁ
 * El archivo trae el nombre de la sede en su propio formato —«1401M CTP DE
 * PEJIBAYE - PÉREZ ZELEDÓN»— que no es el de `sedes.nombre` («CTP DE
 * PEJIBAYE»). Y la fecha viene con la hora pegada al turno («03/10/2026
 * 8:00AM»): quien cambia de turno cambia de hora.
 *
 * Escribir esos dos valores por nuestra cuenta produciría un archivo que el
 * otro sistema lee distinto del que generó. Copiarlos de alguien que ya esté en
 * la sede de destino los deja en el formato correcto sin tener que adivinarlo.
 *
 * Si la sede es nueva y no hay a quién copiarle, se usa el nombre del catálogo
 * y se avisa en la simulación.
 */
function referencia_de_sede(int $conv, string $codigo, ?string $turno): array
{
    // El NOMBRE es de la sede: da igual el turno de quien lo tenga escrito.
    $stmt = db()->prepare('
        SELECT sede_nombre_texto FROM estudiantes
        WHERE convocatoria_id = ? AND sede_codigo = ? AND sede_nombre_texto IS NOT NULL
        LIMIT 1
    ');
    $stmt->execute([$conv, $codigo]);
    $nombre = $stmt->fetchColumn();
    $nombre = $nombre !== false ? (string) $nombre : null;

    // La FECHA es del turno, no de la sede: «3-M-DOMINGO» son las 8:00 AM del
    // domingo en las 317 sedes. Por eso se busca por turno en toda la
    // convocatoria y no dentro de la sede, que puede no tener a nadie en ese
    // turno todavía —o ser nueva y no tener a nadie en ninguno.
    $fecha = ['fecha_texto' => null, 'fecha_examen' => null];

    if ($turno !== null && $turno !== '') {
        $stmt = db()->prepare('
            SELECT fecha_texto, fecha_examen FROM estudiantes
            WHERE convocatoria_id = ? AND turno = ? AND fecha_texto IS NOT NULL
            LIMIT 1
        ');
        $stmt->execute([$conv, $turno]);
        $f = $stmt->fetch();

        if (is_array($f)) {
            $fecha = $f;
        }
    }

    return [
        'sede_nombre_texto' => $nombre,
        'fecha_texto'       => $fecha['fecha_texto'],
        'fecha_examen'      => $fecha['fecha_examen'],
        'tieneNombre'       => $nombre !== null,
        'tieneFecha'        => $fecha['fecha_texto'] !== null,
    ];
}

/**
 * Los números de aula del destino que ya tienen estudiantes.
 *
 * @param int[] $excluirIds Ids de estudiantes que no cuentan como "ya
 *              ocupando" un aula — el caso de un traslado dentro de la misma
 *              sede, donde esos mismos estudiantes son los que liberan el
 *              número al moverse.
 */
function aulas_ocupadas(int $conv, string $sede, array $excluirIds = []): array
{
    if ($sede === '') {
        return [];
    }

    $sql     = '
        SELECT DISTINCT CAST(numero_aula AS UNSIGNED) AS n
        FROM estudiantes WHERE convocatoria_id = ? AND sede_codigo = ?
    ';
    $valores = [$conv, $sede];

    if ($excluirIds !== []) {
        $marcas   = implode(',', array_fill(0, count($excluirIds), '?'));
        $sql     .= " AND id NOT IN ({$marcas})";
        $valores  = array_merge($valores, $excluirIds);
    }

    $sql .= ' ORDER BY n';

    $stmt = db()->prepare($sql);
    $stmt->execute($valores);

    return array_values(array_filter(
        array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)),
        static fn(int $n): bool => $n > 0
    ));
}

// ==================================================================
// Aplicación
// ==================================================================

/**
 * Aplica el traslado.
 *
 * Vuelve a armar el plan desde cero en vez de confiar en lo que manda la
 * pantalla. La simulación que el usuario vio pudo quedar vieja —otra carga,
 * otro traslado— y aceptar su resultado sería dejar que el navegador decida
 * qué se escribe en la base.
 */
function trasladar(array $yo): void
{
    exigir_metodo(['POST']);
    exigir_csrf();
    exigir_esquema_al_dia();

    $plan = armar_plan();

    if (!$plan['sePuede']) {
        responder_json([
            'success' => false,
            'error'   => 'El traslado no se puede hacer: ' . implode(' ', $plan['errores']),
            'errores' => $plan['errores'],
        ], 422);
    }

    $conv      = $plan['convocatoriaId'];
    $capacidad = $plan['capacidad'];
    $inicial   = $plan['aulaInicial'];
    $sincronizar = param('sincronizarAulas') !== '';

    db()->beginTransaction();

    try {
        // ---------- la sede destino ----------
        $destinoId = $plan['destino']['id'];
        $sedeCreada = false;

        if ($destinoId === null) {
            $destinoId  = crear_sede_destino($conv, $plan);
            $sedeCreada = true;
        }

        // ---------- los estudiantes, otra vez y con candado ----------
        [$filtro, $valores] = filtro_turno(
            $conv,
            $plan['origen']['codigo'],
            param('turnoOrigen'),
            param('todosLosTurnos') !== ''
        );

        $stmt = db()->prepare("
            SELECT id, sede_codigo, numero_aula, turno
            FROM estudiantes WHERE {$filtro}
            ORDER BY CAST(numero_aula AS UNSIGNED), numero_aula, identificacion
            FOR UPDATE
        ");
        $stmt->execute($valores);
        $gente = $stmt->fetchAll();

        if (count($gente) !== $plan['total']) {
            throw new RuntimeException(
                'Los datos cambiaron mientras se preparaba el traslado. Volvé a simularlo.'
            );
        }

        // ---------- la cabecera ----------
        db()->prepare('
            INSERT INTO traslados
                (convocatoria_id, sede_origen_id, turno_origen, sede_destino_id,
                 turno_destino, capacidad_aula, aulas_destino, estudiantes,
                 aula_inicial, motivo, sede_creada, aulas_sincronizadas, hecho_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            $conv,
            id_de_sede($conv, $plan['origen']['codigo']),
            $plan['origen']['turno'],
            $destinoId,
            $plan['turnoDestino'],
            $capacidad,
            $plan['aulasNecesarias'],
            $plan['total'],
            $inicial,
            param('motivo') ?: null,
            $sedeCreada ? 1 : 0,
            $sincronizar ? 1 : 0,
            $yo['nombre'],
        ]);

        $trasladoId = (int) db()->lastInsertId();

        // ---------- mover ----------
        $aulaDe  = aulas_por_posicion($plan['tamanos'], $inicial);
        $porAula = [];   // aula destino → ids
        $detalle = [];

        foreach (array_values($gente) as $i => $e) {
            $aula = $aulaDe[$i] ?? (string) $inicial;

            $porAula[$aula][] = (int) $e['id'];

            $detalle[] = [
                $trasladoId, (int) $e['id'],
                (string) $e['sede_codigo'], (string) $e['numero_aula'], $e['turno'],
                $plan['destino']['codigo'], $aula,
                $plan['conservarTurno'] ? $e['turno'] : $plan['turnoDestino'],
            ];
        }

        // Una sentencia por aula de destino, no una por estudiante: son unas
        // decenas de consultas en vez de varios cientos.
        // El nombre y la fecha con el formato del otro sistema, copiados de la
        // sede de destino.
        $ref = referencia_de_sede(
            $conv,
            $plan['destino']['codigo'],
            $plan['conservarTurno'] ? null : $plan['turnoDestino']
        );

        foreach ($porAula as $aula => $ids) {
            // El SET se arma según lo que de verdad hay que cambiar. Cuando
            // cada quien conserva su turno, esa columna simplemente no entra:
            // es más seguro que reescribirle a cada estudiante el valor que ya
            // tenía.
            $set  = ['sede_codigo = ?', 'sede_id = ?', 'numero_aula = ?'];
            $vals = [$plan['destino']['codigo'], $destinoId, (string) $aula];

            if (!$plan['conservarTurno']) {
                $set[]  = 'turno = ?';
                $vals[] = $plan['turnoDestino'] ?: null;
            }

            if ($ref['sede_nombre_texto'] !== null) {
                $set[]  = 'sede_nombre_texto = ?';
                $vals[] = $ref['sede_nombre_texto'];
            }

            if ($ref['fecha_texto'] !== null) {
                $set[]  = 'fecha_texto = ?';
                $vals[] = $ref['fecha_texto'];
            }

            if ($ref['fecha_examen'] !== null) {
                $set[]  = 'fecha_examen = ?';
                $vals[] = $ref['fecha_examen'];
            }

            $set[] = 'trasladado = 1';

            $marcas = implode(',', array_fill(0, count($ids), '?'));

            db()->prepare(
                'UPDATE estudiantes SET ' . implode(', ', $set) . " WHERE id IN ({$marcas})"
            )->execute(array_merge($vals, $ids));
        }

        insertar_detalle($detalle);

        // ---------- aulas del sistema de embalaje ----------
        $aulasTocadas = ['creadas' => 0, 'actualizadas' => 0, 'vaciadas' => 0];

        if ($sincronizar) {
            $aulasTocadas = sincronizar_aulas(
                $conv, $destinoId, $plan['destino']['codigo'], $plan['turnoDestino'],
                id_de_sede($conv, $plan['origen']['codigo']), $plan['origen']['codigo']
            );
        }

        db()->commit();

    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    bitacora_apuntar(
        'traslados', 'trasladar',
        sprintf('sede=%s→%s estudiantes=%d aulas=%d',
            $plan['origen']['codigo'], $plan['destino']['codigo'],
            $plan['total'], $plan['aulasNecesarias'])
    );

    responder_json([
        'success'   => true,
        'traslado'  => $trasladoId,
        'mensaje'   => sprintf(
            'Se trasladaron %d estudiante(s) de la sede %s a la sede %s, en %d aula(s) de %d.',
            $plan['total'], $plan['origen']['codigo'], $plan['destino']['codigo'],
            $plan['aulasNecesarias'], $capacidad
        ),
        'sedeCreada' => $sedeCreada,
        'aulas'      => $aulasTocadas,
    ]);
}

/** Crea la sede de destino copiando lo que se sabe de la de origen. */
function crear_sede_destino(int $conv, array $plan): int
{
    // Se vuelve a comprobar acá dentro de la transacción: entre la simulación
    // y este momento otra persona pudo crear una sede con ese mismo código.
    $stmt = db()->prepare('SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $stmt->execute([$conv, $plan['destino']['codigo']]);

    if ($stmt->fetchColumn() !== false) {
        throw new RuntimeException(
            'El código ' . $plan['destino']['codigo'] . ' se acaba de ocupar. Elegí otro.'
        );
    }

    // La fecha de examen la hereda del origen: es la misma aplicación.
    $stmt = db()->prepare('SELECT fecha_examen, numero_aplicacion FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $stmt->execute([$conv, $plan['origen']['codigo']]);
    $origen = $stmt->fetch();

    // fetch() devuelve false cuando no hay fila, y leerle una clave a false es
    // un aviso de PHP y un null silencioso. Acá no debería pasar —armar_plan ya
    // exige que la sede de origen exista— pero el que se apoya en eso es este
    // código, no el lector.
    if (!is_array($origen)) {
        $origen = ['fecha_examen' => null, 'numero_aplicacion' => null];
    }

    db()->prepare('
        INSERT INTO sedes
            (convocatoria_id, codigo, nombre, turno, numero_aplicacion, fecha_examen,
             aulas_previstas, direccion, telefono, correo,
             coordinador_nombre, coordinador_correo, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ')->execute([
        $conv,
        $plan['destino']['codigo'],
        $plan['destino']['nombre'],
        $plan['turnoDestino'] ?: null,
        $origen['numero_aplicacion'] ?? null,
        $origen['fecha_examen'] ?? null,
        $plan['aulasNecesarias'],
        param('direccionNueva') ?: null,
        param('telefonoNueva') ?: null,
        param('correoNueva') ?: null,
        param('coordinadorNombre') ?: null,
        param('coordinadorCorreo') ?: null,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Pone al día las aulas del módulo de embalaje.
 *
 * POR QUÉ ES OPCIONAL Y NO AUTOMÁTICO
 * `aulas` es donde Ingreso de Datos guarda los folletos, las tulas y los
 * marchamos: semanas de trabajo del personal de bodega. Un traslado cambia
 * cuánta gente hay en cada aula, y eso hay que reflejarlo o los folletos van a
 * salir mal. Pero hacerlo en silencio, encima de una sede que ya se embaló,
 * sería peor que no hacerlo. Por eso se pregunta.
 *
 * Las aulas que quedan sin estudiantes NO se borran: pueden tener material ya
 * armado. Se les pone 0 asignados y se reporta cuántas quedaron así.
 */
function sincronizar_aulas(
    int $conv, int $destinoId, string $codigoDestino, ?string $turno,
    ?int $origenId, string $codigoOrigen
): array {
    $tocadas = ['creadas' => 0, 'actualizadas' => 0, 'vaciadas' => 0];

    $stmt = db()->prepare('
        SELECT numero_aula, COUNT(*) AS n
        FROM estudiantes WHERE convocatoria_id = ? AND sede_codigo = ?
        GROUP BY numero_aula
    ');
    $stmt->execute([$conv, $codigoDestino]);

    $existe = db()->prepare('SELECT id FROM aulas WHERE convocatoria_id = ? AND sede_id = ? AND numero_aula = ?');
    $crear  = db()->prepare('
        INSERT INTO aulas (convocatoria_id, sede_id, numero_aula, asignados, turno)
        VALUES (?, ?, ?, ?, ?)
    ');
    $poner  = db()->prepare('UPDATE aulas SET asignados = ? WHERE id = ?');

    foreach ($stmt->fetchAll() as $a) {
        $existe->execute([$conv, $destinoId, (string) $a['numero_aula']]);
        $id = $existe->fetchColumn();

        if ($id === false) {
            $crear->execute([$conv, $destinoId, (string) $a['numero_aula'], (int) $a['n'], $turno]);
            $tocadas['creadas']++;
        } else {
            $poner->execute([(int) $a['n'], (int) $id]);
            $tocadas['actualizadas']++;
        }
    }

    // La sede de origen: se recuenta lo que quedó en cada una de sus aulas.
    // Las que se vaciaron quedan en 0 y NO se borran — pueden tener material ya
    // armado, y borrarlas se llevaría por delante los folletos capturados.
    if ($origenId !== null) {
        db()->prepare('
            UPDATE aulas a
            SET a.asignados = (
                SELECT COUNT(*) FROM estudiantes e
                WHERE e.convocatoria_id = a.convocatoria_id
                  AND e.sede_codigo = ?
                  AND e.numero_aula = a.numero_aula
            )
            WHERE a.convocatoria_id = ? AND a.sede_id = ?
        ')->execute([$codigoOrigen, $conv, $origenId]);

        $stmt = db()->prepare('
            SELECT COUNT(*) FROM aulas
            WHERE convocatoria_id = ? AND sede_id = ? AND asignados = 0
        ');
        $stmt->execute([$conv, $origenId]);

        $tocadas['vaciadas'] = (int) $stmt->fetchColumn();
    }

    return $tocadas;
}

/** Inserta el detalle en lotes. */
function insertar_detalle(array $detalle): void
{
    foreach (array_chunk($detalle, LOTE_INSERCION) as $lote) {
        $marcas = implode(',', array_fill(0, count($lote), '(?,?,?,?,?,?,?,?)'));

        db()->prepare("
            INSERT INTO traslado_detalle
                (traslado_id, estudiante_id, sede_antes, aula_antes, turno_antes,
                 sede_despues, aula_despues, turno_despues)
            VALUES {$marcas}
        ")->execute(array_merge(...array_map('array_values', $lote)));
    }
}

// ==================================================================
// Revertir
// ==================================================================

/**
 * Deshace un traslado, devolviendo a cada estudiante a donde estaba.
 *
 * Se niega si alguno de esos estudiantes fue movido otra vez después: revertir
 * el primero de dos traslados encadenados dejaría al estudiante en un lugar que
 * nadie decidió. En ese caso hay que revertir el más reciente primero.
 */
function revertir(array $yo): void
{
    exigir_metodo(['POST']);
    exigir_csrf();

    $id = param_int('traslado');

    $stmt = db()->prepare('SELECT * FROM traslados WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();

    if ($t === false) {
        responder_json(['success' => false, 'error' => 'Ese traslado no existe'], 404);
    }

    if ($t['revertido_en'] !== null) {
        responder_json(['success' => false, 'error' => 'Ese traslado ya estaba revertido'], 409);
    }

    $stmt = db()->prepare('
        SELECT COUNT(*)
        FROM traslado_detalle d
        JOIN traslados t2 ON t2.id = d.traslado_id
        WHERE d.estudiante_id IN (SELECT estudiante_id FROM traslado_detalle WHERE traslado_id = ?)
          AND d.traslado_id > ?
          AND t2.revertido_en IS NULL
    ');
    $stmt->execute([$id, $id]);

    if ((int) $stmt->fetchColumn() > 0) {
        responder_json([
            'success' => false,
            'error'   => 'Hay estudiantes de este traslado que después se movieron otra vez. '
                       . 'Revertí primero el traslado más reciente.',
        ], 409);
    }

    db()->beginTransaction();

    try {
        $stmt = db()->prepare('
            SELECT d.estudiante_id, d.sede_antes, d.aula_antes, d.turno_antes,
                   e.sede_origen, e.aula_origen, e.turno_origen
            FROM traslado_detalle d
            JOIN estudiantes e ON e.id = d.estudiante_id
            WHERE d.traslado_id = ?
        ');
        $stmt->execute([$id]);

        $volver = db()->prepare('
            UPDATE estudiantes
            SET sede_codigo = ?, sede_id = ?, numero_aula = ?, turno = ?, trasladado = ?
            WHERE id = ?
        ');

        $conv    = (int) $t['convocatoria_id'];
        $idsSede = [];
        $cuantos = 0;

        foreach ($stmt->fetchAll() as $d) {
            $codigo = (string) $d['sede_antes'];
            $idsSede[$codigo] ??= id_de_sede($conv, $codigo);

            // Si vuelve a donde estaba al importar, deja de contar como trasladado.
            $enSuOrigen = $codigo === (string) $d['sede_origen']
                       && (string) $d['aula_antes'] === (string) $d['aula_origen'];

            $volver->execute([
                $codigo,
                $idsSede[$codigo],
                (string) $d['aula_antes'],
                $d['turno_antes'],
                $enSuOrigen ? 0 : 1,
                (int) $d['estudiante_id'],
            ]);

            $cuantos++;
        }

        db()->prepare('UPDATE traslados SET revertido_por = ?, revertido_en = NOW() WHERE id = ?')
            ->execute([$yo['nombre'], $id]);

        db()->commit();

    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    bitacora_apuntar('traslados', 'revertir', "traslado={$id} estudiantes={$cuantos}");

    responder_json([
        'success' => true,
        'mensaje' => "Se devolvieron {$cuantos} estudiante(s) a su sede y aula anteriores. "
                   . 'Las aulas creadas en el destino no se borraron.',
    ]);
}

// ==================================================================
// Historial
// ==================================================================

function historial(): void
{
    $conv = convocatoria_exigida_traslados();

    $stmt = db()->prepare('
        SELECT t.*, o.codigo AS origen_codigo, o.nombre AS origen_nombre,
               d.codigo AS destino_codigo, d.nombre AS destino_nombre
        FROM traslados t
        JOIN sedes o ON o.id = t.sede_origen_id
        JOIN sedes d ON d.id = t.sede_destino_id
        WHERE t.convocatoria_id = ?
        ORDER BY t.id DESC
        LIMIT 100
    ');
    $stmt->execute([$conv]);

    responder_json([
        'success'   => true,
        'traslados' => array_map(static fn(array $t): array => [
            'id'            => (int) $t['id'],
            'origen'        => $t['origen_codigo'] . ' · ' . $t['origen_nombre'],
            'destino'       => $t['destino_codigo'] . ' · ' . $t['destino_nombre'],
            'turnoOrigen'   => $t['turno_origen'],
            'turnoDestino'  => $t['turno_destino'],
            'estudiantes'   => (int) $t['estudiantes'],
            'aulas'         => (int) $t['aulas_destino'],
            'capacidad'     => (int) $t['capacidad_aula'],
            'motivo'        => $t['motivo'],
            'sedeCreada'    => (int) $t['sede_creada'] === 1,
            'por'           => $t['hecho_por'],
            'fecha'         => $t['hecho_en'],
            'revertido'     => $t['revertido_en'] !== null,
            'revertidoPor'  => $t['revertido_por'],
            'revertidoEn'   => $t['revertido_en'],
        ], $stmt->fetchAll()),
    ]);
}

// ==================================================================
// Carga del padrón de estudiantes
// ==================================================================

/** Lee el archivo subido y dice qué haría, sin escribir nada. */
function analizar_archivo(): void
{
    exigir_metodo(['POST']);

    $conv    = convocatoria_exigida_traslados();
    $lectura = leer_subida();
    $mapa    = mapear_columnas($lectura['columnas']);

    $faltan = array_values(array_diff(COLUMNAS_OBLIGATORIAS, array_keys($mapa)));

    if ($faltan !== []) {
        responder_json([
            'success'  => false,
            'error'    => 'No se reconocieron estas columnas en el archivo: ' . implode(', ', $faltan)
                        . '. Las columnas encontradas son: ' . implode(', ', $lectura['columnas']),
            'columnas' => $lectura['columnas'],
            'mapa'     => $mapa,
        ], 422);
    }

    // Se recorre entero: es la única forma de detectar identificaciones
    // repetidas antes de escribir, y una repetida significa que dos personas
    // comparten número de cómputo, que es un problema de origen.
    $personas   = [];
    $repetidas  = [];
    $sinDatos   = 0;
    $sedes      = [];
    $turnos     = [];

    foreach ($lectura['filas'] as $fila) {
        $cedula = trim((string) ($fila[$mapa['identificacion']] ?? ''));
        $sede   = trim((string) ($fila[$mapa['sede']] ?? ''));
        $aula   = trim((string) ($fila[$mapa['aula']] ?? ''));

        if ($cedula === '' || $sede === '' || $aula === '') {
            $sinDatos++;
            continue;
        }

        if (isset($personas[$cedula])) {
            $repetidas[$cedula] = true;
        }

        $personas[$cedula] = true;
        $sedes[$sede] = ($sedes[$sede] ?? 0) + 1;

        if (isset($mapa['turno'])) {
            $turno = trim((string) ($fila[$mapa['turno']] ?? ''));
            $turnos[$turno] = ($turnos[$turno] ?? 0) + 1;
        }
    }

    // Cuántos de esos ya están en la base, y cuántos de ellos ya se movieron.
    $yaEstan = 0;
    $moverianDeVuelta = 0;

    foreach (array_chunk(array_keys($personas), 500) as $lote) {
        $marcas = implode(',', array_fill(0, count($lote), '?'));

        $stmt = db()->prepare("
            SELECT COUNT(*) AS n, SUM(trasladado) AS movidos
            FROM estudiantes
            WHERE convocatoria_id = ? AND identificacion IN ({$marcas})
        ");
        $stmt->execute(array_merge([$conv], $lote));
        $r = $stmt->fetch();

        $yaEstan += (int) $r['n'];
        $moverianDeVuelta += (int) $r['movidos'];
    }

    // Sedes del archivo que no existen en la convocatoria.
    $stmt = db()->prepare('SELECT codigo FROM sedes WHERE convocatoria_id = ?');
    $stmt->execute([$conv]);
    $enBase = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

    $desconocidas = array_values(array_diff(array_keys($sedes), array_keys($enBase)));

    responder_json([
        'success'  => true,
        'formato'  => $lectura['formato'],
        'columnas' => $lectura['columnas'],
        'mapa'     => $mapa,
        'ignoradas' => array_values(array_diff($lectura['columnas'], array_values($mapa))),
        'conteos'  => [
            'filas'        => count($lectura['filas']),
            'validas'      => count($personas),
            'sinDatos'     => $sinDatos,
            'repetidas'    => count($repetidas),
            'yaEstan'      => $yaEstan,
            'nuevos'       => count($personas) - $yaEstan,
            'sedes'        => count($sedes),
            'trasladados'  => $moverianDeVuelta,
        ],
        'sedesDesconocidas' => array_slice($desconocidas, 0, 40),
        'turnos'   => $turnos,
        'muestra'  => array_slice($lectura['filas'], 0, 8),
    ]);
}

/** Guarda el padrón. */
function cargar_padron(array $yo): void
{
    exigir_metodo(['POST']);
    exigir_csrf();
    exigir_esquema_al_dia();

    $conv    = convocatoria_exigida_traslados();
    $lectura = leer_subida();
    $mapa    = mapear_columnas($lectura['columnas']);

    $faltan = array_values(array_diff(COLUMNAS_OBLIGATORIAS, array_keys($mapa)));

    if ($faltan !== []) {
        responder_json([
            'success' => false,
            'error'   => 'Faltan columnas obligatorias: ' . implode(', ', $faltan),
        ], 422);
    }

    $reemplazar = param('modo') === 'reemplazar';

    if ($reemplazar) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM traslados WHERE convocatoria_id = ? AND revertido_en IS NULL');
        $stmt->execute([$conv]);

        if ((int) $stmt->fetchColumn() > 0) {
            responder_json([
                'success' => false,
                'error'   => 'No se puede reemplazar el padrón: hay traslados aplicados que se '
                           . 'perderían junto con su historial. Revertilos primero, o cargá en '
                           . 'modo actualizar.',
            ], 409);
        }
    }

    // Códigos de sede → id, una sola vez.
    $stmt = db()->prepare('SELECT codigo, id FROM sedes WHERE convocatoria_id = ?');
    $stmt->execute([$conv]);
    $sedes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (function_exists('set_time_limit')) {
        @set_time_limit(300);
    }

    db()->beginTransaction();

    try {
        if ($reemplazar) {
            db()->prepare('DELETE FROM estudiantes WHERE convocatoria_id = ?')->execute([$conv]);
        }

        // Se cuenta DESPUÉS del borrado. Contarlo antes haría que en modo
        // reemplazar «nuevos» diera cero o negativo: el padrón viejo ya no está
        // y todo lo que entra es nuevo.
        $antes = contar_estudiantes($conv);

        $lote        = [];
        $leidas      = 0;
        $descartadas = 0;

        foreach ($lectura['filas'] as $fila) {
            $cedula = trim((string) ($fila[$mapa['identificacion']] ?? ''));
            $sede   = trim((string) ($fila[$mapa['sede']] ?? ''));
            $aula   = trim((string) ($fila[$mapa['aula']] ?? ''));

            if ($cedula === '' || $sede === '' || $aula === '') {
                $descartadas++;
                continue;
            }

            $turno      = isset($mapa['turno']) ? trim((string) ($fila[$mapa['turno']] ?? '')) : '';
            $fechaTexto = isset($mapa['fecha']) ? trim((string) ($fila[$mapa['fecha']] ?? '')) : '';
            $fecha      = $fechaTexto !== '' ? fecha_a_sql($fechaTexto) : null;

            $lote[] = [
                $conv,
                mb_substr($cedula, 0, 40),
                valor($fila, $mapa, 'formula', 60),
                valor($fila, $mapa, 'nombre', 200),
                valor($fila, $mapa, 'formulario', 60),
                mb_substr($sede, 0, 20),
                isset($sedes[$sede]) ? (int) $sedes[$sede] : null,
                mb_substr($aula, 0, 20),
                $turno !== '' ? mb_substr($turno, 0, 50) : null,
                $fecha,
                $fechaTexto !== '' ? mb_substr($fechaTexto, 0, 40) : null,
                valor($fila, $mapa, 'sedeNombre', 200),
                mb_substr($sede, 0, 20),
                mb_substr($aula, 0, 20),
                $turno !== '' ? mb_substr($turno, 0, 50) : null,
                json_encode($fila, JSON_UNESCAPED_UNICODE),
            ];

            $leidas++;

            if (count($lote) >= LOTE_INSERCION) {
                guardar_lote($lote);
                $lote = [];
            }
        }

        if ($lote !== []) {
            guardar_lote($lote);
        }

        $despues = contar_estudiantes($conv);

        db()->prepare('
            INSERT INTO cargas_estudiantes
                (convocatoria_id, archivo, filas_leidas, nuevos, actualizados,
                 descartados, columnas, cargado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            $conv,
            mb_substr(nombre_subido(), 0, 255),
            $leidas,
            max(0, $despues - $antes),
            $leidas - max(0, $despues - $antes),
            $descartadas,
            json_encode([
                'columnas' => $lectura['columnas'],
                'mapa'     => $mapa,
                'formato'  => $lectura['formato'],
            ], JSON_UNESCAPED_UNICODE),
            $yo['nombre'],
        ]);

        db()->commit();

    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    bitacora_apuntar(
        'traslados', 'cargar',
        sprintf('archivo=%s filas=%d modo=%s', nombre_subido(), $leidas, $reemplazar ? 'reemplazar' : 'actualizar')
    );

    responder_json([
        'success'      => true,
        'mensaje'      => sprintf(
            'Se cargaron %d estudiante(s): %d nuevos y %d actualizados. %s',
            $leidas, max(0, $despues - $antes), $leidas - max(0, $despues - $antes),
            $descartadas > 0 ? "Se descartaron {$descartadas} fila(s) sin fórmula, sede o aula." : ''
        ),
        'nuevos'       => max(0, $despues - $antes),
        'actualizados' => $leidas - max(0, $despues - $antes),
        'descartados'  => $descartadas,
        'total'        => $despues,
    ]);
}

/**
 * Escribe un lote.
 *
 * ON DUPLICATE KEY UPDATE es lo que hace que volver a subir el archivo
 * actualice en vez de reventar contra el índice único. Y sí, resetea
 * `trasladado` y el origen: un archivo nuevo es una afirmación nueva sobre
 * dónde va cada quien. La pantalla avisa cuántos trasladados va a revertir
 * ANTES de que alguien apriete el botón.
 */
function guardar_lote(array $lote): void
{
    $marcas = implode(',', array_fill(0, count($lote), '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'));

    db()->prepare("
        INSERT INTO estudiantes
            (convocatoria_id, identificacion, formula, nombre, formulario,
             sede_codigo, sede_id, numero_aula, turno, fecha_examen, fecha_texto,
             sede_nombre_texto, sede_origen, aula_origen, turno_origen, fila_original)
        VALUES {$marcas}
        ON DUPLICATE KEY UPDATE
            formula = VALUES(formula),
            nombre = VALUES(nombre),
            formulario = VALUES(formulario),
            sede_codigo = VALUES(sede_codigo),
            sede_id = VALUES(sede_id),
            numero_aula = VALUES(numero_aula),
            turno = VALUES(turno),
            fecha_examen = VALUES(fecha_examen),
            fecha_texto = VALUES(fecha_texto),
            sede_nombre_texto = VALUES(sede_nombre_texto),
            sede_origen = VALUES(sede_origen),
            aula_origen = VALUES(aula_origen),
            turno_origen = VALUES(turno_origen),
            fila_original = VALUES(fila_original),
            trasladado = 0
    ")->execute(array_merge(...array_map('array_values', $lote)));
}

function contar_estudiantes(int $conv): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM estudiantes WHERE convocatoria_id = ?');
    $stmt->execute([$conv]);

    return (int) $stmt->fetchColumn();
}

/** Un campo opcional del archivo, recortado al largo de la columna. */
function valor(array $fila, array $mapa, string $campo, int $largo): ?string
{
    if (!isset($mapa[$campo])) {
        return null;
    }

    $v = trim((string) ($fila[$mapa[$campo]] ?? ''));

    return $v === '' ? null : mb_substr($v, 0, $largo);
}

// ==================================================================
// Exportación
// ==================================================================

/**
 * Baja el Excel. NO responde JSON: escribe el archivo directamente.
 *
 * SOBRE EL FORMATO
 * Sale como tabla HTML con extensión .xls, que es exactamente lo que exporta el
 * sistema de coordinaciones y lo que Excel abre sin preguntar nada. La razón de
 * fondo no es la compatibilidad sino los ceros a la izquierda: en un CSV, Excel
 * convierte la sede «07» en el número 7 y la fórmula «0004512» en 4512, sin
 * avisar. Acá cada celda va marcada como texto y llega intacta.
 *
 * LAS COLUMNAS SON LAS DEL ARCHIVO ORIGINAL
 * Se reconstruyen desde `fila_original`, con los valores de sede, aula y turno
 * reemplazados por los actuales. Así el archivo que sale se puede volver a
 * subir al otro sistema sin que nadie tenga que reacomodar nada.
 */
function exportar(): void
{
    $conv = convocatoria_exigida_traslados();
    $que  = param('que', 'todos');

    switch ($que) {
        case 'traslado':
            $id = param_int('traslado');

            $stmt = db()->prepare('
                SELECT e.*, d.sede_antes, d.aula_antes, d.turno_antes
                FROM traslado_detalle d
                JOIN estudiantes e ON e.id = d.estudiante_id
                WHERE d.traslado_id = ?
                ORDER BY CAST(d.aula_despues AS UNSIGNED), d.aula_despues, e.identificacion
            ');
            $stmt->execute([$id]);
            $nombre = "traslado_{$id}";
            $conAnterior = true;
            break;

        case 'sede':
            exigir(['sede']);

            $stmt = db()->prepare('
                SELECT * FROM estudiantes
                WHERE convocatoria_id = ? AND sede_codigo = ?
                ORDER BY CAST(numero_aula AS UNSIGNED), numero_aula, identificacion
            ');
            $stmt->execute([$conv, param('sede')]);
            $nombre = 'sede_' . preg_replace('/[^A-Za-z0-9\-]/', '', param('sede'));
            $conAnterior = false;
            break;

        case 'movidos':
            $stmt = db()->prepare('
                SELECT * FROM estudiantes
                WHERE convocatoria_id = ? AND trasladado = 1
                ORDER BY sede_codigo, CAST(numero_aula AS UNSIGNED), numero_aula, identificacion
            ');
            $stmt->execute([$conv]);
            $nombre = 'estudiantes_trasladados';
            $conAnterior = false;
            break;

        default:
            $stmt = db()->prepare('
                SELECT * FROM estudiantes
                WHERE convocatoria_id = ?
                ORDER BY CAST(sede_codigo AS UNSIGNED), sede_codigo,
                         CAST(numero_aula AS UNSIGNED), numero_aula, identificacion
            ');
            $stmt->execute([$conv]);
            $nombre = 'estudiantes_completo';
            $conAnterior = false;
    }

    $columnas = columnas_de_exportacion($conv);
    $mapa     = mapear_columnas($columnas);

    if ($conAnterior) {
        $columnas[] = 'SEDE_ANTERIOR';
        $columnas[] = 'AULA_ANTERIOR';
        $columnas[] = 'TURNO_ANTERIOR';
    }

    $archivo = sprintf('%s_%s.xls', $nombre, date('Ymd_Hi'));

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Cache-Control: no-store');

    if (function_exists('set_time_limit')) {
        @set_time_limit(300);
    }

    echo "\xEF\xBB\xBF";
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    // mso-number-format \@ es «texto». Es lo que evita que Excel se coma los
    // ceros a la izquierda de la fórmula y del código de sede.
    echo '<style>td { mso-number-format:"\@"; } th { background:#e8f0fe; font-weight:bold; }</style>';
    echo '</head><body><table border="1">';

    echo '<tr>';
    foreach ($columnas as $c) {
        echo '<th>' . htmlspecialchars((string) $c, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr>';

    $filas = 0;

    while (($e = $stmt->fetch()) !== false) {
        $original = json_decode((string) ($e['fila_original'] ?? ''), true);
        $original = is_array($original) ? $original : [];

        // Lo actual pisa lo del archivo: es el punto de todo el ejercicio.
        //
        // Son CINCO columnas, no dos. Cambiar solo la sede y el aula dejaría el
        // archivo contradiciéndose: la sede 918 en una columna y el nombre del
        // colegio del que la gente se fue en la de al lado, con la hora del
        // turno viejo. El otro sistema no tendría cómo saber cuál creerle.
        if (isset($mapa['sede']))       { $original[$mapa['sede']]       = (string) $e['sede_codigo']; }
        if (isset($mapa['aula']))       { $original[$mapa['aula']]       = (string) $e['numero_aula']; }
        if (isset($mapa['turno']))      { $original[$mapa['turno']]      = (string) ($e['turno'] ?? ''); }
        if (isset($mapa['sedeNombre'])) { $original[$mapa['sedeNombre']] = (string) ($e['sede_nombre_texto'] ?? ''); }
        if (isset($mapa['fecha']))      { $original[$mapa['fecha']]      = (string) ($e['fecha_texto'] ?? ''); }

        echo '<tr>';

        foreach ($columnas as $c) {
            $v = match ($c) {
                'SEDE_ANTERIOR'  => (string) ($e['sede_antes'] ?? ''),
                'AULA_ANTERIOR'  => (string) ($e['aula_antes'] ?? ''),
                'TURNO_ANTERIOR' => (string) ($e['turno_antes'] ?? ''),
                default          => (string) ($original[$c] ?? valor_calculado($e, $mapa, (string) $c)),
            };

            echo '<td>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</td>';
        }

        echo '</tr>';

        // Con decenas de miles de filas conviene ir soltando lo que ya se armó
        // en vez de guardarlo entero en memoria.
        if (++$filas % 500 === 0) {
            flush();
        }
    }

    echo '</table></body></html>';
    exit;
}

/**
 * Los encabezados con los que se exporta.
 *
 * Se toman de la última carga, que es la que sabe con qué columnas vino el
 * archivo y en qué orden. Si nunca se cargó nada, se usa un juego mínimo para
 * que la descarga siga funcionando en vez de salir vacía.
 */
function columnas_de_exportacion(int $conv): array
{
    $stmt = db()->prepare('
        SELECT columnas FROM cargas_estudiantes
        WHERE convocatoria_id = ? ORDER BY id DESC LIMIT 1
    ');
    $stmt->execute([$conv]);
    $guardado = $stmt->fetchColumn();

    if ($guardado !== false) {
        $datos = json_decode((string) $guardado, true);

        if (is_array($datos) && !empty($datos['columnas'])) {
            return array_values(array_map('strval', $datos['columnas']));
        }
    }

    return ['FORMULA', 'NOMBRE', 'IDENTIFICACION', 'FORMULARIO',
            'SEDE', 'NUMERO_AULA', 'TURNO', 'FECHA'];
}

/** Para una columna que no venía en el archivo original, el valor de la base. */
function valor_calculado(array $e, array $mapa, string $columna): string
{
    $campo = array_search($columna, $mapa, true);

    return match ($campo) {
        'formula'        => (string) ($e['formula'] ?? ''),
        'nombre'         => (string) ($e['nombre'] ?? ''),
        'identificacion' => (string) ($e['identificacion'] ?? ''),
        'formulario'     => (string) ($e['formulario'] ?? ''),
        'sede'           => (string) $e['sede_codigo'],
        'sedeNombre'     => (string) ($e['sede_nombre_texto'] ?? ''),
        'aula'           => (string) $e['numero_aula'],
        'turno'          => (string) ($e['turno'] ?? ''),
        'fecha'          => (string) ($e['fecha_texto'] ?? $e['fecha_examen'] ?? ''),
        default          => '',
    };
}

// ==================================================================
// Comunes
// ==================================================================

/**
 * Corta con un mensaje accionable si el esquema no está al día.
 *
 * POR QUÉ HACE FALTA
 * `migrar.php` lleva la cuenta de las migraciones por nombre de archivo. Si una
 * migración ya aplicada se corrige en su propio archivo en vez de encadenar una
 * nueva, la corrección no vuelve a correr nunca y la tabla se queda a medias.
 *
 * Pasó de verdad con la 018. El síntoma fue un 500 sin explicación al cargar el
 * padrón, con el detalle solo en el log del servidor —que es exactamente donde
 * nadie lo va a buscar cuando algo no funciona el día de la aplicación—.
 *
 * Esta comprobación convierte ese 500 en «falta correr las migraciones».
 */
function exigir_esquema_al_dia(): void
{
    static $listo = false;

    if ($listo) {
        return;
    }

    $columnas = array_column(db()->query('SHOW COLUMNS FROM estudiantes')->fetchAll(), 'Field');

    $faltan = array_values(array_diff(
        ['identificacion', 'formula', 'fecha_texto', 'sede_nombre_texto', 'sede_origen'],
        $columnas
    ));

    if ($faltan !== []) {
        responder_json([
            'success' => false,
            'error'   => 'La base de datos está desactualizada: a la tabla `estudiantes` le faltan '
                       . 'las columnas ' . implode(', ', $faltan) . '. Hay que correr '
                       . '«php scripts/migrar.php» en el servidor antes de usar este módulo.',
        ], 503);
    }

    $listo = true;
}

/** La convocatoria sobre la que se trabaja. */
function convocatoria_exigida_traslados(): int
{
    $conv = convocatoria_actual();

    if ($conv === null) {
        responder_json([
            'success' => false,
            'error'   => 'No hay ninguna convocatoria cargada en el sistema.',
        ], 409);
    }

    return $conv;
}

/**
 * El WHERE y sus valores para «los estudiantes de esta sede y este turno».
 *
 * Va en una función porque lo usan la consulta, la simulación y la aplicación,
 * y son los tres momentos en que el conjunto tiene que ser exactamente el
 * mismo. Escrito tres veces, la aplicación terminaría moviendo un grupo
 * distinto del que se simuló.
 */
function filtro_turno(int $conv, string $sede, string $turno, bool $todos): array
{
    $filtro  = 'convocatoria_id = ? AND sede_codigo = ?';
    $valores = [$conv, $sede];

    if (!$todos) {
        if ($turno === '') {
            // «Sin turno» es un valor, no una ausencia de filtro.
            $filtro .= " AND (turno IS NULL OR turno = '')";
        } else {
            $filtro .= ' AND turno = ?';
            $valores[] = $turno;
        }
    }

    return [$filtro, $valores];
}

function id_de_sede(int $conv, string $codigo): ?int
{
    $stmt = db()->prepare('SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $stmt->execute([$conv, $codigo]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
}

function nombre_de_sede(int $conv, string $codigo): string
{
    $stmt = db()->prepare('SELECT nombre FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $stmt->execute([$conv, $codigo]);
    $n = $stmt->fetchColumn();

    return $n !== false ? (string) $n : '(sede no registrada)';
}

/** El nombre con el que llegó el archivo. */
function nombre_subido(): string
{
    return (string) ($_FILES['archivo']['name'] ?? 'archivo');
}

/**
 * Lee el archivo subido y devuelve sus filas.
 *
 * Los errores de PHP al subir se traducen a algo accionable. «UPLOAD_ERR_INI_SIZE»
 * en pantalla no le dice nada a nadie; «el archivo pesa más de lo que el
 * servidor acepta» sí.
 */
function leer_subida(): array
{
    $archivo = $_FILES['archivo'] ?? null;

    if (!is_array($archivo)) {
        responder_json(['success' => false, 'error' => 'No llegó ningún archivo.'], 400);
    }

    $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        $mensaje = match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'El archivo pesa más de lo que el servidor acepta ('
                . ini_get('upload_max_filesize') . '). Guardalo como CSV, que pesa mucho menos '
                . 'que un .xlsx con el mismo contenido.',
            UPLOAD_ERR_PARTIAL   => 'La subida se cortó a la mitad. Probá de nuevo.',
            UPLOAD_ERR_NO_FILE   => 'No se eligió ningún archivo.',
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
