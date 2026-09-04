<?php
/**
 * Módulo de uso interno: solo administración.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("directorio", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Directorio con datos de contacto del personal: se pide a los buscadores
         que no lo indexen. -->
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Funcionarios · UCR</title>
  <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== RESET & VARIABLES ===== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            --info: #3b82f6;
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
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --radius-2xl: 2rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .app {
            max-width: 1440px;
            margin: 0 auto;
        }

        h1, h2, h3, h4 {
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        /* ===== CARDS ===== */

        .card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            transition: all 0.2s ease;
        }

        .card:hover {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(255, 255, 255, 0.8) inset;
        }

        .card-header {
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* ===== BANNER DE ERROR ===== */

        .banner-respaldo {
            display: none;
            align-items: center;
            gap: 1rem;
            background: #fef2f2;
            border: 2px solid #dc2626;
            border-left-width: 6px;
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            color: #7f1d1d;
        }

        .banner-respaldo.visible { display: flex; }

        .banner-respaldo > i {
            font-size: 1.75rem;
            color: #dc2626;
            flex-shrink: 0;
        }

        .banner-respaldo-texto {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            flex: 1;
        }

        .banner-respaldo-texto strong { font-size: 1rem; }
        .banner-respaldo-texto span { font-size: 0.875rem; }

        .banner-respaldo-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            transition: background 0.2s;
        }

        .banner-respaldo-btn:hover { background: #b91c1c; }

        /* ===== HERO ===== */

        .hero {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--gray-600);
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-2xl);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        /* ===== STATS ===== */

        .stats-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .sello-actualizacion {
            font-size: 0.8125rem;
            color: var(--gray-500);
            margin-right: auto;
        }

        .sello-actualizacion.viejo {
            color: var(--warning);
            font-weight: 600;
        }

        .btn-refrescar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            border-radius: var(--radius-2xl);
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }

        .btn-refrescar:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-refrescar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-refrescar.cargando i {
            animation: spin 0.8s linear infinite;
        }

        .stat-badge {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-2xl);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: var(--shadow);
            color: var(--gray-700);
            font-weight: 500;
        }

        .stat-badge i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .btn-principal {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: var(--radius-2xl);
            padding: 0.8rem 1.3rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-principal:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-principal:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-principal.secundario {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary);
            border: 1px solid var(--gray-200);
        }

        .btn-peligro {
            background: #fff;
            color: var(--danger);
            border: 1px solid #fecaca;
            border-radius: var(--radius);
            padding: 0.45rem 0.7rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .btn-peligro:hover {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .btn-peligro:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ===== FORMULARIO ===== */

        .form-section { margin-bottom: 1.5rem; }

        .form-section.colapsada {
            display: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .campo label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .campo input {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            background: white;
            font-size: 0.95rem;
        }

        .campo input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(110, 193, 228, 0.15);
        }

        .campo-full { grid-column: 1 / -1; }

        .nota-campo {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .acciones-form {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
        }

        /* ===== SEARCH ===== */

        .search-section { margin-bottom: 1.5rem; }

        .search-box { position: relative; }

        .search-box i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.125rem;
            pointer-events: none;
            z-index: 1;
        }

        .search-box input {
            width: 100%;
            padding: 1.125rem 1.25rem 1.125rem 3rem;
            border: 1px solid rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-2xl);
            font-size: 1rem;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--secondary);
            background: white;
            box-shadow: 0 0 0 4px rgba(110, 193, 228, 0.15), var(--shadow);
        }

        .clear-search {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            display: none;
            transition: color 0.2s;
            font-size: 1.125rem;
        }

        .clear-search:hover { color: var(--danger); }

        .sede-item-count {
            background: var(--gray-100);
            border-radius: var(--radius-2xl);
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        /* ===== DIRECTORIO ===== */

        .area-bloque { margin-bottom: 2rem; }

        .area-titulo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--primary);
            font-size: 1.15rem;
        }

        .area-titulo .cuenta {
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: var(--radius-2xl);
        }

        .personas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .persona-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1.1rem 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .persona-card:hover {
            border-color: var(--secondary);
            box-shadow: var(--shadow);
        }

        .persona-acciones {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .btn-horario {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .4rem .65rem; border: 1px solid var(--gray-200); border-radius: .5rem;
            background: var(--gray-50); color: var(--gray-600); font: inherit; font-size: .74rem; font-weight: 700;
            cursor: pointer; white-space: nowrap;
        }
        .btn-horario:hover { background: #eaf3fb; border-color: var(--secondary); color: var(--primary-dark); }

        /* ---------- modal de horario ---------- */
        .fondo-modal {
            display: none; position: fixed; inset: 0; background: rgba(17,24,39,.55);
            align-items: center; justify-content: center; z-index: 200; padding: 1rem;
        }
        .fondo-modal.visible { display: flex; }
        .modal-horario {
            background: #fff; border-radius: 14px; padding: 1.5rem; max-width: 900px; width: 100%;
            max-height: 88vh; overflow-y: auto;
        }
        .modal-horario .modal-cerrar {
            float: right; background: none; border: none; font-size: 1.3rem; color: var(--gray-400); cursor: pointer;
        }
        .modal-horario h3 { color: var(--gray-900); margin-bottom: .2rem; }
        .modal-horario .sub { color: var(--gray-500); font-size: .82rem; margin-bottom: 1rem; }

        .controles-semana {
            display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.1rem;
        }
        .controles-semana input[type="date"] {
            padding: .45rem .6rem; border: 1px solid var(--gray-300); border-radius: .5rem; font: inherit; font-size: .82rem;
        }
        .controles-semana button {
            padding: .45rem .65rem; border: 1px solid var(--gray-200); border-radius: .5rem;
            background: var(--gray-50); color: var(--gray-700); font: inherit; font-size: .78rem; font-weight: 700; cursor: pointer;
        }
        .controles-semana button:hover { background: var(--gray-100); }

        .semanario-modal { display: grid; grid-template-columns: repeat(5, 1fr); gap: .75rem; }
        @media (max-width: 760px) { .semanario-modal { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 420px) { .semanario-modal { grid-template-columns: 1fr; } }

        .dia-modal {
            border: 1px solid var(--gray-200); border-radius: .6rem; padding: .7rem; display: flex; flex-direction: column; gap: .4rem;
        }
        /* Cada día es una parada de tabulación: tiene que verse cuál está
           enfocado, no solo escucharse. */
        .dia-modal:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
        .dia-modal .dia-titulo { font-size: .82rem; font-weight: 700; color: var(--gray-800); }
        .dia-modal .dia-fecha { font-size: .68rem; color: var(--gray-400); margin-bottom: .1rem; }
        .dia-modal .situacion-banner {
            font-size: .7rem; font-weight: 700; padding: .3rem .5rem; border-radius: .4rem; background: #fef3c7; color: #92400e;
        }
        .dia-modal .franja {
            font-size: .74rem; font-weight: 600; padding: .3rem .5rem; border-radius: .4rem;
        }
        .dia-modal .franja.presencial { background: #eaf3fb; color: var(--primary-dark); }
        .dia-modal .franja.virtual { background: #f3e8ff; color: #6b21a8; }
        .dia-modal .sin-franjas { font-size: .72rem; color: var(--gray-400); font-style: italic; }

        .persona-inicial {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .persona-datos {
            flex: 1;
            min-width: 0;
        }

        .persona-nombre {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.35rem;
        }

        .persona-contacto {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            font-size: 0.875rem;
        }

        .persona-contacto a {
            color: var(--gray-600);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .persona-contacto a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .persona-contacto i {
            color: var(--gray-400);
            width: 14px;
            flex-shrink: 0;
        }

        .persona-contacto .extension {
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .persona-contacto .sin-dato {
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-style: italic;
        }

        /* ===== LOADING ===== */

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        @keyframes toastIn {
            from { opacity: 0; transform: translateX(-16px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-16px); }
        }

        /* ===== EMPTY STATES ===== */

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .empty-state p { color: var(--gray-500); }

        :focus-visible {
            outline: 3px solid var(--secondary);
            outline-offset: 2px;
        }

        .barra-sesion {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra-sesion a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .personas-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .acciones-form { justify-content: stretch; }
            .acciones-form .btn-principal { width: 100%; justify-content: center; }
        }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--gray-100); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--gray-400); }
    </style>
</head>
<body>
    <div class="app">
        <div class="barra-sesion">
            <span><i class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
            <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
        </div>

        <div id="loadingOverlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <div id="bannerRespaldo" class="banner-respaldo">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="banner-respaldo-texto">
                <strong>Sin conexión con el servidor</strong>
                <span>No se pudo cargar el directorio. Los datos que ves pueden estar incompletos o desactualizados.</span>
            </div>
            <button id="btnReintentarCarga" class="banner-respaldo-btn">
                <i class="fa-solid fa-rotate"></i> Reintentar
            </button>
        </div>

        <div class="hero">
            <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
                <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
            </div>
            <h1>Funcionarios PAA</h1>
            <p>Universidad de Costa Rica · IIP</p>
        </div>

        <div class="stats-row">
            <span id="selloActualizacion" class="sello-actualizacion"></span>
            <button id="btnRefrescarDatos" class="btn-refrescar" title="Volver a consultar el directorio">
                <i class="fa-solid fa-rotate"></i> Actualizar datos
            </button>
            <button id="btnExportarDirectorio" class="btn-refrescar" title="Descargar el directorio como Excel">
                <i class="fa-solid fa-file-excel"></i> Descargar Excel
            </button>
            <div class="stat-badge">
                <i class="fa-solid fa-users"></i>
                <span id="totalPersonas">0 funcionarios</span>
            </div>
        </div>


        <div class="card search-section">
            <div class="card-header">
                <h3><i class="fa-solid fa-magnifying-glass"></i> Buscar en funcionarios</h3>
                <span class="sede-item-count" id="totalAreas">0 áreas</span>
            </div>
            <div class="card-body">
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="buscadorInput" placeholder="Buscar por nombre, área, correo, extensión o teléfono...">
                    <button id="clearSearchBtn" class="clear-search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="directorioContainer">
            <div class="empty-state">
                <i class="fa-regular fa-address-book"></i>
                <h3>Cargando directorio...</h3>
                <p>Consultando la base de datos</p>
            </div>
        </div>
    </div>

    <div class="fondo-modal" id="modalHorario">
        <div class="modal-horario" role="dialog" aria-modal="true" aria-labelledby="horarioNombre">
            <button type="button" class="modal-cerrar" id="btnCerrarHorario"
                    aria-label="Cerrar el horario">&times;</button>
            <h3 id="horarioNombre" tabindex="-1"></h3>
            <p class="sub">Horario semanal y situaciones especiales de esa semana</p>

            <div class="controles-semana">
                <button type="button" id="btnSemanaAnterior" aria-label="Semana anterior">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>
                <label for="horarioFecha" class="paa-sr-solo">Semana que se está viendo</label>
                <input type="date" id="horarioFecha">
                <button type="button" id="btnSemanaSiguiente" aria-label="Semana siguiente">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
                <button type="button" id="btnSemanaHoy">Hoy</button>
            </div>

            <div id="horarioCuerpo"></div>
        </div>
    </div>

    <script>
        // ================= CONFIG ====================
        // Antes esto apuntaba al CSV público de Google Sheets. Ahora consulta
        // la base de datos del sistema a través de nuestro propio endpoint.
        const API_DIRECTORIO = '../api/funcionarios.php';

        // ================= STATE ====================
        let personas = [];
        let personasFiltradas = [];
        let areasDisponibles = [];

        let ultimaActualizacion = null;
        let cargandoDatos = false;
        const DATOS_VIEJOS_MIN = 15;

        function mostrarNotificacion(mensaje, tipo = 'info', duracion = 3500) {
            const colores = {
                success: 'var(--success)',
                error: 'var(--danger)',
                warning: 'var(--warning)',
                info: 'var(--info)'
            };

            let contenedor = document.getElementById('notificaciones-container');
            if (!contenedor) {
                contenedor = document.createElement('div');
                contenedor.id = 'notificaciones-container';
                contenedor.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 12000;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    align-items: flex-end;
                    pointer-events: none;
                `;
                document.body.appendChild(contenedor);
            }

            const noti = document.createElement('div');
            noti.style.cssText = `
                background: ${colores[tipo] || colores.info};
                color: white;
                padding: 11px 14px;
                border-radius: 10px;
                box-shadow: var(--shadow-lg);
                font-weight: 600;
                font-size: 0.92rem;
                max-width: min(560px, 92vw);
                white-space: pre-line;
                pointer-events: auto;
                animation: toastIn 0.22s ease;
            `;
            noti.textContent = String(mensaje || '');
            contenedor.appendChild(noti);

            setTimeout(() => {
                noti.style.animation = 'toastOut 0.22s ease';
                setTimeout(() => noti.remove(), 220);
            }, duracion);
        }

        function marcarDatosActualizados() {
            ultimaActualizacion = new Date();
            refrescarSelloActualizacion();
        }

        function marcarBotonRefrescar(cargando) {
            const btn = document.getElementById('btnRefrescarDatos');
            if (!btn) return;
            btn.disabled = cargando;
            btn.classList.toggle('cargando', cargando);
        }

        function refrescarSelloActualizacion() {
            const sello = document.getElementById('selloActualizacion');
            if (!sello) return;

            if (!ultimaActualizacion) {
                sello.textContent = '';
                sello.classList.remove('viejo');
                return;
            }

            const minutos = Math.floor((Date.now() - ultimaActualizacion.getTime()) / 60000);
            const hora = ultimaActualizacion.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });

            let texto;
            if (minutos < 1) texto = `Datos actualizados hace instantes (${hora})`;
            else if (minutos === 1) texto = `Datos de hace 1 minuto (${hora})`;
            else texto = `Datos de hace ${minutos} minutos (${hora})`;

            sello.textContent = texto;
            sello.classList.toggle('viejo', minutos >= DATOS_VIEJOS_MIN);
        }

        // ================= INIT =====================
        document.addEventListener('DOMContentLoaded', () => {
            configurarEventListeners();
            cargarDirectorio();
            setInterval(refrescarSelloActualizacion, 30000);
        });

        function configurarEventListeners() {
            document.getElementById('btnRefrescarDatos').addEventListener('click', cargarDirectorio);
            document.getElementById('btnReintentarCarga').addEventListener('click', cargarDirectorio);
            document.getElementById('btnExportarDirectorio').addEventListener('click', () => {
                window.location.href = `${API_DIRECTORIO}?accion=exportar`;
            });
            // El directorio es solo de consulta: crear, editar y borrar
            // funcionarios se hace desde Administración de Usuarios, que es
            // donde también se maneja el acceso al sistema. Tener las dos
            // cosas en dos lugares llevaba a dar de alta a alguien acá y que
            // después no pudiera entrar, o al revés.

            const buscador = document.getElementById('buscadorInput');
            const clearBtn = document.getElementById('clearSearchBtn');

            buscador.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                clearBtn.style.display = val ? 'block' : 'none';
                filtrar(val);
            });

            clearBtn.addEventListener('click', () => {
                buscador.value = '';
                clearBtn.style.display = 'none';
                buscador.focus();
                filtrar('');
            });
        }

        // ================ CARGA DEL DIRECTORIO =================
        // Ya no hay que parsear CSV: el endpoint devuelve JSON con los
        // teléfonos como lista y las áreas ya ordenadas.
        async function cargarDirectorio() {
            if (cargandoDatos) return;
            cargandoDatos = true;
            marcarBotonRefrescar(true);

            try {
                document.getElementById('loadingOverlay').style.display = 'flex';

                const respuesta = await fetch(`${API_DIRECTORIO}?_ts=${Date.now()}`, { cache: 'no-store' });
                if (!respuesta.ok) throw new Error(`el servidor respondió ${respuesta.status}`);

                const datos = await respuesta.json();
                if (!datos.success) throw new Error(datos.error || 'respuesta sin éxito');
                if (!Array.isArray(datos.personas)) {
                    throw new Error('la respuesta del servidor no tiene personas válidas');
                }

                personas = datos.personas;
                areasDisponibles = Array.isArray(datos.areas) ? datos.areas : [];
                renderSugerenciasArea();

                console.log(`Directorio cargado: ${personas.length} personas`);
                document.getElementById('bannerRespaldo').classList.remove('visible');
                marcarDatosActualizados();
                filtrar(document.getElementById('buscadorInput').value.trim());

            } catch (error) {
                console.error('Error cargando el directorio:', error);
                document.getElementById('bannerRespaldo').classList.add('visible');
                mostrarNotificacion(`❌ No se pudo cargar el directorio (${error.message}).`, 'error', 8000);
                if (personas.length === 0) {
                    document.getElementById('directorioContainer').innerHTML = `
                        <div class="empty-state">
                            <i class="fa-regular fa-circle-xmark"></i>
                            <h3>No se pudo cargar el directorio</h3>
                            <p>Revisá la conexión y usá "Reintentar".</p>
                        </div>`;
                }
            } finally {
                document.getElementById('loadingOverlay').style.display = 'none';
                cargandoDatos = false;
                marcarBotonRefrescar(false);
            }
        }

        // ================ BÚSQUEDA =================
        function filtrar(termino) {
            const t = termino.toLowerCase();
            personasFiltradas = !t ? [...personas] : personas.filter(p =>
                p.nombre.toLowerCase().includes(t) ||
                p.area.toLowerCase().includes(t) ||
                (p.correo || '').toLowerCase().includes(t) ||
                (p.telefonos || []).some(tel => tel.toLowerCase().includes(t)) ||
                (p.extension || '').toLowerCase().includes(t)
            );
            renderDirectorio();
        }

        // ================ RENDER =================
        function renderDirectorio() {
            const cont = document.getElementById('directorioContainer');

            document.getElementById('totalPersonas').innerHTML =
                `${personasFiltradas.length} funcionario${personasFiltradas.length === 1 ? '' : 's'}`;

            if (personasFiltradas.length === 0) {
                document.getElementById('totalAreas').textContent = '0 áreas';
                cont.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-regular fa-face-frown"></i>
                        <h3>Sin resultados</h3>
                        <p>Ningún funcionario coincide con la búsqueda.</p>
                    </div>`;
                return;
            }

            const porArea = new Map();
            personasFiltradas.forEach(p => {
                if (!porArea.has(p.area)) porArea.set(p.area, []);
                porArea.get(p.area).push(p);
            });

            document.getElementById('totalAreas').textContent =
                `${porArea.size} área${porArea.size === 1 ? '' : 's'}`;

            let html = '';
            porArea.forEach((gente, area) => {
                html += `
                    <div class="area-bloque">
                        <h3 class="area-titulo">
                            <i class="fa-solid fa-people-group"></i>
                            ${escapar(area)}
                            <span class="cuenta">${gente.length}</span>
                        </h3>
                        <div class="personas-grid">
                            ${gente.map(renderPersona).join('')}
                        </div>
                    </div>`;
            });
            cont.innerHTML = html;
        }

        function renderPersona(p) {
            const inicial = (p.nombre.trim()[0] || '?').toUpperCase();

            const correo = p.correo
                ? `<a href="mailto:${encodeURIComponent(p.correo)}" title="${escapar(p.correo)}"><i class="fa-regular fa-envelope"></i>${escapar(p.correo)}</a>`
                : `<span class="sin-dato"><i class="fa-regular fa-envelope"></i>sin correo</span>`;

            // Los teléfonos ya vienen separados desde la base de datos:
            // no hay que partir cadenas como "8874-0425 / 4701-0551".
            const telefonos = (p.telefonos && p.telefonos.length)
                ? p.telefonos.map(t =>
                    `<a href="tel:${t.replace(/[^0-9+]/g, '')}"><i class="fa-solid fa-phone"></i>${escapar(t)}</a>`
                  ).join('')
                : `<span class="sin-dato"><i class="fa-solid fa-phone"></i>sin teléfono</span>`;

            const extension = p.extension
                ? `<span class="extension"><i class="fa-solid fa-phone-volume"></i>ext. ${escapar(p.extension)}</span>`
                : '';

            return `
                <div class="persona-card">
                    <div class="persona-inicial">${escapar(inicial)}</div>
                    <div class="persona-datos">
                        <div class="persona-nombre">${escapar(p.nombre)}</div>
                        <div class="persona-contacto">
                            ${correo}
                            ${telefonos}
                            ${extension}
                        </div>
                    </div>
                    <div class="persona-acciones">
                        <button class="btn-horario" type="button" onclick="abrirHorario(${p.id})" title="Ver horario semanal">
                            <i class="fa-solid fa-clock"></i> Horario
                        </button>
                    </div>
                </div>`;
        }

        function renderSugerenciasArea() {
            const lista = document.getElementById('listaAreas');
            if (!lista) return;
            lista.innerHTML = areasDisponibles.map(area => `<option value="${escapar(area)}"></option>`).join('');
        }

        // Acá vivían enviarFormularioFuncionario(), toggleFormularioFuncionario()
        // y manejarClickDirectorio(): el alta y la baja de funcionarios desde
        // esta pantalla. Se quitaron junto con el formulario y el botón de
        // eliminar. El directorio quedó como lo que su nombre dice, una lista
        // de contactos para consultar, y el alta y la baja pasaron a
        // Administración de Usuarios.


        function escapar(texto) {
            return String(texto ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // ================= MODAL DE HORARIO ====================
        const API_HORARIOS = '../api/horarios.php';
        const DIAS_SEMANA = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        const DIAS_SEMANA_LARGO = { lunes: 'Lunes', martes: 'Martes', miercoles: 'Miércoles', jueves: 'Jueves', viernes: 'Viernes' };
        const ETIQUETAS_TIPO_SITUACION = {
            vacaciones: 'Vacaciones', incapacidad: 'Incapacidad', reunion: 'Reunión',
            cambio_horario: 'Cambio de horario', trabajo_remoto: 'Trabajo remoto',
            cita_medica: 'Cita médica', permiso: 'Permiso', otro: 'Otro',
        };

        let horarioPersonaId = null;

        document.getElementById('btnCerrarHorario').addEventListener('click', cerrarHorario);
        document.getElementById('modalHorario').addEventListener('click', e => {
            if (e.target.id === 'modalHorario') cerrarHorario();
        });
        document.getElementById('btnSemanaAnterior').addEventListener('click', () => moverSemana(-7));
        document.getElementById('btnSemanaSiguiente').addEventListener('click', () => moverSemana(7));
        document.getElementById('btnSemanaHoy').addEventListener('click', () => {
            document.getElementById('horarioFecha').value = formatoISO(new Date());
            cargarHorarioPersona();
        });
        document.getElementById('horarioFecha').addEventListener('change', cargarHorarioPersona);

        function abrirHorario(personaId) {
            const p = personas.find(x => x.id === personaId);
            horarioPersonaId = personaId;
            // El nombre de la persona va en el título de la ventana, que es lo
            // primero que se anuncia al abrirla.
            document.getElementById('horarioNombre').textContent =
                p ? `Horario de ${p.nombre}` : 'Horario';
            document.getElementById('horarioFecha').value = formatoISO(new Date());
            // Al hacerse visible, includes/ui_accesible.php manda el foco
            // adentro: ya no hay que recorrer la lista entera de funcionarios
            // hasta el final de la página para llegar a esta ventana.
            document.getElementById('modalHorario').classList.add('visible');
            cargarHorarioPersona();
        }

        function cerrarHorario() {
            document.getElementById('modalHorario').classList.remove('visible');
        }

        function moverSemana(dias) {
            const campo = document.getElementById('horarioFecha');
            const fecha = new Date(campo.value + 'T00:00:00');
            fecha.setDate(fecha.getDate() + dias);
            campo.value = formatoISO(fecha);
            cargarHorarioPersona();
        }

        async function cargarHorarioPersona() {
            const cuerpo = document.getElementById('horarioCuerpo');
            cuerpo.innerHTML = '<p class="sin-franjas">Cargando…</p>';

            const fecha = document.getElementById('horarioFecha').value;

            try {
                const r = await fetch(`${API_HORARIOS}?accion=verHorario&persona_id=${horarioPersonaId}&fecha=${fecha}&_ts=${Date.now()}`, { cache: 'no-store' });
                const d = await r.json();
                if (!d.success) throw new Error(d.error || 'No se pudo consultar el horario');

                if (!d.tieneCuenta) {
                    cuerpo.innerHTML = '<p class="sin-franjas">Esta persona no tiene cuenta en el sistema, no hay horario registrado.</p>';
                    return;
                }

                cuerpo.innerHTML = `<div class="semanario-modal">${DIAS_SEMANA.map(dia => pintarDiaModal(dia, d)).join('')}</div>`;
            } catch (e) {
                cuerpo.innerHTML = `<p class="sin-franjas">${escapar(e.message)}</p>`;
            }
        }

        function pintarDiaModal(dia, d) {
            const situaciones = (d.situaciones && d.situaciones[dia]) || [];
            const franjas = (d.dias && d.dias[dia]) || [];
            const fecha = (d.fechasSemana && d.fechasSemana[dia]) || '';

            const banner = situaciones.map(s => `
                <div class="situacion-banner"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> ${escapar(ETIQUETAS_TIPO_SITUACION[s.tipo] || s.tipo)}${s.descripcion ? ': ' + escapar(s.descripcion) : ''}</div>
            `).join('');

            const chips = franjas.length
                ? franjas.map(f => `
                    <div class="franja ${f.modalidad}">${f.horaInicio}–${f.horaFin} · ${f.modalidad === 'virtual' ? 'Virtual' : 'Presencial'}</div>
                `).join('')
                : '<p class="sin-franjas">Sin horario definido.</p>';

            // Cada día es una parada de tabulación con TODO lo suyo en el
            // nombre accesible. Antes eran cajas de texto sueltas: al recorrer
            // el modal con el teclado no había dónde pararse y el lector se las
            // saltaba, así que el horario semanal —lo que se venía a ver— era
            // justo lo único que no se escuchaba.
            const resumen = [
                DIAS_SEMANA_LARGO[dia],
                fechaLarga(fecha),
                franjas.length
                    ? franjas.map(f =>
                        `de ${f.horaInicio} a ${f.horaFin}, ${f.modalidad === 'virtual' ? 'virtual' : 'presencial'}`
                      ).join('; ')
                    : 'sin horario definido',
                situaciones.map(s =>
                    (ETIQUETAS_TIPO_SITUACION[s.tipo] || s.tipo) + (s.descripcion ? `, ${s.descripcion}` : '')
                ).join('; '),
            ].filter(Boolean).join('. ');

            return `
                <div class="dia-modal" role="group" tabindex="0" aria-label="${escapar(resumen)}">
                    <div class="dia-titulo" aria-hidden="true">${DIAS_SEMANA_LARGO[dia]}</div>
                    <div class="dia-fecha" aria-hidden="true">${escapar(fechaCorta(fecha))}</div>
                    <div aria-hidden="true">
                        ${banner}
                        ${chips}
                    </div>
                </div>
            `;
        }

        /** «lunes 15 de setiembre», para el nombre accesible de cada día. */
        function fechaLarga(iso) {
            if (!iso) return '';
            const f = new Date(iso + 'T00:00:00');
            const meses = ['enero','febrero','marzo','abril','mayo','junio',
                           'julio','agosto','setiembre','octubre','noviembre','diciembre'];
            return `${f.getDate()} de ${meses[f.getMonth()]} de ${f.getFullYear()}`;
        }

        function fechaCorta(iso) {
            if (!iso) return '';
            const f = new Date(iso + 'T00:00:00');
            const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            return `${f.getDate()} ${meses[f.getMonth()]}`;
        }

        function formatoISO(fecha) {
            const y = fecha.getFullYear();
            const m = String(fecha.getMonth() + 1).padStart(2, '0');
            const d = String(fecha.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
    </script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
