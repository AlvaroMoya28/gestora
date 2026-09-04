<?php
/**
 * Módulo Carátulas.
 *
 * Genera la hoja que se pega en cada caja o tula. La persona escribe el número
 * de sede y el sistema trae nombre, aulas, folletos, marchamos y coordinador
 * desde la misma base que alimenta Ingreso de Datos y Revisión.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo('caratulas', '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Carátulas · UCR</title>
    <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #0055a4;
            --primary-dark: #003b73;
            --primary-light: #2b7fc2;
            --secondary: #6ec1e4;
            --success: #10b981;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
            --radius-lg: 1rem;
        }

        body {
            min-height: 100vh;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
            color: var(--gray-800);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
        }

        .app { max-width: 1180px; margin: 0 auto; }

        .barra {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .barra a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .hero { text-align: center; margin-bottom: 1.5rem; }

        .hero .logo {
            display: inline-flex;
            margin-bottom: .6rem;
            padding: 6px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .hero .logo img { display: block; width: auto; height: 28px; }

        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p { color: var(--gray-600); }

        .tarjeta {
            margin-bottom: 1.25rem;
            padding: 1.35rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            background: #fff;
            box-shadow: var(--shadow);
        }

        .tarjeta h2 {
            display: flex;
            align-items: center;
            gap: .55rem;
            margin-bottom: 1rem;
            color: var(--gray-700);
            font-size: 1.05rem;
            font-weight: 700;
        }

        .busqueda {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) auto auto;
            gap: .75rem;
            align-items: end;
        }

        .dropdown {
            display: none;
            margin-top: .6rem;
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
        }

        .dropdown.visible { display: block; }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem .75rem;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            font-size: .875rem;
        }

        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:hover { background: var(--gray-50); }
        .dropdown-item.seleccionada { background: #eaf3fb; }
        .dropdown-item input { width: auto; }
        .dropdown-item .codigo { font-weight: 800; color: var(--primary); min-width: 2.6rem; }
        .dropdown-item .nombre { color: var(--gray-700); flex: 1; }
        .dropdown-item .coordinador {
            color: var(--gray-500); font-size: .78rem; font-style: italic;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 11rem;
        }
        .dropdown-vacio { padding: .9rem .75rem; color: var(--gray-500); font-size: .85rem; }
        .dropdown-aviso {
            display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap;
            padding: .6rem .75rem; background: #eef6ff; color: var(--primary-dark);
            font-size: .8rem; font-weight: 600; border-bottom: 1px solid var(--gray-200);
        }
        .dropdown-aviso-btn {
            font-family: inherit; font-size: .78rem; font-weight: 700; cursor: pointer;
            background: var(--primary); color: #fff; border: none; border-radius: 999px;
            padding: .3rem .75rem; white-space: nowrap;
        }
        .dropdown-aviso-btn:hover { background: var(--primary-dark); }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .75rem;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .5rem .35rem .7rem;
            border-radius: 999px;
            background: var(--gray-100);
            color: var(--gray-700);
            font-size: .8rem;
            font-weight: 700;
        }

        .chip button {
            padding: 0;
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 50%;
            background: var(--gray-300);
            color: #fff;
            font-size: .7rem;
        }

        .chip button:hover { background: var(--gray-500); }

        .acciones-busqueda {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        label {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            color: var(--gray-600);
            font-size: .8rem;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: .7rem .8rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font: inherit;
            font-size: .95rem;
        }

        input:focus {
            outline: 2px solid var(--secondary);
            outline-offset: -1px;
        }

        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .72rem 1.05rem;
            border: 0;
            border-radius: var(--radius);
            cursor: pointer;
            font: inherit;
            font-size: .875rem;
            font-weight: 700;
            white-space: nowrap;
        }

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover:not(:disabled) { background: var(--primary-dark); }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover:not(:disabled) { background: var(--gray-200); }

        .aviso {
            display: none;
            margin-bottom: 1rem;
            padding: .85rem 1rem;
            border-radius: var(--radius);
            font-size: .9rem;
        }

        .aviso.info { display: block; background: #dbeafe; color: #1e40af; }
        .aviso.error { display: block; background: #fee2e2; color: #991b1b; }
        .aviso.exito { display: block; background: #d1fae5; color: #065f46; }

        .resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .dato {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: .8rem .9rem;
            background: var(--gray-50);
        }

        .dato small {
            display: block;
            margin-bottom: .2rem;
            color: var(--gray-500);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .dato strong {
            color: var(--gray-800);
            font-size: .96rem;
        }

        .preview-wrap {
            display: none;
        }

        .preview-wrap.visible {
            display: block;
        }

        .acciones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .nota {
            color: var(--gray-500);
            font-size: .82rem;
        }

        .preview-scroll {
            overflow: auto;
            padding: 1rem;
            border: 1px dashed var(--gray-300);
            border-radius: var(--radius-lg);
            background: var(--gray-50);
        }

        .caratula {
            position: relative;
            width: 8.5in;
            min-height: 11in;
            margin: 0 auto 1rem;
            padding: .52in .72in .6in;
            border: 2px solid #ff0000;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            page-break-after: always;
        }

        .caratula:last-child { margin-bottom: 0; page-break-after: auto; }

        .logos {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            align-items: center;
            gap: .45in;
            margin-bottom: .65in;
            color: #555;
        }

        .logo-ucr {
            display: flex;
            align-items: center;
            min-height: .9in;
        }

        .logo-ucr img {
            display: block;
            width: 100%;
            height: auto;
            max-height: .95in;
            object-fit: contain;
        }

        .logo-iip {
            display: flex;
            align-items: center;
            gap: .1in;
            font-size: 13px;
            line-height: 1.05;
            color: #555;
        }

        .logo-iip strong {
            font-size: 23px;
            color: #666;
            font-weight: 500;
            line-height: 1;
        }

        .logo-iip .marca {
            width: .45in;
            height: 2px;
            margin-top: 5px;
            background: #6ec1e4;
        }

        .sede-numero {
            display: flex;
            align-items: baseline;
            gap: .16in;
            margin-bottom: .34in;
        }

        .sede-numero span {
            font-size: 38px;
            letter-spacing: .01em;
        }

        .sede-numero strong {
            font-size: 74px;
            font-weight: 400;
            line-height: .9;
        }

        .bloque-linea { margin-bottom: .17in; }

        .rotulo {
            margin-bottom: .08in;
            font-size: 20px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .valor-sede {
            display: block;
            width: 100%;
            padding-bottom: .03in;
            border-bottom: 1.5px solid #000;
            font-size: 23px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .linea-form {
            display: flex;
            align-items: baseline;
            gap: .08in;
            margin: .16in 0;
            font-size: 16px;
            line-height: 1.35;
        }

        .linea-form .label { white-space: nowrap; }

        .llenado {
            display: inline-block;
            min-width: 1.2in;
            padding: 0 .04in .02in;
            border-bottom: 1px solid #000;
            font-weight: 700;
            text-align: center;
            line-height: 1.1;
        }

        .llenado.largo { min-width: 1.8in; }

        .cuadro {
            margin: .18in 0 .72in;
            border: 1.5px solid #2b7fc2;
        }

        .cuadro-titulo {
            padding: .08in;
            border-bottom: 1.5px solid #2b7fc2;
            font-size: 13px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        .cuadro-cuerpo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .13in .45in;
            padding: .14in .22in;
            font-size: 13px;
        }

        .cuadro-cuerpo .linea-form {
            margin: 0;
            font-size: 13px;
        }

        .firma-box {
            margin-top: .2in;
            border: 1.5px solid #2b7fc2;
            min-height: 1.9in;
        }

        .firma-titulo {
            padding: .08in;
            font-size: 18px;
            font-weight: 800;
            text-align: center;
        }

        .firma-cuerpo {
            padding: .12in 0 0;
            font-size: 13px;
        }

        .firma-cuerpo .linea-form {
            margin: .18in 0;
            font-size: 14px;
        }

        .firma-cuerpo .llenado {
            min-width: 4.2in;
            text-align: left;
            font-weight: 700;
        }

        .firma-cuerpo .llenado.vacio {
            color: transparent;
        }

        @media (max-width: 920px) {
            body { padding: 1rem; }
            .busqueda { grid-template-columns: 1fr; }
            .caratula {
                transform: scale(.72);
                transform-origin: top left;
                margin-right: -2.38in;
                margin-bottom: -2.98in;
            }
        }

        @media print {
            @page { size: letter; margin: 0; }
            body { padding: 0; background: #fff; }
            body > *:not(.solo-impresion) { display: none !important; }
            .solo-impresion { display: block !important; }
            .caratula {
                width: 8.5in;
                min-height: 11in;
                margin: 0;
                box-shadow: none;
                transform: none;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* AJUSTE_CARATULAS_HOJA_UNICA */
        .caratula {
            height: 11in;
            min-height: 11in;
            max-height: 11in;
            overflow: hidden;
            break-inside: avoid;
            break-after: page;
            page-break-inside: avoid;
            page-break-after: always;
        }

        .logos {
            gap: .25in;
            margin-bottom: .38in;
        }

        .logo-paa-imagen {
            display: flex;
            min-height: .66in;
            padding: .08in .12in;
            align-items: center;
            justify-content: center;
            border-radius: .08in;
            background: #0055a4;
        }

        .logo-paa-imagen img {
            display: block;
            width: 100%;
            height: auto;
            max-height: .58in;
            object-fit: contain;
        }

        .cuadro {
            margin-bottom: .45in;
        }

        .firma-box {
            min-height: 1.70in;
        }

        .pdf-fondo {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .55);
        }

        .pdf-fondo.visible { display: flex; }

        .pdf-caja {
            width: 100%;
            max-width: 22rem;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            background: #fff;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .pdf-caja i {
            font-size: 1.6rem;
            color: var(--primary);
            margin-bottom: .6rem;
        }

        .pdf-caja h3 {
            margin-bottom: .3rem;
            color: var(--gray-800);
            font-size: 1.05rem;
        }

        .pdf-caja p {
            margin-bottom: 1rem;
            color: var(--gray-500);
            font-size: .85rem;
        }

        .pdf-barra {
            height: .55rem;
            border-radius: 999px;
            background: var(--gray-200);
            overflow: hidden;
        }

        .pdf-barra-relleno {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transition: width .15s ease;
        }

        @media print {
            html, body {
                width: 8.5in;
                margin: 0;
                padding: 0;
            }

            .solo-impresion {
                width: 8.5in;
                margin: 0;
                padding: 0;
            }

            .caratula {
                width: 8.5in;
                height: 11in;
                min-height: 11in;
                max-height: 11in;
                margin: 0;
                overflow: hidden;
                break-inside: avoid;
                break-after: page;
                page-break-inside: avoid;
                page-break-after: always;
            }

            .caratula:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="barra">
        <span><i class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div class="logo">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA">
        </div>
        <h1>Carátulas</h1>
        <p>Generación de carátulas por sede y tula</p>
    </div>

    <div id="aviso" class="aviso info">
        Buscá una sede, seleccioná varias o usá "Todas las sedes" para imprimirlas de una vez.
    </div>

    <div class="tarjeta">
        <h2><i class="fa-solid fa-magnifying-glass"></i> Buscar y seleccionar sedes</h2>
        <form class="busqueda" id="formBuscar">
            <label>Número o nombre de sede, o nombre del coordinador
                <input type="text" id="buscador" autocomplete="off" placeholder="Ej. 219, Liceo... o el nombre de quien coordina">
            </label>
            <button class="btn-secundario" type="button" id="btnTodas">
                <i class="fa-solid fa-layer-group"></i> Todas las sedes
            </button>
            <button class="btn-secundario" type="button" id="btnLimpiar">
                <i class="fa-solid fa-eraser"></i> Limpiar
            </button>
        </form>

        <div class="dropdown" id="dropdown"></div>
        <div class="chips" id="chips"></div>

        <div class="acciones-busqueda">
            <p class="nota" id="notaSeleccion">Ninguna sede seleccionada.</p>
            <button class="btn-primario" type="submit" form="formBuscar" id="btnGenerar" disabled>
                <i class="fa-solid fa-rotate"></i> Generar carátulas
            </button>
        </div>
    </div>

    <div class="preview-wrap" id="resultado">
        <div class="tarjeta">
            <h2><i class="fa-regular fa-file-lines"></i> Datos encontrados</h2>
            <div class="resumen" id="resumen"></div>
            <div class="acciones">
                <p class="nota" id="notaImpresion"></p>
                <div style="display:flex; gap:.6rem; flex-wrap:wrap;">
                    <button class="btn-secundario" type="button" id="btnDescargarPdf">
                        <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                    </button>
                    <button class="btn-primario" type="button" id="btnImprimir">
                        <i class="fa-solid fa-print"></i> Imprimir carátulas
                    </button>
                </div>
            </div>
            <div class="preview-scroll" id="preview"></div>
        </div>
    </div>
</div>

<div class="solo-impresion" id="soloImpresion" style="display:none;"></div>

<div class="pdf-fondo" id="pdfFondo">
    <div class="pdf-caja">
        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
        <h3>Generando el PDF</h3>
        <p id="pdfEstado">Preparando…</p>
        <div class="pdf-barra"><div class="pdf-barra-relleno" id="pdfRelleno"></div></div>
    </div>
</div>

<script>
const API = '../api/caratulas.php';

const UMBRAL_CHIPS = 20;    // más de esto, se resume en un solo chip (820 nodos no se pintan)
const UMBRAL_PREVIEW = 15;  // más de esto, no se arma la vista previa en pantalla

let datosActuales = null;   // array de {sede, aulas, tulas}, una por sede generada
let listaSedes = [];        // catálogo {id, codigo, nombre} para buscar/seleccionar
let seleccion = new Map();  // codigo -> {codigo, nombre}
let previewHtmlCache = null; // evita armar el HTML de las carátulas dos veces (vista previa + impresión)

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formBuscar').addEventListener('submit', generarSeleccionadas);
    document.getElementById('btnLimpiar').addEventListener('click', limpiar);
    document.getElementById('btnTodas').addEventListener('click', seleccionarTodas);
    document.getElementById('btnImprimir').addEventListener('click', imprimirCaratulas);
    document.getElementById('btnDescargarPdf').addEventListener('click', descargarPdf);

    const buscador = document.getElementById('buscador');
    buscador.addEventListener('input', () => pintarDropdown(buscador.value.trim()));
    buscador.addEventListener('focus', () => pintarDropdown(buscador.value.trim()));

    document.addEventListener('click', e => {
        const dropdown = document.getElementById('dropdown');
        if (!dropdown.contains(e.target) && e.target !== buscador) {
            dropdown.classList.remove('visible');
        }
    });

    cargarListado().then(() => {
        const params = new URLSearchParams(window.location.search);
        const sede = params.get('sede');
        if (sede) {
            const s = listaSedes.find(x => String(x.codigo) === sede);
            if (s) seleccionar(s);
            generarSeleccionadas(new Event('submit'));
        }
    });
});

