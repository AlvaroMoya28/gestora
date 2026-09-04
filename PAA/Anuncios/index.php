<?php
/**
 * Anuncios: redactar un correo y mandarlo a los funcionarios del PAA.
 *
 * El universo de destinatarios es el mismo que el directorio de
 * Funcionarios PAA (ver api/anuncios.php) — no una lista aparte. Solo
 * administración y desarrollo: mandar correo a todo el personal de una vez
 * tiene demasiado alcance como para ser una excepción individual.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo('anuncios', '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Anuncios · UCR</title>
    <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #0055a4;
            --primary-dark: #003b73;
            --primary-light: #2b7fc2;
            --secondary: #6EC1E4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
            --radius-lg: 1rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .app { max-width: 900px; margin: 0 auto; }

        .hero { text-align: center; margin-bottom: 1.75rem; }
        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text; background-clip: text; color: transparent;
            margin-bottom: 0.35rem;
        }
        .hero p { color: var(--gray-600); }

        .barra-sesion {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra-sesion a:hover { text-decoration: underline; }

        .tarjeta {
            background: #fff; border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg); box-shadow: var(--shadow);
            padding: 1.5rem; margin-bottom: 1.5rem;
        }
        .tarjeta h2 {
            font-size: 1.05rem; font-weight: 600; color: var(--gray-700);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }

        label { font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); display: block; margin-bottom: .35rem; }
        .campo { margin-bottom: 1rem; }

        input, textarea, select {
            font-family: inherit; font-size: 0.9rem;
            padding: 0.6rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius); background: #fff; width: 100%;
        }
        input:focus, textarea:focus, select:focus { outline: 2px solid var(--secondary); outline-offset: -1px; }
        textarea { resize: vertical; min-height: 160px; font-family: inherit; }

        .de-remitente {
            display: flex; align-items: center; gap: .6rem;
            background: var(--gray-50); border: 1px solid var(--gray-200);
            border-radius: var(--radius); padding: .6rem .8rem;
            font-size: .85rem; color: var(--gray-600); margin-bottom: 1rem;
        }
        .de-remitente strong { color: var(--gray-800); }

        button {
            font-family: inherit; font-size: 0.875rem; font-weight: 600;
            padding: 0.6rem 1.1rem; border: none; border-radius: var(--radius);
            cursor: pointer; display: inline-flex; align-items: center; gap: 0.45rem;
        }
        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover { background: var(--primary-dark); }
        .btn-primario:disabled { background: var(--gray-300); cursor: not-allowed; }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover { background: var(--gray-200); }
        .btn-chico { padding: 0.35rem 0.7rem; font-size: 0.78rem; }

        .acciones { display: flex; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap; align-items: center; }

        .aviso {
            padding: 0.85rem 1.1rem; border-radius: var(--radius);
            margin-bottom: 1rem; font-size: 0.9rem; display: none;
        }
        .aviso.exito { background: #d1fae5; color: #065f46; display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; display: block; }
        .aviso.info  { background: #dbeafe; color: #1e40af; display: block; }

        .vacio { text-align: center; padding: 2.5rem 1rem; color: var(--gray-400); }

        /* Adjuntos */
        .adjuntos-lista { display: flex; flex-direction: column; gap: .4rem; margin-top: .6rem; }
        .adjunto-fila {
            display: flex; align-items: center; gap: .6rem;
            background: var(--gray-50); border: 1px solid var(--gray-200);
            border-radius: var(--radius); padding: .5rem .7rem; font-size: .82rem;
        }
        .adjunto-fila span:first-of-type { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adjunto-fila .peso { color: var(--gray-500); flex-shrink: 0; }
        .adjunto-fila button {
            background: none; color: var(--gray-400); padding: .2rem .4rem;
            flex-shrink: 0;
        }
        .adjunto-fila button:hover { color: var(--danger); }
        .peso-total { font-size: .78rem; color: var(--gray-500); margin-top: .5rem; }
        .peso-total.excedido { color: var(--danger); font-weight: 700; }

        /* Destinatarios */
        .destinatarios-barra {
            display: flex; gap: .6rem; align-items: center; flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .destinatarios-barra input[type="search"] { flex: 1; min-width: 180px; }
        .destinatarios-contador { font-size: .82rem; color: var(--gray-600); font-weight: 600; white-space: nowrap; }

        .destinatarios-lista {
            max-height: 320px; overflow-y: auto;
            border: 1px solid var(--gray-200); border-radius: var(--radius);
        }
        .destinatario-area {
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
            color: var(--gray-500); background: var(--gray-50);
            padding: .4rem .8rem; position: sticky; top: 0;
        }
        .destinatario-fila {
            display: flex; align-items: center; gap: .6rem;
            padding: .5rem .8rem; font-size: .87rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .destinatario-fila:hover { background: var(--gray-50); }
        .destinatario-fila input { width: auto; }
        .destinatario-fila .correo { color: var(--gray-500); font-size: .78rem; }
        .destinatario-fila.excluido { opacity: .5; }
        .oculta { display: none; }

        .tabla-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th {
            text-align: left; font-size: 0.68rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: var(--gray-500); padding: 0.55rem 0.6rem;
            border-bottom: 2px solid var(--gray-200); white-space: nowrap;
        }
        td { padding: 0.6rem; border-bottom: 1px solid var(--gray-100); }

        .nota {
            font-size: 0.8125rem; color: var(--gray-500);
            background: var(--gray-50); border-left: 3px solid var(--gray-300);
            padding: 0.75rem 1rem; border-radius: 0 var(--radius) var(--radius) 0;
            margin-top: 1rem; line-height: 1.6;
        }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .tarjeta { padding: 1.15rem; }
        }
    </style>
</head>
<body>
<div class="app">

    <div class="barra-sesion">
        <span><i class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
        <span><a href="<?= h(base_url()) ?>">Inicio</a> · <a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
        </div>
        <h1>Anuncios</h1>
        <p>Correo a los funcionarios del PAA</p>
    </div>

    <div id="aviso" class="aviso"></div>

    <div class="tarjeta">
        <h2><i class="fa-solid fa-pen-to-square"></i> Redactar</h2>

        <div class="de-remitente">
            <i class="fa-regular fa-envelope"></i>
            <span>De: <strong>Soporte PAA UCR &lt;soporte.paa@ucr.ac.cr&gt;</strong></span>
        </div>

        <div class="campo">
            <label for="asunto">Asunto</label>
            <input type="text" id="asunto" maxlength="200" placeholder="Ej: Cambio en el horario de la próxima aplicación">
        </div>

        <div class="campo">
            <label for="cuerpo">Mensaje</label>
            <textarea id="cuerpo" placeholder="Escribí el anuncio. Los saltos de línea se respetan tal cual en el correo."></textarea>
        </div>

        <div class="campo">
            <label for="adjuntosInput">Adjuntos (opcional — imágenes, PDF, etc.)</label>
            <input type="file" id="adjuntosInput" multiple>
            <div class="adjuntos-lista" id="adjuntosLista"></div>
            <div class="peso-total" id="pesoTotal"></div>
        </div>

        <div class="campo" style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;">
            <input type="checkbox" id="notificarSistema" checked style="width:auto;">
            <label for="notificarSistema" style="margin:0;">Publicar también como notificación en el sistema (la campanita, para todo el personal)</label>
        </div>
        <div class="campo" id="campoNivel">
            <label for="nivelAviso">Importancia de la notificación</label>
            <select id="nivelAviso">
                <option value="baja">Informativo</option>
                <option value="media" selected>Importante</option>
                <option value="alta">Urgente</option>
            </select>
        </div>
    </div>

    <div class="tarjeta">
        <h2><i class="fa-solid fa-users"></i> Destinatarios</h2>
        <p style="font-size:.82rem;color:var(--gray-500);margin-bottom:1rem;">
            Es la misma lista del directorio de <strong>Funcionarios PAA</strong>. Por defecto se le manda a
            todos — destildá a quien no corresponda (por ejemplo, alguien que ya no es parte del equipo).
        </p>

        <div class="destinatarios-barra">
            <input type="search" id="buscarDestinatario" placeholder="Buscar por nombre o área…">
            <button class="btn-secundario btn-chico" id="btnMarcarTodos"><i class="fa-solid fa-check-double"></i> Marcar todos</button>
            <button class="btn-secundario btn-chico" id="btnDesmarcarTodos"><i class="fa-regular fa-square"></i> Desmarcar todos</button>
            <span class="destinatarios-contador" id="contadorDestinatarios"></span>
        </div>

        <div class="destinatarios-lista" id="listaDestinatarios">
            <div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
        </div>
        <div class="vacio oculta" id="sinResultadosDestinatarios">No hay nadie que coincida con esa búsqueda.</div>
    </div>

    <div class="tarjeta">
        <div class="acciones" style="margin-top:0;">
            <button class="btn-primario" id="btnEnviar">
                <i class="fa-solid fa-paper-plane"></i> Enviar anuncio
            </button>
            <button class="btn-secundario" id="btnEnviarPrueba">
                <i class="fa-solid fa-flask"></i> Enviar prueba a desarrolladores
            </button>
            <button class="btn-secundario" id="btnSoloNotificar">
                <i class="fa-solid fa-bullhorn"></i> Publicar solo como notificación, sin correo
            </button>
            <span id="estadoEnvio" style="font-size:.85rem;color:var(--gray-500);"></span>
        </div>
        <div class="nota">
            El correo sale con el mismo formato para todos: encabezado del PAA, tu asunto y tu mensaje debajo.
            Se manda uno por uno a cada destinatario (no en copia), y queda registrado en la bitácora de correos
            del sistema.<br><br>
            <strong>Enviar prueba</strong> manda el borrador tal cual está (con los mismos adjuntos) solo a las
            cuentas de desarrollo, con "[PRUEBA]" en el asunto — no cuenta como el envío real ni le llega a
            ningún funcionario.<br><br>
            <strong>Publicar solo como notificación</strong> usa el mismo asunto y mensaje, pero no manda
            ningún correo: aparece únicamente en la campanita de notificaciones (asunto → título, mensaje →
            texto del aviso), para algo que no necesita llegar al buzón de cada quien.
        </div>
    </div>

    <div class="tarjeta">
        <h2><i class="fa-regular fa-clock"></i> Anuncios anteriores</h2>
        <div class="tabla-scroll">
            <table>
                <thead><tr>
                    <th>Fecha</th><th>Asunto</th><th>Enviados</th>
                </tr></thead>
                <tbody id="tablaHistorial">
                    <tr><td colspan="3" class="vacio">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
const API = '../api/anuncios.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

let personas = [];
let adjuntos = []; // { nombre, tipo, contenido (base64), peso }

const PESO_MAXIMO = 5 * 1024 * 1024; // 5 MB, igual que el límite del servidor

document.addEventListener('DOMContentLoaded', () => {
    cargarDestinatarios();
    cargarHistorial();

    document.getElementById('adjuntosInput').addEventListener('change', agregarAdjuntos);
    document.getElementById('buscarDestinatario').addEventListener('input', filtrarDestinatarios);
    document.getElementById('btnMarcarTodos').addEventListener('click', () => marcarTodos(true));
    document.getElementById('btnDesmarcarTodos').addEventListener('click', () => marcarTodos(false));
    document.getElementById('btnEnviar').addEventListener('click', enviarAnuncio);
    document.getElementById('btnEnviarPrueba').addEventListener('click', enviarPrueba);
    document.getElementById('btnSoloNotificar').addEventListener('click', publicarSoloAviso);

    const chkNotificar = document.getElementById('notificarSistema');
    const campoNivel = document.getElementById('campoNivel');
    campoNivel.hidden = !chkNotificar.checked;
    chkNotificar.addEventListener('change', () => { campoNivel.hidden = !chkNotificar.checked; });
});

// ===================== DESTINATARIOS =====================

async function cargarDestinatarios() {
    const cont = document.getElementById('listaDestinatarios');
    try {
        const r = await fetch(`${API}?accion=destinatarios`, { cache: 'no-store' });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo cargar la lista');

        personas = d.personas;

        if (!personas.length) {
            cont.innerHTML = '<div class="vacio">No hay funcionarios en el directorio todavía.</div>';
            actualizarContador();
            return;
        }

        const porArea = {};
        personas.forEach(p => { (porArea[p.area] ||= []).push(p); });

        cont.innerHTML = Object.entries(porArea).map(([area, lista]) => `
            <div class="destinatario-area">${escapar(area)}</div>
            ${lista.map(p => `
                <label class="destinatario-fila" data-nombre="${escapar((p.nombre + ' ' + area).toLowerCase())}">
                    <input type="checkbox" class="chk-destinatario" data-id="${p.id}" checked onchange="actualizarContador()">
                    <span style="flex:1;">${escapar(p.nombre)}</span>
                    <span class="correo">${escapar(p.correo || 'sin correo')}</span>
                </label>
            `).join('')}
        `).join('');

        actualizarContador();
    } catch (e) {
        cont.innerHTML = `<div class="vacio">${escapar(e.message)}</div>`;
    }
}

function filtrarDestinatarios() {
    const texto = document.getElementById('buscarDestinatario').value.trim().toLowerCase();

    document.querySelectorAll('.destinatario-fila').forEach(fila => {
        fila.classList.toggle('oculta', texto !== '' && !fila.dataset.nombre.includes(texto));
    });

    // El encabezado de área es un elemento aparte, no un padre de las filas:
    // si el filtro deja a toda un área sin nadie visible, el título se queda
    // solo colgando. Se oculta también cuando ninguna fila siguiente (hasta
    // el próximo encabezado) sobrevivió al filtro.
    let quedaAlguien = false;
    document.querySelectorAll('.destinatario-area').forEach(area => {
        let hayVisibles = false;
        let el = area.nextElementSibling;
        while (el && !el.classList.contains('destinatario-area')) {
            if (el.classList.contains('destinatario-fila') && !el.classList.contains('oculta')) {
                hayVisibles = true;
                break;
            }
            el = el.nextElementSibling;
        }
        area.classList.toggle('oculta', !hayVisibles);
        quedaAlguien = quedaAlguien || hayVisibles;
    });

    document.getElementById('sinResultadosDestinatarios').classList.toggle('oculta', quedaAlguien);
}

function marcarTodos(marcar) {
    document.querySelectorAll('.chk-destinatario').forEach(c => { c.checked = marcar; });
    actualizarContador();
}

function actualizarContador() {
    const total = document.querySelectorAll('.chk-destinatario').length;
    const marcados = document.querySelectorAll('.chk-destinatario:checked').length;
    document.getElementById('contadorDestinatarios').textContent = `${marcados} de ${total} seleccionados`;
}

function idsExcluidos() {
    return Array.from(document.querySelectorAll('.chk-destinatario:not(:checked)'))
        .map(c => Number(c.dataset.id));
}

// ===================== ADJUNTOS =====================

function agregarAdjuntos(evento) {
    const archivos = Array.from(evento.target.files || []);
    evento.target.value = ''; // permite volver a elegir el mismo archivo si se saca y se vuelve a poner

    archivos.forEach(archivo => {
        const lector = new FileReader();
        lector.onload = () => {
            // El resultado de readAsDataURL trae "data:tipo;base64," antes del
            // contenido; solo se manda lo de después de la coma.
            const base64 = String(lector.result).split(',')[1] || '';
            adjuntos.push({ nombre: archivo.name, tipo: archivo.type || 'application/octet-stream', contenido: base64, peso: archivo.size });
            pintarAdjuntos();
        };
        lector.readAsDataURL(archivo);
    });
}

function quitarAdjunto(indice) {
    adjuntos.splice(indice, 1);
    pintarAdjuntos();
}

function pintarAdjuntos() {
    const lista = document.getElementById('adjuntosLista');
    lista.innerHTML = adjuntos.map((a, i) => `
        <div class="adjunto-fila">
            <i class="fa-regular fa-file"></i>
            <span>${escapar(a.nombre)}</span>
            <span class="peso">${formatearPeso(a.peso)}</span>
            <button type="button" onclick="quitarAdjunto(${i})" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
        </div>
    `).join('');

    const total = adjuntos.reduce((n, a) => n + a.peso, 0);
    const cajaTotal = document.getElementById('pesoTotal');
    if (adjuntos.length) {
        cajaTotal.textContent = `Total: ${formatearPeso(total)} de ${formatearPeso(PESO_MAXIMO)} máximo`;
        cajaTotal.classList.toggle('excedido', total > PESO_MAXIMO);
    } else {
        cajaTotal.textContent = '';
        cajaTotal.classList.remove('excedido');
    }
}

function formatearPeso(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

// ===================== ENVIAR =====================

async function enviarAnuncio() {
    const asunto = document.getElementById('asunto').value.trim();
    const cuerpo = document.getElementById('cuerpo').value.trim();

    if (!asunto || !cuerpo) {
        mostrar('Escribí el asunto y el mensaje antes de enviar.', 'error');
        return;
    }

    const pesoAdjuntos = adjuntos.reduce((n, a) => n + a.peso, 0);
    if (pesoAdjuntos > PESO_MAXIMO) {
        mostrar('Los adjuntos pesan demasiado entre todos (máximo 5 MB en total).', 'error');
        return;
    }

    const excluidos = idsExcluidos();
    const totalDestinatarios = personas.length - excluidos.length;

    if (totalDestinatarios <= 0) {
        mostrar('No queda nadie seleccionado para mandarle el anuncio.', 'error');
        return;
    }

    if (!confirm(`Vas a mandar este correo a ${totalDestinatarios} funcionario(s).\n\n¿Confirmás el envío?`)) return;

    const boton = document.getElementById('btnEnviar');
    const estado = document.getElementById('estadoEnvio');
    boton.disabled = true;
    estado.textContent = 'Enviando… puede tardar un momento si son muchos destinatarios.';

    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'enviar',
                asunto, cuerpo,
                excluidos,
                adjuntos: adjuntos.map(a => ({ nombre: a.nombre, tipo: a.tipo, contenido: a.contenido })),
                notificar: document.getElementById('notificarSistema').checked ? '1' : '0',
                nivel: document.getElementById('nivelAviso').value,
                csrf: CSRF,
            })
        });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo enviar el anuncio');

        estado.textContent = '';

        if (d.fallidos.length) {
            mostrar(
                `Enviado a ${d.enviados} de ${d.enviados + d.fallidos.length}. Fallaron: ` +
                d.fallidos.map(f => f.nombre).join(', '),
                'info'
            );
        } else {
            mostrar(`Anuncio enviado a ${d.enviados} funcionario(s).`, 'exito');
        }

        document.getElementById('asunto').value = '';
        document.getElementById('cuerpo').value = '';
        adjuntos = [];
        pintarAdjuntos();
        cargarHistorial();
    } catch (e) {
        estado.textContent = '';
        mostrar('Error: ' + e.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

async function enviarPrueba() {
    const asunto = document.getElementById('asunto').value.trim();
    const cuerpo = document.getElementById('cuerpo').value.trim();

    if (!asunto || !cuerpo) {
        mostrar('Escribí el asunto y el mensaje antes de mandar la prueba.', 'error');
        return;
    }

    const boton = document.getElementById('btnEnviarPrueba');
    const estado = document.getElementById('estadoEnvio');
    boton.disabled = true;
    estado.textContent = 'Mandando la prueba…';

    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'enviarPrueba',
                asunto, cuerpo,
                adjuntos: adjuntos.map(a => ({ nombre: a.nombre, tipo: a.tipo, contenido: a.contenido })),
                csrf: CSRF,
            })
        });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo mandar la prueba');

        estado.textContent = '';
        mostrar(
            d.fallidos.length
                ? `Prueba enviada a ${d.enviados} desarrollador(es). Falló: ${d.fallidos.map(f => f.correo).join(', ')}`
                : `Prueba enviada a ${d.enviados} desarrollador(es). Revisá el correo — el asunto lleva "[PRUEBA]".`,
            'exito'
        );
    } catch (e) {
        estado.textContent = '';
        mostrar('Error: ' + e.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

async function publicarSoloAviso() {
    const asunto = document.getElementById('asunto').value.trim();
    const cuerpo = document.getElementById('cuerpo').value.trim();

    if (!asunto || !cuerpo) {
        mostrar('Escribí el título y el mensaje del aviso antes de publicarlo.', 'error');
        return;
    }

    if (!confirm('Esto NO manda ningún correo — solo aparece en la campanita de notificaciones para todo el personal.\n\n¿Publicar el aviso?')) return;

    const boton = document.getElementById('btnSoloNotificar');
    const estado = document.getElementById('estadoEnvio');
    boton.disabled = true;
    estado.textContent = 'Publicando…';

    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'publicarAviso',
                asunto, cuerpo,
                nivel: document.getElementById('nivelAviso').value,
                csrf: CSRF,
            })
        });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo publicar el aviso');

        estado.textContent = '';
        mostrar('Aviso publicado. Ya aparece en la campanita de notificaciones.', 'exito');
    } catch (e) {
        estado.textContent = '';
        mostrar('Error: ' + e.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================== HISTORIAL =====================

async function cargarHistorial() {
    const cuerpo = document.getElementById('tablaHistorial');
    try {
        const r = await fetch(`${API}?accion=historial`, { cache: 'no-store' });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo cargar el historial');

        if (!d.campanas.length) {
            cuerpo.innerHTML = '<tr><td colspan="3" class="vacio">Todavía no se ha mandado ningún anuncio.</td></tr>';
            return;
        }

        cuerpo.innerHTML = d.campanas.map(c => `
            <tr>
                <td>${formatearFecha(c.enviadoEn)}</td>
                <td>${escapar(c.asunto || '—')}</td>
                <td>${c.exitosos} de ${c.total}</td>
            </tr>
        `).join('');
    } catch (e) {
        cuerpo.innerHTML = `<tr><td colspan="3" class="vacio">${escapar(e.message)}</td></tr>`;
    }
}

function formatearFecha(texto) {
    if (!texto) return '—';
    const fecha = new Date(String(texto).replace(' ', 'T'));
    if (isNaN(fecha)) return texto;
    return fecha.toLocaleString('es-CR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// ===================== UTILIDADES =====================

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.textContent = mensaje;
    aviso.className = 'aviso ' + tipo;
    aviso.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (tipo === 'exito') setTimeout(() => { aviso.className = 'aviso'; }, 8000);
}

function escapar(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
