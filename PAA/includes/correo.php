<?php
/**
 * Envío de correo del sistema PAA.
 *
 * REGLA QUE NO SE NEGOCIA: el destinatario nunca viene del cliente.
 *
 * Los Apps Script del sistema viejo ya trabajaban así (ver el comentario de
 * enviarBoletaPdf_ en Activos.gs y enviarActa_ en RevisionAulas_AppScript.gs),
 * y la razón sigue vigente palabra por palabra: estas páginas son públicas.
 * Si el endpoint aceptara la dirección que le pasen, cualquiera podría usarlo
 * para mandar correo con formato institucional a quien quisiera — un relay
 * abierto a nombre de la UCR, ideal para phishing.
 *
 * Por eso las funciones de acá reciben un IDENTIFICADOR (número de sede,
 * nombre de persona, id de aula) y resuelven el correo contra la base. El
 * universo de destinatarios posibles queda limitado a lo que ya está cargado.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Tope de envíos por módulo y por día.
 *
 * En Apps Script esto existía por la cuota de Gmail (100 destinatarios/día).
 * Acá esa cuota ya no aplica, pero el tope se mantiene por otra razón: acota
 * el daño si alguien encuentra la forma de disparar envíos en masa.
 */
const MAX_CORREOS_DIA = 300;

/**
 * Correo del coordinador de una sede, resuelto contra la base.
 *
 * Reemplaza a buscarCorreoCoordinador_() del Apps Script, que recorría todas
 * las filas del Sheet hasta encontrar la sede.
 *
 * Busca primero el enlace formal a `personas` y cae al texto que venía del
 * Sheet solo si aún no se ha enlazado, que es lo normal recién importados los
 * datos.
 */
function correo_coordinador_de_sede(string $codigoSede, ?int $convocatoriaId = null): ?string
{
    // La cláusula de convocatoria se agrega solo si hay una: repetir el mismo
    // placeholder nombrado (`:conv IS NULL OR ... = :conv`) no funciona con
    // consultas preparadas reales, que es como está configurado PDO acá.
    $sql = "
        SELECT COALESCE(p.correo, a.coordinador_correo) AS correo
        FROM aulas a
        INNER JOIN sedes s ON s.id = a.sede_id
        LEFT  JOIN personas p ON p.id = a.persona_id
        WHERE s.codigo = :sede
          AND COALESCE(p.correo, a.coordinador_correo) IS NOT NULL
          AND COALESCE(p.correo, a.coordinador_correo) <> ''
    ";
    $parametros = ['sede' => $codigoSede];

    if ($convocatoriaId !== null) {
        $sql .= ' AND a.convocatoria_id = :conv';
        $parametros['conv'] = $convocatoriaId;
    }

    $sql .= ' ORDER BY a.id LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
    $correo = $stmt->fetchColumn();

    return $correo !== false && $correo !== null && $correo !== ''
        ? (string) $correo
        : null;
}

/**
 * Correo de una persona del catálogo, buscada por nombre.
 *
 * Reemplaza a correoDePersona_() de Activos.gs. La comparación ignora
 * mayúsculas y acentos porque el nombre llega tal como lo escribió quien
 * llenó la boleta; la collation utf8mb4 de la base ya hace ambas cosas.
 */
function correo_de_persona(string $nombre): ?string
{
    $stmt = db()->prepare("
        SELECT correo
        FROM personas
        WHERE nombre = :nombre
          AND activo = 1
          AND correo IS NOT NULL
          AND correo <> ''
        LIMIT 1
    ");
    $stmt->execute(['nombre' => trim($nombre)]);
    $correo = $stmt->fetchColumn();

    return $correo !== false && $correo !== null ? (string) $correo : null;
}

/**
 * ¿Queda cupo de envíos hoy para este módulo?
 *
 * Sustituye los contadores que Apps Script guardaba en PropertiesService: acá
 * se cuentan las filas del día en `correos_enviados`, que además deja la
 * bitácora de a quién se le escribió.
 */
function dentro_del_limite_diario(string $modulo, int $tope = MAX_CORREOS_DIA): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM correos_enviados
        WHERE modulo = :modulo
          AND exitoso = 1
          AND DATE(enviado_en) = CURDATE()
    ");
    $stmt->execute(['modulo' => $modulo]);

    return (int) $stmt->fetchColumn() < $tope;
}

/**
 * Quita del HTML lo que podría convertirlo en un vector de ataque.
 *
 * Igual que limpiarHtml_() en los Apps Script, y con la misma advertencia: el
 * cuerpo sigue viniendo del cliente, así que esto es reducción de daño, no una
 * garantía. La protección de fondo es que solo se puede escribir a
 * destinatarios que ya están en la base, y con tope diario.
 */
