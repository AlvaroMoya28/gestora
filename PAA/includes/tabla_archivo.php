<?php
/**
 * Lectura de archivos de tabla (CSV, XLSX y el «.xls» que en realidad es HTML).
 *
 * POR QUÉ ESTÁ ESCRITO A MANO
 * El sistema no usa Composer, así que no hay PhpSpreadsheet. Lo que sí hay es
 * suficiente: un .xlsx es un ZIP con XML adentro, y PHP trae ZipArchive y
 * XMLReader de fábrica. Se lee en streaming porque un padrón de estudiantes
 * son decenas de miles de filas y cargarlo entero en memoria como árbol DOM
 * tumbaría el proceso.
 *
 * LOS TRES FORMATOS Y POR QUÉ LOS TRES
 *   · CSV    — el camino confiable. Es lo que se recomienda en la pantalla.
 *   · XLSX   — lo que la gente tiene abierto en Excel y no quiere convertir.
 *   · «.xls» — el sistema de coordinaciones exporta una tabla HTML con esa
 *              extensión. Ya nos pasó con los coordinadores (ver
 *              scripts/importar_coordinadores.php) y no se puede suponer que
 *              alguien lo va a notar antes de subirlo.
 *
 * El .xls binario de verdad (BIFF, Excel 97-2003) NO se lee, y se dice con esas
 * palabras en vez de devolver filas vacías: un archivo que «se importó» sin
 * datos es mucho peor que uno que se rechazó.
 */

declare(strict_types=1);

/** Tope de filas. Corta un archivo equivocado antes de que agote el servidor. */
const MAX_FILAS_TABLA = 250000;

/**
 * Lee un archivo de tabla y devuelve sus columnas y sus filas.
 *
 * @return array{columnas: string[], filas: array<int, array<string, string>>, formato: string}
 * @throws RuntimeException con un mensaje pensado para mostrarse tal cual.
 */
function leer_archivo_tabular(string $ruta, string $nombre): array
{
    if (!is_file($ruta) || !is_readable($ruta)) {
        throw new RuntimeException('No se pudo leer el archivo subido.');
    }

    $extension = mb_strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

    // La extensión orienta, pero no decide: los primeros bytes son la verdad.
    // Un .xls que empieza con «<» es HTML, y un .csv que alguien renombró
    // desde un .xlsx sigue siendo un ZIP.
    $firma = (string) file_get_contents($ruta, false, null, 0, 8);

    // Gzip. Un CSV comprimido es la forma más chica de mandar un padrón
    // completo: los 56 737 estudiantes de 2026 pesan 6,9 MB en CSV y menos de
    // 1 MB comprimidos, muy por debajo de cualquier tope de subida. Que un CSV
    // pese MÁS que el .xlsx del que salió sorprende, pero es lo esperable: el
    // .xlsx ya es un ZIP por dentro y el CSV es texto plano.
    if (str_starts_with($firma, "\x1f\x8b")) {
        return leer_comprimido($ruta, $nombre);
    }

    if (str_starts_with($firma, "PK\x03\x04")) {
        return leer_xlsx($ruta);
    }

    // D0 CF 11 E0 = documento compuesto de Office. Es el .xls binario.
    if (str_starts_with($firma, "\xD0\xCF\x11\xE0")) {
        throw new RuntimeException(
            'Ese archivo es un Excel viejo (.xls binario), que este sistema no puede leer. '
            . 'Abrilo en Excel y guardalo como «Libro de Excel (.xlsx)» o como CSV UTF-8.'
        );
    }

    $inicio = ltrim((string) file_get_contents($ruta, false, null, 0, 512));
    $inicio = preg_replace('/^\xEF\xBB\xBF/', '', $inicio) ?? $inicio;

    if (str_starts_with(ltrim($inicio), '<')) {
        return leer_html($ruta);
    }

    if (in_array($extension, ['csv', 'txt', 'tsv'], true) || $extension === '') {
        return leer_csv($ruta);
    }

    // Última oportunidad: si no se reconoció la firma pero la extensión dice
    // que es una hoja de cálculo, se intenta como texto separado.
    return leer_csv($ruta);
}

// ------------------------------------------------------------------
// Comprimido
// ------------------------------------------------------------------

