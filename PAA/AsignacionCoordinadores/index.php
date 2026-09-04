<?php
/**
 * Módulo de uso interno: solo administración.
 *
 * La guarda va antes de imprimir nada; si esta línea falta, la página queda
 * abierta a cualquiera que sepa la dirección.
 */
require_once __DIR__ . '/../includes/modulos.php';
$yo = exigir_modulo("coordinadores", '../');
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title>Gestión PAA · Asignación de Coordinadores · UCR</title>
	<link rel="icon" type="image/png" href="../assets/favicon-paa.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
		<!-- SheetJS desde CDN. El integrity hace que el navegador verifique el
	     archivo antes de ejecutarlo: si el CDN sirviera otro contenido, se
	     bloquea en vez de correr código ajeno sobre los archivos del usuario. -->
	<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"
	        integrity="sha384-vtjasyidUo0kW94K5MXDXntzOJpQgBKXmE7e2Ga4LG0skTTLeBi97eFAXsqewJjw"
	        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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

			--primary-blue: var(--primary-light);
			--secondary-blue: var(--primary);
			--light-blue: #e8f0fe;
			--dark-blue: var(--primary-dark);
			--accent-green: var(--success);
			--accent-amber: var(--warning);
			--text-dark: var(--gray-800);
			--text-light: var(--gray-500);
			--background: var(--gray-50);
			--white: #ffffff;
			--border-color: var(--gray-200);
			--shadow-light: var(--shadow);
			--shadow-medium: 0 12px 30px rgba(0, 0, 0, 0.14);
			--shadow-hover: 0 18px 34px rgba(0, 0, 0, 0.18);
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

		.app { max-width: 1300px; margin: 0 auto; }

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

		.panel {
			background: var(--white);
			border-radius: var(--radius);
			box-shadow: var(--shadow-light);
			border: 1px solid var(--border-color);
			padding: 20px;
			margin-bottom: 20px;
		}

		.upload-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 18px;
			align-items: start;
		}

		.field-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.field-group label {
			color: var(--secondary-blue);
			font-weight: 700;
			font-size: 0.95rem;
		}

		.file-input {
			display: none;
		}

		.file-picker {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.file-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			padding: 11px 14px;
			border-radius: 10px;
			border: 1px solid #c9ddf3;
			background: linear-gradient(135deg, #f7fbff, #ecf4ff);
			color: var(--secondary-blue);
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
			box-shadow: 0 6px 14px rgba(44, 90, 160, 0.12);
		}

		.file-btn:hover {
			transform: translateY(-2px);
			background: linear-gradient(135deg, #edf7ff, #dfeeff);
			box-shadow: 0 10px 20px rgba(44, 90, 160, 0.18);
		}

		.file-btn.loaded {
			background: linear-gradient(135deg, #e8f8ef, #d9f4e5);
			border-color: #bde4cb;
			color: #0f6b36;
		}

		.file-name {
			font-size: 0.84rem;
			color: var(--text-light);
			background: #f8fbff;
			border: 1px solid #e0ebf7;
			padding: 7px 10px;
			border-radius: 8px;
		}

		.file-name.has-file {
			color: #0f6b36;
			border-color: #bde4cb;
			background: #f3fff7;
		}

		.criterio-prioridad {
			border: 1px solid #c9ddf3;
			border-radius: 10px;
			padding: 10px 12px;
			min-width: 220px;
			background: #fff;
			color: var(--gray-700);
			font-weight: 600;
			outline: none;
			transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
			box-shadow: 0 2px 6px rgba(44, 90, 160, 0.08);
		}

		.criterio-prioridad:hover,
		.criterio-prioridad:focus {
			border-color: var(--primary-light);
			box-shadow: 0 0 0 3px rgba(43, 127, 194, 0.12);
		}

		.actions-row {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			margin-top: 14px;
		}

		.btn {
			border: none;
			border-radius: 10px;
			font-weight: 700;
			padding: 11px 16px;
			cursor: pointer;
			transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
		}

		.btn:hover {
			transform: translateY(-2px);
		}

		.btn-primary {
			background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
			color: #fff;
			box-shadow: 0 8px 16px rgba(44, 90, 160, 0.28);
		}

		.btn-secondary {
			background: #eef6ff;
			color: var(--secondary-blue);
			border: 1px solid #c9ddf3;
		}

		.btn-success {
			background: linear-gradient(135deg, #159947, #0b7a37);
			color: #fff;
			box-shadow: 0 8px 16px rgba(21, 153, 71, 0.25);
		}

		.btn:disabled {
			opacity: 0.55;
			cursor: not-allowed;
			transform: none;
			box-shadow: none;
		}

		.help {
			margin-top: 8px;
			color: var(--text-light);
			font-size: 0.88rem;
		}

		.status-box {
			background: var(--light-blue);
			border-left: 5px solid var(--secondary-blue);
			padding: 12px 14px;
			border-radius: 0 10px 10px 0;
			margin-top: 12px;
			color: var(--text-dark);
			font-size: 0.92rem;
			white-space: pre-line;
		}

		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
			gap: 12px;
			margin-bottom: 16px;
		}

		.stat-card {
			background: var(--white);
			border: 1px solid var(--border-color);
			border-radius: 12px;
			box-shadow: var(--shadow-light);
			padding: 14px;
		}

		.stat-card h3 {
			font-size: 0.85rem;
			color: var(--text-light);
			margin-bottom: 6px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.03em;
		}

		.stat-card .value {
			font-size: 1.5rem;
			color: var(--dark-blue);
			font-weight: 800;
		}

		.pagination-wrap {
			display: flex;
			flex-direction: column;
			gap: 10px;
			margin: 14px 0 8px;
		}

		.pagination-info {
			font-size: 0.9rem;
			color: var(--text-light);
			font-weight: 600;
		}

		.pagination-controls {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}

		.pagination-btn {
			border: 1px solid #c9ddf3;
			background: #fff;
			color: var(--secondary-blue);
			border-radius: 10px;
			padding: 8px 12px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.18s ease;
			min-width: 42px;
		}

		.pagination-btn:hover:not(:disabled) {
			transform: translateY(-1px);
			background: #eef6ff;
		}

		.pagination-btn.active {
			background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
			color: #fff;
			border-color: transparent;
		}

		.pagination-btn:disabled {
			opacity: 0.5;
			cursor: not-allowed;
		}

		.filters-row {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			align-items: center;
			margin-bottom: 16px;
		}

		.filters-row input,
		.filters-row select {
			border: 1px solid #c9ddf3;
			border-radius: 10px;
			padding: 10px 12px;
			min-width: 270px;
			background: #fff;
		}

		.contador {
			background: linear-gradient(135deg, var(--secondary-blue), var(--dark-blue));
			color: #fff;
			border-radius: 999px;
			padding: 8px 14px;
			display: inline-block;
			font-size: 0.9rem;
			font-weight: 700;
			margin-bottom: 14px;
		}

		.acordeon {
			margin-bottom: 14px;
			border-radius: 14px;
			overflow: hidden;
			background: #fff;
			border: 1px solid #d6e4f2;
			box-shadow: var(--shadow-light);
			transition: all 0.2s ease;
		}

		.acordeon:hover {
			box-shadow: var(--shadow-hover);
			transform: translateY(-2px);
		}

		.acordeon-cabecera {
			padding: 16px;
			display: grid;
			grid-template-columns: minmax(0, 1fr) auto;
			column-gap: 12px;
			align-items: flex-start;
			cursor: pointer;
			background: #fff;
		}

		.acordeon-cabecera > div {
			flex: 1;
			min-width: 0;
			text-align: left;
		}

		.acordeon-cabecera h3 {
			color: var(--dark-blue);
			font-size: 1.05rem;
			font-weight: 800;
			display: flex;
			align-items: center;
			justify-content: flex-start;
			gap: 8px;
			flex-wrap: wrap;
			text-align: left;
		}

		.sede-code {
			display: inline-flex;
			align-items: center;
			padding: 3px 9px;
			margin-right: 8px;
			border-radius: 999px;
			background: #e8f2ff;
			color: var(--secondary-blue);
			font-size: 0.78rem;
			font-weight: 800;
			letter-spacing: 0.02em;
		}

		.acordeon-cabecera p {
			color: var(--text-light);
			font-size: 0.88rem;
			margin-top: 4px;
			text-align: left;
			word-break: break-word;
		}

		.acordeon-icono {
			width: 32px;
			height: 32px;
			min-width: 32px;
			min-height: 32px;
			max-width: 32px;
			max-height: 32px;
			border-radius: 50%;
			background: var(--primary-blue);
			color: #fff;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1rem;
			font-weight: 700;
			transition: transform 0.25s ease;
			flex-shrink: 0;
			justify-self: end;
			align-self: center;
			padding: 0;
			line-height: 1;
		}

		.acordeon.abierto .acordeon-icono {
			transform: rotate(45deg);
			background: var(--secondary-blue);
		}

		.acordeon-contenido {
			max-height: 0;
			overflow: hidden;
			transition: max-height 0.28s ease, padding 0.28s ease;
			padding: 0 16px;
			background: #fcfdff;
		}

		.acordeon.abierto .acordeon-contenido {
			max-height: 1200px;
			padding: 4px 16px 16px;
		}

		.info-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 10px;
			margin-bottom: 12px;
		}

		.info-item {
			background: #eef6ff;
			border-left: 4px solid var(--primary-blue);
			border-radius: 10px;
			padding: 10px;
		}

		.info-item strong {
			display: block;
			color: var(--secondary-blue);
			font-size: 0.8rem;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}

		.info-item div {
			color: var(--text-dark);
			font-size: 0.95rem;
			font-weight: 600;
			margin-top: 4px;
		}

		.asignado-box {
			background: linear-gradient(135deg, #eaf8ee, #f3fff7);
			border: 1px solid #ccead6;
			border-left: 5px solid var(--accent-green);
			border-radius: 10px;
			padding: 12px;
		}

		.asignado-box h4 {
			color: #0f6b36;
			margin-bottom: 8px;
		}

		.sin-asignar {
			background: #fff7ed;
			border: 1px solid #fed7aa;
			border-left: 5px solid var(--accent-amber);
			border-radius: 10px;
			padding: 12px;
			color: #9a3412;
			font-weight: 700;
		}

		.table-wrap {
			overflow-x: auto;
			margin-top: 12px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			background: #fff;
			border-radius: 12px;
			overflow: hidden;
		}

		th,
		td {
			border-bottom: 1px solid #e5edf5;
			padding: 10px;
			text-align: left;
			font-size: 0.9rem;
			white-space: nowrap;
		}

		th {
			background: #edf5fe;
			color: var(--secondary-blue);
			font-size: 0.8rem;
			text-transform: uppercase;
			letter-spacing: 0.03em;
		}

		.badge {
			display: inline-block;
			border-radius: 999px;
			padding: 4px 10px;
			font-size: 0.75rem;
			font-weight: 700;
		}

		.badge-ok {
			background: #dcfce7;
			color: #166534;
		}

		.badge-warn {
			background: #ffedd5;
			color: #9a3412;
		}

		.manual-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-top: 10px;
		}

		.manual-btn {
			border: 1px solid #bfd5ec;
			background: #f4f9ff;
			color: var(--secondary-blue);
			border-radius: 10px;
			padding: 8px 10px;
			font-size: 0.82rem;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.18s ease;
		}

		.manual-btn:hover {
			background: #e8f2ff;
			transform: translateY(-1px);
		}

		.modal-overlay {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.52);
			display: none;
			align-items: center;
			justify-content: center;
			padding: 14px;
			z-index: 9999;
		}

		.modal-overlay.open {
			display: flex;
		}

		.modal-card {
			width: min(760px, 100%);
			max-height: 88vh;
			overflow: auto;
			background: #ffffff;
			border: 1px solid #d7e5f4;
			border-radius: 14px;
			box-shadow: var(--shadow-medium);
			padding: 16px;
		}

		.modal-card h3 {
			color: var(--dark-blue);
			font-size: 1.15rem;
			margin-bottom: 10px;
		}

		.modal-meta {
			background: #f4f9ff;
			border: 1px solid #d6e5f5;
			border-radius: 10px;
			padding: 10px;
			font-size: 0.9rem;
			margin-bottom: 12px;
		}

		.modal-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
			margin-bottom: 12px;
		}

		.modal-group label {
			font-weight: 700;
			color: var(--secondary-blue);
			font-size: 0.92rem;
		}

		.modal-group select {
			border: 1px solid #c7daf0;
			border-radius: 10px;
			padding: 10px;
			background: #fff;
		}

		.modal-group input[type="text"] {
			border: 1px solid #c7daf0;
			border-radius: 10px;
			padding: 10px;
			background: #fff;
		}

		.move-conflict-box {
			display: none;
			background: #fff7ed;
			border: 1px solid #fed7aa;
			border-left: 4px solid #f97316;
			border-radius: 10px;
			padding: 10px;
			font-size: 0.9rem;
			margin-bottom: 12px;
		}

		.move-conflict-box.active {
			display: block;
		}

		.radio-row {
			display: flex;
			flex-direction: column;
			gap: 8px;
			margin-top: 8px;
		}

		.radio-row label {
			display: flex;
			gap: 8px;
			align-items: flex-start;
			cursor: pointer;
		}

		.modal-actions {
			display: flex;
			gap: 10px;
			justify-content: flex-end;
			margin-top: 12px;
		}

		.loading {
			text-align: center;
			padding: 26px;
			color: var(--text-light);
		}

		.error {
			margin-top: 10px;
			border-radius: 10px;
			background: #fef2f2;
			border: 1px solid #fecaca;
			color: var(--danger);
			padding: 10px;
			font-weight: 700;
		}

		@media (max-width: 900px) {
			body {
				padding: 14px;
			}

			.acordeon-cabecera {
				flex-direction: column;
				align-items: flex-start;
			}

			.filters-row input,
			.filters-row select {
				min-width: 100%;
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
			<h1>Asignación de Coordinadores</h1>
			<p>
				Cargue el archivo de respuestas del formulario de coordinadores y el archivo de sedes.
				El sistema calcula la mejor asignacion por afinidad (disponibilidad, ubicacion y condiciones declaradas) y permite exportar el resultado final.
			</p>
		</div>

		<section class="panel">
			<div class="upload-grid">
				<div class="field-group">
					<label for="coordinadoresFile">Archivo de coordinadores (Excel o CSV)</label>
					<div class="file-picker">
						<input id="coordinadoresFile" class="file-input" type="file" accept=".xlsx,.xls,.csv" />
						<label for="coordinadoresFile" id="coordinadoresFileBtn" class="file-btn">Seleccionar archivo</label>
						<div id="coordinadoresFileName" class="file-name">Ningun archivo seleccionado</div>
					</div>
					<small class="help">Use el Excel generado por Forms con todas las respuestas.</small>
				</div>
				<div class="field-group">
					<label for="sedesFile">Archivo de sedes (Excel o CSV)</label>
					<div class="file-picker">
						<input id="sedesFile" class="file-input" type="file" accept=".xlsx,.xls,.csv" />
						<label for="sedesFile" id="sedesFileBtn" class="file-btn">Seleccionar archivo</label>
						<div id="sedesFileName" class="file-name">Ningun archivo seleccionado</div>
					</div>
					<small class="help">Debe contener al menos nombre de sede y direccion o ubicacion.</small>
				</div>
				<div class="field-group">
					<label for="criterioPrioridad">Prioridad de asignacion</label>
					<select id="criterioPrioridad" class="criterio-prioridad">
						<option value="balanceado">Balanceado (recomendado)</option>
						<option value="ubicacion">Priorizar cercania geografica</option>
						<option value="disponibilidad">Priorizar disponibilidad de turnos</option>
					</select>
				</div>
			</div>

			<div class="actions-row">
				<button class="btn btn-primary" id="btnProcesar">Procesar y asignar</button>
				<button class="btn btn-secondary" id="btnReasignar" disabled>Recalcular asignaciones</button>
				<button class="btn btn-success" id="btnExportar" disabled>Exportar Excel de asignaciones</button>
			</div>

			<div id="statusBox" class="status-box">Esperando archivos para iniciar.</div>
			<div id="errorBox" class="error" style="display:none;"></div>
		</section>

		<section class="panel">
			<div class="stats-grid">
				<div class="stat-card">
					<h3>Coordinadores cargados</h3>
					<div class="value" id="statCoordinadores">0</div>
				</div>
				<div class="stat-card">
					<h3>Sedes cargadas</h3>
					<div class="value" id="statSedes">0</div>
				</div>
				<div class="stat-card">
					<h3>Sedes asignadas</h3>
					<div class="value" id="statAsignadas">0</div>
				</div>
				<div class="stat-card">
					<h3>Sin asignar</h3>
					<div class="value" id="statSinAsignar">0</div>
				</div>
			</div>

			<div class="filters-row">
				<input id="busqueda" type="text" placeholder="Buscar por sede, direccion o coordinador" />
				<select id="filtroEstado">
					<option value="todos">Todas</option>
					<option value="asignadas">Solo asignadas</option>
					<option value="sin-asignar">Solo sin asignar</option>
				</select>
			</div>

			<div id="contadorResultados" class="contador">0 resultados</div>
			<div id="paginacionSedes" class="pagination-wrap">
				<div id="paginacionInfo" class="pagination-info"></div>
				<div id="paginacionControles" class="pagination-controls"></div>
			</div>
			<div id="listaSedes" class="loading">Cargue ambos archivos para visualizar resultados.</div>
		</section>

		<section class="panel">
			<h2 style="color:var(--dark-blue); margin-bottom:10px;">Vista tabular de asignaciones</h2>
			<div class="table-wrap">
				<table>
					<thead>
						<tr>
							<th>#</th>
							<th>Sede</th>
							<th>Coordinador asignado</th>
							<th>Correo</th>
							<th>Puntaje</th>
							<th>Estado</th>
						</tr>
					</thead>
					<tbody id="tablaBody">
						<tr><td colspan="6" style="text-align:center; color:#64748b;">Sin datos aun.</td></tr>
					</tbody>
				</table>
			</div>
		</section>
	</div>

	<div id="modalEditorAsignacion" class="modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
		<div class="modal-card">
			<h3 id="modalTitulo">Editar asignacion</h3>
			<div class="modal-meta" id="modalMetaSede"></div>

			<div id="modalSeccionCambio" class="modal-group">
				<label for="modalCoordinadorSelect">Seleccionar coordinador para esta sede</label>
				<input id="modalCoordinadorSearch" type="text" placeholder="Buscar coordinador..." />
				<select id="modalCoordinadorSelect"></select>
			</div>

			<div id="modalSeccionMover" class="modal-group" style="display:none;">
				<label for="modalSedeDestinoSelect">Mover coordinador actual hacia otra sede</label>
				<input id="modalSedeDestinoSearch" type="text" placeholder="Buscar sede destino..." />
				<select id="modalSedeDestinoSelect"></select>
			</div>

			<div id="modalChangeConflict" class="move-conflict-box">
				<div id="modalChangeConflictText"></div>
				<div class="radio-row">
					<label>
						<input type="radio" name="modoConflictoCambio" value="swap" checked />
						<span>Intercambiar coordinadores entre ambas sedes.</span>
					</label>
					<label>
						<input type="radio" name="modoConflictoCambio" value="replace" />
						<span>Asignar en esta sede y dejar la sede origen del coordinador sin asignar.</span>
					</label>
				</div>
			</div>

			<div id="modalMoveConflict" class="move-conflict-box">
				<div id="modalMoveConflictText"></div>
				<div class="radio-row">
					<label>
						<input type="radio" name="modoConflicto" value="swap" checked />
						<span>Intercambiar coordinadores entre ambas sedes.</span>
					</label>
					<label>
						<input type="radio" name="modoConflicto" value="replace" />
						<span>Mover el coordinador a la sede destino y dejar esta sede destino sin su coordinador actual.</span>
					</label>
				</div>
			</div>

			<div class="modal-actions">
				<button id="modalCancelarBtn" class="btn btn-secondary" type="button">Cancelar</button>
				<button id="modalAplicarBtn" class="btn btn-primary" type="button">Aplicar cambio</button>
			</div>
		</div>
	</div>

	<script>
		const state = {
			coordinadoresRaw: [],
			sedesRaw: [],
			coordinadores: [],
			sedes: [],
			asignaciones: [],
			asignacionResumen: null,
			filtroBusqueda: "",
			filtroEstado: "todos",
			editContext: null
		};

		const refs = {
			coordinadoresFile: document.getElementById("coordinadoresFile"),
			coordinadoresFileBtn: document.getElementById("coordinadoresFileBtn"),
			coordinadoresFileName: document.getElementById("coordinadoresFileName"),
			sedesFile: document.getElementById("sedesFile"),
			sedesFileBtn: document.getElementById("sedesFileBtn"),
			sedesFileName: document.getElementById("sedesFileName"),
			criterioPrioridad: document.getElementById("criterioPrioridad"),
			btnProcesar: document.getElementById("btnProcesar"),
			btnReasignar: document.getElementById("btnReasignar"),
			btnExportar: document.getElementById("btnExportar"),
			statusBox: document.getElementById("statusBox"),
			errorBox: document.getElementById("errorBox"),
			statCoordinadores: document.getElementById("statCoordinadores"),
			statSedes: document.getElementById("statSedes"),
			statAsignadas: document.getElementById("statAsignadas"),
			statSinAsignar: document.getElementById("statSinAsignar"),
			busqueda: document.getElementById("busqueda"),
			filtroEstado: document.getElementById("filtroEstado"),
			contadorResultados: document.getElementById("contadorResultados"),
			paginacionInfo: document.getElementById("paginacionInfo"),
			paginacionControles: document.getElementById("paginacionControles"),
			listaSedes: document.getElementById("listaSedes"),
			tablaBody: document.getElementById("tablaBody"),
			modalEditorAsignacion: document.getElementById("modalEditorAsignacion"),
			modalTitulo: document.getElementById("modalTitulo"),
			modalMetaSede: document.getElementById("modalMetaSede"),
			modalSeccionCambio: document.getElementById("modalSeccionCambio"),
			modalSeccionMover: document.getElementById("modalSeccionMover"),
			modalCoordinadorSearch: document.getElementById("modalCoordinadorSearch"),
			modalCoordinadorSelect: document.getElementById("modalCoordinadorSelect"),
			modalSedeDestinoSearch: document.getElementById("modalSedeDestinoSearch"),
			modalSedeDestinoSelect: document.getElementById("modalSedeDestinoSelect"),
			modalMoveConflict: document.getElementById("modalMoveConflict"),
			modalMoveConflictText: document.getElementById("modalMoveConflictText"),
			modalChangeConflict: document.getElementById("modalChangeConflict"),
			modalChangeConflictText: document.getElementById("modalChangeConflictText"),
			modalCancelarBtn: document.getElementById("modalCancelarBtn"),
			modalAplicarBtn: document.getElementById("modalAplicarBtn")
		};

		const ITEMS_POR_PAGINA = 50;
		let paginaActualSedes = 1;

		const GAM_CANTONES = {
			"san jose": [
				"san jose", "escazu", "desamparados", "aserri", "mora", "goicoechea", "santa ana", "alajuelita",
				"vazquez de coronado", "tibas", "moravia", "montes de oca", "curridabat"
			],
			"alajuela": ["alajuela", "atenas", "poas"],
			"cartago": ["cartago", "paraiso", "la union", "oreamuno", "alvarado", "el guarco"],
			"heredia": ["heredia", "barva", "santo domingo", "santa barbara", "san rafael", "san isidro", "belen", "flores", "san pablo"]
		};

		function norm(value) {
			return (value || "").toString().trim();
		}

		function normLower(value) {
			return norm(value).toLowerCase();
		}

		function sanitizeKey(key) {
			return normLower(key)
				.normalize("NFD")
				.replace(/[\u0300-\u036f]/g, "")
				.replace(/[^a-z0-9]+/g, " ")
				.trim();
		}

		function findField(row, candidates) {
			const entries = Object.entries(row || {});
			for (const [key, value] of entries) {
				const normalizedKey = sanitizeKey(key);
				for (const candidate of candidates) {
					if (normalizedKey.includes(sanitizeKey(candidate))) return value;
				}
			}
			return "";
		}

		function parseBooleanAnswer(value) {
			const v = sanitizeKey(value);
			if (!v) return false;
			if (v === "no" || v.startsWith("no ") || v.includes(" no ")) return false;
			return v === "si" || v === "s" || v === "yes" || v.includes(" si ") || v.startsWith("si ") || v.endsWith(" si");
		}

		function parseMulti(value) {
			const raw = norm(value).replace(/\u00a0/g, " ");
			if (!raw) return [];
			return raw
				.split(/\n|,|;/)
				.map(item => norm(item))
				.filter(Boolean);
		}

		function parseResidenceParts(value) {
			const raw = norm(value).replace(/\u00a0/g, " ");
			const parts = raw.split(",").map(v => norm(v));
			return {
				provincia: parts[0] || "",
				canton: parts[1] || "",
				distrito: parts[2] || ""
			};
		}

		function classifyCollaborationPreference(value) {
			const v = sanitizeKey(value);
			if (!v) return "ANY";
			if (v.includes("solo en el gran area metropolitana") || (v.includes("gran area metropolitana") && !v.includes("fuera"))) {
				return "GAM_ONLY";
			}
			if (v.includes("fuera del gran area metropolitana")) {
				return "OUTSIDE_GAM";
			}
			if (v.includes("sedes regionales") || v.includes("recintos") || v.includes("sus alrededores")) {
				return "REGIONAL_ONLY";
			}
			if (v.includes("cualquiera de las anteriores")) {
				return "ANY";
			}
			return "ANY";
		}

		function parseExperienceYears(value) {
			const raw = sanitizeKey(value);
			if (!raw) return 0;

			const numericMatch = raw.match(/\d+/);
			if (numericMatch) return Number(numericMatch[0]);

			if (raw.includes("mas de 8")) return 8;
			if (raw.includes("un ano") || raw.includes("uno")) return 1;
			if (raw.includes("dos")) return 2;
			if (raw.includes("tres")) return 3;
			if (raw.includes("cuatro")) return 4;
			if (raw.includes("cinco")) return 5;
			return 0;
		}

		function containsAny(text, terms) {
			const t = sanitizeKey(text);
			return terms.some(term => t.includes(term));
		}

		function buildGeoKey(provincia, canton) {
			return `${sanitizeKey(provincia)}|${sanitizeKey(canton)}`;
		}

		function isGamByProvinciaCanton(provincia, canton) {
			const prov = sanitizeKey(provincia);
			const cant = sanitizeKey(canton);
			const cantonesGam = GAM_CANTONES[prov] || [];
			return cantonesGam.includes(cant);
		}

		function isGradoElegible(grado) {
			const g = sanitizeKey(grado);
			return g.includes("doctorado") || g.includes("maestria") || g.includes("licenciatura");
		}

		function determineGamByText(text) {
			const t = sanitizeKey(text);
			const termsGam = [
				"san jose",
				"alajuela",
				"heredia",
				"cartago",
				"gran area metropolitana",
				"gam",
				"sabana",
				"plaza viquez",
				"teatro nacional",
				"goicoechea",
				"coronado",
				"curridabat",
				"desamparados",
				"moravia",
				"la union"
			];
			return termsGam.some(term => t.includes(term));
		}

		function inferProvinciaByText(text) {
			const t = sanitizeKey(text);
			const provincias = ["san jose", "alajuela", "cartago", "heredia", "guanacaste", "puntarenas", "limon"];
			for (const p of provincias) {
				if (t.includes(p)) return p;
			}
			return "";
		}

		function tokenSimilarity(aText, bText) {
			const stop = new Set(["de", "la", "el", "los", "las", "del", "y", "en", "con", "para", "por", "san", "sede", "liceo"]);
			const tokens = str => sanitizeKey(str)
				.split(" ")
				.filter(t => t.length >= 4 && !stop.has(t));

			const a = new Set(tokens(aText));
			const b = new Set(tokens(bText));
			if (!a.size || !b.size) return 0;

			let intersection = 0;
			a.forEach(token => {
				if (b.has(token)) intersection += 1;
			});

			return intersection;
		}

		function normalizeCoordinador(row, index) {
			const nombre = norm(findField(row, ["nombre completo", "nombre y apellidos", "nombre"]));
			const correo = norm(findField(row, ["correo electronico", "correo", "email"]));
			const identificacion = norm(findField(row, ["numero de identificacion", "identificacion", "cedula"]));
			const direccion = norm(findField(row, ["direccion de residencia", "residencia", "provincia canton distrito"]));
			const telefono = norm(findField(row, ["numero de telefono", "telefono"]));
			const oficina = norm(findField(row, ["telefono de oficina", "numero de telefono de oficina"]));
			const grado = norm(findField(row, ["grado academico", "grado"]));
			const lugarTrabajo = norm(findField(row, ["lugar de trabajo", "trabajo"]));
			const tipoNombramiento = norm(findField(row, ["tipo de nombramiento"]));
			const dispParticipar = parseBooleanAnswer(findField(row, ["disposicion de participar", "esta en disposicion"]));
			const turnos = parseMulti(findField(row, ["marque los turnos", "turnos en los que esta dispuesto"]));
			const disponibilidadZona = norm(findField(row, ["esta dispuesto a colaborar", "gran area metropolitana", "sedes regionales"]));
			const tienePariente = parseBooleanAnswer(findField(row, ["tiene algun pariente", "pariente cercano", "mismo domicilio"]));
			const nombrePariente = norm(findField(row, ["nombre completo de la persona que realizara", "nombre completo de la persona"]));
			const tieneVehiculo = parseBooleanAnswer(findField(row, ["disponibilidad para manejar vehiculo institucional", "manejar vehiculo institucional"]));
			const tienePermiso = parseBooleanAnswer(findField(row, ["cuenta con permiso institucional", "permiso institucional para manejar"]));
			const recomienda = parseBooleanAnswer(findField(row, ["desea recomendar alguna persona", "recomendar alguna persona"]));
			const aniosApoyo = parseExperienceYears(findField(row, ["cantidad de anos", "ha colaborado como aplicadora de apoyo", "anos que esta persona"]));
			const observaciones = norm(findField(row, ["observacion", "comentario"]));
			const residenciaParts = parseResidenceParts(direccion);
			const preferenciaColaboracion = classifyCollaborationPreference(disponibilidadZona);

			return {
				idInterno: `coord-${index + 1}`,
				// Id de la fila en la base, cuando los datos vinieron de ahí.
				// Es lo que permite guardar la asignación apuntando a la
				// persona y no solo a su nombre escrito.
				idBase: row.id ?? null,
				nombre,
				correo,
				identificacion,
				direccion,
				telefono,
				oficina,
				grado,
				lugarTrabajo,
				tipoNombramiento,
				dispParticipar,
				turnos,
				disponibilidadZona,
				preferenciaColaboracion,
				residenciaParts,
				tienePariente,
				nombrePariente,
				tieneVehiculo,
				tienePermiso,
				recomienda,
				aniosApoyo,
				observaciones,
				elegiblePorGrado: isGradoElegible(grado)
			};
		}

		function normalizeSede(row, index) {
			const codigo = norm(findField(row, ["idsede", "id sede", "codigo sede", "numero sede", "codigo", "id"]));
			const nombreSede = norm(findField(row, ["nombresede", "nombre sede", "sede nombre", "nombre de sede"]));
			const nombreGenerico = norm(findField(row, ["nombre"]));
			const nombre = nombreSede || (sanitizeKey(nombreGenerico) === sanitizeKey(codigo) ? "" : nombreGenerico);
			const direccion = norm(findField(row, ["direccion", "ubicacion"]));
			const provinciaRaw = norm(findField(row, ["provincia"]));
			const canton = norm(findField(row, ["canton"]));
			const distrito = norm(findField(row, ["distrito"]));
			const region = norm(findField(row, ["region", "recinto"]));
			const gamColumn = norm(findField(row, ["gam", "area metropolitana", "gran area metropolitana", "dentro del area metropolitana"]));
			const provincia = provinciaRaw || inferProvinciaByText(`${direccion} ${region} ${nombre}`);
			const gamFromColumn = gamColumn ? parseBooleanAnswer(gamColumn) : null;
			const isGamDetected = isGamByProvinciaCanton(provincia, canton) || determineGamByText(`${provincia} ${canton} ${distrito} ${direccion} ${region} ${nombre}`);

			return {
				idInterno: `sede-${index + 1}`,
				codigo,
				nombre: nombre || `Sede ${index + 1}`,
				direccion,
				provincia,
				canton,
				distrito,
				region,
				isGam: gamFromColumn === null ? isGamDetected : gamFromColumn
			};
		}

		async function parseWorkbook(file) {
			const buffer = await file.arrayBuffer();
			const workbook = XLSX.read(buffer, { type: "array" });
			const firstSheet = workbook.SheetNames[0];
			const ws = workbook.Sheets[firstSheet];
			return XLSX.utils.sheet_to_json(ws, { defval: "" });
		}

		function normalizePrioridadValue(value) {
			const v = sanitizeKey(value);
			if (!v) return "";

			if (
				v.includes("ubicacion") ||
				v.includes("geograf") ||
				v.includes("cercania") ||
				v.includes("distancia") ||
				v.includes("residencia")
			) {
				return "ubicacion";
			}

			if (
				v.includes("disponibilidad") ||
				v.includes("turnos") ||
				v.includes("horario") ||
				v.includes("jornada")
			) {
				return "disponibilidad";
			}

			if (
				v.includes("balanceado") ||
				v.includes("equilibrado") ||
				v.includes("mixto") ||
				v.includes("general")
			) {
				return "balanceado";
			}

			return "";
		}

		function hasAnyFieldInRows(rows, candidates) {
			if (!Array.isArray(rows) || !rows.length) return false;
			for (const row of rows) {
				const keys = Object.keys(row || {});
				for (const key of keys) {
					const normalizedKey = sanitizeKey(key);
					for (const candidate of candidates) {
						if (normalizedKey.includes(sanitizeKey(candidate))) {
							return true;
						}
					}
				}
			}
			return false;
		}

		function getPrioridadVotes(rows) {
			const votes = { balanceado: 0, ubicacion: 0, disponibilidad: 0 };
			if (!Array.isArray(rows) || !rows.length) return votes;

			for (const row of rows) {
				for (const [rawKey, rawValue] of Object.entries(row || {})) {
					const key = sanitizeKey(rawKey);
					if (!key.includes("prioridad") && !key.includes("criterio")) continue;

					const normalized = normalizePrioridadValue(rawValue);
					if (!normalized) continue;
					votes[normalized] += 1;
				}
			}

			return votes;
		}

		function pickTopPriority(votes) {
			const priorities = ["balanceado", "ubicacion", "disponibilidad"];
			let top = "";
			let topCount = 0;

			for (const p of priorities) {
				if ((votes[p] || 0) > topCount) {
					top = p;
					topCount = votes[p] || 0;
				}
			}

			return { top, topCount };
		}

		function detectPrioridadAsignacionFromFiles(coordinadoresRows, sedesRows) {
			const votesCoord = getPrioridadVotes(coordinadoresRows);
			const votesSedes = getPrioridadVotes(sedesRows);
			const combined = {
				balanceado: (votesCoord.balanceado || 0) + (votesSedes.balanceado || 0),
				ubicacion: (votesCoord.ubicacion || 0) + (votesSedes.ubicacion || 0),
				disponibilidad: (votesCoord.disponibilidad || 0) + (votesSedes.disponibilidad || 0)
			};

			const fromCombined = pickTopPriority(combined);
			if (fromCombined.topCount > 0) {
				return {
					value: fromCombined.top,
					source: "columnas de prioridad en los archivos"
				};
			}

			const hasGeoSedes = hasAnyFieldInRows(sedesRows, [
				"provincia", "canton", "distrito", "direccion", "ubicacion", "region", "gam", "area metropolitana"
			]);
			const hasCoordsGeo = hasAnyFieldInRows(coordinadoresRows, [
				"direccion de residencia", "residencia", "provincia canton distrito", "lugar de trabajo"
			]);
			const hasTurnos = hasAnyFieldInRows(coordinadoresRows, [
				"turnos", "marque los turnos", "disponibilidad", "horario"
			]);

			if (hasGeoSedes && hasCoordsGeo && hasTurnos) {
				return {
					value: "balanceado",
					source: "estructura de columnas de sedes y coordinadores"
				};
			}

			if (hasGeoSedes && hasCoordsGeo && !hasTurnos) {
				return {
					value: "ubicacion",
					source: "datos geograficos detectados en ambos archivos"
				};
			}

			if (hasTurnos && !(hasGeoSedes && hasCoordsGeo)) {
				return {
					value: "disponibilidad",
					source: "datos de turnos/disponibilidad detectados"
				};
			}

			return {
				value: "balanceado",
				source: "valor por defecto"
			};
		}

		function updateStatus(message) {
			refs.statusBox.textContent = message;
		}

		function showError(message) {
			refs.errorBox.style.display = "block";
			refs.errorBox.textContent = message;
		}

		function clearError() {
			refs.errorBox.style.display = "none";
			refs.errorBox.textContent = "";
		}

		function scoreCoordinatorForSede(coord, sede, prioridad) {
			let score = 0;
			const reasons = [];

			if (!coord.dispParticipar) {
				return { score: -9999, reasons: ["No disponible para participar este ano"] };
			}

			if (!coord.elegiblePorGrado) {
				score -= 140;
				reasons.push("Grado academico menor al minimo recomendado");
			} else {
				score += 22;
				reasons.push("Cumple grado academico");
			}

			const zona = sanitizeKey(coord.disponibilidadZona);
			if (zona.includes("cualquiera")) {
				score += 22;
				reasons.push("Acepta cualquier zona");
			} else if (zona.includes("gran area metropolitana") || zona.includes("solo en el gran area metropolitana")) {
				if (sede.isGam) {
					score += 30;
					reasons.push("Preferencia GAM compatible");
				} else {
					score -= 26;
					reasons.push("Prefiere GAM y la sede parece fuera de GAM");
				}
			} else if (zona.includes("fuera del gran area metropolitana") || zona.includes("sedes regionales") || zona.includes("recintos")) {
				if (!sede.isGam) {
					score += 30;
					reasons.push("Preferencia regional compatible");
				} else {
					score -= 22;
					reasons.push("Prefiere regional y la sede parece GAM");
				}
			}

			const residencia = sanitizeKey(coord.direccion);
			const lugarTrabajo = sanitizeKey(coord.lugarTrabajo);
			const geoSede = sanitizeKey(`${sede.provincia} ${sede.canton} ${sede.distrito} ${sede.direccion} ${sede.nombre} ${sede.region}`);

			if (residencia && geoSede) {
				if (sede.distrito && residencia.includes(sanitizeKey(sede.distrito))) {
					score += 30;
					reasons.push("Coincidencia por distrito");
				} else if (sede.canton && residencia.includes(sanitizeKey(sede.canton))) {
					score += 20;
					reasons.push("Coincidencia por canton");
				} else if (sede.provincia && residencia.includes(sanitizeKey(sede.provincia))) {
					score += 12;
					reasons.push("Coincidencia por provincia");
				}
			}

			const overlapResidencia = tokenSimilarity(residencia, geoSede);
			if (overlapResidencia > 0) {
				score += Math.min(24, overlapResidencia * 6);
				reasons.push("Afinidad textual por direccion de residencia y sede");
			}

			const overlapTrabajo = tokenSimilarity(lugarTrabajo, geoSede);
			if (overlapTrabajo > 0) {
				score += Math.min(18, overlapTrabajo * 6);
				reasons.push("Afinidad textual por lugar de trabajo y sede");
			}

			const turnosCount = coord.turnos.length;
			if (turnosCount >= 6 || containsAny(coord.turnos.join(" "), ["todas las opciones", "todas"])) {
				score += 16;
				reasons.push("Alta disponibilidad de turnos");
			} else if (turnosCount >= 3) {
				score += 8;
				reasons.push("Disponibilidad media de turnos");
			} else if (turnosCount > 0) {
				score += 3;
			}

			if (coord.tienePariente) {
				score -= 35;
				reasons.push("Declara pariente o conviviente aplicando PAA");
			}

			if (coord.tieneVehiculo) {
				score += 8;
				reasons.push("Puede manejar vehiculo institucional");
			}

			if (coord.tienePermiso) {
				score += 7;
				reasons.push("Tiene permiso institucional para manejar");
			}

			if (coord.aniosApoyo > 0) {
				score += Math.min(20, coord.aniosApoyo * 2);
				reasons.push("Experiencia como aplicador de apoyo");
			}

			if (prioridad === "ubicacion") {
				if (residencia && geoSede && (residencia.includes(sanitizeKey(sede.provincia)) || residencia.includes(sanitizeKey(sede.canton)))) {
					score += 16;
					reasons.push("Ajuste por prioridad de ubicacion");
				}
			} else if (prioridad === "disponibilidad") {
				score += Math.min(16, turnosCount * 2);
				reasons.push("Ajuste por prioridad de disponibilidad");
			}

			return { score, reasons };
		}

		function isHardEligibleForSede(coord, sede, strictMode = true) {
			if (!coord.dispParticipar) {
				return { ok: false, reason: "No disponible para participar" };
			}

			if (!coord.elegiblePorGrado) {
				return { ok: false, reason: "No cumple grado academico minimo" };
			}

			const pref = coord.preferenciaColaboracion;
			const sameProvince = sanitizeKey(coord.residenciaParts.provincia) && sanitizeKey(coord.residenciaParts.provincia) === sanitizeKey(sede.provincia);

			if (pref === "GAM_ONLY" && !sede.isGam) {
				return { ok: false, reason: "Prefiere solo GAM" };
			}

			if (pref === "OUTSIDE_GAM" && sede.isGam) {
				return { ok: false, reason: "Prefiere fuera de GAM" };
			}

			if (pref === "REGIONAL_ONLY" && sede.isGam) {
				return { ok: false, reason: "Prefiere sedes regionales" };
			}

			if (strictMode && (pref === "OUTSIDE_GAM" || pref === "REGIONAL_ONLY") && !sameProvince) {
				return { ok: false, reason: "Fuera GAM/Regional exige misma provincia en modo estricto" };
			}

			return { ok: true, reason: "Elegible" };
		}

		function getCoordinatorClusterPenalty(coord, sede, asignacionesPorCoordinador) {
			const current = asignacionesPorCoordinador.get(coord.idInterno) || [];
			if (!current.length) return 0;

			const provinciaSede = sanitizeKey(sede.provincia);
			const cantonSede = sanitizeKey(sede.canton);
			const distritoSede = sanitizeKey(sede.distrito);

			const hasDistrito = current.some(s => sanitizeKey(s.distrito) && sanitizeKey(s.distrito) === distritoSede);
			if (hasDistrito) return 10;

			const hasCanton = current.some(s => sanitizeKey(s.canton) && sanitizeKey(s.canton) === cantonSede);
			if (hasCanton) return 6;

			const hasProvincia = current.some(s => sanitizeKey(s.provincia) && sanitizeKey(s.provincia) === provinciaSede);
			if (hasProvincia) return 3;

			return -18;
		}

		function scoreWithLoadAndProximity(coord, sede, prioridad, asignacionesPorCoordinador, cargaActual, cargaObjetivo) {
			const base = scoreCoordinatorForSede(coord, sede, prioridad);
			let score = base.score;
			const reasons = [...base.reasons];

			const residenciaProv = sanitizeKey(coord.residenciaParts.provincia);
			const residenciaCant = sanitizeKey(coord.residenciaParts.canton);
			const residenciaDist = sanitizeKey(coord.residenciaParts.distrito);
			const sedeProv = sanitizeKey(sede.provincia);
			const sedeCant = sanitizeKey(sede.canton);
			const sedeDist = sanitizeKey(sede.distrito);

			if (residenciaDist && sedeDist && residenciaDist === sedeDist) {
				score += 40;
				reasons.push("Misma residencia por distrito");
			} else if (residenciaCant && sedeCant && residenciaCant === sedeCant) {
				score += 28;
				reasons.push("Misma residencia por canton");
			} else if (residenciaProv && sedeProv && residenciaProv === sedeProv) {
				score += 16;
				reasons.push("Misma residencia por provincia");
			} else {
				score -= 14;
			}

			const bonusCluster = getCoordinatorClusterPenalty(coord, sede, asignacionesPorCoordinador);
			score += bonusCluster;
			if (bonusCluster > 0) {
				reasons.push("Sede cercana a otras ya asignadas al coordinador");
			} else if (bonusCluster < 0) {
				reasons.push("Sede lejana respecto a otras del mismo coordinador");
			}

			const carga = cargaActual.get(coord.idInterno) || 0;
			score -= carga * 7;
			if (carga < cargaObjetivo) {
				score += 10;
				reasons.push(`Balance de carga favorable (carga actual ${carga}, objetivo ${cargaObjetivo})`);
			} else {
				reasons.push(`Carga actual del coordinador: ${carga} sede(s), objetivo aproximado: ${cargaObjetivo}`);
			}

			return { score, reasons };
		}

		function rebalancearCoberturaMinima(asignaciones, prioridad, cargaActual, asignacionesPorCoordinador, cargaObjetivo) {
			const totalCoordinadores = state.coordinadores.length;
			const totalSedes = state.sedes.length;

			const result = {
				fullCoveragePossible: totalSedes >= totalCoordinadores,
				usedBefore: 0,
				usedAfter: 0,
				reassignedCount: 0,
				missingCount: 0,
				message: ""
			};

			const countUsed = () => state.coordinadores.filter(c => (cargaActual.get(c.idInterno) || 0) > 0).length;
			const getWithoutSede = () => state.coordinadores.filter(c => (cargaActual.get(c.idInterno) || 0) === 0);

			result.usedBefore = countUsed();

			if (!result.fullCoveragePossible) {
				result.usedAfter = result.usedBefore;
				result.missingCount = Math.max(0, totalCoordinadores - result.usedAfter);
				result.message = "No es posible usar a todos los coordinadores porque hay menos sedes que coordinadores.";
				return result;
			}

			let guard = 0;
			const guardMax = Math.max(30, totalCoordinadores * 4);

			while (getWithoutSede().length > 0 && guard < guardMax) {
				const targetCoord = getWithoutSede()[0];
				let bestMove = null;

				for (const item of asignaciones) {
					if (!item.coordinador) continue;
					const donorCoord = item.coordinador;
					const donorLoad = cargaActual.get(donorCoord.idInterno) || 0;
					if (donorLoad <= 1) continue;

					const enriched = scoreWithLoadAndProximity(
						targetCoord,
						item.sede,
						prioridad,
						asignacionesPorCoordinador,
						cargaActual,
						cargaObjetivo
					);

					let moveScore = enriched.score - 28;
					moveScore += donorLoad * 3;

					if (!bestMove || moveScore > bestMove.moveScore) {
						bestMove = {
							item,
							donorCoord,
							donorLoad,
							targetCoord,
							moveScore,
							reasons: enriched.reasons
						};
					}
				}

				if (!bestMove) break;

				const donorId = bestMove.donorCoord.idInterno;
				const targetId = bestMove.targetCoord.idInterno;

				cargaActual.set(donorId, Math.max(0, (cargaActual.get(donorId) || 0) - 1));
				cargaActual.set(targetId, (cargaActual.get(targetId) || 0) + 1);

				const donorList = asignacionesPorCoordinador.get(donorId) || [];
				const donorIdx = donorList.findIndex(s => s.idInterno === bestMove.item.sede.idInterno);
				if (donorIdx >= 0) donorList.splice(donorIdx, 1);
				asignacionesPorCoordinador.set(donorId, donorList);

				const targetList = asignacionesPorCoordinador.get(targetId) || [];
				targetList.push(bestMove.item.sede);
				asignacionesPorCoordinador.set(targetId, targetList);

				bestMove.item.coordinador = bestMove.targetCoord;
				bestMove.item.estado = "asignada";
				bestMove.item.score = bestMove.moveScore;
				bestMove.item.reasons = [
					"Rebalanceo de cobertura: se reasigna esta sede para que todos los coordinadores tengan al menos una asignacion.",
					`Sede transferida desde ${bestMove.donorCoord.nombre || "coordinador previo"} (carga previa ${bestMove.donorLoad}) hacia ${bestMove.targetCoord.nombre || "coordinador"}.`,
					...bestMove.reasons,
					`Puntaje de rebalanceo aplicado: ${Math.round(bestMove.moveScore)}.`
				];

				result.reassignedCount += 1;
				guard += 1;
			}

			result.usedAfter = countUsed();
			result.missingCount = Math.max(0, totalCoordinadores - result.usedAfter);

			if (result.missingCount === 0) {
				result.message = "Cobertura completa de coordinadores lograda (todos tienen al menos una sede).";
			} else {
				result.message = "No se pudo completar cobertura total de coordinadores con el rebalanceo disponible.";
			}

			return result;
		}

		function calcularAsignaciones() {
			const prioridad = refs.criterioPrioridad.value;
			const cargaActual = new Map();
			const asignacionesPorCoordinador = new Map();
			state.coordinadores.forEach(c => {
				cargaActual.set(c.idInterno, 0);
				asignacionesPorCoordinador.set(c.idInterno, []);
			});

			const cargaObjetivo = state.coordinadores.length
				? Math.ceil(state.sedes.length / state.coordinadores.length)
				: 0;
			const cargaPreferidaMax = Math.max(1, cargaObjetivo);
			const cargaToleradaEstricto = Math.max(cargaPreferidaMax + 1, 2);
			const cargaToleradaRelajado = cargaToleradaEstricto + 2;

			const sedesConDificultad = state.sedes.map(sede => {
				const elegiblesEstricto = state.coordinadores.filter(coord => isHardEligibleForSede(coord, sede, true).ok).length;
				return { sede, elegiblesEstricto };
			}).sort((a, b) => a.elegiblesEstricto - b.elegiblesEstricto);

			const asignaciones = [];

			const construirCandidatos = (sede, etapa) => {
				const candidatos = [];
				for (const coord of state.coordinadores) {
					const carga = cargaActual.get(coord.idInterno) || 0;

					if (etapa === "estricto") {
						const hard = isHardEligibleForSede(coord, sede, true);
						if (!hard.ok) continue;
						if (carga >= cargaToleradaEstricto) continue;

						const enriched = scoreWithLoadAndProximity(coord, sede, prioridad, asignacionesPorCoordinador, cargaActual, cargaObjetivo);
						let score = enriched.score;
						const reasons = [
							"Etapa estricta: se respetan preferencias declaradas y cercania.",
							...enriched.reasons
						];
						if (carga >= cargaPreferidaMax) {
							score -= (carga - cargaPreferidaMax + 1) * 14;
							reasons.push(`Penalizacion por sobrecarga en etapa estricta (carga ${carga}, preferida <= ${cargaPreferidaMax}).`);
						}
						candidatos.push({ coordinador: coord, score, reasons, etapa });
						continue;
					}

					if (etapa === "relajado") {
						const hardRelaxed = isHardEligibleForSede(coord, sede, false);
						if (!hardRelaxed.ok) continue;
						if (carga >= cargaToleradaRelajado) continue;

						const enriched = scoreWithLoadAndProximity(coord, sede, prioridad, asignacionesPorCoordinador, cargaActual, cargaObjetivo);
						let score = enriched.score - 16;
						const reasons = [
							"Etapa relajada: se flexibilizan algunas restricciones para evitar sedes sin coordinador.",
							...enriched.reasons,
							"Penalizacion de flexibilidad aplicada por falta de candidato estricto."
						];
						if (carga >= cargaPreferidaMax) {
							score -= (carga - cargaPreferidaMax + 1) * 10;
							reasons.push(`Penalizacion por sobrecarga en etapa relajada (carga ${carga}, preferida <= ${cargaPreferidaMax}).`);
						}
						candidatos.push({ coordinador: coord, score, reasons, etapa });
						continue;
					}

					if (etapa === "fallback") {
						if (!coord.dispParticipar) continue;

						const enriched = scoreWithLoadAndProximity(coord, sede, prioridad, asignacionesPorCoordinador, cargaActual, cargaObjetivo);
						let score = enriched.score - 34;
						const reasons = [
							"Etapa fallback: se prioriza no dejar sede sin coordinador.",
							...enriched.reasons,
							"Se acepta menor afinidad por falta de opciones en etapas anteriores."
						];
						if (carga >= cargaPreferidaMax) {
							score -= (carga - cargaPreferidaMax + 1) * 8;
							reasons.push(`Penalizacion por sobrecarga en fallback (carga ${carga}, preferida <= ${cargaPreferidaMax}).`);
						}
						candidatos.push({ coordinador: coord, score, reasons, etapa });
						continue;
					}

					if (etapa === "emergencia") {
						const enriched = scoreWithLoadAndProximity(coord, sede, prioridad, asignacionesPorCoordinador, cargaActual, cargaObjetivo);
						let score = enriched.score - 60;
						const reasons = [
							"Etapa de emergencia: no hubo candidatos disponibles en etapas previas.",
							...enriched.reasons,
							"Asignacion forzada para cubrir la sede.",
							coord.dispParticipar ? "El coordinador indico disponibilidad general." : "El coordinador no indico disponibilidad clara; asignacion excepcional."
						];
						if (carga >= cargaPreferidaMax) {
							score -= (carga - cargaPreferidaMax + 1) * 6;
							reasons.push(`Penalizacion por sobrecarga en emergencia (carga ${carga}, preferida <= ${cargaPreferidaMax}).`);
						}
						candidatos.push({ coordinador: coord, score, reasons, etapa });
					}
				}
				return candidatos;
			};

			for (const item of sedesConDificultad) {
				const sede = item.sede;
				let candidatos = construirCandidatos(sede, "estricto");
				if (!candidatos.length) candidatos = construirCandidatos(sede, "relajado");
				if (!candidatos.length) candidatos = construirCandidatos(sede, "fallback");
				if (!candidatos.length) candidatos = construirCandidatos(sede, "emergencia");

				candidatos.sort((a, b) => b.score - a.score);
				const elegido = candidatos[0] || null;

				if (elegido) {
					const id = elegido.coordinador.idInterno;
					const nuevaCarga = (cargaActual.get(id) || 0) + 1;
					cargaActual.set(id, nuevaCarga);
					asignacionesPorCoordinador.get(id).push(sede);

					elegido.reasons = [
						...elegido.reasons,
						`Resultado final: coordinador asignado en etapa ${elegido.etapa} con puntaje ${Math.round(elegido.score)}.`,
						`Carga del coordinador despues de asignar esta sede: ${nuevaCarga}.`
					];
				}

				asignaciones.push({
					sede,
					coordinador: elegido?.coordinador || null,
					score: elegido?.score ?? null,
					reasons: elegido?.reasons || [
						"No se encontro ningun candidato, incluso en etapa de emergencia.",
						"Verifique disponibilidad declarada, datos de residencia y campos de sedes."
					],
					estado: elegido ? "asignada" : "sin-asignar"
				});
			}

			const resumenCobertura = rebalancearCoberturaMinima(
				asignaciones,
				prioridad,
				cargaActual,
				asignacionesPorCoordinador,
				cargaObjetivo
			);
			state.asignacionResumen = resumenCobertura;

			state.asignaciones = asignaciones.sort((a, b) => a.sede.nombre.localeCompare(b.sede.nombre, "es"));
			paginaActualSedes = 1;
		}

		function filtrarAsignaciones() {
			const term = sanitizeKey(state.filtroBusqueda);
			return state.asignaciones.filter(item => {
				if (state.filtroEstado === "asignadas" && item.estado !== "asignada") return false;
				if (state.filtroEstado === "sin-asignar" && item.estado !== "sin-asignar") return false;

				if (!term) return true;

				const target = sanitizeKey([
					item.sede.nombre,
					item.sede.direccion,
					item.sede.provincia,
					item.sede.canton,
					item.sede.distrito,
					item.coordinador?.nombre || "",
					item.coordinador?.correo || ""
				].join(" "));

				return target.includes(term);
			});
		}

		function escapeHtml(text) {
			return norm(text)
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/\"/g, "&quot;")
				.replace(/'/g, "&#039;");
		}

		function getAsignacionBySedeId(sedeId) {
			return state.asignaciones.find(item => item.sede.idInterno === sedeId) || null;
		}

		function getAsignacionByCoordinatorId(coordId, excludedSedeId = "") {
			return state.asignaciones.find(item => {
				if (!item.coordinador) return false;
				if (excludedSedeId && item.sede.idInterno === excludedSedeId) return false;
				return item.coordinador.idInterno === coordId;
			}) || null;
		}

		function getAsignacionLabel(item) {
			if (!item) return "";
			const codigo = item.sede.codigo || "N/A";
			return `${codigo} - ${item.sede.nombre}`;
		}

		function setAsignacionManual(item, coordinador, detalle) {
			item.coordinador = coordinador || null;
			item.estado = coordinador ? "asignada" : "sin-asignar";
			item.score = null;
			item.reasons = [detalle];
		}

		function renderCoordinadorOptionsForEditor(sedeId, searchTerm = "") {
			const item = getAsignacionBySedeId(sedeId);
			if (!item) return;

			const currentCoordId = item.coordinador?.idInterno || "";
			const term = sanitizeKey(searchTerm);
			const coordinadoresOrdenados = state.coordinadores
				.slice()
				.sort((a, b) => (a.nombre || "").localeCompare(b.nombre || "", "es", { sensitivity: "base" }));

			const disponibles = [];
			const ocupados = [];
			for (const coord of coordinadoresOrdenados) {
				const asignacionActual = getAsignacionByCoordinatorId(coord.idInterno, sedeId);
				const isOcupado = Boolean(asignacionActual);
				const baseLabel = `${coord.nombre || "Sin nombre"} (${coord.correo || "sin correo"})`;
				const fullLabel = isOcupado
					? `${baseLabel} | Asignado en ${getAsignacionLabel(asignacionActual)}`
					: `${baseLabel} | Disponible`;

				if (term && !sanitizeKey(fullLabel).includes(term)) continue;

				const row = {
					coordId: coord.idInterno,
					label: fullLabel,
					isOcupado,
					asignacionActual
				};

				if (isOcupado && coord.idInterno !== currentCoordId) {
					ocupados.push(row);
				} else {
					disponibles.push(row);
				}
			}

			const options = [`<option value="">-- Buscar y seleccionar coordinador --</option>`];
			if (disponibles.length) {
				options.push(`<optgroup label="Disponibles o actual">`);
				for (const row of disponibles) {
					const selected = row.coordId === currentCoordId ? "selected" : "";
					options.push(`<option value="${row.coordId}" ${selected}>${escapeHtml(row.label)}</option>`);
				}
				options.push(`</optgroup>`);
			}

			if (ocupados.length) {
				options.push(`<optgroup label="Asignados en otras sedes (intercambio o reemplazo)">`);
				for (const row of ocupados) {
					options.push(`<option value="${row.coordId}">${escapeHtml(row.label)}</option>`);
				}
				options.push(`</optgroup>`);
			}

			refs.modalCoordinadorSelect.innerHTML = options.join("");
		}

		function renderSedeDestinoOptions(searchTerm = "") {
			const context = state.editContext;
			if (!context || context.mode !== "move") return;
			const sedeId = context.sedeId;
			const term = sanitizeKey(searchTerm);

			const options = [`<option value="">-- Buscar y seleccionar sede destino --</option>`];
			const rows = state.asignaciones
				.filter(entry => entry.sede.idInterno !== sedeId)
				.sort((a, b) => getAsignacionLabel(a).localeCompare(getAsignacionLabel(b), "es", { sensitivity: "base", numeric: true }));

			for (const entry of rows) {
				const ocupado = entry.coordinador ? ` | ${entry.coordinador.nombre || "Sin nombre"}` : " | Sin asignar";
				const label = getAsignacionLabel(entry) + ocupado;
				if (term && !sanitizeKey(label).includes(term)) continue;
				options.push(`<option value="${entry.sede.idInterno}">${escapeHtml(label)}</option>`);
			}

			refs.modalSedeDestinoSelect.innerHTML = options.join("");
		}

		function updateChangeConflictUi() {
			const context = state.editContext;
			if (!context || context.mode !== "change") return;

			const selectedCoordId = refs.modalCoordinadorSelect.value;
			if (!selectedCoordId) {
				refs.modalChangeConflict.classList.remove("active");
				refs.modalChangeConflictText.textContent = "";
				return;
			}

			const conflictItem = getAsignacionByCoordinatorId(selectedCoordId, context.sedeId);
			if (!conflictItem) {
				refs.modalChangeConflict.classList.remove("active");
				refs.modalChangeConflictText.textContent = "";
				return;
			}

			refs.modalChangeConflict.classList.add("active");
			refs.modalChangeConflictText.textContent = `El coordinador seleccionado ya esta asignado en ${getAsignacionLabel(conflictItem)}.`;
			const defaultRadio = refs.modalChangeConflict.querySelector('input[name="modoConflictoCambio"][value="swap"]');
			if (defaultRadio) defaultRadio.checked = true;
		}

		function abrirEditorAsignacion(sedeId) {
			clearError();
			const item = getAsignacionBySedeId(sedeId);
			if (!item) return;

			state.editContext = { mode: "change", sedeId };
			refs.modalTitulo.textContent = "Cambiar coordinador de la sede";
			refs.modalMetaSede.textContent = `Sede: ${getAsignacionLabel(item)}\nActual: ${item.coordinador?.nombre || "Sin asignar"}`;
			refs.modalSeccionCambio.style.display = "flex";
			refs.modalSeccionMover.style.display = "none";
			refs.modalMoveConflict.classList.remove("active");
			refs.modalChangeConflict.classList.remove("active");
			refs.modalCoordinadorSearch.value = "";
			renderCoordinadorOptionsForEditor(sedeId, "");
			refs.modalEditorAsignacion.classList.add("open");
			refs.modalEditorAsignacion.setAttribute("aria-hidden", "false");
		}

		function updateMoveConflictUi() {
			const context = state.editContext;
			if (!context || context.mode !== "move") return;

			const destinationId = refs.modalSedeDestinoSelect.value;
			const destinationItem = getAsignacionBySedeId(destinationId);
			if (!destinationItem || !destinationItem.coordinador) {
				refs.modalMoveConflict.classList.remove("active");
				refs.modalMoveConflictText.textContent = "";
				return;
			}

			refs.modalMoveConflict.classList.add("active");
			refs.modalMoveConflictText.textContent = `La sede destino ya tiene coordinador: ${destinationItem.coordinador.nombre || "Sin nombre"}. Elija como resolver el cambio.`;

			const defaultRadio = refs.modalMoveConflict.querySelector('input[name="modoConflicto"][value="swap"]');
			if (defaultRadio) defaultRadio.checked = true;
		}

		function abrirMoverCoordinador(sedeId) {
			clearError();
			const item = getAsignacionBySedeId(sedeId);
			if (!item || !item.coordinador) {
				showError("La sede seleccionada no tiene coordinador asignado para mover.");
				return;
			}

			state.editContext = { mode: "move", sedeId };
			refs.modalTitulo.textContent = "Mover coordinador a otra sede";
			refs.modalMetaSede.textContent = `Origen: ${getAsignacionLabel(item)}\nCoordinador: ${item.coordinador.nombre || "Sin nombre"}`;
			refs.modalSeccionCambio.style.display = "none";
			refs.modalSeccionMover.style.display = "flex";
			refs.modalChangeConflict.classList.remove("active");
			refs.modalSedeDestinoSearch.value = "";
			renderSedeDestinoOptions("");
			refs.modalMoveConflict.classList.remove("active");
			refs.modalMoveConflictText.textContent = "";

			refs.modalEditorAsignacion.classList.add("open");
			refs.modalEditorAsignacion.setAttribute("aria-hidden", "false");
		}

		function cerrarEditorManual() {
			state.editContext = null;
			refs.modalCoordinadorSearch.value = "";
			refs.modalSedeDestinoSearch.value = "";
			refs.modalChangeConflict.classList.remove("active");
			refs.modalMoveConflict.classList.remove("active");
			refs.modalEditorAsignacion.classList.remove("open");
			refs.modalEditorAsignacion.setAttribute("aria-hidden", "true");
		}

		function aplicarCambioManual() {
			const context = state.editContext;
			if (!context) return;

			const origen = getAsignacionBySedeId(context.sedeId);
			if (!origen) {
				cerrarEditorManual();
				return;
			}

			if (context.mode === "change") {
				const selectedCoordId = refs.modalCoordinadorSelect.value;
				const selectedCoord = state.coordinadores.find(coord => coord.idInterno === selectedCoordId) || null;
				const currentCoordId = origen.coordinador?.idInterno || "";

				if ((selectedCoord?.idInterno || "") === currentCoordId) {
					cerrarEditorManual();
					return;
				}

				if (!selectedCoord) {
					setAsignacionManual(origen, null, "Cambio manual: sede marcada como sin asignar.");
					renderAll();
					updateStatus("Cambio manual aplicado. Puede seguir editando asignaciones antes de exportar.");
					cerrarEditorManual();
					return;
				}

				const conflictItem = getAsignacionByCoordinatorId(selectedCoord.idInterno, context.sedeId);
				if (conflictItem) {
					const modeRadio = refs.modalChangeConflict.querySelector('input[name="modoConflictoCambio"]:checked');
					const mode = modeRadio?.value || "swap";
					const coordOrigenActual = origen.coordinador;

					if (mode === "swap") {
						setAsignacionManual(origen, selectedCoord, `Intercambio manual: ${selectedCoord.nombre || "Sin nombre"} asignado desde ${getAsignacionLabel(conflictItem)}.`);
						setAsignacionManual(
							conflictItem,
							coordOrigenActual || null,
							coordOrigenActual
								? `Intercambio manual: ${coordOrigenActual.nombre || "Sin nombre"} movido desde ${getAsignacionLabel(origen)}.`
								: "Intercambio manual: sede queda sin asignar al mover coordinador a otra sede."
						);
					} else {
						setAsignacionManual(origen, selectedCoord, `Cambio manual: ${selectedCoord.nombre || "Sin nombre"} reasignado desde ${getAsignacionLabel(conflictItem)}.`);
						setAsignacionManual(conflictItem, null, "Cambio manual: sede origen del coordinador queda sin asignar.");
					}

					renderAll();
					updateStatus("Cambio manual aplicado. Puede seguir editando asignaciones antes de exportar.");
					cerrarEditorManual();
					return;
				}

				setAsignacionManual(
					origen,
					selectedCoord,
					`Cambio manual: sede reasignada a ${selectedCoord.nombre || "Sin nombre"}.`
				);

				renderAll();
				updateStatus("Cambio manual aplicado. Puede seguir editando asignaciones antes de exportar.");
				cerrarEditorManual();
				return;
			}

			if (context.mode === "move") {
				const destinationId = refs.modalSedeDestinoSelect.value;
				if (!destinationId) {
					showError("Seleccione una sede destino para mover el coordinador.");
					return;
				}

				const destino = getAsignacionBySedeId(destinationId);
				if (!destino) {
					showError("No se encontro la sede destino seleccionada.");
					return;
				}

				const coordinadorOrigen = origen.coordinador;
				if (!coordinadorOrigen) {
					showError("La sede origen ya no tiene coordinador asignado.");
					cerrarEditorManual();
					return;
				}

				if (!destino.coordinador) {
					setAsignacionManual(destino, coordinadorOrigen, `Movimiento manual: ${coordinadorOrigen.nombre || "Sin nombre"} movido desde ${getAsignacionLabel(origen)}.`);
					setAsignacionManual(origen, null, "Movimiento manual: sede origen queda sin asignar.");
				} else {
					const modeRadio = refs.modalMoveConflict.querySelector('input[name="modoConflicto"]:checked');
					const mode = modeRadio?.value || "swap";
					const coordinadorDestino = destino.coordinador;

					if (mode === "swap") {
						setAsignacionManual(destino, coordinadorOrigen, `Intercambio manual: ${coordinadorOrigen.nombre || "Sin nombre"} asignado desde ${getAsignacionLabel(origen)}.`);
						setAsignacionManual(origen, coordinadorDestino, `Intercambio manual: ${coordinadorDestino.nombre || "Sin nombre"} reasignado desde ${getAsignacionLabel(destino)}.`);
					} else {
						setAsignacionManual(destino, coordinadorOrigen, `Movimiento manual: ${coordinadorOrigen.nombre || "Sin nombre"} reemplaza al coordinador previo en destino.`);
						setAsignacionManual(origen, null, "Movimiento manual: sede origen queda sin asignar.");
					}
				}

				renderAll();
				updateStatus("Movimiento manual aplicado. Puede seguir editando asignaciones antes de exportar.");
				cerrarEditorManual();
			}
		}

		function getSedesFiltradasOrdenadas() {
			return filtrarAsignaciones().slice().sort((a, b) => {
				const aCodigo = sanitizeKey(a.sede.codigo || "");
				const bCodigo = sanitizeKey(b.sede.codigo || "");
				if (aCodigo && bCodigo) {
					return aCodigo.localeCompare(bCodigo, "es", { numeric: true, sensitivity: "base" });
				}
				return a.sede.nombre.localeCompare(b.sede.nombre, "es", { numeric: true, sensitivity: "base" });
			});
		}

		function getSedesPaginadas() {
			const lista = getSedesFiltradasOrdenadas();
			const totalPaginas = Math.max(1, Math.ceil(lista.length / ITEMS_POR_PAGINA));
			paginaActualSedes = Math.min(Math.max(1, paginaActualSedes), totalPaginas);
			const inicio = (paginaActualSedes - 1) * ITEMS_POR_PAGINA;
			const fin = inicio + ITEMS_POR_PAGINA;
			return {
				lista,
				totalPaginas,
				items: lista.slice(inicio, fin)
			};
		}

		function renderPaginacion() {
			const { lista, totalPaginas } = getSedesPaginadas();
			if (!lista.length) {
				refs.paginacionInfo.textContent = "";
				refs.paginacionControles.innerHTML = "";
				return;
			}

			const inicio = (paginaActualSedes - 1) * ITEMS_POR_PAGINA + 1;
			const fin = Math.min(paginaActualSedes * ITEMS_POR_PAGINA, lista.length);
			refs.paginacionInfo.textContent = `Mostrando ${inicio}-${fin} de ${lista.length} sedes`;

			const botones = [];
			botones.push(`<button class="pagination-btn" ${paginaActualSedes === 1 ? "disabled" : ""} onclick="cambiarPaginaSedes(${paginaActualSedes - 1})">Anterior</button>`);

			const maxBotones = 5;
			let inicioPagina = Math.max(1, paginaActualSedes - Math.floor(maxBotones / 2));
			let finPagina = Math.min(totalPaginas, inicioPagina + maxBotones - 1);
			if (finPagina - inicioPagina + 1 < maxBotones) {
				inicioPagina = Math.max(1, finPagina - maxBotones + 1);
			}

			for (let pagina = inicioPagina; pagina <= finPagina; pagina++) {
				botones.push(`<button class="pagination-btn ${pagina === paginaActualSedes ? "active" : ""}" onclick="cambiarPaginaSedes(${pagina})">${pagina}</button>`);
			}

			botones.push(`<button class="pagination-btn" ${paginaActualSedes === totalPaginas ? "disabled" : ""} onclick="cambiarPaginaSedes(${paginaActualSedes + 1})">Siguiente</button>`);
			refs.paginacionControles.innerHTML = botones.join("");
		}

		function renderAcordeon() {
			const { lista, items } = getSedesPaginadas();
			refs.contadorResultados.textContent = `${lista.length} ${lista.length == 1 ? "resultado" : "resultados"}`;
			renderPaginacion();

			if (!lista.length) {
				refs.listaSedes.innerHTML = "<div class='loading'>No hay resultados para ese filtro.</div>";
				refs.paginacionInfo.textContent = "";
				refs.paginacionControles.innerHTML = "";
				return;
			}

			refs.listaSedes.innerHTML = items.map((item, idx) => {
				const a = item.coordinador;
				const sede = item.sede;
				const scoreTxt = item.score == null ? "-" : Math.round(item.score);
				const coordinadorProvincia = a?.residenciaParts?.provincia || "N/A";
				const coordinadorCanton = a?.residenciaParts?.canton || "N/A";
				const coordinadorDistrito = a?.residenciaParts?.distrito || "N/A";

				const asignacionHtml = a
					? `
						<div class="asignado-box">
							<h4>Coordinador asignado</h4>
							<div class="info-grid">
								<div class="info-item">
									<strong>Nombre</strong>
									<div>${escapeHtml(a.nombre || "Sin nombre")}</div>
								</div>
								<div class="info-item">
									<strong>Correo</strong>
									<div>${escapeHtml(a.correo || "Sin correo")}</div>
								</div>
								<div class="info-item">
									<strong>Telefono</strong>
									<div>${escapeHtml(a.telefono || a.oficina || "Sin telefono")}</div>
								</div>
								<div class="info-item">
									<strong>Puntaje de afinidad</strong>
									<div>${scoreTxt}</div>
								</div>
							</div>
							<div class="info-grid">
								<div class="info-item">
									<strong>Provincia</strong>
									<div>${escapeHtml(coordinadorProvincia)}</div>
								</div>
								<div class="info-item">
									<strong>Canton</strong>
									<div>${escapeHtml(coordinadorCanton)}</div>
								</div>
								<div class="info-item">
									<strong>Distrito</strong>
									<div>${escapeHtml(coordinadorDistrito)}</div>
								</div>
							</div>
							<div style="font-size:0.88rem; color:#115e34;">
								<strong>Justificacion:</strong> ${escapeHtml(item.reasons.join(". "))}
							</div>
							<div class="manual-actions">
								<button class="manual-btn" onclick="abrirEditorAsignacion('${sede.idInterno}')">Cambiar coordinador</button>
								<button class="manual-btn" onclick="abrirMoverCoordinador('${sede.idInterno}')">Mover a otra sede</button>
							</div>
						</div>
					`
					: `
						<div class="sin-asignar">
							Sin coordinador asignado para esta sede. Revise disponibilidad, ubicacion o datos faltantes.
						</div>
						<div class="manual-actions">
							<button class="manual-btn" onclick="abrirEditorAsignacion('${sede.idInterno}')">Asignar coordinador manualmente</button>
						</div>
					`;

				return `
					<div class="acordeon" id="acc-${paginaActualSedes}-${idx}">
						<div class="acordeon-cabecera" onclick="toggleAcordeon('acc-${paginaActualSedes}-${idx}')">
							<div>
								<h3><span class="sede-code">${escapeHtml(sede.codigo || "N/A")}</span>${escapeHtml(sede.nombre)}</h3>
								<p>${escapeHtml(sede.direccion || "Direccion no disponible")}</p>
							</div>
							<div class="acordeon-icono">+</div>
						</div>
						<div class="acordeon-contenido">
							<div class="info-grid">
								<div class="info-item">
									<strong>Codigo sede</strong>
									<div>${escapeHtml(sede.codigo || "N/A")}</div>
								</div>
								<div class="info-item">
									<strong>Provincia</strong>
									<div>${escapeHtml(sede.provincia || "N/A")}</div>
								</div>
								<div class="info-item">
									<strong>Canton</strong>
									<div>${escapeHtml(sede.canton || "N/A")}</div>
								</div>
								<div class="info-item">
									<strong>Distrito</strong>
									<div>${escapeHtml(sede.distrito || "N/A")}</div>
								</div>
							</div>
							${asignacionHtml}
						</div>
					</div>
				`;
			}).join("");
		}

		function renderTable() {
			const { lista, items } = getSedesPaginadas();
			renderPaginacion();
			if (!lista.length) {
				refs.tablaBody.innerHTML = "<tr><td colspan='6' style='text-align:center; color:#64748b;'>No hay datos para la seleccion actual.</td></tr>";
				return;
			}

			refs.tablaBody.innerHTML = items.map((item, idx) => `
				<tr>
					<td>${(paginaActualSedes - 1) * ITEMS_POR_PAGINA + idx + 1}</td>
					<td>${escapeHtml(item.sede.nombre)}</td>
					<td>${escapeHtml(item.coordinador?.nombre || "Sin asignar")}</td>
					<td>${escapeHtml(item.coordinador?.correo || "-")}</td>
					<td>${item.score == null ? "-" : Math.round(item.score)}</td>
					<td>
						<span class="badge ${item.estado === "asignada" ? "badge-ok" : "badge-warn"}">
							${item.estado === "asignada" ? "Asignada" : "Sin asignar"}
						</span>
						<div class="manual-actions">
							<button class="manual-btn" onclick="abrirEditorAsignacion('${item.sede.idInterno}')">Editar</button>
							${item.coordinador ? `<button class="manual-btn" onclick="abrirMoverCoordinador('${item.sede.idInterno}')">Mover</button>` : ""}
						</div>
					</td>
				</tr>
			`).join("");
		}

		function updateStats() {
			const totalCoordinadores = state.coordinadores.length;
			const totalSedes = state.sedes.length;
			const asignadas = state.asignaciones.filter(a => a.estado === "asignada").length;
			const sinAsignar = totalSedes - asignadas;

			refs.statCoordinadores.textContent = totalCoordinadores;
			refs.statSedes.textContent = totalSedes;
			refs.statAsignadas.textContent = asignadas;
			refs.statSinAsignar.textContent = Math.max(0, sinAsignar);
		}

		function renderAll() {
			updateStats();
			renderAcordeon();
			renderTable();
		}

		function cambiarPaginaSedes(pagina) {
			paginaActualSedes = pagina;
			renderAll();
			window.scrollTo({ top: 0, behavior: "smooth" });
		}

		window.cambiarPaginaSedes = cambiarPaginaSedes;
		window.abrirEditorAsignacion = abrirEditorAsignacion;
		window.abrirMoverCoordinador = abrirMoverCoordinador;

		// ================= BASE DE DATOS =================
		//
		// MIGRADO. Antes esta página solo sabía leer dos archivos XLSX que
		// había que volver a subir en cada sesión, y el resultado se perdía al
		// cerrar la pestaña: no quedaba registro de con qué criterio se asignó
		// a quién.
		//
		// Ahora las sedes salen de la base (ya están cargadas, no tiene sentido
		// subirlas en un archivo) y los postulantes también, si están cargados.
		// Subir archivos sigue funcionando: sirve para probar con datos nuevos
		// antes de importarlos.
		const API_URL = '../api/asignacion_coordinadores.php';
		const CSRF = <?= json_encode(token_csrf()) ?>;

		async function cargarDatosDesdeLaBase() {
			const respuesta = await fetch(`${API_URL}?accion=datos&_ts=${Date.now()}`, { cache: 'no-store' });
			const datos = await respuesta.json();

			if (!datos.success) throw new Error(datos.error || 'La base no devolvió datos');

			// Se pasan por las mismas funciones que los archivos: el endpoint
			// responde con las claves del XLSX justamente para eso.
			state.coordinadoresRaw = datos.postulantes || [];
			state.sedesRaw = datos.sedes || [];

			state.coordinadores = state.coordinadoresRaw
				.map((row, idx) => normalizeCoordinador(row, idx))
				.filter(c => c.nombre || c.correo || c.identificacion);

			state.sedes = state.sedesRaw
				.map((row, idx) => normalizeSede(row, idx))
				.filter(s => s.nombre || s.direccion || s.codigo);

			return state.sedes.length > 0;
		}

		/** Guarda el resultado para que no se pierda al cerrar la pestaña. */
		async function guardarAsignaciones() {
			if (!state.asignaciones.length) return;

			try {
				const respuesta = await fetch(API_URL, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						accion: 'guardar',
						criterio: refs.criterioPrioridad.value,
						asignaciones: state.asignaciones
							.filter(a => a.estado === 'asignada' && a.sede)
							.map(a => ({
								sede: a.sede.codigo,
								postulanteId: a.coordinador?.idBase || null,
								puntaje: a.score ?? null
							})),
						csrf: CSRF
					})
				});

				const datos = await respuesta.json();
				if (!datos.success) {
					console.warn('No se pudo guardar la asignación:', datos.error);
				}
			} catch (error) {
				// No se corta el flujo: la asignación ya está calculada en
				// pantalla y se puede exportar igual.
				console.warn('No se pudo guardar la asignación:', error);
			}
		}

		async function cargarDatosDesdeArchivos() {
			clearError();

			const coordinadoresFile = refs.coordinadoresFile.files[0];
			const sedesFile = refs.sedesFile.files[0];

			// Sin archivos se trabaja con lo que hay en la base.
			if (!coordinadoresFile && !sedesFile) {
				updateStatus("Leyendo datos de la base...");
				try {
					if (await cargarDatosDesdeLaBase()) return true;
					showError("No hay sedes cargadas en la base. Cargue los archivos o importe los datos.");
					return false;
				} catch (error) {
					showError("No se pudo leer la base: " + error.message);
					return false;
				}
			}

			if (!coordinadoresFile || !sedesFile) {
				showError("Debe cargar ambos archivos: coordinadores y sedes.");
				return false;
			}

			updateStatus("Leyendo archivos y normalizando informacion...");

			try {
				state.coordinadoresRaw = await parseWorkbook(coordinadoresFile);
				state.sedesRaw = await parseWorkbook(sedesFile);

				const prioridadDetectada = detectPrioridadAsignacionFromFiles(state.coordinadoresRaw, state.sedesRaw);
				refs.criterioPrioridad.value = prioridadDetectada.value;

				state.coordinadores = state.coordinadoresRaw
					.map((row, idx) => normalizeCoordinador(row, idx))
					.filter(c => c.nombre || c.correo || c.identificacion);

				state.sedes = state.sedesRaw
					.map((row, idx) => normalizeSede(row, idx))
					.filter(s => s.nombre || s.direccion || s.codigo);

				if (!state.coordinadores.length) {
					showError("No se pudieron detectar coordinadores validos. Verifique encabezados del archivo de Forms.");
					return false;
				}

				if (!state.sedes.length) {
					showError("No se pudieron detectar sedes validas. Verifique encabezados del archivo de sedes.");
					return false;
				}

				const sedesConGeo = state.sedes.filter(s => s.provincia || s.canton || s.distrito || s.isGam).length;
				if (sedesConGeo === 0) {
					updateStatus(
						"Archivos cargados correctamente, pero el archivo de sedes no trae datos geograficos claros.\n" +
						"Para mayor precision agregue columnas Provincia, Canton, Distrito o una columna GAM (SI/NO).\n" +
						`Prioridad alineada automaticamente: ${refs.criterioPrioridad.value} (${prioridadDetectada.source}).`
					);
				} else {
					updateStatus(
						`Archivos cargados correctamente.\n` +
						`Coordinadores detectados: ${state.coordinadores.length}.\n` +
						`Sedes detectadas: ${state.sedes.length}.\n` +
						`Prioridad alineada automaticamente: ${refs.criterioPrioridad.value} (${prioridadDetectada.source}).`
					);
				}

				return true;
			} catch (error) {
				showError(`Error leyendo archivos: ${error.message}`);
				return false;
			}
		}

		function runAsignacion() {
			calcularAsignaciones();
			refs.btnExportar.disabled = state.asignaciones.length === 0;
			refs.btnReasignar.disabled = state.asignaciones.length === 0;
			renderAll();

			// Se guarda en la base sin esperar: el resultado ya está en
			// pantalla y no tiene sentido bloquearla por la escritura.
			guardarAsignaciones();

			const asignadas = state.asignaciones.filter(a => a.estado === "asignada").length;
			const sinAsignar = state.asignaciones.length - asignadas;
			const resumen = state.asignacionResumen;
			const coberturaMsg = resumen
				? `Coordinadores con sede: ${resumen.usedAfter}/${state.coordinadores.length}. Rebalanceos: ${resumen.reassignedCount}. ${resumen.message}`
				: "";
			updateStatus(
				`Asignacion completada.\n` +
				`Sedes asignadas: ${asignadas}/${state.sedes.length}.\n` +
				`Sedes sin asignar: ${Math.max(0, sinAsignar)}.\n` +
				`${coberturaMsg}\n` +
				`Puede exportar el resultado final a Excel.`
			);
			paginaActualSedes = 1;
		}

		function exportarAsignacionesExcel() {
			if (!state.asignaciones.length) return;

			const rows = state.asignaciones.map((item, idx) => ({
				Numero: idx + 1,
				SedeCodigo: item.sede.codigo || "",
				SedeNombre: item.sede.nombre || "",
				SedeDireccion: item.sede.direccion || "",
				Provincia: item.sede.provincia || "",
				Canton: item.sede.canton || "",
				Distrito: item.sede.distrito || "",
				CoordinadorNombre: item.coordinador?.nombre || "",
				CoordinadorCorreo: item.coordinador?.correo || "",
				CoordinadorTelefono: item.coordinador?.telefono || item.coordinador?.oficina || "",
				CoordinadorIdentificacion: item.coordinador?.identificacion || "",
				GradoAcademico: item.coordinador?.grado || "",
				DisponibilidadZona: item.coordinador?.disponibilidadZona || "",
				TurnosDisponibles: item.coordinador?.turnos?.join(" | ") || "",
				TieneParientePAA: item.coordinador?.tienePariente ? "SI" : "NO",
				PuntajeAfinidad: item.score == null ? "" : Math.round(item.score),
				EstadoAsignacion: item.estado === "asignada" ? "Asignada" : "Sin asignar",
				Justificacion: item.reasons.join(". ")
			}));

			const ws = XLSX.utils.json_to_sheet(rows);
			const wb = XLSX.utils.book_new();
			XLSX.utils.book_append_sheet(wb, ws, "Asignaciones");

			const timestamp = new Date().toISOString().slice(0, 19).replace(/[T:]/g, "-");
			XLSX.writeFile(wb, `asignacion_coordinadores_${timestamp}.xlsx`);
		}

		function toggleAcordeon(id) {
			const target = document.getElementById(id);
			if (!target) return;
			target.classList.toggle("abierto");
		}

		function updateFileUi(inputRef, btnRef, nameRef) {
			const file = inputRef.files?.[0];
			if (file) {
				btnRef.classList.add("loaded");
				btnRef.textContent = "Cambiar archivo";
				nameRef.classList.add("has-file");
				nameRef.textContent = file.name;
				return;
			}

			btnRef.classList.remove("loaded");
			btnRef.textContent = "Seleccionar archivo";
			nameRef.classList.remove("has-file");
			nameRef.textContent = "Ningun archivo seleccionado";
		}

		window.toggleAcordeon = toggleAcordeon;

		refs.modalSedeDestinoSelect.addEventListener("change", updateMoveConflictUi);
		refs.modalCoordinadorSelect.addEventListener("change", updateChangeConflictUi);
		refs.modalCoordinadorSearch.addEventListener("input", () => {
			const context = state.editContext;
			if (!context || context.mode !== "change") return;
			renderCoordinadorOptionsForEditor(context.sedeId, refs.modalCoordinadorSearch.value);
			updateChangeConflictUi();
		});

		refs.modalSedeDestinoSearch.addEventListener("input", () => {
			renderSedeDestinoOptions(refs.modalSedeDestinoSearch.value);
			updateMoveConflictUi();
		});
		refs.modalCancelarBtn.addEventListener("click", cerrarEditorManual);
		refs.modalAplicarBtn.addEventListener("click", aplicarCambioManual);

		refs.modalEditorAsignacion.addEventListener("click", event => {
			if (event.target === refs.modalEditorAsignacion) {
				cerrarEditorManual();
			}
		});

		refs.btnProcesar.addEventListener("click", async () => {
			const ok = await cargarDatosDesdeArchivos();
			if (!ok) return;
			runAsignacion();
		});

		refs.btnReasignar.addEventListener("click", () => {
			clearError();
			if (!state.coordinadores.length || !state.sedes.length) {
				showError("Debe procesar los archivos primero.");
				return;
			}
			runAsignacion();
		});

		refs.btnExportar.addEventListener("click", exportarAsignacionesExcel);

		refs.coordinadoresFile.addEventListener("change", () => {
			updateFileUi(refs.coordinadoresFile, refs.coordinadoresFileBtn, refs.coordinadoresFileName);
		});

		refs.sedesFile.addEventListener("change", () => {
			updateFileUi(refs.sedesFile, refs.sedesFileBtn, refs.sedesFileName);
		});

		refs.busqueda.addEventListener("input", event => {
			state.filtroBusqueda = event.target.value;
			paginaActualSedes = 1;
			renderAll();
		});

		refs.filtroEstado.addEventListener("change", event => {
			state.filtroEstado = event.target.value;
			paginaActualSedes = 1;
			renderAll();
		});

		refs.criterioPrioridad.addEventListener("change", () => {
			if (!state.asignaciones.length) return;
			paginaActualSedes = 1;
			runAsignacion();
		});

		updateFileUi(refs.coordinadoresFile, refs.coordinadoresFileBtn, refs.coordinadoresFileName);
		updateFileUi(refs.sedesFile, refs.sedesFileBtn, refs.sedesFileName);
	</script>
<?php include __DIR__ . '/../includes/boton_inicio.php'; ?>
<?php include __DIR__ . '/../includes/chat_soporte.php'; ?>
<?php include __DIR__ . '/../includes/notificaciones.php'; ?>
</body>
</html>
