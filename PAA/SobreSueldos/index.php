<?php
/**
 * Módulo de uso interno: solo administración.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("sobresueldos", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Gestión PAA · Sobre Sueldos · UCR</title>
  <link rel="icon" type="image/png" href="../assets/favicon-paa.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
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

      /* Alias legacy: nombres de variable originales de este módulo,
         mapeados a la paleta nueva para no reescribir todo el CSS de abajo. */
      --blue:        var(--primary-light);
      --blue-dark:   var(--primary);
      --blue-light:  var(--gray-50);
      --navy:        var(--primary-dark);
      --text:        var(--gray-800);
      --muted:       var(--gray-500);
      --bg:          var(--gray-50);
      --white:       #ffffff;
      --border:      var(--gray-200);
      --green:       var(--success);
      --green-light: #dcfce7;
      --orange:      var(--warning);
      --orange-lt:   #fef3c7;
      --red:         var(--danger);
      --red-light:   #fee2e2;
      --shadow-sm:   0 2px 8px rgba(0,0,0,.07);
      --shadow-md:   0 6px 20px rgba(0,0,0,.12);
      --shadow-lg:   0 12px 32px rgba(0,0,0,.16);
      --radius-old:  14px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #f6f9fc 0%, #f1f5f9 100%);
      color: var(--gray-800);
      line-height: 1.5;
      min-height: 100vh;
      padding: 1.5rem;
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

    /* ── BADGE TOTAL (bajo el hero) ── */
    .topbar-badge-wrap { display: flex; justify-content: center; margin-bottom: 1.25rem; }
    .topbar-badge {
      background: rgba(255,255,255,.9);
      backdrop-filter: blur(8px);
      border: 1px solid var(--gray-200);
      border-radius: 30px;
      padding: 8px 20px;
      font-size: 13px;
      font-weight: 600;
      white-space: nowrap;
      color: var(--gray-700);
      box-shadow: var(--shadow);
    }

    /* ── CONTAINER ── */
    .app { max-width: 1000px; margin: 0 auto; padding: 28px 20px 100px; }

    /* ── TOOLBAR ── */
    .toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 22px;
      align-items: center;
    }
    .search-wrap {
      flex: 1;
      min-width: 220px;
      position: relative;
    }
    .search-wrap input {
      width: 100%;
      padding: 11px 14px 11px 38px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-size: 14px;
      background: var(--white);
      transition: border-color .2s, box-shadow .2s;
    }
    .search-wrap input:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(65,173,231,.18);
    }
    .search-wrap::before {
      content: '🔍';
      position: absolute;
      left: 11px; top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      pointer-events: none;
    }

    /* ── BOTONES ── */
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 9px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all .2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--blue), var(--blue-dark));
      color: #fff;
      box-shadow: 0 4px 12px rgba(65,173,231,.3);
    }
    .btn-primary:hover  { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(65,173,231,.4); }
    .btn-success {
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #fff;
      box-shadow: 0 4px 12px rgba(34,197,94,.3);
    }
    .btn-success:hover { transform: translateY(-1px); }
    .btn-ghost {
      background: var(--white);
      color: var(--blue-dark);
      border: 1.5px solid var(--border);
    }
    .btn-ghost:hover { background: var(--blue-light); border-color: var(--blue); }
    .btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; }

    /* ── SPINNER / ESTADOS ── */
    .spinner {
      width: 38px; height: 38px;
      border: 4px solid rgba(65,173,231,.25);
      border-top-color: var(--blue);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 14px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .estado-carga {
      text-align: center;
      padding: 60px 20px;
      background: var(--white);
      border-radius: var(--radius-old);
      box-shadow: var(--shadow-sm);
      color: var(--muted);
    }
    .error-box {
      background: var(--red-light);
      border-left: 5px solid var(--red);
      border-radius: 0 10px 10px 0;
      padding: 18px 20px;
      color: #7f1d1d;
      margin-bottom: 20px;
    }

    /* ── TOTALES ── */
    .totales-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 10px;
      margin-bottom: 22px;
    }
    .total-card {
      background: var(--white);
      border-radius: 10px;
      padding: 14px;
      text-align: center;
      box-shadow: var(--shadow-sm);
      border-top: 3px solid var(--blue);
    }
    .total-card .num { font-size: 26px; font-weight: 800; color: var(--navy); }
    .total-card .lbl { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 2px; text-transform: uppercase; }

    /* ── ACORDEÓN ── */
    .acordeon {
      background: #fafcff;
      border-radius: var(--radius-old);
      border: 1px solid #d4ddf0;
      box-shadow: 0 3px 14px rgba(0,0,0,.10);
      margin-bottom: 14px;
      transition: box-shadow .25s;
      overflow: hidden;
    }
    .acordeon:hover { box-shadow: 0 6px 22px rgba(0,0,0,.15); }

    .acordeon-head {
      padding: 18px 22px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 14px;
      user-select: none;
      border-bottom: 1.5px solid transparent;
      transition: background .2s, border-color .2s;
    }
    .acordeon.abierto .acordeon-head {
      border-bottom-color: var(--border);
      background: #f8fbff;
    }
    .acordeon-head:hover { background: #f8fbff; }

    .sede-dot {
      width: 13px; height: 13px;
      border-radius: 50%;
      background: var(--blue);
      flex-shrink: 0;
      transition: background .3s;
    }
    .sede-dot--asignada {
      background: var(--green);
      box-shadow: 0 0 0 3px rgba(34,197,94,.2);
    }
    .sede-info { flex: 1; min-width: 0; }
    .sede-nombre {
      font-size: 16px; font-weight: 700;
      color: var(--navy);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sede-desc {
      font-size: 12px; color: var(--muted);
      margin-top: 2px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sede-badges { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; flex-shrink: 0; }
    .badge {
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px; font-weight: 700;
      white-space: nowrap;
    }
    .badge-blue  { background: var(--blue-light);  color: var(--blue-dark); }
    .badge-green { background: var(--green-light); color: #166534; }
    .badge-teal  { background: #ccfbf1; color: #0f766e; }

    /* ── PAGINACIÓN ── */
    .paginacion {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 24px;
      padding-bottom: 8px;
    }
    .pag-btn {
      padding: 7px 13px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      background: var(--white);
      color: var(--blue-dark);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all .2s;
    }
    .pag-btn:hover:not([disabled]) { background: var(--blue-light); border-color: var(--blue); }
    .pag-btn.activo { background: var(--blue); color: #fff; border-color: var(--blue); }
    .pag-btn[disabled] { opacity: .4; cursor: not-allowed; }
    .pag-info { font-size: 12px; color: var(--muted); padding: 0 4px; }

    .toggle-btn {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--blue);
      color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; font-weight: 700;
      flex-shrink: 0;
      transition: transform .3s, background .2s;
      box-shadow: 0 2px 8px rgba(65,173,231,.35);
    }
    .acordeon.abierto .toggle-btn {
      transform: rotate(45deg);
      background: var(--blue-dark);
    }

    /* ── CUERPO ── */
    .acordeon-body {
      max-height: 0;
      overflow: hidden;
      transition: max-height .45s ease;
    }
    .acordeon.abierto .acordeon-body { max-height: 4200px; }
    .acordeon-inner { padding: 22px; }

    /* ── SECCIONES ── */
    .section { margin-bottom: 20px; }
    .section-title {
      font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .8px;
      color: var(--muted);
      margin-bottom: 12px; padding-bottom: 6px;
      border-bottom: 1.5px solid var(--border);
    }

    /* ── GRID CAMPOS ── */
    .fields-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 12px;
    }
    .field-group { display: flex; flex-direction: column; gap: 4px; }
    .field-group label {
      font-size: 11px; font-weight: 700;
      color: var(--muted);
      text-transform: uppercase; letter-spacing: .5px;
    }
    .field-group input, .field-group select {
      padding: 8px 11px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      background: #fafbff;
      color: var(--text);
      transition: border-color .2s, box-shadow .2s;
      width: 100%;
    }
    .field-group input:focus, .field-group select:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(65,173,231,.15);
      background: var(--white);
    }
    .field-group .hint { font-size: 10px; color: #94a3b8; margin-top: 1px; }

    /* ── SELECTOR COORDINADOR ── */
    .coord-wrap { position: relative; }
    .coord-input {
      width: 100%;
      padding: 8px 11px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      background: #fafbff;
      color: var(--text);
      transition: border-color .2s, box-shadow .2s;
    }
    .coord-input:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(65,173,231,.15);
      background: var(--white);
    }
    .coord-list {
      position: absolute;
      top: calc(100% + 3px);
      left: 0; right: 0;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      box-shadow: var(--shadow-md);
      max-height: 210px;
      overflow-y: auto;
      z-index: 60;
      display: none;
    }
    .coord-item {
      padding: 9px 13px;
      cursor: pointer;
      font-size: 13px;
      border-bottom: 1px solid #f0f4fb;
      transition: background .12s;
      line-height: 1.4;
    }
    .coord-item:last-child { border-bottom: none; }
    .coord-item:hover { background: var(--blue-light); }
    .coord-email { display: block; font-size: 11px; color: var(--muted); margin-top: 1px; }
    .coord-vacio { padding: 10px 13px; font-size: 12px; color: var(--muted); font-style: italic; }

    /* ── VIAJE ── */
    .viaje-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    @media (max-width: 500px) { .viaje-grid { grid-template-columns: 1fr; } }

    .viaje-card {
      background: var(--blue-light);
      border-radius: 10px;
      padding: 14px 16px;
      border: 1.5px solid #c7d9f5;
    }
    .viaje-card h4 {
      font-size: 12px; font-weight: 700;
      color: var(--blue-dark);
      text-transform: uppercase; letter-spacing: .6px;
      margin-bottom: 10px;
    }
    .viaje-row { display: flex; gap: 8px; }
    .viaje-row select, .viaje-row input[type="time"] {
      flex: 1;
      padding: 8px 10px;
      border: 1.5px solid #93c5fd;
      border-radius: 8px;
      font-size: 13px;
      background: var(--white);
      color: var(--text);
    }
    .viaje-row select:focus, .viaje-row input[type="time"]:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(65,173,231,.15);
    }

    /* ── DÍAS ── */
    .dias-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
    }
    @media (max-width: 460px) { .dias-grid { grid-template-columns: 1fr; } }

    .dia-card {
      border-radius: 10px;
      padding: 14px 12px;
      text-align: center;
      border: 2px solid var(--border);
      background: #fafbff;
      transition: border-color .2s, background .2s;
    }
    .dia-card.activo {
      border-color: var(--blue);
      background: var(--blue-light);
    }
    .dia-card h4 {
      font-size: 11px; font-weight: 700;
      color: var(--muted);
      margin-bottom: 6px;
      text-transform: uppercase; letter-spacing: .4px;
    }
    .dia-card.activo h4 { color: var(--blue-dark); }
    .dia-codigo {
      font-size: 17px; font-weight: 800;
      color: var(--navy);
      letter-spacing: 1.5px;
      min-height: 26px;
    }
    .dia-detalle {
      font-size: 10px; color: var(--muted);
      margin-top: 6px; line-height: 1.8;
    }
    .dia-detalle span {
      display: inline-block;
      border-radius: 4px;
      padding: 1px 6px;
      margin: 1px;
    }
    .dia-detalle span.on  { background: #bfdbfe; color: #1e40af; font-weight:600; }
    .dia-detalle span.off { background: #f1f5f9; color: #94a3b8; text-decoration: line-through; }

    /* ── FOOTER ACORDEÓN ── */
    .acordeon-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 1.5px solid var(--border);
    }
    .save-status { font-size: 13px; font-weight: 600; min-height: 20px; }
    .save-status.ok     { color: #16a34a; }
    .save-status.error  { color: var(--red); }
    .save-status.saving { color: var(--blue); }

    /* ── PRESUPUESTO ── */
    .presup-box {
      background: #f0fdf4;
      border: 1.5px solid #86efac;
      border-radius: 10px;
      padding: 14px 16px;
      margin-top: 12px;
    }
    .presup-box-title {
      font-size: 11px; font-weight: 700;
      color: #166534; text-transform: uppercase; letter-spacing: .6px;
      margin-bottom: 10px; padding-bottom: 6px;
      border-bottom: 1.5px solid #d1fae5;
    }
    .presup-row {
      display: flex; justify-content: space-between; align-items: baseline;
      font-size: 13px; padding: 5px 0;
      border-bottom: 1px solid #d1fae5;
      gap: 12px;
    }
    .presup-row:last-child { border-bottom: none; }
    .presup-row .plbl { color: var(--muted); flex: 1; }
    .presup-row .pval { font-weight: 700; color: var(--navy); white-space: nowrap; }
    .presup-total {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 15px; font-weight: 800; color: #166534;
      margin-top: 10px; padding-top: 10px;
      border-top: 2.5px solid #86efac;
    }
    .presup-nota {
      font-size: 12px; color: var(--muted); font-style: italic; margin-top: 4px;
    }

    /* ── FILTROS ── */
    .filtros-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 18px;
      align-items: center;
    }
    .filtros-bar label {
      font-size: 11px; font-weight: 700;
      color: var(--muted);
      text-transform: uppercase; letter-spacing: .4px;
      white-space: nowrap;
    }
    .filtros-bar select {
      padding: 7px 11px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      font-size: 13px;
      background: var(--white);
      color: var(--text);
      cursor: pointer;
      transition: border-color .2s;
    }
    .filtros-bar select:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(65,173,231,.15);
    }
    .filtro-sep {
      width: 1px; height: 22px;
      background: var(--border);
      margin: 0 4px;
    }
    @media (max-width: 600px) { .filtro-sep { display: none; } }

    /* ── STICKY BAR ── */
    .sticky-bar {
      position: fixed;
      bottom: 0; left: 0; right: 0;
      background: rgba(255,255,255,.96);
      backdrop-filter: blur(8px);
      border-top: 1.5px solid var(--border);
      padding: 12px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      z-index: 100;
      box-shadow: 0 -4px 16px rgba(0,0,0,.08);
    }
    .sticky-info { font-size: 13px; color: var(--muted); }
    .sticky-info strong { color: var(--text); }

    @media (max-width: 600px) {
      .app { padding: 16px 12px 100px; }
      .fields-grid { grid-template-columns: 1fr 1fr; }
      .sticky-bar { padding: 10px 14px; flex-direction: column; gap: 8px; }
    }
  </style>
  <script src="https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
  <h1>Sobre Sueldos</h1>
  <p>Gestión de personal, comidas y hospedaje por sede</p>
</div>

<div class="topbar-badge-wrap">
  <div class="topbar-badge" id="badge-total">Cargando…</div>
</div>


  <input type="hidden" id="aulas" value="">

  <!-- TOOLBAR -->
  <div class="toolbar">
    <div class="search-wrap">
      <input type="text" id="busqueda"
        placeholder="Buscar sede por nombre o descripción…"
        oninput="filtrar(this.value)">
    </div>
    <button class="btn btn-primary" onclick="recargar()">🔄 Recargar</button>
    <button class="btn btn-success" id="btn-guardar-todas" onclick="guardarTodas()" disabled>
      💾 Guardar Todo
    </button>
    <button class="btn btn-primary" onclick="descargarExcel()">
      📊 Descargar Excel
    </button>
  </div>

  <!-- FILTROS -->
  <div class="filtros-bar">
    <label>Ordenar:</label>
    <select id="filtro-orden" onchange="aplicarFiltros()">
      <option value="az">Nombre A → Z</option>
      <option value="za">Nombre Z → A</option>
      <option value="aulas-asc">Menos aulas primero</option>
      <option value="aulas-desc">Más aulas primero</option>
    </select>
    <div class="filtro-sep"></div>
    <label>Estado:</label>
    <select id="filtro-estado" onchange="aplicarFiltros()">
      <option value="todas">Todas las sedes</option>
      <option value="con-datos">Con datos guardados</option>
      <option value="sin-datos">Sin datos</option>
    </select>
    <div class="filtro-sep"></div>
    <label>Aulas:</label>
    <select id="filtro-aulas" onchange="aplicarFiltros()">
      <option value="todas">Cualquier cantidad</option>
      <option value="1-5">1 – 5 aulas</option>
      <option value="6-10">6 – 10 aulas</option>
      <option value="11-20">11 – 20 aulas</option>
      <option value="21+">21 o más aulas</option>
    </select>
    <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
      <span id="resultado-count" style="font-size:12px;color:var(--muted);font-weight:600;white-space:nowrap;"></span>
      <button class="btn btn-ghost" id="btn-limpiar" onclick="limpiarFiltros()" style="padding:6px 12px;font-size:12px;display:none;">
        ✕ Limpiar
      </button>
    </div>
  </div>

  <!-- RESUMEN TOTALES -->
  <div class="totales-grid" id="totales-grid" style="display:none;"></div>

  <!-- CONTENIDO PRINCIPAL -->
  <div id="contenido">
    <div class="estado-carga">
      <div class="spinner"></div>
      <div>Cargando sedes desde Google Sheets…</div>
    </div>
  </div>

  <!-- PAGINACIÓN -->
  <div id="paginacion" class="paginacion" style="display:none;"></div>

</div>

<!-- STICKY BOTTOM BAR -->
<div class="sticky-bar">
  <div class="sticky-info">
    <strong id="sticky-count">—</strong> sedes &nbsp;·&nbsp;
    <span id="sticky-status">Sin cambios pendientes</span>
  </div>
  <button class="btn btn-success" id="sticky-save" onclick="guardarTodas()" disabled>
    💾 Guardar Todo en Sheet
  </button>
</div>

<script>
// ════════════════════════════════════════════════════════════
//  CONFIGURACIÓN
// ════════════════════════════════════════════════════════════
// MIGRADO A LA BASE DE DATOS INSTITUCIONAL.
//
// Esta página cruzaba TRES Google Sheets distintos en el navegador: el del
// presupuesto por sede, uno de coordinadores y uno de tarifas de hospedaje.
// Los tres se leían por opensheet.elk.sh, con opensheet.vercel.app de
// respaldo. Ahora los tres salen del mismo endpoint.
//
// El de coordinadores desaparece como fuente aparte: era otra copia de la
// misma gente que ya está en el Directorio de Funcionarios.
const API_URL = '../api/sobresueldos.php';
const CSRF = <?= json_encode(token_csrf()) ?>;

const PROXY_URLS     = [`${API_URL}?accion=getSedes`];
const COORD_PROXY    = [`${API_URL}?accion=coordinadores`];
const HOSPEDAJE_PROXY = [`${API_URL}?accion=tarifasHospedaje`];

const PAGE_SIZE = 50;

// ════════════════════════════════════════════════════════════
//  ESTADO GLOBAL
// ════════════════════════════════════════════════════════════
let sedesData         = [];
let sedesFiltradas    = [];
let coordinadoresData = [];
let cambiosPend       = new Set();
let proxyIdx          = 0;
let currentPage       = 0;
let hospedajeData     = [];

// ════════════════════════════════════════════════════════════
//  URL DEL APPS SCRIPT
// ════════════════════════════════════════════════════════════
//
// Antes esta URL se leía de localStorage y se podía sobrescribir. Como es una
// máquina compartida, un valor guardado una vez quedaba activo para todos los
// que usaran esa computadora después, y ahí van datos salariales. Ahora es una
// constante: para cambiar el endpoint hay que editar el archivo.
const APPS_SCRIPT_URL = API_URL;

// ════════════════════════════════════════════════════════════
//  ALGORITMO: MINUTOS DESDE MEDIANOCHE
// ════════════════════════════════════════════════════════════
function toMin(t) {
  if (!t) return null;
  const [h, m] = t.split(':').map(Number);
  return h * 60 + m;
}

// ════════════════════════════════════════════════════════════
//  ALGORITMO: CALCULA COMIDAS Y HOSPEDAJE POR DÍA
//
//  REGLAS:
//  Día de salida
//    · Desayuno  → solo si hora salida < 07:00
//    · Almuerzo  → siempre
//    · Cena      → siempre
//    · Hospedaje → siempre (se queda esa noche)
//
//  Días intermedios → D + A + C + H siempre
//
//  Día de llegada (regreso)
//    · Desayuno  → siempre (se levantó allá)
//    · Almuerzo  → si hora llegada >= 11:00
//    · Cena      → si hora llegada >= 20:00
//    · Hospedaje → nunca (ya volvió a casa)
//
//  Mismo día (salida = llegada)
//    · Desayuno  → si hora salida < 07:00
//    · Almuerzo  → si hora llegada >= 11:00
//    · Cena      → si hora llegada >= 20:00
//    · Hospedaje → nunca
// ════════════════════════════════════════════════════════════
function calcDia(pos, mSal, mLleg) {
  let d = false, a = false, c = false, h = false;

  if (pos === 'medio') {
    d = true; a = true; c = true; h = true;

  } else if (pos === 'salida') {
    d = mSal < 7 * 60;       // antes de las 7:00
    a = true;
    c = true;
    h = true;

  } else if (pos === 'llegada') {
    d = true;
    a = mLleg >= 11 * 60;    // llega después de las 11:00
    c = mLleg >= 20 * 60;    // llega después de las 20:00
    h = false;

  } else if (pos === 'unico') {
    d = mSal  < 7  * 60;
    a = mLleg >= 11 * 60;
    c = mLleg >= 20 * 60;
    h = false;
  }

  const partes = [];
  if (d) partes.push('D');
  if (a) partes.push('A');
  if (c) partes.push('C');
  if (h) partes.push('H');

  return { codigo: partes.join('-') || '—', items: { d, a, c, h } };
}

const DIA_MAP = { viernes: 1, sabado: 2, domingo: 3 };

function calcularDias(diaSal, horaSal, diaLleg, horaLleg) {
  const empty = { codigo: '', items: {} };
  const res   = { dia1: {...empty}, dia2: {...empty}, dia3: {...empty} };

  const nSal  = DIA_MAP[diaSal];
  const nLleg = DIA_MAP[diaLleg];
  if (!nSal || !nLleg || nLleg < nSal) return res;

  const mSal  = toMin(horaSal);
  const mLleg = toMin(horaLleg);
  if (mSal === null || mLleg === null) return res;

  for (let n = nSal; n <= nLleg; n++) {
    let pos;
    if (nSal === nLleg)   pos = 'unico';
    else if (n === nSal)  pos = 'salida';
    else if (n === nLleg) pos = 'llegada';
    else                  pos = 'medio';

    res['dia' + n] = calcDia(pos, mSal, mLleg);
  }
  return res;
}

// ════════════════════════════════════════════════════════════
//  CÁLCULO AUTOMÁTICO DE PERSONAL (sin conserje)
//  · Coordinador : 1 siempre
//  · Aplicador   : 1 por aula
//  · Apoyo       : 2 si aulas > 5, 1 si ≤ 5
// ════════════════════════════════════════════════════════════
function calcPersonal(aulas) {
  const n = parseInt(aulas) || 0;
  return {
    coordinador: 1,
    aplicador:   n,
    apoyo:       n > 5 ? 2 : 1,
    conserje:    Math.max(1, Math.ceil(n / 5))  // Para información, pero no se cuenta en sobresueldos
  };
}

// ════════════════════════════════════════════════════════════
//  CÁLCULO DE COSTO DE CONSERJERÍA (servicio aparte)
//  cantidad_conserjes × costo_por_unidad
// ════════════════════════════════════════════════════════════
function calcCostoConserjeria(aulas, cantidadConserjes) {
  const n = parseInt(aulas) || 0;
  const cantidad = parseInt(cantidadConserjes) || 0;
  const costoPorUnidad = n < 5 ? 20000 : 27000;
  return cantidad * costoPorUnidad;
}

// ════════════════════════════════════════════════════════════
//  CARGA DESDE GOOGLE SHEETS (proxy público, solo lectura)
// ════════════════════════════════════════════════════════════
async function cargarSedes() {
  const cont = document.getElementById('contenido');
  cont.innerHTML = `<div class="estado-carga">
    <div class="spinner"></div>
    <div>Conectando (proxy ${proxyIdx + 1}/${PROXY_URLS.length})…</div>
  </div>`;

  try {
    const res  = await fetch(PROXY_URLS[proxyIdx], { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);

    // El endpoint responde {success, sedes:[…]}; opensheet devolvía el
    // arreglo pelado. Se aceptan las dos formas.
    const json = await res.json();
    if (json && json.success === false) throw new Error(json.error || 'Sin datos');
    const raw  = json.sedes || json;

    if (!Array.isArray(raw) || !raw.length) throw new Error('Sin datos');

    sedesData = raw.map((r, i) => {
      // Normaliza nombres de columna (case-insensitive)
      const get = (...keys) => {
        for (const k of keys) {
          const v = (r[k] || r[k.toLowerCase()] || r[k.toUpperCase()] || '').toString().trim();
          if (v) return v;
        }
        return '';
      };

      const sede  = get('SEDE_EXAMEN');
      if (!sede) return null;

      const aulas = parseInt(get('AULAS')) || 0;
      const p     = calcPersonal(aulas);

      // Usa valor del sheet si existe, si no usa el calculado
      const sv = (key, fallback) => {
        const v = get(key);
        return v !== '' ? v : String(fallback);
      };

      const rawCoord    = get('COORD_REGULAR');
      const coordinador = /^\d+$/.test(rawCoord) ? '' : rawCoord;
      const tieneDatos  = coordinador !== '';

      return {
        rowIndex:        i + 2,
        sede,
        descripcion:     get('DESCRIPCION'),
        aulas,
        coordinador,
        aplicador:       sv('APLICADOR', p.aplicador),
        apoyo:           sv('APOYO',     p.apoyo),
        una:             get('UNA'),
        ucr:             get('UCR'),
        conserje:        sv('CONSERJE',  p.conserje),
        dia1:            get('DIA 1'),
        dia2:            get('DIA 2'),
        dia3:            get('DIA 3'),
        tieneDatos,
        presupuesto:     parseInt(get('PRESUPUESTO')) || 0,
        desglose:        get('DESGLOSE'),
        guardado:        false
      };
    }).filter(Boolean);

    if (!sedesData.length) throw new Error('No se pudo procesar ninguna sede');

    sedesFiltradas = [...sedesData];
    cambiosPend.clear();

    renderTotales();
    renderSedes();
    actualizarSticky();

  } catch (err) {
    console.error('Error proxy', proxyIdx, err);
    proxyIdx++;
    if (proxyIdx < PROXY_URLS.length) {
      setTimeout(cargarSedes, 900);
    } else {
      cont.innerHTML = `<div class="error-box">
        <strong>❌ No se pudo conectar con Google Sheets</strong><br>
        ${err.message}<br><br>
        <button class="btn btn-primary" onclick="recargar()">🔄 Reintentar</button>
      </div>`;
    }
  }
}

function recargar() {
  proxyIdx = 0;
  cargarSedes();
}

// ════════════════════════════════════════════════════════════
//  CARGA COORDINADORES DESDE SHEET EXTERNO
// ════════════════════════════════════════════════════════════
async function cargarCoordinadores() {
  for (const url of COORD_PROXY) {
    try {
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) continue;
      const json = await res.json();
      const raw = json.coordinadores || json;
      if (!Array.isArray(raw) || !raw.length) continue;

      coordinadoresData = raw.map(r => {
        const g = k => (r[k] || r[k.toLowerCase()] || '').toString().trim();
        const nombre    = g('Nombre');
        const apellido1 = g('Apellido1');
        const apellido2 = g('Apellido2');
        const email     = g('EmailUCR');
        const id        = g('Número de ID') || g('Numero de ID');
        if (!nombre && !apellido1) return null;
        return { id, nombre, apellido1, apellido2, email,
                 nombreCompleto: [nombre, apellido1, apellido2].filter(Boolean).join(' ') };
      }).filter(Boolean);

      coordinadoresData.sort((a, b) => a.nombreCompleto.localeCompare(b.nombreCompleto, 'es'));
      if (sedesData.length) renderTotales();
      return;
    } catch (_) { /* intenta siguiente proxy */ }
  }
}

// ════════════════════════════════════════════════════════════
//  CARGA HOSPEDAJE DESDE SHEET
// ════════════════════════════════════════════════════════════
async function cargarHospedaje() {
  for (const url of HOSPEDAJE_PROXY) {
    try {
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) continue;
      const json = await res.json();
      const raw = json.tarifas || json;
      if (!Array.isArray(raw) || !raw.length) continue;

      hospedajeData = raw.map(r => {
        const g = k => (r[k] || r[k.toLowerCase()] || '').toString().trim();
        return {
          provincia: g('Provincia'),
          canton:    g('Canton') || g('Cantón'),
          distrito:  g('Distrito'),
          tarifa:    parseInt((g('Tarifa') || '0').replace(/\./g, '').replace(/,/g, '')) || 0
        };
      }).filter(r => r.provincia && r.canton && r.distrito && r.tarifa);

      document.querySelectorAll('select[id$="-prov"]').forEach(sel => {
        initPresupuestoCard(sel.id.slice(0, -5));
      });
      return;
    } catch (_) { /* try next proxy */ }
  }
}