/** Cuánto se acepta descomprimir. Corta un archivo inflado a propósito. */
const MAX_BYTES_DESCOMPRIMIDO = 200 * 1024 * 1024;

/**
 * Descomprime a un temporal y lee eso.
 *
 * Se escribe a disco en vez de descomprimir en memoria porque el contenido
 * puede ser de cientos de megas, y porque así el archivo resultante pasa por
 * exactamente el mismo camino que cualquier otro: se le mira la firma y se
 * decide si es CSV, XLSX o HTML. Un .csv.gz y un .xlsx.gz funcionan igual sin
 * escribir nada aparte.
 */
function leer_comprimido(string $ruta, string $nombre): array
{
    $entrada = @gzopen($ruta, 'rb');

    if ($entrada === false) {
        throw new RuntimeException('No se pudo abrir el archivo comprimido.');
    }

    $destino = tempnam(sys_get_temp_dir(), 'paa_gz_');
    $salida  = $destino !== false ? @fopen($destino, 'wb') : false;

    if ($salida === false) {
        gzclose($entrada);
        throw new RuntimeException('El servidor no pudo crear un archivo temporal.');
    }

    $escritos = 0;

    while (!gzeof($entrada)) {
        $trozo = gzread($entrada, 262144);

        if ($trozo === false || $trozo === '') {
            break;
        }

        $escritos += strlen($trozo);

        if ($escritos > MAX_BYTES_DESCOMPRIMIDO) {
            fclose($salida);
            gzclose($entrada);
            @unlink($destino);
            throw new RuntimeException(
                'El archivo comprimido contiene más de lo que este servidor puede procesar.'
            );
        }

        fwrite($salida, $trozo);
    }

    fclose($salida);
    gzclose($entrada);

    // Se le quita el .gz al nombre para que la extensión de adentro oriente
    // igual que si el archivo se hubiera subido sin comprimir.
    $interno = preg_replace('/\.gz$/i', '', $nombre) ?? $nombre;

    try {
        $lectura = leer_archivo_tabular($destino, $interno);
    } finally {
        @unlink($destino);
    }

    return $lectura;
}

// ------------------------------------------------------------------
// CSV
// ------------------------------------------------------------------

/**
 * Lee un CSV averiguando el separador a partir de la primera línea.
 *
 * Excel en español guarda con punto y coma, Excel en inglés con coma, y las
 * exportaciones de bases de datos suelen usar tabulador. Pedirle a alguien que
 * sepa cuál tiene su archivo es pedirle que adivine.
 */
function leer_csv(string $ruta): array
{
    $manejador = fopen($ruta, 'r');

    if ($manejador === false) {
        throw new RuntimeException('No se pudo abrir el archivo.');
    }

    $primera = fgets($manejador);

    if ($primera === false) {
        fclose($manejador);
        throw new RuntimeException('El archivo está vacío.');
    }

    $separador = separador_probable($primera);
    rewind($manejador);

    $columnas = [];
    $filas    = [];
    $numero   = 0;

    while (($celdas = fgetcsv($manejador, 0, $separador, '"', '\\')) !== false) {
        $numero++;

        if ($numero > MAX_FILAS_TABLA + 1) {
            break;
        }

        $celdas = array_map(static fn($c): string => limpiar_celda((string) ($c ?? '')), $celdas);

        if ($numero === 1) {
            // El BOM va pegado al primer encabezado y lo vuelve irreconocible.
            $celdas[0] = preg_replace('/^\xEF\xBB\xBF/', '', $celdas[0]) ?? $celdas[0];
            $columnas  = $celdas;
            continue;
        }

        if (implode('', $celdas) === '') {
            continue;
        }

        $filas[] = combinar($columnas, $celdas);
    }

    fclose($manejador);

    return ['columnas' => $columnas, 'filas' => $filas, 'formato' => 'CSV'];
}

/** Cuál de los separadores habituales aparece más en la línea de encabezados. */
function separador_probable(string $linea): string
{
    $candidatos = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];

    foreach (array_keys($candidatos) as $sep) {
        $candidatos[$sep] = substr_count($linea, $sep);
    }

    arsort($candidatos);
    $mejor = array_key_first($candidatos);

    return $candidatos[$mejor] > 0 ? (string) $mejor : ',';
}

