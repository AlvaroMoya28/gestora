<?php
/**
 * Catálogo de módulos del sistema y quién entra a cada uno.
 *
 * ESTE ARCHIVO ES LA ÚNICA VERDAD SOBRE LOS PERMISOS.
 *
 * Antes cada página repetía su propia guarda —casi todas con ROLES_ADMIN— y el
 * portal tenía además una lista aparte de qué mostrar. Eran dos lugares que
 * podían discrepar: un módulo podía aparecer en el menú de alguien que después
 * se topaba con una redirección, o quedar fuera del menú aunque sí tuviera
 * permiso. Y para dar acceso a un rol nuevo había que tocar tantos archivos
 * como módulos.
 *
 * Ahora la lista de roles de cada módulo vive acá y se usa para las dos cosas:
 * decidir qué se dibuja en el portal y cortar la entrada a la página. Agregar
 * un módulo o darle acceso a alguien es editar una línea de este arreglo.
 */

declare(strict_types=1);

require_once __DIR__ . '/sesion.php';

/**
 * Las categorías del centro de herramientas, en el orden en que se muestran.
 *
 * Con doce módulos ya no alcanza una sola grilla: agrupados por lo que cada
 * quien viene a hacer (preparar la aplicación, embalar el material, resolver
 * algo puntual) se encuentran más rápido que en una lista plana.
 */
function categorias_del_sistema(): array
{
    return [
        'administracion' => [
            'nombre' => 'Administración',
            'icono' => 'fa-building-columns',
            'descripcion' => 'Sedes, importación de datos, traslados, sobresueldos y coordinadores.',
        ],
        'embalaje' => [
            'nombre' => 'Embalaje',
            'icono' => 'fa-box-open',
            'descripcion' => 'Lo que se usa el día de la aplicación: ingreso de datos, carátulas, control de tulas y revisión.',
        ],
        'informatica' => [
            'nombre' => 'Informática',
            'icono' => 'fa-network-wired',
            // Distinta de "administración" a propósito: sedes, traslados y
            // sobresueldos son de la gestión del programa; esto es
            // administración técnica del sistema y del inventario, cosa que
            // no le toca a cualquiera que administre el PAA.
            'descripcion' => 'Cuentas de acceso al sistema e inventario de equipo institucional.',
        ],
        'recursos' => [
            // Antes "Soporte": con el cronograma sumado ya no es solo a dónde
            // ir cuando algo falla, es todo lo que un funcionario necesita
            // consultar del día a día del programa.
            'nombre' => 'Recursos',
            'icono' => 'fa-user-group',
            'descripcion' => 'Directorio de contactos, reporte de averías y el cronograma del proceso.',
        ],
    ];
}

/**
 * Todos los módulos, en el orden en que se muestran dentro de su categoría.
 *
 * `roles` es la lista de quiénes entran. `estado` es lo que la tarjeta rotula:
 * 'live' o 'beta'. `categoria` es una clave de categorias_del_sistema().
 */