async function cargarListado() {
    try {
        const d = await pedir(`${API}?accion=listar`);
        listaSedes = d.sedes || [];
    } catch (e) {
        mostrar('No se pudo cargar el listado de sedes: ' + e.message, 'error');
    }
}

function pintarDropdown(texto) {
    const dropdown = document.getElementById('dropdown');
    const t = texto.toLowerCase();

    const coincidencias = t
        ? listaSedes.filter(s =>
            String(s.codigo).toLowerCase().includes(t) ||
            String(s.nombre).toLowerCase().includes(t) ||
            String(s.coordinador || '').toLowerCase().includes(t)
        )
        : listaSedes;

    if (!coincidencias.length) {
        dropdown.innerHTML = '<div class="dropdown-vacio">No hay sedes que coincidan.</div>';
        dropdown.classList.add('visible');
        return;
    }

    // Buscando por coordinador suele traer varias sedes de una: se avisa
    // arriba cuántas son y se ofrece marcarlas todas juntas, en vez de
    // obligar a hacer clic sede por sede.
    const porCoordinador = t && coincidencias.length > 1 &&
        coincidencias.every(s => String(s.coordinador || '').toLowerCase().includes(t));
    const aviso = porCoordinador
        ? `<div class="dropdown-aviso">
               ${coincidencias.length} sedes a cargo de esta persona coordinadora.
               <button type="button" class="dropdown-aviso-btn" id="btnSeleccionarCoincidencias">Marcar las ${coincidencias.length}</button>
           </div>`
        : '';

    dropdown.innerHTML = aviso + coincidencias.slice(0, 60).map(s => {
        const marcada = seleccion.has(String(s.codigo));
        return `
            <div class="dropdown-item ${marcada ? 'seleccionada' : ''}" data-codigo="${escapar(s.codigo)}">
                <input type="checkbox" ${marcada ? 'checked' : ''} tabindex="-1">
                <span class="codigo">${escapar(s.codigo)}</span>
                <span class="nombre">${escapar(s.nombre)}</span>
                ${s.coordinador ? `<span class="coordinador">${escapar(s.coordinador)}</span>` : ''}
            </div>
        `;
    }).join('');

    dropdown.querySelectorAll('.dropdown-item').forEach(fila => {
        fila.addEventListener('click', () => {
            const codigo = fila.dataset.codigo;
            const s = listaSedes.find(x => String(x.codigo) === codigo);
            if (!s) return;
            if (seleccion.has(codigo)) quitarSeleccion(codigo); else seleccionar(s);
            pintarDropdown(document.getElementById('buscador').value.trim());
        });
    });

    const btnCoincidencias = document.getElementById('btnSeleccionarCoincidencias');
    if (btnCoincidencias) {
        btnCoincidencias.addEventListener('click', (e) => {
            e.stopPropagation();
            coincidencias.forEach(s => seleccion.set(String(s.codigo), s));
            pintarChips();
            pintarDropdown(document.getElementById('buscador').value.trim());
        });
    }

    dropdown.classList.add('visible');
}