// ------------------------------------------------------------------
// XLSX
// ------------------------------------------------------------------

/**
 * Lee la primera hoja de un .xlsx.
 *
 * Un .xlsx guarda casi todo el texto en un diccionario aparte
 * (xl/sharedStrings.xml) y en la hoja deja solo el índice. Por eso hay que
 * leer los dos archivos y en ese orden.
 */
function leer_xlsx(string $ruta): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException(
            'Este servidor no puede abrir archivos .xlsx. '
            . 'Guardá la hoja como CSV UTF-8 y subila así.'
        );
    }

    $zip = new ZipArchive();

    if ($zip->open($ruta) !== true) {
        throw new RuntimeException('El archivo .xlsx está dañado o incompleto.');
    }

    // La primera hoja no siempre se llama sheet1.xml.
    $hoja = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $interno = (string) $zip->getNameIndex($i);
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $interno)) {
            if ($hoja === null || strnatcmp($interno, $hoja) < 0) {
                $hoja = $interno;
            }
        }
    }

    $zip->close();

    if ($hoja === null) {
        throw new RuntimeException('El archivo .xlsx no tiene ninguna hoja legible.');
    }

    $textos = leer_cadenas_compartidas($ruta);
    return leer_hoja_xlsx($ruta, $hoja, $textos);
}

/** El diccionario de textos del libro, en el orden en que la hoja los indexa. */
function leer_cadenas_compartidas(string $ruta): array
{
    $lector = new XMLReader();

    if (!@$lector->open('zip://' . $ruta . '#xl/sharedStrings.xml')) {
        return [];   // Un libro puede no tener ninguno si todo son números.
    }

    $textos = [];
    $actual = null;

    while ($lector->read()) {
        if ($lector->nodeType === XMLReader::ELEMENT && $lector->name === 'si') {
            $actual = '';
        } elseif ($lector->nodeType === XMLReader::ELEMENT && $lector->name === 't' && $actual !== null) {
            $actual .= $lector->readString();
        } elseif ($lector->nodeType === XMLReader::END_ELEMENT && $lector->name === 'si') {
            $textos[] = $actual ?? '';
            $actual   = null;
        }
    }

    $lector->close();

    return $textos;
}

/** Recorre las filas de una hoja y las devuelve ya resueltas contra el diccionario. */
function leer_hoja_xlsx(string $ruta, string $hoja, array $textos): array
{
    $lector = new XMLReader();

    if (!@$lector->open('zip://' . $ruta . '#' . $hoja)) {
        throw new RuntimeException('No se pudo abrir la hoja del archivo .xlsx.');
    }

    $columnas = [];
    $filas    = [];
    $numero   = 0;

    while ($lector->read()) {
        if ($lector->nodeType !== XMLReader::ELEMENT || $lector->name !== 'row') {
            continue;
        }

        $numero++;

        if ($numero > MAX_FILAS_TABLA + 1) {
            break;
        }

        $celdas = celdas_de_fila($lector, $textos);

        if ($numero === 1) {
            $columnas = $celdas;
            continue;
        }

        if (implode('', $celdas) === '') {
            continue;
        }

        $filas[] = combinar($columnas, $celdas);
    }

    $lector->close();

    return ['columnas' => $columnas, 'filas' => $filas, 'formato' => 'XLSX'];
}

/**
 * Las celdas de la fila en la que está parado el lector, ya alineadas.
 *
 * Excel omite las celdas vacías: una fila con datos en A y en D trae dos
 * celdas, no cuatro. El atributo `r` de cada una («D5») dice su columna real,
 * y sin usarlo los valores se correrían hacia la izquierda y quedarían bajo el
 * encabezado equivocado.
 */