// ════════════════════════════════════════════════════════════
//  COMPONENTE: SELECTOR DE COORDINADOR
// ════════════════════════════════════════════════════════════
function buildCoordSelector(fieldId, valorActual, rowIndex) {
  return `
  <div class="coord-wrap" id="${fieldId}-wrap" data-row="${rowIndex}">
    <input type="text" class="coord-input" id="${fieldId}-text"
      placeholder="Buscar coordinador…"
      value="${esc(valorActual)}"
      autocomplete="off"
      oninput="filtrarCoord('${fieldId}')"
      onfocus="filtrarCoord('${fieldId}')"
      onblur="ocultarCoordListDelay('${fieldId}')">
    <input type="hidden" id="${fieldId}" value="${esc(valorActual)}">
    <div class="coord-list" id="${fieldId}-list"></div>
  </div>`;
}

function filtrarCoord(fieldId) {
  const input   = document.getElementById(fieldId + '-text');
  const list    = document.getElementById(fieldId + '-list');
  const wrap    = document.getElementById(fieldId + '-wrap');
  const rowIndex = wrap ? wrap.dataset.row : '';
  if (!input || !list) return;

  const q = input.value.toLowerCase().trim();
  const matches = coordinadoresData.length === 0
    ? []
    : (q === ''
        ? coordinadoresData.slice(0, 60)
        : coordinadoresData.filter(c =>
            c.nombreCompleto.toLowerCase().includes(q) ||
            c.email.toLowerCase().includes(q)
          ).slice(0, 60));

  if (!matches.length) {
    list.innerHTML = `<div class="coord-vacio">${coordinadoresData.length ? 'Sin resultados' : 'Cargando coordinadores…'}</div>`;
  } else {
    list.innerHTML = matches.map(c => `
      <div class="coord-item"
        onmousedown="seleccionarCoord('${fieldId}', '${esc(c.nombreCompleto)}', '${rowIndex}')">
        <strong>${esc(c.nombre)} ${esc(c.apellido1)}</strong> ${esc(c.apellido2)}
        <span class="coord-email">${esc(c.email)}</span>
      </div>`).join('');
  }
  list.style.display = 'block';
}

