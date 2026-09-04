<?php
/**
 * Cambio de contraseña.
 *
 * Sale sola la primera vez que alguien entra, porque todas las cuentas se
 * crean con la misma contraseña inicial: hasta que cada quien elija la suya,
 * cualquiera que conozca esa clave podría entrar en su nombre.
 *
 * También se puede abrir cuando uno quiera, para cambiarla.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/sesion.php';

$u = usuario_actual();

if ($u === null) {
    header('Location: login.php');
    exit;
}

// Cuenta compartida (Personal Extraordinario): la contraseña la administra
// quien administra, no cada persona que entra con ella. Ni siquiera se le
// muestra la pantalla.
if ($u['rol'] === ROL_EXTRAORDINARIO) {
    header('Location: ' . base_url());
    exit;
}

$error = '';
$listo = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Volvé a intentarlo.';
    } else {
        $resultado = cambiar_contrasena(
            (string) ($_POST['actual'] ?? ''),
            (string) ($_POST['contrasena'] ?? ''),
            (string) ($_POST['confirmacion'] ?? '')
        );

        if ($resultado['ok']) {
            $listo = true;
            $u = usuario_actual();
        } else {
            $error = $resultado['error'];
        }
    }
}

$obligatorio = $u['debe_cambiar'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Contraseña · Gestión PAA · UCR</title>
  <link rel="icon" type="image/png" href="assets/favicon-paa.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #0055a4;
            --primary-dark: #003b73;
            --secondary: #6EC1E4;
            --success: #10b981;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0055a4 0%, #2b7fc2 55%, #6EC1E4 100%);
            color: var(--gray-800);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem;
        }

        .caja {
            background: #fff;
            width: 100%; max-width: 460px;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            padding: 2.5rem 2.25rem;
        }

        h1 {
            font-size: 1.4rem; font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .sub { color: var(--gray-500); font-size: 0.875rem; margin-bottom: 1.75rem; }

        .aviso {
            background: #fef3c7; color: #92400e;
            font-size: 0.875rem; padding: 0.9rem 1rem;
            border-radius: 0.6rem; margin-bottom: 1.5rem;
            display: flex; gap: 0.6rem; align-items: flex-start;
        }

        .campo { margin-bottom: 1.1rem; }

        label {
            display: block; font-size: 0.8125rem; font-weight: 600;
            color: var(--gray-600); margin-bottom: 0.35rem;
        }

        input {
            width: 100%; font-family: inherit; font-size: 0.95rem;
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--gray-300); border-radius: 0.6rem;
        }

        input:focus {
            outline: 2px solid var(--secondary); outline-offset: -1px;
        }

        .requisitos {
            font-size: 0.75rem; color: var(--gray-500);
            margin-top: 0.35rem; line-height: 1.6;
        }

        button {
            width: 100%; font-family: inherit; font-size: 0.95rem; font-weight: 600;
            padding: 0.8rem; margin-top: 0.5rem; border: none;
            border-radius: 0.6rem; background: var(--primary); color: #fff;
            cursor: pointer; display: flex; align-items: center;
            justify-content: center; gap: 0.5rem;
        }

        button:hover { background: var(--primary-dark); }

        .error, .exito {
            font-size: 0.875rem; padding: 0.8rem 1rem;
            border-radius: 0.6rem; margin-bottom: 1.25rem;
            display: flex; gap: 0.5rem; align-items: flex-start;
        }

        .error { background: #fee2e2; color: #991b1b; }
        .exito { background: #d1fae5; color: #065f46; }

        .enlace {
            display: block; text-align: center; margin-top: 1.5rem;
            color: var(--primary); text-decoration: none;
            font-size: 0.875rem; font-weight: 600;
        }

        .enlace:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="caja">
        <h1><?= $obligatorio ? 'Elegí tu contraseña' : 'Cambiar contraseña' ?></h1>
        <p class="sub"><?= h($u['nombre']) ?> · <?= h($u['usuario']) ?></p>

        <?php if ($listo): ?>
            <div class="exito">
                <i class="fa-solid fa-circle-check" style="margin-top:2px;"></i>
                <span>Listo. De ahora en adelante entrá con esta contraseña.</span>
            </div>
            <a class="enlace" href="index.php">
                <i class="fa-solid fa-arrow-right"></i> Continuar al sistema
            </a>

        <?php else: ?>

            <?php if ($obligatorio): ?>
                <div class="aviso">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;"></i>
                    <span>
                        Estás usando la contraseña inicial, que es la misma para
                        todas las personas. Elegí una propia para continuar.
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="error">
                    <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= h(token_csrf()) ?>">

                <div class="campo">
                    <label for="actual">Contraseña actual</label>
                    <input type="password" id="actual" name="actual" required autofocus
                           autocomplete="current-password">
                </div>

                <div class="campo">
                    <label for="contrasena">Contraseña nueva</label>
                    <input type="password" id="contrasena" name="contrasena" required
                           autocomplete="new-password" minlength="<?= LARGO_MINIMO_CONTRASENA ?>">
                    <span class="requisitos">
                        Al menos <?= LARGO_MINIMO_CONTRASENA ?> caracteres y distinta de la inicial.
                    </span>
                </div>

                <div class="campo">
                    <label for="confirmacion">Repetila</label>
                    <input type="password" id="confirmacion" name="confirmacion" required
                           autocomplete="new-password" minlength="<?= LARGO_MINIMO_CONTRASENA ?>">
                </div>

                <button type="submit">
                    <i class="fa-solid fa-key"></i> Guardar contraseña
                </button>
            </form>

            <?php if ($obligatorio): ?>
                <a class="enlace" href="salir.php">
                    <i class="fa-solid fa-arrow-left"></i> Volver al login
                </a>
            <?php else: ?>
                <a class="enlace" href="index.php">Cancelar y volver</a>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