function celdas_de_fila(XMLReader $lector, array $textos): array
{
    $celdas = [];
    $xml    = $lector->readOuterXml();

    if ($xml === '') {
        return [];
    }

    if (!preg_match_all('#<c\b([^>]*)/>|<c\b([^>]*)>([\s\S]*?)</c>#', $xml, $todas, PREG_SET_ORDER)) {
        return [];
    }

    foreach ($todas as $c) {
        $atributos = $c[1] !== '' ? $c[1] : ($c[2] ?? '');
        $interior  = $c[3] ?? '';

        $indice = 0;
        if (preg_match('/\br="([A-Z]+)\d+"/', $atributos, $ref)) {
            $indice = indice_de_columna($ref[1]);
        } else {
            $indice = count($celdas);
        }

        $tipo = '';
        if (preg_match('/\bt="([^"]+)"/', $atributos, $t)) {
            $tipo = $t[1];
        }

        $valor = '';

        if ($tipo === 'inlineStr') {
            preg_match_all('#<t[^>]*>([\s\S]*?)</t>#', $interior, $ts);
            $valor = implode('', $ts[1]);
        } elseif (preg_match('#<v>([\s\S]*?)</v>#', $interior, $v)) {
            $valor = $v[1];

            if ($tipo === 's') {
                $valor = $textos[(int) $valor] ?? '';
            }
        }

        $valor = limpiar_celda(html_entity_decode($valor, ENT_QUOTES | ENT_XML1, 'UTF-8'));

        // Rellena los huecos que Excel se saltó.
        while (count($celdas) < $indice) {
            $celdas[] = '';
        }

        $celdas[$indice] = $valor;
    }

    return $celdas;
}

/** «A» → 0, «B» → 1, «AA» → 26. */
function indice_de_columna(string $letras): int
{
    $n = 0;

    foreach (str_split($letras) as $letra) {
        $n = $n * 26 + (ord($letra) - 64);
    }

    return $n - 1;
}

// ------------------------------------------------------------------
// «.xls» que es HTML
// ------------------------------------------------------------------

