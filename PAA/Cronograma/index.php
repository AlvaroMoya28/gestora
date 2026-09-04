<?php
/**
 * Módulo Cronograma.
 *
 * Los hitos clave del proceso de una convocatoria (envío de folletos,
 * capacitaciones, día de aplicación, corrección, publicación de
 * resultados...) en un calendario único, en vez de en la cabeza de cada
 * quien o en un Excel aparte.
 *
 * Cualquiera con acceso lo consulta; solo coordinación académica y
 * administrativa (Karol y Jenny) lo editan, más desarrollo como respaldo
 * (ver includes/modulos.php y api/cronograma.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo('cronograma', '../');

// Mismos usuarios exactos que api/cronograma.php — USUARIOS_EDITORES_CRONOGRAMA.
$esAdmin = $yo['rol'] === ROL_DESARROLLADOR || in_array((int) $yo['id'], [21, 27], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Cronograma · UCR</title>
    <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        .sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }

        :root {
            --primary: #0055a4; --primary-dark: #003b73; --primary-light: #2b7fc2;
            --secondary: #6EC1E4; --success: #10b981; --warning: #f59e0b;
            --danger: #ef4444; --morado: #8b5cf6;
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-400: #9ca3af; --gray-500: #6b7280;
            --gray-600: #4b5563; --gray-700: #374151; --gray-800: #1f2937; --gray-900: #111827;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius: 0.5rem; --radius-lg: 1rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
            color: var(--gray-800); line-height: 1.5; min-height: 100vh; padding: 1.5rem;
        }

        .app { max-width: 1240px; margin: 0 auto; }

        .barra-sesion {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra-sesion a:hover { text-decoration: underline; }

        .hero { text-align: center; margin-bottom: 1.75rem; }
        .hero .logo {
            display: inline-flex; margin-bottom: .6rem; padding: 6px 14px;
            border-radius: 12px; background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }
        .hero .logo img { display: block; width: auto; height: 28px; }
        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .hero p { color: var(--gray-600); }

        .tarjeta {
            margin-bottom: 1.25rem; padding: 1.25rem 1.35rem;
            border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
            background: #fff; box-shadow: var(--shadow);
        }

        button {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            padding: .65rem 1rem; border: 0; border-radius: var(--radius); cursor: pointer;
            font: inherit; font-size: .85rem; font-weight: 700; white-space: nowrap;
        }
        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover { background: var(--primary-dark); }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover { background: var(--gray-200); }
        .btn-peligro { background: #fee2e2; color: #991b1b; }
        .btn-peligro:hover { background: #fecaca; }
        .btn-icono { padding: .5rem .6rem; }

        select, input, textarea {
            width: 100%; padding: .62rem .75rem; border: 1px solid var(--gray-300);
            border-radius: var(--radius); font: inherit; font-size: .88rem; background: #fff;
        }
        select:focus, input:focus, textarea:focus { outline: 2px solid var(--secondary); outline-offset: -1px; }
        label { display: block; margin-bottom: .3rem; font-size: .8rem; font-weight: 700; color: var(--gray-600); }
        .campo { margin-bottom: .9rem; }
        .campo-fila { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }

        /* ---------- controles ---------- */
        .controles {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap;
        }
        .controles-izq { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
        .controles select { width: auto; min-width: 160px; }
        .mes-actual { font-weight: 800; font-size: 1.05rem; color: var(--gray-800); min-width: 190px; text-align: center; }

        /* ---------- layout del calendario ----------
           Una sola columna: los hitos del día pasaron del panel lateral a una
           ventana emergente, porque para llegar al panel con el teclado había
           que recorrer el mes completo. */
        .layout { display: block; }

        .cal-cabecera { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 4px; }
        .cal-cabecera span {
            text-align: center; font-size: .7rem; font-weight: 800; color: var(--gray-500);
            text-transform: uppercase; letter-spacing: .04em; padding: .3rem 0;
        }
        .cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; }
        .cal-dia {
            min-height: 92px; min-width: 0; border: 1px solid var(--gray-200); border-radius: .5rem;
            padding: .35rem; cursor: pointer; background: #fff; display: flex; flex-direction: column; gap: .2rem;
            transition: border-color .15s ease, background .15s ease;
            width: 100%; text-align: left; justify-content: flex-start; align-items: stretch;
            color: inherit; font: inherit;
        }
        .cal-dia:hover { border-color: var(--primary-light); }
        .cal-dia:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
        .cal-dia.fuera-de-mes { background: var(--gray-50); color: var(--gray-400); }
        .cal-dia.hoy { border-color: var(--primary); }
        .cal-dia.hoy .cal-numero { color: var(--primary); }
        .cal-dia.seleccionado { background: #eaf3fb; border-color: var(--primary); box-shadow: 0 0 0 1px var(--primary); }
        .cal-numero { font-size: .8rem; font-weight: 700; color: var(--gray-700); }
        .cal-hito {
            display: block; width: 100%; min-width: 0; max-width: 100%;
            font-size: .68rem; font-weight: 700; padding: .12rem .35rem; border-radius: .3rem;
            color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .cal-mas { font-size: .65rem; color: var(--gray-500); font-weight: 700; }

        /* En celular no cabe el texto de los hitos (ver el media query de
           abajo), así que se reemplaza por puntos de color: sin esto un día
           con 1 o 2 hitos se quedaba sin ninguna señal de que tenía algo. */
        .cal-puntos { display: none; gap: .15rem; flex-wrap: wrap; margin-top: auto; }
        .cal-punto { width: .4rem; height: .4rem; border-radius: 50%; flex-shrink: 0; }
        .cal-punto-mas { font-size: .58rem; color: var(--gray-500); font-weight: 700; }

        /* ---------- ventana de hitos del día ---------- */
        .fecha-larga { font-size: .8rem; color: var(--gray-500); margin-bottom: 1rem; text-transform: capitalize; }
        .vacio { font-size: .85rem; color: var(--gray-500); padding: 1rem 0; text-align: center; }

        .hito-item {
            border: 1px solid var(--gray-200); border-left: 4px solid var(--primary);
            border-radius: .5rem; padding: .7rem .8rem; margin-bottom: .7rem;
        }
        .hito-item .titulo { font-weight: 700; font-size: .9rem; color: var(--gray-800); margin-bottom: .15rem; }
        .hito-item .rango { font-size: .72rem; color: var(--gray-500); margin-bottom: .35rem; }
        .hito-item .descripcion { font-size: .82rem; color: var(--gray-600); white-space: pre-wrap; }
        .hito-item .acciones { display: flex; gap: .4rem; margin-top: .55rem; }
        .hito-item .acciones button { padding: .35rem .55rem; font-size: .72rem; }

        /* ---------- modal ---------- */
        .fondo-modal {
            display: none; position: fixed; inset: 0; background: rgba(17,24,39,.55);
            align-items: center; justify-content: center; z-index: 100; padding: 1rem;
        }
        .fondo-modal.visible { display: flex; }
        .modal { background: #fff; border-radius: 14px; padding: 1.5rem; max-width: 480px; width: 100%; max-height: 85vh; overflow-y: auto; }
        .modal h3 { margin-bottom: 1rem; color: var(--gray-900); }
        .modal-cerrar { float: right; background: none; border: none; font-size: 1.3rem; color: var(--gray-400); cursor: pointer; padding: 0; }

        .aviso { display: none; margin-bottom: .9rem; padding: .7rem .9rem; border-radius: var(--radius); font-size: .85rem; }
        .aviso.visible { display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; }
        .aviso.exito { background: #d1fae5; color: #065f46; }

        @media (max-width: 480px) {
            .cal-dia { min-height: 64px; }
            .cal-hito, .cal-mas { display: none; }
            .cal-puntos { display: flex; }
        }
    </style>
</head>
<body>
<div class="app">

    <div class="barra-sesion">
        <span><i class="fa-regular fa-user" aria-hidden="true"></i> <?= h($yo['nombre']) ?></span>
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div class="logo">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA">
        </div>
        <h1>Cronograma</h1>
        <p>Los hitos clave del proceso: folletos, capacitaciones, aplicación, corrección y publicación</p>
    </div>

    <div class="tarjeta controles">
        <div class="controles-izq">
            <label for="selectConvocatoria" class="sr-only">Convocatoria</label>
            <select id="selectConvocatoria" aria-label="Convocatoria"></select>
            <button class="btn-secundario btn-icono" type="button" id="btnMesAnterior" title="Mes anterior" aria-label="Mes anterior">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <span class="mes-actual" id="mesActualTexto" aria-live="polite" aria-atomic="true"></span>
            <button class="btn-secundario btn-icono" type="button" id="btnMesSiguiente" title="Mes siguiente" aria-label="Mes siguiente">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <button class="btn-secundario" type="button" id="btnHoy">Hoy</button>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <button class="btn-secundario" type="button" id="btnExportar">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Descargar Excel
            </button>
            <?php if ($esAdmin): ?>
            <button class="btn-primario" type="button" id="btnAgregar">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar hito
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="layout">
        <div class="tarjeta">
            <!-- La fila de encabezados se oculta al lector de pantalla: cada
                 día ya dice su propio día de la semana en el nombre («sábado
                 12 de setiembre»), así que leerla antes de empezar era
                 escuchar siete palabras que después se repiten en cada casilla. -->
            <div class="cal-cabecera" aria-hidden="true">
                <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
            </div>
            <div class="cal-grid" id="calGrid"></div>
        </div>
    </div>
</div>

<!-- ===== VENTANA: HITOS DEL DÍA =====
     Antes esto era un panel al lado del calendario. Para llegar a él con el
     teclado había que recorrer el mes entero, casilla por casilla. Como
     ventana emergente el foco entra directo al elegir el día y se sale con
     Escape o con la X. -->
<div class="fondo-modal" id="modalDia">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="diaTitulo">
        <button type="button" class="modal-cerrar" id="btnCerrarDia" aria-label="Cerrar los hitos del día">&times;</button>
        <h3 id="diaTitulo" tabindex="-1">Hitos del día</h3>
        <p class="fecha-larga" id="diaFecha"></p>
        <div id="diaCuerpo"></div>
    </div>
</div>

<?php if ($esAdmin): ?>
<!-- ===== MODAL: CREAR / EDITAR HITO ===== -->
<div class="fondo-modal" id="modalHito">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
        <button type="button" class="modal-cerrar" id="btnCerrarModal" aria-label="Cerrar formulario de hito">&times;</button>
        <h3 id="modalTitulo" tabindex="-1">Agregar hito</h3>
        <div class="aviso" id="avisoModal"></div>
        <form id="formHito">
            <input type="hidden" id="hitoId">
            <div class="campo">
                <label for="hitoTitulo">Título</label>
                <input type="text" id="hitoTitulo" maxlength="150" required>
            </div>
            <div class="campo campo-fila">
                <div>
                    <label for="hitoFechaInicio">Fecha de inicio</label>
                    <input type="date" id="hitoFechaInicio" required>
                </div>
                <div>
                    <label for="hitoFechaFin">Fecha de fin (opcional)</label>
                    <input type="date" id="hitoFechaFin">
                </div>
            </div>
            <div class="campo">
                <label for="hitoColor">Color</label>
                <select id="hitoColor">
                    <option value="azul">Azul</option>
                    <option value="verde">Verde</option>
                    <option value="naranja">Naranja</option>
                    <option value="rojo">Rojo</option>
                    <option value="morado">Morado</option>
                    <option value="gris">Gris</option>
                </select>
            </div>
            <div class="campo">
                <label for="hitoDescripcion">Descripción (opcional)</label>
                <textarea id="hitoDescripcion" rows="3" maxlength="2000"></textarea>
            </div>
            <button class="btn-primario" type="submit" id="btnGuardarHito" style="width:100%;">Guardar</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const API = '../api/cronograma.php';
const CSRF = <?= json_encode(token_csrf()) ?>;
const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;

const COLORES = {
    azul: '#0055a4', verde: '#10b981', naranja: '#f59e0b',
    rojo: '#ef4444', morado: '#8b5cf6', gris: '#6b7280',
};

const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS_LARGO = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];

let convocatoriaId = null;
let mesActual = new Date();
mesActual.setDate(1);
let hitos = [];
let diaSeleccionado = null;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnMesAnterior').addEventListener('click', () => cambiarMes(-1));
    document.getElementById('btnMesSiguiente').addEventListener('click', () => cambiarMes(1));
    document.getElementById('btnHoy').addEventListener('click', irAHoy);
    document.getElementById('selectConvocatoria').addEventListener('change', e => {
        convocatoriaId = e.target.value;
        cargarHitos();
    });
    document.getElementById('btnExportar').addEventListener('click', () => {
        if (!convocatoriaId) return;
        window.location.href = `${API}?accion=exportar&convocatoria_id=${encodeURIComponent(convocatoriaId)}`;
    });

    document.getElementById('btnCerrarDia').addEventListener('click', cerrarDia);
    document.getElementById('modalDia').addEventListener('click', e => {
        if (e.target.id === 'modalDia') cerrarDia();
    });

    if (ES_ADMIN) {
        document.getElementById('btnAgregar').addEventListener('click', () => abrirModal());
        document.getElementById('btnCerrarModal').addEventListener('click', cerrarModal);
        document.getElementById('modalHito').addEventListener('click', e => {
            if (e.target.id === 'modalHito') cerrarModal();
        });
        document.getElementById('formHito').addEventListener('submit', guardarHito);
    }

    cargarConvocatorias();
});

async function cargarConvocatorias() {
    try {
        const d = await pedir(`${API}?accion=listarconvocatorias`);
        const select = document.getElementById('selectConvocatoria');
        select.innerHTML = d.convocatorias.map(c => `
            <option value="${c.id}" ${c.activa ? 'selected' : ''}>
                ${escapar(c.nombre)}${c.activa ? ' (activa)' : ''}
            </option>
        `).join('');

        const activa = d.convocatorias.find(c => c.activa);
        convocatoriaId = select.value || (activa ? activa.id : (d.convocatorias[0] ? d.convocatorias[0].id : null));
        cargarHitos();
    } catch (e) {
        document.getElementById('calGrid').innerHTML = `<p class="vacio" style="grid-column:1/-1;">${escapar(e.message)}</p>`;
    }
}

async function cargarHitos() {
    if (!convocatoriaId) return;
    try {
        const d = await pedir(`${API}?accion=listar&convocatoria_id=${encodeURIComponent(convocatoriaId)}`);
        hitos = d.hitos || [];
        pintarCalendario();
        pintarPanel();
    } catch (e) {
        hitos = [];
        pintarCalendario();
        document.getElementById('diaCuerpo').innerHTML = `<p class="vacio">${escapar(e.message)}</p>`;
    }
}

function cambiarMes(delta) {
    mesActual.setMonth(mesActual.getMonth() + delta);
    pintarCalendario();
}

function irAHoy() {
    mesActual = new Date();
    mesActual.setDate(1);
    diaSeleccionado = formatoISO(new Date());
    pintarCalendario();
    pintarPanel();
}

/** Los hitos que caen en un día dado (fecha_inicio <= día <= fecha_fin||fecha_inicio). */
function hitosDelDia(iso) {
    return hitos.filter(h => iso >= h.fechaInicio && iso <= (h.fechaFin || h.fechaInicio));
}

function pintarCalendario() {
    document.getElementById('mesActualTexto').textContent =
        `${MESES[mesActual.getMonth()]} ${mesActual.getFullYear()}`;

    const primerDiaMes = new Date(mesActual.getFullYear(), mesActual.getMonth(), 1);
    // Lunes = 0 ... domingo = 6, para que la semana empiece en lunes.
    const offset = (primerDiaMes.getDay() + 6) % 7;

    const inicioGrilla = new Date(primerDiaMes);
    inicioGrilla.setDate(inicioGrilla.getDate() - offset);

    const hoyIso = formatoISO(new Date());
    const celdas = [];

    for (let i = 0; i < 42; i++) {
        const fecha = new Date(inicioGrilla);
        fecha.setDate(fecha.getDate() + i);
        const iso = formatoISO(fecha);
        const fueraDeMes = fecha.getMonth() !== mesActual.getMonth();
        const delDia = hitosDelDia(iso);

        const clases = ['cal-dia'];
        if (fueraDeMes) clases.push('fuera-de-mes');
        if (iso === hoyIso) clases.push('hoy');
        if (iso === diaSeleccionado) clases.push('seleccionado');

        const visibles = delDia.slice(0, 2);
        const resto = delDia.length - visibles.length;

        // Puntos para la vista de celular (ver media query): hasta 4, con
        // "+N" para el resto, independiente del recorte de arriba.
        const puntosVisibles = delDia.slice(0, 4);
        const restoPuntos = delDia.length - puntosVisibles.length;

        celdas.push(`
            <button type="button" class="${clases.join(' ')}" data-fecha="${iso}"
                    aria-label="${escapar(etiquetaDia(fecha, delDia, iso === hoyIso))}"
                    aria-pressed="${iso === diaSeleccionado ? 'true' : 'false'}"
                    ${iso === hoyIso ? 'aria-current="date"' : ''}>
                <span class="cal-numero" aria-hidden="true">${fecha.getDate()}</span>
                ${visibles.map(h => `
                    <span class="cal-hito" aria-hidden="true" title="${escapar(h.titulo)}" style="background:${COLORES[h.color] || COLORES.azul}">${escapar(h.titulo)}</span>
                `).join('')}
                ${resto > 0 ? `<span class="cal-mas" aria-hidden="true">+${resto} más</span>` : ''}
                ${delDia.length ? `
                    <span class="cal-puntos" aria-hidden="true">
                        ${puntosVisibles.map(h => `<span class="cal-punto" style="background:${COLORES[h.color] || COLORES.azul}"></span>`).join('')}
                        ${restoPuntos > 0 ? `<span class="cal-punto-mas">+${restoPuntos}</span>` : ''}
                    </span>` : ''}
            </button>
        `);
    }

    const grid = document.getElementById('calGrid');
    grid.innerHTML = celdas.join('');
    const celdasEl = Array.from(grid.querySelectorAll('.cal-dia'));
    celdasEl.forEach(celda => {
        celda.addEventListener('click', () => {
            diaSeleccionado = celda.dataset.fecha;
            pintarCalendario();
            pintarPanel();
            abrirDia();
        });
    });
    configurarNavegacionTeclado(celdasEl);
}

/** Abre la ventana con lo que hay ese día. El foco lo mueve includes/ui_accesible.php. */
function abrirDia() {
    document.getElementById('modalDia').classList.add('visible');
}

function cerrarDia() {
    document.getElementById('modalDia').classList.remove('visible');
}

/** Fecha completa en español, más si tiene hitos o no — todo en el aria-label
 *  de la celda, para no depender de que se note por color ni por texto chico. */
function etiquetaDia(fecha, delDia, esHoy) {
    const nombreDia = DIAS_LARGO[fecha.getDay()];
    const fechaLegible = `${fecha.getDate()} de ${MESES[fecha.getMonth()].toLowerCase()} de ${fecha.getFullYear()}`;
    let etiquetaHitos;
    if (!delDia.length) {
        etiquetaHitos = 'sin hitos';
    } else if (delDia.length === 1) {
        etiquetaHitos = `1 hito: ${delDia[0].titulo}`;
    } else {
        etiquetaHitos = `${delDia.length} hitos: ${delDia.map(h => h.titulo).join(', ')}`;
    }
    return `${nombreDia} ${fechaLegible}${esHoy ? ', hoy' : ''}, ${etiquetaHitos}`;
}

/** Roving tabindex: una sola celda del calendario es alcanzable con Tab; las
 *  flechas mueven el foco entre las demás, como en cualquier calendario
 *  accesible (el mouse dejaba de ser la única forma de recorrer los días). */
function configurarNavegacionTeclado(celdas) {
    if (!celdas.length) return;

    let indiceFoco = celdas.findIndex(c => c.dataset.fecha === diaSeleccionado);
    if (indiceFoco === -1) indiceFoco = celdas.findIndex(c => c.classList.contains('hoy'));
    if (indiceFoco === -1) indiceFoco = 0;

    celdas.forEach((c, i) => { c.tabIndex = i === indiceFoco ? 0 : -1; });

    celdas.forEach((celda, i) => {
        celda.addEventListener('keydown', e => {
            let destino = null;
            switch (e.key) {
                case 'ArrowRight': destino = i + 1; break;
                case 'ArrowLeft': destino = i - 1; break;
                case 'ArrowDown': destino = i + 7; break;
                case 'ArrowUp': destino = i - 7; break;
                case 'Home': destino = i - (i % 7); break;
                case 'End': destino = i - (i % 7) + 6; break;
                default: return;
            }
            if (destino === null || destino < 0 || destino >= celdas.length) return;
            e.preventDefault();
            celdas[i].tabIndex = -1;
            celdas[destino].tabIndex = 0;
            celdas[destino].focus();
        });
    });
}

function pintarPanel() {
    const panelTitulo = document.getElementById('diaTitulo');
    const panelFecha = document.getElementById('diaFecha');
    const cuerpo = document.getElementById('diaCuerpo');

    if (!diaSeleccionado) {
        panelTitulo.textContent = 'Seleccioná un día';
        panelFecha.textContent = '';
        cuerpo.innerHTML = '<p class="vacio">Elegí un día del calendario para ver sus hitos.</p>';
        return;
    }

    const fecha = new Date(diaSeleccionado + 'T00:00:00');
    const fechaLarga = `${DIAS_LARGO[fecha.getDay()]} ${fecha.getDate()} de ${MESES[fecha.getMonth()].toLowerCase()} de ${fecha.getFullYear()}`;

    // El día completo va en el título de la ventana: es lo primero que se
    // anuncia al abrirla, así queda claro de qué día se está hablando.
    panelTitulo.textContent = `Hitos del ${fechaLarga}`;
    panelFecha.textContent = fechaLarga;

    const delDia = hitosDelDia(diaSeleccionado);

    let html = delDia.map(h => `
        <div class="hito-item" style="border-left-color:${COLORES[h.color] || COLORES.azul}">
            <div class="titulo">${escapar(h.titulo)}</div>
            <div class="rango">${rangoLegible(h)}</div>
            ${h.descripcion ? `<div class="descripcion">${escapar(h.descripcion)}</div>` : ''}
            ${ES_ADMIN ? `
                <div class="acciones">
                    <button class="btn-secundario" type="button" onclick="abrirModal(${h.id})" aria-label="Editar ${escapar(h.titulo)}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Editar</button>
                    <button class="btn-peligro" type="button" onclick="eliminarHito(${h.id})" aria-label="Borrar ${escapar(h.titulo)}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Borrar</button>
                </div>
            ` : ''}
        </div>
    `).join('');

    if (!delDia.length) {
        html = '<p class="vacio">Sin hitos este día.</p>';
    }

    if (ES_ADMIN) {
        html += `
            <button class="btn-secundario" type="button" style="width:100%;margin-top:.4rem;" onclick="abrirModal(null, '${diaSeleccionado}')">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar hito este día
            </button>
        `;
    }

    cuerpo.innerHTML = html;
}

function rangoLegible(h) {
    if (!h.fechaFin || h.fechaFin === h.fechaInicio) {
        return fechaCorta(h.fechaInicio);
    }
    return `${fechaCorta(h.fechaInicio)} — ${fechaCorta(h.fechaFin)}`;
}

function fechaCorta(iso) {
    const f = new Date(iso + 'T00:00:00');
    return `${f.getDate()} de ${MESES[f.getMonth()].toLowerCase()}`;
}

let elementoFocoPrevio = null;

function abrirModal(id, fechaSugerida) {
    if (!ES_ADMIN) return;

    // Se llega acá desde la ventana del día: se cierra primero para no dejar
    // dos ventanas encimadas (con lector de pantalla eso es imposible de
    // seguir: no se sabe en cuál de las dos se está).
    cerrarDia();

    const form = document.getElementById('formHito');
    form.reset();
    document.getElementById('avisoModal').className = 'aviso';
    document.getElementById('hitoId').value = '';

    if (id) {
        const h = hitos.find(x => x.id === id);
        if (!h) return;
        document.getElementById('modalTitulo').textContent = 'Editar hito';
        document.getElementById('hitoId').value = h.id;
        document.getElementById('hitoTitulo').value = h.titulo;
        document.getElementById('hitoFechaInicio').value = h.fechaInicio;
        document.getElementById('hitoFechaFin').value = h.fechaFin || '';
        document.getElementById('hitoColor').value = h.color || 'azul';
        document.getElementById('hitoDescripcion').value = h.descripcion || '';
    } else {
        document.getElementById('modalTitulo').textContent = 'Agregar hito';
        document.getElementById('hitoFechaInicio').value = fechaSugerida || diaSeleccionado || formatoISO(new Date());
        document.getElementById('hitoColor').value = 'azul';
    }

    elementoFocoPrevio = document.activeElement;
    document.getElementById('modalHito').classList.add('visible');
    document.getElementById('modalTitulo').focus();
}

function cerrarModal() {
    document.getElementById('modalHito').classList.remove('visible');
    if (elementoFocoPrevio && typeof elementoFocoPrevio.focus === 'function') {
        elementoFocoPrevio.focus();
        elementoFocoPrevio = null;
    }
}

document.addEventListener('keydown', e => {
    const modal = document.getElementById('modalHito');
    if (e.key === 'Escape' && modal && modal.classList.contains('visible')) {
        cerrarModal();
    }
});

async function guardarHito(evento) {
    evento.preventDefault();
    if (!ES_ADMIN) return;

    const id = document.getElementById('hitoId').value;
    const cuerpo = {
        accion: id ? 'actualizar' : 'crear',
        convocatoria_id: convocatoriaId,
        titulo: document.getElementById('hitoTitulo').value.trim(),
        fecha_inicio: document.getElementById('hitoFechaInicio').value,
        fecha_fin: document.getElementById('hitoFechaFin').value,
        color: document.getElementById('hitoColor').value,
        descripcion: document.getElementById('hitoDescripcion').value.trim(),
    };
    if (id) cuerpo.id = id;

    const btn = document.getElementById('btnGuardarHito');
    btn.disabled = true;

    try {
        await pedirPost(API, cuerpo);
        cerrarModal();
        await cargarHitos();
        PaaUI.exito(id ? 'El hito quedó actualizado.' : 'El hito quedó agregado al cronograma.');
    } catch (e) {
        const aviso = document.getElementById('avisoModal');
        aviso.textContent = e.message;
        aviso.className = 'aviso error visible';
    } finally {
        btn.disabled = false;
    }
}

async function eliminarHito(id) {
    if (!ES_ADMIN) return;
    if (!confirm('¿Borrar este hito del cronograma?')) return;

    try {
        await pedirPost(API, { accion: 'eliminar', id });
        cerrarDia();
        await cargarHitos();
        PaaUI.exito('El hito se borró del cronograma.');
    } catch (e) {
        PaaUI.error(e.message);
    }
}

function formatoISO(fecha) {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

async function pedir(url) {
    const r = await fetch(url + (url.includes('?') ? '&' : '?') + '_ts=' + Date.now(), { cache: 'no-store' });
    return leerRespuesta(r);
}

async function pedirPost(url, cuerpo) {
    const r = await fetch(url, {
        method: 'POST',
        cache: 'no-store',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ ...cuerpo, csrf: CSRF }),
    });
    return leerRespuesta(r);
}

async function leerRespuesta(r) {
    const texto = await r.text();
    let d;
    try {
        d = JSON.parse(texto);
    } catch (e) {
        throw new Error(`El servidor no respondió correctamente (código ${r.status}).`);
    }
    if (!d.success) throw new Error(d.error || 'No se pudo consultar');
    return d;
}

function escapar(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
