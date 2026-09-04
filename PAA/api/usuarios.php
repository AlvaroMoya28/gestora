<?php
/**
 * Endpoint de Administración de Usuarios.
 *
 *   GET  accion=listar        → cuentas del sistema
 *   GET  accion=sinCuenta     → personas del directorio que todavía no tienen
 *   POST accion=crear         → crea una cuenta (y la persona, si es nueva)
 *   POST accion=rol           → cambia el rol
 *   POST accion=restablecer   → devuelve la contraseña a la inicial
 *   POST accion=activar       → activa o desactiva la cuenta
 *   POST accion=eliminar      → borra la cuenta
 *   GET  accion=modulos       → catálogo completo, para armar las casillas de excepciones
 *   GET  accion=excepciones   → módulos del rol y excepciones de un usuario puntual
 *   POST accion=guardarExcepciones → reemplaza las excepciones de un usuario
 *
 * SOLO PARA ADMINISTRACIÓN. Cada acción vuelve a comprobar el rol: que el
 * módulo no aparezca en el menú de un funcionario no impide que alguien llame
 * al endpoint a mano.
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

exigir_metodo(['GET', 'POST']);

// Guarda de acceso: antes de cualquier otra cosa.
$yo = exigir_modulo_api("usuarios");

// Cambios de rol y de cuenta: la ficha CSRF es obligatoria en todo POST de
// este módulo, no solo en algunas acciones — ver includes/peticion.php.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

$accion = param('accion', param('action', 'listar'));

try {
    switch ($accion) {
        case 'listar':       listar($yo);       break;
        case 'sinCuenta':    sin_cuenta();      break;
        case 'areas':        areas();           break;
        case 'crear':        crear($yo);        break;
        case 'editar':       editar($yo);       break;
        case 'rol':          cambiar_rol($yo);  break;
        case 'restablecer':  restablecer($yo);  break;
        case 'activar':      activar($yo);      break;
        case 'eliminar':     eliminar($yo);     break;
        case 'autorizarDatosBase': autorizar_datos_base_toggle($yo); break;
        case 'modulos':            modulos_catalogo();          break;
        case 'excepciones':        excepciones($yo);            break;
        case 'guardarExcepciones': guardar_excepciones_accion($yo); break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('usuarios', $e);
}

// ------------------------------------------------------------------

/**
 * Solo un desarrollador ve y toca las cuentas de desarrollo.
 *
 * La cuenta de desarrollo es la que puede todo; si un administrador pudiera
 * cambiarle la contraseña, el rol dejaría de significar nada.
 */
function puede_tocar(array $yo, string $rolDestino): bool
{
    return $rolDestino !== ROL_DESARROLLADOR || $yo['rol'] === ROL_DESARROLLADOR;
}