/** Lee la primera tabla de un archivo HTML. */
function leer_html(string $ruta): array
{
    $html = (string) file_get_contents($ruta);
    $html = preg_replace('/^\xEF\xBB\xBF/', '', $html) ?? $html;

    preg_match_all('#<tr[^>]*>([\s\S]*?)</tr>#i', $html, $todas);

    $columnas = [];
    $filas    = [];
    $numero   = 0;

    foreach ($todas[1] as $fila) {
        // El encabezado puede venir en <th> o en <td>: se aceptan los dos.
        preg_match_all('#<t[hd][^>]*>([\s\S]*?)</t[hd]>#i', $fila, $celdas);

        if ($celdas[1] === []) {
            continue;
        }

        $valores = array_map(static function (string $c): string {
            $texto = html_entity_decode(strip_tags($c), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return limpiar_celda($texto);
        }, $celdas[1]);

        $numero++;

        if ($numero === 1) {
            $columnas = $valores;
            continue;
        }

        if (implode('', $valores) === '' || $numero > MAX_FILAS_TABLA + 1) {
            continue;
        }

        $filas[] = combinar($columnas, $valores);
    }

    return ['columnas' => $columnas, 'filas' => $filas, 'formato' => 'HTML (.xls)'];
}

// ------------------------------------------------------------------
// Comunes
// ------------------------------------------------------------------

/**
 * Deja la celda como texto utilizable.
 *
 * El &nbsp; sobrevive a la decodificación como el byte 0xA0, que no es un
 * espacio normal: arruina las comparaciones y no se ve. Ya nos costó una
 * importación (ver scripts/importar_coordinadores.php).
 */
function limpiar_celda(string $texto): string
{
    return trim(str_replace(["\xC2\xA0", "\r"], [' ', ''], $texto));
}

/** Empareja los valores con los encabezados, tolerando filas más cortas o más largas. */
function combinar(array $columnas, array $valores): array
{
    $fila = [];

    foreach ($columnas as $i => $columna) {
        $fila[(string) $columna] = (string) ($valores[$i] ?? '');
    }

    // Columnas sin encabezado: se conservan igual, con un nombre inventado,
    // para que la exportación pueda devolverlas.
    for ($i = count($columnas); $i < count($valores); $i++) {
        if (trim((string) $valores[$i]) !== '') {
            $fila['columna_' . ($i + 1)] = (string) $valores[$i];
        }
    }

    return $fila;
}

// ==================================================================
// Reconocimiento de columnas
// ==================================================================

/**
 * Los nombres con los que cada campo puede venir escrito.
 *
 * POR QUÉ UNA LISTA Y NO UN FORMATO FIJO
 * El archivo lo genera otro sistema, sobre el que no tenemos control, y cambia
 * de un año a otro. Exigir nombres exactos convierte cualquier cambio menor de
 * ese lado en una importación que falla el día que hay prisa. Acá se reconoce
 * lo que se pueda y se muestra en pantalla qué se reconoció, para que quien
 * sube el archivo lo confirme con los ojos antes de escribir nada.
 *
 * El primer nombre de cada lista es el que se usa al exportar si el campo no
 * venía en el archivo original.
 */
const COLUMNAS_ESTUDIANTE = [
    // Va primero porque es la identidad, y en la segunda vuelta el orden decide
    // quién se queda con una columna ambigua.
    'identificacion' => [
        'identificacion', 'cedula', 'computo', 'compute', 'numerocomputo',
        'numcomputo', 'ncomputo', 'documento', 'numerodocumento', 'idestudiante',
    ],
    // La versión del cuadernillo, no un identificador: en 2026 son diez valores
    // para 56 737 personas. Es lo que NO se toca al trasladar.
    'formula' => [
        'formula', 'numeroformula', 'numformula', 'noformula', 'nformula',
        'formulaexamen', 'codigoformula',
    ],
    // «nombrecompleto» va antes que «nombre» a propósito. En el archivo del
    // otro sistema hay las dos columnas y significan cosas distintas:
    // `nombre_completo` es la persona y `NOMBRE` es la sede. Si ganara la
    // genérica, el sistema guardaría el nombre del colegio como nombre del
    // estudiante en las 56 737 filas.
    'nombre' => [
        'nombrecompleto', 'nombreestudiante', 'nombrepersona', 'estudiante',
        'nombreyapellidos', 'nombresyapellidos', 'nombre',
    ],
    'sedeNombre' => [
        'nombresede', 'sedenombre', 'descripcionsede', 'nombrecentro',
        'centroeducativo', 'descripcion',
    ],
    'formulario' => [
        'tipoformularioinscripcion', 'formulario', 'numeroformulario',
        'numformulario', 'nformulario', 'noformulario', 'tipoinscripcion',
    ],
    'sede' => [
        'sede', 'codigosede', 'idsede', 'sedecodigo', 'codsede', 'numerosede',
    ],
    'aula' => [
        'numeroaulaexamen', 'numeroaula', 'aula', 'numaula', 'naula',
        'aulaexamen', 'codigoaula',
    ],
    // «convocatoria» al final de la lista: en el archivo del otro sistema esa
    // columna trae el turno («3-M-DOMINGO»), no el año. El año viene en `anno`
    // y se reconoce abajo. Son dos cosas distintas con nombres que se cruzan.
    'turno' => ['turno', 'jornada', 'convocatoria'],
    'fecha' => ['fecha', 'fechaexamen', 'fechaaplicacion'],
    'anio'  => ['anno', 'ano', 'anio', 'year', 'periodo'],
];

/**
 * Lo mínimo sin lo cual no se puede hacer nada.
 *
 * La fórmula NO está: se conserva si viene, pero no identifica a nadie y hay
 * archivos que podrían no traerla. Sin identificación no hay forma de saber si
 * dos filas son la misma persona, y sin sede y aula no hay nada que trasladar.
 */
const COLUMNAS_OBLIGATORIAS = ['identificacion', 'sede', 'aula'];

/**
 * Deja un encabezado comparable: sin acentos, sin separadores y en minúsculas.
 *
 * «Número de Aula», «NUMERO_AULA» y «numeroAula» tienen que dar lo mismo, y sin
 * el paso de las palabras de enlace no lo daban: el primero quedaba como
 * «numerodeaula» y no calzaba con ningún alias. Se sueltan solo las palabras
 * que venían separadas de verdad por un espacio o un guion bajo, así que
 * «SEDE» o «DESDE» no pierden nada.
 */
const ENLACES_DE_ENCABEZADO = ['de', 'del', 'la', 'el', 'los', 'las', 'y'];

function normalizar_encabezado(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');

    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n',
    ]);

    $partes = preg_split('/[^a-z0-9]+/', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];

    $utiles = array_values(array_filter(
        $partes,
        static fn(string $p): bool => !in_array($p, ENLACES_DE_ENCABEZADO, true)
    ));

    // Un encabezado que fuera solo palabras de enlace se conserva tal cual, o
    // se quedaría sin nombre y dos columnas distintas darían la misma cadena.
    return implode('', $utiles !== [] ? $utiles : $partes);
}

