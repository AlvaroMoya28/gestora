<?php
/**
 * Centro de notificaciones flotante para administración y desarrollo.
 *
 * Se incluye al final del <body> de cualquier página, igual que
 * boton_inicio.php y chat_soporte.php:
 *
 *     <?php include __DIR__ . '/../includes/notificaciones.php'; ?>
 *
 * Arriba a la derecha (el botón de inicio va abajo a la izquierda y el chat
 * abajo a la derecha, así que ahí no chocan). Autocontenido a propósito
 * (CSS y JS con prefijo `paa-notif`), para que se vea igual sin importar el
 * diseño de la página que lo incluya.
 *
 * Lo ven las cuentas del PAA: administración y desarrollo reciben además lo
 * operativo (tickets, mantenimientos, cuentas bloqueadas); funcionarios y
 * administración reciben los cambios recientes de Situaciones Especiales y
 * Horarios —ver api/notificaciones.php— que es justo lo que antes se avisaba
 * por correo.
 *
 * El personal extraordinario NO: su único módulo es Ingreso de Datos porque es
 * el único sin datos de personas, y estas notificaciones son casi todas datos
 * de personas (vacaciones, incapacidades, citas médicas, cambios de horario).
 * La API tampoco se los manda, esto es la otra mitad del cierre.
 *
 * Consulta api/notificaciones.php cada 3 minutos. No cada pocos segundos:
 * ver el comentario de arriba de ese archivo sobre lo que pasó con el chat.
 */

require_once __DIR__ . '/sesion.php';

if (defined('PAA_NOTIFICACIONES_IMPRESO')) {
    return;
}
define('PAA_NOTIFICACIONES_IMPRESO', true);

$paaNotifYo = usuario_actual();

if ($paaNotifYo === null) {
    return;
}

