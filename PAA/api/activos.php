<?php
/**
 * Endpoint del módulo Activos (préstamo de equipo).
 *
 * Reemplaza a Activos.gs, conservando sus mismas acciones para que el
 * frontend cambie lo menos posible:
 *
 *   GET  accion=listar                  → inventario
 *   GET  accion=listarPersonal          → personal que puede retirar equipo
 *   POST accion=agregarPersonal         → alta de personal
 *   POST accion=actualizarDisponibilidad→ marca un activo disponible / no disponible
 *   POST accion=prestar                 → registra un préstamo y devuelve el número de boleta
 *   POST accion=devolver                → cierra el préstamo y libera el activo
 *   POST accion=enviarBoleta            → manda la boleta al correo de la persona
 *   POST accion=actualizar              → edita un activo (datos + si requiere mantenimiento cada 6 meses)
 *   POST accion=registrarMantenimiento  → anota un mantenimiento y actualiza la fecha
 *   GET  accion=historialMantenimiento  → historial de mantenimientos de un activo
 *
 * Mantenimiento existe acá con la misma lógica que en Inventario de Oficina
 * (equipo_oficina) porque el módulo Inventario de Oficina también muestra
 * (y deja editar) lo que hay acá — laptops y tablets de préstamo también
 * necesitan mantenimiento cada 6 meses, y tiene que quedar guardado en la
 * misma tabla `activos` para que se vea igual desde los dos módulos.
 *
 * DOS COSAS QUE MEJORAN RESPECTO AL APPS SCRIPT
 *   · El número de boleta salía de "la última fila de la hoja", que se rompe
 *     si alguien borra una fila o si dos personas prestan a la vez. Ahora sale
 *     de una secuencia con bloqueo (tabla secuencia_boletas).
 *   · Prestar implicaba dos escrituras sin relación entre sí (marcar el activo
 *     y agregar la fila del préstamo). Si la segunda fallaba, el equipo quedaba
 *     bloqueado sin boleta. Ahora las dos van en una transacción.
 *
 * LO QUE NO CAMBIA: el destinatario del correo no viene del cliente. Se manda
 * el NOMBRE de la persona y el correo se resuelve contra el catálogo.
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("activos");

require_once __DIR__ . '/../includes/correo.php';

exigir_metodo(['GET', 'POST']);

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':                   listar_activos();          break;
        case 'listarPersonal':           listar_personal();         break;
        case 'agregarPersonal':          agregar_personal();        break;
        case 'actualizarDisponibilidad': actualizar_disponibilidad(); break;
        case 'prestar':                  prestar();                 break;
        case 'devolver':                 devolver();                break;
        case 'enviarBoleta':
        case 'enviarBoletaPdf':          enviar_boleta();           break;
        case 'actualizar':               actualizar_activo();       break;
        case 'registrarMantenimiento':   registrar_mantenimiento_activo($yo); break;
        case 'historialMantenimiento':   historial_mantenimiento_activo();    break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('activos', $e);
}

// ------------------------------------------------------------------

// Igual que en Inventario de Oficina: cada cuántos meses toca mantenimiento,
// y con cuántos días de anticipación se avisa. Se duplica en vez de
// compartirse porque son dos endpoints independientes con su propia tabla.
const MESES_ENTRE_MANTENIMIENTOS = 6;
const DIAS_AVISO_MANTENIMIENTO   = 30;

// Igual que en Inventario de Oficina: no es lo mismo que "disponible/activo"
// — algo dañado sigue en el inventario, sólo que hay que poder anotarlo.
const CONDICIONES_VALIDAS = ['bueno', 'regular', 'danado'];

function condicion_valida_activo(string $valor): string
{
    return in_array($valor, CONDICIONES_VALIDAS, true) ? $valor : 'bueno';
}

function proximo_mantenimiento_activo(?string $ultimoMantenimiento, string $creadoEn): array
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

function listar_activos(): void
{
    $stmt = db()->query("
        SELECT a.id, a.codigo, a.nombre, a.condicion, a.serie_placa, a.imei,
               a.accesorios, a.estado_obs, a.disponible,
               a.requiere_mantenimiento, a.ultimo_mantenimiento, a.creado_en,
               p.boleta        AS boleta_actual,
               p.funcionario   AS prestado_a,
               p.prestado_en
        FROM activos a
        -- Préstamo abierto, si lo hay: permite mostrar en la misma lista quién
        -- tiene el equipo, cosa que el Sheet no podía sin cruzar dos hojas.
        LEFT JOIN prestamos p
               ON p.activo_id = a.id AND p.estado = 'prestado'
        WHERE a.activo = 1
        ORDER BY a.nombre, a.codigo
    ");

    $sinMantenimiento = [
        'proximoMantenimiento' => null, 'diasParaMantenimiento' => null,
        'mantenimientoVencido' => false, 'mantenimientoPorVencer' => false,
    ];

    $activos = array_map(static function (array $f) use ($sinMantenimiento): array {
        $mant = ((bool) $f['requiere_mantenimiento'])
            ? proximo_mantenimiento_activo($f['ultimo_mantenimiento'], $f['creado_en'])
            : $sinMantenimiento;

        return [
            'id'          => (int) $f['id'],
            'Codigo'      => $f['codigo'],
            'Nombre'      => $f['nombre'],
            'condicion'   => $f['condicion'] ?? 'bueno',
            'SeriePlaca'  => $f['serie_placa'] ?? '',
            'IMEI'        => $f['imei'] ?? '',
            'Accesorios'  => $f['accesorios'] ?? '',
            'EstadoObs'   => $f['estado_obs'] ?? '',
            // La columna "Disponible" del Sheet no era un sí/no: cuando el
            // equipo estaba prestado, la celda tenía el NOMBRE de quien lo
            // tenía, y eso es lo que la pantalla muestra en "Estado". Se
            // reproduce igual: "SI" si está libre, el nombre si está prestado.
            'Disponible'  => $f['disponible'] ? 'SI' : ($f['prestado_a'] ?: 'NO'),
            'disponible'  => (bool) $f['disponible'],
            'boletaActual'=> $f['boleta_actual'] !== null ? (int) $f['boleta_actual'] : null,
            'prestadoA'   => $f['prestado_a'] ?? '',
            'prestadoEn'  => $f['prestado_en'] ?? '',
            'requiereMantenimiento' => (bool) $f['requiere_mantenimiento'],
            'ultimoMantenimiento'   => $f['ultimo_mantenimiento'],
        ] + $mant;
    }, $stmt->fetchAll());

    responder_json([
        'success' => true,
        'activos' => $activos,
        'total'   => count($activos),
    ]);
}

/** Edita los datos de un activo, incluyendo si requiere mantenimiento cada 6 meses. */
function actualizar_activo(): void
{
    exigir_csrf();
    exigir(['id', 'codigo', 'nombre']);

    $id     = param_int('id');
    $codigo = param('codigo');
    $nombre = param('nombre');

    if ($codigo === '' || $nombre === '') {
        responder_json(['success' => false, 'error' => 'Faltan el código o el nombre'], 400);
    }

    $stmt = db()->prepare('SELECT id FROM activos WHERE id = ? AND activo = 1');
    $stmt->execute([$id]);
    if ($stmt->fetch() === false) {
        responder_json(['success' => false, 'error' => 'Ese activo no existe'], 404);
    }

    $choque = db()->prepare('SELECT id FROM activos WHERE codigo = ? AND id <> ?');
    $choque->execute([$codigo, $id]);
    if ($choque->fetch() !== false) {
        responder_json(['success' => false, 'error' => "Ya existe otro activo con el código {$codigo}"], 409);
    }

    db()->prepare('
        UPDATE activos SET
            codigo = ?, nombre = ?, condicion = ?, serie_placa = ?, imei = ?, accesorios = ?,
            estado_obs = ?, requiere_mantenimiento = ?
        WHERE id = ?
    ')->execute([
        $codigo, $nombre, condicion_valida_activo(param('condicion')),
        param('seriePlaca') ?: null, param('imei') ?: null, param('accesorios') ?: null,
        param('estadoObs') ?: null, param('requiereMantenimiento') === '1' ? 1 : 0,
        $id,
    ]);

    bitacora_apuntar('activos', 'actualizar', "id={$id} codigo={$codigo}");

    responder_json(['success' => true]);
}

