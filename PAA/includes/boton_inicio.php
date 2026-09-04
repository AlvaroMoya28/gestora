<?php
/**
 * Antes: un botón flotante «Inicio» abajo a la izquierda.
 * Ahora: la barra superior de navegación (includes/barra_superior.php).
 *
 * POR QUÉ CAMBIÓ
 * El botón flotante estaba al final del documento y abajo en la pantalla. Para
 * quien navega con teclado y lector de pantalla eso significaba recorrer la
 * página completa —todas las tarjetas, todo el calendario— antes de poder
 * volver al inicio. En la sesión de prueba con NVDA fue de lo primero que
 * salió: «que el botón de inicio esté arriba a la izquierda, es lo primero que
 * debería poder accesar».
 *
 * ESTE ARCHIVO SE MANTIENE porque ya estaba incluido al cierre del <body> de
 * las veinte páginas de módulo. En vez de editarlas una por una, acá se cambia
 * lo que se pinta: todas reciben la barra nueva sin tocarlas.
 */

declare(strict_types=1);

require_once __DIR__ . '/barra_superior.php';
