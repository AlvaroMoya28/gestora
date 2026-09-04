<?php
/**
 * Módulo Revisión.
 *
 * Consulta e impresión. Este módulo NO captura nada: lee lo que el personal de
 * bodega ya guardó desde Ingreso de Datos y con eso levanta las actas.
 *
 * La separación es deliberada. Quien embala mete los datos y no emite
 * documentos; el acta se firma sobre lo que quedó registrado y no sobre lo que
 * hay en una pantalla a medio llenar.
 *
 * Lo que se puede hacer acá:
 *   · ver las sedes con su avance, filtradas por completadas / en proceso /
 *     sin empezar
 *   · entrar a una sede y ver todo lo capturado
 *   · imprimir su comprobante, o el de todas las del filtro en un solo PDF
 *   · mandarle el acta al coordinador
 *
 * Solo administración.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo("revision", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Revisión · UCR</title>
  <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #0055a4; --primary-dark: #003b73; --primary-light: #2b7fc2;
            --secondary: #6EC1E4; --success: #10b981; --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-400: #9ca3af; --gray-500: #6b7280;
            --gray-600: #4b5563; --gray-700: #374151; --gray-800: #1f2937;
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

        .barra {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra a:hover { text-decoration: underline; }

        .hero { text-align: center; margin-bottom: 1.75rem; }
        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .hero p { color: var(--gray-600); }

        /* ===== PASOS ===== */
        .pasos {
            display: flex; gap: 0.5rem; margin-bottom: 1.5rem;
            overflow-x: auto; padding-bottom: 0.35rem;
        }
        .paso {
            flex: 1; min-width: 140px; background: #fff; border: 2px solid var(--gray-200);
            border-radius: var(--radius); padding: 0.7rem 0.85rem; cursor: pointer;
            display: flex; align-items: center; gap: 0.6rem; transition: all .15s;
        }
        .paso:hover:not(.bloqueado) { border-color: var(--primary-light); }
        .paso.activo { border-color: var(--primary); background: #f0f7ff; }
        .paso.hecho { border-color: var(--success); }
        .paso.bloqueado { opacity: .5; cursor: not-allowed; }
        .paso-num {
            width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
            background: var(--gray-200); color: var(--gray-600);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem; font-weight: 700;
        }
        .paso.activo .paso-num { background: var(--primary); color: #fff; }
        .paso.hecho .paso-num { background: var(--success); color: #fff; }
        .paso-texto { font-size: 0.8rem; font-weight: 600; line-height: 1.25; }

        /* ===== TARJETAS ===== */
        .tarjeta {
            background: #fff; border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg); box-shadow: var(--shadow);
            padding: 1.5rem; margin-bottom: 1.25rem;
        }
        .tarjeta h2 {
            font-size: 1.05rem; font-weight: 600; color: var(--gray-700);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }

        .vista { display: none; }
        .vista.activa { display: block; }

        /* ===== SEDE ===== */
        .cabecera-sede {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; border-radius: var(--radius-lg); padding: 1.15rem 1.4rem;
            margin-bottom: 1.25rem; display: flex; justify-content: space-between;
            align-items: center; gap: 1rem; flex-wrap: wrap;
        }
        .cabecera-sede h3 { font-size: 1.1rem; font-weight: 700; }
        .cabecera-sede .datos {
            display: flex; gap: 1.25rem; flex-wrap: wrap;
            font-size: 0.8125rem; opacity: .95; margin-top: 0.3rem;
        }

        input, select, textarea {
            font-family: inherit; font-size: 0.9rem; padding: 0.5rem 0.65rem;
            border: 1px solid var(--gray-300); border-radius: var(--radius);
            background: #fff; width: 100%;
        }
        input:focus, select:focus { outline: 2px solid var(--secondary); outline-offset: -1px; }
        label { font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); }

        button {
            font-family: inherit; font-size: 0.875rem; font-weight: 600;
            padding: 0.6rem 1.1rem; border: none; border-radius: var(--radius);
            cursor: pointer; display: inline-flex; align-items: center; gap: 0.45rem;
        }
        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover { background: var(--primary-dark); }
        .btn-primario:disabled { background: var(--gray-400); cursor: not-allowed; }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover { background: var(--gray-200); }
        .btn-exito { background: var(--success); color: #fff; }
        .btn-chico { padding: 0.35rem 0.7rem; font-size: 0.78rem; }

        .acciones {
            display: flex; gap: 0.75rem; margin-top: 1.25rem;
            flex-wrap: wrap; justify-content: space-between;
        }

        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th {
            text-align: left; font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; color: var(--gray-500);
            padding: 0.55rem 0.6rem; border-bottom: 2px solid var(--gray-200); white-space: nowrap;
        }
        td { padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--gray-100); }
        tr:hover td { background: var(--gray-50); }
        .tabla-scroll { overflow-x: auto; }

        .etiqueta {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            padding: 0.15rem 0.5rem; border-radius: 2rem; white-space: nowrap;
        }
        .et-adecuacion { background: #fef3c7; color: #92400e; }
        .et-normal { background: var(--gray-100); color: var(--gray-600); }
        .et-ok { background: #d1fae5; color: #065f46; }
        .et-falta { background: #fee2e2; color: #991b1b; }

        .buscador { margin-bottom: 1rem; }

        .sedes-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.85rem; max-height: 60vh; overflow-y: auto; padding-right: 0.25rem;
        }

        /* Vista de lista: filas compactas para recorrer muchas sedes seguidas. */
        .sedes-lista { max-height: 60vh; overflow-y: auto; overflow-x: auto; }
        .sedes-lista table { font-size: 0.82rem; }
        .sedes-lista tbody tr { cursor: pointer; }
        .sedes-lista tbody tr:hover td { background: #f0f7ff; }
        .sedes-lista .avance { margin: 0; width: 70px; }

        .cambio-vista { display: flex; gap: 2px; background: var(--gray-100);
                        border-radius: var(--radius); padding: 3px; }
        .cambio-vista button {
            background: transparent; color: var(--gray-500);
            padding: 0.4rem 0.7rem; border-radius: calc(var(--radius) - 2px);
        }
        .cambio-vista button.activa { background: #fff; color: var(--primary);
                                      box-shadow: var(--shadow); }

        .btn-borrar-avance {
            background: transparent; color: var(--danger); border: 1px solid var(--danger);
            padding: 0.3rem 0.6rem; font-size: 0.75rem;
        }
        .btn-borrar-avance:hover { background: #fee2e2; }
        .sede-card {
            border: 1px solid var(--gray-200); border-radius: var(--radius);
            padding: 0.9rem 1rem; cursor: pointer; transition: all .15s; background: #fff;
        }
        .sede-card:hover { border-color: var(--primary); box-shadow: var(--shadow); }
        .sede-card .codigo { font-size: 0.72rem; color: var(--gray-500); font-weight: 700; }
        .sede-card .nombre { font-weight: 600; margin: 0.15rem 0 0.4rem; font-size: 0.9rem; }
        .sede-card .meta { font-size: 0.75rem; color: var(--gray-500); }
        .avance { display: flex; gap: 3px; margin-top: 0.5rem; }
        .avance span {
            flex: 1; height: 4px; border-radius: 2px; background: var(--gray-200);
        }
        .avance span.ok { background: var(--success); }

        /* ===== TULAS ===== */
        .tula-caja {
            border: 2px solid var(--gray-200); border-radius: var(--radius);
            padding: 0.9rem 1rem; margin-bottom: 0.75rem;
        }
        .tula-caja.con-coord { border-color: var(--warning); background: #fffbeb; }
        .tula-cab {
            display: flex; justify-content: space-between; align-items: center;
            gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.6rem;
        }
        .tula-cab strong { font-size: 0.95rem; }
        .chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .chip {
            background: var(--gray-100); border-radius: 2rem; padding: 0.2rem 0.7rem;
            font-size: 0.78rem; font-weight: 600; color: var(--gray-700);
        }
        .chip.adec { background: #fef3c7; color: #92400e; }

        .aviso {
            padding: 0.85rem 1.1rem; border-radius: var(--radius);
            margin-bottom: 1rem; font-size: 0.9rem; display: none;
        }
        .aviso.exito { background: #d1fae5; color: #065f46; display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; display: block; }
        .aviso.info  { background: #dbeafe; color: #1e40af; display: block; }

        .nota {
            font-size: 0.8125rem; color: var(--gray-500); background: var(--gray-50);
            border-left: 3px solid var(--gray-300); padding: 0.7rem 0.95rem;
            border-radius: 0 var(--radius) var(--radius) 0; margin-top: 0.9rem; line-height: 1.6;
        }

        .vacio { text-align: center; padding: 2.5rem 1rem; color: var(--gray-400); }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .tarjeta { padding: 1.15rem; }
        }

        /* ===== CONTADORES ===== */
        .contadores {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem; margin-bottom: 1.25rem;
        }
        /* Son botones y no tarjetas: cada número filtra la lista al tocarlo,
           que es lo primero que uno intenta hacer al ver un contador. */
        .contador {
            background: #fff; border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg); padding: 1rem 1.15rem;
            cursor: pointer; text-align: left; transition: all .15s;
            display: block; width: 100%;
        }
        .contador:hover { border-color: var(--primary-light); }
        .contador.activo { border-color: var(--primary); background: #f0f7ff; }
        .contador .cifra { font-size: 1.9rem; font-weight: 800; line-height: 1; }
        .contador .rotulo {
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; color: var(--gray-500); margin-top: 0.35rem;
        }
        .contador.completa .cifra { color: var(--success); }
        .contador.proceso .cifra { color: var(--warning); }
        .contador.sin_empezar .cifra { color: var(--gray-400); }
        .contador.todas .cifra { color: var(--primary); }

        .et-proceso { background: #fef3c7; color: #92400e; }
        .et-sin { background: var(--gray-100); color: var(--gray-500); }

        /* ===== MODAL DE MATERIALES ===== */
        .fondo-modal {
            position: fixed; inset: 0; background: rgba(0,0,0,.5);
            display: none; align-items: center; justify-content: center;
            z-index: 9000; padding: 1rem;
        }
        .fondo-modal.abierto { display: flex; }
        .modal {
            background: #fff; border-radius: var(--radius-lg);
            width: min(560px, 100%); max-height: 88vh;
            display: flex; flex-direction: column; box-shadow: var(--shadow-lg);
        }
        .modal-cab {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; padding: 1.1rem 1.3rem;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
        .modal-cab h3 { font-size: 1.05rem; font-weight: 700; }
        .modal-cab p { font-size: 0.8rem; opacity: .9; margin-top: 0.2rem; }
        .modal-cuerpo { padding: 1.1rem 1.3rem; overflow-y: auto; }
        .modal-pie {
            padding: 1rem 1.3rem; border-top: 1px solid var(--gray-200);
            display: flex; gap: 0.6rem; justify-content: space-between; flex-wrap: wrap;
        }
        .material-fila {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.45rem 0.5rem; border-bottom: 1px dotted var(--gray-200);
            cursor: pointer; border-radius: var(--radius);
        }
        .material-fila:hover { background: #f0f7ff; }
        .material-fila input { width: 18px; height: 18px; flex-shrink: 0; }
        .material-fila span { font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="app">

    <div class="barra">
        <span><i class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
        </div>
        <h1>Revisión</h1>
        <p>Consulta de lo capturado e impresión de comprobantes por sede</p>
    </div>

    <div id="aviso" class="aviso"></div>

    <!-- ===== LISTA ===== -->
    <div class="vista activa" id="vistaLista">

        <div class="contadores" id="contadores"></div>

        <div class="tarjeta">
            <h2><i class="fa-solid fa-magnifying-glass"></i> Buscar un folleto</h2>
            <p style="font-size:.875rem;color:var(--gray-600);margin-bottom:.9rem;">
                Encontrá en qué sede y aula quedó un folleto (de aula o del coordinador), por su número.
            </p>
            <form id="formBuscarFolleto" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:.6rem;">
                <input type="text" id="folletoBuscado" placeholder="Ej: 35192" style="flex:1;min-width:180px;">
                <button type="submit" class="btn-primario"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            </form>
            <div id="resultadoBuscarFolleto"></div>
        </div>

        <div class="tarjeta">
            <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:220px;">
                    <input type="search" id="buscarSede"
                           placeholder="Buscar por número, nombre, coordinador o fórmula…">
                </div>
                <div class="cambio-vista">
                    <button id="vistaTarjetas" onclick="cambiarVista('tarjetas')" title="Tarjetas">
                        <i class="fa-solid fa-table-cells-large"></i></button>
                    <button id="vistaFilas" onclick="cambiarVista('lista')" title="Lista">
                        <i class="fa-solid fa-list"></i></button>
                </div>
                <button class="btn-primario" onclick="imprimirLote()">
                    <i class="fa-solid fa-file-pdf"></i> Imprimir todas las del filtro</button>
            </div>

            <div id="listaSedes" class="sedes-grid"></div>
        </div>
    </div>

    <!-- ===== DETALLE DE UNA SEDE ===== -->
    <div class="vista" id="vistaDetalle">
        <div id="cabeceraSede"></div>

        <div class="tarjeta">
            <h2><i class="fa-solid fa-book"></i> Folletos por aula</h2>
            <div class="tabla-scroll">
                <table>
                    <thead><tr>
                        <th>Aula</th><th>Tipo</th><th>Fórmula</th><th>Estudiantes</th>
                        <th>Desde</th><th>Hasta</th><th>Tula</th>
                    </tr></thead>
                    <tbody id="tablaAulas"></tbody>
                </table>
            </div>
        </div>

        <div class="tarjeta">
            <h2><i class="fa-solid fa-boxes-stacked"></i> Tulas y marchamos</h2>
            <div id="listaTulas"></div>
        </div>

        <!-- Solo aparece si la sede tiene el interruptor prendido: ver
             pintarAdecuacion() en IngresoDatos/index.php. -->
        <div class="tarjeta" id="tarjetaAdecuacion" style="display:none;">
            <h2><i class="fa-solid fa-universal-access"></i> Materiales de adecuación</h2>
            <div class="tabla-scroll">
                <table id="tablaAdecuacion"></table>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: LISTA DE MATERIALES ===== -->
<div class="fondo-modal" id="modalMateriales">
    <div class="modal">
        <div class="modal-cab">
            <h3><i class="fa-solid fa-clipboard-check"></i> Materiales del acta</h3>
            <p id="modalMaterialesNota">Marcá los que ya se revisaron.</p>
        </div>
        <div class="modal-cuerpo" id="modalMaterialesLista"></div>
        <div class="modal-pie">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <button class="btn-secundario btn-chico" onclick="marcarMateriales(true)">
                    <i class="fa-solid fa-check-double"></i> Marcar todos</button>
                <button class="btn-secundario btn-chico" onclick="marcarMateriales(false)">
                    <i class="fa-regular fa-square"></i> Dejar vacía</button>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <button class="btn-secundario" onclick="cerrarMateriales(null)">Cancelar</button>
                <button class="btn-primario" onclick="cerrarMateriales(true)">
                    <i class="fa-solid fa-print"></i> Imprimir</button>
            </div>
        </div>
    </div>
</div>

<script>
const API = '../api/revision.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

// El nombre se pasa desde PHP acá arriba y no dentro del texto del acta: PHP
// metido en medio de una plantilla de JavaScript es difícil de leer y fácil de
// romper al editar.
const USUARIO = <?= json_encode($yo['nombre'], JSON_UNESCAPED_UNICODE) ?>;

let sedes = [];
let totales = { completa: 0, proceso: 0, sin_empezar: 0 };
let materiales = [];
let filtro = 'todas';          // todas | completa | proceso | sin_empezar
let detalle = null;            // { sede, aulas, tulas } de la sede abierta

let vista = 'tarjetas';
try {
    vista = localStorage.getItem('paa.revision.vista') || 'tarjetas';
} catch (e) { /* navegador en modo privado */ }

const ROTULOS = {
    completa:    'Completadas',
    proceso:     'En proceso',
    sin_empezar: 'Sin empezar'
};

document.addEventListener('DOMContentLoaded', () => {
    cambiarVista(vista);
    cargarSedes();
    cargarMateriales();
    document.getElementById('buscarSede').addEventListener('input', pintarSedes);
    document.getElementById('formBuscarFolleto').addEventListener('submit', buscarFolleto);
});

// ===================== CARGA =====================

async function cargarSedes() {
    try {
        const d = await pedir(`${API}?accion=sedes`);
        sedes = d.sedes;
        totales = d.totales;
        pintarContadores();
        pintarSedes();
    } catch (e) {
        document.getElementById('listaSedes').innerHTML =
            `<div class="vacio"><i class="fa-solid fa-triangle-exclamation"></i> ${escapar(e.message)}</div>`;
    }
}

/**
 * Catálogo de materiales.
 *
 * Si falla no se avisa en pantalla: el acta se imprime igual, solo que sin esa
 * sección. Cortar la impresión porque no se pudo leer una lista de chequeo
 * sería peor que imprimirla incompleta.
 */
async function cargarMateriales() {
    try {
        const d = await pedir(`${API}?accion=materiales`);
        materiales = d.materiales || [];
    } catch (e) {
        materiales = [];
    }
}

// ===================== LISTA =====================

function pintarContadores() {
    const total = sedes.length;

    document.getElementById('contadores').innerHTML = [
        ['todas',       'Todas las sedes', total],
        ['completa',    ROTULOS.completa,    totales.completa],
        ['proceso',     ROTULOS.proceso,     totales.proceso],
        ['sin_empezar', ROTULOS.sin_empezar, totales.sin_empezar]
    ].map(([clave, rotulo, cifra]) => `
        <button class="contador ${clave} ${filtro === clave ? 'activo' : ''}"
                onclick="filtrarPor('${clave}')">
            <div class="cifra">${cifra}</div>
            <div class="rotulo">${rotulo}</div>
        </button>`).join('');
}

function filtrarPor(clave) {
    filtro = clave;
    pintarContadores();
    pintarSedes();
}

function cambiarVista(cual) {
    vista = cual;
    try { localStorage.setItem('paa.revision.vista', cual); } catch (e) { /* modo privado */ }

    document.getElementById('vistaTarjetas').classList.toggle('activa', cual === 'tarjetas');
    document.getElementById('vistaFilas').classList.toggle('activa', cual === 'lista');
    pintarSedes();
}

/** Las sedes que se están viendo: filtro de estado más texto buscado. */
function sedesFiltradas() {
    const texto = (document.getElementById('buscarSede').value || '').toLowerCase();

    return sedes.filter(s =>
        (filtro === 'todas' || s.estado === filtro) &&
        (!texto ||
         s.codigo.toLowerCase().includes(texto) ||
         s.nombre.toLowerCase().includes(texto) ||
         (s.coordinador || '').toLowerCase().includes(texto) ||
         (s.formulas || '').toLowerCase().includes(texto))
    );
}

function pintarSedes() {
    const lista = sedesFiltradas();
    const cont = document.getElementById('listaSedes');

    if (!lista.length) {
        cont.className = '';
        cont.innerHTML = '<div class="vacio">No hay sedes que coincidan.</div>';
        return;
    }

    cont.className = vista === 'lista' ? 'sedes-lista' : 'sedes-grid';
    cont.innerHTML = vista === 'lista' ? filasSedes(lista) : tarjetasSedes(lista);
}

function etiquetaEstado(estado) {
    const clase = { completa: 'et-ok', proceso: 'et-proceso', sin_empezar: 'et-sin' }[estado];
    return `<span class="etiqueta ${clase}">${ROTULOS[estado]}</span>`;
}

function tarjetasSedes(lista) {
    return lista.map(s => `
        <div class="sede-card" onclick="abrirSede('${escapar(s.codigo)}')">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;">
                <span class="codigo">SEDE ${escapar(s.codigo)}</span>
                ${etiquetaEstado(s.estado)}
            </div>
            <div class="nombre">${escapar(s.nombre)}</div>
            <div class="meta">
                ${s.aulas} aula(s) · ${s.estudiantes} estudiantes · ${s.tulas} tula(s)
            </div>
            <div class="avance">
                ${s.pasos.map(p => `<span class="${p ? 'ok' : ''}"></span>`).join('')}
            </div>
        </div>`).join('');
}

function filasSedes(lista) {
    return `
        <table>
            <thead><tr>
                <th>Sede</th><th>Nombre</th><th>Aulas</th><th>Tulas</th>
                <th>Estado</th><th>Avance</th>
            </tr></thead>
            <tbody>${lista.map(s => `
                <tr onclick="abrirSede('${escapar(s.codigo)}')">
                    <td><strong>${escapar(s.codigo)}</strong></td>
                    <td>${escapar(s.nombre)}</td>
                    <td>${s.aulas}</td>
                    <td>${s.tulas}</td>
                    <td>${etiquetaEstado(s.estado)}</td>
                    <td><div class="avance">
                        ${s.pasos.map(p => `<span class="${p ? 'ok' : ''}"></span>`).join('')}
                    </div></td>
                </tr>`).join('')}
            </tbody>
        </table>`;
}

// ===================== BUSCADOR DE FOLLETOS =====================
const ETIQUETA_TIPO_FOLLETO = {
    aula: 'Folletos de aula',
    aula_adecuacion: 'Folletos de adecuación (mezclados en un aula)',
    aula_suelto: 'Folleto suelto de un aula (fuera del rango)',
    coordinador: 'Folletos del coordinador',
    coordinador_adecuacion: 'Folletos de adecuación del coordinador',
};

async function buscarFolleto(e) {
    e.preventDefault();
    const codigo = document.getElementById('folletoBuscado').value.trim();
    const cont = document.getElementById('resultadoBuscarFolleto');

    if (!codigo) { cont.innerHTML = ''; return; }

    cont.innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i> Buscando…</div>';

    try {
        const d = await pedir(`${API}?accion=buscarFolleto&folleto=${encodeURIComponent(codigo)}`);
        const resultados = d.resultados || [];

        if (!resultados.length) {
            cont.innerHTML = `<div class="vacio">No se encontró ningún rango que incluya el folleto ${escapar(codigo)}.</div>`;
            return;
        }

        cont.innerHTML = resultados.map(r => `
            <div class="sede-card" style="margin-top:.6rem;cursor:pointer;" onclick="abrirSede('${escapar(r.sedeCodigo)}')">
                <div class="codigo">SEDE ${escapar(r.sedeCodigo)}</div>
                <div class="nombre">${escapar(r.sedeNombre)}</div>
                <div class="meta">
                    ${escapar(ETIQUETA_TIPO_FOLLETO[r.tipo] || r.tipo)}${r.aula ? ` · Aula ${escapar(r.aula)}` : ''}<br>
                    ${r.tipo === 'aula_suelto' ? `Folleto: ${escapar(r.desde)}` : `Rango: ${escapar(r.desde)} – ${escapar(r.hasta)}`}
                </div>
            </div>
        `).join('');
    } catch (err) {
        cont.innerHTML = `<div class="vacio"><i class="fa-solid fa-triangle-exclamation"></i> ${escapar(err.message)}</div>`;
    }
}

// ===================== DETALLE =====================

async function abrirSede(codigo) {
    try {
        detalle = await pedir(`${API}?accion=sede&sede=${encodeURIComponent(codigo)}`);

        pintarCabecera();
        pintarAulas();
        pintarTulas();
        pintarAdecuacion();

        document.getElementById('vistaLista').classList.remove('activa');
        document.getElementById('vistaDetalle').classList.add('activa');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

function volverALista() {
    detalle = null;
    document.getElementById('vistaDetalle').classList.remove('activa');
    document.getElementById('vistaLista').classList.add('activa');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function pintarCabecera() {
    const s = detalle.sede;
    const enLista = sedes.find(x => x.codigo === s.codigo) || {};

    document.getElementById('cabeceraSede').innerHTML = `
        <div class="cabecera-sede">
            <div>
                <h3>Sede ${escapar(s.codigo)} · ${escapar(s.nombre)}</h3>
                <div class="datos">
                    <span><i class="fa-regular fa-calendar"></i> ${escapar(s.convocatoria)}
                        ${s.fecha ? ' · ' + escapar(s.fecha) : ''}</span>
                    <span><i class="fa-solid fa-door-open"></i> ${detalle.aulas.length} aula(s)</span>
                    <span><i class="fa-solid fa-boxes-stacked"></i> ${detalle.tulas.length} tula(s)</span>
                    ${s.turno ? `<span><i class="fa-regular fa-clock"></i> ${escapar(s.turno)}</span>` : ''}
                    ${s.coordinador ? `<span><i class="fa-regular fa-user"></i> ${escapar(s.coordinador)}</span>` : ''}
                    ${s.correoCoordinador ? `<span><i class="fa-regular fa-envelope"></i> ${escapar(s.correoCoordinador)}</span>` : ''}
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <button class="btn-secundario btn-chico" onclick="volverALista()">
                    <i class="fa-solid fa-arrow-left"></i> Volver</button>
                <button class="btn-secundario btn-chico" onclick="imprimirUna()">
                    <i class="fa-solid fa-print"></i> Imprimir comprobante</button>
                <button class="btn-secundario btn-chico" onclick="enviarActa()">
                    <i class="fa-regular fa-envelope"></i> Enviar al coordinador</button>
            </div>
        </div>
        ${enLista.estado !== 'completa' ? `
            <div class="aviso info" style="display:block">
                <i class="fa-solid fa-circle-info"></i>
                Esta sede todavía está ${enLista.estado === 'proceso' ? 'en proceso' : 'sin empezar'}.
                El comprobante se puede imprimir igual, pero lo que aún no se
                capturó va a salir con guiones.
            </div>` : ''}`;
}

function pintarAulas() {
    document.getElementById('tablaAulas').innerHTML = detalle.aulas.map(a => {
        const t = tulaDeAula(a.id);
        return `
        <tr>
            <td><strong>${escapar(a.numero)}</strong></td>
            <td><span class="etiqueta ${a.esAdecuacion ? 'et-adecuacion' : 'et-normal'}">
                ${a.esAdecuacion ? 'Adecuación' : 'Regular'}</span></td>
            <td>${escapar(a.formula || '—')}</td>
            <td>${a.asignados}</td>
            <td>${escapar(a.desde || '—')}</td>
            <td>${escapar(a.hasta || '—')}</td>
            <td>${t ? t.numero : '—'}</td>
        </tr>
        ${a.adecDesde ? `
        <tr>
            <td></td>
            <td colspan="3" style="font-size:.78rem;color:var(--gray-600)">
                <i class="fa-solid fa-puzzle-piece"></i> Folletos de adecuación mezclados en esta aula
            </td>
            <td>${escapar(a.adecDesde)}</td>
            <td>${escapar(a.adecHasta || '—')}</td>
            <td></td>
        </tr>` : ''}
        ${a.folletosSueltos ? `
        <tr>
            <td></td>
            <td colspan="3" style="font-size:.78rem;color:var(--gray-600)">
                <i class="fa-solid fa-shuffle"></i> Folletos sueltos de esta aula (fuera del rango)
            </td>
            <td colspan="2">${escapar(a.folletosSueltos.replace(/\n/g, ', '))}</td>
            <td></td>
        </tr>` : ''}`;
    }).join('');
}

function pintarTulas() {
    const cont = document.getElementById('listaTulas');

    if (!detalle.tulas.length) {
        cont.innerHTML = '<div class="vacio">Todavía no se armaron las tulas de esta sede.</div>';
        return;
    }

    cont.innerHTML = detalle.tulas.map(t => `
        <div class="tula-caja">
            <div class="tula-cab">
                <strong>Tula ${t.numero}</strong>
                <span class="etiqueta ${t.estado === 'disponible' ? 'et-normal' : 'et-ok'}">
                    ${escapar(t.estado)}</span>
            </div>
            <div class="chips" style="margin:.5rem 0;">
                ${t.aulas.map(a => `<span class="chip ${a.esAdecuacion ? 'adec' : ''}">
                    Aula ${escapar(a.numero)}</span>`).join('') || '<span class="chip">sin aulas</span>'}
                ${t.llevaCoord ? '<span class="chip adec">Folletos del coordinador</span>' : ''}
            </div>
            <div style="font-size:.8rem;color:var(--gray-500)">
                ${t.estudiantes} estudiante(s) ·
                Marchamo 1: <strong>${escapar(t.marchamo1 || '—')}</strong> ·
                Marchamo 2: <strong>${escapar(t.marchamo2 || '—')}</strong>
            </div>
        </div>`).join('');
}

/** Mismos campos y orden que pintarAdecuacion() en IngresoDatos/index.php. */
const CAMPOS_ADECUACION = [
    ['calcSimple',   'Calculadora simple'],
    ['calcParlante', 'Calculadora parlante'],
    ['atril',        'Atril'],
    ['mesa',         'Mesa'],
    ['pizarras',     'Pizarras'],
    ['audifonos',    'Audífonos'],
    ['tableta',      'Tableta'],
    ['computadora',  'Computadora'],
    ['diccionario',  'Diccionario'],
    ['lampara',      'Lámpara'],
];

/** Solo se ve si la sede tiene el interruptor prendido y trae aulas de adecuación. */
function pintarAdecuacion() {
    const tarjeta = document.getElementById('tarjetaAdecuacion');
    const filas = detalle.sede.materialesAdecuacion || [];

    if (!detalle.sede.tieneMaterialesAdecuacion || !filas.length) {
        tarjeta.style.display = 'none';
        return;
    }

    tarjeta.style.display = '';
    document.getElementById('tablaAdecuacion').innerHTML = `
        <thead><tr>
            <th>Aula</th>
            ${CAMPOS_ADECUACION.map(([, etiqueta]) => `<th>${etiqueta}</th>`).join('')}
        </tr></thead>
        <tbody>
            ${filas.map(f => `
                <tr>
                    <td><strong>${escapar(f.numero)}</strong></td>
                    ${CAMPOS_ADECUACION.map(([campo]) => `<td>${escapar(f[campo] || '—')}</td>`).join('')}
                </tr>
            `).join('')}
        </tbody>`;
}

/** La tula que contiene esa aula, o null si todavía no se armaron. */
function tulaDeAula(aulaId) {
    return detalle.tulas.find(t => t.aulas.some(a => a.id === aulaId)) || null;
}

// ===================== MATERIALES =====================
//
// Antes de imprimir se pregunta cuáles materiales quedaron revisados. El acta
// se firma con esa lista, así que dejar que salga siempre vacía obligaría a
// llenarla a mano en cada sede, y marcarla toda por defecto haría que se firme
// como revisado algo que nadie miró.

let resolverMateriales = null;

function pedirMateriales(nota) {
    document.getElementById('modalMaterialesNota').textContent = nota;

    document.getElementById('modalMaterialesLista').innerHTML = materiales.length
        ? materiales.map((m, i) => `
            <label class="material-fila">
                <input type="checkbox" data-material="${i}">
                <span>${escapar(m.nombre)}${m.unidad ? ' (' + escapar(m.unidad) + ')' : ''}</span>
            </label>`).join('')
        : '<div class="vacio">No hay materiales cargados en el catálogo.</div>';

    document.getElementById('modalMateriales').classList.add('abierto');

    return new Promise(resolver => { resolverMateriales = resolver; });
}

function marcarMateriales(valor) {
    document.querySelectorAll('#modalMaterialesLista input[type=checkbox]')
        .forEach(c => { c.checked = valor; });
}

function cerrarMateriales(aceptar) {
    document.getElementById('modalMateriales').classList.remove('abierto');

    const marcados = aceptar
        ? [...document.querySelectorAll('#modalMaterialesLista input:checked')]
              .map(c => Number(c.dataset.material))
        : null;

    if (resolverMateriales) {
        resolverMateriales(marcados);
        resolverMateriales = null;
    }
}

// ===================== IMPRESIÓN =====================

async function imprimirUna() {
    const marcados = await pedirMateriales(
        `Marcá los materiales revisados en la sede ${detalle.sede.codigo}.`);

    if (marcados === null) return;   // canceló

    const folio = `REV-${detalle.sede.codigo}-${Date.now().toString().slice(-6)}`;

    // La tabla de materiales de adecuación (si corresponde) ya va incluida
    // dentro de construirComprobante(), debajo de "Materiales a Revisar".
    abrirImpresion(
        `Acta de revisión · Sede ${detalle.sede.codigo}`,
        construirComprobante(detalle, folio, marcados, false)
    );
}

/**
 * Todas las actas del filtro, pegadas en un solo documento.
 *
 * Cada una arranca en página nueva, así el PDF sale listo para imprimir y
 * repartir sin tener que separar nada a mano. Se puede pedir la convocatoria
 * completa: el sistema anterior también lo permitía y es como se preparan las
 * carpetas antes de la aplicación.
 *
 * POR QUÉ SE ESCRIBE POR TANDAS
 * Armar trescientas actas de un solo tirón bloquea el hilo del navegador
 * varios segundos: la ventana se queda en blanco, no responde, y la gente la
 * cierra creyendo que se colgó. Escribiéndolas de a poco y cediendo el control
 * entre tanda y tanda, la barra de progreso avanza y se ve que está trabajando.
 */
async function imprimirLote() {
    const lista = sedesFiltradas();

    if (!lista.length) {
        mostrar('No hay sedes en el filtro actual.', 'info');
        return;
    }

    if (!confirm(
        `Se van a generar ${lista.length} acta(s) en un solo documento.\n\n` +
        `Filtro: ${filtro === 'todas' ? 'todas las sedes' : ROTULOS[filtro]}\n\n` +
        `${lista.length > 100 ? 'Con esta cantidad la generación puede tardar un par de minutos.\n\n' : ''}` +
        `¿Continuar?`)) return;

    const marcados = await pedirMateriales(
        `Los materiales marcados se aplican a las ${lista.length} actas.`);

    if (marcados === null) return;

    // La ventana se abre ANTES de consultar: si se abriera después, el
    // navegador la trataría como emergente no pedida por la persona y la
    // bloquearía.
    const win = abrirVentanaProgreso(`Actas de revisión · ${lista.length} sede(s)`, lista.length);

    if (!win) {
        mostrar('El navegador bloqueó la ventana. Permití las ventanas emergentes para este sitio.', 'error');
        return;
    }

    try {
        avisarEnVentana(win, 'Consultando los datos de las sedes…');

        const d = await enviar({ accion: 'varias', sedes: lista.map(s => s.codigo) });

        const POR_TANDA = 10;

        for (let i = 0; i < d.actas.length; i += POR_TANDA) {
            const tanda = d.actas.slice(i, i + POR_TANDA);

            win.document.write(tanda.map((datos, j) => {
                const folio = `REV-${datos.sede.codigo}-${Date.now().toString().slice(-6)}-${i + j}`;
                // La primera no lleva salto: si no, el PDF empieza en blanco.
                return construirComprobante(datos, folio, marcados, i + j > 0);
            }).join(''));

            avisarEnVentana(win,
                `Generando actas… ${Math.min(i + POR_TANDA, d.actas.length)} de ${d.actas.length}`);

            // Le devuelve el control al navegador para que pinte el avance.
            await new Promise(r => setTimeout(r, 0));
        }

        await cerrarEImprimir(win, d.actas.length);
        mostrar(`${d.total} acta(s) listas para imprimir.`, 'exito');

    } catch (e) {
        avisarEnVentana(win, 'No se pudo generar: ' + e.message);
        mostrar(e.message, 'error');
    }
}

/** Una sola acta: no hace falta barra de progreso. */
function abrirImpresion(titulo, html) {
    const win = window.open('', '_blank');

    if (!win) {
        mostrar('El navegador bloqueó la ventana. Permití las ventanas emergentes para este sitio.', 'error');
        return;
    }

    win.document.write(cabeceraDocumento(titulo) + html);
    win.document.close();

    // Sin esta pausa el diálogo puede abrirse antes de que el navegador
    // termine de maquetar, y sale la primera página en blanco.
    setTimeout(() => win.print(), 500);
}

/** Encabezado común de las ventanas de impresión. */
function cabeceraDocumento(titulo) {
    return `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
        <title>${escapar(titulo)}</title>
        <style>
            body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
            /* 0.2cm de margen: con el margen normal del navegador, una sede de
               muchas aulas se pasa a una segunda página casi vacía. */
            @media print { body { margin: 0.2cm; } .barra-avance { display: none !important; } }

            .barra-avance {
                position: sticky; top: 0; z-index: 10;
                display: flex; align-items: center; justify-content: space-between;
                gap: 16px; flex-wrap: wrap;
                background: #b45309; color: white; padding: 12px 20px; font-size: 14px;
            }
            .barra-avance.lista { background: #0055a4; }
            .barra-avance button {
                background: white; color: #0055a4; border: none; border-radius: 999px;
                padding: 10px 20px; font-weight: 700; font-size: 14px; cursor: pointer;
            }
            .girando {
                display: inline-block; width: 16px; height: 16px;
                border: 2px solid rgba(255,255,255,0.4); border-top-color: white;
                border-radius: 50%; animation: giro 0.8s linear infinite;
                vertical-align: -3px; margin-right: 8px;
            }
            @keyframes giro { to { transform: rotate(360deg); } }
        </style></head><body>`;
}

/**
 * Ventana con barra de estado para los lotes largos.
 *
 * El botón de imprimir NO existe todavía: si estuviera desde el inicio,
 * tocarlo a media generación bajaría un PDF incompleto sin ningún aviso.
 */
function abrirVentanaProgreso(titulo, total) {
    const win = window.open('', '_blank');
    if (!win) return null;

    win.document.write(cabeceraDocumento(titulo) + `
        <div class="barra-avance" id="barraAvance">
            <span id="estadoAvance"><span class="girando"></span>Preparando… 0 de ${total}</span>
            <span><strong>No cierres esta ventana</strong> — el botón de impresión aparece al terminar.</span>
        </div>`);

    return win;
}

/** Refleja el avance dentro de la ventana, que sigue abierta y se puede leer. */
function avisarEnVentana(win, texto) {
    const estado = win.document.getElementById('estadoAvance');
    if (estado) estado.innerHTML = '<span class="girando"></span>' + escapar(texto);
}

/** Cierra el documento, habilita la descarga y abre el diálogo. */
async function cerrarEImprimir(win, generadas) {
    win.document.write('</body></html>');
    win.document.close();

    // Sin esta pausa el diálogo puede abrirse antes de que el navegador
    // termine de maquetar, y salen páginas en blanco.
    await new Promise(r => setTimeout(r, 600));

    const barra = win.document.getElementById('barraAvance');

    if (barra) {
        barra.classList.add('lista');
        barra.innerHTML =
            `<span><strong>${generadas} acta(s) listas.</strong> ` +
            `En el diálogo elegí «Guardar como PDF» para tenerlas en un archivo.</span>`;

        const boton = win.document.createElement('button');
        boton.textContent = 'Imprimir o guardar PDF';
        boton.onclick = () => win.print();
        barra.appendChild(boton);
    }

    win.print();
}

/**
 * Manda el acta al coordinador de la sede.
 *
 * Se manda el NÚMERO DE SEDE, no la dirección: el servidor resuelve el
 * destinatario contra la base. Aceptar la dirección desde acá convertiría el
 * sistema en un relay de correo a nombre de la UCR.
 */
async function enviarActa() {
    const s = detalle.sede;

    if (!s.correoCoordinador) {
        mostrar('Esta sede no tiene correo de coordinador registrado, así que no hay a quién enviarle el acta.', 'error');
        return;
    }

    const marcados = await pedirMateriales(
        `Marcá los materiales revisados. El acta se le manda a ${s.coordinador || 'el coordinador'}.`);

    if (marcados === null) return;

    if (!confirm(
        `Se le va a enviar el acta de la sede ${s.codigo} a:\n\n` +
        `${s.coordinador || 'el coordinador'}\n${s.correoCoordinador}\n\n¿Continuar?`)) return;

    const folio = `REV-${s.codigo}-${Date.now().toString().slice(-6)}`;

    try {
        const d = await enviar({
            accion: 'enviarActa',
            sede: s.codigo,
            folio,
            asunto: `Acta de revisión y entrega de material · Sede ${s.codigo}`,
            actaHTML: construirComprobante(detalle, folio, marcados, false),
            aulasRevisadas: detalle.aulas.length
        });

        mostrar(`Acta enviada a ${d.destinatario} con el folio ${d.folio}.`, 'exito');
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

// ===================== EL ACTA =====================

/**
 * Arma el HTML del acta de una sede.
 *
 * El formato es el del sistema anterior (RevisionAulas.html), a propósito: es
 * el documento que los coordinadores llevan años firmando y el que el personal
 * sabe leer de un vistazo en la bodega. Cambiar el aspecto no aportaba nada y
 * obligaba a todos a reaprenderlo.
 *
 * Lo único que cambió son los datos de origen. Donde el acta vieja decía
 * "# Caja" ahora dice "# Tula", porque las cajas dejaron de existir como
 * unidad: el material se embala en tulas y los marchamos van por tula, no por
 * aula. Las columnas de marchamo muestran los de la tula que contiene esa aula.
 *
 * @param datos       { sede, aulas, tulas } tal como los devuelve el servidor.
 * @param marcados    Índices de los materiales revisados.
 * @param saltoDePagina Para el lote: cada acta después de la primera empieza
 *                      en hoja nueva.
 */
function construirComprobante(datos, folio, marcados, saltoDePagina) {
    const s = datos.sede;
    const totalFolletos = datos.aulas.reduce((n, a) => n + (Number(a.asignados) || 0), 0);

    return `
        <!-- print-color-adjust: le pide al navegador que imprima los fondos
             claros (el encabezado de la tabla, la barra de totales). Por
             defecto los descarta para ahorrar tinta. Es una petición, no una
             garantía: hay impresoras y ajustes que igual los ignoran, y por eso
             ningún texto del acta depende del fondo para poder leerse. -->
        <div style="font-family: 'Inter', sans-serif; width:100%; max-width:1400px; margin:0 auto; padding:8px; background:white; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact;${saltoDePagina ? ' page-break-before: always;' : ''}">
            <!-- HEADER -->
            <div style="text-align:center; border-bottom:2px solid #6EC1E4; padding-bottom:3px; margin-bottom:5px;">
                <h1 style="color:#000; margin:0; font-size:16px;">Instituto de Investigaciones Psicológicas</h1>
                <h2 style="color:#000; margin:1px 0; font-size:13px; font-weight:600;">Comprobante de Revisión y Entrega de Material</h2>
                <h3 style="color:#000; margin:0; font-size:11px; font-weight:500;">Prueba de Aptitud Académica</h3>
            </div>

            <!-- INFORMACIÓN GENERAL -->
            <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:3px; background:#f9fafb; padding:4px; border-radius:4px; margin-bottom:5px;">
                <div style="font-size:10px;"><strong>N° Sede:</strong> ${escapar(s.codigo)}</div>
                <div style="font-size:10px;"><strong>Convocatoria:</strong> ${escapar(s.convocatoria)}</div>
                <div style="font-size:10px;"><strong>Fecha:</strong> ${escapar(s.fecha || '—')}</div>
                <div style="font-size:10px;"><strong>Turno:</strong> ${escapar(s.turno || '—')}</div>
                <div style="grid-column: span 4; font-size:10px;"><strong>Sede:</strong> ${escapar(s.nombre)}</div>
                <!-- No se imprime quién generó el acta: el documento se firma a
                     mano abajo, y esas firmas son las que dicen quién respondió
                     por la entrega. Un nombre impreso arriba señalaba a quien
                     apretó el botón, que no es lo mismo ni tiene por qué serlo. -->
                <div style="grid-column: span 4; font-size:10px;"><strong>Coordinador(a):</strong> ${escapar(s.coordinador || '—')}</div>
            </div>

            <!-- TABLA DE AULAS -->
            <h4 style="color:#000; border-bottom:1px solid #6EC1E4; padding-bottom:1px; margin:6px 0 3px 0; font-size:12px; font-weight:600;">Distribución por Aula</h4>
            <!-- table-layout:fixed con anchos declarados, y no automático.
                 Con la letra más grande, nueve columnas y códigos de marchamo
                 sin espacios, el navegador ensancharía la tabla más allá del
                 papel y la última columna quedaría cortada al imprimir. Con los
                 anchos fijos la tabla nunca se pasa: si un código no cabe, se
                 parte en dos líneas dentro de su celda.
                 Padding y letra achicados a propósito: hasta diez aulas (con
                 sub-línea de adecuación incluida) tienen que caber sin pasar
                 a una segunda hoja casi vacía. -->
            <table style="width:100%; table-layout:fixed; border-collapse:collapse; margin-bottom:5px; font-size:10px;">
                <colgroup>
                    <col style="width:7%"><col style="width:8%"><col style="width:6%">
                    <col style="width:11%"><col style="width:11%">
                    <col style="width:11%"><col style="width:11%">
                    <col style="width:17.5%"><col style="width:17.5%">
                </colgroup>
                <thead>
                    <tr style="background:#e8f0fe; color:#000; border-bottom:2px solid #0055a4;">
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Aula</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Folletos</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;"># Tula</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Desde</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Hasta</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Desde Coord</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Hasta Coord</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">MARCHAMO 1</th>
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">MARCHAMO 2</th>
                    </tr>
                </thead>
                <tbody>${filasDelActa(datos)}</tbody>
            </table>

            <!-- TOTALES -->
            <div style="display: flex; justify-content: space-around; align-items: center; background: #e8f0fe; padding: 5px 12px; border-radius: 4px; margin: 6px 0; border-left: 3px solid #0055a4;">
                <div style="text-align: center; flex: 1;"><strong>Total folletos:</strong> ${totalFolletos}</div>
                <div style="text-align: center; flex: 1;"><strong>Total tulas:</strong> ${datos.tulas.length}</div>
                <div style="text-align: center; flex: 1;"><strong>Total aulas:</strong> ${datos.aulas.length}</div>
            </div>

            <!-- TABLA DE MATERIALES -->
            <h4 style="color:#000; border-bottom:1px solid #6EC1E4; padding-bottom:1px; margin:6px 0 3px 0; font-size:12px; font-weight:600;">Materiales a Revisar</h4>
            <!-- Ancho completo y no 80%: la lista pasó a dos columnas y con la
                 letra más grande necesita el ancho del papel para que los
                 nombres de material no se partan. -->
            <table style="width:100%; border-collapse:collapse; margin-bottom:5px; font-size:10px;">
                <thead>
                    <tr style="background:#e8f0fe; color:#000; border-bottom:2px solid #0055a4;">
                        <th style="padding:4px 3px; text-align:center; color:#000; font-weight:700;">Materiales</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:5px;">${listaDeMateriales(marcados)}</td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLA DE MATERIALES DE ADECUACIÓN -->
            <!-- Solo si la sede tiene el interruptor prendido y trae aulas de
                 adecuación con datos — ver tablaMaterialesAdecuacion(). -->
            ${datos.sede.tieneMaterialesAdecuacion && (datos.sede.materialesAdecuacion || []).length
                ? tablaMaterialesAdecuacion(datos)
                : ''}

            <!-- OBSERVACIONES REGISTRADAS DURANTE EL EMBALAJE -->
            <!-- box-sizing:border-box y word-break en el texto: sin esto, una
                 observación larga sin espacios se sale del ancho de la hoja
                 en vez de partirse en varias líneas. -->
            ${datos.sede.observaciones && datos.sede.observaciones.length ? `
                <div style="background:#f3f4f6; padding:5px; border-left:3px solid #6b7280; margin-bottom:5px; width:100%; box-sizing:border-box; page-break-inside:avoid;">
                    <p style="margin:1px 0; font-size:10px;"><strong>Se adjunta que:</strong></p>
                    ${datos.sede.observaciones.map(o => `
                        <p style="margin:2px 0; font-size:9px; word-break:break-word; overflow-wrap:break-word;">
                            ${escapar(o.texto)}
                        </p>
                    `).join('')}
                </div>
            ` : ''}

            <!-- OBSERVACIONES -->
            <div style="background:#f3f4f6; padding:5px; border-left:3px solid #6b7280; margin-bottom:6px; width:100%; box-sizing:border-box;">
                <p style="margin:1px 0; font-size:10px;"><strong>Observaciones:</strong></p>
                <p style="margin:1px 0; border-bottom:2px solid #000; padding:1px; font-size:9px; width:100%;">_________________________________________________________</p>
                <p style="margin:1px 0; border-bottom:2px solid #000; padding:1px; font-size:9px; width:100%;">_________________________________________________________</p>
            </div>

            <!-- FIRMAS REVISIÓN -->
            <h4 style="color:#000; margin:8px 0 5px 0; font-size:12px; font-weight:600; text-align:center;">Firmas de Revisión</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:10px; page-break-inside:avoid;">
                <div style="text-align:center;">
                    <p style="margin:0; font-size:11px;">_________________________</p>
                    <p style="margin:3px 0 0 0; font-size:10px;"><strong>Coordinador(a)</strong></p>
                </div>
                <div style="text-align:center;">
                    <p style="margin:0; font-size:11px;">_________________________</p>
                    <p style="margin:3px 0 0 0; font-size:10px;"><strong>Funcionario(a) PAA</strong></p>
                </div>
            </div>

            <!-- FIRMAS ENTREGA -->
            <h4 style="color:#000; margin:8px 0 5px 0; font-size:12px; font-weight:600; text-align:center;">Firmas de Entrega</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:10px; page-break-inside:avoid;">
                <div style="text-align:center;">
                    <p style="margin:0; font-size:11px;">_________________________</p>
                    <p style="margin:3px 0 0 0; font-size:10px;"><strong>Coordinador(a)</strong></p>
                </div>
                <div style="text-align:center;">
                    <p style="margin:0; font-size:11px;">_________________________</p>
                    <p style="margin:3px 0 0 0; font-size:10px;"><strong>Funcionario(a) PAA</strong></p>
                </div>
            </div>

            <!-- PIE DE PÁGINA -->
            <div style="text-align:center; margin-top:5px; border-top:1px solid #6EC1E4; padding-top:2px; font-size:8px; color:#000;">
                Documento generado el ${new Date().toLocaleDateString('es-CR')}${folio ? ' · Folio: ' + escapar(folio) : ''}
            </div>
        </div>
    `;
}

/**
 * La tabla de materiales de adecuación, para meter DENTRO del mismo
 * comprobante (ver construirComprobante) — no lleva encabezado, información
 * general ni firmas propias: son las mismas de arriba, una sola vez.
 *
 * Sin salto de página forzado: si alcanza el espacio en la primera hoja,
 * queda ahí mismo debajo de "Materiales a Revisar"; si no alcanza, el propio
 * navegador la corta a la página siguiente sola, como cualquier otra tabla.
 */
function tablaMaterialesAdecuacion(datos) {
    const filas = datos.sede.materialesAdecuacion || [];

    return `
        <h4 style="color:#000; border-bottom:1px solid #6EC1E4; padding-bottom:1px; margin:6px 0 3px 0; font-size:12px; font-weight:600;">Materiales de Adecuación por Aula</h4>
        <table style="width:100%; table-layout:fixed; border-collapse:collapse; margin-bottom:5px; font-size:9.5px;">
            <thead>
                <tr style="background:#e8f0fe; color:#000; border-bottom:2px solid #0055a4;">
                    <th style="padding:4px 2px; text-align:center; color:#000; font-weight:700; width:6%;">Aula</th>
                    ${CAMPOS_ADECUACION.map(([, etiqueta]) =>
                        `<th style="padding:4px 2px; text-align:center; color:#000; font-weight:700;">${etiqueta}</th>`
                    ).join('')}
                </tr>
            </thead>
            <tbody>
                ${filas.map(f => `
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:3px 2px; text-align:center; word-break:break-word;"><strong>${escapar(f.numero)}</strong></td>
                        ${CAMPOS_ADECUACION.map(([campo]) =>
                            `<td style="padding:3px 2px; text-align:center; word-break:break-word;">${escapar(f[campo] || '—')}</td>`
                        ).join('')}
                    </tr>
                `).join('')}
            </tbody>
        </table>`;
}

/**
 * Una fila por aula.
 *
 * Las columnas de coordinador repiten el rango de la sede en todas las filas,
 * igual que el acta anterior. Ese rango es uno solo para la sede entera; se
 * mantiene por fila porque así está impreso el formato que se firma.
 */
function filasDelActa(datos) {
    return datos.aulas.map(a => {
        const t = datos.tulas.find(x => x.aulas.some(y => y.id === a.id)) || null;
        return `
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">${escapar(a.numero)}${a.esAdecuacion ? ' <span style="color:#000">(adec.)</span>' : ''}</td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;"><strong>${a.asignados}</strong></td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">${t ? t.numero : '—'}</td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">
                    ${escapar(a.desde || '—')}
                    ${a.adecDesde ? `<br><span style="font-size:8px;color:#000;">adec: ${escapar(a.adecDesde)}</span>` : ''}
                    ${a.folletosSueltos ? `<br><span style="font-size:8px;color:#000;">+ ${escapar(a.folletosSueltos.replace(/\n/g, ', '))}</span>` : ''}
                </td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">
                    ${escapar(a.hasta || '—')}
                    ${a.adecDesde ? `<br><span style="font-size:8px;color:#000;">${escapar(a.adecHasta || '—')}</span>` : ''}
                </td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">
                    ${datos.sede.coordSinFolletos ? 'no tiene' : escapar(datos.sede.coordDesde || '—')}
                    ${datos.sede.coordAdecDesde ? `<br><span style="font-size:8px;color:#000;">adec: ${escapar(datos.sede.coordAdecDesde)}</span>` : ''}
                </td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">
                    ${datos.sede.coordSinFolletos ? '' : escapar(datos.sede.coordHasta || '—')}
                    ${datos.sede.coordAdecDesde ? `<br><span style="font-size:8px;color:#000;">${escapar(datos.sede.coordAdecHasta || '—')}</span>` : ''}
                </td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">${escapar((t && t.marchamo1) || '—')}</td>
                <td style="padding:3px 3px; text-align:center; font-size:10px; word-break:break-word;">${escapar((t && t.marchamo2) || '—')}</td>
            </tr>`;
    }).join('');
}

/** Lista de chequeo, con lo que se marcó en el diálogo. */
function listaDeMateriales(marcados) {
    if (!materiales.length) {
        return '<div style="padding:4px 0; color:#000;">No hay materiales cargados en el catálogo.</div>';
    }

    // Dos columnas. Al subir la letra de 8px a 11px esta lista creció casi un
    // 40% de alto, y es la sección más larga del acta: sola bastaba para
    // empujar el comprobante a una segunda página casi vacía. En dos columnas
    // ocupa la mitad y el acta vuelve a caber en una hoja.
    //
    // break-inside:avoid evita que un material quede partido entre el pie de
    // una columna y la cabeza de la otra.
    return `<div style="columns:2; column-gap:28px;">` + materiales.map((m, i) => `
        <div style="border-bottom:1px dotted #ccc; padding:3px 0; font-size:10px; display: flex; justify-content: space-between; align-items: center; width: 100%; break-inside: avoid;">
            <span style="flex: 1;">${escapar(m.nombre)}${m.unidad ? ' <span style="color:#000">(' + escapar(m.unidad) + ')</span>' : ''}</span>
            <span style="margin-left: 12px; flex-shrink: 0;">${marcados.includes(i) ? '☑' : '⬜'}</span>
        </div>
    `).join('') + `</div>`;
}

// ===================== UTILIDADES =====================

async function pedir(url) {
    const r = await fetch(url + (url.includes('?') ? '&' : '?') + '_ts=' + Date.now(),
                          { cache: 'no-store' });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo consultar');
    return d;
}

async function enviar(cuerpo) {
    const r = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...cuerpo, csrf: CSRF })
    });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo completar la operación');
    return d;
}

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.textContent = mensaje;
    aviso.className = 'aviso ' + tipo;
    aviso.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (tipo === 'exito') setTimeout(() => { aviso.className = 'aviso'; }, 7000);
}

function escapar(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
</script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
