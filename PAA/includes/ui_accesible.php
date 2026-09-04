<?php
/**
 * Capa de accesibilidad compartida por todo el sistema.
 *
 * POR QUÉ EXISTE
 * Después de sentarnos a ver cómo usa el sistema una compañera no vidente con
 * NVDA, quedó claro que los problemas no eran de un módulo: eran los mismos en
 * todos. Las ventanas emergentes se abren al final del documento, así que para
 * llegar a ellas con el teclado hay que recorrer la página entera; las «X» de
 * cerrar son un ícono sin texto, así que el lector no las nombra y no hay forma
 * de saber que existen; y al guardar algo no pasa nada audible, así que no se
 * sabe si se guardó.
 *
 * Arreglar eso módulo por módulo eran veinte parches que se iban a desincronizar.
 * Acá se arregla una vez, para todos:
 *
 *   · Cualquier modal que se muestre (por clase `visible`/`activo`/`abierto` o
 *     por `display`) recibe el foco automáticamente, atrapa el Tab mientras
 *     está abierto, se cierra con Escape y devuelve el foco a donde estaba.
 *   · Todo botón de cerrar que sea solo un ícono recibe un nombre accesible.
 *   · PaaUI.exito() / PaaUI.error() son el MISMO aviso en todo el sistema.
 *
 * Se carga desde includes/barra_superior.php, que a su vez entra en todas las
 * páginas de módulo, y desde index.php.
 */

declare(strict_types=1);

if (defined('PAA_UI_ACCESIBLE_IMPRESO')) {
    return;
}
define('PAA_UI_ACCESIBLE_IMPRESO', true);
?>
<style>
/* ============================================================
   Aviso único de resultado (éxito / error) para todo el sistema
   ============================================================ */
.paa-aviso-fondo {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 10050;
}
.paa-aviso-fondo.visible { display: flex; }

