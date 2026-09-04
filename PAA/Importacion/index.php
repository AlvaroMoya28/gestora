<?php
/**
 * Módulo Importación de Datos.
 *
 * Carga anual desde el sistema de coordinaciones: se sube el JSON que ese
 * sistema exporta y de ahí salen las sedes, sus coordinadores, el padrón de
 * personas aplicadoras y quién quedó nombrado en cada puesto.
 *
 * Antes esto entraba por tres caminos distintos —un CSV de sedes, un .xls de
 * coordinadores y nada para el padrón— y cada año había que acordarse del
 * orden. Acá es un archivo y un botón.
 *
 * DOS SALVAGUARDAS QUE NO SE QUITAN
 *   · Nada se escribe sin que antes se vea qué se va a escribir. El archivo
 *     viene de un sistema que no controlamos.
 *   · Las aulas solo se crean cuando faltan. Lo que el personal capturó en
 *     ellas vale semanas de trabajo y el JSON no lo trae.
 *
 * Borrar un año entero también se hace desde acá, porque es la otra mitad de
 * lo mismo: si se puede cargar 2027, tiene que poder sacarse 2025.
 *
 * Solo administración y desarrollo.y 35192
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo("importacion", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Importación de Datos · UCR</title>
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
        .sedes-lista { max-height: 60vh; overflow-y: auto; }
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

        /* ===== ZONA DE ARCHIVO ===== */
        .soltar {
            border: 2px dashed var(--gray-300); border-radius: var(--radius-lg);
            padding: 2.5rem 1.5rem; text-align: center; cursor: pointer;
            transition: all .15s; background: var(--gray-50);
        }
        .soltar:hover, .soltar.encima {
            border-color: var(--primary); background: #f0f7ff;
        }
        .soltar i { font-size: 2.5rem; color: var(--gray-400); margin-bottom: .75rem; }
        .soltar.encima i, .soltar:hover i { color: var(--primary); }
        .soltar h3 { font-size: 1rem; font-weight: 600; color: var(--gray-700); }
        .soltar p { font-size: .85rem; color: var(--gray-500); margin-top: .35rem; }

        .archivo-puesto {
            display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
            background: #d1fae5; border: 1px solid #6ee7b7;
            border-radius: var(--radius); padding: .9rem 1.1rem;
        }
        .archivo-puesto strong { color: #065f46; }

        /* ===== CONTEOS DEL ANÁLISIS ===== */
        .cifras {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .75rem; margin: 1rem 0;
        }
        .cifra {
            background: #fff; border: 1px solid var(--gray-200);
            border-radius: var(--radius); padding: .9rem 1rem;
        }
        .cifra .n { font-size: 1.6rem; font-weight: 800; color: var(--primary); line-height: 1; }
        .cifra .r {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; color: var(--gray-500); margin-top: .3rem;
        }
        .cifra.nueva .n { color: var(--success); }
        .cifra.ojo .n { color: var(--warning); }

        /* ===== AÑOS ===== */
        .anio-fila {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap; padding: .9rem 1.1rem;
            border: 1px solid var(--gray-200); border-radius: var(--radius);
            margin-bottom: .6rem; background: #fff;
        }
        .anio-fila.activa { border-color: var(--success); background: #f0fdf4; }
        .anio-fila .datos { font-size: .8rem; color: var(--gray-500); margin-top: .2rem; }

        .zona-peligro {
            background: #fff7ed; border: 1px solid #fed7aa;
            border-radius: var(--radius); padding: 1.1rem 1.25rem; margin-top: 1.25rem;
        }
        .zona-peligro h4 { color: #9a3412; font-size: .92rem; margin-bottom: .4rem; }
        .zona-peligro p { color: #7c2d12; font-size: .82rem; line-height: 1.55; }

        .lista-avisos {
            max-height: 220px; overflow-y: auto; background: var(--gray-50);
            border-radius: var(--radius); padding: .75rem 1rem;
            font-size: .8rem; color: var(--gray-600);
        }
        .lista-avisos div { padding: .2rem 0; border-bottom: 1px dotted var(--gray-200); }

        /* ===== CAMBIOS CAMPO POR CAMPO ===== */
        .cambios-detalle {
            max-height: 320px; overflow-y: auto; border: 1px solid var(--gray-200);
            border-radius: var(--radius); margin-top: .5rem;
        }
        .cambios-detalle summary {
            cursor: pointer; font-size: .85rem; font-weight: 600; color: var(--gray-700);
            padding: .55rem .9rem;
        }
        .cambio-fila {
            padding: .55rem .9rem; border-top: 1px solid var(--gray-100); font-size: .8rem;
        }
        .cambio-fila strong { color: var(--gray-700); }
        .cambio-campo {
            display: flex; gap: .4rem; flex-wrap: wrap; color: var(--gray-600);
            margin-top: .2rem;
        }
        .cambio-campo .etiqueta { font-weight: 600; min-width: 130px; }
        .cambio-campo .antes { color: var(--danger); text-decoration: line-through; }
        .cambio-campo .despues { color: var(--success); }
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
        <h1>Importación de Datos</h1>
        <p>Carga anual desde el sistema de coordinaciones y limpieza de años anteriores</p>
    </div>

    <div id="aviso" class="aviso"></div>

    <!-- ===== PASO 1: EL ARCHIVO ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-file-arrow-up"></i> 1 · Elegí el archivo</h2>

        <div id="zonaSoltar" class="soltar" onclick="document.getElementById('archivo').click()">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <h3>Arrastrá el JSON acá, o tocá para buscarlo</h3>
            <p>La exportación completa del sistema de coordinaciones (.json)</p>
        </div>
        <input type="file" id="archivo" accept=".json,application/json" style="display:none;">

        <div id="archivoPuesto" style="display:none;margin-top:1rem;"></div>
    </div>

    <!-- ===== PASO 2: LO QUE HARÍA ===== -->
    <div class="tarjeta" id="tarjetaAnalisis" style="display:none;">
        <h2><i class="fa-solid fa-magnifying-glass-chart"></i> 2 · Qué se va a importar</h2>
        <div id="analisis"></div>
    </div>

    <!-- ===== AÑOS EN LA BASE ===== -->
    <div class="tarjeta">
        <h2><i class="fa-regular fa-calendar"></i> Años cargados en el sistema</h2>
        <div id="listaAnios"><div class="vacio">Cargando…</div></div>

        <div class="zona-peligro">
            <h4><i class="fa-solid fa-triangle-exclamation"></i> Borrar un año completo</h4>
            <p>
                Elimina la convocatoria y <strong>todo</strong> lo que cuelga de ella:
                sedes, aulas, tulas, marchamos, actas y asignaciones. No se puede deshacer
                y no hay papelera. La convocatoria activa no se puede borrar: primero hay
                que activar otra.
            </p>
        </div>
    </div>

    <!-- ===== HISTORIAL ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Importaciones anteriores</h2>
        <div class="tabla-scroll">
            <table>
                <thead><tr>
                    <th>Fecha</th><th>Año</th><th>Archivo</th>
                    <th>Sedes</th><th>Aulas</th><th>Padrón</th><th>Por</th>
                </tr></thead>
                <tbody id="tablaHistorial"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API = '../api/importacion.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

// El contenido del archivo se guarda acá: se lee una vez y se usa dos veces,
// para analizar y para importar. Volver a leerlo entre los dos pasos abriría
// la puerta a que se importe algo distinto de lo que se revisó.
let contenido = null;
let nombreArchivo = '';
let analisis = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarAnios();
    cargarHistorial();

    document.getElementById('archivo').addEventListener('change', e => {
        if (e.target.files.length) tomarArchivo(e.target.files[0]);
    });

    const zona = document.getElementById('zonaSoltar');
    ['dragenter', 'dragover'].forEach(ev =>
        zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.add('encima'); }));
    ['dragleave', 'drop'].forEach(ev =>
        zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.remove('encima'); }));

    zona.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) tomarArchivo(e.dataTransfer.files[0]);
    });
});

