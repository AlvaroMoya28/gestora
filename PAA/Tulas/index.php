<?php
/**
 * Módulo de uso interno: solo administración.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("tulas", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión PAA · Control de Tulas · UCR</title>
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

        /* ===== TYPOGRAPHY ===== */
        h1, h2, h3, h4 {
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        /* ===== CARDS MODERNOS ===== */
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

        .card-footer {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
        }

        /* ===== HEADER ===== */
        /* ===== BANNER MODO RESPALDO ===== */
        .banner-respaldo {
            display: none; /* se muestra solo si falla la carga del Sheet */
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

        .banner-respaldo.visible {
            display: flex;
        }

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

        .banner-respaldo-texto strong {
            font-size: 1rem;
        }

        .banner-respaldo-texto span {
            font-size: 0.875rem;
        }

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

        .banner-respaldo-btn:hover {
            background: #b91c1c;
        }

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













        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        /* ===== MODE SELECTOR ===== */
        .mode-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .mode-group {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-2xl);
            padding: 0.375rem;
            display: inline-flex;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: var(--shadow);
        }

        .mode-btn {
            border: none;
            padding: 0.875rem 2rem;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mode-btn i {
            font-size: 1.125rem;
        }

        .mode-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .mode-btn.active i {
            color: white;
        }

        .mode-btn:not(.active):hover {
            background: rgba(255, 255, 255, 0.8);
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
            margin-right: auto;   /* empuja el resto de la fila a la derecha */
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

        /* ===== CONTADORES DE AVANCE ===== */
        .panel-contadores {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .contador {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border: 1px solid var(--gray-200);
            border-left: 4px solid var(--gray-300);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .contador.afuera  { border-left-color: var(--warning); }
        .contador.adentro { border-left-color: var(--success); }
        .contador.avance  { border-left-color: var(--primary); }

        .contador-icono {
            font-size: 1.5rem;
            color: var(--gray-400);
            flex-shrink: 0;
        }

        .contador.afuera  .contador-icono { color: var(--warning); }
        .contador.adentro .contador-icono { color: var(--success); }

        .contador-valor {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.1;
        }

        .contador-etiqueta {
            font-size: 0.8125rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .avance-barra {
            margin-top: 0.5rem;
            width: 100%;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
        }

        .avance-barra span {
            display: block;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        /* Resumen de la sede abierta */
        .resumen-sede {
            display: none;   /* solo aparece con una sede seleccionada */
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            background: var(--secondary-light);
            border: 1px solid var(--secondary);
            border-radius: var(--radius-lg);
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .resumen-sede.visible { display: flex; }

        .resumen-sede strong { color: var(--primary); }

        .resumen-sede .completa {
            background: var(--success);
            color: white;
            border-radius: var(--radius-2xl);
            padding: 0.2rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* ===== SEARCH ===== */
        .search-section {
            margin-bottom: 1.5rem;
        }

        .search-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0 0.5rem;
        }

        .search-header h3 {
            font-size: 1.125rem;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-header h3 i {
            color: var(--primary);
        }

        .sede-count {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-2xl);
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
            color: var(--primary);
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        /* ===== SEARCH BOX ===== */
        .search-box {
            position: relative;
        }

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

        .clear-search:hover {
            color: var(--danger);
        }

        /* ===== SELECTED SEDE ===== */
        .selected-sede {
            background: rgba(110, 193, 228, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(110, 193, 228, 0.3);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .sede-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .sede-badge {
            background: var(--primary);
            color: white;
            font-weight: 600;
            padding: 0.375rem 1rem;
            border-radius: var(--radius-2xl);
            font-size: 0.875rem;
        }

        .sede-name {
            font-weight: 500;
            color: var(--gray-800);
        }

        .btn-change {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-2xl);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-change:hover {
            background: var(--primary);
            color: white;
        }

        /* ===== DROPDOWN RESULTADOS ===== */
        .results-dropdown {
            margin-top: 0.5rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-height: 320px;
            overflow-y: auto;
            display: none;
            border: 1px solid var(--gray-200);
        }

        .results-dropdown.show {
            display: block;
        }

        /* Es un <button> y no un <div> para que se pueda llegar con Tab y
           activar con Enter o Espacio. Se neutralizan los estilos propios
           del botón para que se siga viendo como una fila de la lista. */
        .sede-item {
            width: 100%;
            text-align: left;
            font: inherit;
            color: inherit;
            background: white;
            border: none;
            border-bottom: 1px solid var(--gray-100);
            padding: 1rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: background 0.15s;
        }

        .sede-item:focus-visible {
            outline: 3px solid var(--secondary);
            outline-offset: -3px;
            background: var(--secondary-light);
        }

        .sede-item:last-child {
            border-bottom: none;
        }

        .sede-item:hover {
            background: var(--secondary-light);
        }

        .sede-item-id {
            background: var(--gray-100);
            color: var(--primary);
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-2xl);
            font-size: 0.875rem;
        }

        .sede-item-name {
            flex: 1;
            font-weight: 500;
            color: var(--gray-800);
        }

        .sede-item-count {
            background: var(--gray-100);
            border-radius: var(--radius-2xl);
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        /* ===== SCAN PANEL ===== */
        .scan-panel {
            margin-bottom: 1.5rem;
        }

        .scan-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .scan-header h3 {
            font-size: 1.25rem;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .scan-header h3 i {
            color: var(--primary);
        }

        .mode-indicator {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-2xl);
            font-weight: 500;
            font-size: 0.875rem;
            box-shadow: var(--shadow);
        }

        .input-group-scan {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .input-group-scan input {
            flex: 1;
            min-width: 240px;
            padding: 1rem 1.25rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-2xl);
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
        }

        .input-group-scan input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(110, 193, 228, 0.15);
        }

        .btn-scan {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow);
        }

        .btn-scan:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-manual {
            background: white;
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            padding: 1rem 2rem;
            border-radius: var(--radius-2xl);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-manual:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
        }

        .message-scan {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            padding-left: 0.5rem;
        }

        /* ===== MODAL PERSONALIZADO PARA INGRESO MANUAL ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-2xl);
            max-width: 450px;
            width: 90%;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: modalAppear 0.3s ease;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-header i {
            font-size: 1.75rem;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-body {
            padding: 2rem 1.5rem;
        }

        .modal-body p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
        }

        .modal-input-group {
            margin-bottom: 1.5rem;
        }

        .modal-input-group label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .modal-input-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-2xl);
            font-size: 1.1rem;
            transition: all 0.2s;
        }

        .modal-input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 85, 164, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow);
        }

        .modal-btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .modal-btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .modal-btn-secondary:hover {
            background: var(--gray-200);
        }

        .modal-result {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: var(--radius-lg);
            display: none;
        }

        .modal-result.success {
            background: #d1fae5;
            color: #065f46;
            display: block;
        }

        .modal-result.error {
            background: #fee2e2;
            color: #b91c1c;
            display: block;
        }

        .modal-result.warning {
            background: #fff3cd;
            color: #856404;
            display: block;
        }

        .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        /* ===== INFO MESSAGE ===== */
        .info-message {
            background: #e3f0fa;
            border-left: 5px solid var(--info);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .info-message i {
            font-size: 2rem;
            color: var(--info);
        }

        .info-message-content h4 {
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .info-message-content p {
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .info-message-content .btn-suggest {
            background: var(--info);
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-2xl);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-message-content .btn-suggest:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* ===== VERIFICAR TODO BUTTON ===== */
        .btn-verificar-todo {
            background: var(--info);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }

        .btn-verificar-todo:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-verificar-todo i {
            font-size: 1rem;
        }

        .action-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        /* ===== ACORDEÓN TULAS + PAGINACIÓN ===== */
        .accordion-list {
            display: grid;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .sede-accordion-item {
            background: #f8fbff;
            border: 1px solid #dbeafe;
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .sede-accordion-item[open] {
            border-color: #93c5fd;
        }

        .sede-accordion-item > summary {
            list-style: none;
            cursor: pointer;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background: #eff6ff;
        }

        .sede-accordion-item > summary::-webkit-details-marker {
            display: none;
        }

        .sede-summary-main {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            min-width: 0;
        }

        .sede-summary-id {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .sede-summary-name {
            color: var(--gray-800);
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sede-summary-meta {
            color: var(--gray-600);
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .sede-accordion-item .accordion-list {
            padding: 0.7rem;
            margin-bottom: 0;
        }

        .tula-accordion-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .tula-accordion-item[open] {
            border-color: #b8d9f2;
            box-shadow: var(--shadow);
        }

        .tula-accordion-item summary {
            list-style: none;
            cursor: pointer;
            padding: 0.85rem 1rem;
            display: grid;
            grid-template-columns: minmax(130px, 1.3fr) minmax(95px, 0.9fr) minmax(90px, 0.8fr) minmax(120px, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
        }

        .tula-accordion-item summary::-webkit-details-marker {
            display: none;
        }

        .tula-col {
            min-width: 0;
            font-size: 0.86rem;
            color: var(--gray-700);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tula-col strong {
            color: var(--gray-900);
        }

        .tula-expand-hint {
            color: var(--gray-500);
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .tula-accordion-item[open] .tula-expand-hint i {
            transform: rotate(180deg);
        }

        .tula-expand-hint i {
            transition: transform 0.2s ease;
        }

        .tula-accordion-body {
            border-top: 1px solid var(--gray-100);
            padding: 0.9rem 1rem;
            background: #fcfdff;
        }

        .tula-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.55rem 1rem;
            margin-bottom: 0.75rem;
        }

        .tula-detail-item {
            font-size: 0.84rem;
            color: var(--gray-700);
        }

        .tula-detail-item strong {
            color: var(--gray-900);
        }

        .tula-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .tula-readonly-note {
            font-size: 0.82rem;
            color: var(--gray-500);
            font-style: italic;
            text-align: right;
        }

        .pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .pagination-info {
            color: var(--gray-600);
            font-size: 0.84rem;
            font-weight: 500;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .pagination-btn {
            border: 1px solid var(--gray-300);
            background: #fff;
            color: var(--gray-700);
            border-radius: var(--radius);
            padding: 0.42rem 0.65rem;
            min-width: 36px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
        }

        .pagination-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .pagination-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        @media (max-width: 860px) {
            .tula-accordion-item summary {
                grid-template-columns: 1fr 1fr;
            }

            .tula-expand-hint {
                grid-column: span 2;
            }

            .tula-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== TABLE ===== */
        .table-container {
            background: white;
            border-radius: var(--radius-xl);
            overflow-x: auto;
            box-shadow: var(--shadow-lg);
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }


        th {
            background: var(--gray-50);
            color: var(--gray-700);
            font-weight: 600;
            font-size: 0.875rem;
            padding: 1rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        td {
            padding: 0.875rem 0.75rem;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.875rem;
            background: white;
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }







        /* ===== TULAS: VERIFICADAS Y FALTANTES ===== */
        .aviso-tulas {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            border-radius: var(--radius);
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-left: 4px solid var(--warning);
            font-size: 0.9rem;
            color: #92400e;
        }

        .aviso-tulas.completa {
            background: #ecfdf5;
            border-color: #a7f3d0;
            border-left-color: var(--success);
            color: #065f46;
        }

        tbody tr.tula-falta { background: #fffbeb; }
        tbody tr.tula-ok { background: #f0fdf4; }

        .marchamo-codigo {
            font-family: 'SFMono-Regular', Consolas, monospace;
            letter-spacing: 0.02em;
        }

        .marchamo-codigo.secundario {
            font-weight: 400;
            font-size: 0.85em;
            color: var(--gray-500);
        }

        .sin-marchamo {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--danger);
        }

        /* Los números de aula son identificadores: se muestran tabulares para
           que se alineen en columna y no se lean como una cantidad. */
        .numero-aula {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }

        .check-falta { color: var(--gray-500); }

        .resumen-faltantes {
            margin-top: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-radius: var(--radius-sm);
            background: #fffbeb;
            border-left: 3px solid var(--warning);
            font-size: 0.85rem;
            color: #92400e;
        }

        .pista-escaneo {
            margin-top: 0.6rem;
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .estado-disponible {
            background: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-2xl);
            font-weight: 500;
            font-size: 0.75rem;
            display: inline-block;
        }

        .estado-prestado {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-2xl);
            font-weight: 500;
            font-size: 0.75rem;
            display: inline-block;
        }

        .check-verificado {
            color: var(--success);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
        }

        .btn-verificar {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-2xl);
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-verificar:hover {
            background: #0d9488;
        }

        .btn-desverificar {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-2xl);
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-desverificar:hover {
            background: #dc2626;
        }





        /* ===== VOUCHER PANEL ===== */
        .voucher-panel {
            margin-top: 1.5rem;
        }

        .voucher-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .voucher-header h3 {
            font-size: 1.25rem;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .voucher-header h3 i {
            color: var(--success);
        }

        .voucher-count {
            background: var(--success);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-2xl);
            font-weight: 600;
        }

        .voucher-summary {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .voucher-summary ul {
            list-style: none;
            margin-top: 0.75rem;
        }

        .voucher-summary li {
            padding: 0.5rem 0;
            border-bottom: 1px dashed var(--gray-200);
            display: flex;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .btn-generate {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            border: none;
            padding: 1.25rem 2.5rem;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 auto;
            box-shadow: var(--shadow);
        }

        .btn-generate:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-generate:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ===== NUEVO BOTÓN: SOLO ACTUALIZAR ESTADO ===== */
        .btn-actualizar-estado {
            background: linear-gradient(135deg, var(--info), #2563eb);
            color: white;
            border: none;
            padding: 1.25rem 2.5rem;
            border-radius: var(--radius-2xl);
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 auto;
            box-shadow: var(--shadow);
        }

        .btn-actualizar-estado:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-actualizar-estado:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-buttons-voucher {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
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

        .empty-state p {
            color: var(--gray-500);
        }

        /* ===== ACCESSIBILITY ===== */
        :focus-visible {
            outline: 3px solid var(--secondary);
            outline-offset: 2px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 640px) {
            body { padding: 1rem; }
            .mode-group { width: 100%; }
            .mode-btn { flex: 1; justify-content: center; }
            .input-group-scan { flex-direction: column; }
            .btn-change { width: 100%; justify-content: center; }
            th, td { padding: 0.5rem; }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        .barra-sesion {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra-sesion a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="app">
        <div class="barra-sesion">
            <span><i class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
            <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <!-- Modal para Ingreso Manual -->
        <div id="modalManual" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fa-regular fa-pen-to-square"></i>
                    <h3>Ingreso Manual de Tula</h3>
                </div>
                <div class="modal-body">
                    <p>Ingresa el código de barras de la tula que deseas verificar:</p>
                    <div class="modal-input-group">
                        <label for="modalTulaInput">Código de barras</label>
                        <input type="text" id="modalTulaInput" placeholder="Ej: 1234567890" autocomplete="off">
                    </div>
                    <div id="modalResult" class="modal-result"></div>
                    <div class="modal-actions">
                        <button id="modalCancelBtn" class="modal-btn modal-btn-secondary">
                            <i class="fa-solid fa-times"></i> Cancelar
                        </button>
                        <button id="modalConfirmBtn" class="modal-btn modal-btn-primary">
                            <i class="fa-solid fa-check"></i> Verificar
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    Escanea o ingresa el código de barras de la tula
                </div>
            </div>
        </div>

        <!-- Aviso de modo respaldo (datos de ejemplo, NO reales) -->
        <div id="bannerRespaldo" class="banner-respaldo">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="banner-respaldo-texto">
                <strong>Sin conexión con Google Sheets — datos de ejemplo</strong>
                <span>Las tulas que ves <u>no son reales</u>. El escaneo, los comprobantes y la actualización de estados están bloqueados.</span>
            </div>
            <button id="btnReintentarCarga" class="banner-respaldo-btn">
                <i class="fa-solid fa-rotate"></i> Reintentar
            </button>
        </div>

        <!-- Hero -->
        <div class="hero">
            <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
                <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
            </div>
            <h1>Control de Tulas</h1>
            <p>Universidad de Costa Rica · IIP</p>
        </div>

        <!-- GESTIÓN SECTION -->
        <div id="gestionSection">
            <!-- Mode Selector -->
            <div class="mode-selector">
                <div class="mode-group">
                    <button id="btnModoSalida" class="mode-btn salida active">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Salida</span>
                    </button>
                    <button id="btnModoEntrada" class="mode-btn entrada">
                        <i class="fa-solid fa-arrow-left-to-bracket"></i>
                        <span>Entrada</span>
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <span id="selloActualizacion" class="sello-actualizacion"></span>
                <button id="btnRefrescarDatos" class="btn-refrescar" title="Volver a leer los datos del Sheet">
                    <i class="fa-solid fa-rotate"></i> Actualizar datos
                </button>
                <div class="stat-badge">
                    <i class="fa-solid fa-box"></i>
                    <span id="contadorTulas">0 tulas</span>
                </div>
            </div>

            <!-- Contadores de avance -->
            <div id="panelContadores" class="panel-contadores">
                <div class="contador">
                    <span class="contador-icono"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div>
                        <div class="contador-valor" id="contTotal">0</div>
                        <div class="contador-etiqueta">Total de tulas</div>
                    </div>
                </div>
                <div class="contador afuera">
                    <span class="contador-icono"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                    <div>
                        <div class="contador-valor" id="contAfuera">0</div>
                        <div class="contador-etiqueta" id="contAfueraEtiqueta">Entregadas</div>
                    </div>
                </div>
                <div class="contador adentro">
                    <span class="contador-icono"><i class="fa-solid fa-building-columns"></i></span>
                    <div>
                        <div class="contador-valor" id="contAdentro">0</div>
                        <div class="contador-etiqueta" id="contAdentroEtiqueta">En el IIP</div>
                    </div>
                </div>
                <div class="contador avance">
                    <div style="flex:1; min-width:0;">
                        <div class="contador-valor" id="contPorcentaje">0%</div>
                        <div class="contador-etiqueta" id="contAvanceEtiqueta">Avance de entrega</div>
                        <div class="avance-barra"><span id="avanceRelleno"></span></div>
                    </div>
                </div>
            </div>

            <!-- Resumen de la sede abierta -->
            <div id="resumenSede" class="resumen-sede"></div>

            <!-- Search Section for Gestion (AHORA ARRIBA) -->
            <div class="card search-section">
                <div class="card-header">
                    <h3><i class="fa-solid fa-magnifying-glass"></i> Buscar sede</h3>
                    <span class="sede-count" id="totalSedes">0 sedes</span>
                </div>
                <div class="card-body">
                    <!-- Selected Sede -->
                    <div id="sedeSeleccionadaInfo" class="selected-sede" style="display: none;">
                        <div class="sede-info">
                            <span class="sede-badge" id="sedeSeleccionadaId"></span>
                            <span class="sede-name" id="sedeSeleccionadaNombre"></span>
                        </div>
                        <button id="btnCambiarSede" class="btn-change">
                            <i class="fa-solid fa-rotate"></i> Cambiar
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="search-box">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="buscadorSedeInput" placeholder="Buscar por ID o nombre...">
                        <button id="clearSearchBtn" class="clear-search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Results -->
                    <div id="resultadosBusqueda" class="results-dropdown"></div>
                </div>
            </div>

            <!-- Scan Panel -->
            <div id="escaneoPanel" class="card scan-panel" style="display: none;">
                <div class="card-header scan-header">
                    <h3><i class="fa-solid fa-camera"></i> Escaneo</h3>
                    <span id="modoActual" class="mode-indicator">SALIDA</span>
                </div>
                <div class="card-body">
                    <div class="input-group-scan">
                        <input type="text" id="codigoInput" placeholder="Escaneá el marchamo de la tula…" autocomplete="off">
                        <button id="btnEscanear" class="btn-scan">
                            <i class="fa-solid fa-barcode"></i> Verificar
                        </button>
                        <button id="btnManual" class="btn-manual">
                            <i class="fa-regular fa-pen-to-square"></i> Manual
                        </button>
                    </div>
                    <p class="pista-escaneo" id="pistaEscaneo">
                        <i class="fa-regular fa-lightbulb"></i>
                        La brida identifica la tula y su sede: no hace falta elegir la sede antes.
                    </p>
                    <div id="mensajeEscaneo" class="message-scan"></div>
                </div>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                <span id="resultadosCount">Mostrando 0 resultados</span>
            </div>

            <!-- Tulas Table -->
            <div id="tulasContainer">
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>Cargando datos...</h3>
                    <p>Conectando con Google Sheets</p>
                </div>
            </div>

            <!-- Voucher Panel -->
            <div id="comprobantePanel" class="card voucher-panel" style="display: none;">
                <div class="card-header voucher-header">
                    <h3><i class="fa-regular fa-circle-check"></i> Tulas verificadas</h3>
                    <span id="contadorVerificadas" class="voucher-count">0/0</span>
                </div>
                <div class="card-body">
                    <div id="resumenTulas" class="voucher-summary"></div>
                    <div class="action-buttons-voucher">
                        <button id="btnGenerarComprobante" class="btn-generate">
                            <i class="fa-regular fa-envelope"></i> Enviar comprobante
                        </button>
                        <button id="btnSoloActualizarEstado" class="btn-actualizar-estado">
                            <i class="fa-solid fa-rotate"></i> Solo actualizar estado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ================= CONFIG ====================
        //
        // MIGRADO A LA BASE DE DATOS INSTITUCIONAL.
        //
        // Antes acá había tres URLs de Google: el CSV público del Sheet y dos
        // veces el mismo Apps Script (APPS_SCRIPT_URL y GAS_URL apuntaban al
        // mismo lugar). Ahora todo pasa por el endpoint propio, que lee y
        // escribe en MariaDB.
        //
        // El endpoint acepta las mismas operaciones que hacía el Apps Script:
        //   ?accion=listar              → las tulas de la convocatoria
        //   accion=actualizarEstados    → marcar prestadas / devueltas
        //   accion=enviarComprobante    → correo al coordinador de la sede
        const API_URL = '../api/aulas.php';
        const CSRF = <?= json_encode(token_csrf()) ?>;

        const CORREO_PREDETERMINADO = '';
        // El remitente lo define el servidor (includes/config.php). Se deja la
        // constante porque la pantalla la muestra en el comprobante.
        const CORREO_REMITENTE      = 'no-responder@gestionpaa.ucr.ac.cr';

        // ================= STATE ====================
        let tulasData = [];            // Todos los registros del Excel
        let tulasFiltradas = [];        // Registros activos (sin filtros avanzados)
        let tulasSedeActual = [];        // Registros (filas/aulas) de la sede actual según modo
        let tulasSedeCompleta = [];      // Todos los registros de la sede actual

        // ================= EL BULTO, NO LA FILA ====================
        //
        // El Sheet tiene una fila por AULA, pero lo que sale y entra del IIP es
        // la TULA: un bulto que puede llevar varias aulas adentro y que va
        // cerrado con un par de bridas — una de salida y una de retorno.
        //
        // Desde que los marchamos son la identificación oficial, todo acá
        // trabaja con tulas: se escanea una brida, se verifica el bulto entero,
        // y al actualizar el Sheet se tocan todas las filas de ese bulto.
        let tulasDeSede = [];            // Bultos de la sede abierta, según modo
        let tulasVerificadas = [];        // Bultos ya verificados, para el comprobante
        let modoActual = 'salida';
        let sedeActual = null;
        let sedeNombreActual = '';
        let sedesData = [];               // Lista de sedes para el buscador
        let sedesFiltradas = [];

        // true = no se pudo leer el Sheet y estamos con datos de ejemplo.
        // Mientras esté activo se bloquea escanear, enviar comprobantes y
        // actualizar estados en el Sheet.
        let modoRespaldo = false;

        // Control de frescura de los datos
        let ultimaActualizacion = null;   // Date de la última carga con éxito
        let cargandoDatos = false;        // evita cargas simultáneas
        const DATOS_VIEJOS_MIN = 15;      // a partir de aquí el sello avisa

        let tulaPendienteManual = null;
        let paginaActualTulas = 1;
        let paginaActualSedes = 1;
        const TULAS_POR_PAGINA = 20;
        const SEDES_POR_PAGINA = 5;

        // ================= UX: MENSAJES / DIÁLOGOS PERSONALIZADOS =====================
        function inferirTipoNotificacion(mensaje = '') {
            const txt = String(mensaje || '');
            if (txt.includes('❌') || /error/i.test(txt)) return 'error';
            if (txt.includes('⚠️') || /atenci[oó]n|warning|cuidado/i.test(txt)) return 'warning';
            if (txt.includes('✅') || /exito|éxito|correctamente|enviado|actualizado/i.test(txt)) return 'success';
            return 'info';
        }

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

        function abrirDialogoPersonalizado({
            titulo = 'Mensaje',
            mensaje = '',
            modo = 'alert', // alert | confirm | prompt
            etiquetaInput = 'Valor',
            placeholder = '',
            valorInicial = '',
            textoConfirmar = 'Aceptar',
            textoCancelar = 'Cancelar'
        } = {}) {
            return new Promise((resolve) => {
                let overlay = document.getElementById('customDialogOverlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'customDialogOverlay';
                    overlay.className = 'modal-overlay';
                    overlay.style.zIndex = '13000';
                    overlay.innerHTML = `
                        <div class="modal-content" style="max-width:520px; width:min(92vw,520px);">
                            <div class="modal-header">
                                <i class="fa-regular fa-message"></i>
                                <h3 id="customDialogTitle">Mensaje</h3>
                            </div>
                            <div class="modal-body">
                                <p id="customDialogMessage" style="white-space:pre-line;"></p>
                                <div id="customDialogInputWrap" class="modal-input-group" style="display:none; margin-top:10px;">
                                    <label id="customDialogInputLabel" for="customDialogInput">Valor</label>
                                    <input type="text" id="customDialogInput" autocomplete="off">
                                </div>
                                <div class="modal-actions" style="margin-top:10px;">
                                    <button id="customDialogCancel" class="modal-btn modal-btn-secondary">Cancelar</button>
                                    <button id="customDialogOk" class="modal-btn modal-btn-primary">Aceptar</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                }

                const titleEl = document.getElementById('customDialogTitle');
                const msgEl = document.getElementById('customDialogMessage');
                const inputWrap = document.getElementById('customDialogInputWrap');
                const inputLabel = document.getElementById('customDialogInputLabel');
                const inputEl = document.getElementById('customDialogInput');
                const btnCancel = document.getElementById('customDialogCancel');
                const btnOk = document.getElementById('customDialogOk');

                titleEl.textContent = titulo;
                msgEl.textContent = mensaje;
                btnOk.textContent = textoConfirmar;
                btnCancel.textContent = textoCancelar;

                inputWrap.style.display = modo === 'prompt' ? 'block' : 'none';
                btnCancel.style.display = modo === 'alert' ? 'none' : 'inline-flex';
                if (inputLabel) inputLabel.textContent = etiquetaInput || 'Valor';

                inputEl.value = valorInicial || '';
                inputEl.placeholder = placeholder || '';

                const cerrar = (resultado) => {
                    overlay.style.display = 'none';
                    document.removeEventListener('keydown', onKeyDown);
                    resolve(resultado);
                };

                const onKeyDown = (e) => {
                    if (e.key === 'Escape') {
                        if (modo === 'alert') cerrar(true);
                        else if (modo === 'confirm') cerrar(false);
                        else cerrar(null);
                    }
                    if (e.key === 'Enter') {
                        if (modo === 'prompt') cerrar(inputEl.value);
                        else cerrar(true);
                    }
                };

                btnOk.onclick = () => {
                    if (modo === 'prompt') cerrar(inputEl.value);
                    else cerrar(true);
                };

                btnCancel.onclick = () => {
                    if (modo === 'confirm') cerrar(false);
                    else cerrar(null);
                };

                overlay.onclick = (e) => {
                    if (e.target !== overlay) return;
                    if (modo === 'alert') cerrar(true);
                    else if (modo === 'confirm') cerrar(false);
                    else cerrar(null);
                };

                document.addEventListener('keydown', onKeyDown);
                overlay.style.display = 'flex';

                setTimeout(() => {
                    if (modo === 'prompt') inputEl.focus();
                    else btnOk.focus();
                }, 40);
            });
        }

        async function modalConfirmacion(mensaje, titulo = 'Confirmación') {
            return await abrirDialogoPersonalizado({
                titulo,
                mensaje,
                modo: 'confirm',
                textoConfirmar: 'Sí, continuar',
                textoCancelar: 'Cancelar'
            });
        }

        async function modalPrompt(mensaje, valorInicial = '', titulo = 'Ingrese un valor', placeholder = '', etiquetaInput = 'Valor') {
            return await abrirDialogoPersonalizado({
                titulo,
                mensaje,
                modo: 'prompt',
                valorInicial,
                etiquetaInput,
                placeholder,
                textoConfirmar: 'Aceptar',
                textoCancelar: 'Cancelar'
            });
        }

        // Reemplazo global de alert nativo (Google modal) por notificación interna
        window.alert = function(mensaje) {
            mostrarNotificacion(mensaje, inferirTipoNotificacion(mensaje));
        };

        // ================= INIT =====================
        document.addEventListener('DOMContentLoaded', () => {
            configurarEventListeners();
            cargarDatosDesdeGoogleSheets();

            // Mantiene vivo el "hace N minutos" sin volver a pedir datos
            setInterval(refrescarSelloActualizacion, 30000);

            // Configurar modal
            document.getElementById('modalCancelBtn').addEventListener('click', cerrarModalManual);
            document.getElementById('modalConfirmBtn').addEventListener('click', confirmarManual);
            document.getElementById('modalTulaInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') confirmarManual();
            });

            // Botón solo actualizar estado
            document.getElementById('btnSoloActualizarEstado').addEventListener('click', soloActualizarEstado);

        });

        // ================ CONTADORES DE AVANCE =================
        //
        // El Sheet guarda un solo campo de estado: 'disponible' (la tula está en
        // el IIP) o 'prestado' (está en la sede). NO hay historial, así que una
        // tula que ya volvió es indistinguible de una que nunca salió.
        //
        // Por eso los rótulos dependen del modo activo, que es el que dice en
        // qué fase de la jornada estamos:
        //   SALIDA  -> prestado = entregadas    | disponible = por entregar
        //   ENTRADA -> disponible = devueltas   | prestado   = por devolver
        // El número es el mismo; lo que cambia es qué significa.

        function contarPorEstado(lista) {
            let prestadas = 0;
            lista.forEach(t => { if (t.estado === 'prestado') prestadas++; });
            return { total: lista.length, prestadas, disponibles: lista.length - prestadas };
        }

        function actualizarContadores() {
            const g = contarPorEstado(tulasData);
            const enSalida = modoActual === 'salida';

            // En salida el avance es lo entregado; en entrada, lo devuelto.
            const hechas = enSalida ? g.prestadas : g.disponibles;
            const faltan = g.total - hechas;
            const pct = g.total > 0 ? Math.round((hechas / g.total) * 100) : 0;

            document.getElementById('contTotal').textContent = g.total;
            document.getElementById('contAfuera').textContent = g.prestadas;
            document.getElementById('contAdentro').textContent = g.disponibles;
            document.getElementById('contPorcentaje').textContent = `${pct}%`;
            document.getElementById('avanceRelleno').style.width = `${pct}%`;

            document.getElementById('contAfueraEtiqueta').textContent =
                enSalida ? 'Entregadas (en sedes)' : 'Faltan por devolver';
            document.getElementById('contAdentroEtiqueta').textContent =
                enSalida ? 'Faltan por entregar' : 'Devueltas (en el IIP)';
            document.getElementById('contAvanceEtiqueta').textContent =
                enSalida ? `Entregadas: ${hechas} de ${g.total}` : `Devueltas: ${hechas} de ${g.total}`;

            actualizarResumenSede(enSalida);
        }

        // Detalle de la sede abierta, para no tener que sacar la cuenta a mano
        function actualizarResumenSede(enSalida) {
            const caja = document.getElementById('resumenSede');

            if (!sedeActual || tulasSedeCompleta.length === 0) {
                caja.classList.remove('visible');
                caja.innerHTML = '';
                return;
            }

            const s = contarPorEstado(tulasSedeCompleta);
            const hechas = enSalida ? s.prestadas : s.disponibles;
            const faltan = s.total - hechas;
            const verbo = enSalida ? 'entregadas' : 'devueltas';

            caja.innerHTML =
                `<span><strong>Sede ${sedeActual}</strong> · ${sedeNombreActual || ''}</span>` +
                `<span>${hechas} de ${s.total} ${verbo}</span>` +
                (faltan > 0
                    ? `<span>· faltan <strong>${faltan}</strong></span>`
                    : `<span class="completa">✓ completa</span>`);
            caja.classList.add('visible');
        }

        // ================ FRESCURA DE LOS DATOS =================
        //
        // Los datos se leen del Sheet al abrir la página. Si alguien lo edita
        // durante la jornada, esta pestaña se queda con la copia vieja: por eso
        // el sello dice cuándo se cargaron y el botón permite releerlos sin
        // recargar la página (lo que perdería el estado de la pantalla).

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

        // Releer el Sheet descarta lo verificado en pantalla (los registros
        // nuevos son otros objetos), así que si hay trabajo sin guardar se avisa.
        async function refrescarDatosManual() {
            if (tulasVerificadas.length > 0) {
                const seguir = await modalConfirmacion(
                    `Tenés ${tulasVerificadas.length} tula(s) verificada(s) sin enviar.\n\n` +
                    `Al actualizar los datos se van a descartar y habrá que escanearlas de nuevo.\n\n` +
                    `¿Actualizar de todos modos?`,
                    'Hay tulas verificadas sin enviar'
                );
                if (!seguir) return;
            }
            cargarDatosDesdeGoogleSheets();
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

        // ================ PARSEO DE CSV =================
        //
        // Un CSV de Sheets no se puede partir por líneas y comas a mano: un campo
        // entrecomillado puede contener comas, saltos de línea y comillas
        // escapadas (""). Recorremos el texto carácter por carácter llevando el
        // estado de "dentro de comillas", y usamos el mismo parser para el
        // encabezado y para las filas, para que nunca se desalineen.
        //
        // Devuelve un array de filas, cada una array de celdas ya sin comillas.
        function parsearCSV(texto) {
            const normalizado = String(texto || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');

            const filas = [];
            let fila = [];
            let campo = '';
            let enComillas = false;

            for (let i = 0; i < normalizado.length; i++) {
                const c = normalizado[i];

                if (enComillas) {
                    if (c !== '"') {
                        campo += c;
                    } else if (normalizado[i + 1] === '"') {
                        campo += '"';   // comilla escapada dentro del campo
                        i++;
                    } else {
                        enComillas = false;
                    }
                    continue;
                }

                if (c === '"') {
                    enComillas = true;
                } else if (c === ',') {
                    fila.push(campo);
                    campo = '';
                } else if (c === '\n') {
                    fila.push(campo);
                    filas.push(fila);
                    fila = [];
                    campo = '';
                } else {
                    campo += c;
                }
            }

            // Última fila, si el archivo no termina con salto de línea
            if (campo !== '' || fila.length > 0) {
                fila.push(campo);
                filas.push(fila);
            }

            return filas
                .map(f => f.map(v => v.trim()))
                .filter(f => f.some(v => v !== ''));   // descartar filas vacías
        }

        // ================ CARGAR DATOS DE LA BASE =================
        //
        // Antes esto descargaba el CSV público del Sheet y lo parseaba a mano,
        // probando cinco variantes del nombre de cada columna porque el
        // encabezado podía cambiar de un día para otro ("MARCHAMO1",
        // "MARCHAMO_1", "MARCHAMO 1"...). El endpoint devuelve JSON con los
        // campos ya normalizados y con los mismos nombres que usa esta página,
        // así que el resto del archivo no cambió.
        async function cargarDatos() {
            if (cargandoDatos) return;   // evita cargas encimadas por doble clic
            cargandoDatos = true;
            marcarBotonRefrescar(true);

            try {
                document.getElementById('loadingOverlay').style.display = 'flex';

                // _ts evita que el navegador sirva una respuesta cacheada
                const response = await fetch(`${API_URL}?accion=listar&_ts=${Date.now()}`, { cache: 'no-store' });
                const datos = await response.json();

                if (!datos.success) {
                    throw new Error(datos.error || 'La base no devolvió datos');
                }

                // `total` no existe en la base: era una columna de notas del
                // Sheet. Se deja vacía para no romper lo que la lee.
                tulasData = (datos.tulas || []).map(t => ({ total: '', ...t }));

                // Una respuesta vacía es tan peligrosa como un error de red:
                // no la damos por buena.
                if (tulasData.length === 0) {
                    throw new Error('La base no devolvió ningún registro');
                }

                console.log(`Datos cargados: ${tulasData.length} registros`);
                desactivarModoRespaldo();
                marcarDatosActualizados();
                tulasFiltradas = [...tulasData];
                actualizarSedesData();
                document.getElementById('contadorTulas').innerHTML = `📦 ${tulasData.length} tulas`;
                actualizarContadores();
                restaurarVistaTrasRecarga();

            } catch (error) {
                console.error('Error cargando datos:', error);
                cargarDatosRespaldo();
            } finally {
                document.getElementById('loadingOverlay').style.display = 'none';
                cargandoDatos = false;
                marcarBotonRefrescar(false);
            }
        }

        // El resto del archivo llama a la función por su nombre viejo. Se deja
        // como alias en vez de renombrar en decenas de lugares.
        const cargarDatosDesdeGoogleSheets = cargarDatos;

        // Tras releer los datos, los objetos de tulasSedeActual quedaron obsoletos.
        // Si había una sede abierta la recargamos desde los datos nuevos; si esa
        // sede ya no existe, volvemos a la vista general.
        function restaurarVistaTrasRecarga() {
            if (!sedeActual) {
                mostrarTulasInicial(true);
                return;
            }

            const sigueExistiendo = tulasData.some(t => t.idSede === sedeActual);
            if (!sigueExistiendo) {
                mostrarNotificacion(`⚠️ La sede ${sedeActual} ya no aparece en el Sheet. Volviendo a la vista general.`, 'warning', 6000);
                sedeActual = null;
                document.getElementById('sedeSeleccionadaInfo').style.display = 'none';
                document.getElementById('escaneoPanel').style.display = 'none';
                mostrarTulasInicial(true);
                return;
            }

            cargarTulasSedePorId(sedeActual);
        }

        // ================ MODO RESPALDO =================
        function activarModoRespaldo() {
            modoRespaldo = true;
            document.getElementById('bannerRespaldo').classList.add('visible');
            mostrarNotificacion('❌ No se pudo conectar con Google Sheets. Las tulas mostradas son de ejemplo y las operaciones están bloqueadas.', 'error', 8000);
        }

        function desactivarModoRespaldo() {
            const yaEstaba = modoRespaldo;
            modoRespaldo = false;
            document.getElementById('bannerRespaldo').classList.remove('visible');
            if (yaEstaba) {
                // Los datos cambiaron bajo los pies: descartamos lo verificado
                // para no mezclar tulas de ejemplo con las reales.
                tulasVerificadas = [];
                document.getElementById('comprobantePanel').style.display = 'none';
                mostrarNotificacion('✅ Conexión restablecida. Datos reales cargados.', 'success');
            }
        }

        // Guard para las acciones que no deben correr con datos de ejemplo.
        // Devuelve true si la acción debe abortarse.
        function bloqueadoPorRespaldo(accion) {
            if (!modoRespaldo) return false;
            mostrarNotificacion(`❌ ${accion} está deshabilitado: los datos actuales son de ejemplo, no del Sheet. Usá "Reintentar" para volver a conectar.`, 'error', 6000);
            return true;
        }

        function actualizarSedesData() {
            const map = new Map();
            tulasData.forEach(t => {
                if (t.idSede && !map.has(t.idSede)) {
                    map.set(t.idSede, { id: t.idSede, nombre: t.NombreSede || `Sede ${t.idSede}` });
                }
            });
            sedesData = Array.from(map.values()).sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }));
            document.getElementById('totalSedes').innerHTML = `🏫 ${sedesData.length} sedes`;
        }

        function configurarEventListeners() {
            // Reintentar la carga del Sheet cuando estamos en modo respaldo
            document.getElementById('btnReintentarCarga').addEventListener('click', cargarDatosDesdeGoogleSheets);

            // Releer los datos del Sheet sin recargar la página
            document.getElementById('btnRefrescarDatos').addEventListener('click', refrescarDatosManual);

            document.getElementById('btnModoSalida').addEventListener('click', () => setModo('salida'));
            document.getElementById('btnModoEntrada').addEventListener('click', () => setModo('entrada'));

            const buscador = document.getElementById('buscadorSedeInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            const btnCambiar = document.getElementById('btnCambiarSede');

            buscador.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                if (val) {
                    clearBtn.style.display = 'block';
                    buscarSedes(val);
                    document.getElementById('resultadosBusqueda').classList.add('show');
                } else {
                    clearBtn.style.display = 'none';
                    document.getElementById('resultadosBusqueda').classList.remove('show');
                }
            });

            clearBtn.addEventListener('click', () => {
                buscador.value = '';
                clearBtn.style.display = 'none';
                document.getElementById('resultadosBusqueda').classList.remove('show');
            });

            btnCambiar.addEventListener('click', () => {
                sedeActual = null;
                document.getElementById('sedeSeleccionadaInfo').style.display = 'none';
                document.getElementById('escaneoPanel').style.display = 'none';
                document.getElementById('comprobantePanel').style.display = 'none';
                buscador.value = '';
                buscador.focus();
                tulasVerificadas = [];
                mostrarTulasInicial(true);
            });

            // Los resultados se redibujan con innerHTML, así que el listener va en
            // el contenedor (que no se reemplaza) y lee el id del data-sede.
            document.getElementById('resultadosBusqueda').addEventListener('click', (e) => {
                const item = e.target.closest('.sede-item');
                if (item && item.dataset.sede) seleccionarSede(item.dataset.sede);
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-box') && !e.target.closest('.results-dropdown')) {
                    document.getElementById('resultadosBusqueda').classList.remove('show');
                }
            });

            document.getElementById('btnEscanear').addEventListener('click', escanearTula);
            document.getElementById('btnManual').addEventListener('click', mostrarModalManual);
            document.getElementById('codigoInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') escanearTula();
            });

            document.getElementById('btnGenerarComprobante').addEventListener('click', () => {
                if (tulasVerificadas.length === 0) {
                    alert('No hay tulas verificadas');
                    return;
                }
                generarComprobantePDF();
            });
        }

        // ================ MODAL MANUAL =================
        function mostrarModalManual() {
            if (bloqueadoPorRespaldo('El ingreso manual')) return;
            document.getElementById('modalTulaInput').value = '';
            document.getElementById('modalResult').style.display = 'none';
            document.getElementById('modalResult').className = 'modal-result';
            document.getElementById('modalManual').style.display = 'flex';
            setTimeout(() => document.getElementById('modalTulaInput').focus(), 100);
        }

        function cerrarModalManual() {
            document.getElementById('modalManual').style.display = 'none';
        }

        // El ingreso manual existe para cuando la brida está rota y el lector no
        // la lee: se digita el mismo código que se habría escaneado, no otra cosa.
        function confirmarManual() {
            const codigo = document.getElementById('modalTulaInput').value.trim();
            if (!codigo) {
                mostrarResultadoModal('Escribí el código del marchamo', 'warning');
                return;
            }

            const buscado = codigo.toUpperCase();
            const bulto = tulasDeSede.find(b => (marchamoDelModo(b) || '').toUpperCase() === buscado);

            if (!bulto) {
                mostrarResultadoModal(
                    `Ninguna tula pendiente de esta sede tiene el marchamo de ` +
                    `${modoActual === 'entrada' ? 'retorno' : 'salida'} "${escapar(codigo)}"`, 'error');
                return;
            }

            if (tulasVerificadas.some(v => v.id === bulto.id)) {
                mostrarResultadoModal(`Esa tula (${escapar(nombrarAulasDeTula(bulto))}) ya está verificada`, 'warning');
                return;
            }

            mostrarResultadoModal(
                `Tula encontrada: ${escapar(nombrarAulasDeTula(bulto))} · ${bulto.folletos} folletos. ¿Verificar?`,
                'success', bulto);
            tulaPendienteManual = bulto;
        }

        function mostrarResultadoModal(mensaje, tipo, tula = null) {
            const resultDiv = document.getElementById('modalResult');
            resultDiv.className = `modal-result ${tipo}`;
            resultDiv.innerHTML = mensaje;
            resultDiv.style.display = 'block';

            if (tula && tipo === 'success') {
                const confirmarBtn = document.createElement('button');
                confirmarBtn.className = 'modal-btn modal-btn-primary';
                confirmarBtn.style.marginTop = '1rem';
                confirmarBtn.style.width = '100%';
                confirmarBtn.innerHTML = '<i class="fa-solid fa-check"></i> Sí, verificar tula';
                confirmarBtn.onclick = () => {
                    cerrarModalManual();
                    verificarTula(tula.id);
                };
                resultDiv.appendChild(confirmarBtn);
            }
        }

        // Los datos vienen de un Sheet que edita gente: se escapan antes de
        // insertarlos como HTML para que un valor con < o " no rompa la página.
        function escapar(texto) {
            return String(texto ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // ================ BUSCADOR (Gestion) =================
        function buscarSedes(termino) {
            const t = termino.toLowerCase();
            sedesFiltradas = sedesData.filter(s => 
                s.id.toLowerCase().includes(t) || s.nombre.toLowerCase().includes(t)
            );
            mostrarResultados();
        }

        function mostrarResultados() {
            const div = document.getElementById('resultadosBusqueda');
            if (!sedesFiltradas.length) {
                div.innerHTML = `<div class="empty-state" style="padding:2rem;"><i class="fa-regular fa-face-frown"></i><p>No se encontraron sedes</p></div>`;
            } else {
                let html = '';
                sedesFiltradas.slice(0, 15).forEach(s => {
                    const cantidad = tulasData.filter(t => t.idSede === s.id).length;
                    html += `
                        <button type="button" class="sede-item" data-sede="${escapar(s.id)}">
                            <span class="sede-item-id">${escapar(s.id)}</span>
                            <span class="sede-item-name">${escapar(s.nombre)}</span>
                            <span class="sede-item-count">📦 ${cantidad}</span>
                        </button>
                    `;
                });
                if (sedesFiltradas.length > 15) {
                    html += `<div style="padding:1rem; text-align:center; color:var(--gray-500);">y ${sedesFiltradas.length - 15} más...</div>`;
                }
                div.innerHTML = html;
            }
            div.classList.add('show');
        }

        window.seleccionarSede = function(idSede) {
            const sede = sedesData.find(s => s.id === idSede);
            if (!sede) return;

            ocultarVisualizadorSedes();
            sedeActual = idSede;
            sedeNombreActual = sede.nombre;
            
            document.getElementById('sedeSeleccionadaNombre').textContent = sede.nombre;
            document.getElementById('sedeSeleccionadaId').textContent = `ID: ${sede.id}`;
            document.getElementById('sedeSeleccionadaInfo').style.display = 'flex';
            
            document.getElementById('resultadosBusqueda').classList.remove('show');
            document.getElementById('buscadorSedeInput').value = '';
            document.getElementById('clearSearchBtn').style.display = 'none';
            
            cargarTulasSedePorId(idSede);
        };

        function ocultarVisualizadorSedes() {
            const visualizador = document.getElementById('visualizadorSedesWrapper');
            if (visualizador) {
                visualizador.style.display = 'none';
            }
        }

        function cargarDatosRespaldo() {
            tulasData = [
                { idSede: '1', NombreSede: 'UCR-FAC. CIENCIAS SOCIALES P-2', idUnico: '1-1', asignados: 36, estado: 'disponible', coordinador: 'MARLEN SANCHEZ MONTERO', fechaAplicacion: '10/05/2024', convocatoria: '2- M-SABADO', totalCajas: '6', desdeCoord: '288', hastaCoord: '295', desde: '1', hasta: '36', aula: '1', cantidadFolletosTotal: 295, codigoBarras: '123456', marchamo1: 'M001', marchamo2: 'M002', correoCoordinador: 'coordinador@example.com' },
                { idSede: '1', NombreSede: 'UCR-FAC. CIENCIAS SOCIALES P-2', idUnico: '1-2', asignados: 30, estado: 'disponible', coordinador: 'MARLEN SANCHEZ MONTERO', fechaAplicacion: '10/05/2024', convocatoria: '2- M-SABADO', totalCajas: '', desdeCoord: '', desde: '37', hasta: '66', aula: '2', cantidadFolletosTotal: 295, codigoBarras: '123457', marchamo1: 'M003', marchamo2: 'M004', correoCoordinador: 'coordinador@example.com' },
                { idSede: '2', NombreSede: 'UCR-FAC. CIENCIAS SOCIALES P-3', idUnico: '2-1', asignados: 35, estado: 'disponible', coordinador: 'DIANA MARTINEZ', fechaAplicacion: '10/05/2024', convocatoria: '2- M-SABADO', totalCajas: '', desdeCoord: '', desde: '296', hasta: '330', aula: '1', cantidadFolletosTotal: 281, codigoBarras: '123458', marchamo1: 'M005', marchamo2: 'M006', correoCoordinador: 'diana.martinez@example.com' },
                { idSede: '2', NombreSede: 'UCR-FAC. CIENCIAS SOCIALES P-3', idUnico: '2-2', asignados: 25, estado: 'disponible', coordinador: 'DIANA MARTINEZ', fechaAplicacion: '10/05/2024', convocatoria: '2- M-SABADO', totalCajas: '6', desdeCoord: '569', hastaCoord: '576', desde: '331', hasta: '355', aula: '2', cantidadFolletosTotal: 281, codigoBarras: '123459', marchamo1: 'M007', marchamo2: 'M008', correoCoordinador: 'diana.martinez@example.com' },
                { idSede: '32', NombreSede: 'COLEGIO ANGLOAMERICANO', idUnico: '32-1', asignados: 24, estado: 'disponible', coordinador: 'DIANA CAROLINA GONZALEZ JIMENEZ', fechaAplicacion: '10/05/2024', convocatoria: '2- M-SABADO', totalCajas: '5', desdeCoord: '8230', hastaCoord: '8237', desde: '8040', hasta: '8063', aula: '1', cantidadFolletosTotal: 198, codigoBarras: '123460', marchamo1: 'M009', marchamo2: 'M010', correoCoordinador: 'diana.gonzalez@example.com' }
            ];
            activarModoRespaldo();
            tulasFiltradas = [...tulasData];
            actualizarSedesData();
            document.getElementById('contadorTulas').innerHTML = `📦 ${tulasData.length} tulas`;
            actualizarContadores();
            mostrarTulasInicial(true);
        }

        function setModo(modo) {
            modoActual = modo;
            const btnSalida = document.getElementById('btnModoSalida');
            const btnEntrada = document.getElementById('btnModoEntrada');
            const ind = document.getElementById('modoActual');
            if (modo === 'salida') {
                btnSalida.classList.add('active');
                btnEntrada.classList.remove('active');
                ind.textContent = 'SALIDA';
                ind.style.background = 'var(--primary)';
            } else {
                btnEntrada.classList.add('active');
                btnSalida.classList.remove('active');
                ind.textContent = 'ENTRADA';
                ind.style.background = 'var(--primary)';
            }
            tulasVerificadas = [];
            actualizarContadores();
            if (sedeActual) {
                cargarTulasSedePorId(sedeActual);
            } else {
                mostrarTulasInicial(true);
            }
        }

        /**
         * Junta las filas (aulas) en bultos por su par de bridas.
         *
         * Las aulas que comparten MARCHAMO1 y MARCHAMO2 son una sola tula: se
         * embalaron juntas y viajan juntas. Las que todavía no tienen bridas no
         * se pueden escanear —no hay qué leer—, así que cada una queda como su
         * propio bulto marcado `sinMarchamo`, para que se vea que falta
         * asignarlas y no desaparezcan de la lista.
         */
        function agruparEnTulas(filas) {
            const porPar = new Map();
            const bultos = [];

            filas.forEach(fila => {
                const salida = (fila.marchamo1 || '').trim();
                const entrada = (fila.marchamo2 || '').trim();
                const sinMarchamo = !salida || !entrada;

                const clave = sinMarchamo
                    ? `sin:${fila.idUnico}`
                    : `par:${salida.toUpperCase()}|${entrada.toUpperCase()}`;

                if (!porPar.has(clave)) {
                    const bulto = {
                        id: clave,
                        marchamoSalida: salida,
                        marchamoEntrada: entrada,
                        sinMarchamo,
                        aulas: [],
                        folletos: 0,
                        idSede: fila.idSede,
                        NombreSede: fila.NombreSede,
                        coordinador: fila.coordinador,
                        correoCoordinador: fila.correoCoordinador,
                        convocatoria: fila.convocatoria,
                        fechaAplicacion: fila.fechaAplicacion,
                        estado: fila.estado
                    };
                    porPar.set(clave, bulto);
                    bultos.push(bulto);
                }

                const bulto = porPar.get(clave);
                bulto.aulas.push(fila);
                bulto.folletos += fila.asignados || 0;
            });

            return bultos;
        }

        /**
         * Los NÚMEROS de aula del bulto: "3" o "1, 2 y 3".
         *
         * Son identificadores, no una cuenta. Por eso nunca se muestra junto a
         * ellos cuántas aulas son: puestos al lado, "1, 2 y 3" y "3 aulas" se
         * leen como si el 3 fuera lo mismo en los dos, y no lo es.
         */
        function numerosDeAula(bulto) {
            const numeros = bulto.aulas.map(a => a.aula);
            if (numeros.length === 1) return String(numeros[0]);
            return `${numeros.slice(0, -1).join(', ')} y ${numeros[numeros.length - 1]}`;
        }

        /** Lo mismo, con su preposición, para meterlo en una frase. */
        function nombrarAulasDeTula(bulto) {
            return `${bulto.aulas.length === 1 ? 'aula' : 'aulas'} ${numerosDeAula(bulto)}`;
        }

        /** La brida que se escanea en el modo activo. */
        function marchamoDelModo(bulto) {
            return modoActual === 'entrada' ? bulto.marchamoEntrada : bulto.marchamoSalida;
        }

        function cargarTulasSedePorId(id) {
            sedeActual = id;

            tulasSedeCompleta = tulasFiltradas.filter(t => t.idSede === id);

            if (modoActual === 'entrada') {
                tulasSedeActual = tulasSedeCompleta.filter(t => t.estado === 'prestado');
            } else {
                tulasSedeActual = tulasSedeCompleta.filter(t => t.estado === 'disponible');
            }

            tulasDeSede = agruparEnTulas(tulasSedeActual);
            tulasVerificadas = [];

            // Acá y no más abajo: si la sede no tiene tulas en el modo activo,
            // la función sale por otra rama y el resumen se quedaría mostrando
            // la sede anterior.
            actualizarContadores();

            const disponibles = tulasSedeCompleta.filter(t => t.estado === 'disponible').length;
            const prestadas = tulasSedeCompleta.filter(t => t.estado === 'prestado').length;

            if (tulasSedeActual.length === 0) {
                let mensaje = '';
                let sugerencia = '';
                let botonAccion = null;
                
                if (modoActual === 'salida' && disponibles === 0 && prestadas > 0) {
                    mensaje = `La sede ${sedeNombreActual} tiene todas sus tulas prestadas. No hay tulas disponibles para salida.`;
                    sugerencia = '¿Quieres cambiar a modo ENTRADA para registrar devoluciones?';
                    botonAccion = {
                        texto: 'Cambiar a ENTRADA',
                        modo: 'entrada'
                    };
                } else if (modoActual === 'entrada' && prestadas === 0 && disponibles > 0) {
                    mensaje = `La sede ${sedeNombreActual} tiene todas sus tulas disponibles. No hay tulas prestadas para devolver.`;
                    sugerencia = '¿Quieres cambiar a modo SALIDA para registrar nuevas salidas?';
                    botonAccion = {
                        texto: 'Cambiar a SALIDA',
                        modo: 'salida'
                    };
                } else {
                    mensaje = `No hay tulas en estado "${modoActual === 'salida' ? 'disponible' : 'prestado'}" en esta sede.`;
                }
                
                document.getElementById('tulasContainer').innerHTML = `
                    <div class="info-message">
                        <i class="fa-solid fa-circle-info"></i>
                        <div class="info-message-content">
                            <h4>Información de la sede</h4>
                            <p><strong>Total de tulas en la sede:</strong> ${tulasSedeCompleta.length}</p>
                            <p><strong>Disponibles:</strong> ${disponibles} | <strong>Prestadas:</strong> ${prestadas}</p>
                            <p>${mensaje}</p>
                            ${sugerencia ? `<p>${sugerencia}</p>` : ''}
                            ${botonAccion ? `
                                <button class="btn-suggest" onclick="cambiarModoYSugerencia('${botonAccion.modo}')">
                                    <i class="fa-solid fa-arrow-${botonAccion.modo === 'salida' ? 'right' : 'left'}-from-bracket"></i>
                                    ${botonAccion.texto}
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                document.getElementById('escaneoPanel').style.display = 'none';
                document.getElementById('comprobantePanel').style.display = 'none';
                return;
            }
            
            mostrarTulasSede(true);
            document.getElementById('escaneoPanel').style.display = 'block';
            document.getElementById('comprobantePanel').style.display = 'block';
            document.getElementById('codigoInput').focus();
        }

        window.cambiarModoYSugerencia = function(modo) {
            if (modo === 'salida') {
                document.getElementById('btnModoSalida').click();
            } else {
                document.getElementById('btnModoEntrada').click();
            }
        };

        window.verificarTodo = function() {
            if (bloqueadoPorRespaldo('Verificar todas las tulas')) return;
            if (tulasDeSede.length === 0) return;

            tulasDeSede.forEach(bulto => {
                if (tulasVerificadas.some(v => v.id === bulto.id)) return;
                tulasVerificadas.push(Object.assign({}, bulto, {
                    estado: modoActual === 'salida' ? 'prestado' : 'disponible'
                }));
            });

            mensajeEscaneo(`✅ Las ${tulasDeSede.length} tulas de la sede quedaron verificadas sin escanear`, 'warning');
            mostrarTulasSede();
        };

        function obtenerSedesConTulasPorModo() {
            const tulasModo = tulasFiltradas.filter(t => modoActual === 'entrada' ? t.estado === 'prestado' : t.estado === 'disponible');
            const agrupadas = agruparTulasPorSede(tulasModo);
            const mapAgrupadas = new Map(agrupadas.map(g => [String(g.id), g.tulas]));

            if (sedesData.length === 0) {
                return agrupadas;
            }

            return sedesData.map(s => ({
                id: String(s.id),
                nombre: s.nombre || `Sede ${s.id}`,
                tulas: mapAgrupadas.get(String(s.id)) || []
            }));
        }

        function crearControlesPaginacion(totalItems) {
            const totalPaginas = Math.max(1, Math.ceil(totalItems / TULAS_POR_PAGINA));
            paginaActualTulas = Math.min(Math.max(1, paginaActualTulas), totalPaginas);

            const inicio = (paginaActualTulas - 1) * TULAS_POR_PAGINA;
            const fin = Math.min(inicio + TULAS_POR_PAGINA, totalItems);

            let botones = `
                <button class="pagination-btn" title="Primera" ${paginaActualTulas === 1 ? 'disabled' : ''} onclick="cambiarPaginaTulas(1)">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button class="pagination-btn" title="Retroceder 10" ${paginaActualTulas <= 1 ? 'disabled' : ''} onclick="cambiarPaginaTulas(${paginaActualTulas - 10})">-10</button>
                <button class="pagination-btn" ${paginaActualTulas === 1 ? 'disabled' : ''} onclick="cambiarPaginaTulas(${paginaActualTulas - 1})">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            `;

            const desde = Math.max(1, paginaActualTulas - 2);
            const hasta = Math.min(totalPaginas, paginaActualTulas + 2);

            for (let p = desde; p <= hasta; p++) {
                botones += `<button class="pagination-btn ${p === paginaActualTulas ? 'active' : ''}" onclick="cambiarPaginaTulas(${p})">${p}</button>`;
            }

            botones += `
                <button class="pagination-btn" ${paginaActualTulas === totalPaginas ? 'disabled' : ''} onclick="cambiarPaginaTulas(${paginaActualTulas + 1})">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button class="pagination-btn" title="Avanzar 10" ${paginaActualTulas >= totalPaginas ? 'disabled' : ''} onclick="cambiarPaginaTulas(${paginaActualTulas + 10})">+10</button>
                <button class="pagination-btn" title="Última" ${paginaActualTulas === totalPaginas ? 'disabled' : ''} onclick="cambiarPaginaTulas(${totalPaginas})">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            `;

            return {
                totalPaginas,
                inicio,
                fin,
                html: `
                    <div class="pagination-wrap">
                        <div class="pagination-info">Mostrando ${inicio + 1}-${fin} de ${totalItems} tulas · Página ${paginaActualTulas}/${totalPaginas}</div>
                        <div class="pagination-controls">${botones}</div>
                    </div>
                `
            };
        }

        function crearControlesPaginacionSedes(totalItems) {
            const totalPaginas = Math.max(1, Math.ceil(totalItems / SEDES_POR_PAGINA));
            paginaActualSedes = Math.min(Math.max(1, paginaActualSedes), totalPaginas);

            const inicio = (paginaActualSedes - 1) * SEDES_POR_PAGINA;
            const fin = Math.min(inicio + SEDES_POR_PAGINA, totalItems);

            let botones = `
                <button class="pagination-btn" title="Primera" ${paginaActualSedes === 1 ? 'disabled' : ''} onclick="cambiarPaginaSedes(1)">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button class="pagination-btn" title="Retroceder 10" ${paginaActualSedes <= 1 ? 'disabled' : ''} onclick="cambiarPaginaSedes(${paginaActualSedes - 10})">-10</button>
                <button class="pagination-btn" ${paginaActualSedes === 1 ? 'disabled' : ''} onclick="cambiarPaginaSedes(${paginaActualSedes - 1})">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            `;

            const desde = Math.max(1, paginaActualSedes - 2);
            const hasta = Math.min(totalPaginas, paginaActualSedes + 2);

            for (let p = desde; p <= hasta; p++) {
                botones += `<button class="pagination-btn ${p === paginaActualSedes ? 'active' : ''}" onclick="cambiarPaginaSedes(${p})">${p}</button>`;
            }

            botones += `
                <button class="pagination-btn" ${paginaActualSedes === totalPaginas ? 'disabled' : ''} onclick="cambiarPaginaSedes(${paginaActualSedes + 1})">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button class="pagination-btn" title="Avanzar 10" ${paginaActualSedes >= totalPaginas ? 'disabled' : ''} onclick="cambiarPaginaSedes(${paginaActualSedes + 10})">+10</button>
                <button class="pagination-btn" title="Última" ${paginaActualSedes === totalPaginas ? 'disabled' : ''} onclick="cambiarPaginaSedes(${totalPaginas})">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            `;

            return {
                totalPaginas,
                inicio,
                fin,
                html: `
                    <div class="pagination-wrap">
                        <div class="pagination-info">Mostrando ${inicio + 1}-${fin} de ${totalItems} sedes · Página ${paginaActualSedes}/${totalPaginas}</div>
                        <div class="pagination-controls">${botones}</div>
                    </div>
                `
            };
        }


        /**
         * La lista de bultos de la sede abierta.
         *
         * Se muestran TODOS, verificados y faltantes, en la misma tabla: quien
         * está recibiendo un camión necesita ver de un vistazo qué le falta, no
         * solo lo que ya pasó. Las faltantes van arriba y resaltadas.
         */
        function renderTablaTulasSede(permitirVerificacion = true) {
            if (!Array.isArray(tulasDeSede) || tulasDeSede.length === 0) {
                document.getElementById('tulasContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No hay tulas para mostrar</h3>
                        <p>Intenta cambiar el modo o seleccionar otra sede</p>
                    </div>
                `;
                document.getElementById('resultadosCount').textContent = 'Mostrando 0 resultados';
                return;
            }

            const estaVerificada = b => tulasVerificadas.some(v => v.id === b.id);

            // Primero lo que falta: es lo que se está buscando físicamente.
            const ordenadas = [...tulasDeSede].sort((a, b) => {
                const dif = Number(estaVerificada(a)) - Number(estaVerificada(b));
                if (dif !== 0) return dif;
                return Number(a.aulas[0].aula) - Number(b.aulas[0].aula);
            });

            const pag = crearControlesPaginacion(ordenadas.length);
            const pagina = ordenadas.slice(pag.inicio, pag.fin);
            const faltan = ordenadas.filter(b => !estaVerificada(b)).length;

            let html = permitPerificacionHeader(permitirVerificacion);

            html += faltan === 0
                ? `<div class="aviso-tulas completa"><i class="fa-solid fa-circle-check"></i>
                       Las ${ordenadas.length} tulas de esta sede están verificadas.</div>`
                : `<div class="aviso-tulas"><i class="fa-solid fa-magnifying-glass"></i>
                       Faltan <strong>${faltan}</strong> de ${ordenadas.length} tulas.
                       Escaneá el marchamo de ${modoActual === 'entrada' ? 'retorno' : 'salida'} de cada una.</div>`;

            html += `
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:20%">Marchamo ${modoActual === 'entrada' ? 'retorno' : 'salida'}</th>
                                <th style="width:20%">N.º de aula</th>
                                <th style="width:12%">Folletos</th>
                                <th style="width:16%">Marchamo ${modoActual === 'entrada' ? 'salida' : 'retorno'}</th>
                                <th style="width:16%">Estado</th>
                                <th style="width:16%">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            pagina.forEach(b => {
                const verif = estaVerificada(b);
                const marchamo = marchamoDelModo(b);
                const otra = modoActual === 'entrada' ? b.marchamoSalida : b.marchamoEntrada;

                html += `
                    <tr class="${verif ? 'tula-ok' : 'tula-falta'}">
                        <td>${b.sinMarchamo
                                ? '<span class="sin-marchamo">SIN ASIGNAR</span>'
                                : `<strong class="marchamo-codigo">${escapar(marchamo)}</strong>`}</td>
                        <td><strong class="numero-aula">${escapar(numerosDeAula(b))}</strong></td>
                        <td>${b.folletos}</td>
                        <td>${otra ? `<span class="marchamo-codigo secundario">${escapar(otra)}</span>` : '—'}</td>
                        <td>${verif
                                ? '<span class="check-verificado"><i class="fa-regular fa-circle-check"></i> Verificada</span>'
                                : '<span class="check-falta"><i class="fa-regular fa-circle"></i> Falta</span>'}</td>
                        <td>
                            ${permitirVerificacion
                                ? (!verif
                                    ? `<button class="btn-verificar" onclick="verificarTula('${escapar(b.id)}')">Verificar a mano</button>`
                                    : `<button class="btn-desverificar" onclick="desverificarTula('${escapar(b.id)}')">Quitar</button>`)
                                : '<span class="tula-readonly-note">Modo consulta</span>'
                            }
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
                ${pag.html}
            `;

            document.getElementById('tulasContainer').innerHTML = html;
            document.getElementById('resultadosCount').textContent =
                `${ordenadas.length} tula${ordenadas.length === 1 ? '' : 's'} en esta sede`;
        }

        /**
         * Ficha de una fila (aula) en la vista de consulta por sedes.
         *
         * Solo lee: la verificación se hace en la tabla de la sede abierta, que
         * trabaja con bultos. Acá el encabezado lo abre el MARCHAMO, que es la
         * identificación oficial desde que se dejó de usar el código de barras.
         */
        function renderTulaAccordionItem(t) {
            const estClass = t.estado === 'disponible' ? 'estado-disponible' : 'estado-prestado';
            const estText = t.estado === 'disponible' ? 'Disponible' : 'Prestada';
            const marchamo = (modoActual === 'entrada' ? t.marchamo2 : t.marchamo1) || '';

            return `
                <details class="tula-accordion-item">
                    <summary>
                        <span class="tula-col">${marchamo
                            ? `<strong class="marchamo-codigo">${escapar(marchamo)}</strong>`
                            : '<span class="sin-marchamo">SIN MARCHAMO</span>'}</span>
                        <span class="tula-col">Aula ${escapar(t.aula || '—')}</span>
                        <span class="tula-col">${t.asignados} folletos</span>
                        <span class="tula-col"><span class="${estClass}">${estText}</span></span>
                        <span class="tula-expand-hint"><i class="fa-solid fa-chevron-down"></i> Ver detalle</span>
                    </summary>
                    <div class="tula-accordion-body">
                        <div class="tula-detail-grid">
                            <div class="tula-detail-item"><strong>Sede:</strong> ${escapar(t.idSede)} · ${escapar(t.NombreSede || 'Sin nombre')}</div>
                            <div class="tula-detail-item"><strong>Aula:</strong> ${escapar(t.aula || 'N/A')}</div>
                            <div class="tula-detail-item"><strong>Marchamo salida:</strong> ${escapar(t.marchamo1 || 'sin asignar')}</div>
                            <div class="tula-detail-item"><strong>Marchamo retorno:</strong> ${escapar(t.marchamo2 || 'sin asignar')}</div>
                            <div class="tula-detail-item"><strong>Convocatoria:</strong> ${escapar(t.convocatoria || 'N/A')}</div>
                            <div class="tula-detail-item"><strong>Fecha aplicación:</strong> ${escapar(t.fechaAplicacion || 'N/A')}</div>
                            <div class="tula-detail-item"><strong>Coordinador:</strong> ${escapar(t.coordinador || 'N/A')}</div>
                            <div class="tula-detail-item"><strong>Correo coordinador:</strong> ${escapar(t.correoCoordinador || 'N/A')}</div>
                            <div class="tula-detail-item"><strong>Desde/Hasta:</strong> ${escapar(t.desde || '—')} / ${escapar(t.hasta || '—')}</div>
                            ${t.folletosSueltos ? `<div class="tula-detail-item"><strong>Folletos sueltos:</strong> ${escapar(t.folletosSueltos.replace(/\n/g, ', '))}</div>` : ''}
                        </div>
                        <div class="tula-actions">
                            <span class="tula-readonly-note">Modo consulta por sedes</span>
                            <button class="btn-verificar" onclick="seleccionarSede('${escapar(t.idSede)}')">
                                <i class="fa-solid fa-arrow-right"></i> Abrir sede
                            </button>
                        </div>
                    </div>
                </details>
            `;
        }

        function agruparTulasPorSede(lista = []) {
            const map = new Map();
            lista.forEach(t => {
                const key = String(t.idSede || 'SIN-SEDE');
                if (!map.has(key)) {
                    map.set(key, {
                        id: key,
                        nombre: t.NombreSede || `Sede ${key}`,
                        tulas: []
                    });
                }
                map.get(key).tulas.push(t);
            });
            return Array.from(map.values()).sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }));
        }

        function renderAcordeonTulasPorSede(sedesConTulas) {
            if (!Array.isArray(sedesConTulas) || sedesConTulas.length === 0) {
                document.getElementById('tulasContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No hay tulas para mostrar</h3>
                        <p>Intenta cambiar el modo o seleccionar otra sede</p>
                    </div>
                `;
                document.getElementById('resultadosCount').textContent = 'Mostrando 0 resultados';
                return;
            }

            const totalSedes = sedesConTulas.length;
            const pag = crearControlesPaginacionSedes(totalSedes);
            const grupos = sedesConTulas.slice(pag.inicio, pag.fin);
            const totalTulasPagina = grupos.reduce((acc, g) => acc + g.tulas.length, 0);

            let html = `<div id="visualizadorSedesWrapper" class="accordion-list">`;

            grupos.forEach(grupo => {
                html += `
                    <details class="sede-accordion-item">
                        <summary>
                            <div class="sede-summary-main">
                                <span class="sede-summary-id">Sede ${grupo.id}</span>
                                <span class="sede-summary-name">${grupo.nombre}</span>
                            </div>
                            <span class="sede-summary-meta">${grupo.tulas.length} tula(s) en modo ${modoActual.toUpperCase()}</span>
                        </summary>
                        <div class="accordion-list">
                            ${grupo.tulas.length
                                ? grupo.tulas.map(t => renderTulaAccordionItem(t, false)).join('')
                                : `<div class="tula-readonly-note" style="padding:0.5rem 0.75rem; text-align:left;">No hay tulas para esta sede en modo ${modoActual.toUpperCase()}.</div>`
                            }
                        </div>
                    </details>
                `;
            });

            html += `</div>${pag.html}`;

            document.getElementById('tulasContainer').innerHTML = html;
            document.getElementById('resultadosCount').textContent = `Mostrando ${grupos.length} de ${totalSedes} sedes (${totalTulasPagina} tulas en página)`;
        }

        function permitPerificacionHeader(permitirVerificacion) {
            if (!permitirVerificacion) return '';
            return `
                <div class="action-bar">
                    <button onclick="verificarTodo()" class="btn-verificar-todo">
                        <i class="fa-solid fa-check-double"></i> Verificar Todo
                    </button>
                </div>
            `;
        }

        function mostrarTulasInicial(resetPagina = false) {
            if (resetPagina) paginaActualSedes = 1;

            const sedesConTulas = obtenerSedesConTulasPorModo();
            renderAcordeonTulasPorSede(sedesConTulas);

            document.getElementById('escaneoPanel').style.display = 'none';
            document.getElementById('comprobantePanel').style.display = 'none';
        }

        function mostrarTulasSede(resetPagina = false) {
            if (resetPagina) paginaActualTulas = 1;
            renderTablaTulasSede(true);
            actualizarPanelComprobante();
        }

        window.cambiarPaginaTulas = function(pagina) {
            paginaActualTulas = Number(pagina) || 1;
            if (sedeActual) {
                mostrarTulasSede();
            } else {
                mostrarTulasInicial();
            }
        };

        window.cambiarPaginaSedes = function(pagina) {
            paginaActualSedes = Number(pagina) || 1;
            mostrarTulasInicial();
        };

        window.verificarTula = function(idTula) {
            if (bloqueadoPorRespaldo('Verificar tulas')) return;

            const bulto = tulasDeSede.find(b => b.id === idTula);
            if (!bulto) return;

            if (tulasVerificadas.some(v => v.id === idTula)) {
                mensajeEscaneo(`⚠️ Esa tula ya estaba verificada (${nombrarAulasDeTula(bulto)})`, 'warning');
                return;
            }

            // Al verificar el bulto se verifican TODAS sus aulas: el material va
            // físicamente junto bajo la misma brida, no se puede recibir media
            // tula.
            tulasVerificadas.push(Object.assign({}, bulto, {
                estado: modoActual === 'salida' ? 'prestado' : 'disponible'
            }));

            const marchamo = marchamoDelModo(bulto);
            const faltan = tulasDeSede.length - tulasVerificadas.length;

            mensajeEscaneo(
                `✅ ${marchamo ? marchamo + ' · ' : ''}${nombrarAulasDeTula(bulto)} · ${bulto.folletos} folletos` +
                (faltan > 0 ? ` — faltan ${faltan} tula${faltan === 1 ? '' : 's'}` : ' — sede completa'),
                faltan > 0 ? 'success' : 'success');

            mostrarTulasSede();
        };

        window.desverificarTula = function(idTula) {
            tulasVerificadas = tulasVerificadas.filter(v => v.id !== idTula);
            mensajeEscaneo('↩️ Tula quitada de la lista', 'info');
            mostrarTulasSede();
        };

        /**
         * Busca una brida en TODO el Sheet y devuelve a qué fila pertenece.
         *
         * En salida se busca contra MARCHAMO1 y en entrada contra MARCHAMO2:
         * son bridas distintas y confundirlas es justo lo que hay que detectar.
         * Si el código aparece en la columna equivocada se devuelve igual, pero
         * marcado, para poder decir qué pasó en lugar de un "no encontrado".
         */
        function ubicarPorMarchamo(codigo) {
            const buscado = codigo.trim().toUpperCase();
            if (!buscado) return null;

            const campoDelModo = modoActual === 'entrada' ? 'marchamo2' : 'marchamo1';
            const campoContrario = modoActual === 'entrada' ? 'marchamo1' : 'marchamo2';

            const enSuCampo = tulasData.find(t => (t[campoDelModo] || '').trim().toUpperCase() === buscado);
            if (enSuCampo) return { fila: enSuCampo, correcta: true };

            const enElOtro = tulasData.find(t => (t[campoContrario] || '').trim().toUpperCase() === buscado);
            if (enElOtro) return { fila: enElOtro, correcta: false };

            return null;
        }

        /**
         * El escaneo manda: la brida dice de qué sede es la tula, así que si no
         * hay sede abierta se abre sola, y si es de otra se ofrece cambiar. Antes
         * había que elegir la sede primero y escanear después; ahora quien recibe
         * un camión no necesita saber de dónde viene cada bulto.
         */
        async function escanearTula() {
            if (bloqueadoPorRespaldo('Escanear tulas')) return;
            const cod = document.getElementById('codigoInput').value.trim();
            if (!cod) return alert('Escaneá el marchamo de la tula');

            const limpiarCampo = () => {
                document.getElementById('codigoInput').value = '';
                document.getElementById('codigoInput').focus();
            };

            const hallazgo = ubicarPorMarchamo(cod);

            if (!hallazgo) {
                mensajeEscaneo(`❌ Ningún marchamo ${modoActual === 'entrada' ? 'de retorno' : 'de salida'} tiene el código "${cod}"`, 'danger');
                document.getElementById('codigoInput').select();
                return;
            }

            // Leer la brida del otro sentido casi siempre significa que se abrió
            // la tula por el lado que no era, o que se está en el modo equivocado.
            if (!hallazgo.correcta) {
                const esDeSalida = modoActual === 'entrada';
                mensajeEscaneo(
                    `❌ Ese código es el marchamo de ${esDeSalida ? 'SALIDA' : 'RETORNO'}, ` +
                    `y en modo ${modoActual.toUpperCase()} se escanea el de ${esDeSalida ? 'RETORNO' : 'SALIDA'}` +
                    ` (sede ${hallazgo.fila.idSede}, ${hallazgo.fila.aula ? 'aula ' + hallazgo.fila.aula : ''})`, 'danger');
                document.getElementById('codigoInput').select();
                return;
            }

            const fila = hallazgo.fila;

            // La tula es de otra sede: se ofrece saltar, pero avisando de lo que
            // se pierde si hay verificaciones sin guardar.
            if (sedeActual && fila.idSede !== sedeActual) {
                const pendientes = tulasVerificadas.length;
                const seguir = await modalConfirmacion(
                    `Esa tula es de la sede ${fila.idSede} (${fila.NombreSede || ''}), y estás en la ${sedeActual}.\n\n` +
                    (pendientes
                        ? `Tenés ${pendientes} tula${pendientes === 1 ? '' : 's'} verificada${pendientes === 1 ? '' : 's'} sin guardar en esta sede; cambiar las descarta.\n\n`
                        : '') +
                    `¿Cambiar a la sede ${fila.idSede}?`,
                    'Marchamo de otra sede'
                );
                if (!seguir) { limpiarCampo(); return; }
            }

            if (fila.idSede !== sedeActual) {
                seleccionarSede(fila.idSede);
            }

            // Puede no estar en la lista del modo: ya se entregó, ya se devolvió,
            // o la sede no la tiene en ese estado.
            const bulto = tulasDeSede.find(b => b.aulas.some(a => a.idUnico === fila.idUnico));
            if (!bulto) {
                mensajeEscaneo(
                    `⚠️ La tula existe (sede ${fila.idSede}, ${fila.aula ? 'aula ' + fila.aula : ''}) pero no está ` +
                    `${modoActual === 'salida' ? 'disponible para salida' : 'pendiente de devolución'}. ` +
                    `Su estado es "${fila.estado}".`, 'warning');
                limpiarCampo();
                return;
            }

            verificarTula(bulto.id);
            limpiarCampo();
        }

        function mensajeEscaneo(texto, tipo = 'info') {
            const colores = { danger: 'var(--danger)', warning: 'var(--warning)', success: 'var(--success)', info: 'var(--info)' };
            document.getElementById('mensajeEscaneo').innerHTML =
                `<span style="color:${colores[tipo] || colores.info};">${escapar(texto)}</span>`;
        }

        function actualizarPanelComprobante() {
            document.getElementById('contadorVerificadas').innerText =
                `${tulasVerificadas.length}/${tulasDeSede.length}`;

            const div = document.getElementById('resumenTulas');
            if (!tulasVerificadas.length) {
                div.innerHTML = '<p style="color:var(--gray-500);">Ninguna tula verificada todavía</p>';
                return;
            }

            const faltantes = tulasFaltantes();

            let html = '<ul>';
            tulasVerificadas.forEach(b => {
                const marchamo = marchamoDelModo(b);
                html += `<li><strong>${escapar(marchamo || 'sin marchamo')}</strong> · ` +
                        `${escapar(nombrarAulasDeTula(b))} · ${b.folletos} folletos</li>`;
            });
            html += '</ul>';

            // Lo que falta se dice acá también, y no solo en la tabla: este es el
            // panel desde el que se aprieta "enviar comprobante".
            if (faltantes.length) {
                html += `<div class="resumen-faltantes">
                    <strong>Faltan ${faltantes.length} tula${faltantes.length === 1 ? '' : 's'}:</strong>
                    ${faltantes.map(b => escapar(marchamoDelModo(b) || nombrarAulasDeTula(b))).join(' · ')}
                </div>`;
            }

            div.innerHTML = html;
        }

        /** Los bultos de la sede que todavía nadie verificó. */
        function tulasFaltantes() {
            return tulasDeSede.filter(b => !tulasVerificadas.some(v => v.id === b.id));
        }

        // ================ NUEVA FUNCIÓN: SOLO ACTUALIZAR ESTADO =================
        async function soloActualizarEstado() {
            if (bloqueadoPorRespaldo('Actualizar estados en el Sheet')) return;
            if (tulasVerificadas.length === 0) {
                alert('No hay tulas verificadas para actualizar');
                return;
            }
            
            const confirmar = await modalConfirmacion('¿Estás seguro de que deseas actualizar el estado de las tulas seleccionadas? No se generará ningún comprobante.', 'Actualizar estado');
            if (confirmar) {
                actualizarEstadosEnGoogleSheets();
            }
        }

        // ================ COMPROBANTE PDF LOCAL (IMPRIMIR / DESCARGAR) =================
        function construirHtmlComprobanteCompleto(d, folio) {
            const esc = v => String(v == null ? '' : v)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;').replace(/'/g,'&#39;');

            const modo          = String(d.m || '').toLowerCase().trim();
            const sedeId        = esc(d.s);
            const sedeNombre    = esc(d.n);
            const coord         = esc(d.c || 'No especificado');
            const fechaApp      = esc(d.fa || '');
            const conv          = esc(d.cv || '');
            const fecha         = esc(d.f || '');
            const hora          = esc(d.h || '');
            const fo            = esc(folio || d.fo || '');
            const totalTulas    = d.tt || 0;
            const totalFolletos = d.tf || 0;
            const totalAulas    = d.ta || 0;

            // Cada renglón es una TULA, no un aula: es el bulto lo que se
            // entrega y lo que se recibe, identificado por su brida.
            // [0] marchamo del modo · [1] aulas · [2] folletos · [3] la otra brida
            const etiquetaBrida = modo === 'entrada' ? 'Marchamo retorno' : 'Marchamo salida';
            const etiquetaOtra  = modo === 'entrada' ? 'Marchamo salida' : 'Marchamo retorno';

            const filas = (d.it || []).map(item => {
                const marchamo = esc(item[0] || '');
                const aulas    = esc(item[1] || '');
                const folletos = item[2] || 0;
                const otra     = esc(item[3] || '');

                return `<tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:4px;font-size:10px;font-family:monospace;font-weight:bold;">${marchamo || '—'}</td>
                    <td style="padding:4px;font-size:10px;">${aulas}</td>
                    <td style="padding:4px;font-size:10px;">${folletos}</td>
                    <td style="padding:4px 6px 4px 4px;font-size:9px;font-family:monospace;color:#4b5563;">${otra || '—'}</td>
                </tr>`;
            }).join('');

            // Las tulas que no se entregaron o no llegaron. Van al final, en su
            // propio bloque: quien firma tiene que ver qué NO está recibiendo.
            const faltantes = d.ft || [];
            const bloqueFaltantes = faltantes.length ? `
  <div style="margin-bottom:12px;border:2px solid #dc2626;border-radius:4px;overflow:hidden;">
    <div style="background:#dc2626;color:white;padding:5px 8px;font-size:11px;font-weight:bold;">
      NO ${modo === 'entrada' ? 'RECIBIDAS' : 'ENTREGADAS'} — ${faltantes.length} tula${faltantes.length === 1 ? '' : 's'}
    </div>
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr style="background:#fee2e2;">
        <th style="padding:3px 6px;text-align:left;font-size:9px;">${etiquetaBrida}</th>
        <th style="padding:3px 6px;text-align:left;font-size:9px;">N.º de aula</th>
        <th style="padding:3px 6px;text-align:left;font-size:9px;">Folletos</th>
      </tr></thead>
      <tbody>${faltantes.map(f => `
        <tr style="border-bottom:1px solid #fecaca;">
          <td style="padding:3px 6px;font-size:9px;font-family:monospace;">${esc(f[0] || '—')}</td>
          <td style="padding:3px 6px;font-size:9px;">${esc(f[1] || '')}</td>
          <td style="padding:3px 6px;font-size:9px;">${f[2] || 0}</td>
        </tr>`).join('')}
      </tbody>
    </table>
    <div style="padding:5px 8px;font-size:9px;background:#fef2f2;color:#7f1d1d;">
      Este comprobante ampara únicamente las tulas listadas arriba como
      ${modo === 'entrada' ? 'recibidas' : 'entregadas'}.
    </div>
  </div>` : '';

            // ── SALIDA ─────────────────────────────────────────────────
            if (modo !== 'entrada') {
                return `<div style="font-family:Arial,sans-serif;width:96%;max-width:900px;margin:0;padding:12px;background:white;font-size:10px;">
  <div style="text-align:center;border-bottom:2px solid #6EC1E4;padding-bottom:8px;margin-bottom:12px;">
    <h2 style="color:#0055a4;margin:0;font-size:18px;">IIP · PAA</h2>
    <h3 style="color:#0055a4;margin:2px 0;font-size:14px;">MATERIAL SALIDA - IIP - PAA</h3>
    <p style="color:#6b7280;margin:2px 0 0 0;font-size:9px;">Comprobante de salida de materiales</p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f9fafb;padding:8px;border-radius:6px;margin-bottom:12px;">
    <div>
      <p style="margin:2px 0;font-size:9px;"><strong>Sede:</strong> ${sedeId} - ${sedeNombre}</p>
      <p style="margin:2px 0;font-size:9px;"><strong>Coordinador:</strong> ${coord}</p>
      <p style="margin:2px 0;font-size:9px;"><strong>Fecha aplicación:</strong> ${fechaApp}</p>
    </div>
    <div>
      <p style="margin:2px 0;font-size:9px;"><strong>Convocatoria:</strong> ${conv}</p>
      <p style="margin:2px 0;font-size:9px;"><strong>Fecha salida:</strong> ${fecha}</p>
      <p style="margin:2px 0;font-size:9px;"><strong>Hora:</strong> ${hora}</p>
      <p style="margin:2px 0;font-size:9px;"><strong>Folio:</strong> ${fo}</p>
    </div>
  </div>
  <h4 style="color:#6EC1E4;border-bottom:1px solid #6EC1E4;padding-bottom:3px;margin:0 0 8px 0;font-size:11px;">MATERIAL SALIDA - IIP - PAA</h4>
  <table style="width:100%;border-collapse:collapse;margin-bottom:8px;font-size:9px;">
    <thead><tr style="background:#6EC1E4;color:white;">
      <th style="padding:4px;text-align:left;">${etiquetaBrida}</th>
      <th style="padding:4px;text-align:left;">N.º de aula</th>
      <th style="padding:4px;text-align:left;">Folletos</th>
      <th style="padding:4px 6px 4px 4px;text-align:left;">${etiquetaOtra}</th>
    </tr></thead>
    <tbody>${filas}</tbody>
  </table>
  <div style="display:flex;justify-content:flex-start;gap:30px;background:#e8f0fe;padding:6px 10px;border-radius:4px;margin-bottom:10px;font-size:9px;">
    <span><strong>Tulas entregadas:</strong> ${totalTulas}</span>
    <span><strong>Total folletos:</strong> ${totalFolletos}</span>
    <span><strong>Total aulas:</strong> ${totalAulas}</span>
  </div>
  ${bloqueFaltantes}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;background:#fff3cd;padding:6px 10px;border-radius:4px;margin-bottom:12px;font-size:9px;">
    <div><strong>Inclusiones:</strong> _____</div>
    <div><strong>Reposición:</strong> _____</div>
    <div><strong>Anulaciones:</strong> _____</div>
    <div><strong>Retiros:</strong> _____</div>
  </div>
  <div style="background:#f3f4f6;padding:8px;border-left:4px solid #0055a4;margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:8px;justify-content:space-between;">
      <span style="font-weight:600;color:#0055a4;font-size:9px;">Funcionario que despachó:</span>
            <span style="border-bottom:2px solid #000;flex:1;padding:2px 0;font-size:9px;">&nbsp;</span>
    </div>
  </div>
  <div style="text-align:center;margin-top:5px;border-top:1px solid #6EC1E4;padding-top:3px;font-size:6px;color:#6b7280;">Documento generado el ${fecha} a las ${hora} · Folio: ${fo}</div>
</div>`;
            }

            // ── ENTRADA ─────────────────────────────────────────────────
            return `<div style="font-family:Arial,sans-serif;width:96%;max-width:900px;margin:0;padding:12px;background:white;font-size:11px;">
  <div style="text-align:center;border-bottom:2px solid #6EC1E4;padding-bottom:10px;margin-bottom:12px;">
    <h2 style="color:#0055a4;margin:0;font-size:20px;">IIP · PAA</h2>
  </div>
  <div style="text-align:center;margin-bottom:12px;">
    <h2 style="color:#0055a4;margin:0;font-size:16px;">MATERIAL ENTRADA - IIP - PAA</h2>
    <p style="color:#6b7280;margin:2px 0 0 0;font-size:10px;">Comprobante de devolución de materiales</p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f9fafb;padding:8px;border-radius:6px;margin-bottom:12px;">
    <div>
      <p style="margin:2px 0;font-size:10px;"><strong>Sede:</strong> ${sedeId} - ${sedeNombre}</p>
      <p style="margin:2px 0;font-size:10px;"><strong>Coordinador:</strong> ${coord}</p>
      <p style="margin:2px 0;font-size:10px;"><strong>Fecha aplicación:</strong> ${fechaApp}</p>
    </div>
    <div>
      <p style="margin:2px 0;font-size:10px;"><strong>Convocatoria:</strong> ${conv}</p>
      <p style="margin:2px 0;font-size:10px;"><strong>Fecha devolución:</strong> ${fecha}</p>
      <p style="margin:2px 0;font-size:10px;"><strong>Hora:</strong> ${hora}</p>
      <p style="margin:2px 0;font-size:10px;"><strong>Folio:</strong> ${fo}</p>
    </div>
  </div>
    <h4 style="color:#6EC1E4;border-bottom:1px solid #6EC1E4;padding-bottom:3px;margin:0 0 8px 0;font-size:12px;">MATERIAL ENTRADA - IIP - PAA</h4>
    <table style="width:100%;border-collapse:collapse;margin-bottom:10px;table-layout:fixed;">
        <colgroup>
            <col style="width:28%;">
            <col style="width:26%;">
            <col style="width:18%;">
            <col style="width:28%;">
        </colgroup>
        <thead><tr style="background:#6EC1E4;color:white;">
            <th style="padding:4px;text-align:left;font-size:9px;white-space:nowrap;">${etiquetaBrida}</th>
            <th style="padding:4px;text-align:left;font-size:9px;white-space:nowrap;">N.º de aula</th>
            <th style="padding:4px;text-align:left;font-size:9px;white-space:nowrap;">Folletos</th>
            <th style="padding:4px 8px 4px 4px;text-align:left;font-size:9px;white-space:nowrap;">${etiquetaOtra}</th>
        </tr></thead>
        <tbody>${filas}</tbody>
    </table>
  <div style="display:flex;justify-content:flex-start;gap:30px;background:#e8f0fe;padding:6px 10px;border-radius:4px;margin-bottom:10px;font-size:9px;">
    <span><strong>Tulas recibidas:</strong> ${totalTulas}</span>
    <span><strong>Total folletos:</strong> ${totalFolletos}</span>
    <span><strong>Total aulas:</strong> ${totalAulas}</span>
  </div>
  ${bloqueFaltantes}
  <div style="background:#f3f4f6;padding:6px;border-left:4px solid #0055a4;margin-bottom:8px;">
    <div style="display:flex;align-items:center;gap:8px;">
      <span style="font-weight:600;color:#0055a4;font-size:10px;">Funcionario que recibió:</span>
            <span style="border-bottom:2px solid #000;flex:1;padding:2px 0;font-size:10px;">&nbsp;</span>
    </div>
  </div>
  <div style="margin-bottom:16px;padding:12px;background:#f0f7ff;border-left:5px solid #0055a4;border-radius:0 8px 8px 0;">
    <p style="font-size:11px;line-height:1.6;margin:0;">
      <strong>Yo ${coord} devuelvo al Instituto de Investigaciones Psicológicas</strong><br>
      en forma completa y ordenada, el material que se puso bajo mi responsabilidad para la aplicación<br>
      de la <strong>Prueba de Aptitud Académica de la Universidad de Costa Rica</strong>.
    </p>
    <div style="margin-top:20px;display:flex;flex-direction:column;">
      <div style="border-bottom:2px solid #0055a4;width:60%;margin:0 auto 5px auto;"></div>
      <p style="text-align:center;font-size:10px;font-weight:600;color:#0055a4;margin:0;">Firma del Coordinador(a)</p>
    </div>
  </div>
  <hr style="border:none;border-top:3px double #6EC1E4;margin:30px 0 20px 0;">
  <div style="margin-top:20px;">
    <h2 style="color:#0055a4;margin:0 0 15px 0;font-size:16px;text-align:center;">COMPROBANTE DE DEVOLUCIÓN DE MATERIALES AL IIP</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;background:#f0f7ff;padding:6px;border-radius:4px;margin-bottom:10px;font-size:8px;">
      <div>
        <p style="margin:2px 0;"><strong>Sede:</strong> ${sedeId} - ${sedeNombre}</p>
        <p style="margin:2px 0;"><strong>Coordinador:</strong> ${coord}</p>
      </div>
      <div>
        <p style="margin:2px 0;"><strong>Fecha:</strong> ${fecha}</p>
        <p style="margin:2px 0;"><strong>Hora:</strong> ${hora}</p>
        <p style="margin:2px 0;"><strong>Folio:</strong> ${fo}</p>
      </div>
    </div>
    <div style="background:#f0f7ff;padding:12px;border-left:5px solid #0055a4;border-radius:0 4px 4px 0;margin-bottom:12px;">
      <p style="font-size:10px;line-height:1.5;margin:0;">
        <strong>El Instituto de Investigaciones Psicológicas recibe</strong> del (de la) señor(a):<br>
        <span style="border-bottom:1px dashed #0055a4;display:inline-block;min-width:200px;padding:2px 0;">${coord}</span><br>
        <strong>Coordinador(a) de la sede:</strong>
        <span style="border-bottom:1px dashed #0055a4;display:inline-block;min-width:200px;padding:2px 0;">${sedeNombre}</span><br>
        En forma completa y ordenada el material puesto bajo su responsabilidad para la aplicación<br>
        de la <strong>Prueba de Aptitud Académica de la Universidad de Costa Rica</strong>.
      </p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;margin:20px 0 10px;">
      <div style="text-align:center;">
        <p style="margin:0;font-size:11px;">___________________________________</p>
        <p style="margin:3px 0 0 0;font-size:10px;"><strong>Coordinador(a) Sede</strong></p>
        <p style="margin:2px 0 0 0;font-size:8px;color:#6b7280;">Entrega</p>
      </div>
      <div style="text-align:center;">
        <p style="margin:0;font-size:11px;">___________________________________</p>
        <p style="margin:3px 0 0 0;font-size:10px;"><strong>P/IIP</strong></p>
        <p style="margin:2px 0 0 0;font-size:8px;color:#6b7280;">Recibe</p>
      </div>
    </div>
    <div style="text-align:center;margin-top:15px;border-top:1px solid #6EC1E4;padding-top:6px;font-size:8px;color:#6b7280;">Documento generado el ${fecha} a las ${hora} · Folio: ${fo}</div>
  </div>
</div>`;
        }

        function abrirComprobantePDF(d, folio) {
            const html = construirHtmlComprobanteCompleto(d, folio);
            const win = window.open('', '_blank');
            if (!win) {
                alert('El navegador bloqueó la ventana emergente. Permite las ventanas emergentes para este sitio y vuelve a intentarlo.');
                return;
            }
            win.document.write(`<!DOCTYPE html><html><head>
                <meta charset="UTF-8">
                <title>Comprobante ${folio || ''}</title>
                <style>
                    @media print { body { margin:0.5cm; } @page { size:A4 portrait; margin:0.5cm; } }
                    body { font-family:Arial,sans-serif; margin:0; }
                </style>
            </head><body>${html}<script>window.onload=()=>setTimeout(()=>window.print(),300);<\/script></body></html>`);
            win.document.close();
        }

        async function generarComprobantePDF() {
            if (bloqueadoPorRespaldo('Generar y enviar el comprobante')) return;
            if (tulasVerificadas.length === 0) {
                alert('No hay tulas verificadas');
                return;
            }

            // Un comprobante incompleto es válido —a veces una tula se queda o
            // llega después— pero nunca puede salir sin decirlo: quien lo firma
            // está dando por recibido lo que la hoja diga.
            const faltantes = tulasFaltantes();
            if (faltantes.length) {
                const seguir = await modalConfirmacion(
                    `Faltan ${faltantes.length} de ${tulasDeSede.length} tulas de la sede ${sedeActual}:\n\n` +
                    faltantes.map(b => `  • ${marchamoDelModo(b) || 'sin marchamo'} — ${nombrarAulasDeTula(b)} (${b.folletos} folletos)`).join('\n') +
                    `\n\nEl comprobante saldrá con las ${tulasVerificadas.length} verificadas y ` +
                    `dejando constancia de las que faltan.\n\n¿Generarlo así?`,
                    `Faltan tulas por ${modoActual === 'salida' ? 'entregar' : 'recibir'}`
                );
                if (!seguir) return;
            }

            const sede = tulasData.find(t => t.idSede === sedeActual);
            const primera = tulasVerificadas[0];
            const coordinador = primera?.coordinador || 'No especificado';
            const fechaApp = primera?.fechaAplicacion || '';
            const conv = primera?.convocatoria || 'No especificada';
            const fecha = new Date().toLocaleDateString('es-ES');
            const hora = new Date().toLocaleTimeString('es-ES');
            const folio = `PAA-${sedeActual}-${Date.now().toString().slice(-6)}`;
            const totalExam = tulasVerificadas.reduce((s, b) => s + (b.folletos || 0), 0);
            const totalAulas = tulasVerificadas.reduce((s, b) => s + b.aulas.length, 0);

            // Cada renglón es un BULTO: marchamo del modo, aulas, folletos y la
            // otra brida. Las faltantes viajan aparte, para poder listarlas al
            // final del comprobante sin mezclarlas con lo entregado.
            const comprobanteData = {
                m: modoActual,
                s: sedeActual,
                n: sede?.NombreSede || '',
                c: coordinador,
                fa: fechaApp,
                cv: conv,
                f: fecha,
                h: hora,
                fo: folio,
                tt: tulasVerificadas.length,
                tf: totalExam,
                ta: totalAulas,
                it: tulasVerificadas.map(b => [
                    marchamoDelModo(b) || '',
                    numerosDeAula(b),
                    b.folletos || 0,
                    (modoActual === 'entrada' ? b.marchamoSalida : b.marchamoEntrada) || ''
                ]),
                ft: faltantes.map(b => [
                    marchamoDelModo(b) || '',
                    numerosDeAula(b),
                    b.folletos || 0
                ])
            };

            // ===== RECOLECTAR CORREOS ÚNICOS DE COORDINADORES =====
            const correosUnicos = new Set();
            
            // Agregar correos de tulas verificadas
            tulasVerificadas.forEach(tula => {
                const correo = (tula?.correoCoordinador || '').trim();
                if (correo && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                    correosUnicos.add(correo.toLowerCase());
                }
            });

            // Si no hay correos en tulas verificadas, buscar en tulasSedeCompleta
            if (correosUnicos.size === 0) {
                tulasSedeCompleta.forEach(tula => {
                    const correo = (tula?.correoCoordinador || '').trim();
                    if (correo && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                        correosUnicos.add(correo.toLowerCase());
                    }
                });
            }

            // Si aún no hay correos y hay CORREO_PREDETERMINADO, usarlo
            if (correosUnicos.size === 0 && CORREO_PREDETERMINADO) {
                correosUnicos.add(CORREO_PREDETERMINADO.toLowerCase());
            }

            if (correosUnicos.size === 0) {
                alert('No se encontraron correos de coordinador en las tulas verificadas.\nVerifica que el campo "correo coordinador" esté completo en el Sheet.');
                return;
            }

            // Convertir Set a Array y mostrar lista de correos
            const correosArray = Array.from(correosUnicos);
            console.log(`📧 Enviando comprobante a ${correosArray.length} coordinador(es): ${correosArray.join(', ')}`);

            const tipoLabel = modoActual === 'salida' ? 'Salida de Tulas' : 'Entrada y Devolución de Tulas';
            const asunto = `Comprobante de ${tipoLabel} – Sede ${sedeActual} – PAA UCR`;

            // Enviar comprobante a cada coordinador único
            enviarComprobanteViaEmail(comprobanteData, folio, correosArray, asunto, modoActual);
        }

        function construirActaHtmlMinimaDesdeData(d) {
            // Mismo orden de columnas que el comprobante completo: marchamo del
            // modo, aulas del bulto y folletos.
            const filas = (d?.it || []).map(item => `
                <tr>
                    <td style="padding:6px;border:1px solid #ddd;font-family:monospace;"><strong>${escapar(item[0] || '—')}</strong></td>
                    <td style="padding:6px;border:1px solid #ddd;">${escapar(item[1] || '')}</td>
                    <td style="padding:6px;border:1px solid #ddd;text-align:right;">${item[2] || 0}</td>
                </tr>
            `).join('');

            const tipo = d?.m === 'entrada' ? 'ENTRADA / DEVOLUCIÓN' : 'SALIDA';
            return `
                <div style="font-family:Arial,sans-serif;max-width:760px;margin:0 auto;padding:16px;">
                    <h2 style="margin:0 0 8px 0;color:#0055a4;">Comprobante de ${tipo} de Tulas</h2>
                    <p style="margin:4px 0;"><strong>Sede:</strong> ${d?.s || ''} - ${d?.n || ''}</p>
                    <p style="margin:4px 0;"><strong>Coordinador:</strong> ${d?.c || ''}</p>
                    <p style="margin:4px 0;"><strong>Convocatoria:</strong> ${d?.cv || ''}</p>
                    <p style="margin:4px 0;"><strong>Fecha:</strong> ${d?.f || ''} <strong>Hora:</strong> ${d?.h || ''}</p>
                    <p style="margin:4px 0 12px 0;"><strong>Folio:</strong> ${d?.fo || ''}</p>

                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr style="background:#eaf2fb;">
                                <th style="padding:6px;border:1px solid #ddd;text-align:left;">Código</th>
                                <th style="padding:6px;border:1px solid #ddd;text-align:left;">ID Tula</th>
                                <th style="padding:6px;border:1px solid #ddd;text-align:right;">Folletos</th>
                            </tr>
                        </thead>
                        <tbody>${filas}</tbody>
                    </table>

                    <p style="margin:12px 0 0 0;"><strong>Total tulas:</strong> ${d?.tt || 0} · <strong>Total folletos:</strong> ${d?.tf || 0} · <strong>Total aulas:</strong> ${d?.ta || 0}</p>
                </div>
            `;
        }

        // ================ ENVIAR COMPROBANTE DE TULAS POR CORREO =================
        // ================ ENVIAR COMPROBANTE DE TULAS POR CORREO =================
        //
        // Se manda el NÚMERO DE SEDE, no los correos: el Apps Script resuelve
        // los destinatarios contra el Sheet. Si aceptara direcciones arbitrarias
        // sería un relay de correo abierto, porque esta página es pública y su
        // URL está a la vista de cualquiera.
        //
        // Por eso también es un solo envío y no un bucle por coordinador: el
        // servidor sabe a cuántos le toca escribir.
        async function enviarComprobanteViaEmail(comprobanteData, folio, correosArray, asunto, tipo) {
            const correos = Array.isArray(correosArray) ? correosArray : [correosArray];

            if (!sedeActual) {
                alert('No hay una sede seleccionada.');
                return;
            }
            if (!correos.length) {
                alert('No se encontró el correo del coordinador de esta sede.\nVerifica que el campo correoCoordinador esté completo en el Sheet.');
                return;
            }

            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('mensajeEscaneo').innerHTML =
                `<span style="color:var(--info);">📧 Enviando comprobante a ${correos.length} coordinador(es)...</span>`;

            const datos = {
                sede: sedeActual,
                asunto: asunto,
                actaHTML: construirActaHtmlMinimaDesdeData(comprobanteData),
                folio: folio,
                tipo: tipo || modoActual
            };

            try {
                const respuesta = await enviarComprobanteAlServidor(datos);
                const enviados = respuesta.destinatarios ? respuesta.destinatarios.length : correos.length;

                // Mientras Tulas no tenga cuenta institucional, el servidor
                // contesta success igual (para no dejar las tulas sin marcar
                // como entregadas) pero avisa que no mandó nada de verdad.
                if (respuesta.correoEnviado === false) {
                    document.getElementById('mensajeEscaneo').innerHTML =
                        `<span style="color:var(--warning);">⚠️ ${escapar(respuesta.aviso || 'No se envió correo, pero los estados se actualizaron.')}</span>`;
                } else {
                    document.getElementById('mensajeEscaneo').innerHTML =
                        `<span style="color:var(--success);">✅ Comprobante enviado a ${enviados} coordinador(es)</span>`;
                }
                console.log('📧 Enviado a:', respuesta.destinatarios);

                abrirComprobantePDF(comprobanteData, folio);
                actualizarEstadosEnGoogleSheets();

            } catch (error) {
                console.error('Error enviando el comprobante:', error);
                document.getElementById('mensajeEscaneo').innerHTML =
                    `<span style="color:var(--danger);">❌ No se pudo enviar el comprobante</span>`;
                alert(`❌ No se pudo enviar el comprobante: ${error.message || error}`);
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        }

        const TIMEOUT_ENVIO_MS = 45000;

        // Envía el comprobante al coordinador de la sede.
        //
        // Acá había dos caminos: un POST y, si fallaba, un respaldo por JSONP
        // (inyectar un <script> con los datos en la URL). Ese rodeo existía
        // porque Apps Script no maneja el preflight de CORS y porque su doPost
        // a veces no estaba publicado. Con el endpoint propio, que se sirve
        // desde el mismo dominio que esta página, nada de eso aplica: es un
        // POST normal con JSON.
        //
        // El límite de 6 KB del método viejo también desaparece — el
        // comprobante de una sede grande ya no se corta.
        //
        // La regla de seguridad se mantiene: se manda el NÚMERO DE SEDE, no la
        // dirección de correo. El servidor resuelve el destinatario contra la
        // base. Si aceptara la dirección del cliente, esta página pública sería
        // un relay de correo a nombre de la UCR.
        async function enviarComprobanteAlServidor(datos) {
            const controlador = new AbortController();
            const timeout = setTimeout(() => controlador.abort(), TIMEOUT_ENVIO_MS);

            try {
                const respuesta = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'enviarComprobante', ...datos, csrf: CSRF }),
                    signal: controlador.signal
                });

                const data = await respuesta.json();

                if (!data || !data.success) {
                    throw new Error(data?.error || 'el servidor rechazó el envío');
                }
                return data;

            } finally {
                clearTimeout(timeout);
            }
        }


        async function actualizarEstadosEnGoogleSheets() {
            if (tulasVerificadas.length === 0) {
                console.log('No hay tulas verificadas para actualizar');
                return;
            }

            document.getElementById('loadingOverlay').style.display = 'flex';

            // El Sheet sigue teniendo una fila por aula, así que cada bulto se
            // despliega en todas las suyas: verificar una tula de tres aulas
            // cambia el estado de las tres.
            const actualizacionesPayload = tulasVerificadas.flatMap(bulto =>
                bulto.aulas.map(fila => ({
                    SEDE: fila.idSede,
                    NUMERO_AULA_EXAMEN: fila.aula,
                    codigo_barras: fila.codigoBarras || '',
                    nuevoEstado: bulto.estado
                }))
            );

            console.log('Enviando actualizaciones a la base:', actualizacionesPayload);

            // El endpoint se sirve desde el mismo dominio, así que la respuesta
            // se puede leer directamente.
            //
            // Antes esto era un POST con mode:'no-cors' —que impide leer lo que
            // contesta el servidor— y después había que volver a descargar el
            // CSV entero para comprobar si el cambio se había aplicado
            // (verificarActualizacionEnSheet_, con reintentos y esperas). Todo
            // ese rodeo desaparece: el endpoint dice qué aplicó y qué rechazó.
            try {
                const controlador = new AbortController();
                const timeoutId = setTimeout(() => controlador.abort(), 45000);

                const respuesta = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accion: 'actualizarEstados',
                        actualizaciones: actualizacionesPayload.map(u => ({
                            sede: u.SEDE,
                            aula: u.NUMERO_AULA_EXAMEN,
                            codigoBarras: u.codigo_barras,
                            estado: u.nuevoEstado
                        })),
                        csrf: CSRF
                    }),
                    signal: controlador.signal
                });

                clearTimeout(timeoutId);

                const data = await respuesta.json();

                if (!data.success) {
                    throw new Error(data.error || 'el servidor rechazó la actualización');
                }

                // El servidor puede aceptar unas filas y rechazar otras (una
                // tula que ya no existe, por ejemplo). Eso se avisa en vez de
                // darlo todo por bueno.
                if (data.rechazadas && data.rechazadas.length > 0) {
                    console.warn('Filas rechazadas por el servidor:', data.rechazadas);
                    const detalle = data.rechazadas
                        .map(r => `${r.identificador}: ${r.motivo}`)
                        .join('\n');
                    mostrarNotificacion(
                        `⚠️ ${data.rechazadas.length} de ${actualizacionesPayload.length} filas no se actualizaron`,
                        'warning', 8000
                    );
                    console.warn(detalle);
                }

                if (data.total === 0) {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    document.getElementById('mensajeEscaneo').innerHTML =
                        '<span style="color:var(--danger);">❌ No se actualizó ninguna tula</span>';
                    alert('No se actualizó ninguna tula. Revisá el detalle en la consola del navegador.');
                    return;
                }

                manejarExitoActualizacion_(actualizacionesPayload, {
                    exito: true,
                    success: true,
                    mensaje: `${data.total} fila(s) actualizadas en la base.`
                });
                return;

            } catch (postError) {
                console.error('❌ Error al actualizar la base:', postError);
                document.getElementById('loadingOverlay').style.display = 'none';
                document.getElementById('mensajeEscaneo').innerHTML = '<span style="color:var(--danger);">❌ Error de conexión</span>';
                alert('Error de conexión. No se pudieron guardar los cambios.');
                throw postError;
            }
        }

        function manejarExitoActualizacion_(actualizaciones, data) {
            console.log('✅ Actualización exitosa:', data.mensaje);
            // El payload va por sede + aula, que es de lo que se compone idUnico:
            // se rearma en vez de mandar un campo extra que el servidor ignora.
            actualizaciones.forEach(upd => {
                const idUnico = `${upd.SEDE}-${upd.NUMERO_AULA_EXAMEN}`;
                const idx = tulasData.findIndex(x => x.idUnico === idUnico);
                if (idx !== -1) tulasData[idx].estado = upd.nuevoEstado;
            });
            tulasFiltradas = tulasData.map(t => ({...t}));
            actualizarContadores();
            if (sedeActual) cargarTulasSedePorId(sedeActual);
            document.getElementById('mensajeEscaneo').innerHTML = '<span style="color:var(--success);">✅ Estados actualizados en Google Sheets</span>';
            document.getElementById('loadingOverlay').style.display = 'none';
            alert('✅ Estados actualizados correctamente');
            tulasVerificadas = [];
            mostrarTulasSede();
        }
    </script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