function listar(array $yo): void
{
    // Se traen también los datos de contacto: son los que se muestran en el
    // Directorio de Funcionarios y se editan desde acá, porque el directorio
    // quedó como pantalla de solo consulta.
    $sql = "
        SELECT u.id, u.usuario, u.rol, u.activo, u.debe_cambiar_contrasena,
               u.autoriza_datos_base,
               u.ultimo_ingreso, u.bloqueado_hasta, u.creado_en,
               p.id AS persona_id, p.nombre, p.correo, p.extension,
               a.nombre AS area,
               GROUP_CONCAT(t.telefono ORDER BY t.id SEPARATOR ' / ') AS telefonos
        FROM usuarios u
        INNER JOIN personas p ON p.id = u.persona_id
        LEFT  JOIN areas a    ON a.id = p.area_id
        LEFT  JOIN persona_telefonos t ON t.persona_id = p.id
    ";

    // Las cuentas de desarrollo no se le muestran a la administración normal.
    if ($yo['rol'] !== ROL_DESARROLLADOR) {
        $sql .= " WHERE u.rol <> '" . ROL_DESARROLLADOR . "'";
    }

    $sql .= "
        GROUP BY u.id, u.usuario, u.rol, u.activo, u.debe_cambiar_contrasena,
                 u.ultimo_ingreso, u.bloqueado_hasta, u.creado_en,
                 p.id, p.nombre, p.correo, p.extension, a.nombre
        ORDER BY p.nombre
    ";

    $usuarios = array_map(static function (array $f): array {
        return [
            'id'            => (int) $f['id'],
            'personaId'     => (int) $f['persona_id'],
            'usuario'       => $f['usuario'],
            'nombre'        => $f['nombre'],
            'correo'        => $f['correo'] ?? '',
            'area'          => $f['area'] ?? '',
            'extension'     => $f['extension'] ?? '',
            'telefonos'     => $f['telefonos'] ?? '',
            'rol'           => $f['rol'],
            'activo'        => (bool) $f['activo'],
            'debeCambiar'   => (bool) $f['debe_cambiar_contrasena'],
            // Administración y desarrollo ya autorizan datos base por su rol
            // (ver autoriza_datos_base() en includes/sesion.php); esta columna
            // es el permiso PUNTUAL para cualquier otra cuenta.
            'autorizaDatosBase' => (bool) $f['autoriza_datos_base'],
            'ultimoIngreso' => $f['ultimo_ingreso'],
            'bloqueado'     => $f['bloqueado_hasta'] !== null
                               && strtotime($f['bloqueado_hasta']) > time(),
            'creadoEn'      => $f['creado_en'],
        ];
    }, db()->query($sql)->fetchAll());

    responder_json([
        'success'  => true,
        'usuarios' => $usuarios,
        'total'    => count($usuarios),
        'yo'       => ['id' => $yo['id'], 'rol' => $yo['rol']],
    ]);
}

/** Áreas del catálogo, para el selector del formulario. */
function areas(): void
{
    $stmt = db()->query('SELECT id, nombre FROM areas ORDER BY orden, nombre');
    responder_json(['success' => true, 'areas' => $stmt->fetchAll()]);
}

/**
 * Id del área por su nombre, creándola si no existe.
 *
 * Las áreas se escriben a mano y son pocas; que se cree sola evita tener que
 * ir a otra pantalla solo porque entró alguien de un equipo nuevo.
 */
function id_area_por_nombre(string $nombre): ?int
{
    $nombre = trim($nombre);

    if ($nombre === '') {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM areas WHERE nombre = ?');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        db()->prepare('INSERT INTO areas (nombre, orden) VALUES (?, 99)')->execute([$nombre]);
        $id = db()->lastInsertId();
    }

    return (int) $id;
}

/**
 * Reemplaza los teléfonos de una persona.
 *
 * Llegan en un solo campo porque así se escriben en la práctica:
 * "8874-0425 / 4701-0551". Se parten por barra, coma o punto y coma, que son
 * los tres separadores que aparecen en los datos reales.
 *
 * Se borran y se vuelven a insertar en vez de intentar casarlos uno por uno:
 * son dos o tres por persona y no tienen identidad propia.
 */
function guardar_telefonos(int $personaId, string $texto): void
{
    db()->prepare('DELETE FROM persona_telefonos WHERE persona_id = ?')->execute([$personaId]);

    $numeros = preg_split('#\s*[/,;]\s*#', trim($texto)) ?: [];
    $insertar = db()->prepare('INSERT INTO persona_telefonos (persona_id, telefono) VALUES (?, ?)');

    foreach ($numeros as $numero) {
        $numero = trim($numero);
        if ($numero !== '') {
            $insertar->execute([$personaId, mb_substr($numero, 0, 20)]);
        }
    }
}

