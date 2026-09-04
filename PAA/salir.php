<?php
/**
 * Cierra la sesión y devuelve al ingreso.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/sesion.php';

cerrar_sesion();

header('Location: login.php');
exit;