/**
 * Empareja las columnas del archivo con los campos que el sistema entiende.
 *
 * @return array<string, string> campo → nombre de la columna tal como vino
 */
function mapear_columnas(array $columnas): array
{
    $porNormal = [];

    foreach ($columnas as $columna) {
        $normal = normalizar_encabezado((string) $columna);
        if ($normal !== '' && !isset($porNormal[$normal])) {
            $porNormal[$normal] = (string) $columna;
        }
    }

    $mapa = [];

    // Primero las coincidencias exactas: son las que no se equivocan.
    foreach (COLUMNAS_ESTUDIANTE as $campo => $alias) {
        foreach ($alias as $a) {
            if (isset($porNormal[$a])) {
                $mapa[$campo] = $porNormal[$a];
                continue 2;
            }
        }
    }

    // Después, para lo que quedó suelto, una coincidencia por contenido.
    //
    // Va en segunda vuelta a propósito: si se hiciera todo junto, «formulario»
    // podría quedarse con la columna de fórmula por contener esa palabra, y el
    // número que no se puede tocar terminaría siendo el equivocado.
    foreach (COLUMNAS_ESTUDIANTE as $campo => $alias) {
        if (isset($mapa[$campo])) {
            continue;
        }

        foreach ($porNormal as $normal => $original) {
            if (in_array($original, $mapa, true)) {
                continue;
            }

            foreach ($alias as $a) {
                if (mb_strlen($a) >= 5 && str_contains($normal, $a)) {
                    $mapa[$campo] = $original;
                    continue 3;
                }
            }
        }
    }

    // Tercera vuelta, solo para el nombre de la sede.
    //
    // En el archivo del otro sistema esa columna se llama «NOMBRE» a secas y
    // trae «1401M CTP DE PEJIBAYE - PÉREZ ZELEDÓN». Por su encabezado no hay
    // forma de distinguirla del nombre de una persona; lo que la delata es el
    // contexto: ya hay otra columna, más específica, que se quedó con el nombre
    // de la gente. Si sobra una «nombre» genérica, es la de la sede.
    //
    // Importa reconocerla porque al exportar hay que escribir el nombre de la
    // sede nueva. Si no, el archivo saldría con la sede 918 en una columna y el
    // nombre del colegio del que la gente se acaba de ir en la de al lado.
    if (!isset($mapa['sedeNombre']) && isset($mapa['nombre'])) {
        foreach (['nombre', 'descripcion'] as $generico) {
            if (isset($porNormal[$generico]) && !in_array($porNormal[$generico], $mapa, true)) {
                $mapa['sedeNombre'] = $porNormal[$generico];
                break;
            }
        }
    }

    return $mapa;
}

/**
 * Convierte una fecha como venga a formato de base de datos, o null.
 *
 * Excel guarda las fechas como días transcurridos desde el 30/12/1899, así que
 * una columna de fechas puede llegar acá como «45678». Sin esta conversión ese
 * número entraría como fecha inválida y la columna quedaría vacía sin aviso.
 */
function fecha_a_sql(string $valor): ?string
{
    $valor = trim($valor);

    if ($valor === '') {
        return null;
    }

    if (ctype_digit($valor) && (int) $valor > 20000 && (int) $valor < 80000) {
        return date('Y-m-d', mktime(0, 0, 0, 12, 30 + (int) $valor, 1899));
    }

    // dd/mm/aaaa es lo que escribe Excel en español; strtotime lo leería al
    // revés, como mes/día, y el 3 de julio se volvería el 7 de marzo.
    if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})#', $valor, $p)) {
        return sprintf('%04d-%02d-%02d', (int) $p[3], (int) $p[2], (int) $p[1]);
    }

    if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})#', $valor, $p)) {
        return sprintf('%04d-%02d-%02d', (int) $p[1], (int) $p[2], (int) $p[3]);
    }

    $t = strtotime($valor);

    return $t !== false ? date('Y-m-d', $t) : null;
}
