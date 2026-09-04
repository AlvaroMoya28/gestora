<?php
/**
 * Carga a la base los datos exportados de los Google Sheets.
 *
 *   php scripts/importar_csv.php <destino> <archivo.csv> [--convocatoria="PAA 2026"]
 *   php scripts/importar_csv.php --lista
 *
 * Destinos: sedes, personal, aulas, activos, prestamos, materiales,
 *           sobresueldos, tarifas_hospedaje, postulantes
 *
 * POR QUÉ UN IMPORTADOR Y NO INSERTs EN LA MIGRACIÓN
 * Los datos de los Sheets cambian entre convocatorias. Si vivieran dentro de
 * un archivo de schema/, actualizarlos obligaría a editar una migración ya
 * aplicada —que por diseño nunca se vuelve a correr— o a inventar una
 * migración nueva cada vez que entra una sede. El esquema y los datos son
 * cosas distintas y se manejan aparte.
 *
 * TODO ES IDEMPOTENTE: cada destino tiene una clave natural (el código de la
 * sede, el código del activo, sede+aula...) y se hace UPSERT contra ella. Se
 * puede volver a correr el mismo archivo sin duplicar nada, que es justo lo
 * que hace falta cuando el Sheet se corrige y hay que recargarlo.
 *
 * CÓMO SACAR EL CSV DE UN GOOGLE SHEET
 *   Archivo → Descargar → Valores separados por comas (.csv)
 * o, si la hoja es pública:
 *   https://docs.google.com/spreadsheets/d/<ID>/export?format=csv&gid=<GID>
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/db.php';

// ------------------------------------------------------------------
// Argumentos
// ------------------------------------------------------------------
$destinos = [
    'sedes'        => 'Catálogo de sedes (Sheet 171wt_c…/Hoja1)',
    'personal'     => 'Personal → tabla personas (hoja "Personal" de Activos)',
    'aulas'        => 'Aulas y tulas por sede (Sheet 1KEcA3…, gid=0)',
    'activos'      => 'Inventario de equipo (hoja "Activos")',
    'prestamos'    => 'Historial de préstamos (hoja "Prestamos")',
    'materiales'   => 'Catálogo de materiales (pestaña "Materiales")',
    'sobresueldos' => 'Presupuesto por sede (Sheet 1y8led…/Hoja 1)',
    'tarifas_hospedaje' => 'Tarifas de hospedaje por ubicación (Sheet 1G_jrX…)',
    'postulantes'  => 'Postulantes a coordinación (XLSX exportado a CSV)',
    'materiales_adecuacion' => 'Equipo de adecuación por aula (columnas: Sede, # Aula, y una por material)',
];

if (in_array('--lista', $argv, true) || $argc < 3) {
    echo "Uso: php scripts/importar_csv.php <destino> <archivo.csv> [--convocatoria=\"PAA 2026\"]\n\n";
    echo "Destinos disponibles:\n";
    foreach ($destinos as $clave => $texto) {
        printf("  %-14s %s\n", $clave, $texto);
    }
    echo "\nOpciones:\n";
    echo "  --convocatoria=\"PAA 2026\"   obligatoria para aulas, sobresueldos, postulantes y materiales_adecuacion\n";
    echo "  --reemplazar                deja SOLO lo que trae el archivo (destino sedes).\n";
    echo "                              Las sedes que no vengan se borran; las que tengan\n";
    echo "                              datos asociados se dan de baja en vez de borrarse.\n";
    exit($argc < 3 ? 1 : 0);
}

$destino = $argv[1];
$archivo = $argv[2];

if (!isset($destinos[$destino])) {
    exit("Destino desconocido: {$destino}. Corré --lista para ver los válidos.\n");
}

if (!is_file($archivo)) {
    exit("No existe el archivo: {$archivo}\n");
}

$convocatoria = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--convocatoria=')) {
        $convocatoria = trim(substr($arg, strlen('--convocatoria=')), " \"'");
    }
}

/**
 * --reemplazar: en vez de agregar y actualizar, deja SOLO lo que trae el
 * archivo. Se pide explícitamente porque es destructivo.
 *
 * Hace falta cuando el archivo nuevo no es una corrección del anterior sino
 * otro conjunto de datos. Pasó con las sedes: el catálogo de centros
 * educativos numeraba del 1 al 1001 y la base de aplicación 2026 numera del
 * 401 al 4067, así que 50 códigos coinciden **sin ser la misma sede** (el 401
 * era "CINDEA SAN CARLOS" y pasa a ser "CTP DE PEJIBAYE"). Sin esta opción,
 * esas 50 quedarían con el nombre nuevo y el teléfono y el director del centro
 * viejo: datos mezclados que después nadie puede distinguir.
 */
$reemplazar = in_array('--reemplazar', $argv, true);

// ------------------------------------------------------------------
// Utilidades
// ------------------------------------------------------------------

/**
 * Lee el CSV completo como arreglo de filas asociativas.
 *
 * Los encabezados se normalizan (sin tildes, minúsculas, sin espacios) porque
 * los Sheets vienen con nombres inconsistentes: "SEDE", "Sede", "correo
 * coordinador", "TOTAL FOLLETOS COORD.". Normalizar acá evita repetir esa
 * gimnasia en cada destino.
 */
function leer_csv(string $ruta): array
{
    $fh = fopen($ruta, 'r');
    if ($fh === false) {
        exit("No se pudo abrir {$ruta}\n");
    }

    // Google exporta en UTF-8 con BOM; sin quitarlo, el primer encabezado
    // queda con basura invisible adelante y nunca calza.
    $primeraLinea = fgets($fh);
    if ($primeraLinea !== false && str_starts_with($primeraLinea, "\xEF\xBB\xBF")) {
        $primeraLinea = substr($primeraLinea, 3);
    }
    rewind($fh);
    if ($primeraLinea !== false) {
        fgets($fh); // consumir la línea de encabezados desde el archivo real
    }

    $encabezados = array_map('normalizar_clave', str_getcsv(rtrim((string) $primeraLinea, "\r\n")));

    $filas = [];
    while (($valores = fgetcsv($fh)) !== false) {
        // Fila totalmente vacía: el final de los Sheets suele traer varias.
        if (count(array_filter($valores, static fn($v) => trim((string) $v) !== '')) === 0) {
            continue;
        }

        $fila = [];
        foreach ($encabezados as $i => $clave) {
            $fila[$clave] = isset($valores[$i]) ? trim((string) $valores[$i]) : '';
        }
        $filas[] = $fila;
    }

    fclose($fh);
    return $filas;
}