function seleccionarCoord(fieldId, nombreCompleto, rowIndex) {
  const txt = document.getElementById(fieldId + '-text');
  const hid = document.getElementById(fieldId);
  const lst = document.getElementById(fieldId + '-list');
  if (txt) txt.value = nombreCompleto;
  if (hid) hid.value = nombreCompleto;
  if (lst) lst.style.display = 'none';
  if (rowIndex) marcarCambio(parseInt(rowIndex));
}

function ocultarCoordListDelay(fieldId) {
  setTimeout(() => {
    const lst = document.getElementById(fieldId + '-list');
    if (lst) lst.style.display = 'none';
  }, 200);
}

// ════════════════════════════════════════════════════════════
//  RENDER — TARJETAS RESUMEN
// ════════════════════════════════════════════════════════════
function renderTotales() {
  const grid = document.getElementById('totales-grid');
  const T = sedesData.reduce((acc, s) => {
    const p = calcPersonal(s.aulas);
    acc.aulas += s.aulas;
    acc.aplic += parseInt(s.aplicador) || p.aplicador;
    acc.apoyo += parseInt(s.apoyo)     || p.apoyo;
    acc.cons  += parseInt(s.conserje)  || p.conserje;
    return acc;
  }, { aulas:0, aplic:0, apoyo:0, cons:0 });

  grid.style.display = 'grid';
  grid.innerHTML = [
    { n: sedesData.length,       l: 'Sedes'         },
    { n: T.aulas,                l: 'Total aulas'   },
    { n: coordinadoresData.length, l: 'Coordinadores' },
    { n: T.aplic,           l: 'Aplicadores'   },
    { n: T.apoyo,           l: 'Apoyo'         },
    { n: T.cons,            l: 'Conserjes'     }
  ].map(x => `
    <div class="total-card">
      <div class="num">${x.n}</div>
      <div class="lbl">${x.l}</div>
    </div>`).join('');
}

