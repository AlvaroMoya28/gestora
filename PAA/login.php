<?php
/**
 * Pantalla de ingreso al sistema.
 *
 * El nombre de usuario es el correo institucional hasta la arroba:
 * "guaner.rojas@ucr.ac.cr" entra como "guaner.rojas".
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/sesion.php';

// Si ya hay sesión, no tiene sentido volver a pedir la contraseña.
if (hay_sesion()) {
    $u = usuario_actual();
    header('Location: ' . ($u['debe_cambiar'] ? 'cambiar_contrasena.php' : 'index.php'));
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Volvé a intentarlo.';
    } else {
        $resultado = autenticar($_POST['usuario'] ?? '', $_POST['contrasena'] ?? '');

        if ($resultado['ok']) {
            header('Location: ' . ($resultado['usuario']['debe_cambiar']
                ? 'cambiar_contrasena.php'
                : 'index.php'));
            exit;
        }

        $error = $resultado['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Ingreso · Gestión PAA · UCR</title>
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
            --primary-light: #2b7fc2;
            --secondary: #6EC1E4;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .caja {
            background: #fff;
            width: 100%;
            max-width: 420px;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            padding: 2.5rem 2.25rem;
        }

        .marca { text-align: center; margin-bottom: 2rem; }

        .marca .escudo {
            width: 62px; height: 62px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.6rem;
        }

        .marca h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--primary-dark);
        }

        .marca p { color: var(--gray-500); font-size: 0.875rem; margin-top: 0.25rem; }

        .campo { margin-bottom: 1.1rem; }

        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 0.35rem;
        }

        .entrada { position: relative; }

        .entrada i {
            position: absolute;
            left: 0.9rem; top: 50%;
            transform: translateY(-50%);
            color: var(--gray-300);
        }

        input {
            width: 100%;
            font-family: inherit;
            font-size: 0.95rem;
            padding: 0.75rem 0.9rem 0.75rem 2.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.6rem;
            background: #fff;
        }

        input:focus {
            outline: 2px solid var(--secondary);
            outline-offset: -1px;
            border-color: var(--primary-light);
        }

        .sufijo {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.3rem;
            display: block;
        }

        button {
            width: 100%;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0.8rem;
            margin-top: 0.5rem;
            border: none;
            border-radius: 0.6rem;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }

        button:hover { background: var(--primary-dark); }

        .error {
            background: #fee2e2;
            color: #991b1b;
            font-size: 0.875rem;
            padding: 0.8rem 1rem;
            border-radius: 0.6rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: flex-start; gap: 0.5rem;
        }

        .pie {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--gray-200);
            text-align: center;
            font-size: 0.75rem;
            color: var(--gray-500);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="caja">
        <div class="marca">
            <div class="escudo"><i class="fa-solid fa-graduation-cap"></i></div>
            <h1>Gestión PAA</h1>
            <p>Programa Permanente de la Prueba de Aptitud Académica</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error">
                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;"></i>
                <span><?= h($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= h(token_csrf()) ?>">

            <div class="campo">
                <label for="usuario">Usuario</label>
                <div class="entrada">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" id="usuario" name="usuario" required autofocus
                           autocomplete="username" maxlength="100"
                           value="<?= h($_POST['usuario'] ?? '') ?>"
                           placeholder="nombre.apellido">
                </div>
                <span class="sufijo">Tu correo institucional sin <strong>@ucr.ac.cr</strong></span>
            </div>

            <div class="campo">
                <label for="contrasena">Contraseña</label>
                <div class="entrada">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="contrasena" name="contrasena" required
                           autocomplete="current-password">
                </div>
            </div>

            <button type="submit">
                <i class="fa-solid fa-right-to-bracket"></i> Ingresar
            </button>
        </form>

        <div class="pie">
            ¿Primera vez? Entrá con la contraseña que te dieron y el sistema
            te va a pedir que elijas una propia.<br>
            Si no podés entrar, escribile a la administración del PAA.
        </div>
    </div>
</body>
</html>
