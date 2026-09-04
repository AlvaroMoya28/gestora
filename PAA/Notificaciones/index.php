<?php
/**
 * Ver todas las notificaciones.
 *
 * La campanita (includes/notificaciones.php) solo muestra lo más reciente de
 * cada tipo — acá está el historial completo de los avisos del sistema que
 * se publican desde Anuncios (con o sin correo, ver api/anuncios.php),
 * paginado de a 50.
 *
 * No es un módulo del catálogo: no tiene un permiso propio que dar o quitar,
 * es una extensión de la campanita, así que la ve exactamente quien la ve a
 * ella (todo el mundo menos personal extraordinario — ver el porqué en
 * includes/notificaciones.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_login('../');

if ($yo['rol'] === ROL_EXTRAORDINARIO) {
    header('Location: ' . base_url());
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Notificaciones · UCR</title>
    <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #0055a4; --primary-dark: #003b73; --primary-light: #2b7fc2;
            --secondary: #6EC1E4; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
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

        .app { max-width: 780px; margin: 0 auto; }

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
            background: #fff; border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg); box-shadow: var(--shadow);
            padding: 1.5rem; margin-bottom: 1.25rem;
        }

        .vacio { text-align: center; padding: 2.5rem 1rem; color: var(--gray-400); }
        .vacio i { font-size: 1.8rem; display: block; margin-bottom: .6rem; color: var(--success); }

        .aviso-fila {
            display: flex; gap: .85rem; padding: 1rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .aviso-fila:last-child { border-bottom: none; }

        .aviso-icono {
            width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
        }
        .aviso-icono.alta { background: var(--danger); }
        .aviso-icono.media { background: var(--warning); }
        .aviso-icono.baja { background: var(--gray-500); }

        .aviso-cuerpo { flex: 1; min-width: 0; }
        .aviso-titulo { font-size: .95rem; font-weight: 700; color: var(--gray-800); margin-bottom: .2rem; }
        .aviso-mensaje { font-size: .87rem; color: var(--gray-600); white-space: pre-wrap; margin-bottom: .4rem; }
        .aviso-meta { font-size: .75rem; color: var(--gray-400); }

        .paginacion {
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem; margin-top: .5rem; flex-wrap: wrap;
        }
        .paginacion .info { font-size: .8rem; color: var(--gray-500); }
        .paginas { display: flex; gap: .4rem; align-items: center; }
        .pag-btn {
            border: 1px solid var(--gray-200); background: #fff;
            border-radius: .4rem; padding: .4rem .7rem; cursor: pointer;
            font-family: inherit; font-size: .8rem; font-weight: 600; color: var(--gray-700);
        }
        .pag-btn:disabled { opacity: .4; cursor: not-allowed; }
        .pag-btn:not(:disabled):hover { background: var(--gray-100); }
        .pag-actual { font-size: .8rem; font-weight: 700; color: var(--gray-700); padding: 0 .4rem; }

        .grupo { padding: 1rem 0; border-bottom: 1px solid var(--gray-100); }
        .grupo:last-child { border-bottom: none; }

        .grupo-cab {
            display: flex; align-items: center; gap: .7rem;
            margin-bottom: .7rem; text-decoration: none;
        }
        .grupo-cab .icono {
            width: 2.2rem; height: 2.2rem; border-radius: .65rem; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
        }
        .grupo-cab.alta .icono { background: var(--danger); }
        .grupo-cab.media .icono { background: var(--warning); }
        .grupo-cab.baja .icono { background: var(--gray-500); }
        .grupo-cab .txt strong { display: block; font-size: .92rem; color: var(--gray-800); }
        .grupo-cab .txt span { font-size: .78rem; color: var(--gray-500); }

        .grupo-item {
            padding: .5rem .6rem .5rem 2.9rem;
            border-radius: .5rem; font-size: .85rem;
        }
        .grupo-item:hover { background: var(--gray-50); }
        .grupo-item .texto { color: var(--gray-800); font-weight: 600; }
        .grupo-item .detalle { color: var(--gray-500); }
        .grupo-item.nivel-alta .detalle { color: #b91c1c; }
        .grupo-item.nivel-media .detalle { color: #92400e; }

        .tabs { display: flex; gap: .5rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .tab-btn {
            border: 1px solid var(--gray-200); background: #fff; color: var(--gray-600);
            border-radius: 999px; padding: .5rem 1rem; font-family: inherit;
            font-size: .85rem; font-weight: 700; cursor: pointer;
        }
        .tab-btn.activa { background: var(--primary); border-color: var(--primary); color: #fff; }

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
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div class="logo">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA">
        </div>
        <h1>Notificaciones</h1>
        <p>Todo lo que la campanita avisa, y el historial completo de avisos del sistema</p>
    </div>

    <div class="tabs">
        <button type="button" class="tab-btn" id="tabTodo">Todo pendiente</button>
        <button type="button" class="tab-btn" id="tabAvisos">Avisos publicados</button>
    </div>

    <div class="tarjeta" id="panelTodo">
        <div id="listaTodo">
            <div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i>Cargando…</div>
        </div>
    </div>

    <div class="tarjeta" id="panelAvisos" hidden>
        <div id="lista">
            <div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i>Cargando…</div>
        </div>
        <div class="paginacion" id="paginacion" hidden></div>
    </div>

</div>

<script>
const API = '../api/notificaciones.php';
const BASE = <?= json_encode(base_url(), JSON_UNESCAPED_SLASHES) ?>;

const ETIQUETA_NIVEL = { alta: 'Urgente', media: 'Importante', baja: 'Informativo' };
const ICONO_NIVEL = { alta: 'fa-triangle-exclamation', media: 'fa-bullhorn', baja: 'fa-circle-info' };

let paginaActual = 1;
let todoCargado = false;
let avisosCargados = false;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('tabTodo').addEventListener('click', () => mostrarTab('todo'));
    document.getElementById('tabAvisos').addEventListener('click', () => mostrarTab('avisos'));
    mostrarTab('todo');
});

function mostrarTab(cual) {
    document.getElementById('tabTodo').classList.toggle('activa', cual === 'todo');
    document.getElementById('tabAvisos').classList.toggle('activa', cual === 'avisos');
    document.getElementById('panelTodo').hidden = cual !== 'todo';
    document.getElementById('panelAvisos').hidden = cual !== 'avisos';

    if (cual === 'todo' && !todoCargado) { todoCargado = true; cargarTodo(); }
    if (cual === 'avisos' && !avisosCargados) { avisosCargados = true; cargar(1); }
}

async function cargarTodo() {
    const lista = document.getElementById('listaTodo');
    lista.innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i>Cargando…</div>';

    try {
        const r = await fetch(`${API}?accion=todo`, { cache: 'no-store' });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo cargar');
        pintarTodo(d.grupos || []);
    } catch (e) {
        lista.innerHTML = `<div class="vacio">${escapar(e.message)}</div>`;
    }
}

function pintarTodo(grupos) {
    const lista = document.getElementById('listaTodo');

    if (!grupos.length) {
        lista.innerHTML = '<div class="vacio"><i class="fa-solid fa-circle-check"></i>Todo al día. No hay pendientes.</div>';
        return;
    }

    lista.innerHTML = grupos.map(g => `
        <div class="grupo">
            <a class="grupo-cab ${escapar(g.urgencia)}" href="${BASE}${escapar(g.enlace)}">
                <span class="icono"><i class="fa-solid ${escapar(g.icono)}" aria-hidden="true"></i></span>
                <span class="txt">
                    <strong>${escapar(g.titulo)}</strong>
                    <span>${escapar(g.resumen)}</span>
                </span>
            </a>
            ${g.items.map(it => `
                <div class="grupo-item nivel-${escapar(it.nivel)}">
                    <div class="texto">${escapar(it.texto)}</div>
                    <div class="detalle">${escapar(it.detalle)}</div>
                </div>
            `).join('')}
        </div>
    `).join('');
}

async function cargar(pagina) {
    const lista = document.getElementById('lista');
    lista.innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i>Cargando…</div>';

    try {
        const r = await fetch(`${API}?accion=avisos&pagina=${pagina}`, { cache: 'no-store' });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'No se pudo cargar');

        paginaActual = d.pagina;
        pintar(d);
        pintarPaginacion(d);
    } catch (e) {
        lista.innerHTML = `<div class="vacio">${escapar(e.message)}</div>`;
        document.getElementById('paginacion').hidden = true;
    }
}

function pintar(d) {
    const lista = document.getElementById('lista');

    if (!d.avisos.length) {
        lista.innerHTML = '<div class="vacio"><i class="fa-solid fa-circle-check"></i>Todavía no se ha publicado ningún aviso.</div>';
        return;
    }

    lista.innerHTML = d.avisos.map(a => `
        <div class="aviso-fila">
            <div class="aviso-icono ${escapar(a.nivel)}"><i class="fa-solid ${ICONO_NIVEL[a.nivel] || 'fa-bullhorn'}" aria-hidden="true"></i></div>
            <div class="aviso-cuerpo">
                <div class="aviso-titulo">${escapar(a.titulo)}</div>
                <div class="aviso-mensaje">${escapar(a.mensaje)}</div>
                <div class="aviso-meta">${ETIQUETA_NIVEL[a.nivel] || a.nivel} · ${escapar(a.creado_por)} · ${formatearFecha(a.creado_en)}</div>
            </div>
        </div>
    `).join('');
}

function pintarPaginacion(d) {
    const cont = document.getElementById('paginacion');

    if (d.total <= d.porPagina) {
        cont.hidden = true;
        return;
    }

    cont.hidden = false;
    const desde = d.total === 0 ? 0 : (d.pagina - 1) * d.porPagina + 1;
    const hasta = Math.min(d.pagina * d.porPagina, d.total);

    cont.innerHTML = `
        <span class="info">${desde}–${hasta} de ${d.total}</span>
        <div class="paginas">
            <button class="pag-btn" data-ir="1" ${d.pagina <= 1 ? 'disabled' : ''}>«</button>
            <button class="pag-btn" data-ir="${d.pagina - 1}" ${d.pagina <= 1 ? 'disabled' : ''}>Anterior</button>
            <span class="pag-actual">${d.pagina} / ${d.totalPaginas}</span>
            <button class="pag-btn" data-ir="${d.pagina + 1}" ${d.pagina >= d.totalPaginas ? 'disabled' : ''}>Siguiente</button>
            <button class="pag-btn" data-ir="${d.totalPaginas}" ${d.pagina >= d.totalPaginas ? 'disabled' : ''}>»</button>
        </div>
    `;

    cont.querySelectorAll('.pag-btn[data-ir]').forEach(b => {
        b.addEventListener('click', () => cargar(Number(b.dataset.ir)));
    });
}

function formatearFecha(texto) {
    if (!texto) return '—';
    const fecha = new Date(String(texto).replace(' ', 'T'));
    if (isNaN(fecha)) return texto;
    return fecha.toLocaleString('es-CR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
