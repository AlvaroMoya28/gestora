<?php
/**
 * Módulo Horarios.
 *
 * El horario semanal recurrente de cada quien: franjas de lunes a viernes,
 * cada una con su modalidad presencial o virtual. No es de una fecha
 * concreta, es "la semana típica" — la vista de una semana puntual (con las
 * situaciones especiales de esos días encima) se consulta desde Funcionarios
 * PAA, no acá.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/modulos.php';

$yo = exigir_modulo('horarios', '../');
$esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestión PAA · Horarios · UCR</title>
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

        .app { max-width: 1240px; margin: 0 auto; }

        .barra-sesion {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
            font-size: 0.875rem; color: var(--gray-600);
        }
        .barra-sesion a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .barra-sesion a:hover { text-decoration: underline; }

        .hero { text-align: center; margin-bottom: 1.75rem; }
        .hero .logo {
            display: inline-flex; margin-bottom: .6rem; padding: 6px 14px;
            border-radius: 12px; background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }
        .hero .logo img { display: block; width: auto; height: 28px; }
        .hero h1 {
            font-size: clamp(1.9rem, 5vw, 2.6rem); font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .hero p { color: var(--gray-600); }

        .semanario { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; }
        @media (max-width: 1000px) { .semanario { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .semanario { grid-template-columns: 1fr; } }

        .dia-tarjeta {
            background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
            padding: 1rem; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: .6rem;
        }
        .dia-tarjeta h2 { font-size: .95rem; color: var(--gray-800); }

        .situacion-banner {
            font-size: .75rem; font-weight: 700; padding: .4rem .55rem; border-radius: .5rem;
            background: #fef3c7; color: #92400e;
        }

        .franja-chip {
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
            padding: .45rem .6rem; border-radius: .5rem; font-size: .8rem; font-weight: 600;
        }
        .franja-chip.presencial { background: #eaf3fb; color: var(--primary-dark); }
        .franja-chip.virtual { background: #f3e8ff; color: #6b21a8; }
        .franja-chip button {
            background: none; border: none; cursor: pointer; color: inherit; opacity: .6; font-size: .8rem; padding: 0;
        }
        .franja-chip button:hover { opacity: 1; }

        .sin-franjas { font-size: .78rem; color: var(--gray-400); font-style: italic; }

        .form-franja { display: flex; flex-direction: column; gap: .4rem; border-top: 1px dashed var(--gray-200); padding-top: .6rem; }
        .form-franja .fila { display: flex; gap: .35rem; }
        .form-franja input, .form-franja select {
            flex: 1; min-width: 0; padding: .4rem; border: 1px solid var(--gray-300); border-radius: .4rem;
            font: inherit; font-size: .78rem;
        }

        button {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            padding: .5rem .7rem; border: 0; border-radius: var(--radius); cursor: pointer;
            font: inherit; font-size: .78rem; font-weight: 700;
        }
        .btn-primario { background: var(--primary); color: #fff; }
        .btn-primario:hover { background: var(--primary-dark); }
        .btn-secundario { background: var(--gray-100); color: var(--gray-700); }
        .btn-secundario:hover { background: var(--gray-200); }

        .aviso {
            display: none; margin-bottom: 1.25rem; padding: .75rem 1rem; border-radius: var(--radius); font-size: .85rem;
        }
        .aviso.visible { display: block; }
        .aviso.error { background: #fee2e2; color: #991b1b; }
        .aviso.exito { background: #d1fae5; color: #065f46; }

        .selector-persona {
            display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
            padding: .75rem 1rem; margin-bottom: 1.25rem; box-shadow: var(--shadow);
        }
        .selector-persona label { font-size: .85rem; font-weight: 700; color: var(--gray-700); }
        .selector-persona select {
            flex: 1; min-width: 200px; padding: .5rem .6rem; border: 1px solid var(--gray-300);
            border-radius: .4rem; font: inherit; font-size: .85rem;
        }
        .selector-persona .quien-viendo {
            font-size: .8rem; color: var(--gray-500); width: 100%;
        }

        .sin-cuenta {
            text-align: center; padding: 2.5rem 1rem; color: var(--gray-500);
            background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
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
        <div class="logo">
            <img src="../assets/logo-paa-blanco.png" alt="Gestión PAA">
        </div>
        <h1>Horarios</h1>
        <p id="subtitulo">Tu horario semanal: franjas de lunes a viernes, presencial o virtual</p>
    </div>

    <?php if ($esAdmin): ?>
    <div class="selector-persona">
        <label for="selectorPersona"><i class="fa-solid fa-users" aria-hidden="true"></i> Ver/editar el horario de</label>
        <select id="selectorPersona">
            <option value="">— Mi propio horario —</option>
        </select>
        <p class="quien-viendo" id="quienViendo"></p>
    </div>
    <?php endif; ?>

    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
        <button class="btn-secundario" type="button" id="btnExportar">
            <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Descargar Excel de todo el personal
        </button>
    </div>

    <div id="aviso" class="aviso" aria-live="polite" aria-atomic="true"></div>

    <div class="semanario" id="semanario"></div>
</div>

<script>
const API = '../api/horarios.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
const DIAS_LARGO = { lunes: 'Lunes', martes: 'Martes', miercoles: 'Miércoles', jueves: 'Jueves', viernes: 'Viernes' };
const ETIQUETAS_TIPO = {
    vacaciones: 'Vacaciones', incapacidad: 'Incapacidad', reunion: 'Reunión',
    cambio_horario: 'Cambio de horario', trabajo_remoto: 'Trabajo remoto',
    cita_medica: 'Cita médica', permiso: 'Permiso', otro: 'Otro',
};

const ES_ADMIN = <?= json_encode($esAdmin) ?>;

let datos = { dias: {}, situaciones: {} };
// Copia de trabajo por día: lo que se ve en pantalla mientras se edita, antes de guardar.
let borrador = {};

// null = viendo/editando el horario propio. Con una persona elegida, es su
// usuario_id — hace falta para que guardarDia() sepa a nombre de quién
// escribir (ver la regla de permisos en api/horarios.php: guardarDia sin
// esto siempre cae sobre la cuenta propia).
let usuarioIdActivo = null;
let sinCuenta = false;

// cargar() (horario propio) y cargarDePersona() (el de alguien elegido en el
// selector) son las dos asíncronas. Sin esto, la más lenta de las dos podía
// resolver DESPUÉS de la otra y pisar en silencio lo que fuera que estuviera
// en pantalla — por ejemplo: se elige a alguien, se le borra y define de
// nuevo el lunes, pero el cargar() del horario propio (vacío, porque
// administración/desarrollo no siempre tiene uno) que había quedado
// pendiente desde que se abrió la página termina de responder tarde y
// reemplaza todo por esa nada. Cada carga se numera; al terminar, si ya no
// es la más reciente, se descarta en vez de pintarse.
let cargaId = 0;

document.addEventListener('DOMContentLoaded', () => {
    cargar();
    document.getElementById('btnExportar').addEventListener('click', () => {
        window.location.href = `${API}?accion=exportar`;
    });

    if (ES_ADMIN) {
        cargarPersonas();
        document.getElementById('selectorPersona').addEventListener('change', e => {
            const personaId = e.target.value;
            if (personaId === '') {
                document.getElementById('quienViendo').textContent = '';
                document.getElementById('subtitulo').textContent =
                    'Tu horario semanal: franjas de lunes a viernes, presencial o virtual';
                cargar();
            } else {
                cargarDePersona(personaId);
            }
        });
    }
});

async function cargarPersonas() {
    try {
        const d = await pedir(`${API}?accion=personas`);
        const sel = document.getElementById('selectorPersona');
        d.personas.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.persona_id;
            opt.textContent = p.nombre;
            sel.appendChild(opt);
        });
    } catch (e) {
        // El selector es una comodidad de administración; si no carga, la
        // persona sigue viendo y editando su propio horario sin problema.
        mostrar(e.message, 'error');
    }
}

async function cargar() {
    const miCarga = ++cargaId;
    try {
        const d = await pedir(`${API}?accion=listar`);
        if (miCarga !== cargaId) return; // se eligió otra cosa mientras tanto: se descarta

        usuarioIdActivo = null;
        sinCuenta = false;
        document.getElementById('quienViendo') && (document.getElementById('quienViendo').textContent = '');
        datos = d;
        borrador = {};
        DIAS.forEach(dia => { borrador[dia] = (d.dias[dia] || []).map(f => ({ ...f })); });
        pintar();
    } catch (e) {
        if (miCarga !== cargaId) return;
        mostrar(e.message, 'error');
    }
}

/** Trae el horario de OTRA persona (solo administración/desarrollo, ver el selector). */
async function cargarDePersona(personaId) {
    const miCarga = ++cargaId;
    try {
        const d = await pedir(`${API}?accion=verHorario&persona_id=${encodeURIComponent(personaId)}`);
        if (miCarga !== cargaId) return; // se eligió otra cosa mientras tanto: se descarta

        document.getElementById('quienViendo').textContent =
            d.tieneCuenta ? '' : `${d.nombre} todavía no tiene cuenta en el sistema — no hay dónde guardarle horario.`;
        document.getElementById('subtitulo').textContent =
            `Horario semanal de ${d.nombre}: franjas de lunes a viernes, presencial o virtual`;

        if (!d.tieneCuenta) {
            sinCuenta = true;
            usuarioIdActivo = null;
            document.getElementById('semanario').innerHTML =
                `<div class="sin-cuenta" style="grid-column:1/-1;"><i class="fa-solid fa-user-slash" aria-hidden="true" style="font-size:1.5rem;margin-bottom:.5rem;display:block;"></i>Sin cuenta en el sistema.</div>`;
            return;
        }

        sinCuenta = false;
        usuarioIdActivo = d.usuarioId;
        datos = d;
        borrador = {};
        DIAS.forEach(dia => { borrador[dia] = (d.dias[dia] || []).map(f => ({ ...f })); });
        pintar();
    } catch (e) {
        if (miCarga !== cargaId) return;
        mostrar(e.message, 'error');
    }
}

