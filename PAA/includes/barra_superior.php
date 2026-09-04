<?php
/**
 * Barra superior de navegación, común a todas las páginas del sistema.
 *
 * REEMPLAZA AL BOTÓN FLOTANTE DE «INICIO»
 * Antes había un botón redondo abajo a la izquierda. Para quien navega con
 * teclado y lector de pantalla eso es lo ÚLTIMO que se alcanza: hay que
 * recorrer la página entera para volver al inicio. Ahora Inicio es la primera
 * parada de tabulación de la página.
 *
 * QUÉ TRAE
 *   · Saltar al contenido (primer enlace, invisible hasta que recibe el foco).
 *   · Inicio.
 *   · Módulos: abre el navegador de módulos, con lo que ESA persona puede
 *     abrir según su rol y sus excepciones — la misma verdad de
 *     includes/modulos.php, no una lista aparte.
 *   · Para administración: Dashboard, Estado del sistema y Bitácora.
 *   · Para desarrollo: ver el sistema como otro rol.
 *   · Salir.
 *
 * Se incluye desde includes/boton_inicio.php, que ya estaba en todas las
 * páginas de módulo: así ninguna se queda sin barra ni hay que tocar veinte
 * archivos cuando cambie algo de acá.
 */

declare(strict_types=1);

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/modulos.php';

if (defined('PAA_BARRA_SUPERIOR_IMPRESA')) {
    return;
}
define('PAA_BARRA_SUPERIOR_IMPRESA', true);

$paaYo = usuario_actual();

// Sin sesión no hay barra: la usan login.php y las páginas públicas.
if ($paaYo === null) {
    return;
}

require_once __DIR__ . '/ui_accesible.php';

$paaBase        = base_url();
$paaEsAdmin     = in_array($paaYo['rol'], ROLES_ADMIN, true);
$paaEsDev       = ($paaYo['rolReal'] ?? $paaYo['rol']) === ROL_DESARROLLADOR;
$paaCategorias  = categorias_del_sistema();
$paaMisModulos  = modulos_para($paaYo['rol'], (int) $paaYo['id']);

// Las páginas sueltas que viven dentro de un módulo. No están en el catálogo
// porque no son módulos con permisos propios, pero sí son destinos a los que
// alguien quiere llegar directo desde el navegador.
$paaSubpaginas = [
    'creaciontickets' => [
        ['nombre' => 'Guía de uso de Tickets', 'url' => 'CreacionTickets/guia.php'],
    ],
];

// Agrupado por categoría, en el orden del catálogo.
$paaPorCategoria = [];
foreach ($paaMisModulos as $paaM) {
    $paaPorCategoria[$paaM['categoria'] ?? 'otros'][] = $paaM;
}

// Convocatoria activa. Antes era un panel grande en la página de inicio, pero
// es un dato de TODO el sistema (sedes, aulas, tulas, coordinadores, ingreso y
// revisión trabajan sobre ella), no de una sola página: acá se ve y se cambia
// desde donde sea que esté la persona, y sin ocupar media pantalla.
$paaConvocatorias = [];
$paaConvActiva    = null;
if ($paaEsAdmin) {
    $paaConvocatorias = db()->query('SELECT id, nombre, activa FROM convocatorias ORDER BY nombre DESC')->fetchAll();
    foreach ($paaConvocatorias as $paaConv) {
        if ((int) $paaConv['activa'] === 1) {
            $paaConvActiva = $paaConv;
            break;
        }
    }
}
?>
<style>
/* ============================================================
   Barra superior
   ============================================================ */
.paa-barra {
    position: sticky;
    top: 0;
    z-index: 9990;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 0 1.25rem;
    min-height: 68px;
    background: #01498f;
    color: #fff;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 1px 12px rgba(0, 41, 82, 0.20);
}

