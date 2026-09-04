<?php
/**
 * Guía de uso del módulo de Tickets, dirigida a quien reporta averías (no
 * a quien administra) — con la excepción de un aviso: si no puede usar el
 * sistema, cómo pedirle a administración que le cree el ticket.
 *
 * Video demostrativo hecho con HTML y CSS (pantallas simuladas, no una
 * grabación real). Todos los folios, nombres y fechas que aparecen son de
 * ejemplo.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo('tickets', '../');

/**
 * La narración por voz del navegador (speechSynthesis) se sacó: sonaba mal y
 * no se podía controlar el tono. En su lugar va un audio real, grabado
 * aparte, con un solo archivo para todo el video y una marca de en qué
 * segundo empieza cada escena.
 *
 * Mientras no haya audio, $pista queda en null y el video avanza solo, en
 * silencio, con la duración de cada escena (data-duracion). Para activar el
 * audio cuando esté listo: subir el archivo a Assets o a este mismo directorio,
 * y completar 'url' y 'tiempos' — un segundo por escena, en el mismo orden
 * en que aparecen más abajo (son 6 escenas, así que 'tiempos' necesita 6
 * números, el primero en 0).
 */
$pista = [
    'url'     => 'audio/guia-tickets.mp3',
    'tiempos' => [0.0, 12.6, 21.9, 35, 50.9, 59.3],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Guía de Tickets · UCR</title>
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
    --primary: #0055a4;
    --primary-dark: #003b73;
    --primary-light: #2b7fc2;
    --secondary: #6EC1E4;
    --secondary-light: #e3f0fa;
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
    --gray-900: #111827;
}

body {
    font-family: 'Inter', system-ui, sans-serif;
    background: var(--gray-50);
    color: var(--gray-800);
    padding: 2rem 1.25rem 4rem;
}

.gt-envoltura { max-width: 1080px; margin: 0 auto; }

.gt-volver {
    display: inline-flex; align-items: center; gap: .4rem;
    color: var(--gray-500); text-decoration: none; font-size: .85rem;
    font-weight: 600; margin-bottom: 1rem;
}
.gt-volver:hover { color: var(--primary); }