.paa-aviso-caja {
    background: #fff;
    border-radius: 16px;
    padding: 1.6rem 1.5rem 1.3rem;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    text-align: center;
}
.paa-aviso-icono {
    width: 56px;
    height: 56px;
    margin: 0 auto 0.9rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
}
.paa-aviso-fondo[data-tipo="exito"] .paa-aviso-icono { background: #d1fae5; color: #065f46; }
.paa-aviso-fondo[data-tipo="error"] .paa-aviso-icono { background: #fee2e2; color: #991b1b; }
.paa-aviso-fondo[data-tipo="info"]  .paa-aviso-icono { background: #e3f0fa; color: #0055a4; }

.paa-aviso-titulo {
    font-size: 1.1rem;
    font-weight: 800;
    color: #111827;
    margin-bottom: 0.35rem;
}
.paa-aviso-texto {
    font-size: 0.92rem;
    color: #4b5563;
    margin-bottom: 1.2rem;
    line-height: 1.5;
}
.paa-aviso-boton {
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    padding: 0.7rem 1.6rem;
    background: #0055a4;
    color: #fff;
    cursor: pointer;
    min-width: 140px;
}
.paa-aviso-boton:hover { background: #003b73; }
.paa-aviso-boton:focus-visible { outline: 3px solid #6ec1e4; outline-offset: 2px; }

/* Región que NVDA lee sola: acompaña al modal para que el resultado se
   anuncie aunque el foco tarde en moverse. */
.paa-sr-solo {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* El foco siempre visible: sin esto, quien navega con teclado y sí ve
   (baja visión) tampoco sabe dónde está parado. */
.paa-modal-abierto :focus-visible,
.paa-aviso-fondo :focus-visible {
    outline: 3px solid #6ec1e4;
    outline-offset: 2px;
}

@media print {
    .paa-aviso-fondo { display: none !important; }
}
</style>

<!-- Aviso compartido. Es alertdialog y no dialog: lo que comunica es el
     resultado de algo que la persona acaba de hacer, y NVDA lo anuncia
     completo al recibir el foco. -->
<div class="paa-aviso-fondo" id="paaAvisoFondo" data-tipo="exito" role="alertdialog"
     aria-modal="true" aria-labelledby="paaAvisoTitulo" aria-describedby="paaAvisoTexto">
    <div class="paa-aviso-caja">
        <div class="paa-aviso-icono" id="paaAvisoIcono" aria-hidden="true">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="paa-aviso-titulo" id="paaAvisoTitulo">Listo</div>
        <div class="paa-aviso-texto" id="paaAvisoTexto"></div>
        <button type="button" class="paa-aviso-boton" id="paaAvisoBoton">Aceptar</button>
    </div>
</div>

<!-- Lo que se anuncia sin que haga falta mover el foco (envíos, guardados,
     cambios de estado que no abren ventana). -->
<div class="paa-sr-solo" id="paaAnuncio" role="status" aria-live="polite" aria-atomic="true"></div>

<script>
(function () {
    'use strict';

    if (window.PaaUI) { return; }

    // ================================================================
    // Aviso de resultado, el mismo en todo el sistema
    // ================================================================

    const fondo   = document.getElementById('paaAvisoFondo');
    const icono   = document.getElementById('paaAvisoIcono');
    const titulo  = document.getElementById('paaAvisoTitulo');
    const texto   = document.getElementById('paaAvisoTexto');
    const boton   = document.getElementById('paaAvisoBoton');
    const anuncio = document.getElementById('paaAnuncio');

    // Este bloque se imprime en el punto del documento donde a cada página le
    // tocó incluirlo, que en varias tarjetas del sistema queda adentro de un
    // contenedor con backdrop-filter o transform (los degradados de vidrio del
    // portal, por ejemplo). Eso le crea al aviso un origen de posición que no
    // es la pantalla, así que "position: fixed; inset: 0" deja de cubrir toda
    // la ventana y el cuadro sale descentrado o con un borde cortado. Colgarlo
    // directo de <body> lo saca de cualquier contenedor así, sin tener que
    // rastrear cuál es.
    if (fondo && fondo.parentNode !== document.body)     { document.body.appendChild(fondo); }
    if (anuncio && anuncio.parentNode !== document.body) { document.body.appendChild(anuncio); }

    const ICONOS = {
        exito: 'fa-solid fa-circle-check',
        error: 'fa-solid fa-triangle-exclamation',
        info:  'fa-solid fa-circle-info',
    };
    const TITULOS = { exito: 'Listo', error: 'No se pudo', info: 'Aviso' };

    let focoPrevioAviso = null;
    let alCerrarAviso = null;

    function mostrarAviso(mensaje, tipo, opciones) {
        opciones = opciones || {};
        tipo = ICONOS[tipo] ? tipo : 'info';

        focoPrevioAviso = document.activeElement;
        alCerrarAviso = typeof opciones.alCerrar === 'function' ? opciones.alCerrar : null;

        fondo.setAttribute('data-tipo', tipo);
        icono.innerHTML = '<i class="' + ICONOS[tipo] + '"></i>';
        titulo.textContent = opciones.titulo || TITULOS[tipo];
        texto.textContent = mensaje || '';
        ajustarPorBarra(fondo);
        fondo.classList.add('visible');

        // El foco al botón: es lo que hace que NVDA lea el diálogo entero
        // (título y texto) sin que la persona tenga que ir a buscarlo.
        boton.focus();
    }

    function cerrarAviso() {
        fondo.classList.remove('visible');

        if (focoPrevioAviso && typeof focoPrevioAviso.focus === 'function' &&
            document.contains(focoPrevioAviso)) {
            focoPrevioAviso.focus();
        }
        focoPrevioAviso = null;

        const cb = alCerrarAviso;
        alCerrarAviso = null;
        if (cb) { cb(); }
    }

    boton.addEventListener('click', cerrarAviso);
    fondo.addEventListener('click', function (e) { if (e.target === fondo) { cerrarAviso(); } });
    fondo.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { e.preventDefault(); cerrarAviso(); }
        // Un solo botón: el Tab se queda adentro sin necesidad de más lógica.
        if (e.key === 'Tab') { e.preventDefault(); boton.focus(); }
    });

    // ================================================================
    // Accesibilidad automática de TODAS las ventanas emergentes
    // ================================================================
    //
    // Los módulos abren sus modales agregando una clase (`visible`, `activo`,
    // `abierto`) o poniendo `display`. En vez de tocar los veinte módulos, acá
    // se detecta ese cambio y se aplica lo que le falta a cualquiera de ellos:
    // rol de diálogo, foco adentro, Tab atrapado, Escape para salir y foco de
    // vuelta al abrir/cerrar.

    const SELECTOR_MODAL = [
        '.fondo-modal', '.modal-fondo', '.modal-overlay', '.fondo-modal-hito',
        '[data-paa-modal]',
    ].join(', ');

    const FOCALIZABLES = [
        'a[href]', 'button:not([disabled])', 'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])', 'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])',
    ].join(', ');

    /** ¿Está visible de verdad (no basta la clase: puede estar en display:none)? */
    function estaVisible(el) {
        if (!el || !el.isConnected) { return false; }
        const estilo = window.getComputedStyle(el);
        if (estilo.display === 'none' || estilo.visibility === 'hidden') { return false; }
        return el.offsetParent !== null || estilo.position === 'fixed';
    }

    function focalizablesDe(modal) {
        return Array.prototype.filter.call(
            modal.querySelectorAll(FOCALIZABLES),
            function (el) { return estaVisible(el); }
        );
    }

    /**
     * El botón que el propio módulo usa para cerrar. Se prefiere apretarlo a
     * esconder el modal por la fuerza: así corre la lógica del módulo (limpiar
     * el formulario, recargar la lista) en vez de saltársela.
     */
    function botonCerrarDe(modal) {
        return modal.querySelector(
            '.modal-cerrar, .paa-modal-cerrar, [data-paa-cerrar], ' +
            '[id$="Cerrar"], [id^="btnCerrar"], [class*="cerrar"]'
        );
    }

    /** Le pone nombre a los botones de cerrar que son solo un ícono. */
    function nombrarBotonesDeCierre(modal) {
        const candidatos = modal.querySelectorAll('button, a[role="button"]');
        Array.prototype.forEach.call(candidatos, function (b) {
            const nombre = (b.getAttribute('aria-label') || b.textContent || '').trim();
            // Un botón cuyo único contenido es un ícono no tiene nombre: para
            // NVDA es «botón» a secas, y no hay forma de saber que es la X.
            if (nombre !== '' && nombre !== '×' && nombre !== 'X' && nombre !== '✕') { return; }

            const pista = (b.getAttribute('title') || '').trim();
            b.setAttribute('aria-label', pista !== '' ? pista : 'Cerrar');
        });
    }

    function tituloDe(modal) {
        const encabezado = modal.querySelector('h1, h2, h3, h4');
        return encabezado && encabezado.textContent.trim() !== ''
            ? encabezado.textContent.trim()
            : 'Ventana';
    }

    const estados = new WeakMap();

    /**
     * La barra superior es "position: sticky": ocupa espacio real arriba de
     * la pantalla, pero cada ventana centrada con "inset: 0" (o "top:0" +
     * "height:100%") actúa como si esa barra no existiera. Con contenido
     * alto, en vez de quedar centrada de verdad queda pegada contra el borde
     * de abajo de la barra. Se mide la barra en cada apertura (el alto
     * cambia según el ancho de pantalla) y se le resta al área en la que la
     * ventana centra su contenido — sirve para CUALQUIER ventana con ese
     * patrón, sea el modal de un módulo o el aviso compartido de éxito/error,
     * sin tocar el CSS de cada uno.
     */
    function ajustarPorBarra(el) {
        const barra = document.querySelector('.paa-barra');
        if (barra && estaVisible(barra)) {
            const alto = barra.getBoundingClientRect().height;
            el.style.top = alto + 'px';
            el.style.height = 'calc(100vh - ' + alto + 'px)';
        } else {
            el.style.top = '';
            el.style.height = '';
        }
    }

    function abrirModal(modal) {
        if (estados.has(modal)) { return; }

        const foco = document.activeElement;

        if (!modal.getAttribute('role')) { modal.setAttribute('role', 'dialog'); }
        modal.setAttribute('aria-modal', 'true');
        modal.removeAttribute('aria-hidden');

        if (!modal.getAttribute('aria-label') && !modal.getAttribute('aria-labelledby')) {
            modal.setAttribute('aria-label', tituloDe(modal));
        }

        nombrarBotonesDeCierre(modal);
        document.body.classList.add('paa-modal-abierto');
        ajustarPorBarra(modal);

        // El foco entra a la ventana. Es EL arreglo de fondo: sin esto hay que
        // recorrer con el teclado toda la página (todos los funcionarios, todo
        // el calendario) hasta llegar al final, que es donde vive el modal en
        // el documento.
        const dentro = focalizablesDe(modal);
        const encabezado = modal.querySelector('h1, h2, h3, h4');
        let destino = null;

        // El modal puede pedir a dónde quiere el foco (por ejemplo, un
        // buscador: así se puede escribir de una, y el lector igual anuncia
        // el nombre de la ventana al entrar).
        const pedido = modal.getAttribute('data-paa-foco');
        if (pedido) { destino = modal.querySelector(pedido); }

        if (destino) {
            // ya está elegido
        } else if (encabezado) {
            if (!encabezado.hasAttribute('tabindex')) { encabezado.setAttribute('tabindex', '-1'); }
            destino = encabezado;
        } else if (dentro.length) {
            destino = dentro[0];
        } else {
            if (!modal.hasAttribute('tabindex')) { modal.setAttribute('tabindex', '-1'); }
            destino = modal;
        }

        // En el mismo tick el modal puede estar todavía sin pintar.
        window.setTimeout(function () { try { destino.focus(); } catch (e) { /* ya no está */ } }, 30);

        function alTeclado(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                cerrarModal(modal);
                return;
            }
            if (e.key !== 'Tab') { return; }

            // Tab atrapado: sin esto el foco se escapa a la página de atrás y
            // se pierde el hilo de dónde se estaba.
            const lista = focalizablesDe(modal);
            if (!lista.length) { return; }

            const primero = lista[0];
            const ultimo = lista[lista.length - 1];

            if (e.shiftKey && (document.activeElement === primero || document.activeElement === modal ||
                               !modal.contains(document.activeElement))) {
                e.preventDefault();
                ultimo.focus();
            } else if (!e.shiftKey && document.activeElement === ultimo) {
                e.preventDefault();
                primero.focus();
            }
        }

        modal.addEventListener('keydown', alTeclado);
        estados.set(modal, { foco: foco, alTeclado: alTeclado });
    }

    function cerrarModal(modal) {
        const estado = estados.get(modal);

        const cerrar = botonCerrarDe(modal);
        if (cerrar) {
            cerrar.click();   // corre la lógica del módulo
        } else {
            modal.classList.remove('visible', 'activo', 'abierto');
            modal.style.display = 'none';
        }

        soltarModal(modal, estado);
    }

    function soltarModal(modal, estado) {
        estado = estado || estados.get(modal);
        if (!estado) { return; }

        modal.removeEventListener('keydown', estado.alTeclado);
        estados.delete(modal);

        // ¿Queda alguna otra ventana abierta? (se pueden encimar dos)
        const quedaAlguna = Array.prototype.some.call(
            document.querySelectorAll(SELECTOR_MODAL),
            function (otro) { return otro !== modal && estaVisible(otro); }
        );
        if (!quedaAlguna) {
            document.body.classList.remove('paa-modal-abierto');
        }

        if (estado.foco && typeof estado.foco.focus === 'function' && document.contains(estado.foco)) {
            estado.foco.focus();
        }
    }

    function revisar(modal) {
        if (estaVisible(modal)) {
            abrirModal(modal);
        } else if (estados.has(modal)) {
            soltarModal(modal);
        }
    }

    function vigilar(modal) {
        if (modal.dataset.paaVigilado === '1') { return; }
        modal.dataset.paaVigilado = '1';

        new MutationObserver(function () { revisar(modal); })
            .observe(modal, { attributes: true, attributeFilter: ['class', 'style', 'hidden'] });

        revisar(modal);
    }

    function vigilarTodos(raiz) {
        const nodos = (raiz || document).querySelectorAll(SELECTOR_MODAL);
        Array.prototype.forEach.call(nodos, vigilar);
    }

    // Los modales que el módulo crea después (listas que se repintan) también
    // quedan cubiertos.
    new MutationObserver(function (cambios) {
        cambios.forEach(function (c) {
            Array.prototype.forEach.call(c.addedNodes, function (n) {
                if (n.nodeType !== 1) { return; }
                if (n.matches && n.matches(SELECTOR_MODAL)) { vigilar(n); }
                if (n.querySelectorAll) { vigilarTodos(n); }
            });
        });
    }).observe(document.documentElement, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { vigilarTodos(); });
    } else {
        vigilarTodos();
    }

    // ================================================================
    // Confirmaciones de los módulos que todavía avisan «en la página»
    // ================================================================
    //
    // Casi todos los módulos confirman un guardado poniéndole la clase
    // `aviso exito` a un div y escribiendo el texto adentro. Eso se ve, pero
    // no se escucha: no mueve el foco, no es una región viva, y si el div
    // quedó arriba del formulario hay que devolverse por todo lo que se acaba
    // de llenar para encontrarlo.
    //
    // En vez de reescribir la confirmación de veinte módulos uno por uno, acá
    // se detecta esa clase y se levanta el MISMO aviso compartido. Así el
    // sistema entero confirma igual, y lo dice.

    let ultimoAvisoTexto = '';
    let ultimoAvisoEn = 0;

    function esAvisoDeExito(el) {
        return el.nodeType === 1 &&
               el.classList.contains('aviso') &&
               el.classList.contains('exito') &&
               el.textContent.trim() !== '';
    }

    function revisarAviso(el) {
        if (!esAvisoDeExito(el)) { return; }

        const texto = el.textContent.trim();
        const ahora = Date.now();

        // El mismo texto dos veces seguidas es el mismo guardado (la clase se
        // vuelve a poner al repintar): no se anuncia dos veces.
        if (texto === ultimoAvisoTexto && ahora - ultimoAvisoEn < 4000) { return; }

        // Si el módulo ya abrió el aviso compartido a propósito, no se
        // duplica.
        if (fondo.classList.contains('visible')) { return; }

        ultimoAvisoTexto = texto;
        ultimoAvisoEn = ahora;
        mostrarAviso(texto, 'exito');
    }

    new MutationObserver(function (cambios) {
        cambios.forEach(function (c) {
            if (c.type === 'attributes' && c.target) { revisarAviso(c.target); }

            if (c.type === 'childList') {
                if (c.target && c.target.nodeType === 1) { revisarAviso(c.target); }
                Array.prototype.forEach.call(c.addedNodes, function (n) {
                    if (n.nodeType === 1) { revisarAviso(n); }
                });
            }
        });
    }).observe(document.documentElement, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['class'],
    });

    // ================================================================
    // API pública
    // ================================================================

    window.PaaUI = {
        /** Aviso de que algo salió bien. Es el mismo en todos los módulos. */
        exito: function (mensaje, opciones) { mostrarAviso(mensaje, 'exito', opciones); },
        /** Aviso de que algo falló. */
        error: function (mensaje, opciones) { mostrarAviso(mensaje, 'error', opciones); },
        /** Aviso neutro. */
        info: function (mensaje, opciones) { mostrarAviso(mensaje, 'info', opciones); },
        cerrarAviso: cerrarAviso,

        /**
         * Dice algo en voz alta sin interrumpir ni abrir nada: para lo que pasa
         * seguido (un mensaje enviado, un filtro aplicado) y no amerita una
         * ventana.
         */
        anunciar: function (mensaje) {
            anuncio.textContent = '';
            window.setTimeout(function () { anuncio.textContent = mensaje; }, 60);
        },

        /** Manda el foco a un elemento (y lo hace enfocable si no lo era). */
        enfocar: function (el) {
            if (typeof el === 'string') { el = document.getElementById(el); }
            if (!el) { return; }
            if (!el.hasAttribute('tabindex')) { el.setAttribute('tabindex', '-1'); }
            window.setTimeout(function () { el.focus(); }, 30);
        },

        /** Registra a mano un modal que no calce con los selectores de arriba. */
        vigilarModal: vigilar,
    };
})();
</script>
