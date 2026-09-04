<?php
/**
 * Sesión y control de acceso.
 *
 * Todo lo que se sirve por HTTP —páginas y endpoints— pasa por acá. Una
 * página que se olvide de llamar a exigir_login() queda abierta a cualquiera,
 * así que al agregar un módulo nuevo eso es lo primero que hay que poner.
 *
 * ROLES
 *   funcionario    → directorio del personal y reporte de averías
 *   extraordinario → solo Ingreso de Datos; personal contratado para el embalaje
 *   administrador  → todo el centro de herramientas
 *   desarrollador  → todo, y además oculto del directorio y los selectores
 *
 * QUÉ VE CADA UNO no se decide acá: está en includes/modulos.php, que es de
 * donde salen tanto el menú del portal como las guardas de cada página. Tener
 * esas dos cosas en un solo lugar es lo que evita que alguien vea una tarjeta
 * que después le rebota.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Se carga acá y no en cada endpoint: todos los de api/ incluyen sesion.php
// para exigir_login_api(), así que de esta forma la bitácora queda activa en
// todos sin depender de que cada archivo se acuerde de pedirla.
require_once __DIR__ . '/bitacora.php';

const ROL_FUNCIONARIO    = 'funcionario';
const ROL_EXTRAORDINARIO = 'extraordinario';
const ROL_ADMINISTRADOR  = 'administrador';
const ROL_DESARROLLADOR  = 'desarrollador';

/** Roles con acceso completo. */
const ROLES_ADMIN = [ROL_ADMINISTRADOR, ROL_DESARROLLADOR];

/** Cómo se nombra cada rol en pantalla. */
const NOMBRES_ROLES = [
    ROL_FUNCIONARIO    => 'Funcionario',
    ROL_EXTRAORDINARIO => 'Personal extraordinario',
    ROL_ADMINISTRADOR  => 'Administrador',
    ROL_DESARROLLADOR  => 'Desarrollador',
];

/** Contraseña con la que se crean todas las cuentas. Se cambia al primer ingreso. */
const CONTRASENA_INICIAL = 'paa2026';

/** Mínimo para la contraseña propia. */
const LARGO_MINIMO_CONTRASENA = 8;

/**
 * Intentos fallidos seguidos antes de bloquear la cuenta.
 *
 * Al llegar acá la cuenta se DESACTIVA (no es una espera de minutos): queda
 * igual que si administración la hubiera apagado a mano, y solo administración
 * o desarrollo puede volver a activarla desde Administración de Usuarios. Es
 * a propósito más estricto que un simple temporizador — alguien tanteando
 * contraseñas no puede simplemente esperar y volver a intentar.
 */
const MAX_INTENTOS = 3;

/**
 * Minutos que dura un bloqueo temporal de OTRO tipo (el de edición
 * concurrente de una sede en Ingreso de Datos — ver `bloqueos_sede` y
 * `MINUTOS_BLOQUEO` en api/ingreso_datos.php). No tiene relación con el
 * bloqueo de cuentas de acá arriba; comparte nombre por herencia del mismo
 * vocabulario, no la lógica.
 */
const MINUTOS_BLOQUEO = 15;

/**
 * Minutos que queda desbloqueada la edición de datos base (Ingreso de Datos)
 * después de que alguien autorizado pone su clave. Como la caja registradora
 * del supermercado: quien autoriza no se queda ahí parado vigilando cada
 * campo, deja la ventana abierta un rato para que la persona que está
 * digitando termine el cambio, y después se vuelve a cerrar sola.
 */
const MINUTOS_AUTORIZACION_DATOS_BASE = 10;

/**
 * Minutos de inactividad antes de cerrar la sesión sola.
 *
 * Es contra el olvido de una pestaña abierta y sin atender, no un límite fijo
 * de una hora desde que se entra: mientras la persona sigue usando el
 * sistema, cada página vuelve a correr el reloj (ver usuario_actual()). Quien
 * trabaja seguido nunca lo nota; quien deja la sesión abierta y se va, sí.
 */
const MINUTOS_INACTIVIDAD_SESION = 60;