// ════════════════════════════════════════════════════════════
//  RENDER — LISTA DE SEDES (paginada)
// ════════════════════════════════════════════════════════════
function renderSedes() {
  const cont = document.getElementById('contenido');
  document.getElementById('badge-total').textContent = `📍 ${sedesData.length} Sedes`;
  document.getElementById('sticky-count').textContent = sedesFiltradas.length;

  const rc  = document.getElementById('resultado-count');
  const btn = document.getElementById('btn-limpiar');
  const hayFiltro = sedesFiltradas.length !== sedesData.length;
  if (rc)  rc.textContent  = hayFiltro
    ? `${sedesFiltradas.length} de ${sedesData.length} sedes`
    : `${sedesData.length} sedes en total`;
  if (btn) btn.style.display = hayFiltro ? '' : 'none';

  if (!sedesFiltradas.length) {
    cont.innerHTML = `<div class="estado-carga">🔍 Sin resultados para tu búsqueda.</div>`;
    document.getElementById('paginacion').style.display = 'none';
    return;
  }

  const totalPags = Math.ceil(sedesFiltradas.length / PAGE_SIZE);
  currentPage = Math.min(currentPage, totalPags - 1);
  const inicio = currentPage * PAGE_SIZE;
  const pagina = sedesFiltradas.slice(inicio, inicio + PAGE_SIZE);

  cont.innerHTML = pagina.map(s => buildCard(s)).join('');
  pagina.forEach(s => initPresupuestoCard('ac-' + s.rowIndex));
  document.getElementById('btn-guardar-todas').disabled = false;
  document.getElementById('sticky-save').disabled = false;
  renderPaginacion(totalPags);
}

function renderPaginacion(totalPags) {
  const pag = document.getElementById('paginacion');
  if (totalPags <= 1) { pag.style.display = 'none'; return; }
  pag.style.display = 'flex';

  // Páginas visibles: primera, última, y las 2 adyacentes a la actual
  const visible = new Set([0, totalPags - 1]);
  for (let i = Math.max(0, currentPage - 2); i <= Math.min(totalPags - 1, currentPage + 2); i++) visible.add(i);
  const sorted = [...visible].sort((a, b) => a - b);

  let btns = '';
  let prev = -1;
  for (const p of sorted) {
    if (prev !== -1 && p - prev > 1) btns += `<span class="pag-info">…</span>`;
    btns += `<button class="pag-btn${p === currentPage ? ' activo' : ''}" onclick="cambiarPagina(${p})">${p + 1}</button>`;
    prev = p;
  }

  pag.innerHTML = `
    <button class="pag-btn" ${currentPage === 0 ? 'disabled' : ''} onclick="cambiarPagina(${currentPage - 1})">← Ant.</button>
    ${btns}
    <button class="pag-btn" ${currentPage === totalPags - 1 ? 'disabled' : ''} onclick="cambiarPagina(${currentPage + 1})">Sig. →</button>
    <span class="pag-info">&nbsp;${sedesFiltradas.length} sedes · Pág. ${currentPage + 1}/${totalPags}</span>
  `;
}

