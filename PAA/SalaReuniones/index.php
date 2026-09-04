<?php
/**
 * Módulo Sala de Reuniones.
 *
 * Dos pantallas en un solo archivo:
 *
 *   · Funcionario: el reglamento, el formulario de solicitud y sus propias
 *     solicitudes con el estado en que van.
 *   · Administración: la bandeja de lo que hay que resolver, aprobar/rechazar
 *     con nota, el control del teclado y el mouse del proyector, y la edición
 *     del reglamento.
 *
 * Quién ve cuál lo decide el rol, no un parámetro. La única excepción es
 * `desarrollador`, que necesita poder revisar las dos: para ese rol aparece un
 * selector arriba y el endpoint respeta el mismo parámetro (ver vista_activa()
 * en api/sala_reuniones.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo('sala_reuniones', '../');

$esDev   = ($yo['rolReal'] ?? $yo['rol']) === ROL_DESARROLLADOR;
$esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);

// La vista con la que se abre. Para todos menos desarrollo sale de su rol y no
// hay nada que elegir; el endpoint vuelve a decidirlo por su cuenta, así que
// esto es solo lo que se dibuja.
$vista = $esAdmin ? 'admin' : 'funcionario';
if ($esDev && in_array($_GET['vista'] ?? '', ['admin', 'funcionario'], true)) {
    $vista = $_GET['vista'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Sala de Reuniones · UCR</title>
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
            --gray-600: #4b5563; --gray-700: #374151; --gray-800: #1f2937; --gray-900: #111827;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius: 0.5rem; --radius-lg: 1rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
            color: var(--gray-800); line-height: 1.5; min-height: 100vh; padding: 1.5rem;
        }

        .app { max-width: 1100px; margin: 0 auto; }

        .hero { text-align: center; margin-bottom: 1.5rem; }
        .hero h1 { font-size: 1.75rem; font-weight: 800; color: var(--gray-900); }
        .hero p { color: var(--gray-600); font-size: .9rem; margin-top: .25rem; }

        .tarjeta {
            background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: var(--shadow);
        }
        .tarjeta > h2 {
            font-size: 1.05rem; font-weight: 700; color: var(--gray-900);
            display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem;
        }
        .tarjeta > .subtitulo { font-size: .82rem; color: var(--gray-500); margin-bottom: 1rem; }

        /* Selector de vista, solo para desarrollo. */
        .selector-vista {
            display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
            background: #eef6ff; border: 1px solid #bfdbfe; border-radius: var(--radius);
            padding: .6rem .8rem; margin-bottom: 1.25rem; font-size: .82rem; color: var(--primary-dark);
        }
        .selector-vista a {
            padding: .35rem .8rem; border-radius: 999px; text-decoration: none;
            font-weight: 700; color: var(--primary); border: 1px solid var(--primary-light);
        }
        .selector-vista a.activa { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* Formulario */
        .campos { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .campo { display: flex; flex-direction: column; gap: .3rem; }
        .campo.ancho { grid-column: 1 / -1; }
        .campo > label, .grupo-fecha > legend {
            font-size: .78rem; font-weight: 700; color: var(--gray-700);
            text-transform: uppercase; letter-spacing: .03em;
        }
        input[type="text"], input[type="number"], select, textarea {
            font-family: inherit; font-size: .9rem; padding: .55rem .7rem;
            border: 1px solid var(--gray-300); border-radius: var(--radius);
            background: #fff; color: var(--gray-900); width: 100%;
        }
        textarea { resize: vertical; min-height: 5rem; }
        input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 3px solid var(--secondary); outline-offset: 1px;
        }

        .grupo-fecha { border: none; }
        .partes-fecha { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .3rem; }
        .partes-fecha > div { display: flex; flex-direction: column; gap: .2rem; flex: 1 1 5rem; }
        .partes-fecha label { font-size: .72rem; color: var(--gray-500); font-weight: 600; }
        .fecha-resumen { font-size: .8rem; color: var(--primary-dark); font-weight: 600; margin-top: .4rem; }

        .casilla { display: flex; align-items: flex-start; gap: .55rem; font-size: .87rem; color: var(--gray-700); }
        .casilla input { width: 1.1rem; height: 1.1rem; margin-top: .15rem; flex-shrink: 0; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            font-family: inherit; font-size: .85rem; font-weight: 700; cursor: pointer;
            padding: .6rem 1.1rem; border-radius: var(--radius); border: 1px solid transparent;
        }
        .btn:focus-visible { outline: 3px solid var(--secondary); outline-offset: 2px; }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover:not(:disabled) { background: var(--primary-dark); }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); border-color: var(--gray-200); }
        .btn-secundario:hover:not(:disabled) { background: var(--gray-200); }
        .btn-aprobar { background: var(--success); color: #fff; }
        .btn-aprobar:hover:not(:disabled) { background: #059669; }
        .btn-rechazar { background: #fff; color: #b91c1c; border-color: #fca5a5; }
        .btn-rechazar:hover:not(:disabled) { background: #fee2e2; }

        .aviso { display: none; margin: 1rem 0; padding: .75rem 1rem; border-radius: var(--radius); font-size: .85rem; }
        .aviso.visible { display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; }
        .aviso.exito { background: #d1fae5; color: #065f46; }
        .aviso.info  { background: #e0f2fe; color: #075985; }

        /* Solicitudes */
        .lista { display: flex; flex-direction: column; gap: .85rem; }
        .reserva {
            border: 1px solid var(--gray-200); border-left: 5px solid var(--gray-300);
            border-radius: var(--radius); padding: .9rem 1rem; background: #fff;
        }
        .reserva.pendiente { border-left-color: var(--warning); }
        .reserva.aprobada  { border-left-color: var(--success); }
        .reserva.rechazada { border-left-color: var(--danger); }
        .reserva.cancelada { border-left-color: var(--gray-400); }

        .reserva-cab {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: .75rem; flex-wrap: wrap; margin-bottom: .5rem;
        }
        .reserva-cuando { font-size: .95rem; font-weight: 700; color: var(--gray-900); }
        .reserva-folio { font-size: .74rem; color: var(--gray-500); font-family: ui-monospace, monospace; }
        .reserva-motivo { font-size: .88rem; color: var(--gray-700); }
        .reserva-datos { font-size: .8rem; color: var(--gray-500); margin-top: .35rem; }
        .reserva-nota {
            margin-top: .6rem; padding: .55rem .7rem; background: var(--gray-50);
            border-radius: var(--radius); font-size: .82rem; color: var(--gray-700);
        }
        .reserva-acciones { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .75rem; }

        .chip {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em;
            padding: .25rem .6rem; border-radius: 999px; white-space: nowrap;
        }
        .chip.pendiente { background: #fef3c7; color: #92400e; }
        .chip.aprobada  { background: #d1fae5; color: #065f46; }
        .chip.rechazada { background: #fee2e2; color: #991b1b; }
        .chip.cancelada { background: var(--gray-100); color: var(--gray-600); }
        .chip.equipo    { background: #ede9fe; color: #5b21b6; }
        .chip.bloqueo   { background: #1f2937; color: #fff; }

        /* Bloque del teclado y el mouse */
        .equipo-caja {
            margin-top: .7rem; padding: .7rem .8rem; border-radius: var(--radius);
            background: #faf5ff; border: 1px solid #ddd6fe;
        }
        .equipo-caja h3 {
            font-size: .78rem; font-weight: 800; color: #5b21b6;
            text-transform: uppercase; letter-spacing: .03em; margin-bottom: .35rem;
        }
        .equipo-caja p { font-size: .82rem; color: var(--gray-700); }

        .vacio {
            text-align: center; padding: 2rem 1rem; color: var(--gray-500); font-size: .88rem;
            border: 1px dashed var(--gray-300); border-radius: var(--radius);
        }

        .filtros { display: flex; gap: .6rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 1rem; }
        .filtros .campo { flex: 1 1 10rem; }

        .reglamento-texto { font-size: .88rem; color: var(--gray-700); white-space: pre-wrap; line-height: 1.65; }
        .reglamento-pie { font-size: .75rem; color: var(--gray-500); margin-top: .75rem; }

        .ocupacion { font-size: .82rem; color: var(--gray-600); margin-top: .5rem; }
        .ocupacion ul { margin: .3rem 0 0 1.1rem; }

        /* Calendario: solo para ver, no reserva nada al hacer clic — la nota
           de abajo lo dice explícito para que nadie lo confunda con un botón
           de agendar. Más chico que el de Cronograma a propósito: acá lo que
           importa es si un día tiene algo o no, no el detalle a simple vista. */
        .cal-cabecera-mes {
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem; margin-bottom: .75rem; flex-wrap: wrap;
        }
        .cal-cabecera-mes h3 { font-size: .95rem; font-weight: 700; color: var(--gray-900); }
        .cal-nav { display: flex; gap: .4rem; }
        .cal-nav button {
            width: 2rem; height: 2rem; border-radius: 999px; border: 1px solid var(--gray-200);
            background: #fff; color: var(--gray-600); cursor: pointer; font-size: .8rem;
        }
        .cal-nav button:hover { background: var(--gray-100); }
        .cal-nav button:focus-visible { outline: 3px solid var(--secondary); outline-offset: 1px; }

        .cal-dias-cab { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; margin-bottom: 3px; }
        .cal-dias-cab span {
            text-align: center; font-size: .68rem; font-weight: 700; color: var(--gray-500);
            text-transform: uppercase; letter-spacing: .02em;
        }
        .cal-grid-sala { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 3px; }
        .cal-celda {
            min-height: 46px; border: 1px solid var(--gray-200); border-radius: 8px;
            padding: .25rem .3rem; text-align: left; background: #fff; cursor: pointer;
            font-family: inherit; display: flex; flex-direction: column; gap: .15rem;
        }
        .cal-celda:hover { border-color: var(--primary-light); }
        .cal-celda:focus-visible { outline: 3px solid var(--secondary); outline-offset: 1px; }
        .cal-celda.fuera-de-mes { background: var(--gray-50); border-color: transparent; cursor: default; }
        .cal-celda.fuera-de-mes:hover { border-color: transparent; }
        .cal-celda.hoy { border-color: var(--primary); }
        .cal-celda.cerrado { background: repeating-linear-gradient(45deg, #f9fafb, #f9fafb 6px, #f3f4f6 6px, #f3f4f6 12px); }
        .cal-celda-num { font-size: .74rem; font-weight: 700; color: var(--gray-700); }
        .cal-celda.fuera-de-mes .cal-celda-num { color: var(--gray-300); }
        .cal-celda.hoy .cal-celda-num { color: var(--primary); }
        .cal-celda-puntos { display: flex; gap: .2rem; flex-wrap: wrap; }
        .cal-punto { width: .4rem; height: .4rem; border-radius: 50%; flex-shrink: 0; }
        .cal-punto.aprobada  { background: var(--success); }
        .cal-punto.pendiente { background: var(--warning); }
        .cal-punto.bloqueo   { background: var(--gray-700); }
        .cal-celda-mas { font-size: .62rem; color: var(--gray-500); }

        .cal-nota { font-size: .78rem; color: var(--gray-500); margin-top: .6rem; }

        /* Ventana emergente para aprobar/rechazar. El foco, el Escape y la
           trampa de Tab los pone includes/ui_accesible.php al ver la clase. */
        .fondo-modal {
            display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, .55);
            z-index: 9000; align-items: center; justify-content: center; padding: 1rem;
        }
        .fondo-modal.visible { display: flex; }
        .modal-caja {
            background: #fff; border-radius: var(--radius-lg); padding: 1.5rem;
            max-width: 32rem; width: 100%; max-height: 90vh; overflow-y: auto;
        }
        .modal-caja h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: .35rem; }
        .modal-caja > p { font-size: .85rem; color: var(--gray-600); margin-bottom: 1rem; }
        .modal-acciones { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1.1rem; flex-wrap: wrap; }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .campos { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app" id="paaContenido">

    <div class="hero">
        <h1>Sala de Reuniones</h1>
        <p>Solicitud y préstamo de la sala</p>
    </div>

    <?php if ($esDev): ?>
    <!-- Solo desarrollo: las dos pantallas del módulo se revisan desde acá sin
         tener que cambiar de cuenta. -->
    <div class="selector-vista">
        <span><i class="fa-solid fa-user-secret" aria-hidden="true"></i> Estás viendo la pantalla de:</span>
        <a href="?vista=funcionario" class="<?= $vista === 'funcionario' ? 'activa' : '' ?>"
           <?= $vista === 'funcionario' ? 'aria-current="page"' : '' ?>>Funcionario</a>
        <a href="?vista=admin" class="<?= $vista === 'admin' ? 'activa' : '' ?>"
           <?= $vista === 'admin' ? 'aria-current="page"' : '' ?>>Administración</a>
    </div>
    <?php endif; ?>

    <div id="aviso" class="aviso" role="status" aria-live="polite" aria-atomic="true"></div>

    <!-- ================= REGLAMENTO ================= -->
    <section class="tarjeta" aria-labelledby="tituloReglamento">
        <h2 id="tituloReglamento"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Reglamento de uso</h2>
        <p class="subtitulo">Lo que hay que cumplir para usar la sala. Aplica a toda reserva aprobada.</p>

        <div class="reglamento-texto" id="reglamentoTexto">Cargando…</div>
        <p class="reglamento-pie" id="reglamentoPie"></p>

        <?php if ($vista === 'admin'): ?>
        <div style="margin-top:1rem;">
            <button type="button" class="btn btn-secundario" id="btnEditarReglamento">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> Editar el reglamento
            </button>
        </div>
        <div id="editorReglamento" hidden style="margin-top:1rem;">
            <div class="campo">
                <label for="reglamentoEdicion">Texto del reglamento</label>
                <textarea id="reglamentoEdicion" rows="12"></textarea>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" id="btnCancelarReglamento">Cancelar</button>
                <button type="button" class="btn btn-primario" id="btnGuardarReglamento">Guardar reglamento</button>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- ================= CALENDARIO ================= -->
    <!-- Solo para ver: un clic en un día abre el detalle, no reserva nada. Aun
         viendo un día libre acá, la solicitud sigue necesitando aprobación —
         puede haber algo que este calendario no cuente. -->
    <section class="tarjeta" aria-labelledby="tituloCalendario">
        <h2 id="tituloCalendario"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Calendario de la sala</h2>
        <p class="subtitulo">
            Lunes a viernes, de 7:00 a.m. a 4:00 p.m. Es solo para consultar: pedir la sala se hace
            <?= $vista === 'admin' ? 'desde el formulario de la persona interesada' : 'con el formulario de arriba' ?>,
            y siempre pasa por aprobación aunque el día se vea libre acá.
        </p>

        <?php if ($vista === 'admin'): ?>
        <div style="margin-bottom:1rem;">
            <button type="button" class="btn btn-secundario" id="btnEventoManual">
                <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> Agregar evento manual
            </button>
        </div>
        <?php endif; ?>

        <div class="cal-cabecera-mes">
            <h3 id="calMesTitulo"></h3>
            <div class="cal-nav">
                <button type="button" id="calMesAnterior" aria-label="Mes anterior"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                <button type="button" id="calMesSiguiente" aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="cal-dias-cab" aria-hidden="true">
            <span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
        </div>
        <div class="cal-grid-sala" id="calGridSala"></div>
        <p class="cal-nota">
            <span class="cal-punto aprobada" style="display:inline-block;"></span> Aprobada
            &nbsp; <span class="cal-punto pendiente" style="display:inline-block;"></span> Pendiente
            &nbsp; <span class="cal-punto bloqueo" style="display:inline-block;"></span> No disponible
            &nbsp; · el rayado es fuera del horario de la sala (fines de semana)
        </p>
    </section>

    <?php if ($vista === 'funcionario'): ?>
    <!-- ================= PANTALLA DE FUNCIONARIO ================= -->

    <section class="tarjeta" aria-labelledby="tituloSolicitar">
        <h2 id="tituloSolicitar"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> Solicitar la sala</h2>
        <p class="subtitulo">
            La reserva no queda hecha hasta que administración la apruebe. Se avisa por correo.
            También podés elegir el día directo desde el calendario de más arriba.
        </p>
        <button type="button" class="btn btn-primario" id="btnAbrirSolicitar">
            <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> Solicitar la sala
        </button>
    </section>

    <section class="tarjeta" aria-labelledby="tituloMisSolicitudes">
        <h2 id="tituloMisSolicitudes"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Mis solicitudes</h2>
        <p class="subtitulo">En qué va cada una de las que has pedido.</p>
        <div class="lista" id="listaReservas"></div>
    </section>

    <?php else: ?>
    <!-- ================= PANTALLA DE ADMINISTRACIÓN ================= -->

    <section class="tarjeta" aria-labelledby="tituloBandeja">
        <h2 id="tituloBandeja"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Solicitudes</h2>
        <p class="subtitulo" id="resumenPendientes">Lo que hay que resolver y el historial.</p>

        <div class="filtros">
            <div class="campo">
                <label for="filtroEstado">Estado</label>
                <select id="filtroEstado">
                    <option value="pendiente">Pendientes</option>
                    <option value="aprobada">Aprobadas</option>
                    <option value="rechazada">Rechazadas</option>
                    <option value="cancelada">Canceladas</option>
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="campo" style="flex:2 1 14rem;">
                <label for="filtroTexto">Buscar</label>
                <input type="text" id="filtroTexto" placeholder="Folio, motivo o quién la pidió">
            </div>
        </div>

        <div class="lista" id="listaReservas"></div>
    </section>
    <?php endif; ?>
</div>

<!-- Ventana para aprobar, rechazar o cancelar con una nota. -->
<div class="fondo-modal" id="modalDecision" role="dialog" aria-modal="true"
     aria-labelledby="decisionTitulo" data-paa-foco="#decisionNota">
    <div class="modal-caja">
        <h2 id="decisionTitulo">Resolver la solicitud</h2>
        <p id="decisionResumen"></p>

        <div class="campo">
            <label for="decisionNota" id="decisionNotaEtiqueta">Nota para quien solicitó</label>
            <textarea id="decisionNota" rows="4"></textarea>
        </div>

        <div class="modal-acciones">
            <button type="button" class="btn btn-secundario" id="decisionCerrar">Cancelar</button>
            <button type="button" class="btn btn-primario" id="decisionConfirmar">Confirmar</button>
        </div>
    </div>
</div>

<?php if ($vista === 'funcionario'): ?>
<!-- Formulario de solicitud. Se abre con el botón de arriba o eligiendo un
     día en el calendario (que llega con la fecha ya puesta). Sin
     data-paa-foco: cae al título por defecto, así NVDA anuncia primero
     "Solicitar la sala" (la ventana en la que se está) y de ahí en adelante
     se recorre el formulario de arriba hacia abajo, empezando por la fecha. -->
<div class="fondo-modal" id="modalSolicitar" role="dialog" aria-modal="true" aria-labelledby="solicitarTitulo">
    <div class="modal-caja">
        <h2 id="solicitarTitulo" tabindex="-1">Solicitar la sala</h2>
        <p>La reserva no queda hecha hasta que administración la apruebe. Se avisa por correo.</p>

        <form id="formSolicitud" novalidate>
            <div class="campos">
                <fieldset class="campo ancho grupo-fecha">
                    <legend>Fecha solicitada</legend>
                    <div class="partes-fecha" id="fechaPartes"></div>
                </fieldset>

                <div class="campo">
                    <label for="horaInicio">Hora de inicio</label>
                    <select id="horaInicio" required></select>
                </div>

                <div class="campo">
                    <label for="horaFin">Hora de fin</label>
                    <select id="horaFin" required></select>
                </div>

                <div class="campo">
                    <label for="personas">¿Cuántas personas?</label>
                    <input type="number" id="personas" min="1" max="100" value="1">
                </div>

                <div class="campo ancho">
                    <label for="motivo">Motivo de la solicitud</label>
                    <input type="text" id="motivo" maxlength="200" required
                           placeholder="Ej.: reunión de coordinación del embalaje">
                </div>

                <div class="campo ancho">
                    <label for="informacionAdicional">Información adicional</label>
                    <textarea id="informacionAdicional" maxlength="2000"
                              placeholder="Cualquier cosa que administración deba saber: si viene alguien de fuera, si se necesita acomodar la sala de alguna forma…"></textarea>
                </div>

                <div class="campo ancho casilla">
                    <input type="checkbox" id="requiereProyector">
                    <label for="requiereProyector" style="text-transform:none;letter-spacing:0;font-weight:600;">
                        Voy a usar el proyector.
                        El teclado y el mouse se retiran en la Oficina de Informática antes de la reunión
                        y se devuelven ahí mismo al terminar.
                    </label>
                </div>

                <div class="campo ancho casilla">
                    <input type="checkbox" id="aceptaReglamento" required>
                    <label for="aceptaReglamento" style="text-transform:none;letter-spacing:0;font-weight:600;">
                        Leí el reglamento de uso y me comprometo a cumplirlo.
                    </label>
                </div>
            </div>

            <div id="ocupacionDia" class="ocupacion" role="status" aria-live="polite"></div>

            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" id="solicitarCerrar">Cancelar</button>
                <button type="submit" class="btn btn-primario" id="btnSolicitar">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Enviar la solicitud
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Detalle de un día del calendario.
     · Funcionario NO usa esta ventana: al elegir un día en el calendario le
       abre directo el formulario de solicitud con la fecha ya puesta (ver
       abrirSolicitarDesdeCalendario en el script) — así no hay que pasar por
       una pantalla intermedia solo para llegar a pedir la sala.
     · Administración sí la usa, para ver qué hay ese día antes de decidir; el
       botón de abajo lo manda directo a anotar un evento manual en esa fecha.
     Sin data-paa-foco: cae al título por defecto (ver el comentario de
     arriba), que es un elemento real y enfocable — apuntarlo a un contenedor
     sin tabindex hacía que el foco no entrara a la ventana. -->
<div class="fondo-modal" id="modalDia" role="dialog" aria-modal="true" aria-labelledby="diaTitulo">
    <div class="modal-caja">
        <h2 id="diaTitulo" tabindex="-1">Ese día</h2>
        <div id="diaCuerpo" class="lista"></div>
        <div class="modal-acciones">
            <button type="button" class="btn btn-secundario" id="diaCerrar">Cerrar</button>
            <?php if ($vista === 'admin'): ?>
            <button type="button" class="btn btn-primario" id="diaAgregarManual">
                <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> Agregar evento manual acá
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($vista === 'admin'): ?>
<!-- Evento manual: administración anota algo directo en el calendario, sin
     pasar por aprobación — es administración registrando la realidad, no
     pidiéndose permiso a sí misma. Por eso el horario acá es libre (no está
     acotado a 7 a.m.-4 p.m. de lunes a viernes como el formulario normal):
     un arreglo puede caer un fin de semana. -->
<div class="fondo-modal" id="modalManual" role="dialog" aria-modal="true"
     aria-labelledby="manualTitulo" data-paa-foco="#manualMotivo">
    <div class="modal-caja">
        <h2 id="manualTitulo">Agregar evento manual</h2>
        <p>Para un pedido que llegó por fuera del sistema, o para marcar la sala como no disponible (arreglos, por ejemplo).</p>

        <div class="campos">
            <div class="campo ancho">
                <label for="manualTipo">Tipo</label>
                <select id="manualTipo">
                    <option value="reserva">Reserva (alguien va a usar la sala)</option>
                    <option value="bloqueo">No disponible (arreglos, etc.)</option>
                </select>
            </div>

            <div class="campo ancho" id="manualNombreCampo">
                <label for="manualNombre">¿A nombre de quién? (opcional)</label>
                <input type="text" id="manualNombre" maxlength="160" placeholder="Ej.: Dirección del PAA">
            </div>

            <fieldset class="campo ancho grupo-fecha">
                <legend>Fecha</legend>
                <div class="partes-fecha" id="manualFechaPartes"></div>
            </fieldset>

            <div class="campo">
                <label for="manualHoraInicio">Hora de inicio</label>
                <select id="manualHoraInicio"></select>
            </div>

            <div class="campo">
                <label for="manualHoraFin">Hora de fin</label>
                <select id="manualHoraFin"></select>
            </div>

            <div class="campo ancho">
                <label for="manualMotivo">Motivo</label>
                <input type="text" id="manualMotivo" maxlength="200" required>
            </div>

            <div class="campo ancho">
                <label for="manualInformacionAdicional">Información adicional</label>
                <textarea id="manualInformacionAdicional" maxlength="2000"></textarea>
            </div>
        </div>

        <div class="modal-acciones">
            <button type="button" class="btn btn-secundario" id="manualCerrar">Cancelar</button>
            <button type="button" class="btn btn-primario" id="manualGuardar">Agregar al calendario</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const API   = '../api/sala_reuniones.php';
const CSRF  = <?= json_encode(token_csrf()) ?>;
const VISTA = <?= json_encode($vista) ?>;
const YO_ID = <?= (int) $yo['id'] ?>;

const MESES_LARGOS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                      'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
const DIAS_LARGO = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

const ETIQUETA_ESTADO = {
    pendiente: 'Pendiente', aprobada: 'Aprobada',
    rechazada: 'Rechazada', cancelada: 'Cancelada',
};

let reservas = [];
// Qué se está resolviendo en la ventana emergente: { id, decision }.
let decisionEnCurso = null;
// Qué día está mostrando el modal de detalle (para "Agregar evento manual acá").
let diaMostrado = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarReglamento();

    if (VISTA === 'funcionario') {
        armarSelectorFecha('fechaPartes', 'la fecha solicitada');
        llenarHoras();
        document.getElementById('formSolicitud').addEventListener('submit', enviarSolicitud);
        document.getElementById('btnAbrirSolicitar').addEventListener('click', () => abrirSolicitar());
        document.getElementById('solicitarCerrar').addEventListener('click', cerrarSolicitar);
    } else {
        document.getElementById('filtroEstado').addEventListener('change', cargarReservas);
        document.getElementById('filtroTexto').addEventListener('input', filtrarConEspera);
        prepararEditorReglamento();
        prepararEventoManual();
        document.getElementById('diaAgregarManual').addEventListener('click', () => {
            cerrarDia();
            abrirEventoManual(diaMostrado);
        });
    }

    document.getElementById('decisionCerrar').addEventListener('click', cerrarDecision);
    document.getElementById('decisionConfirmar').addEventListener('click', confirmarDecision);
    document.getElementById('diaCerrar').addEventListener('click', cerrarDia);

    document.getElementById('calMesAnterior').addEventListener('click', () => cambiarMesCalendario(-1));
    document.getElementById('calMesSiguiente').addEventListener('click', () => cambiarMesCalendario(1));

    cargarReservas();
    cargarCalendario();
});

// ------------------------------------------------------------------
// Reglamento
// ------------------------------------------------------------------

async function cargarReglamento() {
    try {
        const d = await pedir(`${API}?accion=reglamento`);
        document.getElementById('reglamentoTexto').textContent = d.texto || 'Todavía no se ha escrito el reglamento.';

        const pie = document.getElementById('reglamentoPie');
        if (d.actualizado && d.actualizado.por) {
            pie.textContent = `Última actualización: ${d.actualizado.por}, ${fechaHoraLegible(d.actualizado.en)}.`;
        } else {
            pie.textContent = '';
        }

        const editor = document.getElementById('reglamentoEdicion');
        if (editor) editor.value = d.texto || '';
    } catch (e) {
        document.getElementById('reglamentoTexto').textContent = 'No se pudo cargar el reglamento.';
    }
}

function prepararEditorReglamento() {
    const abrir  = document.getElementById('btnEditarReglamento');
    const editor = document.getElementById('editorReglamento');
    if (!abrir || !editor) return;

    abrir.addEventListener('click', () => {
        editor.hidden = false;
        abrir.hidden = true;
        document.getElementById('reglamentoEdicion').focus();
    });

    document.getElementById('btnCancelarReglamento').addEventListener('click', () => {
        editor.hidden = true;
        abrir.hidden = false;
        abrir.focus();
        cargarReglamento();
    });

    document.getElementById('btnGuardarReglamento').addEventListener('click', async () => {
        const texto = document.getElementById('reglamentoEdicion').value.trim();
        if (!texto) { mostrarError('El reglamento no puede quedar vacío.'); return; }

        try {
            await pedirPost(API, { accion: 'guardarReglamento', texto });
            editor.hidden = true;
            abrir.hidden = false;
            abrir.focus();
            await cargarReglamento();
            avisarExito('Reglamento actualizado.');
        } catch (e) {
            mostrarError(e.message);
        }
    });
}

// ------------------------------------------------------------------
// Solicitud (funcionario)
// ------------------------------------------------------------------

/**
 * Las horas se eligen de una lista de medias horas, no con un campo libre ni
 * con <input type="time">: en la prueba con lector de pantalla el campo de
 * hora es de los más difíciles de llenar, y para reservar una sala nunca hace
 * falta el minuto exacto.
 */
function llenarHoras() {
    // La sala se cierra a las 4 p.m.: no tiene sentido ofrecer una hora que el
    // servidor va a rechazar igual (ver SALA_HORA_CIERRE en el endpoint).
    const opciones = [];
    for (let h = 7; h <= 16; h++) {
        for (const m of ['00', '30']) {
            if (h === 16 && m === '30') continue;
            const valor = `${String(h).padStart(2, '0')}:${m}`;
            opciones.push(`<option value="${valor}">${valor}</option>`);
        }
    }

    const inicio = document.getElementById('horaInicio');
    const fin    = document.getElementById('horaFin');

    inicio.innerHTML = opciones.join('');
    fin.innerHTML    = opciones.join('');
    inicio.value = '08:00';
    fin.value    = '09:00';

    inicio.addEventListener('change', () => {
        // La de fin sigue a la de inicio cuando queda inconsistente, en vez de
        // dejar que la persona mande algo imposible y reciba un error.
        if (fin.value <= inicio.value) {
            const siguiente = opciones.findIndex(o => o.includes(`value="${inicio.value}"`)) + 1;
            if (siguiente > 0 && siguiente < opciones.length) {
                fin.value = opciones[siguiente].match(/value="([^"]+)"/)[1];
            }
        }
    });
}

async function enviarSolicitud(evento) {
    evento.preventDefault();

    const fecha  = leerSelectorFecha('fechaPartes');
    const inicio = document.getElementById('horaInicio').value;
    const fin    = document.getElementById('horaFin').value;
    const motivo = document.getElementById('motivo').value.trim();

    if (!fecha)  { return errorEnCampo('Elegí el día, el mes y el año de la fecha solicitada.', 'fechaPartesDia'); }
    if (esFinDeSemana(fecha)) { return errorEnCampo('La sala solo se presta de lunes a viernes.', 'fechaPartesDia'); }
    if (fin <= inicio) { return errorEnCampo('La hora de fin tiene que ser posterior a la de inicio.', 'horaFin'); }
    if (!motivo) { return errorEnCampo('Escribí para qué necesitás la sala.', 'motivo'); }
    if (!document.getElementById('aceptaReglamento').checked) {
        return errorEnCampo('Hay que leer y aceptar el reglamento de uso antes de solicitar la sala.', 'aceptaReglamento');
    }

    const boton = document.getElementById('btnSolicitar');
    boton.disabled = true;

    try {
        const d = await pedirPost(API, {
            accion: 'crear',
            fecha,
            hora_inicio: inicio,
            hora_fin: fin,
            motivo,
            informacion_adicional: document.getElementById('informacionAdicional').value.trim(),
            personas: document.getElementById('personas').value || '1',
            requiere_proyector: document.getElementById('requiereProyector').checked ? '1' : '0',
        });

        document.getElementById('formSolicitud').reset();
        ponerSelectorFecha('fechaPartes', '', 'la fecha solicitada');
        llenarHoras();
        document.getElementById('ocupacionDia').textContent = '';

        avisarExito(
            `Solicitud ${d.folio} enviada. Te llega un correo cuando administración la resuelva.`,
            cerrarSolicitar
        );
        await cargarReservas();
        await cargarCalendario();
    } catch (e) {
        mostrarError(e.message);
    } finally {
        boton.disabled = false;
    }
}

/** Sábado o domingo, a partir de una fecha ISO. La sala no se presta esos días. */
function esFinDeSemana(iso) {
    const dia = new Date(iso + 'T00:00:00').getDay();
    return dia === 0 || dia === 6;
}

/** Lo que ya está apartado ese día, para no mandar una solicitud que choca. */
async function revisarOcupacion() {
    const caja  = document.getElementById('ocupacionDia');
    const fecha = leerSelectorFecha('fechaPartes');

    if (!caja) return;
    if (!fecha) { caja.textContent = ''; return; }

    if (esFinDeSemana(fecha)) {
        caja.innerHTML = `<strong>La sala no se presta los ${fechaLarga(fecha).split(' ')[0]}s.</strong> Solo de lunes a viernes.`;
        return;
    }

    try {
        const d = await pedir(`${API}?accion=ocupacion&fecha=${encodeURIComponent(fecha)}`);

        if (!d.franjas.length) {
            caja.textContent = `La sala está libre todo el ${fechaLarga(fecha)}, de 7:00 a.m. a 4:00 p.m.`;
            return;
        }

        const filas = d.franjas.map(f =>
            `<li>De ${f.hora_inicio.slice(0, 5)} a ${f.hora_fin.slice(0, 5)} — ${ETIQUETA_ESTADO[f.estado] || f.estado}</li>`
        ).join('');

        caja.innerHTML = `Ese día la sala ya tiene:<ul>${filas}</ul>`;
    } catch (e) {
        caja.textContent = '';
    }
}

// ------------------------------------------------------------------
// Listado
// ------------------------------------------------------------------

let esperaFiltro = null;

function filtrarConEspera() {
    clearTimeout(esperaFiltro);
    esperaFiltro = setTimeout(cargarReservas, 300);
}

async function cargarReservas() {
    const parametros = new URLSearchParams({ accion: 'listar', vista: VISTA });

    if (VISTA === 'admin') {
        const estado = document.getElementById('filtroEstado').value;
        const texto  = document.getElementById('filtroTexto').value.trim();
        if (estado) parametros.set('estado', estado);
        if (texto)  parametros.set('q', texto);
    }

    try {
        const d = await pedir(`${API}?${parametros}`);
        reservas = d.reservas;
        pintarReservas();

        const resumen = document.getElementById('resumenPendientes');
        if (resumen) {
            resumen.textContent = d.pendientes === 0
                ? 'No hay solicitudes pendientes de resolver.'
                : `${d.pendientes} solicitud(es) pendiente(s) de resolver.`;
        }
    } catch (e) {
        mostrarError(e.message);
    }
}

function pintarReservas() {
    const caja = document.getElementById('listaReservas');

    if (!reservas.length) {
        caja.innerHTML = `<p class="vacio">${
            VISTA === 'admin'
                ? 'No hay solicitudes que coincidan con el filtro.'
                : 'Todavía no has solicitado la sala.'
        }</p>`;
        return;
    }

    caja.innerHTML = reservas.map(pintarUna).join('');

    caja.querySelectorAll('[data-accion]').forEach(b => {
        b.addEventListener('click', () => manejarAccion(b.dataset.accion, Number(b.dataset.id)));
    });
}

function pintarUna(r) {
    const esBloqueo = r.tipo === 'bloqueo';

    const proyector = r.requiereProyector
        ? `<span class="chip equipo"><i class="fa-solid fa-desktop" aria-hidden="true"></i> Usa el proyector</span>`
        : '';

    const chipManual = r.origen === 'manual' && !esBloqueo
        ? `<span class="chip cancelada">Anotado por administración</span>`
        : '';

    const nota = r.respuesta
        ? `<div class="reserva-nota"><strong>${escapar(r.resueltoPor || 'Administración')}:</strong> ${escapar(r.respuesta)}</div>`
        : '';

    const extra = r.informacionAdicional
        ? `<div class="reserva-nota">${escapar(r.informacionAdicional)}</div>`
        : '';

    const datos = esBloqueo
        ? `Anotado por ${escapar(r.resueltoPor || 'administración')} · ${fechaHoraLegible(r.creadoEn)}`
        : `${VISTA === 'admin' ? `Solicita ${escapar(r.solicitante)} · ` : ''}${r.personas} persona(s)`
          + ` · Pedida el ${fechaHoraLegible(r.creadoEn)}`;

    return `
        <article class="reserva ${esBloqueo ? 'rechazada' : r.estado}">
            <div class="reserva-cab">
                <div>
                    <div class="reserva-cuando">${escapar(r.fechaLarga)}, de ${r.horaInicio} a ${r.horaFin}</div>
                    <div class="reserva-folio">${escapar(r.folio)}</div>
                </div>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    <span class="chip ${esBloqueo ? 'bloqueo' : r.estado}">
                        ${esBloqueo ? 'No disponible' : (ETIQUETA_ESTADO[r.estado] || r.estado)}
                    </span>
                    ${chipManual}
                    ${proyector}
                </div>
            </div>

            <div class="reserva-motivo">${escapar(r.motivo)}</div>
            <div class="reserva-datos">${datos}</div>

            ${extra}
            ${nota}
            ${bloqueEquipo(r)}
            ${accionesDe(r)}
        </article>
    `;
}

/**
 * El teclado y el mouse del proyector.
 *
 * Solo aparece en reservas aprobadas que pidieron proyector: en cualquier otra
 * no hay nada que retirar, y mostrarlo sería una fila de ruido por reserva.
 */
function bloqueEquipo(r) {
    if (r.estado !== 'aprobada' || !r.requiereProyector) return '';

    let estado;
    if (r.equipo.devueltoEn) {
        estado = `Devueltos el ${fechaHoraLegible(r.equipo.devueltoEn)}` +
                 (r.equipo.devueltoPor ? ` (recibió ${escapar(r.equipo.devueltoPor)})` : '') + '.';
    } else if (r.equipo.retiradoEn) {
        estado = `Retirados el ${fechaHoraLegible(r.equipo.retiradoEn)}` +
                 (r.equipo.retiradoPor ? ` (entregó ${escapar(r.equipo.retiradoPor)})` : '') +
                 '. <strong>Faltan por devolver.</strong>';
    } else {
        estado = 'Todavía no se han retirado de la Oficina de Informática.';
    }

    const botones = VISTA !== 'admin' ? '' : `
        <div class="reserva-acciones">
            ${!r.equipo.retiradoEn
                ? `<button type="button" class="btn btn-secundario" data-accion="retirado" data-id="${r.id}">
                       <i class="fa-solid fa-hand-holding" aria-hidden="true"></i> Marcar retirados
                   </button>`
                : ''}
            ${r.equipo.retiradoEn && !r.equipo.devueltoEn
                ? `<button type="button" class="btn btn-secundario" data-accion="devuelto" data-id="${r.id}">
                       <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Marcar devueltos
                   </button>`
                : ''}
            ${r.equipo.retiradoEn
                ? `<button type="button" class="btn btn-secundario" data-accion="deshacerEquipo" data-id="${r.id}">
                       Deshacer
                   </button>`
                : ''}
        </div>`;

    return `
        <div class="equipo-caja">
            <h3>Teclado y mouse del proyector</h3>
            <p>${estado}</p>
            ${botones}
        </div>`;
}

function accionesDe(r) {
    const botones = [];

    if (VISTA === 'admin' && r.estado === 'pendiente') {
        botones.push(`<button type="button" class="btn btn-aprobar" data-accion="aprobar" data-id="${r.id}">
                          <i class="fa-solid fa-check" aria-hidden="true"></i> Aprobar
                      </button>`);
        botones.push(`<button type="button" class="btn btn-rechazar" data-accion="rechazar" data-id="${r.id}">
                          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Rechazar
                      </button>`);
    }

    const puedeCancelar = ['pendiente', 'aprobada'].includes(r.estado)
        && (VISTA === 'admin' || r.solicitanteId === YO_ID);

    if (puedeCancelar) {
        botones.push(`<button type="button" class="btn btn-secundario" data-accion="cancelar" data-id="${r.id}">
                          <i class="fa-solid fa-ban" aria-hidden="true"></i> Cancelar la reserva
                      </button>`);
    }

    return botones.length ? `<div class="reserva-acciones">${botones.join('')}</div>` : '';
}

async function manejarAccion(accion, id) {
    if (accion === 'aprobar' || accion === 'rechazar' || accion === 'cancelar') {
        abrirDecision(accion, id);
        return;
    }

    const que = accion === 'deshacerEquipo' ? 'deshacer' : accion;

    try {
        await pedirPost(API, { accion: 'equipo', id, que });
        await cargarReservas();
        avisarExito(
            que === 'retirado' ? 'Anotado: el teclado y el mouse salieron.'
            : que === 'devuelto' ? 'Anotado: el teclado y el mouse volvieron.'
            : 'Se deshizo el registro del equipo.'
        );
    } catch (e) {
        mostrarError(e.message);
    }
}

// ------------------------------------------------------------------
// Ventana de decisión
// ------------------------------------------------------------------

function abrirDecision(decision, id) {
    const r = reservas.find(x => x.id === id);
    if (!r) return;

    decisionEnCurso = { id, decision };

    const titulo = { aprobar: 'Aprobar la solicitud', rechazar: 'Rechazar la solicitud', cancelar: 'Cancelar la reserva' }[decision];
    const etiqueta = decision === 'rechazar'
        ? 'Por qué se rechaza (obligatorio)'
        : 'Nota para quien solicitó (opcional)';

    document.getElementById('decisionTitulo').textContent = titulo;
    document.getElementById('decisionResumen').textContent =
        `${r.folio} · ${r.fechaLarga}, de ${r.horaInicio} a ${r.horaFin} · ${r.solicitante}`;
    document.getElementById('decisionNotaEtiqueta').textContent = etiqueta;
    document.getElementById('decisionNota').value = '';
    document.getElementById('decisionConfirmar').textContent = titulo;

    document.getElementById('modalDecision').classList.add('visible');
}

function cerrarDecision() {
    document.getElementById('modalDecision').classList.remove('visible');
    decisionEnCurso = null;
}

async function confirmarDecision() {
    if (!decisionEnCurso) return;

    const nota  = document.getElementById('decisionNota').value.trim();
    const boton = document.getElementById('decisionConfirmar');

    if (decisionEnCurso.decision === 'rechazar' && !nota) {
        mostrarError('Hay que decir por qué se rechaza: quien pidió la sala necesita saberlo.');
        document.getElementById('decisionNota').focus();
        return;
    }

    boton.disabled = true;

    try {
        let d;
        if (decisionEnCurso.decision === 'cancelar') {
            d = await pedirPost(API, { accion: 'cancelar', vista: VISTA, id: decisionEnCurso.id, respuesta_motivo: nota });
        } else {
            d = await pedirPost(API, {
                accion: 'resolver',
                vista: VISTA,
                id: decisionEnCurso.id,
                decision: decisionEnCurso.decision === 'aprobar' ? 'aprobada' : 'rechazada',
                respuesta_motivo: nota,
            });
        }

        cerrarDecision();
        await cargarReservas();

        // Si el correo no salió se dice: la decisión quedó guardada, pero
        // entonces hay que avisarle a la persona por otro lado.
        if (d.correo && d.correo.enviado === false) {
            mostrarError(`Se guardó la decisión, pero el correo no salió: ${d.correo.error} Avisale por otro medio.`);
        } else {
            avisarExito('Listo. Se le avisó por correo a quien solicitó la sala.');
        }
    } catch (e) {
        mostrarError(e.message);
    } finally {
        boton.disabled = false;
    }
}

// ------------------------------------------------------------------
// Calendario (solo para ver)
// ------------------------------------------------------------------
//
// Un clic en un día abre el detalle; no reserva nada. La aprobación sigue
// siendo obligatoria aunque el día se vea libre acá (puede haber algo que
// este calendario no cuente — ver el comentario de calendario() en el
// endpoint).

let calAnio = new Date().getFullYear();
let calMes  = new Date().getMonth() + 1;   // 1-12
let calDatos = {};   // { 'YYYY-MM-DD': [ítems...] }

function cambiarMesCalendario(delta) {
    calMes += delta;
    if (calMes > 12) { calMes = 1; calAnio++; }
    if (calMes < 1)  { calMes = 12; calAnio--; }
    cargarCalendario();
}

async function cargarCalendario() {
    try {
        const d = await pedir(`${API}?accion=calendario&anio=${calAnio}&mes=${calMes}`);
        calDatos = d.dias || {};
        pintarCalendarioSala();
    } catch (e) {
        // Un calendario que no carga no debe tapar el resto de la página.
    }
}

function pintarCalendarioSala() {
    document.getElementById('calMesTitulo').textContent =
        `${MESES_LARGOS[calMes - 1][0].toUpperCase()}${MESES_LARGOS[calMes - 1].slice(1)} ${calAnio}`;

    const primerDia = new Date(calAnio, calMes - 1, 1);
    const inicio = new Date(primerDia);
    inicio.setDate(inicio.getDate() - inicio.getDay());   // retrocede hasta el domingo

    const hoy = formatoISO(new Date());
    const grid = document.getElementById('calGridSala');
    let celdas = '';

    for (let i = 0; i < 42; i++) {
        const fecha = new Date(inicio);
        fecha.setDate(inicio.getDate() + i);
        const iso = formatoISO(fecha);
        const fueraDeMes = fecha.getMonth() !== calMes - 1;
        const items = calDatos[iso] || [];

        const clases = ['cal-celda'];
        if (fueraDeMes) clases.push('fuera-de-mes');
        if (iso === hoy) clases.push('hoy');
        if (esFinDeSemana(iso)) clases.push('cerrado');

        // Máximo 4 puntos a la vista; el resto se resume en texto, igual que
        // el "+N más" de Cronograma.
        const puntos = items.slice(0, 4)
            .map(it => `<span class="cal-punto ${it.tipo === 'bloqueo' ? 'bloqueo' : it.estado}"></span>`)
            .join('');
        const resto = items.length - 4;

        celdas += `
            <button type="button" class="${clases.join(' ')}" data-fecha="${iso}"
                    aria-label="${fecha.getDate()} de ${MESES_LARGOS[fecha.getMonth()]}${items.length ? `, ${items.length} evento(s)` : ''}">
                <span class="cal-celda-num" aria-hidden="true">${fecha.getDate()}</span>
                <span class="cal-celda-puntos" aria-hidden="true">${puntos}</span>
                ${resto > 0 ? `<span class="cal-celda-mas" aria-hidden="true">+${resto}</span>` : ''}
            </button>
        `;
    }

    grid.innerHTML = celdas;
    grid.querySelectorAll('.cal-celda:not(.fuera-de-mes)').forEach(b => {
        b.addEventListener('click', () => {
            // Funcionario no pasa por una pantalla intermedia: elegir el día
            // ya es empezar a solicitar la sala para ese día. Administración
            // sí ve el detalle primero (qué hay, de quién), porque desde ahí
            // decide qué hacer, no pide nada.
            if (VISTA === 'funcionario') {
                abrirSolicitarDesdeCalendario(b.dataset.fecha);
            } else {
                abrirDia(b.dataset.fecha);
            }
        });
    });
}

function abrirDia(iso) {
    diaMostrado = iso;
    const items = (calDatos[iso] || []).slice().sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));

    document.getElementById('diaTitulo').textContent = fechaLarga(iso);

    const cuerpo = document.getElementById('diaCuerpo');

    if (esFinDeSemana(iso)) {
        cuerpo.innerHTML = `<p class="vacio">La sala no se presta los fines de semana.</p>`;
    } else if (!items.length) {
        cuerpo.innerHTML = `<p class="vacio">Sin nada agendado. Libre de 7:00 a.m. a 4:00 p.m.</p>`;
    } else {
        cuerpo.innerHTML = items.map(it => {
            const esBloqueo = it.tipo === 'bloqueo';
            const detalle = VISTA === 'admin' && it.motivo
                ? `<div class="reserva-motivo">${escapar(it.motivo)}</div>
                   <div class="reserva-datos">${escapar(it.solicitante || '')}${it.folio ? ' · ' + escapar(it.folio) : ''}</div>`
                : '';
            return `
                <article class="reserva ${esBloqueo ? 'rechazada' : it.estado}">
                    <div class="reserva-cab">
                        <div class="reserva-cuando">${it.horaInicio} a ${it.horaFin}</div>
                        <span class="chip ${esBloqueo ? 'bloqueo' : it.estado}">
                            ${esBloqueo ? 'No disponible' : (ETIQUETA_ESTADO[it.estado] || it.estado)}
                        </span>
                    </div>
                    ${detalle}
                </article>
            `;
        }).join('');
    }

    document.getElementById('modalDia').classList.add('visible');
}

function cerrarDia() {
    document.getElementById('modalDia').classList.remove('visible');
}

/**
 * Abre el formulario de solicitud. Con fecha, la deja puesta y ya muestra la
 * ocupación de ese día; sin fecha (el botón "Solicitar la sala" de arriba),
 * abre en blanco para que la persona elija.
 */
function abrirSolicitar(fechaPrellenada) {
    document.getElementById('formSolicitud').reset();
    llenarHoras();
    ponerSelectorFecha('fechaPartes', fechaPrellenada || '', 'la fecha solicitada');
    document.getElementById('ocupacionDia').textContent = '';
    if (fechaPrellenada) { revisarOcupacion(); }
    document.getElementById('modalSolicitar').classList.add('visible');
}

function cerrarSolicitar() {
    document.getElementById('modalSolicitar').classList.remove('visible');
}

/** Un día del calendario, elegido por un funcionario: valida antes de abrir el formulario. */
function abrirSolicitarDesdeCalendario(iso) {
    if (esFinDeSemana(iso)) {
        mostrarError('La sala no se presta los fines de semana. Elegí un día de lunes a viernes.');
        return;
    }
    if (iso < formatoISO(new Date())) {
        mostrarError('Ese día ya pasó. Elegí una fecha futura.');
        return;
    }
    abrirSolicitar(iso);
}

/** yyyy-mm-dd a partir de un objeto Date, en hora local (no toISOString, que usa UTC). */
function formatoISO(fecha) {
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
}

// ------------------------------------------------------------------
// Evento manual (solo administración)
// ------------------------------------------------------------------

function prepararEventoManual() {
    const abrir = document.getElementById('btnEventoManual');
    if (!abrir) return;   // no existe en la vista de funcionario

    llenarHorasManual();
    armarSelectorFecha('manualFechaPartes', 'la fecha del evento');

    const tipo = document.getElementById('manualTipo');
    tipo.addEventListener('change', () => {
        // Un bloqueo no es "a nombre de" nadie: no tiene sentido pedirlo.
        document.getElementById('manualNombreCampo').hidden = tipo.value === 'bloqueo';
    });

    abrir.addEventListener('click', () => abrirEventoManual());

    document.getElementById('manualCerrar').addEventListener('click', () => {
        document.getElementById('modalManual').classList.remove('visible');
    });

    document.getElementById('manualGuardar').addEventListener('click', guardarEventoManual);
}

/**
 * Abre el formulario de evento manual. Con fecha (desde el botón "Agregar
 * evento manual acá" del detalle de un día), la deja puesta; sin fecha (desde
 * el botón general de arriba del calendario), abre en blanco.
 */
function abrirEventoManual(fechaPrellenada) {
    document.getElementById('manualTipo').value = 'reserva';
    document.getElementById('manualNombreCampo').hidden = false;
    document.getElementById('manualNombre').value = '';
    document.getElementById('manualMotivo').value = '';
    document.getElementById('manualInformacionAdicional').value = '';
    ponerSelectorFecha('manualFechaPartes', fechaPrellenada || '', 'la fecha del evento');
    document.getElementById('manualHoraInicio').value = '08:00';
    document.getElementById('manualHoraFin').value = '09:00';
    document.getElementById('modalManual').classList.add('visible');
}

/**
 * Todo el día, no solo 7 a.m.-4 p.m.: un arreglo o un bloqueo puede caer a
 * cualquier hora, y esto lo llena administración, no lo pide alguien.
 */
function llenarHorasManual() {
    const opciones = [];
    for (let h = 0; h <= 23; h++) {
        for (const m of ['00', '30']) {
            const valor = `${String(h).padStart(2, '0')}:${m}`;
            opciones.push(`<option value="${valor}">${valor}</option>`);
        }
    }
    document.getElementById('manualHoraInicio').innerHTML = opciones.join('');
    document.getElementById('manualHoraFin').innerHTML = opciones.join('');
}

async function guardarEventoManual() {
    const fecha  = leerSelectorFecha('manualFechaPartes');
    const inicio = document.getElementById('manualHoraInicio').value;
    const fin    = document.getElementById('manualHoraFin').value;
    const motivo = document.getElementById('manualMotivo').value.trim();

    if (!fecha)  { return errorEnCampo('Elegí el día, el mes y el año.', 'manualFechaPartesDia'); }
    if (fin <= inicio) { return errorEnCampo('La hora de fin tiene que ser posterior a la de inicio.', 'manualHoraFin'); }
    if (!motivo) { return errorEnCampo('Escribí el motivo.', 'manualMotivo'); }

    const boton = document.getElementById('manualGuardar');
    boton.disabled = true;

    try {
        await pedirPost(API, {
            accion: 'crearManual',
            tipo: document.getElementById('manualTipo').value,
            solicitante_nombre: document.getElementById('manualNombre').value.trim(),
            fecha,
            hora_inicio: inicio,
            hora_fin: fin,
            motivo,
            informacion_adicional: document.getElementById('manualInformacionAdicional').value.trim(),
        });

        document.getElementById('modalManual').classList.remove('visible');
        await cargarCalendario();
        await cargarReservas();
        avisarExito('Agregado al calendario.');
    } catch (e) {
        mostrarError(e.message);
    } finally {
        boton.disabled = false;
    }
}

// ------------------------------------------------------------------
// Selector de fecha en tres partes
// ------------------------------------------------------------------
//
// Mismo criterio que Cronograma y Situaciones Especiales: día, mes y año en
// listas separadas, con un resumen hablado de la fecha que quedó armada. El
// <input type="date"> del navegador es de lo más difícil de llenar con lector
// de pantalla.

function armarSelectorFecha(idContenedor, etiqueta) {
    const cont = document.getElementById(idContenedor);
    if (!cont || cont.dataset.armado === '1') return;
    cont.dataset.armado = '1';

    const anioActual = new Date().getFullYear();
    const anios = [anioActual, anioActual + 1];

    const dias = [];
    for (let d = 1; d <= 31; d++) { dias.push(`<option value="${d}">${d}</option>`); }

    cont.innerHTML = `
        <div>
            <label for="${idContenedor}Dia">Día</label>
            <select id="${idContenedor}Dia" aria-label="Día de ${etiqueta}"><option value="">—</option>${dias.join('')}</select>
        </div>
        <div>
            <label for="${idContenedor}Mes">Mes</label>
            <select id="${idContenedor}Mes" aria-label="Mes de ${etiqueta}">
                <option value="">—</option>
                ${MESES_LARGOS.map((m, i) => `<option value="${i + 1}">${m}</option>`).join('')}
            </select>
        </div>
        <div>
            <label for="${idContenedor}Anio">Año</label>
            <select id="${idContenedor}Anio" aria-label="Año de ${etiqueta}">
                <option value="">—</option>
                ${anios.map(a => `<option value="${a}">${a}</option>`).join('')}
            </select>
        </div>
        <p class="fecha-resumen" id="${idContenedor}Resumen" role="status" aria-live="polite"></p>
    `;

    ['Dia', 'Mes', 'Anio'].forEach(parte => {
        document.getElementById(idContenedor + parte).addEventListener('change', () => {
            resumirFecha(idContenedor, etiqueta);
            revisarOcupacion();
        });
    });
}

function resumirFecha(idContenedor, etiqueta) {
    const resumen = document.getElementById(idContenedor + 'Resumen');
    const iso = leerSelectorFecha(idContenedor);
    if (!resumen) return;
    resumen.textContent = iso ? `${etiqueta}: ${fechaLarga(iso)}` : '';
}

function leerSelectorFecha(idContenedor) {
    const d = document.getElementById(idContenedor + 'Dia').value;
    const m = document.getElementById(idContenedor + 'Mes').value;
    const a = document.getElementById(idContenedor + 'Anio').value;

    if (!d || !m || !a) return '';

    return `${a}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
}

function ponerSelectorFecha(idContenedor, iso, etiqueta) {
    const d = document.getElementById(idContenedor + 'Dia');
    const m = document.getElementById(idContenedor + 'Mes');
    const a = document.getElementById(idContenedor + 'Anio');
    if (!d) return;

    if (!iso) {
        d.value = ''; m.value = ''; a.value = '';
    } else {
        const p = iso.split('-');
        a.value = String(Number(p[0]));
        m.value = String(Number(p[1]));
        d.value = String(Number(p[2]));
    }

    resumirFecha(idContenedor, etiqueta);
}

// ------------------------------------------------------------------
// Apoyo
// ------------------------------------------------------------------

function fechaLarga(iso) {
    const f = new Date(iso + 'T00:00:00');
    return `${DIAS_LARGO[f.getDay()]} ${f.getDate()} de ${MESES_LARGOS[f.getMonth()]} de ${f.getFullYear()}`;
}

function fechaHoraLegible(txt) {
    if (!txt) return '—';
    const [dia, hora] = String(txt).split(' ');
    const [a, m, d] = dia.split('-');
    return `${d}/${m}/${a}${hora ? ' a las ' + hora.slice(0, 5) : ''}`;
}

/** Manda el foco al campo que falta, además de decir qué pasó. */
function errorEnCampo(mensaje, idCampo) {
    mostrarError(mensaje);
    const campo = document.getElementById(idCampo);
    if (campo) campo.focus();
}

function avisarExito(mensaje, alCerrar) {
    // El modal compartido de todo el sistema (includes/ui_accesible.php): el
    // aviso se lee solo y no hay que ir a buscarlo arriba de la página.
    // `alCerrar` (opcional) corre después de que la persona cierra el aviso —
    // se usa para cerrar el formulario que quedaba abierto detrás, cuando
    // corresponde, en vez de dejarlo ahí sin motivo.
    if (window.PaaUI) { PaaUI.exito(mensaje, { alCerrar }); return; }
    ponerAviso(mensaje, 'exito');
    if (alCerrar) { alCerrar(); }
}

function mostrarError(mensaje) {
    if (window.PaaUI) { PaaUI.error(mensaje); return; }
    ponerAviso(mensaje, 'error');
}

function ponerAviso(mensaje, clase) {
    const caja = document.getElementById('aviso');
    caja.textContent = mensaje;
    caja.className = `aviso visible ${clase}`;
}

async function pedir(url) {
    const r = await fetch(url + (url.includes('?') ? '&' : '?') + '_ts=' + Date.now(), { cache: 'no-store' });
    return leerRespuesta(r);
}

async function pedirPost(url, cuerpo) {
    const r = await fetch(url, {
        method: 'POST',
        cache: 'no-store',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ ...cuerpo, csrf: CSRF }),
    });
    return leerRespuesta(r);
}

async function leerRespuesta(r) {
    const texto = await r.text();
    let d;
    try {
        d = JSON.parse(texto);
    } catch (e) {
        throw new Error(`El servidor no respondió correctamente (código ${r.status}).`);
    }
    if (!d.success) throw new Error(d.error || 'No se pudo completar la operación');
    return d;
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
