<?php
/**
 * Equipo de Funcionarios: qué computadora, teclado, UPS, etc. tiene
 * asignado cada funcionario del PAA, y el sondeo mensual de si ese equipo
 * sigue bien. Distinto de "Inventario de Oficina", que es el equipo fijo
 * del edificio sin dueño particular.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo('equipo_funcionarios', '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Equipo de Funcionarios · UCR</title>
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
    --secondary-light: #e3f0fa;
    --success: #10b981;
    --success-bg: #d1fae5;
    --warning: #f59e0b;
    --warning-bg: #fef3c7;
    --danger: #ef4444;
    --danger-bg: #fee2e2;
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
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
    color: var(--gray-800);
    line-height: 1.5;
    min-height: 100vh;
    padding: 1.5rem;
}

.app { max-width: 1080px; margin: 0 auto; }

.barra-sesion {
    display: flex; justify-content: space-between; align-items: center;
    gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
    font-size: 0.875rem; color: var(--gray-600);
}
.barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
.barra-sesion a:hover { text-decoration: underline; }

.hero { text-align: center; margin-bottom: 1.75rem; }
.hero-badge {
    display: inline-flex; background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 12px; padding: 6px 14px; margin-bottom: .6rem;
}
.hero-badge img { height: 28px; width: auto; display: block; }
.hero h1 {
    font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
    -webkit-background-clip: text; background-clip: text; color: transparent;
    margin-bottom: .35rem;
}
.hero p { color: var(--gray-600); font-size: .95rem; max-width: 640px; margin: 0 auto; }

.resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .8rem; margin-bottom: 1.25rem; }
.tarjeta-resumen {
    background: #fff; border: 1px solid var(--gray-200); border-radius: 12px;
    padding: 1rem 1.2rem;
}
.tarjeta-resumen .num { font-size: 1.7rem; font-weight: 800; color: var(--gray-900); }
.tarjeta-resumen .etq { font-size: .78rem; color: var(--gray-500); font-weight: 600; }
.tarjeta-resumen.alerta .num { color: var(--danger); }
.tarjeta-resumen.aviso .num { color: var(--warning); }

.tarjeta {
    background: #fff; border: 1px solid var(--gray-200); border-radius: 14px;
    padding: 1.4rem 1.5rem; margin-bottom: 1.25rem;
}
.tarjeta h2 { font-size: 1.05rem; display: flex; align-items: center; gap: .5rem; margin-bottom: .9rem; color: var(--gray-900); }
.tarjeta h2 i { color: var(--primary); }

.aviso { display: none; padding: .7rem 1rem; border-radius: 8px; font-size: .85rem; margin-bottom: .9rem; }
.aviso.exito { display: block; background: var(--success-bg); color: #065f46; }
.aviso.error { display: block; background: var(--danger-bg); color: #991b1b; }
.aviso.info  { display: block; background: var(--secondary-light); color: #1e4a6e; }

.btn-primario, .btn-secundario, .btn-peligro {
    font-family: inherit; font-weight: 600; font-size: .85rem; border-radius: 8px;
    padding: .55rem 1rem; cursor: pointer; border: none; display: inline-flex;
    align-items: center; gap: .4rem;
}
.btn-primario { background: var(--primary); color: #fff; }
.btn-primario:hover { background: var(--primary-dark); }
.btn-secundario { background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-300); }
.btn-secundario:hover { background: var(--gray-200); }
.btn-peligro { background: #fff; color: var(--danger); border: 1px solid var(--danger); }
.btn-peligro:hover { background: var(--danger-bg); }
.btn-chico { padding: .35rem .7rem; font-size: .76rem; }
button:disabled { opacity: .5; cursor: not-allowed; }

.campos { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .9rem; margin-bottom: .9rem; }
.campo.ancho { grid-column: 1 / -1; }
.campo label { display: block; font-size: .78rem; font-weight: 600; color: var(--gray-600); margin-bottom: .3rem; }
.campo input, .campo select, .campo textarea {
    width: 100%; border: 1.5px solid var(--gray-300); border-radius: 7px; padding: .55rem .7rem;
    font-size: .87rem; font-family: inherit; color: var(--gray-800); background: var(--gray-50);
}
.campo input:focus, .campo select:focus, .campo textarea:focus {
    outline: none; border-color: var(--primary-light); background: #fff;
    box-shadow: 0 0 0 3px rgba(43,127,194,.15);
}
.campo textarea { min-height: 60px; resize: vertical; }

.barra-lista { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem; }
.barra-lista input[type="text"] {
    flex: 1; min-width: 220px; border: 1.5px solid var(--gray-300); border-radius: 22px;
    padding: .55rem 1rem; font-size: .85rem; font-family: inherit;
}
.barra-lista input[type="text"]:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(43,127,194,.15); }
.barra-lista select { border: 1.5px solid var(--gray-300); border-radius: 8px; padding: .5rem .6rem; font-size: .82rem; font-family: inherit; }

.persona {
    display: block; width: 100%; text-align: left; font-family: inherit;
    border: 1px solid var(--gray-200); border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: .7rem;
    background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s;
}
.persona:hover { border-color: var(--primary-light); box-shadow: 0 2px 10px rgba(0,85,164,.08); }
.persona:focus-visible { outline: 3px solid var(--primary-light); outline-offset: 2px; }
.persona-cab { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start; }
.persona-area { font-size: .7rem; color: var(--gray-400); font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
.persona-nombre { font-weight: 700; color: var(--gray-900); font-size: .98rem; margin: .1rem 0 .3rem; }
.persona-meta { display: flex; gap: .9rem; flex-wrap: wrap; font-size: .78rem; color: var(--gray-500); }
.persona-meta span { display: inline-flex; align-items: center; gap: .3rem; }

.etiqueta { display: inline-block; padding: .2rem .6rem; border-radius: 20px; font-size: .68rem; font-weight: 700; }
.etiqueta.ok { background: var(--success-bg); color: #065f46; }
.etiqueta.vencido { background: var(--danger-bg); color: #991b1b; }
.etiqueta.porvencer { background: var(--warning-bg); color: #92400e; }
.etiqueta.sinequipo { background: var(--gray-100); color: var(--gray-500); }

.vacio { text-align: center; padding: 2.5rem 1rem; color: var(--gray-400); }

/* ---------- panel de detalle ---------- */
.fondo-modal {
    display: none; position: fixed; inset: 0; background: rgba(17,24,39,.55);
    align-items: center; justify-content: center; z-index: 100; padding: 1rem;
}
.fondo-modal.visible { display: flex; }
.modal { background: #fff; border-radius: 14px; padding: 1.5rem; max-width: 520px; width: 100%; max-height: 85vh; overflow-y: auto; }
.modal.ancho { max-width: 720px; }
.modal h3 { margin-bottom: 1rem; color: var(--gray-900); }
.modal-cerrar { float: right; background: none; border: none; font-size: 1.3rem; color: var(--gray-400); cursor: pointer; }

.pestanas { display: flex; gap: .4rem; border-bottom: 1px solid var(--gray-200); margin-bottom: 1rem; }
.pestana {
    font-family: inherit; font-weight: 600; font-size: .82rem; color: var(--gray-500);
    background: none; border: none; padding: .6rem .3rem; cursor: pointer; border-bottom: 2px solid transparent;
}
.pestana.activa { color: var(--primary); border-bottom-color: var(--primary); }
.panel-pestana { display: none; }
.panel-pestana.activa { display: block; }

.equipo-item { border: 1px solid var(--gray-200); border-radius: 10px; padding: .8rem 1rem; margin-bottom: .6rem; }
.equipo-item.retirado { opacity: .65; background: var(--gray-50); }
.equipo-item-cab { display: flex; justify-content: space-between; gap: .8rem; flex-wrap: wrap; }
.equipo-item-tipo { font-size: .68rem; color: var(--gray-400); font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
.equipo-item-nombre { font-weight: 700; color: var(--gray-900); font-size: .92rem; margin: .05rem 0 .25rem; }
.equipo-item-meta { font-size: .76rem; color: var(--gray-500); }
.equipo-item-obs { font-size: .8rem; color: var(--gray-600); margin-top: .4rem; }
.equipo-item-acciones { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .6rem; }

.historial-item { border-bottom: 1px solid var(--gray-100); padding: .6rem 0; font-size: .83rem; }
.historial-item:last-child { border-bottom: none; }
.historial-fecha { font-weight: 700; color: var(--gray-800); }
.historial-meta { color: var(--gray-500); font-size: .76rem; }
.historial-icono { display: inline-flex; width: 1.2em; }

.sondeo-item { border-bottom: 1px solid var(--gray-100); padding: .6rem 0; font-size: .83rem; }
.sondeo-item:last-child { border-bottom: none; }

@media (max-width: 640px) {
    body { padding: 1.2rem .8rem 3rem; }
    .modal { padding: 1.15rem; }
}
</style>
</head>
<body>
<div class="app">

    <div class="barra-sesion">
        <span><i class="fa-regular fa-user" aria-hidden="true"></i> <?= h($yo['nombre']) ?></span>
        <span><a href="<?= h(base_url()) ?>salir.php">Salir</a></span>
    </div>

    <div class="hero">
        <div class="hero-badge"><img src="../assets/logo-paa-blanco.png" alt="Gestión PAA"></div>
        <h1>Equipo de Funcionarios</h1>
        <p>Qué equipo tiene asignado cada funcionario del PAA, con bitácora de cambios y sondeo
           mensual para revisar si todo sigue bien o hicieron falta arreglos.</p>
    </div>

    <div class="resumen" id="resumen"></div>

    <div class="tarjeta">
        <h2><i class="fa-solid fa-users" aria-hidden="true"></i> Funcionarios</h2>

        <div class="barra-lista">
            <input type="text" id="busqueda" placeholder="Buscar por nombre o área...">
            <select id="filtroSondeo">
                <option value="todos">Todos</option>
                <option value="vencido">Sondeo vencido</option>
                <option value="porvencer">Sondeo por vencer</option>
                <option value="sinequipo">Sin equipo asignado</option>
            </select>
        </div>

        <div id="listaFuncionarios"></div>
    </div>

</div>

<!-- ===== MODAL: DETALLE DE FUNCIONARIO ===== -->
<div class="fondo-modal" id="modalDetalle">
    <div class="modal ancho" role="dialog" aria-modal="true" aria-labelledby="detNombre">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalDetalle')" aria-label="Cerrar detalle de funcionario">&times;</button>
        <h3 id="detNombre" tabindex="-1"></h3>
        <p style="font-size:.82rem;color:var(--gray-500);margin:-.6rem 0 1rem;" id="detArea"></p>

        <div class="pestanas">
            <button type="button" class="pestana activa" data-pestana="equipo" onclick="cambiarPestana('equipo')">Equipo</button>
            <button type="button" class="pestana" data-pestana="sondeo" onclick="cambiarPestana('sondeo')">Sondeo mensual</button>
            <button type="button" class="pestana" data-pestana="bitacora" onclick="cambiarPestana('bitacora')">Bitácora</button>
        </div>

        <!-- ---- EQUIPO ---- -->
        <div class="panel-pestana activa" id="panelEquipo">
            <div id="avisoEquipo" class="aviso"></div>

            <button type="button" class="btn-secundario btn-chico" id="btnAbrirAgregarEquipo" style="margin-bottom:.9rem;">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar equipo
            </button>

            <form id="formAgregarEquipo" style="display:none;margin-bottom:1.1rem;padding:.9rem;background:var(--gray-50);border-radius:10px;">
                <div class="campos">
                    <div class="campo">
                        <label for="eqTipo">Tipo <span style="color:var(--danger)">*</span></label>
                        <select id="eqTipo" onchange="cambiarTipoOtro('eqTipo','eqTipoOtro')" required></select>
                    </div>
                    <div class="campo" id="eqTipoOtroCampo" style="display:none;">
                        <label for="eqTipoOtro">Cuál tipo</label>
                        <input type="text" id="eqTipoOtro" maxlength="60">
                    </div>
                    <div class="campo ancho">
                        <label for="eqNombre">Nombre / descripción <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="eqNombre" maxlength="200" placeholder="Ej: Laptop Dell Latitude 5420" required>
                    </div>
                    <div class="campo"><label for="eqMarca">Marca</label><input type="text" id="eqMarca" maxlength="100"></div>
                    <div class="campo"><label for="eqModelo">Modelo</label><input type="text" id="eqModelo" maxlength="100"></div>
                    <div class="campo"><label for="eqSerie">Serie</label><input type="text" id="eqSerie" maxlength="100"></div>
                    <div class="campo">
                        <label for="eqFecha">Fecha de asignación</label>
                        <input type="date" id="eqFecha">
                    </div>
                    <div class="campo ancho"><label for="eqObservaciones">Observaciones</label><textarea id="eqObservaciones" maxlength="1000"></textarea></div>
                </div>
                <div style="display:flex;gap:.6rem;">
                    <button type="submit" class="btn-primario btn-chico"><i class="fa-solid fa-check" aria-hidden="true"></i> Guardar</button>
                    <button type="button" class="btn-secundario btn-chico" onclick="cerrarFormAgregarEquipo()">Cancelar</button>
                </div>
            </form>

            <div id="listaEquipoPersona"></div>
        </div>

        <!-- ---- SONDEO ---- -->
        <div class="panel-pestana" id="panelSondeo">
            <div id="avisoSondeo" class="aviso"></div>
            <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:1rem;" id="sondeoEstado"></p>

            <form id="formSondeo" style="margin-bottom:1.2rem;">
                <div class="campos">
                    <div class="campo">
                        <label for="soFecha">Fecha</label>
                        <input type="date" id="soFecha">
                    </div>
                    <div class="campo">
                        <label for="soResultado">Resultado <span style="color:var(--danger)">*</span></label>
                        <select id="soResultado" required>
                            <option value="bien">Todo está bien</option>
                            <option value="arreglos">Se necesitaron arreglos</option>
                        </select>
                    </div>
                    <div class="campo ancho">
                        <label for="soObservaciones">Observaciones</label>
                        <input type="text" id="soObservaciones" maxlength="500" placeholder="Qué se revisó o qué se arregló">
                    </div>
                </div>
                <button type="submit" class="btn-primario btn-chico"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Registrar sondeo</button>
            </form>

            <h4 style="font-size:.85rem;color:var(--gray-700);margin-bottom:.5rem;">Sondeos anteriores</h4>
            <div id="listaSondeos"></div>
        </div>

        <!-- ---- BITÁCORA ---- -->
        <div class="panel-pestana" id="panelBitacora">
            <div id="listaHistorial"></div>
        </div>
    </div>
</div>

<!-- ===== MODAL: EDITAR EQUIPO ===== -->
<div class="fondo-modal" id="modalEditarEquipo">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="tituloEditarEquipo">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalEditarEquipo')" aria-label="Cerrar editar equipo">&times;</button>
        <h3 id="tituloEditarEquipo" tabindex="-1">Editar equipo</h3>
        <div id="avisoEditarEquipo" class="aviso"></div>
        <form id="formEditarEquipo">
            <input type="hidden" id="edId">
            <div class="campos">
                <div class="campo">
                    <label for="edTipo">Tipo</label>
                    <select id="edTipo" onchange="cambiarTipoOtro('edTipo','edTipoOtro')" required></select>
                </div>
                <div class="campo" id="edTipoOtroCampo" style="display:none;">
                    <label for="edTipoOtro">Cuál tipo</label>
                    <input type="text" id="edTipoOtro" maxlength="60">
                </div>
                <div class="campo ancho">
                    <label for="edNombre">Nombre / descripción</label>
                    <input type="text" id="edNombre" maxlength="200" required>
                </div>
                <div class="campo"><label for="edMarca">Marca</label><input type="text" id="edMarca" maxlength="100"></div>
                <div class="campo"><label for="edModelo">Modelo</label><input type="text" id="edModelo" maxlength="100"></div>
                <div class="campo"><label for="edSerie">Serie</label><input type="text" id="edSerie" maxlength="100"></div>
                <div class="campo"><label for="edFecha">Fecha de asignación</label><input type="date" id="edFecha"></div>
                <div class="campo ancho"><label for="edObservaciones">Observaciones</label><textarea id="edObservaciones" maxlength="1000"></textarea></div>
            </div>
            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-primario"><i class="fa-solid fa-check" aria-hidden="true"></i> Guardar cambios</button>
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalEditarEquipo')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL: RETIRAR EQUIPO ===== -->
<div class="fondo-modal" id="modalRetirarEquipo">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="tituloRetirarEquipo">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalRetirarEquipo')" aria-label="Cerrar retirar equipo">&times;</button>
        <h3 id="tituloRetirarEquipo" tabindex="-1">Retirar equipo</h3>
        <div id="avisoRetirarEquipo" class="aviso"></div>
        <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:1rem;" id="retirarTextoEquipo"></p>
        <form id="formRetirarEquipo">
            <input type="hidden" id="rtId">
            <div class="campo ancho" style="margin-bottom:1rem;">
                <label for="rtMotivo">Motivo <span style="color:var(--danger)">*</span></label>
                <select id="rtMotivo" onchange="cambiarTipoOtro('rtMotivo','rtMotivoOtro')" required>
                    <option value="Se le entregó a otra persona">Se le entregó a otra persona</option>
                    <option value="Se dañó / ya no sirve">Se dañó / ya no sirve</option>
                    <option value="Se devolvió al inventario de oficina">Se devolvió al inventario de oficina</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="campo ancho" id="rtMotivoOtroCampo" style="display:none;margin-bottom:1rem;">
                <label for="rtMotivoOtro">Cuál motivo</label>
                <input type="text" id="rtMotivoOtro" maxlength="255">
            </div>
            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-peligro"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Retirar</button>
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalRetirarEquipo')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>

<script>
const API = '../api/equipo_funcionarios.php';
const CSRF = <?= json_encode(token_csrf()) ?>;
const TIPOS_FIJOS = ['Computadora', 'Laptop', 'Monitor', 'Teclado', 'Mouse', 'UPS', 'Impresora', 'Audífonos', 'Cable', 'Otro'];

let funcionarios = [];
let tiposSugeridos = TIPOS_FIJOS;
let resumenSondeo = { vencidos: 0, porVencer: 0 };
let personaActual = null;

function escapar(t) {
    return String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function mostrar(id, texto, tipo) {
    const el = document.getElementById(id);
    el.textContent = texto;
    el.className = 'aviso ' + tipo;
}

async function pedir(url, opciones) {
    const r = await fetch(url, opciones);
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'Ocurrió un error');
    return d;
}

async function pedirJson(accion, cuerpo) {
    return pedir(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion, ...cuerpo, csrf: CSRF }),
    });
}

