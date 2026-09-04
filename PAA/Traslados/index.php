<?php
/**
 * Módulo Traslado de Estudiantes.
 *
 * El módulo de emergencia. Se usa cuando una sede se cae y hay que mover a su
 * gente a otra: se elige la sede y el turno de origen, la sede de destino y
 * cuántos estudiantes entran por aula, y el sistema los redistribuye.
 *
 * TRES COSAS QUE LA PANTALLA HACE A PROPÓSITO
 *
 * 1. Obliga a simular antes de aplicar. El botón de aplicar no existe hasta que
 *    hay una simulación sin errores. En una operación de emergencia, con prisa,
 *    es la única forma de que alguien vea la distribución antes de escribirla.
 *
 * 2. Separa errores de avisos. Un error impide seguir; un aviso solo hay que
 *    haberlo leído. Mezclados, se ignoran los dos.
 *
 * 3. Dice en voz alta que la fórmula no se toca. Es la duda que va a tener
 *    cualquiera que use esto, y contestarla antes evita que alguien invente un
 *    procedimiento manual por las dudas.
 *
 * Solo administración y desarrollo.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo("traslados", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Traslado de Estudiantes · UCR</title>
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
            --radius: 0.5rem; --radius-lg: 1rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
            color: var(--gray-800); line-height: 1.5; min-height: 100vh; padding: 1.5rem;
        }

        .app { max-width: 1240px; margin: 0 auto; }

        .barra {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra > :first-child { justify-self: start; }
        #barraEstado {
            justify-self: center;
            text-align: center;
            white-space: nowrap;
        }
        .barra > :last-child { justify-self: end; }
        .barra a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra a:hover { text-decoration: underline; }

        .hero { text-align: center; margin-bottom: 1.5rem; }
        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .hero p { color: var(--gray-600); }

        .tarjeta {
            background: #fff; border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg); box-shadow: var(--shadow);
            padding: 1.5rem; margin-bottom: 1.25rem;
        }
        .tarjeta h2 {
            font-size: 1.05rem; font-weight: 600; color: var(--gray-700);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }
        .tarjeta h2 .contador {
            margin-left: auto; font-size: 0.78rem; font-weight: 600;
            color: var(--gray-500);
        }

        input, select, textarea {
            font-family: inherit; font-size: 0.9rem; padding: 0.5rem 0.65rem;
            border: 1px solid var(--gray-300); border-radius: var(--radius);
            background: #fff; width: 100%; color: var(--gray-800);
        }
        input:focus, select:focus { outline: 2px solid var(--secondary); outline-offset: -1px; }
        label { font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); }
        .campo { margin-bottom: 0.85rem; }
        .campo label { display: block; margin-bottom: 0.25rem; }

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
        .btn-peligro { background: var(--danger); color: #fff; }
        .btn-peligro:hover { background: #b91c1c; }
        .btn-peligro:disabled { background: var(--gray-400); cursor: not-allowed; }
        .btn-chico { padding: 0.35rem 0.7rem; font-size: 0.78rem; }

        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th {
            text-align: left; font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; color: var(--gray-500);
            padding: 0.55rem 0.6rem; border-bottom: 2px solid var(--gray-200); white-space: nowrap;
        }
        td { padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--gray-100); }
        tr:hover td { background: var(--gray-50); }
        .tabla-scroll { overflow-x: auto; max-height: 420px; overflow-y: auto; }

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
        .nota.regla { border-left-color: var(--primary); background: #f0f7ff; color: var(--gray-700); }

        .vacio { text-align: center; padding: 2rem 1rem; color: var(--gray-400); }

        /* ===== COLUMNAS ===== */
        .dos {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
        }
        @media (max-width: 900px) { .dos { grid-template-columns: 1fr; } }

        .caja {
            border: 2px solid var(--gray-200); border-radius: var(--radius);
            padding: 1rem 1.15rem;
        }
        .caja.origen  { border-color: #fca5a5; background: #fef2f2; }
        .caja.destino { border-color: #6ee7b7; background: #f0fdf4; }
        .caja h3 {
            font-size: 0.9rem; font-weight: 700; margin-bottom: 0.85rem;
            display: flex; align-items: center; gap: 0.45rem;
        }
        .caja.origen h3  { color: #991b1b; }
        .caja.destino h3 { color: #065f46; }

        .lista-sedes {
            max-height: 190px; overflow-y: auto; border: 1px solid var(--gray-200);
            border-radius: var(--radius); background: #fff;
        }
        .lista-sedes div {
            padding: 0.45rem 0.7rem; cursor: pointer; font-size: 0.82rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .lista-sedes div:hover { background: #f0f7ff; }
        .lista-sedes div.elegida { background: var(--primary); color: #fff; font-weight: 600; }
        .lista-sedes .cod { font-weight: 700; margin-right: 0.4rem; }
        .lista-sedes .n { font-size: 0.72rem; opacity: .75; float: right; }

        /* ===== CIFRAS ===== */
        .cifras {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
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
        .cifra.ok .n { color: var(--success); }
        .cifra.ojo .n { color: var(--warning); }

        .problemas { margin: 1rem 0; }
        .problemas .fila {
            display: flex; gap: .6rem; align-items: flex-start;
            padding: .6rem .9rem; border-radius: var(--radius);
            font-size: .85rem; margin-bottom: .4rem; line-height: 1.5;
        }
        .problemas .fila.err  { background: #fee2e2; color: #991b1b; }
        .problemas .fila.ojo  { background: #fef3c7; color: #92400e; }

        .etiqueta {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            padding: 0.15rem 0.5rem; border-radius: 2rem; white-space: nowrap;
        }
        .et-ok { background: #d1fae5; color: #065f46; }
        .et-rev { background: var(--gray-200); color: var(--gray-600); }
        .et-nueva { background: #fef3c7; color: #92400e; }

        .soltar {
            border: 2px dashed var(--gray-300); border-radius: var(--radius);
            padding: 1.75rem 1.5rem; text-align: center; cursor: pointer;
            transition: all .15s; background: var(--gray-50);
        }
        .soltar:hover, .soltar.encima { border-color: var(--primary); background: #f0f7ff; }
        .soltar i { font-size: 2rem; color: var(--gray-400); margin-bottom: .5rem; }
        .soltar h3 { font-size: .95rem; font-weight: 600; color: var(--gray-700); }
        .soltar p { font-size: .8rem; color: var(--gray-500); margin-top: .3rem; }

        .plegable > summary {
            cursor: pointer; font-size: 1.05rem; font-weight: 600; color: var(--gray-700);
            display: flex; align-items: center; gap: .5rem; list-style: none;
        }
        .plegable > summary::-webkit-details-marker { display: none; }
        .plegable > summary::before { content: '\f0da'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
        .plegable[open] > summary::before { content: '\f0d7'; }
        .plegable > div { margin-top: 1rem; }

        .marca { display: flex; align-items: flex-start; gap: .5rem; margin: .6rem 0; }
        .marca input { width: auto; margin-top: .2rem; }
        .marca label { font-weight: 500; color: var(--gray-700); font-size: .85rem; line-height: 1.5; }

        .acciones {
            display: flex; gap: 0.75rem; margin-top: 1.25rem;
            flex-wrap: wrap; justify-content: space-between;
        }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .tarjeta { padding: 1.15rem; }
        }
    </style>
</head>
<body>
<div class="app">

    <div class="barra">
        <span><i class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
        <span id="barraEstado"></span>
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
        </div>
        <h1>Traslado de Estudiantes</h1>
        <p>Mover a los estudiantes de una sede a otra y redistribuirlos en aulas de otra capacidad</p>
    </div>

    <div id="aviso" class="aviso"></div>

    <div class="nota regla" style="margin-top:0;margin-bottom:1.25rem;">
        <strong>El número de fórmula no se toca nunca.</strong>
        La fórmula es la versión del cuadernillo que le tocó a cada quien y se va con la
        persona a la sede nueva. Quien identifica al estudiante es su número de cómputo,
        que tampoco cambia.
        <br><br>
        Un traslado cambia el <strong>lugar</strong>: sede, aula, turno, y de arrastre el
        nombre de la sede y la hora del examen, que en el archivo van pegados al turno.
        Todo lo demás sale igual al exportar.
    </div>

    <!-- ===== PADRÓN ===== -->
    <div class="tarjeta">
        <details class="plegable" id="plegablePadron">
            <summary>
                <i class="fa-solid fa-file-arrow-up"></i> Padrón de estudiantes
                <span class="contador" id="contadorPadron"></span>
            </summary>
            <div>
                <div id="zonaSoltar" class="soltar" onclick="document.getElementById('archivo').click()">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <h3>Arrastrá el archivo acá, o tocá para buscarlo</h3>
                    <p>CSV, XLSX, el «.xls» que exporta el otro sistema, o cualquiera de ellos en .gz</p>
                </div>
                <input type="file" id="archivo" accept=".csv,.xlsx,.xls,.txt,.gz" style="display:none;">

                <div id="analisis"></div>

                <div class="nota">
                    <strong>Si el archivo no pasa por el tope de subida, comprimilo.</strong>
                    Un <code>.csv.gz</code> se lee igual que un CSV y pesa alrededor de la
                    séptima parte: el padrón completo de 2026 baja de 6,9 MB a menos de 1 MB.
                    Ojo con la trampa: pasar un <code>.xlsx</code> a CSV lo hace <em>más</em>
                    grande, porque el .xlsx ya viene comprimido por dentro.
                    <br><br>
                    Las columnas se reconocen solas —da igual si dicen <code>NUMERO_AULA</code>,
                    <code>numeroAula</code> o <code>Número de Aula</code>— y la pantalla muestra
                    cuáles reconoció antes de escribir nada.
                </div>
            </div>
        </details>
    </div>

    <!-- ===== TRASLADO ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-people-arrows"></i> Traslado</h2>

        <div class="dos">
            <!-- ORIGEN -->
            <div class="caja origen">
                <h3><i class="fa-solid fa-arrow-right-from-bracket"></i> De dónde salen</h3>

                <div class="campo">
                    <label for="buscarOrigen">Sede de origen</label>
                    <input type="text" id="buscarOrigen" placeholder="Buscar por código o nombre…"
                           oninput="pintarSedes('origen')">
                </div>
                <div class="lista-sedes" id="listaOrigen"></div>

                <div class="campo" style="margin-top:.85rem;">
                    <label for="turnoOrigen">Turno</label>
                    <select id="turnoOrigen" onchange="verOrigen()">
                        <option value="">— elegí primero la sede —</option>
                    </select>
                </div>

                <div id="resumenOrigen"></div>
            </div>

            <!-- DESTINO -->
            <div class="caja destino">
                <h3><i class="fa-solid fa-arrow-right-to-bracket"></i> A dónde van</h3>

                <div class="marca">
                    <input type="radio" name="tipoDestino" id="destinoExistente" value="existente"
                           checked onchange="cambiarTipoDestino()">
                    <label for="destinoExistente">Una sede que ya está en el sistema</label>
                </div>
                <div class="marca">
                    <input type="radio" name="tipoDestino" id="destinoNuevo" value="nuevo"
                           onchange="cambiarTipoDestino()">
                    <label for="destinoNuevo">Crear una sede nueva</label>
                </div>

                <div id="bloqueExistente" style="margin-top:.85rem;">
                    <div class="campo">
                        <label for="buscarDestino">Sede de destino</label>
                        <input type="text" id="buscarDestino" placeholder="Buscar por código o nombre…"
                               oninput="pintarSedes('destino')">
                    </div>
                    <div class="lista-sedes" id="listaDestino"></div>
                </div>

                <div id="bloqueNuevo" style="display:none;margin-top:.85rem;">
                    <div class="campo">
                        <label for="codigoNuevo">Código de la sede</label>
                        <input type="text" id="codigoNuevo" maxlength="20" placeholder="ej. 918">
                    </div>
                    <div class="campo">
                        <label for="nombreNuevo">Nombre de la sede</label>
                        <input type="text" id="nombreNuevo" maxlength="200" placeholder="ej. LICEO DE SANTA ANA">
                    </div>
                    <div class="campo">
                        <label for="direccionNueva">Dirección (opcional)</label>
                        <input type="text" id="direccionNueva" maxlength="255">
                    </div>
                    <div class="campo">
                        <label for="coordinadorNombre">Persona coordinadora (opcional)</label>
                        <input type="text" id="coordinadorNombre" maxlength="150">
                    </div>
                    <div class="campo">
                        <label for="coordinadorCorreo">Correo de esa persona (opcional)</label>
                        <input type="email" id="coordinadorCorreo" maxlength="150">
                    </div>
                    <div class="nota" style="margin-top:0">
                        El código no puede repetir uno que ya exista en esta convocatoria. La fecha de
                        examen la hereda de la sede de origen, porque es la misma aplicación.
                    </div>
                </div>

                <div class="campo" style="margin-top:.85rem;">
                    <label for="reparto">Cómo se reparten</label>
                    <select id="reparto" onchange="cambiarReparto()">
                        <option value="capacidad">Tantos estudiantes por aula</option>
                        <option value="aulas">En una cantidad fija de aulas</option>
                    </select>
                </div>

                <div class="campo" id="bloqueCapacidad">
                    <label for="capacidad">Estudiantes por aula</label>
                    <input type="number" id="capacidad" min="1" max="500" value="25">
                </div>

                <div class="campo" id="bloqueAulas" style="display:none;">
                    <label for="aulasDestino">Cuántas aulas hay disponibles</label>
                    <input type="number" id="aulasDestino" min="1" max="200" value="1">
                    <div style="font-size:.75rem;color:var(--gray-500);margin-top:.25rem;">
                        Los estudiantes se reparten lo más parejo posible entre esas aulas.
                    </div>
                </div>

                <div class="campo">
                    <label for="turnoDestino">Turno en el destino</label>
                    <select id="turnoDestino" onchange="cambiarTurnoDestino()">
                        <option value="">Vacío: se conserva el turno que ya tenían</option>
                        <option value="__nuevo">Escribir un turno nuevo…</option>
                    </select>
                    <input type="text" id="turnoDestinoNuevo" maxlength="50" style="display:none;margin-top:.4rem"
                           placeholder="Nombre del turno nuevo" oninput="olvidarPlan()">
                </div>

                <div class="campo">
                    <label for="numeracion">Numeración de las aulas</label>
                    <select id="numeracion" onchange="document.getElementById('bloqueInicial').style.display =
                             this.value === 'desde' ? '' : 'none'">
                        <option value="continuar">Seguir después de las aulas que ya tiene</option>
                        <option value="uno">Empezar en el aula 1</option>
                        <option value="desde">Empezar en un número que yo elija</option>
                    </select>
                </div>

                <div class="campo" id="bloqueInicial" style="display:none;">
                    <label for="aulaInicial">Primera aula</label>
                    <input type="number" id="aulaInicial" min="1" value="1">
                </div>
            </div>
        </div>

        <div class="acciones">
            <button class="btn-secundario" onclick="limpiar()">
                <i class="fa-solid fa-eraser"></i> Limpiar</button>
            <button class="btn-primario" id="btnSimular" onclick="simular()">
                <i class="fa-solid fa-flask"></i> Simular el traslado</button>
        </div>
    </div>

    <!-- ===== SIMULACIÓN ===== -->
    <div class="tarjeta" id="tarjetaPlan" style="display:none;">
        <h2><i class="fa-solid fa-diagram-project"></i> Qué va a pasar</h2>
        <div id="plan"></div>
    </div>

    <!-- ===== HISTORIAL ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Traslados hechos</h2>
        <div class="tabla-scroll">
            <table>
                <thead><tr>
                    <th>Fecha</th><th>De</th><th>A</th><th>Estudiantes</th>
                    <th>Aulas</th><th>Por</th><th></th>
                </tr></thead>
                <tbody id="tablaHistorial"></tbody>
            </table>
        </div>
    </div>

    <!-- ===== EXPORTAR ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-file-excel"></i> Bajar el Excel</h2>
        <p style="font-size:.875rem;color:var(--gray-600);margin-bottom:1rem;">
            Sale con las mismas columnas que traía el archivo original, con la sede, el aula y el
            turno ya cambiados. Se puede volver a subir al otro sistema tal cual.
        </p>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <button class="btn-secundario" onclick="bajar('todos')">
                <i class="fa-solid fa-download"></i> Todos los estudiantes, ya con los cambios</button>
            <button class="btn-secundario" onclick="bajar('movidos')">
                <i class="fa-solid fa-download"></i> Solo los que se movieron</button>
        </div>
    </div>
</div>

<script>
const API  = '../api/traslados.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

let sedes    = [];
let elegido  = { origen: null, destino: null };
let plan     = null;
let archivo  = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarEstado();
    cargarSedes();
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

// ===================== ESTADO =====================

async function cargarEstado() {
    try {
        const d = await pedir(`${API}?accion=estado`);

        document.getElementById('barraEstado').innerHTML =
            `<i class="fa-regular fa-calendar"></i> ${escapar(d.convocatoria)} ·
             ${d.estudiantes.toLocaleString('es-CR')} estudiante(s)`;

        document.getElementById('contadorPadron').textContent = d.estudiantes === 0
            ? 'todavía no hay ninguno cargado'
            : `${d.estudiantes.toLocaleString('es-CR')} en ${d.sedesConDatos} sede(s)` +
              (d.trasladados > 0 ? ` · ${d.trasladados} trasladado(s)` : '');

        // Sin padrón no hay nada que trasladar: se abre solo la parte de carga.
        if (d.estudiantes === 0) {
            document.getElementById('plegablePadron').open = true;
            mostrar('Todavía no hay estudiantes cargados. Subí el archivo para empezar.', 'info');
        }
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

// ===================== SEDES =====================

async function cargarSedes() {
    try {
        const d = await pedir(`${API}?accion=sedes`);
        sedes = d.sedes;
        pintarSedes('origen');
        pintarSedes('destino');
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

function pintarSedes(cual) {
    const filtro = document.getElementById(cual === 'origen' ? 'buscarOrigen' : 'buscarDestino')
                           .value.trim().toLowerCase();

    // El origen solo tiene sentido si tiene estudiantes; el destino puede no
    // tener ninguno todavía, que es el caso normal.
    let lista = cual === 'origen' ? sedes.filter(s => s.estudiantes > 0) : sedes;

    if (filtro) {
        lista = lista.filter(s =>
            s.codigo.toLowerCase().includes(filtro) || s.nombre.toLowerCase().includes(filtro));
    }

    const caja = document.getElementById(cual === 'origen' ? 'listaOrigen' : 'listaDestino');

    if (!lista.length) {
        caja.innerHTML = `<div style="color:var(--gray-400);cursor:default">
            ${cual === 'origen' ? 'Ninguna sede con estudiantes cargados.' : 'Ninguna sede coincide.'}</div>`;
        return;
    }

    caja.innerHTML = lista.slice(0, 300).map(s => `
        <div onclick="elegirSede('${cual}', '${escapar(s.codigo)}')"
             class="${elegido[cual] === s.codigo ? 'elegida' : ''}">
            <span class="cod">${escapar(s.codigo)}</span>${escapar(s.nombre)}
            <span class="n">${s.estudiantes} est · ${s.aulas} aulas</span>
        </div>`).join('');
}

async function elegirSede(cual, codigo) {
    elegido[cual] = codigo;
    pintarSedes(cual);
    olvidarPlan();

    if (cual === 'origen') {
        await cargarTurnos(codigo);
    } else if (!document.getElementById('destinoNuevo').checked) {
        await cargarTurnosDestino(codigo);
    }
}

async function cargarTurnos(codigo) {
    const select = document.getElementById('turnoOrigen');

    try {
        const d = await pedir(`${API}?accion=turnos&sede=${encodeURIComponent(codigo)}`);

        const total = d.turnos.reduce((a, t) => a + t.estudiantes, 0);

        select.innerHTML =
            `<option value="__todos">Todos los turnos (${total} estudiantes)</option>` +
            d.turnos.map(t =>
                `<option value="${escapar(t.turno)}">
                    ${t.turno === '' ? '(sin turno)' : escapar(t.turno)} —
                    ${t.estudiantes} estudiante(s), ${t.aulas} aula(s)
                 </option>`).join('');

        // Con un solo turno no hay nada que elegir: se elige solo.
        if (d.turnos.length === 1) select.selectedIndex = 1;

        verOrigen();
    } catch (e) {
        select.innerHTML = '<option value="">—</option>';
        mostrar(e.message, 'error');
    }
}

async function verOrigen() {
    olvidarPlan();

    const codigo = elegido.origen;
    const turno  = document.getElementById('turnoOrigen').value;

    if (!codigo || turno === '') {
        document.getElementById('resumenOrigen').innerHTML = '';
        return;
    }

    const parametros = new URLSearchParams({ accion: 'origen', sede: codigo });
    if (turno === '__todos') parametros.set('todosLosTurnos', '1');
    else parametros.set('turno', turno);

    try {
        const d = await pedir(`${API}?${parametros}`);

        document.getElementById('resumenOrigen').innerHTML = `
            <div style="background:#fff;border-radius:var(--radius);padding:.75rem .9rem;
                        margin-top:.5rem;font-size:.85rem;">
                <strong style="font-size:1.25rem;color:#991b1b">${d.total}</strong>
                estudiante(s) en <strong>${d.aulas.length}</strong> aula(s)
                <div style="margin-top:.5rem;display:flex;gap:.3rem;flex-wrap:wrap;">
                    ${d.aulas.map(a => `
                        <span class="etiqueta et-rev" title="aula ${escapar(a.aula)}">
                            ${escapar(a.aula)}: ${a.estudiantes}</span>`).join('')}
                </div>
            </div>`;
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

function cambiarReparto() {
    const porAulas = document.getElementById('reparto').value === 'aulas';
    document.getElementById('bloqueCapacidad').style.display = porAulas ? 'none' : '';
    document.getElementById('bloqueAulas').style.display     = porAulas ? '' : 'none';
    olvidarPlan();
}

function cambiarTipoDestino() {
    const nuevo = document.getElementById('destinoNuevo').checked;
    document.getElementById('bloqueExistente').style.display = nuevo ? 'none' : '';
    document.getElementById('bloqueNuevo').style.display     = nuevo ? '' : 'none';

    // Una sede nueva no tiene turnos propios, pero igual conviene ofrecer los
    // que ya existen en otras sedes en vez de arriesgarse a escribirlos mal.
    if (nuevo) {
        cargarTurnosDestino('');
    } else if (elegido.destino) {
        cargarTurnosDestino(elegido.destino);
    }

    olvidarPlan();
}

/** Muestra el cuadro de texto solo cuando se elige "escribir un turno nuevo". */
function cambiarTurnoDestino() {
    const esNuevo = document.getElementById('turnoDestino').value === '__nuevo';
    document.getElementById('turnoDestinoNuevo').style.display = esNuevo ? '' : 'none';
    olvidarPlan();
}

/**
 * Los turnos para elegir en el destino: primero los que ya tiene esa sede, y
 * después los demás que existen en cualquier otra sede de la convocatoria —
 * un turno como «3-M-DOMINGO» es el mismo horario en cualquier sede, así que
 * aunque esta todavía no tenga a nadie ahí, no hay por qué escribirlo a mano.
 * Si de verdad no existe todavía, queda la opción de escribirlo.
 */
async function cargarTurnosDestino(codigo) {
    const select = document.getElementById('turnoDestino');
    select.innerHTML =
        '<option value="">Vacío: se conserva el turno que ya tenían</option>' +
        '<option value="__nuevo">Escribir un turno nuevo…</option>';

    try {
        const [propios, generales] = await Promise.all([
            codigo ? pedir(`${API}?accion=turnos&sede=${encodeURIComponent(codigo)}`) : Promise.resolve({ turnos: [] }),
            pedir(`${API}?accion=turnosConvocatoria`),
        ]);

        const vistos = new Set();
        let opciones = '';

        // El turno vacío ya está cubierto por la opción "conservar" de arriba.
        (propios.turnos || []).filter(t => t.turno !== '').forEach(t => {
            vistos.add(t.turno);
            opciones += `<option value="${escapar(t.turno)}">
                ${escapar(t.turno)} — ${t.estudiantes} estudiante(s), ${t.aulas} aula(s)
             </option>`;
        });

        (generales.turnos || []).filter(t => !vistos.has(t.turno)).forEach(t => {
            vistos.add(t.turno);
            opciones += `<option value="${escapar(t.turno)}">
                ${escapar(t.turno)} — existe en otra(s) sede(s)${codigo ? ', ninguno acá todavía' : ''}
             </option>`;
        });

        if (opciones) select.insertAdjacentHTML('beforeend', opciones);
    } catch (e) {
        // Si falla la consulta, igual queda la opción de escribir uno nuevo.
    }

    cambiarTurnoDestino();
}

// ===================== SIMULACIÓN =====================

/** Lo que la pantalla le manda al servidor, en un solo lugar. */
function parametrosDelTraslado() {
    const turno = document.getElementById('turnoOrigen').value;
    const nuevo = document.getElementById('destinoNuevo').checked;

    const turnoDestinoSel = document.getElementById('turnoDestino').value;
    const turnoDestino = turnoDestinoSel === '__nuevo'
        ? document.getElementById('turnoDestinoNuevo').value.trim()
        : turnoDestinoSel;

    const p = {
        sedeOrigen:   elegido.origen || '',
        reparto:      document.getElementById('reparto').value,
        capacidad:    document.getElementById('capacidad').value,
        aulasDestino: document.getElementById('aulasDestino').value,
        turnoDestino: turnoDestino,
        numeracion:   document.getElementById('numeracion').value,
        aulaInicial:  document.getElementById('aulaInicial').value,
    };

    if (turno === '__todos') p.todosLosTurnos = '1';
    else p.turnoOrigen = turno;

    if (nuevo) {
        p.sedeNueva        = '1';
        p.codigoNuevo      = document.getElementById('codigoNuevo').value.trim();
        p.nombreNuevo      = document.getElementById('nombreNuevo').value.trim();
        p.direccionNueva   = document.getElementById('direccionNueva').value.trim();
        p.coordinadorNombre = document.getElementById('coordinadorNombre').value.trim();
        p.coordinadorCorreo = document.getElementById('coordinadorCorreo').value.trim();
    } else {
        p.sedeDestino = elegido.destino || '';
    }

    return p;
}

async function simular() {
    const p = parametrosDelTraslado();

    if (!p.sedeOrigen)  { mostrar('Elegí la sede de origen.', 'error'); return; }
    if (!p.sedeNueva && !p.sedeDestino) { mostrar('Elegí la sede de destino.', 'error'); return; }
    if (document.getElementById('turnoOrigen').value === '') {
        mostrar('Elegí el turno de origen.', 'error'); return;
    }

    const boton = document.getElementById('btnSimular');
    boton.disabled = true;
    boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calculando…';

    try {
        plan = await pedir(`${API}?${new URLSearchParams({ accion: 'simulacion', ...p })}`);
        pintarPlan(plan);
        document.getElementById('aviso').className = 'aviso';
    } catch (e) {
        document.getElementById('tarjetaPlan').style.display = 'none';
        mostrar(e.message, 'error');
    } finally {
        boton.disabled = false;
        boton.innerHTML = '<i class="fa-solid fa-flask"></i> Simular el traslado';
    }
}

function pintarPlan(d) {
    document.getElementById('tarjetaPlan').style.display = '';

    const problemas =
        d.errores.map(e => `<div class="fila err">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:.15rem"></i>
            <span>${escapar(e)}</span></div>`).join('') +
        d.avisos.map(a => `<div class="fila ojo">
            <i class="fa-solid fa-triangle-exclamation" style="margin-top:.15rem"></i>
            <span>${escapar(a)}</span></div>`).join('');

    document.getElementById('plan').innerHTML = `
        <div style="font-size:.95rem;margin-bottom:.5rem;">
            <strong>${escapar(d.origen.codigo)}</strong> ${escapar(d.origen.nombre)}
            <span style="color:var(--gray-400)">(${escapar(d.origen.turno || 'sin turno')})</span>
            <i class="fa-solid fa-arrow-right" style="margin:0 .6rem;color:var(--primary)"></i>
            <strong>${escapar(d.destino.codigo)}</strong> ${escapar(d.destino.nombre)}
            <span style="color:var(--gray-400)">(${d.conservarTurno
                ? 'cada quien conserva su turno'
                : escapar(d.turnoDestino || 'sin turno')})</span>
            ${d.destino.nueva ? '<span class="etiqueta et-nueva">se va a crear</span>' : ''}
        </div>

        <div class="cifras">
            <div class="cifra"><div class="n">${d.total}</div>
                <div class="r">Estudiantes que se mueven</div></div>
            <div class="cifra ok"><div class="n">${d.aulasNecesarias}</div>
                <div class="r">Aulas en el destino</div></div>
            <div class="cifra"><div class="n">${d.capacidad}</div>
                <div class="r">${d.modoReparto === 'aulas' ? 'Máximo por aula' : 'Por aula'}</div></div>
            <div class="cifra"><div class="n">${d.aulaInicial}</div>
                <div class="r">Primera aula</div></div>
            ${d.origen.quedan > 0
                ? `<div class="cifra ojo"><div class="n">${d.origen.quedan}</div>
                     <div class="r">Quedan en el origen</div></div>` : ''}
        </div>

        ${problemas ? `<div class="problemas">${problemas}</div>` : ''}

        ${d.total > 0 ? `
            <h3 style="font-size:.9rem;font-weight:700;color:var(--gray-700);margin:1rem 0 .5rem;">
                Cómo quedan repartidos</h3>
            <div class="tabla-scroll">
                <table>
                    <thead><tr>
                        <th>Aula nueva</th><th>Estudiantes</th><th>Venían de las aulas</th>
                    </tr></thead>
                    <tbody>
                        ${d.distribucion.map(a => `
                            <tr>
                                <td><strong>${escapar(a.aula)}</strong></td>
                                <td>${a.estudiantes}</td>
                                <td style="font-size:.8rem;color:var(--gray-500)">
                                    ${a.desde.map(escapar).join(', ')}</td>
                            </tr>`).join('')}
                    </tbody>
                </table>
            </div>
            <div class="nota">
                Se reparten en el orden en que estaban: primero por aula de origen y después por
                número de fórmula, así la gente que estaba junta sigue junta. Cuando las
                capacidades no calzan, un aula de origen queda partida entre dos aulas nuevas;
                la columna de la derecha muestra de cuáles viene cada una.
            </div>` : ''}

        ${d.sePuede ? `
            <div style="border-top:1px solid var(--gray-200);margin-top:1.25rem;padding-top:1.25rem;">
                <div class="campo">
                    <label for="motivo">Por qué se hace este traslado</label>
                    <input type="text" id="motivo" maxlength="255"
                           placeholder="ej. la sede no presta las instalaciones el domingo por la mañana">
                </div>

                <div class="marca">
                    <input type="checkbox" id="sincronizarAulas" checked>
                    <label for="sincronizarAulas">
                        <strong>Poner al día las aulas del sistema de embalaje.</strong>
                        Crea en la sede de destino las aulas que falten y recalcula cuántos
                        estudiantes tiene cada aula en las dos sedes, para que los folletos
                        salgan bien. Las aulas que se queden vacías <u>no se borran</u>.
                    </label>
                </div>

                <div class="acciones">
                    <button class="btn-secundario" onclick="olvidarPlan()">Cancelar</button>
                    <button class="btn-peligro" id="btnAplicar" onclick="aplicar()">
                        <i class="fa-solid fa-people-arrows"></i>
                        Trasladar ${d.total} estudiante(s)</button>
                </div>
            </div>`
        : `<div class="nota" style="margin-top:1rem;">
              Corregí lo de arriba y volvé a simular. Nada se escribió.
           </div>`}`;

    document.getElementById('tarjetaPlan').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function olvidarPlan() {
    plan = null;
    document.getElementById('tarjetaPlan').style.display = 'none';
}

async function aplicar() {
    if (!plan || !plan.sePuede) return;

    if (!confirm(
        `Se van a mover ${plan.total} estudiante(s) de la sede ${plan.origen.codigo} ` +
        `a la sede ${plan.destino.codigo}, en ${plan.aulasNecesarias} aula(s) de ${plan.capacidad}.\n\n` +
        `El número de fórmula de cada quien no cambia.\n\n` +
        `Esto se puede deshacer después desde el historial.\n\n¿Continuar?`)) return;

    const boton = document.getElementById('btnAplicar');
    boton.disabled = true;
    boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Trasladando…';

    const p = parametrosDelTraslado();
    p.accion = 'trasladar';
    p.csrf   = CSRF;
    p.motivo = document.getElementById('motivo').value.trim();

    if (document.getElementById('sincronizarAulas').checked) p.sincronizarAulas = '1';

    try {
        const d = await enviarJSON(p);

        let extra = '';
        if (d.aulas && (d.aulas.creadas || d.aulas.actualizadas)) {
            extra = ` Aulas: ${d.aulas.creadas} creadas, ${d.aulas.actualizadas} actualizadas` +
                    (d.aulas.vaciadas ? `, ${d.aulas.vaciadas} quedaron sin estudiantes en el origen` : '') + '.';
        }

        mostrar(d.mensaje + extra, 'exito');

        olvidarPlan();
        limpiar();
        cargarEstado();
        cargarSedes();
        cargarHistorial();

        if (confirm('Traslado hecho.\n\n¿Bajar ahora el Excel con solo estos cambios?')) {
            bajar('traslado', { traslado: d.traslado });
        }
    } catch (e) {
        mostrar(e.message, 'error');
        boton.disabled = false;
        boton.innerHTML = `<i class="fa-solid fa-people-arrows"></i> Trasladar ${plan.total} estudiante(s)`;
    }
}

function limpiar() {
    elegido = { origen: null, destino: null };
    document.getElementById('buscarOrigen').value = '';
    document.getElementById('buscarDestino').value = '';
    document.getElementById('turnoOrigen').innerHTML =
        '<option value="">— elegí primero la sede —</option>';
    document.getElementById('resumenOrigen').innerHTML = '';
    document.getElementById('codigoNuevo').value = '';
    document.getElementById('nombreNuevo').value = '';
    document.getElementById('turnoDestino').innerHTML =
        '<option value="">Vacío: se conserva el turno que ya tenían</option>' +
        '<option value="__nuevo">Escribir un turno nuevo…</option>';
    document.getElementById('turnoDestinoNuevo').value = '';
    document.getElementById('turnoDestinoNuevo').style.display = 'none';
    pintarSedes('origen');
    pintarSedes('destino');
    olvidarPlan();
}

// ===================== HISTORIAL =====================

async function cargarHistorial() {
    try {
        const d = await pedir(`${API}?accion=historial`);
        const cuerpo = document.getElementById('tablaHistorial');

        cuerpo.innerHTML = d.traslados.length
            ? d.traslados.map(t => `
                <tr style="${t.revertido ? 'opacity:.55' : ''}">
                    <td style="font-size:.78rem;white-space:nowrap">${escapar(t.fecha)}</td>
                    <td style="font-size:.8rem">${escapar(t.origen)}<br>
                        <span style="color:var(--gray-400)">${escapar(t.turnoOrigen || '')}</span></td>
                    <td style="font-size:.8rem">${escapar(t.destino)}
                        ${t.sedeCreada ? '<span class="etiqueta et-nueva">nueva</span>' : ''}</td>
                    <td><strong>${t.estudiantes}</strong></td>
                    <td>${t.aulas} de ${t.capacidad}</td>
                    <td style="font-size:.8rem">${escapar(t.por)}</td>
                    <td style="white-space:nowrap">
                        <button class="btn-secundario btn-chico"
                                onclick="bajar('traslado', { traslado: ${t.id} })">
                            <i class="fa-solid fa-file-excel"></i></button>
                        ${t.revertido
                            ? '<span class="etiqueta et-rev">revertido</span>'
                            : `<button class="btn-secundario btn-chico" style="color:#991b1b"
                                       onclick="revertir(${t.id}, ${t.estudiantes})">
                                 <i class="fa-solid fa-rotate-left"></i></button>`}
                    </td>
                </tr>
                ${t.motivo ? `<tr><td colspan="7"
                    style="font-size:.78rem;color:var(--gray-500);padding-top:0;border-bottom:1px solid var(--gray-200)">
                    ${escapar(t.motivo)}</td></tr>` : ''}`).join('')
            : '<tr><td colspan="7" class="vacio">Todavía no se ha trasladado a nadie.</td></tr>';
    } catch (e) {
        document.getElementById('tablaHistorial').innerHTML =
            `<tr><td colspan="7" class="vacio">${escapar(e.message)}</td></tr>`;
    }
}

async function revertir(id, cuantos) {
    if (!confirm(
        `Se van a devolver ${cuantos} estudiante(s) a la sede y el aula que tenían antes ` +
        `de este traslado.\n\nLas aulas que se hayan creado en el destino no se borran.\n\n¿Seguir?`)) return;

    try {
        const d = await enviarJSON({ accion: 'revertir', csrf: CSRF, traslado: id });
        mostrar(d.mensaje, 'exito');
        cargarEstado();
        cargarSedes();
        cargarHistorial();
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

// ===================== PADRÓN =====================

function tomarArchivo(f) {
    archivo = f;
    document.getElementById('analisis').innerHTML =
        `<div class="aviso info" style="display:block">
            <i class="fa-solid fa-spinner fa-spin"></i> Leyendo ${escapar(f.name)}…</div>`;
    analizarArchivo();
}

async function analizarArchivo() {
    const cuerpo = new FormData();
    cuerpo.append('accion', 'analizar');
    cuerpo.append('archivo', archivo);

    try {
        const d = await enviarArchivo(cuerpo);
        pintarAnalisis(d);
    } catch (e) {
        document.getElementById('analisis').innerHTML =
            `<div class="aviso error" style="display:block">
                <i class="fa-solid fa-circle-exclamation"></i> ${escapar(e.message)}</div>`;
    }
}

function pintarAnalisis(d) {
    const c = d.conteos;

    const reconocidas = Object.entries(d.mapa)
        .map(([campo, col]) => `<div><strong>${escapar(campo)}</strong> ←
             <code>${escapar(col)}</code></div>`).join('');

    document.getElementById('analisis').innerHTML = `
        <div class="aviso exito" style="display:block">
            <i class="fa-regular fa-file"></i>
            <strong>${escapar(archivo.name)}</strong> · ${escapar(d.formato)} ·
            ${(archivo.size / 1024).toFixed(0)} KB
        </div>

        <div class="cifras">
            <div class="cifra"><div class="n">${c.validas}</div><div class="r">Estudiantes</div></div>
            <div class="cifra ok"><div class="n">${c.nuevos}</div><div class="r">Nuevos</div></div>
            <div class="cifra"><div class="n">${c.yaEstan}</div><div class="r">Ya estaban</div></div>
            <div class="cifra"><div class="n">${c.sedes}</div><div class="r">Sedes</div></div>
            ${c.sinDatos > 0 ? `<div class="cifra ojo"><div class="n">${c.sinDatos}</div>
                <div class="r">Filas descartadas</div></div>` : ''}
            ${c.repetidas > 0 ? `<div class="cifra ojo"><div class="n">${c.repetidas}</div>
                <div class="r">Identificaciones repetidas</div></div>` : ''}
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
            <div>
                <h4 style="font-size:.8rem;text-transform:uppercase;color:var(--gray-500);
                           letter-spacing:.04em;margin-bottom:.4rem;">Columnas reconocidas</h4>
                <div style="font-size:.82rem;line-height:1.8">${reconocidas}</div>
            </div>
            ${d.ignoradas.length ? `
                <div>
                    <h4 style="font-size:.8rem;text-transform:uppercase;color:var(--gray-500);
                               letter-spacing:.04em;margin-bottom:.4rem;">Se guardan sin interpretar</h4>
                    <div style="font-size:.82rem;color:var(--gray-500)">
                        ${d.ignoradas.map(escapar).join(', ')}</div>
                    <div style="font-size:.75rem;color:var(--gray-400);margin-top:.3rem">
                        Vuelven a salir igual al exportar.</div>
                </div>` : ''}
        </div>

        ${c.repetidas > 0 ? `
            <div class="problemas" style="margin-top:1rem;">
                <div class="fila ojo"><i class="fa-solid fa-triangle-exclamation"></i>
                <span>Hay ${c.repetidas} identificación(es) repetidas en el archivo. De cada una
                se va a guardar la última fila. Conviene revisar el archivo de origen: una
                identificación repetida son dos personas con el mismo número de cómputo.</span></div>
            </div>` : ''}

        ${d.sedesDesconocidas.length ? `
            <div class="problemas">
                <div class="fila ojo"><i class="fa-solid fa-triangle-exclamation"></i>
                <span>Estas sedes del archivo no están en el catálogo de la convocatoria:
                <strong>${d.sedesDesconocidas.map(escapar).join(', ')}</strong>.
                Los estudiantes se cargan igual, pero esas sedes no se van a poder usar como
                destino hasta que existan.</span></div>
            </div>` : ''}

        ${c.trasladados > 0 ? `
            <div class="problemas">
                <div class="fila err"><i class="fa-solid fa-circle-exclamation"></i>
                <span><strong>${c.trasladados} estudiante(s) que ya fueron trasladados vuelven a
                la asignación que trae el archivo.</strong> Si el archivo es anterior al traslado,
                se deshace el trabajo. Revisá que sea el archivo correcto antes de seguir.</span></div>
            </div>` : ''}

        <div class="campo" style="margin-top:1rem;">
            <label for="modo">Cómo entra</label>
            <select id="modo">
                <option value="actualizar">Actualizar — agrega los nuevos y corrige los que ya están</option>
                <option value="reemplazar">Reemplazar — borra el padrón de este año y carga solo esto</option>
            </select>
        </div>

        <div class="acciones">
            <button class="btn-secundario" onclick="quitarArchivo()">Cancelar</button>
            <button class="btn-primario" id="btnCargar" onclick="cargarPadron()">
                <i class="fa-solid fa-database"></i> Cargar ${c.validas} estudiante(s)</button>
        </div>`;
}

function quitarArchivo() {
    archivo = null;
    document.getElementById('archivo').value = '';
    document.getElementById('analisis').innerHTML = '';
}

async function cargarPadron() {
    const modo = document.getElementById('modo').value;

    if (modo === 'reemplazar' && !confirm(
        'Reemplazar borra TODOS los estudiantes de este año antes de cargar el archivo.\n\n' +
        'No se puede deshacer.\n\n¿Seguir?')) return;

    const boton = document.getElementById('btnCargar');
    boton.disabled = true;
    boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando…';

    const cuerpo = new FormData();
    cuerpo.append('accion', 'cargar');
    cuerpo.append('csrf', CSRF);
    cuerpo.append('modo', modo);
    cuerpo.append('archivo', archivo);

    try {
        const d = await enviarArchivo(cuerpo);
        mostrar(d.mensaje, 'exito');
        quitarArchivo();
        document.getElementById('plegablePadron').open = false;
        cargarEstado();
        cargarSedes();
    } catch (e) {
        mostrar(e.message, 'error');
        boton.disabled = false;
        boton.innerHTML = '<i class="fa-solid fa-database"></i> Cargar';
    }
}

// ===================== EXPORTAR =====================

function bajar(que, extra = {}) {
    const p = new URLSearchParams({ accion: 'exportar', que, ...extra });
    window.location = `${API}?${p}`;
}

// ===================== UTILIDADES =====================

async function pedir(url) {
    const r = await fetch(url + (url.includes('?') ? '&' : '?') + '_ts=' + Date.now(),
                          { cache: 'no-store' });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo consultar');
    return d;
}

async function enviarJSON(cuerpo) {
    const r = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(cuerpo)
    });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo completar la operación');
    return d;
}

/**
 * El archivo va como subida de verdad (multipart), no como texto dentro de un
 * JSON. Un padrón de decenas de miles de filas convertido a base64 crece un
 * tercio y hay que tenerlo entero en memoria dos veces, en el navegador y en
 * PHP. Así el servidor lo recibe en disco y se lee en streaming.
 */
async function enviarArchivo(cuerpo) {
    const r = await fetch(API, { method: 'POST', body: cuerpo });

    let d;
    try {
        d = await r.json();
    } catch {
        // Si PHP corta la subida por tamaño puede contestar con HTML, no JSON.
        throw new Error(
            'El servidor no devolvió una respuesta válida. Suele pasar cuando el archivo ' +
            'pesa más de lo permitido: probá guardándolo como CSV.');
    }

    if (!d.success) throw new Error(d.error || 'No se pudo procesar el archivo');
    return d;
}

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.innerHTML = escapar(mensaje);
    aviso.className = 'aviso ' + tipo;
    aviso.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (tipo === 'exito') setTimeout(() => { aviso.className = 'aviso'; }, 15000);
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