function limpiar_html(string $html): string
{
    $limpio = preg_replace('#<script[\s\S]*?</script>#i', '', $html) ?? '';
    $limpio = preg_replace('#<iframe[\s\S]*?</iframe>#i', '', $limpio) ?? '';
    $limpio = preg_replace('#<object[\s\S]*?</object>#i', '', $limpio) ?? '';
    $limpio = preg_replace('#<embed[\s\S]*?>#i', '', $limpio) ?? '';
    // Atributos on* (onclick, onload, onerror…) y URLs javascript:
    $limpio = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $limpio) ?? '';
    $limpio = preg_replace('#javascript\s*:#i', '', $limpio) ?? '';

    return $limpio;
}

/**
 * Envía un correo HTML y deja constancia en `correos_enviados`.
 *
 * @param string $modulo      Para el tope diario y la bitácora: 'tulas', 'revision_aulas', 'activos'.
 * @param string $destinatario DEBE venir de correo_coordinador_de_sede() o correo_de_persona(),
 *                             nunca de $_POST. Quien llama es responsable de eso.
 * @param string $referencia  Folio, boleta o sede, para poder rastrear el envío después.
 * @param array  $adjuntos    [{nombre, tipo, contenido: binario crudo}]. Vacío por
 *                            defecto — los envíos existentes no cambian.
 *
 * @return array{success: bool, error?: string}
 */
function enviar_correo_html(
    string $modulo,
    string $destinatario,
    string $asunto,
    string $cuerpoHtml,
    string $referencia = '',
    array $adjuntos = []
): array {
    // Cinturón y tirantes: aunque quien llama debió resolverlo contra la base,
    // acá se vuelve a comprobar que sea una dirección válida. Un salto de línea
    // en este valor permitiría inyectar cabeceras y agregar destinatarios.
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Destinatario inválido'];
    }

    if (!dentro_del_limite_diario($modulo)) {
        return ['success' => false, 'error' => 'Se alcanzó el máximo de envíos del día'];
    }

    $cuerpo = limpiar_html($cuerpoHtml);
    $asunto = str_replace(["\r", "\n"], '', $asunto);

    $resultado = entregar_mensaje($modulo, $destinatario, $asunto, $cuerpo, $adjuntos);

    $stmt = db()->prepare("
        INSERT INTO correos_enviados (modulo, referencia, destinatario, asunto, exitoso, error)
        VALUES (:modulo, :referencia, :destinatario, :asunto, :exitoso, :error)
    ");
    $stmt->execute([
        'modulo'       => $modulo,
        'referencia'   => $referencia !== '' ? $referencia : null,
        'destinatario' => $destinatario,
        'asunto'       => $asunto,
        'exitoso'      => $resultado['success'] ? 1 : 0,
        // El motivo real se guarda entero: cuando esto falle va a ser en el
        // servidor, y la bitácora es lo único que va a quedar para saber por qué.
        'error'        => $resultado['success'] ? null : ($resultado['error'] ?? 'error desconocido'),
    ]);

    if (!$resultado['success']) {
        error_log("[correo:{$modulo}] falló el envío a {$destinatario}: " . ($resultado['error'] ?? ''));
        return ['success' => false, 'error' => 'No se pudo enviar el correo'];
    }

    return ['success' => true];
}

/**
 * Entrega el mensaje por el medio configurado PARA ESE MÓDULO.
 *
 * POR QUÉ LA CUENTA DEPENDE DEL MÓDULO
 * No todas las cuentas de correo sirven para todo. gtipaaucr@gmail.com es la
 * cuenta de soporte del programa y está reservada a los tickets de avería: es
 * la dirección a la que la gente responde cuando reporta algo roto.
 *
 * Las actas de revisión y los comprobantes de tulas van a coordinadores de
 * centros educativos: son documentos institucionales, y salir de un @gmail.com
 * de soporte los hace ver como lo que no son. Esos esperan la cuenta
 * institucional que el Centro de Informática todavía no ha entregado.
 *
 * Mientras un módulo no tenga cuenta, sus envíos FALLAN CON MENSAJE CLARO. Eso
 * es a propósito: prefiero que diga «no hay cuenta configurada» a que use la
 * primera que encuentre y mande un acta desde la dirección equivocada.
 *
 * @return array{success: bool, error?: string}
 */
function entregar_mensaje(
    string $modulo,
    string $destinatario,
    string $asunto,
    string $cuerpoHtml,
    array $adjuntos = []
): array {
    $cuenta = cuenta_de_correo($modulo);

    if ($cuenta === null) {
        return [
            'success' => false,
            'error'   => "El módulo «{$modulo}» no tiene cuenta de correo configurada. "
                       . 'Se define en includes/config.php, dentro de correo_cuentas.',
        ];
    }

    require_once __DIR__ . '/smtp.php';

    return smtp_enviar_html(
        $cuenta['smtp'],
        $destinatario,
        $asunto,
        $cuerpoHtml,
        $cuenta['remitente'],
        $adjuntos
    );
}

/**
 * La cuenta que le toca a un módulo, o null si no tiene ninguna.
 *
 * Se busca primero una cuenta propia del módulo y después la general. Así se
 * puede tener una cuenta para todo y una excepción para uno, o —como hoy— una
 * cuenta solo para tickets y nada general, que deja a los demás sin enviar
 * hasta que llegue la institucional.
 *
 * @return array{smtp: array, remitente: string}|null
 */