function seleccionar(s) {
    seleccion.set(String(s.codigo), s);
    pintarChips();
}

function quitarSeleccion(codigo) {
    seleccion.delete(String(codigo));
    pintarChips();
}

function seleccionarTodas() {
    listaSedes.forEach(s => seleccion.set(String(s.codigo), s));
    pintarChips();
    document.getElementById('dropdown').classList.remove('visible');
    generarSeleccionadas(new Event('submit'));
}

function pintarChips() {
    const chips = document.getElementById('chips');
    const nota = document.getElementById('notaSeleccion');
    const btnGenerar = document.getElementById('btnGenerar');

    if (seleccion.size > UMBRAL_CHIPS) {
        // Pintar un nodo por sede con cientos seleccionadas traba el navegador
        // sin aportar nada: nadie lee 820 chips, solo se resume el total.
        chips.innerHTML = `
            <span class="chip">
                ${seleccion.size} sedes seleccionadas
                <button type="button" id="chipQuitarTodas" aria-label="Quitar todas"><i class="fa-solid fa-times"></i></button>
            </span>
        `;
        document.getElementById('chipQuitarTodas').addEventListener('click', () => {
            seleccion.clear();
            pintarChips();
            pintarDropdown(document.getElementById('buscador').value.trim());
        });
    } else {
        chips.innerHTML = [...seleccion.values()].map(s => `
            <span class="chip" data-codigo="${escapar(s.codigo)}">
                ${escapar(s.codigo)} · ${escapar(s.nombre)}
                <button type="button" aria-label="Quitar"><i class="fa-solid fa-times"></i></button>
            </span>
        `).join('');

        chips.querySelectorAll('.chip button').forEach(btn => {
            btn.addEventListener('click', () => {
                quitarSeleccion(btn.closest('.chip').dataset.codigo);
                pintarDropdown(document.getElementById('buscador').value.trim());
            });
        });
    }

    nota.textContent = seleccion.size
        ? `${seleccion.size} sede(s) seleccionada(s).`
        : 'Ninguna sede seleccionada.';
    btnGenerar.disabled = seleccion.size === 0;
}

