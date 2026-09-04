<?php
/**
 * Registro de lo que se hace en el sistema.
 *
 * CÓMO FUNCIONA, Y POR QUÉ ASÍ
 * Los diez endpoints de api/ siguen todos el mismo patrón: leen la acción con
 * param('accion'), la despachan en un switch y contestan con responder_json().
 * Eso permite anotar la actividad en un solo lugar en vez de agregar una
 * llamada en cada caso de cada switch — que es donde, con el tiempo, alguien
 * agrega un caso nuevo y se olvida de registrar.
 *
 * El enganche está en responder_json(): cuando la respuesta sale, ya se sabe
 * qué acción fue y si resultó bien.
 *
 * QUÉ SE REGISTRA
 * Solo lo que modifica datos. Las consultas son la enorme mayoría del tráfico
 * y enterrarían lo que de verdad se quiere encontrar. La regla es al revés de
 * lo que parece natural: en vez de una lista de acciones que SÍ se anotan, hay
 * una lista de las que NO. Si mañana alguien agrega una acción nueva y no
 * actualiza ninguna lista, queda registrada — que es el error correcto. Al
 * revés se perdería justo el rastro de lo nuevo.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Acciones que solo leen. Se comparan en minúsculas y sin distinguir guiones.
 *
 * Están escritas como PREFIJOS: 'listar' cubre listarAulas, listarPersonal, etc.
 *
 * Los prefijos cortos son peligrosos y por eso varios de estos están completos.
 * 'asignaciones' no puede abreviarse a 'asigna', porque taparía asignarUna, que
 * sí modifica; 'coordinadores' tampoco, porque taparía guardarCoordinador.
 *
 * La lista se contrastó contra las acciones que hoy despachan los diez
 * endpoints de api/. Al agregar una acción nueva de solo lectura conviene
 * sumarla acá; si no se hace, lo único que pasa es que la bitácora tenga una
 * fila de más, que es el lado correcto para equivocarse.
 */
const ACCIONES_DE_LECTURA = [
    'listar', 'obtener', 'buscar', 'consultar', 'ver', 'datos', 'resumen',
    'estado', 'detalle', 'materiales', 'areas', 'sincuenta', 'getsedes',
    'sedes', 'sede', 'aulas', 'personas', 'funcionarios', 'reporte',
    'exportar', 'metricas', 'ingresos', 'actividad', 'contar', 'validar',
    'asignaciones', 'catalogos', 'coordinadores', 'hospedaje', 'tarifas',
    'varias', 'turnos', 'origen', 'simulacion', 'historial', 'bloqueos',
];

/**
 * Modulos que no se registran en la bitácora aunque modifiquen datos.
 * Se comparan en minúsculas y sin distinguir guiones.
 * 
 * Los nombres derivan de los nombres de los endppoints de api/ (sin la extensión .php).
 * Por ejemplo, api/chat.php → 'chat'.
 */
const MODULOS_A_IGNORAR = ['chat'];

/**
 * ¿Esta acción modifica algo?
 */
function accion_modifica(string $accion): bool
{
    $normal = str_replace(['_', '-'], '', mb_strtolower(trim($accion), 'UTF-8'));

    if ($normal === '') {
        return false;
    }

    foreach (ACCIONES_DE_LECTURA as $lectura) {
        if (str_starts_with($normal, $lectura)) {
            return false;
        }
    }

    return true;
}

/**
 * Le agrega el nombre que la persona escribió, si vino, a quien queda
 * registrado en la bitácora.
 *
 * Varias cuentas (Personal Extraordinario, hoy) las usa más de una persona a
 * la vez con el mismo usuario y contraseña. Ingreso de Datos ya le pide a
 * cada quien escribir su nombre para el bloqueo de sedes (ver `quien` en
 * IngresoDatos/index.php) y lo manda en cada guardado; acá se aprovecha ese
 * mismo dato para que la bitácora diga "Personal Extraordinario (Floribeth)"
 * en vez de un usuario compartido sin cara.
 *
 * Se prefiere el NOMBRE (el de la persona o el de la cuenta, "Personal
 * Extraordinario") sobre el usuario de acceso: es lo que ya se le muestra a
 * quien administra en el resto de la bitácora, y "alvaro.moyaarrieta (Prueba)"
 * dice menos que "Álvaro Moya Arrieta (Prueba)".
 */
function usuario_texto_con_quien(?string $usuario, ?string $nombre = null): ?string
{
    $base = ($nombre !== null && $nombre !== '') ? $nombre : $usuario;

    if ($base === null || $base === '') {
        return null;
    }

    $quien = function_exists('param') ? trim((string) param('quien')) : '';

    return mb_substr($quien !== '' ? "{$base} ({$quien})" : $base, 0, 100);
}