/** Personas del directorio que todavía no tienen cuenta. */
function sin_cuenta(): void
{
    $stmt = db()->query("
        SELECT p.id, p.nombre, p.correo
        FROM personas p
        INNER JOIN areas a ON a.id = p.area_id
        LEFT JOIN usuarios u ON u.persona_id = p.id
        WHERE u.id IS NULL AND p.activo = 1 AND p.oculto = 0 AND a.nombre <> 'IIP'
        ORDER BY p.nombre
    ");

    responder_json(['success' => true, 'personas' => $stmt->fetchAll()]);
}

/**
 * Crea una cuenta.
 *
 * Puede ser para alguien que ya está en el directorio (persona_id) o para
 * alguien nuevo, en cuyo caso también se crea la persona.
 */
function crear(array $yo): void
{
    $rol = param('rol', ROL_FUNCIONARIO);

    if (!in_array($rol, [ROL_FUNCIONARIO, ROL_EXTRAORDINARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR], true)) {
        responder_json(['success' => false, 'error' => 'Rol inválido'], 400);
    }

    if (!puede_tocar($yo, $rol)) {
        responder_json(['success' => false, 'error' => 'Solo un desarrollador puede crear cuentas de desarrollo'], 403);
    }

    $personaId = param_int('persona_id');
    $correo    = strtolower(param('correo'));
    $nombre    = param('nombre');

    db()->beginTransaction();

    if ($personaId === 0) {
        // Persona nueva: hacen falta nombre y correo.
        if ($nombre === '' || $correo === '') {
            db()->rollBack();
            responder_json(['success' => false, 'error' => 'Falta el nombre o el correo'], 400);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            db()->rollBack();
            responder_json(['success' => false, 'error' => 'El correo no es válido'], 400);
        }

        $stmt = db()->prepare('SELECT id FROM personas WHERE correo = ?');
        $stmt->execute([$correo]);
        $existente = $stmt->fetchColumn();

        if ($existente !== false) {
            $personaId = (int) $existente;
        } else {
            $areaId = id_area_por_nombre(param('area'));

            if ($areaId === null) {
                // area_id es obligatorio en la tabla: si no se indicó, se usa
                // la primera del catálogo antes que rechazar el alta.
                $areaId = (int) db()->query('SELECT id FROM areas ORDER BY orden, id LIMIT 1')->fetchColumn();
            }

            db()->prepare("
                INSERT INTO personas (area_id, nombre, correo, extension, activo, oculto, puede_retirar_activos)
                VALUES (?, ?, ?, ?, 1, ?, 1)
            ")->execute([
                $areaId, $nombre, $correo,
                param('extension') ?: null,
                // Igual que en cambiar_rol(): el directorio es solo para
                // funcionario y administrador.
                in_array($rol, [ROL_DESARROLLADOR, ROL_EXTRAORDINARIO], true) ? 1 : 0,
            ]);

            $personaId = (int) db()->lastInsertId();

            // Los teléfonos van en su propia tabla: varias personas tienen dos.
            $telefonos = param('telefonos');
            if ($telefonos !== '') {
                guardar_telefonos($personaId, $telefonos);
            }
        }
    }

    // Nombre de usuario: el correo hasta la arroba.
    $stmt = db()->prepare('SELECT correo, nombre FROM personas WHERE id = ?');
    $stmt->execute([$personaId]);
    $persona = $stmt->fetch();

    if ($persona === false) {
        db()->rollBack();
        responder_json(['success' => false, 'error' => 'La persona no existe'], 404);
    }

    $correoPersona = (string) $persona['correo'];

    if ($correoPersona === '' || !str_contains($correoPersona, '@')) {
        db()->rollBack();
        responder_json([
            'success' => false,
            'error'   => 'Esa persona no tiene correo, y el usuario se arma con el correo',
        ], 422);
    }

    $usuario = strtolower(strstr($correoPersona, '@', true));

    try {
        db()->prepare("
            INSERT INTO usuarios (persona_id, usuario, contrasena_hash, rol, debe_cambiar_contrasena)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $personaId,
            $usuario,
            password_hash(CONTRASENA_INICIAL, PASSWORD_DEFAULT),
            $rol,
            // Ver el comentario en restablecer(): las cuentas de personal
            // extraordinario son compartidas y no se les pide cambiarla.
            $rol === ROL_EXTRAORDINARIO ? 0 : 1,
        ]);
    } catch (PDOException $e) {
        db()->rollBack();

        if ($e->getCode() === '23000') {
            responder_json([
                'success' => false,
                'error'   => "Ya existe una cuenta para {$usuario}",
            ], 409);
        }
        throw $e;
    }

    db()->commit();

    responder_json([
        'success' => true,
        'usuario' => $usuario,
        'rol'     => $rol,
        'mensaje' => "Cuenta creada. Contraseña inicial: " . CONTRASENA_INICIAL,
    ], 201);
}

