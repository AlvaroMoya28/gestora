<?php
/**
 * Exportar un arreglo de filas como Excel.
 *
 * Mismo truco que ya usan Traslados e Inventario de Oficina: una tabla HTML
 * con extensión .xls, que Excel abre directo sin preguntar nada y sin
 * comerse los ceros a la izquierda. No hay Composer en el sistema, así que
 * no hay PhpSpreadsheet — esto alcanza para lo que estos módulos necesitan.
 */

declare(strict_types=1);

/**
 * @param string $nombreBase sin extensión ni fecha: se le agregan las dos.
 * @param string[] $columnas encabezados, en el orden en que se escriben.
 * @param array<int, array<string, mixed>> $filas cada una con las mismas claves que $columnas.
 */
function exportar_excel_html(string $nombreBase, array $columnas, array $filas): void
{
    $archivo = sprintf('%s_%s.xls', $nombreBase, date('Ymd_Hi'));

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Cache-Control: no-store');

    echo "\xEF\xBB\xBF";
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>td { mso-number-format:"\@"; } th { background:#e8f0fe; font-weight:bold; }</style>';
    echo '</head><body><table border="1">';

    echo '<tr>' . implode('', array_map(
        static fn(string $c): string => '<th>' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '</th>',
        $columnas
    )) . '</tr>';

    foreach ($filas as $fila) {
        echo '<tr>' . implode('', array_map(
            static fn(string $c): string => '<td>' . htmlspecialchars((string) ($fila[$c] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>',
            $columnas
        )) . '</tr>';
    }

    echo '</table></body></html>';
    exit;
}