// ===================== ARCHIVO =====================

function tomarArchivo(archivo) {
    if (!/\.json$/i.test(archivo.name)) {
        mostrar('El archivo tiene que ser un .json. Ese parece de otro tipo.', 'error');
        return;
    }

    const lector = new FileReader();

    lector.onload = () => {
        contenido = lector.result;
        nombreArchivo = archivo.name;

        document.getElementById('archivoPuesto').style.display = '';
        document.getElementById('archivoPuesto').innerHTML = `
            <div class="archivo-puesto">
                <i class="fa-regular fa-file-code" style="font-size:1.4rem;color:#065f46"></i>
                <div style="flex:1;min-width:180px;">
                    <strong>${escapar(archivo.name)}</strong><br>
                    <span style="font-size:.8rem;color:#065f46">
                        ${(archivo.size / 1024).toFixed(0)} KB</span>
                </div>
                <button class="btn-secundario btn-chico" onclick="quitarArchivo()">
                    <i class="fa-solid fa-xmark"></i> Quitar</button>
            </div>`;

        analizar();
    };

    lector.onerror = () => mostrar('No se pudo leer el archivo.', 'error');
    lector.readAsText(archivo, 'UTF-8');
}

function quitarArchivo() {
    contenido = null; nombreArchivo = ''; analisis = null;
    document.getElementById('archivo').value = '';
    document.getElementById('archivoPuesto').style.display = 'none';
    document.getElementById('tarjetaAnalisis').style.display = 'none';
}