function modulos_del_sistema(): array
{
    // Casi todo el sistema es de administración. Se nombra una vez para que la
    // excepción salte a la vista en las pocas que no lo son.
    $administracion = [ROL_ADMINISTRADOR, ROL_DESARROLLADOR];

    return [
        // ---------------------------------------------------------------
        // Administración: preparar la convocatoria y las tareas de gestión
        // que no son del día de la aplicación en sí.
        // ---------------------------------------------------------------
        [
            'id'    => 'sedes',
            'nombre' => 'Sedes',
            'descripcion' => 'Catálogo de las sedes donde se aplica la prueba: contacto, dirección, ubicación, coordinador y folletos de cada centro educativo. Se puede editar cada dato.',
            'estado' => 'live',
            'icono' => 'fa-location-dot',
            'url'   => 'Sedes/',
            'caracteristica' => 'Búsqueda, edición y Excel',
            'categoria' => 'administracion',
            'roles' => $administracion,
        ],
        [
            'id'    => 'importacion',
            'nombre' => 'Importación de Datos',
            'descripcion' => 'Carga anual desde el sistema de coordinaciones: sedes, coordinadores, padrón de personas aplicadoras y puestos nombrados. También permite eliminar un año completo.',
            'estado' => 'live',
            'icono' => 'fa-file-import',
            'url'   => 'Importacion/',
            'caracteristica' => 'Un archivo por año',
            // Reescribe las sedes de una convocatoria y puede borrar años
            // enteros. No lo abre nadie más que quien administra por
            // defecto, pero sí se puede dar como excepción a un funcionario
            // puntual que lo necesite.
            'categoria' => 'administracion',
            'roles' => $administracion,
        ],
        [
            'id'    => 'traslados',
            'nombre' => 'Traslado de Estudiantes',
            'descripcion' => 'Mover a los estudiantes de una sede y turno hacia otra sede, redistribuyéndolos en aulas de otra capacidad. Conserva el número de fórmula y exporta el Excel del cambio.',
            'estado' => 'live',
            'icono' => 'fa-people-arrows',
            'url'   => 'Traslados/',
            'caracteristica' => 'Simula antes de aplicar',
            'categoria' => 'administracion',
            'roles' => $administracion,
        ],
        [
            'id'    => 'sobresueldos',
            'nombre' => 'Sobre Sueldos',
            'descripcion' => 'Gestión de personal, comidas y hospedaje por sede. Asigna coordinadores, calcula aplicadores, apoyo y conserjes, y genera el presupuesto estimado por viaje.',
            'estado' => 'live',
            'icono' => 'fa-file-invoice-dollar',
            'url'   => 'SobreSueldos/',
            'caracteristica' => 'Presupuesto automático',
            'categoria' => 'administracion',
            'roles' => $administracion,
        ],
        [
            'id'    => 'activos',
            'nombre' => 'Activos de Préstamo',
            'descripcion' => 'Préstamo, devolución, escaneo de códigos y generación de boletas de equipo institucional con historial de movimientos.',
            'estado' => 'live',
            'icono' => 'fa-laptop-code',
            'url'   => 'Activos/',
            'caracteristica' => 'Historial de movimientos',
            'categoria' => 'informatica',
            'roles' => $administracion,
        ],
        [
            'id'    => 'inventario_oficina',
            'nombre' => 'Inventario de Oficina',
            'descripcion' => 'Registro del equipo fijo de la oficina —computadoras, laptops, periféricos, cables—, con alta individual o carga masiva por CSV/Excel, bajas y aviso de mantenimiento cada 6 meses para computadoras y laptops.',
            'estado' => 'live',
            'icono' => 'fa-boxes-stacked',
            'url'   => 'InventarioOficina/',
            'caracteristica' => 'Carga masiva y mantenimiento',
            // Es el inventario físico de la oficina, no equipo que se presta a
            // terceros: mismo alcance administrativo que "activos" pero sobre
            // lo que nunca sale del edificio.
            'categoria' => 'informatica',
            'roles' => $administracion,
            'otorgable' => false,
        ],
        [
            'id'    => 'equipo_funcionarios',
            'nombre' => 'Equipo de Funcionarios',
            'descripcion' => 'Qué equipo tiene asignado cada funcionario del PAA —computadoras, teclados, UPS—, con bitácora de altas, ediciones y retiros, y sondeo mensual de mantenimiento con contador de cuándo toca el siguiente.',
            'estado' => 'live',
            'icono' => 'fa-user-gear',
            'url'   => 'EquipoFuncionarios/',
            'caracteristica' => 'Bitácora y sondeo mensual',
            'categoria' => 'informatica',
            'roles' => $administracion,
            'otorgable' => false,
        ],
        [
            'id'    => 'coordinadores',
            'nombre' => 'Asignación de coordinadores',
            'descripcion' => 'Carga de formularios y sedes para calcular la mejor asignación de coordinadores por afinidad de ubicación, disponibilidad y condiciones declaradas.',
            'estado' => 'live',
            'icono' => 'fa-user-check',
            'url'   => 'AsignacionCoordinadores/',
            'caracteristica' => 'Asignación inteligente',
            'categoria' => 'administracion',
            'roles' => $administracion,
        ],
        [
            'id'    => 'usuarios',
            'nombre' => 'Administración de Usuarios',
            'descripcion' => 'Cuentas de acceso al sistema: crear perfiles, restablecer contraseñas olvidadas y dar de baja accesos.',
            'estado' => 'live',
            'icono' => 'fa-user-shield',
            'url'   => 'Usuarios/',
            'caracteristica' => 'Roles y contraseñas',
            // Maneja cuentas y contraseñas de todo el sistema: darlo como
            // excepción individual equivaldría a repartir administración de
            // usuarios en pedazos, que es justo lo que esto intenta evitar.
            'categoria' => 'informatica',
            'roles' => $administracion,
            'otorgable' => false,
        ],
        [
            'id'    => 'anuncios',
            'nombre' => 'Anuncios',
            'descripcion' => 'Redactar un correo y mandarlo a los funcionarios del PAA (el mismo directorio de "Funcionarios PAA"), con opción de excluir a quien no corresponda y adjuntar archivos.',
            'estado' => 'live',
            'icono' => 'fa-bullhorn',
            'url'   => 'Anuncios/',
            'caracteristica' => 'Adjuntos y exclusiones',
            // Manda correo a todo el personal del programa de una sola vez:
            // el mismo alcance que "usuarios", el mismo motivo para no
            // poder dárselo como excepción individual.
            'categoria' => 'informatica',
            'roles' => $administracion,
            'otorgable' => false,
        ],

        // ---------------------------------------------------------------
        // Embalaje: lo que se hace con el material el día de la aplicación,
        // en el orden en que de verdad se usa (folletos → carátulas → tulas → revisar).
        // ---------------------------------------------------------------
        [
            'id'    => 'ingresoDatos',
            'nombre' => 'Ingreso de Datos',
            'descripcion' => 'Registro del embalaje por sede en cinco pasos: folletos de cada aula, folletos del coordinador, armado de las tulas y asignación de sus marchamos.',
            'estado' => 'live',
            'icono' => 'fa-keyboard',
            'url'   => 'IngresoDatos/',
            'caracteristica' => 'Proceso guiado por pasos',
            // El único al que llega el personal extraordinario. Es también el
            // único donde no se ve ningún dato de personas ni de dinero.
            'categoria' => 'embalaje',
            'roles' => [ROL_ADMINISTRADOR, ROL_DESARROLLADOR, ROL_EXTRAORDINARIO],
        ],
        [
            'id'    => 'caratulas',
            'nombre' => 'Carátulas',
            'descripcion' => 'Generación de carátulas por sede y tula, con nombre de sede, aulas, folletos, marchamos y coordinador actualizados desde la base.',
            'estado' => 'live',
            'icono' => 'fa-file-lines',
            'url'   => 'Caratulas/',
            'caracteristica' => 'Impresión por sede',
            'categoria' => 'embalaje',
            'roles' => $administracion,
        ],
        [
            'id'    => 'tulas',
            'nombre' => 'Control de Tulas',
            'descripcion' => 'Salida y entrada de tulas: verificación por código de barras, actualización de estados y envío de comprobantes al coordinador.',
            'estado' => 'live',
            'icono' => 'fa-boxes-stacked',
            'url'   => 'Tulas/',
            'caracteristica' => 'Escáner integrado',
            'categoria' => 'embalaje',
            'roles' => $administracion,
        ],
        [
            'id'    => 'revision',
            'nombre' => 'Revisión',
            'descripcion' => 'Consulta de lo capturado en cada sede e impresión de los comprobantes, uno por uno o todos juntos en un solo PDF.',
            'estado' => 'live',
            'icono' => 'fa-clipboard-check',
            'url'   => 'Revision/',
            'caracteristica' => 'Impresión en lote',
            'categoria' => 'embalaje',
            'roles' => $administracion,
        ],

        // ---------------------------------------------------------------
        // Recursos: todo lo que un funcionario común necesita del día a día
        // del programa — a quién contactar, dónde reportar un problema y
        // qué viene en el calendario. Es la única categoría que también ve
        // un funcionario común, no solo administración y desarrollo.
        // ---------------------------------------------------------------
        [
            'id'    => 'directorio',
            'nombre' => 'Funcionarios PAA',
            'descripcion' => 'Directorio del personal del PAA por área: correo institucional y teléfono de contacto.',
            'estado' => 'live',
            'icono' => 'fa-address-book',
            'url'   => 'Funcionarios/',
            'caracteristica' => 'Contacto directo',
            // Es un directorio de consulta: sirve a todo el personal del
            // programa. El extraordinario no entra porque no es parte de él.
            'categoria' => 'recursos',
            'roles' => [ROL_FUNCIONARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR],
        ],
        [
            'id'    => 'tickets',
            'nombre' => 'Creación de Tickets',
            'descripcion' => 'Reporte y seguimiento de averías en las sedes: se registra qué pasó, dónde y con qué prioridad, y queda el historial hasta que se resuelve.',
            'estado' => 'live',
            'icono' => 'fa-ticket',
            'url'   => 'CreacionTickets/',
            'caracteristica' => 'Seguimiento por estado',
            'categoria' => 'recursos',
            'roles' => [ROL_FUNCIONARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR],
        ],
        [
            'id'    => 'cronograma',
            'nombre' => 'Cronograma',
            'descripcion' => 'Los hitos clave del proceso de la convocatoria activa: envío de folletos, capacitaciones, día de aplicación, corrección y publicación de resultados.',
            'estado' => 'live',
            'icono' => 'fa-calendar-days',
            'url'   => 'Cronograma/',
            'caracteristica' => 'Calendario del proceso',
            // Administración y desarrollo lo editan; funcionarios solo lo
            // consultan (igual que el directorio) — es la única fecha
            // "oficial" del proceso, no algo que cada quien deba llevar
            // por su cuenta en un Excel aparte.
            'categoria' => 'recursos',
            'roles' => [ROL_FUNCIONARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR],
        ],
        [
            'id'    => 'situaciones',
            'nombre' => 'Situaciones Especiales',
            'descripcion' => 'Vacaciones, incapacidades, reuniones, cambios de horario, trabajo remoto, citas médicas y permisos de cada quien, más los anuncios por fecha de administración — un solo calendario en vez de dos.',
            'estado' => 'live',
            'icono' => 'fa-calendar-check',
            'url'   => 'Situaciones/',
            'caracteristica' => 'Cada quien lo suyo',
            // Cualquiera con cuenta registra las propias; el "quién" sale de
            // la sesión, no se escribe a mano, así que no hay forma de
            // anotar una ausencia a nombre de otra persona. Administración y
            // desarrollo sí pueden editar o borrar las de cualquiera, para
            // corregir un error sin depender de que la persona vuelva a
            // entrar. Personal extraordinario no entra: es de quienes
            // tienen una cuenta fija en el programa.
            'categoria' => 'recursos',
            'roles' => [ROL_FUNCIONARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR],
        ],
        [
            'id'    => 'horarios',
            'nombre' => 'Horarios',
            'descripcion' => 'El horario semanal de cada quien: franjas de lunes a viernes con su modalidad presencial o virtual. Cada quien administra el propio; desde Funcionarios PAA se puede consultar el de cualquiera para una semana dada.',
            'estado' => 'live',
            'icono' => 'fa-clock',
            'url'   => 'Horarios/',
            'caracteristica' => 'Semana recurrente',
            // Mismo criterio que Situaciones Especiales: el "quién" sale de
            // la sesión, cada quien administra el propio, administración y
            // desarrollo pueden corregir cualquiera.
            'categoria' => 'recursos',
            'roles' => [ROL_FUNCIONARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR],
        ],
        [
            'id'    => 'sala_reuniones',
            'nombre' => 'Sala de Reuniones',
            'descripcion' => 'Solicitud y préstamo de la sala de reuniones: fecha, hora y motivo. Administración aprueba o rechaza y se avisa por correo. Incluye el reglamento de uso y el control del teclado y el mouse del proyector.',
            'estado' => 'live',
            'icono' => 'fa-door-open',
            'url'   => 'SalaReuniones/',
            'caracteristica' => 'Aprobación y aviso por correo',
            // Mismo criterio que Tickets: cualquiera con cuenta fija pide,
            // administración resuelve. La pantalla que le toca a cada quien la
            // decide su rol, no un parámetro (ver vista_activa() en el
            // endpoint). Personal extraordinario no entra: no es parte del
            // personal del programa.
            'categoria' => 'recursos',
            'roles' => [ROL_FUNCIONARIO, ROL_ADMINISTRADOR, ROL_DESARROLLADOR],
        ],
    ];
}