function initPresupuestoCard(id) {
  const sel = document.getElementById(id + '-prov');
  if (!sel || !hospedajeData.length || sel.options.length > 1) return;
  const provincias = [...new Set(hospedajeData.map(r => r.provincia))].sort((a, b) => a.localeCompare(b, 'es'));
  provincias.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p; opt.textContent = p;
    sel.appendChild(opt);
  });
}

function cambiarPagina(n) {
  currentPage = n;
  renderSedes();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ════════════════════════════════════════════════════════════
//  CONSTRUIR HTML DE UNA SEDE
// ════════════════════════════════════════════════════════════
function buildCard(s) {
  const id = 'ac-' + s.rowIndex;
  const p  = calcPersonal(s.aulas);
  const apoyoHint = s.aulas > 5
    ? '2 apoyo (>5 aulas)'
    : '1 apoyo (≤5 aulas)';

  return `
<div class="acordeon" id="${id}">

  <!-- CABECERA -->
  <div class="acordeon-head" onclick="toggleAc('${id}')">
    <div class="sede-dot${s.tieneDatos ? ' sede-dot--asignada' : ''}" id="${id}-dot"></div>
    <div class="sede-info">
      <div class="sede-nombre">${esc(s.sede)}</div>
      <div class="sede-desc">${esc(s.descripcion) || '—'}</div>
    </div>
    <div class="sede-badges">
      <span class="badge badge-blue">🏫 ${s.aulas} aulas</span>
      ${s.tieneDatos ? `<span class="badge badge-teal" id="${id}-datos-badge">📋 Con datos</span>` : ''}
      ${s.presupuesto > 0 ? `<span class="badge badge-green" id="${id}-presup-badge">₡ ${s.presupuesto.toLocaleString('es-CR')}</span>` : `<span class="badge badge-green" id="${id}-presup-badge" style="display:none;"></span>`}
      <span class="badge badge-green" id="${id}-saved-badge" style="display:none;">✅ Guardado ahora</span>
    </div>
    <div class="toggle-btn">+</div>
  </div>

  <!-- CUERPO -->
  <div class="acordeon-body">
    <div class="acordeon-inner">

      <!-- CAMPOS OCULTOS -->
      <input type="hidden" id="${id}-aulas" value="${s.aulas}">
      <input type="hidden" id="${id}-rowIndex" value="${s.rowIndex}">

      <!-- SECCIÓN: PERSONAL -->
      <div class="section">
        <div class="section-title">👥 Personal — coordinadores y cálculo automático</div>
        <div class="fields-grid">

          <div class="field-group" style="grid-column: 1 / -1;">
            <label>Coordinador</label>
            ${buildCoordSelector(id + '-coord', s.coordinador, s.rowIndex)}
            <span class="hint">Se asigna a aulas regulares y de adecuación</span>
          </div>

          <div class="field-group">
            <label>Aplicadores</label>
            <input type="number" min="0" id="${id}-aplic"
              value="${esc(s.aplicador)}"
              oninput="marcarCambio(${s.rowIndex}); recalcularPresupuesto('${id}', ${s.rowIndex})">
            <span class="hint">Auto: 1 por aula (${s.aulas} aulas)</span>
          </div>

          <div class="field-group">
            <label>Apoyo</label>
            <input type="number" min="0" id="${id}-apoyo"
              value="${esc(s.apoyo)}"
              oninput="marcarCambio(${s.rowIndex}); recalcularPresupuesto('${id}', ${s.rowIndex})">
            <span class="hint">${apoyoHint}</span>
          </div>

          <div class="field-group">
            <label>Conserje</label>
            <input type="number" min="0" id="${id}-cons"
              value="${esc(s.conserje)}"
              oninput="marcarCambio(${s.rowIndex}); recalcularPresupuesto('${id}', ${s.rowIndex})">
            <span class="hint">Auto: 1 c/5 aulas (cantidad para conserjería)</span>
          </div>

          <div class="field-group">
            <label>UNA</label>
            <input type="number" min="0" id="${id}-una"
              value="${esc(s.una)}" placeholder="0"
              oninput="const total = parseInt(v('${id}-aplic')) + parseInt(v('${id}-apoyo')) || 0; const una = parseInt(this.value) || 0; set('${id}-ucr', Math.max(0, total - una)); marcarCambio(${s.rowIndex}); recalcularPresupuesto('${id}', ${s.rowIndex})">
            <span class="hint">De los ${s.aplicador} Aplic + ${s.apoyo} Apoyo, cuántos son UNA</span>
          </div>

          <div class="field-group">
            <label>UCR</label>
            <input type="number" min="0" id="${id}-ucr"
              value="${esc(s.ucr)}" placeholder="0" readonly
              style="background:#f1f5f9;color:#64748b;">
            <span class="hint">Calculado automáticamente (solo estos se presupuestan)</span>
          </div>

        </div>
      </div>

      <!-- SECCIÓN: HORARIO DE VIAJE -->
      <div class="section">
        <div class="section-title">🗓️ Horario de viaje (Vie · Sáb · Dom)</div>
        <div class="viaje-grid">

          <div class="viaje-card">
            <h4>🚌 Salida</h4>
            <div class="viaje-row">
              <select id="${id}-sal-dia"
                onchange="recalcularDias('${id}', ${s.rowIndex})">
                <option value="">— Día —</option>
                <option value="viernes">Viernes</option>
                <option value="sabado">Sábado</option>
                <option value="domingo">Domingo</option>
              </select>
              <input type="time" id="${id}-sal-hora" value="07:00"
                onchange="recalcularDias('${id}', ${s.rowIndex})">
            </div>
          </div>

          <div class="viaje-card">
            <h4>🏠 Regreso</h4>
            <div class="viaje-row">
              <select id="${id}-lle-dia"
                onchange="recalcularDias('${id}', ${s.rowIndex})">
                <option value="">— Día —</option>
                <option value="viernes">Viernes</option>
                <option value="sabado">Sábado</option>
                <option value="domingo">Domingo</option>
              </select>
              <input type="time" id="${id}-lle-hora" value="18:00"
                onchange="recalcularDias('${id}', ${s.rowIndex})">
            </div>
          </div>

        </div>
      </div>

      <!-- SECCIÓN: DÍAS CALCULADOS -->
      <div class="section">
        <div class="section-title">
          🍽️ Comidas y Hospedaje — D=Desayuno &nbsp;A=Almuerzo &nbsp;C=Cena &nbsp;H=Hospedaje
        </div>
        <div class="dias-grid" id="${id}-dias">
          ${buildDiaCard(id, 1, s.dia1)}
          ${buildDiaCard(id, 2, s.dia2)}
          ${buildDiaCard(id, 3, s.dia3)}
        </div>
        <div style="margin-top:9px;font-size:11px;color:var(--muted);">
          💡 Selecciona días de salida y regreso para calcular automáticamente.
          Los códigos se guardarán en las columnas DIA 1, DIA 2, DIA 3.
        </div>
      </div>

      <!-- SECCIÓN: PRESUPUESTO -->
      <div class="section">
        <div class="section-title">🧾 Presupuesto estimado</div>

        <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:14px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text);">
            <input type="checkbox" id="${id}-hosp-chk"
              onchange="toggleHospedaje('${id}', ${s.rowIndex})"
              style="width:16px;height:16px;accent-color:var(--blue);cursor:pointer;">
            Personal necesita hospedaje
          </label>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text);">
            <input type="checkbox" id="${id}-conserjeria-chk"
              onchange="recalcularPresupuesto('${id}', ${s.rowIndex}); marcarCambio(${s.rowIndex})"
              style="width:16px;height:16px;accent-color:#059669;cursor:pointer;">
            Incluir presupuesto de conserjería
          </label>
        </div>

        <div id="${id}-hosp-wrap" style="display:none;">
          <div class="fields-grid" style="margin-bottom:4px;">
            <div class="field-group">
              <label>Provincia</label>
              <select id="${id}-prov"
                onchange="actualizarCantones('${id}', ${s.rowIndex})">
                <option value="">— Seleccionar —</option>
              </select>
            </div>
            <div class="field-group">
              <label>Cantón</label>
              <select id="${id}-cant" disabled
                onchange="actualizarDistritos('${id}', ${s.rowIndex})">
                <option value="">— Primero provincia —</option>
              </select>
            </div>
            <div class="field-group">
              <label>Distrito</label>
              <select id="${id}-dist" disabled
                onchange="recalcularPresupuesto('${id}', ${s.rowIndex})">
                <option value="">— Primero cantón —</option>
              </select>
            </div>
          </div>
        </div>

        <div id="${id}-presup-result">
          ${s.presupuesto > 0 ? `<p class="presup-nota">💾 Presupuesto guardado: <strong>₡${s.presupuesto.toLocaleString('es-CR')}</strong></p>` : ''}
        </div>

        <div id="${id}-presup-edit-wrap" style="display:none;margin-top:10px;padding-top:10px;border-top:1px dashed var(--border);">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;color:var(--muted);font-weight:600;">
            <input type="checkbox" id="${id}-presup-manual-chk"
              onchange="togglePresupManualInput('${id}', ${s.rowIndex})"
              style="width:14px;height:14px;accent-color:var(--blue);cursor:pointer;">
            ✏️ Sobrescribir total manualmente
          </label>
          <div id="${id}-presup-manual-wrap" style="display:none;margin-top:10px;">
            <div class="field-group" style="max-width:220px;">
              <label>Total personalizado (₡)</label>
              <input type="number" id="${id}-presup-manual" min="0" step="1000"
                placeholder="Ej: 250000"
                oninput="marcarCambio(${s.rowIndex})">
              <span class="hint">Reemplaza el total calculado al guardar</span>
            </div>
          </div>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="acordeon-footer">
        <div class="save-status" id="${id}-status"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button class="btn btn-ghost"
            onclick="recalcularPersonal('${id}', ${s.aulas}, ${s.rowIndex})">
            🔁 Recalcular personal
          </button>
          <button class="btn btn-primary"
            onclick="guardarSede('${id}', ${s.rowIndex})">
            💾 Guardar esta sede
          </button>
        </div>
      </div>

    </div>
  </div>
</div>`;
}

// ════════════════════════════════════════════════════════════
//  CONSTRUIR TARJETA DE UN DÍA
// ════════════════════════════════════════════════════════════
const DIA_NOMBRES = { 1:'Viernes (DIA 1)', 2:'Sábado (DIA 2)', 3:'Domingo (DIA 3)' };

function buildDiaCard(id, num, codigoExistente) {
  const activo = codigoExistente ? 'activo' : '';
  const codigo = codigoExistente || '—';

  // Si hay código del sheet, intentar mostrar detalles
  let detalleHTML = '<span>Sin datos</span>';
  if (codigoExistente) {
    const tiene = (l) => codigoExistente.includes(l);
    detalleHTML = buildDetallesFromFlags(tiene('D'), tiene('A'), tiene('C'), tiene('H'));
  }

  return `
  <div class="dia-card ${activo}" id="${id}-diacard-${num}">
    <h4>${DIA_NOMBRES[num]}</h4>
    <div class="dia-codigo" id="${id}-diacod-${num}">${codigo}</div>
    <div class="dia-detalle" id="${id}-diadet-${num}">${detalleHTML}</div>
  </div>`;
}

function buildDetallesFromFlags(d, a, c, h) {
  return [
    { label:'Desayuno',  on: d },
    { label:'Almuerzo',  on: a },
    { label:'Cena',      on: c },
    { label:'Hospedaje', on: h }
  ].map(x => `<span class="${x.on ? 'on' : 'off'}">${x.label}</span>`).join('');
}

// ════════════════════════════════════════════════════════════
//  RECALCULAR DÍAS (al cambiar horario)
// ════════════════════════════════════════════════════════════
function recalcularDias(id, rowIndex) {
  const diaSal  = v(id + '-sal-dia');
  const horaSal = v(id + '-sal-hora');
  const diaLleg = v(id + '-lle-dia');
  const horaLleg = v(id + '-lle-hora');

  if (!diaSal || !diaLleg) {
    [1,2,3].forEach(n => limpiarDia(id, n));
    return;
  }

  const res = calcularDias(diaSal, horaSal, diaLleg, horaLleg);

  [1, 2, 3].forEach(n => {
    const r    = res['dia' + n];
    const card = document.getElementById(`${id}-diacard-${n}`);
    const cod  = document.getElementById(`${id}-diacod-${n}`);
    const det  = document.getElementById(`${id}-diadet-${n}`);
    if (!card) return;

    if (r && r.codigo && r.codigo !== '') {
      card.className = 'dia-card activo';
      cod.textContent = r.codigo;
      det.innerHTML = buildDetallesFromFlags(r.items.d, r.items.a, r.items.c, r.items.h);
    } else {
      limpiarDia(id, n);
    }
  });

  marcarCambio(rowIndex);
  recalcularPresupuesto(id, rowIndex);
}

function limpiarDia(id, n) {
  const card = document.getElementById(`${id}-diacard-${n}`);
  const cod  = document.getElementById(`${id}-diacod-${n}`);
  const det  = document.getElementById(`${id}-diadet-${n}`);
  if (card) card.className = 'dia-card';
  if (cod)  cod.textContent = '—';
  if (det)  det.innerHTML = '<span>Sin datos</span>';
}

// ════════════════════════════════════════════════════════════
//  RECALCULAR PERSONAL
// ════════════════════════════════════════════════════════════
function recalcularPersonal(id, aulas, rowIndex) {
  const p = calcPersonal(aulas);
  set(id + '-aplic', p.aplicador);
  set(id + '-apoyo', p.apoyo);
  set(id + '-cons',  p.conserje);
  // Limpiar UNA/UCR para que se recalcule
  set(id + '-una', 0);
  set(id + '-ucr', p.aplicador + p.apoyo);
  marcarCambio(rowIndex);
  recalcularPresupuesto(id, rowIndex);
}

// ════════════════════════════════════════════════════════════
//  GUARDAR UNA SEDE EN EL SHEET (via Apps Script doGet)
// ════════════════════════════════════════════════════════════
async function guardarSede(id, rowIndex) {
  const url = APPS_SCRIPT_URL;
  if (!url) {
    setStatus(id, 'error', '⚠️ Falta configurar la URL del Apps Script en el código');
    return;
  }

  const tdEl = elId => { const el = document.getElementById(elId); return (el?.textContent || '').trim(); };
  const dia1 = limpiaCodigo(tdEl(id + '-diacod-1'));
  const dia2 = limpiaCodigo(tdEl(id + '-diacod-2'));
  const dia3 = limpiaCodigo(tdEl(id + '-diacod-3'));

  const { total: presupuesto, desglose } = calcPresupuestoParaGuardar(id);

  const params = new URLSearchParams({
    action:       'guardarSede',
    rowIndex:     rowIndex,
    coordinador:  v(id + '-coord'),
    aplicador:    v(id + '-aplic') || '0',
    apoyo:        v(id + '-apoyo') || '0',
    una:          v(id + '-una')   || '',
    ucr:          v(id + '-ucr')   || '',
    conserje:     v(id + '-cons')  || '',
    dia1,
    dia2,
    dia3,
    presupuesto:  presupuesto || '',
    desglose:     desglose    || '',
    csrf:         CSRF
  });

  setStatus(id, 'saving', '⏳ Guardando…');

  try {
    // Antes esto era un GET con todo en la URL —heredado de cuando esto
    // llamaba a un Apps Script—: además de exponer los datos en el historial
    // del navegador y en cualquier log de acceso, una escritura por GET se
    // puede disparar desde un simple <img src="..."> ajeno, sin JavaScript
    // de por medio. Por eso pasa a POST igual que el resto del sistema.
    const res  = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString(),
    });
    const data = await res.json().catch(() => ({}));

    if (data.ok || data.ok === undefined) {
      setStatus(id, 'ok', '✅ Guardado en el sheet');
      cambiosPend.delete(rowIndex);
      actualizarSticky();
      const badge = document.getElementById(id + '-saved-badge');
      if (badge) { badge.style.display = ''; setTimeout(() => { badge.style.display = 'none'; }, 4000); }
      const presupBadge = document.getElementById(id + '-presup-badge');
      if (presupBadge && presupuesto > 0) { presupBadge.textContent = '₡ ' + presupuesto.toLocaleString('es-CR'); presupBadge.style.display = ''; }
      const dot = document.getElementById(id + '-dot');
      if (dot) dot.classList.add('sede-dot--asignada');
      const datosBadge = document.getElementById(id + '-datos-badge');
      if (datosBadge) datosBadge.style.display = '';
      else {
        const badges = document.querySelector(`#${id} .sede-badges`);
        if (badges && !badges.querySelector('.badge-teal')) {
          const nb = document.createElement('span');
          nb.className = 'badge badge-teal';
          nb.id = id + '-datos-badge';
          nb.textContent = '📋 Con datos';
          badges.insertBefore(nb, badges.children[1]);
        }
      }
    } else {
      setStatus(id, 'error', '❌ ' + (data.mensaje || 'Error desconocido'));
    }
  } catch (err) {
    setStatus(id, 'error', '❌ Error de red: ' + err.message);
  }
}

