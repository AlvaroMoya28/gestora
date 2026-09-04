<?php
/**
 * Módulo abierto a todo el personal con cuenta.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("tickets", '../');

// Lo que decide qué pantalla se pinta. Un desarrollador puede alternar entre
// las dos (ver el interruptor más abajo); cualquier otro rol ve siempre la
// que le corresponde.
$esAdminReal    = es_admin();
$esDesarrollador = es_desarrollador();

$datosYo = json_encode([
    'nombre'          => $yo['nombre'],
    'rol'             => $yo['rol'],
    'esAdmin'         => $esAdminReal,
    'esDesarrollador' => $esDesarrollador,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Tickets de Avería · UCR</title>
  <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Las mismas variables que el resto del sistema, para que todo se vea
           como una sola cosa. Si cambiás colores, cambiá acá y no en cada regla. */
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
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
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

        .app { max-width: 1200px; margin: 0 auto; }

        h1, h2, h3 { font-weight: 600; letter-spacing: -0.02em; }

        /* ===== ENCABEZADO ===== */

        .hero { text-align: center; margin-bottom: 1.25rem; }

        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.8rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.35rem;
        }

        .hero p { color: var(--gray-600); }

        /* ===== INTERRUPTOR DE VISTA (solo desarrollador) ===== */

        .vista-switch {
            display: none;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .vista-switch.visible { display: flex; }

        .vista-switch button {
            background: #fff;
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
        }

        .vista-switch button.activo {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* ===== TARJETAS ===== */

        .tarjeta {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .tarjeta h2 {
            font-size: 1.05rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-700);
        }

        /* ===== FORMULARIO ===== */

        .campos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .campo { display: flex; flex-direction: column; gap: 0.35rem; }
        .campo.ancho { grid-column: 1 / -1; }

        label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--gray-600);
        }

        label .requerido { color: var(--danger); }

        input, select, textarea {
            font-family: inherit;
            font-size: 0.9rem;
            padding: 0.6rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            background: #fff;
            color: var(--gray-800);
            width: 100%;
        }

        input:focus, select:focus, textarea:focus {
            outline: 2px solid var(--secondary);
            outline-offset: -1px;
            border-color: var(--primary-light);
        }

        textarea { resize: vertical; min-height: 90px; }

        .acciones {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.25rem;
            flex-wrap: wrap;
        }

        button {
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.65rem 1.4rem;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover { background: var(--primary-dark); }
        .btn-primario:disabled { background: var(--gray-400); cursor: not-allowed; }

        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover { background: var(--gray-200); }

        .btn-peligro { background: #fee2e2; color: #991b1b; }
        .btn-peligro:hover { background: #fecaca; }

        .btn-chico { padding: 0.4rem 0.8rem; font-size: 0.8rem; }

        /* ===== FILTROS Y CONTADORES ===== */

        .filtros {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filtros .campo { min-width: 160px; flex: 1; }

        .contadores {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .contador {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.9rem;
            border-radius: var(--radius-2xl);
            font-size: 0.8125rem;
            font-weight: 600;
            background: var(--gray-100);
            color: var(--gray-600);
            border: 2px solid transparent;
            cursor: pointer;
            font-family: inherit;
            transition: transform .12s, border-color .12s;
        }

        .contador:hover { transform: translateY(-1px); }
        .contador.activo { border-color: currentColor; }

        .contador .numero { font-size: 1rem; }

        .contador.abierto    { background: #fee2e2; color: #991b1b; }
        .contador.en_proceso { background: #fef3c7; color: #92400e; }
        .contador.resuelto   { background: #d1fae5; color: #065f46; }
        .contador.cerrado    { background: var(--gray-100); color: var(--gray-500); }

        /* ===== PAGINACIÓN ===== */

        .paginacion {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-top: 1rem; flex-wrap: wrap;
        }
        .paginacion .paginas { display: flex; gap: 6px; align-items: center; }
        .pag-btn {
            border: 1px solid var(--gray-300); background: #fff;
            border-radius: var(--radius); padding: 0.4rem 0.75rem; cursor: pointer;
            font-family: inherit; font-size: 0.8rem; font-weight: 600; color: var(--gray-700);
        }
        .pag-btn:disabled { opacity: .4; cursor: not-allowed; }
        .pag-btn:not(:disabled):hover { background: var(--gray-100); }

        /* ===== LISTA DE TICKETS ===== */

        .ticket {
            border: 1px solid var(--gray-200);
            border-left: 4px solid var(--gray-300);
            border-radius: var(--radius-md);
            padding: 1rem 1.15rem;
            margin-bottom: 0.85rem;
            background: #fff;
            cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .ticket:hover { border-color: var(--secondary); box-shadow: var(--shadow-sm); }

        .ticket.critica { border-left-color: #dc2626; }
        .ticket.alta    { border-left-color: var(--warning); }
        .ticket.media   { border-left-color: var(--info); }
        .ticket.baja    { border-left-color: var(--gray-300); }
        .ticket.sin_asignar { border-left-color: var(--gray-200); }

        .ticket-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .ticket-folio {
            font-family: ui-monospace, 'Cascadia Code', monospace;
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .ticket-titulo {
            font-weight: 600; margin: 0.15rem 0 0.4rem;
            display: block; width: 100%; text-align: left; font-family: inherit; font-size: inherit;
            background: none; border: none; padding: 0; color: inherit; cursor: pointer;
        }
        .ticket-titulo:hover, .ticket-titulo:focus-visible { text-decoration: underline; }
        .ticket-titulo:focus-visible { outline: 2px solid var(--secondary); outline-offset: 2px; }

        .ticket-meta {
            display: flex;
            gap: 0.9rem;
            flex-wrap: wrap;
            font-size: 0.8125rem;
            color: var(--gray-500);
        }

        .ticket-descripcion {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: 0.6rem;
            white-space: pre-wrap;
        }

        .ticket-etiquetas {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.4rem;
        }

        .ticket-acciones {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .selector-prioridad {
            font-size: 0.75rem;
            padding: 0.3rem 0.5rem;
            width: auto;
        }

        .etiqueta {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-2xl);
            white-space: nowrap;
        }

        .etiqueta.abierto      { background: #fee2e2; color: #991b1b; }
        .etiqueta.en_proceso   { background: #fef3c7; color: #92400e; }
        .etiqueta.resuelto     { background: #d1fae5; color: #065f46; }
        .etiqueta.cerrado      { background: var(--gray-100); color: var(--gray-500); }

        .etiqueta.p-critica    { background: #fee2e2; color: #991b1b; }
        .etiqueta.p-alta       { background: #fef3c7; color: #92400e; }
        .etiqueta.p-media      { background: #dbeafe; color: #1e40af; }
        .etiqueta.p-baja       { background: var(--gray-100); color: var(--gray-600); }
        .etiqueta.p-sin_asignar { background: var(--gray-100); color: var(--gray-400); font-style: italic; }

        /* ===== AVISOS ===== */

        .aviso {
            padding: 0.9rem 1.1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
        }

        .aviso.exito { background: #d1fae5; color: #065f46; display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; display: block; }

        .vacio {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--gray-400);
        }

        .vacio i { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; }

        /* ===== TARJETA DE PERSONA (igual que en Funcionarios) ===== */

        .persona-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1.1rem 1.25rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

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

        .persona-datos { flex: 1; min-width: 0; }

        .persona-etiqueta {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-400);
            margin-bottom: 0.15rem;
        }

        .persona-nombre { font-weight: 600; color: var(--gray-800); margin-bottom: 0.35rem; }

        .persona-contacto { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.875rem; }

        .persona-contacto a {
            color: var(--gray-600);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .persona-contacto a:hover { color: var(--primary); text-decoration: underline; }
        .persona-contacto i { color: var(--gray-400); width: 14px; flex-shrink: 0; }

        .persona-contacto .extension,
        .persona-contacto .sin-dato {
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .persona-contacto .sin-dato { color: var(--gray-400); font-style: italic; }

        /* ===== MODAL DE DETALLE ===== */

        .modal-fondo {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.55);
            align-items: flex-start;
            justify-content: center;
            padding: 3vh 1rem;
            overflow-y: auto;
            z-index: 50;
        }

        .modal-fondo.visible { display: flex; }

        .modal {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 640px;
            width: 100%;
            padding: 1.5rem;
        }

        .modal-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .modal-cerrar {
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 0.4rem 0.6rem;
            border-radius: var(--radius);
        }

        .modal h3 { font-size: 1.05rem; margin: 1.25rem 0 0.6rem; color: var(--gray-700); }
        .modal h3:first-of-type { margin-top: 0; }

        .comentario {
            border-bottom: 1px solid var(--gray-100);
            padding: 0.6rem 0;
            font-size: 0.875rem;
        }

        .comentario:last-child { border-bottom: none; }

        .comentario-meta {
            display: flex;
            justify-content: space-between;
            color: var(--gray-500);
            font-size: 0.75rem;
            margin-bottom: 0.15rem;
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
            .tarjeta { padding: 1.15rem; }
            .modal { padding: 1.15rem; }
        }
    </style>
</head>
<body>
    <div class="app">

        <div class="barra-sesion">
            <span><i aria-hidden="true" class="fa-regular fa-user"></i> <?= h($yo['nombre']) ?></span>
            <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
        </div>

        <div class="hero">
            <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
                <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
            </div>
            <h1>Ticket de Avería</h1>
            <p>Reporte y seguimiento de averías de soporte informático dentro del PAA</p>
        </div>

        <a href="guia.php" class="tarjeta" style="display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit;margin-bottom:1rem;">
            <div style="width:42px;height:42px;border-radius:50%;background:var(--secondary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i aria-hidden="true" class="fa-solid fa-circle-play" style="font-size:1.2rem;"></i>
            </div>
            <div>
                <strong style="color:var(--gray-800);">¿Primera vez usando Tickets?</strong><br>
                <span style="font-size:.85rem;color:var(--gray-500);">Vea la guía con el video paso a paso y las preguntas frecuentes.</span>
            </div>
        </a>

        <div class="vista-switch" id="vistaSwitch">
            <button type="button" class="btn-chico" data-vista="funcionario" id="btnVistaFuncionario">
                <i aria-hidden="true" class="fa-regular fa-user"></i> Ver como funcionario
            </button>
            <button type="button" class="btn-chico" data-vista="admin" id="btnVistaAdmin">
                <i aria-hidden="true" class="fa-solid fa-user-shield"></i> Ver como administrador
            </button>
        </div>

        <!-- ===== FORMULARIO DE REPORTE (vista funcionario) ===== -->
        <div class="tarjeta" id="tarjetaForm">
            <h2><i aria-hidden="true" class="fa-solid fa-circle-plus"></i> Reportar una avería</h2>

            <div id="aviso" class="aviso"></div>

            <form id="formTicket">
                <div class="campos">

                    <div class="campo">
                        <label for="categoria">Tipo de avería</label>
                        <select id="categoria" name="categoria_id" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>

                    <div class="campo ancho">
                        <label for="titulo">Resumen <span class="requerido">*</span></label>
                        <input type="text" id="titulo" name="titulo" maxlength="200" required
                               placeholder="Ej: El equipo no enciende">
                    </div>

                    <div class="campo ancho">
                        <label for="descripcion">Descripción <span class="requerido">*</span></label>
                        <textarea id="descripcion" name="descripcion" required
                                  placeholder="Describe con más detalle qué pasó."></textarea>
                    </div>
                </div>

                <div class="acciones">
                    <button type="submit" class="btn-primario" id="btnEnviar">
                        <i aria-hidden="true" class="fa-solid fa-paper-plane"></i> Reportar avería
                    </button>
                    <button type="reset" class="btn-secundario">
                        <i aria-hidden="true" class="fa-solid fa-eraser"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- ===== CREAR A NOMBRE DE ALGUIEN (solo administración/desarrollo) ===== -->
        <div class="tarjeta" id="tarjetaCrearAdmin" style="display:none;">
            <button type="button" class="btn-secundario" id="btnAbrirCrearAdmin">
                <i aria-hidden="true" class="fa-solid fa-user-plus"></i> Crear ticket para un funcionario
            </button>

            <form id="formTicketAdmin" style="display:none;margin-top:1.1rem;">
                <div id="avisoAdmin" class="aviso"></div>

                <div class="campos">
                    <div class="campo ancho">
                        <label for="usuarioTicketAdmin">Funcionario <span class="requerido">*</span></label>
                        <select id="usuarioTicketAdmin" name="usuario_id" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="categoriaAdmin">Tipo de avería</label>
                        <select id="categoriaAdmin" name="categoria_id" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>

                    <div class="campo ancho">
                        <label for="tituloAdmin">Resumen <span class="requerido">*</span></label>
                        <input type="text" id="tituloAdmin" name="titulo" maxlength="200" required
                               placeholder="Ej: El equipo no enciende">
                    </div>

                    <div class="campo ancho">
                        <label for="descripcionAdmin">Descripción <span class="requerido">*</span></label>
                        <textarea id="descripcionAdmin" name="descripcion" required
                                  placeholder="Describe con más detalle qué pasó."></textarea>
                    </div>
                </div>

                <div class="acciones">
                    <button type="submit" class="btn-primario" id="btnEnviarAdmin">
                        <i aria-hidden="true" class="fa-solid fa-paper-plane"></i> Reportar avería
                    </button>
                    <button type="reset" class="btn-secundario" id="btnCerrarCrearAdmin">
                        <i aria-hidden="true" class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                </div>
            </form>
        </div>

        <!-- ===== LISTADO ===== -->
        <div class="tarjeta">
            <h2 id="tituloLista"><i aria-hidden="true" class="fa-solid fa-list-check"></i> Averías reportadas</h2>

            <div class="contadores" id="contadores"></div>

            <div class="filtros">
                <div class="campo">
                    <label for="filtroEstado">Estado</label>
                    <select id="filtroEstado">
                        <option value="">Todos</option>
                        <option value="abierto">Abierto</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="resuelto">Resuelto</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="filtroPrioridad">Prioridad</label>
                    <select id="filtroPrioridad">
                        <option value="">Todas</option>
                        <option value="critica">Crítica</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>

                <div class="campo" id="campoOrden">
                    <label for="filtroOrden">Orden</label>
                    <select id="filtroOrden">
                        <option value="recientes">Más recientes primero</option>
                        <option value="antiguos">Más antiguos primero</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="filtroBusqueda">Buscar</label>
                    <input type="search" id="filtroBusqueda" placeholder="Folio, texto, persona...">
                </div>

                <button class="btn-secundario" id="btnRefrescar">
                    <i aria-hidden="true" class="fa-solid fa-rotate"></i> Refrescar
                </button>

                <button class="btn-peligro" id="btnLimpiarCerrados" style="display:none;">
                    <i aria-hidden="true" class="fa-regular fa-trash-can"></i> Limpiar cerrados
                </button>
            </div>

            <div id="listaTickets" style="margin-top:1.25rem;">
                <div class="vacio"><i aria-hidden="true" class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
            </div>
            <div class="paginacion" id="paginacionTickets"></div>
        </div>
    </div>

    <!-- ===== MODAL DE DETALLE ===== -->
    <div class="modal-fondo" id="modalFondo">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
            <div class="modal-cabecera">
                <div>
                    <div class="ticket-folio" id="modalFolio"></div>
                    <h2 id="modalTitulo" style="margin-top:0.2rem;" tabindex="-1"></h2>
                </div>
                <button type="button" class="modal-cerrar" id="btnCerrarModal" aria-label="Cerrar detalle del ticket">
                    <i aria-hidden="true" class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="modalAviso" class="aviso"></div>

            <div id="modalCuerpo"></div>
        </div>
    </div>

<script>
// ===================================================================
// Capa de datos: todo pasa por el endpoint, nunca por Google Sheets.
// ===================================================================
const API = '../api/tickets.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

// Quién soy, calculado del lado del servidor. `esDesarrollador` habilita el
// interruptor de vista; para cualquier otro rol la vista queda fija.
const YO = <?= $datosYo ?>;

// Vista activa: 'funcionario' o 'admin'. Para un desarrollador se recuerda
// entre recargas; para cualquier otro rol siempre es la que le corresponde.
let vistaActiva = YO.esAdmin ? 'admin' : 'funcionario';
if (YO.esDesarrollador) {
    const guardada = localStorage.getItem('ticketsVista');
    vistaActiva = (guardada === 'funcionario' || guardada === 'admin') ? guardada : 'admin';
}

let catalogos = { categorias: [] };
let ticketActual = null; // folio del ticket abierto en el modal
let paginaTickets = 1;

// ===================================================================
// Arranque
// ===================================================================
document.addEventListener('DOMContentLoaded', () => {
    if (YO.esDesarrollador) {
        document.getElementById('vistaSwitch').classList.add('visible');
        document.getElementById('btnVistaFuncionario').addEventListener('click', () => cambiarVista('funcionario'));
        document.getElementById('btnVistaAdmin').addEventListener('click', () => cambiarVista('admin'));
    }

    aplicarVista();
    cargarCatalogos();
    cargarTickets();

    document.getElementById('formTicket').addEventListener('submit', enviarTicket);
    document.getElementById('formTicketAdmin').addEventListener('submit', enviarTicketAdmin);
    document.getElementById('btnAbrirCrearAdmin').addEventListener('click', () => {
        document.getElementById('formTicketAdmin').style.display = '';
        document.getElementById('btnAbrirCrearAdmin').style.display = 'none';
    });
    document.getElementById('btnCerrarCrearAdmin').addEventListener('click', () => {
        document.getElementById('formTicketAdmin').style.display = 'none';
        document.getElementById('btnAbrirCrearAdmin').style.display = '';
    });
    document.getElementById('btnRefrescar').addEventListener('click', cargarTickets);
    document.getElementById('btnLimpiarCerrados').addEventListener('click', limpiarCerrados);
    document.getElementById('filtroEstado').addEventListener('change', filtrarDesdeInicio);
    document.getElementById('filtroPrioridad').addEventListener('change', filtrarDesdeInicio);
    document.getElementById('filtroOrden').addEventListener('change', filtrarDesdeInicio);
    document.getElementById('btnCerrarModal').addEventListener('click', cerrarModal);
    document.getElementById('modalFondo').addEventListener('click', (e) => {
        if (e.target.id === 'modalFondo') cerrarModal();
    });

    // Se espera a que la persona deje de escribir antes de consultar, para no
    // mandar una petición por cada tecla.
    let temporizador;
    document.getElementById('filtroBusqueda').addEventListener('input', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(filtrarDesdeInicio, 350);
    });
});

/** Cualquier cambio de filtro vuelve a la página 1: si no, filtrar desde la
 *  página 7 podría mostrar «no hay resultados» sin que en verdad no los haya. */
function filtrarDesdeInicio() {
    paginaTickets = 1;
    cargarTickets();
}

function cambiarVista(vista) {
    vistaActiva = vista;
    if (YO.esDesarrollador) localStorage.setItem('ticketsVista', vista);
    aplicarVista();
    paginaTickets = 1;
    cargarTickets();
}

/** Muestra/oculta lo que corresponde a cada pantalla. */
function aplicarVista() {
    const esAdmin = vistaActiva === 'admin';

    document.getElementById('tarjetaForm').style.display = esAdmin ? 'none' : '';
    document.getElementById('tarjetaCrearAdmin').style.display = esAdmin ? '' : 'none';
    document.getElementById('campoOrden').style.display = esAdmin ? '' : 'none';
    document.getElementById('btnLimpiarCerrados').style.display = esAdmin ? '' : 'none';
    document.getElementById('tituloLista').innerHTML = esAdmin
        ? '<i aria-hidden="true" class="fa-solid fa-list-check"></i> Todas las averías'
        : '<i aria-hidden="true" class="fa-solid fa-list-check"></i> Mis averías';

    if (YO.esDesarrollador) {
        document.getElementById('btnVistaFuncionario').classList.toggle('activo', !esAdmin);
        document.getElementById('btnVistaAdmin').classList.toggle('activo', esAdmin);
    }
}

// ===================================================================
// Catálogo de categorías para el selector
// ===================================================================
async function cargarCatalogos() {
    try {
        const respuesta = await fetch(`${API}?accion=catalogos`, { cache: 'no-store' });
        const datos = await respuesta.json();

        if (!datos.success) throw new Error(datos.error || 'No se pudieron cargar los catálogos');

        catalogos = datos;

        const opcionesCategoria = datos.categorias.map(c =>
            `<option value="${c.id}">${escapar(c.nombre)}</option>`
        ).join('');
        document.getElementById('categoria').innerHTML = opcionesCategoria;

        // El creador para administración es un formulario aparte (ver
        // tarjetaCrearAdmin): solo lo usa quien administra, así que el
        // servidor solo manda `datos.usuarios` en ese caso.
        if (YO.esAdmin && datos.usuarios && datos.usuarios.length) {
            document.getElementById('categoriaAdmin').innerHTML = opcionesCategoria;
            document.getElementById('usuarioTicketAdmin').innerHTML = datos.usuarios.map(u =>
                `<option value="${u.id}">${escapar(u.nombre)}${u.rol === 'extraordinario' ? ' (Personal Extraordinario)' : ''}</option>`
            ).join('');
        }

    } catch (error) {
        mostrarAviso('No se pudieron cargar las categorías: ' + error.message, 'error');
    }
}

// ===================================================================
// Crear un ticket
// ===================================================================
async function enviarTicket(evento) {
    // Sin esto el navegador recarga la página y se pierde todo.
    evento.preventDefault();

    const boton = document.getElementById('btnEnviar');
    boton.disabled = true;

    const formulario = new FormData(evento.target);
    const cuerpo = Object.fromEntries(formulario.entries());
    cuerpo.accion = 'crear';
    cuerpo.csrf = CSRF;

    try {
        const respuesta = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(cuerpo)
        });

        const datos = await respuesta.json();

        if (!datos.success) throw new Error(datos.error || 'No se pudo registrar el ticket');

        evento.target.reset();
        cargarTickets();

        // La confirmación va en una ventana con el foco adentro, y no en el
        // aviso de arriba del formulario: ese quedaba ANTES de todo lo que se
        // acababa de llenar, así que para leerlo había que devolverse por todo
        // el formulario y no se sabía si la avería se había reportado o no.
        PaaUI.exito(
            `La avería quedó reportada con el folio ${datos.folio}. ` +
            `Guardá ese folio: con él se le da seguimiento.`,
            { titulo: 'Avería reportada' }
        );

    } catch (error) {
        PaaUI.error('No se pudo reportar la avería: ' + error.message);
    } finally {
        boton.disabled = false;
    }
}

/**
 * Igual que enviarTicket(), pero desde el formulario de administración: acá
 * SIEMPRE viaja `usuario_id`, porque el ticket es para el funcionario
 * seleccionado, no para quien lo está registrando.
 */
async function enviarTicketAdmin(evento) {
    evento.preventDefault();

    const boton = document.getElementById('btnEnviarAdmin');
    boton.disabled = true;

    const formulario = new FormData(evento.target);
    const cuerpo = Object.fromEntries(formulario.entries());
    cuerpo.accion = 'crear';
    cuerpo.csrf = CSRF;

    try {
        const respuesta = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(cuerpo)
        });

        const datos = await respuesta.json();

        if (!datos.success) throw new Error(datos.error || 'No se pudo registrar el ticket');

        evento.target.reset();
        cargarTickets();
        PaaUI.exito(`La avería quedó reportada con el folio ${datos.folio}.`,
                    { titulo: 'Avería reportada' });

    } catch (error) {
        PaaUI.error('No se pudo reportar la avería: ' + error.message);
    } finally {
        boton.disabled = false;
    }
}

// ===================================================================
// Listado
// ===================================================================
async function cargarTickets() {
    const contenedor = document.getElementById('listaTickets');
    contenedor.innerHTML = '<div class="vacio"><i aria-hidden="true" class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';

    // URLSearchParams arma la cadena y escapa los valores; concatenar a mano
    // se rompe con un espacio o una tilde en la búsqueda.
    const parametros = new URLSearchParams({ accion: 'listar', pagina: paginaTickets });
    if (YO.esDesarrollador) parametros.set('vista', vistaActiva);

    const estado = document.getElementById('filtroEstado').value;
    const prioridad = document.getElementById('filtroPrioridad').value;
    const orden = document.getElementById('filtroOrden').value;
    const busqueda = document.getElementById('filtroBusqueda').value.trim();

    if (estado)    parametros.set('estado', estado);
    if (prioridad) parametros.set('prioridad', prioridad);
    if (orden)     parametros.set('orden', orden);
    if (busqueda)  parametros.set('q', busqueda);

    try {
        const respuesta = await fetch(`${API}?${parametros}`, { cache: 'no-store' });
        const datos = await respuesta.json();

        if (!datos.success) throw new Error(datos.error || 'No se pudo consultar');

        pintarContadores(datos.contadores, estado);
        pintarTickets(datos.tickets);
        dibujarPaginacionTickets(datos);

    } catch (error) {
        contenedor.innerHTML =
            `<div class="vacio"><i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i>
             No se pudieron cargar los tickets.<br><small>${escapar(error.message)}</small></div>`;
    }
}

/**
 * Las pastillas son también el filtro de estado: tocar una lo aplica, y
 * tocar la ya activa lo quita (vuelve a la vista por defecto, que ya de por
 * sí no muestra cerrados — ver la nota en api/tickets.php:listar()).
 */
function pintarContadores(contadores, estadoActivo) {
    const etiquetas = {
        abierto: 'Abiertos',
        en_proceso: 'En proceso',
        resuelto: 'Resueltos',
        cerrado: 'Cerrados'
    };

    document.getElementById('contadores').innerHTML = Object.entries(etiquetas).map(
        ([clave, texto]) => `
            <button type="button" class="contador ${clave} ${clave === estadoActivo ? 'activo' : ''}"
                    data-estado="${clave}">
                <span class="numero">${contadores[clave] ?? 0}</span> ${texto}
            </button>`
    ).join('');

    document.querySelectorAll('#contadores .contador').forEach(boton => {
        boton.addEventListener('click', () => {
            const sel = document.getElementById('filtroEstado');
            sel.value = sel.value === boton.dataset.estado ? '' : boton.dataset.estado;
            filtrarDesdeInicio();
        });
    });
}

function dibujarPaginacionTickets(datos) {
    const cont = document.getElementById('paginacionTickets');
    const desde = datos.total === 0 ? 0 : (datos.pagina - 1) * datos.porPagina + 1;
    const hasta = Math.min(datos.pagina * datos.porPagina, datos.total);

    cont.innerHTML = `
        <span style="font-size:.8rem; color:var(--gray-500)">
            ${desde}–${hasta} de ${datos.total}
        </span>
        <div class="paginas">
            <button type="button" class="pag-btn" data-ir="1" aria-label="Primera página" ${datos.pagina <= 1 ? 'disabled' : ''}>«</button>
            <button type="button" class="pag-btn" data-ir="${datos.pagina - 1}" ${datos.pagina <= 1 ? 'disabled' : ''}>Anterior</button>
            <span style="font-size:.8rem; font-weight:600; padding:0 8px">
                Página ${datos.pagina} de ${datos.totalPaginas}
            </span>
            <button type="button" class="pag-btn" data-ir="${datos.pagina + 1}" ${datos.pagina >= datos.totalPaginas ? 'disabled' : ''}>Siguiente</button>
            <button type="button" class="pag-btn" data-ir="${datos.totalPaginas}" aria-label="Última página" ${datos.pagina >= datos.totalPaginas ? 'disabled' : ''}>»</button>
        </div>`;

    cont.querySelectorAll('.pag-btn[data-ir]').forEach(boton => {
        boton.addEventListener('click', () => {
            paginaTickets = Number(boton.dataset.ir);
            cargarTickets();
        });
    });
}

function pintarTickets(tickets) {
    const contenedor = document.getElementById('listaTickets');

    if (!tickets.length) {
        contenedor.innerHTML =
            '<div class="vacio"><i aria-hidden="true" class="fa-regular fa-face-smile"></i> No hay averías que mostrar.</div>';
        return;
    }

    contenedor.innerHTML = tickets.map(pintarTicket).join('');

    // El título de cada ticket es un botón real: eso lo hace alcanzable con
    // Tab y activable con Enter/Espacio, sin depender del mouse.
    contenedor.querySelectorAll('.ticket-titulo').forEach(boton => {
        boton.addEventListener('click', (e) => {
            e.stopPropagation();
            abrirDetalle(boton.dataset.folio);
        });
    });

    // La tarjeta completa también reacciona al clic (por comodidad con mouse),
    // pero ignora los controles interactivos que ya tienen su propio manejador.
    contenedor.querySelectorAll('.ticket').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target.closest('.selector-prioridad') || e.target.closest('.ticket-acciones') || e.target.closest('.ticket-titulo')) return;
            abrirDetalle(el.dataset.folio);
        });
    });

    if (vistaActiva === 'admin') {
        contenedor.querySelectorAll('.selector-prioridad').forEach(sel => {
            sel.addEventListener('click', e => e.stopPropagation());
            sel.addEventListener('change', () => asignarPrioridad(sel.dataset.folio, sel.value, cargarTickets));
        });
    }

    contenedor.querySelectorAll('[data-accion="cerrar"]').forEach(boton => {
        boton.addEventListener('click', (e) => {
            e.stopPropagation();
            cambiarEstadoTicket(boton.dataset.folio, 'cerrado', cargarTickets);
        });
    });
}

function pintarTicket(t) {
    const asignado = t.asignado_a
        ? `<span><i aria-hidden="true" class="fa-solid fa-user-check"></i> ${escapar(t.asignado_a)}</span>`
        : '';
    const prioridad = t.prioridad || 'sin_asignar';

    const selectorPrioridad = vistaActiva === 'admin'
        ? `<select class="selector-prioridad" data-folio="${escapar(t.folio)}" aria-label="Prioridad del ticket ${escapar(t.folio)}">
               ${opcionesPrioridad(t.prioridad)}
           </select>`
        : `<span class="etiqueta p-${escapar(prioridad)}">${escapar(textoPrioridad(prioridad))}</span>`;

    const puedeCerrar = vistaActiva === 'funcionario' && t.estado !== 'cerrado';
    const botonCerrar = puedeCerrar
        ? `<div class="ticket-acciones">
               <button type="button" class="btn-secundario btn-chico" data-accion="cerrar" data-folio="${escapar(t.folio)}">
                   <i aria-hidden="true" class="fa-solid fa-check"></i> Marcar como cerrado
               </button>
           </div>`
        : '';

    return `
        <div class="ticket ${escapar(prioridad)}" data-folio="${escapar(t.folio)}">
            <div class="ticket-cabecera">
                <div>
                    <div class="ticket-folio">${escapar(t.folio)}</div>
                    <button type="button" class="ticket-titulo" data-folio="${escapar(t.folio)}" aria-label="Ver detalle de ${escapar(t.folio)}: ${escapar(t.titulo)}">${escapar(t.titulo)}</button>
                    <div class="ticket-meta">
                        <span><i aria-hidden="true" class="fa-solid ${escapar(t.categoria_icono || 'fa-tag')}"></i>
                              ${escapar(t.categoria)}</span>
                        <span><i aria-hidden="true" class="fa-regular fa-user"></i> ${escapar(t.reportado_por)}</span>
                        <span><i aria-hidden="true" class="fa-regular fa-clock"></i> ${formatearFecha(t.creado_en)}</span>
                        ${asignado}
                    </div>
                </div>
                <div class="ticket-etiquetas">
                    <span class="etiqueta ${escapar(t.estado)}">${escapar(textoEstado(t.estado))}</span>
                    ${selectorPrioridad}
                </div>
            </div>
            <div class="ticket-descripcion">${escapar(t.descripcion)}</div>
            ${botonCerrar}
        </div>`;
}

function opcionesPrioridad(actual) {
    const opciones = [
        ['', 'Sin asignar'],
        ['baja', 'Baja'],
        ['media', 'Media'],
        ['alta', 'Alta'],
        ['critica', 'Crítica'],
    ];
    return opciones.map(([valor, texto]) =>
        `<option value="${valor}" ${(actual || '') === valor ? 'selected' : ''}>${texto}</option>`
    ).join('');
}

// ===================================================================
// Detalle de un ticket
// ===================================================================
let elementoFocoPrevio = null;

async function abrirDetalle(folio) {
    const modalFondo = document.getElementById('modalFondo');
    if (!modalFondo.classList.contains('visible')) {
        elementoFocoPrevio = document.activeElement;
    }

    ticketActual = folio;
    document.getElementById('modalFolio').textContent = folio;
    document.getElementById('modalTitulo').textContent = 'Cargando...';
    document.getElementById('modalCuerpo').innerHTML = '<div class="vacio"><i aria-hidden="true" class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';
    document.getElementById('modalAviso').className = 'aviso';
    modalFondo.classList.add('visible');

    try {
        const parametros = new URLSearchParams({ accion: 'ver', folio });
        if (YO.esDesarrollador) parametros.set('vista', vistaActiva);

        const respuesta = await fetch(`${API}?${parametros}`, { cache: 'no-store' });
        const datos = await respuesta.json();

        if (!datos.success) throw new Error(datos.error || 'No se pudo cargar el ticket');

        pintarDetalle(datos);

    } catch (error) {
        document.getElementById('modalCuerpo').innerHTML =
            `<div class="vacio"><i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i> ${escapar(error.message)}</div>`;
    } finally {
        // El foco se manda al título hasta que el contenido está listo (o el
        // error ya se pintó), para que NVDA anuncie algo con sentido.
        document.getElementById('modalTitulo').focus();
    }
}

function cerrarModal() {
    document.getElementById('modalFondo').classList.remove('visible');
    ticketActual = null;
    if (elementoFocoPrevio && typeof elementoFocoPrevio.focus === 'function') {
        elementoFocoPrevio.focus();
        elementoFocoPrevio = null;
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('modalFondo').classList.contains('visible')) {
        cerrarModal();
    }
});

function pintarDetalle(datos) {
    const t = datos.ticket;
    document.getElementById('modalTitulo').textContent = t.titulo;

    const prioridad = t.prioridad || 'sin_asignar';

    const comentarios = datos.comentarios.length
        ? datos.comentarios.map(c => `
            <div class="comentario">
                <div class="comentario-meta">
                    <span>${escapar(c.autor)}${c.estado_nuevo ? ' · cambió el estado a ' + escapar(textoEstado(c.estado_nuevo)) : ''}</span>
                    <span>${formatearFecha(c.creado_en)}</span>
                </div>
                <div>${escapar(c.comentario)}</div>
            </div>`).join('')
        : '<p class="vacio" style="padding:1rem;">Todavía no hay comentarios.</p>';

    document.getElementById('modalCuerpo').innerHTML = `
        <div class="ticket-meta" style="margin-bottom:0.75rem;">
            <span><i aria-hidden="true" class="fa-solid ${escapar(t.categoria_icono || 'fa-tag')}"></i> ${escapar(t.categoria)}</span>
            <span><i aria-hidden="true" class="fa-regular fa-clock"></i> ${formatearFecha(t.creado_en)}</span>
        </div>
        <div style="margin-bottom:1rem;">
            <span class="etiqueta ${escapar(t.estado)}">${escapar(textoEstado(t.estado))}</span>
            <span class="etiqueta p-${escapar(prioridad)}">${escapar(textoPrioridad(prioridad))}</span>
        </div>
        <div class="ticket-descripcion" style="margin-bottom:1rem;">${escapar(t.descripcion)}</div>

        <h3><i aria-hidden="true" class="fa-regular fa-id-badge"></i> Quién reportó</h3>
        ${renderPersona(t.contacto_reportado, 'Reportó')}
        ${t.contacto_asignado ? `<h3><i aria-hidden="true" class="fa-solid fa-user-check"></i> Asignado</h3>${renderPersona(t.contacto_asignado, 'Asignado')}` : ''}

        <div id="modalAcciones"></div>

        <h3><i aria-hidden="true" class="fa-regular fa-comments"></i> Historial</h3>
        <div id="modalComentarios">${comentarios}</div>

        <form id="formComentario" style="margin-top:0.75rem; display:flex; gap:0.5rem;">
            <label for="nuevoComentario" class="sr-only">Agregar un comentario</label>
            <input type="text" id="nuevoComentario" placeholder="Agregar un comentario..." style="flex:1;" required>
            <button type="submit" class="btn-secundario btn-chico">
                <i aria-hidden="true" class="fa-solid fa-paper-plane"></i> Comentar
            </button>
        </form>
    `;

    pintarAccionesEstado(t, datos.esAdmin);

    document.getElementById('formComentario').addEventListener('submit', enviarComentario);
}

function pintarAccionesEstado(t, esAdmin) {
    const contenedor = document.getElementById('modalAcciones');
    const botones = [];

    if (esAdmin) {
        if (t.estado !== 'en_proceso' && t.estado !== 'resuelto' && t.estado !== 'cerrado') {
            botones.push(['en_proceso', 'Poner en proceso', 'btn-secundario']);
        }
        if (t.estado !== 'resuelto' && t.estado !== 'cerrado') {
            botones.push(['resuelto', 'Marcar como resuelto', 'btn-secundario']);
        }
        if (t.estado !== 'cerrado') {
            botones.push(['cerrado', 'Cerrar ticket', 'btn-peligro']);
        }
    } else if (t.estado !== 'cerrado') {
        botones.push(['cerrado', 'Marcar como cerrado', 'btn-peligro']);
    }

    if (!botones.length) {
        contenedor.innerHTML = '';
        return;
    }

    contenedor.innerHTML = `<div class="acciones" style="margin-top:0;">${
        botones.map(([estado, texto, clase]) =>
            `<button type="button" class="${clase}" data-estado="${estado}">${escapar(texto)}</button>`
        ).join('')
    }</div>`;

    contenedor.querySelectorAll('button').forEach(boton => {
        boton.addEventListener('click', () => cambiarEstadoTicket(t.folio, boton.dataset.estado, () => abrirDetalle(t.folio)));
    });
}

function renderPersona(p, etiqueta) {
    if (!p || !p.nombre) {
        return '<p class="vacio" style="padding:0.75rem;">Sin datos de contacto.</p>';
    }

    const inicial = (p.nombre.trim()[0] || '?').toUpperCase();
    const correo = p.correo
        ? `<a href="mailto:${encodeURIComponent(p.correo)}" title="${escapar(p.correo)}">
               <i aria-hidden="true" class="fa-regular fa-envelope"></i>${escapar(p.correo)}</a>`
        : `<span class="sin-dato"><i aria-hidden="true" class="fa-regular fa-envelope"></i>Sin correo</span>`;
    const telefonos = (p.telefonos && p.telefonos.length)
        ? p.telefonos.map(t =>
            `<a href="tel:${t.replace(/[^0-9+]/g, '')}"><i aria-hidden="true" class="fa-solid fa-phone"></i>${escapar(t)}</a>`
          ).join('')
        : `<span class="sin-dato"><i aria-hidden="true" class="fa-solid fa-phone"></i>Sin teléfono</span>`;
    const extension = p.extension
        ? `<span class="extension"><i aria-hidden="true" class="fa-solid fa-headset"></i>Ext. ${escapar(p.extension)}</span>` : '';

    return `<div class="persona-card">
        <div class="persona-inicial">${escapar(inicial)}</div>
        <div class="persona-datos">
            <div class="persona-etiqueta">${escapar(etiqueta)}</div>
            <div class="persona-nombre">${escapar(p.nombre)}</div>
            <div class="persona-contacto">${correo}${telefonos}${extension}</div>
        </div>
    </div>`;
}

// ===================================================================
// Comentarios y cambios de estado
// ===================================================================
async function enviarComentario(evento) {
    evento.preventDefault();
    const campo = document.getElementById('nuevoComentario');
    const texto = campo.value.trim();
    if (!texto) return;

    try {
        const respuesta = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'comentar', folio: ticketActual, comentario: texto, csrf: CSRF })
        });
        const datos = await respuesta.json();
        if (!datos.success) throw new Error(datos.error || 'No se pudo agregar el comentario');

        campo.value = '';
        abrirDetalle(ticketActual);

    } catch (error) {
        mostrarAvisoModal('Error: ' + error.message);
    }
}

async function cambiarEstadoTicket(folio, estado, alTerminar) {
    try {
        const respuesta = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'cambiarEstado', folio, estado, csrf: CSRF })
        });
        const datos = await respuesta.json();
        if (!datos.success) throw new Error(datos.error || 'No se pudo cambiar el estado');

        if (alTerminar) alTerminar();

    } catch (error) {
        if (ticketActual === folio) {
            mostrarAvisoModal('Error: ' + error.message);
        } else {
            mostrarAviso('Error: ' + error.message, 'error');
        }
    }
}

