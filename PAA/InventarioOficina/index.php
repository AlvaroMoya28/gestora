<?php
/**
 * Inventario de Oficina: el equipo fijo de la oficina (computadoras,
 * laptops, periféricos, cables...), no el que se presta a las sedes — eso
 * es el módulo "Activos de Préstamo".
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo('inventario_oficina', '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Inventario de Oficina · UCR</title>
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
.hero p { color: var(--gray-600); font-size: .95rem; max-width: 620px; margin: 0 auto; }

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
.campo-check { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--gray-700); }

/* ---------- carga masiva ---------- */
.soltar {
    border: 2px dashed var(--gray-300); border-radius: 12px; padding: 1.8rem;
    text-align: center; cursor: pointer; transition: border-color .15s, background .15s;
}
.soltar:hover, .soltar.encima { border-color: var(--primary-light); background: var(--secondary-light); }
.soltar i { font-size: 1.8rem; color: var(--primary-light); margin-bottom: .5rem; display: block; }
.soltar h3 { font-size: .95rem; color: var(--gray-800); margin-bottom: .25rem; }
.soltar p { font-size: .78rem; color: var(--gray-500); }

.cifras { display: flex; gap: .6rem; flex-wrap: wrap; margin: .9rem 0; }
.cifra { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: .5rem .8rem; font-size: .78rem; }
.cifra b { display: block; font-size: 1.1rem; color: var(--gray-900); }

table.muestra { width: 100%; border-collapse: collapse; font-size: .76rem; margin-top: .6rem; }
table.muestra th, table.muestra td { border: 1px solid var(--gray-200); padding: .35rem .5rem; text-align: left; }
table.muestra th { background: var(--gray-50); }
.tabla-scroll { overflow-x: auto; }

/* ---------- búsqueda y lista ---------- */
.barra-lista { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem; }
.barra-lista input[type="text"] {
    flex: 1; min-width: 220px; border: 1.5px solid var(--gray-300); border-radius: 22px;
    padding: .55rem 1rem; font-size: .85rem; font-family: inherit;
}
.barra-lista input[type="text"]:focus { outline: none; border-color: var(--primary-light); }
.barra-lista select { border: 1.5px solid var(--gray-300); border-radius: 8px; padding: .5rem .6rem; font-size: .82rem; font-family: inherit; }

