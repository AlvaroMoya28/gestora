<?php
/**
 * Conexión central a la base de datos gestionpaa (MariaDB).
 *
 * Las credenciales viven en includes/config.php, que NO se versiona.
 * Ver includes/config.example.php para la plantilla.
 *
 * Se usa PDO con consultas preparadas en todo el sistema: es lo que evita
 * que un dato con comillas o un intento de inyección SQL llegue al motor.
 */

// Un error de PHP sin capturar (fatal, TypeError de declare(strict_types=1),
// notice) se imprime tal cual en la respuesta si display_errors está en el
// php.ini del servidor — y eso puede sacar rutas del servidor, nombres de
// tabla o fragmentos de consulta a cualquiera que visite la página. Va acá
// porque db.php es lo primero que carga tanto cada página como cada
// endpoint. No aplica en CLI: ahí sí conviene ver el error en la terminal de
// quien está corriendo un script de mantenimiento a mano.
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

/**
 * Devuelve la conexión PDO, creándola solo la primera vez que se pide.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $rutaConfig = __DIR__ . '/config.php';

        if (!is_file($rutaConfig)) {
            throw new RuntimeException(
                'Falta includes/config.php. Copiá config.example.php y completá las credenciales.'
            );
        }

        $cfg = require $rutaConfig;

        // Un socket local (desarrollo) tiene prioridad sobre host+puerto,
        // que es como conecta el servidor de producción.
        if (!empty($cfg['db_socket'])) {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                $cfg['db_socket'],
                $cfg['db_name']
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;%sdbname=%s;charset=utf8mb4',
                $cfg['db_host'],
                !empty($cfg['db_port']) ? 'port=' . (int) $cfg['db_port'] . ';' : '',
                $cfg['db_name']
            );
        }

        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            // Que los errores lancen excepción en vez de fallar en silencio
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Consultas preparadas reales del servidor, no emuladas
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}

/**
 * Responde en JSON y termina. Centralizado para que todos los endpoints
 * del sistema contesten con el mismo formato.
 */
function responder_json(array $datos, int $codigo = 200): void
{
    // La bitácora se escribe acá porque es el único punto por el que salen
    // TODAS las respuestas del sistema. Poner la llamada en cada caso de cada
    // switch funcionaría hoy y se rompería en el primer caso nuevo que alguien
    // agregue sin acordarse. Ver includes/bitacora.php.
    //
    // Va protegido por function_exists porque db.php lo usan también los
    // scripts de terminal, donde no hay sesión ni bitácora cargadas.
    if (function_exists('bitacora_automatica')) {
        bitacora_automatica($datos);
    }

    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}