function cuenta_de_correo(string $modulo): ?array
{
    $rutaConfig = __DIR__ . '/config.php';
    $cfg = is_file($rutaConfig) ? require $rutaConfig : [];

    $cuentas = $cfg['correo_cuentas'] ?? [];

    $cuenta = $cuentas[$modulo]
        ?? $cuentas['general']
        // Compatibilidad con la forma anterior, cuando había una sola cuenta
        // para todo el sistema. Si un servidor todavía tiene esa configuración,
        // se sigue respetando en vez de dejarlo sin correo de golpe.
        ?? (isset($cfg['correo_smtp'])
            ? ['smtp' => $cfg['correo_smtp'], 'remitente' => $cfg['correo_remitente'] ?? '']
            : null);

    if (!is_array($cuenta) || empty($cuenta['smtp']['host'])) {
        return null;
    }

    // Gmail reescribe el remitente si no coincide con el usuario que autentica,
    // así que por defecto se usa ese mismo en vez de arriesgar una discrepancia.
    $remitente = trim((string) ($cuenta['remitente'] ?? ''));

    if ($remitente === '') {
        $remitente = (string) ($cuenta['smtp']['usuario'] ?? '');
    }

    return ['smtp' => $cuenta['smtp'], 'remitente' => $remitente];
}

// ------------------------------------------------------------------
// Plantilla visual compartida
// ------------------------------------------------------------------
//
// Un correo de aviso (ticket nuevo, cambio de estado, situación especial,
// anuncio) es un mensaje corto que arma el propio backend, no un documento.
// Antes cada endpoint tenía su propio <p>Hola,</p> suelto, sin nada que lo
// distinguiera de un correo cualquiera; ahora todos pasan por acá y salen
// con el mismo encabezado degradado y tipografía que el resto del sistema.
//
// APARTE A PROPÓSITO: las actas de revisión, comprobantes de tulas y
// boletas de activos (aulas.php, activos.php) NO usan esto. Esas ya son
// documentos completos armados en el cliente con su propio membrete;
// meterlas dentro de otro encabezado las duplicaría.

/**
 * Envuelve un cuerpo HTML ya armado en el encabezado/pie de marca del PAA.
 *
 * @param string $titulo     Lo que va grande en el encabezado (normalmente el asunto).
 * @param string $cuerpoHtml HTML ya seguro (escapado por quien llama si viene de texto libre).
 * @param string $pie        Línea pequeña bajo la tarjeta, ej. "Enviado por Fulano". Vacío si no aplica.
 */
function plantilla_correo(string $titulo, string $cuerpoHtml, string $pie = ''): string
{
    $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');

    $pieHtml = '';
    if ($pie !== '') {
        $pieSeguro = htmlspecialchars($pie, ENT_QUOTES, 'UTF-8');
        $pieHtml = "<div style=\"text-align:center;color:#9ca3af;font-size:11px;margin-top:12px;\">{$pieSeguro}</div>";
    }

    return <<<HTML
        <div style="font-family:'Inter',Arial,sans-serif;max-width:640px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#0055a4,#2b7fc2);padding:20px 24px;border-radius:8px 8px 0 0;">
                <div style="color:#fff;font-size:12px;letter-spacing:.05em;text-transform:uppercase;opacity:.85;">PAA · Prueba de Aptitud Académica</div>
                <div style="color:#fff;font-size:19px;font-weight:700;margin-top:4px;">{$tituloSeguro}</div>
            </div>
            <div style="background:#fff;padding:24px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;color:#1f2937;font-size:14px;line-height:1.6;">
                {$cuerpoHtml}
            </div>
            {$pieHtml}
        </div>
        HTML;
}

/**
 * Un bloque de "etiqueta arriba, valor abajo" para datos sueltos dentro del
 * cuerpo (folio, título, fechas...). Reemplaza los "<strong>X:</strong> Y<br>"
 * apilados que tenía cada correo antes, cada uno a su manera.
 */
function correo_campo(string $etiqueta, string $valor): string
{
    $etiquetaSegura = htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8');
    $valorSeguro = nl2br(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'));

    return <<<HTML
        <div style="margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:2px;">{$etiquetaSegura}</div>
            <div style="font-size:14px;color:#1f2937;">{$valorSeguro}</div>
        </div>
        HTML;
}

/** Una etiqueta de color, para prioridad/estado/tipo dentro del cuerpo. */
function correo_insignia(string $texto, string $color = '#0055a4'): string
{
    $textoSeguro = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    $colorSeguro = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');

    return <<<HTML
        <span style="display:inline-block;background:{$colorSeguro};color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;padding:3px 10px;border-radius:999px;">{$textoSeguro}</span>
        HTML;
}

/** Los módulos que hoy tienen cuenta, para los diagnósticos. */
function modulos_con_correo(): array
{
    $rutaConfig = __DIR__ . '/config.php';
    $cfg = is_file($rutaConfig) ? require $rutaConfig : [];

    return array_keys($cfg['correo_cuentas'] ?? []);
}