function pintar() {
    document.getElementById('semanario').innerHTML = DIAS.map(dia => pintarDia(dia)).join('');

    DIAS.forEach(dia => {
        document.getElementById(`form-${dia}`).addEventListener('submit', e => agregarFranja(e, dia));
        document.getElementById(`guardar-${dia}`).addEventListener('click', () => guardarDia(dia));
    });
}

function pintarDia(dia) {
    const situaciones = datos.situaciones[dia] || [];
    const franjas = borrador[dia] || [];

    const banner = situaciones.map(s => `
        <div class="situacion-banner"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> ${escapar(ETIQUETAS_TIPO[s.tipo] || s.tipo)}${s.descripcion ? ': ' + escapar(s.descripcion) : ''}</div>
    `).join('');

    const chips = franjas.length
        ? franjas.map((f, i) => `
            <div class="franja-chip ${f.modalidad}">
                <span>${f.horaInicio}–${f.horaFin} · ${f.modalidad === 'virtual' ? 'Virtual' : 'Presencial'}</span>
                <button type="button" onclick="quitarFranja('${dia}', ${i})" title="Quitar" aria-label="Quitar franja de ${f.horaInicio} a ${f.horaFin}, ${f.modalidad === 'virtual' ? 'virtual' : 'presencial'}, el ${DIAS_LARGO[dia].toLowerCase()}"><i class="fa-solid fa-times" aria-hidden="true"></i></button>
            </div>
        `).join('')
        : '<p class="sin-franjas">Sin horario definido.</p>';

    return `
        <div class="dia-tarjeta">
            <h2>${DIAS_LARGO[dia]}</h2>
            ${banner}
            ${chips}
            <form class="form-franja" id="form-${dia}">
                <div class="fila">
                    <input type="time" required aria-label="Hora de inicio, ${DIAS_LARGO[dia]}">
                    <input type="time" required aria-label="Hora de fin, ${DIAS_LARGO[dia]}">
                </div>
                <div class="fila">
                    <select aria-label="Modalidad, ${DIAS_LARGO[dia]}">
                        <option value="presencial">Presencial</option>
                        <option value="virtual">Virtual</option>
                    </select>
                    <button class="btn-secundario" type="submit">+ Franja</button>
                </div>
            </form>
            <button class="btn-primario" type="button" id="guardar-${dia}">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Guardar ${DIAS_LARGO[dia]}
            </button>
        </div>
    `;
}