/* Marca a la izquierda: ancla la barra y deja claro dónde se está. */
.paa-barra-marca {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: #fff;
    padding: 0.4rem 0.75rem 0.4rem 0;
    border-right: 1px solid rgba(255, 255, 255, 0.18);
    flex-shrink: 0;
}
.paa-barra-marca span {
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    white-space: nowrap;
}
.paa-barra-marca:focus-visible { outline: 3px solid #ffd166; outline-offset: 3px; border-radius: 6px; }

.paa-barra-nav {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex: 1 1 auto;
    min-width: 0;
}

/* Inicio y Módulos van SUELTOS, fuera de esta franja: son la salida de
   emergencia de cualquier pantalla y no pueden quedar donde un espacio
   angosto los mande a un scroll lateral que nadie ve que existe. Lo único
   que entra acá adentro son los atajos de más (Dashboard/Estado/Bitácora,
   solo administración), que igual están repetidos dentro de «Módulos» —
   si no caben, se scrollean ellos, nunca Inicio ni Módulos. */
.paa-barra-nav-extra {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    overflow-x: auto;
    scrollbar-width: none;
}
.paa-barra-nav-extra::-webkit-scrollbar { display: none; }

/* Una sola altura para TODO lo que va en la barra: botones, selectores y el
   botón de notificaciones. Antes cada cosa se dimensionaba por su propio
   padding y las piezas quedaban a distinta altura entre sí. */
.paa-barra-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    height: 42px;
    padding: 0 0.9rem;
    border-radius: 10px;
    border: 1px solid transparent;
    background: transparent;
    color: #fff;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    line-height: 1.2;
    white-space: nowrap;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}