/** Minúsculas, sin tildes, sin puntuación: "TOTAL FOLLETOS COORD." → "total_folletos_coord" */
function normalizar_clave(string $texto): string
{
    $t = mb_strtolower(trim($texto), 'UTF-8');
    $t = strtr($t, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ñ' => 'n', 'ü' => 'u',
    ]);
    $t = preg_replace('/[^a-z0-9]+/', '_', $t) ?? '';
    return trim($t, '_');
}

/**
 * Primer valor no vacío entre varios nombres de columna posibles.
 * Los Sheets no son consistentes: "Cordinador" y "Coordinador" conviven.
 */
function campo(array $fila, array $candidatos, string $porDefecto = ''): string
{
    foreach ($candidatos as $c) {
        $clave = normalizar_clave($c);
        if (isset($fila[$clave]) && $fila[$clave] !== '') {
            return $fila[$clave];
        }
    }
    return $porDefecto;
}

function entero(array $fila, array $candidatos, int $porDefecto = 0): int
{
    $v = campo($fila, $candidatos);
    if ($v === '') {
        return $porDefecto;
    }
    // Los Sheets traen números con separador de miles: "1.250" o "1,250"
    return (int) preg_replace('/[^0-9\-]/', '', $v);
}

/**
 * Devuelve el id de la convocatoria, creándola si hace falta.
 *
 * Se marca activa al crearla: es la que van a usar las páginas. Si hubiera que
 * volver a una anterior, se cambia la bandera a mano. Antes se creaba inactiva
 * y eso dejaba el sistema mostrando "no hay convocatoria activa" hasta que
 * alguien se acordara del paso extra.
 */
function id_convocatoria(?string $nombre, ?string $fecha = null): int
{
    if ($nombre === null || $nombre === '') {
        exit("Este destino necesita --convocatoria=\"nombre\"\n");
    }

    $stmt = db()->prepare('SELECT id FROM convocatorias WHERE nombre = ?');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();

    if ($id !== false) {
        return (int) $id;
    }

    db()->exec('UPDATE convocatorias SET activa = 0');
    db()->prepare('INSERT INTO convocatorias (nombre, fecha_aplicacion, activa) VALUES (?, ?, 1)')
        ->execute([$nombre, $fecha]);

    echo "  · convocatoria creada y marcada como activa: {$nombre}"
        . ($fecha !== null ? " ({$fecha})" : '') . "\n";

    return (int) db()->lastInsertId();
}

/**
 * "03/10/2026  8:00AM" → "2026-10-03".
 *
 * Se interpreta como día/mes/año, que es como se escribe la fecha en Costa
 * Rica. strtotime() sin ayuda leería 03/10 como 10 de marzo.
 */
function fecha_iso(string $texto): ?string
{
    if (!preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $texto, $m)) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
}

/**
 * Id de una sede dentro de una convocatoria, creándola si no existe.
 *
 * La sede pertenece a su convocatoria: el código 401 es un colegio en 2026 y
 * otro distinto en 2027 (ver schema/011_sedes_por_convocatoria.sql). Por eso
 * la búsqueda va siempre por las dos cosas.
 *
 * Se crea al vuelo a propósito: el archivo de aulas trae sedes que a veces
 * todavía no están cargadas, y abortar la importación entera por eso obligaría
 * a cargar los archivos en un orden exacto. Queda con el nombre que traiga el
 * archivo y se completa después importando el listado de sedes.
 */
function id_sede(int $convocatoriaId, string $codigo, string $nombre = ''): ?int
{
    $codigo = trim($codigo);
    if ($codigo === '') {
        return null;
    }

    static $cache = [];
    $clave = $convocatoriaId . '|' . $codigo;
    if (isset($cache[$clave])) {
        return $cache[$clave];
    }

    $stmt = db()->prepare('SELECT id FROM sedes WHERE convocatoria_id = ? AND codigo = ?');
    $stmt->execute([$convocatoriaId, $codigo]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        db()->prepare('INSERT INTO sedes (convocatoria_id, codigo, nombre) VALUES (?, ?, ?)')
            ->execute([$convocatoriaId, $codigo, $nombre !== '' ? $nombre : "Sede {$codigo}"]);
        $id = (int) db()->lastInsertId();
        echo "  · sede creada al vuelo: {$codigo}\n";
    }

    return $cache[$clave] = (int) $id;
}

/** Id de un área del catálogo, creándola si no existe. */
function id_area(string $nombre): ?int
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        return null;
    }

    static $cache = [];
    if (isset($cache[$nombre])) {
        return $cache[$nombre];
    }

    $stmt = db()->prepare('SELECT id FROM areas WHERE nombre = ?');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        db()->prepare('INSERT INTO areas (nombre, orden) VALUES (?, 99)')->execute([$nombre]);
        $id = (int) db()->lastInsertId();
    }

    return $cache[$nombre] = (int) $id;
}

/** "SI"/"SÍ"/"1"/"true" → true. El resto, false. */
function es_si(string $valor): bool
{
    $v = normalizar_clave($valor);
    return in_array($v, ['si', 'sí', 's', '1', 'true', 'x', 'verdadero'], true);
}

/**
 * ¿Provincia + cantón están en la Gran Área Metropolitana?
 *
 * Misma lista que la constante GAM_CANTONES del JS de Asignación de
 * Coordinadores. Se calcula al importar, no en cada consulta: es un dato que
 * no cambia.
 */
function es_gam(string $provincia, string $canton): bool
{
    static $gam = [
        'san jose' => ['san jose', 'escazu', 'desamparados', 'aserri', 'mora', 'goicoechea',
                       'santa ana', 'alajuelita', 'vazquez de coronado', 'tibas', 'moravia',
                       'montes de oca', 'curridabat'],
        'alajuela' => ['alajuela', 'atenas', 'poas'],
        'cartago'  => ['cartago', 'paraiso', 'la union', 'oreamuno', 'alvarado', 'el guarco'],
        'heredia'  => ['heredia', 'barva', 'santo domingo', 'santa barbara', 'san rafael',
                       'san isidro', 'belen', 'flores', 'san pablo'],
    ];

    $p = str_replace('_', ' ', normalizar_clave($provincia));
    $c = str_replace('_', ' ', normalizar_clave($canton));

    return isset($gam[$p]) && in_array($c, $gam[$p], true);
}