/**
 * Edita los datos de contacto de la persona detrás de una cuenta.
 *
 * Son los que se ven en el Directorio de Funcionarios. Se editan desde acá
 * porque el directorio quedó como pantalla de consulta: tener el alta y la
 * edición en dos lugares llevaba a que los datos de contacto y el acceso se
 * fueran separando.
 *
 * Si cambia el correo, cambia también el nombre de usuario, porque el usuario
 * es el correo hasta la arroba. Se avisa en la respuesta.
 */
function editar(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés editar cuentas de desarrollo'], 403);
    }

    $nombre = param('nombre');
    $correo = strtolower(param('correo'));

    if ($nombre === '' || $correo === '') {
        responder_json(['success' => false, 'error' => 'El nombre y el correo son obligatorios'], 400);
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responder_json(['success' => false, 'error' => 'El correo no es válido'], 400);
    }

    $personaId = (int) $destino['persona_id'];
    $usuarioNuevo = strtolower(strstr($correo, '@', true) ?: '');
    $cambioUsuario = $usuarioNuevo !== '' && $usuarioNuevo !== $destino['usuario'];

    db()->beginTransaction();

    try {
        db()->prepare("
            UPDATE personas
            SET nombre = ?, correo = ?, extension = ?, area_id = COALESCE(?, area_id)
            WHERE id = ?
        ")->execute([
            $nombre,
            $correo,
            param('extension') ?: null,
            id_area_por_nombre(param('area')),
            $personaId,
        ]);

        // Solo se tocan los teléfonos si vino el campo: mandar la edición sin
        // él no debería borrarlos.
        if (isset(entrada()['telefonos'])) {
            guardar_telefonos($personaId, param('telefonos'));
        }

        if ($cambioUsuario) {
            db()->prepare('UPDATE usuarios SET usuario = ? WHERE id = ?')
                ->execute([$usuarioNuevo, $id]);
        }

        db()->commit();

    } catch (PDOException $e) {
        db()->rollBack();

        if ($e->getCode() === '23000') {
            responder_json([
                'success' => false,
                'error'   => 'Ese correo o ese usuario ya están en uso por otra persona',
            ], 409);
        }
        throw $e;
    }

    responder_json([
        'success' => true,
        'mensaje' => $cambioUsuario
            ? "Datos actualizados. El usuario para entrar ahora es: {$usuarioNuevo}"
            : 'Datos actualizados.',
        'usuario' => $cambioUsuario ? $usuarioNuevo : $destino['usuario'],
    ]);
}