/**
 * Los módulos que le corresponden a un rol, con las excepciones puntuales de
 * un usuario aplicadas encima (si se pasa su id).
 *
 * Las excepciones son la DIFERENCIA contra el rol, no una lista aparte: por
 * eso alcanza con agregar/quitar sobre lo que ya trae `$rol`, en vez de
 * guardarle a cada usuario su catálogo completo (ver schema/027).
 */
function modulos_para(string $rol, int $usuarioId = 0): array
{
    $catalogo = modulos_del_sistema();

    $resultado = [];
    foreach ($catalogo as $m) {
        if (in_array($rol, $m['roles'], true)) {
            $resultado[$m['id']] = $m;
        }
    }

    if ($usuarioId > 0) {
        $excepciones = excepciones_de_usuario($usuarioId);
        $porId = array_column($catalogo, null, 'id');

        foreach ($excepciones['quitados'] as $id) {
            unset($resultado[$id]);
        }

        foreach ($excepciones['agregados'] as $id) {
            // Solo si sigue existiendo en el catálogo y se puede otorgar
            // como excepción — ver 'otorgable' en modulos_del_sistema().
            if (isset($porId[$id]) && ($porId[$id]['otorgable'] ?? true)) {
                $resultado[$id] = $porId[$id];
            }
        }
    }

    // Se recorre el catálogo para devolver en su orden original: el mapa por
    // id de arriba no lo conserva, y el portal agrupa por categoría en ese
    // orden.
    $ordenado = [];
    foreach ($catalogo as $m) {
        if (isset($resultado[$m['id']])) {
            $ordenado[] = $resultado[$m['id']];
        }
    }

    return $ordenado;
}

