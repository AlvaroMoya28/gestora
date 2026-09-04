<?php
/**
 * Crea la cuenta compartida del personal extraordinario.
 *
 *   php scripts/crear_extraordinario.php            → muestra qué haría
 *   php scripts/crear_extraordinario.php --aplicar  → la crea de verdad
 *
 * QUÉ ES
 * Una sola cuenta que usa todo el personal contratado para el embalaje:
 *
 *   usuario:    extraordinario
 *   contraseña: paa2026
 *
 * POR QUÉ UNA COMPARTIDA Y NO UNA POR PERSONA
 * Es personal temporal que rota entre turnos y del que muchas veces no se
 * tiene el correo institucional —requisito del nombre de usuario en este
 * sistema— hasta después de que empezaron a trabajar. Crear y dar de baja
 * cuentas al ritmo de esa rotación sería trabajo de administración a diario,
 * durante las semanas de mayor carga.
 *
 * LO QUE ESO CUESTA, DICHO CLARO
 * Con una cuenta compartida la bitácora registra QUÉ se hizo, pero no QUIÉN.
 * Si hace falta rastrear a una persona en particular, hay que crearle cuenta
 * propia con el módulo de Usuarios eligiendo el mismo rol. Por eso este rol
 * llega solo a Ingreso de Datos: es la única pantalla del sistema donde no se
 * ven datos de personas ni de dinero, y donde todo lo que se captura queda
 * amarrado a una sede que después se revisa igual.
 *
 * NO SE LE EXIGE CAMBIAR LA CONTRASEÑA
 * Si la primera persona del turno la cambiara, el resto se quedaría afuera.
 * Se puede cambiar a propósito —desde el módulo de Usuarios, avisándole a
 * quien corresponda— pero el sistema no la va a pedir al entrar.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se corre desde la terminal.\n");
}

$raiz = dirname(__DIR__);
require_once $raiz . '/includes/sesion.php';

$aplicar = in_array('--aplicar', $argv, true);
$pdo = db();

const USUARIO_EXTRAORDINARIO = 'extraordinario';
const NOMBRE_EXTRAORDINARIO  = 'Personal Extraordinario';

echo "=== Cuenta del personal extraordinario ===\n\n";

// ------------------------------------------------------------------
// ¿Ya existe?
// ------------------------------------------------------------------
$stmt = $pdo->prepare('SELECT id, rol, activo FROM usuarios WHERE usuario = ?');
$stmt->execute([USUARIO_EXTRAORDINARIO]);
$existente = $stmt->fetch();

if ($existente !== false) {
    printf("  La cuenta «%s» ya existe (rol: %s, %s).\n\n",
        USUARIO_EXTRAORDINARIO,
        $existente['rol'],
        (int) $existente['activo'] === 1 ? 'activa' : 'desactivada');

    // No se le toca la contraseña: puede que ya la hayan cambiado a propósito
    // y volver a ponerla en la inicial dejaría entrar a quien supiera la vieja.
    echo "  No se modifica nada. Para restablecerle la contraseña usá el\n";
    echo "  módulo de Usuarios, que además deja constancia de quién lo hizo.\n";
    exit(0);
}

// ------------------------------------------------------------------
// La persona a la que se cuelga la cuenta
// ------------------------------------------------------------------
// `usuarios.persona_id` es obligatorio: cada cuenta pertenece a alguien del
// directorio. Esta no es una persona real, así que se marca como oculta para
// que no aparezca en Funcionarios PAA ni en los selectores de coordinador,
// préstamo de equipo o asignación de sedes.
$stmt = $pdo->prepare('SELECT id FROM personas WHERE nombre = ?');
$stmt->execute([NOMBRE_EXTRAORDINARIO]);
$personaId = $stmt->fetchColumn();

if (!$aplicar) {
    echo "  Se crearía:\n\n";
    echo "    persona     " . NOMBRE_EXTRAORDINARIO . " (oculta del directorio)\n";
    echo "    usuario     " . USUARIO_EXTRAORDINARIO . "\n";
    echo "    contraseña  " . CONTRASENA_INICIAL . "\n";
    echo "    rol         " . ROL_EXTRAORDINARIO . "\n";
    echo "    módulos     Ingreso de Datos, y nada más\n";
    echo "    cambio de contraseña al entrar: NO\n\n";
    echo "  Para crearla de verdad:\n\n";
    echo "    php scripts/crear_extraordinario.php --aplicar\n";
    exit(0);
}

$pdo->beginTransaction();

try {
    if ($personaId === false) {
        $pdo->prepare("
            INSERT INTO personas (nombre, area, activo, oculto)
            VALUES (?, ?, 1, 1)
        ")->execute([NOMBRE_EXTRAORDINARIO, 'Embalaje']);

        $personaId = (int) $pdo->lastInsertId();
        echo "  · Persona creada (oculta del directorio).\n";
    } else {
        $personaId = (int) $personaId;
        echo "  · La persona ya existía, se reutiliza.\n";
    }

    $pdo->prepare("
        INSERT INTO usuarios
            (persona_id, usuario, contrasena_hash, rol, debe_cambiar_contrasena, activo)
        VALUES (?, ?, ?, ?, 0, 1)
    ")->execute([
        $personaId,
        USUARIO_EXTRAORDINARIO,
        password_hash(CONTRASENA_INICIAL, PASSWORD_DEFAULT),
        ROL_EXTRAORDINARIO,
    ]);

    $pdo->commit();

    echo "  · Cuenta creada.\n\n";
    echo "  Ya se puede entrar con:\n\n";
    echo "    usuario:     " . USUARIO_EXTRAORDINARIO . "\n";
    echo "    contraseña:  " . CONTRASENA_INICIAL . "\n\n";
    echo "  Al entrar va a ver el portal con una sola tarjeta: Ingreso de Datos.\n";

} catch (Throwable $e) {
    $pdo->rollBack();
    exit("\n  No se pudo crear: " . $e->getMessage() . "\n");
}