function cambiar_rol(array $yo): void
{
    exigir(['id', 'rol']);

    $id  = param_int('id');
    $rol = param('rol');

    if (!in_array($rol, [ROL_FUNCIONARIO, ROL_EXTRAORDINARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR], true)) {
        responder_json(['success' => false, 'error' => 'Rol inválido'], 400);
    }

    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol']) || !puede_tocar($yo, $rol)) {
        responder_json(['success' => false, 'error' => 'No podés cambiar cuentas de desarrollo'], 403);
    }

    // Quitarse el propio acceso deja el sistema sin quien lo administre.
    if ($id === $yo['id'] && $rol === ROL_FUNCIONARIO) {
        responder_json([
            'success' => false,
            'error'   => 'No podés quitarte a vos mismo el acceso de administración',
        ], 409);
    }

    db()->prepare('UPDATE usuarios SET rol = ? WHERE id = ?')->execute([$rol, $id]);

    // El directorio es "Funcionarios PAA": solo funcionario y administrador
    // pertenecen ahí. Desarrollo no se muestra por ser cuenta de prueba, y
    // Personal Extraordinario tampoco porque es una cuenta compartida entre
    // varias personas físicas, no alguien puntual a quien contactar.
    db()->prepare('UPDATE personas SET oculto = ? WHERE id = ?')
        ->execute([
            in_array($rol, [ROL_DESARROLLADOR, ROL_EXTRAORDINARIO], true) ? 1 : 0,
            (int) $destino['persona_id'],
        ]);

    responder_json(['success' => true, 'rol' => $rol]);
}

/**
 * Devuelve la contraseña a la inicial.
 *
 * Es lo que se usa cuando alguien la olvida: no se puede recuperar la que
 * tenía (justamente porque están cifradas y no se guardan en claro), así que
 * se le pone la inicial y el sistema le pedirá una nueva al entrar.
 */
function restablecer(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés tocar cuentas de desarrollo'], 403);
    }

    // A las cuentas de personal extraordinario no se les exige cambiarla: son
    // compartidas por todo un turno, y si la primera persona que entra elige
    // una nueva, el resto se queda afuera sin saber por qué.
    $exigirCambio = $destino['rol'] === ROL_EXTRAORDINARIO ? 0 : 1;

    db()->prepare("
        UPDATE usuarios
        SET contrasena_hash = ?, debe_cambiar_contrasena = ?,
            intentos_fallidos = 0, bloqueado_hasta = NULL
        WHERE id = ?
    ")->execute([password_hash(CONTRASENA_INICIAL, PASSWORD_DEFAULT), $exigirCambio, $id]);

    responder_json([
        'success' => true,
        'mensaje' => 'Contraseña restablecida a ' . CONTRASENA_INICIAL
            . ($exigirCambio ? '. Se le pedirá una nueva al entrar.' : '. Esta cuenta la usa tal cual.'),
    ]);
}

function activar(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');
    $activo = param('activo') === '1' || param('activo') === 'true';
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés tocar cuentas de desarrollo'], 403);
    }

    if ($id === $yo['id'] && !$activo) {
        responder_json(['success' => false, 'error' => 'No podés desactivar tu propia cuenta'], 409);
    }

    // Al reactivar se limpia también el contador de intentos fallidos: si no,
    // una cuenta que se autobloqueó por eso (ver MAX_INTENTOS en
    // includes/sesion.php) quedaría a un solo intento equivocado de
    // volver a bloquearse apenas la persona intentara entrar de nuevo.
    if ($activo) {
        db()->prepare('UPDATE usuarios SET activo = 1, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?')
            ->execute([$id]);
    } else {
        db()->prepare('UPDATE usuarios SET activo = 0 WHERE id = ?')->execute([$id]);
    }

    responder_json(['success' => true, 'activo' => $activo]);
}

/**
 * Da o quita el permiso PUNTUAL de autorizar la edición de datos base en
 * Ingreso de Datos (ver el candado de supervisor en ese módulo y
 * autoriza_datos_base() en includes/sesion.php).
 *
 * Administración y desarrollo ya lo tienen por su rol: esta columna es para
 * cualquier otra cuenta (Carlos Solera Aguilar es el primer caso) que
 * necesite poder desbloquearlo sin volverse administradora de todo el
 * sistema.
 */
