<?php
/**
 * Módulo de uso interno: solo administración.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("sedes", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Trae datos de contacto de cada centro educativo (director, conserje,
         teléfono): se pide a los buscadores que no lo indexen. -->
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Sedes · UCR</title>
  <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            /* Paleta base del sistema PAA (la misma de Ingreso de Datos,
               Revisión, Traslados, Usuarios y Anuncios) — el resto de nombres
               de variable de este módulo quedan como alias suyos para no
               reescribir cada regla. */
            --primary: #0055a4;
            --primary-dark: #003b73;
            --primary-light: #2b7fc2;
            --secondary: #6EC1E4;
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
            --secondary-blue: var(--primary);
            --light-blue: #e3f0fa;
            --dark-blue: var(--primary-dark);
            --text-dark: var(--gray-800);
            --text-light: var(--gray-500);
            --background: var(--gray-50);
            --white: #ffffff;
            --border-color: var(--gray-200);
            --shadow-light: var(--shadow);
            --shadow-medium: 0 4px 16px rgba(17, 24, 39, .10);
            --shadow-hover: 0 6px 20px rgba(17, 24, 39, .12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            max-width: 1080px;
            margin: 0 auto;
        }

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

        .data-source {
            background-color: var(--light-blue);
            border-left: 3px solid var(--secondary-blue);
            padding: .9rem 1.1rem;
            margin-bottom: 1.25rem;
            border-radius: 0 10px 10px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .data-source-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .data-source-item strong {
            color: var(--secondary-blue);
        }

        /* --- ACORDEÓN -------------------------------------- */
        .acordeon {
            margin-bottom: .7rem;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            transition: border-color .15s ease;
            position: relative;
            border: 1px solid var(--border-color);
        }

        .acordeon:hover {
            border-color: var(--primary-blue);
        }

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
        }

        .acordeon-cabecera:hover {
            background: var(--light-blue);
        }

        .acordeon-cabecera h3 {
            font-size: .98rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .acordeon-cabecera p {
            font-size: .78rem;
            color: var(--text-light);
            margin-top: .3rem;
        }

        .acordeon-icono {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: var(--secondary-blue);
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            transition: transform .25s ease;
        }

        .acordeon.abierto .acordeon-icono {
            transform: rotate(45deg);
            background: var(--light-blue);
        }

        /* Contenido con animación suave */
        .acordeon-contenido {
            background-color: #ffffff;
            max-height: 0;
            overflow: hidden;
            padding: 0 1.2rem;
            transition:
                max-height 0.35s ease,
                padding 0.2s ease;
            position: relative;
            z-index: 1;
            border-top: 1px solid transparent;
        }

        .acordeon.abierto .acordeon-contenido {
            max-height: 5000px;
            padding: 1.2rem;
            border-top-color: var(--border-color);
        }

        /* Imagen */
        .imagen-sede {
            width: 100%;
            max-width: 640px;
            height: 320px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1.2rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid var(--border-color);
        }

        .imagen-placeholder {
            width: 100%;
            max-width: 640px;
            height: 320px;
            background: var(--light-blue);
            border-radius: 10px;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-blue);
            font-size: 16px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
            flex-direction: column;
            gap: 12px;
            border: 1px solid var(--border-color);
        }

        /* Grid de información */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: .8rem;
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: .9rem 1rem;
            background-color: var(--background);
            border-radius: 10px;
            transition: background-color .15s ease;
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

        .info-text {
            flex: 1;
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

        /* Descripción */
        .descripcion-sede {
            background: var(--light-blue);
            padding: 1.1rem 1.2rem;
            border-radius: 10px;
            border-left: 3px solid var(--secondary-blue);
            margin-top: 1rem;
        }

        .descripcion-sede strong {
            color: var(--secondary-blue-dark, var(--dark-blue));
            font-size: .92rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: .6rem;
            font-weight: 700;
        }

        .descripcion-sede strong::before {
            content: '\f0f6';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--secondary-blue);
        }

        .descripcion-sede p {
            margin: 0;
            color: var(--text-dark);
            line-height: 1.6;
            font-size: .9rem;
        }

        /* Estados de carga */
        .estado-carga {
            text-align: center;
            padding: 3.5rem 1.5rem;
            background: #ffffff;
            border-radius: 14px;
            margin: 1.25rem 0;
            border: 1px solid var(--border-color);
            color: var(--text-light);
        }

        .contador-sedes {
            background: var(--secondary-blue);
            color: white;
            padding: 10px 22px;
            border-radius: 20px;
            font-weight: 700;
            margin-bottom: 1.25rem;
            display: inline-block;
            font-size: 14px;
        }

        .mensaje-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 1.25rem;
            border-radius: 10px;
            border-left: 4px solid #ef4444;
            text-align: center;
            margin: 1.25rem 0;
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
            background: var(--dark-blue);
        }

        /* Filtros y búsqueda */
        .filtros-container {
            background: white;
            padding: 1.4rem 1.5rem;
            border-radius: 14px;
            margin-bottom: 1.25rem;
            border: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .filtro-busqueda {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .filtro-busqueda input {
            width: 100%;
            padding: 11px 14px 11px 40px;
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
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--text-light);
        }

        .filtro-orden {
            display: flex;
            gap: 10px;
        }

        .filtro-orden select {
            padding: 11px 14px;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            cursor: pointer;
            transition: border-color .15s ease;
            color: var(--text-dark);
        }

        .filtro-orden select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(43, 127, 194, .15);
        }

        /* Indicador de acordeón abierto */
        .acordeon-indicador {
            display: inline-block;
            margin-left: 10px;
            font-size: 12px;
            color: var(--secondary-blue);
            font-weight: 600;
            opacity: 0.8;
        }

        /* Animación de carga */
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

        @keyframes spin {
            to { transform: rotate(360deg); }
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
            border-color: var(--primary-blue);
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
        }

        /* Selector de elementos por página */
        .selector-pagina {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            background: var(--light-blue);
            padding: 8px 15px;
            border-radius: 10px;
        }

        .selector-pagina select {
            padding: 7px 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            background: white;
            cursor: pointer;
            color: var(--text-dark);
        }

        /* Cabecera del acordeón con acciones */
        .acordeon-cabecera-fila {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            width: 100%;
        }

        .boton-editar {
            background: var(--light-blue);
            color: var(--secondary-blue);
            border: 1px solid var(--primary-blue);
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 12.5px;
            white-space: nowrap;
            transition: background .15s ease, color .15s ease;
        }

        .boton-editar:hover {
            background: var(--secondary-blue);
            color: white;
        }

        /* Insignias de estado */
        .insignias { display: flex; flex-wrap: wrap; gap: 6px; margin-top: .4rem; }

        .insignia {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .insignia-ok    { background: #d1fae5; color: #065f46; }
        .insignia-alerta { background: #fef3c7; color: #92400e; }
        .insignia-neutra { background: var(--gray-100); color: var(--gray-600); }
        .insignia-baja   { background: #fee2e2; color: #991b1b; }

        /* Controles extra sobre la barra de filtros */
        .filtro-extra {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .82rem;
            color: var(--text-light);
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }

        .boton-accion-secundaria {
            background: white;
            color: var(--secondary-blue);
            border: 1.5px solid var(--primary-blue);
            padding: 9px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background .15s ease, color .15s ease;
        }

        .boton-accion-secundaria:hover {
            background: var(--secondary-blue);
            color: white;
        }

        .acciones-hero {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: .9rem;
        }

        /* --- ACTUALIZAR DESDE EXCEL -------------------------------- */
        .panel-actualizar {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .panel-actualizar-cabecera {
            padding: 1.1rem 1.4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
        }

        .panel-actualizar-cabecera h2 {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-actualizar-cabecera p {
            font-size: .8rem;
            color: var(--text-light);
            margin-top: 3px;
        }

        .panel-actualizar-indicador {
            font-size: 18px;
            color: var(--secondary-blue);
            font-weight: 700;
            transition: transform .2s ease;
            flex: 0 0 auto;
        }

        .panel-actualizar.abierto .panel-actualizar-indicador { transform: rotate(45deg); }

        .panel-actualizar-cuerpo {
            display: none;
            padding: 0 1.4rem 1.4rem;
            border-top: 1px solid var(--border-color);
        }

        .panel-actualizar.abierto .panel-actualizar-cuerpo { display: block; padding-top: 1.2rem; }

        .fila-carga {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .fila-carga input[type="file"] {
            flex: 1;
            min-width: 220px;
            padding: 9px 12px;
            border: 1.5px dashed var(--border-color);
            border-radius: 10px;
            font-size: .85rem;
            background: var(--gray-50);
        }

        .boton-analizar {
            background: var(--secondary-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: .85rem;
            cursor: pointer;
        }
        .boton-analizar:hover { background: var(--dark-blue); }
        .boton-analizar:disabled { opacity: .6; cursor: not-allowed; }

        .boton-aplicar-dotacion {
            background: #059669;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: .85rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        .boton-aplicar-dotacion:hover { background: #047857; }
        .boton-aplicar-dotacion:disabled { opacity: .6; cursor: not-allowed; }

        .resumen-dotacion {
            margin-top: 1.1rem;
        }

        .resumen-dotacion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: .9rem;
        }

        .chip-dotacion {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            background: var(--light-blue);
            color: var(--secondary-blue);
        }
        .chip-dotacion.alerta { background: #fef3c7; color: #92400e; }
        .chip-dotacion.ok { background: #d1fae5; color: #065f46; }

        .tabla-cambios-dotacion {
            max-height: 340px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }

        .fila-cambio-sede {
            padding: .7rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: .82rem;
        }
        .fila-cambio-sede:last-child { border-bottom: none; }
        .fila-cambio-sede strong { color: var(--text-dark); }
        .fila-cambio-sede .campo-cambio { color: var(--text-light); display: block; margin-top: 2px; }
        .fila-cambio-sede .campo-cambio b { color: var(--secondary-blue); font-weight: 700; }

        .huerfanas-dotacion {
            margin-top: .8rem;
            font-size: .78rem;
            color: #92400e;
        }
        .huerfanas-dotacion summary { cursor: pointer; font-weight: 700; }

        /* --- MODAL DE EDICIÓN -------------------------------------- */
        .modal-fondo {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, .55);
            z-index: 100;
            align-items: flex-start;
            justify-content: center;
            padding: 3vh 1rem;
            overflow-y: auto;
        }

        .modal-fondo.abierto { display: flex; }

        .modal-caja {
            background: white;
            border-radius: 16px;
            max-width: 780px;
            width: 100%;
            box-shadow: var(--shadow-hover);
            overflow: hidden;
        }

        .modal-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.1rem 1.4rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .modal-cabecera h2 { font-size: 1.15rem; font-weight: 800; }
        .modal-cabecera p { font-size: .78rem; opacity: .9; margin-top: 2px; }

        .modal-cerrar {
            background: rgba(255,255,255,.18);
            border: none;
            color: white;
            width: 30px; height: 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            flex: 0 0 auto;
        }

        .modal-cerrar:hover { background: rgba(255,255,255,.32); }

        .modal-cuerpo {
            padding: 1.3rem 1.4rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-fieldset {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 1.1rem 1.15rem;
            margin-bottom: 1rem;
        }

        .modal-fieldset legend {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--secondary-blue);
            padding: 0 6px;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: .8rem;
        }

        .modal-campo { display: flex; flex-direction: column; gap: 4px; }
        .modal-campo.ancho { grid-column: 1 / -1; }

        .modal-campo label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--text-light);
        }

        .modal-campo input[type="text"],
        .modal-campo input[type="number"],
        .modal-campo input[type="date"],
        .modal-campo input[type="email"],
        .modal-campo input[type="url"],
        .modal-campo textarea {
            padding: 9px 11px;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            font-size: .88rem;
            font-family: inherit;
            color: var(--text-dark);
        }

        .modal-campo input:focus, .modal-campo textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(43, 127, 194, .15);
        }

        .modal-campo textarea { resize: vertical; min-height: 60px; }

        .modal-check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .85rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
        }

        .modal-check input { width: 17px; height: 17px; cursor: pointer; }

        .modal-pie {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 1rem 1.4rem;
            border-top: 1px solid var(--border-color);
            background: var(--gray-50);
        }

        .modal-boton {
            padding: 10px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: .88rem;
            cursor: pointer;
            border: none;
        }

        .modal-boton.cancelar { background: white; border: 1.5px solid var(--border-color); color: var(--text-dark); }
        .modal-boton.guardar { background: var(--secondary-blue); color: white; }
        .modal-boton.guardar:hover { background: var(--dark-blue); }
        .modal-boton:disabled { opacity: .6; cursor: not-allowed; }

        .modal-aviso {
            margin: 0 1.4rem 1rem;
            padding: .7rem 1rem;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 600;
            display: none;
        }
        .modal-aviso.mostrar { display: block; }
        .modal-aviso.error { background: #fee2e2; color: #991b1b; }
        .modal-aviso.ok { background: #d1fae5; color: #065f46; }

        /* Responsive */
        @media (max-width: 768px) {
            .imagen-sede, .imagen-placeholder {
                height: 220px;
                max-width: 100%;
            }

            .acordeon-cabecera {
                padding: .9rem 1rem;
            }

            .acordeon-cabecera h3 {
                font-size: .9rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .filtros-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filtro-busqueda {
                min-width: 100%;
            }

            .selector-pagina {
                margin-left: 0;
                justify-content: center;
                width: 100%;
            }

            .paginacion {
                gap: 5px;
            }

            .boton-pagina {
                padding: 6px 12px;
                font-size: 13px;
            }
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
            <h1>Sedes</h1>
            <p>Información completa de todas nuestras sedes</p>
            <div class="acciones-hero">
                <button class="boton-accion-secundaria" onclick="descargarExcel()">
                    <i class="fa-solid fa-file-excel"></i> Descargar Excel
                </button>
                <button class="boton-accion-secundaria" onclick="descargarJson()">
                    <i class="fa-solid fa-file-code"></i> Exportar JSON
                </button>
            </div>
        </div>

        <div class="panel-actualizar" id="panelActualizar">
            <div class="panel-actualizar-cabecera" onclick="togglePanelActualizar()">
                <div>
                    <h2><i class="fa-solid fa-file-import"></i> Actualizar sedes desde Excel</h2>
                    <p>Subí el Excel del padrón externo para completar o corregir turno, coordinador y dotación de puestos de las sedes que ya existen.</p>
                </div>
                <span class="panel-actualizar-indicador">+</span>
            </div>
            <div class="panel-actualizar-cuerpo">
                <div class="fila-carga">
                    <input type="file" id="archivoDotacion" accept=".xls,.xlsx,.html">
                    <button type="button" class="boton-analizar" id="botonAnalizarDotacion" onclick="analizarDotacion()">
                        <i class="fa-solid fa-magnifying-glass-chart"></i> Analizar cambios
                    </button>
                </div>
                <div id="resultadoDotacion"></div>
            </div>
        </div>

        <div id="filtros" class="filtros-container" style="display: none;">
            <div class="filtro-busqueda">
                <input type="text" id="busqueda" placeholder="Buscar sedes por nombre, código, coordinador, fórmula...">
            </div>
            <div class="filtro-orden">
                <select id="ordenar-por">
                    <option value="nombre">Ordenar por nombre</option>
                    <option value="codigo">Ordenar por código</option>
                </select>
            </div>
            <label class="filtro-extra">
                <input type="checkbox" id="incluir-inactivas" style="width:16px;height:16px;cursor:pointer;">
                Incluir sedes inactivas
            </label>
            <div class="selector-pagina">
                <span>Mostrar:</span>
                <select id="elementos-por-pagina">
                    <option value="10">10 sedes</option>
                    <option value="20" selected>20 sedes</option>
                    <option value="50">50 sedes</option>
                    <option value="100">100 sedes</option>
                </select>
            </div>
        </div>

        <div id="sedes-content">
            <div class="estado-carga">
                <div class="spinner"></div>
                <div>Cargando sedes...</div>
            </div>
        </div>
    </div>

    <div class="modal-fondo" id="modalEditar">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <div>
                    <h2 id="modalTitulo">Editar sede</h2>
                    <p id="modalSubtitulo">&nbsp;</p>
                </div>
                <button type="button" class="modal-cerrar" onclick="cerrarEdicion()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div id="modalAviso" class="modal-aviso"></div>

            <form id="formEditar" onsubmit="return guardarEdicion(event)">
                <div class="modal-cuerpo">
                    <input type="hidden" id="f_id">

                    <fieldset class="modal-fieldset">
                        <legend>Identificación</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_codigo">Código</label>
                                <input type="text" id="f_codigo" required>
                            </div>
                            <div class="modal-campo">
                                <label for="f_codigo_externo">Código externo</label>
                                <input type="number" id="f_codigo_externo">
                            </div>
                            <div class="modal-campo ancho">
                                <label for="f_nombre">Nombre</label>
                                <input type="text" id="f_nombre" required>
                            </div>
                            <div class="modal-campo">
                                <label for="f_turno">Turno</label>
                                <input type="text" id="f_turno" placeholder="Diurno, Nocturno...">
                            </div>
                            <div class="modal-campo">
                                <label for="f_numero_aplicacion">N.º de aplicación</label>
                                <input type="text" id="f_numero_aplicacion">
                            </div>
                            <div class="modal-campo">
                                <label for="f_fecha_examen">Fecha de examen</label>
                                <input type="date" id="f_fecha_examen">
                            </div>
                            <div class="modal-campo">
                                <label class="modal-check"><input type="checkbox" id="f_activo"> Sede activa</label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Aplicación</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_aulas_previstas">Aulas previstas</label>
                                <input type="number" id="f_aulas_previstas" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_apoyo_requerido">Apoyo requerido</label>
                                <input type="number" id="f_apoyo_requerido" min="0">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Coordinador</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_coordinador_nombre">Nombre</label>
                                <input type="text" id="f_coordinador_nombre">
                            </div>
                            <div class="modal-campo">
                                <label for="f_coordinador_correo">Correo</label>
                                <input type="email" id="f_coordinador_correo">
                            </div>
                            <div class="modal-campo">
                                <label for="f_coordinador_cedula">Cédula</label>
                                <input type="text" id="f_coordinador_cedula">
                            </div>
                            <div class="modal-campo">
                                <label for="f_coordinador_universidad">Universidad</label>
                                <input type="text" id="f_coordinador_universidad">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Dotación de puestos</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_total_puestos">Total puestos</label>
                                <input type="number" id="f_total_puestos" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_minimo_una">Mínimo una</label>
                                <input type="number" id="f_minimo_una" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_aula_nombrados">Aula nombrados</label>
                                <input type="number" id="f_aula_nombrados" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_apoyo_nombrados">Apoyo nombrados</label>
                                <input type="number" id="f_apoyo_nombrados" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_puestos_ocupados">Puestos ocupados</label>
                                <input type="number" id="f_puestos_ocupados" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_puestos_libres">Puestos libres</label>
                                <input type="number" id="f_puestos_libres" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_una_nombrados">Una nombrados</label>
                                <input type="number" id="f_una_nombrados" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_una_faltan">Una faltan</label>
                                <input type="number" id="f_una_faltan" min="0">
                            </div>
                            <div class="modal-campo">
                                <label for="f_estado_dotacion">Estado</label>
                                <input type="text" id="f_estado_dotacion" placeholder="Sin iniciar, Pendiente, Completo...">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Contacto de la sede</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_director">Director</label>
                                <input type="text" id="f_director">
                            </div>
                            <div class="modal-campo">
                                <label for="f_contacto_conserje">Contacto conserje</label>
                                <input type="text" id="f_contacto_conserje">
                            </div>
                            <div class="modal-campo">
                                <label for="f_telefono">Teléfono</label>
                                <input type="text" id="f_telefono">
                            </div>
                            <div class="modal-campo">
                                <label for="f_correo">Correo</label>
                                <input type="email" id="f_correo">
                            </div>
                            <div class="modal-campo ancho">
                                <label for="f_direccion">Dirección</label>
                                <textarea id="f_direccion"></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Ubicación</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_provincia">Provincia</label>
                                <input type="text" id="f_provincia">
                            </div>
                            <div class="modal-campo">
                                <label for="f_canton">Cantón</label>
                                <input type="text" id="f_canton">
                            </div>
                            <div class="modal-campo">
                                <label for="f_distrito">Distrito</label>
                                <input type="text" id="f_distrito">
                            </div>
                            <div class="modal-campo">
                                <label class="modal-check"><input type="checkbox" id="f_en_gam"> Está en el GAM</label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Folletos del coordinador</legend>
                        <div class="modal-grid">
                            <div class="modal-campo">
                                <label for="f_coord_folleto_desde">Rango desde</label>
                                <input type="text" id="f_coord_folleto_desde">
                            </div>
                            <div class="modal-campo">
                                <label for="f_coord_folleto_hasta">Rango hasta</label>
                                <input type="text" id="f_coord_folleto_hasta">
                            </div>
                            <div class="modal-campo">
                                <label for="f_coord_folleto_adec_desde">Adecuación desde</label>
                                <input type="text" id="f_coord_folleto_adec_desde">
                            </div>
                            <div class="modal-campo">
                                <label for="f_coord_folleto_adec_hasta">Adecuación hasta</label>
                                <input type="text" id="f_coord_folleto_adec_hasta">
                            </div>
                            <div class="modal-campo">
                                <label class="modal-check"><input type="checkbox" id="f_coord_sin_folletos"> No hay folleto de coordinador para esta sede</label>
                            </div>
                            <div class="modal-campo">
                                <label class="modal-check"><input type="checkbox" id="f_materiales_adecuacion"> Tiene materiales de adecuación</label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="modal-fieldset">
                        <legend>Otros</legend>
                        <div class="modal-grid">
                            <div class="modal-campo ancho">
                                <label for="f_descripcion">Descripción</label>
                                <textarea id="f_descripcion"></textarea>
                            </div>
                            <div class="modal-campo ancho">
                                <label for="f_imagen_url">URL de imagen</label>
                                <input type="url" id="f_imagen_url">
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="modal-pie">
                    <button type="button" class="modal-boton cancelar" onclick="cerrarEdicion()">Cancelar</button>
                    <button type="submit" class="modal-boton guardar" id="botonGuardar">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const CSRF = <?= json_encode(token_csrf()) ?>;
        // Antes acá había un SHEET_ID y dos servicios de terceros
        // (opensheet.elk.sh y opensheet.vercel.app) por los que pasaban los
        // datos de contacto de cada centro educativo. Ahora los datos salen de
        // la base institucional y no salen del servidor de la UCR.
        const API_SEDES = '../api/sedes.php';

        let sedesData = [];
        let sedesFiltradas = [];
        let paginaActual = 1;
        let elementosPorPagina = 20;
        let sedesOrdenadas = [];

        // Lista de imágenes de respaldo de Unsplash
        const IMAGENES_POR_DEFECTO = [
            'https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-4.0.3&w=800&q=80',
            'https://images.unsplash.com/photo-1541336032412-2048a678540d?ixlib=rb-4.0.3&w=800&q=80',
            'https://images.unsplash.com/photo-1588072432836-e10032774350?ixlib=rb-4.0.3&w=800&q=80',
            'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&w=800&q=80',
            'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?ixlib=rb-4.0.3&w=800&q=80',
            'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?ixlib=rb-4.0.3&w=800&q=80'
        ];

        async function cargarSedes() {
            const container = document.getElementById('sedes-content');

            container.innerHTML = `
                <div class="estado-carga">
                    <div class="spinner"></div>
                    <div>Cargando sedes...</div>
                </div>
            `;

            try {
                // _ts evita que el navegador sirva una respuesta cacheada
                const incluirInactivas = document.getElementById('incluir-inactivas')?.checked ? '1' : '0';
                const response = await fetch(
                    `${API_SEDES}?accion=listar&incluir_inactivas=${incluirInactivas}&_ts=${Date.now()}`,
                    { cache: 'no-store' }
                );

                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);

                const datos = await response.json();

                if (!datos.success) {
                    throw new Error(datos.error || 'La consulta no devolvió datos');
                }

                sedesData = convertirDatos(datos.sedes);

                // Una respuesta vacía es tan sospechosa como un error de red:
                // no la damos por buena en silencio.
                if (sedesData.length === 0) {
                    throw new Error('No hay sedes cargadas en el sistema');
                }

                sedesFiltradas = [...sedesData];
                sedesOrdenadas = [...sedesData];

                mostrarFiltros();
                mostrarSedes();

            } catch (error) {
                console.error('Error cargando sedes:', error);
                // Ya no hay servicio de respaldo al que reintentar: si la base
                // no responde, se dice claramente en vez de girar en un bucle.
                mostrarErrorCompleto(error);
            }
        }

        function convertirDatos(filas) {
            if (!Array.isArray(filas)) return [];

            // El endpoint ya devuelve los campos con estos nombres y sin
            // espacios sobrantes, así que aquí solo queda asignar la imagen de
            // respaldo cuando la sede no tiene una propia.
            return filas.map((fila, index) => ({
                ...fila,
                ImagenPorDefecto: IMAGENES_POR_DEFECTO[index % IMAGENES_POR_DEFECTO.length]
            }));
        }

        function mostrarFiltros() {
            const filtrosContainer = document.getElementById('filtros');
            filtrosContainer.style.display = 'flex';

            // Configurar búsqueda en tiempo real
            document.getElementById('busqueda').addEventListener('input', function() {
                filtrarSedes(this.value);
            });

            // Configurar ordenamiento
            document.getElementById('ordenar-por').addEventListener('change', function() {
                ordenarSedes(this.value);
            });

            // Configurar elementos por página
            document.getElementById('elementos-por-pagina').addEventListener('change', function() {
                elementosPorPagina = parseInt(this.value);
                paginaActual = 1;
                mostrarSedes();
            });

            document.getElementById('incluir-inactivas').addEventListener('change', function() {
                paginaActual = 1;
                cargarSedes();
            });
        }

        function filtrarSedes(termino) {
            if (!termino) {
                sedesFiltradas = [...sedesData];
            } else {
                const terminoLower = termino.toLowerCase();
                sedesFiltradas = sedesData.filter(sede =>
                    (sede.nombre || '').toLowerCase().includes(terminoLower) ||
                    (sede.direccion || '').toLowerCase().includes(terminoLower) ||
                    (sede.director || '').toLowerCase().includes(terminoLower) ||
                    (sede.codigo || '').toLowerCase().includes(terminoLower) ||
                    (sede.coordinador_nombre || '').toLowerCase().includes(terminoLower) ||
                    (sede.turno || '').toLowerCase().includes(terminoLower) ||
                    (sede.formulas || '').toLowerCase().includes(terminoLower)
                );
            }

            // Aplicar ordenamiento actual a los resultados filtrados
            const criterioOrden = document.getElementById('ordenar-por').value;
            ordenarSedes(criterioOrden, false);
        }

        function ordenarSedes(criterio, resetearPaginacion = true) {
            if (resetearPaginacion) {
                paginaActual = 1;
            }

            sedesOrdenadas = [...sedesFiltradas];

            if (criterio === 'nombre') {
                sedesOrdenadas.sort((a, b) => (a.nombre || '').localeCompare(b.nombre || ''));
            } else if (criterio === 'codigo') {
                sedesOrdenadas.sort((a, b) => (a.codigo || '').localeCompare(b.codigo || '', undefined, { numeric: true }));
            }

            mostrarSedes();
        }

        function mostrarSedes() {
            const container = document.getElementById('sedes-content');
            
            // Calcular índices para la paginación
            const indiceInicio = (paginaActual - 1) * elementosPorPagina;
            const indiceFin = indiceInicio + elementosPorPagina;
            const sedesPagina = sedesOrdenadas.slice(indiceInicio, indiceFin);
            const totalPaginas = Math.ceil(sedesOrdenadas.length / elementosPorPagina);
            
            let html = `
                <div style="text-align: center; margin-bottom: 25px;">
                    <div class="contador-sedes">
                        📍 ${sedesOrdenadas.length} Sedes
                    </div>
                    <div style="margin-top: 10px;">
                        <button class="boton-reintentar" onclick="reiniciarCarga()" style="background: linear-gradient(135deg, #41ADE7, #2c5aa0);">
                            🔄 Recargar
                        </button>
                    </div>
                </div>
            `;
            
            if (sedesOrdenadas.length === 0) {
                html += `
                    <div class="estado-carga">
                        <div>🔍 No se encontraron sedes que coincidan con tu búsqueda</div>
                    </div>
                `;
            } else {
                // Información de paginación
                html += `
                    <div class="info-paginacion">
                        Mostrando sedes ${indiceInicio + 1} - ${Math.min(indiceFin, sedesOrdenadas.length)} de ${sedesOrdenadas.length}
                    </div>
                `;
                
                // Sedes de la página actual
                sedesPagina.forEach((sede, index) => {
                    const indiceGlobal = indiceInicio + index;

                    // Verificar si hay una imagen válida (no vacía y no de Google Drive)
                    const tieneImagenValida = sede.imagen_url &&
                                             sede.imagen_url.trim() !== '' &&
                                             !sede.imagen_url.includes('drive.google.com');

                    const imagenHTML = tieneImagenValida ? `
                        <img src="${sede.imagen_url}" alt="${sede.nombre}" class="imagen-sede"
                             onerror="this.style.display='none'"
                             loading="lazy">
                    ` : '';

                    const insignias = `
                        <div class="insignias">
                            ${sede.activo ? '<span class="insignia insignia-ok"><i class="fa-solid fa-circle-check"></i> Activa</span>' : '<span class="insignia insignia-baja"><i class="fa-solid fa-circle-xmark"></i> Inactiva</span>'}
                            ${sede.en_gam ? '<span class="insignia insignia-neutra">GAM</span>' : ''}
                            ${sede.turno ? `<span class="insignia insignia-neutra">${sede.turno}</span>` : ''}
                            ${sede.formulas ? `<span class="insignia insignia-neutra" title="Fórmula(s) de examen de las aulas"><i class="fa-solid fa-square-root-variable"></i> ${sede.formulas}</span>` : ''}
                            ${sede.coord_sin_folletos ? '<span class="insignia insignia-alerta">Sin folleto de coordinador</span>' : ''}
                            ${sede.materiales_adecuacion ? '<span class="insignia insignia-neutra"><i class="fa-solid fa-universal-access"></i> Adecuación</span>' : ''}
                            ${sede.estado_dotacion ? `<span class="insignia ${sede.puestos_libres > 0 ? 'insignia-alerta' : 'insignia-ok'}">${sede.estado_dotacion}</span>` : ''}
                            ${sede.puestos_libres > 0 ? `<span class="insignia insignia-alerta">${sede.puestos_libres} puesto(s) libre(s)</span>` : ''}
                        </div>
                    `;

                    html += `
                    <div class="acordeon" id="acordeon${indiceGlobal}">
                        <div class="acordeon-cabecera" onclick="toggleAcordeon('acordeon${indiceGlobal}')">
                            <div class="acordeon-cabecera-fila">
                                <div>
                                    <h3>${sede.nombre || 'Sin nombre'} <span class="acordeon-indicador">+</span></h3>
                                    <p>Sede ${sede.codigo || 'sin código'}${sede.numero_aplicacion ? ' · Aplicación ' + sede.numero_aplicacion : ''}</p>
                                    ${insignias}
                                </div>
                                <button type="button" class="boton-editar" onclick="event.stopPropagation(); abrirEdicion(${sede.id})">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </button>
                            </div>
                            <div class="acordeon-icono">+</div>
                        </div>
                        <div class="acordeon-contenido">
                            ${imagenHTML}

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">📍</div>
                                    <div class="info-text">
                                        <strong>Dirección</strong>
                                        <div>${sede.direccion || 'No especificada'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🗺️</div>
                                    <div class="info-text">
                                        <strong>Ubicación</strong>
                                        <div>${[sede.distrito, sede.canton, sede.provincia].filter(Boolean).join(', ') || 'No especificada'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">📞</div>
                                    <div class="info-text">
                                        <strong>Teléfono</strong>
                                        <div>${sede.telefono || 'No especificado'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">📧</div>
                                    <div class="info-text">
                                        <strong>Email</strong>
                                        <div>${sede.correo || 'No especificado'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">👨‍🏫</div>
                                    <div class="info-text">
                                        <strong>Director</strong>
                                        <div>${sede.director || 'No especificado'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🔑</div>
                                    <div class="info-text">
                                        <strong>Contacto Conserje</strong>
                                        <div>${sede.contacto_conserje || 'No especificado'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🧑‍💼</div>
                                    <div class="info-text">
                                        <strong>Coordinador</strong>
                                        <div>${sede.coordinador_nombre || 'No especificado'}${sede.coordinador_correo ? ' · ' + sede.coordinador_correo : ''}</div>
                                        <div>${sede.coordinador_cedula ? 'Céd. ' + sede.coordinador_cedula : ''}${sede.coordinador_universidad ? ' · ' + sede.coordinador_universidad : ''}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🪑</div>
                                    <div class="info-text">
                                        <strong>Puestos (ocupados / total)</strong>
                                        <div>${sede.puestos_ocupados} / ${sede.total_puestos} · Libres: ${sede.puestos_libres}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">📅</div>
                                    <div class="info-text">
                                        <strong>Fecha de examen</strong>
                                        <div>${sede.fecha_examen || 'No especificada'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🏫</div>
                                    <div class="info-text">
                                        <strong>Aulas previstas / apoyo</strong>
                                        <div>${sede.aulas_previstas} / ${sede.apoyo_requerido}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">📦</div>
                                    <div class="info-text">
                                        <strong>Folletos del coordinador</strong>
                                        <div>${sede.coord_folleto_desde || sede.coord_folleto_hasta ? `${sede.coord_folleto_desde || '?'} – ${sede.coord_folleto_hasta || '?'}` : 'Sin registrar'}</div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🆔</div>
                                    <div class="info-text">
                                        <strong>Código externo</strong>
                                        <div>${sede.codigo_externo ?? 'No especificado'}</div>
                                    </div>
                                </div>
                            </div>
                            ${sede.descripcion ? `
                            <div class="descripcion-sede">
                                <strong>📋 Descripción</strong>
                                <p>${sede.descripcion}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>`;
                });
                
                // Controles de paginación
                if (totalPaginas > 1) {
                    html += generarControlesPaginacion(totalPaginas);
                }
            }

            container.innerHTML = html;
        }

        function generarControlesPaginacion(totalPaginas) {
            let html = '<div class="paginacion">';
            
            // Botón anterior
            html += `
                <button class="boton-pagina" onclick="cambiarPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>
                    ← Anterior
                </button>
            `;
            
            // Páginas
            const paginasAMostrar = 5; // Número máximo de páginas a mostrar
            let inicioPagina = Math.max(1, paginaActual - Math.floor(paginasAMostrar / 2));
            let finPagina = Math.min(totalPaginas, inicioPagina + paginasAMostrar - 1);
            
            // Ajustar si estamos cerca del final
            if (finPagina - inicioPagina + 1 < paginasAMostrar) {
                inicioPagina = Math.max(1, finPagina - paginasAMostrar + 1);
            }
            
            // Primera página si no está incluida
            if (inicioPagina > 1) {
                html += `
                    <button class="boton-pagina" onclick="cambiarPagina(1)">1</button>
                    ${inicioPagina > 2 ? '<span>...</span>' : ''}
                `;
            }
            
            // Páginas numeradas
            for (let i = inicioPagina; i <= finPagina; i++) {
                html += `
                    <button class="boton-pagina ${i === paginaActual ? 'activa' : ''}" onclick="cambiarPagina(${i})">
                        ${i}
                    </button>
                `;
            }
            
            // Última página si no está incluida
            if (finPagina < totalPaginas) {
                html += `
                    ${finPagina < totalPaginas - 1 ? '<span>...</span>' : ''}
                    <button class="boton-pagina" onclick="cambiarPagina(${totalPaginas})">${totalPaginas}</button>
                `;
            }
            
            // Botón siguiente
            html += `
                <button class="boton-pagina" onclick="cambiarPagina(${paginaActual + 1})" ${paginaActual === totalPaginas ? 'disabled' : ''}>
                    Siguiente →
                </button>
            `;
            
            html += '</div>';
            return html;
        }

        function cambiarPagina(nuevaPagina) {
            paginaActual = nuevaPagina;
            mostrarSedes();
            // Desplazar hacia arriba para mejor UX
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function mostrarErrorCompleto(error) {
            const container = document.getElementById('sedes-content');
            
            container.innerHTML = `
                <div class="mensaje-error">
                    <h3>❌ No se pudieron cargar las sedes</h3>
                    <p><strong>Error:</strong> ${error.message}</p>

                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: left;">
                        <h4>Qué revisar</h4>
                        <ul>
                            <li>Que el catálogo esté cargado:
                                <code>php scripts/importar_csv.php sedes archivo.csv</code></li>
                            <li>Que <code>includes/config.php</code> exista y tenga la clave de la base.</li>
                            <li>El log de errores del servidor, donde queda el detalle real.</li>
                        </ul>
                    </div>

                    <div>
                        <button class="boton-reintentar" onclick="reiniciarCarga()">
                            🔄 Reintentar Conexión
                        </button>
                    </div>
                </div>
            `;
        }

        function reiniciarCarga() {
            paginaActual = 1;
            cargarSedes();
        }

        function descargarExcel() {
            window.location.href = `${API_SEDES}?accion=exportar`;
        }

        function descargarJson() {
            window.location.href = `${API_SEDES}?accion=exportarJson`;
        }

        // --- Actualizar desde Excel (dotación) -----------------------

        let ultimoResultadoDotacion = null;

        function togglePanelActualizar() {
            document.getElementById('panelActualizar').classList.toggle('abierto');
        }

        function etiquetaCampoDotacion(campo) {
            const etiquetas = {
                codigo_externo: 'Código externo', turno: 'Turno', fecha_examen: 'Fecha de examen',
                aulas_previstas: 'Aulas previstas', apoyo_requerido: 'Apoyo requerido',
                total_puestos: 'Total puestos', minimo_una: 'Mínimo una',
                aula_nombrados: 'Aula nombrados', apoyo_nombrados: 'Apoyo nombrados',
                puestos_ocupados: 'Puestos ocupados', puestos_libres: 'Puestos libres',
                una_nombrados: 'Una nombrados', una_faltan: 'Una faltan', estado_dotacion: 'Estado',
                coordinador_nombre: 'Coordinador', coordinador_correo: 'Correo coordinador',
                coordinador_cedula: 'Cédula coordinador', coordinador_universidad: 'Universidad coordinador',
            };
            return etiquetas[campo] || campo;
        }

        async function analizarDotacion() {
            const input = document.getElementById('archivoDotacion');
            const boton = document.getElementById('botonAnalizarDotacion');
            const resultado = document.getElementById('resultadoDotacion');

            if (!input.files.length) {
                resultado.innerHTML = `<p style="color:#991b1b; margin-top:1rem;">Elegí primero un archivo.</p>`;
                return;
            }

            boton.disabled = true;
            boton.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Analizando...`;
            resultado.innerHTML = '';

            try {
                const cuerpo = new FormData();
                cuerpo.append('accion', 'analizarDotacion');
                cuerpo.append('csrf', CSRF);
                cuerpo.append('archivo', input.files[0]);

                const respuesta = await fetch(API_SEDES, { method: 'POST', body: cuerpo });
                const datos = await respuesta.json();

                if (!datos.success) {
                    throw new Error(datos.error || 'No se pudo analizar el archivo');
                }

                ultimoResultadoDotacion = datos;
                pintarResultadoDotacion(datos, false);

            } catch (error) {
                resultado.innerHTML = `<p style="color:#991b1b; margin-top:1rem;">${error.message}</p>`;
            } finally {
                boton.disabled = false;
                boton.innerHTML = `<i class="fa-solid fa-magnifying-glass-chart"></i> Analizar cambios`;
            }
        }

        function pintarResultadoDotacion(datos, aplicado) {
            const resultado = document.getElementById('resultadoDotacion');

            let html = `
                <div class="resumen-dotacion">
                    <div class="resumen-dotacion-chips">
                        <span class="chip-dotacion">${datos.total} sedes en el archivo</span>
                        <span class="chip-dotacion">${datos.encontradas} encontradas</span>
                        <span class="chip-dotacion ${datos.conCambios > 0 ? 'alerta' : 'ok'}">${datos.conCambios} con cambios${aplicado ? ' (aplicados)' : ''}</span>
                        <span class="chip-dotacion">${datos.sinCambios} ya al día</span>
                        ${datos.huerfanas.length ? `<span class="chip-dotacion alerta">${datos.huerfanas.length} sin sede en la base</span>` : ''}
                    </div>
            `;

            if (datos.cambios.length) {
                html += `<div class="tabla-cambios-dotacion">`;
                datos.cambios.forEach(c => {
                    const camposHtml = Object.entries(c.diff).map(([campo, v]) =>
                        `<span class="campo-cambio"><b>${etiquetaCampoDotacion(campo)}:</b> ${v.antes ?? '(vacío)'} → ${v.despues ?? '(vacío)'}</span>`
                    ).join('');
                    html += `<div class="fila-cambio-sede"><strong>${c.codigo} — ${c.nombre}</strong>${camposHtml}</div>`;
                });
                html += `</div>`;
            }

            if (datos.huerfanas.length) {
                html += `
                    <details class="huerfanas-dotacion">
                        <summary>${datos.huerfanas.length} código(s) del archivo no coinciden con ninguna sede de la base</summary>
                        <div>${datos.huerfanas.join('<br>')}</div>
                    </details>
                `;
            }

            if (!aplicado && datos.conCambios > 0) {
                html += `
                    <button type="button" class="boton-aplicar-dotacion" id="botonAplicarDotacion" onclick="aplicarDotacion()">
                        <i class="fa-solid fa-check"></i> Aplicar estos ${datos.conCambios} cambio(s)
                    </button>
                `;
            } else if (aplicado) {
                html += `<p style="color:#065f46; font-weight:700; margin-top:1rem;"><i class="fa-solid fa-circle-check"></i> Cambios aplicados. Recargá la lista de sedes para verlos.</p>`;
            }

            html += `</div>`;
            resultado.innerHTML = html;
        }

        async function aplicarDotacion() {
            const input = document.getElementById('archivoDotacion');
            const boton = document.getElementById('botonAplicarDotacion');

            if (!input.files.length) return;

            if (boton) {
                boton.disabled = true;
                boton.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Aplicando...`;
            }

            try {
                const cuerpo = new FormData();
                cuerpo.append('accion', 'importarDotacion');
                cuerpo.append('csrf', CSRF);
                cuerpo.append('archivo', input.files[0]);

                const respuesta = await fetch(API_SEDES, { method: 'POST', body: cuerpo });
                const datos = await respuesta.json();

                if (!datos.success) {
                    throw new Error(datos.error || 'No se pudo aplicar el cambio');
                }

                pintarResultadoDotacion(datos, true);
                cargarSedes();

            } catch (error) {
                document.getElementById('resultadoDotacion').insertAdjacentHTML(
                    'beforeend',
                    `<p style="color:#991b1b; margin-top:1rem;">${error.message}</p>`
                );
                if (boton) {
                    boton.disabled = false;
                    boton.innerHTML = `<i class="fa-solid fa-check"></i> Aplicar cambios`;
                }
            }
        }

        // --- Edición ------------------------------------------------

        const CAMPOS_TEXTO_MODAL = [
            'codigo', 'codigo_externo', 'nombre', 'turno', 'numero_aplicacion', 'fecha_examen',
            'aulas_previstas', 'apoyo_requerido',
            'total_puestos', 'minimo_una', 'aula_nombrados', 'apoyo_nombrados',
            'puestos_ocupados', 'puestos_libres', 'una_nombrados', 'una_faltan', 'estado_dotacion',
            'coordinador_nombre', 'coordinador_correo', 'coordinador_cedula', 'coordinador_universidad',
            'director', 'contacto_conserje', 'telefono', 'correo', 'direccion',
            'provincia', 'canton', 'distrito',
            'coord_folleto_desde', 'coord_folleto_hasta', 'coord_folleto_adec_desde', 'coord_folleto_adec_hasta',
            'descripcion', 'imagen_url',
        ];
        const CAMPOS_BOOL_MODAL = ['activo', 'en_gam', 'coord_sin_folletos', 'materiales_adecuacion'];

        function abrirEdicion(id) {
            const sede = sedesData.find(s => s.id === id);
            if (!sede) return;

            document.getElementById('f_id').value = sede.id;
            document.getElementById('modalTitulo').textContent = sede.nombre || 'Editar sede';
            document.getElementById('modalSubtitulo').textContent = `Sede ${sede.codigo}`;

            CAMPOS_TEXTO_MODAL.forEach(campo => {
                const el = document.getElementById('f_' + campo);
                if (el) el.value = sede[campo] ?? '';
            });
            CAMPOS_BOOL_MODAL.forEach(campo => {
                const el = document.getElementById('f_' + campo);
                if (el) el.checked = !!sede[campo];
            });

            ocultarAvisoModal();
            document.getElementById('modalEditar').classList.add('abierto');
        }

        function cerrarEdicion() {
            document.getElementById('modalEditar').classList.remove('abierto');
        }

        function ocultarAvisoModal() {
            const aviso = document.getElementById('modalAviso');
            aviso.className = 'modal-aviso';
            aviso.textContent = '';
        }

        function mostrarAvisoModal(mensaje, tipo) {
            const aviso = document.getElementById('modalAviso');
            aviso.className = `modal-aviso mostrar ${tipo}`;
            aviso.textContent = mensaje;
        }

        async function guardarEdicion(evento) {
            evento.preventDefault();

            const boton = document.getElementById('botonGuardar');
            boton.disabled = true;
            boton.textContent = 'Guardando...';
            ocultarAvisoModal();

            const cuerpo = { accion: 'guardar', csrf: CSRF, id: document.getElementById('f_id').value };
            CAMPOS_TEXTO_MODAL.forEach(campo => {
                cuerpo[campo] = document.getElementById('f_' + campo).value;
            });
            CAMPOS_BOOL_MODAL.forEach(campo => {
                cuerpo[campo] = document.getElementById('f_' + campo).checked ? '1' : '0';
            });

            try {
                const respuesta = await fetch(API_SEDES, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(cuerpo),
                });
                const datos = await respuesta.json();

                if (!datos.success) {
                    throw new Error(datos.error || 'No se pudo guardar el cambio');
                }

                // Reemplaza la sede editada en memoria y vuelve a pintar, sin
                // recargar todo el catálogo contra el servidor.
                const indice = sedesData.findIndex(s => s.id === datos.sede.id);
                if (indice !== -1) {
                    sedesData[indice] = { ...sedesData[indice], ...datos.sede };
                }
                filtrarSedes(document.getElementById('busqueda').value);

                mostrarAvisoModal('Cambios guardados.', 'ok');
                setTimeout(cerrarEdicion, 700);

            } catch (error) {
                mostrarAvisoModal(error.message, 'error');
            } finally {
                boton.disabled = false;
                boton.textContent = 'Guardar cambios';
            }

            return false;
        }

        document.getElementById('modalEditar').addEventListener('click', function(evento) {
            if (evento.target === this) cerrarEdicion();
        });

        document.addEventListener('keydown', function(evento) {
            if (evento.key === 'Escape') cerrarEdicion();
        });

        function toggleAcordeon(id) {
            const acordeon = document.getElementById(id);
            const indicador = acordeon.querySelector('.acordeon-indicador');
            
            acordeon.classList.toggle('abierto');
            
            if (acordeon.classList.contains('abierto')) {
                indicador.textContent = '-';
            } else {
                indicador.textContent = '+';
            }
        }

        document.addEventListener('DOMContentLoaded', cargarSedes);
        
        window.cargarSedes = cargarSedes;
        window.reiniciarCarga = reiniciarCarga;
        window.toggleAcordeon = toggleAcordeon;
        window.filtrarSedes = filtrarSedes;
        window.ordenarSedes = ordenarSedes;
        window.cambiarPagina = cambiarPagina;
        window.descargarExcel = descargarExcel;
        window.descargarJson = descargarJson;
        window.abrirEdicion = abrirEdicion;
        window.cerrarEdicion = cerrarEdicion;
        window.guardarEdicion = guardarEdicion;
        window.togglePanelActualizar = togglePanelActualizar;
        window.analizarDotacion = analizarDotacion;
        window.aplicarDotacion = aplicarDotacion;
    </script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>