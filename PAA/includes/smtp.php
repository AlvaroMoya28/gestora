<?php
/**
 * Envío de correo por SMTP, hablando el protocolo directamente.
 *
 * POR QUÉ EXISTE ESTE ARCHIVO
 * El servidor donde vive el sistema (acceso01) no tiene MTA: sendmail_path
 * apunta a /usr/sbin/sendmail y ese programa no está instalado, así que mail()
 * devuelve false siempre. No hay nada que configurar en PHP para arreglarlo —
 * falta el programa. La salida es no usar mail() y entregarle el mensaje a un
 * servidor SMTP por la red.
 *
 * Se escribe a mano en vez de usar PHPMailer porque el hosting no tiene
 * Composer y el sistema entero es PHP sin dependencias. Son ~150 líneas y el
 * protocolo lleva estable desde 1982.
 *
 * Todo lo que devuelve el servidor remoto queda en el mensaje de error: cuando
 * esto falle, va a fallar en un servidor al que no tengo acceso, y la única
 * forma de arreglarlo va a ser leer lo que dijo el otro lado.
 */

declare(strict_types=1);

/**
 * Entrega un mensaje HTML a un servidor SMTP.
 *
 * @param array  $cfg Config del correo: host, puerto, seguridad, usuario, clave.
 * @param array  $adjuntos Lista de {nombre, tipo, contenido} — contenido en
 *               binario crudo (no base64 todavía; eso lo hace smtp_armar_mensaje()).
 *               Vacío por defecto: los llamadores existentes (acta, boleta,
 *               comprobante, ticket) no cambian de formato ni de firma.
 * @return array{success: bool, error?: string}
 */