function autorizar_datos_base_toggle(array $yo): void
{
    exigir(['id']);

    $id      = param_int('id');
    $autoriza = param('autoriza') === '1' || param('autoriza') === 'true';
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés tocar cuentas de desarrollo'], 403);
    }

    if (in_array($destino['rol'], ROLES_ADMIN, true)) {
        responder_json([
            'success' => false,
            'error'   => 'Esa cuenta ya autoriza datos base por ser administración o desarrollo; no hace falta marcarla aparte.',
        ], 409);
    }

    db()->prepare('UPDATE usuarios SET autoriza_datos_base = ? WHERE id = ?')
        ->execute([$autoriza ? 1 : 0, $id]);

    bitacora_apuntar(
        'usuarios',
        'autorizarDatosBase',
        "id={$id} usuario={$destino['usuario']} autoriza=" . ($autoriza ? 'si' : 'no')
    );

    responder_json(['success' => true, 'autorizaDatosBase' => $autoriza]);
}

/**
 * Borra la cuenta.
 *
 * La persona se conserva: sigue en el directorio y los tickets que reportó
 * siguen apuntando a ella. Lo que se elimina es el acceso.
 */
function eliminar(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés borrar cuentas de desarrollo'], 403);
    }

    if ($id === $yo['id']) {
        responder_json(['success' => false, 'error' => 'No podés borrar tu propia cuenta'], 409);
    }

    db()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);

    // Borrar la cuenta también la saca de Funcionarios PAA: personas.id es
    // UNIQUE por usuario (ver schema/013), así que esta persona no tiene
    // ninguna otra cuenta a la que le afecte quedar oculta.
    db()->prepare('UPDATE personas SET activo = 0 WHERE id = ?')->execute([(int) $destino['persona_id']]);

    responder_json([
        'success' => true,
        'mensaje' => 'Cuenta eliminada y persona quitada del directorio de Funcionarios PAA.',
    ]);
}

/**
 * El catálogo completo de módulos, para dibujar las casillas de excepciones.
 *
 * Se manda 'otorgable' tal cual para que la pantalla pueda deshabilitar (no
 * esconder) los que no se pueden dar como excepción individual — usuarios,
 * importación y traslados — con una nota de por qué, en vez de que
 * simplemente no aparezcan y alguien se pregunte dónde quedaron.
 */
function modulos_catalogo(): void
{
    $modulos = array_map(static fn(array $m): array => [
        'id'        => $m['id'],
        'nombre'    => $m['nombre'],
        'categoria' => $m['categoria'],
        'otorgable' => $m['otorgable'] ?? true,
    ], modulos_del_sistema());

    responder_json(['success' => true, 'modulos' => $modulos]);
}

/** Los módulos del rol de un usuario y sus excepciones actuales, para premarcar las casillas. */
function excepciones(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés tocar cuentas de desarrollo'], 403);
    }

    $delRol = array_column(modulos_para($destino['rol']), 'id');
    $excepciones = excepciones_de_usuario($id);

    responder_json([
        'success'    => true,
        'rol'        => $destino['rol'],
        'delRol'     => $delRol,
        'agregados'  => $excepciones['agregados'],
        'quitados'   => $excepciones['quitados'],
    ]);
}

function guardar_excepciones_accion(array $yo): void
{
    exigir(['id']);

    $id = param_int('id');
    $destino = buscar_usuario($id);

    if (!puede_tocar($yo, $destino['rol'])) {
        responder_json(['success' => false, 'error' => 'No podés tocar cuentas de desarrollo'], 403);
    }

    $entrada = entrada();
    $agregados = array_map('strval', (array) ($entrada['agregados'] ?? []));
    $quitados  = array_map('strval', (array) ($entrada['quitados'] ?? []));

    guardar_excepciones_usuario($id, $agregados, $quitados);

    responder_json(['success' => true]);
}

function buscar_usuario(int $id): array
{
    $stmt = db()->prepare('SELECT id, persona_id, usuario, rol FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $fila = $stmt->fetch();

    if ($fila === false) {
        responder_json(['success' => false, 'error' => 'La cuenta no existe'], 404);
    }

    return $fila;
}
