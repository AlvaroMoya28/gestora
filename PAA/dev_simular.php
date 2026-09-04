<?php
/**
 * Interruptor de "probar como otro rol", solo para desarrollo.
 *
 *   dev_simular.php?rol=funcionario     → ver el sistema como funcionario
 *   dev_simular.php?rol=extraordinario  → ver el sistema como Personal Extraordinario
 *   dev_simular.php?rol=administrador   → ver el sistema como administración
 *   dev_simular.php (sin rol, o rol=desarrollador) → volver a la cuenta real
 *
 * No es una pantalla: hace el cambio y vuelve a donde estabas. simular_rol()
 * ya se encarga de que solo un desarrollador de verdad pueda usar esto.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/sesion.php';

if (usuario_actual() === null) {
    header('Location: ' . base_url() . 'login.php');
    exit;
}

simular_rol($_GET['rol'] ?? $_POST['rol'] ?? null);

// Vuelve a la página desde donde se pidió el cambio; si no hay ninguna
// (llegaron acá directo), al portal.
$volver = $_SERVER['HTTP_REFERER'] ?? (base_url());
header('Location: ' . $volver);
exit;
