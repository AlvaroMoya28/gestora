<?php
/**
 * Portal del sistema PAA. Es lo primero que ve cualquiera que entre.
 *
 * ANTES ESTA PÁGINA ERA SOLO PARA ADMINISTRACIÓN
 * Al resto se le redirigía directo a su único módulo. Eso funcionaba mientras
 * «el resto» fuera una sola clase de persona con un solo módulo; en cuanto
 * aparecieron los funcionarios con dos y el personal extraordinario con otro,
 * cada rol nuevo obligaba a agregar una redirección más, en login.php, en esta
 * página y en la guarda de cada módulo — y bastaba equivocarse en una ruta para
 * dejar a alguien sin poder entrar a nada.
 *
 * Ahora entran todos acá y cada quien ve las tarjetas de los módulos que le
 * tocan, según includes/modulos.php. Dar acceso a un rol nuevo es editar una
 * línea de ese arreglo, no repartir redirecciones por el sistema.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/sesion.php';
require_once __DIR__ . '/includes/modulos.php';

$yo = exigir_login();

$misModulos = modulos_para($yo['rol'], (int) $yo['id']);
$rotulos    = titulo_del_portal($yo['rol']);

// Dashboard, Estado del Sistema y Bitácora son pantallas de operación: miden
// los servicios y muestran quién entró y qué tocó. A quien solo usa un módulo
// no le dicen nada y le harían creer que el sistema es más grande de lo que su
// cuenta alcanza.
$esAdmin = in_array($yo['rol'], ROLES_ADMIN, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Gestión PAA · UCR</title>
  <link rel="icon" type="image/png" href="assets/favicon-paa.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #0055a4;
      --primary-dark: #003b73;
      --primary-light: #2b7fc2;
      --secondary: #6ec1e4;
      --secondary-soft: #e3f0fa;
      --accent: #10b981;
      --accent-soft: #d1fae5;
      --warning: #f59e0b;
      --danger: #ef4444;
      --gray-50: #f9fafb;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #111827;
      --white: #ffffff;
      --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.05);
      --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 20px 35px -10px rgba(0, 0, 0, 0.12);
      --radius-sm: 12px;
      --radius: 20px;
      --radius-lg: 28px;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: radial-gradient(circle at 10% 20%, #f0f9ff 0%, #e6f0fa 100%);
      color: var(--gray-900);
      line-height: 1.5;
      min-height: 100vh;
      padding: 24px;
    }

    /* Una sola columna: el menú lateral pasó a ser la barra superior
       (includes/barra_superior.php). En la prueba con NVDA el menú de la
       izquierda era lo primero que había que recorrer en cada visita y
       repetía el nombre de la vista que ya estaba en el título. */
    .app-container {
      max-width: 1440px;
      margin: 0 auto;
      display: block;
    }

    /* La franja de contexto de la cuenta (foto, nombre, cambiar contraseña y
       convocatoria activa) ocupaba toda la primera pantalla antes de los
       módulos. Quién sos y sobre qué convocatoria trabajás ya viven en la
       barra de arriba, así que acá no queda nada. */

    .brand {
      background: linear-gradient(135deg, var(--primary), var(--primary-light), var(--secondary));
      border-radius: var(--radius);
      padding: 20px;
      color: white;
      margin-bottom: 24px;
      text-align: center;
    }

    .brand h1 {
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .brand p {
      font-size: 0.8rem;
      opacity: 0.9;
      margin-top: 6px;
    }

    .nav-menu {
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 20px;
    }

    .nav-btn {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      background: transparent;
      border: 1px solid var(--gray-200);
      border-radius: 14px;
      font-weight: 600;
      font-size: 0.95rem;
      color: var(--gray-700);
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: left;
    }

    .nav-btn i {
      width: 24px;
      font-size: 1.1rem;
    }

    .nav-btn.active {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
      box-shadow: var(--shadow-sm);
    }

    .nav-btn:not(.active):hover {
      background: var(--secondary-soft);
      border-color: var(--secondary);
    }

    .nav-btn:focus-visible {
      outline: 3px solid var(--secondary);
      outline-offset: 2px;
    }

    /* Buscador de módulos del portal */
    .buscador-modulos {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      margin-bottom: 20px;
      box-shadow: var(--shadow-sm);
    }
    .buscador-modulos i { color: var(--gray-500); }
    .buscador-modulos input {
      flex: 1;
      border: none;
      outline: none;
      font-family: inherit;
      font-size: 0.95rem;
      color: var(--gray-900);
      background: transparent;
      min-width: 0;
    }
    .buscador-modulos input:focus-visible { outline: none; }
    .buscador-modulos:focus-within {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 3px rgba(43, 127, 194, 0.18);
    }

    .sync-status {
      border-top: 1px dashed var(--gray-300);
      padding-top: 20px;
      margin-top: 8px;
    }

    .badge-live {
      background: var(--accent-soft);
      color: #065f46;
      border-radius: 40px;
      padding: 8px 12px;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
    }

    /* Modo de prueba (solo desarrollo) */
    .modo-prueba {
      border-top: 1px dashed var(--gray-300);
      margin-top: 14px;
      padding-top: 14px;
    }
    .modo-prueba-titulo {
      font-size: 0.7rem;
      font-weight: 700;
      color: var(--gray-500);
      text-transform: uppercase;
      letter-spacing: .03em;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .modo-prueba-botones {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
    }
    .modo-prueba-btn {
      font-size: 0.72rem;
      font-weight: 600;
      text-align: center;
      padding: 7px 4px;
      border-radius: 8px;
      background: #fff;
      color: var(--gray-600);
      text-decoration: none;
      border: 1px solid var(--gray-200);
    }
    .modo-prueba-btn:hover { border-color: var(--primary-light); color: var(--primary); }
    .modo-prueba-btn.activo {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }


    .banner-simulando {
      background: linear-gradient(135deg, #7c3aed, #a855f7);
      color: #fff;
      text-align: center;
      font-size: 0.8rem;
      font-weight: 700;
      padding: 8px 16px;
      border-radius: var(--radius);
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .banner-simulando a { color: #fff; text-decoration: underline; }

    /* Main Content */
    .main-content {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .top-bar {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(12px);
      border-radius: var(--radius);
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .page-title h2 {
      font-size: 1.7rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .page-title p {
      color: var(--gray-500);
      font-size: 0.85rem;
      margin-top: 4px;
    }

    .btn {
      padding: 10px 18px;
      border-radius: 40px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      font-size: 0.85rem;
    }

    .btn-outline {
      background: white;
      border: 1px solid var(--gray-200);
      color: var(--gray-700);
    }

    .btn-outline:hover {
      background: var(--gray-100);
      transform: translateY(-2px);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      box-shadow: var(--shadow-sm);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* Metrics Grid - Dashboard */
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--white);
      border-radius: var(--radius);
      padding: 24px;
      border: 1px solid var(--gray-200);
      transition: all 0.2s;
      box-shadow: var(--shadow-sm);
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: var(--gray-500);
      margin-bottom: 16px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .stat-header i {
      font-size: 1.4rem;
      color: var(--primary);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 8px;
    }

    .stat-delta {
      font-size: 0.75rem;
      font-weight: 500;
    }

    .stat-delta.up { color: var(--accent); }
    .stat-delta.flat { color: var(--gray-500); }
    .stat-delta.warn { color: var(--warning); }

    /* Dashboard Two Columns */
    .dashboard-two-columns {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-top: 8px;
    }

    .dashboard-three-columns {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-top: 8px;
    }

    @media (max-width: 1200px) {
      .dashboard-three-columns { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 700px) {
      .dashboard-three-columns { grid-template-columns: 1fr; }
    }

    .dashboard-card {
      background: var(--white);
      border-radius: var(--radius);
      padding: 24px;
      border: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
    }

    .dashboard-card h3 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .dashboard-card h3 i {
      color: var(--primary);
      font-size: 1.2rem;
    }

    .card-nota {
      font-size: 0.76rem;
      color: var(--gray-500);
      margin: -10px 0 12px;
    }

    /* Tools Grid - Principal */
    .tools-categoria { margin-bottom: 36px; }
    .tools-categoria:last-child { margin-bottom: 0; }

    .tools-categoria-titulo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
    }
    .tools-categoria-titulo .icono {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: var(--secondary-soft);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.05rem;
      flex-shrink: 0;
    }
    .tools-categoria-titulo h3 {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--gray-900);
    }
    .tools-categoria-titulo .cuenta {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--gray-500);
      background: var(--gray-100);
      padding: 3px 10px;
      border-radius: 999px;
    }
    .tools-categoria-titulo hr {
      flex: 1;
      border: none;
      border-top: 1px solid var(--gray-200);
    }

    .tools-wrapper {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 28px;
      margin-top: 8px;
    }

    .tool-modern-card {
      display: block;            /* es un <a>: se comporta como bloque */
      text-decoration: none;
      color: inherit;
      background: var(--white);
      border-radius: var(--radius);
      padding: 28px;
      border: 1px solid var(--gray-200);
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
      cursor: pointer;
    }

    .tool-modern-card:hover {
      transform: translateY(-8px);
      border-color: var(--primary-light);
      box-shadow: var(--shadow-lg);
    }

    .tool-icon {
      width: 64px;
      height: 64px;
      background: var(--secondary-soft);
      border-radius: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 2rem;
      margin-bottom: 24px;
      transition: all 0.2s;
    }

    .tool-modern-card:hover .tool-icon {
      background: var(--primary);
      color: white;
      transform: scale(1.05);
    }

    .tag-live, .tag-beta {
      font-size: 0.7rem;
      padding: 4px 12px;
      border-radius: 40px;
      font-weight: 700;
      display: inline-block;
    }

    .tag-live { background: var(--accent-soft); color: #065f46; }
    .tag-beta { background: #ffedd5; color: #b45309; }

    .tool-stats {
      display: flex;
      gap: 20px;
      margin: 20px 0 16px;
      padding-top: 16px;
      border-top: 1px solid var(--gray-100);
      font-size: 0.8rem;
      color: var(--gray-500);
    }

    .tool-stats span {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* System Status */
    .status-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .status-item {
      background: var(--white);
      padding: 14px 18px;
      border-radius: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 1px solid var(--gray-200);
    }

    .dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 10px;
    }
    .dot.ok { background: var(--accent); box-shadow: 0 0 0 2px var(--accent-soft);}
    .dot.warn { background: var(--warning);}

    /* Quick List Items */
    .quick-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid var(--gray-100);
    }

    .quick-item:last-child {
      border-bottom: none;
    }

    .quick-item strong {
      font-size: 0.95rem;
      color: var(--gray-900);
    }

    .quick-item small {
      font-size: 0.7rem;
      color: var(--gray-500);
      display: block;
    }

    .quick-item.clicable {
      cursor: pointer;
      border-radius: 8px;
      margin: 0 -8px;
      padding: 12px 8px;
      transition: background .15s;
    }
    .quick-item.clicable:hover { background: var(--gray-50); }

    /* Barra de proporción detrás del contador, en Módulos más usados */
    .quick-item .barra-fondo {
      position: relative;
      flex: 1;
      margin-right: 12px;
    }
    .quick-item .barra-fondo .barra-relleno {
      position: absolute;
      inset: 0;
      right: auto;
      background: var(--secondary-soft);
      border-radius: 6px;
      z-index: 0;
    }
    .quick-item .barra-fondo strong {
      position: relative;
      z-index: 1;
      padding-left: 8px;
      line-height: 28px;
    }

    /* Modal */
    .modal-glass {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(8px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
    }
    .modal-card {
      background: white;
      border-radius: 32px;
      max-width: 420px;
      width: 90%;
      overflow: hidden;
      animation: fadeUp 0.2s ease;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: scale(0.96);}
      to { opacity: 1; transform: scale(1);}
    }
    .modal-header {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      padding: 20px;
      color: white;
    }
    .modal-body { padding: 24px; white-space: pre-line; }
    .modal-footer { padding: 16px 24px 24px; display: flex; justify-content: flex-end; gap: 12px; }

    .hidden { display: none; }

    /* ---------- BITÁCORA ---------- */

    .bitacora-tabs {
      display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap;
    }
    .bitacora-tab {
      border: none; cursor: pointer; font-family: inherit;
      font-size: 0.82rem; font-weight: 600; padding: 9px 16px;
      border-radius: var(--radius-sm); background: var(--white);
      color: var(--gray-700); box-shadow: var(--shadow-sm);
      transition: all .18s ease;
    }
    .bitacora-tab.active { background: var(--primary); color: var(--white); }
    .bitacora-tab:not(.active):hover { background: var(--gray-100); }

    .filtros {
      display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;
      background: var(--white); padding: 16px; border-radius: var(--radius);
      box-shadow: var(--shadow-sm); margin-bottom: 16px;
    }
    .filtros label {
      display: flex; flex-direction: column; gap: 4px;
      font-size: 0.7rem; font-weight: 700; color: var(--gray-500);
      text-transform: uppercase; letter-spacing: .03em;
    }
    .filtros input, .filtros select {
      font-family: inherit; font-size: 0.85rem; padding: 7px 10px;
      border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
      background: var(--white); color: var(--gray-900);
    }

    /* El scroll horizontal va en el contenedor, no en la página: con la IP y
       la fecha la tabla no cabe en pantallas angostas. */
    .tabla-scroll {
      overflow-x: auto; background: var(--white);
      border-radius: var(--radius); box-shadow: var(--shadow-sm);
    }
    .tabla-bitacora { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .tabla-bitacora th {
      text-align: left; padding: 12px 14px; white-space: nowrap;
      font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .04em; color: var(--gray-500);
      background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
      position: sticky; top: 0;
    }
    .tabla-bitacora td {
      padding: 11px 14px; border-bottom: 1px solid var(--gray-100);
      color: var(--gray-700); vertical-align: middle;
    }
    .tabla-bitacora tbody tr:hover { background: var(--gray-50); }
    .tabla-bitacora .mono {
      font-family: ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
      font-size: 0.76rem; color: var(--gray-500);
    }
    .tabla-vacia { padding: 40px; text-align: center; color: var(--gray-500); }

    .pill {
      display: inline-block; padding: 3px 9px; border-radius: 999px;
      font-size: 0.68rem; font-weight: 700; white-space: nowrap;
    }
    .pill-ok   { background: #dcfce7; color: #166534; }
    .pill-mal  { background: #fee2e2; color: #991b1b; }
    .pill-mod  { background: var(--primary-light); color: var(--primary-dark); }

    .paginacion {
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; margin-top: 14px; flex-wrap: wrap;
    }
    .paginacion .paginas { display: flex; gap: 6px; align-items: center; }
    .pag-btn {
      border: 1px solid var(--gray-200); background: var(--white);
      border-radius: var(--radius-sm); padding: 7px 12px; cursor: pointer;
      font-family: inherit; font-size: 0.8rem; font-weight: 600;
      color: var(--gray-700);
    }
    .pag-btn:disabled { opacity: .4; cursor: not-allowed; }
    .pag-btn:not(:disabled):hover { background: var(--gray-100); }

    /* Barras del gráfico de horas: sin librerías, con divs. Es lo que ya hace
       el resto de la página y evita cargar un Chart.js entero para esto. */
    .barras { display: flex; align-items: flex-end; gap: 3px; height: 130px; margin-top: 10px; }
    .barra {
      flex: 1; background: var(--secondary-soft); border-radius: 4px 4px 0 0;
      min-height: 2px; position: relative; transition: background .15s;
      display: flex; align-items: flex-end; justify-content: center;
    }
    .barra:hover { background: var(--primary); }
    .barra.pico { background: var(--primary-light); }
    .barra .valor {
      position: absolute; top: -18px; font-size: 0.62rem; font-weight: 700;
      color: var(--gray-500); white-space: nowrap;
    }
    .barra:hover .valor { color: var(--primary); }
    .barras-etiquetas {
      display: flex; gap: 3px; margin-top: 6px;
      font-size: 0.6rem; color: var(--gray-500);
    }
    .barras-etiquetas span { flex: 1; text-align: center; }

    .horas-cabecera {
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; flex-wrap: wrap; margin-bottom: 0;
    }
    .horas-cabecera h3 { margin-bottom: 0; }
    .horas-pico {
      font-size: 0.78rem; font-weight: 600; color: var(--primary);
      background: var(--secondary-soft); padding: 4px 12px; border-radius: 999px;
    }

    /* Encabezado del resumen: métricas + selector de período */
    .resumen-encabezado {
      display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;
    }
    .periodo-selector {
      display: flex; flex-direction: column; gap: 4px;
      font-size: 0.7rem; font-weight: 700; color: var(--gray-500);
      text-transform: uppercase; letter-spacing: .03em;
      margin-bottom: 4px;
    }
    .periodo-selector select {
      font-family: inherit; font-size: 0.85rem; padding: 9px 12px;
      border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
      background: var(--white); color: var(--gray-900);
    }

    /* En línea ahora */
    .en-linea-card { margin-bottom: 18px; }
    .en-linea-punto {
      width: 9px; height: 9px; border-radius: 50%; background: var(--accent);
      display: inline-block; margin-left: 6px;
      box-shadow: 0 0 0 3px var(--accent-soft);
      animation: pulso 2s infinite;
    }
    @keyframes pulso {
      0%, 100% { opacity: 1; }
      50% { opacity: .35; }
    }
    .en-linea-lista { display: flex; flex-wrap: wrap; gap: 10px; }
    .en-linea-persona {
      display: flex; align-items: center; gap: 8px;
      background: var(--gray-50); border: 1px solid var(--gray-100);
      border-radius: 999px; padding: 6px 14px 6px 8px;
    }
    .en-linea-avatar {
      width: 26px; height: 26px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white; font-size: 0.68rem; font-weight: 700;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .en-linea-persona strong { font-size: 0.8rem; color: var(--gray-900); }
    .en-linea-persona small { font-size: 0.68rem; color: var(--gray-500); display: block; }

    .zona-peligro {
      background: #fff7ed; border: 1px solid #fed7aa;
      border-radius: var(--radius); padding: 18px; margin-top: 20px;
    }
    .zona-peligro h4 { color: #9a3412; font-size: 0.9rem; margin-bottom: 6px; }
    .zona-peligro p { color: #7c2d12; font-size: 0.8rem; margin-bottom: 14px; line-height: 1.5; }

    @media (max-width: 900px) {
      body { padding: 16px; }
      .app-container { grid-template-columns: 1fr; }
      .tools-wrapper { grid-template-columns: 1fr; }
      .dashboard-two-columns { grid-template-columns: 1fr; }
      .tool-modern-card { padding: 20px; }
    }

    @media (max-width: 480px) {
      .tool-stats { flex-direction: column; gap: 8px; }
    }
  </style>
</head>
<body>

<div class="app-container">
  <!-- Acá vivía una franja con la foto, el nombre, «Cambiar contraseña» y el
       panel de «Convocatoria activa». Ocupaba toda la primera pantalla y
       empujaba los módulos hacia abajo, que es lo que la gente viene a buscar.
       Quién sos, cambiar la contraseña y la convocatoria activa están ahora en
       la barra de arriba (includes/barra_superior.php), disponibles desde
       cualquier página y no solo desde el inicio. -->

  <!-- Acá había una segunda fila de botones (Módulos, Dashboard, Estado del
       sistema, Bitácora) que repetía exactamente lo que ya está en la barra
       de arriba: se veía dos veces y, con lector de pantalla, se escuchaba
       dos veces. La navegación quedó una sola vez, en la barra superior; los
       enlaces de esa barra cambian de vista sin recargar (ver más abajo). -->

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <?php if (!empty($yo['simulando'])): ?>
    <div class="banner-simulando">
      <i class="fa-solid fa-user-secret"></i>
      <span>Estás viendo el sistema como <strong><?= h(nombre_del_rol($yo['rol'])) ?></strong>, modo prueba.</span>
      <a href="<?= h(base_url()) ?>dev_simular.php?rol=<?= h(ROL_DESARROLLADOR) ?>">Volver a mi cuenta</a>
    </div>
    <?php endif; ?>
    <div class="top-bar">
      <div class="page-title">
        <h2 id="viewTitle"><?= h($rotulos['titulo']) ?></h2>
        <p id="viewSubtitle"><?= h($rotulos['sub']) ?></p>
      </div>
      <!-- Acá había «Sincronizar» y «Exportar». La medición ya corre sola al
           entrar y cada cinco minutos, así que el botón no hacía nada que no
           pasara igual; y la exportación bajaba un JSON del estado interno que
           no le servía a nadie desde acá. -->
    </div>

    <!-- VISTA HERRAMIENTAS (PRINCIPAL) -->
    <!-- Los módulos y quién entra a cada uno están en includes/modulos.php. -->
    <div id="herramientasView" class="view-container" role="region" aria-label="Módulos disponibles">
      <!-- Buscador: con veinte módulos repartidos en cuatro categorías, ir a
           buscarlos con el teclado categoría por categoría es largo. Escribir
           dos letras y que quede uno solo es mucho más corto. -->
      <div class="buscador-modulos">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <label for="buscarModulo" class="paa-sr-solo">Buscar módulo por nombre o por lo que hace</label>
        <input type="text" id="buscarModulo" autocomplete="off"
               placeholder="Buscar módulo… (sedes, tickets, cronograma, horarios…)">
      </div>
      <div id="buscarModuloResultado" role="status" aria-live="polite" class="paa-sr-solo"></div>

      <div id="toolsGridModern"></div>
    </div>

    <?php if ($esAdmin): ?>
    <!-- VISTA DASHBOARD -->
    <div id="dashboardView" class="view-container hidden" role="region" aria-label="Dashboard" hidden>
      <div class="metrics-grid" id="metricsContainer"></div>
      <div class="dashboard-two-columns">
        <div class="dashboard-card">
          <h3><i class="fa-regular fa-star"></i> Herramientas Más Activas</h3>
          <div id="topToolsList"></div>
        </div>
        <div class="dashboard-card">
          <h3><i class="fa-solid fa-server"></i> Monitoreo Rápido</h3>
          <div id="quickStatus"></div>
        </div>
      </div>
    </div>

    <!-- VISTA SISTEMA -->
    <div id="sistemaView" class="view-container hidden" role="region" aria-label="Estado del sistema" hidden>
      <div class="status-list" id="systemFullStatus"></div>
    </div>

    <!-- VISTA BITÁCORA -->
    <div id="bitacoraView" class="view-container hidden" role="region" aria-label="Bitácora" hidden>

      <div class="bitacora-tabs" id="bitacoraTabs">
        <button class="bitacora-tab active" data-panel="resumen"><i class="fa-solid fa-chart-simple"></i> Resumen</button>
        <button class="bitacora-tab" data-panel="ingresos"><i class="fa-solid fa-right-to-bracket"></i> Ingresos</button>
        <button class="bitacora-tab" data-panel="actividad"><i class="fa-solid fa-list-check"></i> Actividad</button>
      </div>

      <!-- RESUMEN -->
      <div id="bitResumen">
        <div class="dashboard-card en-linea-card">
          <h3><i class="fa-solid fa-tower-broadcast"></i> En línea ahora
            <span class="en-linea-punto" id="enLineaPunto"></span>
          </h3>
          <p class="card-nota">Personas con alguna acción en los últimos 5 minutos. Se actualiza solo.</p>
          <div id="bitEnLinea"></div>
        </div>

        <div class="resumen-encabezado">
          <div class="metrics-grid" id="bitMetricas" style="flex:1;"></div>
          <label class="periodo-selector">Período
            <select id="bitDias">
              <option value="7">Últimos 7 días</option>
              <option value="30" selected>Últimos 30 días</option>
              <option value="90">Últimos 90 días</option>
              <option value="365">Último año</option>
            </select>
          </label>
        </div>
        <div class="dashboard-three-columns">
          <div class="dashboard-card">
            <h3><i class="fa-solid fa-user-clock"></i> Quiénes entran más</h3>
            <p class="card-nota">Por cantidad de inicios de sesión.</p>
            <div id="bitTopUsuarios"></div>
          </div>
          <div class="dashboard-card">
            <h3><i class="fa-solid fa-bolt"></i> Quiénes trabajan más</h3>
            <p class="card-nota">Por cantidad de acciones hechas en el sistema.</p>
            <div id="bitTopActivos"></div>
          </div>
          <div class="dashboard-card">
            <h3><i class="fa-solid fa-cubes"></i> Módulos más usados</h3>
            <p class="card-nota">Acciones registradas por módulo.</p>
            <div id="bitTopModulos"></div>
          </div>
        </div>
        <div class="dashboard-card" style="margin-top: 18px;">
          <div class="horas-cabecera">
            <h3><i class="fa-regular fa-clock"></i> A qué hora se trabaja</h3>
            <span class="horas-pico" id="bitHorasPico"></span>
          </div>
          <div class="barras" id="bitHoras"></div>
          <div class="barras-etiquetas" id="bitHorasEtiquetas"></div>
        </div>

        <div class="zona-peligro">
          <h4><i class="fa-solid fa-triangle-exclamation"></i> Limpiar la bitácora</h4>
          <p>
            Elimina registros viejos para que la tabla no crezca sin control.
            La limpieza <strong>queda registrada</strong> con tu nombre, la fecha y
            cuántas filas se quitaron: es la única forma de que este registro
            sirva como evidencia.
          </p>
          <div class="filtros" style="margin: 0; box-shadow: none; padding: 0; background: transparent;">
            <label>Eliminar
              <select id="limpiarQue">
                <option value="ambas">Ingresos y actividad</option>
                <option value="ingresos">Solo ingresos</option>
                <option value="actividad">Solo actividad</option>
              </select>
            </label>
            <label>Anteriores a
              <select id="limpiarDias">
                <option value="365">1 año</option>
                <option value="180">6 meses</option>
                <option value="90" selected>3 meses</option>
                <option value="30">1 mes</option>
                <option value="0">— vaciar todo —</option>
              </select>
            </label>
            <button class="btn btn-outline" id="limpiarBtn"
                    style="color:#9a3412; border-color:#fed7aa;">
              <i class="fa-solid fa-broom"></i> Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- INGRESOS -->
      <div id="bitIngresos" class="hidden" hidden>
        <div class="filtros">
          <label>Usuario <input type="search" id="fiUsuario" placeholder="parte del nombre"></label>
          <label>Resultado
            <select id="fiResultado">
              <option value="">Todos</option>
              <option value="si">Solo exitosos</option>
              <option value="no">Solo fallidos</option>
            </select>
          </label>
          <label>Desde <input type="date" id="fiDesde"></label>
          <label>Hasta <input type="date" id="fiHasta"></label>
          <button class="btn btn-primary" id="fiAplicar"><i class="fa-solid fa-filter"></i> Filtrar</button>
          <button class="btn btn-outline" id="fiLimpiar">Quitar filtros</button>
        </div>
        <div class="tabla-scroll">
          <table class="tabla-bitacora">
            <thead>
              <tr><th>Fecha y hora</th><th>Usuario</th><th>Nombre</th><th>Resultado</th><th>Origen</th></tr>
            </thead>
            <tbody id="tbIngresos"></tbody>
          </table>
        </div>
        <div class="paginacion" id="pgIngresos"></div>
      </div>

      <!-- ACTIVIDAD -->
      <div id="bitActividad" class="hidden" hidden>
        <div class="filtros">
          <label>Usuario <input type="search" id="faUsuario" placeholder="parte del nombre"></label>
          <label>Módulo
            <select id="faModulo"><option value="">Todos</option></select>
          </label>
          <label>Desde <input type="date" id="faDesde"></label>
          <label>Hasta <input type="date" id="faHasta"></label>
          <button class="btn btn-primary" id="faAplicar"><i class="fa-solid fa-filter"></i> Filtrar</button>
          <button class="btn btn-outline" id="faLimpiar">Quitar filtros</button>
        </div>
        <div class="tabla-scroll">
          <table class="tabla-bitacora">
            <thead>
              <tr><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Sobre qué</th><th>Resultado</th></tr>
            </thead>
            <tbody id="tbActividad"></tbody>
          </table>
        </div>
        <div class="paginacion" id="pgActividad"></div>
      </div>

    </div>
    <?php endif; ?>
  </main>
</div>

<!-- MODAL NOTIFICACIÓN -->
<div id="globalModal" class="modal-glass" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="modalTitle">Notificación</h3>
    </div>
    <div class="modal-body" id="modalMessage">Mensaje del sistema</div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="closeModalBtn">Entendido</button>
    </div>
  </div>
</div>

<script>
  // ====================== CATÁLOGO DE MÓDULOS ======================
  //
  // Viene de includes/modulos.php YA FILTRADO por el rol de quien entró: el
  // navegador nunca recibe la lista completa. Si se filtrara acá, bastaría con
  // abrir las herramientas del navegador para ver los nombres y las rutas de
  // todo lo que existe, y eso es justamente lo que no debe saber alguien que
  // solo tiene acceso a una pantalla.
  const toolLibrary = <?= json_encode(array_map(
      static fn(array $m): array => [
          "id" => $m["id"], "nombre" => $m["nombre"],
          "descripcion" => $m["descripcion"], "estado" => $m["estado"],
          "icono" => $m["icono"], "url" => $m["url"],
          "caracteristica" => $m["caracteristica"],
          "categoria" => $m["categoria"],
      ],
      $misModulos
  ), JSON_UNESCAPED_UNICODE) ?>;

  // Mismo catálogo de categorías que arma el menú del lado del servidor: el
  // orden en que se muestran las secciones sale de acá, no de en qué orden
  // aparecen los módulos en toolLibrary.
  const CATEGORIAS = <?= json_encode(categorias_del_sistema(), JSON_UNESCAPED_UNICODE) ?>;

  // Las pantallas de operación existen solo para administración: el resto del
  // marcado ni siquiera se envía, así que el JS tiene que saberlo para no
  // buscar elementos que no están.
  const ES_ADMIN = <?= $esAdmin ? "true" : "false" ?>;

  // Métricas dinámicas
  // ====================== ORÍGENES DE DATOS REALES ======================
  //
  // Todo lo que se muestra en Dashboard y Estado del Sistema se mide acá.
  // Antes eran constantes escritas a mano ("428 operaciones/día", "187 ms")
  // que nunca cambiaban: una pantalla de monitoreo que siempre decía
  // "Operativo" da confianza falsa, que es peor que no tener monitoreo.
  //
  // Se dejó fuera todo lo que no se puede medir desde el navegador
  // (operaciones por día, sesiones activas, usuarios por módulo): no hay
  // de dónde sacarlo sin un registro de eventos del lado del servidor.

  // MIGRADO A LA BASE DE DATOS INSTITUCIONAL.
  //
  // Antes esta página medía el estado del sistema descargando dos CSV de
  // Google y haciendo ping a dos Apps Script: cuatro llamadas a servicios
  // externos en cada carga. Ahora es una consulta al propio backend.
  // Raíz del sitio. Los enlaces a los módulos se arman con esto y no como
  // rutas relativas: relativas, estando en /Modulo/ el enlace 'Otro/' lleva a
  // /Modulo/Otro/ y la dirección se va multiplicando en cada clic.
  const BASE = <?= json_encode(base_url()) ?>;

  const API_RESUMEN = 'api/administracion.php?accion=resumen';
  const API_ESTADO  = 'api/administracion.php?accion=estado';

  const TIMEOUT_SONDEO_MS = 15000;

  let metricData = [];      // se llena al medir
  let systemHealth = [];    // idem
  let midiendo = false;
  let currentView = "herramientas";

  // ---------- utilidades ----------

  function escapar(texto) {
    return String(texto == null ? '' : texto)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // Mismo parser que usan Tulas y Revisión: un CSV de Sheets puede traer
  // comas, saltos de línea y comillas escapadas dentro de un campo.
  function parsearCSV(texto) {
    const t = String(texto || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    const filas = [];
    let fila = [], campo = '', enComillas = false;

    for (let i = 0; i < t.length; i++) {
      const c = t[i];
      if (enComillas) {
        if (c !== '"') campo += c;
        else if (t[i + 1] === '"') { campo += '"'; i++; }
        else enComillas = false;
        continue;
      }
      if (c === '"') enComillas = true;
      else if (c === ',') { fila.push(campo); campo = ''; }
      else if (c === '\n') { fila.push(campo); filas.push(fila); fila = []; campo = ''; }
      else campo += c;
    }
    if (campo !== '' || fila.length) { fila.push(campo); filas.push(fila); }
    return filas.map(f => f.map(v => v.trim())).filter(f => f.some(v => v !== ''));
  }

  // Cronometra una petición y clasifica el resultado. Nunca lanza: un servicio
  // caído es un dato a mostrar, no un error que rompa la página.
  async function sondear(nombre, ejecutar) {
    const t0 = performance.now();
    const controlador = new AbortController();
    const corte = setTimeout(() => controlador.abort(), TIMEOUT_SONDEO_MS);

    try {
      const detalle = await ejecutar(controlador.signal);
      const ms = Math.round(performance.now() - t0);
      return {
        nombre,
        estado: detalle || 'Operativo',
        nivel: ms > 3000 ? 'warn' : 'ok',
        latencia: ms + ' ms'
      };
    } catch (error) {
      return {
        nombre,
        estado: error.name === 'AbortError' ? 'Sin respuesta' : 'Caído',
        nivel: 'error',
        latencia: '—',
        error: String(error.message || error)
      };
    } finally {
      clearTimeout(corte);
    }
  }

  // ---------- medición ----------

  let datosTulas = null;   // { total, prestadas, disponibles, sedes }

  async function medirTodo() {
    if (midiendo) return;
    midiendo = true;
    renderSistema();   // muestra "midiendo…" mientras tanto

    const [tulas, personas, baseDatos] = await Promise.all([
      sondear('Tulas de la convocatoria', async (signal) => {
        const r = await fetch(`${API_RESUMEN}&_ts=${Date.now()}`, { cache: 'no-store', signal });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'sin datos');

        // Las métricas de la jornada vienen calculadas por la base, en vez de
        // recorrer el CSV entero en el navegador.
        datosTulas = {
          total:       d.tulas.total,
          prestadas:   d.tulas.prestado,
          disponibles: d.tulas.disponible,
          sedes:       d.contadores.sedesConAulas
        };

        if (d.tulas.total === 0) throw new Error('sin registros');
        return d.tulas.total + ' registros';
      }),

      sondear('Directorio de funcionarios', async (signal) => {
        const r = await fetch(`${API_RESUMEN}&_ts=${Date.now()}`, { cache: 'no-store', signal });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'sin datos');
        return d.contadores.personas + ' personas';
      }),

      // Antes acá se sondeaban los dos Apps Script mandándoles una operación
      // inválida a propósito. Ya no existen: lo que hay que vigilar es la base
      // y que las tablas tengan datos.
      sondear('Base de datos', async (signal) => {
        const r = await fetch(`${API_ESTADO}&_ts=${Date.now()}`, { cache: 'no-store', signal });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'no responde');

        const vacias = Object.entries(d.tablas)
          .filter(([, n]) => n === null)
          .map(([t]) => t);

        if (vacias.length) throw new Error('faltan tablas: ' + vacias.join(', '));
        return 'Conectada · PHP ' + d.php;
      })
    ]);

    systemHealth = [tulas, personas, baseDatos];
    construirMetricas();

    midiendo = false;
    renderDashboard();
    renderSistema();
  }

  function construirMetricas() {
    const caidos = systemHealth.filter(s => s.nivel === 'error').length;
    const lentos = systemHealth.filter(s => s.nivel === 'warn').length;

    metricData = [
      {
        titulo: "Módulos Activos", valor: toolLibrary.length,
        delta: "en el catálogo", tipo: "flat", icon: "fa-cube"
      },
      {
        titulo: "Servicios en línea",
        valor: `${systemHealth.length - caidos}/${systemHealth.length}`,
        delta: caidos ? `${caidos} sin responder` : (lentos ? `${lentos} lento(s)` : "todos responden"),
        tipo: caidos ? "warn" : "up",
        icon: "fa-heart-pulse"
      }
    ];

    if (datosTulas) {
      const pct = datosTulas.total ? Math.round((datosTulas.prestadas / datosTulas.total) * 100) : 0;
      metricData.push(
        {
          titulo: "Tulas entregadas", valor: `${datosTulas.prestadas}/${datosTulas.total}`,
          delta: `${pct}% de la jornada`, tipo: pct === 100 ? "up" : "flat", icon: "fa-boxes-stacked"
        },
        {
          titulo: "Sedes cargadas", valor: datosTulas.sedes,
          delta: "en el Sheet de tulas", tipo: "flat", icon: "fa-school"
        }
      );
    } else {
      metricData.push({
        titulo: "Datos de tulas", valor: "—",
        delta: "no se pudo leer el Sheet", tipo: "warn", icon: "fa-triangle-exclamation"
      });
    }
  }

  // ---------- modal ----------

  let ultimoFoco = null;

  function showModal(title, message) {
    const modal = document.getElementById("globalModal");
    ultimoFoco = document.activeElement;
    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalMessage").innerText = message;
    modal.style.display = "flex";
    document.getElementById("closeModalBtn").focus();
  }

  function closeModal() {
    document.getElementById("globalModal").style.display = "none";
    // Devolver el foco a donde estaba evita que quien navega con teclado
    // quede perdido al principio de la página.
    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
    ultimoFoco = null;
  }

  function modalAbierto() {
    return document.getElementById("globalModal").style.display === "flex";
  }

  // ---------- vistas ----------

  function renderDashboard() {
    const metricsDiv = document.getElementById("metricsContainer");

    if (!metricData.length) {
      metricsDiv.innerHTML = `<div class="stat-card" style="grid-column:1/-1; text-align:center; color:var(--gray-500);">
        <i class="fa-solid fa-rotate fa-spin"></i> Midiendo…
      </div>`;
    } else {
      metricsDiv.innerHTML = metricData.map(m => `
        <div class="stat-card">
          <div class="stat-header">
            <span>${escapar(m.titulo)}</span>
            <i class="fa-solid ${escapar(m.icon)}"></i>
          </div>
          <div class="stat-number">${escapar(m.valor)}</div>
          <div class="stat-delta ${escapar(m.tipo)}">${escapar(m.delta)}</div>
        </div>
      `).join("");
    }

    // Accesos rápidos: enlaces reales, no botones que llaman a window.open
    document.getElementById("topToolsList").innerHTML = toolLibrary.map(t => `
      <div class="quick-item">
        <div>
          <strong>${escapar(t.nombre)}</strong>
          <small>${escapar(t.caracteristica || '')}</small>
        </div>
        <a class="btn btn-outline" style="padding: 6px 14px;" href="${BASE}${escapar(t.url)}">
          <i class="fa-solid fa-arrow-right"></i> Abrir
        </a>
      </div>
    `).join("");

    document.getElementById("quickStatus").innerHTML = systemHealth.length
      ? systemHealth.map(s => `
          <div style="display:flex; justify-content:space-between; align-items:center; padding: 12px 0; border-bottom: 1px solid var(--gray-100);">
            <div><span class="dot ${escapar(s.nivel)}"></span> ${escapar(s.nombre)}</div>
            <span style="font-weight:600; font-size:0.85rem;">${escapar(s.estado)}</span>
          </div>
        `).join("")
      : `<div style="color:var(--gray-500); padding:12px 0;"><i class="fa-solid fa-rotate fa-spin"></i> Midiendo…</div>`;
  }

  function tarjetaHerramienta(t) {
    // La tarjeta entera es un <a>: así se puede abrir con el teclado, con la
    // rueda del mouse, copiar el enlace y ver a dónde lleva antes de tocarlo.
    // Por eso el "botón" de adentro es un <span>: un <button> dentro de un
    // enlace es HTML inválido.
    return `
      <a class="tool-modern-card" href="${BASE}${escapar(t.url)}">
        <div class="tool-icon"><i class="fa-solid ${escapar(t.icono)}"></i></div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; gap: 12px;">
          <h3 style="font-size:1.4rem; font-weight: 700; color: var(--gray-900);">${escapar(t.nombre)}</h3>
          <span class="${t.estado === 'live' ? 'tag-live' : 'tag-beta'}">${t.estado === 'live' ? 'OPERATIVO' : 'BETA'}</span>
        </div>
        <p style="color: var(--gray-600); font-size:0.9rem; line-height: 1.5; margin-bottom: 16px;">${escapar(t.descripcion)}</p>
        ${t.caracteristica ? `<div style="background: var(--gray-50); padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; color: var(--primary); display: inline-block; margin-bottom: 16px;"><i class="fa-regular fa-circle-check"></i> ${escapar(t.caracteristica)}</div>` : ''}
        <span class="btn btn-primary" style="width:100%; justify-content:center; margin-top: 8px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Acceder al módulo
        </span>
      </a>`;
  }

  // Categorías con al menos un módulo para este rol. Si es una sola (el caso
  // de funcionario y de personal extraordinario), no tiene sentido pedir un
  // clic de más para "entrar" a la única opción que hay.
  const categoriasConModulos = () => Object.entries(CATEGORIAS)
    .map(([clave, cat]) => [clave, cat, toolLibrary.filter(t => t.categoria === clave)])
    .filter(([, , modulos]) => modulos.length > 0);

  // La categoría abierta vive en la URL (?categoria=embalaje), no solo en esta
  // variable: así "Atrás" del navegador tiene a dónde volver. Sin esto, salir
  // de un módulo con "Atrás" no aterrizaba en su categoría ni en el portal —
  // caía en lo que fuera que hubiera antes en el historial, que podía ser
  // login.php y sentirse como si el sistema hubiera echado a la persona.
  const categoriaDeUrl = () => new URLSearchParams(location.search).get("categoria");

  // null = viendo las categorías. Un id = adentro de esa categoría.
  let categoriaAbierta = categoriaDeUrl();

  window.addEventListener("popstate", () => {
    categoriaAbierta = categoriaDeUrl();
    renderHerramientas();
  });

  function tarjetaCategoria([clave, cat, modulos]) {
    return `
      <a class="tool-modern-card categoria-card" href="#" data-categoria="${escapar(clave)}">
        <div class="tool-icon"><i class="fa-solid ${escapar(cat.icono)}"></i></div>
        <h3 style="font-size:1.4rem; font-weight: 700; color: var(--gray-900); margin-bottom: 10px;">${escapar(cat.nombre)}</h3>
        <p style="color: var(--gray-600); font-size:0.9rem; line-height: 1.5; margin-bottom: 16px;">${escapar(cat.descripcion)}</p>
        <span class="btn btn-primary" style="width:100%; justify-content:center; margin-top: 8px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-folder-open"></i> Ver ${modulos.length} módulo${modulos.length === 1 ? '' : 's'}
        </span>
      </a>`;
  }

  /**
   * Lo escrito en el buscador. Mientras haya algo escrito se muestran los
   * módulos que calzan, planos y sin categorías: buscar sirve justamente para
   * saltarse el paso de elegir categoría.
   */
  const terminoBuscado = () =>
    (document.getElementById("buscarModulo")?.value || "").trim().toLowerCase();

  function renderBusqueda(termino) {
    const container = document.getElementById("toolsGridModern");
    const aviso = document.getElementById("buscarModuloResultado");

    const calzan = toolLibrary.filter(t =>
      [t.nombre, t.descripcion, t.caracteristica, CATEGORIAS[t.categoria]?.nombre]
        .filter(Boolean).join(" ").toLowerCase().includes(termino)
    );

    if (aviso) {
      aviso.textContent = calzan.length === 1
        ? `1 módulo coincide con ${termino}`
        : `${calzan.length} módulos coinciden con ${termino}`;
    }

    if (!calzan.length) {
      container.innerHTML = `<div class="stat-card" style="text-align:center; padding: 40px 20px;">
        <h4>Ningún módulo coincide con «${escapar(termino)}»</h4>
        <p style="color: var(--gray-500);">Probá con otra palabra, o borrá la búsqueda para ver todo.</p>
      </div>`;
      return;
    }

    container.innerHTML = `
      <div class="tools-categoria">
        <div class="tools-categoria-titulo">
          <h3>Resultados de la búsqueda</h3>
          <span class="cuenta">${calzan.length}</span>
          <hr>
        </div>
        <div class="tools-wrapper">${calzan.map(tarjetaHerramienta).join("")}</div>
      </div>`;
  }

  function renderHerramientas() {
    const container = document.getElementById("toolsGridModern");

    const termino = terminoBuscado();
    if (termino) { return renderBusqueda(termino); }

    const aviso = document.getElementById("buscarModuloResultado");
    if (aviso) aviso.textContent = "";

    const disponibles = categoriasConModulos();

    if (disponibles.length === 0) {
      container.innerHTML = `<div class="stat-card" style="text-align:center; padding: 60px 20px;">
        <i class="fa-regular fa-compass" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 16px; display: block;"></i>
        <h4>No hay herramientas disponibles</h4>
        <p style="color: var(--gray-500);">Agrega herramientas al catálogo para visualizarlas aquí</p>
      </div>`;
      return;
    }

    // Una sola categoría disponible: se muestra directo, sin pedir el clic.
    const forzada = disponibles.length === 1 ? disponibles[0][0] : null;
    const activa = categoriaAbierta ?? forzada;

    if (activa === null) {
      container.innerHTML = `<div class="tools-wrapper">${disponibles.map(tarjetaCategoria).join("")}</div>`;

      container.querySelectorAll(".categoria-card").forEach(el => {
        el.addEventListener("click", (e) => {
          e.preventDefault();
          categoriaAbierta = el.dataset.categoria;
          history.pushState({ categoria: categoriaAbierta }, "", "?categoria=" + encodeURIComponent(categoriaAbierta));
          renderHerramientas();
        });
      });
      return;
    }

    const [, cat, modulos] = disponibles.find(([clave]) => clave === activa) || [];
    if (!cat) { categoriaAbierta = null; return renderHerramientas(); }

    container.innerHTML = `
      <div class="tools-categoria">
        <div class="tools-categoria-titulo">
          ${forzada ? "" : `
            <button type="button" class="btn btn-outline" id="btnVolverCategorias" style="padding:8px 14px;">
              <i class="fa-solid fa-arrow-left"></i> Categorías
            </button>`}
          <div class="icono"><i class="fa-solid ${escapar(cat.icono)}"></i></div>
          <h3>${escapar(cat.nombre)}</h3>
          <span class="cuenta">${modulos.length}</span>
          <hr>
        </div>
        <div class="tools-wrapper">
          ${modulos.map(tarjetaHerramienta).join("")}
        </div>
      </div>`;

    const btnVolver = document.getElementById("btnVolverCategorias");
    if (btnVolver) btnVolver.addEventListener("click", () => {
      categoriaAbierta = null;
      history.pushState({ categoria: null }, "", location.pathname);
      renderHerramientas();
    });
  }

  function renderSistema() {
    const div = document.getElementById("systemFullStatus");

    if (midiendo && !systemHealth.length) {
      div.innerHTML = `<div class="status-item"><div><i class="fa-solid fa-rotate fa-spin"></i> Midiendo servicios…</div></div>`;
      return;
    }

    div.innerHTML = systemHealth.map(s => `
      <div class="status-item">
        <div>
          <span class="dot ${escapar(s.nivel)}"></span> <strong>${escapar(s.nombre)}</strong>
          ${s.error ? `<div style="font-size:0.78rem; color:var(--danger); margin-left:20px;">${escapar(s.error)}</div>` : ''}
        </div>
        <div><span style="background: var(--gray-100); padding:4px 12px; border-radius:40px;">${escapar(s.estado)} · ${escapar(s.latencia)}</span></div>
      </div>
    `).join("") || `<div class="status-item"><div>Sin mediciones todavía</div></div>`;
  }

  // ---------- navegación ----------

  // El rótulo de la primera pestaña lo pone PHP, porque cambia según el rol:
  // quien ve diez módulos está en un centro de herramientas y quien ve uno no.
  const VISTAS = {
    herramientas: {
      titulo: document.getElementById("viewTitle").innerText,
      sub:    document.getElementById("viewSubtitle").innerText
    },
    dashboard:    { titulo: "Dashboard", sub: "Indicadores medidos en vivo contra los Sheets y los Apps Script" },
    sistema:      { titulo: "Estado del Sistema", sub: "Disponibilidad y tiempo de respuesta de cada servicio" },
    bitacora:     { titulo: "Bitácora", sub: "Quién entra al sistema, cuándo y qué modifica" }
  };

  function setActiveView(viewId) {
    currentView = viewId;

    Object.keys(VISTAS).forEach(v => {
      // Sin sesión de administración estos paneles no se enviaron al
      // navegador: no es un error, es que esa cuenta no los tiene.
      const panel = document.getElementById(v + "View");
      if (!panel) return;

      const oculto = v !== viewId;
      panel.classList.toggle("hidden", oculto);
      panel.hidden = oculto;
    });

    document.getElementById("viewTitle").innerText = VISTAS[viewId].titulo;
    document.getElementById("viewSubtitle").innerText = VISTAS[viewId].sub;

    if (viewId === "herramientas") { detenerEnLinea(); renderHerramientas(); }
    else if (viewId === "dashboard") { detenerEnLinea(); renderDashboard(); }
    else if (viewId === "bitacora") {
      abrirBitacora();
      // La primera vez ya la deja andando abrirPanel(); al volver a la
      // pestaña sin recargarla, hay que retomarla a mano.
      if (bitacoraCargada && panelBitacoraActivo === "resumen") iniciarEnLinea();
    }
    else { detenerEnLinea(); renderSistema(); }

  }

  // ---------- eventos ----------

  /**
   * Conecta un botón solo si está en la página.
   *
   * Los controles de administración no se envían a las demás cuentas, y un
   * addEventListener sobre null corta la ejecución del script entero: dejaría
   * el portal sin funcionar justo para quien menos margen tiene de arreglarlo.
   */
  function alTocar(id, accion) {
    const el = document.getElementById(id);
    if (el) el.addEventListener("click", accion);
  }

  alTocar("closeModalBtn", closeModal);

  const campoBuscarModulo = document.getElementById("buscarModulo");
  if (campoBuscarModulo) {
    campoBuscarModulo.addEventListener("input", renderHerramientas);
    // Enter no recarga: el filtrado ya es en vivo, y recargar borraría lo
    // escrito y devolvería a la persona al principio de la página.
    campoBuscarModulo.addEventListener("keydown", (e) => {
      if (e.key === "Enter") e.preventDefault();
    });
  }

  // Cerrar el modal tocando fuera
  document.getElementById("globalModal").addEventListener("click", (e) => {
    if (e.target.id === "globalModal") closeModal();
  });

  /**
   * Los enlaces de la barra superior (Inicio, Dashboard, Estado del sistema,
   * Bitácora) cambian de vista sin recargar cuando ya se está en el portal.
   * Recargar acá borraba lo escrito en el buscador y devolvía a la persona al
   * principio de la página.
   */
  const botonesNav = Array.from(document.querySelectorAll(".paa-barra-btn[data-vista]"));

  botonesNav.forEach(btn => {
    btn.addEventListener("click", (e) => {
      const vista = btn.dataset.vista;
      if (!vista) return;
      if (vista !== "herramientas" && !ES_ADMIN) return;

      e.preventDefault();
      setActiveView(vista);

      const url = vista === "herramientas" ? location.pathname : location.pathname + "?vista=" + vista;
      history.pushState({ vista }, "", url);
      marcarBarra(vista);
    });
  });

  function marcarBarra(vista) {
    botonesNav.forEach(b => {
      const activo = b.dataset.vista === vista;
      b.classList.toggle("actual", activo);
      if (activo) b.setAttribute("aria-current", "page");
      else b.removeAttribute("aria-current");
    });
  }

  document.addEventListener("keydown", (e) => {
    // Escape cierra el modal
    if (e.key === "Escape" && modalAbierto()) {
      closeModal();
    }
  });

  // Atrás/Adelante del navegador entre las vistas del portal.
  window.addEventListener("popstate", () => {
    const vista = new URLSearchParams(location.search).get("vista") || "herramientas";
    if (vista === "herramientas" || ES_ADMIN) {
      setActiveView(vista);
      marcarBarra(vista);
    }
  });

  const CSRF = <?= json_encode(token_csrf()) ?>;

  // El selector de convocatoria activa se mudó a la barra superior
  // (includes/barra_superior.php): es un dato de todo el sistema, no solo de
  // esta página, y acá ocupaba media pantalla.

  // ====================== BITÁCORA ======================

  // Página actual de cada tabla. Se guardan acá y no en el DOM para que al
  // filtrar se pueda volver a la 1 sin tener que leerlo de ningún lado.
  const paginaDe = { ingresos: 1, actividad: 1 };
  let bitacoraCargada = false;

  async function pedirBitacora(parametros) {
    const url = BASE + "api/bitacora.php?" + new URLSearchParams(parametros);
    const r = await fetch(url, { headers: { "Accept": "application/json" } });
    const d = await r.json();

    if (!d.success) throw new Error(d.error || "No se pudo consultar la bitácora");
    return d;
  }

  /** Fecha legible. La base devuelve "2026-08-24 14:03:11". */
  function fechaLegible(txt) {
    if (!txt) return "—";
    const [dia, hora] = txt.split(" ");
    const [a, m, d] = dia.split("-");
    return `${d}/${m}/${a} ${(hora || "").slice(0, 5)}`;
  }

  function escapar(t) {
    return String(t ?? "").replace(/[&<>"]/g, c =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
  }

  // ---------- resumen ----------

  /**
   * Quién hizo algo, de verdad.
   *
   * Cuentas compartidas (Personal Extraordinario, hoy) tienen un solo nombre
   * de catálogo para varias personas distintas. Cuando la persona escribió
   * quién es (ver `quien` en IngresoDatos/index.php), el usuario de sesión
   * queda como "Personal Extraordinario (Floribeth)" — eso es más útil que el
   * nombre de catálogo repetido, así que se prefiere cuando aparece.
   */
  function nombreMostrado(nombre, usuario) {
    if (usuario && usuario.includes("(")) return usuario;
    return nombre || usuario || "—";
  }

  /** Iniciales para el avatar redondo, igual que en la tarjeta de perfil. */
  function iniciales(nombre) {
    const partes = String(nombre || "?").trim().split(/\s+/);
    return ((partes[0]?.[0] || "?") + (partes[1]?.[0] || "")).toUpperCase();
  }

  /** Hace cuánto fue una fecha ("hace 2 min", "hace 3 h"), para lo que es reciente de verdad. */
  function haceCuanto(txt) {
    if (!txt) return "—";
    const minutos = Math.max(0, Math.round((Date.now() - new Date(txt.replace(" ", "T"))) / 60000));
    if (minutos < 1) return "justo ahora";
    if (minutos < 60) return `hace ${minutos} min`;
    return `hace ${Math.round(minutos / 60)} h`;
  }

  /** Abre la Actividad de una persona: es el atajo de "quiero ver qué hizo". */
  function verActividadDe(usuario) {
    paginaDe.actividad = 1;
    abrirPanel("actividad", { usuario: usuario || "" });
  }

  async function cargarResumen() {
    const dias = Number(document.getElementById("bitDias").value) || 30;

    try {
      const d = await pedirBitacora({ accion: "resumen", dias });
      const etiquetaPeriodo = dias === 365 ? "último año" : `${dias} días`;

      const t = d.totales;
      document.getElementById("bitMetricas").innerHTML = [
        ["Ingresos hoy",        t.ingresosHoy, "fa-right-to-bracket", "Entradas exitosas del día"],
        ["Personas activas",    t.activos,     "fa-users",            `Distintas, en ${etiquetaPeriodo}`],
        ["Acciones registradas", t.acciones,   "fa-list-check",       `Cambios en ${etiquetaPeriodo}`],
        ["Intentos fallidos",   t.fallidos,    "fa-shield-halved",    `Contraseñas erradas, ${etiquetaPeriodo}`]
      ].map(([titulo, valor, icono, nota]) => `
        <div class="stat-card">
          <div class="stat-header">
            <span>${titulo}</span>
            <i class="fa-solid ${icono}"></i>
          </div>
          <div class="stat-number">${valor}</div>
          <div class="stat-delta">${nota}</div>
        </div>
      `).join("");

      document.getElementById("bitTopUsuarios").innerHTML = d.topUsuarios.length
        ? d.topUsuarios.map(u => `
            <div class="quick-item clicable" data-usuario="${escapar(u.usuario)}" title="Ver la actividad de ${escapar(u.nombre || u.usuario)}">
              <div>
                <strong>${escapar(u.nombre || u.usuario || "—")}</strong><br>
                <small style="color:var(--gray-500)">último: ${fechaLegible(u.ultimo)}</small>
              </div>
              <span class="pill pill-mod">${u.veces} ingreso(s)</span>
            </div>`).join("")
        : '<p style="color:var(--gray-500); font-size:.85rem">Todavía no hay ingresos registrados.</p>';

      document.getElementById("bitTopActivos").innerHTML = d.topActivos.length
        ? d.topActivos.map(u => `
            <div class="quick-item clicable" data-usuario="${escapar(u.usuario)}" title="Ver la actividad de ${escapar(nombreMostrado(u.nombre, u.usuario))}">
              <div>
                <strong>${escapar(nombreMostrado(u.nombre, u.usuario))}</strong><br>
                <small style="color:var(--gray-500)">acciones en ${etiquetaPeriodo}</small>
              </div>
              <span class="pill pill-mod">${u.veces}</span>
            </div>`).join("")
        : '<p style="color:var(--gray-500); font-size:.85rem">Todavía no se ha registrado actividad.</p>';

      const maxModulo = Math.max(1, ...d.topModulos.map(m => Number(m.veces)));
      document.getElementById("bitTopModulos").innerHTML = d.topModulos.length
        ? d.topModulos.map(m => `
            <div class="quick-item clicable" data-modulo="${escapar(m.modulo)}" title="Ver la actividad de ${escapar(m.modulo)}">
              <div class="barra-fondo">
                <div class="barra-relleno" style="width:${(m.veces / maxModulo) * 100}%"></div>
                <strong style="text-transform:capitalize">${escapar(m.modulo)}</strong>
              </div>
              <span class="pill pill-mod">${m.veces}</span>
            </div>`).join("")
        : '<p style="color:var(--gray-500); font-size:.85rem">Todavía no se ha registrado actividad.</p>';

      // Clic en una persona o un módulo: salta directo a Actividad, filtrado.
      document.querySelectorAll("#bitTopUsuarios .clicable, #bitTopActivos .clicable").forEach(el =>
        el.addEventListener("click", () => verActividadDe(el.dataset.usuario)));

      document.querySelectorAll("#bitTopModulos .clicable").forEach(el =>
        el.addEventListener("click", () => {
          paginaDe.actividad = 1;
          abrirPanel("actividad", { modulo: el.dataset.modulo });
        }));

      dibujarHoras(d.porHora);

    } catch (e) {
      showModal("Bitácora", e.message);
    }
  }

  /**
   * Gráfico de actividad por hora.
   *
   * Se dibujan las 24 horas aunque la consulta solo devuelva las que tuvieron
   * movimiento: si no, un día con actividad solo a las 8 y a las 15 mostraría
   * dos barras pegadas y parecería que se trabajó seguido.
   */
  function dibujarHoras(datos) {
    const porHora = new Array(24).fill(0);
    datos.forEach(d => { porHora[Number(d.hora)] = Number(d.veces); });

    const maximoReal = Math.max(0, ...porHora);
    const maximo = Math.max(1, maximoReal);   // piso para no dividir entre 0 al dibujar
    const horaPico = porHora.indexOf(maximoReal);

    document.getElementById("bitHoras").innerHTML = porHora.map((v, h) => `
      <div class="barra ${v === maximoReal && v > 0 ? "pico" : ""}" style="height:${Math.max(2, (v / maximo) * 100)}%"
           title="${String(h).padStart(2, "0")}:00 — ${v} acción(es)">
        ${v > 0 ? `<span class="valor">${v}</span>` : ""}
      </div>
    `).join("");

    document.getElementById("bitHorasEtiquetas").innerHTML =
      porHora.map((_, h) => `<span>${h % 3 === 0 ? h : ""}</span>`).join("");

    document.getElementById("bitHorasPico").textContent = maximoReal > 0
      ? `Pico: ${String(horaPico).padStart(2, "0")}:00 (${maximoReal} acción(es))`
      : "Sin movimiento en el período";
  }

  // ---------- en línea ahora ----------

  let intervaloEnLinea = null;

  async function cargarEnLinea() {
    try {
      const d = await pedirBitacora({ accion: "enLinea" });
      const cont = document.getElementById("bitEnLinea");

      cont.innerHTML = d.personas.length
        ? `<div class="en-linea-lista">${d.personas.map(p => `
            <div class="en-linea-persona" title="${escapar(p.modulo)} · ${haceCuanto(p.ultimo)}">
              <span class="en-linea-avatar">${escapar(iniciales(nombreMostrado(p.nombre, p.usuario)))}</span>
              <span>
                <strong>${escapar(nombreMostrado(p.nombre, p.usuario))}</strong>
                <small style="text-transform:capitalize">${escapar(p.modulo)} · ${haceCuanto(p.ultimo)}</small>
              </span>
            </div>`).join("")}</div>`
        : '<p style="color:var(--gray-500); font-size:.85rem">Nadie con actividad reciente.</p>';

    } catch (e) {
      // Silencioso: es un panel que se refresca solo, no vale la pena
      // interrumpir con un modal por un refresco que falló.
    }
  }

  function iniciarEnLinea() {
    clearInterval(intervaloEnLinea);
    cargarEnLinea();
    intervaloEnLinea = setInterval(cargarEnLinea, 20000);
  }

  function detenerEnLinea() {
    clearInterval(intervaloEnLinea);
    intervaloEnLinea = null;
  }

  // ---------- tablas ----------

  async function cargarIngresos() {
    const cuerpo = document.getElementById("tbIngresos");
    cuerpo.innerHTML = '<tr><td colspan="5" class="tabla-vacia">Cargando…</td></tr>';

    try {
      const d = await pedirBitacora({
        accion:    "ingresos",
        pagina:    paginaDe.ingresos,
        usuario:   document.getElementById("fiUsuario").value.trim(),
        resultado: document.getElementById("fiResultado").value,
        desde:     document.getElementById("fiDesde").value,
        hasta:     document.getElementById("fiHasta").value
      });

      cuerpo.innerHTML = d.filas.length ? d.filas.map(f => `
        <tr>
          <td class="mono">${fechaLegible(f.fecha)}</td>
          <td><strong>${escapar(f.usuario || "—")}</strong></td>
          <td>${escapar(f.nombre || "—")}</td>
          <td><span class="pill ${f.exitoso ? "pill-ok" : "pill-mal"}">
            ${f.exitoso ? "Entró" : "Rechazado"}</span></td>
          <td class="mono">${escapar(f.ip || "—")}</td>
        </tr>`).join("")
        : '<tr><td colspan="5" class="tabla-vacia">No hay ingresos con esos filtros.</td></tr>';

      dibujarPaginacion("pgIngresos", d, "ingresos", cargarIngresos);

    } catch (e) {
      cuerpo.innerHTML = `<tr><td colspan="5" class="tabla-vacia">${escapar(e.message)}</td></tr>`;
    }
  }

  /**
   * @param {{usuario?: string, modulo?: string}} forzar Filtro que llega desde
   * un clic en Resumen ("ver actividad de..."), que puede pedir un módulo que
   * el <select> todavía no tiene cargado la primera vez.
   */
  async function cargarActividad(forzar = {}) {
    const cuerpo = document.getElementById("tbActividad");
    cuerpo.innerHTML = '<tr><td colspan="6" class="tabla-vacia">Cargando…</td></tr>';

    if (forzar.usuario !== undefined) document.getElementById("faUsuario").value = forzar.usuario;

    try {
      const d = await pedirBitacora({
        accion:  "actividad",
        pagina:  paginaDe.actividad,
        usuario: document.getElementById("faUsuario").value.trim(),
        modulo:  forzar.modulo ?? document.getElementById("faModulo").value,
        desde:   document.getElementById("faDesde").value,
        hasta:   document.getElementById("faHasta").value
      });

      cuerpo.innerHTML = d.filas.length ? d.filas.map(f => `
        <tr>
          <td class="mono">${fechaLegible(f.fecha)}</td>
          <td><strong>${escapar(nombreMostrado(f.nombre, f.usuario))}</strong></td>
          <td style="text-transform:capitalize">${escapar(f.modulo)}</td>
          <td>${escapar(f.accion)}</td>
          <td class="mono">${escapar(f.detalle || "—")}</td>
          <td><span class="pill ${f.exitoso ? "pill-ok" : "pill-mal"}">
            ${f.exitoso ? "Hecho" : "Falló"}</span></td>
        </tr>`).join("")
        : '<tr><td colspan="6" class="tabla-vacia">No hay actividad con esos filtros.</td></tr>';

      // El selector de módulos se llena con lo que existe: si mañana se agrega
      // un módulo, aparece solo.
      const sel = document.getElementById("faModulo");
      if (sel.options.length <= 1 && d.modulos.length) {
        sel.innerHTML = '<option value="">Todos</option>' +
          d.modulos.map(m => `<option value="${escapar(m)}">${escapar(m)}</option>`).join("");
      }
      if (forzar.modulo !== undefined) sel.value = forzar.modulo;

      dibujarPaginacion("pgActividad", d, "actividad", cargarActividad);

    } catch (e) {
      cuerpo.innerHTML = `<tr><td colspan="6" class="tabla-vacia">${escapar(e.message)}</td></tr>`;
    }
  }

  function dibujarPaginacion(contenedorId, d, clave, recargar) {
    const cont = document.getElementById(contenedorId);
    const desde = d.total === 0 ? 0 : (d.pagina - 1) * d.porPagina + 1;
    const hasta = Math.min(d.pagina * d.porPagina, d.total);

    cont.innerHTML = `
      <span style="font-size:.8rem; color:var(--gray-500)">
        ${desde}–${hasta} de ${d.total}
      </span>
      <div class="paginas">
        <button class="pag-btn" data-ir="1" ${d.pagina <= 1 ? "disabled" : ""}>«</button>
        <button class="pag-btn" data-ir="${d.pagina - 1}" ${d.pagina <= 1 ? "disabled" : ""}>Anterior</button>
        <span style="font-size:.8rem; font-weight:600; padding:0 8px">
          ${d.pagina} / ${d.totalPaginas}
        </span>
        <button class="pag-btn" data-ir="${d.pagina + 1}" ${d.pagina >= d.totalPaginas ? "disabled" : ""}>Siguiente</button>
        <button class="pag-btn" data-ir="${d.totalPaginas}" ${d.pagina >= d.totalPaginas ? "disabled" : ""}>»</button>
      </div>`;

    cont.querySelectorAll(".pag-btn[data-ir]").forEach(b => {
      b.addEventListener("click", () => {
        paginaDe[clave] = Number(b.dataset.ir);
        recargar();
      });
    });
  }

  // ---------- limpieza ----------

  async function limpiarBitacora() {
    const que  = document.getElementById("limpiarQue").value;
    const dias = document.getElementById("limpiarDias").value;

    const cuerpo = new URLSearchParams({ accion: "limpiar", que, dias, csrf: CSRF });

    if (dias === "0") {
      const escrito = prompt(
        "Esto borra TODA la bitácora y no se puede deshacer.\n\n" +
        "Para confirmar, escribí: BORRAR TODO"
      );
      if (escrito === null) return;
      cuerpo.set("confirmacion", escrito);
    } else {
      const texto = document.getElementById("limpiarDias").selectedOptions[0].text;
      if (!confirm(`Se van a eliminar los registros anteriores a ${texto}.\n\n¿Continuar?`)) return;
    }

    try {
      const r = await fetch(BASE + "api/bitacora.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: cuerpo
      });
      const d = await r.json();

      if (!d.success) throw new Error(d.error);

      showModal("Bitácora limpia", d.mensaje);
      cargarResumen();

    } catch (e) {
      showModal("No se pudo limpiar", e.message);
    }
  }

  // ---------- pestañas internas ----------

  let panelBitacoraActivo = "resumen";

  /** @param {{usuario?: string, modulo?: string}} filtroActividad Solo aplica si nombre === "actividad". */
  function abrirPanel(nombre, filtroActividad = {}) {
    panelBitacoraActivo = nombre;

    ["resumen", "ingresos", "actividad"].forEach(p => {
      const div = document.getElementById("bit" + p.charAt(0).toUpperCase() + p.slice(1));
      const oculto = p !== nombre;
      div.classList.toggle("hidden", oculto);
      div.hidden = oculto;
    });

    document.querySelectorAll(".bitacora-tab").forEach(b =>
      b.classList.toggle("active", b.dataset.panel === nombre));

    if (nombre === "resumen") {
      cargarResumen();
      iniciarEnLinea();
    } else {
      detenerEnLinea();
      if (nombre === "ingresos") cargarIngresos();
      else cargarActividad(filtroActividad);
    }
  }

  function abrirBitacora() {
    // Solo la primera vez: volver a la pestaña no debería descartar los
    // filtros que la persona dejó puestos.
    if (bitacoraCargada) return;
    bitacoraCargada = true;
    abrirPanel("resumen");
  }

  // ---------- eventos de la bitácora ----------
  //
  // Todo este bloque solo aplica a administración: para las demás cuentas el
  // panel ni se envió.

  if (ES_ADMIN) {
    document.querySelectorAll(".bitacora-tab").forEach(b =>
      b.addEventListener("click", () => abrirPanel(b.dataset.panel)));

    document.getElementById("bitDias").addEventListener("change", cargarResumen);

    alTocar("limpiarBtn", limpiarBitacora);

    alTocar("fiAplicar", () => {
      paginaDe.ingresos = 1;   // sin esto, filtrar desde la página 7 mostraría vacío
      cargarIngresos();
    });

    alTocar("faAplicar", () => {
      paginaDe.actividad = 1;
      cargarActividad();
    });

    alTocar("fiLimpiar", () => {
      ["fiUsuario", "fiResultado", "fiDesde", "fiHasta"].forEach(id =>
        document.getElementById(id).value = "");
      paginaDe.ingresos = 1;
      cargarIngresos();
    });

    alTocar("faLimpiar", () => {
      ["faUsuario", "faModulo", "faDesde", "faHasta"].forEach(id =>
        document.getElementById(id).value = "");
      paginaDe.actividad = 1;
      cargarActividad();
    });

    // Enter en los campos de texto filtra, que es lo que se espera al escribir.
    ["fiUsuario", "faUsuario"].forEach(id => {
      document.getElementById(id).addEventListener("keydown", e => {
        if (e.key !== "Enter") return;
        e.preventDefault();
        document.getElementById(id === "fiUsuario" ? "fiAplicar" : "faAplicar").click();
      });
    });
  }

  // ---------- inicialización ----------

  document.addEventListener("DOMContentLoaded", () => {
    // La barra superior enlaza a ?vista=dashboard, ?vista=sistema y
    // ?vista=bitacora: así se llega directo a una sección desde cualquier
    // módulo, sin tener que entrar al portal y después buscar la pestaña.
    const VISTAS = ["herramientas", "dashboard", "sistema", "bitacora"];
    const pedida = new URLSearchParams(location.search).get("vista");
    const inicial = (ES_ADMIN && VISTAS.includes(pedida)) ? pedida : "herramientas";

    setActiveView(inicial);

    // La medición alimenta el Dashboard y el Estado del Sistema. Sin esas
    // pantallas no hay nada que mostrar, y consultar diez endpoints cada cinco
    // minutos para descartarlo sería gasto puro — encima desde cuentas que no
    // tienen permiso sobre varios de ellos.
    if (ES_ADMIN) {
      medirTodo();
      setInterval(medirTodo, 5 * 60 * 1000);
    }
  });
</script>
<?php include __DIR__ . '/includes/barra_superior.php'; ?>
<?php include __DIR__ . '/includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/includes/notificaciones.php'; ?>
</body>
</html>
