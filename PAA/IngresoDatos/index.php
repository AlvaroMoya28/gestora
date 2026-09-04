<?php
/**
 * Módulo Ingreso de Datos.
 *
 * Es lo que usa el personal de bodega durante el embalaje, en cinco pasos:
 *
 *   1. Elegir la sede
 *   2. Folletos de cada aula (normales y de adecuación)
 *   3. Folletos del coordinador, que son de la sede
 *   4. Armado de las tulas: qué aulas van en cada bulto
 *   5. Marchamos: los dos de cada tula
 *
 * ACÁ NO SE IMPRIME NADA.
 *
 * Este módulo solo mete datos a la base. Los comprobantes se levantan desde el
 * módulo Revisión, que lee lo ya guardado. Están separados a propósito: quien
 * embala no emite documentos, y así el acta se firma sobre datos que ya
 * quedaron registrados y no sobre lo que hay en pantalla a medio llenar.
 *
 * Quién entra: lo decide includes/modulos.php. Este módulo lo usan también
 * las cuentas de personal extraordinario, que no ven nada más del sistema.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo("ingresoDatos", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Ingreso de Datos · UCR</title>
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

        /* ===== PUNTOS DE PARTIDA ===== */
        .arranques {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem; padding: 1rem 1.15rem; background: #f0f7ff;
            border: 1px solid #bfdbfe; border-radius: 0.5rem;
        }
        .arranques label { display: block; margin-bottom: .3rem; }

        /* Lo que la base dice, junto al campo donde se escribe lo que se ve.
           Es para contrastar, no para rellenar. */
        .dice-la-base {
            font-size: .75rem; color: var(--gray-500); margin-top: .3rem; line-height: 1.4;
        }
        .dice-la-base.discrepa { color: var(--danger); font-weight: 600; }

        /* ===== AVANCE GENERAL ===== */
        .avance-general {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem; margin-bottom: .6rem;
        }
        @media (max-width: 780px) {
            .avance-general { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 420px) {
            .avance-general { grid-template-columns: 1fr; }
        }
        .avance-card {
            display: flex; align-items: center; gap: .9rem;
            min-width: 0;   /* si no, el texto empuja la tarjeta en vez de partirse */
            border-radius: var(--radius-lg); padding: 1.1rem 1.25rem;
            box-shadow: var(--shadow);
            border: 1px solid transparent;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .avance-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .avance-card .icono {
            flex-shrink: 0; width: 46px; height: 46px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; color: #fff;
        }
        .avance-card .texto { min-width: 0; }
        .avance-card .numero { font-size: 1.7rem; font-weight: 800; line-height: 1; color: var(--gray-800); }
        .avance-card .etiqueta {
            font-size: .78rem; color: var(--gray-600); margin-top: .2rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .03em;
            white-space: normal; overflow-wrap: break-word;
        }

        .avance-card.completa   { background: linear-gradient(135deg, #ecfdf5, #ffffff); border-color: #a7f3d0; }
        .avance-card.completa   .icono { background: var(--success); }
        .avance-card.proceso    { background: linear-gradient(135deg, #fffbeb, #ffffff); border-color: #fde68a; }
        .avance-card.proceso    .icono { background: var(--warning); }
        .avance-card.sinempezar { background: linear-gradient(135deg, #f9fafb, #ffffff); border-color: var(--gray-200); }
        .avance-card.sinempezar .icono { background: var(--gray-400); }
        .avance-card.ocupadas   { background: linear-gradient(135deg, #eff6ff, #ffffff); border-color: #bfdbfe; }
        .avance-card.ocupadas   .icono { background: var(--primary); }

        .avance-general .pie {
            grid-column: 1 / -1; font-size: .78rem; color: var(--gray-500); margin: -.15rem 0 .65rem;
        }

        /* Una celda que el sistema completó. Se marca en vez de bloquearse:
           hay excepciones y la gente tiene que poder corregirlas. */
        .calculado { background: #f0f7ff; border-color: #bfdbfe; }
        .editado   { background: #fffbeb; border-color: #fcd34d; font-weight: 600; }

        /* Fila extra bajo un aula con folletos de adecuación mezclados: se
           distingue de la fila del aula sin gritar tanto como un color de fondo. */
        .fila-adec td { border-top: none; padding-top: 0; }
        .fila-adec input { border-style: dashed; }

        .estado-guardado {
            margin-left: auto; font-size: .75rem; font-weight: 600;
            color: var(--gray-400); white-space: nowrap;
        }
        .estado-guardado.ok { color: var(--success); }

        /* ===== EDICIÓN DE DATOS BASE (candado de supervisor) ===== */
        .btn-candado-base {
            font-family: inherit; font-size: .72rem; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .3rem .65rem; border-radius: 999px; border: 1px solid var(--gray-300);
            background: #fff; color: var(--gray-600); white-space: nowrap;
        }
        .btn-candado-base:hover { border-color: var(--primary-light); color: var(--primary); }
        .btn-candado-base.desbloqueado {
            background: #fef3c7; border-color: #f59e0b; color: #92400e;
        }
        .aviso-base-desbloqueada {
            display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            background: #fef3c7; color: #92400e; border: 1px solid #f59e0b;
            border-radius: .5rem; padding: .55rem .85rem; font-size: .82rem;
            font-weight: 600; margin-bottom: 1rem;
        }
        .aviso-base-desbloqueada i { flex-shrink: 0; }
        .aviso-base-desbloqueada span { flex: 1; }

        /* Celdas de la tabla de aulas cuando pasan a ser editables. */
        .celda-base-editable {
            width: 100%; font-size: .82rem; padding: .3rem .4rem;
            border: 1px solid var(--gray-300); border-radius: .35rem; font-family: inherit;
        }
        .celda-base-editable:focus { border-color: var(--primary); outline: none; }

        /* ===== PROBLEMAS ===== */
        .problemas { margin: 1rem 0 0; }
        .problemas .fila {
            display: flex; gap: .55rem; align-items: flex-start;
            padding: .55rem .85rem; border-radius: .5rem;
            font-size: .84rem; margin-bottom: .35rem; line-height: 1.5;
        }
        .problemas .fila.err { background: #fee2e2; color: #991b1b; }
        .problemas .fila.ojo { background: #fef3c7; color: #92400e; }
        .problemas .cierre {
            display: flex; gap: .6rem; flex-wrap: wrap;
            align-items: center; margin-top: .6rem;
        }
        .problemas .cierre span { font-size: .8rem; color: var(--gray-500); }

        input.malo { border-color: var(--danger); background: #fee2e2; }

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

        /* ===== FILTRO DE SEGMENTO ===== */
        .filtro-segmento {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .filtro-segmento label {
            font-weight: 600;
            font-size: 0.8125rem;
            color: var(--gray-700);
        }
        .filtro-segmento select {
            width: auto;
            padding: 0.35rem 0.6rem;
            font-size: 0.8125rem;
            border-radius: var(--radius);
            border: 1px solid var(--gray-300);
            background: #fff;
        }
        .filtro-segmento .info-bloqueo {
            font-size: 0.75rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filtro-segmento .info-bloqueo .ocupado {
            color: var(--warning);
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

        /* ===== VENTANAS EMERGENTES ===== */
        .fondo-modal {
            display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, .55);
            z-index: 9000; align-items: center; justify-content: center; padding: 1rem;
        }
        .fondo-modal.visible { display: flex; }
        .modal-caja {
            background: #fff; border-radius: var(--radius-lg); padding: 1.5rem;
            max-width: 26rem; width: 100%; max-height: 90vh; overflow-y: auto;
        }
        .modal-caja h2 { font-size: 1.05rem; font-weight: 700; margin-bottom: .35rem; color: var(--gray-700); }
        .modal-caja > p { font-size: .82rem; color: var(--gray-500); margin-bottom: 1rem; }
        .modal-campo { margin-bottom: .85rem; }
        .modal-campo label {
            display: block; font-size: .78rem; font-weight: 700; color: var(--gray-600);
            text-transform: uppercase; letter-spacing: .02em; margin-bottom: .3rem;
        }
        .modal-acciones { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1.1rem; flex-wrap: wrap; }
        .modal-enlace-alterno {
            background: none; border: none; color: var(--primary); font-size: .78rem;
            font-weight: 600; cursor: pointer; text-decoration: underline; padding: 0; margin-top: .4rem;
        }
        .modal-casilla {
            display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--gray-700);
        }
        .modal-casilla input { width: auto; }

        .auto-calc-badge {
            font-size: 0.7rem;
            background: var(--primary-light);
            color: #fff;
            padding: 0.1rem 0.5rem;
            border-radius: 2rem;
            margin-left: 0.5rem;
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
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
        </div>
        <h1>Ingreso de Datos</h1>
        <p>Registro del embalaje por sede: folletos, tulas y marchamos</p>
    </div>

    <!-- ===== AVANCE GENERAL (de mi parte, si las sedes están divididas) ===== -->
    <div class="avance-general" id="avanceGeneral"></div>

    <div id="aviso" class="aviso"></div>

    <!-- ===== QUIÉN SOY Y QUÉ ME TOCA ===== -->
    <div class="filtro-segmento" id="filtroSegmento">
        <label>
            <i class="fa-regular fa-user"></i> Soy:
            <button class="btn-chico btn-secundario" id="botonQuien" onclick="preguntarQuien()"
                    title="Todos entran con la misma cuenta: este nombre es para que los demás sepan quién está en cada sede">
                …</button>
        </label>
        <label>
            <i class="fa-solid fa-people-group"></i> Dividir las sedes en:
            <select id="totalSegmentosSelect" onchange="cambiarDivision()">
                <option value="1">No dividir</option>
                <option value="2">2 partes</option>
                <option value="3">3 partes</option>
                <option value="4">4 partes</option>
                <option value="5">5 partes</option>
                <option value="6">6 partes</option>
                <option value="7">7 partes</option>
                <option value="8">8 partes</option>
            </select>
        </label>
        <label id="cajaSegmento" style="display:none;">
            Me toca la parte:
            <select id="segmentoSelect" onchange="aplicarFiltroSegmento()"></select>
        </label>
        <span class="info-bloqueo" id="infoBloqueo">
            <i class="fa-regular fa-circle-check" style="color:var(--success)"></i>
            <span>Nadie más está trabajando en este momento</span>
        </span>
    </div>

    <!-- ===== BARRA DE PASOS ===== -->
    <div class="pasos" id="barraPasos"></div>

    <!-- ===== PASO 1: SEDE ===== -->
    <div class="vista activa" id="vista1">
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
            <h2><i class="fa-solid fa-location-dot"></i> Elegí la sede</h2>

            <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:220px;">
                    <input type="search" id="buscarSede"
                           placeholder="Buscar por número, nombre, coordinador o fórmula…">
                </div>
                <div class="cambio-vista">
                    <button id="vistaTarjetas" class="activa" onclick="cambiarVista('tarjetas')"
                            title="Ver como tarjetas">
                        <i class="fa-solid fa-table-cells-large"></i></button>
                    <button id="vistaLista" onclick="cambiarVista('lista')"
                            title="Ver como lista">
                        <i class="fa-solid fa-list"></i></button>
                </div>
            </div>

            <div id="listaSedes">
                <div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
            </div>
        </div>
    </div>

    <!-- ===== PASOS 2 A 5 ===== -->
    <div id="cabeceraSede"></div>

    <!-- Observaciones: se muestra igual en los pasos 2, 3 y 4 — no es de
         ningún paso en particular, es de la sede completa. Se agrega acá y
         se imprime en Revisión, antes del espacio de observaciones a mano. -->
    <div id="panelObservaciones"></div>

    <!-- Materiales de adecuación: igual que observaciones, no es de ningún
         paso — solo aparece si alguien prende el interruptor para esta sede. -->
    <div id="panelAdecuacion"></div>

    <div class="vista" id="vista2">
        <div class="tarjeta">
            <h2><i class="fa-solid fa-book"></i> Folletos
                <span class="auto-calc-badge">Se completa solo</span>
                <span class="estado-guardado" id="estadoGuardado"></span>
                <button type="button" class="btn-candado-base" id="btnCandadoBase"
                        title="Editar los datos base de las aulas (número, tipo, fórmula, estudiantes)">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <span id="candadoBaseTexto">Editar datos base</span>
                </button>
            </h2>
            <p style="font-size:.875rem;color:var(--gray-600);margin-bottom:1rem;">
                Escribí <strong>dónde arranca cada tanda</strong> y el resto se completa solo:
                cuántos folletos lleva cada aula ya está en el sistema. La columna
                <strong>Folletos</strong> es lo que dice la base y <strong>Cubre</strong> lo que
                da el rango que escribiste — sirven para contrastar. Todo lo que se autocompleta
                se puede corregir a mano.
            </p>

            <div id="avisoBaseDesbloqueada" class="aviso-base-desbloqueada" style="display:none;">
                <i class="fa-solid fa-lock-open" aria-hidden="true"></i>
                <span id="avisoBaseTexto"></span>
                <button type="button" class="btn-secundario btn-chico" onclick="bloquearBaseAhora()">
                    <i class="fa-solid fa-lock"></i> Bloquear ahora
                </button>
            </div>

            <div class="arranques">
                <div>
                    <label for="arranqueAulas">Primer folleto de las aulas</label>
                    <input type="text" id="arranqueAulas" placeholder="ej. 28401">
                </div>
                <div id="cajaArranqueAdec" style="display:none;">
                    <label for="arranqueAdec">Primer folleto de adecuación</label>
                    <input type="text" id="arranqueAdec" placeholder="ej. A0001">
                </div>
                <div>
                    <label for="coordDesde">Primer folleto del coordinador</label>
                    <input type="text" id="coordDesde" placeholder="ej. 28918">
                    <div class="dice-la-base" id="diceCoord"></div>
                </div>
                <div>
                    <label for="coordHasta">Último del coordinador</label>
                    <input type="text" id="coordHasta" placeholder="se calcula">
                    <div class="dice-la-base" id="cuentaCoord"></div>
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;margin-top:.75rem;">
                <input type="checkbox" id="coordSinFolletos" style="width:auto"
                       onchange="alternarCoordSinFolletos(this.checked)">
                Esta sede no tiene folletos de coordinador
            </label>

            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;margin-top:.75rem;">
                <input type="checkbox" id="coordTieneAdec" style="width:auto"
                       onchange="alternarCoordAdec(this.checked)">
                El coordinador también trae folletos de adecuación mezclados
            </label>

            <div class="arranques" id="cajaCoordAdec" style="display:none;margin-top:.6rem;">
                <div>
                    <label for="coordAdecDesde">Adecuación del coordinador: desde</label>
                    <input type="text" id="coordAdecDesde" placeholder="ej. AD-9">
                </div>
                <div>
                    <label for="coordAdecHasta">Adecuación del coordinador: hasta</label>
                    <input type="text" id="coordAdecHasta" placeholder="ej. AD-10">
                    <div class="dice-la-base" id="cuentaCoordAdec"></div>
                </div>
            </div>

            <div class="tabla-scroll" style="margin-top:1.1rem;">
                <table>
                    <thead><tr>
                        <th>Aula</th><th>Tipo</th>
                        <th title="La fórmula del examen que trae esta aula, según la base">Fórmula</th>
                        <th title="Los folletos que la base dice que lleva esta aula">
                            Folletos <span style="font-weight:400;text-transform:none">(la base)</span></th>
                        <th>Desde</th><th>Hasta</th>
                        <th title="Los que cubre el rango que escribiste">Cubre</th>
                        <th title="El aula trae, entre sus folletos, un grupo con numeración de adecuación">Adec.</th>
                        <th title="Folletos que no siguen el rango consecutivo — para sumar uno suelto sin correr toda la sede">Sueltos</th>
                    </tr></thead>
                    <tbody id="tablaAulas"></tbody>
                </table>
            </div>

            <div id="cajaAgregarAula" style="display:none;margin-top:.75rem;">
                <button type="button" class="btn-secundario btn-chico" onclick="abrirAgregarAula()">
                    <i class="fa-solid fa-plus"></i> Agregar un aula que no está en la base
                </button>
            </div>

            <div id="problemasFolletos"></div>

            <div class="nota">
                <i class="fa-solid fa-lightbulb"></i>
                Los códigos pueden llevar letras y ceros a la izquierda —los de adecuación suelen
                tenerlos— y se conservan tal cual: de <code>A0001</code> el sistema sigue con
                <code>A0002</code>, no con <code>2</code>. Cada serie se cuenta por separado, así
                que los de adecuación nunca se cruzan con los de las aulas regulares.
                <br><br>
                Si corregís el «desde» de un aula, las que siguen se recalculan a partir de esa.
                Un aula con 0 estudiantes no lleva folletos. Los del coordinador van aparte: si la
                base trae el dato se calcula el «hasta»; si no lo trae, se escriben los dos a mano.
            </div>

            <div class="acciones">
                <button class="btn-secundario" onclick="irAPaso(1)">
                    <i class="fa-solid fa-arrow-left"></i> Cambiar de sede</button>
                <button class="btn-primario" onclick="guardarFolletos()">
                    Guardar y seguir <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <div class="vista" id="vista3">
        <div class="tarjeta">
            <h2><i class="fa-solid fa-box-open"></i> Armado de las tulas</h2>
            <p style="font-size:.875rem;color:var(--gray-600);margin-bottom:1rem;">
                Decidí cuántas tulas se van a armar y qué aulas va cada una.
                Los folletos del coordinador van en la que indiques.
            </p>

            <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:.5rem;">
                <div style="max-width:170px;">
                    <label for="cantidadTulas">¿Cuántas tulas?</label>
                    <input type="number" id="cantidadTulas" min="1" max="40" value="1"
                           onchange="rearmar()">
                </div>
                <button class="btn-secundario" onclick="repartirParejo()">
                    <i class="fa-solid fa-shuffle"></i> Repartir de 2 en 2</button>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--gray-600);font-weight:500;">
                    <input type="checkbox" id="juntarSobrante" style="width:auto">
                    Si sobra una aula suelta, juntarla con la última tula (si no, se le arma una aparte)
                </label>
            </div>
            <p style="font-size:.8rem;color:var(--gray-500);margin-bottom:1.25rem;">
                Arma una tula por cada dos aulas seguidas (aula 1 y 2 en la tula 1, aula 3 y 4 en la tula 2, y así) y le
                pone los folletos del coordinador a la última. Se puede seguir ajustando a mano después.
            </p>

            <div id="cajasTulas"></div>

            <div class="acciones">
                <button class="btn-secundario" onclick="irAPaso(2)">
                    <i class="fa-solid fa-arrow-left"></i> Atrás</button>
                <button class="btn-primario" onclick="guardarTulas()">
                    Guardar y seguir <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <div class="vista" id="vista4">
        <div class="tarjeta">
            <h2><i class="fa-solid fa-tags"></i> Marchamos</h2>
            <p style="font-size:.875rem;color:var(--gray-600);margin-bottom:1rem;">
                Escaneá los dos marchamos de cada tula: el de salida y el de retorno.
            </p>
            <div class="tabla-scroll">
                <table>
                    <thead><tr>
                        <th>Tula</th><th>Contenido</th>
                        <th>Marchamo 1 (salida)</th><th>Marchamo 2 (retorno)</th>
                    </tr></thead>
                    <tbody id="tablaMarchamos"></tbody>
                </table>
            </div>
            <div class="nota">
                El lector de códigos manda un Enter al terminar, así que el foco pasa solo al
                siguiente campo: se escanean los dos de una tula y sigue con la de abajo sin tocar
                el mouse. Un mismo código no puede estar en dos tulas; si se repite, se marca en
                rojo al instante.
            </div>

            <div id="problemasMarchamos"></div>

            <div class="acciones">
                <button class="btn-secundario" onclick="irAPaso(3)">
                    <i class="fa-solid fa-arrow-left"></i> Atrás</button>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <button class="btn-exito" onclick="guardarMarchamos()">
                        <i class="fa-solid fa-check"></i> Guardar marchamos</button>
                </div>
            </div>

            <div id="finSede" style="display:none;"></div>
        </div>
    </div>
</div>

<script>
const API = '../api/ingreso_datos.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

// Estado
let sedes = [];
let sede = null;
let aulas = [];
let tulas = [];
let pasoActual = 1;

// Materiales de adecuación no tiene guardado automático como Folletos: se
// marca a mano cuando se toca una casilla, para poder avisar antes de que
// se pierda sin que nadie lo pidiera (ver confirmarSalidaMateriales()).
let materialesSinGuardar = false;
let vista = 'tarjetas';

let segmentoActual = 1;
let totalSegmentos = 1;
let bloqueos = {};   // { codigo: { sesion, quien, hace } }

// Cuando falta la tabla de bloqueos el módulo sigue sirviendo: se puede meter
// datos, que es el trabajo. Lo que se pierde es saber quién está en cada sede,
// y eso hay que decirlo en pantalla en vez de disimularlo.
let sinBloqueos = false;

// Identidad. Todo el personal extraordinario entra con LA MISMA cuenta, así que
// el usuario de la sesión no distingue a nadie: `sesion` identifica a este
// navegador y `quien` es el nombre que la persona escribe, para que el resto
// sepa con quién hablar cuando dos caen en la misma sede.
let sesion = '';
let quien = '';

try {
    vista = localStorage.getItem('paa.revision.vista') || 'tarjetas';
    segmentoActual = parseInt(localStorage.getItem('paa.segmento.actual')) || 1;
    totalSegmentos = parseInt(localStorage.getItem('paa.segmento.total')) || 1;
} catch (e) { /* modo privado */ }

const NOMBRES_PASOS = ['Sede', 'Folletos', 'Armado de tulas', 'Marchamos'];

document.addEventListener('DOMContentLoaded', () => {
    iniciarIdentidad();

    document.getElementById('totalSegmentosSelect').value = totalSegmentos;
    pintarOpcionesDeSegmento();

    pintarPasos();
    cambiarVista(vista);
    cargarSedes();

    document.getElementById('buscarSede').addEventListener('input', pintarSedes);
    document.getElementById('formBuscarFolleto').addEventListener('submit', buscarFolleto);
    conectarCamposDeFolletos();

    // Latido: refresca el bloqueo propio y trae los ajenos.
    setInterval(latir, 30000);

    // Al cerrar la pestaña se suelta la sede. sendBeacon es lo único que el
    // navegador garantiza que sale durante el cierre; un fetch normal se cancela.
    window.addEventListener('pagehide', () => {
        if (!sede) return;
        try {
            navigator.sendBeacon(API, new Blob(
                [JSON.stringify({ accion: 'desbloquearSede', sede: sede.codigo, sesion })],
                { type: 'application/json' }
            ));
        } catch (e) { /* nada que hacer si el navegador no deja */ }
    });
});

// ===================== IDENTIDAD =====================

function iniciarIdentidad() {
    try {
        sesion = sessionStorage.getItem('paa.sesion') || '';
        if (!sesion) {
            sesion = nuevaSesion();
            sessionStorage.setItem('paa.sesion', sesion);
        }
        quien = localStorage.getItem('paa.quien') || '';
    } catch (e) {
        sesion = nuevaSesion();   // modo privado: vale para esta carga
    }

    if (!quien) {
        preguntarQuien(true);
    } else {
        pintarQuien();
    }
}

function nuevaSesion() {
    try {
        return crypto.randomUUID().replace(/-/g, '');
    } catch (e) {
        return (Date.now().toString(16) + Math.random().toString(16).slice(2))
            .padEnd(32, '0').slice(0, 32);
    }
}

function preguntarQuien(primeraVez = false) {
    const nombre = prompt(
        (primeraVez
            ? 'Antes de empezar: ¿cómo te llamás?\n\n'
            : '¿Cómo te llamás?\n\n') +
        'Todos entran con la misma cuenta, así que este nombre es lo único que le\n' +
        'permite al resto saber quién está trabajando en cada sede.',
        quien || '');

    if (nombre !== null && nombre.trim() !== '') {
        quien = nombre.trim().slice(0, 80);
        try { localStorage.setItem('paa.quien', quien); } catch (e) { /* modo privado */ }
    } else if (!quien) {
        quien = 'Sin nombre';
    }

    pintarQuien();
}

function pintarQuien() {
    document.getElementById('botonQuien').textContent = quien;
}

// ===================== SEGMENTOS =====================

/**
 * Las opciones de «me toca la parte» salen de en cuántas partes se dividió.
 *
 * Antes eran ocho fijas: se podía dividir en dos y elegir la parte siete, que
 * no existe, y la lista quedaba vacía sin decir por qué.
 */
function pintarOpcionesDeSegmento() {
    const caja = document.getElementById('cajaSegmento');
    const sel = document.getElementById('segmentoSelect');

    if (segmentoActual > totalSegmentos) segmentoActual = 1;

    sel.innerHTML = Array.from({ length: totalSegmentos }, (_, i) =>
        `<option value="${i + 1}">${i + 1} de ${totalSegmentos}</option>`).join('');
    sel.value = segmentoActual;

    caja.style.display = totalSegmentos > 1 ? '' : 'none';
}

function cambiarDivision() {
    totalSegmentos = parseInt(document.getElementById('totalSegmentosSelect').value) || 1;
    pintarOpcionesDeSegmento();
    aplicarFiltroSegmento();
}

function aplicarFiltroSegmento() {
    segmentoActual = parseInt(document.getElementById('segmentoSelect').value) || 1;

    try {
        localStorage.setItem('paa.segmento.actual', String(segmentoActual));
        localStorage.setItem('paa.segmento.total', String(totalSegmentos));
    } catch (e) { /* modo privado */ }

    pintarSedes();
    pintarInfoBloqueo();
}

/**
 * Las sedes que me tocan: un bloque seguido, no una de cada tres.
 *
 * Se reparten sobre la lista COMPLETA y no sobre lo que quedó del buscador.
 * Calcularlo sobre la lista filtrada hacía que el reparto cambiara a cada letra
 * que alguien escribiera en la búsqueda, y una sede podía entrar y salir de tu
 * parte sin que hubieras tocado nada.
 *
 * Y son bloques seguidos porque las sedes se recorren en orden: «te tocan de la
 * 401 a la 1030» es una instrucción que alguien puede seguir; «te tocan la 401,
 * la 404 y la 407» no.
 */
function sedesDeMiParte() {
    if (totalSegmentos <= 1) return sedes;

    const porParte = Math.ceil(sedes.length / totalSegmentos);
    return sedes.slice((segmentoActual - 1) * porParte, segmentoActual * porParte);
}

function sedesVisibles() {
    const filtro = (document.getElementById('buscarSede').value || '').toLowerCase();

    return sedesDeMiParte().filter(s =>
        !filtro ||
        s.codigo.toLowerCase().includes(filtro) ||
        s.nombre.toLowerCase().includes(filtro) ||
        (s.coordinador || '').toLowerCase().includes(filtro) ||
        (s.formulas || '').toLowerCase().includes(filtro)
    );
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
            <div class="sede-card" style="margin-top:.6rem;cursor:pointer;" onclick="irABuscarSede('${escapar(r.sedeCodigo)}')">
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

/** Salta al Paso 1 filtrado por esa sede, listo para elegirla. */
function irABuscarSede(codigo) {
    document.getElementById('buscarSede').value = codigo;
    pintarSedes();
    document.getElementById('listaSedes').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ===================== BLOQUEOS =====================

/** Refresca el bloqueo propio y trae los ajenos. */
async function latir() {
    if (sinBloqueos) return;

    if (sede) {
        try {
            await enviar({
                accion: 'bloquearSede', sede: sede.codigo,
                sesion, quien, segmento: segmentoActual, forzar: '1'
            });
        } catch (e) { /* si falla, el bloqueo vence solo */ }
    }

    await actualizarBloqueos();
}

async function actualizarBloqueos() {
    try {
        const d = await pedir(`${API}?accion=bloqueos`);
        bloqueos = d.bloqueos || {};
        sinBloqueos = !!d.sinBloqueos;
        pintarInfoBloqueo();
        if (pasoActual === 1) pintarSedes();
        avisarSiMeQuitaronLaSede();
    } catch (e) {
        // Silencioso: no vale la pena interrumpir el trabajo por esto.
    }
}

function ajena(codigo) {
    return bloqueos[codigo] && bloqueos[codigo].sesion !== sesion;
}

/**
 * Si alguien entró a la sede que tengo abierta, hay que decirlo YA.
 *
 * Es el caso que de verdad hace perder trabajo: los dos escribiendo la misma
 * sede, y el que guarda de último pisa al otro sin enterarse ninguno.
 */
let avisadoDe = '';

function avisarSiMeQuitaronLaSede() {
    if (!sede) { avisadoDe = ''; return; }

    if (ajena(sede.codigo)) {
        if (avisadoDe !== sede.codigo) {
            avisadoDe = sede.codigo;
            mostrar(
                `¡Ojo! ${bloqueos[sede.codigo].quien} acaba de entrar a la sede ${sede.codigo}, ` +
                `que es la que tenés abierta. Si los dos guardan, el último pisa al otro. ` +
                `Pónganse de acuerdo antes de seguir.`, 'error');
        }
    } else {
        avisadoDe = '';
    }
}

function pintarInfoBloqueo() {
    const info = document.getElementById('infoBloqueo');

    if (sinBloqueos) {
        info.innerHTML = `
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)"></i>
            <span class="ocupado">Los avisos de «quién está en cada sede» están apagados:
            falta correr las migraciones en el servidor. Se puede trabajar igual, pero
            coordinen a mano para no caer dos en la misma sede.</span>`;
        return;
    }

    const otros = Object.entries(bloqueos).filter(([, b]) => b.sesion !== sesion);

    if (!otros.length) {
        info.innerHTML = `
            <i class="fa-regular fa-circle-check" style="color:var(--success)"></i>
            <span>Nadie más está trabajando en este momento</span>`;
        return;
    }

    info.innerHTML = `
        <i class="fa-solid fa-people-group" style="color:var(--primary)"></i>
        <span class="ocupado">
            ${otros.length} sede(s) ocupada(s):
            ${otros.map(([codigo, b]) => `${codigo} · ${escapar(b.quien)}`).join(' — ')}
        </span>`;
}

/**
 * Toma la sede. Devuelve false solo si la persona decidió no entrar.
 *
 * Cuando está ocupada NO se corta sin más: se dice quién la tiene y se ofrece
 * entrar igual. A veces la otra persona ya se fue, y un candado que no se pueda
 * abrir deja media bodega esperando.
 */
async function bloquearSede(codigo, forzar = false) {
    try {
        await enviar({
            accion: 'bloquearSede', sede: codigo,
            sesion, quien, segmento: segmentoActual,
            forzar: forzar ? '1' : ''
        });
        return true;
    } catch (e) {
        if (!forzar && confirm(e.message + '\n\n¿Entrar igual?')) {
            return bloquearSede(codigo, true);
        }
        if (!forzar) mostrar(e.message, 'error');
        return false;
    }
}

async function desbloquearSede(codigo) {
    try {
        await enviar({ accion: 'desbloquearSede', sede: codigo, sesion });
    } catch (e) { /* si falla, vence solo */ }
}

// ===================== PASOS =====================

/**
 * Los cuatro pasos.
 *
 * En la base el avance se sigue guardando en cuatro marcas —folletos de aula,
 * folletos del coordinador, tulas, marchamos— porque de eso viven Revisión y la
 * lista de sedes. Lo que cambió es la pantalla: las dos primeras se llenan
 * juntas, así que el paso 2 se da por hecho cuando las dos están.
 */
function pasoHecho(n) {
    if (!sede || !sede.pasos) return false;
    if (n === 2) return sede.pasos[0] && sede.pasos[1];
    if (n === 3) return sede.pasos[2];
    if (n === 4) return sede.pasos[3];
    return false;
}

function pintarPasos() {
    document.getElementById('barraPasos').innerHTML = NOMBRES_PASOS.map((nombre, i) => {
        const n = i + 1;
        const bloqueado = n > 1 && !sede;
        const hecho = n > 1 && pasoHecho(n);
        return `
            <div class="paso ${n === pasoActual ? 'activo' : ''} ${hecho ? 'hecho' : ''} ${bloqueado ? 'bloqueado' : ''}"
                 onclick="${bloqueado ? '' : `irAPaso(${n})`}">
                <div class="paso-num">${hecho ? '<i class="fa-solid fa-check"></i>' : n}</div>
                <div class="paso-texto">${nombre}</div>
            </div>`;
    }).join('');
}

function irAPaso(n) {
    if (n > 1 && !sede) return;

    confirmarSalidaMateriales();

    pasoActual = n;
    document.querySelectorAll('.vista').forEach(v => v.classList.remove('activa'));
    document.getElementById('vista' + n).classList.add('activa');
    document.getElementById('cabeceraSede').style.display = n === 1 ? 'none' : '';
    document.getElementById('finSede').style.display = 'none';

    if (n === 2) pintarFolletos();
    if (n === 3) prepararArmado();
    if (n === 4) pintarMarchamos();

    pintarPasos();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===================== PASO 1 =====================

async function cargarSedes() {
    try {
        const d = await pedir(`${API}?accion=sedes`);
        sedes = d.sedes;
        bloqueos = d.bloqueos || {};
        sinBloqueos = !!d.sinBloqueos;
        pintarSedes();
        pintarInfoBloqueo();
    } catch (e) {
        document.getElementById('listaSedes').innerHTML =
            `<div class="vacio"><i class="fa-solid fa-triangle-exclamation"></i> ${escapar(e.message)}</div>`;
    }
}

function cambiarVista(cual) {
    vista = cual;
    try { localStorage.setItem('paa.revision.vista', cual); } catch (e) { /* modo privado */ }
    document.getElementById('vistaTarjetas').classList.toggle('activa', cual === 'tarjetas');
    document.getElementById('vistaLista').classList.toggle('activa', cual === 'lista');
    pintarSedes();
}

function pintarSedes() {
    const lista = sedesVisibles();
    const cont = document.getElementById('listaSedes');

    pintarAvanceGeneral();

    if (!lista.length) {
        cont.className = '';
        cont.innerHTML = totalSegmentos > 1
            ? `<div class="vacio">No hay sedes que coincidan en tu parte
                 (${segmentoActual} de ${totalSegmentos}).</div>`
            : '<div class="vacio">No hay sedes que coincidan.</div>';
        return;
    }

    cont.className = vista === 'lista' ? 'sedes-lista' : 'sedes-grid';
    cont.innerHTML = vista === 'lista' ? filasSedes(lista) : tarjetasSedes(lista);
}

/**
 * Cuadros de avance: cuántas sedes (de MI parte, si están divididas) están
 * completas, en proceso, sin empezar, u ocupadas por otra persona ahora
 * mismo. Se recalcula cada vez que se repinta la lista, así que se mantiene
 * al día con filtros, división en partes y bloqueos.
 */
function pintarAvanceGeneral() {
    const mias = sedesDeMiParte();

    const completas    = mias.filter(s => s.estado === 'completa').length;
    const enProceso     = mias.filter(s => s.estado === 'proceso').length;
    const sinEmpezar    = mias.filter(s => s.estado === 'sin_empezar').length;
    const ocupadas      = mias.filter(s => ajena(s.codigo)).length;

    const tarjetas = [
        ['completa',    completas,  'Completadas',    'fa-solid fa-circle-check'],
        ['proceso',     enProceso,  'En proceso',     'fa-solid fa-spinner'],
        ['sinempezar',  sinEmpezar, 'Sin empezar',    'fa-regular fa-circle'],
        ['ocupadas',    ocupadas,   'Ocupadas ahora', 'fa-regular fa-user'],
    ];

    const subtitulo = totalSegmentos > 1
        ? `de tu parte (${segmentoActual} de ${totalSegmentos}), ${mias.length} en total`
        : `de todas las sedes, ${mias.length} en total`;

    document.getElementById('avanceGeneral').innerHTML = tarjetas.map(([clase, n, etiqueta, icono]) => `
        <div class="avance-card ${clase}">
            <div class="icono"><i class="${icono}"></i></div>
            <div class="texto">
                <div class="numero">${n}</div>
                <div class="etiqueta">${etiqueta}</div>
            </div>
        </div>
    `).join('') + `<div class="pie">${subtitulo}</div>`;
}

function tarjetasSedes(lista) {
    return lista.map(s => {
        const ocupada = ajena(s.codigo);
        return `
        <div class="sede-card" onclick="elegirSede('${escapar(s.codigo)}')"
             style="${ocupada ? 'border-color:var(--warning);background:#fffbeb;' : ''}">
            <div class="codigo">SEDE ${escapar(s.codigo)}</div>
            <div class="nombre">${escapar(s.nombre)}</div>
            <div class="meta">
                ${s.aulas} aula(s)${s.aulasAdecuacion ? ` · ${s.aulasAdecuacion} de adecuación` : ''}
                · ${s.estudiantes} estudiantes<br>
                ${s.tulas ? `${s.tulas} tula(s) armadas` : 'sin tulas'}
                ${s.coordinador ? `· ${escapar(s.coordinador)}` : ''}
                ${ocupada ? `<br><span style="color:#92400e;font-weight:600">
                    <i class="fa-regular fa-user"></i> la tiene ${escapar(bloqueos[s.codigo].quien)}
                 </span>` : ''}
            </div>
            <div class="avance">
                ${s.pasos.map(p => `<span class="${p ? 'ok' : ''}"></span>`).join('')}
            </div>
        </div>`;
    }).join('');
}

function filasSedes(lista) {
    return `
    <table>
        <thead><tr>
            <th>Sede</th><th>Nombre</th><th>Aulas</th><th>Estudiantes</th>
            <th>Coordinador</th><th>Tulas</th><th>Avance</th><th>Estado</th><th></th>
        </tr></thead>
        <tbody>
            ${lista.map(s => {
                const ocupada = ajena(s.codigo);
                return `
                <tr onclick="elegirSede('${escapar(s.codigo)}')"
                    style="${ocupada ? 'background:#fffbeb;' : ''}">
                    <td><strong>${escapar(s.codigo)}</strong></td>
                    <td>${escapar(s.nombre)}</td>
                    <td>${s.aulas}${s.aulasAdecuacion ? ` <span class="etiqueta et-adecuacion">${s.aulasAdecuacion} adec.</span>` : ''}</td>
                    <td>${s.estudiantes}</td>
                    <td>${escapar(s.coordinador || '—')}</td>
                    <td>${s.tulas || '—'}</td>
                    <td><div class="avance">
                        ${s.pasos.map(p => `<span class="${p ? 'ok' : ''}"></span>`).join('')}
                    </div></td>
                    <td>${ocupada
                        ? `<span style="color:#92400e;font-weight:600">${escapar(bloqueos[s.codigo].quien)}</span>`
                        : 'Libre'}</td>
                    <td>${empezada(s)
                        ? `<button class="btn-borrar-avance"
                              onclick="event.stopPropagation(); borrarAvance('${escapar(s.codigo)}')"
                              title="Borrar el avance de esta sede">
                              <i class="fa-regular fa-trash-can"></i></button>`
                        : ''}</td>
                </tr>`;
            }).join('')}
        </tbody>
    </table>`;
}

function empezada(s) {
    return s.tulas > 0 || s.pasos.some(Boolean);
}

async function borrarAvance(codigo) {
    const s = sedes.find(x => x.codigo === codigo);

    const respuesta = prompt(
        `Vas a borrar TODO el avance de la sede ${codigo}` +
        (s ? ` (${s.nombre})` : '') + `:\n\n` +
        `· las ${s ? s.tulas : ''} tula(s) armadas y sus marchamos\n` +
        `· los folletos de cada aula\n` +
        `· los folletos del coordinador\n\n` +
        `La sede vuelve a quedar sin empezar.\n\n` +
        `Escribí el número de sede para confirmar:`
    );

    if (respuesta === null) return;
    if (respuesta.trim() !== codigo) {
        mostrar('El número no coincide: no se borró nada.', 'info');
        return;
    }

    try {
        const d = await enviar({ accion: 'borrarAvance', sede: codigo });
        mostrar(d.mensaje, 'exito');
        await desbloquearSede(codigo);
        if (sede && sede.codigo === codigo) {
            sede = null; aulas = []; tulas = []; materialesSinGuardar = false;
            document.getElementById('cabeceraSede').innerHTML = '';
            document.getElementById('panelObservaciones').innerHTML = '';
            document.getElementById('panelAdecuacion').innerHTML = '';
        }
        await cargarSedes();
        irAPaso(1);
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

async function salirASedes(preguntar = true) {
    // Lo escrito y todavía no mandado se guarda ANTES de preguntar nada: con
    // guardado automático ya no hay razón para que salir cueste trabajo.
    await guardarPendiente();
    confirmarSalidaMateriales();

    if (preguntar && !confirm(
        `La sede ${sede.codigo} va a quedar EN PROCESO con lo que llevás escrito.\n\n` +
        `¿Salir a la lista de sedes?`)) return;

    await desbloquearSede(sede.codigo);

    sede = null; aulas = []; tulas = []; avisadoDe = ''; materialesSinGuardar = false;
    document.getElementById('cabeceraSede').innerHTML = '';
    document.getElementById('panelObservaciones').innerHTML = '';
    document.getElementById('panelAdecuacion').innerHTML = '';
    document.getElementById('finSede').style.display = 'none';

    irAPaso(1);
    await cargarSedes();
}

/** La siguiente sede de mi parte que todavía no esté terminada ni ocupada. */
function siguienteSedePendiente() {
    const mias = sedesDeMiParte();
    const desde = mias.findIndex(s => s.codigo === sede.codigo) + 1;

    return mias.slice(desde).find(s => !s.completa && !ajena(s.codigo)) || null;
}

function mostrarFinDeSede() {
    const siguiente = siguienteSedePendiente();
    const caja = document.getElementById('finSede');

    caja.style.display = '';
    caja.innerHTML = `
        <div style="margin-top:1.25rem;padding:1.15rem 1.25rem;background:#d1fae5;
                    border:1px solid #6ee7b7;border-radius:var(--radius);">
            <div style="font-weight:700;color:#065f46;margin-bottom:.35rem;">
                <i class="fa-solid fa-circle-check"></i>
                Sede ${escapar(sede.codigo)} terminada
            </div>
            <p style="font-size:.85rem;color:#065f46;margin-bottom:1rem;">
                Todo quedó guardado. El comprobante se imprime desde el módulo Revisión.
            </p>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <button class="btn-secundario" onclick="salirASedes(false)">
                    <i class="fa-solid fa-list"></i> Salir a sedes</button>
                ${siguiente ? `
                    <button class="btn-primario" onclick="elegirSede('${escapar(siguiente.codigo)}')">
                        Seguir con la sede ${escapar(siguiente.codigo)}
                        <i class="fa-solid fa-arrow-right"></i></button>` : `
                    <span style="font-size:.85rem;color:#065f46;align-self:center;">
                        No quedan sedes pendientes en tu segmento.</span>`}
            </div>
        </div>`;
}

async function elegirSede(codigo) {
    if (sede && sede.codigo !== codigo) {
        await guardarPendiente();
        confirmarSalidaMateriales();
        await desbloquearSede(sede.codigo);
    }

    // Si está ocupada, bloquearSede pregunta y decide. No se corta acá.
    if (!await bloquearSede(codigo)) return;

    avisadoDe = '';

    try {
        const d = await pedir(`${API}?accion=sede&sede=${encodeURIComponent(codigo)}`);

        sede = d.sede;
        sede.pasos = (sedes.find(s => s.codigo === codigo) || {}).pasos || [false, false, false, false];
        aulas = d.aulas;
        // El checkbox de "folletos de adecuación mezclados" arranca marcado
        // si ya había algo guardado en ese rango, para que al volver a una
        // sede a medias no toque volver a marcarlo.
        aulas.forEach(a => { a.tieneAdec = !!a.adecDesde; a.tieneSueltos = !!a.folletosSueltos; a.sueltos = a.folletosSueltos || ''; });
        actualizarEdicionBase(d.edicionBase);
        tulas = d.tulas.map(t => ({
            numero: t.numero,
            llevaCoord: t.llevaCoord,
            aulas: t.aulas.map(a => a.id),
            marchamo1: t.marchamo1,
            marchamo2: t.marchamo2,
            id: t.id
        }));

        // d.sede.observaciones ya viene con lo acumulado hasta ahora: se
        // agrega en cualquier paso, así que no se reinicia al volver a entrar.

        materialesSinGuardar = false;

        pintarCabecera();
        pintarObservaciones();
        pintarAdecuacion();
        irAPaso(2);
    } catch (e) {
        mostrar(e.message, 'error');
        await desbloquearSede(codigo);
    }
}

function pintarCabecera() {
    const s = sedes.find(x => x.codigo === sede.codigo) || {};
    document.getElementById('cabeceraSede').innerHTML = `
        <div class="cabecera-sede">
            <div>
                <h3>Sede ${escapar(sede.codigo)} · ${escapar(sede.nombre)}</h3>
                <div class="datos">
                    <span><i class="fa-regular fa-calendar"></i> ${escapar(sede.convocatoria)}
                        ${sede.fecha ? ' · ' + escapar(sede.fecha) : ''}</span>
                    <span><i class="fa-solid fa-door-open"></i> ${aulas.length} aula(s)</span>
                    <span><i class="fa-solid fa-users"></i> ${s.estudiantes || 0} estudiantes</span>
                    ${s.turno ? `<span><i class="fa-regular fa-clock"></i> ${escapar(s.turno)}</span>` : ''}
                    ${s.coordinador ? `<span><i class="fa-regular fa-user"></i> ${escapar(s.coordinador)}</span>` : ''}
                    ${s.correoCoordinador ? `<span><i class="fa-regular fa-envelope"></i> ${escapar(s.correoCoordinador)}</span>` : ''}
                    ${totalSegmentos > 1
                        ? `<span><i class="fa-solid fa-people-group"></i>
                             Parte ${segmentoActual} de ${totalSegmentos}</span>`
                        : ''}
                    <span><i class="fa-regular fa-user"></i> ${escapar(quien)}</span>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <button class="btn-secundario btn-chico" onclick="salirASedes()">
                    <i class="fa-solid fa-arrow-left"></i> Salir a sedes</button>
                <button class="btn-secundario btn-chico" onclick="saltarSiguienteSede()">
                    <i class="fa-solid fa-forward"></i> Dejar así y seguir con la siguiente</button>
                <button class="btn-secundario btn-chico"
                        style="color:#991b1b"
                        onclick="borrarAvance('${escapar(sede.codigo)}')">
                    <i class="fa-regular fa-trash-can"></i> Borrar avance</button>
            </div>
        </div>`;
}

/**
 * El panel de observaciones: se ve igual en los pasos 2, 3 y 4, porque es de
 * la sede completa y no de ningún paso en particular. Lo que se agrega acá
 * queda para Revisión, antes del espacio de observaciones a mano del acta.
 */
function pintarObservaciones() {
    const lista = sede.observaciones || [];

    document.getElementById('panelObservaciones').innerHTML = `
        <div class="tarjeta">
            <h2><i class="fa-solid fa-note-sticky"></i> Observaciones de esta sede</h2>

            ${lista.length ? `
                <div class="tabla-scroll" style="margin-bottom:1rem;">
                    ${lista.map(o => `
                        <div style="display:flex;gap:.5rem;align-items:flex-start;padding:.6rem .75rem;border-left:3px solid var(--primary);background:var(--gray-50);border-radius:0 .5rem .5rem 0;margin-bottom:.5rem;">
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.72rem;color:var(--gray-500);margin-bottom:.2rem;">
                                    ${escapar(o.quien || 'Sin nombre')} · ${fechaLegibleObs(o.fecha)}
                                </div>
                                <div style="font-size:.875rem;">${escapar(o.texto)}</div>
                            </div>
                            <button class="btn-secundario btn-chico" style="color:#991b1b;flex-shrink:0;"
                                    onclick="borrarObservacion(${o.id})" title="Borrar esta observación">
                                <i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    `).join('')}
                </div>
            ` : `
                <p style="font-size:.85rem;color:var(--gray-500);margin-bottom:1rem;">
                    Todavía no hay ninguna observación en esta sede.
                </p>
            `}

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-start;">
                <textarea id="textoObservacion" rows="2" placeholder="Ej: faltó un folleto del aula 4, se avisó al coordinador"
                          style="flex:1;min-width:220px;"></textarea>
                <button class="btn-secundario" id="btnAgregarObservacion" onclick="agregarObservacion()">
                    <i class="fa-solid fa-plus"></i> Agregar</button>
            </div>
        </div>`;
}

/**
 * Los campos del equipo de adecuación, en el orden en que se muestran.
 * Se usa tanto para pintar la tabla como para leerla al guardar, así los
 * dos lugares no se pueden desincronizar si algún día se agrega un campo.
 */
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

/**
 * Materiales de adecuación: como Observaciones, se ve igual en los pasos 2,
 * 3 y 4 porque es de la sede completa. Solo aparece la tabla si alguien
 * prende el interruptor para esta sede — no toda sede con aulas de
 * adecuación necesita equipo aparte.
 */
function pintarAdecuacion() {
    const activo = !!sede.tieneMaterialesAdecuacion;
    const filas = sede.materialesAdecuacion || [];

    document.getElementById('panelAdecuacion').innerHTML = `
        <div class="tarjeta">
            <h2><i class="fa-solid fa-universal-access"></i> Materiales de adecuación</h2>

            <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;margin-bottom:1rem;">
                <input type="checkbox" id="chkMaterialesAdecuacion" style="width:auto"
                       ${activo ? 'checked' : ''} onchange="alternarMaterialesAdecuacion(this.checked)">
                Esta sede tiene materiales de adecuación
            </label>

            <div id="cuerpoAdecuacion">${activo ? cuerpoTablaAdecuacion(filas) : ''}</div>
        </div>`;
}

function cuerpoTablaAdecuacion(filas) {
    if (!filas.length) {
        return `<p style="font-size:.85rem;color:var(--gray-500);">
            Esta sede no tiene ninguna aula marcada como adecuación — no hay dónde anotar el equipo.
        </p>`;
    }

    return `
        <div class="tabla-scroll" style="margin-bottom:1rem;">
            <table>
                <thead><tr>
                    <th>Aula</th>
                    ${CAMPOS_ADECUACION.map(([, etiqueta]) => `<th>${etiqueta}</th>`).join('')}
                </tr></thead>
                <tbody>
                    ${filas.map(f => `
                        <tr>
                            <td><strong>${escapar(f.numero)}</strong></td>
                            ${CAMPOS_ADECUACION.map(([campo]) => `
                                <td><input type="text" data-aula="${f.aulaId}" data-campo="${campo}"
                                           value="${escapar(f[campo] || '')}" oninput="materialesSinGuardar = true"></td>
                            `).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <button class="btn-secundario" id="btnGuardarAdecuacion" onclick="guardarMaterialesAdecuacion()">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
            <button class="btn-secundario" onclick="limpiarMaterialesAdecuacion()">
                <i class="fa-solid fa-eraser"></i> Limpiar</button>
        </div>`;
}

async function alternarMaterialesAdecuacion(activo) {
    try {
        const d = await enviar({ accion: 'marcarMaterialesAdecuacion', sede: sede.codigo, activo: activo ? '1' : '' });
        sede.tieneMaterialesAdecuacion = activo;
        sede.materialesAdecuacion = d.materialesAdecuacion;
        materialesSinGuardar = false;
        document.getElementById('cuerpoAdecuacion').innerHTML = activo ? cuerpoTablaAdecuacion(sede.materialesAdecuacion) : '';
    } catch (e) {
        mostrar(e.message, 'error');
        pintarAdecuacion();
    }
}

/**
 * Vacía todas las casillas en pantalla, sin guardar todavía — por si se
 * escribió algo por error y es más fácil arrancar de cero que borrar campo
 * por campo. Queda como cambio sin guardar, igual que si se hubiera
 * escrito a mano: "Guardar cambios" sí manda las casillas vacías.
 */
function limpiarMaterialesAdecuacion() {
    if (!confirm('¿Vaciar todas las casillas de materiales de adecuación de esta sede?\n\nTodavía no se guarda: hay que darle "Guardar cambios" después.')) return;

    document.querySelectorAll('#cuerpoAdecuacion input').forEach(input => { input.value = ''; });
    materialesSinGuardar = true;
}

async function guardarMaterialesAdecuacion() {
    const filas = sede.materialesAdecuacion.map(f => {
        const fila = { aulaId: f.aulaId };
        CAMPOS_ADECUACION.forEach(([campo]) => {
            const input = document.querySelector(`#cuerpoAdecuacion [data-aula="${f.aulaId}"][data-campo="${campo}"]`);
            fila[campo] = input ? input.value.trim() : '';
        });
        return fila;
    });

    const boton = document.getElementById('btnGuardarAdecuacion');
    if (boton) boton.disabled = true;

    try {
        const d = await enviar({ accion: 'guardarMaterialesAdecuacion', sede: sede.codigo, filas });
        sede.materialesAdecuacion = d.materialesAdecuacion;
        materialesSinGuardar = false;
        mostrar('Materiales de adecuación guardados.', 'exito');
    } catch (e) {
        mostrar(e.message, 'error');
    } finally {
        if (boton) boton.disabled = false;
    }
}

/**
 * Si hay casillas de materiales tocadas y todavía no se guardaron, pregunta
 * antes de seguir — cambiar de paso o de sede sin esto perdía en silencio lo
 * que se acababa de escribir, porque esta tabla no tiene guardado automático
 * como Folletos.
 */
function confirmarSalidaMateriales() {
    if (!materialesSinGuardar) return;

    const guardar = confirm(
        'Tenés cambios sin guardar en "Materiales de adecuación".\n\n' +
        'Aceptar: guardarlos antes de continuar.\n' +
        'Cancelar: perderlos y continuar.'
    );

    if (guardar) {
        guardarMaterialesAdecuacion();
    } else {
        materialesSinGuardar = false;
    }
}

function fechaLegibleObs(texto) {
    if (!texto) return '';
    const fecha = new Date(String(texto).replace(' ', 'T'));
    if (isNaN(fecha)) return '';
    return fecha.toLocaleString('es-CR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
}

async function agregarObservacion() {
    const campo = document.getElementById('textoObservacion');
    const texto = campo.value.trim();

    if (!texto) {
        mostrar('Escribí algo antes de agregar la observación.', 'error');
        return;
    }

    const boton = document.getElementById('btnAgregarObservacion');
    boton.disabled = true;

    try {
        const d = await enviar({ accion: 'agregarObservacion', sede: sede.codigo, texto });
        sede.observaciones = d.observaciones;
        pintarObservaciones();
        mostrar('Observación agregada.', 'exito');
    } catch (e) {
        mostrar(e.message, 'error');
        boton.disabled = false;
    }
}

async function borrarObservacion(id) {
    if (!confirm('¿Borrar esta observación?')) return;

    try {
        const d = await enviar({ accion: 'borrarObservacion', sede: sede.codigo, id });
        sede.observaciones = d.observaciones;
        pintarObservaciones();
        mostrar('Observación borrada.', 'exito');
    } catch (e) {
        mostrar(e.message, 'error');
    }
}

/**
 * Para cuando falta un dato puntual (un coordinador sin correo, una tula sin
 * un marchamo) y no vale la pena trabar a toda la sede por eso: guarda lo que
 * hay tal cual —queda EN PROCESO, no completa— y salta derecho a la próxima
 * sede pendiente, sin pasar por la lista. Es lo mismo que hace "Salir a
 * sedes" + elegir la siguiente, en un solo paso.
 */
async function saltarSiguienteSede() {
    const siguiente = siguienteSedePendiente();

    if (!siguiente) {
        mostrar('No quedan sedes pendientes en tu segmento.', 'info');
        return;
    }

    if (!confirm(
        `La sede ${sede.codigo} va a quedar EN PROCESO con lo que llevás escrito.\n\n` +
        `¿Pasar a la sede ${siguiente.codigo}?`)) return;

    await elegirSede(siguiente.codigo);
}

// ===================== PASO 2 · FOLLETOS =====================
//
// La idea de fondo: la cantidad de estudiantes de cada aula YA está en la base.
// Si se sabe dónde arranca una tanda, todo lo demás sale de una suma. Escribir
// trescientas diecisiete veces lo que el sistema puede calcular es trabajo
// regalado, y cada número tecleado es un número que se puede teclear mal.
//
// Pero se autocompleta a la vista y se puede corregir. Un cálculo que no se
// puede tocar obliga a inventar rodeos el día que hay una excepción, y siempre
// hay una excepción.

function ordenPorNumero(lista) {
    return [...lista].sort((a, b) => (parseInt(a.numero) || 0) - (parseInt(b.numero) || 0));
}

// ---------- Códigos de folleto ----------
//
// Un folleto NO siempre es un número. Los de adecuación llevan letras, y hay
// series con ceros a la izquierda que no se pueden perder. Todo lo que sigue
// trabaja sobre el código completo: le encuentra la parte que cuenta, la suma,
// y vuelve a armar el código con su prefijo, su relleno y su sufijo intactos.
//
//   28401    → prefijo «»    número 28401  ancho 5  sufijo «»
//   A0001    → prefijo «A»   número 1      ancho 4  sufijo «»
//   AD-100B  → prefijo «AD-» número 100    ancho 3  sufijo «B»

/** Parte un código en lo fijo y lo que cuenta, o null si no tiene números. */
function partirCodigo(codigo) {
    // Se toma la ÚLTIMA tanda de dígitos: en «AD1234» la que cuenta es 1234, y
    // en «A1B2» es el 2. Es lo que hace que el prefijo pueda tener números.
    const m = String(codigo || '').trim().match(/^(.*?)(\d+)(\D*)$/);

    return m ? { prefijo: m[1], numero: parseInt(m[2], 10), ancho: m[2].length, sufijo: m[3] } : null;
}

/** Rearma el código con otro número, conservando relleno, prefijo y sufijo. */
function armarCodigo(p, numero) {
    return p.prefijo + String(numero).padStart(p.ancho, '0') + p.sufijo;
}

/**
 * La serie de un código: todo menos el número.
 *
 * Dos códigos de series distintas no compiten por el mismo folleto, así que no
 * tiene sentido compararlos. Es lo que permite que las aulas de adecuación
 * usen «A0001» mientras las normales van por «28401» sin que el sistema crea
 * que se están pisando.
 */
function serieDe(codigo) {
    const p = partirCodigo(codigo);
    return p ? p.prefijo + ' ' + p.sufijo : null;
}

/** El nombre de la serie tal como se le muestra a alguien. */
function nombreDeSerie(codigo) {
    const p = partirCodigo(codigo);
    if (!p) return '';
    return (p.prefijo || p.sufijo) ? `«${p.prefijo}…${p.sufijo}»` : 'numérica';
}

/**
 * Cuántos folletos de adecuación tiene MEZCLADOS un aula normal, o 0 si no
 * marcó el checkbox o el rango que escribió todavía no se puede contar.
 *
 * No es lo mismo que `esAdecuacion` (el aula entera de adecuación, que tiene
 * su propia tanda): esto es un aula normal con un sub-rango de otra serie
 * adentro, y se resta del total para saber cuántos folletos le tocan al
 * rango normal.
 */
function cantidadAdecuacionDe(a) {
    if (!a.tieneAdec) return 0;
    const c = folletosDe({ desde: a.adecDesde, hasta: a.adecHasta });
    return c && c > 0 ? c : 0;
}

/** Las aulas normales y las de adecuación son dos tandas de folletos distintas. */
function tandas() {
    const orden = ordenPorNumero(aulas);
    return {
        normales:   orden.filter(a => !a.esAdecuacion),
        adecuacion: orden.filter(a => a.esAdecuacion),
    };
}

function conectarCamposDeFolletos() {
    const arranque = (id, cualTanda) => {
        const campo = document.getElementById(id);
        campo.addEventListener('input', () => {
            encadenar(tandas()[cualTanda], 0, campo.value.trim());
            refrescarCeldas();
            autoguardar();
        });
    };

    arranque('arranqueAulas', 'normales');
    arranque('arranqueAdec', 'adecuacion');

    document.getElementById('coordDesde').addEventListener('input', () => {
        sugerirCoordHasta();
        refrescarCoordinador();
        autoguardar();
    });

    document.getElementById('coordHasta').addEventListener('input', () => {
        refrescarCoordinador();
        autoguardar();
    });

    ['coordAdecDesde', 'coordAdecHasta'].forEach(id => {
        document.getElementById(id).addEventListener('input', (e) => {
            sede[id] = e.target.value.trim();
            refrescarCoordinador();
            autoguardar();
        });
    });
}

/**
 * Reparte folletos consecutivos por una tanda, a partir de un aula.
 *
 * Las aulas sin estudiantes no llevan folletos y no cortan la numeración: se
 * saltan y la siguiente sigue donde iba. Un aula con folletos de adecuación
 * mezclados (ver cantidadAdecuacionDe) consume menos números de esta tanda:
 * esos folletos van en su propio rango, no en el consecutivo normal.
 */
function encadenar(tanda, desdeIndice, inicio) {
    const p = partirCodigo(inicio);

    if (!p) {
        return;   // todavía no escribieron nada usable
    }

    let n = p.numero;

    for (let i = desdeIndice; i < tanda.length; i++) {
        const a = tanda[i];
        const cuantos = a.asignados - cantidadAdecuacionDe(a);

        if (cuantos <= 0) {
            a.desde = ''; a.hasta = ''; a.manual = false;
            continue;
        }

        a.desde  = armarCodigo(p, n);
        a.hasta  = armarCodigo(p, n + cuantos - 1);
        a.manual = false;
        n += cuantos;
    }
}

function pintarFolletos() {
    const t = tandas();

    document.getElementById('cajaArranqueAdec').style.display =
        t.adecuacion.length ? '' : 'none';

    document.getElementById('tablaAulas').innerHTML = ordenPorNumero(aulas).map(a => `
        <tr ${edicionBase.autorizada ? `data-fila-base="${a.id}"` : ''}>
            ${edicionBase.autorizada ? `
            <td><input type="text" class="celda-base-editable" data-campo-base="numero"
                       value="${escapar(a.numero)}" onblur="guardarAulaBaseDesdeFila(${a.id})"></td>
            <td>
                <select class="celda-base-editable" data-campo-base="tipo" onchange="guardarAulaBaseDesdeFila(${a.id})">
                    <option value="regular" ${!a.esAdecuacion ? 'selected' : ''}>Regular</option>
                    <option value="adecuacion" ${a.esAdecuacion ? 'selected' : ''}>Adecuación</option>
                </select>
            </td>
            <td><input type="text" class="celda-base-editable" data-campo-base="formula"
                       value="${escapar(a.formula || '')}" onblur="guardarAulaBaseDesdeFila(${a.id})"></td>
            <td><input type="number" class="celda-base-editable" data-campo-base="asignados" min="0" max="2000"
                       value="${a.asignados}" onblur="guardarAulaBaseDesdeFila(${a.id})"></td>
            ` : `
            <td><strong>${escapar(a.numero)}</strong></td>
            <td><span class="etiqueta ${a.esAdecuacion ? 'et-adecuacion' : 'et-normal'}">
                ${a.esAdecuacion ? 'Adecuación' : 'Regular'}</span></td>
            <td>${escapar(a.formula || '—')}</td>
            <td style="font-weight:600">${a.asignados}</td>
            `}
            <td><input type="text" data-aula="${a.id}" data-campo="desde"
                       ${a.asignados <= 0 ? 'disabled placeholder="sin folletos"' : ''}
                       oninput="editarCelda(${a.id}, 'desde', this.value)"></td>
            <td><input type="text" data-aula="${a.id}" data-campo="hasta"
                       ${a.asignados <= 0 ? 'disabled' : ''}
                       oninput="editarCelda(${a.id}, 'hasta', this.value)"></td>
            <td data-cuenta="${a.id}" style="font-size:.8rem;color:var(--gray-500)"></td>
            <td style="text-align:center">
                ${a.esAdecuacion ? '' : `
                    <input type="checkbox" style="width:auto" ${a.tieneAdec ? 'checked' : ''}
                           title="Esta aula trae, entre sus folletos, un grupo con numeración de adecuación"
                           onchange="alternarAdecMezclada(${a.id}, this.checked)">`}
            </td>
            <td style="text-align:center">
                <input type="checkbox" style="width:auto" ${a.tieneSueltos ? 'checked' : ''}
                       title="Esta aula tiene algún folleto que no sigue el rango consecutivo"
                       onchange="alternarSueltos(${a.id}, this.checked)">
            </td>
        </tr>
        ${a.tieneAdec ? `
        <tr class="fila-adec">
            <td></td>
            <td colspan="3" style="font-size:.78rem;color:var(--gray-600)">
                <i class="fa-solid fa-puzzle-piece"></i> Adecuación de esta aula
            </td>
            <td><input type="text" data-aula="${a.id}" data-campo="adecDesde" placeholder="ej. AD-21"
                       oninput="editarAdec(${a.id}, 'adecDesde', this.value)"></td>
            <td><input type="text" data-aula="${a.id}" data-campo="adecHasta" placeholder="ej. AD-25"
                       oninput="editarAdec(${a.id}, 'adecHasta', this.value)"></td>
            <td data-cuenta-adec="${a.id}" style="font-size:.78rem;color:var(--gray-500)"></td>
            <td></td>
            <td></td>
        </tr>` : ''}
        ${a.tieneSueltos ? `
        <tr class="fila-adec">
            <td></td>
            <td colspan="3" style="font-size:.78rem;color:var(--gray-600)">
                <i class="fa-solid fa-shuffle"></i> Folletos sueltos de esta aula
                <span style="font-weight:400;">(separados por coma — ej. 35192, 35201)</span>
            </td>
            <td colspan="2">
                <input type="text" data-aula="${a.id}" data-campo="sueltos" value="${escapar(a.sueltos || '')}"
                       placeholder="ej. 35192, 35201"
                       oninput="editarSueltos(${a.id}, this.value)">
            </td>
            <td data-cuenta-sueltos="${a.id}" style="font-size:.78rem;color:var(--gray-500)"></td>
            <td></td>
            <td></td>
        </tr>` : ''}`).join('');

    // Los puntos de partida se rellenan con lo que ya hay guardado, para que al
    // volver a una sede a medias se vea de dónde venía.
    //
    // Se busca la primera aula CON dato y no la primera a secas: si el aula 1
    // no tiene estudiantes, no lleva folletos, y la casilla saldría vacía como
    // si la sede no se hubiera empezado.
    const arranqueDe = lista => (lista.find(a => a.desde) || {}).desde || '';

    document.getElementById('arranqueAulas').value = arranqueDe(t.normales);
    document.getElementById('arranqueAdec').value  = arranqueDe(t.adecuacion);
    document.getElementById('coordDesde').value    = sede.coordDesde || '';
    document.getElementById('coordHasta').value    = sede.coordHasta || '';

    document.getElementById('coordSinFolletos').checked = !!sede.coordSinFolletos;
    ['coordDesde', 'coordHasta', 'coordTieneAdec'].forEach(id => {
        document.getElementById(id).disabled = !!sede.coordSinFolletos;
    });

    sede.coordTieneAdec = !!sede.coordAdecDesde;
    document.getElementById('coordTieneAdec').checked = sede.coordTieneAdec;
    document.getElementById('cajaCoordAdec').style.display = sede.coordTieneAdec ? '' : 'none';
    document.getElementById('coordAdecDesde').value = sede.coordAdecDesde || '';
    document.getElementById('coordAdecHasta').value = sede.coordAdecHasta || '';

    refrescarCeldas();
    refrescarCoordinador();
}

/**
 * Vuelca los valores a los campos SIN rehacer la tabla.
 *
 * Rehacerla mientras alguien teclea le quitaría el foco y le comería lo que
 * estaba escribiendo.
 */
function refrescarCeldas() {
    aulas.forEach(a => {
        ['desde', 'hasta'].forEach(campo => {
            const c = document.querySelector(`[data-aula="${a.id}"][data-campo="${campo}"]`);
            if (!c || c === document.activeElement) return;

            c.value = a[campo] || '';
            c.classList.toggle('calculado', !a.manual && !!a[campo]);
            c.classList.toggle('editado', !!a.manual);
        });

        if (a.tieneAdec) {
            ['adecDesde', 'adecHasta'].forEach(campo => {
                const c = document.querySelector(`[data-aula="${a.id}"][data-campo="${campo}"]`);
                if (!c || c === document.activeElement) return;
                c.value = a[campo] || '';
            });
        }

        if (a.tieneSueltos) {
            const c = document.querySelector(`[data-aula="${a.id}"][data-campo="sueltos"]`);
            if (c && c !== document.activeElement) c.value = a.sueltos || '';
        }

        const cantidadAdec = cantidadAdecuacionDe(a);
        const cantidadSueltos = cantidadSueltosDe(a);
        // Un folleto suelto también cubre a un estudiante, aunque no siga la
        // numeración del rango: se descuenta igual que los de adecuación al
        // calcular cuántos deberían salir del rango consecutivo.
        const esperados = a.asignados - cantidadAdec - cantidadSueltos;

        const celda = document.querySelector(`[data-cuenta="${a.id}"]`);
        if (celda) {
            const cuantos = folletosDe(a);

            celda.textContent = cuantos === null ? '—'
                : cuantos + (cuantos === esperados ? '' : ` (deberían ser ${esperados})`);
            celda.style.color = cuantos !== null && cuantos !== esperados
                ? 'var(--danger)' : 'var(--gray-500)';
        }

        const celdaAdec = document.querySelector(`[data-cuenta-adec="${a.id}"]`);
        if (celdaAdec) {
            const cuantosAdec = folletosDe({ desde: a.adecDesde, hasta: a.adecHasta });

            celdaAdec.textContent = cuantosAdec === null ? '—'
                : `${cuantosAdec} · quedan ${esperados} regulares de ${a.asignados}`;
            celdaAdec.style.color = cuantosAdec === null ? 'var(--gray-500)'
                : (esperados < 0 ? 'var(--danger)' : 'var(--gray-500)');
        }

        const celdaSueltos = document.querySelector(`[data-cuenta-sueltos="${a.id}"]`);
        if (celdaSueltos) {
            celdaSueltos.textContent = cantidadSueltos === 1
                ? '1 folleto suelto' : `${cantidadSueltos} folletos sueltos`;
        }
    });
}

/**
 * Cuántos folletos cubre el rango de un aula, o null si no se puede saber.
 *
 * Un rango que empieza en una serie y termina en otra —«A0001» a «B0030»— no
 * cubre nada contable, así que devuelve null en vez de un número inventado.
 */
function folletosDe(a) {
    const d = partirCodigo(a.desde);
    const h = partirCodigo(a.hasta);

    if (!d || !h || serieDe(a.desde) !== serieDe(a.hasta)) return null;

    return h.numero - d.numero + 1;
}

/**
 * Edita el «desde»/«hasta» de UNA sola aula, en la tabla de abajo.
 *
 * No encadena a las que siguen: eso lo hace el «primer folleto» de arriba,
 * que sí recorre toda la tanda de una vez. Acá abajo cada aula puede ser
 * distinta —una serie propia, un rango que no sigue el consecutivo— y
 * encadenar desde una fila pisaba las de más abajo aunque fueran de otra
 * cosa.
 */
function editarCelda(id, campo, valor) {
    const a = aulas.find(x => x.id === id);
    if (!a) return;

    a[campo] = valor.trim();
    a.manual = true;

    // Si cambia el «desde», el «hasta» se recalcula con los estudiantes del
    // aula (menos los que ya se van por el rango de adecuación mezclada, si
    // tiene). Si cambia el «hasta», mandó la persona: el rango es el que puso.
    if (campo === 'desde') {
        const p = partirCodigo(a.desde);
        const cuantos = a.asignados - cantidadAdecuacionDe(a);
        a.hasta = (p && cuantos > 0) ? armarCodigo(p, p.numero + cuantos - 1) : '';
    }

    refrescarCeldas();
    autoguardar();
}

/**
 * Marca o desmarca que esta aula (normal) trae folletos de adecuación
 * mezclados adentro. Al desmarcar se borra el rango: no tiene sentido
 * guardar un «desde/hasta» de adecuación que la pantalla ya no muestra.
 */
function alternarAdecMezclada(id, marcado) {
    const a = aulas.find(x => x.id === id);
    if (!a) return;

    a.tieneAdec = marcado;
    if (!marcado) { a.adecDesde = ''; a.adecHasta = ''; }

    pintarFolletos();
    autoguardar();
}

/** Edita el «desde»/«hasta» del rango de adecuación mezclada de un aula. */
function editarAdec(id, campo, valor) {
    const a = aulas.find(x => x.id === id);
    if (!a) return;

    a[campo] = valor.trim();
    refrescarCeldas();
    autoguardar();
}

/**
 * Folletos SUELTOS: números que no entran en el rango consecutivo del aula.
 *
 * Es para cuando la sede ya está completa y hay que sumarle un folleto a una
 * aula puntual (un estudiante más, por ejemplo) sin correr todo el rango de
 * la sede — el folleto de más sale de otra caja y no sigue la numeración.
 */
function alternarSueltos(id, marcado) {
    const a = aulas.find(x => x.id === id);
    if (!a) return;

    a.tieneSueltos = marcado;
    if (!marcado) { a.sueltos = ''; }

    pintarFolletos();
    autoguardar();
}

function editarSueltos(id, valor) {
    const a = aulas.find(x => x.id === id);
    if (!a) return;

    a.sueltos = valor;
    refrescarCeldas();
    autoguardar();
}

/** Cuántos códigos hay escritos en la lista de sueltos de un aula. */
function cantidadSueltosDe(a) {
    if (!a.tieneSueltos || !a.sueltos) return 0;
    return (a.sueltos.split(/[,;\r\n]+/).map(c => c.trim()).filter(Boolean)).length;
}

/**
 * Cuántos folletos lleva el coordinador según la base, o null si no lo dice.
 *
 * Sale de la columna «TOTAL FOLLETOS COORD.» que ya venía cargada. Cuando la
 * sede no la trae, NO se propone ningún número: «uno por aula» era una regla
 * observada en datos de otros años, no un dato de esta sede, y ofrecerla como
 * sugerencia invitaba a aceptarla sin pensar. Sin el dato, la persona escribe
 * el «desde» y el «hasta» a mano — dos campos en vez de uno, pero sin un
 * número inventado de por medio.
 */
function folletosCoordEsperados() {
    return (sede && sede.folletosCoord > 0) ? sede.folletosCoord : null;
}

/** Cuántos folletos de adecuación tiene mezclados el coordinador, o 0. */
function cantidadCoordAdec() {
    if (!sede || !sede.coordTieneAdec) return 0;
    const c = folletosDe({ desde: sede.coordAdecDesde, hasta: sede.coordAdecHasta });
    return c && c > 0 ? c : 0;
}

function sugerirCoordHasta() {
    const p = partirCodigo(document.getElementById('coordDesde').value);
    const campo = document.getElementById('coordHasta');
    const cuantos = folletosCoordEsperados();

    // Sin dato de la base no se calcula nada: el campo se deja para que la
    // persona lo escriba ella misma.
    if (!p || !cuantos) return;

    campo.value = armarCodigo(p, p.numero + cuantos - 1 - cantidadCoordAdec());
    campo.classList.add('calculado');
}

/**
 * Marca o desmarca que el coordinador trae folletos de adecuación mezclados.
 * Igual que con las aulas: al desmarcar se borra el rango.
 */
function alternarCoordAdec(marcado) {
    if (!sede) return;

    sede.coordTieneAdec = marcado;
    if (!marcado) { sede.coordAdecDesde = ''; sede.coordAdecHasta = ''; }

    document.getElementById('cajaCoordAdec').style.display = marcado ? '' : 'none';
    document.getElementById('coordAdecDesde').value = sede.coordAdecDesde || '';
    document.getElementById('coordAdecHasta').value = sede.coordAdecHasta || '';

    refrescarCoordinador();
    autoguardar();
}

function alternarCoordSinFolletos(marcado) {
    if (!sede) return;

    sede.coordSinFolletos = marcado;

    // No tiene sentido escribir un rango y a la vez decir que no hay
    // folletos: se limpian y se deshabilitan para que no queden datos
    // viejos guardados debajo de la marca.
    ['coordDesde', 'coordHasta', 'coordTieneAdec'].forEach(id => {
        document.getElementById(id).disabled = marcado;
    });
    if (marcado) {
        document.getElementById('coordDesde').value = '';
        document.getElementById('coordHasta').value = '';
        sede.coordDesde = '';
        sede.coordHasta = '';
        if (sede.coordTieneAdec) {
            document.getElementById('coordTieneAdec').checked = false;
            alternarCoordAdec(false);
        }
    }

    refrescarCoordinador();
    autoguardar();
}

/** Lo que la base dice del coordinador, y si el rango escrito le calza. */
function refrescarCoordinador() {
    const dice = document.getElementById('diceCoord');
    const cuenta = document.getElementById('cuentaCoord');
    const cuentaAdec = document.getElementById('cuentaCoordAdec');
    const campoHasta = document.getElementById('coordHasta');

    if (!sede) return;

    const esperadosBase = folletosCoordEsperados();
    const cantidadAdec = cantidadCoordAdec();
    const esperados = esperadosBase ? esperadosBase - cantidadAdec : esperadosBase;

    dice.textContent = esperadosBase
        ? `La base dice: ${esperadosBase} folleto(s)`
        : 'La base no trae el dato: escribí desde y hasta.';
    dice.classList.toggle('discrepa', !esperadosBase);

    // Sin el dato, "hasta" no es un cálculo: es un campo más que hay que
    // llenar, igual que "desde".
    campoHasta.placeholder = esperadosBase ? 'se calcula' : 'escribilo a mano';

    if (cuentaAdec) {
        const cuantosAdec = folletosDe({ desde: sede.coordAdecDesde, hasta: sede.coordAdecHasta });
        cuentaAdec.textContent = cuantosAdec === null ? ''
            : `${cuantosAdec} · quedan ${esperados ?? '?'} regulares` + (esperadosBase ? ` de ${esperadosBase}` : '');
    }

    const d = partirCodigo(document.getElementById('coordDesde').value);
    const h = partirCodigo(document.getElementById('coordHasta').value);
    const mismaSerie = serieDe(document.getElementById('coordDesde').value)
                    === serieDe(document.getElementById('coordHasta').value);

    if (!d || !h || !mismaSerie) {
        cuenta.textContent = '';
        cuenta.classList.remove('discrepa');
        return;
    }

    const cubre = h.numero - d.numero + 1;

    // Sin un número de referencia no hay con qué contrastar: solo se informa
    // cuánto cubre, sin decir si «debería» ser otra cosa.
    cuenta.textContent = esperados
        ? `El rango cubre ${cubre} folleto(s)` + (cubre === esperados ? '' : ` · deberían ser ${esperados}`)
        : `El rango cubre ${cubre} folleto(s)`;
    cuenta.classList.toggle('discrepa', !!esperados && cubre !== esperados);
}

// ---------- Validación ----------

/**
 * Todo lo que se puede comprobar sin salir de la pantalla.
 *
 * Separa lo que está MAL de lo que solo es RARO. Un error impide guardar sin
 * confirmar; un aviso solo hay que haberlo leído. Y ninguno de los dos bloquea
 * de verdad: siempre se puede guardar igual, porque el día de la aplicación
 * aparecen excepciones que ninguna regla escrita de antemano previó, y un
 * sistema que no deja registrarlas obliga a anotarlas en un papel.
 */
function revisarFolletos() {
    const errores = [];
    const avisos  = [];
    const conGente = aulas.filter(a => a.asignados > 0);

    const rangos = [];

    conGente.forEach(a => {
        // Sin rango consecutivo pero con folletos sueltos que ya cubren a
        // todo el mundo: pasa igual, es el caso de un aula cuyos folletos
        // vinieron TODOS de cajas sueltas, sin una tanda propia.
        if (!a.desde && a.tieneSueltos && cantidadSueltosDe(a) >= a.asignados - cantidadAdecuacionDe(a)) {
            return;
        }

        if (!a.desde) {
            errores.push(
                `El aula ${a.numero} lleva ${a.asignados} folleto(s) según la base y no se le ` +
                `asignó ninguno.`);
            return;
        }

        const d = partirCodigo(a.desde);
        const h = partirCodigo(a.hasta);

        if (!d || !h) {
            errores.push(
                `El rango del aula ${a.numero} («${a.desde}» a «${a.hasta}») no tiene ` +
                `ningún número que contar.`);
            return;
        }

        // Un rango tiene que quedarse dentro de su serie: de «A0001» a «B0030»
        // no se sabe cuántos folletos hay ni cuáles son.
        if (serieDe(a.desde) !== serieDe(a.hasta)) {
            errores.push(
                `El aula ${a.numero} empieza en «${a.desde}» y termina en «${a.hasta}», ` +
                `que son series distintas.`);
            return;
        }

        if (h.numero < d.numero) {
            errores.push(
                `El aula ${a.numero} termina («${a.hasta}») antes de donde empieza («${a.desde}»).`);
            return;
        }

        const cuantos = h.numero - d.numero + 1;
        const cantidadSueltos = cantidadSueltosDe(a);
        const esperados = a.asignados - cantidadAdecuacionDe(a) - cantidadSueltos;

        if (cuantos !== esperados) {
            errores.push(
                `El aula ${a.numero} lleva ${esperados} folleto(s) del rango regular ` +
                (a.tieneAdec || a.tieneSueltos
                    ? `(${a.asignados} en total, menos adecuación y sueltos) `
                    : `según la base `) +
                `pero el rango ${a.desde}–${a.hasta} cubre ${cuantos}.`);
        }

        if (a.tieneSueltos && !a.sueltos) {
            errores.push(`El aula ${a.numero} marcó que tiene folletos sueltos pero no escribió ninguno.`);
        }

        rangos.push({ n: a.numero, serie: serieDe(a.desde), d, h, desde: a.desde, hasta: a.hasta });

        // El rango de adecuación mezclada es otra serie: entra a la misma
        // revisión de solapes y huecos que las demás, cada una en la suya.
        if (a.tieneAdec) {
            const dAdec = partirCodigo(a.adecDesde);
            const hAdec = partirCodigo(a.adecHasta);

            if (!a.adecDesde || !a.adecHasta || !dAdec || !hAdec) {
                errores.push(`El aula ${a.numero} marcó folletos de adecuación pero le falta el rango.`);
            } else if (serieDe(a.adecDesde) !== serieDe(a.adecHasta)) {
                errores.push(
                    `El rango de adecuación del aula ${a.numero} («${a.adecDesde}» a «${a.adecHasta}») ` +
                    `son series distintas.`);
            } else if (hAdec.numero < dAdec.numero) {
                errores.push(
                    `El rango de adecuación del aula ${a.numero} termina antes de donde empieza.`);
            } else {
                rangos.push({
                    n: `${a.numero} (adecuación)`, serie: serieDe(a.adecDesde),
                    d: dAdec, h: hAdec, desde: a.adecDesde, hasta: a.adecHasta,
                });
            }
        }
    });

    // Solapes y huecos, SERIE POR SERIE.
    //
    // Las aulas de adecuación usan códigos con letras y son otra tanda de
    // folletos: comparar «A0001» contra «28401» con parseInt daba solapes
    // inventados y huecos de miles de folletos que nadie tenía que revisar.
    const porSerie = {};
    rangos.forEach(r => { (porSerie[r.serie] ||= []).push(r); });

    Object.values(porSerie).forEach(grupo => {
        grupo.sort((x, y) => x.d.numero - y.d.numero);

        for (let i = 1; i < grupo.length; i++) {
            const antes = grupo[i - 1], ahora = grupo[i];

            if (ahora.d.numero <= antes.h.numero) {
                errores.push(
                    `Las aulas ${antes.n} y ${ahora.n} se pisan: la primera llega hasta ` +
                    `${antes.hasta} y la segunda empieza en ${ahora.desde}.`);
            } else if (ahora.d.numero > antes.h.numero + 1) {
                const cuantos = ahora.d.numero - antes.h.numero - 1;
                avisos.push(
                    `Entre el aula ${antes.n} y la ${ahora.n} quedan ${cuantos} folleto(s) ` +
                    `sin usar (${armarCodigo(antes.h, antes.h.numero + 1)}–` +
                    `${armarCodigo(ahora.d, ahora.d.numero - 1)}).`);
            }
        }
    });

    // El coordinador — nada que revisar si ya se dijo que esta sede no tiene.
    const textoCd = document.getElementById('coordDesde').value.trim();
    const textoCh = document.getElementById('coordHasta').value.trim();
    const cd = partirCodigo(textoCd);
    const ch = partirCodigo(textoCh);

    if (sede.coordSinFolletos) {
        // nada que revisar
    } else if (!cd || !ch) {
        errores.push('Faltan los folletos del coordinador.');
    } else if (serieDe(textoCd) !== serieDe(textoCh)) {
        errores.push(
            `Los folletos del coordinador empiezan en «${textoCd}» y terminan en ` +
            `«${textoCh}», que son series distintas.`);
    } else {
        if (ch.numero < cd.numero) {
            errores.push(
                `Los folletos del coordinador terminan («${textoCh}») antes de empezar ` +
                `(«${textoCd}»).`);
        } else {
            const cubre = ch.numero - cd.numero + 1;
            const esperadosBase = folletosCoordEsperados();
            const esperados = esperadosBase ? esperadosBase - cantidadCoordAdec() : esperadosBase;

            // Sin un número de la base no hay contra qué comparar: no se
            // avisa nada, porque no habría con qué justificar el aviso.
            if (esperados && cubre !== esperados) {
                avisos.push(
                    `El coordinador lleva ${textoCd}–${textoCh}, que son ${cubre} folleto(s), ` +
                    `y la base dice ${esperados}` +
                    (esperadosBase !== esperados ? ` (${esperadosBase} menos los de adecuación)` : '') + '.');
            }
        }

        rangos
            .filter(r => r.serie === serieDe(textoCd))
            .forEach(r => {
                if (cd.numero <= r.h.numero && ch.numero >= r.d.numero) {
                    errores.push(
                        `Los folletos del coordinador (${textoCd}–${textoCh}) se pisan con los ` +
                        `del aula ${r.n} (${r.desde}–${r.hasta}).`);
                }
            });
    }

    // El rango de adecuación mezclada del coordinador es otra serie: entra a
    // la misma revisión de solapes que las aulas.
    if (sede && sede.coordTieneAdec) {
        const dAdec = partirCodigo(sede.coordAdecDesde);
        const hAdec = partirCodigo(sede.coordAdecHasta);

        if (!sede.coordAdecDesde || !sede.coordAdecHasta || !dAdec || !hAdec) {
            errores.push('Se marcó que el coordinador tiene folletos de adecuación pero le falta el rango.');
        } else if (serieDe(sede.coordAdecDesde) !== serieDe(sede.coordAdecHasta)) {
            errores.push(
                `El rango de adecuación del coordinador («${sede.coordAdecDesde}» a ` +
                `«${sede.coordAdecHasta}») son series distintas.`);
        } else if (hAdec.numero < dAdec.numero) {
            errores.push('El rango de adecuación del coordinador termina antes de donde empieza.');
        } else {
            rangos
                .filter(r => r.serie === serieDe(sede.coordAdecDesde))
                .forEach(r => {
                    if (dAdec.numero <= r.h.numero && hAdec.numero >= r.d.numero) {
                        errores.push(
                            `La adecuación del coordinador (${sede.coordAdecDesde}–${sede.coordAdecHasta}) ` +
                            `se pisa con la del aula ${r.n} (${r.desde}–${r.hasta}).`);
                    }
                });
        }
    }

    return { errores, avisos };
}

function pintarProblemas(donde, r, accionForzar) {
    const caja = document.getElementById(donde);

    if (!r.errores.length && !r.avisos.length) {
        caja.innerHTML = '';
        return;
    }

    caja.innerHTML = `
        <div class="problemas">
            ${r.errores.map(e => `<div class="fila err">
                <i class="fa-solid fa-circle-exclamation" style="margin-top:.15rem"></i>
                <span>${escapar(e)}</span></div>`).join('')}
            ${r.avisos.map(a => `<div class="fila ojo">
                <i class="fa-solid fa-triangle-exclamation" style="margin-top:.15rem"></i>
                <span>${escapar(a)}</span></div>`).join('')}
            ${accionForzar ? `
                <div class="cierre">
                    <button class="btn-secundario btn-chico" onclick="${accionForzar}">
                        <i class="fa-solid fa-arrow-right"></i> Guardar así de todos modos</button>
                    <span>Se guarda tal cual y queda anotado que se pasó por alto.</span>
                </div>` : ''}
        </div>`;

    caja.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ---------- Guardado ----------

let temporizadorGuardado = null;
let hayCambiosSinGuardar = false;

/** Guarda solo, un rato después de la última tecla. */
function autoguardar() {
    hayCambiosSinGuardar = true;
    marcarEstado('sin guardar…');

    clearTimeout(temporizadorGuardado);
    temporizadorGuardado = setTimeout(() => { guardarPendiente(); }, 1200);
}

/**
 * Manda lo que haya escrito, sin validar y sin avanzar de paso.
 *
 * Es lo que hace que salirse de una sede, cambiar de pantalla o cerrar el
 * navegador ya no cueste nada. Va como borrador: escribe los datos pero no
 * marca el paso como terminado.
 */
async function guardarPendiente() {
    clearTimeout(temporizadorGuardado);

    if (!sede || !hayCambiosSinGuardar) return;

    hayCambiosSinGuardar = false;

    try {
        await enviar({
            accion: 'guardarFolletos', borrador: '1',
            sede_id: sede.id, aulas: aulas.map(a => ({
                id: a.id, desde: a.desde, hasta: a.hasta,
                adecDesde: a.tieneAdec ? a.adecDesde : '', adecHasta: a.tieneAdec ? a.adecHasta : '',
                sueltos: a.tieneSueltos ? a.sueltos : ''
            }))
        });

        sede.coordDesde = document.getElementById('coordDesde').value.trim();
        sede.coordHasta = document.getElementById('coordHasta').value.trim();

        await enviar({
            accion: 'guardarCoordinador', borrador: '1', sede: sede.codigo,
            desde: sede.coordDesde, hasta: sede.coordHasta,
            adecDesde: sede.coordTieneAdec ? sede.coordAdecDesde : '',
            adecHasta: sede.coordTieneAdec ? sede.coordAdecHasta : '',
            sinFolletos: sede.coordSinFolletos ? '1' : ''
        });

        marcarEstado('guardado', true);
    } catch (e) {
        hayCambiosSinGuardar = true;
        marcarEstado('no se pudo guardar');
    }
}

function marcarEstado(texto, bien = false) {
    const caja = document.getElementById('estadoGuardado');
    if (!caja) return;

    caja.textContent = texto;
    caja.className = 'estado-guardado' + (bien ? ' ok' : '');
}

async function guardarFolletos(forzado = false) {
    const r = revisarFolletos();

    if (!forzado && r.errores.length) {
        pintarProblemas('problemasFolletos', r, 'guardarFolletos(true)');
        return;
    }

    pintarProblemas('problemasFolletos', { errores: [], avisos: r.avisos }, null);

    sede.coordDesde = document.getElementById('coordDesde').value.trim();
    sede.coordHasta = document.getElementById('coordHasta').value.trim();

    // Si queda vacío y todavía no se había dicho nada, puede ser que esta
    // sede sencillamente no tenga folletos de coordinador aparte — se
    // pregunta acá, al terminar, para no dejarla "en proceso" para siempre
    // por un dato que nunca va a llegar.
    if (!sede.coordDesde && !sede.coordSinFolletos) {
        const noTiene = confirm('No escribiste folletos del coordinador para esta sede.\n\n¿Es que esta sede no tiene folletos de coordinador?');
        if (noTiene) {
            sede.coordSinFolletos = true;
            document.getElementById('coordSinFolletos').checked = true;
        } else {
            mostrar('Escribí el rango del coordinador, o marcá que esta sede no tiene.', 'error');
            return;
        }
    }

    try {
        hayCambiosSinGuardar = false;
        clearTimeout(temporizadorGuardado);

        await enviar({
            accion: 'guardarFolletos', sede_id: sede.id,
            aulas: aulas.map(a => ({
                id: a.id, desde: a.desde, hasta: a.hasta,
                adecDesde: a.tieneAdec ? a.adecDesde : '', adecHasta: a.tieneAdec ? a.adecHasta : '',
                sueltos: a.tieneSueltos ? a.sueltos : ''
            }))
        });

        await enviar({
            accion: 'guardarCoordinador', sede: sede.codigo,
            desde: sede.coordDesde, hasta: sede.coordHasta,
            adecDesde: sede.coordTieneAdec ? sede.coordAdecDesde : '',
            adecHasta: sede.coordTieneAdec ? sede.coordAdecHasta : '',
            sinFolletos: sede.coordSinFolletos ? '1' : ''
        });

        marcarHecho(0);
        marcarHecho(1);
        marcarEstado('guardado', true);

        if (forzado) {
            mostrar('Se guardó con las diferencias marcadas. Quedó anotado.', 'info');
        }

        irAPaso(3);
    } catch (e) { mostrar(e.message, 'error'); }
}

// ===================== PASO 3 · TULAS =====================

function prepararArmado() {
    if (!tulas.length) {
        tulas = [{ numero: 1, llevaCoord: true, aulas: aulas.map(a => a.id) }];
    }
    document.getElementById('cantidadTulas').value = tulas.length;
    pintarArmado();
}

function rearmar() {
    const cuantas = Math.max(1, parseInt(document.getElementById('cantidadTulas').value) || 1);
    const antes = tulas;

    tulas = Array.from({ length: cuantas }, (_, i) => ({
        numero: i + 1,
        llevaCoord: antes[i] ? antes[i].llevaCoord : (i === cuantas - 1),
        aulas: antes[i] ? antes[i].aulas.filter(id => aulas.some(a => a.id === id)) : []
    }));

    const asignadas = new Set(tulas.flatMap(t => t.aulas));
    const sueltas = aulas.filter(a => !asignadas.has(a.id)).map(a => a.id);
    if (sueltas.length) tulas[0].aulas.push(...sueltas);

    pintarArmado();
}

/**
 * Arma las tulas de dos aulas seguidas cada una: aula 1 y 2 en la tula 1,
 * aula 3 y 4 en la tula 2, etc. — no reparte por turnos (round robin), que es
 * lo que hacía antes y mezclaba aulas de puntas distintas en una misma tula.
 *
 * Si sobra una aula sola (número de aulas impar), el checkbox decide: se
 * suma a la última tula (que queda de 3) o se le arma una tula aparte solo
 * para ella. Cualquiera de las dos formas queda con los folletos del
 * coordinador en la última tula, y todo se puede seguir moviendo a mano
 * después con la tabla de abajo o el campo "¿Cuántas tulas?".
 */
function repartirParejo() {
    const POR_TULA = 2;

    if (!aulas.length) return;

    const juntarSobrante = document.getElementById('juntarSobrante').checked;
    const sobra = aulas.length % POR_TULA;

    let cuantas = Math.ceil(aulas.length / POR_TULA);
    if (sobra !== 0 && juntarSobrante && cuantas > 1) cuantas -= 1;

    tulas = Array.from({ length: cuantas }, (_, i) => ({
        numero: i + 1,
        llevaCoord: i === cuantas - 1,
        aulas: []
    }));

    aulas.forEach((a, i) => {
        const indice = Math.min(Math.floor(i / POR_TULA), cuantas - 1);
        tulas[indice].aulas.push(a.id);
    });

    document.getElementById('cantidadTulas').value = cuantas;
    pintarArmado();
}

function pintarArmado() {
    document.getElementById('cajasTulas').innerHTML = tulas.map((t, i) => {
        const dentro = t.aulas
            .map(id => aulas.find(a => a.id === id))
            .filter(Boolean);
        const estudiantes = dentro.reduce((s, a) => s + a.asignados, 0);

        return `
        <div class="tula-caja ${t.llevaCoord ? 'con-coord' : ''}">
            <div class="tula-cab">
                <strong>Tula ${t.numero}</strong>
                <span style="font-size:.8rem;color:var(--gray-500)">
                    ${dentro.length} aula(s) · ${estudiantes} estudiantes
                </span>
                <label style="display:flex;align-items:center;gap:.4rem;font-weight:600;">
                    <input type="checkbox" style="width:auto" ${t.llevaCoord ? 'checked' : ''}
                           onchange="tulas[${i}].llevaCoord = this.checked; pintarArmado()">
                    Lleva folletos del coordinador
                </label>
            </div>
            <div class="chips">
                ${dentro.length
                    ? dentro.map(a => `<span class="chip ${a.esAdecuacion ? 'adec' : ''}">
                            Aula ${escapar(a.numero)}</span>`).join('')
                    : '<span style="font-size:.8rem;color:var(--gray-400)">sin aulas</span>'}
            </div>
        </div>`;
    }).join('') + `
        <div class="tarjeta" style="box-shadow:none;border-style:dashed;">
            <h2 style="font-size:.9rem;"><i class="fa-solid fa-arrows-turn-right"></i> ¿En qué tula va cada aula?</h2>
            <div class="tabla-scroll">
                <table>
                    <thead><tr><th>Aula</th><th>Tipo</th><th>Folletos</th><th>Tula</th></tr></thead>
                    <tbody>
                        ${aulas.map(a => `
                            <tr>
                                <td><strong>${escapar(a.numero)}</strong></td>
                                <td><span class="etiqueta ${a.esAdecuacion ? 'et-adecuacion' : 'et-normal'}">
                                    ${a.esAdecuacion ? 'Adecuación' : 'Regular'}</span></td>
                                <td style="font-size:.8rem;color:var(--gray-500)">
                                    ${a.desde || '—'} → ${a.hasta || '—'}</td>
                                <td>
                                    <select onchange="moverAula(${a.id}, this.value)">
                                        ${tulas.map(t => `<option value="${t.numero}"
                                            ${t.aulas.includes(a.id) ? 'selected' : ''}>Tula ${t.numero}</option>`).join('')}
                                    </select>
                                </td>
                            </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
}

function moverAula(aulaId, numero) {
    numero = parseInt(numero);
    tulas.forEach(t => { t.aulas = t.aulas.filter(id => id !== aulaId); });
    const destino = tulas.find(t => t.numero === numero);
    if (destino) destino.aulas.push(aulaId);
    pintarArmado();
}

async function guardarTulas() {
    const vacias = tulas.filter(t => !t.aulas.length && !t.llevaCoord);
    if (vacias.length && !confirm(
        `Hay ${vacias.length} tula(s) sin aulas ni folletos de coordinador.\n\n¿Guardar igual?`)) return;

    try {
        const d = await enviar({ accion: 'guardarTulas', sede: sede.codigo, tulas });
        mostrar(`${d.tulas} tula(s) guardadas.`, 'exito');
        marcarHecho(2);

        const det = await pedir(`${API}?accion=sede&sede=${encodeURIComponent(sede.codigo)}`);
        tulas = det.tulas.map(t => ({
            id: t.id, numero: t.numero, llevaCoord: t.llevaCoord,
            aulas: t.aulas.map(a => a.id),
            marchamo1: t.marchamo1, marchamo2: t.marchamo2
        }));

        irAPaso(4);
    } catch (e) { mostrar(e.message, 'error'); }
}

// ===================== PASO 4 · MARCHAMOS =====================

function pintarMarchamos() {
    document.getElementById('tablaMarchamos').innerHTML = tulas.map(t => {
        const dentro = t.aulas.map(id => aulas.find(a => a.id === id)).filter(Boolean);
        const contenido = dentro.map(a => 'Aula ' + a.numero).join(', ')
            + (t.llevaCoord ? (dentro.length ? ' + ' : '') + 'folletos del coordinador' : '');

        return `
        <tr>
            <td><strong>Tula ${t.numero}</strong></td>
            <td style="font-size:.82rem;color:var(--gray-600)">${escapar(contenido) || '—'}</td>
            <td><input type="text" value="${escapar(t.marchamo1 || '')}"
                       data-tula="${t.id}" data-pos="1" placeholder="escanear…"
                       oninput="revisarMarchamos()"></td>
            <td><input type="text" value="${escapar(t.marchamo2 || '')}"
                       data-tula="${t.id}" data-pos="2" placeholder="escanear…"
                       oninput="revisarMarchamos()"></td>
        </tr>`;
    }).join('');

    // El lector de códigos escribe el número y manda un Enter. Al atarlo al
    // salto de campo, se escanean los dos marchamos de una tula y el foco cae
    // solo en la siguiente: la tanda entera se hace sin tocar el mouse.
    const campos = [...document.querySelectorAll('#tablaMarchamos input')];

    campos.forEach((campo, i) => {
        campo.addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;

            e.preventDefault();

            // Al terminar el último, en vez de quedarse ahí se guarda: es el
            // gesto que la persona iba a hacer de todos modos.
            if (i === campos.length - 1) {
                campo.blur();
                guardarMarchamos();
                return;
            }

            campos[i + 1].focus();
            campos[i + 1].select();
        });

        campo.addEventListener('focus', () => campo.select());
    });

    const primerVacio = campos.find(c => !c.value.trim()) || campos[0];
    if (primerVacio) primerVacio.focus();

    revisarMarchamos();
}

/**
 * Marca los códigos repetidos mientras se escanean.
 *
 * El servidor los rechaza igual —el índice único no deja dos veces el mismo—
 * pero enterarse al guardar significa rehacer la tanda. Enterarse en el momento
 * significa volver a escanear ese bulto y seguir.
 */
function revisarMarchamos() {
    const campos = [...document.querySelectorAll('#tablaMarchamos input')];
    const vistos = {};

    campos.forEach(c => {
        const v = c.value.trim();
        if (v) vistos[v] = (vistos[v] || 0) + 1;
    });

    const repetidos = Object.keys(vistos).filter(v => vistos[v] > 1);

    campos.forEach(c => c.classList.toggle('malo', repetidos.includes(c.value.trim())));

    pintarProblemas('problemasMarchamos', {
        errores: repetidos.map(v => `El código ${v} está puesto en más de una tula.`),
        avisos: [],
    }, null);

    return repetidos;
}

/**
 * Guarda los marchamos escaneados hasta el momento.
 *
 * No exige tenerlos todos: una tula puede quedar con uno o ninguno para
 * digitarlo después, y aun así lo ya escaneado no se pierde. Solo se marca
 * la sede como lista cuando de verdad no falta ningún marchamo.
 */
async function guardarMarchamos() {
    if (revisarMarchamos().length) {
        mostrar('Hay códigos repetidos. Corregilos antes de guardar.', 'error');
        return;
    }

    const datos = tulas.map(t => ({
        tulaId: t.id,
        marchamo1: (document.querySelector(`[data-tula="${t.id}"][data-pos="1"]`) || {}).value.trim() || '',
        marchamo2: (document.querySelector(`[data-tula="${t.id}"][data-pos="2"]`) || {}).value.trim() || ''
    }));

    const faltan = datos.filter(d => !d.marchamo1 || !d.marchamo2);

    try {
        const d = await enviar({ accion: 'guardarMarchamos', marchamos: datos });

        if (d.rechazados && d.rechazados.length) {
            pintarProblemas('problemasMarchamos', {
                errores: d.rechazados.map(r => `${r.codigo}: ${r.motivo}`),
                avisos: [],
            }, null);
            mostrar(`${d.aplicados} guardados, ${d.rechazados.length} rechazados. Mirá el detalle abajo.`, 'error');
        } else if (faltan.length) {
            pintarProblemas('problemasMarchamos', {
                errores: [],
                avisos: faltan.map(d => {
                    const t = tulas.find(x => x.id === d.tulaId);
                    return `A la tula ${t ? t.numero : d.tulaId} le falta el marchamo `
                         + (!d.marchamo1 ? 'de salida' : 'de retorno') + '.';
                }),
            }, null);
            mostrar(`${d.aplicados} marchamo(s) guardados. Podés seguir digitando los que faltan más tarde.`, 'exito');
        } else {
            pintarProblemas('problemasMarchamos', { errores: [], avisos: [] }, null);
            mostrar(`${d.aplicados} marchamo(s) guardados. La sede quedó lista.`, 'exito');
            marcarHecho(3);

            const enLista = sedes.find(s => s.codigo === sede.codigo);
            if (enLista) enLista.completa = true;

            // Desbloquear la sede al terminarla
            await desbloquearSede(sede.codigo);
            mostrarFinDeSede();
        }

        // Se refleja tal cual se mandó, vacío incluido: si se borró un
        // código a propósito, la pantalla tiene que mostrarlo vacío y no
        // reponer lo que había antes.
        datos.forEach(x => {
            const t = tulas.find(y => y.id === x.tulaId);
            if (t) { t.marchamo1 = x.marchamo1; t.marchamo2 = x.marchamo2; }
        });
    } catch (e) { mostrar(e.message, 'error'); }
}

// ===================== UTILIDADES =====================

function marcarHecho(indice) {
    if (!sede.pasos) sede.pasos = [false, false, false, false];
    sede.pasos[indice] = true;

    const enLista = sedes.find(s => s.codigo === sede.codigo);
    if (enLista) enLista.pasos = sede.pasos;

    pintarPasos();
}

async function pedir(url) {
    const r = await fetch(url + (url.includes('?') ? '&' : '?') + '_ts=' + Date.now(),
                          { cache: 'no-store' });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo consultar');
    return d;
}

/**
 * Manda `quien` en toda escritura, no solo en los latidos de bloqueo.
 *
 * La cuenta de sesión es la misma para todo el personal extraordinario (ver
 * el comentario de `quien` más arriba): sin esto, la bitácora solo puede
 * decir "Personal Extraordinario" y no distingue quién de todos hizo qué.
 */
async function enviar(cuerpo) {
    const r = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...cuerpo, quien, csrf: CSRF })
    });
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'No se pudo guardar');
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

// ------------------------------------------------------------------
// Edición de datos base (candado de supervisor)
// ------------------------------------------------------------------
//
// Ver el encabezado de includes/sesion.php sobre autorizar_edicion_base().
// El desbloqueo es de la SESIÓN del navegador, no de esta sede en particular:
// una vez abierto, sigue abierto al cambiar de sede hasta que se vence solo
// o alguien lo cierra con "Bloquear ahora".

let edicionBase = { autorizada: false, autorizadoPor: null, hasta: null, puedeAutoAutorizar: false };
let temporizadorBase = null;

/** Aplica un estado nuevo de edicionBase (viene del servidor en cada acción) y redibuja todo lo que depende de él. */
function actualizarEdicionBase(datos) {
    edicionBase = datos || edicionBase;
    pintarCandadoBase();

    const cajaAgregar = document.getElementById('cajaAgregarAula');
    if (cajaAgregar) cajaAgregar.style.display = edicionBase.autorizada ? '' : 'none';

    // La tabla ya pintada tiene que pasar a editable (o volver a texto) sin
    // esperar a la próxima carga de sede.
    if (typeof pintarFolletos === 'function' && sede) pintarFolletos();

    clearInterval(temporizadorBase);
    if (edicionBase.autorizada) {
        temporizadorBase = setInterval(actualizarCandadoCuentaRegresiva, 1000);
        actualizarCandadoCuentaRegresiva();
    }
}

function pintarCandadoBase() {
    const boton = document.getElementById('btnCandadoBase');
    if (!boton) return;   // el paso 2 (Folletos) todavía no se pintó

    const texto = document.getElementById('candadoBaseTexto');
    const aviso = document.getElementById('avisoBaseDesbloqueada');
    const icono = boton.querySelector('i');

    if (edicionBase.autorizada) {
        boton.classList.add('desbloqueado');
        icono.className = 'fa-solid fa-lock-open';
        texto.textContent = 'Datos base desbloqueados';
        aviso.style.display = 'flex';
    } else {
        boton.classList.remove('desbloqueado');
        icono.className = 'fa-solid fa-lock';
        texto.textContent = 'Editar datos base';
        aviso.style.display = 'none';
    }
}

function actualizarCandadoCuentaRegresiva() {
    const avisoTexto = document.getElementById('avisoBaseTexto');
    if (!avisoTexto || !edicionBase.autorizada || !edicionBase.hasta) return;

    const restante = edicionBase.hasta - Math.floor(Date.now() / 1000);

    if (restante <= 0) {
        actualizarEdicionBase({ autorizada: false, autorizadoPor: null, hasta: null, puedeAutoAutorizar: edicionBase.puedeAutoAutorizar });
        mostrar('Se venció la autorización para editar datos base.', 'info');
        return;
    }

    const min = Math.floor(restante / 60);
    const seg = String(restante % 60).padStart(2, '0');
    avisoTexto.textContent = `Desbloqueado por ${escapar(edicionBase.autorizadoPor)} · se cierra en ${min}:${seg}`;
}

function abrirModal(id) { document.getElementById(id).classList.add('visible'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('visible'); }

document.addEventListener('DOMContentLoaded', () => {
    const btnCandado = document.getElementById('btnCandadoBase');
    if (!btnCandado) return;

    btnCandado.addEventListener('click', () => {
        if (edicionBase.autorizada) return;   // ya desbloqueado, no hay nada que pedir

        document.getElementById('desbloquearPropio').style.display = edicionBase.puedeAutoAutorizar ? 'block' : 'none';
        document.getElementById('desbloquearCampos').style.display = edicionBase.puedeAutoAutorizar ? 'none' : 'block';
        document.getElementById('desbloquearUsuario').value = '';
        document.getElementById('desbloquearClave').value = '';
        document.getElementById('desbloquearAviso').className = 'aviso';
        document.getElementById('desbloquearAviso').textContent = '';
        abrirModal('modalDesbloquearBase');
    });

    document.getElementById('btnUsarOtraClave').addEventListener('click', () => {
        document.getElementById('desbloquearPropio').style.display = 'none';
        document.getElementById('desbloquearCampos').style.display = 'block';
        document.getElementById('desbloquearUsuario').focus();
    });

    document.getElementById('btnAutorizarPropio').addEventListener('click', async () => {
        const boton = document.getElementById('btnAutorizarPropio');
        boton.disabled = true;
        try {
            const d = await enviar({ accion: 'autorizarPropioBase' });
            cerrarModal('modalDesbloquearBase');
            actualizarEdicionBase(d);
            mostrar('Datos base desbloqueados.', 'exito');
        } catch (e) {
            const av = document.getElementById('desbloquearAviso');
            av.className = 'aviso error'; av.textContent = e.message;
        } finally {
            boton.disabled = false;
        }
    });

    document.getElementById('formDesbloquearBase').addEventListener('submit', async (ev) => {
        ev.preventDefault();

        const usuario = document.getElementById('desbloquearUsuario').value.trim();
        const clave   = document.getElementById('desbloquearClave').value;
        const av      = document.getElementById('desbloquearAviso');

        if (!usuario || !clave) {
            av.className = 'aviso error'; av.textContent = 'Escribí el usuario y la contraseña.';
            return;
        }

        const boton = document.getElementById('btnConfirmarDesbloqueo');
        boton.disabled = true;

        try {
            const d = await enviar({ accion: 'autorizarEdicionBase', usuario, clave });
            cerrarModal('modalDesbloquearBase');
            actualizarEdicionBase(d);
            mostrar(`Datos base desbloqueados por ${d.autorizadoPor}.`, 'exito');
        } catch (e) {
            av.className = 'aviso error'; av.textContent = e.message;
        } finally {
            boton.disabled = false;
        }
    });

    document.getElementById('formAgregarAula').addEventListener('submit', async (ev) => {
        ev.preventDefault();

        const numero = document.getElementById('nuevaAulaNumero').value.trim();
        const av = document.getElementById('agregarAulaAviso');

        if (!numero) {
            av.className = 'aviso error'; av.textContent = 'Escribí el número del aula.';
            return;
        }
        if (!sede) return;

        try {
            const d = await enviar({
                accion: 'agregarAulaBase',
                sede: sede.codigo,
                numero,
                asignados: document.getElementById('nuevaAulaEstudiantes').value || '0',
                formula: document.getElementById('nuevaAulaFormula').value.trim(),
                esAdecuacion: document.getElementById('nuevaAulaAdecuacion').checked ? '1' : '0',
            });

            aulas = d.aulas;
            aulas.forEach(a => { a.tieneAdec = !!a.adecDesde; a.tieneSueltos = !!a.folletosSueltos; a.sueltos = a.folletosSueltos || ''; });
            actualizarEdicionBase(d.edicionBase);
            pintarCabecera();
            cerrarModal('modalAgregarAulaBase');
            document.getElementById('formAgregarAula').reset();
            mostrar(`Aula ${numero} agregada.`, 'exito');
        } catch (e) {
            av.className = 'aviso error'; av.textContent = e.message;
        }
    });
});

function bloquearBaseAhora() {
    enviar({ accion: 'bloquearEdicionBase' })
        .then(d => { actualizarEdicionBase(d); mostrar('Edición de datos base bloqueada.', 'info'); })
        .catch(e => mostrar(e.message, 'error'));
}

function abrirAgregarAula() {
    document.getElementById('formAgregarAula').reset();
    document.getElementById('agregarAulaAviso').className = 'aviso';
    document.getElementById('agregarAulaAviso').textContent = '';
    abrirModal('modalAgregarAulaBase');
}

/**
 * Guarda desde la tabla un cambio a número/tipo/fórmula/estudiantes de un
 * aula. Se llama al salir del campo (blur/change), no en cada tecla: son
 * cuatro columnas por fila y no hace falta un viaje al servidor por letra.
 */
async function guardarAulaBaseDesdeFila(aulaId) {
    if (!edicionBase.autorizada || !sede) return;

    const fila = document.querySelector(`tr[data-fila-base="${aulaId}"]`);
    if (!fila) return;

    const numero    = fila.querySelector('[data-campo-base="numero"]').value.trim();
    const asignados = fila.querySelector('[data-campo-base="asignados"]').value;
    const formula   = fila.querySelector('[data-campo-base="formula"]').value.trim();
    const tipo      = fila.querySelector('[data-campo-base="tipo"]').value;

    if (!numero) {
        mostrar('El número de aula no puede quedar vacío.', 'error');
        pintarFolletos();
        return;
    }

    try {
        const d = await enviar({
            accion: 'editarAulaBase',
            sede: sede.codigo,
            aula: aulaId,
            numero,
            asignados,
            formula,
            esAdecuacion: tipo === 'adecuacion' ? '1' : '0',
        });

        aulas = d.aulas;
        aulas.forEach(a => { a.tieneAdec = !!a.adecDesde; a.tieneSueltos = !!a.folletosSueltos; a.sueltos = a.folletosSueltos || ''; });
        actualizarEdicionBase(d.edicionBase);
        pintarCabecera();
        mostrar(`Aula ${numero} actualizada.`, 'exito');
    } catch (e) {
        mostrar(e.message, 'error');
        pintarFolletos();   // vuelve a lo que había en el servidor
    }
}
</script>

<!-- ===== EDICIÓN DE DATOS BASE: ventanas emergentes ===== -->

<!-- Desbloqueo. Dos formas: la propia cuenta (si ya tiene el permiso, sin
     volver a escribir la clave que ya puso al entrar) o la clave de otra
     persona autorizada, para cuando quien está digitando no tiene el permiso
     — el caso normal de personal extraordinario. -->
<div class="fondo-modal" id="modalDesbloquearBase" role="dialog" aria-modal="true"
     aria-labelledby="desbloquearTitulo" data-paa-foco="#desbloquearUsuario">
    <div class="modal-caja">
        <h2 id="desbloquearTitulo">Editar datos base</h2>
        <p>
            Cambia número de aula, tipo, fórmula o cantidad de estudiantes. Necesita la clave de
            administración, desarrollo, o de alguien autorizado puntualmente para esto.
        </p>

        <div id="desbloquearPropio" style="display:none;margin-bottom:1rem;">
            <button type="button" class="btn-primario" id="btnAutorizarPropio" style="width:100%;">
                <i class="fa-solid fa-user-check"></i> <span id="desbloquearPropioTexto">Autorizar con mi cuenta</span>
            </button>
            <button type="button" class="modal-enlace-alterno" id="btnUsarOtraClave">
                Usar la clave de otra persona
            </button>
        </div>

        <form id="formDesbloquearBase">
            <div id="desbloquearCampos">
                <div class="modal-campo">
                    <label for="desbloquearUsuario">Usuario (correo hasta la arroba)</label>
                    <input type="text" id="desbloquearUsuario" autocomplete="off" placeholder="ej. carlos.solera">
                </div>
                <div class="modal-campo">
                    <label for="desbloquearClave">Contraseña</label>
                    <input type="password" id="desbloquearClave" autocomplete="off">
                </div>
            </div>

            <div id="desbloquearAviso" class="aviso" style="margin-top:.5rem;"></div>

            <div class="modal-acciones">
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalDesbloquearBase')">Cancelar</button>
                <button type="submit" class="btn-primario" id="btnConfirmarDesbloqueo">
                    <i class="fa-solid fa-lock-open"></i> Desbloquear
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Agregar una aula que no vino en la base. Solo visible ya desbloqueado. -->
<div class="fondo-modal" id="modalAgregarAulaBase" role="dialog" aria-modal="true"
     aria-labelledby="agregarAulaTitulo" data-paa-foco="#nuevaAulaNumero">
    <div class="modal-caja">
        <h2 id="agregarAulaTitulo">Agregar un aula</h2>
        <p>Para una aula que hay que embalar pero que no está en la base todavía.</p>

        <form id="formAgregarAula">
            <div class="modal-campo">
                <label for="nuevaAulaNumero">Número de aula</label>
                <input type="text" id="nuevaAulaNumero" autocomplete="off" required>
            </div>
            <div class="modal-campo">
                <label for="nuevaAulaEstudiantes">Cantidad de estudiantes</label>
                <input type="number" id="nuevaAulaEstudiantes" min="0" max="2000" value="0">
            </div>
            <div class="modal-campo">
                <label for="nuevaAulaFormula">Fórmula</label>
                <input type="text" id="nuevaAulaFormula" autocomplete="off">
            </div>
            <div class="modal-campo modal-casilla">
                <input type="checkbox" id="nuevaAulaAdecuacion">
                <label for="nuevaAulaAdecuacion" style="text-transform:none;font-weight:600;">Es un aula de adecuación</label>
            </div>

            <div id="agregarAulaAviso" class="aviso" style="margin-top:.5rem;"></div>

            <div class="modal-acciones">
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalAgregarAulaBase')">Cancelar</button>
                <button type="submit" class="btn-primario"><i class="fa-solid fa-plus"></i> Agregar</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== RANKING AMISTOSO ===== -->
<style>
    .rk-boton {
        position: fixed; left: 1.25rem; bottom: 5.1rem; z-index: 9997;
        width: 3.4rem; height: 3.4rem; border-radius: 50%; border: none; cursor: pointer;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.25);
        display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
        transition: transform .15s ease;
    }
    .rk-boton:hover { transform: translateY(-2px); }

    .rk-panel {
        position: fixed; left: 1.25rem; bottom: 9.4rem; z-index: 9997;
        width: min(400px, calc(100vw - 2.5rem));
        max-height: min(600px, calc(100vh - 11.5rem));
        background: #fff; border-radius: 1.1rem; box-shadow: 0 20px 45px rgba(0,0,0,.28);
        overflow: hidden; display: none; flex-direction: column;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    .rk-panel.abierto { display: flex; }

    .rk-cabecera {
        background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;
        padding: .9rem 1.1rem; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    }
    .rk-cabecera strong { font-size: .92rem; }
    .rk-cabecera small { display: block; font-size: .72rem; opacity: .85; font-weight: 400; }
    .rk-cerrar {
        background: rgba(255,255,255,.18); border: none; color: #fff; width: 1.8rem; height: 1.8rem;
        border-radius: 50%; cursor: pointer; font-size: .9rem;
    }
    .rk-cerrar:hover { background: rgba(255,255,255,.32); }

    .rk-tabs { display: flex; gap: .4rem; padding: .7rem .9rem 0; flex-shrink: 0; }
    .rk-tab {
        flex: 1; border: none; background: var(--gray-100); color: var(--gray-600);
        font-family: inherit; font-size: .78rem; font-weight: 700; padding: .5rem; border-radius: .5rem .5rem 0 0; cursor: pointer;
    }
    .rk-tab.activo { background: #fff7ed; color: #b45309; }

    .rk-cuerpo { flex: 1; overflow-y: auto; padding: .9rem 1.1rem 1.1rem; background: #fff7ed22; }

    .rk-record {
        background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 1px solid #fed7aa;
        border-radius: .75rem; padding: .8rem 1rem; margin-bottom: .9rem;
        font-size: .82rem; color: #7c2d12; display: flex; gap: .6rem; align-items: flex-start;
    }
    .rk-record i { color: #f59e0b; margin-top: .1rem; }

    .rk-fila {
        display: flex; align-items: center; gap: .7rem; padding: .55rem .3rem;
        border-bottom: 1px solid var(--gray-100);
    }
    .rk-puesto {
        width: 1.6rem; text-align: center; font-weight: 800; font-size: .85rem; color: var(--gray-400); flex-shrink: 0;
    }
    .rk-puesto.oro { color: #d97706; }
    .rk-puesto.plata { color: #9ca3af; }
    .rk-puesto.bronce { color: #b45309; }
    .rk-avatar {
        width: 2.1rem; height: 2.1rem; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700;
    }
    .rk-datos { flex: 1; min-width: 0; }
    .rk-nombre { font-size: .85rem; font-weight: 700; color: var(--gray-800); }
    .rk-detalle { font-size: .74rem; color: var(--gray-500); }
    .rk-numero { font-size: 1.05rem; font-weight: 800; color: #b45309; flex-shrink: 0; }

    .rk-vacio { text-align: center; color: var(--gray-400); font-size: .82rem; padding: 1.5rem 1rem; }
</style>

<button type="button" class="rk-boton" id="rkBoton" title="Ranking de Ingreso de Datos">
    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
</button>

<div class="rk-panel" id="rkPanel">
    <div class="rk-cabecera">
        <div>
            <strong>🏆 Ranking</strong>
            <small>Sedes completadas por persona</small>
        </div>
        <button type="button" class="rk-cerrar" id="rkCerrar" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="rk-tabs">
        <button type="button" class="rk-tab activo" data-tab="hoy">Hoy</button>
        <button type="button" class="rk-tab" data-tab="totales">Total</button>
        <button type="button" class="rk-tab" data-tab="enProceso">En proceso</button>
    </div>
    <div class="rk-cuerpo" id="rkCuerpo">
        <div class="rk-vacio">Cargando…</div>
    </div>
</div>

<script>
(function () {
    const boton = document.getElementById('rkBoton');
    const panel = document.getElementById('rkPanel');
    const cerrar = document.getElementById('rkCerrar');
    const cuerpo = document.getElementById('rkCuerpo');
    const tabs = document.querySelectorAll('.rk-tab');

    let datos = null;
    let tabActiva = 'hoy';

    function escaparRk(t) {
        return String(t ?? '').replace(/[&<>"]/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    }

    function inicialesRk(nombre) {
        const partes = String(nombre || '?').trim().split(/\s+/);
        return ((partes[0]?.[0] || '?') + (partes[1]?.[0] || '')).toUpperCase();
    }

    function fechaLegibleRk(txt) {
        if (!txt) return '';
        const [a, m, d] = String(txt).split('-');
        return `${d}/${m}/${a}`;
    }

    function medallaRk(i) {
        return i === 0 ? 'oro' : i === 1 ? 'plata' : i === 2 ? 'bronce' : '';
    }

    async function cargarRanking() {
        cuerpo.innerHTML = '<div class="rk-vacio"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>';
        try {
            const r = await fetch(`${API}?accion=ranking`, { cache: 'no-store' });
            const d = await r.json();
            if (!d.success) throw new Error(d.error || 'No se pudo cargar el ranking');
            datos = d;
            pintarTab();
        } catch (e) {
            cuerpo.innerHTML = `<div class="rk-vacio">${escaparRk(e.message)}</div>`;
        }
    }

    function pintarTab() {
        if (!datos) return;

        const record = datos.record ? `
            <div class="rk-record">
                <i class="fa-solid fa-fire"></i>
                <div><strong>Récord del día:</strong> ${escaparRk(datos.record.quien)} completó
                ${datos.record.total} sede(s) el ${fechaLegibleRk(datos.record.dia)}.</div>
            </div>` : '';

        if (tabActiva === 'enProceso') {
            cuerpo.innerHTML = (!datos.enProceso.length
                ? '<div class="rk-vacio">Nadie tiene sedes a medias ahora mismo.</div>'
                : datos.enProceso.map((f, i) => `
                    <div class="rk-fila">
                        <div class="rk-puesto ${medallaRk(i)}">${i + 1}</div>
                        <div class="rk-avatar">${escaparRk(inicialesRk(f.quien))}</div>
                        <div class="rk-datos">
                            <div class="rk-nombre">${escaparRk(f.quien)}</div>
                            <div class="rk-detalle">en proceso</div>
                        </div>
                        <div class="rk-numero">${f.sedes}</div>
                    </div>`).join(''));
            return;
        }

        const lista = tabActiva === 'hoy' ? datos.hoy : datos.totales;

        cuerpo.innerHTML = record + (!lista.length
            ? '<div class="rk-vacio">Todavía no hay sedes completadas.</div>'
            : lista.map((f, i) => `
                <div class="rk-fila">
                    <div class="rk-puesto ${medallaRk(i)}">${i + 1}</div>
                    <div class="rk-avatar">${escaparRk(inicialesRk(f.quien))}</div>
                    <div class="rk-datos">
                        <div class="rk-nombre">${escaparRk(f.quien)}</div>
                        <div class="rk-detalle">${f.sedes} sede(s) participada(s)</div>
                    </div>
                    <div class="rk-numero">${f.total}</div>
                </div>`).join(''));
    }

    tabs.forEach(t => t.addEventListener('click', () => {
        tabActiva = t.dataset.tab;
        tabs.forEach(x => x.classList.toggle('activo', x === t));
        pintarTab();
    }));

    boton.addEventListener('click', () => {
        const abrir = !panel.classList.contains('abierto');
        panel.classList.toggle('abierto', abrir);
        if (abrir) cargarRanking(); // siempre trae lo último al abrir
    });
    cerrar.addEventListener('click', () => panel.classList.remove('abierto'));
})();
</script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>