// Sin campanita para el personal extraordinario: no tiene nada que recibir, y
// un botón que siempre dice «todo al día» es ruido de más para navegar.
if ($paaNotifYo['rol'] === ROL_EXTRAORDINARIO) {
    return;
}
?>
<style>
    .paa-notif-boton {
        position: fixed;
        right: 1.25rem;
        top: 1.25rem;
        z-index: 9997;

        width: 3.1rem;
        height: 3.1rem;
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
        font-size: 1.2rem;
    }
    .paa-notif-boton:hover { background: #003b73; transform: translateY(-2px); }
    .paa-notif-boton:focus-visible { outline: 3px solid #6EC1E4; outline-offset: 2px; }
    .paa-notif-boton.hay-urgentes {
        animation: paaNotifPulso 2s ease-in-out infinite;
    }
    @keyframes paaNotifPulso {
        0%, 100% { box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25); }
        50% { box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25), 0 0 0 6px rgba(239, 68, 68, 0.25); }
    }

    .paa-notif-insignia {
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
        font-size: 0.72rem;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
        line-height: 1;
        border: 2px solid #fff;
    }
    .paa-notif-insignia.visible { display: flex; }

    .paa-notif-panel {
        position: fixed;
        right: 1.25rem;
        top: 4.6rem;
        z-index: 9997;
        width: min(380px, calc(100vw - 2.5rem));
        max-height: min(560px, calc(100vh - 6rem));
        overflow-y: auto;

        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 12px 32px rgba(17, 24, 39, 0.22);
        border: 1px solid #e5e7eb;

        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        display: none;
    }
    .paa-notif-panel.visible { display: block; }

    .paa-notif-cab {
        position: sticky;
        top: 0;
        background: #fff;
        padding: 1rem 1.1rem 0.7rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .paa-notif-cab strong { font-size: 0.98rem; color: #1f2937; }
    .paa-notif-cab-acciones { display: flex; align-items: center; gap: 0.6rem; }
    .paa-notif-cab button {
        background: none; border: none; cursor: pointer;
        color: #9ca3af; font-size: 1rem; padding: 0.2rem;
    }
    .paa-notif-cab button:hover { color: #1f2937; }
    #paaNotifMarcarLeidas { font-size: 0.85rem; }

    .paa-notif-vacio {
        padding: 2.2rem 1.25rem;
        text-align: center;
        color: #9ca3af;
        font-size: 0.85rem;
    }
    .paa-notif-vacio i { font-size: 1.6rem; display: block; margin-bottom: 0.5rem; color: #10b981; }

    .paa-notif-grupo { padding: 0.8rem 1.1rem; border-bottom: 1px solid #f3f4f6; }
    .paa-notif-grupo:last-child { border-bottom: none; }

    .paa-notif-grupo-cab {
        display: flex; align-items: center; gap: 0.5rem;
        margin-bottom: 0.55rem; text-decoration: none;
    }
    .paa-notif-grupo-cab .icono {
        width: 1.9rem; height: 1.9rem; border-radius: 0.55rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; color: #fff; flex-shrink: 0;
    }
    .paa-notif-grupo-cab.alta .icono { background: #ef4444; }
    .paa-notif-grupo-cab.media .icono { background: #f59e0b; }
    .paa-notif-grupo-cab.baja .icono { background: #6b7280; }

    .paa-notif-grupo-txt strong { display: block; font-size: 0.85rem; color: #1f2937; }
    .paa-notif-grupo-txt span { font-size: 0.75rem; color: #6b7280; }

    .paa-notif-item {
        padding: 0.4rem 0.5rem 0.4rem 2.4rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
    }
    .paa-notif-item:hover { background: #f9fafb; }
    .paa-notif-item .texto { color: #1f2937; font-weight: 600; }
    .paa-notif-item .detalle { color: #6b7280; }
    .paa-notif-item.nivel-alta .detalle { color: #b91c1c; }
    .paa-notif-item.nivel-media .detalle { color: #92400e; }
    .paa-notif-item.no-leido { position: relative; }
    .paa-notif-item.no-leido::before {
        content: ''; position: absolute; left: 0.7rem; top: 0.85rem;
        width: 0.4rem; height: 0.4rem; border-radius: 50%; background: #0055a4;
    }

    .paa-notif-pie {
        position: sticky; bottom: 0; background: #fff;
        border-top: 1px solid #f3f4f6; padding: 0.65rem 1.1rem;
        text-align: center;
    }
    .paa-notif-pie a {
        font-size: 0.82rem; font-weight: 700; color: #0055a4; text-decoration: none;
    }
    .paa-notif-pie a:hover { text-decoration: underline; }

    @media (max-width: 480px) {
        .paa-notif-boton { top: 0.75rem; right: 0.75rem; width: 2.8rem; height: 2.8rem; }
        .paa-notif-panel { top: 3.9rem; right: 0.75rem; }
    }

    @media print {
        .paa-notif-boton, .paa-notif-panel { display: none !important; }
    }
</style>

<button type="button" class="paa-notif-boton" id="paaNotifBoton" title="Notificaciones" aria-label="Notificaciones">
    <i class="fa-solid fa-bell" aria-hidden="true"></i>
    <span class="paa-notif-insignia" id="paaNotifInsignia">0</span>
</button>

<div class="paa-notif-panel" id="paaNotifPanel">
    <div class="paa-notif-cab">
        <strong><i class="fa-solid fa-bell" style="color:#0055a4;margin-right:.4rem;"></i>Notificaciones</strong>
        <div class="paa-notif-cab-acciones">
            <button type="button" id="paaNotifMarcarLeidas" title="Marcar todas como leídas" aria-label="Marcar todas como leídas">
                <i class="fa-solid fa-check-double"></i>
            </button>
            <button type="button" id="paaNotifCerrar" aria-label="Cerrar"><i class="fa-solid fa-times"></i></button>
        </div>
    </div>
    <div id="paaNotifCuerpo">
        <div class="paa-notif-vacio"><i class="fa-solid fa-spinner fa-spin"></i>Cargando…</div>
    </div>
    <div class="paa-notif-pie">
        <a href="<?= h(base_url()) ?>Notificaciones/"><i class="fa-solid fa-list" aria-hidden="true"></i> Ver todas las notificaciones</a>
    </div>
</div>

<script>
(function () {
    const API = <?= json_encode(base_url() . 'api/notificaciones.php', JSON_UNESCAPED_SLASHES) ?>;
    const BASE = <?= json_encode(base_url(), JSON_UNESCAPED_SLASHES) ?>;
    const INTERVALO_MS = 3 * 60 * 1000; // cada 3 minutos: ver el porqué arriba del archivo.

    // "Ya lo vi" es para siempre, no por un día: un recordatorio que vuelve a
    // salir a diario por algo que ya se revisó (una situación especial, un
    // horario modificado, un aviso leído) es ruido, no aviso. Lo operativo de
    // verdad (un ticket sin atender, equipo vencido) sigue apareciendo en la
    // lista mientras siga pendiente —eso lo decide el propio backend, no esta
    // marca—; acá solo se evita que algo que la persona ya abrió y leyó le
    // vuelva a sumar al contador al día siguiente.
    const CLAVE_VISTOS = 'paaNotifVistos';
    const MAX_VISTOS_GUARDADOS = 500; // no crecer para siempre en localStorage

    const boton = document.getElementById('paaNotifBoton');
    const panel = document.getElementById('paaNotifPanel');
    const cuerpo = document.getElementById('paaNotifCuerpo');
    const insignia = document.getElementById('paaNotifInsignia');
    const cerrar = document.getElementById('paaNotifCerrar');

    let ultimosDatos = null;

    function escapar(t) {
        return String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function hoyISO() {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    /** Mapa { idDelAviso: 'YYYY-MM-DD' de la última vez que se avisó por él }. */
    function leerVistos() {
        try {
            return JSON.parse(localStorage.getItem(CLAVE_VISTOS) || '{}');
        } catch (e) {
            return {};
        }
    }

    function guardarVistos(mapa) {
        try {
            // Si hay que recortar, se descartan las entradas más viejas
            // primero (por fecha), no las que aparezcan primero en el objeto.
            const entradas = Object.entries(mapa).sort((a, b) => (a[1] < b[1] ? -1 : 1));
            localStorage.setItem(CLAVE_VISTOS, JSON.stringify(Object.fromEntries(entradas.slice(-MAX_VISTOS_GUARDADOS))));
        } catch (e) {
            // localStorage no disponible (privado, cuota, etc.): sin persistencia, no es grave.
        }
    }

    function todosLosIds(datos) {
        return (datos.grupos || []).flatMap(g => g.items.map(it => it.id)).filter(Boolean);
    }

    /** Lo que corresponde avisar: solo lo que nunca se ha visto. */
    function contarPendientes(datos) {
        const vistos = leerVistos();
        let total = 0, alta = 0;
        (datos.grupos || []).forEach(g => g.items.forEach(it => {
            if (it.id && vistos[it.id]) return;
            total++;
            if (it.nivel === 'alta') alta++;
        }));
        return { total, alta };
    }

    function actualizarInsignia(datos) {
        const { total, alta } = contarPendientes(datos);
        insignia.textContent = total > 9 ? '9+' : String(total);
        insignia.classList.toggle('visible', total > 0);
        boton.classList.toggle('hay-urgentes', alta > 0);
    }

    function pintar(datos) {
        ultimosDatos = datos;
        actualizarInsignia(datos);

        if (!datos.grupos || !datos.grupos.length) {
            cuerpo.innerHTML = '<div class="paa-notif-vacio"><i class="fa-solid fa-circle-check"></i>Todo al día. No hay pendientes.</div>';
            return;
        }

        const vistos = leerVistos();

        cuerpo.innerHTML = datos.grupos.map(g => `
            <div class="paa-notif-grupo">
                <a class="paa-notif-grupo-cab ${escapar(g.urgencia)}" href="${BASE}${g.enlace}">
                    <span class="icono"><i class="fa-solid ${escapar(g.icono)}"></i></span>
                    <span class="paa-notif-grupo-txt">
                        <strong>${escapar(g.titulo)}</strong>
                        <span>${escapar(g.resumen)}</span>
                    </span>
                </a>
                ${g.items.map(it => `
                    <div class="paa-notif-item nivel-${escapar(it.nivel)}${it.id && !vistos[it.id] ? ' no-leido' : ''}">
                        <div class="texto">${escapar(it.texto)}</div>
                        <div class="detalle">${escapar(it.detalle)}</div>
                    </div>
                `).join('')}
            </div>
        `).join('');
    }

    /** Al abrir el panel el personal ya lo vio: queda marcado para siempre, no solo por hoy. */
    function marcarComoVistos(datos) {
        const vistos = leerVistos();
        const hoy = hoyISO();
        todosLosIds(datos).forEach(id => { vistos[id] = hoy; });
        guardarVistos(vistos);
        actualizarInsignia(datos);
        cuerpo.querySelectorAll('.paa-notif-item.no-leido').forEach(el => el.classList.remove('no-leido'));
    }

    async function cargar() {
        try {
            const r = await fetch(`${API}?accion=resumen`, { cache: 'no-store' });
            const d = await r.json();
            if (d.success) pintar(d);
        } catch (e) {
            // Silencioso: un panel flotante no debe interrumpir la página si
            // la carga falla, ya se reintenta en el próximo intervalo.
        }
    }

    boton.addEventListener('click', async () => {
        const abrir = !panel.classList.contains('visible');
        panel.classList.toggle('visible', abrir);
        if (abrir) {
            await cargar();
            if (ultimosDatos) marcarComoVistos(ultimosDatos);
        }
    });
    cerrar.addEventListener('click', () => panel.classList.remove('visible'));
    document.getElementById('paaNotifMarcarLeidas').addEventListener('click', async () => {
        await cargar();
        if (ultimosDatos) marcarComoVistos(ultimosDatos);
    });
    document.addEventListener('click', e => {
        if (panel.classList.contains('visible') && !panel.contains(e.target) && !boton.contains(e.target)) {
            panel.classList.remove('visible');
        }
    });

    cargar();
    setInterval(cargar, INTERVALO_MS);
})();
</script>