/** Si la conexión actual es HTTPS, directa o detrás de un proxy que la termina antes. */
function conexion_es_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/**
 * Arranca la sesión con las opciones de seguridad puestas.
 *
 * Se llama sola desde las demás funciones; no hace falta invocarla a mano.
 */
function sesion_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // httponly: el JavaScript no puede leer la cookie, así que un XSS no se
    //           lleva la sesión.
    // samesite: la cookie no viaja en peticiones que vengan de otro sitio,
    //           que es la defensa base contra CSRF.
    // secure:   solo por HTTPS. El sitio ya vive en https://gestionpaa.ucr.ac.cr/
    //           y el .htaccess de la raíz redirige todo lo que llegue por
    //           HTTP, así que en el uso normal esto siempre da true. Se
    //           revisa también X-Forwarded-Proto por si el tráfico entra por
    //           un balanceador de UCR que termina el TLS antes de llegar
    //           acá —ahí Apache vería la conexión interna como HTTP aunque
    //           el navegador sí esté en HTTPS, y $_SERVER['HTTPS'] solo no
    //           alcanzaría para darse cuenta.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => conexion_es_https(),
    ]);

    session_name('PAASESION');
    session_start();
}

/**
 * Datos del usuario conectado, o null si no hay sesión.
 *
 * `rol` es el que manda en TODOS los permisos del sistema (módulos, chat,
 * dashboards): si un desarrollador está probando el sistema como otro rol
 * (ver simular_rol()), acá ya viene cambiado, así que no hace falta que cada
 * guarda del sistema sepa nada de la simulación. `rolReal` es el de la
 * cuenta de verdad, para el interruptor mismo y para dejar rastro de que un
 * mensaje se mandó en modo prueba (ver chat_enviar() en api/chat.php).
 */
function usuario_actual(): ?array
{
    sesion_iniciar();

    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    $ahora = time();
    $ultimoUso = (int) ($_SESSION['ultimo_uso'] ?? 0);

    if ($ultimoUso > 0 && ($ahora - $ultimoUso) > MINUTOS_INACTIVIDAD_SESION * 60) {
        cerrar_sesion();
        return null;
    }

    $_SESSION['ultimo_uso'] = $ahora;

    $rolReal = (string) ($_SESSION['rol'] ?? ROL_FUNCIONARIO);
    $rolSimulado = $_SESSION['rol_simulado'] ?? null;

    // Solo un desarrollador de verdad puede estar simulando: si la sesión
    // quedara con el resto del arreglo pero cambiara el rol real (no debería
    // pasar), no se hereda una simulación de otra cuenta.
    $rolActivo = ($rolReal === ROL_DESARROLLADOR && is_string($rolSimulado))
        ? $rolSimulado
        : $rolReal;

    return [
        'id'              => (int) $_SESSION['usuario_id'],
        'persona_id'      => (int) ($_SESSION['persona_id'] ?? 0),
        'usuario'         => (string) ($_SESSION['usuario'] ?? ''),
        'nombre'          => (string) ($_SESSION['nombre'] ?? ''),
        'correo'          => (string) ($_SESSION['correo'] ?? ''),
        'rol'             => $rolActivo,
        'rolReal'         => $rolReal,
        'simulando'       => $rolActivo !== $rolReal,
        'debe_cambiar'    => !empty($_SESSION['debe_cambiar']),
    ];
}

/**
 * Prueba el sistema como si fuera otro rol, sin cerrar sesión.
 *
 * Herramienta de desarrollo. El rol de sesión (`rol`) nunca se toca — la
 * simulación vive en una clave aparte — así que siempre se puede volver, y
 * nadie que no sea desarrollador de verdad puede activarla ni heredarla.
 */
function simular_rol(?string $rol): void
{
    sesion_iniciar();

    if (($_SESSION['rol'] ?? '') !== ROL_DESARROLLADOR) {
        return;
    }

    if ($rol === null || $rol === ROL_DESARROLLADOR) {
        unset($_SESSION['rol_simulado']);
        return;
    }

    if (in_array($rol, [ROL_FUNCIONARIO, ROL_EXTRAORDINARIO, ROL_ADMINISTRADOR], true)) {
        $_SESSION['rol_simulado'] = $rol;
    }
}