.equipo { border: 1px solid var(--gray-200); border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: .7rem; background: #fff; }
.equipo.baja { opacity: .7; background: var(--gray-50); }
.equipo-cab { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.equipo-codigo { font-size: .7rem; color: var(--gray-400); font-weight: 700; letter-spacing: .02em; }
.equipo-nombre { font-weight: 700; color: var(--gray-900); font-size: .98rem; margin: .1rem 0 .3rem; }
.equipo-meta { display: flex; gap: .9rem; flex-wrap: wrap; font-size: .78rem; color: var(--gray-500); }
.equipo-meta span { display: inline-flex; align-items: center; gap: .3rem; }
.equipo-obs { font-size: .82rem; color: var(--gray-600); margin-top: .5rem; }

.etiqueta { display: inline-block; padding: .2rem .6rem; border-radius: 20px; font-size: .68rem; font-weight: 700; }
.etiqueta.ok { background: var(--success-bg); color: #065f46; }
.etiqueta.vencido { background: var(--danger-bg); color: #991b1b; }
.etiqueta.porvencer { background: var(--warning-bg); color: #92400e; }
.etiqueta.baja { background: var(--gray-100); color: var(--gray-500); }

.equipo-acciones { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .8rem; }
.equipo.prestamo { border-style: dashed; }
.etiqueta.prestamo { background: var(--secondary-light); color: #1e4a6e; }
.etiqueta.disponible { background: var(--success-bg); color: #065f46; }
.etiqueta.noDisponible { background: var(--warning-bg); color: #92400e; }
.etiqueta.regular { background: var(--warning-bg); color: #92400e; }
.etiqueta.danado { background: var(--danger-bg); color: #991b1b; }

.vacio { text-align: center; padding: 2.5rem 1rem; color: var(--gray-400); }

.paginacion { display: flex; justify-content: center; align-items: center; gap: .4rem; flex-wrap: wrap; margin-top: 1.2rem; }
.paginacion button {
    font-family: inherit; font-weight: 600; font-size: .82rem; min-width: 2.2rem;
    padding: .4rem .55rem; border-radius: 7px; border: 1.5px solid var(--gray-300);
    background: #fff; color: var(--gray-700); cursor: pointer;
}
.paginacion button:hover:not(:disabled) { border-color: var(--primary-light); color: var(--primary); }
.paginacion button.actual { background: var(--primary); border-color: var(--primary); color: #fff; }
.paginacion button:disabled { opacity: .4; cursor: not-allowed; }
.paginacion .info-pagina { font-size: .78rem; color: var(--gray-500); margin: 0 .4rem; }

.aviso-existe {
    display: none; background: var(--warning-bg); color: #92400e; border-radius: 8px;
    padding: .7rem 1rem; font-size: .84rem; margin-bottom: .9rem;
    align-items: center; justify-content: space-between; gap: .8rem; flex-wrap: wrap;
}
.aviso-existe.visible { display: flex; }

.bloque-serie { border: 1px dashed var(--gray-300); border-radius: 10px; padding: .8rem .9rem; margin-bottom: .9rem; }
.bloque-serie summary { cursor: pointer; font-size: .83rem; font-weight: 600; color: var(--gray-600); }
.bloque-serie summary::-webkit-details-marker { color: var(--primary-light); }
.bloque-serie .campos { margin: .8rem 0 0; }
.bloque-serie-nota { font-size: .76rem; color: var(--gray-500); margin-top: .2rem; }

/* ---------- modales ---------- */
.fondo-modal {
    display: none; position: fixed; inset: 0; background: rgba(17,24,39,.55);
    align-items: center; justify-content: center; z-index: 100; padding: 1rem;
}
.fondo-modal.visible { display: flex; }
.modal { background: #fff; border-radius: 14px; padding: 1.5rem; max-width: 520px; width: 100%; max-height: 85vh; overflow-y: auto; }
.modal h3 { margin-bottom: 1rem; color: var(--gray-900); }
.modal-cerrar { float: right; background: none; border: none; font-size: 1.3rem; color: var(--gray-400); cursor: pointer; }

.historial-item { border-bottom: 1px solid var(--gray-100); padding: .6rem 0; font-size: .83rem; }
.historial-item:last-child { border-bottom: none; }
.historial-fecha { font-weight: 700; color: var(--gray-800); }
.historial-meta { color: var(--gray-500); font-size: .76rem; }

@media (max-width: 640px) {
    body { padding: 1.2rem .8rem 3rem; }
    .modal { padding: 1.15rem; }
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
        <div class="hero-badge"><img src="../assets/logo-paa-blanco.png" alt="Gestión PAA"></div>
        <h1>Inventario de Oficina</h1>
        <p>El equipo fijo de la oficina: computadoras, laptops, periféricos, cables. Alta individual
           o carga masiva, bajas, y aviso de mantenimiento cada 6 meses para computadoras y laptops.</p>
    </div>

    <div class="resumen" id="resumen"></div>

    <!-- ===== REGISTRAR EQUIPO ===== -->
    <div class="tarjeta">
        <button type="button" class="btn-secundario" id="btnAbrirCrear">
            <i class="fa-solid fa-plus"></i> Registrar equipo
        </button>

        <form id="formCrear" style="display:none;margin-top:1.1rem;">
            <div id="avisoCrear" class="aviso"></div>
            <div id="avisoExisteCrear" class="aviso-existe">
                <span id="avisoExisteTexto"></span>
                <button type="button" class="btn-primario btn-chico" id="btnSumarExistente">
                    <i class="fa-solid fa-plus"></i> Sumar cantidad a ese
                </button>
            </div>
            <div class="campos">
                <div class="campo">
                    <label for="crCodigo">Código <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="crCodigo" maxlength="50" placeholder="Ej: CAB-001 (se puede repetir para lo idéntico)" list="listaCodigos" required>
                    <datalist id="listaCodigos"></datalist>
                </div>
                <div class="campo">
                    <label for="crTipo">Tipo <span style="color:var(--danger)">*</span></label>
                    <select id="crTipo" onchange="cambiarTipoOtro('crTipo','crTipoOtro'); llenarNombresSugeridos('crTipo','listaNombresCrear')" required></select>
                </div>
                <div class="campo" id="crTipoOtroCampo" style="display:none;">
                    <label for="crTipoOtro">Cuál tipo</label>
                    <input type="text" id="crTipoOtro" maxlength="60" placeholder="Escriba el tipo">
                </div>
                <div class="campo">
                    <label for="crCantidad">Cantidad <span style="color:var(--danger)">*</span></label>
                    <input type="number" id="crCantidad" min="1" step="1" value="1" required>
                </div>
                <div class="campo">
                    <label for="crCondicion">Estado del artículo</label>
                    <select id="crCondicion">
                        <option value="bueno" selected>Buen estado</option>
                        <option value="regular">Mal estado</option>
                        <option value="danado">Dañado</option>
                    </select>
                </div>
                <div class="campo ancho">
                    <label for="crNombre">Nombre / descripción <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="crNombre" maxlength="200" placeholder="Ej: Cable HDMI, Mouse USB, Laptop Dell Latitude 5420" list="listaNombresCrear" required>
                    <datalist id="listaNombresCrear"></datalist>
                </div>
                <div class="campo ancho">
                    <label for="crObservaciones">Observaciones</label>
                    <textarea id="crObservaciones" maxlength="1000"></textarea>
                </div>
            </div>

            <details class="bloque-serie">
                <summary><i class="fa-solid fa-barcode"></i> Marca, modelo y serie (opcional — para equipos individuales)</summary>
                <p class="bloque-serie-nota">Usalo para computadoras, laptops, impresoras, UPS, routers, etc. que tienen serie
                    o placa propia. Para cosas que se guardan en cantidad (cables, mouses, folletos...) se puede omitir.</p>
                <div class="campos">
                    <div class="campo"><label for="crMarca">Marca</label><input type="text" id="crMarca" maxlength="100"></div>
                    <div class="campo"><label for="crModelo">Modelo</label><input type="text" id="crModelo" maxlength="100"></div>
                    <div class="campo"><label for="crSerie">Serie / placa</label><input type="text" id="crSerie" maxlength="100"></div>
                </div>
            </details>

            <label class="campo-check" for="crRequiereMant" style="margin-bottom:1.1rem;">
                <input type="checkbox" id="crRequiereMant">
                Requiere mantenimiento cada 6 meses
            </label>

            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-primario"><i class="fa-solid fa-check"></i> Guardar</button>
                <button type="button" class="btn-secundario" onclick="cerrarFormCrear()">Cancelar</button>
            </div>
        </form>
    </div>

    <!-- ===== CARGA MASIVA ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-file-arrow-up"></i> Carga masiva</h2>
        <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:1rem;">
            Bajá la plantilla, llenala en Excel y subila. Un código que ya exista actualiza ese equipo
            en vez de duplicarlo; un equipo dado de baja no se reactiva solo con la carga.
        </p>
        <div style="margin-bottom:1rem;">
            <a class="btn-secundario" href="../api/inventario_oficina.php?accion=plantilla">
                <i class="fa-solid fa-download"></i> Descargar plantilla
            </a>
        </div>

        <div class="soltar" id="zonaSoltar">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <h3>Arrastrá el archivo acá, o tocá para buscarlo</h3>
            <p>CSV o Excel (.xlsx) — las columnas se reconocen solas</p>
        </div>
        <input type="file" id="archivo" accept=".csv,.xlsx,.xls,.txt" style="display:none;">

        <div id="analisisCarga"></div>
    </div>

    <!-- ===== LISTA ===== -->
    <div class="tarjeta">
        <h2><i class="fa-solid fa-boxes-stacked"></i> Equipo registrado</h2>

        <div class="barra-lista">
            <input type="text" id="busqueda" placeholder="Buscar por código, nombre, tipo o serie...">
            <select id="filtroMantenimiento">
                <option value="todos">Todos</option>
                <option value="vencido">Mantenimiento vencido</option>
                <option value="porvencer">Mantenimiento por vencer</option>
            </select>
            <select id="filtroEstado">
                <option value="activo">Activos</option>
                <option value="todos">Incluir dados de baja</option>
            </select>
        </div>

        <div id="listaEquipos"></div>
        <div class="paginacion" id="paginacion"></div>
    </div>

</div>

<!-- ===== MODAL: EDITAR ===== -->
<div class="fondo-modal" id="modalEditar">
    <div class="modal">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalEditar')">&times;</button>
        <h3>Editar equipo</h3>
        <div id="avisoEditar" class="aviso"></div>
        <form id="formEditar">
            <input type="hidden" id="edId">
            <div class="campos">
                <div class="campo">
                    <label for="edCodigo">Código</label>
                    <input type="text" id="edCodigo" maxlength="50" required>
                </div>
                <div class="campo">
                    <label for="edTipo">Tipo</label>
                    <select id="edTipo" onchange="cambiarTipoOtro('edTipo','edTipoOtro')" required></select>
                </div>
                <div class="campo" id="edTipoOtroCampo" style="display:none;">
                    <label for="edTipoOtro">Cuál tipo</label>
                    <input type="text" id="edTipoOtro" maxlength="60">
                </div>
                <div class="campo">
                    <label for="edCantidad">Cantidad</label>
                    <input type="number" id="edCantidad" min="1" step="1" required>
                </div>
                <div class="campo">
                    <label for="edCondicion">Estado del artículo</label>
                    <select id="edCondicion">
                        <option value="bueno">Buen estado</option>
                        <option value="regular">Mal estado</option>
                        <option value="danado">Dañado</option>
                    </select>
                </div>
                <div class="campo ancho">
                    <label for="edNombre">Nombre / descripción</label>
                    <input type="text" id="edNombre" maxlength="200" list="listaNombresEditar" required>
                    <datalist id="listaNombresEditar"></datalist>
                </div>
                <div class="campo ancho"><label for="edObservaciones">Observaciones</label><textarea id="edObservaciones" maxlength="1000"></textarea></div>
            </div>

            <details class="bloque-serie">
                <summary><i class="fa-solid fa-barcode"></i> Marca, modelo y serie (opcional)</summary>
                <div class="campos">
                    <div class="campo"><label for="edMarca">Marca</label><input type="text" id="edMarca" maxlength="100"></div>
                    <div class="campo"><label for="edModelo">Modelo</label><input type="text" id="edModelo" maxlength="100"></div>
                    <div class="campo"><label for="edSerie">Serie / placa</label><input type="text" id="edSerie" maxlength="100"></div>
                </div>
            </details>

            <label class="campo-check" for="edRequiereMant" style="margin-bottom:1.1rem;">
                <input type="checkbox" id="edRequiereMant">
                Requiere mantenimiento cada 6 meses
            </label>

            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-primario"><i class="fa-solid fa-check"></i> Guardar cambios</button>
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalEditar')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL: EDITAR ACTIVO DE PRÉSTAMO ===== -->
<div class="fondo-modal" id="modalEditarActivo">
    <div class="modal">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalEditarActivo')">&times;</button>
        <h3>Editar activo de préstamo</h3>
        <div id="avisoEditarActivo" class="aviso"></div>
        <form id="formEditarActivo">
            <input type="hidden" id="eaId">
            <div class="campos">
                <div class="campo">
                    <label for="eaCodigo">Código</label>
                    <input type="text" id="eaCodigo" maxlength="50" required>
                </div>
                <div class="campo">
                    <label for="eaCondicion">Estado del artículo</label>
                    <select id="eaCondicion">
                        <option value="bueno">Buen estado</option>
                        <option value="regular">Mal estado</option>
                        <option value="danado">Dañado</option>
                    </select>
                </div>
                <div class="campo ancho">
                    <label for="eaNombre">Nombre / descripción</label>
                    <input type="text" id="eaNombre" maxlength="200" required>
                </div>
                <div class="campo"><label for="eaSeriePlaca">Serie / placa</label><input type="text" id="eaSeriePlaca" maxlength="100"></div>
                <div class="campo"><label for="eaImei">IMEI</label><input type="text" id="eaImei" maxlength="50"></div>
                <div class="campo ancho"><label for="eaAccesorios">Accesorios</label><input type="text" id="eaAccesorios" maxlength="255"></div>
                <div class="campo ancho"><label for="eaEstadoObs">Observaciones</label><textarea id="eaEstadoObs" maxlength="1000"></textarea></div>
            </div>

            <label class="campo-check" for="eaRequiereMant" style="margin-bottom:1.1rem;">
                <input type="checkbox" id="eaRequiereMant">
                Requiere mantenimiento cada 6 meses
            </label>

            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-primario"><i class="fa-solid fa-check"></i> Guardar cambios</button>
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalEditarActivo')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL: DAR DE BAJA ===== -->
<div class="fondo-modal" id="modalBaja">
    <div class="modal">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalBaja')">&times;</button>
        <h3>Dar de baja</h3>
        <div id="avisoBaja" class="aviso"></div>
        <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:1rem;" id="bajaTextoEquipo"></p>
        <form id="formBaja">
            <input type="hidden" id="bjId">
            <div class="campo ancho" style="margin-bottom:1rem;">
                <label for="bjMotivo">Motivo <span style="color:var(--danger)">*</span></label>
                <select id="bjMotivo" onchange="cambiarTipoOtro('bjMotivo','bjMotivoOtro')" required>
                    <option value="Ya no está">Ya no está</option>
                    <option value="Se dañó / ya no sirve">Se dañó / ya no sirve</option>
                    <option value="Se dio de baja administrativamente">Se dio de baja administrativamente</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="campo ancho" id="bjMotivoOtroCampo" style="display:none;margin-bottom:1rem;">
                <label for="bjMotivoOtro">Cuál motivo</label>
                <input type="text" id="bjMotivoOtro" maxlength="255">
            </div>
            <div style="display:flex;gap:.6rem;">
                <button type="submit" class="btn-peligro"><i class="fa-solid fa-box-archive"></i> Dar de baja</button>
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalBaja')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL: MANTENIMIENTO ===== -->
<div class="fondo-modal" id="modalMantenimiento">
    <div class="modal">
        <button type="button" class="modal-cerrar" onclick="cerrarModal('modalMantenimiento')">&times;</button>
        <h3>Mantenimiento</h3>
        <div id="avisoMantenimiento" class="aviso"></div>
        <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:1rem;" id="mantTextoEquipo"></p>

        <form id="formMantenimiento" style="margin-bottom:1.2rem;">
            <input type="hidden" id="mtId">
            <div class="campos">
                <div class="campo">
                    <label for="mtFecha">Fecha</label>
                    <input type="date" id="mtFecha">
                </div>
                <div class="campo ancho">
                    <label for="mtObservaciones">Observaciones</label>
                    <input type="text" id="mtObservaciones" maxlength="500" placeholder="Qué se le hizo">
                </div>
            </div>
            <button type="submit" class="btn-primario"><i class="fa-solid fa-check"></i> Registrar mantenimiento</button>
        </form>

        <h4 style="font-size:.85rem;color:var(--gray-700);margin-bottom:.5rem;">Historial</h4>
        <div id="historialMantenimiento"></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>

<script>
const API = '../api/inventario_oficina.php';
const API_ACTIVOS = '../api/activos.php';
const CSRF = <?= json_encode(token_csrf()) ?>;
const TIPOS_FIJOS = ['Computadora', 'Laptop', 'Monitor', 'Teclado', 'Mouse', 'Impresora', 'Lectora', 'Router/Switch', 'Cable', 'Otro'];

let equipos = [];
let tiposSugeridos = TIPOS_FIJOS;
let nombresSugeridos = {};
let resumenMantenimiento = { vencidos: 0, porVencer: 0 };
let existenteSugerido = null;
let mantenimientoContexto = { api: API, id: null };

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

async function pedirJsonA(api, accion, cuerpo) {
    return pedir(api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion, ...cuerpo, csrf: CSRF }),
    });
}

async function pedirJson(accion, cuerpo) {
    return pedirJsonA(API, accion, cuerpo);
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

function llenarNombresSugeridos(idSelectTipo, idDatalist) {
    const tipo = document.getElementById(idSelectTipo).value;
    const lista = nombresSugeridos[tipo] || [];
    document.getElementById(idDatalist).innerHTML = lista.map(n => `<option value="${escapar(n)}">`).join('');
}

function llenarCodigosSugeridos() {
    const codigos = [...new Set(equipos.filter(e => e.origen === 'oficina').map(e => e.codigo))];
    document.getElementById('listaCodigos').innerHTML = codigos.map(c => `<option value="${escapar(c)}">`).join('');
}

/** Al escribir/escanear un código en "Registrar equipo", avisa si ya existe. */
function revisarCodigoExistente() {
    const codigo = document.getElementById('crCodigo').value.trim();
    const banner = document.getElementById('avisoExisteCrear');
    const eq = codigo ? equipos.find(e => e.origen === 'oficina' && e.codigo === codigo && e.estado === 'activo') : null;

    if (!eq) {
        banner.classList.remove('visible');
        existenteSugerido = null;
        return;
    }
    existenteSugerido = eq;
    document.getElementById('avisoExisteTexto').innerHTML =
        `Ya existe el código <strong>${escapar(codigo)}</strong>: ${escapar(eq.nombre)} (cantidad actual: ${eq.cantidad}).`;
    banner.classList.add('visible');
}

async function sumarAlExistente() {
    if (!existenteSugerido) return;
    const cantidad = Math.max(1, parseInt(document.getElementById('crCantidad').value, 10) || 1);
    try {
        await pedirJson('sumarCantidad', { id: existenteSugerido.id, cantidad });
        mostrar('avisoCrear', `Se sumaron ${cantidad} al código ${existenteSugerido.codigo}.`, 'exito');
        document.getElementById('formCrear').reset();
        document.getElementById('avisoExisteCrear').classList.remove('visible');
        existenteSugerido = null;
        cargarEquipos();
    } catch (err) {
        mostrar('avisoCrear', err.message, 'error');
    }
}

// ===================== CARGA INICIAL =====================
document.addEventListener('DOMContentLoaded', () => {
    llenarSelectTipos(document.getElementById('crTipo'));
    llenarSelectTipos(document.getElementById('edTipo'));
    cargarEquipos();

    document.getElementById('btnAbrirCrear').addEventListener('click', () => {
        const form = document.getElementById('formCrear');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('formCrear').addEventListener('submit', crearEquipo);
    document.getElementById('formEditar').addEventListener('submit', guardarEdicion);
    document.getElementById('formEditarActivo').addEventListener('submit', guardarEdicionActivo);
    document.getElementById('formBaja').addEventListener('submit', confirmarBaja);
    document.getElementById('formMantenimiento').addEventListener('submit', guardarMantenimiento);
    document.getElementById('crCodigo').addEventListener('input', revisarCodigoExistente);
    document.getElementById('btnSumarExistente').addEventListener('click', sumarAlExistente);

    document.getElementById('busqueda').addEventListener('input', () => { paginaActual = 1; pintarLista(); });
    document.getElementById('filtroMantenimiento').addEventListener('change', () => { paginaActual = 1; pintarLista(); });
    document.getElementById('filtroEstado').addEventListener('change', () => { paginaActual = 1; cargarEquipos(); });

    // Escaneo con pistola: manda el código y un Enter. Con el filtro ya
    // aplicado en vivo (oninput) alcanza con no dejar que Enter recargue nada.
    document.getElementById('busqueda').addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });

    configurarCargaMasiva();
});

async function cargarEquipos() {
    const cont = document.getElementById('listaEquipos');
    cont.innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';

    try {
        const incluirBaja = document.getElementById('filtroEstado').value === 'todos';
        const d = await pedir(`${API}?accion=listar${incluirBaja ? '&incluirBaja=1' : ''}`, { cache: 'no-store' });
        equipos = d.equipos;
        tiposSugeridos = d.tiposSugeridos && d.tiposSugeridos.length ? d.tiposSugeridos : TIPOS_FIJOS;
        nombresSugeridos = d.nombresSugeridos || {};
        resumenMantenimiento = d.resumenMantenimiento;

        llenarSelectTipos(document.getElementById('crTipo'));
        llenarSelectTipos(document.getElementById('edTipo'));
        llenarCodigosSugeridos();

        pintarResumen(d.total);
        pintarLista();
    } catch (e) {
        cont.innerHTML = `<div class="vacio"><i class="fa-solid fa-triangle-exclamation"></i> ${escapar(e.message)}</div>`;
    }
}

function pintarResumen(total) {
    document.getElementById('resumen').innerHTML = `
        <div class="tarjeta-resumen">
            <div class="num">${total}</div>
            <div class="etq">Equipos registrados</div>
        </div>
        <div class="tarjeta-resumen ${resumenMantenimiento.vencidos > 0 ? 'alerta' : ''}">
            <div class="num">${resumenMantenimiento.vencidos}</div>
            <div class="etq">Mantenimiento vencido</div>
        </div>
        <div class="tarjeta-resumen ${resumenMantenimiento.porVencer > 0 ? 'aviso' : ''}">
            <div class="num">${resumenMantenimiento.porVencer}</div>
            <div class="etq">Por vencer (30 días)</div>
        </div>
    `;
}

// ===================== LISTA / BÚSQUEDA / PAGINACIÓN =====================
const POR_PAGINA = 50;
let paginaActual = 1;

function pintarLista() {
    const termino = document.getElementById('busqueda').value.trim().toLowerCase();
    const filtroMant = document.getElementById('filtroMantenimiento').value;

    const filtrados = equipos.filter(eq => {
        const texto = [eq.codigo, eq.tipo, eq.nombre, eq.marca, eq.modelo, eq.serie, eq.ubicacion, eq.responsable]
            .join(' ').toLowerCase();
        if (termino && !texto.includes(termino)) return false;

        if (filtroMant === 'vencido' && !eq.mantenimientoVencido) return false;
        if (filtroMant === 'porvencer' && !eq.mantenimientoPorVencer) return false;

        return true;
    });

    const cont = document.getElementById('listaEquipos');
    const paginacion = document.getElementById('paginacion');

    if (!filtrados.length) {
        cont.innerHTML = '<div class="vacio"><i class="fa-regular fa-face-smile"></i> No hay equipos que mostrar.</div>';
        paginacion.innerHTML = '';
        return;
    }

    const totalPaginas = Math.max(1, Math.ceil(filtrados.length / POR_PAGINA));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;

    const inicio = (paginaActual - 1) * POR_PAGINA;
    const pagina = filtrados.slice(inicio, inicio + POR_PAGINA);

    cont.innerHTML = pagina.map(pintarEquipo).join('');
    pintarPaginacion(filtrados.length, totalPaginas);
}

function irAPagina(n) {
    paginaActual = n;
    pintarLista();
    document.getElementById('listaEquipos').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function pintarPaginacion(totalFiltrados, totalPaginas) {
    const cont = document.getElementById('paginacion');

    if (totalPaginas <= 1) {
        cont.innerHTML = `<span class="info-pagina">${totalFiltrados} equipo(s)</span>`;
        return;
    }

    // Máximo 7 números visibles, siempre con el actual en el medio cuando se puede.
    let desde = Math.max(1, paginaActual - 3);
    let hasta = Math.min(totalPaginas, desde + 6);
    desde = Math.max(1, hasta - 6);

    const botones = [];
    botones.push(`<button ${paginaActual === 1 ? 'disabled' : ''} onclick="irAPagina(${paginaActual - 1})"><i class="fa-solid fa-angle-left"></i></button>`);
    if (desde > 1) botones.push(`<button onclick="irAPagina(1)">1</button>`, desde > 2 ? `<span class="info-pagina">…</span>` : '');
    for (let n = desde; n <= hasta; n++) {
        botones.push(`<button class="${n === paginaActual ? 'actual' : ''}" onclick="irAPagina(${n})">${n}</button>`);
    }
    if (hasta < totalPaginas) botones.push(hasta < totalPaginas - 1 ? `<span class="info-pagina">…</span>` : '', `<button onclick="irAPagina(${totalPaginas})">${totalPaginas}</button>`);
    botones.push(`<button ${paginaActual === totalPaginas ? 'disabled' : ''} onclick="irAPagina(${paginaActual + 1})"><i class="fa-solid fa-angle-right"></i></button>`);

    cont.innerHTML = botones.join('') + `<span class="info-pagina">${totalFiltrados} equipo(s) · página ${paginaActual} de ${totalPaginas}</span>`;
}

function etiquetaCondicion(eq) {
    if (eq.condicion === 'danado') return `<span class="etiqueta danado"><i class="fa-solid fa-triangle-exclamation"></i> Dañado</span>`;
    if (eq.condicion === 'regular') return `<span class="etiqueta regular"><i class="fa-solid fa-circle-exclamation"></i> Mal estado</span>`;
    return '';
}

function etiquetaMantenimiento(eq) {
    if (!eq.requiereMantenimiento) return '';
    if (eq.mantenimientoVencido) return `<span class="etiqueta vencido"><i class="fa-solid fa-triangle-exclamation"></i> Mantenimiento vencido</span>`;
    if (eq.mantenimientoPorVencer) return `<span class="etiqueta porvencer"><i class="fa-regular fa-clock"></i> Vence en ${eq.diasParaMantenimiento} día(s)</span>`;
    return `<span class="etiqueta ok"><i class="fa-solid fa-check"></i> Al día</span>`;
}

function pintarEquipo(eq) {
    const esBaja = eq.estado === 'baja';
    const esPrestamo = eq.origen === 'prestamo';

    let acciones;
    if (esPrestamo) {
        acciones = `
            <button type="button" class="btn-secundario btn-chico" onclick="abrirEditarActivo('${eq.id}')">
                <i class="fa-solid fa-pen"></i> Editar</button>
            ${!esBaja ? `
            <button type="button" class="btn-secundario btn-chico" onclick="abrirMantenimiento('${eq.id}')">
                <i class="fa-solid fa-screwdriver-wrench"></i> Mantenimiento</button>` : ''}
            <a class="btn-secundario btn-chico" href="../Activos/"><i class="fa-solid fa-arrow-up-right-from-square"></i> Gestionar en Activos de Préstamo</a>
        `;
    } else if (esBaja) {
        acciones = `<button type="button" class="btn-secundario btn-chico" onclick="reactivar(${eq.id})">
               <i class="fa-solid fa-rotate-left"></i> Reactivar</button>`;
    } else {
        acciones = `
            <button type="button" class="btn-secundario btn-chico" onclick="abrirEditar(${eq.id})">
                <i class="fa-solid fa-pen"></i> Editar</button>
            ${eq.requiereMantenimiento ? `
            <button type="button" class="btn-secundario btn-chico" onclick="abrirMantenimiento(${eq.id})">
                <i class="fa-solid fa-screwdriver-wrench"></i> Mantenimiento</button>` : ''}
            <button type="button" class="btn-peligro btn-chico" onclick="abrirBaja(${eq.id})">
                <i class="fa-solid fa-box-archive"></i> Dar de baja</button>
        `;
    }

    let etiquetaDerecha;
    if (esPrestamo) {
        etiquetaDerecha = esBaja
            ? `<span class="etiqueta baja">De baja</span>`
            : `<span class="etiqueta ${eq.disponible ? 'disponible' : 'noDisponible'}">${eq.disponible ? 'Disponible' : 'Prestado'}</span>`;
    } else {
        etiquetaDerecha = esBaja ? `<span class="etiqueta baja">De baja</span>` : etiquetaMantenimiento(eq);
    }

    return `
        <div class="equipo ${esBaja ? 'baja' : ''} ${esPrestamo ? 'prestamo' : ''}">
            <div class="equipo-cab">
                <div>
                    <div class="equipo-codigo">${escapar(eq.codigo)} · ${escapar(eq.tipo)}${esPrestamo ? ' · <span class="etiqueta prestamo">Activo de préstamo</span>' : ''}</div>
                    <div class="equipo-nombre">${escapar(eq.nombre)}${eq.cantidad > 1 ? ` <span style="color:var(--gray-500);font-weight:600;">× ${eq.cantidad}</span>` : ''}</div>
                    <div class="equipo-meta">
                        ${eq.marca || eq.modelo ? `<span><i class="fa-solid fa-tag"></i> ${escapar([eq.marca, eq.modelo].filter(Boolean).join(' '))}</span>` : ''}
                        ${eq.serie ? `<span><i class="fa-solid fa-barcode"></i> ${escapar(eq.serie)}</span>` : ''}
                        ${esPrestamo && eq.responsable ? `<span><i class="fa-regular fa-user"></i> Prestado a ${escapar(eq.responsable)}</span>` : ''}
                    </div>
                </div>
                <div style="text-align:right;display:flex;flex-direction:column;gap:.3rem;align-items:flex-end;">${etiquetaCondicion(eq)}${etiquetaDerecha}</div>
            </div>
            ${eq.observaciones ? `<div class="equipo-obs">${escapar(eq.observaciones)}</div>` : ''}
            ${esBaja && eq.motivoBaja ? `<div class="equipo-obs"><i class="fa-solid fa-circle-info"></i> Baja: ${escapar(eq.motivoBaja)}</div>` : ''}
            <div class="equipo-acciones">${acciones}</div>
        </div>
    `;
}

// ===================== CREAR =====================
async function crearEquipo(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;
    document.getElementById('avisoExisteCrear').classList.remove('visible');

    try {
        const tipo = valorTipo('crTipo', 'crTipoOtro');
        if (!tipo) throw new Error('Elegí o escribí el tipo de equipo');

        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'crear',
                codigo: document.getElementById('crCodigo').value.trim(),
                tipo,
                cantidad: document.getElementById('crCantidad').value,
                condicion: document.getElementById('crCondicion').value,
                nombre: document.getElementById('crNombre').value.trim(),
                marca: document.getElementById('crMarca').value.trim(),
                modelo: document.getElementById('crModelo').value.trim(),
                serie: document.getElementById('crSerie').value.trim(),
                observaciones: document.getElementById('crObservaciones').value.trim(),
                requiereMantenimiento: document.getElementById('crRequiereMant').checked ? '1' : '0',
                csrf: CSRF,
            }),
        });
        const d = await r.json();

        if (!d.success) {
            if (d.yaExiste && d.existente) {
                existenteSugerido = { id: d.existente.id, codigo: document.getElementById('crCodigo').value.trim(), nombre: d.existente.nombre, cantidad: d.existente.cantidad };
                document.getElementById('avisoExisteTexto').textContent = d.error;
                document.getElementById('avisoExisteCrear').classList.add('visible');
                document.getElementById('btnSumarExistente').style.display = d.existente.estado === 'activo' ? '' : 'none';
                return;
            }
            throw new Error(d.error || 'Ocurrió un error');
        }

        mostrar('avisoCrear', 'Equipo registrado.', 'exito');
        document.getElementById('formCrear').reset();
        cambiarTipoOtro('crTipo', 'crTipoOtro');
        cargarEquipos();
    } catch (err) {
        mostrar('avisoCrear', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

function cerrarFormCrear() {
    document.getElementById('formCrear').style.display = 'none';
    document.getElementById('formCrear').reset();
}

// ===================== EDITAR =====================
function abrirEditar(id) {
    const eq = equipos.find(x => x.id === id);
    if (!eq) return;

    document.getElementById('avisoEditar').className = 'aviso';
    document.getElementById('edId').value = eq.id;
    document.getElementById('edCodigo').value = eq.codigo;
    document.getElementById('edCantidad').value = eq.cantidad;
    document.getElementById('edCondicion').value = eq.condicion || 'bueno';
    document.getElementById('edNombre').value = eq.nombre;
    document.getElementById('edMarca').value = eq.marca;
    document.getElementById('edModelo').value = eq.modelo;
    document.getElementById('edSerie').value = eq.serie;
    document.getElementById('edObservaciones').value = eq.observaciones;
    document.getElementById('edRequiereMant').checked = eq.requiereMantenimiento;

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
    llenarNombresSugeridos('edTipo', 'listaNombresEditar');

    document.getElementById('modalEditar').classList.add('visible');
}

async function guardarEdicion(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const tipo = valorTipo('edTipo', 'edTipoOtro');
        if (!tipo) throw new Error('Elegí o escribí el tipo de equipo');

        await pedirJson('actualizar', {
            id: document.getElementById('edId').value,
            codigo: document.getElementById('edCodigo').value.trim(),
            tipo,
            cantidad: document.getElementById('edCantidad').value,
            condicion: document.getElementById('edCondicion').value,
            nombre: document.getElementById('edNombre').value.trim(),
            marca: document.getElementById('edMarca').value.trim(),
            modelo: document.getElementById('edModelo').value.trim(),
            serie: document.getElementById('edSerie').value.trim(),
            observaciones: document.getElementById('edObservaciones').value.trim(),
            requiereMantenimiento: document.getElementById('edRequiereMant').checked ? '1' : '0',
        });

        cerrarModal('modalEditar');
        cargarEquipos();
    } catch (err) {
        mostrar('avisoEditar', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================== BAJA / REACTIVAR =====================
function abrirBaja(id) {
    const eq = equipos.find(x => x.id === id);
    if (!eq) return;

    document.getElementById('avisoBaja').className = 'aviso';
    document.getElementById('bjId').value = eq.id;
    document.getElementById('bajaTextoEquipo').textContent = `${eq.codigo} — ${eq.nombre}`;
    document.getElementById('bjMotivo').value = 'Ya no está';
    document.getElementById('bjMotivoOtro').value = '';
    cambiarTipoOtro('bjMotivo', 'bjMotivoOtro');

    document.getElementById('modalBaja').classList.add('visible');
}

async function confirmarBaja(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const motivo = valorTipo('bjMotivo', 'bjMotivoOtro');
        if (!motivo) throw new Error('Indicá el motivo');

        await pedirJson('darDeBaja', { id: document.getElementById('bjId').value, motivo });

        cerrarModal('modalBaja');
        cargarEquipos();
    } catch (err) {
        mostrar('avisoBaja', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

async function reactivar(id) {
    if (!confirm('¿Reactivar este equipo?')) return;
    try {
        await pedirJson('reactivar', { id });
        cargarEquipos();
    } catch (err) {
        alert(err.message);
    }
}

// ===================== EDITAR ACTIVO DE PRÉSTAMO =====================
function abrirEditarActivo(id) {
    const eq = equipos.find(x => x.id === id);
    if (!eq) return;

    document.getElementById('avisoEditarActivo').className = 'aviso';
    document.getElementById('eaId').value = eq.idReal;
    document.getElementById('eaCodigo').value = eq.codigo;
    document.getElementById('eaCondicion').value = eq.condicion || 'bueno';
    document.getElementById('eaNombre').value = eq.nombre;
    document.getElementById('eaSeriePlaca').value = eq.serie;
    document.getElementById('eaImei').value = eq.imei || '';
    document.getElementById('eaAccesorios').value = eq.accesorios || '';
    document.getElementById('eaEstadoObs').value = eq.estadoObs || '';
    document.getElementById('eaRequiereMant').checked = eq.requiereMantenimiento;

    document.getElementById('modalEditarActivo').classList.add('visible');
}

async function guardarEdicionActivo(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        await pedirJsonA(API_ACTIVOS, 'actualizar', {
            id: document.getElementById('eaId').value,
            codigo: document.getElementById('eaCodigo').value.trim(),
            condicion: document.getElementById('eaCondicion').value,
            nombre: document.getElementById('eaNombre').value.trim(),
            seriePlaca: document.getElementById('eaSeriePlaca').value.trim(),
            imei: document.getElementById('eaImei').value.trim(),
            accesorios: document.getElementById('eaAccesorios').value.trim(),
            estadoObs: document.getElementById('eaEstadoObs').value.trim(),
            requiereMantenimiento: document.getElementById('eaRequiereMant').checked ? '1' : '0',
        });

        cerrarModal('modalEditarActivo');
        cargarEquipos();
    } catch (err) {
        mostrar('avisoEditarActivo', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================== MANTENIMIENTO =====================
// Funciona tanto para equipo_oficina como para activos de préstamo: la
// diferencia es a qué endpoint y con qué id se le pega (ver mantenimientoContexto).
async function abrirMantenimiento(id) {
    const eq = equipos.find(x => x.id === id);
    if (!eq) return;

    mantenimientoContexto = {
        api: eq.origen === 'prestamo' ? API_ACTIVOS : API,
        id: eq.origen === 'prestamo' ? eq.idReal : eq.id,
    };

    document.getElementById('avisoMantenimiento').className = 'aviso';
    document.getElementById('mtId').value = mantenimientoContexto.id;
    document.getElementById('mtFecha').value = new Date().toISOString().slice(0, 10);
    document.getElementById('mtObservaciones').value = '';
    document.getElementById('mantTextoEquipo').textContent =
        `${eq.codigo} — ${eq.nombre}` + (eq.ultimoMantenimiento ? ` · Último: ${eq.ultimoMantenimiento}` : ' · Sin mantenimientos registrados');
    document.getElementById('historialMantenimiento').innerHTML = '<div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i></div>';

    document.getElementById('modalMantenimiento').classList.add('visible');

    try {
        const d = await pedir(`${mantenimientoContexto.api}?accion=historialMantenimiento&id=${mantenimientoContexto.id}`, { cache: 'no-store' });
        pintarHistorial(d.historial);
    } catch (err) {
        document.getElementById('historialMantenimiento').innerHTML = `<div class="vacio">${escapar(err.message)}</div>`;
    }
}

function pintarHistorial(historial) {
    const cont = document.getElementById('historialMantenimiento');
    if (!historial.length) {
        cont.innerHTML = '<p style="font-size:.82rem;color:var(--gray-400);">Todavía no hay mantenimientos registrados.</p>';
        return;
    }
    cont.innerHTML = historial.map(h => `
        <div class="historial-item">
            <div class="historial-fecha">${escapar(h.fecha)}</div>
            <div class="historial-meta">${escapar(h.realizado_por || 'Sin indicar')}</div>
            ${h.observaciones ? `<div>${escapar(h.observaciones)}</div>` : ''}
        </div>
    `).join('');
}

async function guardarMantenimiento(e) {
    e.preventDefault();
    const boton = e.target.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const id = document.getElementById('mtId').value;
        await pedirJsonA(mantenimientoContexto.api, 'registrarMantenimiento', {
            id,
            fecha: document.getElementById('mtFecha').value,
            observaciones: document.getElementById('mtObservaciones').value.trim(),
        });

        mostrar('avisoMantenimiento', 'Mantenimiento registrado.', 'exito');
        document.getElementById('mtObservaciones').value = '';
        const d = await pedir(`${mantenimientoContexto.api}?accion=historialMantenimiento&id=${id}`, { cache: 'no-store' });
        pintarHistorial(d.historial);
        cargarEquipos();
    } catch (err) {
        mostrar('avisoMantenimiento', err.message, 'error');
    } finally {
        boton.disabled = false;
    }
}

// ===================== MODALES =====================
function cerrarModal(id) {
    document.getElementById(id).classList.remove('visible');
}
document.querySelectorAll('.fondo-modal').forEach(fondo => {
    fondo.addEventListener('click', e => { if (e.target === fondo) fondo.classList.remove('visible'); });
});

// ===================== CARGA MASIVA =====================
let archivoElegido = null;

function configurarCargaMasiva() {
    const zona = document.getElementById('zonaSoltar');
    const input = document.getElementById('archivo');

    zona.addEventListener('click', () => input.click());
    input.addEventListener('change', () => { if (input.files.length) tomarArchivo(input.files[0]); });

    ['dragenter', 'dragover'].forEach(ev =>
        zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.add('encima'); }));
    ['dragleave', 'drop'].forEach(ev =>
        zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.remove('encima'); }));
    zona.addEventListener('drop', e => { if (e.dataTransfer.files.length) tomarArchivo(e.dataTransfer.files[0]); });
}

function tomarArchivo(archivo) {
    archivoElegido = archivo;
    document.getElementById('analisisCarga').innerHTML =
        `<div class="aviso info"><i class="fa-solid fa-spinner fa-spin"></i> Leyendo ${escapar(archivo.name)}...</div>`;
    analizarArchivo();
}

async function enviarArchivo(accion) {
    const cuerpo = new FormData();
    cuerpo.append('accion', accion);
    cuerpo.append('archivo', archivoElegido);
    cuerpo.append('csrf', CSRF);

    const r = await fetch(API, { method: 'POST', body: cuerpo });
    let d;
    try {
        d = await r.json();
    } catch {
        throw new Error('El servidor no devolvió una respuesta válida. Probá con un archivo más liviano.');
    }
    if (!d.success) throw new Error(d.error || 'No se pudo procesar el archivo');
    return d;
}

async function analizarArchivo() {
    try {
        const d = await enviarArchivo('analizar');
        pintarAnalisis(d);
    } catch (e) {
        document.getElementById('analisisCarga').innerHTML =
            `<div class="aviso error"><i class="fa-solid fa-triangle-exclamation"></i> ${escapar(e.message)}</div>`;
    }
}

function pintarAnalisis(d) {
    const c = d.conteos;
    const reconocidas = Object.entries(d.mapa)
        .map(([campo, col]) => `<div><strong>${escapar(campo)}</strong> ← <code>${escapar(col)}</code></div>`).join('');

    const filasMuestra = d.muestra.slice(0, 5);
    const columnasMuestra = filasMuestra.length ? Object.keys(filasMuestra[0]) : [];

    document.getElementById('analisisCarga').innerHTML = `
        <div class="aviso info" style="display:block;">
            <i class="fa-regular fa-file"></i> <strong>${escapar(archivoElegido.name)}</strong> ·
            ${escapar(d.formato)} · ${(archivoElegido.size / 1024).toFixed(0)} KB
        </div>
        <div class="cifras">
            <div class="cifra"><b>${c.filas}</b>filas leídas</div>
            <div class="cifra"><b>${c.nuevos}</b>nuevos</div>
            <div class="cifra"><b>${c.actualizados}</b>se actualizan</div>
            <div class="cifra"><b>${c.deBaja}</b>de baja (no se tocan)</div>
            <div class="cifra"><b>${c.sinDatos}</b>sin datos obligatorios</div>
            <div class="cifra"><b>${c.repetidas}</b>códigos repetidos</div>
        </div>
        <div style="margin-bottom:.8rem;">
            <strong style="font-size:.8rem;">Columnas reconocidas</strong>
            <div style="font-size:.8rem;color:var(--gray-600);">${reconocidas || 'Ninguna'}</div>
        </div>
        ${d.ignoradas.length ? `<div style="margin-bottom:.8rem;font-size:.8rem;color:var(--gray-500);">
            Se ignoran: ${d.ignoradas.map(escapar).join(', ')}</div>` : ''}
        ${filasMuestra.length ? `
        <div class="tabla-scroll">
            <table class="muestra">
                <tr>${columnasMuestra.map(c => `<th>${escapar(c)}</th>`).join('')}</tr>
                ${filasMuestra.map(f => `<tr>${columnasMuestra.map(c => `<td>${escapar(f[c])}</td>`).join('')}</tr>`).join('')}
            </table>
        </div>` : ''}
        <div style="margin-top:1rem;display:flex;gap:.6rem;">
            <button class="btn-primario" id="btnConfirmarCarga" ${c.nuevos + c.actualizados === 0 ? 'disabled' : ''}>
                <i class="fa-solid fa-database"></i> Cargar ${c.nuevos + c.actualizados} equipo(s)
            </button>
            <button class="btn-secundario" onclick="cancelarCarga()">Cancelar</button>
        </div>
    `;

    const boton = document.getElementById('btnConfirmarCarga');
    if (boton) boton.addEventListener('click', confirmarCarga);
}

async function confirmarCarga() {
    const boton = document.getElementById('btnConfirmarCarga');
    boton.disabled = true;
    boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando...';

    try {
        const d = await enviarArchivo('cargar');
        document.getElementById('analisisCarga').innerHTML =
            `<div class="aviso exito" style="display:block;"><i class="fa-solid fa-circle-check"></i> ${escapar(d.mensaje)}</div>`;
        archivoElegido = null;
        document.getElementById('archivo').value = '';
        cargarEquipos();
    } catch (e) {
        document.getElementById('analisisCarga').innerHTML +=
            `<div class="aviso error" style="display:block;margin-top:.6rem;"><i class="fa-solid fa-triangle-exclamation"></i> ${escapar(e.message)}</div>`;
    }
}

function cancelarCarga() {
    archivoElegido = null;
    document.getElementById('archivo').value = '';
    document.getElementById('analisisCarga').innerHTML = '';
}
</script>
</body>
</html>
