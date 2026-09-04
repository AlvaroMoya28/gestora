<?php
/**
 * Chat de soporte flotante entre cada persona y administración.
 *
 * Se incluye al final del <body> de cualquier página, igual que
 * boton_inicio.php:
 *
 *     <?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
 *
 * Autocontenido a propósito (CSS y JS con prefijo `paa-chat`), para que
 * funcione igual sin importar el diseño de la página que lo incluya. Se
 * imprime en TODAS las páginas con sesión: es la manera de pedir ayuda desde
 * donde sea, no una pantalla a la que hay que entrar.
 *
 * DOS FORMAS MUY DISTINTAS DE VERLO
 * Un funcionario o Personal Extraordinario tiene UNA conversación (la suya,
 * con "administración") y el panel es directo: mensajes y un campo para
 * escribir. Un administrador o desarrollador tiene UNA POR PERSONA, así que
 * el panel abre primero en una bandeja (como una lista de conversaciones) y
 * al tocar una entra al hilo — con un botón para volver a la bandeja.
 */

require_once __DIR__ . '/sesion.php';

if (defined('PAA_CHAT_SOPORTE_IMPRESO')) {
    return;
}
define('PAA_CHAT_SOPORTE_IMPRESO', true);

$paaChatYo = usuario_actual();

if ($paaChatYo === null) {
    return;
}

$paaChatEsAdmin = in_array($paaChatYo['rol'], ROLES_ADMIN, true);