// ===================== SELECTS DE TIPO =====================
function llenarSelectTipos(select) {
    select.innerHTML = tiposSugeridos.map(t => `<option value="${escapar(t)}">${escapar(t)}</option>`).join('');
}

window.cambiarTipoOtro = function (idSelect, idOtro) {
    const select = document.getElementById(idSelect);
    const campoOtro = document.getElementById(idOtro + 'Campo') || document.getElementById(idOtro).closest('.campo');
    const esOtro = select.value === 'Otro';
    if (campoOtro) campoOtro.style.display = esOtro ? '' : 'none';
};

function valorTipo(idSelect, idOtro) {
    const select = document.getElementById(idSelect);
    if (select.value === 'Otro') {
        return document.getElementById(idOtro).value.trim();
    }
    return select.value;
}

// ===================== CARGA INICIAL =====================
document.addEventListener('DOMContentLoaded', () => {
    llenarSelectTipos(document.getElementById('eqTipo'));
    llenarSelectTipos(document.getElementById('edTipo'));
    cargarFuncionarios();

    document.getElementById('busqueda').addEventListener('input', pintarLista);
    document.getElementById('filtroSondeo').addEventListener('change', pintarLista);

    document.getElementById('btnAbrirAgregarEquipo').addEventListener('click', () => {
        const form = document.getElementById('formAgregarEquipo');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('formAgregarEquipo').addEventListener('submit', agregarEquipo);
    document.getElementById('formEditarEquipo').addEventListener('submit', guardarEdicionEquipo);
    document.getElementById('formRetirarEquipo').addEventListener('submit', confirmarRetiro);
    document.getElementById('formSondeo').addEventListener('submit', guardarSondeo);
});

async function cargarFuncionarios() {
    const cont = document.getElementById('listaFuncionarios');
    cont.innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Cargando...</div>';

    try {
        const d = await pedir(`${API}?accion=listar`, { cache: 'no-store' });
        funcionarios = d.funcionarios;
        tiposSugeridos = d.tiposSugeridos && d.tiposSugeridos.length ? d.tiposSugeridos : TIPOS_FIJOS;
        resumenSondeo = d.resumenSondeo;

        llenarSelectTipos(document.getElementById('eqTipo'));
        llenarSelectTipos(document.getElementById('edTipo'));

        pintarResumen(d.total);
        pintarLista();
    } catch (e) {
        cont.innerHTML = `<div class="vacio"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> ${escapar(e.message)}</div>`;
    }
}

function pintarResumen(total) {
    document.getElementById('resumen').innerHTML = `
        <div class="tarjeta-resumen">
            <div class="num">${total}</div>
            <div class="etq">Funcionarios</div>
        </div>
        <div class="tarjeta-resumen ${resumenSondeo.vencidos > 0 ? 'alerta' : ''}">
            <div class="num">${resumenSondeo.vencidos}</div>
            <div class="etq">Sondeo vencido</div>
        </div>
        <div class="tarjeta-resumen ${resumenSondeo.porVencer > 0 ? 'aviso' : ''}">
            <div class="num">${resumenSondeo.porVencer}</div>
            <div class="etq">Por vencer (7 días)</div>
        </div>
    `;
}

// ===================== LISTA / BÚSQUEDA =====================
function pintarLista() {
    const termino = document.getElementById('busqueda').value.trim().toLowerCase();
    const filtro = document.getElementById('filtroSondeo').value;

    const filtrados = funcionarios.filter(p => {
        const texto = [p.nombre, p.area].join(' ').toLowerCase();
        if (termino && !texto.includes(termino)) return false;

        if (filtro === 'vencido' && !p.sondeoVencido) return false;
        if (filtro === 'porvencer' && !p.sondeoPorVencer) return false;
        if (filtro === 'sinequipo' && p.totalEquipo > 0) return false;

        return true;
    });

    const cont = document.getElementById('listaFuncionarios');

    if (!filtrados.length) {
        cont.innerHTML = '<div class="vacio"><i class="fa-regular fa-face-smile" aria-hidden="true"></i> No hay funcionarios que mostrar.</div>';
        return;
    }

    cont.innerHTML = filtrados.map(pintarFuncionario).join('');
}

function etiquetaSondeo(p) {
    if (p.totalEquipo === 0) return `<span class="etiqueta sinequipo"><i class="fa-solid fa-minus" aria-hidden="true"></i> Sin equipo asignado</span>`;
    if (p.sondeoVencido) return `<span class="etiqueta vencido"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Sondeo vencido</span>`;
    if (p.sondeoPorVencer) return `<span class="etiqueta porvencer"><i class="fa-regular fa-clock" aria-hidden="true"></i> Sondeo en ${p.diasParaSondeo} día(s)</span>`;
    return `<span class="etiqueta ok"><i class="fa-solid fa-check" aria-hidden="true"></i> Al día</span>`;
}

function pintarFuncionario(p) {
    const etiquetaTexto = p.totalEquipo === 0 ? 'sin equipo asignado'
        : p.sondeoVencido ? 'sondeo vencido'
        : p.sondeoPorVencer ? `sondeo por vencer en ${p.diasParaSondeo} día(s)`
        : 'sondeo al día';
    const etiquetaAria = `Ver detalle de ${escapar(p.nombre)}, ${escapar(p.area)}, ${p.totalEquipo} equipo(s), ${etiquetaTexto}`;
    return `
        <button type="button" class="persona" onclick="abrirDetalle(${p.id})" aria-label="${etiquetaAria}">
            <div class="persona-cab">
                <div>
                    <div class="persona-area">${escapar(p.area)}</div>
                    <div class="persona-nombre">${escapar(p.nombre)}</div>
                    <div class="persona-meta">
                        <span><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> ${p.totalEquipo} equipo(s)</span>
                        ${p.ultimoSondeo ? `<span><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Último sondeo: ${escapar(p.ultimoSondeo)}</span>` : ''}
                    </div>
                </div>
                <div>${etiquetaSondeo(p)}</div>
            </div>
        </button>
    `;
}

// ===================== DETALLE DE FUNCIONARIO =====================
async function abrirDetalle(id) {
    const p = funcionarios.find(x => x.id === id);
    if (!p) return;

    personaActual = id;
    document.getElementById('detNombre').textContent = p.nombre;
    document.getElementById('detArea').textContent = p.area;
    document.getElementById('avisoEquipo').className = 'aviso';
    document.getElementById('formAgregarEquipo').style.display = 'none';
    document.getElementById('formAgregarEquipo').reset();

    cambiarPestana('equipo');
    abrirModalConFoco('modalDetalle', document.getElementById('detNombre'));

    document.getElementById('listaEquipoPersona').innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Cargando...</div>';
    document.getElementById('listaSondeos').innerHTML = '';
    document.getElementById('listaHistorial').innerHTML = '';

    await recargarDetalle();
}

async function recargarDetalle() {
    if (!personaActual) return;
    try {
        const d = await pedir(`${API}?accion=detalle&personaId=${personaActual}`, { cache: 'no-store' });
        pintarEquipoPersona(d.equipos);
        pintarSondeos(d.sondeos);
        pintarHistorial(d.historial);
        pintarEstadoSondeo(d.contador, d.sondeos[0] || null);
    } catch (e) {
        document.getElementById('listaEquipoPersona').innerHTML = `<div class="vacio">${escapar(e.message)}</div>`;
    }
}

function cambiarPestana(nombre) {
    document.querySelectorAll('.pestana').forEach(b => b.classList.toggle('activa', b.dataset.pestana === nombre));
    document.querySelectorAll('.panel-pestana').forEach(p => p.classList.remove('activa'));
    document.getElementById('panel' + nombre.charAt(0).toUpperCase() + nombre.slice(1)).classList.add('activa');
}

let equipoPersonaActual = [];

function pintarEquipoPersona(equipos) {
    equipoPersonaActual = equipos;
    const cont = document.getElementById('listaEquipoPersona');

    if (!equipos.length) {
        cont.innerHTML = '<div class="vacio"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Todavía no tiene equipo asignado.</div>';
        return;
    }

    cont.innerHTML = equipos.map(eq => {
        const retirado = eq.estado === 'retirado';
        const acciones = retirado ? '' : `
            <button type="button" class="btn-secundario btn-chico" onclick="abrirEditarEquipo(${eq.id})" aria-label="Editar ${escapar(eq.nombre)}">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> Editar</button>
            <button type="button" class="btn-peligro btn-chico" onclick="abrirRetirarEquipo(${eq.id})" aria-label="Retirar ${escapar(eq.nombre)}">
                <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Retirar</button>
        `;

        return `
            <div class="equipo-item ${retirado ? 'retirado' : ''}">
                <div class="equipo-item-cab">
                    <div>
                        <div class="equipo-item-tipo">${escapar(eq.tipo)}</div>
                        <div class="equipo-item-nombre">${escapar(eq.nombre)}</div>
                        <div class="equipo-item-meta">
                            ${[eq.marca, eq.modelo].filter(Boolean).map(escapar).join(' ')}
                            ${eq.serie ? ` · Serie: ${escapar(eq.serie)}` : ''}
                            ${eq.fecha_asignacion ? ` · Desde ${escapar(eq.fecha_asignacion)}` : ''}
                        </div>
                    </div>
                    ${retirado ? '<span class="etiqueta sinequipo">Retirado</span>' : ''}
                </div>
                ${eq.observaciones ? `<div class="equipo-item-obs">${escapar(eq.observaciones)}</div>` : ''}
                ${retirado && eq.motivo_retiro ? `<div class="equipo-item-obs"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> ${escapar(eq.motivo_retiro)}</div>` : ''}
                <div class="equipo-item-acciones">${acciones}</div>
            </div>
        `;
    }).join('');
}

async function agregarEquipo(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const tipo = valorTipo('eqTipo', 'eqTipoOtro');
        if (!tipo) throw new Error('Elegí o escribí el tipo de equipo');

        await pedirJson('crearEquipo', {
            personaId: personaActual,
            tipo,
            nombre: document.getElementById('eqNombre').value.trim(),
            marca: document.getElementById('eqMarca').value.trim(),
            modelo: document.getElementById('eqModelo').value.trim(),
            serie: document.getElementById('eqSerie').value.trim(),
            fechaAsignacion: document.getElementById('eqFecha').value,
            observaciones: document.getElementById('eqObservaciones').value.trim(),
        });

        mostrar('avisoEquipo', 'Equipo agregado.', 'exito');
        cerrarFormAgregarEquipo();
        await recargarDetalle();
        cargarFuncionarios();
    } catch (err) {
        mostrar('avisoEquipo', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

function cerrarFormAgregarEquipo() {
    document.getElementById('formAgregarEquipo').style.display = 'none';
    document.getElementById('formAgregarEquipo').reset();
    cambiarTipoOtro('eqTipo', 'eqTipoOtro');
}

function abrirEditarEquipo(id) {
    const eq = equipoPersonaActual.find(x => x.id === id);
    if (!eq) return;

    document.getElementById('avisoEditarEquipo').className = 'aviso';
    document.getElementById('edId').value = eq.id;
    document.getElementById('edNombre').value = eq.nombre;
    document.getElementById('edMarca').value = eq.marca || '';
    document.getElementById('edModelo').value = eq.modelo || '';
    document.getElementById('edSerie').value = eq.serie || '';
    document.getElementById('edFecha').value = eq.fecha_asignacion || '';
    document.getElementById('edObservaciones').value = eq.observaciones || '';

    const select = document.getElementById('edTipo');
    const yaExiste = tiposSugeridos.includes(eq.tipo);
    if (!yaExiste) {
        select.value = 'Otro';
        document.getElementById('edTipoOtro').value = eq.tipo;
    } else {
        select.value = eq.tipo;
        document.getElementById('edTipoOtro').value = '';
    }
    cambiarTipoOtro('edTipo', 'edTipoOtro');

    abrirModalConFoco('modalEditarEquipo', document.getElementById('tituloEditarEquipo'));
}

async function guardarEdicionEquipo(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const tipo = valorTipo('edTipo', 'edTipoOtro');
        if (!tipo) throw new Error('Elegí o escribí el tipo de equipo');

        await pedirJson('actualizarEquipo', {
            id: document.getElementById('edId').value,
            personaId: personaActual,
            tipo,
            nombre: document.getElementById('edNombre').value.trim(),
            marca: document.getElementById('edMarca').value.trim(),
            modelo: document.getElementById('edModelo').value.trim(),
            serie: document.getElementById('edSerie').value.trim(),
            fechaAsignacion: document.getElementById('edFecha').value,
            observaciones: document.getElementById('edObservaciones').value.trim(),
        });

        cerrarModal('modalEditarEquipo');
        await recargarDetalle();
        cargarFuncionarios();
    } catch (err) {
        mostrar('avisoEditarEquipo', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

function abrirRetirarEquipo(id) {
    const eq = equipoPersonaActual.find(x => x.id === id);
    if (!eq) return;

    document.getElementById('avisoRetirarEquipo').className = 'aviso';
    document.getElementById('rtId').value = eq.id;
    document.getElementById('retirarTextoEquipo').textContent = `${eq.tipo} — ${eq.nombre}`;
    document.getElementById('rtMotivo').value = 'Se le entregó a otra persona';
    document.getElementById('rtMotivoOtro').value = '';
    cambiarTipoOtro('rtMotivo', 'rtMotivoOtro');

    abrirModalConFoco('modalRetirarEquipo', document.getElementById('tituloRetirarEquipo'));
}

async function confirmarRetiro(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const motivo = valorTipo('rtMotivo', 'rtMotivoOtro');
        if (!motivo) throw new Error('Indicá el motivo');

        await pedirJson('retirarEquipo', {
            id: document.getElementById('rtId').value,
            personaId: personaActual,
            motivo,
        });

        cerrarModal('modalRetirarEquipo');
        await recargarDetalle();
        cargarFuncionarios();
    } catch (err) {
        mostrar('avisoRetirarEquipo', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================== SONDEO =====================
function pintarEstadoSondeo(contador, ultimo) {
    const el = document.getElementById('sondeoEstado');
    if (!contador.proximoSondeo) {
        el.textContent = 'Todavía no tiene equipo asignado, así que no hay sondeo que sondear.';
        return;
    }
    if (contador.sondeoVencido) {
        el.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="color:var(--danger);" aria-hidden="true"></i> El sondeo está vencido desde el ${escapar(contador.proximoSondeo)}.`;
    } else if (contador.sondeoPorVencer) {
        el.innerHTML = `<i class="fa-regular fa-clock" style="color:var(--warning);" aria-hidden="true"></i> Toca sondear en ${contador.diasParaSondeo} día(s), el ${escapar(contador.proximoSondeo)}.`;
    } else {
        el.innerHTML = `<i class="fa-solid fa-check" style="color:var(--success);" aria-hidden="true"></i> Al día. Próximo sondeo: ${escapar(contador.proximoSondeo)}.`;
    }
    document.getElementById('soFecha').value = new Date().toISOString().slice(0, 10);
}

function pintarSondeos(sondeos) {
    const cont = document.getElementById('listaSondeos');
    if (!sondeos.length) {
        cont.innerHTML = '<p style="font-size:.82rem;color:var(--gray-400);">Todavía no hay sondeos registrados.</p>';
        return;
    }
    cont.innerHTML = sondeos.map(s => `
        <div class="sondeo-item">
            <div class="historial-fecha">
                ${escapar(s.fecha)}
                ${s.resultado === 'bien'
                    ? '<span class="etiqueta ok" style="margin-left:.4rem;">Todo bien</span>'
                    : '<span class="etiqueta porvencer" style="margin-left:.4rem;">Se hicieron arreglos</span>'}
            </div>
            <div class="historial-meta">${escapar(s.realizado_por || 'Sin indicar')}</div>
            ${s.observaciones ? `<div>${escapar(s.observaciones)}</div>` : ''}
        </div>
    `).join('');
}

async function guardarSondeo(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        await pedirJson('registrarSondeo', {
            personaId: personaActual,
            fecha: document.getElementById('soFecha').value,
            resultado: document.getElementById('soResultado').value,
            observaciones: document.getElementById('soObservaciones').value.trim(),
        });

        mostrar('avisoSondeo', 'Sondeo registrado.', 'exito');
        document.getElementById('soObservaciones').value = '';
        await recargarDetalle();
        cargarFuncionarios();
    } catch (err) {
        mostrar('avisoSondeo', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================== BITÁCORA =====================
const ICONOS_EVENTO = {
    equipo_agregado: 'fa-solid fa-plus',
    equipo_editado: 'fa-solid fa-pen',
    equipo_retirado: 'fa-solid fa-box-archive',
    equipo_reactivado: 'fa-solid fa-rotate-left',
    sondeo: 'fa-solid fa-clipboard-check',
};

function pintarHistorial(historial) {
    const cont = document.getElementById('listaHistorial');
    if (!historial.length) {
        cont.innerHTML = '<div class="vacio"><i class="fa-regular fa-clock" aria-hidden="true"></i> Todavía no hay movimientos.</div>';
        return;
    }
    cont.innerHTML = historial.map(h => `
        <div class="historial-item">
            <div class="historial-fecha">
                <span class="historial-icono"><i class="${ICONOS_EVENTO[h.tipo_evento] || 'fa-solid fa-circle-info'}" aria-hidden="true"></i></span>
                ${escapar(h.creado_en)}
            </div>
            <div class="historial-meta">${escapar(h.realizado_por || 'Sin indicar')}</div>
            <div>${escapar(h.descripcion)}</div>
        </div>
    `).join('');
}

// ===================== MODALES =====================
let elementoFocoPrevio = null;

function abrirModalConFoco(id, elementoAEnfocar) {
    elementoFocoPrevio = document.activeElement;
    document.getElementById(id).classList.add('visible');
    if (elementoAEnfocar) elementoAEnfocar.focus();
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('visible');
    if (elementoFocoPrevio && typeof elementoFocoPrevio.focus === 'function') {
        elementoFocoPrevio.focus();
        elementoFocoPrevio = null;
    }
}
document.querySelectorAll('.fondo-modal').forEach(fondo => {
    fondo.addEventListener('click', e => { if (e.target === fondo) cerrarModal(fondo.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const abierto = document.querySelector('.fondo-modal.visible');
        if (abierto) cerrarModal(abierto.id);
    }
});
</script>
</body>
</html>