.paa-barra-btn i { font-size: 0.95rem; }
.paa-barra-btn:hover { background: rgba(255, 255, 255, 0.15); color: #fff; }
.paa-barra-btn:focus-visible { outline: 3px solid #ffd166; outline-offset: 2px; }

/* El que abre el navegador de módulos: se distingue con un contorno, no con
   un relleno — el relleno blanco compite con la sección actual y confunde
   cuál de los dos es «donde estoy». */
.paa-barra-btn.principal {
    border-color: rgba(255, 255, 255, 0.55);
    background: rgba(255, 255, 255, 0.08);
}
.paa-barra-btn.principal:hover { background: rgba(255, 255, 255, 0.20); border-color: #fff; }

/* La sección donde está parada la persona: una sola marca clara, en blanco
   sólido. Sin sombras hacia adentro. */
.paa-barra-btn.actual {
    background: #fff;
    color: #01498f;
    font-weight: 700;
    border-color: #fff;
}
.paa-barra-btn.actual:hover { background: #fff; color: #01376c; }

/* Bloque derecho: quién sos, notificaciones y salir. */
.paa-barra-acciones {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-shrink: 0;
}

.paa-barra-separador {
    width: 1px;
    height: 30px;
    background: rgba(255, 255, 255, 0.24);
}

.paa-barra-quien {
    display: inline-flex;
    flex-direction: column;
    line-height: 1.3;
    text-align: right;
    max-width: 190px;
}
.paa-barra-quien strong {
    font-size: 0.82rem;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.paa-barra-quien small { font-size: 0.72rem; color: #b9d8f2; }

/* ---- Botones de icono del bloque derecho ----
   Notificaciones, cambiar contraseña y salir son tres acciones de la cuenta y
   se ven como tres: mismo cuadro de 42 px, mismo borde, mismo redondeo. Antes
   «Cambiar contraseña» iba con texto entre dos botones de solo icono y el
   bloque quedaba desparejo. El nombre lo pone aria-label, así que el lector de
   pantalla los sigue anunciando completos. */
.paa-barra-icono {
    width: 42px;
    padding: 0;
    justify-content: center;
    background: rgba(255, 255, 255, 0.10);
    border-color: rgba(255, 255, 255, 0.30);
    flex-shrink: 0;
}
.paa-barra-icono i { font-size: 1rem; }
.paa-barra-icono:hover { background: rgba(255, 255, 255, 0.22); }

/* Salir es el único destructivo del grupo: mismo cuadro, aviso al pasar. */
.paa-barra-icono.salir:hover { background: #dc2626; border-color: #dc2626; }

/* Selector de convocatoria activa y selector de rol de desarrollo comparten
   la misma pastilla: en blanco translúcido como el resto — el amarillo sobre
   azul quedaba sucio y se leía mal. */
.paa-barra-modo {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    height: 42px;
    padding: 0 0.4rem 0 0.7rem;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.10);
    border: 1px solid rgba(255, 255, 255, 0.30);
}
.paa-barra-modo label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #cfe4f7;
    white-space: nowrap;
}
.paa-barra-modo select {
    background: #fff;
    color: #01376c;
    border: none;
    border-radius: 7px;
    padding: 0.34rem 0.5rem;
    font-family: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}
.paa-barra-modo select:focus-visible { outline: 3px solid #ffd166; outline-offset: 2px; }
.paa-barra-modo select:disabled { opacity: .6; cursor: progress; }

/* Convocatoria y «Ver como» son la misma pieza: no se diferencian con bordes
   distintos, se diferencian por lo que dice su etiqueta. */
.paa-barra-convocatoria select { min-width: 6.5rem; }

/* «Ver como» cerrado solo necesita mostrar la inicial del rol (D/A/F/E): el
   nombre completo era tan ancho que en pantallas medianas se montaba encima
   de Módulos o de la campanita. El nombre completo sigue en cada <option>
   —lo que se lee al abrir el desplegable y lo que anuncia el lector de
   pantalla—, acá solo se recorta lo que se ve ya elegido. */
.paa-barra-vercomo {
    width: 2.9rem;
    max-width: 2.9rem;
    overflow: hidden;
    text-overflow: clip;
    white-space: nowrap;
    padding-right: 1.3rem;
}

/* Notificaciones: se mete acá adentro (ver el script al final). Antes era un
   botón flotante que quedaba encima del de Salir.
   `relative` y no `static`: la insignia con el número va posicionada CONTRA
   este botón, así que sin posición propia se iba a la esquina de la página. */
.paa-barra .paa-notif-boton {
    position: relative !important;
    top: auto !important;
    right: auto !important;
    width: 42px !important;
    height: 42px !important;
    border-radius: 10px !important;
    background: rgba(255, 255, 255, 0.10) !important;
    border: 1px solid rgba(255, 255, 255, 0.30) !important;
    box-shadow: none !important;
    font-size: 1rem !important;
    flex-shrink: 0;
}
.paa-barra .paa-notif-boton:hover { background: rgba(255, 255, 255, 0.22) !important; transform: none !important; }
.paa-barra .paa-notif-boton:focus-visible { outline: 3px solid #ffd166; outline-offset: 2px; }
/* El número rojo, pegado a la campana. */
.paa-barra .paa-notif-insignia {
    top: -0.35rem !important;
    right: -0.35rem !important;
    border-color: #01498f !important;
}

/* El panel de notificaciones cuelga de la barra, no de la esquina. */
body.paa-con-barra .paa-notif-panel { top: 76px; }

/* Enlace para saltarse la barra: invisible hasta que se le da Tab. */
.paa-saltar {
    position: absolute;
    left: -9999px;
    top: 0;
    background: #fff;
    color: #0055a4;
    padding: 0.7rem 1rem;
    font-weight: 700;
    border-radius: 0 0 8px 0;
    z-index: 10000;
}
.paa-saltar:focus {
    left: 0;
    outline: 3px solid #ffd166;
}

/* ============================================================
   Navegador de módulos
   ============================================================ */
.paa-modulos-fondo {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.55);
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding: 1rem;
    overflow-y: auto;
    z-index: 10020;
}
.paa-modulos-fondo.visible { display: flex; }

.paa-modulos-caja {
    background: #fff;
    border-radius: 16px;
    padding: 1.4rem 1.5rem 1.6rem;
    max-width: 860px;
    width: 100%;
    margin: 2rem auto;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: #111827;
}
.paa-modulos-cab {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.9rem;
}
.paa-modulos-cab h2 { font-size: 1.15rem; margin: 0 0 0.15rem; color: #111827; }
.paa-modulos-cab p { font-size: 0.82rem; color: #6b7280; margin: 0; }

.paa-modulos-cerrar {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    width: 38px;
    height: 38px;
    font-size: 1.05rem;
    color: #374151;
    cursor: pointer;
    flex-shrink: 0;
}
.paa-modulos-cerrar:hover { background: #e5e7eb; }
.paa-modulos-cerrar:focus-visible { outline: 3px solid #6ec1e4; outline-offset: 2px; }

.paa-modulos-buscar {
    width: 100%;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    font-family: inherit;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.paa-modulos-buscar:focus { outline: none; border-color: #2b7fc2; box-shadow: 0 0 0 3px rgba(43,127,194,.18); }

.paa-modulos-grupo { margin-bottom: 1.2rem; }
.paa-modulos-grupo h3 {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    margin: 0 0 0.5rem;
}
.paa-modulos-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.5rem; }

.paa-modulo-enlace {
    display: block;
    padding: 0.7rem 0.85rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-decoration: none;
    color: #111827;
    background: #fff;
}
.paa-modulo-enlace:hover { border-color: #2b7fc2; background: #f8fbff; }
.paa-modulo-enlace:focus-visible { outline: 3px solid #6ec1e4; outline-offset: 2px; }
.paa-modulo-enlace strong { display: block; font-size: 0.9rem; font-weight: 700; }
.paa-modulo-enlace span { display: block; font-size: 0.76rem; color: #6b7280; margin-top: 0.15rem; }
.paa-modulo-enlace.sub { border-style: dashed; }

.paa-modulos-vacio { font-size: 0.85rem; color: #6b7280; padding: 0.6rem 0; }

@media (max-width: 1200px) {
    .paa-barra-marca { display: none; }
    .paa-barra-quien { max-width: 140px; }
    .paa-barra-convocatoria label span,
    .paa-barra-modo label span { display: none; }
}

@media (max-width: 820px) {
    .paa-barra { gap: 0.75rem; padding: 0 0.75rem; min-height: 60px; }
    .paa-barra-quien, .paa-barra-separador { display: none; }
    .paa-barra-btn { padding: 0 0.7rem; }
    .paa-barra-btn .paa-barra-texto { display: none; }
    .paa-barra-btn.principal .paa-barra-texto { display: inline; }
    .paa-barra-modo label { display: none; }
}

@media print {
    .paa-barra, .paa-modulos-fondo, .paa-saltar { display: none !important; }
}
</style>

<a class="paa-saltar" href="#paaContenido">Saltar al contenido</a>

<header class="paa-barra">
    <!-- Solo el nombre: el logotipo ancho «PAA · Prueba de Aptitud Académica»
         a 30 px de alto quedaba ilegible y encima repetía lo que dice el
         texto de al lado. -->
    <a class="paa-barra-marca" href="<?= h($paaBase) ?>">
        <span>Gestión PAA</span>
    </a>

    <nav class="paa-barra-nav" aria-label="Navegación principal">
        <a class="paa-barra-btn" href="<?= h($paaBase) ?>" data-vista="herramientas" aria-label="Inicio">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            <span class="paa-barra-texto">Inicio</span>
        </a>

        <button type="button" class="paa-barra-btn principal" id="paaAbrirModulos"
                aria-haspopup="dialog" aria-expanded="false">
            <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
            <span class="paa-barra-texto">Módulos</span>
        </button>

        <?php if ($paaEsAdmin): ?>
        <div class="paa-barra-nav-extra">
            <a class="paa-barra-btn" href="<?= h($paaBase) ?>?vista=dashboard" data-vista="dashboard" aria-label="Dashboard">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                <span class="paa-barra-texto">Dashboard</span>
            </a>
            <a class="paa-barra-btn" href="<?= h($paaBase) ?>?vista=sistema" data-vista="sistema" aria-label="Estado del sistema">
                <i class="fa-solid fa-microchip" aria-hidden="true"></i>
                <span class="paa-barra-texto">Estado del sistema</span>
            </a>
            <a class="paa-barra-btn" href="<?= h($paaBase) ?>?vista=bitacora" data-vista="bitacora" aria-label="Bitácora">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                <span class="paa-barra-texto">Bitácora</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="paa-barra-acciones">
        <?php if ($paaEsAdmin && $paaConvocatorias): ?>
        <!-- Convocatoria activa. Al elegir otra se pregunta antes de cambiarla:
             es un cambio que afecta a todo el sistema, no solo a esta pantalla. -->
        <div class="paa-barra-modo paa-barra-convocatoria">
            <label for="paaConvocatoria"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> <span>Convocatoria</span></label>
            <select id="paaConvocatoria"
                    data-actual="<?= $paaConvActiva ? (int) $paaConvActiva['id'] : '' ?>">
                <?php if (!$paaConvActiva): ?>
                <option value="" selected>Ninguna activa</option>
                <?php endif; ?>
                <?php foreach ($paaConvocatorias as $paaConv): ?>
                <option value="<?= (int) $paaConv['id'] ?>" <?= (int) $paaConv['activa'] === 1 ? 'selected' : '' ?>>
                    <?= h($paaConv['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if ($paaEsDev): ?>
        <!-- Cambiar de rol sin cerrar sesión. Vive en la barra y no escondido
             en una pantalla: es lo que se usa para probar cómo ve el sistema
             cada tipo de cuenta. -->
        <div class="paa-barra-modo">
            <label for="paaVerComo"><i class="fa-solid fa-user-secret" aria-hidden="true"></i> <span>Ver como</span></label>
            <select id="paaVerComo" class="paa-barra-vercomo"
                    title="Ver como — D: Desarrollador, A: Administrador, F: Funcionario, E: Extraordinario">
                <?php foreach ([
                    ROL_DESARROLLADOR  => ['D', 'Desarrollador'],
                    ROL_ADMINISTRADOR  => ['A', 'Administrador'],
                    ROL_FUNCIONARIO    => ['F', 'Funcionario'],
                    ROL_EXTRAORDINARIO => ['E', 'Extraordinario'],
                ] as $paaRol => [$paaInicial, $paaEtiqueta]): ?>
                <option value="<?= h($paaRol) ?>" <?= $paaYo['rol'] === $paaRol ? 'selected' : '' ?>
                        aria-label="<?= h($paaEtiqueta) ?>">
                    <?= h($paaInicial) ?> — <?= h($paaEtiqueta) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <span class="paa-barra-separador" aria-hidden="true"></span>

        <span class="paa-barra-quien">
            <strong><?= h($paaYo['nombre']) ?></strong>
            <small><?= h(nombre_del_rol($paaYo['rol'])) ?></small>
        </span>

        <!-- Acá se mete el botón de notificaciones (ver el script). -->
        <span id="paaBarraNotificaciones"></span>

        <?php if ($paaYo['rol'] !== ROL_EXTRAORDINARIO): ?>
        <!-- Estaba suelto en la página de inicio, dentro de la franja de perfil.
             Es una acción de la cuenta, así que vive con el resto de la cuenta y
             se alcanza desde cualquier página. -->
        <a class="paa-barra-btn paa-barra-icono" href="<?= h($paaBase) ?>cambiar_contrasena.php"
           aria-label="Cambiar contraseña" title="Cambiar contraseña">
            <i class="fa-solid fa-key" aria-hidden="true"></i>
        </a>
        <?php endif; ?>

        <a class="paa-barra-btn paa-barra-icono salir" href="<?= h($paaBase) ?>salir.php"
           aria-label="Salir del sistema" title="Salir del sistema">
            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
        </a>
    </div>
</header>

<!-- ===== NAVEGADOR DE MÓDULOS ===== -->
<!-- data-paa-modal: así includes/ui_accesible.php lo trata como ventana
     emergente y le manda el foco al abrirse. Sin esto había que recorrer la
     página entera para llegar a esta lista, que es justo lo contrario de lo
     que este navegador viene a resolver. -->
<div class="paa-modulos-fondo" id="paaModulosFondo" data-paa-modal
     data-paa-foco="#paaModulosBuscar" role="dialog" aria-modal="true"
     aria-labelledby="paaModulosTitulo">
    <div class="paa-modulos-caja">
        <div class="paa-modulos-cab">
            <div>
                <h2 id="paaModulosTitulo" tabindex="-1">Módulos del sistema</h2>
                <p>Lo que tu cuenta puede abrir. Escribí para filtrar.</p>
            </div>
            <button type="button" class="paa-modulos-cerrar" id="paaModulosCerrar" aria-label="Cerrar módulos">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <label for="paaModulosBuscar" class="paa-sr-solo">Buscar módulo</label>
        <input type="text" id="paaModulosBuscar" class="paa-modulos-buscar"
               placeholder="Buscar módulo… (por ejemplo: sedes, tickets, cronograma)" autocomplete="off">

        <div id="paaModulosResultado" role="status" aria-live="polite" class="paa-sr-solo"></div>

        <div id="paaModulosContenido">
            <?php foreach ($paaCategorias as $paaClave => $paaCat):
                if (empty($paaPorCategoria[$paaClave])) { continue; } ?>
            <div class="paa-modulos-grupo" data-grupo>
                <h3><?= h($paaCat['nombre']) ?></h3>
                <div class="paa-modulos-lista">
                    <?php foreach ($paaPorCategoria[$paaClave] as $paaM): ?>
                    <a class="paa-modulo-enlace" href="<?= h($paaBase . $paaM['url']) ?>"
                       data-buscar="<?= h(mb_strtolower($paaM['nombre'] . ' ' . ($paaM['descripcion'] ?? ''), 'UTF-8')) ?>">
                        <strong><?= h($paaM['nombre']) ?></strong>
                        <span><?= h($paaM['caracteristica'] ?? '') ?></span>
                    </a>
                    <?php foreach ($paaSubpaginas[$paaM['id']] ?? [] as $paaSub): ?>
                    <a class="paa-modulo-enlace sub" href="<?= h($paaBase . $paaSub['url']) ?>"
                       data-buscar="<?= h(mb_strtolower($paaSub['nombre'] . ' ' . $paaM['nombre'], 'UTF-8')) ?>">
                        <strong><?= h($paaSub['nombre']) ?></strong>
                        <span>Dentro de <?= h($paaM['nombre']) ?></span>
                    </a>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($paaEsAdmin): ?>
            <div class="paa-modulos-grupo" data-grupo>
                <h3>Administración del sistema</h3>
                <div class="paa-modulos-lista">
                    <a class="paa-modulo-enlace" href="<?= h($paaBase) ?>?vista=dashboard" data-buscar="dashboard métricas indicadores">
                        <strong>Dashboard</strong><span>Indicadores del sistema</span>
                    </a>
                    <a class="paa-modulo-enlace" href="<?= h($paaBase) ?>?vista=sistema" data-buscar="estado del sistema servicios">
                        <strong>Estado del sistema</strong><span>Servicios y tiempos de respuesta</span>
                    </a>
                    <a class="paa-modulo-enlace" href="<?= h($paaBase) ?>?vista=bitacora" data-buscar="bitácora registro auditoría quién entró">
                        <strong>Bitácora</strong><span>Quién entró y qué tocó</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- «Ver como» no se repite acá: vive en la barra, al lado del
                 nombre de la cuenta, que es donde se busca. -->
        </div>

        <p class="paa-modulos-vacio" id="paaModulosSinResultados" hidden>
            Ningún módulo coincide con esa búsqueda.
        </p>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ----------------------------------------------------------------
    // La barra se imprime al final del <body> (es un include que va con
    // los demás al cierre de cada página), pero tiene que ser lo PRIMERO
    // del documento: si queda al final, para llegar a «Inicio» con el
    // teclado hay que recorrer toda la página, que es justo el problema
    // que se está arreglando. Se sube al principio apenas carga.
    // ----------------------------------------------------------------
    function subirAlPrincipio() {
        const barra   = document.querySelector('.paa-barra');
        const saltar  = document.querySelector('.paa-saltar');
        if (!barra) { return; }

        if (barra.parentNode !== document.body || document.body.firstChild !== saltar) {
            document.body.insertBefore(barra, document.body.firstChild);
            if (saltar) { document.body.insertBefore(saltar, barra); }
        }

        document.body.classList.add('paa-con-barra');

        // Cada módulo le pone su propio padding al <body> (1.5rem, 24px…). Sin
        // esto la barra quedaría metida hacia adentro, con franjas de fondo a
        // los lados. Se compensa con el padding real de esta página, sea cual
        // sea, en vez de suponer uno.
        const est = window.getComputedStyle(document.body);
        barra.style.marginTop = '-' + est.paddingTop;
        barra.style.marginLeft = '-' + est.paddingLeft;
        barra.style.marginRight = '-' + est.paddingRight;
        barra.style.marginBottom = est.paddingTop;

        meterNotificaciones();
        marcarSeccionActual();

        // Destino del enlace «Saltar al contenido»: el primer contenedor de
        // contenido real de la página, sea cual sea el diseño del módulo.
        if (!document.getElementById('paaContenido')) {
            const destino = document.querySelector('main, .app-container, .app, .contenedor, .envoltura');
            if (destino) {
                destino.id = 'paaContenido';
                if (!destino.hasAttribute('tabindex')) { destino.setAttribute('tabindex', '-1'); }
            }
        }
    }

    /**
     * El botón de notificaciones se imprime aparte (includes/notificaciones.php)
     * y quedaba flotando en la esquina, encima del botón de Salir. Se trae a la
     * barra: mismo botón y mismo panel, pero en su lugar.
     */
    function meterNotificaciones() {
        const hueco = document.getElementById('paaBarraNotificaciones');
        const boton = document.getElementById('paaNotifBoton');
        if (hueco && boton && boton.parentNode !== hueco) {
            hueco.appendChild(boton);
        }
    }

    /** Deja marcada la sección en la que se está. */
    function marcarSeccionActual() {
        const enLaPortada = /\/(index\.php)?$/.test(location.pathname);
        const vista = new URLSearchParams(location.search).get('vista') || 'herramientas';

        document.querySelectorAll('.paa-barra-btn[data-vista]').forEach(function (b) {
            const activo = enLaPortada && b.dataset.vista === vista;
            b.classList.toggle('actual', activo);
            if (activo) { b.setAttribute('aria-current', 'page'); }
            else { b.removeAttribute('aria-current'); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', subirAlPrincipio);
    } else {
        subirAlPrincipio();
    }

    // El botón de notificaciones puede imprimirse después de la barra: si
    // todavía no estaba, se reintenta cuando termine de cargar todo.
    window.addEventListener('load', meterNotificaciones);

    // Ver como: cambia el rol de prueba sin cerrar sesión (solo desarrollo).
    const verComo = document.getElementById('paaVerComo');
    if (verComo) {
        verComo.addEventListener('change', function () {
            window.location.href = <?= json_encode($paaBase . 'dev_simular.php?rol=', JSON_UNESCAPED_SLASHES) ?>
                + encodeURIComponent(verComo.value);
        });
    }

    // Convocatoria activa. Estaba en un panel de la página de inicio; el
    // endpoint es el mismo que ya usaba Importación de Datos, acá solo se
    // alcanza desde cualquier página. Se confirma antes de cambiar porque
    // mueve el piso de todo el sistema.
    const selConv = document.getElementById('paaConvocatoria');
    if (selConv) {
        selConv.addEventListener('change', async function () {
            const id = selConv.value;
            const previo = selConv.dataset.actual || '';
            if (!id || id === previo) { return; }

            const nombre = selConv.options[selConv.selectedIndex].text.trim();
            const seguir = confirm(
                '¿Poner "' + nombre + '" como la convocatoria activa?\n\n' +
                'Sedes, aulas, tulas, coordinadores, ingreso de datos y revisión van a ' +
                'trabajar sobre esos datos en todo el sistema hasta que se cambie de nuevo.'
            );
            if (!seguir) { selConv.value = previo; return; }

            selConv.disabled = true;
            try {
                const r = await fetch(<?= json_encode($paaBase . 'api/importacion.php', JSON_UNESCAPED_SLASHES) ?>, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        accion: 'activar',
                        csrf: <?= json_encode(token_csrf()) ?>,
                        convocatoria: id
                    })
                });
                const d = await r.json();
                if (!d.success) { throw new Error(d.error || 'No se pudo cambiar la convocatoria activa'); }
                location.reload();
            } catch (e) {
                selConv.disabled = false;
                selConv.value = previo;
                if (window.PaaUI) { PaaUI.error(e.message); } else { alert(e.message); }
            }
        });
    }

    const boton    = document.getElementById('paaAbrirModulos');
    const fondo    = document.getElementById('paaModulosFondo');
    const cerrar   = document.getElementById('paaModulosCerrar');
    const buscar   = document.getElementById('paaModulosBuscar');
    const vacio    = document.getElementById('paaModulosSinResultados');
    const resultado= document.getElementById('paaModulosResultado');

    if (!boton || !fondo) { return; }

    // El foco, la trampa de Tab y el Escape los pone includes/ui_accesible.php
    // al detectar que el modal se hizo visible: acá solo se abre y se cierra.
    function abrir() {
        fondo.classList.add('visible');
        boton.setAttribute('aria-expanded', 'true');
        buscar.value = '';
        filtrar();
    }

    function cerrarPanel() {
        fondo.classList.remove('visible');
        boton.setAttribute('aria-expanded', 'false');
    }

    boton.addEventListener('click', abrir);
    cerrar.addEventListener('click', cerrarPanel);
    fondo.addEventListener('click', function (e) { if (e.target === fondo) { cerrarPanel(); } });

    function filtrar() {
        const termino = (buscar.value || '').trim().toLowerCase();
        let visibles = 0;

        document.querySelectorAll('#paaModulosContenido [data-grupo]').forEach(function (grupo) {
            let enGrupo = 0;

            grupo.querySelectorAll('.paa-modulo-enlace').forEach(function (enlace) {
                const texto = (enlace.dataset.buscar || '') + ' ' + enlace.textContent.toLowerCase();
                const calza = !termino || texto.indexOf(termino) !== -1;
                enlace.hidden = !calza;
                if (calza) { enGrupo++; }
            });

            grupo.hidden = enGrupo === 0;
            visibles += enGrupo;
        });

        vacio.hidden = visibles > 0;

        // Que el lector diga cuántos quedaron sin tener que recorrer la lista.
        if (termino) {
            resultado.textContent = visibles === 1
                ? '1 módulo coincide'
                : visibles + ' módulos coinciden';
        } else {
            resultado.textContent = '';
        }
    }

    buscar.addEventListener('input', filtrar);
})();
</script>