// Cuenta compartida: el chat le pide el nombre la primera vez, igual que
// Ingreso de Datos para el bloqueo de sedes — y reusa el MISMO dato
// (localStorage `paa.quien`), para no preguntarlo dos veces en la misma
// máquina.
$paaChatCuentaCompartida = $paaChatYo['rol'] === ROL_EXTRAORDINARIO;
?>
<style>
    .paa-chat-boton {
        position: fixed;
        right: 1.25rem;
        bottom: 1.25rem;
        z-index: 9998;

        width: 3.4rem;
        height: 3.4rem;
        border-radius: 50%;
        border: none;
        cursor: pointer;

        background: #0055a4;
        color: #fff;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        transition: background 0.15s ease, transform 0.15s ease;

        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .paa-chat-boton:hover { background: #003b73; transform: translateY(-2px); }
    .paa-chat-boton:focus-visible { outline: 3px solid #6EC1E4; outline-offset: 2px; }

    .paa-chat-insignia {
        position: absolute;
        top: -0.2rem;
        right: -0.2rem;
        min-width: 1.3rem;
        height: 1.3rem;
        padding: 0 0.3rem;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 0.7rem;
        font-weight: 800;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        animation: paa-chat-pulso 1.6s infinite;
    }
    .paa-chat-insignia.visible { display: flex; }

    @keyframes paa-chat-pulso {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.12); }
    }

    .paa-chat-panel {
        position: fixed;
        right: 1.25rem;
        bottom: 5.1rem;
        z-index: 9998;
        width: min(360px, calc(100vw - 2.5rem));
        height: min(520px, calc(100vh - 7.5rem));

        background: #fff;
        border-radius: 1.1rem;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.28);
        overflow: hidden;
        display: none;
        flex-direction: column;

        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    .paa-chat-panel.abierto { display: flex; }

    .paa-chat-cabecera {
        background: linear-gradient(135deg, #0055a4, #2b7fc2);
        color: #fff;
        padding: 0.9rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-shrink: 0;
    }
    .paa-chat-cabecera-texto { flex: 1; min-width: 0; }
    .paa-chat-cabecera strong { font-size: 0.92rem; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .paa-chat-cabecera small { display: block; font-size: 0.72rem; opacity: 0.85; font-weight: 400; }
    .paa-chat-volver, .paa-chat-cerrar {
        background: rgba(255,255,255,0.18);
        border: none;
        color: #fff;
        width: 1.8rem;
        height: 1.8rem;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.9rem;
        line-height: 1;
        flex-shrink: 0;
    }
    .paa-chat-volver:hover, .paa-chat-cerrar:hover { background: rgba(255,255,255,0.32); }
    .paa-chat-volver { display: none; }
    .paa-chat-volver.visible { display: block; }

    .paa-chat-mensajes {
        flex: 1;
        overflow-y: auto;
        padding: 0.9rem;
        background: #f6f9fc;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .paa-chat-vacio {
        margin: auto;
        text-align: center;
        color: #9ca3af;
        font-size: 0.82rem;
        padding: 1rem;
    }

    .paa-chat-burbuja {
        max-width: 82%;
        padding: 0.5rem 0.75rem;
        border-radius: 0.9rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        font-size: 0.85rem;
        line-height: 1.4;
        align-self: flex-start;
        word-wrap: break-word;
    }
    .paa-chat-burbuja.mia {
        align-self: flex-end;
        background: #0055a4;
        color: #fff;
        border-color: #0055a4;
    }
    .paa-chat-autor {
        font-size: 0.68rem;
        font-weight: 700;
        margin-bottom: 0.15rem;
        color: #2b7fc2;
    }
    .paa-chat-burbuja.mia .paa-chat-autor { color: #d6e9fb; }
    .paa-chat-hora {
        font-size: 0.63rem;
        opacity: 0.65;
        margin-top: 0.2rem;
        text-align: right;
    }

    .paa-chat-forma {
        display: flex;
        gap: 0.5rem;
        padding: 0.75rem;
        border-top: 1px solid #e5e7eb;
        background: #fff;
        flex-shrink: 0;
    }
    .paa-chat-texto {
        flex: 1;
        resize: none;
        border: 1px solid #d1d5db;
        border-radius: 0.65rem;
        padding: 0.5rem 0.7rem;
        font-family: inherit;
        font-size: 0.85rem;
        max-height: 4.5rem;
    }
    .paa-chat-texto:focus { outline: 2px solid #6EC1E4; outline-offset: -1px; }
    .paa-chat-enviar {
        border: none;
        background: #0055a4;
        color: #fff;
        width: 2.6rem;
        border-radius: 0.65rem;
        cursor: pointer;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .paa-chat-enviar:hover { background: #003b73; }
    .paa-chat-enviar:disabled { opacity: 0.5; cursor: not-allowed; }

    /* ===== Bandeja (solo administración) ===== */
    .paa-chat-bandeja {
        flex: 1;
        overflow-y: auto;
        background: #fff;
    }
    .paa-chat-conv {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        text-align: left;
        width: 100%;
        background: none;
        border-left: 3px solid transparent;
        font-family: inherit;
    }
    .paa-chat-conv:hover { background: #f6f9fc; }
    .paa-chat-conv.con-pendientes { border-left-color: #ef4444; }
    .paa-chat-conv-borrar {
        flex-shrink: 0;
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        opacity: 0;
        transition: opacity 0.15s ease, background 0.15s ease, color 0.15s ease;
    }
    .paa-chat-conv:hover .paa-chat-conv-borrar,
    .paa-chat-conv:focus-within .paa-chat-conv-borrar { opacity: 1; }
    .paa-chat-conv-borrar:hover { background: #fee2e2; color: #991b1b; }
    .paa-chat-conv-avatar {
        width: 2.3rem;
        height: 2.3rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #0055a4, #2b7fc2);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .paa-chat-conv-datos { flex: 1; min-width: 0; }
    .paa-chat-conv-nombre {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .paa-chat-conv-rol {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6b7280;
        background: #f3f4f6;
        padding: 0.05rem 0.4rem;
        border-radius: 999px;
    }
    .paa-chat-conv-ultimo {
        font-size: 0.78rem;
        color: #6b7280;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .paa-chat-conv-insignia {
        min-width: 1.2rem;
        height: 1.2rem;
        padding: 0 0.3rem;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    @media print {
        .paa-chat-boton, .paa-chat-panel { display: none !important; }
    }

    /* Estado del último envío (enviado / no se pudo). */
    .paa-chat-estado {
        margin: 0;
        padding: 0 12px 6px;
        font-size: 0.72rem;
        font-weight: 600;
        color: #047857;
        min-height: 1rem;
    }
    .paa-chat-estado.error { color: #b91c1c; }

    /* Por si la página no trae includes/ui_accesible.php. */
    .paa-chat-panel .paa-sr-solo {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
    }
</style>

<button type="button" class="paa-chat-boton" id="paaChatBoton" title="Chat de soporte">
    <i class="fa-solid fa-comments" aria-hidden="true"></i>
    <span class="paa-chat-insignia" id="paaChatInsignia">0</span>
</button>

<div class="paa-chat-panel" id="paaChatPanel">
    <div class="paa-chat-cabecera">
        <button type="button" class="paa-chat-volver" id="paaChatVolver" title="Volver a las conversaciones">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        </button>
        <div class="paa-chat-cabecera-texto">
            <strong id="paaChatTitulo">Chat de soporte</strong>
            <small id="paaChatSubtitulo">Con administración</small>
        </div>
        <button type="button" class="paa-chat-volver" id="paaChatBorrarHilo"
                title="Borrar esta conversación" aria-label="Borrar esta conversación">
            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
        </button>
        <!-- aria-label y no solo title: el title no lo anuncia NVDA al llegar
             con Tab, así que este botón era «botón» a secas y no había forma
             de saber que era el de cerrar. -->
        <button type="button" class="paa-chat-cerrar" id="paaChatCerrar"
                title="Cerrar el chat" aria-label="Cerrar el chat">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="paa-chat-bandeja" id="paaChatBandeja" style="display:none;">
        <div class="paa-chat-vacio">Cargando…</div>
    </div>

    <div class="paa-chat-mensajes" id="paaChatMensajes" style="display:none;">
        <div class="paa-chat-vacio">Cargando…</div>
    </div>
    <!-- Estado del último envío: se ve y NVDA lo anuncia solo. -->
    <p class="paa-chat-estado" id="paaChatEstado" role="status" aria-live="polite"></p>

    <form class="paa-chat-forma" id="paaChatForma" style="display:none;">
        <label for="paaChatTexto" class="paa-sr-solo">Escribí tu mensaje</label>
        <textarea class="paa-chat-texto" id="paaChatTexto" rows="1"
                  placeholder="Escribí un mensaje…" maxlength="2000"></textarea>
        <button type="submit" class="paa-chat-enviar" title="Enviar" aria-label="Enviar mensaje">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
        </button>
    </form>
</div>

<script>
(function () {
    const API = <?= json_encode(base_url() . 'api/chat.php', JSON_UNESCAPED_SLASHES) ?>;
    const CSRF = <?= json_encode(token_csrf()) ?>;
    const CUENTA_COMPARTIDA = <?= $paaChatCuentaCompartida ? 'true' : 'false' ?>;
    const ES_ADMIN = <?= $paaChatEsAdmin ? 'true' : 'false' ?>;

    const boton = document.getElementById('paaChatBoton');
    const insignia = document.getElementById('paaChatInsignia');
    const panel = document.getElementById('paaChatPanel');
    const cerrar = document.getElementById('paaChatCerrar');
    const volver = document.getElementById('paaChatVolver');
    const borrarHilo = document.getElementById('paaChatBorrarHilo');
    const titulo = document.getElementById('paaChatTitulo');
    const subtitulo = document.getElementById('paaChatSubtitulo');
    const bandejaCont = document.getElementById('paaChatBandeja');
    const mensajesCont = document.getElementById('paaChatMensajes');
    const forma = document.getElementById('paaChatForma');
    const texto = document.getElementById('paaChatTexto');

    let abierto = false;
    // 'bandeja' solo existe para administración. Para todos los demás la
    // vista siempre es 'hilo', directo a su única conversación.
    let vista = ES_ADMIN ? 'bandeja' : 'hilo';
    let conversacionId = ES_ADMIN ? null : 0;   // 0 = "la mía", la resuelve el servidor
    let conversacionNombre = '';
    let ultimoId = 0;
    let intervaloAbierto = null;
    let intervaloCerrado = null;

    function quien() {
        if (!CUENTA_COMPARTIDA) return '';
        try { return localStorage.getItem('paa.quien') || ''; } catch (e) { return ''; }
    }

    function iniciales(nombre) {
        const partes = String(nombre || '?').trim().split(/\s+/);
        return ((partes[0]?.[0] || '?') + (partes[1]?.[0] || '')).toUpperCase();
    }

    // Un "ding" corto con Web Audio en vez de un archivo de sonido: así no
    // hay ningún .mp3 que desplegar ni que se pueda perder al copiar
    // archivos al servidor.
    //
    // POR QUÉ SE CREA EN CUANTO HAY UN CLIC, Y NO LA PRIMERA VEZ QUE SUENA
    // Los navegadores crean el AudioContext "suspendido" a menos que se
    // origine dentro de una interacción real (clic, tecla). El aviso casi
    // siempre lo dispara un sondeo de fondo (setInterval), no un clic, así
    // que crearlo recién ahí lo dejaba suspendido para siempre — sin ningún
    // error, simplemente nunca sonaba. Por eso se crea y se "despierta" apenas
    // hay el primer toque en la página, mucho antes de que haga falta sonar.
    let paaChatAudioCtx = null;
    function paaChatDesbloquearAudio() {
        try {
            paaChatAudioCtx = paaChatAudioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (paaChatAudioCtx.state === 'suspended') paaChatAudioCtx.resume();
        } catch (e) { /* sin Web Audio: sonarNotificacion() ya lo maneja solo */ }
    }
    ['pointerdown', 'keydown', 'touchstart'].forEach(evento =>
        document.addEventListener(evento, paaChatDesbloquearAudio, { once: true, passive: true })
    );

    function sonarNotificacion() {
        try {
            paaChatAudioCtx = paaChatAudioCtx || new (window.AudioContext || window.webkitAudioContext)();
            const ctx = paaChatAudioCtx;
            if (ctx.state === 'suspended') ctx.resume();
            const ahora = ctx.currentTime;

            [880, 1320].forEach((frecuencia, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = frecuencia;
                osc.connect(gain);
                gain.connect(ctx.destination);

                const inicio = ahora + i * 0.11;
                gain.gain.setValueAtTime(0.0001, inicio);
                gain.gain.exponentialRampToValueAtTime(0.16, inicio + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, inicio + 0.22);
                osc.start(inicio);
                osc.stop(inicio + 0.22);
            });
        } catch (e) { /* algún navegador viejo sin Web Audio: sin sonido, sin romper nada */ }
    }

    async function pedir(accion, parametros, opciones) {
        const qs = new URLSearchParams({ accion, csrf: CSRF, ...(parametros || {}) });
        const r = await fetch(`${API}?${qs}`, { credentials: 'same-origin', ...opciones });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo consultar el chat');
        return d;
    }

    function escapar(t) {
        return String(t ?? '').replace(/[&<>"]/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    }

    function horaLegible(txt) {
        if (!txt) return '';
        const fecha = new Date(String(txt).replace(' ', 'T'));
        if (isNaN(fecha)) return '';
        return fecha.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
    }

    function fechaCorta(txt) {
        if (!txt) return '';
        const fecha = new Date(String(txt).replace(' ', 'T'));
        if (isNaN(fecha)) return '';
        const hoy = new Date();
        const esHoy = fecha.toDateString() === hoy.toDateString();
        return esHoy
            ? fecha.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' })
            : fecha.toLocaleDateString('es-CR', { day: '2-digit', month: '2-digit' });
    }

    // ---------- parámetros de conversación ----------
    // conversacionId es null en la bandeja (todavía no se abrió ninguna) y 0
    // para quien no administra (le basta "la mía": el servidor la resuelve
    // solo y no hace falta mandar nada).
    function paramsConversacion() {
        return conversacionId ? { conversacion_id: conversacionId } : {};
    }

    // ---------- mensajes de UN hilo ----------

    function pintarMensajes(lista, { reemplazar }) {
        if (reemplazar) mensajesCont.innerHTML = '';

        if (reemplazar && !lista.length) {
            mensajesCont.innerHTML = '<div class="paa-chat-vacio">Todavía no hay mensajes. Escribí el primero.</div>';
        }

        lista.forEach(m => {
            const burbuja = document.createElement('div');
            burbuja.className = 'paa-chat-burbuja' + (m.mio ? ' mia' : '');
            burbuja.innerHTML = `
                ${m.mio ? '' : `<div class="paa-chat-autor">${escapar(m.autor)}</div>`}
                <div>${escapar(m.mensaje)}</div>
                <div class="paa-chat-hora">${horaLegible(m.fecha)}</div>
            `;
            mensajesCont.appendChild(burbuja);
            ultimoId = Math.max(ultimoId, m.id);
        });

        if (lista.length) mensajesCont.scrollTop = mensajesCont.scrollHeight;
    }

    async function cargarHiloInicial() {
        try {
            const d = await pedir('listar', paramsConversacion());
            conversacionId = d.conversacionId;
            pintarMensajes(d.mensajes, { reemplazar: true });
        } catch (e) {
            mensajesCont.innerHTML = `<div class="paa-chat-vacio">${escapar(e.message)}</div>`;
        }
    }

    async function sondearHilo() {
        try {
            const d = await pedir('listar', { ...paramsConversacion(), despues_de: ultimoId });
            if (d.mensajes.length) {
                pintarMensajes(d.mensajes, { reemplazar: false });
                // Los propios no suenan: ya se sabe que se acaban de mandar.
                if (d.mensajes.some(m => !m.mio)) sonarNotificacion();
            }
        } catch (e) { /* silencioso: es un sondeo de fondo */ }
    }

    // ---------- bandeja (solo administración) ----------

    async function cargarBandeja() {
        bandejaCont.innerHTML = '<div class="paa-chat-vacio">Cargando…</div>';
        try {
            const d = await pedir('conversaciones');
            pintarBandeja(d.conversaciones);
        } catch (e) {
            bandejaCont.innerHTML = `<div class="paa-chat-vacio">${escapar(e.message)}</div>`;
        }
    }

    function pintarBandeja(lista) {
        if (!lista.length) {
            bandejaCont.innerHTML = '<div class="paa-chat-vacio">Todavía nadie escribió. Cuando alguien reporte algo, aparece acá.</div>';
            return;
        }

        bandejaCont.innerHTML = lista.map(c => `
            <div class="paa-chat-conv ${c.noLeidos > 0 ? 'con-pendientes' : ''}" data-id="${c.conversacionId}" data-nombre="${escapar(c.nombre)}" tabindex="0" role="button">
                <div class="paa-chat-conv-avatar">${escapar(iniciales(c.nombre))}</div>
                <div class="paa-chat-conv-datos">
                    <div class="paa-chat-conv-nombre">
                        ${escapar(c.nombre)}
                        <span class="paa-chat-conv-rol">${c.rol === 'extraordinario' ? 'Extraordinario' : 'Funcionario'}</span>
                    </div>
                    <div class="paa-chat-conv-ultimo">${escapar(c.ultimoMensaje)}</div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.3rem;">
                    <span style="font-size:.65rem; color:#9ca3af;">${fechaCorta(c.ultimoEn)}</span>
                    ${c.noLeidos > 0 ? `<span class="paa-chat-conv-insignia">${c.noLeidos > 99 ? '99+' : c.noLeidos}</span>` : ''}
                </div>
                <button type="button" class="paa-chat-conv-borrar" data-id="${c.conversacionId}" data-nombre="${escapar(c.nombre)}" title="Borrar esta conversación">
                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                </button>
            </div>
        `).join('');

        bandejaCont.querySelectorAll('.paa-chat-conv').forEach(el => {
            el.addEventListener('click', () => abrirHilo(Number(el.dataset.id), el.dataset.nombre));
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrirHilo(Number(el.dataset.id), el.dataset.nombre); }
            });
        });

        bandejaCont.querySelectorAll('.paa-chat-conv-borrar').forEach(el => {
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                borrarConversacionDesdeBandeja(Number(el.dataset.id), el.dataset.nombre);
            });
        });
    }

    async function borrarConversacionDesdeBandeja(id, nombre) {
        if (!confirm(`¿Borrar toda la conversación con ${nombre}?\n\nSe pierden todos los mensajes, sin poder recuperarlos.`)) return;

        try {
            await pedir('borrarConversacion', { conversacion_id: id }, { method: 'POST' });
            cargarBandeja();
            actualizarInsignia();
        } catch (e) {
            alert('No se pudo borrar: ' + e.message);
        }
    }

    // ---------- insignia del botón (siempre visible, panel cerrado o no) ----------

    // null y no 0: así el primer chequeo al cargar la página solo anota la
    // base, sin sonar por mensajes que ya estaban sin leer de antes.
    let paaChatNoLeidosPrevio = null;

    async function actualizarInsignia() {
        try {
            const d = await pedir('noLeidos');
            if (d.noLeidos > 0) {
                insignia.textContent = d.noLeidos > 99 ? '99+' : d.noLeidos;
                insignia.classList.add('visible');
            } else {
                insignia.classList.remove('visible');
            }

            if (paaChatNoLeidosPrevio !== null && d.noLeidos > paaChatNoLeidosPrevio) {
                sonarNotificacion();
            }
            paaChatNoLeidosPrevio = d.noLeidos;
        } catch (e) { /* silencioso */ }
    }

    async function marcarVisto() {
        try { await pedir('marcarVisto', paramsConversacion(), { method: 'POST' }); } catch (e) { /* silencioso */ }
    }

    // ---------- cambios de vista ----------

    function mostrarBandeja() {
        vista = 'bandeja';
        conversacionId = null;
        ultimoId = 0;
        clearInterval(intervaloAbierto);

        titulo.textContent = 'Chat de soporte';
        subtitulo.textContent = 'Conversaciones';
        volver.classList.remove('visible');
        borrarHilo.classList.remove('visible');
        bandejaCont.style.display = 'block';
        mensajesCont.style.display = 'none';
        forma.style.display = 'none';

        cargarBandeja();
        intervaloAbierto = setInterval(() => { cargarBandeja(); actualizarInsignia(); }, 6000);
    }

    async function abrirHilo(id, nombre) {
        vista = 'hilo';
        conversacionId = id || 0;
        conversacionNombre = nombre || '';
        ultimoId = 0;
        clearInterval(intervaloAbierto);

        titulo.textContent = ES_ADMIN ? (conversacionNombre || 'Conversación') : 'Chat de soporte';
        subtitulo.textContent = ES_ADMIN ? 'Con esta persona' : 'Con administración';
        volver.classList.toggle('visible', ES_ADMIN);
        borrarHilo.classList.toggle('visible', ES_ADMIN && conversacionId > 0);
        bandejaCont.style.display = 'none';
        mensajesCont.style.display = 'flex';
        forma.style.display = 'flex';

        mensajesCont.innerHTML = '<div class="paa-chat-vacio">Cargando…</div>';
        await cargarHiloInicial();
        await marcarVisto();
        actualizarInsignia();

        intervaloAbierto = setInterval(async () => {
            await sondearHilo();
            await marcarVisto();
        }, 4000);

        texto.focus();
    }

    async function abrirPanel() {
        abierto = true;
        panel.classList.add('abierto');
        clearInterval(intervaloCerrado);

        if (ES_ADMIN) {
            mostrarBandeja();
        } else {
            await abrirHilo(0, '');
        }
    }

    function cerrarPanel() {
        abierto = false;
        panel.classList.remove('abierto');
        clearInterval(intervaloAbierto);
        intervaloCerrado = setInterval(actualizarInsignia, 15000);
    }

    boton.addEventListener('click', () => abierto ? cerrarPanel() : abrirPanel());
    cerrar.addEventListener('click', () => { cerrarPanel(); boton.focus(); });

    // Escape también cierra, y el foco vuelve al botón del chat: si no, queda
    // dando vueltas dentro de un panel que ya no se ve.
    panel.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { e.preventDefault(); cerrarPanel(); boton.focus(); }
    });
    volver.addEventListener('click', () => { if (ES_ADMIN) mostrarBandeja(); });

    borrarHilo.addEventListener('click', async () => {
        if (!ES_ADMIN || !conversacionId) return;
        if (!confirm(`¿Borrar toda la conversación con ${conversacionNombre || 'esta persona'}?\n\nSe pierden todos los mensajes, sin poder recuperarlos.`)) return;

        try {
            await pedir('borrarConversacion', { conversacion_id: conversacionId }, { method: 'POST' });
            mostrarBandeja();
        } catch (e) {
            alert('No se pudo borrar: ' + e.message);
        }
    });

    forma.addEventListener('submit', async (e) => {
        e.preventDefault();
        const valor = texto.value.trim();
        if (!valor) return;

        texto.disabled = true;
        estadoEnvio('Enviando el mensaje…');

        try {
            const d = await pedir('enviar', null, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mensaje: valor,
                    quien: quien(),
                    ...(conversacionId ? { conversacion_id: conversacionId } : {})
                })
            });
            conversacionId = d.conversacionId;
            texto.value = '';
            await sondearHilo();

            // Confirmación del envío, visible y hablada. Antes el mensaje
            // aparecía en la lista y nada más: sin ver la pantalla no había
            // forma de saber si había salido o si se había perdido.
            estadoEnvio('Mensaje enviado');
        } catch (e) {
            estadoEnvio('No se pudo enviar: ' + e.message, true);
        } finally {
            texto.disabled = false;
            texto.focus();
        }
    });

    /**
     * Dice en qué quedó el envío. Es una región `aria-live`, así que NVDA lo
     * lee sola sin sacar el foco del campo de escribir.
     */
    let borrarEstado = null;
    function estadoEnvio(mensaje, esError) {
        const caja = document.getElementById('paaChatEstado');
        if (!caja) return;

        caja.textContent = mensaje;
        caja.classList.toggle('error', !!esError);

        clearTimeout(borrarEstado);
        if (!esError) {
            borrarEstado = setTimeout(() => { caja.textContent = ''; }, 5000);
        }
    }

    // Enter manda, Shift+Enter hace salto de línea — como cualquier chat.
    texto.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            forma.requestSubmit();
        }
    });

    actualizarInsignia();
    intervaloCerrado = setInterval(actualizarInsignia, 15000);
})();
</script>
