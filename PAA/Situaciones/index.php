<?php
/**
 * Módulo Situaciones Especiales.
 *
 * Vacaciones, incapacidades, reuniones, cambios de horario, trabajo remoto,
 * citas médicas y permisos. Cada quien registra los suyos —el "quién" sale
 * de la sesión, no se escribe a mano— con la fecha en que empiezan y en que
 * terminan. Todos ven el calendario completo, para saber quién está fuera
 * y cuándo; cada quien edita y borra solo lo suyo, salvo administración y
 * desarrollo, que pueden corregir cualquiera (ver api/situaciones.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo('situaciones', '../');
$esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Situaciones Especiales · UCR</title>
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
            --danger: #ef4444; --morado: #8b5cf6; --cian: #06b6d4; --cafe: #78350f;
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
        .mes-actual { font-weight: 800; font-size: 1.05rem; color: var(--gray-800); min-width: 190px; text-align: center; }
        #filtroFuncionario { width: auto; min-width: 210px; }

        /* ---------- historial por funcionario ---------- */
        .historial { display: none; }
        .historial.visible { display: block; }
        .historial-cab {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap; margin-bottom: .9rem;
        }
        .historial-cab h2 { font-size: 1rem; color: var(--gray-800); }
        .historial-cab .ano-nav { display: flex; align-items: center; gap: .5rem; }
        .historial-cab .ano-nav span { font-weight: 800; min-width: 3.2rem; text-align: center; }
        .historial-resumen {
            display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem;
        }
        .historial-chip {
            font-size: .74rem; font-weight: 700; padding: .3rem .6rem; border-radius: 999px;
            color: #fff; white-space: nowrap;
        }
        .historial-lista { display: flex; flex-direction: column; gap: .6rem; max-height: 420px; overflow-y: auto; }
        .historial-item {
            border: 1px solid var(--gray-200); border-left: 4px solid var(--primary);
            border-radius: .5rem; padding: .6rem .8rem; cursor: pointer;
            display: flex; align-items: center; justify-content: space-between; gap: .8rem;
            width: 100%; text-align: left; font: inherit; color: inherit; background: #fff;
        }
        .historial-item:hover { background: var(--gray-50); }
        .historial-item:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
        .historial-item .tipo-etiqueta { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
        .historial-item .rango { font-size: .78rem; color: var(--gray-500); margin-top: .1rem; }
        .historial-item .dias { font-size: .72rem; color: var(--gray-500); font-weight: 700; white-space: nowrap; }

        /* ---------- leyenda ---------- */
        .leyenda { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-top: -.5rem; margin-bottom: 1.1rem; font-size: .76rem; color: var(--gray-600); }
        .leyenda span { display: inline-flex; align-items: center; gap: .35rem; }
        .leyenda i { width: .6rem; height: .6rem; border-radius: 50%; display: inline-block; }

        /* ---------- layout del calendario ----------
           Una sola columna: lo del día pasó a una ventana emergente. */
        .layout { display: block; }

        /* ---------- fecha en tres listas ---------- */
        .grupo-fecha {
            border: 1px solid var(--gray-200); border-radius: .6rem;
            padding: .6rem .8rem .8rem; margin-bottom: .9rem;
        }
        .grupo-fecha legend {
            font-size: .8rem; font-weight: 700; color: var(--gray-700); padding: 0 .35rem;
        }
        .fecha-partes { display: flex; gap: .5rem; flex-wrap: wrap; }
        .fecha-partes > div { flex: 1 1 100px; min-width: 90px; }
        .fecha-partes label {
            display: block; font-size: .72rem; font-weight: 600;
            color: var(--gray-500); margin-bottom: .2rem;
        }
        .fecha-partes select {
            width: 100%; border: 1.5px solid var(--gray-300); border-radius: .45rem;
            padding: .5rem .4rem; font-family: inherit; font-size: .88rem; background: #fff;
        }
        .fecha-partes select:focus-visible { outline: 3px solid var(--primary-light); outline-offset: 1px; }
        .fecha-resumen { font-size: .78rem; color: var(--gray-600); margin-top: .5rem; }

        .cal-cabecera { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 4px; }
        .cal-cabecera span {
            text-align: center; font-size: .7rem; font-weight: 800; color: var(--gray-500);
            text-transform: uppercase; letter-spacing: .04em; padding: .3rem 0;
        }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .cal-dia {
            min-height: 92px; border: 1px solid var(--gray-200); border-radius: .5rem;
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
            font-size: .66rem; font-weight: 700; padding: .12rem .35rem; border-radius: .3rem;
            color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .cal-mas { font-size: .65rem; color: var(--gray-500); font-weight: 700; }

        /* En celular no cabe el texto de los hitos (ver el media query de
           abajo), así que se reemplaza por puntos de color: sin esto un día
           con 1 o 2 situaciones/avisos se quedaba sin ninguna señal. */
        .cal-puntos { display: none; gap: .15rem; flex-wrap: wrap; margin-top: auto; }
        .cal-punto { width: .4rem; height: .4rem; border-radius: 50%; flex-shrink: 0; }
        .cal-punto-mas { font-size: .58rem; color: var(--gray-500); font-weight: 700; }

        /* ---------- panel lateral ---------- */
        .fecha-larga { font-size: .8rem; color: var(--gray-500); margin-bottom: 1rem; text-transform: capitalize; }
        .vacio { font-size: .85rem; color: var(--gray-500); padding: 1rem 0; text-align: center; }

        .hito-item {
            border: 1px solid var(--gray-200); border-left: 4px solid var(--primary);
            border-radius: .5rem; padding: .7rem .8rem; margin-bottom: .7rem;
        }
        .hito-item .titulo { font-weight: 700; font-size: .9rem; color: var(--gray-800); margin-bottom: .15rem; }
        .hito-item .tipo-etiqueta { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
        .hito-item .rango { font-size: .72rem; color: var(--gray-500); margin: .2rem 0 .35rem; }
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
        <h1>Situaciones Especiales</h1>
        <p>Vacaciones, incapacidades, reuniones, permisos y demás — quién está fuera y cuándo</p>
    </div>

    <div class="tarjeta controles">
        <div class="controles-izq">
            <button class="btn-secundario btn-icono" type="button" id="btnMesAnterior" title="Mes anterior" aria-label="Mes anterior">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <span class="mes-actual" id="mesActualTexto" aria-live="polite" aria-atomic="true"></span>
            <button class="btn-secundario btn-icono" type="button" id="btnMesSiguiente" title="Mes siguiente" aria-label="Mes siguiente">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <button class="btn-secundario" type="button" id="btnHoy">Hoy</button>
            <label for="filtroFuncionario" class="sr-only">Filtrar por funcionario</label>
            <select id="filtroFuncionario" aria-label="Filtrar por funcionario">
                <option value="">Todos los funcionarios</option>
            </select>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <button class="btn-secundario" type="button" id="btnExportar">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Descargar Excel
            </button>
            <?php if ($esAdmin): ?>
            <button class="btn-secundario" type="button" id="btnAgregarAnuncio">
                <i class="fa-solid fa-thumbtack" aria-hidden="true"></i> Agregar anuncio
            </button>
            <?php endif; ?>
            <button class="btn-primario" type="button" id="btnAgregar">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar situación
            </button>
        </div>
    </div>

    <div class="leyenda" id="leyenda"></div>

    <div class="tarjeta historial" id="historial">
        <div class="historial-cab">
            <h2 id="historialTitulo">Historial</h2>
            <div class="ano-nav">
                <button class="btn-secundario btn-icono" type="button" id="btnAnoAnterior" title="Año anterior" aria-label="Año anterior">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>
                <span id="historialAnoTexto" aria-live="polite"></span>
                <button class="btn-secundario btn-icono" type="button" id="btnAnoSiguiente" title="Año siguiente" aria-label="Año siguiente">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="historial-resumen" id="historialResumen"></div>
        <div class="historial-lista" id="historialLista"></div>
    </div>

    <div class="layout">
        <div class="tarjeta">
            <!-- Oculta al lector: cada casilla ya dice su día de la semana. -->
            <div class="cal-cabecera" aria-hidden="true">
                <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
            </div>
            <div class="cal-grid" id="calGrid"></div>
        </div>
    </div>
</div>

<!-- ===== VENTANA: LO DEL DÍA =====
     Antes era el panel de la derecha, al que solo se llegaba después de
     recorrer todo el calendario con el teclado. -->
<div class="fondo-modal" id="modalDia">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="diaTitulo">
        <button type="button" class="modal-cerrar" id="btnCerrarDia" aria-label="Cerrar lo del día">&times;</button>
        <h3 id="diaTitulo" tabindex="-1">El día</h3>
        <p class="fecha-larga" id="diaFecha"></p>
        <div id="diaCuerpo"></div>
    </div>
</div>

<!-- ===== MODAL: CREAR / EDITAR SITUACIÓN ===== -->
<div class="fondo-modal" id="modalHito">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
        <button type="button" class="modal-cerrar" id="btnCerrarModal" aria-label="Cerrar formulario de situación">&times;</button>
        <h3 id="modalTitulo" tabindex="-1">Agregar situación</h3>
        <div class="aviso" id="avisoModal"></div>
        <form id="formHito">
            <input type="hidden" id="hitoId">
            <div class="campo">
                <label for="hitoTipo">Tipo de situación</label>
                <select id="hitoTipo" required></select>
            </div>
            <!-- Fechas en tres listas (día, mes, año) en vez del campo de
                 fecha del navegador. Con lector de pantalla ese campo se
                 anuncia como una sola casilla y no queda claro qué se está
                 escribiendo ni en qué orden; con tres listas cada una dice qué
                 es y cuál valor quedó elegido. -->
            <fieldset class="grupo-fecha">
                <legend>Fecha de inicio</legend>
                <div class="fecha-partes" id="hitoInicioPartes"></div>
            </fieldset>
            <fieldset class="grupo-fecha">
                <legend>Fecha de fin (opcional; dejala vacía si es un solo día)</legend>
                <div class="fecha-partes" id="hitoFinPartes"></div>
            </fieldset>
            <div class="campo">
                <label for="hitoDescripcion">Detalle (opcional)</label>
                <textarea id="hitoDescripcion" rows="3" maxlength="2000"></textarea>
            </div>
            <button class="btn-primario" type="submit" id="btnGuardarHito" style="width:100%;">Guardar</button>
        </form>
    </div>
</div>

<?php if ($esAdmin): ?>
<!-- ===== MODAL: CREAR / EDITAR ANUNCIO ===== -->
<div class="fondo-modal" id="modalAnuncio">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTituloAnuncio">
        <button type="button" class="modal-cerrar" id="btnCerrarModalAnuncio" aria-label="Cerrar formulario de anuncio">&times;</button>
        <h3 id="modalTituloAnuncio" tabindex="-1">Agregar anuncio</h3>
        <div class="aviso" id="avisoModalAnuncio"></div>
        <form id="formAnuncio">
            <input type="hidden" id="anuncioId">
            <div class="campo">
                <label for="anuncioTitulo">Título</label>
                <input type="text" id="anuncioTitulo" maxlength="150" required>
            </div>
            <fieldset class="grupo-fecha">
                <legend>Fecha de inicio</legend>
                <div class="fecha-partes" id="anuncioInicioPartes"></div>
            </fieldset>
            <fieldset class="grupo-fecha">
                <legend>Fecha de fin (opcional; dejala vacía si es un solo día)</legend>
                <div class="fecha-partes" id="anuncioFinPartes"></div>
            </fieldset>
            <div class="campo">
                <label for="anuncioDescripcion">Detalle (opcional)</label>
                <textarea id="anuncioDescripcion" rows="3" maxlength="2000"></textarea>
            </div>
            <button class="btn-primario" type="submit" id="btnGuardarAnuncio" style="width:100%;">Guardar</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const API = '../api/situaciones.php';
const CSRF = <?= json_encode(token_csrf()) ?>;
const YO_ID = <?= (int) $yo['id'] ?>;
const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;

const TIPOS = {
    vacaciones:      { etiqueta: 'Vacaciones',        color: '#10b981' },
    incapacidad:     { etiqueta: 'Incapacidad',       color: '#ef4444' },
    reunion:         { etiqueta: 'Reunión',           color: '#0055a4' },
    cambio_horario:  { etiqueta: 'Cambio de horario', color: '#f59e0b' },
    trabajo_remoto:  { etiqueta: 'Trabajo remoto',    color: '#8b5cf6' },
    cita_medica:     { etiqueta: 'Cita médica',       color: '#06b6d4' },
    permiso:         { etiqueta: 'Permiso',           color: '#6b7280' },
    otro:            { etiqueta: 'Otro',              color: '#78350f' },
};

const ANUNCIO_COLOR = '#0f766e';
const ANUNCIO_ICONO = '📌';

const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS_LARGO = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];

let mesActual = new Date();
mesActual.setDate(1);
let situaciones = [];
let anuncios = [];
let diaSeleccionado = null;
let filtroFuncionarioId = '';
let historialAno = new Date().getFullYear();

document.addEventListener('DOMContentLoaded', () => {
    pintarLeyenda();
    llenarSelectTipos();

    document.getElementById('btnMesAnterior').addEventListener('click', () => cambiarMes(-1));
    document.getElementById('btnMesSiguiente').addEventListener('click', () => cambiarMes(1));
    document.getElementById('btnHoy').addEventListener('click', irAHoy);
    document.getElementById('btnExportar').addEventListener('click', () => {
        window.location.href = `${API}?accion=exportar`;
    });
    document.getElementById('filtroFuncionario').addEventListener('change', cambiarFiltroFuncionario);
    document.getElementById('btnAnoAnterior').addEventListener('click', () => cambiarAnoHistorial(-1));
    document.getElementById('btnAnoSiguiente').addEventListener('click', () => cambiarAnoHistorial(1));
    document.getElementById('btnAgregar').addEventListener('click', () => abrirModal());
    document.getElementById('btnCerrarModal').addEventListener('click', cerrarModal);
    document.getElementById('modalHito').addEventListener('click', e => {
        if (e.target.id === 'modalHito') cerrarModal();
    });
    document.getElementById('formHito').addEventListener('submit', guardarHito);

    document.getElementById('btnCerrarDia').addEventListener('click', cerrarDia);
    document.getElementById('modalDia').addEventListener('click', e => {
        if (e.target.id === 'modalDia') cerrarDia();
    });

    if (ES_ADMIN) {
        document.getElementById('btnAgregarAnuncio').addEventListener('click', () => abrirModalAnuncio());
        document.getElementById('btnCerrarModalAnuncio').addEventListener('click', cerrarModalAnuncio);
        document.getElementById('modalAnuncio').addEventListener('click', e => {
            if (e.target.id === 'modalAnuncio') cerrarModalAnuncio();
        });
        document.getElementById('formAnuncio').addEventListener('submit', guardarAnuncio);
    }

    cargarSituaciones();
});

function pintarLeyenda() {
    const chipsTipos = Object.values(TIPOS).map(t => `
        <span><i aria-hidden="true" style="background:${t.color}"></i>${escapar(t.etiqueta)}</span>
    `).join('');
    const chipAnuncio = `<span><i aria-hidden="true" style="background:${ANUNCIO_COLOR}"></i>${ANUNCIO_ICONO} Anuncio</span>`;

    document.getElementById('leyenda').innerHTML = chipAnuncio + chipsTipos;
}

function llenarSelectTipos() {
    document.getElementById('hitoTipo').innerHTML = Object.entries(TIPOS).map(([clave, t]) => `
        <option value="${clave}">${escapar(t.etiqueta)}</option>
    `).join('');
}

function puedeEditar(s) {
    return ES_ADMIN || s.usuarioId === YO_ID;
}

async function cargarSituaciones() {
    try {
        const d = await pedir(`${API}?accion=listar`);
        situaciones = d.situaciones || [];
        anuncios = d.anuncios || [];
        llenarSelectFuncionarios();
        pintarCalendario();
        pintarPanel();
        pintarHistorial();
    } catch (e) {
        situaciones = [];
        anuncios = [];
        pintarCalendario();
        document.getElementById('diaCuerpo').innerHTML = `<p class="vacio">${escapar(e.message)}</p>`;
    }
}

function llenarSelectFuncionarios() {
    const select = document.getElementById('filtroFuncionario');
    const vistos = new Map();
    situaciones.forEach(s => { if (!vistos.has(s.usuarioId)) vistos.set(s.usuarioId, s.nombre); });

    const personas = [...vistos.entries()].sort((a, b) => a[1].localeCompare(b[1], 'es'));

    const seleccionActual = select.value;
    select.innerHTML = '<option value="">Todos los funcionarios</option>'
        + personas.map(([id, nombre]) => `<option value="${id}">${escapar(nombre)}</option>`).join('');
    select.value = seleccionActual;
}

function cambiarFiltroFuncionario() {
    filtroFuncionarioId = document.getElementById('filtroFuncionario').value;
    pintarCalendario();
    pintarHistorial();
}

function cambiarAnoHistorial(delta) {
    historialAno += delta;
    pintarHistorial();
}

/** El resumen anual de un funcionario: solo tiene sentido con uno seleccionado. */
function pintarHistorial() {
    const bloque = document.getElementById('historial');

    if (!filtroFuncionarioId) {
        bloque.classList.remove('visible');
        return;
    }
    bloque.classList.add('visible');

    const idNum = Number(filtroFuncionarioId);
    const nombre = (situaciones.find(s => s.usuarioId === idNum) || {}).nombre || '';
    const delAno = situaciones
        .filter(s => s.usuarioId === idNum && s.fechaInicio.slice(0, 4) === String(historialAno))
        .sort((a, b) => a.fechaInicio.localeCompare(b.fechaInicio));

    document.getElementById('historialTitulo').textContent = `Historial de ${nombre}`;
    document.getElementById('historialAnoTexto').textContent = historialAno;

    const porTipo = {};
    delAno.forEach(s => {
        const dias = diasEntre(s.fechaInicio, s.fechaFin || s.fechaInicio);
        if (!porTipo[s.tipo]) porTipo[s.tipo] = { cantidad: 0, dias: 0 };
        porTipo[s.tipo].cantidad += 1;
        porTipo[s.tipo].dias += dias;
    });

    const resumen = document.getElementById('historialResumen');
    const clavesTipo = Object.keys(porTipo);
    resumen.innerHTML = clavesTipo.length === 0
        ? '<span style="font-size:.8rem;color:var(--gray-500);">Sin registros este año.</span>'
        : clavesTipo.map(tipo => {
            const t = TIPOS[tipo] || TIPOS.otro;
            const r = porTipo[tipo];
            return `<span class="historial-chip" style="background:${t.color}">${escapar(t.etiqueta)}: ${r.cantidad} · ${r.dias} día${r.dias === 1 ? '' : 's'}</span>`;
        }).join('');

    const lista = document.getElementById('historialLista');
    lista.innerHTML = delAno.length === 0
        ? '<p class="vacio">Este funcionario no tiene situaciones registradas en el año.</p>'
        : delAno.map(s => {
            const t = TIPOS[s.tipo] || TIPOS.otro;
            const dias = diasEntre(s.fechaInicio, s.fechaFin || s.fechaInicio);
            const etiquetaAria = `Ir al ${rangoLegible(s)}, ${t.etiqueta}, ${dias} día${dias === 1 ? '' : 's'}`;
            return `
                <button type="button" class="historial-item" style="border-left-color:${t.color}" onclick="irAlDia('${s.fechaInicio}')" aria-label="${escapar(etiquetaAria)}">
                    <div>
                        <div class="tipo-etiqueta" style="color:${t.color}">${escapar(t.etiqueta)}</div>
                        <div class="rango">${rangoLegible(s)}${s.descripcion ? ' · ' + escapar(s.descripcion) : ''}</div>
                    </div>
                    <div class="dias">${dias} día${dias === 1 ? '' : 's'}</div>
                </button>
            `;
        }).join('');
}

function diasEntre(inicio, fin) {
    const msPorDia = 24 * 60 * 60 * 1000;
    return Math.round((new Date(fin + 'T00:00:00') - new Date(inicio + 'T00:00:00')) / msPorDia) + 1;
}

function irAlDia(iso) {
    mesActual = new Date(iso + 'T00:00:00');
    mesActual.setDate(1);
    diaSeleccionado = iso;
    pintarCalendario();
    pintarPanel();
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

/** Las situaciones que cubren un día dado (fecha_inicio <= día <= fecha_fin||fecha_inicio). */
function situacionesDelDia(iso) {
    return situaciones
        .filter(s => !filtroFuncionarioId || s.usuarioId === Number(filtroFuncionarioId))
        .filter(s => iso >= s.fechaInicio && iso <= (s.fechaFin || s.fechaInicio));
}

/** Los anuncios por fecha que cubren un día dado. */
function anunciosDelDia(iso) {
    return anuncios.filter(a => iso >= a.fechaInicio && iso <= (a.fechaFin || a.fechaInicio));
}

function pintarCalendario() {
    document.getElementById('mesActualTexto').textContent =
        `${MESES[mesActual.getMonth()]} ${mesActual.getFullYear()}`;

    const primerDiaMes = new Date(mesActual.getFullYear(), mesActual.getMonth(), 1);
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
        const anunciosDia = anunciosDelDia(iso);
        const situacionesDia = situacionesDelDia(iso);

        const clases = ['cal-dia'];
        if (fueraDeMes) clases.push('fuera-de-mes');
        if (iso === hoyIso) clases.push('hoy');
        if (iso === diaSeleccionado) clases.push('seleccionado');

        // Los anuncios van primero: son del programa entero, no de una
        // persona, así que pesan más si hay que recortar por espacio.
        const total = anunciosDia.length + situacionesDia.length;
        const visiblesAnuncios = anunciosDia.slice(0, 2);
        const visiblesSituaciones = situacionesDia.slice(0, Math.max(0, 2 - visiblesAnuncios.length));
        const resto = total - visiblesAnuncios.length - visiblesSituaciones.length;

        // Colores de TODO lo del día (no solo lo "visible" de arriba), para
        // los puntos de la vista de celular — ver media query.
        const coloresDia = [
            ...anunciosDia.map(() => ANUNCIO_COLOR),
            ...situacionesDia.map(s => (TIPOS[s.tipo] || TIPOS.otro).color),
        ];
        const puntosVisibles = coloresDia.slice(0, 4);
        const restoPuntos = coloresDia.length - puntosVisibles.length;

        celdas.push(`
            <button type="button" class="${clases.join(' ')}" data-fecha="${iso}"
                    aria-label="${escapar(etiquetaDia(fecha, anunciosDia, situacionesDia, iso === hoyIso))}"
                    aria-pressed="${iso === diaSeleccionado ? 'true' : 'false'}"
                    ${iso === hoyIso ? 'aria-current="date"' : ''}>
                <span class="cal-numero" aria-hidden="true">${fecha.getDate()}</span>
                ${visiblesAnuncios.map(a => `
                    <span class="cal-hito" aria-hidden="true" style="background:${ANUNCIO_COLOR}">${ANUNCIO_ICONO} ${escapar(a.titulo)}</span>
                `).join('')}
                ${visiblesSituaciones.map(s => `
                    <span class="cal-hito" aria-hidden="true" style="background:${(TIPOS[s.tipo] || TIPOS.otro).color}">${escapar(s.nombre)}</span>
                `).join('')}
                ${resto > 0 ? `<span class="cal-mas" aria-hidden="true">+${resto} más</span>` : ''}
                ${coloresDia.length ? `
                    <span class="cal-puntos" aria-hidden="true">
                        ${puntosVisibles.map(color => `<span class="cal-punto" style="background:${color}"></span>`).join('')}
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

/** Abre la ventana con lo del día. El foco lo mueve includes/ui_accesible.php. */
function abrirDia() {
    document.getElementById('modalDia').classList.add('visible');
}

function cerrarDia() {
    document.getElementById('modalDia').classList.remove('visible');
}

/** Fecha completa en español, con anuncios y situaciones del día, para el
 *  aria-label de cada celda — nada queda solo en el color o en texto chico. */
function etiquetaDia(fecha, anunciosDia, situacionesDia, esHoy) {
    const nombreDia = DIAS_LARGO[fecha.getDay()];
    const fechaLegible = `${fecha.getDate()} de ${MESES[fecha.getMonth()].toLowerCase()} de ${fecha.getFullYear()}`;

    // Cada cosa del día se nombra con su etiqueta (el tipo) y su detalle: el
    // color y el texto chico de la casilla no le dicen nada a quien no ve, y
    // sin el tipo no se sabe si es una vacación, una incapacidad o un permiso.
    const partes = [];

    if (anunciosDia.length) {
        partes.push(`${anunciosDia.length} anuncio${anunciosDia.length === 1 ? '' : 's'}: ` +
            anunciosDia.map(a => a.descripcion ? `${a.titulo}, ${a.descripcion}` : a.titulo).join('; '));
    }

    if (situacionesDia.length) {
        partes.push(`${situacionesDia.length} situaci${situacionesDia.length === 1 ? 'ón' : 'ones'}: ` +
            situacionesDia.map(s => {
                const t = TIPOS[s.tipo] || TIPOS.otro;
                return `${s.nombre}, ${t.etiqueta}` + (s.descripcion ? `, ${s.descripcion}` : '');
            }).join('; '));
    }

    const etiquetaContenido = partes.length ? partes.join('. ') : 'nada registrado';

    return `${nombreDia} ${fechaLegible}${esHoy ? ', hoy' : ''}, ${etiquetaContenido}`;
}

/** Roving tabindex: una sola celda del calendario es alcanzable con Tab; las
 *  flechas mueven el foco entre las demás. */
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
        cuerpo.innerHTML = '<p class="vacio">Elegí un día del calendario para ver quién está fuera.</p>';
        return;
    }

    const fecha = new Date(diaSeleccionado + 'T00:00:00');
    const fechaLarga = `${DIAS_LARGO[fecha.getDay()]} ${fecha.getDate()} de ${MESES[fecha.getMonth()].toLowerCase()} de ${fecha.getFullYear()}`;

    // El día completo, en el título: es lo primero que se anuncia al abrir.
    panelTitulo.textContent = fechaLarga;
    panelFecha.textContent = fechaLarga;

    const anunciosDia = anunciosDelDia(diaSeleccionado);
    const situacionesDia = situacionesDelDia(diaSeleccionado);

    const htmlAnuncios = anunciosDia.map(a => `
        <div class="hito-item" style="border-left-color:${ANUNCIO_COLOR}">
            <div class="titulo">${ANUNCIO_ICONO} ${escapar(a.titulo)}</div>
            <div class="tipo-etiqueta" style="color:${ANUNCIO_COLOR}">Anuncio</div>
            <div class="rango">${rangoLegible(a)}</div>
            ${a.descripcion ? `<div class="descripcion">${escapar(a.descripcion)}</div>` : ''}
            ${ES_ADMIN ? `
                <div class="acciones">
                    <button class="btn-secundario" type="button" onclick="abrirModalAnuncio(${a.id})" aria-label="Editar anuncio ${escapar(a.titulo)}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Editar</button>
                    <button class="btn-peligro" type="button" onclick="eliminarAnuncio(${a.id})" aria-label="Borrar anuncio ${escapar(a.titulo)}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Borrar</button>
                </div>
            ` : ''}
        </div>
    `).join('');

    const htmlSituaciones = situacionesDia.map(s => {
        const t = TIPOS[s.tipo] || TIPOS.otro;
        return `
            <div class="hito-item" style="border-left-color:${t.color}">
                <div class="titulo">${escapar(s.nombre)}</div>
                <div class="tipo-etiqueta" style="color:${t.color}">${escapar(t.etiqueta)}</div>
                <div class="rango">${rangoLegible(s)}</div>
                ${s.descripcion ? `<div class="descripcion">${escapar(s.descripcion)}</div>` : ''}
                ${puedeEditar(s) ? `
                    <div class="acciones">
                        <button class="btn-secundario" type="button" onclick="abrirModal(${s.id})" aria-label="Editar ${escapar(t.etiqueta)} de ${escapar(s.nombre)}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Editar</button>
                        <button class="btn-peligro" type="button" onclick="eliminarHito(${s.id})" aria-label="Borrar ${escapar(t.etiqueta)} de ${escapar(s.nombre)}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Borrar</button>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');

    let html = htmlAnuncios + htmlSituaciones;

    if (!anunciosDia.length && !situacionesDia.length) {
        html = '<p class="vacio">Nada registrado este día.</p>';
    }

    cuerpo.innerHTML = html;
}

function rangoLegible(s) {
    if (!s.fechaFin || s.fechaFin === s.fechaInicio) {
        return fechaCorta(s.fechaInicio);
    }
    return `${fechaCorta(s.fechaInicio)} — ${fechaCorta(s.fechaFin)}`;
}

function fechaCorta(iso) {
    const f = new Date(iso + 'T00:00:00');
    return `${f.getDate()} de ${MESES[f.getMonth()].toLowerCase()}`;
}

// ===================== FECHA EN TRES LISTAS =====================
//
// El campo de fecha del navegador se anuncia como una sola casilla y no deja
// claro qué se escribe ni en qué orden. Tres listas —día, mes y año— dicen
// cada una qué son y qué quedó elegido, y se recorren con Tab como cualquier
// otro campo.

const MESES_LARGOS = ['enero','febrero','marzo','abril','mayo','junio',
                      'julio','agosto','setiembre','octubre','noviembre','diciembre'];

/** Dibuja el trío de listas dentro de un contenedor y devuelve sus ids. */
function armarSelectorFecha(idContenedor, etiqueta, opcional) {
    const cont = document.getElementById(idContenedor);
    if (!cont || cont.dataset.armado === '1') return;
    cont.dataset.armado = '1';

    const anioActual = new Date().getFullYear();
    const anios = [];
    for (let a = anioActual - 1; a <= anioActual + 2; a++) { anios.push(a); }

    const vacio = opcional ? '<option value="">—</option>' : '';

    const dias = [];
    for (let d = 1; d <= 31; d++) { dias.push(`<option value="${d}">${d}</option>`); }

    cont.innerHTML = `
        <div>
            <label for="${idContenedor}Dia">Día</label>
            <select id="${idContenedor}Dia" aria-label="Día de ${etiqueta}">${vacio}${dias.join('')}</select>
        </div>
        <div>
            <label for="${idContenedor}Mes">Mes</label>
            <select id="${idContenedor}Mes" aria-label="Mes de ${etiqueta}">
                ${vacio}${MESES_LARGOS.map((m, i) => `<option value="${i + 1}">${m}</option>`).join('')}
            </select>
        </div>
        <div>
            <label for="${idContenedor}Anio">Año</label>
            <select id="${idContenedor}Anio" aria-label="Año de ${etiqueta}">
                ${vacio}${anios.map(a => `<option value="${a}">${a}</option>`).join('')}
            </select>
        </div>
        <p class="fecha-resumen" id="${idContenedor}Resumen" role="status" aria-live="polite"></p>
    `;

    ['Dia', 'Mes', 'Anio'].forEach(parte => {
        document.getElementById(idContenedor + parte)
            .addEventListener('change', () => resumirFecha(idContenedor, etiqueta));
    });
}

/** Dice en voz alta la fecha que quedó armada, para confirmar sin ver. */
function resumirFecha(idContenedor, etiqueta) {
    const resumen = document.getElementById(idContenedor + 'Resumen');
    const iso = leerSelectorFecha(idContenedor);

    if (!resumen) return;

    if (!iso) {
        resumen.textContent = '';
        return;
    }

    const f = new Date(iso + 'T00:00:00');
    resumen.textContent = `${etiqueta}: ${DIAS_LARGO[f.getDay()]} ${f.getDate()} de ` +
                          `${MESES_LARGOS[f.getMonth()]} de ${f.getFullYear()}`;
}

/** La fecha elegida en formato ISO, o '' si está incompleta. */
function leerSelectorFecha(idContenedor) {
    const d = document.getElementById(idContenedor + 'Dia').value;
    const m = document.getElementById(idContenedor + 'Mes').value;
    const a = document.getElementById(idContenedor + 'Anio').value;

    if (!d || !m || !a) return '';

    return `${a}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
}

/** Deja las listas mostrando una fecha ISO (o vacías si no hay). */
function ponerSelectorFecha(idContenedor, iso, etiqueta) {
    const d = document.getElementById(idContenedor + 'Dia');
    const m = document.getElementById(idContenedor + 'Mes');
    const a = document.getElementById(idContenedor + 'Anio');

    if (!iso) {
        d.value = ''; m.value = ''; a.value = '';
        resumirFecha(idContenedor, etiqueta);
        return;
    }

    const partes = iso.split('-');
    a.value = String(Number(partes[0]));
    m.value = String(Number(partes[1]));
    d.value = String(Number(partes[2]));
    resumirFecha(idContenedor, etiqueta);
}

let elementoFocoPrevio = null;

function abrirModal(id) {
    // Se llega desde la ventana del día: se cierra para no encimar ventanas.
    cerrarDia();

    armarSelectorFecha('hitoInicioPartes', 'fecha de inicio', false);
    armarSelectorFecha('hitoFinPartes', 'fecha de fin', true);

    const form = document.getElementById('formHito');
    form.reset();
    document.getElementById('avisoModal').className = 'aviso';
    document.getElementById('hitoId').value = '';

    if (id) {
        const s = situaciones.find(x => x.id === id);
        if (!s || !puedeEditar(s)) return;
        document.getElementById('modalTitulo').textContent = 'Editar situación';
        document.getElementById('hitoId').value = s.id;
        document.getElementById('hitoTipo').value = s.tipo;
        ponerSelectorFecha('hitoInicioPartes', s.fechaInicio, 'fecha de inicio');
        ponerSelectorFecha('hitoFinPartes', s.fechaFin || '', 'fecha de fin');
        document.getElementById('hitoDescripcion').value = s.descripcion || '';
    } else {
        document.getElementById('modalTitulo').textContent = 'Agregar situación';
        ponerSelectorFecha('hitoInicioPartes', diaSeleccionado || formatoISO(new Date()), 'fecha de inicio');
        ponerSelectorFecha('hitoFinPartes', '', 'fecha de fin');
        document.getElementById('hitoTipo').selectedIndex = 0;
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

async function guardarHito(evento) {
    evento.preventDefault();

    const id = document.getElementById('hitoId').value;
    const inicio = leerSelectorFecha('hitoInicioPartes');

    if (!inicio) {
        const aviso = document.getElementById('avisoModal');
        aviso.textContent = 'Falta la fecha de inicio: elegí día, mes y año.';
        aviso.className = 'aviso error visible';
        document.getElementById('hitoInicioPartesDia').focus();
        return;
    }

    const cuerpo = {
        accion: id ? 'actualizar' : 'crear',
        tipo: document.getElementById('hitoTipo').value,
        fecha_inicio: inicio,
        fecha_fin: leerSelectorFecha('hitoFinPartes'),
        descripcion: document.getElementById('hitoDescripcion').value.trim(),
    };
    if (id) cuerpo.id = id;

    const btn = document.getElementById('btnGuardarHito');
    btn.disabled = true;

    try {
        await pedirPost(API, cuerpo);
        cerrarModal();
        await cargarSituaciones();
        PaaUI.exito(id ? 'La situación quedó actualizada.' : 'La situación quedó registrada.');
    } catch (e) {
        const aviso = document.getElementById('avisoModal');
        aviso.textContent = e.message;
        aviso.className = 'aviso error visible';
    } finally {
        btn.disabled = false;
    }
}

async function eliminarHito(id) {
    if (!confirm('¿Borrar esta situación?')) return;

    try {
        await pedirPost(API, { accion: 'eliminar', id });
        cerrarDia();
        await cargarSituaciones();
        PaaUI.exito('La situación se borró.');
    } catch (e) {
        PaaUI.error(e.message);
    }
}

let elementoFocoPrevioAnuncio = null;

function abrirModalAnuncio(id) {
    if (!ES_ADMIN) return;

    cerrarDia();

    armarSelectorFecha('anuncioInicioPartes', 'fecha de inicio', false);
    armarSelectorFecha('anuncioFinPartes', 'fecha de fin', true);

    const form = document.getElementById('formAnuncio');
    form.reset();
    document.getElementById('avisoModalAnuncio').className = 'aviso';
    document.getElementById('anuncioId').value = '';

    if (id) {
        const a = anuncios.find(x => x.id === id);
        if (!a) return;
        document.getElementById('modalTituloAnuncio').textContent = 'Editar anuncio';
        document.getElementById('anuncioId').value = a.id;
        document.getElementById('anuncioTitulo').value = a.titulo;
        ponerSelectorFecha('anuncioInicioPartes', a.fechaInicio, 'fecha de inicio');
        ponerSelectorFecha('anuncioFinPartes', a.fechaFin || '', 'fecha de fin');
        document.getElementById('anuncioDescripcion').value = a.descripcion || '';
    } else {
        document.getElementById('modalTituloAnuncio').textContent = 'Agregar anuncio';
        ponerSelectorFecha('anuncioInicioPartes', diaSeleccionado || formatoISO(new Date()), 'fecha de inicio');
        ponerSelectorFecha('anuncioFinPartes', '', 'fecha de fin');
    }

    elementoFocoPrevioAnuncio = document.activeElement;
    document.getElementById('modalAnuncio').classList.add('visible');
    document.getElementById('modalTituloAnuncio').focus();
}

function cerrarModalAnuncio() {
    document.getElementById('modalAnuncio').classList.remove('visible');
    if (elementoFocoPrevioAnuncio && typeof elementoFocoPrevioAnuncio.focus === 'function') {
        elementoFocoPrevioAnuncio.focus();
        elementoFocoPrevioAnuncio = null;
    }
}

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    const modalHito = document.getElementById('modalHito');
    const modalAnuncio = document.getElementById('modalAnuncio');
    if (modalHito && modalHito.classList.contains('visible')) {
        cerrarModal();
    } else if (modalAnuncio && modalAnuncio.classList.contains('visible')) {
        cerrarModalAnuncio();
    }
});

async function guardarAnuncio(evento) {
    evento.preventDefault();
    if (!ES_ADMIN) return;

    const id = document.getElementById('anuncioId').value;
    const inicio = leerSelectorFecha('anuncioInicioPartes');

    if (!inicio) {
        const aviso = document.getElementById('avisoModalAnuncio');
        aviso.textContent = 'Falta la fecha de inicio: elegí día, mes y año.';
        aviso.className = 'aviso error visible';
        document.getElementById('anuncioInicioPartesDia').focus();
        return;
    }

    const cuerpo = {
        accion: id ? 'actualizarAnuncio' : 'crearAnuncio',
        titulo: document.getElementById('anuncioTitulo').value.trim(),
        fecha_inicio: inicio,
        fecha_fin: leerSelectorFecha('anuncioFinPartes'),
        descripcion: document.getElementById('anuncioDescripcion').value.trim(),
    };
    if (id) cuerpo.id = id;

    const btn = document.getElementById('btnGuardarAnuncio');
    btn.disabled = true;

    try {
        await pedirPost(API, cuerpo);
        cerrarModalAnuncio();
        await cargarSituaciones();
        PaaUI.exito(id ? 'El anuncio quedó actualizado.' : 'El anuncio quedó publicado.');
    } catch (e) {
        const aviso = document.getElementById('avisoModalAnuncio');
        aviso.textContent = e.message;
        aviso.className = 'aviso error visible';
    } finally {
        btn.disabled = false;
    }
}

async function eliminarAnuncio(id) {
    if (!ES_ADMIN) return;
    if (!confirm('¿Borrar este anuncio?')) return;

    try {
        await pedirPost(API, { accion: 'eliminarAnuncio', id });
        cerrarDia();
        await cargarSituaciones();
        PaaUI.exito('El anuncio se borró.');
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
