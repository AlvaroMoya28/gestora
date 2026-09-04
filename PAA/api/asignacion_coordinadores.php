<?php
/**
 * Endpoint del módulo Asignación de Coordinadores.
 *
 *   GET  accion=datos       → postulantes y sedes para calcular la asignación
 *   GET  accion=asignaciones→ asignaciones guardadas de la convocatoria
 *   POST accion=guardar     → guarda el resultado de la asignación
 *   POST accion=asignarUna  → cambia a mano el coordinador de una sede
 *
 * QUÉ CAMBIA Y QUÉ NO
 * El algoritmo de afinidad (GAM, provincia/cantón, grado académico, años de
 * experiencia, balanceo de carga) se queda donde está: en el JavaScript de la
 * página, que es la lógica que la usuaria ya validó y ajustó. No se reescribe
 * en PHP sin necesidad.
 *
 * Lo que cambia es de dónde salen los datos y dónde queda el resultado:
 *   · Las sedes ya no se suben en un XLSX: salen de la tabla `sedes`.
 *   · Los postulantes salen de la tabla `postulantes` (se cargan una vez con
 *     scripts/importar_csv.php) en lugar de subir el archivo en cada sesión.
 *   · La asignación se guarda. Antes vivía solo en la pestaña del navegador:
 *     al cerrarla se perdía, sin registro de con qué criterio se asignó a quién.
 *
 * El campo `manual` distingue lo que puso el algoritmo de lo que la usuaria
 * cambió a mano, para que recalcular no pise los ajustes.
 */

require_once __DIR__ . '/../includes/peticion.php';
require_once __DIR__ . '/../includes/modulos.php';

// Solo administración: que el módulo no aparezca en el menú de un funcionario
// no impide que alguien llame a este endpoint a mano.
$yo = exigir_modulo_api("coordinadores");


exigir_metodo(['GET', 'POST']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
}

$accion = param('accion', param('action', 'datos'));