/**
 * Lee la franja que haya en el formulario de "agregar" de ese día, sin
 * tocar `borrador`. null = el formulario está vacío (no hay nada pendiente,
 * no es un error). 'error' = hay algo escrito pero no es válido (ya se
 * avisó por qué). Si no hay problema, devuelve la franja lista para usar.
 */
function leerFranjaDelForm(dia) {
    const form = document.getElementById(`form-${dia}`);
    const [inicio, fin] = form.querySelectorAll('input[type="time"]');
    const modalidad = form.querySelector('select').value;

    if (!inicio.value && !fin.value) return null;

    if (!inicio.value || !fin.value) {
        mostrar('Falta la hora de inicio o la de fin de la franja que estabas escribiendo.', 'error');
        return 'error';
    }
    if (fin.value <= inicio.value) {
        mostrar('La hora de fin debe ser posterior a la de inicio.', 'error');
        return 'error';
    }

    return { horaInicio: inicio.value, horaFin: fin.value, modalidad };
}

function agregarFranja(evento, dia) {
    evento.preventDefault();
    const franja = leerFranjaDelForm(dia);
    if (!franja || franja === 'error') return;

    borrador[dia].push(franja);
    borrador[dia].sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));
    pintar();
}

function quitarFranja(dia, indice) {
    borrador[dia].splice(indice, 1);
    pintar();
}