async function generarSeleccionadas(evento) {
    evento.preventDefault();

    if (!seleccion.size) {
        mostrar('Seleccioná al menos una sede de la lista.', 'error');
        return;
    }

    const btn = document.getElementById('btnGenerar');
    btn.disabled = true;

    // Se trae sede por sede con el mismo endpoint de siempre (accion=sede),
    // que es simple y probado, en vez de un solo pedido gigante con todas
    // juntas: con cientos de sedes eso es lo que venía fallando.
    const codigos = [...seleccion.keys()];
    const resultados = [];
    let fallidas = 0;

    for (let i = 0; i < codigos.length; i++) {
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Generando ${i + 1}/${codigos.length}`;
        try {
            const d = await pedir(`${API}?accion=sede&sede=${encodeURIComponent(codigos[i])}`);
            resultados.push({ sede: d.sede, aulas: d.aulas, tulas: d.tulas });
        } catch (e) {
            fallidas++;
        }
    }

    btn.disabled = seleccion.size === 0;
    btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Generar carátulas';

    if (!resultados.length) {
        datosActuales = null;
        document.getElementById('resultado').classList.remove('visible');
        mostrar('No se pudo generar ninguna carátula.', 'error');
        return;
    }

    datosActuales = resultados;
    pintarResultado();
    document.getElementById('resultado').scrollIntoView({ behavior: 'smooth', block: 'start' });
    mostrar(
        fallidas
            ? `${resultados.length} sede(s) cargada(s); ${fallidas} no se pudieron cargar.`
            : `${resultados.length} sede(s) cargada(s) correctamente.`,
        fallidas ? 'error' : 'exito'
    );

    if (resultados.length === 1) {
        history.replaceState(null, '', `?sede=${encodeURIComponent(resultados[0].sede.codigo)}`);
    } else {
        history.replaceState(null, '', location.pathname);
    }
}

function pintarResultado() {
    const lista = datosActuales;
    const aulas = lista.reduce((n, item) => n + item.aulas.length, 0);
    const tulas = lista.reduce((n, item) => n + item.tulas.length, 0);
    const caratulas = lista.reduce((n, item) => n + gruposDeCaratula(item).length, 0);

    const datos = lista.length === 1
        ? [
            ['Sede', lista[0].sede.codigo],
            ['Nombre', lista[0].sede.nombre],
            ['Coordinador(a)', lista[0].sede.coordinador || 'Sin dato'],
            ['Aulas', aulas],
            ['Tulas', tulas],
            ['Carátulas', caratulas],
        ]
        : [
            ['Sedes seleccionadas', lista.length],
            ['Aulas', aulas],
            ['Tulas', tulas],
            ['Carátulas', caratulas],
        ];

    document.getElementById('resumen').innerHTML = datos.map(([rotulo, valor]) => `
        <div class="dato">
            <small>${escapar(rotulo)}</small>
            <strong>${escapar(valor)}</strong>
        </div>
    `).join('');

    document.getElementById('notaImpresion').textContent = lista.length === 1
        ? (caratulas === 1
            ? 'Se va a imprimir 1 carátula para esta sede.'
            : `Se van a imprimir ${caratulas} carátulas para esta sede, una por cada tula.`)
        : `Se van a imprimir ${caratulas} carátulas de ${lista.length} sedes.`;

    const preview = document.getElementById('preview');
    if (lista.length <= UMBRAL_PREVIEW) {
        previewHtmlCache = previewHtml(lista);
        preview.innerHTML = previewHtmlCache;
    } else {
        // Con cientos de carátulas, armar y pintar la vista previa en pantalla
        // congela el navegador sin que nadie la vaya a revisar página por
        // página: se arma una sola vez, directo al imprimir.
        previewHtmlCache = null;
        preview.innerHTML = `<div class="dropdown-vacio">Vista previa omitida: son ${caratulas} carátulas. Dale directo a "Imprimir carátulas".</div>`;
    }
    document.getElementById('resultado').classList.add('visible');
}

function limpiar() {
    datosActuales = null;
    seleccion.clear();
    pintarChips();
    document.getElementById('buscador').value = '';
    document.getElementById('dropdown').classList.remove('visible');
    document.getElementById('resultado').classList.remove('visible');
    mostrar('Buscá una sede, seleccioná varias o usá "Todas las sedes".', 'info');
    history.replaceState(null, '', location.pathname);
    document.getElementById('buscador').focus();
}

function imprimirCaratulas() {
    if (!datosActuales || !datosActuales.length) {
        mostrar('Primero generá al menos una carátula.', 'error');
        return;
    }

    if (datosActuales.length > 1 && !confirm(`Se van a imprimir carátulas de ${datosActuales.length} sedes. ¿Continuar?`)) {
        return;
    }

    const btn = document.getElementById('btnImprimir');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparando';

    // Se arma en el próximo frame para que el navegador pinte el botón
    // deshabilitado antes de trabar el hilo con cientos de carátulas.
    requestAnimationFrame(() => {
        const html = previewHtmlCache !== null ? previewHtmlCache : previewHtml(datosActuales);
        document.getElementById('soloImpresion').innerHTML = html;

        setTimeout(() => {
            window.print();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-print"></i> Imprimir carátulas';
        }, 150);
    });
}

let libreriasPdfPromise = null;

function cargarScriptDinamico(src) {
    return new Promise((resolve, reject) => {
        const existente = document.querySelector(`script[data-src="${src}"]`);
        if (existente) {
            if (existente.dataset.loaded === 'true') return resolve();
            existente.addEventListener('load', () => resolve(), { once: true });
            existente.addEventListener('error', () => reject(new Error(`No se pudo cargar ${src}`)), { once: true });
            return;
        }

        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.dataset.src = src;
        s.onload = () => { s.dataset.loaded = 'true'; resolve(); };
        s.onerror = () => reject(new Error(`No se pudo cargar ${src}`));
        document.head.appendChild(s);
    });
}

async function cargarLibreriasPdf() {
    if (!libreriasPdfPromise) {
        libreriasPdfPromise = (async () => {
            await cargarScriptDinamico('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js');
            await cargarScriptDinamico('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
        })();
    }
    await libreriasPdfPromise;
    return window.jspdf?.jsPDF;
}

function mostrarModalPdf(texto) {
    document.getElementById('pdfEstado').textContent = texto;
    document.getElementById('pdfRelleno').style.width = '0%';
    document.getElementById('pdfFondo').classList.add('visible');
}

function actualizarModalPdf(hecho, total) {
    document.getElementById('pdfEstado').textContent = `Generando carátula ${hecho} de ${total}…`;
    document.getElementById('pdfRelleno').style.width = `${Math.round((hecho / total) * 100)}%`;
}

function ocultarModalPdf() {
    document.getElementById('pdfFondo').classList.remove('visible');
}

async function descargarPdf() {
    if (!datosActuales || !datosActuales.length) {
        mostrar('Primero generá al menos una carátula.', 'error');
        return;
    }

    const grupos = [];
    datosActuales.forEach(item => {
        gruposDeCaratula(item).forEach(g => grupos.push({ item, g }));
    });

    if (!grupos.length) return;

    if (grupos.length > 30 && !confirm(`Se va a armar un PDF de ${grupos.length} carátulas. Puede tardar unos minutos. ¿Continuar?`)) {
        return;
    }

    const btn = document.getElementById('btnDescargarPdf');
    btn.disabled = true;
    mostrarModalPdf('Preparando…');

    const contenedor = document.createElement('div');
    contenedor.style.cssText = `
        position: fixed; left: -100000px; top: 0;
        width: 8.5in; height: 11in;
        background: #fff; margin: 0; padding: 0; z-index: -1; overflow: hidden;
    `;
    document.body.appendChild(contenedor);

    try {
        const jsPDFCtor = await cargarLibreriasPdf();
        if (!jsPDFCtor || typeof window.html2canvas !== 'function') {
            throw new Error('No se pudieron cargar las librerías para armar el PDF.');
        }

        const doc = new jsPDFCtor({ orientation: 'p', unit: 'in', format: 'letter' });

        for (let i = 0; i < grupos.length; i++) {
            actualizarModalPdf(i + 1, grupos.length);

            contenedor.innerHTML = construirCaratula(grupos[i].item, grupos[i].g);
            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));

            const canvas = await window.html2canvas(contenedor, {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                logging: false,
            });

            const imgData = canvas.toDataURL('image/jpeg', 0.92);
            if (i > 0) doc.addPage('letter', 'p');
            doc.addImage(imgData, 'JPEG', 0, 0, 8.5, 11, undefined, 'FAST');
        }

        const nombre = datosActuales.length === 1
            ? `Caratula_Sede_${datosActuales[0].sede.codigo}.pdf`
            : `Caratulas_${datosActuales.length}_sedes.pdf`;
        doc.save(nombre);
        mostrar('PDF descargado correctamente.', 'exito');
    } catch (e) {
        mostrar('No se pudo generar el PDF: ' + e.message, 'error');
    } finally {
        document.body.removeChild(contenedor);
        ocultarModalPdf();
        btn.disabled = false;
    }
}

function previewHtml(lista) {
    return lista.map(item => gruposDeCaratula(item).map(g => construirCaratula(item, g)).join('')).join('');
}

function gruposDeCaratula(datos) {
    if (datos.tulas && datos.tulas.length) {
        return datos.tulas.map(t => ({
            numero: t.numero || '',
            marchamo1: t.marchamo1 || '',
            marchamo2: t.marchamo2 || '',
            aulas: t.aulas || [],
            llevaCoord: !!t.llevaCoord,
        }));
    }

    return [{
        numero: '',
        marchamo1: '',
        marchamo2: '',
        aulas: datos.aulas || [],
        llevaCoord: false,
    }];
}

function construirCaratula(datos, grupo) {
    const s = datos.sede;
    const aulas = normalizarAulas(grupo.aulas, datos.aulas);
    const filas = filasAulas(aulas) + (grupo.llevaCoord ? filaCoordinador(s) : '');

    return `
        <section class="caratula">
            <header class="logos">
                <div class="logo-ucr">
                    <img src="../assets/logo-ucr.png" alt="Universidad de Costa Rica">
                </div>
                <div class="logo-iip">
                    <strong>IIP</strong>
                    <span><span class="marca"></span>Instituto de<br>Investigaciones<br>Psicológicas</span>
                </div>
                <div class="logo-paa-imagen">
                    <img src="../assets/logo-paa-clasico.png"
                         alt="Programa Permanente de la Prueba de Aptitud Académica">
                </div>
            </header>

            <div class="sede-numero">
                <span>SEDE N°:</span>
                <strong>${escapar(s.codigo)}</strong>
            </div>

            <div class="bloque-linea">
                <div class="rotulo">Nombre de la sede:</div>
                <span class="valor-sede" style="font-size:${tamanoNombreSede(s.nombre)}">${escapar(s.nombre || '')}</span>
            </div>

            <div class="linea-form">
                <span class="label">CAJA / TULA N°:</span>
                <span class="llenado largo">${valorOLinea(grupo.numero)}</span>
            </div>

            ${filas}

            <div class="cuadro">
                <div class="cuadro-titulo">Apertura y cierre de tula o caja</div>
                <div class="cuadro-cuerpo">
                    <div class="linea-form">
                        <span class="label">Marchamo #1:</span>
                        <span class="llenado largo">${valorOLinea(grupo.marchamo1)}</span>
                    </div>
                    <div class="linea-form">
                        <span class="label">Hora apertura:</span>
                        <span class="llenado largo">&nbsp;</span>
                    </div>
                    <div class="linea-form">
                        <span class="label">Marchamo #2:</span>
                        <span class="llenado largo">${valorOLinea(grupo.marchamo2)}</span>
                    </div>
                    <div class="linea-form">
                        <span class="label">Hora cierre:</span>
                        <span class="llenado largo">&nbsp;</span>
                    </div>
                </div>
            </div>

            <div class="firma-box">
                <div class="firma-titulo">COORDINADOR(A)</div>
                <div class="firma-cuerpo">
                    <div class="linea-form">
                        <span class="label">NOMBRE COMPLETO:</span>
                        <span class="llenado">${valorOLinea(s.coordinador || '')}</span>
                    </div>
                    <div class="linea-form">
                        <span class="label">N° . CÉDULA:</span>
                        <span class="llenado vacio">&nbsp;</span>
                    </div>
                    <div class="linea-form">
                        <span class="label">FIRMA:</span>
                        <span class="llenado vacio">&nbsp;</span>
                    </div>
                </div>
            </div>
        </section>
    `;
}

function normalizarAulas(aulasGrupo, aulasCompletas) {
    const porId = new Map((aulasCompletas || []).map(a => [String(a.id), a]));

    const aulas = (aulasGrupo || []).map(a => {
        const completa = porId.get(String(a.id)) || {};
        return { ...a, ...completa };
    });

    return aulas.length ? aulas : (aulasCompletas || []);
}

function filasAulas(aulas) {
    // Sin relleno: una tula con una sola aula (con o sin folletos del
    // coordinador debajo) no necesita una segunda fila vacía — mejor quitar
    // el espacio en blanco que dejarlo sin usar.
    return aulas.map(a => filaAula(a)).join('');
}

function filaAula(aula) {
    // Los folletos sueltos (fuera del rango consecutivo, ver schema/046) van
    // en la caja igual que los demás: si no se avisan acá, la carátula dice
    // menos folletos de los que en realidad hay que meter.
    const sueltos = aula.folletosSueltos
        ? `<div class="linea-form" style="font-size:.8em;">
               <span class="label">+ SUELTOS:</span>
               <span class="llenado largo">${valorOLinea(aula.folletosSueltos.replace(/\n/g, ', '))}</span>
           </div>`
        : '';

    return `
        <div class="linea-form">
            <span class="label">AULA N°:</span>
            <span class="llenado">${valorOLinea(aula.numero || '')}</span>
            <span class="label">FOLLETOS DEL:</span>
            <span class="llenado largo">${valorOLinea(aula.desde || '')}</span>
            <span class="label">AL:</span>
            <span class="llenado largo">${valorOLinea(aula.hasta || '')}</span>
        </div>
        ${sueltos}
    `;
}

/**
 * Achica la letra del nombre de la sede cuando es largo, para que no se
 * vaya a una segunda línea: la hoja tiene alto fijo y todo lo de abajo
 * (aulas, marchamos, firma) se desacomoda si el nombre crece la caja.
 */
function tamanoNombreSede(nombre) {
    const largo = String(nombre ?? '').length;
    if (largo > 60) return '13px';
    if (largo > 48) return '16px';
    if (largo > 36) return '19px';
    return '23px';
}

/** La tula que lleva los folletos del coordinador los muestra debajo de sus aulas, con el mismo estilo. */
function filaCoordinador(sede) {
    if (sede.coordSinFolletos) {
        return `
            <div class="linea-form">
                <span class="label">FOLLETOS DEL COORDINADOR:</span>
                <span class="llenado largo">No tiene folletos asignados</span>
            </div>
        `;
    }

    return `
        <div class="linea-form">
            <span class="label">FOLLETOS DEL COORDINADOR:</span>
            <span class="llenado largo">${valorOLinea(sede.coordDesde)}</span>
            <span class="label">AL:</span>
            <span class="llenado largo">${valorOLinea(sede.coordHasta)}</span>
        </div>
    `;
}

function valorOLinea(valor) {
    const texto = String(valor ?? '').trim();
    return texto ? escapar(texto) : '&nbsp;';
}

async function pedir(url) {
    const r = await fetch(url + (url.includes('?') ? '&' : '?') + '_ts=' + Date.now(), {
        cache: 'no-store',
    });
    return leerRespuesta(r);
}

async function leerRespuesta(r) {
    const texto = await r.text();
    let d;
    try {
        d = JSON.parse(texto);
    } catch (e) {
        // El servidor no devolvió JSON: probablemente se cayó a medio camino
        // (tiempo agotado, error fatal de PHP) armando un lote muy grande.
        throw new Error(`El servidor no respondió correctamente (código ${r.status}). Probá con menos sedes a la vez.`);
    }
    if (!d.success) throw new Error(d.error || 'No se pudo consultar');
    return d;
}

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.textContent = mensaje;
    aviso.className = 'aviso ' + tipo;
    if (tipo === 'error') {
        aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (tipo === 'exito') {
        setTimeout(() => {
            if (aviso.className === 'aviso exito') {
                aviso.className = 'aviso info';
                aviso.textContent = 'Podés imprimir la carátula o buscar otra sede.';
            }
        }, 5000);
    }
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