try {
    switch ($accion) {
        case 'datos':        datos();          break;
        case 'asignaciones': ver_asignaciones(); break;
        case 'guardar':      guardar();        break;
        case 'asignarUna':   asignar_una();    break;

        default:
            responder_json(['success' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    fallo('asignacion_coordinadores', $e);
}

// ------------------------------------------------------------------

function datos(): void
{
    $convocatoriaId = convocatoria_actual();

    // Los postulantes se devuelven con los mismos nombres de campo que
    // normalizeCoordinador() producía en el JS, para que el algoritmo de
    // afinidad funcione sin tocarlo.
    // La cláusula se arma en PHP: PDO usa consultas preparadas reales y ahí no
    // se puede repetir un placeholder nombrado dentro de la misma consulta.
    // Se incluyen los postulantes sin convocatoria asignada, que son los que
    // se cargaron sin indicarla.
    $sql = "
        SELECT id, nombre, correo, identificacion, telefono, telefono_oficina,
               provincia, canton, distrito, direccion, en_gam,
               grado_academico, lugar_trabajo, tipo_nombramiento,
               anios_experiencia, disponible, elegible
        FROM postulantes
    ";
    $parametros = [];

    if ($convocatoriaId !== null) {
        $sql .= ' WHERE (convocatoria_id = :conv OR convocatoria_id IS NULL)';
        $parametros['conv'] = $convocatoriaId;
    }

    $sql .= ' ORDER BY nombre';

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);

    // Se responde con las mismas claves que traía el XLSX del formulario de
    // inscripción, para que la página pueda seguir usando sus funciones
    // normalizeCoordinador() y normalizeSede() sin cambios. Esa lógica —GAM,
    // grado académico, años de experiencia— ya está validada y no conviene
    // reescribirla del otro lado.
    $postulantes = array_map(static function (array $f): array {
        return [
            'Nombre completo'            => $f['nombre'],
            'Correo electronico'         => $f['correo'] ?? '',
            'Numero de identificacion'   => $f['identificacion'] ?? '',
            'Direccion de residencia'    => $f['direccion']
                ?: trim(implode(', ', array_filter([
                    $f['provincia'] ?? '', $f['canton'] ?? '', $f['distrito'] ?? '',
                ]))),
            'Numero de telefono'         => $f['telefono'] ?? '',
            'Telefono de oficina'        => $f['telefono_oficina'] ?? '',
            'Grado academico'            => $f['grado_academico'] ?? '',
            'Lugar de trabajo'           => $f['lugar_trabajo'] ?? '',
            'Tipo de nombramiento'       => $f['tipo_nombramiento'] ?? '',

            'id'              => (int) $f['id'],
            'nombre'          => $f['nombre'],
            'correo'          => $f['correo'] ?? '',
            'identificacion'  => $f['identificacion'] ?? '',
            'telefono'        => $f['telefono'] ?? '',
            'oficina'         => $f['telefono_oficina'] ?? '',
            'provincia'       => $f['provincia'] ?? '',
            'canton'          => $f['canton'] ?? '',
            'distrito'        => $f['distrito'] ?? '',
            'direccion'       => $f['direccion'] ?? '',
            'esGam'           => (bool) $f['en_gam'],
            'grado'           => $f['grado_academico'] ?? '',
            'lugarTrabajo'    => $f['lugar_trabajo'] ?? '',
            'tipoNombramiento'=> $f['tipo_nombramiento'] ?? '',
            'experiencia'     => $f['anios_experiencia'] !== null ? (int) $f['anios_experiencia'] : null,
            'disponible'      => (bool) $f['disponible'],
            'elegible'        => (bool) $f['elegible'],
        ];
    }, $stmt->fetchAll());

    // Las sedes salen de la base y traen ya el número de aulas de la
    // convocatoria, que es un criterio de carga para el algoritmo.
    $joinAulas = $convocatoriaId !== null
        ? 'LEFT JOIN aulas a ON a.sede_id = s.id AND a.convocatoria_id = :conv'
        : 'LEFT JOIN aulas a ON a.sede_id = s.id';

    // provincia/canton/distrito vienen del Sheet de sedes (migración 009).
    // Son mejores que lo que tenía el JS, que debía adivinar la ubicación
    // leyendo el texto de la dirección (inferProvinciaByText).
    $stmt = db()->prepare("
        SELECT s.id, s.codigo, s.nombre, s.direccion, s.telefono,
               s.provincia, s.canton, s.distrito, s.en_gam,
               COUNT(a.id) AS aulas
        FROM sedes s
        {$joinAulas}
        WHERE s.activo = 1 AND s.convocatoria_id = :convSede
        GROUP BY s.id, s.codigo, s.nombre, s.direccion, s.telefono,
                 s.provincia, s.canton, s.distrito, s.en_gam
        ORDER BY CAST(s.codigo AS UNSIGNED), s.codigo
    ");
    $stmt->execute($convocatoriaId !== null
        ? ['conv' => $convocatoriaId, 'convSede' => $convocatoriaId]
        : ['convSede' => null]);

    $sedes = array_map(static function (array $f): array {
        return [
            // Claves del XLSX de sedes, para normalizeSede().
            'idSede'     => $f['codigo'],
            'NombreSede' => $f['nombre'],
            'Direccion'  => $f['direccion'] ?? '',
            'Provincia'  => $f['provincia'] ?? '',
            'Canton'     => $f['canton'] ?? '',
            'Distrito'   => $f['distrito'] ?? '',
            'GAM'        => $f['en_gam'] ? 'SI' : 'NO',

            'id'        => (int) $f['id'],
            'nombre'    => $f['nombre'],
            'direccion' => $f['direccion'] ?? '',
            'telefono'  => $f['telefono'] ?? '',
            'provincia' => $f['provincia'] ?? '',
            'canton'    => $f['canton'] ?? '',
            'distrito'  => $f['distrito'] ?? '',
            'esGam'     => (bool) $f['en_gam'],
            'aulas'     => (int) $f['aulas'],
        ];
    }, $stmt->fetchAll());

    responder_json([
        'success'      => true,
        'postulantes'  => $postulantes,
        'sedes'        => $sedes,
        'convocatoria' => $convocatoriaId,
    ]);
}

function ver_asignaciones(): void
{
    $convocatoriaId = convocatoria_actual();
    $filtro = $convocatoriaId !== null ? 'WHERE asg.convocatoria_id = :conv' : '';

    $stmt = db()->prepare("
        SELECT asg.id, asg.puntaje, asg.criterio, asg.manual, asg.observacion,
               asg.actualizado_en,
               s.codigo AS sede, s.nombre AS nombre_sede,
               COALESCE(po.nombre, pe.nombre) AS coordinador,
               COALESCE(po.correo, pe.correo) AS correo,
               po.id AS postulante_id, pe.id AS persona_id
        FROM asignaciones_sede asg
        INNER JOIN sedes s        ON s.id = asg.sede_id
        LEFT  JOIN postulantes po ON po.id = asg.postulante_id
        LEFT  JOIN personas pe    ON pe.id = asg.persona_id
        {$filtro}
        ORDER BY CAST(s.codigo AS UNSIGNED), s.codigo
    ");
    $stmt->execute($convocatoriaId !== null ? ['conv' => $convocatoriaId] : []);

    responder_json(['success' => true, 'asignaciones' => $stmt->fetchAll()]);
}

/**
 * Guarda el resultado completo de una corrida del algoritmo.
 *
 * Las asignaciones marcadas como manuales NO se pisan: el algoritmo puede
 * volver a correrse las veces que haga falta sin deshacer los ajustes que la
 * usuaria ya revisó.
 */
function guardar(): void
{
    $convocatoriaId = convocatoria_actual();
    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay convocatoria activa'], 409);
    }

    $asignaciones = entrada()['asignaciones'] ?? [];
    if (!is_array($asignaciones) || $asignaciones === []) {
        responder_json(['success' => false, 'error' => 'No se recibió ninguna asignación'], 400);
    }

    $criterio = param('criterio');

    // La sede se resuelve dentro de la convocatoria: el código solo no basta.
    $buscarSede = db()->prepare('SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $guardar = db()->prepare("
        INSERT INTO asignaciones_sede
            (convocatoria_id, sede_id, postulante_id, persona_id, puntaje, criterio, manual)
        VALUES (:conv, :sede, :postulante, :persona, :puntaje, :criterio, 0)
        ON DUPLICATE KEY UPDATE
            postulante_id = IF(manual = 1, postulante_id, VALUES(postulante_id)),
            persona_id    = IF(manual = 1, persona_id,    VALUES(persona_id)),
            puntaje       = IF(manual = 1, puntaje,       VALUES(puntaje)),
            criterio      = IF(manual = 1, criterio,      VALUES(criterio))
    ");

    $guardadas = 0;
    $omitidas  = [];

    db()->beginTransaction();

    foreach ($asignaciones as $a) {
        $codigoSede = trim((string) ($a['sede'] ?? $a['idSede'] ?? ''));
        $buscarSede->execute([$convocatoriaId, $codigoSede]);
        $sedeId = $buscarSede->fetchColumn();

        if ($sedeId === false) {
            $omitidas[] = $codigoSede !== '' ? $codigoSede : '(sin código)';
            continue;
        }

        $postulanteId = isset($a['postulanteId']) ? (int) $a['postulanteId'] : 0;
        $personaId    = isset($a['personaId']) ? (int) $a['personaId'] : 0;

        $guardar->execute([
            'conv'       => $convocatoriaId,
            'sede'       => (int) $sedeId,
            'postulante' => $postulanteId > 0 ? $postulanteId : null,
            'persona'    => $personaId > 0 ? $personaId : null,
            'puntaje'    => isset($a['puntaje']) ? (float) $a['puntaje'] : null,
            'criterio'   => $criterio !== '' ? $criterio : ($a['criterio'] ?? null),
        ]);

        $guardadas++;
    }

    db()->commit();

    responder_json([
        'success'   => true,
        'guardadas' => $guardadas,
        'omitidas'  => $omitidas,
    ]);
}

/** Cambio manual del coordinador de una sede. Queda marcado como manual. */
function asignar_una(): void
{
    exigir(['sede']);

    $convocatoriaId = convocatoria_actual();
    if ($convocatoriaId === null) {
        responder_json(['success' => false, 'error' => 'No hay convocatoria activa'], 409);
    }

    $sedeId = id_sede_por_codigo(param('sede'), $convocatoriaId);

    if ($sedeId === null) {
        responder_json([
            'success' => false,
            'error'   => 'La sede no existe en esta convocatoria',
        ], 404);
    }

    $postulanteId = param_int('postulanteId');
    $personaId    = param_int('personaId');

    if ($postulanteId > 0 && $personaId > 0) {
        responder_json([
            'success' => false,
            'error'   => 'Indicá un postulante o una persona del catálogo, no ambos',
        ], 400);
    }

    $stmt = db()->prepare("
        INSERT INTO asignaciones_sede
            (convocatoria_id, sede_id, postulante_id, persona_id, criterio, manual, observacion)
        VALUES (:conv, :sede, :postulante, :persona, 'manual', 1, :obs)
        ON DUPLICATE KEY UPDATE
            postulante_id = VALUES(postulante_id),
            persona_id    = VALUES(persona_id),
            manual        = 1,
            observacion   = VALUES(observacion)
    ");
    $stmt->execute([
        'conv'       => $convocatoriaId,
        'sede'       => (int) $sedeId,
        'postulante' => $postulanteId > 0 ? $postulanteId : null,
        'persona'    => $personaId > 0 ? $personaId : null,
        'obs'        => param('observacion') ?: null,
    ]);

    responder_json(['success' => true]);
}
