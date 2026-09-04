<?php
/**
 * Endpoint del módulo Carátulas.
 *
 * No guarda datos: solo toma la información ya capturada para una sede y la
 * devuelve lista para imprimir las carátulas de cajas/tulas.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';
require_once __DIR__ . '/../includes/sedes_datos.php';

$yo = exigir_modulo_api('caratulas');

exigir_metodo(['GET']);

$accion = param('accion', param('action', 'sede'));

try {
    switch ($accion) {
        case 'sede':
            caratula_sede();
            break;

        case 'listar':
            caratulas_listar();
            break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('caratulas', $e, 'No se pudo generar la carátula');
}

function caratula_sede(): void
{
    exigir(['sede']);

    $conv   = convocatoria_exigida();
    $sedeId = sede_exigida(param('sede'), $conv);
    $datos  = datos_de_sede($sedeId, $conv);

    responder_json([
        'success'        => true,
        'convocatoriaId' => $conv,
    ] + $datos);
}

/**
 * Lista liviana de sedes (id, código, nombre, coordinador) para el buscador
 * con selección.
 *
 * Se incluye el coordinador porque quien viene a retirar las carátulas
 * impresas casi siempre es la persona coordinadora, no alguien que sepa de
 * memoria el número de la sede — buscar por su nombre es lo natural.
 */
function caratulas_listar(): void
{
    $conv   = convocatoria_exigida();
    $sedes  = sedes_con_estado($conv);

    responder_json([
        'success' => true,
        'sedes'   => array_map(static fn (array $s): array => [
            'id'          => $s['id'],
            'codigo'      => $s['codigo'],
            'nombre'      => $s['nombre'],
            'coordinador' => $s['coordinador'] ?? '',
        ], $sedes),
    ]);
}
