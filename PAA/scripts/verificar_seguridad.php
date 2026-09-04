<?php
/**
 * Comprueba que las carpetas privadas NO se puedan descargar desde el navegador.
 *
 *   php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr
 *
 * POR QUÉ EXISTE ESTE SCRIPT
 * --------------------------
 * backups/, includes/, schema/ y scripts/ viven dentro de la carpeta que sirve
 * la web. Los .htaccess deberían bloquearlas, pero .htaccess SOLO funciona en
 * Apache con AllowOverride activado. Si el servidor usa nginx, o si Apache
 * ignora los .htaccess, los respaldos de la base quedan públicos y cualquiera
 * puede bajarse todos los datos, incluidas las credenciales.
 *
 * Este script confirma que el bloqueo de verdad funciona. Correlo después de
 * cada despliegue.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$base = rtrim($argv[1] ?? '', '/');
if ($base === '') {
    exit("Uso: php scripts/verificar_seguridad.php https://gestionpaa.ucr.ac.cr\n");
}

// Rutas que JAMÁS deben responder 200
$rutasPrivadas = [
    '/CONTEXTO.md',
    '/CHANGELOG.md',
    '/README.md',
    '/includes/config.php',
    '/includes/config.example.php',
    '/includes/db.php',
    '/backups/',
    '/backups/REGISTRO.md',
    '/schema/001_coordinadores.sql',
    '/scripts/respaldar_bd.php',
];

// Rutas que SÍ deben funcionar
$rutasPublicas = [
    '/',
    '/Coordinadores_Directorio/',
    '/api/coordinadores.php',
];

function estado(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_NOBODY         => false,
    ]);
    $cuerpo = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    return ['codigo' => $codigo, 'bytes' => strlen((string) $cuerpo), 'error' => $error];
}

$fallos = 0;
$sinRespuesta = 0;   // rutas que no se pudieron consultar: NO cuentan como aprobadas

echo "Verificando {$base}\n\n";
echo "--- Deben estar BLOQUEADAS ---\n";

foreach ($rutasPrivadas as $ruta) {
    $r = estado($base . $ruta);

    if ($r['error'] !== '') {
        echo "  ?  {$ruta}  (no se pudo consultar: {$r['error']})\n";
        $sinRespuesta++;
        continue;
    }

    // 403/404 está bien. 200 con cuerpo vacío también puede ser PHP ejecutado
    // sin salida, pero para un .sql o .md un 200 es una fuga real.
    if ($r['codigo'] === 200 && $r['bytes'] > 0) {
        echo "  ✗  {$ruta}  → 200 con {$r['bytes']} bytes  ¡EXPUESTO!\n";
        $fallos++;
    } else {
        echo "  ✓  {$ruta}  → {$r['codigo']}\n";
    }
}

echo "\n--- Deben FUNCIONAR ---\n";

foreach ($rutasPublicas as $ruta) {
    $r = estado($base . $ruta);

    if ($r['error'] !== '') {
        echo "  ?  {$ruta}  (no se pudo consultar: {$r['error']})\n";
        $sinRespuesta++;
        continue;
    }

    if ($r['codigo'] === 200) {
        echo "  ✓  {$ruta}  → 200\n";
    } else {
        echo "  ✗  {$ruta}  → {$r['codigo']}  (debería responder 200)\n";
        $fallos++;
    }
}

echo "\n";

if ($fallos > 0) {
    echo "{$fallos} problema(s). Si algo privado quedó expuesto, movelo FUERA de\n";
    echo "la carpeta web de inmediato: los respaldos contienen todos los datos.\n";
    exit(1);
}

// Una ruta que no se pudo consultar NO es una ruta aprobada. Decir "todo
// correcto" acá daría luz verde para migrar creyendo que los respaldos están
// protegidos, sin haberlo comprobado — justo el error que este script existe
// para evitar.
if ($sinRespuesta > 0) {
    echo "{$sinRespuesta} ruta(s) no se pudieron consultar: LA VERIFICACIÓN NO SE HIZO.\n";
    echo "Corré el script desde una máquina que resuelva el dominio (con VPN),\n";
    echo "o probá las URLs a mano. NO asumas que el bloqueo funciona.\n";
    exit(2);
}

echo "Todo correcto.\n";