/**
 * Anota una acción en la bitácora.
 *
 * Nunca lanza excepción: si la bitácora falla, la operación del usuario tiene
 * que seguir. Un sistema que se cae porque no pudo escribir su propio registro
 * es peor que uno sin registro.
 */
function bitacora_apuntar(
    string $modulo,
    string $accion,
    ?string $detalle = null,
    bool $exitoso = true
): void {
    bitacora_ya_apuntado(true);

    try {
        $u = function_exists('usuario_actual') ? usuario_actual() : null;

        db()->prepare("
            INSERT INTO actividad (usuario_id, usuario_texto, modulo, accion, detalle, exitoso, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $u['id'] ?? null,
            usuario_texto_con_quien($u['usuario'] ?? null, $u['nombre'] ?? null),
            mb_substr($modulo, 0, 50),
            mb_substr($accion, 0, 60),
            $detalle !== null ? mb_substr($detalle, 0, 255) : null,
            $exitoso ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('[bitacora] no se pudo registrar: ' . $e->getMessage());
    }
}

/**
 * ¿Este endpoint ya registró su acción a mano?
 *
 * @param bool|null $marcar true para marcar; null solo consulta.
 */
function bitacora_ya_apuntado(?bool $marcar = null): bool
{
    static $hecho = false;

    if ($marcar === true) {
        $hecho = true;
    }

    return $hecho;
}

/**
 * Enganche automático, llamado desde responder_json().
 *
 * @param array $respuesta Lo que se le va a contestar al cliente, para saber
 *                         si la operación salió bien.
 */
function bitacora_automatica(array $respuesta): void
{
    // Si el endpoint ya anotó algo a mano, ese registro es mejor que este:
    // lleva el detalle que solo él conocía (cuántas filas borró, qué cambió).
    // Sin esta guarda, esas operaciones aparecerían dos veces.
    if (bitacora_ya_apuntado()) {
        return;
    }

    // Sin sesión no hay nada útil que anotar: los intentos de entrar ya
    // quedan en `ingresos`.
    if (!function_exists('usuario_actual') || usuario_actual() === null) {
        return;
    }

    $accion = function_exists('param')
        ? param('accion', param('action'))
        : (string) ($_REQUEST['accion'] ?? $_REQUEST['action'] ?? '');

    if ($accion === '' || !accion_modifica($accion)) {
        return;
    }

    // El módulo es el nombre del endpoint: api/revision.php → 'revision'.
    $modulo = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
    if ($modulo === '' || in_array($modulo, MODULOS_A_IGNORAR, true)) {
        return;
    }

    // reemplazar guiones y guiones bajos por espacios, y capitalizar cada palabra.
    $modulo = ucwords(str_replace(['_', '-'], ' ', $modulo));

    // en accion, reemplazar camel case por espacios y capitalizar cada palabra
    $accion = preg_replace('/(?<!^)([A-Z])/', ' $1', $accion);
    $accion = ucwords(str_replace(['_', '-'], ' ', $accion));

    // El éxito se toma de la respuesta. Los endpoints devuelven success=false
    // con código 200 en los errores de validación, así que mirar solo el
    // código HTTP marcaría como buenos varios que no lo fueron.
    $exitoso = !isset($respuesta['success']) || $respuesta['success'] !== false;

    bitacora_apuntar($modulo, $accion, bitacora_detalle(), $exitoso);
}

/**
 * Arma la descripción corta de sobre qué se actuó.
 *
 * Toma los parámetros que identifican cosas en este sistema. NO se guarda la
 * entrada completa a propósito: llevaría nombres, correos y teléfonos de
 * funcionarios y de directores de centros educativos a una tabla que se
 * consulta desde una pantalla de administración. La bitácora tiene que decir
 * qué se tocó, no duplicar los datos personales.
 */
function bitacora_detalle(): ?string
{
    if (!function_exists('entrada')) {
        return null;
    }

    $interesantes = [
        'sede', 'sede_id', 'codigo', 'aula', 'tula', 'numero', 'id', 'usuario',
        'boleta', 'folio', 'equipo', 'ticket', 'persona', 'convocatoria_id',
    ];

    $datos = entrada();
    $partes = [];

    foreach ($interesantes as $clave) {
        if (isset($datos[$clave]) && is_scalar($datos[$clave])) {
            $valor = trim((string) $datos[$clave]);
            if ($valor !== '' && mb_strlen($valor) <= 60) {
                $partes[] = "{$clave}={$valor}";
            }
        }
        if (count($partes) >= 4) {
            break;
        }
    }

    return $partes === [] ? null : implode(' ', $partes);
}