// ===================== ANÁLISIS =====================

async function analizar() {
    mostrar('Revisando el archivo…', 'info');

    try {
        const d = await enviar({ accion: 'analizar', contenido });
        analisis = d;
        pintarAnalisis(d);
        document.getElementById('aviso').className = 'aviso';
    } catch (e) {
        document.getElementById('tarjetaAnalisis').style.display = 'none';
        mostrar(e.message, 'error');
    }
}

function pintarAnalisis(d) {
    const anios = Object.keys(d.anios);
    const c = d.conteos;

    document.getElementById('tarjetaAnalisis').style.display = '';

    // Un archivo con más de un año no se puede importar: no habría forma de
    // decidir a cuál convocatoria va cada sede.
    if (anios.length !== 1) {
        document.getElementById('analisis').innerHTML = `
            <div class="aviso error" style="display:block">
                <i class="fa-solid fa-triangle-exclamation"></i>
                ${anios.length === 0
                    ? 'Ninguna sede trae fecha de examen, así que no se sabe a qué año pertenece el archivo.'
                    : `El archivo mezcla ${anios.length} años (${anios.join(', ')}). Hay que separarlos antes de importar.`}
            </div>`;
        return;
    }

    const anio = anios[0];
    const conv = d.convocatoria;

    document.getElementById('analisis').innerHTML = `
        <div class="aviso ${conv ? 'info' : 'exito'}" style="display:block">
            <i class="fa-solid ${conv ? 'fa-circle-info' : 'fa-circle-plus'}"></i>
            ${conv
                ? `El año <strong>${anio}</strong> ya existe en el sistema. Las sedes que ya
                   están se actualizan y las nuevas se agregan.`
                : `El año <strong>${anio}</strong> todavía no existe: se va a crear.
                   No queda activo — eso se hace aparte, cuando corresponda.`}
        </div>

        <div class="cifras">
            <div class="cifra"><div class="n">${c.sedes}</div><div class="r">Sedes en el archivo</div></div>
            <div class="cifra nueva"><div class="n">${c.sedesNuevas}</div><div class="r">Sedes nuevas</div></div>
            <div class="cifra"><div class="n">${c.sedesExistentes}</div><div class="r">Ya existen</div></div>
            <div class="cifra${c.sedesConCambios > 0 ? ' ojo' : ''}"><div class="n">${c.sedesConCambios}</div><div class="r">Sedes con cambios</div></div>
            <div class="cifra"><div class="n">${c.aulasPrevistas}</div><div class="r">Aulas previstas</div></div>
            <div class="cifra"><div class="n">${c.padron}</div><div class="r">Personas del padrón</div></div>
            <div class="cifra${c.padronConCambios > 0 ? ' ojo' : ''}"><div class="n">${c.padronConCambios}</div><div class="r">Padrón con cambios</div></div>
            <div class="cifra"><div class="n">${c.aplicadores}</div><div class="r">Puestos nombrados</div></div>
            <div class="cifra${c.asignacionesNuevas > 0 || c.asignacionesQuitadas > 0 ? ' ojo' : ''}">
                <div class="n">${c.asignacionesNuevas + c.asignacionesQuitadas}</div>
                <div class="r">Puestos que cambian</div></div>
            ${c.sinCoordinador > 0
                ? `<div class="cifra ojo"><div class="n">${c.sinCoordinador}</div>
                     <div class="r">Sin coordinador</div></div>` : ''}
        </div>

        ${d.diferenciasAulas.length ? `
            <div class="nota" style="margin-bottom:1rem;">
                <strong>${d.diferenciasAulas.length} sede(s) tienen distinta cantidad de aulas
                que la que dice el archivo.</strong><br>
                Las aulas que falten se crean; las que sobren <u>no se borran</u>, porque pueden
                ser de adecuación y llevar folletos ya capturados.
            </div>
            <div class="lista-avisos">
                ${d.diferenciasAulas.map(x => `<div>${escapar(x)}</div>`).join('')}
            </div>` : ''}

        ${d.cambiosSedes.length ? `
            <details class="cambios-detalle">
                <summary>Ver los ${d.cambiosSedes.length} cambio(s) de sede campo por campo</summary>
                ${d.cambiosSedes.map(pintarCambioSede).join('')}
            </details>` : ''}

        ${d.diferenciasPersonas.length ? `
            <details class="cambios-detalle">
                <summary>Ver los ${c.padronConCambios} cambio(s) del padrón campo por campo
                    ${c.padronConCambios > d.diferenciasPersonas.length ? ' (primeros ' + d.diferenciasPersonas.length + ')' : ''}</summary>
                ${d.diferenciasPersonas.map(pintarCambioPersona).join('')}
            </details>` : ''}

        ${(c.asignacionesNuevas > 0 || c.asignacionesQuitadas > 0) ? `
            <div class="nota" style="margin-bottom:1rem;">
                Los puestos nombrados se reemplazan enteros al importar:
                <strong>${c.asignacionesNuevas}</strong> puesto(s) quedarían nuevos o con otra persona,
                <strong>${c.asignacionesQuitadas}</strong> se quitarían por no venir en el archivo,
                y ${c.asignacionesIguales} seguirían igual.
            </div>` : ''}

        <div class="acciones">
            <button class="btn-secundario" onclick="quitarArchivo()">Cancelar</button>
            <button class="btn-primario" id="btnImportar" onclick="importar()">
                <i class="fa-solid fa-database"></i> Importar el año ${anio}</button>
        </div>`;
}

