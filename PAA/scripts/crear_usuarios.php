<?php
/**
 * Crea las cuentas de acceso a partir del directorio de funcionarios.
 *
 *   php scripts/crear_usuarios.php            → muestra qué haría
 *   php scripts/crear_usuarios.php --aplicar  → las crea de verdad
 *
 * QUÉ HACE
 * Por cada persona activa del directorio crea un usuario:
 *   · nombre de usuario = el correo hasta la arroba
 *   · contraseña        = la inicial, la misma para todos
 *   · debe cambiarla la primera vez que entre
 *
 * Las cuentas que ya existen NO se tocan: no les cambia la contraseña ni el
 * rol. Así se puede volver a correr cuando entre gente nueva sin resetear a
 * quienes ya eligieron la suya.
 *
 * POR QUÉ ACÁ Y NO EN UNA MIGRACIÓN
 * El hash de una contraseña no se puede calcular desde SQL: hace falta PHP
 * para llamar a password_hash(), que además genera un salt aleatorio distinto
 * para cada una.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/sesion.php';

$aplicar = in_array('--aplicar', $argv, true);
$pdo = db();

// ------------------------------------------------------------------
// Quién es administrador
// ------------------------------------------------------------------
// El resto del personal entra como funcionario y solo ve el módulo de
// tickets. Para cambiarle el rol a alguien después, está el módulo de
// Administración de Usuarios.
$administradores = [
    'andrei.fallas',        // Andrei Fallas Rojas
];

// Cuenta de desarrollo: no es personal del programa, así que se marca oculta
// y no aparece en el directorio ni en los selectores del sistema.
$desarrolladores = [
    'alvaro.moyaarrieta',   // Álvaro Moya Arrieta
];

// ------------------------------------------------------------------
// La cuenta de desarrollo tiene que existir como persona
// ------------------------------------------------------------------
// No es personal del programa, pero un usuario necesita una persona detrás:
// es lo que da nombre y correo, y lo que referencian los registros que cree.
// Se marca oculta para que no salga en el directorio ni en los selectores.
$personaDesarrollo = [
    'nombre' => 'Álvaro Moya Arrieta',
    'correo' => 'alvaro.moyaarrieta@ucr.ac.cr',
    'area'   => 'Apoyo Informático',
];

$stmt = $pdo->prepare('SELECT id FROM personas WHERE correo = ?');
$stmt->execute([$personaDesarrollo['correo']]);

if ($stmt->fetchColumn() === false) {
    if ($aplicar) {
        // El área es obligatoria en la tabla; se reutiliza la que ya existe
        // en el catálogo o se crea si hiciera falta.
        $stmt = $pdo->prepare('SELECT id FROM areas WHERE nombre = ?');
        $stmt->execute([$personaDesarrollo['area']]);
        $areaId = $stmt->fetchColumn();

        if ($areaId === false) {
            $pdo->prepare('INSERT INTO areas (nombre, orden) VALUES (?, 99)')
                ->execute([$personaDesarrollo['area']]);
            $areaId = $pdo->lastInsertId();
        }

        $pdo->prepare("
            INSERT INTO personas (area_id, nombre, correo, extension, activo, oculto, puede_retirar_activos)
            VALUES (?, ?, ?, NULL, 1, 1, 1)
        ")->execute([(int) $areaId, $personaDesarrollo['nombre'], $personaDesarrollo['correo']]);

        echo "Persona de desarrollo creada: {$personaDesarrollo['nombre']} (oculta)\n\n";
    } else {
        echo "Falta crear la persona de desarrollo: {$personaDesarrollo['nombre']}\n";
        echo "  (se creará oculta, sin extensión, al correr con --aplicar)\n\n";
    }
}

// ------------------------------------------------------------------
// Personas sin cuenta
// ------------------------------------------------------------------
$personas = $pdo->query("
    SELECT p.id, p.nombre, p.correo, u.id AS usuario_id, u.usuario, u.rol
    FROM personas p
    LEFT JOIN usuarios u ON u.persona_id = p.id
    WHERE p.activo = 1
    ORDER BY p.nombre
")->fetchAll();

$porCrear = [];
$yaTienen = [];
$sinCorreo = [];

foreach ($personas as $p) {
    if ($p['usuario_id'] !== null) {
        $yaTienen[] = $p;
        continue;
    }

    $correo = trim((string) $p['correo']);

    // Sin correo no hay nombre de usuario posible: es la regla acordada.
    if ($correo === '' || !str_contains($correo, '@')) {
        $sinCorreo[] = $p;
        continue;
    }

    $usuario = strtolower(strstr($correo, '@', true));

    $rol = ROL_FUNCIONARIO;
    if (in_array($usuario, $desarrolladores, true)) {
        $rol = ROL_DESARROLLADOR;
    } elseif (in_array($usuario, $administradores, true)) {
        $rol = ROL_ADMINISTRADOR;
    }

    $porCrear[] = ['persona' => $p, 'usuario' => $usuario, 'rol' => $rol];
}

// ------------------------------------------------------------------
// Informe
// ------------------------------------------------------------------
echo "Personas activas en el directorio: " . count($personas) . "\n";
echo "  ya tienen cuenta: " . count($yaTienen) . "\n";
echo "  se van a crear:   " . count($porCrear) . "\n";
if ($sinCorreo !== []) {
    echo "  sin correo (no se puede crear usuario): " . count($sinCorreo) . "\n";
    foreach ($sinCorreo as $p) {
        echo "      · {$p['nombre']}\n";
    }
}

if ($porCrear !== []) {
    echo "\nCuentas a crear:\n";
    printf("  %-26s %-34s %s\n", 'USUARIO', 'NOMBRE', 'ROL');
    echo '  ' . str_repeat('-', 74) . "\n";
    foreach ($porCrear as $c) {
        printf("  %-26s %-34s %s\n",
            $c['usuario'],
            mb_substr($c['persona']['nombre'], 0, 34),
            $c['rol']
        );
    }
}

if ($yaTienen !== []) {
    echo "\nYa tenían cuenta (no se tocan):\n";
    foreach ($yaTienen as $p) {
        printf("  %-26s %s\n", $p['usuario'], $p['rol']);
    }
}

if (!$aplicar) {
    echo "\nEsto fue solo una vista previa: no se creó nada.\n";
    echo "Para crearlas:\n\n";
    echo "  php scripts/crear_usuarios.php --aplicar\n";
    exit(0);
}

if ($porCrear === []) {
    echo "\nNo hay cuentas nuevas que crear.\n";
    exit(0);
}

// ------------------------------------------------------------------
// Crear
// ------------------------------------------------------------------
echo "\nCreando...\n";

$hash = password_hash(CONTRASENA_INICIAL, PASSWORD_DEFAULT);

$insertar = $pdo->prepare("
    INSERT INTO usuarios (persona_id, usuario, contrasena_hash, rol, debe_cambiar_contrasena)
    VALUES (?, ?, ?, ?, 1)
");
$ocultar = $pdo->prepare('UPDATE personas SET oculto = 1 WHERE id = ?');

$pdo->beginTransaction();

try {
    foreach ($porCrear as $c) {
        // Un hash por cuenta: aunque la contraseña sea la misma, el salt que
        // genera password_hash() es distinto cada vez, así que los valores
        // guardados no se parecen entre sí.
        $insertar->execute([
            (int) $c['persona']['id'],
            $c['usuario'],
            password_hash(CONTRASENA_INICIAL, PASSWORD_DEFAULT),
            $c['rol'],
        ]);

        if ($c['rol'] === ROL_DESARROLLADOR) {
            $ocultar->execute([(int) $c['persona']['id']]);
            echo "  {$c['usuario']} · {$c['rol']} (oculto del directorio)\n";
        } else {
            echo "  {$c['usuario']} · {$c['rol']}\n";
        }
    }

    $pdo->commit();

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "No se creó ninguna cuenta.\n";
    exit(1);
}

echo "\nListo. " . count($porCrear) . " cuenta(s) creada(s).\n\n";
echo "Contraseña inicial para todas: " . CONTRASENA_INICIAL . "\n";
echo "El sistema les va a pedir que la cambien la primera vez que entren.\n";
