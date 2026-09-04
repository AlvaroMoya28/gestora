<?php
/**
 * Busca un servidor SMTP al que este servidor pueda entregarle correo.
 *
 *   php scripts/buscar_smtp.php                 → prueba los candidatos conocidos
 *   php scripts/buscar_smtp.php smtp.ucr.ac.cr  → prueba solo ese
 *
 * POR QUÉ
 * acceso01 no tiene MTA (no existe /usr/sbin/sendmail), así que mail() no
 * sirve. La alternativa es entregarle el mensaje por red a un servidor SMTP.
 * Falta saber cuál acepta a este servidor, y eso solo se averigua probando
 * desde adentro de la red de la UCR.
 *
 * Este script NO manda correo: solo abre la conversación, saluda y anota qué
 * contestó cada uno. Es seguro correrlo las veces que haga falta.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

/**
 * Candidatos, del más probable al menos.
 *
 * El puerto 25 sin cifrado va primero porque un relay interno de la
 * institución normalmente acepta a las máquinas de su propia red sin pedir
 * usuario, que es el caso más cómodo: no habría contraseñas que guardar.
 */
$candidatos = [
    ['smtp.ucr.ac.cr',     25,  'ninguna'],
    ['smtp.ucr.ac.cr',     587, 'tls'],
    ['correo.ucr.ac.cr',   25,  'ninguna'],
    ['mail.ucr.ac.cr',     25,  'ninguna'],
    ['relay.ucr.ac.cr',    25,  'ninguna'],
    ['smtp.gmail.com',     587, 'tls'],
];

if (isset($argv[1])) {
    $host = $argv[1];
    $candidatos = [
        [$host, 25,  'ninguna'],
        [$host, 587, 'tls'],
        [$host, 465, 'ssl'],
    ];
}

echo "=== Buscando un servidor SMTP alcanzable ===\n";
echo "    (solo se saluda; no se envía ningún correo)\n\n";

$funcionan = [];

foreach ($candidatos as [$host, $puerto, $seguridad]) {
    printf("  %-22s :%-4d ", $host, $puerto);

    $direccion = $seguridad === 'ssl' ? "ssl://{$host}:{$puerto}" : "tcp://{$host}:{$puerto}";

    // 6 segundos: si un relay de la propia red no contesta en ese tiempo, es
    // que no está ahí. Un timeout largo por candidato haría eterno el barrido.
    $conexion = @stream_socket_client($direccion, $err, $msg, 6);

    if ($conexion === false) {
        echo "no responde ({$msg})\n";
        continue;
    }

    stream_set_timeout($conexion, 6);
    $saludo = (string) fgets($conexion, 515);

    if (!str_starts_with($saludo, '220')) {
        echo "contesta pero no es SMTP: " . trim($saludo) . "\n";
        fclose($conexion);
        continue;
    }

    // Saludar para ver qué ofrece.
    $yo = gethostname() ?: 'localhost';
    fwrite($conexion, "EHLO {$yo}\r\n");

    $capacidades = '';
    while (($linea = fgets($conexion, 515)) !== false) {
        $capacidades .= $linea;
        if (strlen($linea) < 4 || $linea[3] !== '-') {
            break;
        }
    }

    @fwrite($conexion, "QUIT\r\n");
    fclose($conexion);

    $tieneTls  = stripos($capacidades, 'STARTTLS') !== false;
    $pideAuth  = stripos($capacidades, 'AUTH') !== false;

    echo "RESPONDE ✔  ";
    echo $tieneTls ? 'STARTTLS sí, ' : 'STARTTLS no, ';
    echo $pideAuth ? "acepta AUTH\n" : "sin AUTH anunciado\n";

    $funcionan[] = [
        'host'      => $host,
        'puerto'    => $puerto,
        'seguridad' => $seguridad === 'ninguna' && $tieneTls ? 'tls' : $seguridad,
        'auth'      => $pideAuth,
        'saludo'    => trim($saludo),
    ];
}

// ------------------------------------------------------------------
// Qué hacer con lo encontrado
// ------------------------------------------------------------------
echo "\n";

if ($funcionan === []) {
    echo "Ninguno respondió.\n\n";
    echo "Eso quiere decir que la red bloquea la salida a servidores de correo,\n";
    echo "o que el relay de la UCR tiene otro nombre. Hay que preguntarle al\n";
    echo "Centro de Informática (2511-5000, ci5000@ucr.ac.cr):\n\n";
    echo "  «Necesito enviar correo desde una aplicación PHP alojada en\n";
    echo "   acceso01. El servidor no tiene MTA. ¿Cuál es el servidor SMTP\n";
    echo "   institucional, en qué puerto, y necesito usuario y contraseña\n";
    echo "   o autorizan la IP del servidor?»\n\n";
    echo "Con esa respuesta se llena correo_smtp en includes/config.php.\n";
    exit(1);
}

$mejor = $funcionan[0];

echo "Responde: " . count($funcionan) . " servidor(es).\n\n";
echo "Agregá esto a includes/config.php, dentro del array que devuelve:\n\n";
echo "    'correo_remitente' => 'PAA UCR <no-responder@gestionpaa.ucr.ac.cr>',\n";
echo "    'correo_smtp' => [\n";
echo "        'host'      => '{$mejor['host']}',\n";
echo "        'puerto'    => {$mejor['puerto']},\n";
echo "        'seguridad' => '{$mejor['seguridad']}',\n";

if ($mejor['auth']) {
    echo "        'usuario'   => '',   // ← pedírselo al Centro de Informática\n";
    echo "        'clave'     => '',\n";
} else {
    echo "        // Este servidor no anunció AUTH: puede que acepte a esta\n";
    echo "        // máquina por su IP. Probá así primero; si rechaza el envío,\n";
    echo "        // hay que pedir usuario y contraseña.\n";
    echo "        'usuario'   => '',\n";
    echo "        'clave'     => '',\n";
}

echo "    ],\n\n";
echo "Y después probá el envío de verdad:\n\n";
echo "    php scripts/probar_correo.php tu.correo@ucr.ac.cr\n";