// ════════════════════════════════════════════════════════════
//  GUARDAR TODAS LAS SEDES
// ════════════════════════════════════════════════════════════
async function guardarTodas() {
  if (!APPS_SCRIPT_URL) {
    alert('⚠️ Falta configurar la URL del Apps Script en el código');
    return;
  }
  if (!sedesFiltradas.length) return;

  const total = sedesFiltradas.length;
  const confirmado = confirm(
    `⚠️ Guardar todas las sedes\n\n` +
    `Esto sobreescribirá los datos de ${total} sede${total !== 1 ? 's' : ''} en Google Sheets.\n\n` +
    `¿Estás seguro/a de que quieres continuar?`
  );
  if (!confirmado) return;

  const btn = document.getElementById('btn-guardar-todas');
  btn.disabled = true;
  btn.textContent = '⏳ Guardando…';

  let ok = 0, errores = 0;
  for (const s of sedesFiltradas) {
    const id = 'ac-' + s.rowIndex;
    try {
      await guardarSede(id, s.rowIndex);
      ok++;
    } catch {
      errores++;
    }
    await sleep(350); // pausa entre requests para no saturar el script
  }

  btn.disabled = false;
  btn.textContent = '💾 Guardar Todo';
  alert(`Proceso finalizado:\n✅ ${ok} sedes guardadas\n❌ ${errores} con error`);
}

