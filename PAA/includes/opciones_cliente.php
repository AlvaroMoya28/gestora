<?php
/**
 * Genera el archivo temporal de opciones que leen mysqldump y mysql.
 *
 * La contraseña NUNCA va en la línea de comandos: cualquiera con acceso al
 * servidor la vería con `ps aux`. Por eso se escribe en un archivo temporal
 * con permisos 0600 que se borra apenas termina el comando.
 *
 * Lo usan scripts/respaldar_bd.php y scripts/restaurar_bd.php.
 */

declare(strict_types=1);

/**
 * Escribe el archivo de opciones y devuelve su ruta.
 * Quien lo llama es responsable de borrarlo con unlink().
 */
function crear_opciones_cliente(array $cfg, string $prefijo = 'paa'): string
{
    $ruta = tempnam(sys_get_temp_dir(), $prefijo);
    if ($ruta === false) {
        throw new RuntimeException('No se pudo crear el archivo temporal de opciones.');
    }

    chmod($ruta, 0600);

    $lineas = ["[client]"];

    if (!empty($cfg['db_socket'])) {
        $lineas[] = 'socket=' . $cfg['db_socket'];
    } else {
        $lineas[] = 'host=' . $cfg['db_host'];
        if (!empty($cfg['db_port'])) {
            $lineas[] = 'port=' . (int) $cfg['db_port'];
        }
    }

    $lineas[] = 'user=' . $cfg['db_user'];
    // Entre comillas por si la clave trae caracteres especiales
    $lineas[] = 'password="' . str_replace('"', '\\"', (string) $cfg['db_pass']) . '"';

    file_put_contents($ruta, implode("\n", $lineas) . "\n");

    return $ruta;
}
