<?php
/**
 * Administración de Usuarios.
 *
 * Crear cuentas, cambiar roles, restablecer contraseñas olvidadas, activar,
 * desactivar y eliminar accesos.
 *
 * Solo para administración: la guarda va antes de imprimir nada.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo("usuarios", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Usuarios · UCR</title>
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

        .hero { text-align: center; margin-bottom: 2rem; }

        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text; background-clip: text; color: transparent;
            margin-bottom: 0.35rem;
        }

        .hero p { color: var(--gray-600); }

        .tarjeta {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .tarjeta h2 {
            font-size: 1.05rem; font-weight: 600; color: var(--gray-700);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }

        .campos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
        }

        .campo { display: flex; flex-direction: column; gap: 0.35rem; }

        label { font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); }

        input, select {
            font-family: inherit; font-size: 0.9rem;
            padding: 0.6rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius); background: #fff; width: 100%;
        }

        input:focus, select:focus {
            outline: 2px solid var(--secondary); outline-offset: -1px;
        }

        button {
            font-family: inherit; font-size: 0.875rem; font-weight: 600;
            padding: 0.6rem 1.1rem; border: none; border-radius: var(--radius);
            cursor: pointer; display: inline-flex; align-items: center; gap: 0.45rem;
        }

        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover { background: var(--primary-dark); }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover { background: var(--gray-200); }
        .btn-peligro { background: #fee2e2; color: #991b1b; }
        .btn-peligro:hover { background: #fecaca; }
        .btn-chico { padding: 0.35rem 0.7rem; font-size: 0.78rem; }

        .acciones { display: flex; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap; }

        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }

        th {
            text-align: left; font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: var(--gray-500); padding: 0.6rem 0.7rem;
            border-bottom: 2px solid var(--gray-200); white-space: nowrap;
        }

        td { padding: 0.7rem; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        tr:hover td { background: var(--gray-50); }

        .tabla-scroll { overflow-x: auto; }

        .etiqueta {
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.04em; padding: 0.2rem 0.6rem;
            border-radius: 2rem; white-space: nowrap;
        }

        .rol-funcionario   { background: var(--gray-100); color: var(--gray-600); }
        .rol-administrador { background: #dbeafe; color: #1e40af; }
        .rol-desarrollador { background: #ede9fe; color: #5b21b6; }
        .rol-extraordinario { background: #fef3c7; color: #92400e; }

        .estado-si { color: var(--success); font-weight: 600; }
        .estado-no { color: var(--gray-400); }
        .aviso-clave { color: var(--warning); font-weight: 600; }

        .fila-acciones { display: flex; gap: 0.4rem; flex-wrap: wrap; }

        /* Casilla de "autoriza datos base": un interruptor, no un checkbox
           suelto — se toca seguido y a simple vista tiene que verse si está
           prendido o apagado, no solo "marcado". */
        .switch-base {
            position: relative; display: inline-block; width: 2.4rem; height: 1.35rem;
            cursor: pointer;
        }
        .switch-base input { opacity: 0; width: 0; height: 0; position: absolute; }
        .switch-base .pista {
            position: absolute; inset: 0; background: var(--gray-300);
            border-radius: 999px; transition: background .15s ease;
        }
        .switch-base .pista::after {
            content: ''; position: absolute; top: 2px; left: 2px;
            width: 1.05rem; height: 1.05rem; background: #fff; border-radius: 50%;
            transition: transform .15s ease; box-shadow: 0 1px 2px rgba(0,0,0,.25);
        }
        .switch-base input:checked + .pista { background: var(--success); }
        .switch-base input:checked + .pista::after { transform: translateX(1.05rem); }
        .switch-base input:disabled + .pista { opacity: .6; cursor: not-allowed; }
        .switch-base input:focus-visible + .pista { outline: 2px solid var(--secondary); outline-offset: 2px; }

        .aviso {
            padding: 0.85rem 1.1rem; border-radius: var(--radius);
            margin-bottom: 1rem; font-size: 0.9rem; display: none;
        }
        .aviso.exito { background: #d1fae5; color: #065f46; display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; display: block; }

        .vacio { text-align: center; padding: 2.5rem 1rem; color: var(--gray-400); }

        .barra-sesion {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;
            font-size: 0.875rem; color: var(--gray-600);
        }

        .barra-sesion a {
            color: var(--primary); text-decoration: none; font-weight: 600;
        }
        .barra-sesion a:hover { text-decoration: underline; }

        .nota {
            font-size: 0.8125rem; color: var(--gray-500);
            background: var(--gray-50); border-left: 3px solid var(--gray-300);
            padding: 0.75rem 1rem; border-radius: 0 var(--radius) var(--radius) 0;
            margin-top: 1rem; line-height: 1.6;
        }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .tarjeta { padding: 1.15rem; }
        }

        /* ===== Modal de módulos por usuario ===== */
        .modal-fondo {
            display: none;
            position: fixed; inset: 0; z-index: 999;
            background: rgba(15, 23, 42, 0.45);
            align-items: center; justify-content: center;
            padding: 1rem;
        }
        .modal-fondo.abierto { display: flex; }
        .modal-caja {
            background: #fff; border-radius: var(--radius-lg);
            width: min(560px, 100%); max-height: min(640px, 90vh);
            display: flex; flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,.35);
        }
        .modal-cabecera {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: .75rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--gray-200);
        }
        .modal-cuerpo { padding: 1.1rem 1.25rem; overflow-y: auto; flex: 1; }
        .modal-pie {
            display: flex; justify-content: flex-end; gap: .6rem;
            padding: 1rem 1.25rem; border-top: 1px solid var(--gray-200);
        }
        .modulos-categoria { margin-bottom: 1rem; }
        .modulos-categoria h3 {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; color: var(--gray-500); margin-bottom: .5rem;
        }
        .modulo-fila {
            display: flex; align-items: center; gap: .55rem;
            padding: .4rem .5rem; border-radius: .5rem; font-size: .875rem;
        }
        .modulo-fila:hover { background: var(--gray-50); }
        .modulo-fila input { width: auto; }
        .modulo-fila .etiqueta-rol {
            font-size: .68rem; color: var(--gray-500); margin-left: auto;
        }
        .modulo-fila.no-otorgable { opacity: .55; }
        .modulo-fila.no-otorgable label { cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="app">

        <div class="barra-sesion">
            <span>
                <i class="fa-regular fa-user"></i>
                <?= h($yo['nombre']) ?> ·
                <span class="etiqueta rol-<?= h($yo['rol']) ?>"><?= h($yo['rol']) ?></span>
            </span>
            <span>
                <a href="<?= h(base_url()) ?>cambiar_contrasena.php">Cambiar mi contraseña</a> ·
                <a href="<?= h(base_url()) ?>salir.php">Salir</a>
            </span>
        </div>

        <div class="hero">
            <div style="display:inline-flex;background:linear-gradient(135deg,#0055a4,#2b7fc2);border-radius:12px;padding:6px 14px;margin-bottom:.6rem;">
                <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA" style="height:28px;width:auto;display:block;">
            </div>
            <h1>Administración de Usuarios</h1>
            <p>Cuentas de acceso al sistema PAA</p>
        </div>

        <div id="aviso" class="aviso"></div>

        <!-- ===== CREAR ===== -->
        <div class="tarjeta">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
                <h2 style="margin:0;"><i class="fa-solid fa-user-plus"></i> Crear una cuenta</h2>
                <button type="button" class="btn-primario btn-chico" id="btnAbrirCrear">
                    <i class="fa-solid fa-plus"></i> Crear cuenta
                </button>
            </div>

            <div id="cajaCrear" style="display:none;margin-top:1.1rem;">
            <form id="formCrear">
                <div class="campos">
                    <div class="campo">
                        <label for="persona">Persona del directorio</label>
                        <select id="persona" name="persona_id">
                            <option value="">— Alguien nuevo —</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="rol">Rol</label>
                        <select id="rol" name="rol">
                            <option value="funcionario">Funcionario — directorio y tickets</option>
                            <option value="extraordinario">Personal extraordinario — solo Ingreso de Datos</option>
                            <option value="administrador">Administrador — todo el sistema</option>
                            <?php if ($yo['rol'] === ROL_DESARROLLADOR): ?>
                                <option value="desarrollador">Desarrollador — todo y oculto</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="campo" id="campoNombre">
                        <label for="nombre">Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" maxlength="150"
                               placeholder="Karen Calvo Díaz">
                    </div>

                    <div class="campo" id="campoCorreo">
                        <label for="correo">Correo institucional</label>
                        <input type="email" id="correo" name="correo" maxlength="150"
                               placeholder="nombre.apellido@ucr.ac.cr">
                    </div>

                    <div class="campo" id="campoArea">
                        <label for="area">Área</label>
                        <input type="text" id="area" name="area" maxlength="100"
                               list="listaAreas" placeholder="Equipo de Verbal">
                        <datalist id="listaAreas"></datalist>
                    </div>

                    <div class="campo" id="campoTelefonos">
                        <label for="telefonos">Teléfonos</label>
                        <input type="text" id="telefonos" name="telefonos" maxlength="250"
                               placeholder="8874-0425 / 4701-0551">
                    </div>

                    <div class="campo" id="campoExtension">
                        <label for="extension">Extensión</label>
                        <input type="text" id="extension" name="extension" maxlength="20"
                               placeholder="6035">
                    </div>
                </div>

                <div class="acciones">
                    <button type="submit" class="btn-primario">
                        <i class="fa-solid fa-plus"></i> Crear cuenta
                    </button>
                    <button type="reset" class="btn-secundario">
                        <i class="fa-solid fa-eraser"></i> Limpiar
                    </button>
                    <button type="button" class="btn-secundario" id="btnCerrarCrear">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                </div>
            </form>

            <div class="nota">
                El <strong>nombre de usuario</strong> se arma solo: es el correo hasta
                la arroba. La cuenta nace con la contraseña inicial
                <code><?= h(CONTRASENA_INICIAL) ?></code> y el sistema le pide a la
                persona que elija una propia la primera vez que entre.<br>
                El <strong>área, los teléfonos y la extensión</strong> son los datos
                que se muestran en el Directorio de Funcionarios. Si hay varios
                teléfonos, separalos con una barra.
            </div>
            </div>
        </div>

        <!-- ===== LISTADO ===== -->
        <div class="tarjeta">
            <h2><i class="fa-solid fa-users"></i> Cuentas del sistema</h2>

            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre y contacto</th>
                            <th>Área</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th title="Permiso para desbloquear la edición de datos base en Ingreso de Datos">Datos base</th>
                            <th>Contraseña</th>
                            <th>Último ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTabla">
                        <tr><td colspan="9" class="vacio">
                            <i class="fa-solid fa-spinner fa-spin"></i> Cargando…
                        </td></tr>
                    </tbody>
                </table>
            </div>

            <div class="nota">
                <strong>¿Alguien olvidó su contraseña?</strong> Usá «Restablecer»: le
                devuelve la contraseña inicial y le pide una nueva al entrar. Las
                contraseñas se guardan cifradas y no se pueden consultar, ni siquiera
                desde acá — por eso la única salida es restablecerla.
            </div>
        </div>
    </div>

    <!-- ===== MÓDULOS: excepciones por usuario ===== -->
    <div class="modal-fondo" id="modalModulosFondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <div>
                    <h2 style="margin:0;font-size:1.05rem;"><i class="fa-solid fa-table-cells"></i> Módulos de <span id="modalModulosNombre"></span></h2>
                    <p style="margin:.2rem 0 0;font-size:.8rem;color:var(--gray-500);">
                        Premarcados los que le tocan por su rol (<span id="modalModulosRol"></span>). Marcá o
                        destildá para darle una excepción puntual sin cambiarle el rol.
                    </p>
                </div>
                <button type="button" class="btn-secundario btn-chico" onclick="cerrarModulos()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="modalModulosCuerpo" class="modal-cuerpo">
                <div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
            </div>
            <div class="modal-pie">
                <button type="button" class="btn-secundario" onclick="cerrarModulos()">Cancelar</button>
                <button type="button" class="btn-primario" id="btnGuardarModulos" onclick="guardarModulos()">
                    <i class="fa-solid fa-check"></i> Guardar
                </button>
            </div>
        </div>
    </div>

<script>
const API = '../api/usuarios.php';
const CSRF = <?= json_encode(token_csrf()) ?>;
let yo = null;
let cuentas = [];   // lo último que devolvió el servidor, para editar sin volver a pedirlo

document.addEventListener('DOMContentLoaded', () => {
    cargar();
    cargarPersonas();
    cargarAreas();
    document.getElementById('formCrear').addEventListener('submit', crear);
    document.getElementById('persona').addEventListener('change', alternarCampos);
    alternarCampos();

    document.getElementById('btnAbrirCrear').addEventListener('click', () => {
        document.getElementById('cajaCrear').style.display = '';
        document.getElementById('btnAbrirCrear').style.display = 'none';
    });
    document.getElementById('btnCerrarCrear').addEventListener('click', cerrarCrear);
});

function cerrarCrear() {
    document.getElementById('cajaCrear').style.display = 'none';
    document.getElementById('btnAbrirCrear').style.display = '';
    document.getElementById('formCrear').reset();
    alternarCampos();
}

/** Sugerencias de área, para no escribirlas distinto cada vez. */
async function cargarAreas() {
    try {
        const r = await fetch(`${API}?accion=areas`, { cache: 'no-store' });
        const d = await r.json();
        if (!d.success) return;

        document.getElementById('listaAreas').innerHTML =
            d.areas.map(a => `<option value="${escapar(a.nombre)}"></option>`).join('');
    } catch (e) {
        console.warn('No se pudieron cargar las áreas:', e);
    }
}

/** Si se elige a alguien del directorio, sus datos ya existen. */
function alternarCampos() {
    const nuevo = document.getElementById('persona').value === '';
    ['campoNombre', 'campoCorreo', 'campoArea'].forEach(id => {
        document.getElementById(id).style.display = nuevo ? '' : 'none';
    });
    document.getElementById('nombre').required = nuevo;
    document.getElementById('correo').required = nuevo;
}

async function cargarPersonas() {
    try {
        const r = await fetch(`${API}?accion=sinCuenta`, { cache: 'no-store' });
        const d = await r.json();
        if (!d.success) return;

        const sel = document.getElementById('persona');
        sel.innerHTML = '<option value="">— Alguien nuevo —</option>' +
            d.personas.map(p =>
                `<option value="${p.id}">${escapar(p.nombre)} (${escapar(p.correo || 'sin correo')})</option>`
            ).join('');
    } catch (e) {
        console.warn('No se pudo cargar el directorio:', e);
    }
}

async function cargar() {
    const cuerpo = document.getElementById('cuerpoTabla');

    try {
        const r = await fetch(`${API}?accion=listar&_ts=${Date.now()}`, { cache: 'no-store' });
        const d = await r.json();

        if (!d.success) throw new Error(d.error || 'No se pudo consultar');

        yo = d.yo;
        cuentas = d.usuarios;

        if (!d.usuarios.length) {
            cuerpo.innerHTML = '<tr><td colspan="9" class="vacio">No hay cuentas todavía.</td></tr>';
            return;
        }

        cuerpo.innerHTML = d.usuarios.map(fila).join('');

    } catch (error) {
        cuerpo.innerHTML = `<tr><td colspan="9" class="vacio">
            <i class="fa-solid fa-triangle-exclamation"></i>
            ${escapar(error.message)}</td></tr>`;
    }
}

function fila(u) {
    const yoMismo = yo && u.id === yo.id;

    const estado = u.activo
        ? '<span class="estado-si">Activa</span>'
        : '<span class="estado-no">Desactivada</span>';

    const clave = u.debeCambiar
        ? '<span class="aviso-clave">Inicial — sin cambiar</span>'
        : '<span class="estado-si">Propia</span>';

    const bloqueo = u.bloqueado
        ? ' <span class="etiqueta" style="background:#fee2e2;color:#991b1b;">bloqueada</span>'
        : '';

    // Sobre uno mismo no se ofrecen las acciones que dejarían el sistema sin
    // administración: el servidor también las rechaza.
    const acciones = [
        `<button class="btn-secundario btn-chico" onclick="editar(${u.id})">
            <i class="fa-solid fa-pen"></i> Editar</button>`,
        `<button class="btn-secundario btn-chico" onclick="restablecer(${u.id}, '${escapar(u.usuario)}')">
            <i class="fa-solid fa-key"></i> Restablecer</button>`,
        (u.rol === 'desarrollador') ? '' :
        `<button class="btn-secundario btn-chico" onclick="abrirModulos(${u.id})">
            <i class="fa-solid fa-table-cells"></i> Módulos</button>`,
        yoMismo ? '' :
        `<button class="btn-secundario btn-chico" onclick="activar(${u.id}, ${u.activo ? 'false' : 'true'})">
            <i class="fa-solid fa-power-off"></i> ${u.activo ? 'Desactivar' : 'Activar'}</button>`,
        yoMismo ? '' :
        `<button class="btn-peligro btn-chico" onclick="eliminar(${u.id}, '${escapar(u.usuario)}')">
            <i class="fa-regular fa-trash-can"></i> Borrar</button>`
    ].filter(Boolean).join('');

    const roles = ['funcionario', 'extraordinario', 'administrador']
        .concat(yo && yo.rol === 'desarrollador' ? ['desarrollador'] : []);

    const selectorRol = yoMismo
        ? `<span class="etiqueta rol-${escapar(u.rol)}">${escapar(u.rol)}</span>`
        : `<select onchange="cambiarRol(${u.id}, this.value)" style="padding:0.3rem 0.5rem; font-size:0.8rem;">
             ${roles.map(r => `<option value="${r}" ${r === u.rol ? 'selected' : ''}>${r}</option>`).join('')}
           </select>`;

    // Administración y desarrollo ya autorizan datos base por su rol: la
    // casilla se muestra marcada y sin poder tocarse, en vez de dejar
    // desmarcar algo que el servidor igual va a seguir concediendo.
    const esAdminODev = u.rol === 'administrador' || u.rol === 'desarrollador';
    const datosBase = esAdminODev
        ? `<label class="switch-base" title="Ya lo tiene por ser ${escapar(u.rol)}">
               <input type="checkbox" checked disabled><span class="pista"></span></label>`
        : `<label class="switch-base" title="Puede desbloquear la edición de datos base en Ingreso de Datos">
               <input type="checkbox" ${u.autorizaDatosBase ? 'checked' : ''}
                      onchange="cambiarAutorizaDatosBase(${u.id}, this.checked)"><span class="pista"></span></label>`;

    // Lo que se ve en el Directorio de Funcionarios: si acá falta algo, allá
    // esa persona aparece incompleta.
    const contacto = [
        u.telefonos ? `<i class="fa-solid fa-phone"></i> ${escapar(u.telefonos)}` : '',
        u.extension ? `<i class="fa-solid fa-phone-volume"></i> ext. ${escapar(u.extension)}` : ''
    ].filter(Boolean).join(' · ');

    return `
        <tr>
            <td><strong>${escapar(u.usuario)}</strong>${bloqueo}</td>
            <td>
                ${escapar(u.nombre)}<br>
                <small style="color:var(--gray-500)">${escapar(u.correo)}</small>
                ${contacto ? `<br><small style="color:var(--gray-500)">${contacto}</small>` : ''}
            </td>
            <td>${u.area
                ? escapar(u.area)
                : '<span style="color:var(--warning)">sin área</span>'}</td>
            <td>${selectorRol}</td>
            <td>${estado}</td>
            <td>${datosBase}</td>
            <td>${clave}</td>
            <td>${u.ultimoIngreso ? formatearFecha(u.ultimoIngreso) : '<span class="estado-no">nunca</span>'}</td>
            <td><div class="fila-acciones">${acciones}</div></td>
        </tr>`;
}

async function crear(evento) {
    evento.preventDefault();

    const datos = Object.fromEntries(new FormData(evento.target).entries());
    datos.accion = 'crear';

    const respuesta = await enviar(datos);
    if (respuesta) {
        cerrarCrear();
        cargar();
        cargarPersonas();
    }
}

/**
 * Editar los datos de contacto, que son los que salen en el directorio.
 *
 * Se piden con prompt() en vez de armar un modal: son cinco campos y esta
 * pantalla es de uso ocasional. Si en algún momento se vuelve frecuente,
 * conviene cambiarlo por un formulario en su lugar.
 */
async function editar(id) {
    const u = cuentas.find(c => c.id === id);
    if (!u) return;

    const nombre = prompt('Nombre completo:', u.nombre);
    if (nombre === null) return;

    const correo = prompt(
        'Correo institucional:\n\n' +
        'OJO: el usuario para entrar sale de acá (lo de antes de la arroba).',
        u.correo
    );
    if (correo === null) return;

    const area = prompt('Área:', u.area);
    if (area === null) return;

    const telefonos = prompt('Teléfonos (separados por /):', u.telefonos);
    if (telefonos === null) return;

    const extension = prompt('Extensión:', u.extension);
    if (extension === null) return;

    await enviar({ accion: 'editar', id, nombre, correo, area, telefonos, extension });
    cargar();
    cargarAreas();
}

async function cambiarRol(id, rol) {
    await enviar({ accion: 'rol', id, rol });
    cargar();
}

async function restablecer(id, usuario) {
    if (!confirm(`¿Restablecer la contraseña de ${usuario}?\n\n` +
                 `Va a quedar con la contraseña inicial y el sistema le pedirá ` +
                 `una nueva la próxima vez que entre.`)) return;

    await enviar({ accion: 'restablecer', id });
    cargar();
}

async function activar(id, activo) {
    await enviar({ accion: 'activar', id, activo: activo ? '1' : '0' });
    cargar();
}

/** Da o quita el permiso puntual de autorizar la edición de datos base (Ingreso de Datos). */
async function cambiarAutorizaDatosBase(id, autoriza) {
    await enviar({ accion: 'autorizarDatosBase', id, autoriza: autoriza ? '1' : '0' });
    cargar();   // recarga siempre: si falló, deja la casilla como está de verdad en el servidor
}

async function eliminar(id, usuario) {
    if (!confirm(`¿Borrar la cuenta de ${usuario}?\n\n` +
                 `Pierde el acceso al sistema. La persona sigue en el directorio ` +
                 `y lo que haya registrado no se toca.`)) return;

    await enviar({ accion: 'eliminar', id });
    cargar();
}

// ===================== MÓDULOS: excepciones por usuario =====================

let catalogoModulos = null;   // el catálogo no cambia en la sesión: se pide una sola vez
let modulosUsuarioId = null;  // a quién le estamos editando las excepciones ahora mismo

async function abrirModulos(id) {
    const u = cuentas.find(c => c.id === id);
    if (!u) return;

    modulosUsuarioId = id;
    document.getElementById('modalModulosNombre').textContent = u.nombre || u.usuario;
    document.getElementById('modalModulosRol').textContent = u.rol;
    document.getElementById('modalModulosCuerpo').innerHTML =
        '<div class="vacio"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>';
    document.getElementById('modalModulosFondo').classList.add('abierto');

    try {
        if (!catalogoModulos) {
            const r = await fetch(`${API}?accion=modulos`);
            const d = await r.json();
            if (!d.success) throw new Error(d.error || 'No se pudo cargar el catálogo');
            catalogoModulos = d.modulos;
        }

        const r2 = await fetch(`${API}?accion=excepciones&id=${id}`);
        const datos = await r2.json();
        if (!datos.success) throw new Error(datos.error || 'No se pudieron cargar las excepciones');

        pintarCasillasModulos(catalogoModulos, datos.delRol, datos.agregados, datos.quitados);
    } catch (error) {
        document.getElementById('modalModulosCuerpo').innerHTML =
            `<div class="vacio">${escapar(error.message)}</div>`;
    }
}

function pintarCasillasModulos(modulos, delRol, agregados, quitados) {
    const porCategoria = {};
    modulos.forEach(m => {
        (porCategoria[m.categoria] ||= []).push(m);
    });

    const nombresCategoria = { administracion: 'Administración', embalaje: 'Embalaje', informatica: 'Informática', soporte: 'Soporte' };

    document.getElementById('modalModulosCuerpo').innerHTML = Object.entries(porCategoria).map(([cat, lista]) => `
        <div class="modulos-categoria">
            <h3>${escapar(nombresCategoria[cat] || cat)}</h3>
            ${lista.map(m => {
                // Lo tiene por rol, salvo que se le haya quitado como excepción.
                const porRol = delRol.includes(m.id) && !quitados.includes(m.id);
                // O no lo tiene por rol, pero se le agregó como excepción.
                const marcado = porRol || agregados.includes(m.id);
                // Solo se puede DAR como excepción si el módulo lo permite; quitar
                // uno que ya tiene por rol siempre se puede.
                const deshabilitado = !m.otorgable && !delRol.includes(m.id);

                return `
                    <div class="modulo-fila ${deshabilitado ? 'no-otorgable' : ''}">
                        <input type="checkbox" id="mod_${m.id}" data-modulo="${m.id}"
                               ${marcado ? 'checked' : ''} ${deshabilitado ? 'disabled' : ''}>
                        <label for="mod_${m.id}">${escapar(m.nombre)}</label>
                        ${delRol.includes(m.id) ? '<span class="etiqueta-rol">por rol</span>' : ''}
                        ${deshabilitado ? '<span class="etiqueta-rol">no se puede otorgar por excepción</span>' : ''}
                    </div>`;
            }).join('')}
        </div>
    `).join('') + `
        <input type="hidden" id="modulosDelRol" value='${JSON.stringify(delRol)}'>
    `;
}

function cerrarModulos() {
    document.getElementById('modalModulosFondo').classList.remove('abierto');
    modulosUsuarioId = null;
}

async function guardarModulos() {
    if (modulosUsuarioId === null) return;

    const delRol = JSON.parse(document.getElementById('modulosDelRol').value);
    const casillas = document.querySelectorAll('#modalModulosCuerpo input[type="checkbox"]');

    const agregados = [];
    const quitados = [];

    casillas.forEach(c => {
        const id = c.dataset.modulo;
        const marcado = c.checked;
        const porRol = delRol.includes(id);

        if (marcado && !porRol) agregados.push(id);
        if (!marcado && porRol) quitados.push(id);
    });

    const boton = document.getElementById('btnGuardarModulos');
    boton.disabled = true;
    try {
        const d = await enviar({ accion: 'guardarExcepciones', id: modulosUsuarioId, agregados, quitados });
        if (d) cerrarModulos();
    } finally {
        boton.disabled = false;
    }
}

/** Manda la petición y muestra el resultado. Devuelve los datos si salió bien. */
async function enviar(cuerpo) {
    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...cuerpo, csrf: CSRF })
        });

        const d = await r.json();

        if (!d.success) {
            mostrar(d.error || 'No se pudo completar la operación', 'error');
            return null;
        }

        mostrar(d.mensaje || 'Listo.', 'exito');
        return d;

    } catch (error) {
        mostrar('Error de conexión: ' + error.message, 'error');
        return null;
    }
}

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.textContent = mensaje;
    aviso.className = 'aviso ' + tipo;
    aviso.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (tipo === 'exito') setTimeout(() => { aviso.className = 'aviso'; }, 8000);
}

function formatearFecha(texto) {
    const f = new Date(String(texto).replace(' ', 'T'));
    if (isNaN(f)) return texto;
    return f.toLocaleString('es-CR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function escapar(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
</script>

<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