/**
 * Qué excepciones tiene un usuario sobre los módulos de su rol.
 *
 * @return array{agregados: string[], quitados: string[]}
 */
function excepciones_de_usuario(int $usuarioId): array
{
    if ($usuarioId <= 0) {
        return ['agregados' => [], 'quitados' => []];
    }

    $stmt = db()->prepare('SELECT modulo, tipo FROM usuario_modulos WHERE usuario_id = ?');
    $stmt->execute([$usuarioId]);

    $agregados = [];
    $quitados = [];
    foreach ($stmt->fetchAll() as $fila) {
        if ($fila['tipo'] === 'agregado') {
            $agregados[] = $fila['modulo'];
        } else {
            $quitados[] = $fila['modulo'];
        }
    }

    return ['agregados' => $agregados, 'quitados' => $quitados];
}

/**
 * Reemplaza las excepciones de un usuario por las que se acaban de elegir.
 *
 * Se reemplaza todo el conjunto en vez de calcular altas/bajas: la pantalla
 * de administración manda la lista completa de casillas marcadas cada vez,
 * así que es más simple (y más difícil de dejar a medias) borrar y volver a
 * insertar dentro de una transacción que tratar de diferenciar contra lo que
 * había antes.
 *
 * @param string[] $agregados Ids de módulo que el usuario no tiene por rol pero sí debe ver.
 * @param string[] $quitados  Ids de módulo que el usuario tiene por rol pero no debe ver.
 */