async function guardarDia(dia) {
    // Si quedó una franja escrita en el formulario pero nunca se le dio
    // "+ Franja", "Guardar" la incorpora igual — de lo contrario esa hora
    // que la persona acababa de escribir se perdía en silencio: el botón
    // "Guardar {día}" guardaba lo que ya estaba confirmado en chips, no lo
    // que todavía estaba a medio escribir debajo.
    const pendiente = leerFranjaDelForm(dia);
    if (pendiente === 'error') return;
    if (pendiente) {
        borrador[dia].push(pendiente);
        borrador[dia].sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));
    }

    const btn = document.getElementById(`guardar-${dia}`);
    btn.disabled = true;

    try {
        await pedirPost(API, {
            accion: 'guardarDia',
            dia_semana: dia,
            franjas: JSON.stringify(borrador[dia]),
            usuario_id: usuarioIdActivo || '',
        });
        datos.dias[dia] = borrador[dia].map(f => ({ ...f }));
        pintar();
        mostrar(`Horario del ${DIAS_LARGO[dia].toLowerCase()} guardado.`, 'exito');
    } catch (e) {
        mostrar(e.message, 'error');
        btn.disabled = false;
    }
}

function mostrar(mensaje, tipo) {
    const aviso = document.getElementById('aviso');
    aviso.textContent = mensaje;
    aviso.className = `aviso ${tipo} visible`;
    if (tipo === 'exito') {
        setTimeout(() => aviso.classList.remove('visible'), 4000);
    }
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
    if (!d.success) throw new Error(d.error || 'No se pudo consultar');
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
