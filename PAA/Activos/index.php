<?php
/**
 * Módulo de uso interno: solo administración.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("activos", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="robots" content="noindex, nofollow">
  <title>Gestión PAA · Activos de Préstamo · UCR</title>
  <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    :root {
      /* Paleta base del sistema PAA (la misma de Ingreso de Datos, Revisión,
         Traslados, Usuarios y Anuncios) — el resto de nombres de variable de
         este módulo quedan como alias suyos para no reescribir cada regla. */
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

      --primary-blue: var(--primary-light);
      --primary-blue-dark: var(--primary);
      --secondary-blue: var(--primary);
      --secondary-blue-dark: var(--primary-dark);
      --light-blue: #e3f0fa;
      --dark-blue: var(--primary-dark);
      --text-dark: var(--gray-800);
      --text-light: var(--gray-500);
      --text-white: #ffffff;
      --background: var(--gray-50);
      --white: #ffffff;
      --border-color: var(--gray-200);
      --shadow-light: var(--shadow);
      --shadow-medium: 0 4px 16px rgba(17, 24, 39, .10);
      --shadow-hover: 0 6px 20px rgba(17, 24, 39, .12);
      --ok: var(--success);
      --ok-dark: #065f46;
      --warn: var(--warning);
      --warn-dark: #92400e;
      --bad: var(--danger);
      --bad-dark: #991b1b;
      --highlight: #fef3c7;
      --highlight-border: #fde68a;
      --modal-overlay: rgba(17, 24, 39, .55);
      --scan-highlight: #e3f0fa;
      --btn-delete: var(--danger);
      --btn-delete-hover: #dc2626;
      --btn-copy: var(--primary);
      --btn-copy-hover: var(--primary-dark);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
      color: var(--gray-800);
      line-height: 1.5;
      min-height: 100vh;
      padding: 1.5rem;
    }

    .app { max-width: 1320px; margin: 0 auto; }

    .barra-sesion {
      display: flex; justify-content: space-between; align-items: center;
      gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
      font-size: 0.875rem; color: var(--gray-600);
    }
    .barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .barra-sesion a:hover { text-decoration: underline; }

    .hero { text-align: center; margin-bottom: 1.75rem; }
    .hero h1 {
      font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
      background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
      -webkit-background-clip: text; background-clip: text; color: transparent;
      margin-bottom: .35rem;
    }
    .hero p { color: var(--gray-600); font-size: .95rem; }

    /* FILTROS */
    .filtros-container {
      background: white;
      padding: 1.4rem 1.5rem;
      border-radius: 14px;
      margin-bottom: 1.25rem;
      border: 1px solid var(--border-color);
      display: flex;
      flex-wrap: wrap;
      gap: 15px 20px;
      align-items: flex-start;
    }

    .filtro-busqueda {
      flex: 2;
      min-width: 300px;
      position: relative;
      padding-bottom: 1.4rem;
    }

    .filtro-busqueda input {
      width: 100%;
      padding: 12px 45px 12px 45px;
      border: 1.5px solid var(--border-color);
      border-radius: 10px;
      font-size: 15px;
      font-family: inherit;
      transition: border-color .15s ease, box-shadow .15s ease;
      background: var(--background);
      color: var(--text-dark);
    }

    .filtro-busqueda input:focus {
      outline: none;
      border-color: var(--primary-blue);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(43, 127, 194, .15);
    }

    .filtro-busqueda::before {
      content: '\f002';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      color: var(--text-light);
      z-index: 1;
    }

    .filtro-busqueda::after {
      content: '\f00d';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      color: var(--text-light);
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.2s ease;
      pointer-events: none;
    }

    .filtro-busqueda.limpiable::after {
      opacity: 0.8;
      pointer-events: auto;
    }

    .filtro-busqueda.limpiable::after:hover {
      opacity: 1;
      color: var(--bad);
    }

    .badge-busqueda {
      position: absolute;
      left: -8px;
      top: -8px;
      background: var(--secondary-blue);
      color: white;
      font-size: 10px;
      padding: 2px 7px;
      border-radius: 20px;
      font-weight: 700;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .filtro-busqueda.con-filtro .badge-busqueda {
      opacity: 1;
    }

    /* Aviso rápido al escanear un código que no coincide con ningún activo. */
    .filtro-busqueda.sin-resultado input {
      border-color: var(--bad);
      animation: sacudida 0.4s ease;
    }
    @keyframes sacudida {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-6px); }
      75% { transform: translateX(6px); }
    }

    .ayuda-busqueda {
      position: absolute;
      bottom: -20px;
      left: 0;
      font-size: 11px;
      color: var(--text-light);
      background: var(--light-blue);
      padding: 2px 8px;
      border-radius: 12px;
      white-space: nowrap;
    }

    .filtro-orden {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      flex: 1;
      min-width: 250px;
    }

    .filtro-orden select {
      flex: 1;
      padding: 11px 14px;
      border: 1.5px solid var(--border-color);
      border-radius: 10px;
      font-size: 14px;
      font-family: inherit;
      background: white;
      cursor: pointer;
      transition: border-color .15s ease;
      font-weight: 500;
      color: var(--text-dark);
    }

    .filtro-orden select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(43, 127, 194, .15);
    }

    .selector-pagina {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: auto;
      background: var(--light-blue);
      padding: 8px 15px;
      border-radius: 10px;
    }

    .selector-pagina span {
      color: var(--text-dark);
      font-weight: 500;
      font-size: 14px;
    }

    .selector-pagina select {
      padding: 7px 10px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
      background: white;
      cursor: pointer;
      font-weight: 500;
      color: var(--text-dark);
    }

    /* CONTADOR */
    .contador-sedes {
      background: var(--secondary-blue);
      color: white;
      padding: 10px 22px;
      border-radius: 20px;
      font-weight: 700;
      margin-bottom: 15px;
      display: inline-block;
      font-size: 14px;
    }

    .boton-reintentar {
      background: var(--secondary-blue);
      color: white;
      border: none;
      padding: 11px 22px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 15px;
      font-family: inherit;
      margin: 8px;
      transition: background .15s ease;
      font-weight: 600;
    }
    .boton-reintentar:hover {
      background: var(--secondary-blue-dark);
    }

    /* ACORDEÓN */
    .acordeon {
      margin-bottom: .7rem;
      border-radius: 12px;
      overflow: hidden;
      background: #ffffff;
      transition: border-color .15s ease;
      position: relative;
      border: 1px solid var(--border-color);
    }
    .acordeon:hover { border-color: var(--primary-blue); }

    .acordeon-cabecera {
      background: #ffffff;
      padding: 1rem 1.2rem;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background .15s ease;
      position: relative;
      z-index: 1;
      gap: 14px;
    }
    .acordeon-cabecera:hover { background: var(--light-blue); }

    .acordeon-cabecera h3 {
      font-size: .98rem;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .subline {
      font-size: .78rem;
      color: var(--text-light);
      margin-top: .3rem;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .subline span {
      color: var(--text-light);
    }

    .subline b {
      color: var(--secondary-blue);
    }

    .acordeon-icono {
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--gray-100, #f3f4f6);
      color: var(--secondary-blue);
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      transition: transform .25s ease;
      flex: 0 0 auto;
    }
    .acordeon.abierto .acordeon-icono {
      transform: rotate(45deg);
      background: var(--light-blue);
    }

    .acordeon-contenido {
      background-color: #ffffff;
      max-height: 0;
      overflow: hidden;
      padding: 0 1.2rem;
      transition: max-height 0.35s ease, padding 0.2s ease;
      position: relative;
      z-index: 1;
      border-top: 1px solid transparent;
    }
    .acordeon.abierto .acordeon-contenido {
      max-height: 5000px;
      padding: 1.2rem;
      border-top-color: var(--border-color);
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: .8rem;
      margin-bottom: 10px;
    }

    .info-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: .9rem 1rem;
      background-color: var(--background);
      border-radius: 10px;
      transition: background-color .15s ease;
      position: relative;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    .info-item:hover {
      background-color: var(--light-blue);
    }

    .info-icon {
      font-size: 16px;
      color: var(--secondary-blue);
      margin-top: 2px;
      width: 34px;
      height: 34px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--light-blue);
      border-radius: 8px;
      flex: 0 0 auto;
    }

    .info-text strong {
      display: block;
      color: var(--text-light);
      margin-bottom: 3px;
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      font-weight: 700;
    }
    .info-text div {
      color: var(--text-dark);
      font-size: .92rem;
      font-weight: 500;
    }

    /* RESALTADO DE BÚSQUEDA */
    .highlight {
      background-color: var(--highlight);
      border-bottom: 2px solid var(--highlight-border);
      padding: 0 2px;
      border-radius: 3px;
      font-weight: 600;
      color: var(--text-dark);
    }

    .acciones {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 12px;
      justify-content: flex-end;
    }

    .btn {
      border: none;
      background: var(--btn-copy);
      color: var(--text-white);
      padding: 9px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-family: inherit;
      transition: background .15s ease;
      font-size: 13px;
    }
    .btn:hover {
      background: var(--btn-copy-hover);
    }

    .btn-prestamo {
      background: var(--secondary-blue);
      color: white;
      border: none;
      padding: 11px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 14px;
      font-family: inherit;
      margin: 8px 0;
      transition: background .15s ease;
      font-weight: 600;
    }
    .btn-prestamo:hover {
      background: var(--secondary-blue-dark);
    }

    .btn-entrada {
      background: #fff;
      color: var(--secondary-blue);
      border: 1.5px solid var(--secondary-blue);
      padding: 11px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 14px;
      font-family: inherit;
      margin: 8px 0;
      transition: background .15s ease;
      font-weight: 600;
    }
    .btn-entrada:hover {
      background: var(--light-blue);
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
    }
    .badge.ok {
      background: var(--light-blue);
      color: var(--secondary-blue-dark);
      border: 1px solid var(--primary-blue);
    }
    .badge.bad {
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #9ca3af;
    }
    .badge.warn {
      background: #fef3c7;
      color: var(--warn-dark);
      border: 1px solid var(--warn);
    }

    /* CARGA / ERROR */
    .estado-carga {
      text-align: center;
      padding: 3.5rem 1.5rem;
      background: #ffffff;
      border-radius: 14px;
      margin: 1.25rem 0;
      border: 1px solid var(--border-color);
      color: var(--text-light);
    }

    .spinner {
      display: inline-block;
      width: 34px;
      height: 34px;
      border: 3px solid var(--light-blue);
      border-radius: 50%;
      border-top-color: var(--secondary-blue);
      animation: spin 1s ease-in-out infinite;
      margin-bottom: 15px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .mensaje-error {
      background: #fee2e2;
      color: #991b1b;
      padding: 1.25rem;
      border-radius: 10px;
      border-left: 4px solid var(--bad);
      text-align: center;
      margin: 1.25rem 0;
    }

    /* PAGINACIÓN */
    .paginacion {
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 1.5rem 0;
      gap: 8px;
      flex-wrap: wrap;
    }
    .boton-pagina {
      background: white;
      border: 1px solid var(--border-color);
      color: var(--text-dark);
      padding: 7px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-family: inherit;
      transition: border-color .15s ease, background .15s ease;
      font-weight: 600;
      font-size: 13px;
    }
    .boton-pagina:hover {
      background: var(--light-blue);
      border-color: var(--secondary-blue);
      color: var(--secondary-blue);
    }
    .boton-pagina.activa {
      background: var(--secondary-blue);
      color: white;
      border-color: var(--secondary-blue);
    }
    .boton-pagina:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .info-paginacion {
      text-align: center;
      margin: 12px 0;
      color: var(--text-light);
      font-size: 13px;
      font-weight: 500;
    }

    /* SUGERENCIAS DE BÚSQUEDA */
    .sugerencias-container {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1.5px solid var(--primary-blue);
      border-top: none;
      border-radius: 0 0 10px 10px;
      max-height: 250px;
      overflow-y: auto;
      z-index: 1000;
      box-shadow: var(--shadow-medium);
      display: none;
    }

    .sugerencias-container.visible {
      display: block;
    }

    .sugerencia-item {
      padding: 10px 15px;
      cursor: pointer;
      transition: background .15s ease;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-dark);
    }

    .sugerencia-item:last-child {
      border-bottom: none;
    }

    .sugerencia-item:hover {
      background: var(--light-blue);
    }

    .sugerencia-item .campo {
      font-weight: 700;
      color: var(--secondary-blue);
      font-size: 11px;
      text-transform: uppercase;
    }

    .sugerencia-item .valor {
      font-weight: 600;
      color: var(--text-dark);
    }

    .sugerencia-item .tipo {
      font-size: 11px;
      color: var(--text-light);
      margin-left: 8px;
    }

    /* ===== MODAL DE PRÉSTAMO ===== */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--modal-overlay);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      padding: 1rem;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      max-width: 700px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      background: var(--secondary-blue);
      color: white;
      padding: 1.3rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border-radius: 16px 16px 0 0;
    }

    .modal-header i {
      font-size: 1.3rem;
    }

    .modal-header h3 {
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0;
      color: white;
    }

    .modal-close-top {
      margin-left: auto;
      background: rgba(255, 255, 255, 0.15);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.35);
      width: 32px;
      height: 32px;
      border-radius: 50%;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      transition: background .15s ease;
    }

    .modal-close-top:hover {
      background: rgba(255, 255, 255, 0.28);
    }

    .modal-body {
      padding: 1.6rem;
    }

    .modal-form-group {
      margin-bottom: 1.3rem;
    }

    .modal-form-group label {
      display: block;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.4rem;
      font-size: 0.85rem;
    }

    .modal-form-group input,
    .modal-form-group select,
    .modal-form-group textarea {
      width: 100%;
      padding: 0.6rem 0.85rem;
      border: 1.5px solid var(--border-color);
      border-radius: 8px;
      font-size: 0.92rem;
      font-family: inherit;
      transition: border-color .15s ease, box-shadow .15s ease;
      color: var(--text-dark);
      background: var(--background);
    }

    .modal-form-group input:focus,
    .modal-form-group select:focus,
    .modal-form-group textarea:focus {
      outline: none;
      border-color: var(--primary-blue);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(43, 127, 194, .15);
    }

    /* SCANNER SECTION */
    .scanner-section {
      background: var(--light-blue);
      border: 1.5px solid var(--primary-blue);
      border-radius: 12px;
      padding: 1.2rem;
      margin-bottom: 1.3rem;
    }

    .scanner-input-group {
      display: flex;
      gap: 0.5rem;
    }

    .scanner-input-group input {
      flex: 1;
      padding: 0.8rem 1rem;
      border: 1.5px solid var(--primary-blue);
      border-radius: 8px;
      font-size: 1rem;
      font-family: inherit;
      letter-spacing: .5px;
      color: var(--text-dark);
      background: #fff;
    }

    .scanner-input-group input:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(43, 127, 194, .2);
    }

    .btn-scanner {
      background: var(--secondary-blue);
      color: white;
      border: none;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: background .15s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-scanner:hover {
      background: var(--secondary-blue-dark);
    }

    .scanner-message {
      margin-top: 0.5rem;
      padding: 0.5rem;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 500;
    }

    .scanner-message.success {
      background: #d1fae5;
      color: var(--ok-dark);
      border: 1px solid var(--ok);
    }

    .scanner-message.error {
      background: #fee2e2;
      color: var(--bad-dark);
      border: 1px solid var(--bad);
    }

    .scanner-message.info {
      background: #e3f0fa;
      color: var(--secondary-blue-dark);
      border: 1px solid var(--secondary-blue);
    }

    .articulos-lista {
      max-height: 300px;
      overflow-y: auto;
      border: 1.5px solid var(--border-color);
      border-radius: 10px;
      padding: 0.4rem;
      background: white;
    }

    .articulo-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.7rem;
      border-bottom: 1px solid var(--border-color);
      transition: background .15s ease;
    }

    .articulo-item:last-child {
      border-bottom: none;
    }

    .articulo-item:hover {
      background: var(--scan-highlight);
    }

    .articulo-info {
      flex: 1;
    }

    .articulo-nombre {
      font-weight: 600;
      color: var(--text-dark);
      font-size: 0.92rem;
    }

    .articulo-detalle {
      font-size: 0.8rem;
      color: var(--text-light);
      margin-top: 0.2rem;
    }

    .articulo-detalle span {
      display: inline-block;
      margin-right: 1rem;
    }

    .articulo-detalle strong {
      color: var(--secondary-blue);
    }

    .btn-eliminar {
      background: var(--btn-delete);
      color: white;
      border: none;
      padding: 0.45rem 0.9rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-family: inherit;
      transition: background .15s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-eliminar:hover {
      background: var(--btn-delete-hover);
    }

    .total-articulos {
      background: var(--light-blue);
      padding: 0.7rem;
      border-radius: 8px;
      margin-top: 1rem;
      font-weight: 600;
      text-align: center;
      color: var(--secondary-blue-dark);
      font-size: .9rem;
    }

    .modal-actions {
      display: flex;
      gap: .8rem;
      justify-content: flex-end;
      margin-top: 1.6rem;
    }

    .modal-btn {
      padding: 0.7rem 1.5rem;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.9rem;
      font-family: inherit;
      cursor: pointer;
      transition: background .15s ease;
    }

    .modal-btn-primary {
      background: var(--secondary-blue);
      color: white;
    }

    .modal-btn-primary:hover {
      background: var(--secondary-blue-dark);
    }

    .modal-btn-secondary {
      background: var(--background);
      color: var(--text-dark);
      border: 1px solid var(--border-color);
    }

    .modal-btn-secondary:hover {
      background: var(--border-color);
    }

    .modal-info {
      background: var(--light-blue);
      border-left: 3px solid var(--secondary-blue);
      padding: .9rem 1rem;
      border-radius: 8px;
      margin-bottom: 1.3rem;
      font-size: 0.88rem;
      color: var(--text-dark);
    }

    .modal-info i {
      color: var(--secondary-blue);
      margin-right: 0.5rem;
    }

    /* ===== MODAL DE CARGA ===== */
    #modalCarga {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(17, 24, 39, .5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 20000;
    }

    .modal-carga-contenido {
      background: white;
      padding: 26px 36px;
      border-radius: 16px;
      text-align: center;
      box-shadow: var(--shadow-hover);
    }

    .modal-carga-spinner {
      display: inline-block;
      width: 42px;
      height: 42px;
      border: 4px solid var(--light-blue);
      border-radius: 50%;
      border-top-color: var(--secondary-blue);
      animation: spin 1s ease-in-out infinite;
      margin-bottom: 15px;
    }

    .modal-carga-texto {
      color: var(--text-dark);
      font-size: 1.05rem;
      font-weight: 600;
      margin: 10px 0 5px;
    }

    .modal-carga-subtexto {
      color: var(--text-light);
      font-size: 0.85rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .acordeon-cabecera { padding: .9rem 1rem; }
      .acordeon-cabecera h3 { font-size: .9rem; }
      .info-grid { grid-template-columns: 1fr; }
      .filtros-container { flex-direction: column; align-items: stretch; }
      .filtro-busqueda { min-width: 100%; }
      .selector-pagina { margin-left: 0; justify-content: center; width: 100%; }
      .paginacion { gap: 6px; }
      .boton-pagina { padding: 6px 12px; font-size: 13px; }
      .acciones { justify-content: stretch; }
      .btn { width: 100%; }
      .ayuda-busqueda {
        position: static;
        margin-top: 5px;
        display: inline-block;
      }
      .modal-content { width: 100%; }
      .scanner-input-group { flex-direction: column; }
      .btn-eliminar { width: auto; }
    }

    /* Notificaciones */
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }

    /* ====== SELECTOR DE PERSONAL (autocompletado) ====== */
    .personal-picker { position: relative; }

    .personal-lista {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1.5px solid var(--primary-blue);
      border-top: none;
      border-radius: 0 0 10px 10px;
      max-height: 260px;
      overflow-y: auto;
      z-index: 50;
      box-shadow: var(--shadow-medium);
      display: none;
    }

    .personal-lista.visible { display: block; }

    .personal-item {
      padding: 10px 14px;
      cursor: pointer;
      border-bottom: 1px solid var(--border-color);
      transition: background 0.15s ease;
    }

    .personal-item:last-child { border-bottom: none; }

    .personal-item:hover,
    .personal-item.activo { background: var(--light-blue); }

    .personal-item-nombre {
      font-weight: 700;
      color: var(--text-dark);
      font-size: 0.92rem;
    }

    .personal-item-meta {
      font-size: 0.78rem;
      color: var(--text-light);
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 2px;
    }

    .personal-item-vacio {
      padding: 14px;
      text-align: center;
      color: var(--text-light);
      font-size: 0.88rem;
    }

    .personal-item-nuevo {
      background: var(--light-blue);
      color: var(--secondary-blue-dark);
      font-weight: 700;
      text-align: center;
      position: sticky;
      bottom: 0;
      border-top: 1px solid var(--border-color);
    }

    .personal-ayuda {
      margin-top: 6px;
      font-size: 0.8rem;
      color: var(--text-light);
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .btn-link-personal {
      background: none;
      border: none;
      color: var(--secondary-blue);
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      font-size: 0.8rem;
      text-decoration: underline;
    }

    .btn-link-personal:hover { color: var(--secondary-blue-dark); }

    .personal-datos {
      background: var(--light-blue);
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 1.3rem;
      display: none;
    }

    .personal-datos.visible { display: block; }

    .personal-datos-cabecera {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .personal-datos-cabecera strong {
      color: var(--secondary-blue-dark);
      font-size: 0.92rem;
    }

    .personal-datos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 6px 16px;
      font-size: 0.84rem;
      color: var(--text-dark);
    }

    .personal-datos-grid span b { color: var(--secondary-blue); }

    .btn-quitar-persona {
      background: var(--btn-delete);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 5px 10px;
      font-size: 0.76rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
    }

    .btn-quitar-persona:hover { background: var(--btn-delete-hover); }

    /* Modal de nuevo personal, por encima de los otros modales */
    #modalNuevoPersonal { z-index: 15000; }

    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0 16px;
    }

    @media (max-width: 640px) {
      .form-grid-2 { grid-template-columns: 1fr; }
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
      <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
        <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
      </div>
      <h1>Activos de Préstamo</h1>
      <p>Consulta rápida de activos, detalles técnicos y disponibilidad. Sistema integrado con escáner.</p>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap; margin-bottom: 15px;">
      <button class="btn-entrada" onclick="abrirModalEntrada()">
        <i class="fas fa-box-open"></i> Nueva Boleta de Entrada
      </button>
      <button class="btn-prestamo" onclick="abrirModalPrestamo()">
        <i class="fas fa-clipboard-list"></i> Nueva Boleta de Préstamo
      </button>
    </div>

    <div id="filtros" class="filtros-container" style="display:none;">
      <div class="filtro-busqueda" id="filtro-busqueda-container">
        <input type="text" id="busqueda" placeholder="Buscar o escanear: nombre, código, serie, imei, id o accesorios...">
        <span class="badge-busqueda" id="badge-busqueda">🔍</span>
        <div class="ayuda-busqueda">
          💡 Use "campo:texto" ej: "id:42", "codigo:123", "imei:35", "disponible:si" — o escanee el código y presione Enter
        </div>
        <div class="sugerencias-container" id="sugerencias"></div>
      </div>

      <div class="filtro-orden">
        <select id="ordenar-por">
          <option value="codigo">📊 Ordenar por Código</option>
          <option value="nombre">📝 Ordenar por Nombre</option>
          <option value="disponible">✅ Ordenar por Disponible</option>
        </select>

        <select id="filtro-disponible">
          <option value="todos" selected>📋 Todos los estados</option>
          <option value="si">✅ Solo disponibles</option>
          <option value="no">⛔ No disponibles</option>
        </select>
      </div>

      <div class="selector-pagina">
        <span>Mostrar:</span>
        <select id="elementos-por-pagina">
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>

    <div id="activos-content">
      <div class="estado-carga">
        <div class="spinner"></div>
        <div>Cargando información desde Google Sheets…</div>
      </div>
    </div>
  </div>

  <!-- Modal de Entrada -->
  <div id="modalEntrada" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));">
        <i class="fas fa-box-open"></i>
        <h3>Nueva Boleta de Entrada</h3>
        <button class="modal-close-top" onclick="cerrarModalEntrada()" aria-label="Cerrar modal de entrada" title="Cerrar">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="modal-info" style="border-left-color: var(--primary-blue);">
          <i class="fas fa-info-circle" style="color: var(--primary-blue);"></i> Escanee los códigos de los artículos que regresan para marcarlos nuevamente como disponibles. Si indica un correo, la boleta también se enviará en PDF.
        </div>

        <div class="modal-form-group personal-picker">
          <label><i class="fas fa-user-check"></i> Nombre de quien recibe *</label>
          <input type="text" id="modalResponsableEntrada" placeholder="Escriba para buscar en la lista de Personal…" autocomplete="off"
                 oninput="onInputPersonal('entrada')"
                 onfocus="onInputPersonal('entrada')"
                 onkeydown="onKeyPersonal(event, 'entrada')">
          <div class="personal-lista" id="listaPersonalEntrada"></div>
          <div class="personal-ayuda">
            <i class="fas fa-address-book"></i>
            <span id="estadoPersonalEntrada">Buscando personal…</span>
            <button type="button" class="btn-link-personal" onclick="abrirModalNuevoPersonal('entrada')">
              Registrar nuevo personal
            </button>
          </div>
        </div>

        <div class="personal-datos" id="datosPersonalEntrada"></div>

        <div class="modal-form-group">
          <label><i class="fas fa-envelope"></i> Correo electrónico (opcional)</label>
          <input type="email" id="modalEmailEntrada" placeholder="se toma de la hoja Personal" autocomplete="off" readonly
                 title="El correo se toma de la hoja Personal segun la persona indicada arriba">
        </div>

        <div class="scanner-section" style="border-color: var(--primary-blue); background: linear-gradient(135deg, var(--light-blue), white);">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <i class="fas fa-barcode" style="font-size: 1.5rem; color: var(--secondary-blue);"></i>
            <h4 style="color: var(--secondary-blue-dark); margin: 0;">Escanear artículos de entrada</h4>
          </div>

          <div class="scanner-input-group">
            <input type="text" id="scannerInputEntrada" placeholder="Escanee o ingrese código del artículo..." autocomplete="off" style="border-color: var(--primary-blue);">
            <button class="btn-scanner" onclick="agregarArticuloEntradaPorCodigo()" style="background: var(--secondary-blue);">
              <i class="fas fa-barcode"></i> Agregar
            </button>
          </div>
          <div id="scannerMessageEntrada" class="scanner-message"></div>
          <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-dark);">
            <i class="fas fa-keyboard"></i> También puede ingresar el código manualmente y presionar Enter
          </div>
        </div>

        <div class="modal-form-group">
          <label><i class="fas fa-box"></i> Artículos ingresados (<span id="totalArticulosEntradaModal">0</span>)</label>
          <div id="modalArticulosEntradaList" class="articulos-lista">
            <div style="text-align: center; color: var(--text-dark); padding: 2rem;">
              No hay artículos agregados. Escanee o ingrese códigos.
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="modal-btn modal-btn-secondary" onclick="cerrarModalEntrada()">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button class="modal-btn modal-btn-primary" onclick="generarBoletaEntrada()" style="background: var(--secondary-blue);">
            <i class="fas fa-file-pdf"></i> Generar Boleta
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Préstamo -->
  <div id="modalPrestamo" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <i class="fas fa-clipboard-list"></i>
        <h3>Nueva Boleta de Préstamo</h3>
        <button class="modal-close-top" onclick="cerrarModalPrestamo()" aria-label="Cerrar modal de préstamo" title="Cerrar">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="modal-info">
          <i class="fas fa-info-circle"></i> Escanee los códigos de los artículos para agregarlos al préstamo. Si indica un correo, la boleta también se enviará en PDF.
        </div>

        <div class="modal-form-group personal-picker">
          <label><i class="fas fa-user"></i> Nombre de quien retira *</label>
          <input type="text" id="modalResponsable" placeholder="Escriba para buscar en la lista de Personal…" autocomplete="off"
                 oninput="onInputPersonal('prestamo')"
                 onfocus="onInputPersonal('prestamo')"
                 onkeydown="onKeyPersonal(event, 'prestamo')">
          <div class="personal-lista" id="listaPersonalPrestamo"></div>
          <div class="personal-ayuda">
            <i class="fas fa-address-book"></i>
            <span id="estadoPersonalPrestamo">Buscando personal…</span>
            <button type="button" class="btn-link-personal" onclick="abrirModalNuevoPersonal('prestamo')">
              Registrar nuevo personal
            </button>
          </div>
        </div>

        <div class="personal-datos" id="datosPersonalPrestamo"></div>

        <div class="modal-form-group">
          <label><i class="fas fa-envelope"></i> Correo electrónico (opcional)</label>
          <input type="email" id="modalEmail" placeholder="se toma de la hoja Personal" autocomplete="off" readonly
                 title="El correo se toma de la hoja Personal segun la persona indicada arriba">
        </div>

        <!-- Sección de escáner -->
        <div class="scanner-section">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <i class="fas fa-barcode" style="font-size: 1.5rem; color: var(--secondary-blue);"></i>
            <h4 style="color: var(--secondary-blue-dark); margin: 0;">Escanear artículos</h4>
          </div>
          
          <div class="scanner-input-group">
            <input type="text" id="scannerInput" placeholder="Escanee o ingrese código del artículo..." autocomplete="off">
            <button class="btn-scanner" onclick="agregarArticuloPorCodigo()">
              <i class="fas fa-barcode"></i> Agregar
            </button>
          </div>
          <div id="scannerMessage" class="scanner-message"></div>
          <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-dark);">
            <i class="fas fa-keyboard"></i> También puede ingresar el código manualmente y presionar Enter
          </div>
        </div>

        <div class="modal-form-group">
          <label><i class="fas fa-box"></i> Artículos agregados (<span id="totalArticulosModal">0</span>)</label>
          <div id="modalArticulosList" class="articulos-lista">
            <!-- Se llenará dinámicamente -->
            <div style="text-align: center; color: var(--text-dark); padding: 2rem;">
              No hay artículos agregados. Escanee o ingrese códigos.
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="modal-btn modal-btn-secondary" onclick="cerrarModalPrestamo()">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button class="modal-btn modal-btn-primary" onclick="generarBoletaPrestamo()">
            <i class="fas fa-file-pdf"></i> Generar Boleta
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para registrar nuevo personal en la hoja "Personal" -->
  <div id="modalNuevoPersonal" class="modal-overlay">
    <div class="modal-content" style="max-width: 640px;">
      <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));">
        <i class="fas fa-user-plus"></i>
        <h3>Registrar nuevo personal</h3>
        <button class="modal-close-top" onclick="cerrarModalNuevoPersonal()" aria-label="Cerrar modal de nuevo personal" title="Cerrar">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="modal-info" style="border-left-color: var(--primary-blue);">
          <i class="fas fa-info-circle" style="color: var(--primary-blue);"></i>
          La persona se guardará en la hoja <strong>Personal</strong> del Google Sheets y quedará disponible para futuras boletas.
        </div>

        <div class="modal-form-group">
          <label><i class="fas fa-id-badge"></i> Nombre completo *</label>
          <input type="text" id="nuevoPersonalNombre" placeholder="NOMBRE APELLIDO APELLIDO" autocomplete="off">
        </div>

        <div class="modal-form-group">
          <label><i class="fas fa-sitemap"></i> Área *</label>
          <input type="text" id="nuevoPersonalArea" list="listaAreasPersonal" placeholder="Ej: EQUIPO DE MATEMÁTICA" autocomplete="off">
          <datalist id="listaAreasPersonal"></datalist>
        </div>

        <div class="modal-form-group">
          <label><i class="fas fa-envelope"></i> Correo institucional *</label>
          <input type="email" id="nuevoPersonalCorreo" placeholder="persona@ucr.ac.cr" autocomplete="off">
        </div>

        <div class="form-grid-2">
          <div class="modal-form-group">
            <label><i class="fas fa-mobile-alt"></i> Teléfono 1</label>
            <input type="text" id="nuevoPersonalTelefono1" placeholder="8888-8888" autocomplete="off">
          </div>

          <div class="modal-form-group">
            <label><i class="fas fa-phone"></i> Teléfono 2 / Extensión</label>
            <input type="text" id="nuevoPersonalTelefono2" placeholder="1234" autocomplete="off">
          </div>
        </div>

        <div id="mensajeNuevoPersonal" class="scanner-message"></div>

        <div class="modal-actions">
          <button class="modal-btn modal-btn-secondary" onclick="cerrarModalNuevoPersonal()">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button class="modal-btn modal-btn-primary" id="btnGuardarNuevoPersonal" onclick="guardarNuevoPersonal()">
            <i class="fas fa-save"></i> Guardar personal
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Carga (aparece mientras se actualiza el Sheet) -->
  <div id="modalCarga">
    <div class="modal-carga-contenido">
      <div class="modal-carga-spinner"></div>
      <div class="modal-carga-texto">Actualizando disponibilidad...</div>
      <div class="modal-carga-subtexto">Por favor espere</div>
    </div>
  </div>

  <script>
    // ====== CONFIG ======
    //
    // MIGRADO A LA BASE DE DATOS INSTITUCIONAL.
    //
    // Antes acá había un Sheet leído a través de opensheet.elk.sh (con
    // opensheet.vercel.app de respaldo) y un Apps Script para escribir. Ahora
    // todo pasa por el endpoint propio.
    //
    // La hoja "Personal" desaparece: el personal sale de `personas`, el mismo
    // catálogo del Directorio de Funcionarios. Antes eran dos listas separadas
    // con las mismas personas cargadas dos veces, que se desincronizaban en
    // cuanto alguien cambiaba de correo.
    const APPS_SCRIPT_URL = '../api/activos.php';
    const CSRF = <?= json_encode(token_csrf()) ?>;

    // Se conservan como arreglos porque el resto del archivo recorre estas
    // listas probando un servicio tras otro. Ahora hay uno solo y siempre
    // responde, pero el bucle no estorba.
    const SHEET_SERVICES = [`${APPS_SCRIPT_URL}?accion=listar`];
    const PERSONAL_SERVICES = [`${APPS_SCRIPT_URL}?accion=listarPersonal`];

    // ====== STATE ======
    let serviceIndex = 0;
    let data = [];
    let filtradas = [];
    let ordenadas = [];
    let paginaActual = 1;
    let elementosPorPagina = 20;
    let terminoBusquedaActual = '';
    let timeoutBusqueda = null;
    let articulosPrestamoModal = [];
    let articulosEntradaModal = [];
    let autoRefreshTimer = null;
    let sincronizandoDatos = false;
    const AUTO_REFRESH_MS = 4000;

    // ====== ESTADO DEL PERSONAL ======
    let personal = [];
    let personalCargado = false;
    let cargandoPersonal = false;
    const personaSeleccionada = { prestamo: null, entrada: null };
    const resultadosPersonal = { prestamo: [], entrada: [] };
    const indiceResaltado = { prestamo: -1, entrada: -1 };
    let modalDestinoPersonal = 'prestamo';

    const REFS_PERSONAL = {
      prestamo: {
        input: 'modalResponsable',
        email: 'modalEmail',
        lista: 'listaPersonalPrestamo',
        datos: 'datosPersonalPrestamo',
        estado: 'estadoPersonalPrestamo'
      },
      entrada: {
        input: 'modalResponsableEntrada',
        email: 'modalEmailEntrada',
        lista: 'listaPersonalEntrada',
        datos: 'datosPersonalEntrada',
        estado: 'estadoPersonalEntrada'
      }
    };

    // ====== HELPERS ======
    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
    
    function norm(v) { return (v ?? '').toString().trim(); }
    
    function normUpper(v) { return norm(v).toUpperCase(); }
    
    function esSI(v) { return normUpper(v) === 'SI' || normUpper(v) === 'SÍ'; }

    function validarEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(norm(email));
    }

    function escapeHTML(str) {
      return (str ?? '').toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", "&#039;");
    }

    function resaltarTexto(texto, termino) {
      if (!termino || !texto) return escapeHTML(texto);
      
      const textoEscapado = escapeHTML(texto.toString());
      const terminos = termino.toLowerCase().split(/\s+/).filter(t => t.length > 1);
      
      if (terminos.length === 0) return textoEscapado;
      
      let resultado = textoEscapado;
      terminos.forEach(term => {
        const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        resultado = resultado.replace(regex, '<span class="highlight">$1</span>');
      });
      
      return resultado;
    }

    function escapeForCopy(str) {
      return (str ?? '').toString().replace(/\s+\n/g, '\n').trim();
    }

    async function copiarTexto(texto) {
      try {
        await navigator.clipboard.writeText(texto);
        mostrarNotificacion('✅ Copiado al portapapeles', 'success');
      } catch (e) {
        const ta = document.createElement('textarea');
        ta.value = texto;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        mostrarNotificacion('✅ Copiado al portapapeles', 'success');
      }
    }

    function mostrarNotificacion(mensaje, tipo = 'success') {
      const colores = {
        success: 'var(--secondary-blue)',
        error: 'var(--bad)',
        warning: 'var(--warn)',
        info: 'var(--primary-blue)'
      };

      let contenedor = document.getElementById('notificaciones-container');
      if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'notificaciones-container';
        contenedor.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          z-index: 9999;
          display: flex;
          flex-direction: column;
          gap: 10px;
          align-items: flex-end;
          pointer-events: none;
        `;
        document.body.appendChild(contenedor);
      }

      const notificacion = document.createElement('div');
      notificacion.style.cssText = `
        position: relative;
        background: ${colores[tipo] || colores.success};
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        box-shadow: var(--shadow-medium);
        animation: slideIn 0.3s ease;
        font-weight: 600;
        max-width: min(420px, 90vw);
        pointer-events: auto;
      `;
      notificacion.textContent = mensaje;
      contenedor.appendChild(notificacion);
      
      setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notificacion.remove(), 300);
      }, 3000);
    }

    async function fetchConTimeout(url, ms = 8000) {
      const controller = new AbortController();
      const t = setTimeout(() => controller.abort(), ms);
      try {
        return await fetch(url, { signal: controller.signal, cache: "no-store" });
      } finally {
        clearTimeout(t);
      }
    }

    // ====== SINCRONIZACIÓN AUTOMÁTICA CON SHEETS ======
    async function recargarDatosSilencioso(notificarCambios = false) {
      if (sincronizandoDatos) return;
      sincronizandoDatos = true;

      try {
        let nuevosDatos = null;

        for (let i = 0; i < SHEET_SERVICES.length; i++) {
          const response = await fetchConTimeout(SHEET_SERVICES[i], 8000);
          if (!response.ok) continue;

          // El endpoint responde {success, activos:[…]}; opensheet devolvía
          // el arreglo pelado. Se aceptan las dos formas.
          const json = await response.json();
          const rows = json.activos || json;
          const convertidos = convertirDatos(rows);
          if (convertidos.length > 0) {
            nuevosDatos = convertidos;
            break;
          }
        }

        if (!nuevosDatos) return;

        const mapaAnterior = new Map(data.map(a => [a.Codigo, a.Disponible]));
        let cambiosDisponibilidad = 0;

        nuevosDatos.forEach(a => {
          const anterior = mapaAnterior.get(a.Codigo);
          if (anterior && normUpper(anterior) !== normUpper(a.Disponible)) {
            cambiosDisponibilidad++;
          }
        });

        data = nuevosDatos;

        const termino = document.getElementById('busqueda')?.value || terminoBusquedaActual;
        filtrar(termino);

        const totalPaginas = Math.max(1, Math.ceil(ordenadas.length / elementosPorPagina));
        if (paginaActual > totalPaginas) {
          paginaActual = totalPaginas;
          render();
        }

        if (notificarCambios && cambiosDisponibilidad > 0) {
          mostrarNotificacion(`🔄 Se sincronizó disponibilidad (${cambiosDisponibilidad} cambio(s))`, 'info');
        }
      } catch (error) {
        console.warn('Sincronización automática omitida:', error);
      } finally {
        sincronizandoDatos = false;
      }
    }

    function iniciarAutoRefresh() {
      if (autoRefreshTimer) clearInterval(autoRefreshTimer);
      autoRefreshTimer = setInterval(() => {
        recargarDatosSilencioso(false);
      }, AUTO_REFRESH_MS);
    }

    // ====== FUNCIÓN PARA MOSTRAR/OCULTAR MODAL DE CARGA ======
    function mostrarModalCarga(mensaje = 'Actualizando disponibilidad...', subMensaje = 'Por favor espere') {
      const modal = document.getElementById('modalCarga');
      const texto = modal.querySelector('.modal-carga-texto');
      const subtexto = modal.querySelector('.modal-carga-subtexto');
      texto.textContent = mensaje;
      subtexto.textContent = subMensaje;
      modal.style.display = 'flex';
    }

    function ocultarModalCarga() {
      const modal = document.getElementById('modalCarga');
      modal.style.display = 'none';
    }

    function textoError(err) {
      if (!err) return 'Error desconocido';
      if (typeof err === 'string') return err;
      if (err.message) return err.message;
      try { return JSON.stringify(err); } catch (_) { return String(err); }
    }

    // ====== FUNCIÓN PARA ACTUALIZAR GOOGLE SHEETS (MÉTODO SIMPLE Y CONFIABLE) ======
    async function actualizarDisponibilidadEnSheet(codigos, estado = 'NO') {
      try {
        mostrarModalCarga('Actualizando disponibilidad en Google Sheets...', 'Conectando con el servidor');

        const estadoFinal = norm(estado) || 'NO';

        const params = new URLSearchParams({
          action: 'actualizarDisponibilidad',
          codigos: JSON.stringify(codigos),
          estado: normUpper(estadoFinal) === 'SI' ? 'SI' : estadoFinal,
          csrf: CSRF
        });

        // Intento principal: POST (más estable para payloads largos)
        try {
          const response = await fetch(APPS_SCRIPT_URL, {
            method: 'POST',
            mode: 'cors',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: params.toString()
          });

          if (!response.ok) {
            const texto = await response.text().catch(() => '');
            throw new Error(`HTTP ${response.status}${texto ? `: ${texto}` : ''}`);
          }

          const result = await response.json();
          ocultarModalCarga();

          if (result?.success) {
            mostrarNotificacion(`✅ ${result.message || 'Disponibilidad actualizada en Sheets'}`, 'success');
            return result;
          }

          throw new Error(result?.error || 'Apps Script devolvió error');
        } catch (postError) {
          // Fallback 1: GET en CORS para intentar leer un error real del Apps Script
          try {
            const url = `${APPS_SCRIPT_URL}?${params.toString()}`;
            const getResponse = await fetch(url, { method: 'GET', mode: 'cors', cache: 'no-store' });

            if (!getResponse.ok) {
              const texto = await getResponse.text().catch(() => '');
              throw new Error(`GET HTTP ${getResponse.status}${texto ? `: ${texto}` : ''}`);
            }

            const getResult = await getResponse.json();
            ocultarModalCarga();

            if (getResult?.success) {
              mostrarNotificacion(`✅ ${getResult.message || 'Disponibilidad actualizada en Sheets'}`, 'success');
              return getResult;
            }

            throw new Error(getResult?.error || 'Apps Script devolvió error en GET');
          } catch (getError) {
            // Fallback 2: envío ciego (no-cors). En WordPress/preview suele actualizar pero no deja leer respuesta.
            try {
              const url = `${APPS_SCRIPT_URL}?${params.toString()}`;
              await fetch(url, { method: 'GET', mode: 'no-cors', cache: 'no-store' });
            } catch (_) {}

            ocultarModalCarga();
            const msg = `POST: ${textoError(postError)} | GET: ${textoError(getError)}`;
            console.warn('Actualización enviada en modo compatibilidad (sin confirmación por CORS):', {
              detalle: msg,
              estado: estadoFinal,
              codigos
            });

            mostrarNotificacion('ℹ️ Solicitud enviada a Sheets (compatibilidad CORS). Sincronizando...', 'info');

            setTimeout(() => {
              recargarDatosSilencioso(true);
            }, 2200);

            return { success: true, fallback: true, unconfirmed: true, message: 'Solicitud enviada en modo compatibilidad' };
          }
        }
      } catch (error) {
        console.error('Error al actualizar Sheet:', error);
        ocultarModalCarga();
        mostrarNotificacion('⚠️ No se pudo actualizar la disponibilidad en Sheets', 'error');
        return { success: false, error: error.message };
      }
    }

    function construirMensajeCorreoPorTipo(tipo) {
      const esDevolucion = normLower(tipo) === 'entrada' || normLower(tipo) === 'devolucion';
      const lineaTramite = esDevolucion
        ? 'Tipo de trámite: Devolución de activo(s).'
        : 'Tipo de trámite: Préstamo de activo(s).';

      const texto = [
        'Estimado(a):',
        '',
        'Se adjunta la boleta correspondiente en formato PDF.',
        '',
        lineaTramite,
        '',
        'Para cualquier duda o consulta, puede comunicarse con el Departamento de Informática al 6294.',
        '',
        'Este mensaje ha sido generado automáticamente por el Sistema de Activos del Programa Permanente de la Prueba de Aptitud Académica.'
      ].join('\n');

      const html = `
        <div style="font-family:Segoe UI, Arial, sans-serif; color:#222; line-height:1.5;">
          <p>Estimado(a):</p>
          <p>Se adjunta la boleta correspondiente en formato PDF.</p>
          <p>${escapeHTML(lineaTramite)}</p>
          <p>Para cualquier duda o consulta, puede comunicarse con el Departamento de Informática al 6294.</p>
          <p style="margin-top:18px; color:#555;">Este mensaje ha sido generado automáticamente por el Sistema de Activos del Programa Permanente de la Prueba de Aptitud Académica.</p>
        </div>
      `;

      return { texto, html };
    }

    // Los datos vienen de un Sheet que edita gente: se escapan antes de
    // insertarlos como HTML para que un valor con < o " no rompa la página.
    function escaparHtml(texto) {
      return String(texto == null ? '' : texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function normLower(v) {
      return norm(v).toLowerCase();
    }

    // Se manda el NOMBRE de la persona, no el correo: el Apps Script resuelve
    // la dirección contra la hoja Personal. Si aceptara una dirección
    // arbitraria sería un relay de correo abierto (con PDF adjunto), porque
    // esta página es pública y su URL está a la vista de cualquiera.
    async function enviarBoletaPorCorreo({ persona, asunto, nombreArchivo, html, tipo }) {
      const destinatario = norm(persona);
      if (!destinatario) return { success: false, skipped: true };

      try {
        mostrarModalCarga('Enviando boleta por correo...', destinatario);

        const mensajeCorreo = construirMensajeCorreoPorTipo(tipo || 'boleta');

        const params = new URLSearchParams({
          action: 'enviarBoletaPdf',
          persona: destinatario,
          asunto: asunto || 'Boleta de equipo',
          nombreArchivo: nombreArchivo || 'boleta.pdf',
          tipo: tipo || 'boleta',
          html: html || '',
          mensajeTexto: mensajeCorreo.texto,
          mensajeHtml: mensajeCorreo.html,
          csrf: CSRF
        });

        const response = await fetch(APPS_SCRIPT_URL, {
          method: 'POST',
          mode: 'cors',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
          },
          body: params.toString()
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const result = await response.json();
        if (!result?.success) throw new Error(result?.error || 'No se pudo enviar el correo');

        mostrarNotificacion(`📧 Boleta enviada a ${result.email || destinatario}`, 'success');
        return result;
      } catch (error) {
        console.error('Error al enviar boleta por correo:', error);
        // El motivo importa: ahora el destinatario se resuelve contra la hoja
        // Personal, así que la causa más común es un nombre escrito a mano que
        // no está en la hoja. Sin el detalle, quien usa la página no tiene cómo
        // saber que le basta con corregir el nombre o agregar a la persona.
        mostrarNotificacion(
          `⚠️ La boleta se generó, pero no se pudo enviar por correo: ${error.message}`,
          'warning'
        );
        return { success: false, error: error.message };
      } finally {
        ocultarModalCarga();
      }
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
        s.onload = () => {
          s.dataset.loaded = 'true';
          resolve();
        };
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

    async function descargarBoletaPdfLocal({ tipo, folio, htmlContenido }) {
      try {
        const jsPDFCtor = await cargarLibreriasPdf();
        if (!jsPDFCtor || typeof window.html2canvas !== 'function') {
          throw new Error('Librerías PDF no disponibles');
        }

        const contenedor = document.createElement('div');
        contenedor.style.cssText = `
          position: fixed;
          left: -100000px;
          top: 0;
          width: 21.59cm;
          min-height: 27.94cm;
          background: white;
          margin: 0;
          padding: 0;
          z-index: -1;
          overflow: hidden;
        `;
        contenedor.innerHTML = htmlContenido;
        document.body.appendChild(contenedor);

        await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));

        const canvas = await window.html2canvas(contenedor, {
          backgroundColor: '#ffffff',
          scale: 2,
          useCORS: true,
          logging: false
        });

        document.body.removeChild(contenedor);

        const doc = new jsPDFCtor({ orientation: 'p', unit: 'mm', format: 'letter' });
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const imgData = canvas.toDataURL('image/png');
        const imgWidth = pageWidth;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        doc.addImage(imgData, 'PNG', 0, 0, imgWidth, Math.min(imgHeight, pageHeight), undefined, 'FAST');

        const nombre = tipo === 'entrada'
          ? `Boleta_Entrada_${folio}.pdf`
          : `Boleta_Prestamo_${folio}.pdf`;

        doc.save(nombre);
        return { success: true };
      } catch (error) {
        console.error('Error al descargar PDF local:', error);
        return { success: false, error: error.message };
      }
    }

    // ====== BÚSQUEDA MEJORADA ======
    function buscarAvanzado(termino, activo) {
      if (!termino) return true;
      
      termino = termino.toLowerCase().trim();
      
      // Búsqueda por campos específicos (formato "campo:texto")
      if (termino.includes(':')) {
        const partes = termino.split(/\s+/);
        return partes.every(parte => {
          if (!parte.includes(':')) {
            return buscarEnTodosLosCampos(parte, activo);
          }
          
          const [campo, valor] = parte.split(':');
          const campoLower = campo.toLowerCase().trim();
          const valorLower = (valor || '').toLowerCase().trim();
          
          if (!valorLower) return true;
          
          switch(campoLower) {
            case 'id':
              return activo.Id.toLowerCase().includes(valorLower);
            case 'codigo':
            case 'cod':
              return activo.Codigo.toLowerCase().includes(valorLower);
            case 'nombre':
            case 'nom':
              return activo.Nombre.toLowerCase().includes(valorLower);
            case 'serie':
            case 'placa':
              return activo.SeriePlaca.toLowerCase().includes(valorLower);
            case 'imei':
              return activo.IMEI.toLowerCase().includes(valorLower);
            case 'accesorios':
            case 'acc':
              return activo.Accesorios.toLowerCase().includes(valorLower);
            case 'estado':
            case 'obs':
              return activo.EstadoObs.toLowerCase().includes(valorLower);
            case 'disponible':
            case 'disp':
              const disponible = esSI(activo.Disponible);
              return (valorLower === 'si' && disponible) || (valorLower === 'no' && !disponible);
            default:
              return buscarEnTodosLosCampos(parte, activo);
          }
        });
      }
      
      // Búsqueda general con múltiples términos
      const terminos = termino.split(/\s+/).filter(t => t.length > 0);
      return terminos.every(t => buscarEnTodosLosCampos(t, activo));
    }

    function buscarEnTodosLosCampos(termino, activo) {
      const t = termino.toLowerCase();
      return (
        activo.Id.toLowerCase().includes(t) ||
        activo.Codigo.toLowerCase().includes(t) ||
        activo.Nombre.toLowerCase().includes(t) ||
        activo.SeriePlaca.toLowerCase().includes(t) ||
        activo.IMEI.toLowerCase().includes(t) ||
        activo.Accesorios.toLowerCase().includes(t) ||
        activo.EstadoObs.toLowerCase().includes(t)
      );
    }

    // Generar sugerencias basadas en la búsqueda actual
    function generarSugerencias(termino) {
      if (!termino || termino.length < 2 || !data.length) return [];
      
      const terminoLower = termino.toLowerCase();
      const sugerencias = [];
      const campos = [
        { nombre: 'Id', campo: 'Id', icono: '🆔' },
        { nombre: 'Código', campo: 'Codigo', icono: '🔢' },
        { nombre: 'Nombre', campo: 'Nombre', icono: '📝' },
        { nombre: 'Serie/Placa', campo: 'SeriePlaca', icono: '🔖' },
        { nombre: 'IMEI', campo: 'IMEI', icono: '📱' },
        { nombre: 'Accesorios', campo: 'Accesorios', icono: '🔌' }
      ];
      
      const vistas = new Set();
      
      data.forEach(activo => {
        campos.forEach(({ nombre, campo, icono }) => {
          const valor = activo[campo];
          if (valor && valor.toLowerCase().includes(terminoLower)) {
            const clave = `${campo}:${valor}`;
            if (!vistas.has(clave) && sugerencias.length < 10) {
              vistas.add(clave);
              sugerencias.push({
                tipo: 'dato',
                campo: nombre,
                icono: icono,
                valor: valor,
                texto: `${icono} ${nombre}: ${valor}`,
                busqueda: `${campo.toLowerCase()}:${valor}`
              });
            }
          }
        });
      });
      
      if (terminoLower.startsWith('dis')) {
        sugerencias.unshift({
          tipo: 'comando',
          texto: '✅ disponible:si',
          busqueda: 'disponible:si'
        }, {
          tipo: 'comando',
          texto: '⛔ disponible:no',
          busqueda: 'disponible:no'
        });
      }
      
      return sugerencias.slice(0, 8);
    }

    function mostrarSugerencias(sugerencias) {
      const container = document.getElementById('sugerencias');
      const input = document.getElementById('busqueda');
      
      if (!sugerencias.length) {
        container.classList.remove('visible');
        return;
      }
      
      container.innerHTML = sugerencias.map(s => `
        <div class="sugerencia-item" onclick="aplicarSugerencia('${s.busqueda.replace(/'/g, "\\'")}')">
          ${escaparHtml(s.texto)}
          ${s.tipo === 'comando' ? '<span class="tipo">comando</span>' : ''}
        </div>
      `).join('');
      
      container.classList.add('visible');
      
      setTimeout(() => {
        document.addEventListener('click', function cerrar(e) {
          if (!container.contains(e.target) && e.target !== input) {
            container.classList.remove('visible');
            document.removeEventListener('click', cerrar);
          }
        });
      }, 100);
    }

    window.aplicarSugerencia = function(busqueda) {
      const input = document.getElementById('busqueda');
      input.value = busqueda;
      document.getElementById('sugerencias').classList.remove('visible');
      filtrar(busqueda);
    };

    function limpiarBusqueda() {
      const input = document.getElementById('busqueda');
      input.value = '';
      document.getElementById('filtro-busqueda-container').classList.remove('limpiable', 'con-filtro');
      filtrar('');
      input.focus();
    }

    // ====== MÓDULO DE PERSONAL (hoja "Personal") ======
    const RE_DIACRITICOS = new RegExp('[\\u0300-\\u036f]', 'g');

    function sinAcentos(valor) {
      return norm(valor).normalize('NFD').replace(RE_DIACRITICOS, '');
    }

    function claveNormalizada(valor) {
      return sinAcentos(valor).toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    function convertirPersonal(rows) {
      if (!Array.isArray(rows)) return [];

      return rows.map(fila => {
        const mapa = {};
        Object.keys(fila || {}).forEach(k => { mapa[claveNormalizada(k)] = fila[k]; });

        const tomar = (...claves) => {
          for (const clave of claves) {
            if (mapa[clave] !== undefined && norm(mapa[clave])) return norm(mapa[clave]);
          }
          return '';
        };

        return {
          Area: tomar('area', 'areas', 'departamento', 'unidad'),
          Persona: tomar('persona', 'nombre', 'nombrecompleto', 'funcionario'),
          Correo: tomar('correo', 'email', 'correoelectronico'),
          Telefono1: tomar('telefono1', 'telefono', 'celular', 'tel1'),
          Telefono2: tomar('telefono2', 'extension', 'ext', 'tel2')
        };
      }).filter(p => p.Persona);
    }

    async function cargarPersonal(silencioso = true) {
      if (cargandoPersonal) return personal;
      cargandoPersonal = true;

      try {
        let lista = null;

        // 1) Lectura pública (rápida) por opensheet
        for (const servicio of PERSONAL_SERVICES) {
          try {
            const response = await fetchConTimeout(servicio, 8000);
            if (!response.ok) continue;
            const json = await response.json();
            const rows = json.personal || json;
            const convertidos = convertirPersonal(rows);
            if (convertidos.length > 0) { lista = convertidos; break; }
          } catch (_) { /* siguiente servicio */ }
        }

        // 2) Respaldo: pedir la lista directamente al Apps Script
        if (!lista) {
          try {
            const url = `${APPS_SCRIPT_URL}?action=listarPersonal`;
            const response = await fetchConTimeout(url, 10000);
            if (response.ok) {
              const result = await response.json();
              if (result?.success) lista = convertirPersonal(result.personal || []);
            }
          } catch (_) { /* sin respaldo */ }
        }

        if (lista) {
          lista.sort((a, b) => a.Persona.localeCompare(b.Persona, 'es'));
          personal = lista;
          personalCargado = true;
          actualizarDatalistAreas();
        }

        actualizarEstadoPersonal();

        if (!silencioso) {
          if (personalCargado) {
            mostrarNotificacion(`👥 ${personal.length} personas cargadas desde la hoja Personal`, 'info');
          } else {
            mostrarNotificacion('⚠️ No se pudo leer la hoja "Personal"', 'warning');
          }
        }

        return personal;
      } finally {
        cargandoPersonal = false;
      }
    }

    function actualizarEstadoPersonal() {
      Object.values(REFS_PERSONAL).forEach(refs => {
        const el = document.getElementById(refs.estado);
        if (!el) return;
        el.textContent = personalCargado
          ? `${personal.length} personas disponibles ·`
          : 'No se pudo cargar la hoja Personal ·';
      });
    }

    function actualizarDatalistAreas() {
      const datalist = document.getElementById('listaAreasPersonal');
      if (!datalist) return;

      const areas = [...new Set(personal.map(p => p.Area).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'es'));

      datalist.innerHTML = areas.map(a => `<option value="${escapeHTML(a)}"></option>`).join('');
    }

    function buscarPersonal(termino) {
      const t = claveNormalizada(termino);
      if (!t) return personal.slice(0, 50);

      return personal
        .filter(p =>
          claveNormalizada(p.Persona).includes(t) ||
          claveNormalizada(p.Area).includes(t) ||
          claveNormalizada(p.Correo).includes(t)
        )
        .slice(0, 50);
    }

    function onInputPersonal(tipo) {
      const refs = REFS_PERSONAL[tipo];
      const input = document.getElementById(refs.input);
      if (!input) return;

      const termino = input.value;
      const seleccion = personaSeleccionada[tipo];

      // Si el texto ya no coincide con la persona elegida, se suelta la selección
      if (seleccion && claveNormalizada(seleccion.Persona) !== claveNormalizada(termino)) {
        personaSeleccionada[tipo] = null;
        mostrarDatosPersona(tipo);
      }

      resultadosPersonal[tipo] = buscarPersonal(termino);
      indiceResaltado[tipo] = -1;
      renderListaPersonal(tipo);
    }

    function renderListaPersonal(tipo) {
      const refs = REFS_PERSONAL[tipo];
      const contenedor = document.getElementById(refs.lista);
      if (!contenedor) return;

      const items = resultadosPersonal[tipo] || [];
      let html = '';

      if (!personalCargado) {
        html += `<div class="personal-item-vacio">No se pudo cargar la hoja "Personal". Puede escribir el nombre manualmente.</div>`;
      } else if (items.length === 0) {
        html += `<div class="personal-item-vacio">Sin coincidencias en la lista de personal.</div>`;
      } else {
        items.forEach((p, i) => {
          const meta = [p.Area, p.Correo].filter(Boolean).map(escapeHTML).join(' · ');
          html += `
            <div class="personal-item${indiceResaltado[tipo] === i ? ' activo' : ''}" onmousedown="event.preventDefault(); seleccionarPersonaPorIndice('${tipo}', ${i})">
              <div class="personal-item-nombre">${escapeHTML(p.Persona)}</div>
              <div class="personal-item-meta">${meta || '<i>Sin datos adicionales</i>'}</div>
            </div>
          `;
        });
      }

      html += `
        <div class="personal-item personal-item-nuevo" onmousedown="event.preventDefault(); abrirModalNuevoPersonal('${tipo}')">
          <i class="fas fa-user-plus"></i> Registrar nuevo personal
        </div>
      `;

      contenedor.innerHTML = html;
      contenedor.classList.add('visible');
    }

    function ocultarListaPersonal(tipo) {
      const contenedor = document.getElementById(REFS_PERSONAL[tipo].lista);
      if (contenedor) contenedor.classList.remove('visible');
      indiceResaltado[tipo] = -1;
    }

    function onKeyPersonal(event, tipo) {
      const contenedor = document.getElementById(REFS_PERSONAL[tipo].lista);
      const abierta = contenedor && contenedor.classList.contains('visible');
      const items = resultadosPersonal[tipo] || [];

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!abierta) { onInputPersonal(tipo); return; }
        if (items.length === 0) return;

        const paso = event.key === 'ArrowDown' ? 1 : -1;
        indiceResaltado[tipo] = (indiceResaltado[tipo] + paso + items.length) % items.length;
        renderListaPersonal(tipo);

        const activo = contenedor.querySelector('.personal-item.activo');
        if (activo) activo.scrollIntoView({ block: 'nearest' });
        return;
      }

      if (event.key === 'Enter') {
        if (abierta && indiceResaltado[tipo] >= 0) {
          event.preventDefault();
          seleccionarPersonaPorIndice(tipo, indiceResaltado[tipo]);
        }
        return;
      }

      if (event.key === 'Escape') {
        ocultarListaPersonal(tipo);
      }
    }

    function seleccionarPersonaPorIndice(tipo, indice) {
      const persona = (resultadosPersonal[tipo] || [])[indice];
      if (!persona) return;
      seleccionarPersona(tipo, persona);
    }

    function seleccionarPersona(tipo, persona) {
      const refs = REFS_PERSONAL[tipo];
      personaSeleccionada[tipo] = persona;

      const input = document.getElementById(refs.input);
      if (input) input.value = persona.Persona;

      const email = document.getElementById(refs.email);
      if (email && persona.Correo) email.value = persona.Correo;

      mostrarDatosPersona(tipo);
      ocultarListaPersonal(tipo);
    }

    function limpiarSeleccionPersona(tipo) {
      const refs = REFS_PERSONAL[tipo];
      personaSeleccionada[tipo] = null;

      const input = document.getElementById(refs.input);
      if (input) { input.value = ''; input.focus(); }

      const email = document.getElementById(refs.email);
      if (email) email.value = '';

      mostrarDatosPersona(tipo);
      ocultarListaPersonal(tipo);
    }

    function mostrarDatosPersona(tipo) {
      const contenedor = document.getElementById(REFS_PERSONAL[tipo].datos);
      if (!contenedor) return;

      const p = personaSeleccionada[tipo];
      if (!p) {
        contenedor.classList.remove('visible');
        contenedor.innerHTML = '';
        return;
      }

      contenedor.innerHTML = `
        <div class="personal-datos-cabecera">
          <strong><i class="fas fa-user-check"></i> ${escapeHTML(p.Persona)}</strong>
          <button type="button" class="btn-quitar-persona" onclick="limpiarSeleccionPersona('${tipo}')">
            <i class="fas fa-times"></i> Quitar
          </button>
        </div>
        <div class="personal-datos-grid">
          <span><b>Área:</b> ${escapeHTML(p.Area || 'N/A')}</span>
          <span><b>Correo:</b> ${escapeHTML(p.Correo || 'N/A')}</span>
          <span><b>Teléfono 1:</b> ${escapeHTML(p.Telefono1 || 'N/A')}</span>
          <span><b>Teléfono 2:</b> ${escapeHTML(p.Telefono2 || 'N/A')}</span>
        </div>
      `;
      contenedor.classList.add('visible');
    }

    function datosPersonaParaBoleta(tipo, correoFallback) {
      const p = personaSeleccionada[tipo];
      const telefonos = p
        ? [p.Telefono1, p.Telefono2].map(norm).filter(Boolean).join(' / ')
        : '';

      return {
        area: escapeHTML(norm(p?.Area) || 'N/A'),
        correo: escapeHTML(norm(p?.Correo) || norm(correoFallback) || 'N/A'),
        telefonos: escapeHTML(telefonos || 'N/A')
      };
    }

    // ====== ALTA DE NUEVO PERSONAL ======
    function abrirModalNuevoPersonal(tipo) {
      modalDestinoPersonal = tipo || 'prestamo';
      ocultarListaPersonal(modalDestinoPersonal);

      const refs = REFS_PERSONAL[modalDestinoPersonal];
      const escrito = norm(document.getElementById(refs.input)?.value);

      document.getElementById('nuevoPersonalNombre').value = escrito;
      document.getElementById('nuevoPersonalArea').value = '';
      document.getElementById('nuevoPersonalCorreo').value = '';
      document.getElementById('nuevoPersonalTelefono1').value = '';
      document.getElementById('nuevoPersonalTelefono2').value = '';

      const mensaje = document.getElementById('mensajeNuevoPersonal');
      mensaje.innerHTML = '';
      mensaje.className = 'scanner-message';

      actualizarDatalistAreas();
      document.getElementById('modalNuevoPersonal').style.display = 'flex';

      setTimeout(() => {
        const foco = escrito ? 'nuevoPersonalArea' : 'nuevoPersonalNombre';
        document.getElementById(foco).focus();
      }, 100);
    }

    function cerrarModalNuevoPersonal() {
      document.getElementById('modalNuevoPersonal').style.display = 'none';
    }

    function mostrarMensajeNuevoPersonal(texto, tipo) {
      const el = document.getElementById('mensajeNuevoPersonal');
      if (!el) return;
      el.textContent = texto;
      el.className = `scanner-message ${tipo}`;
    }

    async function guardarNuevoPersonal() {
      const nombre = norm(document.getElementById('nuevoPersonalNombre').value);
      const area = norm(document.getElementById('nuevoPersonalArea').value);
      const correo = norm(document.getElementById('nuevoPersonalCorreo').value);
      const telefono1 = norm(document.getElementById('nuevoPersonalTelefono1').value);
      const telefono2 = norm(document.getElementById('nuevoPersonalTelefono2').value);

      if (!nombre) {
        mostrarMensajeNuevoPersonal('Ingrese el nombre completo de la persona', 'error');
        document.getElementById('nuevoPersonalNombre').focus();
        return;
      }

      if (!area) {
        mostrarMensajeNuevoPersonal('Indique el área a la que pertenece', 'error');
        document.getElementById('nuevoPersonalArea').focus();
        return;
      }

      if (!correo || !validarEmail(correo)) {
        mostrarMensajeNuevoPersonal('Ingrese un correo electrónico válido', 'error');
        document.getElementById('nuevoPersonalCorreo').focus();
        return;
      }

      const duplicado = personal.find(p =>
        claveNormalizada(p.Persona) === claveNormalizada(nombre) ||
        (p.Correo && claveNormalizada(p.Correo) === claveNormalizada(correo))
      );

      if (duplicado) {
        mostrarMensajeNuevoPersonal(`Ya existe un registro para "${duplicado.Persona}"`, 'error');
        return;
      }

      const boton = document.getElementById('btnGuardarNuevoPersonal');
      boton.disabled = true;
      mostrarMensajeNuevoPersonal('Guardando en Google Sheets…', 'info');

      const resultado = await enviarPersonalAlSheet({
        area,
        persona: nombre,
        correo,
        telefono1,
        telefono2
      });

      boton.disabled = false;

      if (!resultado.success) {
        mostrarMensajeNuevoPersonal(resultado.error || 'No se pudo guardar el personal', 'error');
        return;
      }

      const nuevaPersona = {
        Area: area,
        Persona: nombre,
        Correo: correo,
        Telefono1: telefono1,
        Telefono2: telefono2
      };

      personal.push(nuevaPersona);
      personal.sort((a, b) => a.Persona.localeCompare(b.Persona, 'es'));
      personalCargado = true;
      actualizarDatalistAreas();
      actualizarEstadoPersonal();

      cerrarModalNuevoPersonal();
      seleccionarPersona(modalDestinoPersonal, nuevaPersona);

      mostrarNotificacion(
        resultado.unconfirmed
          ? 'ℹ️ Personal enviado a Sheets (sin confirmación por CORS)'
          : `✅ ${nombre} agregado a la hoja Personal`,
        resultado.unconfirmed ? 'info' : 'success'
      );

      // Refresca desde la hoja para confirmar el guardado
      setTimeout(() => cargarPersonal(true), 2500);
    }

    async function enviarPersonalAlSheet(persona) {
      const params = new URLSearchParams({
        action: 'agregarPersonal',
        area: persona.area,
        persona: persona.persona,
        correo: persona.correo,
        telefono1: persona.telefono1,
        telefono2: persona.telefono2,
        csrf: CSRF
      });

      // 1) POST normal
      try {
        const response = await fetch(APPS_SCRIPT_URL, {
          method: 'POST',
          mode: 'cors',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: params.toString()
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const result = await response.json();
        if (result?.success) return { success: true, persona: result.persona };
        throw new Error(result?.error || 'Apps Script devolvió error');
      } catch (postError) {
        // 2) GET con CORS
        try {
          const response = await fetch(`${APPS_SCRIPT_URL}?${params.toString()}`, {
            method: 'GET',
            mode: 'cors',
            cache: 'no-store'
          });

          if (!response.ok) throw new Error(`GET HTTP ${response.status}`);

          const result = await response.json();
          if (result?.success) return { success: true, persona: result.persona };
          return { success: false, error: result?.error || 'Apps Script devolvió error en GET' };
        } catch (getError) {
          // 3) Envío ciego (no-cors): no se puede leer la respuesta
          try {
            await fetch(`${APPS_SCRIPT_URL}?${params.toString()}`, {
              method: 'GET',
              mode: 'no-cors',
              cache: 'no-store'
            });
            console.warn('Alta de personal enviada en modo compatibilidad:', {
              post: textoError(postError),
              get: textoError(getError)
            });
            return { success: true, unconfirmed: true };
          } catch (blindError) {
            return { success: false, error: textoError(blindError) };
          }
        }
      }
    }

    // ====== FUNCIONES DEL MODAL ======
    function abrirModalPrestamo() {
      const modal = document.getElementById('modalPrestamo');
      
      articulosPrestamoModal = [];
      
      document.getElementById('modalResponsable').value = '';
      document.getElementById('modalEmail').value = '';
      document.getElementById('scannerInput').value = '';
      document.getElementById('scannerMessage').innerHTML = '';
      document.getElementById('scannerMessage').className = 'scanner-message';

      personaSeleccionada.prestamo = null;
      resultadosPersonal.prestamo = [];
      mostrarDatosPersona('prestamo');
      ocultarListaPersonal('prestamo');
      actualizarEstadoPersonal();
      if (!personalCargado) cargarPersonal(true);

      actualizarListaArticulosModal();

      modal.style.display = 'flex';
      
      setTimeout(() => {
        document.getElementById('scannerInput').focus();
      }, 100);
    }

    function abrirModalEntrada() {
      const modal = document.getElementById('modalEntrada');

      articulosEntradaModal = [];

      document.getElementById('modalResponsableEntrada').value = '';
      document.getElementById('modalEmailEntrada').value = '';
      document.getElementById('scannerInputEntrada').value = '';
      document.getElementById('scannerMessageEntrada').innerHTML = '';
      document.getElementById('scannerMessageEntrada').className = 'scanner-message';

      personaSeleccionada.entrada = null;
      resultadosPersonal.entrada = [];
      mostrarDatosPersona('entrada');
      ocultarListaPersonal('entrada');
      actualizarEstadoPersonal();
      if (!personalCargado) cargarPersonal(true);

      actualizarListaArticulosEntradaModal();

      modal.style.display = 'flex';

      setTimeout(() => {
        document.getElementById('scannerInputEntrada').focus();
      }, 100);
    }

    function cerrarModalPrestamo() {
      document.getElementById('modalPrestamo').style.display = 'none';
    }

    function cerrarModalEntrada() {
      document.getElementById('modalEntrada').style.display = 'none';
    }

    function mostrarMensajeScanner(mensaje, tipo) {
      const msgDiv = document.getElementById('scannerMessage');
      msgDiv.textContent = mensaje;
      msgDiv.className = `scanner-message ${tipo}`;
      
      setTimeout(() => {
        msgDiv.innerHTML = '';
        msgDiv.className = 'scanner-message';
      }, 3000);
    }

    function mostrarMensajeScannerEntrada(mensaje, tipo) {
      const msgDiv = document.getElementById('scannerMessageEntrada');
      msgDiv.textContent = mensaje;
      msgDiv.className = `scanner-message ${tipo}`;

      setTimeout(() => {
        msgDiv.innerHTML = '';
        msgDiv.className = 'scanner-message';
      }, 3000);
    }

    function agregarArticuloPorCodigo() {
      const codigo = document.getElementById('scannerInput').value.trim();
      if (!codigo) {
        mostrarMensajeScanner('Ingrese un código de artículo', 'error');
        return;
      }

      const articulo = data.find(a => 
        a.Codigo.toLowerCase() === codigo.toLowerCase() ||
        a.SeriePlaca.toLowerCase() === codigo.toLowerCase() ||
        a.Codigo.toLowerCase().includes(codigo.toLowerCase())
      );

      if (!articulo) {
        mostrarMensajeScanner(`❌ No se encontró artículo con código "${codigo}"`, 'error');
        document.getElementById('scannerInput').value = '';
        document.getElementById('scannerInput').focus();
        return;
      }

      if (!esSI(articulo.Disponible)) {
        mostrarMensajeScanner(`❌ El artículo "${escaparHtml(articulo.Nombre)}" no está disponible`, 'error');
        document.getElementById('scannerInput').value = '';
        document.getElementById('scannerInput').focus();
        return;
      }

      if (articulosPrestamoModal.some(a => a.Codigo === articulo.Codigo)) {
        mostrarMensajeScanner(`⚠️ "${escaparHtml(articulo.Nombre)}" ya está en la lista`, 'info');
        document.getElementById('scannerInput').value = '';
        document.getElementById('scannerInput').focus();
        return;
      }

      articulosPrestamoModal.push(articulo);
      actualizarListaArticulosModal();
      
      mostrarMensajeScanner(`✅ "${escaparHtml(articulo.Nombre)}" agregado`, 'success');
      document.getElementById('scannerInput').value = '';
      document.getElementById('scannerInput').focus();
    }

    function eliminarArticuloModal(codigo) {
      articulosPrestamoModal = articulosPrestamoModal.filter(a => a.Codigo !== codigo);
      actualizarListaArticulosModal();
      mostrarMensajeScanner('Artículo removido', 'info');
    }

    function agregarArticuloEntradaPorCodigo() {
      const codigo = document.getElementById('scannerInputEntrada').value.trim();
      if (!codigo) {
        mostrarMensajeScannerEntrada('Ingrese un código de artículo', 'error');
        return;
      }

      const articulo = data.find(a =>
        a.Codigo.toLowerCase() === codigo.toLowerCase() ||
        a.SeriePlaca.toLowerCase() === codigo.toLowerCase() ||
        a.Codigo.toLowerCase().includes(codigo.toLowerCase())
      );

      if (!articulo) {
        mostrarMensajeScannerEntrada(`❌ No se encontró artículo con código "${codigo}"`, 'error');
        document.getElementById('scannerInputEntrada').value = '';
        document.getElementById('scannerInputEntrada').focus();
        return;
      }

      if (articulosEntradaModal.some(a => a.Codigo === articulo.Codigo)) {
        mostrarMensajeScannerEntrada(`⚠️ "${escaparHtml(articulo.Nombre)}" ya está en la lista`, 'info');
        document.getElementById('scannerInputEntrada').value = '';
        document.getElementById('scannerInputEntrada').focus();
        return;
      }

      if (esSI(articulo.Disponible)) {
        mostrarMensajeScannerEntrada(`❌ "${escaparHtml(articulo.Nombre)}" ya está disponible y no requiere boleta de entrada`, 'error');
        document.getElementById('scannerInputEntrada').value = '';
        document.getElementById('scannerInputEntrada').focus();
        return;
      }

      articulosEntradaModal.push(articulo);
      actualizarListaArticulosEntradaModal();

      mostrarMensajeScannerEntrada(`✅ "${escaparHtml(articulo.Nombre)}" agregado para entrada`, 'success');

      document.getElementById('scannerInputEntrada').value = '';
      document.getElementById('scannerInputEntrada').focus();
    }

    function eliminarArticuloEntradaModal(codigo) {
      articulosEntradaModal = articulosEntradaModal.filter(a => a.Codigo !== codigo);
      actualizarListaArticulosEntradaModal();
      mostrarMensajeScannerEntrada('Artículo removido', 'info');
    }

    function actualizarListaArticulosModal() {
      const container = document.getElementById('modalArticulosList');
      document.getElementById('totalArticulosModal').textContent = articulosPrestamoModal.length;
      
      if (articulosPrestamoModal.length === 0) {
        container.innerHTML = `
          <div style="text-align: center; color: var(--text-dark); padding: 2rem;">
            <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5; color: var(--secondary-blue);"></i>
            <p>No hay artículos agregados</p>
            <p style="font-size: 0.9rem;">Escanee o ingrese códigos para agregarlos</p>
          </div>
        `;
        return;
      }

      let html = '';
      articulosPrestamoModal.forEach(articulo => {
        html += `
          <div class="articulo-item">
            <div class="articulo-info">
              <div class="articulo-nombre">
                ${escapeHTML(articulo.Nombre)}
              </div>
              <div class="articulo-detalle">
                <span><strong>Código:</strong> ${escapeHTML(articulo.Codigo)}</span>
                <span><strong>Serie:</strong> ${escapeHTML(articulo.SeriePlaca || 'N/A')}</span>
              </div>
            </div>
            <button class="btn-eliminar" onclick="eliminarArticuloModal('${escaparHtml(articulo.Codigo)}')">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        `;
      });

      container.innerHTML = html;
    }

    function actualizarListaArticulosEntradaModal() {
      const container = document.getElementById('modalArticulosEntradaList');
      document.getElementById('totalArticulosEntradaModal').textContent = articulosEntradaModal.length;

      if (articulosEntradaModal.length === 0) {
        container.innerHTML = `
          <div style="text-align: center; color: var(--text-dark); padding: 2rem;">
            <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5; color: var(--secondary-blue);"></i>
            <p>No hay artículos agregados</p>
            <p style="font-size: 0.9rem;">Escanee o ingrese códigos para agregarlos</p>
          </div>
        `;
        return;
      }

      let html = '';
      articulosEntradaModal.forEach(articulo => {
        html += `
          <div class="articulo-item">
            <div class="articulo-info">
              <div class="articulo-nombre">
                ${escapeHTML(articulo.Nombre)}
              </div>
              <div class="articulo-detalle">
                <span><strong>Código:</strong> ${escapeHTML(articulo.Codigo)}</span>
                <span><strong>Serie:</strong> ${escapeHTML(articulo.SeriePlaca || 'N/A')}</span>
                <span><strong>Estado:</strong> ${escapeHTML(articulo.Disponible || 'N/A')}</span>
              </div>
            </div>
            <button class="btn-eliminar" onclick="eliminarArticuloEntradaModal('${escaparHtml(articulo.Codigo)}')">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        `;
      });

      container.innerHTML = html;
    }

    async function generarBoletaPrestamo() {
      const responsable = document.getElementById('modalResponsable').value.trim();
      const emailDestino = document.getElementById('modalEmail').value.trim();
      if (!responsable) {
        alert('Por favor ingrese el nombre de quien retira');
        document.getElementById('modalResponsable').focus();
        return;
      }

      if (emailDestino && !validarEmail(emailDestino)) {
        alert('Ingrese un correo electrónico válido o deje el campo vacío');
        document.getElementById('modalEmail').focus();
        return;
      }

      if (articulosPrestamoModal.length === 0) {
        alert('Agregue al menos un artículo para prestar');
        return;
      }

      const noDisponibles = articulosPrestamoModal.filter(a => !esSI(a.Disponible));
      if (noDisponibles.length > 0) {
        alert(`Los siguientes artículos no están disponibles:\n${noDisponibles.map(a => a.Nombre).join('\n')}`);
        return;
      }

      const codigos = articulosPrestamoModal.map(a => a.Codigo);
      const estadoPrestamo = responsable;
      const win = window.open('', '_blank');

      if (!win) {
        alert('Por favor permita ventanas emergentes para generar la boleta');
        return;
      }

      win.document.write(`
        <html>
          <head><title>Generando boleta...</title></head>
          <body style="font-family: Segoe UI, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">
            <div>Generando boleta, por favor espere...</div>

          </body>
        </html>
      `);
      win.document.close();

      cerrarModalPrestamo();
      mostrarModalCarga('Actualizando disponibilidad en Google Sheets...', 'Conectando con el servidor');

      // Actualizar en Google Sheets
      const resultado = await actualizarDisponibilidadEnSheet(codigos, estadoPrestamo);
      
      // Actualizar datos locales SIEMPRE (incluso si la actualización remota falla)
      codigos.forEach(codigo => {
        const articulo = data.find(a => a.Codigo === codigo);
        if (articulo) {
          articulo.Disponible = estadoPrestamo;
        }
      });

      filtrar(document.getElementById('busqueda').value);
      
      ocultarModalCarga();

      // Generar la boleta
      const fechaRetiro = new Date().toLocaleDateString('es-ES');
      const horaRetiro = new Date().toLocaleTimeString('es-ES');
      
      const folio = `PAA-${Date.now().toString().slice(-6)}`;
      const fechaActual = new Date().toLocaleDateString('es-ES');
      const horaActual = new Date().toLocaleTimeString('es-ES');
      const responsableSeguro = escapeHTML(responsable);
      const datosPersona = datosPersonaParaBoleta('prestamo', emailDestino);

      const totalItems = articulosPrestamoModal.length;

      let fontSizeBase, fontSizeTable, fontSizeHeader, rowPadding, sectionPadding, pagePaddingCm, tableLineHeight, footerFontSize;

      if (totalItems <= 8) {
        fontSizeBase = 13.1;
        fontSizeTable = 11.6;
        fontSizeHeader = 18;
        rowPadding = 4;
        sectionPadding = 12;
        pagePaddingCm = 0.58;
        tableLineHeight = 1.22;
        footerFontSize = 8.4;
      } else if (totalItems <= 14) {
        fontSizeBase = 11.1;
        fontSizeTable = 9.6;
        fontSizeHeader = 14.8;
        rowPadding = 3;
        sectionPadding = 8;
        pagePaddingCm = 0.5;
        tableLineHeight = 1.16;
        footerFontSize = 8;
      } else if (totalItems <= 20) {
        fontSizeBase = 10.4;
        fontSizeTable = 8.55;
        fontSizeHeader = 13.3;
        rowPadding = 1;
        sectionPadding = 5;
        pagePaddingCm = 0.4;
        tableLineHeight = 1.06;
        footerFontSize = 7.8;
      } else {
        fontSizeBase = 8.8;
        fontSizeTable = 7.3;
        fontSizeHeader = 10.8;
        rowPadding = 1;
        sectionPadding = 4;
        pagePaddingCm = 0.35;
        tableLineHeight = 1.05;
        footerFontSize = 7.2;
      }

      const recortarTextoBoleta = (valor, maxLen) => {
        const limpio = norm(valor) || 'N/A';
        return limpio.length > maxLen ? `${limpio.slice(0, Math.max(1, maxLen - 1))}…` : limpio;
      };

      let filasArticulos = '';
      let contador = 1;
      articulosPrestamoModal.forEach(articulo => {
        const nombreImpresion = recortarTextoBoleta(articulo.Nombre, totalItems > 14 ? 28 : 40);
        const accesorios = norm(articulo.Accesorios) || 'N/A';
        const estadoObs = norm(articulo.EstadoObs) || 'N/A';

        filasArticulos += `
          <tr style="border-bottom:1px solid #e5e7eb; line-height:${tableLineHeight};">
            <td style="padding:${rowPadding}px; text-align:center; font-size:${fontSizeTable}px;">${contador}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px;">${escapeHTML(articulo.Codigo)}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px;">${escapeHTML(nombreImpresion)}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px;">${escapeHTML(articulo.SeriePlaca || 'N/A')}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px; white-space:normal; word-break:break-word;">${escapeHTML(accesorios)}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px; white-space:normal; word-break:break-word;">${escapeHTML(estadoObs)}</td>
          </tr>
        `;
        contador++;
      });

      const boletaHTML = `
        <div id="print-root" style="font-family: 'Segoe UI', sans-serif; width:20.85cm; margin:0 auto; padding:${pagePaddingCm}cm; background:white; font-size:${fontSizeBase}px; line-height:1.18; color:#333; box-sizing:border-box;">
          <!-- HEADER INSTITUCIONAL -->
          <div style="text-align:center; border-bottom:2px solid #2c5aa0; padding-bottom:${Math.max(6, sectionPadding)}px; margin-bottom:${Math.max(6, sectionPadding)}px;">
            <h1 style="color:#2c5aa0; margin:0 0 2px 0; font-size:${fontSizeHeader}px;">Instituto de Investigaciones Psicológicas</h1>
            <h2 style="color:#333; margin:0 0 2px 0; font-size:${fontSizeHeader-2}px;">Universidad de Costa Rica</h2>
            <h3 style="color:#41ADE7; margin:0; font-size:${fontSizeHeader-4}px;">Prueba de Aptitud Académica · Programa Permanente</h3>
          </div>

          <!-- TÍTULO DE LA BOLETA -->
          <div style="text-align:center; margin-bottom:${Math.max(6, sectionPadding)}px;">
            <h2 style="color:#2c5aa0; font-size:${fontSizeHeader}px; border:2px solid #41ADE7; display:inline-block; padding:5px 18px; border-radius:40px; margin:0; letter-spacing:0.2px;">
              BOLETA DE PRÉSTAMO DE EQUIPO
            </h2>
          </div>

          <!-- INFORMACIÓN GENERAL -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; background:#f9fafb; padding:${sectionPadding}px; border-radius:8px; margin-bottom:${Math.max(6, sectionPadding)}px; border:1px solid #d8e3f6;">
            <div>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Responsable:</strong> ${responsableSeguro}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Área:</strong> ${datosPersona.area}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Fecha retiro:</strong> ${fechaRetiro} ${horaRetiro}</p>
            </div>
            <div>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Correo:</strong> ${datosPersona.correo}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Teléfonos:</strong> ${datosPersona.telefonos}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Folio:</strong> ${folio} · <strong>Emisión:</strong> ${fechaActual} ${horaActual}</p>
            </div>
          </div>

          <!-- TABLA DE ARTÍCULOS -->
          <h4 style="color:#2c5aa0; border-bottom:1px solid #41ADE7; padding-bottom:3px; margin:${Math.max(6, sectionPadding)}px 0 5px 0; font-size:${fontSizeBase+1.8}px;">
            Artículos prestados
          </h4>

          <table style="width:100%; border-collapse:collapse; margin-bottom:${Math.max(5, sectionPadding)}px; font-size:${fontSizeTable}px; table-layout:fixed;">
            <thead>
              <tr style="background:#2c5aa0; color:white;">
                <th style="padding:${rowPadding + 2}px; text-align:center; width:4%;">#</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:9%;">Código</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:19%;">Artículo</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:17%;">Serie</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:20%;">Accesorios</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:31%;">Estado</th>
              </tr>
            </thead>
            <tbody>
              ${filasArticulos}
            </tbody>
          </table>

          <!-- TOTALES -->
          <div style="background:#e8f0fe; padding:5px 8px; border-radius:5px; margin:4px 0 ${Math.max(6, sectionPadding)}px 0; display:flex; justify-content:center; border:1px solid #cfe1ff;">
            <span style="font-size:${fontSizeBase+1}px; color:#2c5aa0; font-weight:700;"><strong>Total artículos:</strong> ${totalItems}</span>
          </div>

          <!-- DECLARACIÓN INSTITUCIONAL -->
          <div style="background:#f0f7ff; padding:${sectionPadding}px; border-radius:6px; margin:${Math.max(5, sectionPadding)}px 0; border-left:3px solid #2c5aa0;">
            <p style="font-size:${fontSizeBase}px; line-height:1.22; margin:0; text-align:justify;">
              <strong>El suscrito ${responsableSeguro}</strong>, se hace responsable por los artículos detallados anteriormente,
              los cuales retira del <strong>Instituto de Investigaciones Psicológicas de la Universidad de Costa Rica</strong>.
              Se compromete a devolverlos en las mismas condiciones en que los recibe.
            </p>
          </div>

          <!-- FIRMAS -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin:${Math.max(10, sectionPadding + 4)}px 0 10px 0;">
            <div style="text-align:center;">
              <div style="border-bottom:2px solid #2c5aa0; margin-bottom:4px; padding-top:${Math.max(18, sectionPadding + 16)}px;"></div>
              <p style="margin:0; font-size:${fontSizeBase}px; font-weight:600;">Firma del responsable</p>
            </div>
            <div style="text-align:center;">
              <div style="border-bottom:2px solid #2c5aa0; margin-bottom:4px; padding-top:${Math.max(18, sectionPadding + 16)}px;"></div>
              <p style="margin:0; font-size:${fontSizeBase}px; font-weight:600;">Funcionario IIP · PAA</p>
            </div>
          </div>

          <!-- PIE DE PÁGINA -->
          <div style="text-align:center; border-top:1px solid #41ADE7; padding-top:5px; font-size:${footerFontSize}px; color:#666; margin-top:8px;">
            <p style="margin:0;">IIP · Universidad de Costa Rica - Folio: ${folio}</p>
          </div>
        </div>
      `;

      const documentoHTML = `
        <html>
          <head>
            <title>Boleta de Préstamo ${folio}</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
              html, body {
                margin: 0;
                padding: 0;
                background: white;
              }
              @media print {
                html, body {
                  width: 21.59cm;
                  height: 27.94cm;
                  margin: 0;
                  padding: 0;
                  background: white;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
                .no-print { display: none !important; }
                #print-root {
                  width: 20.85cm !important;
                  margin: 0 auto !important;
                  box-shadow: none !important;
                  page-break-after: avoid;
                }
              }
              table { 
                page-break-inside: avoid; 
                width: 100%;
              }
              tr { 
                page-break-inside: avoid; 
                page-break-after: auto; 
              }
              th, td {
                word-wrap: break-word;
                overflow-wrap: break-word;
                vertical-align: top;
              }
              @page {
                size: letter portrait;
                margin: 0.28cm;
              }
              .btn-imprimir, .btn-cerrar {
                background: #2c5aa0;
                color: white;
                border: none;
                padding: 10px 25px;
                border-radius: 30px;
                font-weight: 600;
                cursor: pointer;
                margin: 0 10px;
                font-size: 14px;
                transition: all 0.3s ease;
              }
              .btn-imprimir:hover, .btn-cerrar:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
              }
              .btn-cerrar {
                background: #6c757d;
              }
            </style>
            <script>
              window.addEventListener('load', function() {
                setTimeout(function() {
                  try { window.print(); } catch(e) {}
                }, 250);
              });
            <\/script>
          </head>
          <body style="margin:0; font-family: 'Segoe UI', sans-serif;">
            ${boletaHTML}
            <div style="text-align:center; margin:20px 0; padding:10px;" class="no-print">
              <button class="btn-imprimir" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
              </button>
              <button class="btn-cerrar" onclick="window.close()">
                <i class="fas fa-times"></i> Cerrar
              </button>
            </div>
          </body>
        </html>
      `;

      win.document.write(documentoHTML);
      win.document.close();

      const descargaLocal = await descargarBoletaPdfLocal({
        tipo: 'prestamo',
        folio,
        htmlContenido: boletaHTML
      });

      if (descargaLocal.success) {
        mostrarNotificacion('⬇️ PDF descargado en su computadora', 'success');
      } else {
        mostrarNotificacion('⚠️ No se pudo descargar el PDF local', 'warning');
      }

      if (responsable) {
        await enviarBoletaPorCorreo({
          persona: responsable,
          asunto: `Boleta de préstamo ${folio}`,
          nombreArchivo: `Boleta_Prestamo_${folio}.pdf`,
          html: documentoHTML,
          tipo: 'prestamo'
        });
      }

      if (resultado.success) {
        mostrarNotificacion(`✅ Boleta generada con ${articulosPrestamoModal.length} artículos`, 'success');
      } else {
        mostrarNotificacion(`⚠️ Boleta generada (Sheet no actualizado)`, 'warning');
      }
    }

    async function generarBoletaEntrada() {
      const responsable = document.getElementById('modalResponsableEntrada').value.trim();
      const emailDestino = document.getElementById('modalEmailEntrada').value.trim();
      if (!responsable) {
        alert('Por favor ingrese el nombre de quien recibe los artículos');
        document.getElementById('modalResponsableEntrada').focus();
        return;
      }

      if (emailDestino && !validarEmail(emailDestino)) {
        alert('Ingrese un correo electrónico válido o deje el campo vacío');
        document.getElementById('modalEmailEntrada').focus();
        return;
      }

      if (articulosEntradaModal.length === 0) {
        alert('Agregue al menos un artículo para registrar la entrada');
        return;
      }

      const yaDisponibles = articulosEntradaModal.filter(a => esSI(a.Disponible));
      if (yaDisponibles.length > 0) {
        alert(`Los siguientes artículos ya están disponibles y no se pueden incluir en la boleta de entrada:\n${yaDisponibles.map(a => a.Nombre).join('\n')}`);
        return;
      }

      const codigos = articulosEntradaModal.map(a => a.Codigo);
      const win = window.open('', '_blank');

      if (!win) {
        alert('Por favor permita ventanas emergentes para generar la boleta');
        return;
      }

      win.document.write(`
        <html>
          <head><title>Generando boleta...</title></head>
          <body style="font-family: Segoe UI, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">
            <div>Generando boleta, por favor espere...</div>
          </body>
        </html>
      `);
      win.document.close();

      cerrarModalEntrada();
      mostrarModalCarga('Actualizando disponibilidad en Google Sheets...', 'Registrando entrada de artículos');

      const resultado = await actualizarDisponibilidadEnSheet(codigos, 'SI');

      codigos.forEach(codigo => {
        const articulo = data.find(a => a.Codigo === codigo);
        if (articulo) {
          articulo.Disponible = 'SI';
        }
      });

      filtrar(document.getElementById('busqueda').value);
      ocultarModalCarga();

      const folio = `ENT-${Date.now().toString().slice(-6)}`;
      const fechaActual = new Date().toLocaleDateString('es-ES');
      const horaActual = new Date().toLocaleTimeString('es-ES');
      const responsableSeguro = escapeHTML(responsable);
      const datosPersona = datosPersonaParaBoleta('entrada', emailDestino);
      const totalItems = articulosEntradaModal.length;

      let fontSizeBase, fontSizeTable, fontSizeHeader, rowPadding, sectionPadding, pagePaddingCm, tableLineHeight, footerFontSize;

      if (totalItems <= 8) {
        fontSizeBase = 13.1;
        fontSizeTable = 11.6;
        fontSizeHeader = 18;
        rowPadding = 4;
        sectionPadding = 12;
        pagePaddingCm = 0.58;
        tableLineHeight = 1.22;
        footerFontSize = 8.4;
      } else if (totalItems <= 14) {
        fontSizeBase = 11.1;
        fontSizeTable = 9.6;
        fontSizeHeader = 14.8;
        rowPadding = 3;
        sectionPadding = 8;
        pagePaddingCm = 0.5;
        tableLineHeight = 1.16;
        footerFontSize = 8;
      } else if (totalItems <= 20) {
        fontSizeBase = 10.4;
        fontSizeTable = 8.55;
        fontSizeHeader = 13.3;
        rowPadding = 1;
        sectionPadding = 5;
        pagePaddingCm = 0.4;
        tableLineHeight = 1.06;
        footerFontSize = 7.8;
      } else {
        fontSizeBase = 8.8;
        fontSizeTable = 7.3;
        fontSizeHeader = 10.8;
        rowPadding = 1;
        sectionPadding = 4;
        pagePaddingCm = 0.35;
        tableLineHeight = 1.05;
        footerFontSize = 7.2;
      }

      const recortarTextoBoletaEntrada = (valor, maxLen) => {
        const limpio = norm(valor) || 'N/A';
        return limpio.length > maxLen ? `${limpio.slice(0, Math.max(1, maxLen - 1))}…` : limpio;
      };

      let filasArticulos = '';
      articulosEntradaModal.forEach((articulo, index) => {
        const nombreImpresion = recortarTextoBoletaEntrada(articulo.Nombre, totalItems > 14 ? 34 : 46);
        const accesorios = norm(articulo.Accesorios) || 'N/A';
        filasArticulos += `
          <tr style="border-bottom:1px solid #e5e7eb; line-height:${tableLineHeight};">
            <td style="padding:${rowPadding}px; text-align:center; font-size:${fontSizeTable}px;">${index + 1}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px;">${escapeHTML(articulo.Codigo)}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px;">${escapeHTML(nombreImpresion)}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px;">${escapeHTML(articulo.SeriePlaca || 'N/A')}</td>
            <td style="padding:${rowPadding}px; font-size:${fontSizeTable}px; white-space:normal; word-break:break-word;">${escapeHTML(accesorios)}</td>
          </tr>
        `;
      });

      const boletaHTML = `
        <div id="print-root" style="font-family: 'Segoe UI', sans-serif; width:20.85cm; margin:0 auto; padding:${pagePaddingCm}cm; background:white; font-size:${fontSizeBase}px; line-height:1.18; color:#333; box-sizing:border-box;">
          <div style="text-align:center; border-bottom:2px solid #2c5aa0; padding-bottom:${Math.max(6, sectionPadding)}px; margin-bottom:${Math.max(6, sectionPadding)}px;">
            <h1 style="color:#2c5aa0; margin:0 0 2px 0; font-size:${fontSizeHeader}px;">Instituto de Investigaciones Psicológicas</h1>
            <h2 style="color:#333; margin:0 0 2px 0; font-size:${fontSizeHeader-2}px;">Universidad de Costa Rica</h2>
            <h3 style="color:#41ADE7; margin:0; font-size:${fontSizeHeader-4}px;">Prueba de Aptitud Académica · Programa Permanente</h3>
          </div>

          <div style="text-align:center; margin-bottom:${Math.max(6, sectionPadding)}px;">
            <h2 style="color:#2c5aa0; font-size:${fontSizeHeader}px; border:2px solid #41ADE7; display:inline-block; padding:5px 18px; border-radius:40px; margin:0; letter-spacing:0.2px;">
              BOLETA DE ENTRADA DE EQUIPO
            </h2>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; background:#f9fafb; padding:${sectionPadding}px; border-radius:8px; margin-bottom:${Math.max(6, sectionPadding)}px; border:1px solid #d8e3f6;">
            <div>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Recibido por:</strong> ${responsableSeguro}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Área:</strong> ${datosPersona.area}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Fecha:</strong> ${fechaActual} ${horaActual}</p>
            </div>
            <div>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Correo:</strong> ${datosPersona.correo}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Teléfonos:</strong> ${datosPersona.telefonos}</p>
              <p style="margin:2px 0; font-size:${fontSizeBase}px;"><strong>Folio:</strong> ${folio} · <strong>Emisión:</strong> ${fechaActual} ${horaActual}</p>
            </div>
          </div>

          <h4 style="color:#2c5aa0; border-bottom:1px solid #41ADE7; padding-bottom:3px; margin:${Math.max(6, sectionPadding)}px 0 5px 0; font-size:${fontSizeBase+1.8}px;">
            Artículos recibidos
          </h4>

          <table style="width:100%; border-collapse:collapse; margin-bottom:${Math.max(5, sectionPadding)}px; font-size:${fontSizeTable}px; table-layout:fixed;">
            <thead>
              <tr style="background:#2c5aa0; color:white;">
                <th style="padding:${rowPadding + 2}px; text-align:center; width:4%;">#</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:12%;">Código</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:30%;">Artículo</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:21%;">Serie</th>
                <th style="padding:${rowPadding + 2}px; text-align:left; width:33%;">Accesorios</th>
              </tr>
            </thead>
            <tbody>
              ${filasArticulos}
            </tbody>
          </table>

          <div style="background:#e8f0fe; padding:5px 8px; border-radius:5px; margin:4px 0 ${Math.max(6, sectionPadding)}px 0; display:flex; justify-content:center; border:1px solid #cfe1ff;">
            <span style="font-size:${fontSizeBase+1}px; color:#2c5aa0; font-weight:700;"><strong>Total artículos:</strong> ${totalItems}</span>
          </div>

          <div style="background:#f0f7ff; padding:${sectionPadding}px; border-radius:6px; margin:${Math.max(5, sectionPadding)}px 0; border-left:3px solid #2c5aa0;">
            <p style="font-size:${fontSizeBase}px; line-height:1.22; margin:0; text-align:justify;">
              Se deja constancia de que los artículos detallados fueron recibidos nuevamente por el
              <strong>Instituto de Investigaciones Psicológicas de la Universidad de Costa Rica</strong> y se registraron como
              <strong>disponibles</strong> en el sistema institucional.
            </p>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin:${Math.max(10, sectionPadding + 4)}px 0 10px 0;">
            <div style="text-align:center;">
              <div style="border-bottom:2px solid #2c5aa0; margin-bottom:4px; padding-top:${Math.max(18, sectionPadding + 16)}px;"></div>
              <p style="margin:0; font-size:${fontSizeBase}px; font-weight:600;">Firma de quien entrega</p>
            </div>
            <div style="text-align:center;">
              <div style="border-bottom:2px solid #2c5aa0; margin-bottom:4px; padding-top:${Math.max(18, sectionPadding + 16)}px;"></div>
              <p style="margin:0; font-size:${fontSizeBase}px; font-weight:600;">Funcionario IIP · PAA</p>
            </div>
          </div>

          <div style="text-align:center; border-top:1px solid #41ADE7; padding-top:5px; font-size:${footerFontSize}px; color:#666; margin-top:8px;">
            <p style="margin:0;">IIP · Universidad de Costa Rica - Folio: ${folio}</p>
          </div>
        </div>
      `;

      const documentoHTML = `
        <html>
          <head>
            <title>Boleta de Entrada ${folio}</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
              html, body {
                margin: 0;
                padding: 0;
                background: white;
              }
              @media print {
                html, body {
                  width: 21.59cm;
                  height: 27.94cm;
                  margin: 0;
                  padding: 0;
                  background: white;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
                .no-print { display: none !important; }
                #print-root {
                  width: 20.85cm !important;
                  margin: 0 auto !important;
                  box-shadow: none !important;
                  page-break-after: avoid;
                }
              }
              table { 
                page-break-inside: avoid; 
                width: 100%;
              }
              tr { 
                page-break-inside: avoid; 
                page-break-after: auto; 
              }
              th, td {
                word-wrap: break-word;
                overflow-wrap: break-word;
                vertical-align: top;
              }
              @page {
                size: letter portrait;
                margin: 0.28cm;
              }
              .btn-imprimir, .btn-cerrar {
                background: #2c5aa0;
                color: white;
                border: none;
                padding: 10px 25px;
                border-radius: 30px;
                font-weight: 600;
                cursor: pointer;
                margin: 0 10px;
                font-size: 14px;
                transition: all 0.3s ease;
              }
              .btn-imprimir:hover, .btn-cerrar:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
              }
              .btn-cerrar { background: #6c757d; }
            </style>
            <script>
              window.addEventListener('load', function() {
                setTimeout(function() {
                  try { window.print(); } catch(e) {}
                }, 250);
              });
            <\/script>
          </head>
          <body style="margin:0; font-family: 'Segoe UI', sans-serif;">
            ${boletaHTML}
            <div style="text-align:center; margin:20px 0; padding:10px;" class="no-print">
              <button class="btn-imprimir" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
              <button class="btn-cerrar" onclick="window.close()"><i class="fas fa-times"></i> Cerrar</button>
            </div>
          </body>
        </html>
      `;

      win.document.write(documentoHTML);
      win.document.close();

      const descargaLocal = await descargarBoletaPdfLocal({
        tipo: 'entrada',
        folio,
        htmlContenido: boletaHTML
      });

      if (descargaLocal.success) {
        mostrarNotificacion('⬇️ PDF descargado en su computadora', 'success');
      } else {
        mostrarNotificacion('⚠️ No se pudo descargar el PDF local', 'warning');
      }

      if (responsable) {
        await enviarBoletaPorCorreo({
          persona: responsable,
          asunto: `Boleta de entrada ${folio}`,
          nombreArchivo: `Boleta_Entrada_${folio}.pdf`,
          html: documentoHTML,
          tipo: 'entrada'
        });
      }

      if (resultado.success) {
        mostrarNotificacion(`✅ Boleta de entrada generada con ${articulosEntradaModal.length} artículos`, 'success');
      } else {
        mostrarNotificacion('⚠️ Boleta generada (Sheet no actualizado)', 'warning');
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const scannerInput = document.getElementById('scannerInput');
      if (scannerInput) {
        scannerInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            agregarArticuloPorCodigo();
          }
        });
      }

      const scannerInputEntrada = document.getElementById('scannerInputEntrada');
      if (scannerInputEntrada) {
        scannerInputEntrada.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            agregarArticuloEntradaPorCodigo();
          }
        });
      }

      // Cierra el desplegable de personal al hacer clic fuera del selector
      document.addEventListener('click', function(e) {
        Object.keys(REFS_PERSONAL).forEach(tipo => {
          const refs = REFS_PERSONAL[tipo];
          const input = document.getElementById(refs.input);
          const lista = document.getElementById(refs.lista);
          if (!input || !lista) return;
          if (input.contains(e.target) || lista.contains(e.target)) return;
          lista.classList.remove('visible');
        });
      });

      // Precarga la lista del personal
      cargarPersonal(true);
    });

    // ====== LOAD ======
    async function cargar() {
      const container = document.getElementById('activos-content');

      if (serviceIndex >= SHEET_SERVICES.length) {
        mostrarErrorCompleto(new Error('Todos los servicios fallaron o se quedaron sin respuesta.'));
        return;
      }

      const currentService = SHEET_SERVICES[serviceIndex];

      container.innerHTML = `
        <div class="estado-carga">
          <div class="spinner"></div>
          <div>Conectando con servicio ${serviceIndex + 1}/${SHEET_SERVICES.length}…</div>
          <div style="margin-top:10px; font-size:14px; opacity:.8;">Timeout: 8s</div>
        </div>
      `;

      try {
        const response = await fetchConTimeout(currentService, 8000);
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);

        const json = await response.json();
        if (json && json.success === false) throw new Error(json.error || 'La base no devolvió datos');

        const rows = json.activos || json;
        if (!rows || (Array.isArray(rows) && rows.length === 0)) throw new Error('No hay datos');

        data = convertirDatos(rows);
        if (data.length === 0) throw new Error('No se pudieron procesar los datos (¿encabezados?)');

        filtradas = [...data];
        ordenadas = [...data];
        paginaActual = 1;

        mostrarFiltros();
        ordenar('codigo', false);
        iniciarAutoRefresh();

      } catch (err) {
        console.error('Fallo servicio:', currentService, err);
        serviceIndex++;
        await sleep(500);
        cargar();
      }
    }

    function convertirDatos(rows) {
      if (!Array.isArray(rows)) return [];
      return rows.map(r => ({
        Id: norm(r['id']),
        Codigo: norm(r['Codigo']),
        Nombre: norm(r['Nombre']),
        // Ojo: las claves de la API son "SeriePlaca" y "EstadoObs", sin la
        // barra — con la barra nunca coincidían y estos dos campos quedaban
        // siempre vacíos en toda la pantalla (búsqueda, tarjetas, "copiar
        // detalle"...).
        SeriePlaca: norm(r['SeriePlaca']),
        IMEI: norm(r['IMEI']),
        Accesorios: norm(r['Accesorios']),
        EstadoObs: norm(r['EstadoObs']),
        Disponible: norm(r['Disponible'])
      }))
      .filter(x => x.Codigo || x.Nombre);
    }

    // ====== UI: FILTERS ======
    function mostrarFiltros() {
      const filtrosContainer = document.getElementById('filtros');
      filtrosContainer.style.display = 'flex';

      const inputBusqueda = document.getElementById('busqueda');
      const containerBusqueda = document.getElementById('filtro-busqueda-container');
      
      inputBusqueda.addEventListener('input', function(e) {
        const termino = e.target.value;
        
        if (termino) {
          containerBusqueda.classList.add('limpiable', 'con-filtro');
        } else {
          containerBusqueda.classList.remove('limpiable', 'con-filtro');
        }
        
        if (timeoutBusqueda) clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
          filtrar(termino);
          
          if (termino.length >= 2) {
            const sugerencias = generarSugerencias(termino);
            mostrarSugerencias(sugerencias);
          } else {
            document.getElementById('sugerencias').classList.remove('visible');
          }
        }, 300);
      });

      // Pistola escáner: manda el código y un Enter. Se busca de una vez (sin
      // esperar el debounce) y, si da un solo resultado, se abre y se lleva
      // la pantalla ahí — así no hay que buscarlo a mano en la lista, y el
      // campo queda listo (limpio y enfocado) para el siguiente escaneo.
      inputBusqueda.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        if (timeoutBusqueda) clearTimeout(timeoutBusqueda);
        const termino = inputBusqueda.value;
        filtrar(termino);

        document.getElementById('sugerencias').classList.remove('visible');

        if (termino && filtradas.length === 1) {
          const acordeon = document.getElementById('acordeon0');
          if (acordeon) {
            acordeon.classList.add('abierto');
            acordeon.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          // Se limpia el campo (sin volver a filtrar, para no perder la
          // tarjeta recién abierta) y queda enfocado para el próximo escaneo.
          inputBusqueda.value = '';
          containerBusqueda.classList.remove('limpiable', 'con-filtro');
          inputBusqueda.focus();
        } else if (termino && filtradas.length === 0) {
          containerBusqueda.classList.add('sin-resultado');
          setTimeout(() => containerBusqueda.classList.remove('sin-resultado'), 600);
        }
      });

      containerBusqueda.addEventListener('click', function(e) {
        if (e.target === containerBusqueda || e.target.classList.contains('badge-busqueda')) {
          limpiarBusqueda();
        }
      });

      document.getElementById('ordenar-por').addEventListener('change', function() {
        ordenar(this.value, true);
      });

      document.getElementById('filtro-disponible').addEventListener('change', function() {
        filtrar(document.getElementById('busqueda').value);
      });

      document.getElementById('elementos-por-pagina').addEventListener('change', function() {
        elementosPorPagina = parseInt(this.value, 10);
        paginaActual = 1;
        render();
      });
    }

    function filtrar(termino) {
      terminoBusquedaActual = termino || '';
      
      const filtroDisp = document.getElementById('filtro-disponible')?.value || 'todos';

      filtradas = data.filter(a => {
        const matchTexto = !terminoBusquedaActual || buscarAvanzado(terminoBusquedaActual, a);

        const disponible = esSI(a.Disponible);
        const matchDisp = (filtroDisp === 'todos')
          || (filtroDisp === 'si' && disponible)
          || (filtroDisp === 'no' && !disponible);

        return matchTexto && matchDisp;
      });

      const criterioOrden = document.getElementById('ordenar-por').value;
      ordenar(criterioOrden, false);
    }

    function ordenar(criterio, resetearPaginacion = true) {
      if (resetearPaginacion) paginaActual = 1;

      ordenadas = [...filtradas];

      if (criterio === 'nombre') {
        ordenadas.sort((a, b) => a.Nombre.localeCompare(b.Nombre, 'es'));
      } else if (criterio === 'disponible') {
        ordenadas.sort((a, b) => (esSI(b.Disponible) - esSI(a.Disponible)) || (parseInt(a.Codigo) || 0) - (parseInt(b.Codigo) || 0));
      } else {
        ordenadas.sort((a, b) => (parseInt(a.Codigo) || 0) - (parseInt(b.Codigo) || 0));
      }

      render();
    }

    // ====== RENDER ======
    function render() {
      const container = document.getElementById('activos-content');

      const inicio = (paginaActual - 1) * elementosPorPagina;
      const fin = inicio + elementosPorPagina;
      const pageItems = ordenadas.slice(inicio, fin);
      const totalPaginas = Math.ceil(ordenadas.length / elementosPorPagina);

      let html = `
        <div style="text-align:center; margin-bottom: 25px;">
          <div class="contador-sedes">
            📦 ${ordenadas.length} Activos 
            ${terminoBusquedaActual ? `<span style="opacity:0.9;"> | Filtrado: "${terminoBusquedaActual}"</span>` : ''}
          </div>
          <div style="margin-top: 10px;">
            <button class="boton-reintentar" onclick="reiniciarCarga()">🔄 Recargar desde Google Sheets</button>
          </div>
        </div>
      `;

      if (ordenadas.length === 0) {
        html += `
          <div class="estado-carga">
            <div style="font-size: 48px; margin-bottom: 20px;">🔍</div>
            <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px; color: var(--text-dark);">No se encontraron activos</div>
            <div style="color: var(--text-dark); margin-bottom: 20px;">
              ${terminoBusquedaActual ? `No hay resultados para "${terminoBusquedaActual}"` : 'No hay activos disponibles'}
            </div>
            <button class="boton-reintentar" onclick="limpiarBusqueda()">🗑️ Limpiar búsqueda</button>
          </div>
        `;
        container.innerHTML = html;
        return;
      }

      html += `
        <div class="info-paginacion">
          Mostrando activos ${inicio + 1} - ${Math.min(fin, ordenadas.length)} de ${ordenadas.length}
        </div>
      `;

      pageItems.forEach((a, idx) => {
        const iGlobal = inicio + idx;
        const id = `acordeon${iGlobal}`;

        const disponible = esSI(a.Disponible);
        const badge = disponible
          ? `<span class="badge ok">✅ Disponible</span>`
          : `<span class="badge bad">⛔ No disponible</span>`;

        const warnBadge = (!a.Accesorios || a.Accesorios.toLowerCase().includes('ninguno') || a.Accesorios === '')
          ? `<span class="badge warn">⚠️ Sin accesorios</span>`
          : '';

        const detalleParaCopiar =
`*Activo:* ${escapeForCopy(a.Nombre)}
*Código:* ${escapeForCopy(a.Codigo)}
*Serie/Placa:* ${escapeForCopy(a.SeriePlaca)}
*IMEI:* ${escapeForCopy(a.IMEI)}
*Accesorios:* ${escapeForCopy(a.Accesorios)}
*Estado/Obs:* ${escapeForCopy(a.EstadoObs)}
*${disponible ? 'Disponible' : 'Prestado a'}:* ${escapeForCopy(a.Disponible)}`;

        const bloqueDisponibilidad = disponible
          ? `
                <div class="info-item">
                  <div class="info-icon">📦</div>
                  <div class="info-text">
                    <strong>Disponible</strong>
                    <div>${resaltarTexto(a.Disponible || 'SI', terminoBusquedaActual)}</div>
                  </div>
                </div>
              `
          : `
                <div class="info-item">
                  <div class="info-icon">👤</div>
                  <div class="info-text">
                    <strong>Prestado a</strong>
                    <div>${resaltarTexto(a.Disponible || 'No indicado', terminoBusquedaActual)}</div>
                  </div>
                </div>
              `;

        html += `
          <div class="acordeon" id="${id}">
            <div class="acordeon-cabecera" onclick="toggleAcordeon('${id}')">
              <div style="flex:1;">
                <h3>
                  ${resaltarTexto(a.Nombre || 'Activo', terminoBusquedaActual)}
                  ${badge}
                  ${warnBadge}
                </h3>
                <div class="subline">
                  <span><b>Código:</b> ${resaltarTexto(a.Codigo || 'N/D', terminoBusquedaActual)}</span>
                  <span>•</span>
                  <span><b>Serie/Placa:</b> ${resaltarTexto(a.SeriePlaca || 'N/D', terminoBusquedaActual)}</span>
                </div>
              </div>
              <div class="acordeon-icono">+</div>
            </div>

            <div class="acordeon-contenido">
              <div class="info-grid">
                <div class="info-item">
                  <div class="info-icon">🆔</div>
                  <div class="info-text">
                    <strong>IMEI</strong>
                    <div>${resaltarTexto(a.IMEI || 'No indicado', terminoBusquedaActual)}</div>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">🔌</div>
                  <div class="info-text">
                    <strong>Accesorios</strong>
                    <div>${resaltarTexto(a.Accesorios || 'No indicado', terminoBusquedaActual)}</div>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">📝</div>
                  <div class="info-text">
                    <strong>Estado / Observaciones</strong>
                    <div>${resaltarTexto(a.EstadoObs || 'Sin observación', terminoBusquedaActual)}</div>
                  </div>
                </div>

                ${bloqueDisponibilidad}
              </div>

              <div class="acciones">
                <button class="btn" onclick="copiarTexto(${JSON.stringify(detalleParaCopiar)})">
                  <i class="fas fa-copy"></i> Copiar detalle
                </button>
                ${disponible ? `
                  <button class="btn" onclick="generarBoletaIndividual('${a.Codigo}')" style="background: var(--secondary-blue);">
                    <i class="fas fa-file-pdf"></i> Boleta individual
                  </button>
                ` : ''}
              </div>
            </div>
          </div>
        `;
      });

      if (totalPaginas > 1) html += generarControlesPaginacion(totalPaginas);

      container.innerHTML = html;
    }

    function generarControlesPaginacion(totalPaginas) {
      let html = '<div class="paginacion">';

      html += `
        <button class="boton-pagina" onclick="cambiarPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>
          ← Anterior
        </button>
      `;

      const maxPag = 5;
      let ini = Math.max(1, paginaActual - Math.floor(maxPag / 2));
      let fin = Math.min(totalPaginas, ini + maxPag - 1);
      if (fin - ini + 1 < maxPag) ini = Math.max(1, fin - maxPag + 1);

      if (ini > 1) {
        html += `<button class="boton-pagina" onclick="cambiarPagina(1)">1</button>`;
        if (ini > 2) html += `<span>...</span>`;
      }

      for (let p = ini; p <= fin; p++) {
        html += `<button class="boton-pagina ${p === paginaActual ? 'activa' : ''}" onclick="cambiarPagina(${p})">${p}</button>`;
      }

      if (fin < totalPaginas) {
        if (fin < totalPaginas - 1) html += `<span>...</span>`;
        html += `<button class="boton-pagina" onclick="cambiarPagina(${totalPaginas})">${totalPaginas}</button>`;
      }

      html += `
        <button class="boton-pagina" onclick="cambiarPagina(${paginaActual + 1})" ${paginaActual === totalPaginas ? 'disabled' : ''}>
          Siguiente →
        </button>
      `;

      html += '</div>';
      return html;
    }

    function cambiarPagina(nueva) {
      paginaActual = nueva;
      render();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleAcordeon(id) {
      const acordeon = document.getElementById(id);
      acordeon.classList.toggle('abierto');
    }

    function mostrarErrorCompleto(error) {
      const container = document.getElementById('activos-content');
      container.innerHTML = `
        <div class="mensaje-error">
          <h3>❌ No se pudo cargar la información</h3>
          <p><strong>Error:</strong> ${escapeHTML(error.message)}</p>
          <div style="margin-top: 10px;">
            <button class="boton-reintentar" onclick="reiniciarCarga()">🔄 Reintentar</button>
          </div>
          <p style="margin-top: 12px; font-size: 14px; opacity: 0.85;">
            Revisá que el inventario esté cargado
            (<code>php scripts/importar_csv.php activos archivo.csv</code>) y el
            log de errores del servidor, donde queda el detalle.
          </p>
        </div>
      `;
    }

    function reiniciarCarga() {
      serviceIndex = 0;
      paginaActual = 1;
      cargar();
    }

    // Función para generar boleta individual de un artículo
    async function generarBoletaIndividual(codigo) {
      const articulo = data.find(a => a.Codigo === codigo);
      if (!articulo) return;

      if (!esSI(articulo.Disponible)) {
        alert('Este artículo no está disponible para préstamo');
        return;
      }

      if (!personalCargado) await cargarPersonal(true);

      const responsable = prompt('Ingrese el nombre de quien retira el artículo:');
      if (!responsable) return;

      // Si el nombre coincide con alguien de la hoja "Personal", se toman sus datos
      const personaHoja = personal.find(p => claveNormalizada(p.Persona) === claveNormalizada(responsable)) || null;

      // Ya no se pide un correo a mano: el destinatario sale de la hoja
      // Personal, resuelto por el Apps Script a partir del nombre.
      const emailDestino = personaHoja?.Correo || '';

      const win = window.open('', '_blank');
      if (!win) {
        alert('Por favor permita ventanas emergentes para generar la boleta');
        return;
      }

      win.document.write(`
        <html>
          <head><title>Generando boleta...</title></head>
          <body style="font-family: Segoe UI, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">
            <div>Generando boleta, por favor espere...</div>
          </body>
        </html>
      `);
      win.document.close();

      mostrarModalCarga('Actualizando disponibilidad...', 'Conectando con el servidor');

      const estadoPrestamo = responsable.trim();
      const resultado = await actualizarDisponibilidadEnSheet([codigo], estadoPrestamo);
      
      articulo.Disponible = estadoPrestamo;
      filtrar(document.getElementById('busqueda').value);
      
      ocultarModalCarga();

      const folio = `PAA-${Date.now().toString().slice(-6)}`;
      const fechaActual = new Date().toLocaleDateString('es-ES');
      const horaActual = new Date().toLocaleTimeString('es-ES');
      const responsableSeguro = escapeHTML(responsable.trim());
      
      const fechaRetiro = new Date().toLocaleDateString('es-ES');

      const boletaHTML = `
        <div style="font-family: 'Segoe UI', sans-serif; width:100%; max-width:700px; margin:0 auto; padding:20px; background:white;">
          <!-- HEADER INSTITUCIONAL -->
          <div style="text-align:center; border-bottom:3px solid #2c5aa0; padding-bottom:15px; margin-bottom:20px;">
            <h1 style="color:#2c5aa0; margin:0; font-size:22px;">IIP · Universidad de Costa Rica</h1>
            <h2 style="color:#333; margin:5px 0; font-size:18px;">Prueba de Aptitud Académica</h2>
          </div>

          <div style="text-align:center; margin-bottom:20px;">
            <h2 style="color:#2c5aa0; font-size:20px; border:2px solid #41ADE7; display:inline-block; padding:8px 25px; border-radius:40px;">
              COMPROBANTE DE PRÉSTAMO INDIVIDUAL
            </h2>
          </div>

          <div style="background:#f9fafb; padding:15px; border-radius:10px; margin-bottom:20px;">
            <p style="margin:5px 0; font-size:12px; color:#333;"><strong>Responsable:</strong> ${responsableSeguro}</p>
            <p style="margin:5px 0; font-size:12px; color:#333;"><strong>Área:</strong> ${escapeHTML(norm(personaHoja?.Area) || 'N/A')}</p>
            <p style="margin:5px 0; font-size:12px; color:#333;"><strong>Correo:</strong> ${escapeHTML(norm(personaHoja?.Correo) || norm(emailDestino) || 'N/A')}</p>
            <p style="margin:5px 0; font-size:12px; color:#333;"><strong>Teléfonos:</strong> ${escapeHTML([personaHoja?.Telefono1, personaHoja?.Telefono2].map(norm).filter(Boolean).join(' / ') || 'N/A')}</p>
            <p style="margin:5px 0; font-size:12px; color:#333;"><strong>Fecha de retiro:</strong> ${fechaRetiro}</p>
            <p style="margin:5px 0; font-size:12px; color:#333;"><strong>Folio:</strong> ${folio}</p>
          </div>

          <div style="border:2px solid #41ADE7; border-radius:10px; padding:20px; margin-bottom:20px;">
            <h3 style="color:#2c5aa0; margin:0 0 15px 0; font-size:16px;">Detalle del artículo</h3>
            <table style="width:100%; border-collapse:collapse;">
              <tr><td style="padding:5px; font-weight:600; color:#333;">Código:</td><td style="color:#333;">${escaparHtml(articulo.Codigo)}</td></tr>
              <tr><td style="padding:5px; font-weight:600; color:#333;">Nombre:</td><td style="color:#333;">${escaparHtml(articulo.Nombre)}</td></tr>
              <tr><td style="padding:5px; font-weight:600; color:#333;">Serie/Placa:</td><td style="color:#333;">${escaparHtml(articulo.SeriePlaca || 'N/A')}</td></tr>
              <tr><td style="padding:5px; font-weight:600; color:#333;">Accesorios:</td><td style="color:#333;">${escaparHtml(articulo.Accesorios || 'N/A')}</td></tr>
              <tr><td style="padding:5px; font-weight:600; color:#333;">Estado/Obs:</td><td style="color:#333;">${escaparHtml(articulo.EstadoObs || 'N/A')}</td></tr>
            </table>
          </div>

          <div style="background:#f0f7ff; padding:20px; border-radius:8px; margin-bottom:20px; border-left:4px solid #2c5aa0;">
            <p style="font-size:12px; line-height:1.6; margin:0; text-align:justify; color:#333;">
              <strong>${responsableSeguro}</strong> se hace responsable por el artículo detallado anteriormente, 
              el cual retira del <strong>Instituto de Investigaciones Psicológicas de la Universidad de Costa Rica</strong>.
            </p>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; margin:30px 0;">
            <div style="text-align:center;">
              <div style="border-bottom:2px solid #2c5aa0; margin-bottom:5px; padding-top:30px;"></div>
              <p style="font-size:11px; font-weight:600; color:#333;">Firma del responsable</p>
            </div>
            <div style="text-align:center;">
              <div style="border-bottom:2px solid #2c5aa0; margin-bottom:5px; padding-top:30px;"></div>
              <p style="font-size:11px; font-weight:600; color:#333;">Funcionario IIP</p>
            </div>
          </div>

          <div style="text-align:center; margin-top:20px; border-top:1px solid #41ADE7; padding-top:10px; font-size:9px; color:#666;">
            Documento generado el ${fechaActual} a las ${horaActual} · Folio: ${folio}
          </div>
        </div>
      `;

      const documentoHTML = `
        <html>
          <head>
            <title>Boleta Individual ${folio}</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
              @media print {
                body { margin: 0.5cm; }
                .no-print { display: none; }
              }
              .btn-imprimir, .btn-cerrar {
                background: #2c5aa0;
                color: white;
                border: none;
                padding: 10px 25px;
                border-radius: 30px;
                font-weight: 600;
                cursor: pointer;
                margin: 0 10px;
                font-size: 14px;
                transition: all 0.3s ease;
              }
              .btn-imprimir:hover, .btn-cerrar:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
              }
              .btn-cerrar {
                background: #6c757d;
              }
            </style>
          </head>
          <body style="margin:0; font-family: 'Segoe UI', sans-serif;">
            ${boletaHTML}
            <div style="text-align:center; margin:20px 0; padding:10px;" class="no-print">
              <button class="btn-imprimir" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
              </button>
              <button class="btn-cerrar" onclick="window.close()">
                <i class="fas fa-times"></i> Cerrar
              </button>
            </div>
          </body>
        </html>
      `;

      win.document.write(documentoHTML);
      win.document.close();

      if (responsable) {
        await enviarBoletaPorCorreo({
          persona: responsable,
          asunto: `Boleta individual ${folio}`,
          nombreArchivo: `Boleta_Individual_${folio}.pdf`,
          html: documentoHTML,
          tipo: 'individual'
        });
      }

      if (resultado.success) {
        mostrarNotificacion('✅ Boleta individual generada', 'success');
      } else {
        mostrarNotificacion('⚠️ Boleta generada (Sheet no actualizado)', 'warning');
      }
    }

    // Exponer funciones al window
    window.reiniciarCarga = reiniciarCarga;
    window.toggleAcordeon = toggleAcordeon;
    window.cambiarPagina = cambiarPagina;
    window.copiarTexto = copiarTexto;
    window.limpiarBusqueda = limpiarBusqueda;
    window.abrirModalEntrada = abrirModalEntrada;
    window.abrirModalPrestamo = abrirModalPrestamo;
    window.cerrarModalEntrada = cerrarModalEntrada;
    window.cerrarModalPrestamo = cerrarModalPrestamo;
    window.generarBoletaEntrada = generarBoletaEntrada;
    window.generarBoletaPrestamo = generarBoletaPrestamo;
    window.generarBoletaIndividual = generarBoletaIndividual;
    window.agregarArticuloEntradaPorCodigo = agregarArticuloEntradaPorCodigo;
    window.agregarArticuloPorCodigo = agregarArticuloPorCodigo;
    window.eliminarArticuloEntradaModal = eliminarArticuloEntradaModal;
    window.eliminarArticuloModal = eliminarArticuloModal;
    window.recargarDatosSilencioso = recargarDatosSilencioso;
    window.onInputPersonal = onInputPersonal;
    window.onKeyPersonal = onKeyPersonal;
    window.seleccionarPersonaPorIndice = seleccionarPersonaPorIndice;
    window.limpiarSeleccionPersona = limpiarSeleccionPersona;
    window.abrirModalNuevoPersonal = abrirModalNuevoPersonal;
    window.cerrarModalNuevoPersonal = cerrarModalNuevoPersonal;
    window.guardarNuevoPersonal = guardarNuevoPersonal;
    window.cargarPersonal = cargarPersonal;

    // Al volver a la pestaña/ventana, sincroniza por si alguien cambió SI/NO manualmente en Sheets
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) recargarDatosSilencioso(true);
    });

    window.addEventListener('focus', () => {
      recargarDatosSilencioso(false);
    });

    document.addEventListener('DOMContentLoaded', cargar);
  </script>
<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