function registrar_mantenimiento_activo(array $yo): void
{
    exigir_csrf();
    exigir(['id']);
    $id            = param_int('id');
    $fecha         = param('fecha') ?: date('Y-m-d');
    $observaciones = param('observaciones');

    $stmt = db()->prepare('SELECT id FROM activos WHERE id = ? AND activo = 1');
    $stmt->execute([$id]);
    if ($stmt->fetch() === false) {
        responder_json(['success' => false, 'error' => 'Ese activo no existe'], 404);
    }

    db()->prepare('
        INSERT INTO activos_mantenimientos (activo_id, fecha, realizado_por, observaciones)
        VALUES (?, ?, ?, ?)
    ')->execute([$id, $fecha, $yo['nombre'], $observaciones ?: null]);

    db()->prepare('
        UPDATE activos
        SET ultimo_mantenimiento = (
                SELECT MAX(fecha) FROM activos_mantenimientos WHERE activo_id = ?
            ),
            requiere_mantenimiento = 1
        WHERE id = ?
    ')->execute([$id, $id]);

    bitacora_apuntar('activos', 'registrarMantenimiento', "id={$id} fecha={$fecha}");

    responder_json(['success' => true]);
}

function historial_mantenimiento_activo(): void
{
    exigir(['id']);
    $id = param_int('id');

    $stmt = db()->prepare('
        SELECT fecha, realizado_por, observaciones, creado_en
        FROM activos_mantenimientos
        WHERE activo_id = ?
        ORDER BY fecha DESC, id DESC
    ');
    $stmt->execute([$id]);

    responder_json(['success' => true, 'historial' => $stmt->fetchAll()]);
}

/**
 * Personal habilitado para retirar equipo.
 *
 * Sale del catálogo `personas`, el mismo del directorio de coordinadores. En
 * el sistema viejo esto era una hoja aparte con las mismas personas cargadas
 * de nuevo, que se desincronizaba en cuanto alguien cambiaba de correo.
 */
function listar_personal(): void
{
    $stmt = db()->query("
        SELECT p.id, p.nombre, p.correo, ar.nombre AS area,
               GROUP_CONCAT(t.telefono ORDER BY t.id SEPARATOR '|') AS telefonos
        FROM personas p
        LEFT JOIN areas ar ON ar.id = p.area_id
        LEFT JOIN persona_telefonos t ON t.persona_id = p.id
        WHERE p.activo = 1 AND p.oculto = 0 AND p.puede_retirar_activos = 1
        GROUP BY p.id, p.nombre, p.correo, ar.nombre
        ORDER BY p.nombre
    ");

    $personal = array_map(static function (array $f): array {
        $telefonos = $f['telefonos'] !== null && $f['telefonos'] !== ''
            ? explode('|', $f['telefonos'])
            : [];

        return [
            'id'         => (int) $f['id'],
            'Area'       => $f['area'] ?? '',
            'Persona'    => $f['nombre'],
            'Correo'     => $f['correo'] ?? '',
            'Telefono1'  => $telefonos[0] ?? '',
            'Telefono2'  => $telefonos[1] ?? '',
        ];
    }, $stmt->fetchAll());

    responder_json(['success' => true, 'personal' => $personal, 'total' => count($personal)]);
}

function agregar_personal(): void
{
    // Se exige acá y no arriba, gateada por método: este endpoint todavía
    // tiene un modo de compatibilidad que escribe por GET (ver el frontend,
    // Activos/index.php) heredado de Apps Script — sin esto, ese camino se
    // quedaría sin ninguna protección.
    exigir_csrf();
    exigir(['persona']);

    $nombre = param('persona', param('nombre'));
    $correo = param('correo');
    $area   = param('area');

    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responder_json(['success' => false, 'error' => 'El correo no es válido'], 400);
    }

    db()->beginTransaction();

    $areaId = null;
    if ($area !== '') {
        $stmt = db()->prepare('SELECT id FROM areas WHERE nombre = ?');
        $stmt->execute([$area]);
        $areaId = $stmt->fetchColumn();

        if ($areaId === false) {
            db()->prepare('INSERT INTO areas (nombre, orden) VALUES (?, 99)')->execute([$area]);
            $areaId = (int) db()->lastInsertId();
        }
    }

    // Si la persona ya existe (mismo correo), se actualiza en vez de duplicar:
    // es el error más común al dar de alta a alguien que ya estaba en el
    // directorio.
    $stmt = db()->prepare("
        INSERT INTO personas (area_id, nombre, correo, activo, puede_retirar_activos)
        VALUES (:area, :nombre, :correo, 1, 1)
        ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            area_id = COALESCE(VALUES(area_id), area_id),
            puede_retirar_activos = 1,
            id = LAST_INSERT_ID(id)
    ");
    $stmt->execute([
        'area'   => $areaId !== null ? (int) $areaId : null,
        'nombre' => $nombre,
        'correo' => $correo !== '' ? $correo : null,
    ]);

    $personaId = (int) db()->lastInsertId();

    $insTel = db()->prepare("
        INSERT INTO persona_telefonos (persona_id, telefono)
        SELECT :pid, :tel FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM persona_telefonos WHERE persona_id = :pid2 AND telefono = :tel2
        )
    ");
    foreach (['telefono1', 'telefono2'] as $campo) {
        $tel = param($campo);
        if ($tel !== '') {
            $insTel->execute(['pid' => $personaId, 'tel' => $tel, 'pid2' => $personaId, 'tel2' => $tel]);
        }
    }

    db()->commit();

    responder_json(['success' => true, 'id' => $personaId, 'persona' => $nombre]);
}

/**
 * Marca uno o varios activos como disponibles o prestados.
 *
 * Es la acción que usa la pantalla al entregar y al recibir equipo. El valor
 * de `estado` funciona como en el Sheet original:
 *
 *   · "SI" / "SÍ"  → queda disponible, y se cierra el préstamo abierto.
 *   · cualquier otro texto → es el NOMBRE de quien se lo lleva: el equipo
 *     queda no disponible y se abre un préstamo a esa persona.
 *
 * Ese segundo caso es lo que hacía que la columna "Disponible" del Sheet
 * tuviera nombres de gente. Acá el nombre no se pierde: pasa a `prestamos`,
 * donde además queda la fecha y se puede saber después quién tuvo qué.
 */
function actualizar_disponibilidad(): void
{
    exigir_csrf();

    // La pantalla manda varios códigos al prestar o recibir una tanda.
    $entrada = entrada();
    $codigos = $entrada['codigos'] ?? null;

    if (!is_array($codigos)) {
        $codigos = array_filter([param('codigo')]);
    }

    if ($codigos === []) {
        responder_json(['success' => false, 'error' => 'No se recibió ningún código'], 400);
    }

    $valor = param('estado', param('disponible'));

    // "SI"/"SÍ" en cualquier combinación de mayúsculas y tildes.
    $normalizado = strtoupper(strtr(trim($valor), ['Í' => 'I', 'í' => 'i']));
    $quedaDisponible = in_array($normalizado, ['SI', 'S', '1', 'TRUE'], true);
    $funcionario = $quedaDisponible ? null : trim($valor);

    $buscar   = db()->prepare('SELECT id, nombre FROM activos WHERE codigo = ? AND activo = 1');
    $marcar   = db()->prepare('UPDATE activos SET disponible = ? WHERE id = ?');
    $cerrar   = db()->prepare("
        UPDATE prestamos SET estado = 'devuelto', devuelto_en = NOW()
        WHERE activo_id = ? AND estado = 'prestado'
    ");
    $persona  = db()->prepare('SELECT id FROM personas WHERE nombre = ? AND activo = 1 LIMIT 1');
    $abrir    = db()->prepare("
        INSERT INTO prestamos (boleta, activo_id, persona_id, funcionario, estado)
        VALUES (?, ?, ?, ?, 'prestado')
    ");

    $aplicados = [];
    $noEncontrados = [];

    db()->beginTransaction();

    foreach ($codigos as $codigo) {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            continue;
        }

        $buscar->execute([$codigo]);
        $activo = $buscar->fetch();

        if ($activo === false) {
            $noEncontrados[] = $codigo;
            continue;
        }

        $marcar->execute([$quedaDisponible ? 1 : 0, (int) $activo['id']]);

        // Al devolver se cierra el préstamo que estuviera abierto; al prestar
        // también, por si el anterior había quedado sin cerrar.
        $cerrar->execute([(int) $activo['id']]);

        if (!$quedaDisponible && $funcionario !== '') {
            $persona->execute([$funcionario]);
            $personaId = $persona->fetchColumn();

            db()->prepare('UPDATE secuencia_boletas SET ultimo = ultimo + 1 WHERE id = 1')->execute();
            $boleta = (int) db()->query('SELECT ultimo FROM secuencia_boletas WHERE id = 1')->fetchColumn();

            $abrir->execute([
                $boleta,
                (int) $activo['id'],
                $personaId !== false ? (int) $personaId : null,
                $funcionario,
            ]);
        }

        $aplicados[] = $codigo;
    }

    db()->commit();

    if ($aplicados === []) {
        responder_json([
            'success' => false,
            'error'   => 'Ningún código existe: ' . implode(', ', $noEncontrados),
        ], 404);
    }

    responder_json([
        'success'       => true,
        'ok'            => true,
        'actualizados'  => $aplicados,
        'noEncontrados' => $noEncontrados,
        'disponible'    => $quedaDisponible,
        'funcionario'   => $funcionario,
    ]);
}

/**
 * Registra un préstamo.
 *
 * Todo pasa dentro de una transacción con el activo bloqueado (SELECT ... FOR
 * UPDATE): sin eso, dos personas escaneando el mismo equipo a la vez podrían
 * llevárselo las dos, cada una con su boleta.
 */
function prestar(): void
{
    exigir_csrf();
    exigir(['codigo', 'funcionario']);

    $codigo      = param('codigo');
    $funcionario = param('funcionario', param('persona'));
    $observacion = param('observacion');

    db()->beginTransaction();

    $stmt = db()->prepare('SELECT id, nombre, disponible FROM activos WHERE codigo = ? AND activo = 1 FOR UPDATE');
    $stmt->execute([$codigo]);
    $activo = $stmt->fetch();

    if ($activo === false) {
        db()->rollBack();
        responder_json(['success' => false, 'error' => "Código no encontrado: {$codigo}"], 404);
    }

    if ((int) $activo['disponible'] === 0) {
        db()->rollBack();
        responder_json(['success' => false, 'error' => 'El activo ya está prestado'], 409);
    }

    // Persona del catálogo, si el nombre calza. No es obligatorio: en el
    // mostrador a veces retira alguien que todavía no está cargado.
    $stmtP = db()->prepare('SELECT id FROM personas WHERE nombre = ? AND activo = 1 LIMIT 1');
    $stmtP->execute([$funcionario]);
    $personaId = $stmtP->fetchColumn();

    // Secuencia de boleta. El UPDATE bloquea la fila hasta el commit, así que
    // dos préstamos simultáneos obtienen números distintos.
    db()->prepare('UPDATE secuencia_boletas SET ultimo = ultimo + 1 WHERE id = 1')->execute();
    $boleta = (int) db()->query('SELECT ultimo FROM secuencia_boletas WHERE id = 1')->fetchColumn();

    db()->prepare("
        INSERT INTO prestamos (boleta, activo_id, persona_id, funcionario, observacion, estado)
        VALUES (?, ?, ?, ?, ?, 'prestado')
    ")->execute([
        $boleta,
        (int) $activo['id'],
        $personaId !== false ? (int) $personaId : null,
        $funcionario,
        $observacion !== '' ? $observacion : null,
    ]);

    db()->prepare('UPDATE activos SET disponible = 0 WHERE id = ?')->execute([(int) $activo['id']]);

    db()->commit();

    responder_json([
        'success' => true,
        'boleta'  => $boleta,
        'codigo'  => $codigo,
        'nombre'  => $activo['nombre'],
    ]);
}

/**
 * Cierra un préstamo abierto y libera el activo.
 *
 * El Apps Script no tenía esta acción: la devolución se hacía cambiando la
 * columna "Disponible" a mano en el Sheet, y el historial quedaba diciendo
 * PRESTADO para siempre.
 */
function devolver(): void
{
    exigir_csrf();

    $codigo = param('codigo');
    $boleta = param_int('boleta');

    if ($codigo === '' && $boleta === 0) {
        responder_json(['success' => false, 'error' => 'Falta el código del activo o el número de boleta'], 400);
    }

    db()->beginTransaction();

    if ($boleta > 0) {
        $stmt = db()->prepare("SELECT id, activo_id FROM prestamos WHERE boleta = ? AND estado = 'prestado'");
        $stmt->execute([$boleta]);
    } else {
        $stmt = db()->prepare("
            SELECT p.id, p.activo_id
            FROM prestamos p
            INNER JOIN activos a ON a.id = p.activo_id
            WHERE a.codigo = ? AND p.estado = 'prestado'
            ORDER BY p.prestado_en DESC
            LIMIT 1
        ");
        $stmt->execute([$codigo]);
    }

    $prestamo = $stmt->fetch();

    if ($prestamo === false) {
        db()->rollBack();
        responder_json(['success' => false, 'error' => 'No hay un préstamo abierto para eso'], 404);
    }

    db()->prepare("UPDATE prestamos SET estado = 'devuelto', devuelto_en = NOW() WHERE id = ?")
        ->execute([(int) $prestamo['id']]);
    db()->prepare('UPDATE activos SET disponible = 1 WHERE id = ?')
        ->execute([(int) $prestamo['activo_id']]);

    db()->commit();

    responder_json(['success' => true, 'prestamoId' => (int) $prestamo['id']]);
}

/**
 * Envía la boleta al correo de la persona.
 *
 * El cliente manda el NOMBRE, no la dirección: la página es pública y aceptar
 * un destinatario arbitrario convertiría esto en un relay de correo a nombre
 * de la cuenta del PAA.
 */
function enviar_boleta(): void
{
    exigir_csrf();
    exigir(['persona']);

    $persona = param('persona');
    $html    = (string) (entrada()['html'] ?? '');

    if (trim($html) === '') {
        responder_json(['success' => false, 'error' => 'Falta el contenido de la boleta'], 400);
    }

    $destinatario = correo_de_persona($persona);

    if ($destinatario === null) {
        responder_json([
            'success' => false,
            'error'   => "{$persona} no tiene un correo registrado en el sistema",
        ], 422);
    }

    $boleta = param('boleta');
    $resultado = enviar_correo_html(
        'activos',
        $destinatario,
        param('asunto', 'Boleta de préstamo de equipo' . ($boleta !== '' ? " N.º {$boleta}" : '')),
        $html,
        $boleta
    );

    if (!$resultado['success']) {
        responder_json($resultado, 502);
    }

    responder_json(['success' => true, 'destinatario' => $destinatario, 'boleta' => $boleta]);
}