.gt-hero {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; border-radius: 16px; padding: 2rem 2.2rem;
    box-shadow: 0 6px 22px rgba(0,85,164,.22);
}
.gt-hero-badge {
    display: inline-flex; background: rgba(255,255,255,.15); border-radius: 12px;
    padding: 6px 14px; margin-bottom: .8rem;
}
.gt-hero-badge img { height: 26px; width: auto; display: block; }
.gt-hero h1 { font-size: 1.9rem; margin-bottom: .5rem; }
.gt-hero p { color: #dcecf9; max-width: 640px; font-size: .98rem; line-height: 1.5; }
.gt-hero-links { margin-top: 1.1rem; display: flex; gap: .6rem; flex-wrap: wrap; }
.gt-hero-btn {
    display: inline-block; background: #fff; color: var(--primary); padding: .55rem 1.1rem;
    border-radius: 22px; font-weight: 600; font-size: .85rem; text-decoration: none;
    border: none; cursor: pointer; font-family: inherit;
}
.gt-hero-btn:hover { background: #eaf4fd; }
.gt-hero-btn.borde { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.65); }
.gt-hero-btn.borde:hover { background: rgba(255,255,255,.15); }

.gt-h2 {
    font-size: 1.35rem; margin: 2.4rem 0 .4rem; color: var(--primary-dark);
    border-bottom: 2px solid var(--gray-200); padding-bottom: .5rem;
}
.gt-intro { color: var(--gray-500); margin-bottom: .9rem; font-size: .88rem; }

/* ---------- video simulado ---------- */
.gt-video { background: #12222f; border-radius: 14px; padding: .75rem; box-shadow: 0 8px 26px rgba(0,0,0,.16); }
.gt-marco { display: flex; align-items: center; gap: 6px; padding: 0 .4rem .55rem; }
.gt-punto { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.gt-punto.rojo { background: #ff5f57; } .gt-punto.ambar { background: #febc2e; } .gt-punto.verde { background: #28c840; }
.gt-url { margin-left: 12px; background: #1d3242; color: #8fb3ca; font-size: .68rem; padding: 3px 12px; border-radius: 10px; }

.gt-pantalla { position: relative; background: #fff; border-radius: 9px; height: 460px; overflow: hidden; }
.gt-escena {
    position: absolute; inset: 0; padding: 0; opacity: 0; visibility: hidden; transition: opacity .35s;
    overflow: auto;
}
.gt-escena.activa { opacity: 1; visibility: visible; }
.gt-lienzo { padding: 1.6rem 1.8rem; font-size: .92rem; color: var(--gray-800); }

.gt-cortina {
    position: absolute; inset: 0; z-index: 30; background: rgba(9,20,29,.9);
    display: flex; align-items: center; justify-content: center; border-radius: 9px; cursor: pointer;
}
.gt-play-grande {
    background: none; border: none; cursor: pointer; font-family: inherit; padding: 0;
    display: flex; flex-direction: column; align-items: center; gap: 18px; color: #fff;
}
.gt-play-icono {
    width: 80px; height: 80px; border-radius: 50%; background: var(--primary-light); color: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 30px rgba(0,0,0,.5), 0 0 0 10px rgba(43,127,194,.16);
    transition: transform .2s;
}
.gt-play-grande:hover .gt-play-icono { transform: scale(1.08); }
.gt-play-caja {
    background: rgba(6,15,22,.72); border: 1px solid rgba(255,255,255,.12); border-radius: 12px;
    padding: 11px 22px; text-align: center;
}
.gt-play-txt { font-size: 16px; font-weight: 700; }
.gt-play-nota { font-size: 12px; color: #a8c6dc; display: block; margin-top: 3px; }

.gt-barra { height: 4px; background: #24394a; border-radius: 3px; margin: .7rem .15rem .55rem; overflow: hidden; }
.gt-progreso { height: 100%; width: 0; background: linear-gradient(90deg,#3fa9f5,#7ed0ff); border-radius: 3px; }
.gt-controles { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; padding: 0 .2rem; }
.gt-ctrl {
    background: #24394a; color: #d5e6f2; border: none; border-radius: 16px; padding: .4rem .85rem;
    font-size: .74rem; cursor: pointer; font-family: inherit;
}
.gt-ctrl:hover { background: #31506a; color: #fff; }
.gt-ctrl.encendido { background: var(--success); color: #fff; font-weight: 700; }
.gt-paso { color: #8fb3ca; font-size: .7rem; margin-left: 2px; }
.gt-chips { display: flex; gap: 4px; flex-wrap: wrap; margin-left: auto; }
.gt-chip {
    width: 19px; height: 19px; border-radius: 50%; background: #24394a; color: #7f9db4; border: none;
    font-size: .62rem; cursor: pointer; font-family: inherit; line-height: 19px; padding: 0;
}
.gt-chip:hover { background: #31506a; color: #fff; }
.gt-chip.activo { background: #3fa9f5; color: #08283f; font-weight: 700; }
.gt-vel-grupo { display: inline-flex; gap: 3px; margin-left: 6px; }
.gt-vel.activo { background: #3fa9f5; color: #08283f; font-weight: 700; }
.gt-vol-grupo { display: inline-flex; align-items: center; gap: 6px; background: #1b2f3f; border-radius: 16px; padding: 3px 10px 3px 3px; }
.gt-vol-barra { width: 76px; height: 4px; accent-color: #3fa9f5; cursor: pointer; }
.gt-narracion { margin-top: .6rem; background: #0b1922; color: #cfe3f2; border-radius: 8px; padding: .65rem .9rem; font-size: .82rem; min-height: 40px; line-height: 1.5; }

/* ---------- "pantallas" que se simulan dentro del video ---------- */
.sv-h1 { font-size: 1.35rem; font-weight: 700; margin-bottom: .2rem; color: var(--gray-900); }
.sv-h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: .5rem; color: var(--gray-900); }
.sv-sub { font-size: .8rem; color: var(--gray-500); margin-bottom: .9rem; }
.sv-hero {
    background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff;
    border-radius: 10px; padding: 1rem 1.3rem; margin-bottom: 1rem;
}
.sv-hero .sv-h1 { color: #fff; }
.sv-hero p { color: #dcecf9; font-size: .8rem; }

.sv-campo { margin-bottom: .8rem; }
.sv-label { display: block; font-size: .78rem; font-weight: 600; color: var(--gray-700); margin-bottom: .3rem; }
.sv-input, .sv-select {
    border: 1.5px solid var(--gray-300); border-radius: 7px; padding: .55rem .8rem;
    font-size: .82rem; color: var(--gray-700); background: var(--gray-50); position: relative;
}
.sv-select { display: flex; justify-content: space-between; align-items: center; }
.sv-select.foco, .sv-input.foco { border-color: var(--primary-light); background: #fff; box-shadow: 0 0 0 3px rgba(43,127,194,.15); }
.sv-textarea { min-height: 60px; }
.sv-ph { color: var(--gray-400); }

.sv-lista-desp {
    border: 1.5px solid var(--primary-light); border-radius: 7px; margin-top: 4px; overflow: hidden;
    box-shadow: 0 5px 14px rgba(0,0,0,.09); background: #fff;
}
.sv-op { padding: .55rem .8rem; font-size: .8rem; border-bottom: 1px solid var(--gray-100); display: flex; gap: .5rem; align-items: center; }
.sv-op:last-child { border-bottom: none; }
.sv-op i { color: var(--primary-light); width: 16px; text-align: center; }
.sv-op.elegida { background: var(--secondary-light); font-weight: 600; }

.sv-btn { display: inline-block; padding: .5rem 1rem; border-radius: 7px; font-size: .78rem; font-weight: 600; color: #fff; }
.sv-btn.azul { background: var(--primary); }
.sv-btn.gris { background: var(--gray-300); color: var(--gray-700); }
.sv-btn.rojo { background: var(--danger); }
.sv-btn.rojo-borde { background: #fff; color: var(--danger); border: 1.5px solid var(--danger); }
.sv-btn.verde { background: var(--success); }

.sv-aviso {
    font-size: .78rem; padding: .65rem .9rem; border-radius: 8px; margin-top: .7rem;
    background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;
}

.sv-contadores { display: flex; gap: .5rem; margin-bottom: .8rem; flex-wrap: wrap; }
.sv-contador { padding: .3rem .75rem; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.sv-contador.abierto { background: #fee2e2; color: #991b1b; }
.sv-contador.en_proceso { background: #fef3c7; color: #92400e; }
.sv-contador.resuelto { background: #d1fae5; color: #065f46; }
.sv-contador.cerrado { background: var(--gray-100); color: var(--gray-500); }

.sv-ticket { border: 1px solid var(--gray-200); border-radius: 10px; padding: .85rem 1rem; margin-bottom: .6rem; background: #fff; }
.sv-ticket-cab { display: flex; justify-content: space-between; gap: .8rem; }
.sv-folio { font-size: .68rem; color: var(--gray-400); font-weight: 700; letter-spacing: .03em; }
.sv-titulo { font-weight: 700; color: var(--gray-900); font-size: .92rem; margin: .1rem 0 .3rem; }
.sv-meta { display: flex; gap: .8rem; flex-wrap: wrap; font-size: .72rem; color: var(--gray-500); }
.sv-meta span { display: inline-flex; align-items: center; gap: .3rem; }
.sv-etq { display: inline-block; padding: .2rem .6rem; border-radius: 20px; font-size: .68rem; font-weight: 700; margin-left: .35rem; }
.sv-etq.abierto { background: #fee2e2; color: #991b1b; }
.sv-etq.en_proceso { background: #fef3c7; color: #92400e; }
.sv-etq.resuelto { background: #d1fae5; color: #065f46; }
.sv-etq.cerrado { background: var(--gray-100); color: var(--gray-500); }
.sv-etq.p-baja { background: var(--gray-100); color: var(--gray-600); }
.sv-etq.p-media { background: #dbeafe; color: #1e40af; }
.sv-etq.p-alta { background: #fef3c7; color: #92400e; }
.sv-etq.p-critica { background: #fee2e2; color: #991b1b; }
.sv-desc { font-size: .8rem; color: var(--gray-600); margin-top: .5rem; }

.sv-modal { border: 1px solid var(--gray-200); border-radius: 12px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: 1rem 1.2rem; }
.sv-comentario { border-bottom: 1px solid var(--gray-100); padding: .55rem 0; font-size: .8rem; }
.sv-comentario-meta { display: flex; justify-content: space-between; font-size: .68rem; color: var(--gray-400); margin-bottom: .15rem; }

.sv-acciones { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .8rem; }

.sv-persona { display: flex; align-items: center; gap: .6rem; background: var(--gray-50); border-radius: 8px; padding: .5rem .7rem; margin-top: .4rem; }
.sv-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; }

.sv-dialogo { margin: 1rem auto 0; max-width: 380px; background: #fff; border-radius: 10px; box-shadow: 0 10px 32px rgba(0,0,0,.26); padding: 1rem 1.1rem; border: 1px solid var(--gray-200); }
.sv-dialogo-tit { font-size: .8rem; font-weight: 700; color: var(--gray-900); margin-bottom: .35rem; }
.sv-dialogo-txt { font-size: .76rem; color: var(--gray-600); margin-bottom: .8rem; }
.sv-dialogo-btns { display: flex; gap: .5rem; justify-content: flex-end; }

.sv-nota-azul { border-left: 4px solid var(--primary); background: var(--secondary-light); padding: .7rem .9rem; font-size: .78rem; color: #1e4a6e; margin-bottom: .7rem; border-radius: 0 6px 6px 0; }

/* animaciones de entrada */
@keyframes svSubir { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@keyframes svLatir { 0%,100% { box-shadow: 0 0 0 0 rgba(43,127,194,.5); } 50% { box-shadow: 0 0 0 9px rgba(43,127,194,0); } }
.sv-a1, .sv-a2, .sv-a3, .sv-a4 { opacity: 0; }
.gt-escena.activa .sv-a1 { animation: svSubir .45s .05s both; }
.gt-escena.activa .sv-a2 { animation: svSubir .45s .40s both; }
.gt-escena.activa .sv-a3 { animation: svSubir .45s .80s both; }
.gt-escena.activa .sv-a4 { animation: svSubir .45s 1.25s both; }
.gt-escena.activa .sv-latir { animation: svLatir 1.7s 1.1s 3; }

/* ---------- ampliado ---------- */
.gt-video.gt-expandido {
    position: fixed !important; inset: 0 !important; width: 100vw !important; height: 100vh !important;
    z-index: 99999; border-radius: 0; margin: 0; padding: 1rem 1.5rem; box-sizing: border-box;
    overflow: auto; background: #12222f;
}
.gt-video.gt-expandido .gt-pantalla { max-width: 1100px; margin: 0 auto; }
.gt-video.gt-expandido .gt-marco, .gt-video.gt-expandido .gt-barra,
.gt-video.gt-expandido .gt-controles, .gt-video.gt-expandido .gt-narracion { max-width: 1100px; margin-left: auto; margin-right: auto; }
body.gt-sin-scroll { overflow: hidden; }

/* ---------- pasos ---------- */
.gt-pasos { list-style: none; counter-reset: gt; padding: 0; margin-top: 1rem; }
.gt-pasos li {
    counter-increment: gt; position: relative; padding: .1rem 0 1rem 3.1rem;
    border-left: 2px solid var(--gray-200); margin-left: 1.1rem;
}
.gt-pasos li:last-child { border-left-color: transparent; }
.gt-pasos li::before {
    content: counter(gt); position: absolute; left: -18px; top: 0; width: 32px; height: 32px;
    border-radius: 50%; background: var(--primary); color: #fff; font-weight: 700; font-size: .85rem;
    text-align: center; line-height: 32px; box-shadow: 0 0 0 4px var(--gray-50);
}
.gt-pasos h3 { font-size: .98rem; margin-bottom: .2rem; color: var(--gray-900); }
.gt-pasos p { color: var(--gray-600); font-size: .85rem; }

/* ---------- reglas ---------- */
.gt-reglas { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .75rem; margin-top: 1rem; }
.gt-regla { background: #fff; border: 1px solid var(--gray-200); border-radius: 11px; padding: .9rem 1rem; }
.gt-regla h3 { margin-bottom: .3rem; font-size: .88rem; font-weight: 600; color: var(--primary-dark); }
.gt-regla p { margin: 0; font-size: .8rem; color: var(--gray-600); }

/* ---------- faq ---------- */
.gt-buscador { margin: 1rem 0 .8rem; display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; }
.gt-buscador input {
    flex: 1; min-width: 240px; border: 1.5px solid var(--gray-300); border-radius: 24px;
    padding: .65rem 1.1rem; font-size: .85rem; font-family: inherit; color: var(--gray-800); background: #fff;
}
.gt-buscador input:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(43,127,194,.15); }
#gtResultado { font-size: .76rem; color: var(--gray-500); }

.gt-faq details {
    background: #fff; border: 1px solid var(--gray-200); border-radius: 10px; margin-bottom: .5rem;
    padding: 0 1rem; transition: box-shadow .2s;
}
.gt-faq details[open] { box-shadow: 0 3px 12px rgba(0,85,164,.09); border-color: var(--secondary); }
.gt-faq summary { cursor: pointer; padding: .8rem 0; font-weight: 600; color: var(--gray-900); font-size: .86rem; list-style: none; position: relative; padding-right: 1.6rem; }
.gt-faq summary::-webkit-details-marker { display: none; }
.gt-faq summary::after { content: "+"; position: absolute; right: 2px; top: .6rem; font-size: 1.2rem; color: var(--primary-light); font-weight: 400; }
.gt-faq details[open] summary::after { content: "−"; }
.gt-faq details p { margin: 0 0 .85rem; color: var(--gray-600); font-size: .84rem; }
.gt-faq details.gt-oculta { display: none; }
.gt-vacio { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; border-radius: 9px; padding: .8rem 1rem; font-size: .82rem; }

.gt-cierre { margin-top: 2.2rem; background: var(--secondary-light); border-radius: 12px; padding: 1.4rem 1.6rem; text-align: center; }
.gt-cierre p { margin-bottom: .8rem; color: #1e4a6e; }
.gt-cierre .gt-hero-btn { background: var(--primary); color: #fff; }
.gt-cierre .gt-hero-btn:hover { background: var(--primary-dark); }

@media (max-width: 700px) {
    body { padding: 1.2rem .8rem 3rem; }
    .gt-pantalla { height: 560px; }
    .gt-hero h1 { font-size: 1.4rem; }
    .gt-chips { margin-left: 0; }
}
@media (prefers-reduced-motion: reduce) {
    .gt-escena.activa * { animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
}
@media print {
    .gt-video, .gt-hero-links, .gt-buscador, .gt-cierre, .gt-intro, .gt-volver { display: none !important; }
    body { background: #fff !important; padding: 0 !important; }
    .gt-hero { background: none !important; color: #000 !important; box-shadow: none !important; padding: 0 0 .6rem !important; border-bottom: 2px solid #000; border-radius: 0 !important; }
    .gt-hero h1, .gt-hero p, .gt-hero-badge { color: #000 !important; }
    .gt-h2 { color: #000 !important; page-break-after: avoid; }
    .gt-pasos li, .gt-regla, .gt-faq details { page-break-inside: avoid; }
    .gt-faq details { border: 1px solid #999 !important; box-shadow: none !important; }
    .gt-faq summary::after { content: "" !important; }
}
</style>
</head>
<body>
<div class="gt-envoltura">

    <a class="gt-volver" href="index.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver a Tickets</a>

    <div class="gt-hero">
        <div class="gt-hero-badge"><img src="../assets/logo-paa-blanco.png" alt="Gestión PAA"></div>
        <h1>Guía del módulo de Tickets</h1>
        <p>Cómo reportar una avería, darle seguimiento y, si administrás el sistema, cómo asignarla,
           cambiarle el estado y cerrarla. Con video paso a paso, instrucciones y preguntas frecuentes.</p>
        <div class="gt-hero-links">
            <a class="gt-hero-btn" href="#gt-video">Ver el video</a>
            <a class="gt-hero-btn borde" href="#gt-pasos">Instrucciones</a>
            <a class="gt-hero-btn borde" href="#gt-faq">Preguntas frecuentes</a>
            <button type="button" class="gt-hero-btn borde" id="gtImprimir">Imprimir la guía</button>
        </div>
    </div>

    <h2 class="gt-h2" id="gt-video">Video demostrativo</h2>
    <p class="gt-intro">Se repite solo. Podés pausarlo, reiniciarlo o saltar directamente a un paso.
       Los folios y nombres que aparecen son inventados, para el ejemplo.</p>

    <div class="gt-video" id="gtVideo">
        <div class="gt-marco">
            <span class="gt-punto rojo"></span><span class="gt-punto ambar"></span><span class="gt-punto verde"></span>
            <span class="gt-url">tickets.paa.ucr.ac.cr</span>
        </div>

        <div class="gt-pantalla" id="gtPantalla">

            <div class="gt-cortina" id="gtCortina">
                <button type="button" class="gt-play-grande" id="gtPlayGrande" aria-label="Reproducir el video">
                    <span class="gt-play-icono">
                        <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true"><path d="M8 5.14v13.72L19 12z" fill="currentColor"></path></svg>
                    </span>
                    <span class="gt-play-caja">
                        <span class="gt-play-txt">Reproducir el video</span>
                        <span class="gt-play-nota">Dura poco más de un minuto</span>
                    </span>
                </button>
            </div>

            <!-- Escena 1: reportar, tipo de avería -->
            <div class="gt-escena activa" aria-hidden="true" data-duracion="11000"
                 data-narracion="Para reportar una avería, entre al módulo de Tickets. Elija el tipo: Avería de equipo, Problema de red, Equipo de cómputo, u Otro si no encaja en las anteriores.">
                <div class="gt-lienzo">
                    <div class="sv-hero sv-a1">
                        <div class="sv-h1">Ticket de Avería</div>
                        <p>Reporte y seguimiento de averías de soporte informático dentro del PAA</p>
                    </div>
                    <div class="sv-h2 sv-a2">Reportar una avería</div>
                    <div class="sv-campo sv-a3">
                        <span class="sv-label">Tipo de avería</span>
                        <div class="sv-select foco">Avería de equipo <i class="fa-solid fa-chevron-down" style="font-size:.65rem;"></i></div>
                        <div class="sv-lista-desp">
                            <div class="sv-op elegida"><i class="fa-solid fa-triangle-exclamation"></i> Avería de equipo</div>
                            <div class="sv-op"><i class="fa-solid fa-wifi"></i> Problema de red</div>
                            <div class="sv-op"><i class="fa-solid fa-desktop"></i> Equipo de cómputo</div>
                            <div class="sv-op"><i class="fa-solid fa-circle-question"></i> Otro</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Escena 2: resumen y descripción -->
            <div class="gt-escena" aria-hidden="true" data-duracion="10000"
                 data-narracion="Escriba un resumen corto y describa con más detalle qué pasó. Cuanta más información deje, más rápido se puede atender.">
                <div class="gt-lienzo">
                    <div class="sv-h2 sv-a1">Reportar una avería</div>
                    <div class="sv-campo sv-a1">
                        <span class="sv-label">Tipo de avería</span>
                        <div class="sv-input">Avería de equipo</div>
                    </div>
                    <div class="sv-campo sv-a2">
                        <span class="sv-label">Resumen</span>
                        <div class="sv-input foco">El proyector del aula 3 no enciende</div>
                    </div>
                    <div class="sv-campo sv-a3">
                        <span class="sv-label">Descripción</span>
                        <div class="sv-input sv-textarea">Se probó el cable y el interruptor de pared, pero la lámpara no prende. Ya se avisó a la persona coordinadora de la sede.</div>
                    </div>
                    <div class="sv-a4"><span class="sv-btn azul sv-latir"><i class="fa-solid fa-paper-plane"></i> Reportar avería</span></div>
                </div>
            </div>

            <!-- Escena 3: folio generado -->
            <div class="gt-escena" aria-hidden="true" data-duracion="9000"
                 data-narracion="Al enviarla, el sistema genera un folio único, como TK-20260828-001. Guárdelo: sirve para dar seguimiento a esa avería.">
                <div class="gt-lienzo">
                    <div class="sv-h2 sv-a1">Reportar una avería</div>
                    <div class="sv-aviso sv-a2"><i class="fa-solid fa-circle-check"></i>
                        Avería registrada con el folio <b>TK-20260828-001</b></div>
                    <div class="sv-nota-azul sv-a3">
                        El folio se genera solo, con la fecha y un número correlativo. No hace falta
                        escribirlo ni inventarlo: aparece apenas se envía el reporte.
                    </div>
                </div>
            </div>

            <!-- Escena 4: mis averías, contadores y colores -->
            <div class="gt-escena" aria-hidden="true" data-duracion="12000"
                 data-narracion="En Mis averías puede ver todo lo que ha reportado. Los números de arriba filtran por estado, y el color de cada etiqueta indica en qué va: rojo es abierto, amarillo en proceso, verde resuelto, y gris cerrado.">
                <div class="gt-lienzo">
                    <div class="sv-h2 sv-a1">Mis averías</div>
                    <div class="sv-contadores sv-a2">
                        <span class="sv-contador abierto">2 Abierto</span>
                        <span class="sv-contador en_proceso">1 En proceso</span>
                        <span class="sv-contador resuelto">3 Resuelto</span>
                        <span class="sv-contador cerrado">5 Cerrado</span>
                    </div>
                    <div class="sv-ticket sv-a3">
                        <div class="sv-ticket-cab">
                            <div>
                                <div class="sv-folio">TK-20260828-001</div>
                                <div class="sv-titulo">El proyector del aula 3 no enciende</div>
                                <div class="sv-meta">
                                    <span><i class="fa-solid fa-triangle-exclamation"></i> Avería de equipo</span>
                                    <span><i class="fa-regular fa-clock"></i> hace 2 minutos</span>
                                </div>
                            </div>
                            <div><span class="sv-etq abierto">Abierto</span></div>
                        </div>
                    </div>
                    <div class="sv-ticket sv-a4">
                        <div class="sv-ticket-cab">
                            <div>
                                <div class="sv-folio">TK-20260821-004</div>
                                <div class="sv-titulo">No hay señal de internet en la sala de cómputo</div>
                                <div class="sv-meta"><span><i class="fa-solid fa-wifi"></i> Problema de red</span></div>
                            </div>
                            <div><span class="sv-etq en_proceso">En proceso</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Escena 5: detalle y comentarios -->
            <div class="gt-escena" aria-hidden="true" data-duracion="11000"
                 data-narracion="Al abrir una avería puede ver quién la reportó y el historial de comentarios, y agregar uno nuevo si hace falta más información.">
                <div class="gt-lienzo">
                    <div class="sv-modal sv-a1">
                        <div class="sv-folio">TK-20260821-004</div>
                        <div class="sv-titulo">No hay señal de internet en la sala de cómputo</div>
                        <div style="margin:.5rem 0;"><span class="sv-etq en_proceso">En proceso</span> <span class="sv-etq p-alta">Alta</span></div>
                        <div class="sv-desc sv-a2">Ningún equipo de la sala logra conectarse a la red desde ayer en la tarde.</div>
                        <div style="margin-top:.8rem;font-weight:700;font-size:.8rem;" class="sv-a3">Historial</div>
                        <div class="sv-comentario sv-a3">
                            <div class="sv-comentario-meta"><span>Soporte Informático · cambió el estado a En proceso</span><span>hoy, 9:14 a.m.</span></div>
                            <div>Ya se revisó el switch de la sala, se está esperando un repuesto.</div>
                        </div>
                        <div class="sv-campo sv-a4" style="margin-top:.6rem;">
                            <div class="sv-input foco" style="display:flex;justify-content:space-between;">
                                <span class="sv-ph">Agregar un comentario...</span>
                                <i class="fa-solid fa-paper-plane" style="color:var(--primary-light);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Escena 6: si no puede usar el sistema -->
            <div class="gt-escena" aria-hidden="true" data-duracion="11000"
                 data-narracion="Si usted no puede entrar al sistema, o prefiere reportarlo de otra forma, puede acudir con administración: le cuenta qué pasó, y ellos le crean el ticket a su nombre. Le va a quedar registrado igual, con su folio, para que le puedan dar seguimiento.">
                <div class="gt-lienzo">
                    <div class="sv-h2 sv-a1">¿No puede reportarlo usted mismo?</div>
                    <div class="sv-persona sv-a2">
                        <div class="sv-avatar"><i class="fa-solid fa-user"></i></div>
                        <div>
                            <div style="font-weight:700;font-size:.85rem;">Acuda con administración</div>
                            <div style="font-size:.78rem;color:var(--gray-500);">Soporte Informático</div>
                        </div>
                    </div>
                    <div class="sv-nota-azul sv-a3">
                        Cuéntele a alguien de administración qué averío pasó. Ellos pueden reportarla
                        a su nombre desde el sistema.
                    </div>
                    <div class="sv-aviso sv-a4"><i class="fa-solid fa-circle-check"></i>
                        Igual le queda un folio propio, como TK-20260828-002, para darle seguimiento.</div>
                </div>
            </div>

        </div>

        <div class="gt-barra"><div class="gt-progreso" id="gtProgreso"></div></div>

        <div class="gt-controles">
            <button type="button" class="gt-ctrl" id="gtPlay" aria-label="Reproducir o pausar el video">▶ Reproducir</button>
            <button type="button" class="gt-ctrl" id="gtReiniciar" aria-label="Reiniciar el video">↺ Reiniciar</button>
            <button type="button" class="gt-ctrl" id="gtExpandir" aria-label="Ampliar o reducir el video">⛶ Ampliar</button>

            <span class="gt-vol-grupo" id="gtVolGrupo" hidden>
                <button type="button" class="gt-ctrl" id="gtSilenciar" aria-label="Silenciar o restablecer el sonido">🔊</button>
                <input type="range" class="gt-vol-barra" id="gtVolumen" min="0" max="100" step="5" value="100" aria-label="Volumen del audio">
            </span>

            <span class="gt-paso" id="gtPaso">Paso 1 de 6</span>
            <span class="gt-vel-grupo">
                <button type="button" class="gt-ctrl gt-vel" data-vel="0.6" aria-label="Velocidad lenta">Lento</button>
                <button type="button" class="gt-ctrl gt-vel activo" data-vel="1" aria-label="Velocidad normal">Normal</button>
                <button type="button" class="gt-ctrl gt-vel" data-vel="1.6" aria-label="Velocidad rápida">Rápido</button>
            </span>
            <div class="gt-chips" id="gtChips"></div>
        </div>

        <div class="gt-narracion" id="gtNarracion" aria-live="polite" aria-atomic="true"></div>
        <audio id="gtAudio" preload="metadata"></audio>
    </div>

    <script>
        window.gtPista = <?php echo json_encode($pista, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>

    <h2 class="gt-h2" id="gt-pasos">Instrucciones paso a paso</h2>

    <ol class="gt-pasos">
        <li>
            <h3>Entre al módulo de Tickets</h3>
            <p>Desde el portal, abra <b>Tickets</b>. Va a ver primero el formulario para reportar una
               avería nueva.</p>
        </li>
        <li>
            <h3>Elija el tipo de avería</h3>
            <p>Hay cuatro opciones: <b>Avería de equipo</b>, <b>Problema de red</b>, <b>Equipo de cómputo</b>,
               y <b>Otro</b> para lo que no encaje en las anteriores.</p>
        </li>
        <li>
            <h3>Escriba el resumen y la descripción</h3>
            <p>El resumen es una frase corta, como un título. La descripción es donde se cuenta con
               más detalle qué pasó — entre más específico, más rápido se puede atender.</p>
        </li>
        <li>
            <h3>Envíe el reporte y guarde el folio</h3>
            <p>Al pulsar <b>Reportar avería</b>, el sistema genera un folio único (por ejemplo
               <b>TK-20260828-001</b>). Con ese folio se identifica el ticket en todo momento.</p>
        </li>
        <li>
            <h3>Revise "Mis averías"</h3>
            <p>Ahí aparecen todas las averías que usted ha reportado, con su estado marcado por color.
               Los contadores de arriba también sirven para filtrar.</p>
        </li>
        <li>
            <h3>Agregue comentarios si hace falta</h3>
            <p>Al abrir una avería puede ver el historial completo y escribir un comentario nuevo,
               por ejemplo para dar más información o preguntar en qué va.</p>
        </li>
        <li>
            <h3>Espere el aviso por correo</h3>
            <p>Cada vez que quien administra cambia el estado de su avería, le llega un correo
               automático avisándole. No hace falta estar revisando la página.</p>
        </li>
        <li>
            <h3>Marque como cerrado cuando ya esté solucionada</h3>
            <p>Si el problema ya se resolvió, usted mismo puede marcar su avería como <b>cerrada</b>
               desde la lista.</p>
        </li>
        <li>
            <h3>Si no puede usar el sistema, pida ayuda</h3>
            <p>Cuéntele a alguien de administración qué averío pasó. Ellos pueden crear el ticket a
               nombre suyo, y le va a quedar registrado igual, con su propio folio, para darle
               seguimiento.</p>
        </li>
    </ol>

    <h2 class="gt-h2">Algo que conviene tener presente</h2>

    <div class="gt-reglas">
        <div class="gt-regla">
            <h3>El folio es lo importante</h3>
            <p>Guárdelo o anótelo. Con ese número se identifica su avería en cualquier momento,
               aunque haya reportado varias.</p>
        </div>
        <div class="gt-regla">
            <h3>Usted no elige la prioridad</h3>
            <p>Quién tan urgente es atender su avería lo define administración, no quien reporta.
               No hace falta pedirlo con "urgente" en el resumen.</p>
        </div>
        <div class="gt-regla">
            <h3>Los cambios de estado avisan solos</h3>
            <p>No hace falta estar revisando la página: cada vez que su avería cambia de estado, le
               llega un correo automático.</p>
        </div>
        <div class="gt-regla">
            <h3>Solo ve sus propias averías</h3>
            <p>La lista "Mis averías" muestra únicamente lo que usted ha reportado, no lo de los
               demás funcionarios.</p>
        </div>
        <div class="gt-regla">
            <h3>¿No puede reportarlo usted mismo?</h3>
            <p>Acuda con administración: ellos pueden crear el ticket a su nombre, y le queda con su
               propio folio para darle seguimiento igual.</p>
        </div>
    </div>

    <h2 class="gt-h2" id="gt-faq">Preguntas frecuentes</h2>

    <div class="gt-buscador">
        <label for="gtBuscar" class="sr-only">Buscar en las preguntas frecuentes</label>
        <input type="text" id="gtBuscar" placeholder="Escriba una palabra para encontrar su pregunta…" autocomplete="off">
        <span id="gtResultado" aria-live="polite"></span>
    </div>

    <div class="gt-faq" id="gtFaq">

        <details>
            <summary>¿Qué tipos de avería puedo elegir?</summary>
            <p>Solo hay cuatro: <b>Avería de equipo</b>, <b>Problema de red</b>, <b>Equipo de cómputo</b> y
               <b>Otro</b>. Si su avería no encaja claramente en ninguna, use "Otro" y descríbala bien.</p>
        </details>

        <details>
            <summary>¿Para qué sirve el folio?</summary>
            <p>Es el identificador único de cada avería, con el formato TK-AAAAMMDD-000. Sirve para
               dar seguimiento, y aparece automáticamente al reportar — no hay que escribirlo.</p>
        </details>

        <details>
            <summary>¿Puedo cambiar la prioridad de mi propia avería?</summary>
            <p>No. La prioridad la define quien administra el sistema, según qué tan urgente sea
               atenderla, no quien la reporta.</p>
        </details>

        <details>
            <summary>¿Me avisan cuando mi avería cambia de estado?</summary>
            <p>Sí. Cada vez que el estado cambia (por ejemplo, de "Abierto" a "En proceso" o a
               "Resuelto"), le llega un correo automático a la dirección con la que reportó.</p>
        </details>

        <details>
            <summary>¿Puedo cerrar yo mismo un ticket que reporté?</summary>
            <p>Sí. Desde "Mis averías" tiene el botón <b>Marcar como cerrado</b> para cualquier avería
               propia que ya no esté abierta.</p>
        </details>

        <details>
            <summary>¿Qué significan los colores de las etiquetas?</summary>
            <p><span style="color:#991b1b;font-weight:700;">Rojo</span> es Abierto,
               <span style="color:#92400e;font-weight:700;">amarillo</span> es En proceso,
               <span style="color:#065f46;font-weight:700;">verde</span> es Resuelto, y
               <span style="color:var(--gray-500);font-weight:700;">gris</span> es Cerrado.</p>
        </details>

        <details>
            <summary>¿Cómo agrego un comentario a una avería?</summary>
            <p>Ábrala desde la lista, y al final del detalle hay un campo de texto para escribir el
               comentario. Sirve tanto para quien reportó como para quien administra.</p>
        </details>

        <details>
            <summary>¿Qué hago si no puedo entrar al sistema para reportar una avería?</summary>
            <p>Acuda con alguien de administración y cuéntele qué pasó. Ellos pueden crear el ticket
               a nombre suyo, y le queda registrado igual, con su propio folio, para darle
               seguimiento.</p>
        </details>

        <details>
            <summary>¿Puedo ver los tickets de otras personas?</summary>
            <p>No. La vista "Mis averías" solo muestra lo que usted mismo ha reportado.</p>
        </details>

    </div>

</div>

<div class="gt-cierre">
    <p>¿Tiene alguna duda que esta guía no resuelve? Repórtela como una avería de tipo "Otro".</p>
    <a class="gt-hero-btn" href="index.php">Ir a Tickets</a>
</div>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>

<script>
(function () {
    var escenas = [].slice.call(document.querySelectorAll('.gt-escena'));
    if (!escenas.length) { return; }

    var elProgreso    = document.getElementById('gtProgreso');
    var elNarracion   = document.getElementById('gtNarracion');
    var elPaso        = document.getElementById('gtPaso');
    var elChips       = document.getElementById('gtChips');
    var btnPlay       = document.getElementById('gtPlay');
    var btnReiniciar  = document.getElementById('gtReiniciar');
    var btnExpandir   = document.getElementById('gtExpandir');
    var video         = document.getElementById('gtVideo');
    var cortina       = document.getElementById('gtCortina');
    var btnPlayGrande = document.getElementById('gtPlayGrande');
    var volGrupo      = document.getElementById('gtVolGrupo');
    var volBarra      = document.getElementById('gtVolumen');
    var btnSilenciar  = document.getElementById('gtSilenciar');
    var audio         = document.getElementById('gtAudio');

    var duraciones = escenas.map(function (e) {
        return parseInt(e.getAttribute('data-duracion'), 10) || 8000;
    });

    // Si hay un audio real configurado (ver $pista en el PHP) y trae un
    // segundo de inicio por cada escena, el video se sincroniza con ese
    // audio en vez de avanzar solo por tiempo. Mientras no haya audio,
    // sigue avanzando en silencio con la duración de cada escena.
    var pista = window.gtPista || null;
    var conAudio = !!(pista && pista.url && pista.tiempos && pista.tiempos.length === escenas.length);

    var indice = 0;
    var transcurrido = 0;
    var corriendo = false;
    var velocidad = 1;
    var ultimo = 0;
    var chips = [];

    var volumen = 1;
    var silenciado = false;
    try {
        var v = window.localStorage.getItem('paaTicketsVolumen');
        if (v !== null) { volumen = Math.max(0, Math.min(1, parseFloat(v))); }
        silenciado = (window.localStorage.getItem('paaTicketsSilencio') === '1');
    } catch (e) { }

    escenas.forEach(function (e, i) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'gt-chip';
        b.textContent = String(i + 1);
        b.setAttribute('aria-label', 'Ir al paso ' + (i + 1));
        b.addEventListener('click', function () { ir(i); });
        elChips.appendChild(b);
        chips.push(b);
    });

    function pintarVolumen() {
        btnSilenciar.textContent = (silenciado || volumen === 0) ? '🔈' : '🔊';
    }

    if (conAudio) {
        audio.src = pista.url;
        audio.volume = silenciado ? 0 : volumen;
        volGrupo.hidden = false;
        volBarra.value = Math.round((silenciado ? 0 : volumen) * 100);
        pintarVolumen();
    }

    function segmentoFin(i) {
        if (i + 1 < pista.tiempos.length) { return pista.tiempos[i + 1]; }
        return (audio.duration && !isNaN(audio.duration)) ? audio.duration : pista.tiempos[i] + duraciones[i] / 1000;
    }

    function pintar() {
        escenas.forEach(function (e, i) { e.classList.toggle('activa', i === indice); });
        chips.forEach(function (c, i) { c.classList.toggle('activo', i === indice); });
        elPaso.textContent = 'Paso ' + (indice + 1) + ' de ' + escenas.length;
        elNarracion.textContent = escenas[indice].getAttribute('data-narracion') || '';
        transcurrido = 0;
    }

    function siguiente() {
        indice = (indice + 1) % escenas.length;
        pintar();
        if (conAudio) {
            audio.currentTime = pista.tiempos[indice];
            if (corriendo) { audio.play().catch(function () { }); }
        }
    }

    function ir(i) {
        indice = ((i % escenas.length) + escenas.length) % escenas.length;
        pintar();
        if (conAudio) {
            audio.currentTime = pista.tiempos[indice];
            if (corriendo) { audio.play().catch(function () { }); }
        }
    }

    function tick(ahora) {
        if (!corriendo) { return; }

        if (conAudio) {
            var inicio = pista.tiempos[indice];
            var fin    = segmentoFin(indice);
            var pos    = audio.currentTime;
            var pct    = fin > inicio ? ((pos - inicio) / (fin - inicio)) * 100 : 100;
            elProgreso.style.width = Math.max(0, Math.min(100, pct)) + '%';

            if (pos >= fin - 0.05) { siguiente(); }
        } else {
            var delta = ultimo ? (ahora - ultimo) : 0;
            ultimo = ahora;
            transcurrido += delta * velocidad;

            var dur = duraciones[indice];
            elProgreso.style.width = Math.min(100, (transcurrido / dur) * 100) + '%';

            if (transcurrido >= dur) { siguiente(); }
        }

        requestAnimationFrame(tick);
    }

    function reproducir() {
        if (corriendo) { return; }
        corriendo = true;
        ultimo = 0;
        btnPlay.textContent = '⏸ Pausar';
        if (conAudio) { audio.play().catch(function () { }); }
        requestAnimationFrame(tick);
    }

    function pausar() {
        corriendo = false;
        btnPlay.textContent = '▶ Reproducir';
        if (conAudio) { audio.pause(); }
    }

    btnPlay.addEventListener('click', function () { corriendo ? pausar() : reproducir(); });

    btnReiniciar.addEventListener('click', function () {
        pausar();
        indice = 0;
        transcurrido = 0;
        elProgreso.style.width = '0%';
        pintar();
        if (conAudio) { audio.currentTime = pista.tiempos[0]; }
    });

    btnExpandir.addEventListener('click', function () {
        var expandido = video.classList.toggle('gt-expandido');
        document.body.classList.toggle('gt-sin-scroll', expandido);
        btnExpandir.textContent = expandido ? '⛶ Reducir' : '⛶ Ampliar';
    });

    document.querySelectorAll('.gt-vel').forEach(function (b) {
        b.addEventListener('click', function () {
            velocidad = parseFloat(b.dataset.vel);
            document.querySelectorAll('.gt-vel').forEach(function (x) { x.classList.remove('activo'); });
            b.classList.add('activo');
            if (conAudio) { audio.playbackRate = velocidad; }
        });
    });

    if (conAudio) {
        volBarra.addEventListener('input', function () {
            volumen = volBarra.value / 100;
            silenciado = volumen === 0;
            audio.volume = volumen;
            try {
                window.localStorage.setItem('paaTicketsVolumen', String(volumen));
                window.localStorage.setItem('paaTicketsSilencio', silenciado ? '1' : '0');
            } catch (e) { }
            pintarVolumen();
        });

        btnSilenciar.addEventListener('click', function () {
            silenciado = !silenciado;
            audio.volume = silenciado ? 0 : volumen;
            volBarra.value = Math.round(audio.volume * 100);
            try { window.localStorage.setItem('paaTicketsSilencio', silenciado ? '1' : '0'); } catch (e) { }
            pintarVolumen();
        });
    }

    // La primera reproducción necesita un clic real: los navegadores no
    // dejan reproducir audio con sonido sin que la persona haya interactuado.
    btnPlayGrande.addEventListener('click', function () {
        cortina.remove();
        if (conAudio) { audio.currentTime = pista.tiempos[0]; }
        pintar();
        reproducir();
    });

    pintar();
    elNarracion.textContent = '';

    document.getElementById('gtImprimir').addEventListener('click', function () { window.print(); });
})();

// ===================== BUSCADOR DE PREGUNTAS FRECUENTES =====================
(function () {
    var campo = document.getElementById('gtBuscar');
    var resultado = document.getElementById('gtResultado');
    var faq = document.getElementById('gtFaq');
    if (!campo || !faq) { return; }

    var detalles = [].slice.call(faq.querySelectorAll('details'));
    var vacio = null;

    campo.addEventListener('input', function () {
        var texto = campo.value.trim().toLowerCase();
        var visibles = 0;

        detalles.forEach(function (d) {
            var coincide = !texto || d.textContent.toLowerCase().indexOf(texto) !== -1;
            d.classList.toggle('gt-oculta', !coincide);
            if (coincide) { visibles++; }
            if (texto && coincide) { d.open = true; }
        });

        resultado.textContent = texto ? (visibles + ' de ' + detalles.length + ' preguntas') : '';

        if (!visibles && !vacio) {
            vacio = document.createElement('div');
            vacio.className = 'gt-vacio';
            vacio.textContent = 'Ninguna pregunta coincide con esa búsqueda.';
            faq.appendChild(vacio);
        } else if (visibles && vacio) {
            vacio.remove();
            vacio = null;
        }
    });
})();
</script>
</body>
</html>