// ════════════════════════════════════════════════════════════
//  PRESUPUESTO — HOSPEDAJE + COMIDAS
// ════════════════════════════════════════════════════════════
function togglePresupManualInput(id, rowIndex) {
  const chk  = document.getElementById(id + '-presup-manual-chk');
  const wrap = document.getElementById(id + '-presup-manual-wrap');
  if (!wrap) return;
  wrap.style.display = chk.checked ? '' : 'none';
  if (chk.checked) {
    const { total } = calcPresupuestoParaGuardar(id);
    const input = document.getElementById(id + '-presup-manual');
    if (input && !input.value) input.value = total || '';
  }
  marcarCambio(rowIndex);
}

function toggleHospedaje(id, rowIndex) {
  const chk  = document.getElementById(id + '-hosp-chk');
  const wrap = document.getElementById(id + '-hosp-wrap');
  if (!wrap) return;
  wrap.style.display = chk.checked ? '' : 'none';
  recalcularPresupuesto(id, rowIndex);
  marcarCambio(rowIndex);
}

function actualizarCantones(id, rowIndex) {
  const prov    = v(id + '-prov');
  const cantSel = document.getElementById(id + '-cant');
  const distSel = document.getElementById(id + '-dist');

  cantSel.innerHTML = '<option value="">— Seleccionar —</option>';
  distSel.innerHTML = '<option value="">— Primero cantón —</option>';
  distSel.disabled  = true;

  if (!prov) { cantSel.disabled = true; recalcularPresupuesto(id, rowIndex); return; }

  const cantones = [...new Set(
    hospedajeData.filter(r => r.provincia === prov).map(r => r.canton)
  )].sort((a, b) => a.localeCompare(b, 'es'));

  cantones.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c; opt.textContent = c;
    cantSel.appendChild(opt);
  });
  cantSel.disabled = false;
  recalcularPresupuesto(id, rowIndex);
}

function actualizarDistritos(id, rowIndex) {
  const prov    = v(id + '-prov');
  const cant    = v(id + '-cant');
  const distSel = document.getElementById(id + '-dist');

  distSel.innerHTML = '<option value="">— Seleccionar —</option>';

  if (!prov || !cant) { distSel.disabled = true; recalcularPresupuesto(id, rowIndex); return; }

  const distritos = hospedajeData
    .filter(r => r.provincia === prov && r.canton === cant)
    .sort((a, b) => a.distrito.localeCompare(b.distrito, 'es'));

  distritos.forEach(({ distrito, tarifa }) => {
    const opt = document.createElement('option');
    opt.value = distrito;
    opt.dataset.tarifa = tarifa;
    opt.textContent = distrito;
    distSel.appendChild(opt);
  });
  distSel.disabled = false;
  recalcularPresupuesto(id, rowIndex);
}

function recalcularPresupuesto(id, rowIndex) {
  const result = document.getElementById(id + '-presup-result');
  if (!result) return;

  const hospChk   = document.getElementById(id + '-hosp-chk');
  const consChk   = document.getElementById(id + '-conserjeria-chk');
  const necesitaH = hospChk && hospChk.checked;
  const necesitaC = consChk && consChk.checked;

  let desayunos = 0, almuerzos = 0, cenas = 0, noches = 0;
  for (let n = 1; n <= 3; n++) {
    const cod = (document.getElementById(`${id}-diacod-${n}`)?.textContent || '').replace('—', '');
    if (cod.includes('D')) desayunos++;
    if (cod.includes('A')) almuerzos++;
    if (cod.includes('C')) cenas++;
    if (cod.includes('H')) noches++;
  }

  const aplic       = parseInt(v(id + '-aplic')) || 0;
  const apoyo       = parseInt(v(id + '-apoyo')) || 0;
  const una         = parseInt(v(id + '-una')) || 0;
  const ucr         = parseInt(v(id + '-ucr')) || 0;
  const aulas       = parseInt(v(id + '-aulas')) || 0;
  const conserjes   = parseInt(v(id + '-cons')) || 0;
  
  // Personal para sobresueldos: 1 coord + UCR (no UNA)
  const personasPresupuesto = 1 + ucr;

  const PRECIO = { D: 4200, A: 5600, C: 5600 };
  const totalComidas = (desayunos * PRECIO.D + almuerzos * PRECIO.A + cenas * PRECIO.C) * personasPresupuesto;

  let tarifa = 0, totalHosp = 0, provCantDist = '';
  if (necesitaH) {
    const distSel = document.getElementById(id + '-dist');
    const selOpt  = distSel?.options[distSel.selectedIndex];
    if (selOpt && selOpt.value) {
      tarifa       = parseInt(selOpt.dataset.tarifa || '0');
      provCantDist = `${v(id + '-prov')} / ${v(id + '-cant')} / ${selOpt.value}`;
      totalHosp    = noches * tarifa * personasPresupuesto;
    }
  }

  const costoConserjeria = necesitaC ? calcCostoConserjeria(aulas, conserjes) : 0;

  const fmt      = n => '₡' + n.toLocaleString('es-CR');
  const total    = totalComidas + totalHosp + costoConserjeria;
  const editWrap = document.getElementById(id + '-presup-edit-wrap');

  if (!necesitaH && desayunos + almuerzos + cenas === 0 && !necesitaC) {
    result.innerHTML = '';
    if (editWrap) editWrap.style.display = 'none';
    return;
  }

  if (editWrap) editWrap.style.display = '';

  const rows = [];

  // Personal para sobresueldos
  const unaLabel = una > 0 ? ` (${una} UNA no se presupuestan)` : '';
  rows.push({ l: 'Personal — Sobresueldos', v: `${personasPresupuesto} persona${personasPresupuesto !== 1 ? 's' : ''} (1 coord + ${ucr} UCR${unaLabel})` });

  if (necesitaH) {
    if (tarifa > 0 && noches > 0) {
      rows.push({ l: `Hospedaje — ${provCantDist}`, v: `${noches} noche${noches !== 1 ? 's' : ''} × ${fmt(tarifa)} × ${personasPresupuesto} pers = ${fmt(totalHosp)}` });
    } else if (noches === 0) {
      rows.push({ l: 'Hospedaje', v: 'Sin noches (configura los días de viaje)' });
    } else {
      rows.push({ l: 'Hospedaje', v: 'Selecciona provincia, cantón y distrito' });
    }
  }

  if (desayunos > 0) rows.push({ l: `Desayunos  (${desayunos} × ${fmt(PRECIO.D)} × ${personasPresupuesto} pers)`, v: fmt(desayunos * PRECIO.D * personasPresupuesto) });
  if (almuerzos > 0) rows.push({ l: `Almuerzos (${almuerzos} × ${fmt(PRECIO.A)} × ${personasPresupuesto} pers)`, v: fmt(almuerzos * PRECIO.A * personasPresupuesto) });
  if (cenas > 0)     rows.push({ l: `Cenas (${cenas} × ${fmt(PRECIO.C)} × ${personasPresupuesto} pers)`,          v: fmt(cenas * PRECIO.C * personasPresupuesto) });

  if (necesitaC) {
    const costoPorUnit = aulas < 5 ? 20000 : 27000;
    rows.push({ l: `Conserjería (${conserjes} cons × ${fmt(costoPorUnit)} en sed. de ${aulas} aulas)`, v: fmt(costoConserjeria) });
  }

  // Construir desglose simple para mostrar
  const desgloseParts = [];
  if (necesitaH && totalHosp > 0) 
    desgloseParts.push(`Hospedaje ${fmt(totalHosp)}`);
  if (desayunos > 0) 
    desgloseParts.push(`Desayunos ${fmt(desayunos * PRECIO.D * personasPresupuesto)}`);
  if (almuerzos > 0) 
    desgloseParts.push(`Almuerzos ${fmt(almuerzos * PRECIO.A * personasPresupuesto)}`);
  if (cenas > 0) 
    desgloseParts.push(`Cenas ${fmt(cenas * PRECIO.C * personasPresupuesto)}`);
  if (necesitaC && costoConserjeria > 0) 
    desgloseParts.push(`Conserjería ${fmt(costoConserjeria)}`);

  result.innerHTML = `
    <div class="presup-box">
      <div class="presup-box-title">Desglose estimado</div>
      <div class="presup-row" style="padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;line-height:1.5;color:var(--text);">
        ${desgloseParts.join(' + ')}
      </div>
      <div class="presup-total">
        <span>TOTAL ESTIMADO</span>
        <span>${fmt(total)}</span>
      </div>
    </div>`;
}