/**
 * Recuerda qué fila del CSV se está insertando.
 *
 * Sirve para que un error del motor (un dato más largo que su columna, por
 * ejemplo) diga en qué fila y con qué contenido pasó. Sin esto, MySQL informa
 * "Data too long for column 'telefono' at row 1" —donde "row 1" es la del
 * INSERT, no la del archivo— y hay que buscar a mano entre mil filas.
 */
function fila_actual(?int $numero = null, ?array $fila = null): array
{
    static $actual = ['numero' => null, 'fila' => null];

    if ($numero !== null) {
        // +2: la numeración del arreglo empieza en 0 y el archivo tiene
        // encabezados, así que este es el número de línea que se ve en el Sheet.
        $actual = ['numero' => $numero + 2, 'fila' => $fila];
    }

    return $actual;
}

// ------------------------------------------------------------------
// Importadores por destino
// ------------------------------------------------------------------

$filas = leer_csv($archivo);
echo "Archivo: {$archivo}\n";
echo "Filas leídas: " . count($filas) . "\n";
echo "Destino: {$destino}\n\n";

if ($filas === []) {
    exit("El archivo no tiene filas de datos.\n");
}

$pdo = db();
$insertados = 0;
$actualizados = 0;
$omitidos = 0;

// Se envuelve todo en una transacción: si el CSV trae una fila corrupta a la
// mitad, no queda media importación aplicada.
$pdo->beginTransaction();