function guardar_excepciones_usuario(int $usuarioId, array $agregados, array $quitados): void
{
    $porId = array_column(modulos_del_sistema(), null, 'id');

    // Nunca se guarda una excepción para un módulo que no existe o que no se
    // puede otorgar — cinturón y tirantes además del filtro de lectura en
    // modulos_para(), por si algún día se lee esta tabla desde otro lado.
    $agregados = array_values(array_filter(
        $agregados,
        static fn(string $id): bool => isset($porId[$id]) && ($porId[$id]['otorgable'] ?? true)
    ));
    $quitados = array_values(array_filter(
        $quitados,
        static fn(string $id): bool => isset($porId[$id])
    ));

    db()->beginTransaction();

    db()->prepare('DELETE FROM usuario_modulos WHERE usuario_id = ?')->execute([$usuarioId]);

    $insertar = db()->prepare('INSERT INTO usuario_modulos (usuario_id, modulo, tipo) VALUES (?, ?, ?)');
    foreach ($agregados as $id) {
        $insertar->execute([$usuarioId, $id, 'agregado']);
    }
    foreach ($quitados as $id) {
        $insertar->execute([$usuarioId, $id, 'quitado']);
    }

    db()->commit();
}

/** Un módulo por su id, o null. */
function modulo(string $id): ?array
{
    foreach (modulos_del_sistema() as $m) {
        if ($m['id'] === $id) {
            return $m;
        }
    }

    return null;
}