// ===================== DETALLE DE CAMBIOS =====================

const ETIQUETAS_CAMPO_SEDE = {
    codigo_externo: 'Código externo', nombre: 'Nombre', turno: 'Turno',
    numero_aplicacion: 'N.º aplicación', fecha_examen: 'Fecha de examen',
    aulas_previstas: 'Aulas previstas', apoyo_requerido: 'Apoyo requerido',
    coordinador_nombre: 'Coordinador', coordinador_correo: 'Correo coordinador',
};

const ETIQUETAS_CAMPO_PERSONA = {
    nombre: 'Nombre', cedula: 'Cédula', universidad: 'Universidad',
    aprobo_coordinacion: 'Aprobó coordinación', aprobo_apoyo: 'Aprobó apoyo',
    aprobo_aula: 'Aprobó aula', rol_actual: 'Rol actual',
};

function valorLegible(v) {
    if (v === null || v === '' || v === undefined) return '(vacío)';
    if (v === 1 || v === '1') return 'Sí';
    if (v === 0 || v === '0') return 'No';
    return escapar(String(v));
}

function pintarDiffCampos(diff, etiquetas) {
    return Object.entries(diff).map(([campo, cambio]) => `
        <div class="cambio-campo">
            <span class="etiqueta">${etiquetas[campo] || campo}</span>
            <span class="antes">${valorLegible(cambio.antes)}</span>
            <i class="fa-solid fa-arrow-right" style="font-size:.7rem;margin-top:.15rem;"></i>
            <span class="despues">${valorLegible(cambio.despues)}</span>
        </div>`).join('');
}