function smtp_enviar_html(
    array $cfg,
    string $destinatario,
    string $asunto,
    string $cuerpoHtml,
    string $remitente,
    array $adjuntos = []
): array {
    $host      = (string) ($cfg['host'] ?? '');
    $puerto    = (int)    ($cfg['puerto'] ?? 587);
    $seguridad = (string) ($cfg['seguridad'] ?? 'tls');   // 'tls' | 'ssl' | 'ninguna'
    $usuario   = (string) ($cfg['usuario'] ?? '');
    $clave     = (string) ($cfg['clave'] ?? '');
    $timeout   = (int)    ($cfg['timeout'] ?? 15);

    if ($host === '') {
        return ['success' => false, 'error' => 'No hay servidor SMTP configurado'];
    }

    // MAIL FROM lleva la dirección sola. El remitente puede venir con nombre
    // ("PAA UCR <algo@ucr.ac.cr>"), que sirve para la cabecera pero no para el
    // protocolo.
    $sobreDe = smtp_solo_direccion($remitente);
    if ($sobreDe === null) {
        return ['success' => false, 'error' => 'El remitente configurado no es una dirección válida'];
    }

    $destino = $seguridad === 'ssl' ? "ssl://{$host}:{$puerto}" : "tcp://{$host}:{$puerto}";

    $conexion = @stream_socket_client(
        $destino,
        $codigoError,
        $mensajeError,
        $timeout,
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => ['SNI_enabled' => true]])
    );

    if ($conexion === false) {
        return ['success' => false, 'error' => "No se pudo conectar a {$host}:{$puerto} — {$mensajeError}"];
    }

    stream_set_timeout($conexion, $timeout);

    try {
        // El servidor saluda primero.
        smtp_esperar($conexion, 220);

        // Identificarse con el nombre real de la máquina: varios relays
        // rechazan un EHLO que no resuelva.
        $yo = gethostname() ?: 'localhost';
        $capacidades = smtp_ordenar($conexion, "EHLO {$yo}", 250);

        if ($seguridad === 'tls') {
            if (stripos($capacidades, 'STARTTLS') === false) {
                throw new RuntimeException(
                    "El servidor no ofrece STARTTLS. Si esta red no lo necesita, " .
                    "poné 'seguridad' => 'ninguna' en la configuración."
                );
            }

            smtp_ordenar($conexion, 'STARTTLS', 220);

            $cifrado = @stream_socket_enable_crypto(
                $conexion,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );

            if ($cifrado !== true) {
                throw new RuntimeException('Falló el cifrado TLS con el servidor');
            }

            // Después de STARTTLS hay que volver a saludar: las capacidades
            // anteriores se descartan.
            $capacidades = smtp_ordenar($conexion, "EHLO {$yo}", 250);
        }

        if ($usuario !== '') {
            if (stripos($capacidades, 'AUTH') === false) {
                throw new RuntimeException('El servidor no acepta autenticación en esta conexión');
            }

            // Las dos líneas siguientes llevan las credenciales en base64. Se
            // les pasa una etiqueta para que, si el servidor las rechaza, el
            // error diga «el usuario» y no el valor: ese mensaje se le muestra
            // a quien esté operando y además se guarda en correos_enviados.
            smtp_ordenar($conexion, 'AUTH LOGIN', 334, 'inicio de sesión');
            smtp_ordenar($conexion, base64_encode($usuario), 334, 'el usuario');
            smtp_ordenar($conexion, base64_encode($clave), 235, 'la contraseña');
        }

        smtp_ordenar($conexion, "MAIL FROM:<{$sobreDe}>", 250);
        smtp_ordenar($conexion, "RCPT TO:<{$destinatario}>", [250, 251]);
        smtp_ordenar($conexion, 'DATA', 354);

        smtp_escribir($conexion, smtp_armar_mensaje($destinatario, $asunto, $cuerpoHtml, $remitente, $adjuntos));
        smtp_ordenar($conexion, '.', 250);

        // Si QUIT falla da igual: el mensaje ya fue aceptado.
        @smtp_escribir($conexion, "QUIT\r\n");
        fclose($conexion);

        return ['success' => true];

    } catch (Throwable $e) {
        if (is_resource($conexion)) {
            fclose($conexion);
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Manda una orden y comprueba que la respuesta sea la esperada.
 *
 * @param int|int[] $esperado Código(s) que cuentan como éxito.
 * @param string    $etiqueta Cómo nombrar esta orden si falla. OBLIGATORIA
 *                            cuando la orden lleva credenciales: sin ella se
 *                            usa la orden entera, y el mensaje de error
 *                            terminaría mostrando la contraseña en base64 —
 *                            en pantalla y en la bitácora de correos.
 * @return string Respuesta completa, para poder leer las capacidades del EHLO.
 */
function smtp_ordenar($conexion, string $orden, $esperado, string $etiqueta = ''): string
{
    smtp_escribir($conexion, $orden . "\r\n");

    return smtp_esperar($conexion, $esperado, $etiqueta !== '' ? $etiqueta : $orden);
}

/**
 * Lee la respuesta del servidor y verifica su código.
 *
 * Una respuesta SMTP puede ocupar varias líneas: las intermedias llevan guion
 * después del código ("250-STARTTLS") y la última un espacio ("250 HELP").
 */
function smtp_esperar($conexion, $esperado, string $contexto = 'saludo inicial'): string
{
    $respuesta = '';

    while (($linea = fgets($conexion, 515)) !== false) {
        $respuesta .= $linea;

        // Cuarto carácter: '-' hay más, ' ' terminó.
        if (strlen($linea) < 4 || $linea[3] !== '-') {
            break;
        }
    }

    if ($respuesta === '') {
        $info = stream_get_meta_data($conexion);
        throw new RuntimeException(
            ($info['timed_out'] ?? false)
                ? "El servidor no respondió a tiempo ({$contexto})"
                : "El servidor cortó la conexión ({$contexto})"
        );
    }

    $codigo = (int) substr($respuesta, 0, 3);
    $validos = is_array($esperado) ? $esperado : [$esperado];

    if (!in_array($codigo, $validos, true)) {
        throw new RuntimeException(
            'El servidor rechazó ' . descripcion_segura($contexto) . ': ' . trim($respuesta)
        );
    }

    return $respuesta;
}

/**
 * Cómo nombrar en un error la orden que falló, sin filtrar credenciales.
 *
 * ESTO YA FALLÓ UNA VEZ. La versión anterior confiaba en detectar lo peligroso
 * por su forma: descartaba lo que empezara con «AUTH» o pasara de 60
 * caracteres. Pero en AUTH LOGIN el usuario y la contraseña viajan como base64
 * a secas —ni empiezan con AUTH ni son largos—, así que se colaron: Gmail
 * rechazó la clave y el mensaje de error la mostró completa, en pantalla y
 * guardada en correos_enviados.
 *
 * Ahora quien llama pasa una etiqueta para los pasos con credenciales, y esto
 * es la red de seguridad por si alguien agrega un paso nuevo y se olvida:
 * cualquier cosa que parezca base64 se reemplaza en vez de mostrarse.
 */
function descripcion_segura(string $contexto): string
{
    // base64 sin espacios y de largo par: no hay ninguna orden SMTP legítima
    // con esa forma, pero sí las credenciales.
    $pareceBase64 = preg_match('/^[A-Za-z0-9+\/]{8,}={0,2}$/', $contexto) === 1
                 && !str_contains($contexto, ' ');

    if ($pareceBase64) {
        return 'las credenciales';
    }

    return '«' . mb_substr($contexto, 0, 40) . '»';
}

function smtp_escribir($conexion, string $datos): void
{
    if (fwrite($conexion, $datos) === false) {
        throw new RuntimeException('Se perdió la conexión con el servidor de correo');
    }
}

/**
 * Arma el mensaje completo: cabeceras y cuerpo.
 *
 * El cuerpo va en base64 porque el HTML de los comprobantes lleva acentos y
 * líneas largas, y base64 evita tener que pensar en los límites de longitud de
 * línea del protocolo.
 *
 * Sin adjuntos es un mensaje de una sola parte (text/html), igual que
 * siempre. Con adjuntos se envuelve todo en multipart/mixed: una parte
 * text/html y una parte application/octet-stream por cada archivo, cada
 * una en base64 con su propio Content-Disposition.
 *
 * @param array $adjuntos [{nombre: string, tipo: string, contenido: string binario}]
 */
function smtp_armar_mensaje(
    string $destinatario,
    string $asunto,
    string $cuerpoHtml,
    string $remitente,
    array $adjuntos = []
): string {
    $cabecerasComunes = [
        'Date: ' . date('r'),
        'From: ' . smtp_cabecera_codificada($remitente),
        'To: ' . $destinatario,
        'Subject: ' . smtp_cabecera_codificada($asunto),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (gethostname() ?: 'paa') . '>',
        'MIME-Version: 1.0',
    ];

    if ($adjuntos === []) {
        $cabeceras = array_merge($cabecerasComunes, [
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ]);

        $cuerpo = chunk_split(base64_encode($cuerpoHtml), 76, "\r\n");

        return implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpo;
    }

    // El boundary no puede aparecer dentro del cuerpo por casualidad: al ser
    // aleatorio (16 bytes en hexadecimal) la probabilidad es, en la práctica,
    // cero.
    $boundary = 'paa-' . bin2hex(random_bytes(16));

    $cabeceras = array_merge($cabecerasComunes, [
        "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
    ]);

    $partes = [];

    $partes[] = "--{$boundary}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($cuerpoHtml), 76, "\r\n");

    foreach ($adjuntos as $adjunto) {
        $nombre = (string) ($adjunto['nombre'] ?? 'adjunto');
        $tipo   = (string) ($adjunto['tipo'] ?? 'application/octet-stream');
        $datos  = (string) ($adjunto['contenido'] ?? '');

        // Sin \r\n ni comillas: un nombre de archivo o un tipo MIME
        // manipulados ahí podrían inyectar cabeceras nuevas dentro de esta
        // parte del mensaje.
        $nombreSeguro = str_replace(['"', "\r", "\n"], '', $nombre);
        $tipoSeguro   = str_replace(["\r", "\n"], '', $tipo);

        $partes[] = "--{$boundary}\r\n"
            . "Content-Type: {$tipoSeguro}; name=\"{$nombreSeguro}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"{$nombreSeguro}\"\r\n\r\n"
            . chunk_split(base64_encode($datos), 76, "\r\n");
    }

    $cuerpoCompleto = implode('', $partes) . "--{$boundary}--\r\n";

    return implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpoCompleto;
}

/**
 * Codifica una cabecera si lleva caracteres no ASCII.
 *
 * "Revisión · Sede 47" tiene que viajar como =?UTF-8?B?...?= o llega con la
 * tilde rota.
 */
function smtp_cabecera_codificada(string $texto): string
{
    $texto = str_replace(["\r", "\n"], '', $texto);

    if (preg_match('/[\x80-\xFF]/', $texto) !== 1) {
        return $texto;
    }

    // Si viene como «Nombre <dirección>», solo el nombre se codifica: la
    // dirección tiene que quedar legible para el servidor.
    if (preg_match('/^(.*?)\s*<([^>]+)>$/', $texto, $partes) === 1) {
        return '=?UTF-8?B?' . base64_encode(trim($partes[1])) . "?= <{$partes[2]}>";
    }

    return '=?UTF-8?B?' . base64_encode($texto) . '?=';
}

/**
 * Saca la dirección de un remitente que puede venir con nombre.
 */
function smtp_solo_direccion(string $remitente): ?string
{
    $direccion = preg_match('/<([^>]+)>/', $remitente, $partes) === 1
        ? trim($partes[1])
        : trim($remitente);

    return filter_var($direccion, FILTER_VALIDATE_EMAIL) ? $direccion : null;
}