function calcPresupuestoParaGuardar(id) {
  const hospChk   = document.getElementById(id + '-hosp-chk');
  const consChk   = document.getElementById(id + '-conserjeria-chk');
  const necesitaH = hospChk && hospChk.checked;
  const necesitaC = consChk && consChk.checked;

  let desayunos = 0, almuerzos = 0, cenas = 0, noches = 0;
  for (let n = 1; n <= 3; n++) {
    const cod = (document.getElementById(`${id}-diacod-${n}`)?.textContent || '').replace('—', '');
    if (cod.includes('D')) desayunos++;
    if (cod.includes('A')) almuerzos++;
    if (cod.includes('C')) cenas++;
    if (cod.includes('H')) noches++;
  }

  const aplic       = parseInt(v(id + '-aplic')) || 0;
  const apoyo       = parseInt(v(id + '-apoyo')) || 0;
  const una         = parseInt(v(id + '-una')) || 0;
  const ucr         = parseInt(v(id + '-ucr')) || 0;
  const aulas       = parseInt(v(id + '-aulas')) || 0;
  const conserjes   = parseInt(v(id + '-cons')) || 0;
  
  // Personal para sobresueldos: 1 coord + UCR (no UNA)
  const personasPresupuesto = 1 + ucr;

  let tarifa = 0, totalHosp = 0;
  if (necesitaH) {
    const distSel = document.getElementById(id + '-dist');
    const opt     = distSel?.options[distSel?.selectedIndex];
    if (opt && opt.value) {
      tarifa    = parseInt(opt.dataset.tarifa || '0');
      totalHosp = noches * tarifa * personasPresupuesto;
    }
  }

  const totalComidas = (desayunos * 4200 + almuerzos * 5600 + cenas * 5600) * personasPresupuesto;
  const costoConserjeria = necesitaC ? calcCostoConserjeria(aulas, conserjes) : 0;
  const calcTotal    = totalHosp + totalComidas + costoConserjeria;

  const formatCR = (val) => '₡' + val.toLocaleString('es-CR');

  // Construir desglose legible
  const desgloseParts = [];
  if (necesitaH && totalHosp > 0) 
    desgloseParts.push(`Hospedaje ${formatCR(totalHosp)}`);
  if (desayunos > 0) 
    desgloseParts.push(`Desayunos ${formatCR(desayunos * 4200 * personasPresupuesto)}`);
  if (almuerzos > 0) 
    desgloseParts.push(`Almuerzos ${formatCR(almuerzos * 5600 * personasPresupuesto)}`);
  if (cenas > 0) 
    desgloseParts.push(`Cenas ${formatCR(cenas * 5600 * personasPresupuesto)}`);
  if (necesitaC && conserjes > 0) {
    desgloseParts.push(`Conserjería ${formatCR(costoConserjeria)}`);
  }

  const manualChk = document.getElementById(id + '-presup-manual-chk');
  if (manualChk && manualChk.checked) {
    const manualVal = parseInt(v(id + '-presup-manual')) || calcTotal;
    return { total: manualVal, desglose: formatCR(manualVal) + ' [manual]' };
  }

  return { total: calcTotal, desglose: desgloseParts.join(' + ') || formatCR(calcTotal) };
}

// ════════════════════════════════════════════════════════════
//  FILTRAR Y ORDENAR
// ════════════════════════════════════════════════════════════
function filtrar(termino) {
  aplicarFiltros();
}

function limpiarFiltros() {
  document.getElementById('busqueda').value        = '';
  document.getElementById('filtro-orden').value    = 'az';
  document.getElementById('filtro-estado').value   = 'todas';
  document.getElementById('filtro-aulas').value    = 'todas';
  aplicarFiltros();
}

function aplicarFiltros() {
  currentPage = 0;

  const termino = (document.getElementById('busqueda')?.value || '').toLowerCase().trim();
  const orden   = document.getElementById('filtro-orden')?.value  || 'az';
  const estado  = document.getElementById('filtro-estado')?.value || 'todas';
  const aulasF  = document.getElementById('filtro-aulas')?.value  || 'todas';

  let resultado = [...sedesData];

  // Búsqueda por texto
  if (termino) {
    resultado = resultado.filter(s =>
      s.sede.toLowerCase().includes(termino) ||
      s.descripcion.toLowerCase().includes(termino) ||
      s.coordinador.toLowerCase().includes(termino)
    );
  }

  // Filtro por estado
  if (estado === 'con-datos') resultado = resultado.filter(s => s.tieneDatos);
  if (estado === 'sin-datos') resultado = resultado.filter(s => !s.tieneDatos);

  // Filtro por rango de aulas
  if (aulasF !== 'todas') {
    resultado = resultado.filter(s => {
      const n = s.aulas;
      if (aulasF === '1-5')   return n >= 1  && n <= 5;
      if (aulasF === '6-10')  return n >= 6  && n <= 10;
      if (aulasF === '11-20') return n >= 11 && n <= 20;
      if (aulasF === '21+')   return n >= 21;
      return true;
    });
  }

  // Ordenamiento
  resultado.sort((a, b) => {
    if (orden === 'az')         return a.sede.localeCompare(b.sede, 'es');
    if (orden === 'za')         return b.sede.localeCompare(a.sede, 'es');
    if (orden === 'aulas-asc')  return a.aulas - b.aulas;
    if (orden === 'aulas-desc') return b.aulas - a.aulas;
    return 0;
  });

  sedesFiltradas = resultado;
  renderSedes();
}

// ════════════════════════════════════════════════════════════
//  ACORDEÓN
// ════════════════════════════════════════════════════════════
function toggleAc(id) {
  const ac = document.getElementById(id);
  const estaAbierto = !ac.classList.contains('abierto');
  ac.classList.toggle('abierto');
  
  // Cuando se abre un acordeón, regenerar el desglose automáticamente
  if (estaAbierto) {
    const rowIndex = parseInt(document.getElementById(id + '-rowIndex').value) || 0;
    setTimeout(() => recalcularPresupuesto(id, rowIndex), 100);
  }
}

// ════════════════════════════════════════════════════════════
//  STICKY BAR
// ════════════════════════════════════════════════════════════
function marcarCambio(rowIndex) {
  cambiosPend.add(rowIndex);
  actualizarSticky();
}
function actualizarSticky() {
  const n  = cambiosPend.size;
  const el = document.getElementById('sticky-status');
  if (el) el.textContent = n > 0
    ? `⚠️ ${n} sede(s) con cambios sin guardar`
    : 'Sin cambios pendientes';
}

// ════════════════════════════════════════════════════════════
//  UTILIDADES
// ════════════════════════════════════════════════════════════
function v(id)      { return (document.getElementById(id)?.value || '').trim(); }
function set(id, x) { const el = document.getElementById(id); if (el) el.value = x; }
function esc(s)     { return String(s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
function limpiaCodigo(s) { return (s || '').replace('—','').trim(); }
function sleep(ms)  { return new Promise(r => setTimeout(r, ms)); }

function setStatus(id, tipo, msg) {
  const el = document.getElementById(id + '-status');
  if (!el) return;
  el.className = 'save-status ' + tipo;
  el.textContent = msg;
  if (tipo === 'ok') setTimeout(() => { if (el) el.textContent = ''; }, 5000);
}

// ════════════════════════════════════════════════════════════
//  DESCARGAR EXCEL
// ════════════════════════════════════════════════════════════
function descargarExcel() {
  if (!sedesData.length) {
    alert('No hay sedes cargadas para descargar');
    return;
  }

  // Preparar datos para Excel
  const datosExcel = sedesData.map(sede => ({
    'SEDE_EXAMEN': sede.sede || '',
    'DESCRIPCION': sede.descripcion || '',
    'AULAS': sede.aulas || '',
    'COORD_REGULAR': sede.coordinador || '',
    'COORD_ADECUACION': sede.coordinador || '',
    'APLICADOR': sede.aplicador || '',
    'APOYO': sede.apoyo || '',
    'UNA': sede.una || '',
    'UCR': sede.ucr || '',
    'CONSERJE': sede.conserje || '',
    'DIA 1': sede.dia1 || '',
    'DIA 2': sede.dia2 || '',
    'DIA 3': sede.dia3 || '',
    'PRESUPUESTO': sede.presupuesto || 0,
    'DESGLOSE': sede.desglose || ''
  }));

  // Crear libro de Excel
  const ws = XLSX.utils.json_to_sheet(datosExcel);
  
  // Ajustar ancho de columnas
  const columnWidths = [25, 35, 8, 20, 20, 12, 10, 8, 8, 10, 12, 12, 12, 15, 40];
  ws['!cols'] = columnWidths.map(w => ({ wch: w }));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Sedes PAA');

  // Descargar archivo
  const fecha = new Date().toISOString().split('T')[0];
  XLSX.writeFile(wb, `PAA_Sedes_${fecha}.xlsx`);
}

// ════════════════════════════════════════════════════════════
//  INICIO
// ════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  cargarCoordinadores();
  cargarHospedaje();
  cargarSedes();
});
</script>
<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