function pintarCambioSede(s) {
    return `
        <div class="cambio-fila">
            <strong>${escapar(s.codigo)}</strong> — ${escapar(s.nombre)}
            ${pintarDiffCampos(s.diff, ETIQUETAS_CAMPO_SEDE)}
        </div>`;
}

function pintarCambioPersona(p) {
    return `
        <div class="cambio-fila">
            <strong>${escapar(p.nombre)}</strong> — ${escapar(p.correo)}
            ${pintarDiffCampos(p.diff, ETIQUETAS_CAMPO_PERSONA)}
        </div>`;
}

// ===================== IMPORTACIÓN =====================

async function importar() {
    const anio = Object.keys(analisis.anios)[0];
    const c = analisis.conteos;

    if (!confirm(
        `Se va a importar el año ${anio}:\n\n` +
        `· ${c.sedesNuevas} sede(s) nuevas\n` +
        `· ${c.sedesExistentes} sede(s) actualizadas\n` +
        `· ${c.padron} persona(s) del padrón\n\n` +
        `Lo que el personal ya capturó en las aulas no se toca.\n\n¿Continuar?`)) return;

    const boton = document.getElementById('btnImportar');
    boton.disabled = true;
    boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importando…';

    try {
        const d = await enviar({
            accion: 'importar', csrf: CSRF, contenido, archivo: nombreArchivo
        });

        const r = d.resultado;
        mostrar(
            `${d.mensaje}  ` +
            `Sedes: ${r.sedesNuevas} nuevas, ${r.sedesActualizadas} actualizadas · ` +
            `Aulas creadas: ${r.aulasCreadas} · ` +
            `Padrón: ${r.padronNuevas} nuevas, ${r.padronActualizadas} actualizadas · ` +
            `Puestos: ${r.asignaciones}`, 'exito');

        quitarArchivo();
        cargarAnios();
        cargarHistorial();

    } catch (e) {
        mostrar(e.message, 'error');
        boton.disabled = false;
        boton.innerHTML = '<i class="fa-solid fa-database"></i> Importar el año ' + anio;
    }
}

// ===================== AÑOS =====================

async function cargarAnios() {
    try {
        const d = await pedir(`${API}?accion=convocatorias`);
        const cont = document.getElementById('listaAnios');

        if (!d.convocatorias.length) {
            cont.innerHTML = '<div class="vacio">Todavía no hay ningún año cargado.</div>';
            return;
        }

        cont.innerHTML = d.convocatorias.map(c => `
            <div class="anio-fila ${c.activa ? 'activa' : ''}">
                <div>
                    <strong style="font-size:1.05rem">${escapar(c.nombre)}</strong>
                    ${c.activa ? '<span class="etiqueta et-ok" style="margin-left:.5rem">Activa</span>' : ''}
                    <div class="datos">
                        ${c.sedes} sede(s) · ${c.aulas} aula(s) ·
                        ${c.tulas} tula(s) · ${c.asignaciones} puesto(s) nombrado(s)
                    </div>
                </div>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    <button class="btn-secundario btn-chico"
                            onclick="renombrar(${c.id}, '${escapar(c.nombre)}')">
                        <i class="fa-solid fa-pen"></i> Renombrar</button>
                    ${c.activa
                        ? `<span style="font-size:.78rem;color:var(--gray-500);align-self:center">
                             Activá otra para poder borrarla</span>`
                        : `<button class="btn-secundario btn-chico"
                                   onclick="activar(${c.id}, '${escapar(c.nombre)}')">
                             <i class="fa-regular fa-circle-check"></i> Activar</button>
                           <button class="btn-secundario btn-chico" style="color:#991b1b"
                                   onclick="borrarAnio(${c.id}, '${escapar(c.nombre)}', ${c.sedes + c.aulas + c.tulas})">
                             <i class="fa-regular fa-trash-can"></i> Borrar</button>`}
                </div>
            </div>`).join('') +
            `<div class="nota" style="margin-top:.75rem;">
                <strong>El nombre del año no es una etiqueta.</strong> Es con lo que el
                importador reconoce a qué convocatoria pertenece un archivo: deduce el año
                de las fechas de examen y busca una que se llame igual. Si no calza, crea
                otra y las sedes del mismo año quedan repartidas en dos.
             </div>` +
            `<div class="nota" style="margin-top:.75rem;">
                El padrón de personas aplicadoras (${d.padron}) es común a todos los años y
                no se borra con ninguno: la misma gente vuelve de una convocatoria a otra.
             </div>`;
    } catch (e) {
        document.getElementById('listaAnios').innerHTML =
            `<div class="vacio">${escapar(e.message)}</div>`;
    }
}