try {
    switch ($destino) {

        // ----------------------------------------------------------
        case 'sedes':
            // Las sedes pertenecen a una convocatoria: el mismo código es un
            // colegio distinto cada año.
            $convId = id_convocatoria($convocatoria);

            $sql = $pdo->prepare("
                INSERT INTO sedes (convocatoria_id, codigo, nombre, director, contacto_conserje,
                                   telefono, correo, direccion, descripcion, imagen_url,
                                   provincia, canton, distrito, en_gam)
                VALUES (:conv, :codigo, :nombre, :director, :conserje,
                        :telefono, :correo, :direccion, :descripcion, :imagen,
                        :provincia, :canton, :distrito, :gam)
                ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    director = VALUES(director),
                    contacto_conserje = VALUES(contacto_conserje),
                    telefono = VALUES(telefono),
                    correo = VALUES(correo),
                    direccion = VALUES(direccion),
                    descripcion = VALUES(descripcion),
                    imagen_url = VALUES(imagen_url),
                    provincia = VALUES(provincia),
                    canton = VALUES(canton),
                    distrito = VALUES(distrito),
                    en_gam = VALUES(en_gam)
            ");

            foreach ($filas as $numero => $f) {
                $codigo = campo($f, ['idSede', 'id_sede', 'SEDE', 'codigo']);
                $nombre = campo($f, ['NombreSede', 'nombre_sede', 'NOMBRE', 'nombre']);

                if ($codigo === '' || $nombre === '') {
                    $omitidos++;
                    continue;
                }

                $provincia = campo($f, ['Provincia']);
                $canton    = campo($f, ['Cantón', 'Canton']);

                fila_actual($numero, $f);
                $sql->execute([
                    'conv'        => $convId,
                    'codigo'      => $codigo,
                    'nombre'      => $nombre,
                    'director'    => campo($f, ['Director']) ?: null,
                    'conserje'    => campo($f, ['ContactoConserje', 'contacto_conserje']) ?: null,
                    'telefono'    => campo($f, ['Telefono', 'teléfono']) ?: null,
                    'correo'      => campo($f, ['Email', 'correo']) ?: null,
                    'direccion'   => campo($f, ['Direccion', 'dirección']) ?: null,
                    'descripcion' => campo($f, ['Descripcion', 'descripción']) ?: null,
                    'imagen'      => campo($f, ['ImagenURL', 'imagen_url']) ?: null,
                    'provincia'   => $provincia ?: null,
                    'canton'      => $canton ?: null,
                    'distrito'    => campo($f, ['Distrito']) ?: null,
                    'gam'         => es_gam($provincia, $canton) ? 1 : 0,
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;
            }

            // --reemplazar: quitar las sedes que no vienen en el archivo.
            //
            // No se hace un DELETE a ciegas: hay tablas que referencian sedes
            // (aulas, tickets, sobresueldos, actas...). Una sede con historial
            // no se puede borrar sin borrar ese historial, así que esas se dan
            // de baja en vez de eliminarse. El resto sí se borra.
            if ($reemplazar) {
                echo "\n--reemplazar: quitando las sedes de esta convocatoria que no están en el archivo...\n";

                $codigos = [];
                foreach ($filas as $f) {
                    $c = campo($f, ['idSede', 'id_sede', 'SEDE', 'codigo']);
                    if ($c !== '') {
                        $codigos[] = $c;
                    }
                }

                // Acotado a la convocatoria: las sedes de otros años no se
                // tocan, que es justamente lo que se busca al tenerlas
                // separadas por convocatoria.
                $marcadores = implode(',', array_fill(0, count($codigos), '?'));
                $sobrantes = $pdo->prepare(
                    "SELECT id, codigo, nombre FROM sedes
                     WHERE convocatoria_id = ? AND codigo NOT IN ({$marcadores})"
                );
                $sobrantes->execute(array_merge([$convId], $codigos));

                $borrar     = $pdo->prepare('DELETE FROM sedes WHERE id = ?');
                $desactivar = $pdo->prepare('UPDATE sedes SET activo = 0 WHERE id = ?');
                $borradas = 0;
                $dadasDeBaja = [];

                foreach ($sobrantes->fetchAll() as $vieja) {
                    try {
                        $borrar->execute([(int) $vieja['id']]);
                        $borradas++;
                    } catch (PDOException $e) {
                        // 23000 = la sede está referenciada por otra tabla
                        if ($e->getCode() !== '23000') {
                            throw $e;
                        }
                        $desactivar->execute([(int) $vieja['id']]);
                        $dadasDeBaja[] = $vieja['codigo'] . ' — ' . $vieja['nombre'];
                    }
                }

                echo "  · borradas: {$borradas}\n";

                if ($dadasDeBaja !== []) {
                    echo "  · dadas de baja (tienen datos asociados, no se pueden borrar): "
                        . count($dadasDeBaja) . "\n";
                    foreach (array_slice($dadasDeBaja, 0, 10) as $d) {
                        echo "      {$d}\n";
                    }
                    if (count($dadasDeBaja) > 10) {
                        echo "      ... y " . (count($dadasDeBaja) - 10) . " más\n";
                    }
                    echo "    Quedan en la base pero no se muestran en el sistema.\n";
                }
            }
            break;

        // ----------------------------------------------------------
        case 'personal':
            // Hoja "Personal" de Activos: Área, Persona, Correo, Teléfono 1, Teléfono 2
            $insPersona = $pdo->prepare("
                INSERT INTO personas (area_id, nombre, correo, activo)
                VALUES (:area, :nombre, :correo, 1)
                ON DUPLICATE KEY UPDATE
                    nombre  = VALUES(nombre),
                    area_id = COALESCE(VALUES(area_id), area_id),
                    id = LAST_INSERT_ID(id)
            ");
            $buscaPorNombre = $pdo->prepare('SELECT id FROM personas WHERE nombre = ? LIMIT 1');
            $insTel = $pdo->prepare("
                INSERT INTO persona_telefonos (persona_id, telefono)
                SELECT :pid, :tel FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM persona_telefonos
                    WHERE persona_id = :pid2 AND telefono = :tel2
                )
            ");

            foreach ($filas as $f) {
                $nombre = campo($f, ['Persona', 'nombre', 'nombre_completo']);
                if ($nombre === '') {
                    $omitidos++;
                    continue;
                }

                $correo = campo($f, ['Correo', 'correo_electronico', 'email']);
                $areaId = id_area(campo($f, ['Área', 'Area', 'area']));

                if ($correo !== '') {
                    // El correo es UNIQUE: el UPSERT resuelve a quien ya exista
                    // del directorio (módulo 1) en vez de duplicarlo.
                    $insPersona->execute([
                        'area'   => $areaId,
                        'nombre' => $nombre,
                        'correo' => $correo,
                    ]);
                    $personaId = (int) $pdo->lastInsertId();
                    $insPersona->rowCount() === 1 ? $insertados++ : $actualizados++;
                } else {
                    // Sin correo no hay clave natural; se busca por nombre exacto.
                    $buscaPorNombre->execute([$nombre]);
                    $personaId = (int) ($buscaPorNombre->fetchColumn() ?: 0);

                    if ($personaId === 0) {
                        $pdo->prepare('INSERT INTO personas (area_id, nombre, activo) VALUES (?, ?, 1)')
                            ->execute([$areaId, $nombre]);
                        $personaId = (int) $pdo->lastInsertId();
                        $insertados++;
                    } else {
                        $actualizados++;
                    }
                }

                foreach (['Teléfono 1', 'Telefono 1', 'Teléfono 2', 'Telefono 2', 'TELEFONO'] as $col) {
                    $tel = campo($f, [$col]);
                    if ($tel !== '') {
                        $insTel->execute([
                            'pid' => $personaId, 'tel' => $tel,
                            'pid2' => $personaId, 'tel2' => $tel,
                        ]);
                    }
                }
            }
            break;

        // ----------------------------------------------------------
        case 'aulas':
            // La fecha de aplicación sale del propio archivo (columna FECHA).
            $fechaArchivo = null;
            foreach ($filas as $f) {
                $texto = campo($f, ['FECHA', 'fecha']);
                if ($texto !== '') {
                    $fechaArchivo = fecha_iso($texto);
                    break;
                }
            }

            $convId = id_convocatoria($convocatoria, $fechaArchivo);

            $sql = $pdo->prepare("
                INSERT INTO aulas (
                    convocatoria_id, sede_id, numero_aula, formula, asignados,
                    folleto_desde, folleto_hasta, grupo_embalaje, coord_desde, coord_hasta,
                    personas_por_aula, total_folletos, total_folletos_coord, total_cajas,
                    total_aulas, adecuaciones, turno, coordinador_nombre, coordinador_correo,
                    codigo_barras, estado
                ) VALUES (
                    :conv, :sede, :aula, :formula, :asignados,
                    :desde, :hasta, :grupo, :cdesde, :chasta,
                    :personas, :folletos, :folletos_coord, :cajas,
                    :total_aulas, :adecuaciones, :turno, :coord_nombre, :coord_correo,
                    :codigo, :estado
                )
                ON DUPLICATE KEY UPDATE
                    formula = VALUES(formula),
                    asignados = VALUES(asignados),
                    folleto_desde = VALUES(folleto_desde),
                    folleto_hasta = VALUES(folleto_hasta),
                    grupo_embalaje = VALUES(grupo_embalaje),
                    coord_desde = VALUES(coord_desde),
                    coord_hasta = VALUES(coord_hasta),
                    personas_por_aula = VALUES(personas_por_aula),
                    total_folletos = VALUES(total_folletos),
                    total_folletos_coord = VALUES(total_folletos_coord),
                    total_cajas = VALUES(total_cajas),
                    total_aulas = VALUES(total_aulas),
                    adecuaciones = VALUES(adecuaciones),
                    turno = VALUES(turno),
                    coordinador_nombre = VALUES(coordinador_nombre),
                    coordinador_correo = VALUES(coordinador_correo),
                    codigo_barras = VALUES(codigo_barras),
                    id = LAST_INSERT_ID(id)
            ");

            // Los marchamos van a su tabla. Se reemplazan los de la posición
            // correspondiente en vez de acumularlos.
            // Los marchamos ya no se importan: desde la migración 014 se le
            // ponen a la TULA, que es el bulto que se sella, y se asignan
            // escaneándolos en el paso 5 del módulo Revisión. Importarlos por
            // aula crearía pares repetidos que la base ahora rechaza.

            // Enlaza el aula con la persona del catálogo cuando el correo calza.
            $ligaPersona = $pdo->prepare("
                UPDATE aulas a
                INNER JOIN personas p ON p.correo = a.coordinador_correo
                SET a.persona_id = p.id
                WHERE a.id = ? AND a.persona_id IS NULL
            ");

            foreach ($filas as $f) {
                $codigoSede = campo($f, ['SEDE', 'sede', 'idSede']);
                $numeroAula = campo($f, ['NUMERO_AULA_EXAMEN', 'numero_aula_examen', 'aula']);

                if ($codigoSede === '' || $numeroAula === '') {
                    $omitidos++;
                    continue;
                }

                $sedeId = id_sede($convId, $codigoSede, campo($f, ["NOMBRE", "NombreSede"]));

                // El Sheet traía estados libres; se acota a la lista blanca que
                // ya validaba el Apps Script.
                $estado = normalizar_clave(campo($f, ['estado'], 'disponible'));
                if (!in_array($estado, ['disponible', 'prestado', 'devuelto'], true)) {
                    $estado = 'disponible';
                }

                $codigoBarras = campo($f, ['codigo_barras', 'CODIGO_BARRAS']);

                $sql->execute([
                    'conv'           => $convId,
                    'sede'           => $sedeId,
                    'aula'           => $numeroAula,
                    'formula'        => campo($f, ['FORMULA']) ?: null,
                    'asignados'      => entero($f, ['ASIGNADOS']),
                    'desde'          => campo($f, ['Desde']) ?: null,
                    'hasta'          => campo($f, ['Hasta']) ?: null,
                    'grupo'          => campo($f, ['grupo_embalaje']) ?: null,
                    'cdesde'         => campo($f, ['desde_coord']) ?: null,
                    'chasta'         => campo($f, ['hasta_coord']) ?: null,
                    'personas'       => campo($f, ['Personas por aula']) ?: null,
                    'folletos'       => entero($f, ['TOTAL FOLLETOS', 'total2']),
                    'folletos_coord' => campo($f, ['TOTAL FOLLETOS COORD.']) ?: null,
                    // La base 2026 llama a estas columnas distinto que el Sheet
                    // anterior: "TULAS O CAJAS" y "Total_Aulas".
                    'cajas'          => campo($f, ['TOTAL CAJAS', 'TULAS O CAJAS', 'Total TULAS']) ?: null,
                    'total_aulas'    => campo($f, ['TOTAL AULAS', 'Total_Aulas']) ?: null,
                    'adecuaciones'   => campo($f, ['Adecuaciones']) ?: null,
                    // En la base de embalaje 2026 el turno viene en la columna
                    // CONVOCATORIA ("1- M-SABADO", "2-T-SABADO"...): cada sede
                    // aparece en uno solo, así que es el turno de la sede y no
                    // una convocatoria aparte. La convocatoria del sistema es
                    // la que se pasa por --convocatoria.
                    'turno'          => campo($f, ['Turno', 'CONVOCATORIA']) ?: null,
                    'coord_nombre'   => campo($f, ['Cordinador', 'Coordinador']) ?: null,
                    'coord_correo'   => campo($f, ['correo coordinador', 'correo_coordinador']) ?: null,
                    'codigo'         => $codigoBarras !== '' ? $codigoBarras : null,
                    'estado'         => $estado,
                ]);

                $aulaId = (int) $pdo->lastInsertId();
                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;

                $ligaPersona->execute([$aulaId]);
            }
            break;

        // ----------------------------------------------------------
        case 'activos':
            $sql = $pdo->prepare("
                INSERT INTO activos (codigo, nombre, serie_placa, imei, accesorios, estado_obs, disponible)
                VALUES (:codigo, :nombre, :serie, :imei, :accesorios, :estado, :disponible)
                ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    serie_placa = VALUES(serie_placa),
                    imei = VALUES(imei),
                    accesorios = VALUES(accesorios),
                    estado_obs = VALUES(estado_obs),
                    disponible = VALUES(disponible)
            ");

            // La columna "Disponible" no es un sí/no: cuando el equipo está
            // prestado, la celda trae el NOMBRE de quien lo tiene ("Marisel",
            // "Guaner Rojas", "ANDREI FALLAS ROJAS"). Ese nombre se convierte
            // en un préstamo abierto, que es donde corresponde: así no se
            // pierde quién tiene cada cosa y queda enlazado al funcionario.
            $buscarActivo  = $pdo->prepare('SELECT id FROM activos WHERE codigo = ?');
            $buscarPersona = $pdo->prepare('SELECT id FROM personas WHERE nombre = ? AND activo = 1 LIMIT 1');
            $cerrarPrevios = $pdo->prepare("
                UPDATE prestamos SET estado = 'devuelto', devuelto_en = NOW()
                WHERE activo_id = ? AND estado = 'prestado'
            ");
            $abrirPrestamo = $pdo->prepare("
                INSERT INTO prestamos (boleta, activo_id, persona_id, funcionario, estado)
                VALUES (?, ?, ?, ?, 'prestado')
            ");
            $prestados = 0;
            $sinCalzar = [];

            foreach ($filas as $numero => $f) {
                $codigo = campo($f, ['Codigo', 'Código', 'codigo']);
                if ($codigo === '') {
                    $omitidos++;
                    continue;
                }

                $celda = campo($f, ['Disponible']);
                // Vacío se asume disponible, que es como se comportaba la página.
                $disponible = ($celda === '' || es_si($celda));

                fila_actual($numero, $f);
                $sql->execute([
                    'codigo'     => $codigo,
                    'nombre'     => campo($f, ['Nombre'], '(sin nombre)'),
                    'serie'      => campo($f, ['Serie/Placa', 'serie_placa', 'Serie']) ?: null,
                    'imei'       => campo($f, ['IMEI']) ?: null,
                    'accesorios' => campo($f, ['Accesorios']) ?: null,
                    'estado'     => campo($f, ['Estado/Obs', 'estado_obs', 'Estado']) ?: null,
                    'disponible' => $disponible ? 1 : 0,
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;

                // Si la celda traía un nombre, se registra el préstamo.
                if (!$disponible && normalizar_clave($celda) !== 'no') {
                    $buscarActivo->execute([$codigo]);
                    $activoId = (int) $buscarActivo->fetchColumn();

                    $cerrarPrevios->execute([$activoId]);

                    $buscarPersona->execute([$celda]);
                    $personaId = $buscarPersona->fetchColumn();

                    if ($personaId === false) {
                        // El nombre del Sheet suele ser parcial ("Marisel",
                        // "Danny"): se intenta calzar con el catálogo por el
                        // principio del nombre antes de darlo por desconocido.
                        $porParte = $pdo->prepare(
                            'SELECT id FROM personas WHERE activo = 1 AND nombre LIKE ? LIMIT 2'
                        );
                        $porParte->execute([$celda . '%']);
                        $posibles = $porParte->fetchAll();
                        // Solo si no hay ambigüedad: con dos candidatos, elegir
                        // uno sería adivinar a quién se le prestó el equipo.
                        $personaId = count($posibles) === 1 ? $posibles[0]['id'] : false;
                    }

                    if ($personaId === false) {
                        $sinCalzar[] = $celda;
                    }

                    $pdo->prepare('UPDATE secuencia_boletas SET ultimo = ultimo + 1 WHERE id = 1')->execute();
                    $boleta = (int) $pdo->query('SELECT ultimo FROM secuencia_boletas WHERE id = 1')->fetchColumn();

                    $abrirPrestamo->execute([
                        $boleta,
                        $activoId,
                        $personaId !== false ? (int) $personaId : null,
                        $celda,
                    ]);
                    $prestados++;
                }
            }

            if ($prestados > 0) {
                echo "  · préstamos abiertos según la columna Disponible: {$prestados}\n";
            }
            if ($sinCalzar !== []) {
                echo "  · nombres que no calzan con el directorio (se guardan igual como texto): "
                    . implode(', ', array_unique($sinCalzar)) . "\n";
            }
            break;

        // ----------------------------------------------------------
        case 'prestamos':
            $buscaActivo  = $pdo->prepare('SELECT id FROM activos WHERE codigo = ?');
            $buscaPersona = $pdo->prepare('SELECT id FROM personas WHERE nombre = ? LIMIT 1');
            $sql = $pdo->prepare("
                INSERT INTO prestamos (boleta, activo_id, persona_id, funcionario, observacion, estado, prestado_en)
                VALUES (:boleta, :activo, :persona, :funcionario, :observacion, :estado, :fecha)
                ON DUPLICATE KEY UPDATE
                    estado = VALUES(estado),
                    observacion = VALUES(observacion)
            ");
            $maxBoleta = 0;

            foreach ($filas as $f) {
                $codigo = campo($f, ['Codigo', 'Código']);
                $buscaActivo->execute([$codigo]);
                $activoId = $buscaActivo->fetchColumn();

                if ($activoId === false) {
                    echo "  ⚠ préstamo omitido: el activo {$codigo} no existe (importá primero 'activos')\n";
                    $omitidos++;
                    continue;
                }

                $funcionario = campo($f, ['Funcionario'], '(sin nombre)');
                $buscaPersona->execute([$funcionario]);
                $personaId = $buscaPersona->fetchColumn();

                $boleta = entero($f, ['Boleta']);
                if ($boleta === 0) {
                    $omitidos++;
                    continue;
                }
                $maxBoleta = max($maxBoleta, $boleta);

                // "PRESTADO"/"DEVUELTO" del Sheet → enum
                $estadoSheet = normalizar_clave(campo($f, ['EstadoPrestamo', 'estado_prestamo']));
                $estado = $estadoSheet === 'devuelto' ? 'devuelto' : 'prestado';

                // "27/09/2025 14:30" y variantes; si no se entiende, queda ahora.
                $fechaTexto = campo($f, ['FechaHora', 'fecha_hora', 'Fecha']);
                $fecha = $fechaTexto !== '' ? strtotime(str_replace('/', '-', $fechaTexto)) : false;

                $sql->execute([
                    'boleta'      => $boleta,
                    'activo'      => (int) $activoId,
                    'persona'     => $personaId !== false ? (int) $personaId : null,
                    'funcionario' => $funcionario,
                    'observacion' => campo($f, ['Observacion', 'Observación']) ?: null,
                    'estado'      => $estado,
                    'fecha'       => date('Y-m-d H:i:s', $fecha !== false ? $fecha : time()),
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;
            }

            // La secuencia debe continuar donde terminó el Sheet, o la próxima
            // boleta chocaría con una ya importada.
            if ($maxBoleta > 0) {
                $pdo->prepare('UPDATE secuencia_boletas SET ultimo = GREATEST(ultimo, ?) WHERE id = 1')
                    ->execute([$maxBoleta]);
                echo "  · secuencia de boletas puesta en {$maxBoleta}\n";
            }
            break;

        // ----------------------------------------------------------
        case 'materiales':
            $sql = $pdo->prepare("
                INSERT INTO materiales (nombre, unidad, orden)
                VALUES (:nombre, :unidad, :orden)
                ON DUPLICATE KEY UPDATE
                    unidad = VALUES(unidad),
                    orden = VALUES(orden)
            ");

            foreach ($filas as $i => $f) {
                $nombre = campo($f, ['Material', 'nombre', 'MATERIAL', 'descripcion']);
                if ($nombre === '') {
                    $omitidos++;
                    continue;
                }

                $sql->execute([
                    'nombre' => $nombre,
                    'unidad' => campo($f, ['Unidad', 'unidad']) ?: null,
                    'orden'  => entero($f, ['Orden'], $i + 1),
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;
            }
            break;

        // ----------------------------------------------------------
        case 'sobresueldos':
            $convId = id_convocatoria($convocatoria);

            $sql = $pdo->prepare("
                INSERT INTO sobresueldos (
                    convocatoria_id, sede_id, descripcion, aulas,
                    coord_regular, coord_adecuacion, aplicador, apoyo, una, ucr,
                    servicio_conserjeria, dia1, dia2, dia3, presupuesto, desglose
                ) VALUES (
                    :conv, :sede, :descripcion, :aulas,
                    :coord, :coord_adec, :aplicador, :apoyo, :una, :ucr,
                    :conserje, :dia1, :dia2, :dia3, :presupuesto, :desglose
                )
                ON DUPLICATE KEY UPDATE
                    descripcion = VALUES(descripcion),
                    aulas = VALUES(aulas),
                    coord_regular = VALUES(coord_regular),
                    coord_adecuacion = VALUES(coord_adecuacion),
                    aplicador = VALUES(aplicador),
                    apoyo = VALUES(apoyo),
                    una = VALUES(una),
                    ucr = VALUES(ucr),
                    servicio_conserjeria = VALUES(servicio_conserjeria),
                    dia1 = VALUES(dia1),
                    dia2 = VALUES(dia2),
                    dia3 = VALUES(dia3),
                    presupuesto = VALUES(presupuesto),
                    desglose = VALUES(desglose)
            ");

            foreach ($filas as $numero => $f) {
                // El Sheet llama a esta columna SEDE_EXAMEN, no SEDE como el
                // de tulas. Se aceptan las dos formas.
                $codigoSede = campo($f, ['SEDE_EXAMEN', 'SEDE', 'sede']);
                if ($codigoSede === '') {
                    $omitidos++;
                    continue;
                }

                fila_actual($numero, $f);
                $sql->execute([
                    'conv'        => $convId,
                    'sede'        => id_sede($convId, $codigoSede, campo($f, ["DESCRIPCION", "descripcion"])),
                    'descripcion' => campo($f, ['DESCRIPCION', 'descripcion']) ?: null,
                    'aulas'       => entero($f, ['AULAS', 'aulas']),
                    'coord'       => campo($f, ['COORD_REGULAR', 'coordinador']) ?: null,
                    'coord_adec'  => campo($f, ['COORD_ADECUACION']) ?: null,
                    'aplicador'   => campo($f, ['APLICADOR']) ?: null,
                    'apoyo'       => campo($f, ['APOYO']) ?: null,
                    'una'         => campo($f, ['UNA']) ?: null,
                    'ucr'         => campo($f, ['UCR']) ?: null,
                    'conserje'    => campo($f, ['CONSERJE', 'SERVICIO_CONSERJERIA']) ?: null,
                    // En el Sheet los días llevan espacio: "DIA 1", "DIA 2"...
                    'dia1'        => campo($f, ['DIA 1', 'DIA1']) ?: null,
                    'dia2'        => campo($f, ['DIA 2', 'DIA2']) ?: null,
                    'dia3'        => campo($f, ['DIA 3', 'DIA3']) ?: null,
                    'presupuesto' => entero($f, ['PRESUPUESTO']),
                    'desglose'    => campo($f, ['DESGLOSE']) ?: null,
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;
            }
            break;

        // ----------------------------------------------------------
        case 'tarifas_hospedaje':
            // Catálogo de tarifas por ubicación, no un registro de gastos:
            // ver schema/012_tarifas_hospedaje.sql. La página de Sobresueldos
            // lo usa para calcular el presupuesto de cada sede.
            $sql = $pdo->prepare("
                INSERT INTO tarifas_hospedaje (provincia, canton, distrito, tarifa)
                VALUES (:provincia, :canton, :distrito, :tarifa)
                ON DUPLICATE KEY UPDATE tarifa = VALUES(tarifa)
            ");

            foreach ($filas as $numero => $f) {
                $provincia = campo($f, ['Provincia']);
                $canton    = campo($f, ['Cantón', 'Canton']);
                $distrito  = campo($f, ['Distrito']);
                $tarifa    = entero($f, ['Tarifa', 'Monto']);

                // Sin ubicación completa la fila no sirve: la página busca la
                // tarifa por las tres cosas juntas.
                if ($provincia === '' || $canton === '' || $distrito === '' || $tarifa === 0) {
                    $omitidos++;
                    continue;
                }

                fila_actual($numero, $f);
                $sql->execute([
                    'provincia' => $provincia,
                    'canton'    => $canton,
                    'distrito'  => $distrito,
                    'tarifa'    => $tarifa,
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;
            }
            break;


        // ----------------------------------------------------------
        case 'postulantes':
            $convId = $convocatoria !== null ? id_convocatoria($convocatoria) : null;

            $sql = $pdo->prepare("
                INSERT INTO postulantes (
                    nombre, identificacion, correo, telefono, telefono_oficina,
                    provincia, canton, distrito, direccion, en_gam,
                    grado_academico, lugar_trabajo, tipo_nombramiento, anios_experiencia,
                    disponible, convocatoria_id
                ) VALUES (
                    :nombre, :ident, :correo, :telefono, :oficina,
                    :provincia, :canton, :distrito, :direccion, :gam,
                    :grado, :trabajo, :nombramiento, :experiencia,
                    :disponible, :conv
                )
                ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    correo = VALUES(correo),
                    telefono = VALUES(telefono),
                    provincia = VALUES(provincia),
                    canton = VALUES(canton),
                    distrito = VALUES(distrito),
                    en_gam = VALUES(en_gam),
                    grado_academico = VALUES(grado_academico),
                    lugar_trabajo = VALUES(lugar_trabajo),
                    anios_experiencia = VALUES(anios_experiencia)
            ");

            foreach ($filas as $numero => $f) {
                $nombre = campo($f, ['Nombre completo', 'nombre y apellidos', 'nombre']);
                if ($nombre === '') {
                    $omitidos++;
                    continue;
                }

                fila_actual($numero, $f);

                // La residencia venía como "provincia, cantón, distrito" en una
                // sola celda; el JS la partía en cada render (parseResidenceParts).
                $residencia = campo($f, ['Direccion de residencia', 'residencia',
                                         'provincia canton distrito', 'direccion']);
                $partes = array_map('trim', explode(',', $residencia));
                $provincia = $partes[0] ?? '';
                $canton    = $partes[1] ?? '';
                $distrito  = $partes[2] ?? '';

                $enGam = es_gam($provincia, $canton);

                $sql->execute([
                    'nombre'       => $nombre,
                    'ident'        => campo($f, ['Numero de identificacion', 'identificacion', 'cedula']) ?: null,
                    'correo'       => campo($f, ['Correo electronico', 'correo', 'email']) ?: null,
                    'telefono'     => campo($f, ['Numero de telefono', 'telefono']) ?: null,
                    'oficina'      => campo($f, ['Telefono de oficina', 'numero de telefono de oficina']) ?: null,
                    'provincia'    => $provincia ?: null,
                    'canton'       => $canton ?: null,
                    'distrito'     => $distrito ?: null,
                    'direccion'    => $residencia ?: null,
                    'gam'          => $enGam ? 1 : 0,
                    'grado'        => campo($f, ['Grado academico', 'grado']) ?: null,
                    'trabajo'      => campo($f, ['Lugar de trabajo', 'trabajo']) ?: null,
                    'nombramiento' => campo($f, ['Tipo de nombramiento']) ?: null,
                    'experiencia'  => entero($f, ['Anios de experiencia', 'experiencia']) ?: null,
                    'disponible'   => 1,
                    'conv'         => $convId,
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;
            }
            break;

        // ----------------------------------------------------------
        case 'materiales_adecuacion':
            // A diferencia de 'aulas' y 'sedes', acá NO se crea nada al vuelo:
            // el aula tiene que existir ya (importada antes con 'aulas') y
            // estar marcada como de adecuación. Si no calza, se avisa en vez
            // de inventar un aula que después nadie sabría de dónde salió.
            $convId = id_convocatoria($convocatoria);

            $buscarAula = $pdo->prepare("
                SELECT a.id AS aula_id, a.sede_id,
                       (a.es_adecuacion = 1 OR (a.folleto_adec_desde IS NOT NULL AND a.folleto_adec_desde <> '')) AS calza
                FROM aulas a
                INNER JOIN sedes s ON s.id = a.sede_id
                WHERE s.convocatoria_id = ? AND s.codigo = ? AND a.numero_aula = ?
            ");

            $sql = $pdo->prepare("
                INSERT INTO aula_materiales_adecuacion
                    (aula_id, sede_id, convocatoria_id, calc_simple, calc_parlante,
                     atril, mesa, pizarras, audifonos, tableta, computadora, diccionario, lampara)
                VALUES (:aula, :sede, :conv, :calc_simple, :calc_parlante,
                        :atril, :mesa, :pizarras, :audifonos, :tableta, :computadora, :diccionario, :lampara)
                ON DUPLICATE KEY UPDATE
                    calc_simple = VALUES(calc_simple), calc_parlante = VALUES(calc_parlante),
                    atril = VALUES(atril), mesa = VALUES(mesa), pizarras = VALUES(pizarras),
                    audifonos = VALUES(audifonos), tableta = VALUES(tableta),
                    computadora = VALUES(computadora), diccionario = VALUES(diccionario),
                    lampara = VALUES(lampara)
            ");

            $marcarSede = $pdo->prepare(
                'UPDATE sedes SET materiales_adecuacion = 1 WHERE id = ? AND materiales_adecuacion = 0'
            );

            $sedesMarcadas = [];
            $noEncontradas = [];
            $noCalzan = [];

            foreach ($filas as $numero => $f) {
                $codigoSede = campo($f, ['Sede', 'SEDE', 'sede']);
                $numeroAula = campo($f, ['# Aula', 'Aula', 'aula', 'numero_aula', 'NUMERO_AULA_EXAMEN']);

                if ($codigoSede === '' || $numeroAula === '') {
                    $omitidos++;
                    continue;
                }

                fila_actual($numero, $f);

                $buscarAula->execute([$convId, $codigoSede, $numeroAula]);
                $aula = $buscarAula->fetch();

                if ($aula === false) {
                    $omitidos++;
                    $noEncontradas[] = "sede {$codigoSede}, aula {$numeroAula}";
                    continue;
                }
                if (!(bool) $aula['calza']) {
                    $omitidos++;
                    $noCalzan[] = "sede {$codigoSede}, aula {$numeroAula}";
                    continue;
                }

                // Igual que en la pantalla manual (ver guardar_materiales_adecuacion
                // en api/ingreso_datos.php): texto libre, vacío se guarda como NULL.
                $valor = static function (array $f, array $candidatos): ?string {
                    $v = campo($f, $candidatos);
                    return $v !== '' ? mb_substr($v, 0, 80) : null;
                };

                $sql->execute([
                    'aula'        => (int) $aula['aula_id'],
                    'sede'        => (int) $aula['sede_id'],
                    'conv'        => $convId,
                    'calc_simple'   => $valor($f, ['Calculadora simple', 'calc_simple']),
                    'calc_parlante' => $valor($f, ['Calculadora parlante', 'calc_parlante']),
                    'atril'         => $valor($f, ['Atril']),
                    'mesa'          => $valor($f, ['Mesa']),
                    'pizarras'      => $valor($f, ['Pizarras', 'Pizarra']),
                    'audifonos'     => $valor($f, ['Audífonos', 'Audifonos']),
                    'tableta'       => $valor($f, ['Tableta']),
                    'computadora'   => $valor($f, ['Computadora']),
                    'diccionario'   => $valor($f, ['Diccionario']),
                    'lampara'       => $valor($f, ['Lámpara', 'Lampara']),
                ]);

                $sql->rowCount() === 1 ? $insertados++ : $actualizados++;

                $sedeId = (int) $aula['sede_id'];
                if (!isset($sedesMarcadas[$sedeId])) {
                    $marcarSede->execute([$sedeId]);
                    $sedesMarcadas[$sedeId] = true;
                }
            }

            if ($noEncontradas !== []) {
                echo "  ⚠ aulas que no existen en el sistema (importá primero 'aulas'): "
                    . count($noEncontradas) . "\n";
                foreach (array_slice($noEncontradas, 0, 15) as $d) {
                    echo "      {$d}\n";
                }
                if (count($noEncontradas) > 15) {
                    echo "      ... y " . (count($noEncontradas) - 15) . " más\n";
                }
            }
            if ($noCalzan !== []) {
                echo "  ⚠ aulas que existen pero no están marcadas como de adecuación: "
                    . count($noCalzan) . "\n";
                foreach (array_slice($noCalzan, 0, 15) as $d) {
                    echo "      {$d}\n";
                }
                if (count($noCalzan) > 15) {
                    echo "      ... y " . (count($noCalzan) - 15) . " más\n";
                }
            }
            if ($sedesMarcadas !== []) {
                echo "  · sedes marcadas con \"materiales de adecuación\" activado: "
                    . count($sedesMarcadas) . "\n";
            }
            break;
    }

    $pdo->commit();

} catch (Throwable $e) {
    $pdo->rollBack();

    echo "\nERROR: " . $e->getMessage() . "\n";

    // Decir en qué fila fue. MySQL informa "at row 1" refiriéndose al INSERT,
    // no al archivo, así que por sí solo ese mensaje no sirve para encontrarla.
    $ubicacion = fila_actual();
    if ($ubicacion['numero'] !== null) {
        echo "\nOcurrió en la línea {$ubicacion['numero']} del archivo. Contenido:\n";
        foreach ($ubicacion['fila'] ?? [] as $columna => $valor) {
            if (trim((string) $valor) !== '') {
                printf("  %-22s (%3d) %s\n", $columna, mb_strlen((string) $valor), mb_substr((string) $valor, 0, 90));
            }
        }
        echo "\nEl número entre paréntesis es la cantidad de caracteres: si el error\n";
        echo "dice 'Data too long', ahí se ve cuál campo no cabe.\n";
    }

    echo "\nNo se importó nada: la transacción se revirtió completa.\n";
    exit(1);
}

echo "\n";
echo "Insertados:   {$insertados}\n";
echo "Actualizados: {$actualizados}\n";
echo "Omitidos:     {$omitidos}\n";
echo "\nListo.\n";