/**
 * Guarda de página: corta si el rol no tiene este módulo.
 *
 * Reemplaza a los exigir_rol() repetidos en cada índice. La diferencia que
 * importa no es escribir menos, sino que el permiso se lee del mismo lugar del
 * que salió el menú: si alguien ve la tarjeta, entra; si no la ve, no entra.
 *
 * @param string $base Prefijo hasta la raíz ('../' desde la carpeta de un módulo).
 */
function exigir_modulo(string $id, string $base = ''): array
{
    $u = exigir_login($base);

    if (!usuario_ve_modulo($u, $id)) {
        header('Location: ' . base_url());
        exit;
    }

    return $u;
}

/** Si un usuario (ya con su rol activo resuelto) entra a este módulo, contando sus excepciones. */
function usuario_ve_modulo(array $u, string $id): bool
{
    $permitidos = array_column(modulos_para($u['rol'], (int) $u['id']), 'id');

    return in_array($id, $permitidos, true);
}

/**
 * Guarda de endpoint: responde JSON en vez de redirigir.
 *
 * POR QUÉ TIENE QUE EXISTIR ESTA Y NO BASTA exigir_modulo()
 * Cada módulo son dos piezas: la página y su endpoint. Si la página lee sus
 * permisos del catálogo pero el endpoint tiene los suyos escritos aparte, se
 * separan — y el síntoma es el peor posible: la pantalla abre, se ve completa,
 * y las consultas devuelven 403. Parece que el sistema está roto cuando lo que
 * está mal es el permiso.
 *
 * Pasó de verdad: al personal extraordinario le abría Ingreso de Datos y no le
 * cargaba ninguna sede, y a los funcionarios les habría pasado lo mismo con el
 * directorio en cuanto alguno entrara.
 */
function exigir_modulo_api(string $id): array
{
    $u = exigir_login_api();

    if (!usuario_ve_modulo($u, $id)) {
        responder_json([
            'success' => false,
            'error'   => 'No tenés permiso para esta operación.',
        ], 403);
    }

    return $u;
}

/**
 * Cómo se llama el portal para cada rol.
 *
 * Quien ve diez módulos está en un centro de herramientas; quien ve uno, no.
 * Llamarle igual a las dos cosas haría sentir que falta algo.
 */
function titulo_del_portal(string $rol): array
{
    if (in_array($rol, ROLES_ADMIN, true)) {
        return [
            'titulo' => 'Módulos',
            'sub'    => 'Acceso directo a los módulos operativos del Programa Permanente de la Prueba de Aptitud Académica',
        ];
    }

    if ($rol === ROL_EXTRAORDINARIO) {
        return [
            'titulo' => 'Ingreso de material',
            'sub'    => 'Registro del embalaje de las sedes asignadas',
        ];
    }

    return [
        'titulo' => 'Mis herramientas',
        'sub'    => 'Módulos disponibles para tu cuenta',
    ];
}
