<?php
/**
 * Utilidades comunes a todos los endpoints de api/.
 *
 * Existen para que cada endpoint no repita la misma gimnasia de leer el
 * cuerpo, validar el método y sacar parámetros con su valor por defecto — que
 * es donde se cuelan los descuidos.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sesion.php'; // csrf_valido() para exigir_csrf()

/**
 * Corta la petición si el método no es uno de los permitidos.
 *
 * @param string[] $permitidos ej. ['GET'], ['POST'], ['GET','POST']
 */
function exigir_metodo(array $permitidos): void
{
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (!in_array($metodo, $permitidos, true)) {
        header('Allow: ' . implode(', ', $permitidos));
        responder_json(['success' => false, 'error' => 'Método no permitido'], 405);
    }
}

/**
 * Datos de entrada de la petición, vengan como vengan.
 *
 * El sistema viejo mandaba de todo: JSON en el cuerpo, form-urlencoded, y
 * hasta GET con parámetros (los Apps Script aceptaban las tres formas para
 * esquivar las restricciones de CORS del navegador). Se admiten todas para no
 * tener que reescribir a la vez el frontend de cada módulo.
 */
function entrada(): array
{
    static $datos = null;

    if ($datos !== null) {
        return $datos;
    }

    $datos = [];

    $crudo = file_get_contents('php://input');
    if ($crudo !== false && trim($crudo) !== '') {
        $json = json_decode($crudo, true);
        if (is_array($json)) {
            $datos = $json;
        } else {
            // form-urlencoded en el cuerpo
            parse_str($crudo, $comoForm);
            if (is_array($comoForm)) {
                $datos = $comoForm;
            }
        }
    }

    // $_POST y $_GET completan (y no pisan) lo que vino en el cuerpo.
    return $datos = array_merge($_GET, $_POST, $datos);
}

/** Un parámetro de entrada como texto ya recortado. */
function param(string $nombre, string $porDefecto = ''): string
{
    $datos = entrada();
    return isset($datos[$nombre]) && is_scalar($datos[$nombre])
        ? trim((string) $datos[$nombre])
        : $porDefecto;
}

/** Un parámetro de entrada como entero. */
function param_int(string $nombre, int $porDefecto = 0): int
{
    $v = param($nombre);
    return $v === '' ? $porDefecto : (int) $v;
}

/**
 * Corta la petición si no trae una ficha CSRF válida.
 *
 * Se llama después de exigir_modulo_api()/exigir_login_api() (necesita la
 * sesión ya arrancada) en cualquier acción que cambie datos. La ficha viaja
 * como parámetro `csrf` en el mismo cuerpo del POST.
 */
function exigir_csrf(): void
{
    if (!csrf_valido(param('csrf'))) {
        responder_json([
            'success' => false,
            'error'   => 'Ficha de seguridad vencida. Recargá la página.',
        ], 403);
    }
}

/** Corta la petición si falta alguno de los parámetros obligatorios. */
function exigir(array $nombres): void
{
    foreach ($nombres as $nombre) {
        if (param($nombre) === '') {
            responder_json(['success' => false, 'error' => "Falta el parámetro: {$nombre}"], 400);
        }
    }
}

/**
 * Id de la convocatoria sobre la que trabaja la petición.
 *
 * Si no se indica, se usa la marcada como activa. Así las páginas del día de
 * la aplicación no tienen que pasar el parámetro, pero se puede consultar una
 * convocatoria pasada agregando ?convocatoria_id=N.
 */
function convocatoria_actual(): ?int
{
    $id = param_int('convocatoria_id');

    if ($id > 0) {
        return $id;
    }

    $id = db()->query('SELECT id FROM convocatorias WHERE activa = 1 ORDER BY id DESC LIMIT 1')
              ->fetchColumn();

    if ($id !== false) {
        return (int) $id;
    }

    // Si nadie marcó una activa, usamos la más reciente. Eso evita que el
    // módulo se caiga solo por olvido de la bandera y mantiene el sitio útil
    // mientras se corrige la tabla.
    $id = db()->query('SELECT id FROM convocatorias ORDER BY id DESC LIMIT 1')
              ->fetchColumn();

    return $id !== false ? (int) $id : null;
}

/**
 * Id interno de una sede a partir de su código.
 *
 * El código NO identifica una sede por sí solo: las sedes se numeran de nuevo
 * cada año, así que el 401 es un colegio en 2026 y otro distinto en 2027 (ver
 * schema/011_sedes_por_convocatoria.sql). Siempre hay que decir de qué
 * convocatoria se está hablando.
 *
 * Devuelve null si esa sede no existe en esa convocatoria.
 *
 * @param bool $soloActivas Para los formularios, que no deben ofrecer sedes
 *                          dadas de baja. Al consultar datos históricos se
 *                          pasa false, o las filas viejas dejarían de encontrarse.
 */
function id_sede_por_codigo(string $codigo, ?int $convocatoriaId = null, bool $soloActivas = false): ?int
{
    $convocatoriaId ??= convocatoria_actual();

    if ($convocatoriaId === null) {
        return null;
    }

    $sql = 'SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?';
    if ($soloActivas) {
        $sql .= ' AND activo = 1';
    }

    $stmt = db()->prepare($sql);
    $stmt->execute([$convocatoriaId, $codigo]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
}

/**
 * Registra el error real en el log y responde algo genérico.
 *
 * Un mensaje de PDO en el navegador puede revelar nombres de tablas, rutas o
 * credenciales; el detalle solo debe quedar en el log del servidor.
 */
function fallo(string $modulo, Throwable $e, string $mensaje = 'No se pudo procesar la solicitud'): void
{
    error_log("[{$modulo}] " . $e->getMessage());
    responder_json(['success' => false, 'error' => $mensaje], 500);
}