function hay_sesion(): bool
{
    return usuario_actual() !== null;
}

function es_admin(): bool
{
    $u = usuario_actual();
    return $u !== null && in_array($u['rol'], ROLES_ADMIN, true);
}

function es_desarrollador(): bool
{
    $u = usuario_actual();
    return $u !== null && $u['rol'] === ROL_DESARROLLADOR;
}

/**
 * Comprueba usuario y contraseña contra la base, con el mismo bloqueo por
 * intentos fallidos que usa el login. NO toca la sesión: ni abre una ni la
 * cierra. Es lo que reutilizan tanto autenticar() (que sí abre sesión) como
 * autorizar_edicion_base() (que verifica a OTRA persona sin desconectar a
 * quien ya está usando el navegador — ver el comentario de esa función).
 *
 * @return array{ok: bool, error?: string, fila?: array}
 */
function verificar_credenciales(string $usuario, string $contrasena): array
{
    $usuario = strtolower(trim($usuario));

    // Por si alguien escribe el correo completo: se queda con lo de antes
    // de la arroba, que es lo que se pidió como nombre de usuario.
    if (str_contains($usuario, '@')) {
        $usuario = strstr($usuario, '@', true) ?: $usuario;
    }

    if ($usuario === '' || $contrasena === '') {
        return ['ok' => false, 'error' => 'Escribí el usuario y la contraseña.'];
    }

    $stmt = db()->prepare("
        SELECT u.id, u.persona_id, u.usuario, u.contrasena_hash, u.rol,
               u.debe_cambiar_contrasena, u.activo, u.intentos_fallidos,
               u.bloqueado_hasta,
               p.nombre, p.correo
        FROM usuarios u
        INNER JOIN personas p ON p.id = u.persona_id
        WHERE u.usuario = ?
    ");
    $stmt->execute([$usuario]);
    $fila = $stmt->fetch();

    // El mismo mensaje para "no existe" y "contraseña incorrecta": decir cuál
    // de las dos falló le confirma a quien tantea qué usuarios existen.
    $mensajeGenerico = 'Usuario o contraseña incorrectos.';

    if ($fila === false) {
        registrar_ingreso(null, $usuario, false);
        return ['ok' => false, 'error' => $mensajeGenerico];
    }

    if ((int) $fila['activo'] === 0) {
        registrar_ingreso((int) $fila['id'], $usuario, false);
        return ['ok' => false, 'error' => 'Esta cuenta está desactivada. Hablá con la administración.'];
    }

    // Solo queda por compatibilidad con filas que ya tuvieran un bloqueo
    // temporal de antes de este cambio (ver MAX_INTENTOS): las cuentas nuevas
    // que se bloquean ya no usan bloqueado_hasta, se desactivan directamente.
    if ($fila['bloqueado_hasta'] !== null && strtotime($fila['bloqueado_hasta']) > time()) {
        $minutos = (int) ceil((strtotime($fila['bloqueado_hasta']) - time()) / 60);
        return ['ok' => false, 'error' => "Demasiados intentos fallidos. Probá de nuevo en {$minutos} minuto(s)."];
    }

    if (!password_verify($contrasena, $fila['contrasena_hash'])) {
        $intentos = ((int) $fila['intentos_fallidos']) + 1;

        if ($intentos >= MAX_INTENTOS) {
            db()->prepare("
                UPDATE usuarios
                SET intentos_fallidos = 0, bloqueado_hasta = NULL, activo = 0
                WHERE id = ?
            ")->execute([(int) $fila['id']]);

            registrar_ingreso((int) $fila['id'], $usuario, false);
            bitacora_apuntar(
                'sesion',
                'bloqueoAutomatico',
                "usuario «{$usuario}» desactivado tras {$intentos} intentos fallidos seguidos",
                false
            );

            return [
                'ok'    => false,
                'error' => 'Esta cuenta se bloqueó por demasiados intentos fallidos. '
                    . 'Pedile a un administrador o desarrollador que la reactive.',
            ];
        }

        db()->prepare('UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?')
            ->execute([$intentos, (int) $fila['id']]);

        registrar_ingreso((int) $fila['id'], $usuario, false);
        return ['ok' => false, 'error' => $mensajeGenerico];
    }

    // Contraseña correcta: se limpia el contador.
    db()->prepare("
        UPDATE usuarios
        SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_ingreso = NOW()
        WHERE id = ?
    ")->execute([(int) $fila['id']]);

    registrar_ingreso((int) $fila['id'], $usuario, true);

    return ['ok' => true, 'fila' => $fila];
}

/**
 * Comprueba usuario y contraseña y, si son correctos, abre la sesión.
 *
 * @return array{ok: bool, error?: string, usuario?: array}
 */
function autenticar(string $usuario, string $contrasena): array
{
    sesion_iniciar();

    $resultado = verificar_credenciales($usuario, $contrasena);

    if (!$resultado['ok']) {
        return $resultado;
    }

    $fila = $resultado['fila'];

    // Se cambia el identificador de sesión al entrar: si alguien logró fijar
    // uno de antemano, deja de servirle.
    session_regenerate_id(true);

    $_SESSION['usuario_id']   = (int) $fila['id'];
    $_SESSION['persona_id']   = (int) $fila['persona_id'];
    $_SESSION['usuario']      = $fila['usuario'];
    $_SESSION['nombre']       = $fila['nombre'];
    $_SESSION['correo']       = $fila['correo'];
    $_SESSION['rol']          = $fila['rol'];
    $_SESSION['debe_cambiar'] = (int) $fila['debe_cambiar_contrasena'] === 1;

    return ['ok' => true, 'usuario' => usuario_actual()];
}

/**
 * ¿Esta cuenta puede autorizar la edición de los datos base de una sede en
 * Ingreso de Datos (cantidad de aulas, estudiantes por aula, fórmula, tipo)?
 *
 * Administración y desarrollo siempre pueden. Además, cualquiera con la
 * columna `usuarios.autoriza_datos_base` en 1 — así una persona puntual
 * (Carlos Solera, por ejemplo) puede tener este permiso sin volverse
 * administrador de todo el sistema. Se activa con una fila en
 * schema/045_autorizacion_datos_base.sql o actualizando esa columna a mano.
 */
function autoriza_datos_base(array $filaUsuario): bool
{
    if (in_array($filaUsuario['rol'], ROLES_ADMIN, true)) {
        return true;
    }

    return (int) ($filaUsuario['autoriza_datos_base'] ?? 0) === 1;
}

/**
 * Desbloquea la edición de datos base en la sesión ACTUAL (la del navegador
 * de quien está digitando, normalmente personal extraordinario), verificando
 * la clave de OTRA persona autorizada.
 *
 * ES A PROPÓSITO QUE NO USA autenticar(): esto no es un login. La persona que
 * está trabajando sigue siendo quien es — su nombre sigue en la bitácora de
 * cada cambio— , solo que por un rato corto el formulario deja de estar
 * bloqueado. Es el mismo patrón que la llave del supervisor en una caja de
 * supermercado: alguien más autoriza la operación puntual sin tomar la caja.
 *
 * @return array{ok: bool, error?: string, autorizadoPor?: string, hasta?: int}
 */
function autorizar_edicion_base(string $usuario, string $contrasena): array
{
    sesion_iniciar();

    if (usuario_actual() === null) {
        return ['ok' => false, 'error' => 'La sesión se cerró. Volvé a entrar.'];
    }

    $resultado = verificar_credenciales($usuario, $contrasena);

    if (!$resultado['ok']) {
        return $resultado;
    }

    $fila = $resultado['fila'];

    if (!autoriza_datos_base($fila)) {
        return [
            'ok'    => false,
            'error' => "{$fila['nombre']} no tiene permiso para autorizar cambios en los datos base.",
        ];
    }

    $hasta = time() + MINUTOS_AUTORIZACION_DATOS_BASE * 60;

    $_SESSION['edicion_base_hasta']         = $hasta;
    $_SESSION['edicion_base_autorizado_por'] = $fila['nombre'];

    return ['ok' => true, 'autorizadoPor' => $fila['nombre'], 'hasta' => $hasta];
}

/**
 * ¿La edición de datos base sigue desbloqueada en esta sesión?
 *
 * Se revisa en cada escritura, no solo al mostrar el botón: un desbloqueo
 * vencido no debe dejar pasar el guardado solo porque la pantalla todavía no
 * se refrescó.
 */
function edicion_base_autorizada(): bool
{
    sesion_iniciar();

    $hasta = (int) ($_SESSION['edicion_base_hasta'] ?? 0);

    if ($hasta <= time()) {
        unset($_SESSION['edicion_base_hasta'], $_SESSION['edicion_base_autorizado_por']);
        return false;
    }

    return true;
}

/** Bloquea la edición de datos base antes de que se venza sola (botón "Bloquear ahora"). */
function bloquear_edicion_base(): void
{
    sesion_iniciar();
    unset($_SESSION['edicion_base_hasta'], $_SESSION['edicion_base_autorizado_por']);
}

/**
 * Cambia la contraseña del usuario conectado.
 *
 * Pide la actual y la verifica contra la que hay guardada: sin eso, cualquiera
 * que encontrara una sesión abierta podría dejar afuera a su dueño con solo
 * poner una contraseña nueva, sin saber la vieja.
 *
 * @return array{ok: bool, error?: string}
 */
function cambiar_contrasena(string $actual, string $nueva, string $confirmacion): array
{
    $u = usuario_actual();

    if ($u === null) {
        return ['ok' => false, 'error' => 'La sesión se cerró. Volvé a entrar.'];
    }

    // Cuenta compartida: si cualquiera de las personas que la usan pudiera
    // cambiarla, dejaría afuera a todas las demás a mitad del día de
    // aplicación. La contraseña de esta cuenta la administra quien administra,
    // no cada persona que entra con ella.
    if ($u['rol'] === ROL_EXTRAORDINARIO) {
        return ['ok' => false, 'error' => 'Esta cuenta es compartida: solo administración puede cambiarle la contraseña.'];
    }

    $stmt = db()->prepare('SELECT contrasena_hash FROM usuarios WHERE id = ?');
    $stmt->execute([$u['id']]);
    $hashActual = $stmt->fetchColumn();

    if ($hashActual === false || !password_verify($actual, $hashActual)) {
        return ['ok' => false, 'error' => 'La contraseña actual no es correcta.'];
    }

    if ($nueva !== $confirmacion) {
        return ['ok' => false, 'error' => 'Las dos contraseñas nuevas no coinciden.'];
    }

    if (mb_strlen($nueva) < LARGO_MINIMO_CONTRASENA) {
        return ['ok' => false, 'error' => 'La contraseña debe tener al menos '
            . LARGO_MINIMO_CONTRASENA . ' caracteres.'];
    }

    // Sin esto, dejar la de siempre contaría como "ya la cambié" y la cuenta
    // seguiría abierta para cualquiera que conozca la clave inicial.
    if ($nueva === CONTRASENA_INICIAL) {
        return ['ok' => false, 'error' => 'Elegí una contraseña distinta de la inicial.'];
    }

    db()->prepare("
        UPDATE usuarios
        SET contrasena_hash = ?, debe_cambiar_contrasena = 0
        WHERE id = ?
    ")->execute([password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);

    $_SESSION['debe_cambiar'] = false;

    return ['ok' => true];
}

function cerrar_sesion(): void
{
    sesion_iniciar();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

// ------------------------------------------------------------------
// Guardas para las páginas
// ------------------------------------------------------------------

/**
 * Corta la página si no hay sesión, o si la contraseña sigue siendo la inicial.
 *
 * @param string $base Prefijo hasta la raíz del sitio ('' desde la raíz,
 *                     '../' desde la carpeta de un módulo).
 */
function exigir_login(string $base = ''): array
{
    $u = usuario_actual();

    if ($u === null) {
        // Absoluta: una redirección relativa desde una URL equivocada
        // encadenaría carpetas en vez de llegar al login.
        header('Location: ' . base_url() . 'login.php');
        exit;
    }

    if ($u['debe_cambiar']) {
        header('Location: ' . base_url() . 'cambiar_contrasena.php');
        exit;
    }

    return $u;
}

/**
 * Además de la sesión, exige uno de los roles indicados.
 *
 * A quien no le corresponde no se le muestra un error: se le manda a donde sí
 * puede estar. Un funcionario que llegue a un módulo de administración es casi
 * siempre alguien que siguió un enlace, no alguien intentando entrar.
 */
function exigir_rol(array $roles, string $base = ''): array
{
    $u = exigir_login($base);

    if (!in_array($u['rol'], $roles, true)) {
        // Al portal, no a un módulo fijo: es la única pantalla que existe para
        // todos los roles y que además le muestra a cada quien lo que sí puede
        // abrir. Mandarlo a tickets dejaba al personal extraordinario, que no
        // reporta averías, rebotando contra una puerta cerrada.
        header('Location: ' . base_url());
        exit;
    }

    return $u;
}

/** Nombre legible del rol, para mostrar en pantalla. */
function nombre_del_rol(string $rol): string
{
    return NOMBRES_ROLES[$rol] ?? ucfirst($rol);
}

/** Para los endpoints: responde JSON en vez de redirigir. */
function exigir_login_api(): array
{
    $u = usuario_actual();

    if ($u === null) {
        responder_json([
            'success'  => false,
            'error'    => 'Tenés que iniciar sesión.',
            'sesion'   => false,
        ], 401);
    }

    return $u;
}

/** Para los endpoints que solo puede usar la administración. */
function exigir_rol_api(array $roles): array
{
    $u = exigir_login_api();

    if (!in_array($u['rol'], $roles, true)) {
        responder_json([
            'success' => false,
            'error'   => 'No tenés permiso para esta operación.',
        ], 403);
    }

    return $u;
}

// ------------------------------------------------------------------
// CSRF
// ------------------------------------------------------------------

/**
 * Ficha que acompaña a los formularios.
 *
 * La cookie ya va con SameSite=Lax, que frena la mayoría de los envíos desde
 * otro sitio. Esto es la segunda capa, por si el navegador es viejo o si algún
 * día hace falta relajar esa política.
 */
function token_csrf(): string
{
    sesion_iniciar();

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_valido(?string $token): bool
{
    sesion_iniciar();

    return !empty($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

// ------------------------------------------------------------------

/** Deja constancia del intento, haya entrado o no. */
function registrar_ingreso(?int $usuarioId, string $texto, bool $exitoso): void
{
    try {
        db()->prepare("
            INSERT INTO ingresos (usuario_id, usuario_texto, exitoso, ip)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $usuarioId,
            mb_substr($texto, 0, 100),
            $exitoso ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        // La bitácora no puede impedir que alguien entre.
        error_log('[sesion] no se pudo registrar el ingreso: ' . $e->getMessage());
    }
}

/**
 * Dirección de la raíz del sitio, como ruta absoluta ("/" o "/subcarpeta/").
 *
 * Los enlaces entre módulos tienen que ser absolutos. Con enlaces relativos,
 * estando en /RevisionAulas/ el enlace "Revision/" lleva a
 * /RevisionAulas/Revision/, y de ahí a /RevisionAulas/Revision/Revision/: la
 * URL se va multiplicando en cada clic en vez de dar un 404 que avise.
 *
 * Se calcula comparando dónde está este archivo con la raíz que sirve el
 * servidor, así funciona igual si algún día el sitio cuelga de una subcarpeta.
 */
function base_url(): string
{
    $raiz = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));

    $base = ($docRoot !== '' && str_starts_with($raiz, $docRoot))
        ? substr($raiz, strlen($docRoot))
        : '';

    return rtrim($base, '/') . '/';
}

/** Escapa para imprimir en HTML. */
function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
