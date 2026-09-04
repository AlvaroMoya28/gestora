<?php
/**
 * Comprueba qué módulos pueden enviar correo, y prueba uno de verdad.
 *
 *   php scripts/probar_correo.php                          → revisa la configuración
 *   php scripts/probar_correo.php tickets tu@ucr.ac.cr     → manda una prueba
 *
 * POR QUÉ SE PRUEBA POR MÓDULO
 * Cada módulo puede tener su propia cuenta: gtipaaucr@gmail.com está reservada
 * a los tickets de avería, y las actas de revisión esperan la cuenta
 * institucional. Una prueba genérica diría «el correo funciona» aunque el
 * módulo que importa no tenga cuenta.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/correo.php';

/** Los módulos que hoy pueden mandar algo. Hay que mantenerlo a mano. */
const MODULOS_QUE_ENVIAN = [
    'revision' => 'Acta de revisión al coordinador de la sede',
    'tulas'    => 'Comprobante de salida y entrada de tulas',
    'activos'  => 'Boleta de préstamo de equipo',
    'tickets'  => 'Avisos de tickets de avería',
];

echo "=== Cuentas de correo por módulo ===\n\n";

$configurados = [];

foreach (MODULOS_QUE_ENVIAN as $modulo => $para) {
    $cuenta = cuenta_de_correo($modulo);

    if ($cuenta === null) {
        printf("  %-10s SIN CUENTA\n", $modulo);
        printf("  %-10s %s\n\n", '', $para);
        continue;
    }

    $configurados[] = $modulo;

    printf("  %-10s %s:%s (%s)\n",
        $modulo,
        $cuenta['smtp']['host'],
        $cuenta['smtp']['puerto'] ?? 587,
        $cuenta['smtp']['seguridad'] ?? 'tls');
    printf("  %-10s sale como %s\n", '', $cuenta['remitente']);
    printf("  %-10s %s\n\n", '', $para);
}

if ($configurados === []) {
    echo "  Ningún módulo puede enviar correo todavía.\n\n";
    echo "  Las cuentas se definen en includes/config.php, dentro de\n";
    echo "  'correo_cuentas'. Ver includes/config.example.php.\n";
}

// ------------------------------------------------------------------
// Bitácora
// ------------------------------------------------------------------
echo "=== Últimos envíos registrados ===\n\n";

try {
    $filas = db()->query("
        SELECT modulo, destinatario, exitoso, error, enviado_en
        FROM correos_enviados
        ORDER BY enviado_en DESC
        LIMIT 10
    ")->fetchAll();

    if ($filas === []) {
        echo "  Todavía no se ha intentado enviar ningún correo.\n";
    } else {
        foreach ($filas as $f) {
            printf(
                "  %-19s %-10s %-30s %s\n",
                $f['enviado_en'],
                $f['modulo'],
                mb_substr($f['destinatario'], 0, 30),
                $f['exitoso'] ? 'enviado' : ('FALLÓ: ' . mb_substr((string) $f['error'], 0, 60))
            );
        }
    }

    $fallidos = (int) db()->query(
        'SELECT COUNT(*) FROM correos_enviados WHERE exitoso = 0'
    )->fetchColumn();

    if ($fallidos > 0) {
        echo "\n  ⚠️  Hay {$fallidos} envío(s) fallidos en total.\n";
    }

} catch (Throwable $e) {
    echo "  No se pudo leer la bitácora: " . $e->getMessage() . "\n";
}

// ------------------------------------------------------------------
// Envío de prueba
// ------------------------------------------------------------------
$modulo  = $argv[1] ?? null;
$destino = $argv[2] ?? null;

if ($modulo === null || $destino === null) {
    echo "\n";
    echo "Para mandar una prueba hay que decir DESDE QUÉ MÓDULO:\n\n";
    echo "  php scripts/probar_correo.php tickets tu.correo@ucr.ac.cr\n\n";
    echo "Módulos: " . implode(', ', array_keys(MODULOS_QUE_ENVIAN)) . "\n";
    exit(0);
}

if (!isset(MODULOS_QUE_ENVIAN[$modulo])) {
    exit("\n'{$modulo}' no es un módulo que envíe correo.\n"
       . "Son: " . implode(', ', array_keys(MODULOS_QUE_ENVIAN)) . "\n");
}

if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
    exit("\n'{$destino}' no es una dirección válida.\n");
}

echo "\n=== Enviando prueba de «{$modulo}» a {$destino} ===\n\n";

$html = '<h2>Prueba del sistema PAA</h2>'
      . '<p>Este mensaje salió por la cuenta configurada para el módulo <strong>'
      . htmlspecialchars($modulo, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
      . '<p style="color:#666;font-size:12px">Enviado el ' . date('d/m/Y H:i')
      . ' desde ' . gethostname() . '</p>';

// Se llama a entregar_mensaje() y no a enviar_correo_html() porque esa última
// exige que el destinatario esté en la base, que es la regla del sistema. Acá
// se prueba el transporte, no la regla — pero por el mismo camino.
$resultado = entregar_mensaje($modulo, $destino, 'Prueba de correo · Sistema PAA', $html);

if ($resultado['success']) {
    echo "  ✔ El servidor SMTP aceptó el mensaje.\n\n";
    echo "  Con SMTP, que lo acepte sí es buena señal: un servidor de correo de\n";
    echo "  verdad se hizo cargo de entregarlo. Aun así, revisá el buzón (y la\n";
    echo "  carpeta de no deseados).\n";
} else {
    echo "  ✘ No se pudo enviar.\n\n";
    echo "  Motivo: " . $resultado['error'] . "\n\n";
    echo "  Si habla de autenticación, revisá que la clave sea la CONTRASEÑA DE\n";
    echo "  APLICACIÓN de 16 letras y no la de entrar a la cuenta.\n";
}