async function renombrar(id, nombre) {
    const nuevo = prompt(
        'Nombre de la convocatoria.\n\n' +
        'Tiene que ser el año tal cual —2026, 2027— porque es con esto que el\n' +
        'importador reconoce a qué año pertenece cada archivo.',
        nombre);

    if (nuevo === null || nuevo.trim() === nombre) return;

    try {
        const d = await enviar({
            accion: 'renombrar', csrf: CSRF, convocatoria: id, nombre: nuevo.trim()
        });

        mostrar(d.mensaje, 'exito');
        cargarAnios();
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

async function activar(id, nombre) {
    if (!confirm(
        `Todos los módulos van a pasar a trabajar sobre ${nombre}.\n\n` +
        `Si hay gente embalando en este momento, va a ver otras sedes.\n\n¿Continuar?`)) return;

    try {
        const d = await enviar({ accion: 'activar', csrf: CSRF, convocatoria: id });
        mostrar(d.mensaje, 'exito');
        cargarAnios();
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

async function borrarAnio(id, nombre, filas) {
    if (!confirm(
        `Vas a borrar el año ${nombre} y aproximadamente ${filas} registro(s).\n\n` +
        `Esto NO se puede deshacer: se van sedes, aulas, tulas, marchamos y actas.\n\n` +
        `¿Seguir?`)) return;

    const escrito = prompt(`Para confirmar, escribí el año exactamente:\n\n${nombre}`);

    if (escrito === null) return;

    try {
        const d = await enviar({
            accion: 'borrarAnio', csrf: CSRF, convocatoria: id, confirmacion: escrito
        });

        mostrar(d.mensaje, 'exito');
        cargarAnios();
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

// ===================== HISTORIAL =====================

async function cargarHistorial() {
    try {
        const d = await pedir(`${API}?accion=historial`);
        const cuerpo = document.getElementById('tablaHistorial');

        cuerpo.innerHTML = d.importaciones.length
            ? d.importaciones.map(i => `
                <tr>
                    <td>${escapar(i.fecha)}</td>
                    <td><strong>${escapar(i.convocatoria)}</strong></td>
                    <td style="font-size:.78rem">${escapar(i.archivo)}</td>
                    <td>${i.sedesNuevas} + ${i.sedesActualizadas}</td>
                    <td>${i.aulasCreadas}</td>
                    <td>${i.padron}</td>
                    <td>${escapar(i.por)}</td>
                </tr>`).join('')
            : '<tr><td colspan="7" class="vacio">Todavía no se ha importado nada.</td></tr>';
    } catch (e) {
        document.getElementById('tablaHistorial').innerHTML =
            `<tr><td colspan="7" class="vacio">${escapar(e.message)}</td></tr>`;
    }
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
        body: JSON.stringify(cuerpo)
    });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo completar la operación');
    return d;
}

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.innerHTML = escapar(mensaje);
    aviso.className = 'aviso ' + tipo;
    aviso.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (tipo === 'exito') setTimeout(() => { aviso.className = 'aviso'; }, 12000);
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