async function asignarPrioridad(folio, prioridad, alTerminar) {
    try {
        const respuesta = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'asignarPrioridad', folio, prioridad, csrf: CSRF })
        });
        const datos = await respuesta.json();
        if (!datos.success) throw new Error(datos.error || 'No se pudo asignar la prioridad');

        if (alTerminar) alTerminar();

    } catch (error) {
        mostrarAviso('Error: ' + error.message, 'error');
    }
}

/** Borra de una sola vez todos los tickets cerrados. Es borrado real, no archivo. */
async function limpiarCerrados() {
    if (!confirm(
        'Esto BORRA de una vez todos los tickets marcados como "Cerrado", con su historial de comentarios.\n\n' +
        'No es un archivo — no se pueden recuperar después.\n\n' +
        '¿Continuar?'
    )) return;

    const boton = document.getElementById('btnLimpiarCerrados');
    boton.disabled = true;

    try {
        const respuesta = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'limpiarCerrados', csrf: CSRF })
        });
        const datos = await respuesta.json();
        if (!datos.success) throw new Error(datos.error || 'No se pudo limpiar los tickets cerrados');

        mostrarAviso(datos.mensaje, 'exito');
        paginaTickets = 1;
        cargarTickets();
    } catch (error) {
        mostrarAviso('Error: ' + error.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================================================================
// Utilidades
// ===================================================================

function textoEstado(estado) {
    return { abierto: 'Abierto', en_proceso: 'En proceso',
             resuelto: 'Resuelto', cerrado: 'Cerrado' }[estado] || estado;
}

function textoPrioridad(prioridad) {
    return { critica: 'Crítica', alta: 'Alta', media: 'Media', baja: 'Baja',
             sin_asignar: 'Sin asignar' }[prioridad] || prioridad;
}

function formatearFecha(texto) {
    if (!texto) return '';
    const fecha = new Date(texto.replace(' ', 'T'));
    if (isNaN(fecha)) return texto;
    return fecha.toLocaleString('es-CR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function mostrarAviso(mensaje, tipo, idCaja = 'aviso') {
    const aviso = document.getElementById(idCaja);
    aviso.textContent = mensaje;
    aviso.className = 'aviso ' + tipo;
    aviso.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (tipo === 'exito') {
        setTimeout(() => { aviso.className = 'aviso'; }, 6000);
    }
}

function mostrarAvisoModal(mensaje) {
    const aviso = document.getElementById('modalAviso');
    aviso.textContent = mensaje;
    aviso.className = 'aviso error';
}

/**
 * Escapa el texto antes de meterlo en el HTML.
 *
 * IMPORTANTE: usá esto SIEMPRE con cualquier dato que venga de la base. Sin
 * esto, alguien podría escribir <script> en la descripción de una avería y el
 * navegador lo ejecutaría (eso se llama XSS). Es la regla del proyecto.
 */
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
