<?php
require 'conexion.php';
require 'sesion.php';

if (!function_exists('e')) {
    function e($texto) {
        return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    }
}

// Si ya hay sesión, no tiene sentido ver el login
if (usuarioActual()) {
    header('Location: index.php');
    exit;
}

$modo    = (($_GET['modo'] ?? '') === 'registro') ? 'registro' : 'login';
$errores = [];
$nombre  = '';
$correo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf($_POST['csrf'] ?? '')) {
        $errores['general'] = 'La sesión expiró. Inténtalo de nuevo.';
    } else {

        $accion   = $_POST['accion'] ?? '';
        $correo   = mb_strtolower(trim($_POST['correo'] ?? ''));
        $password = $_POST['password'] ?? '';

        /* ---------- CREAR CUENTA ---------- */
        if ($accion === 'registro') {

            $modo      = 'registro';
            $nombre    = trim($_POST['nombre'] ?? '');
            $confirmar = $_POST['confirmar'] ?? '';

            if ($nombre === '' || mb_strlen($nombre) > 80) {
                $errores['nombre'] = 'Escribe tu nombre (máx. 80 caracteres).';
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
                $errores['correo'] = 'Escribe un correo válido.';
            }

            if (strlen($password) < 8) {
                $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif (strlen($password) > 72) {
                $errores['password'] = 'La contraseña es demasiado larga (máx. 72).';
            }

            if ($password !== $confirmar) {
                $errores['confirmar'] = 'Las contraseñas no coinciden.';
            }

            if (empty($errores)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $stmt = $conexion->prepare(
                        'INSERT INTO usuarios (nombre, correo, password_hash)
                         VALUES (?, ?, ?)'
                    );
                    $stmt->bind_param('sss', $nombre, $correo, $hash);
                    $stmt->execute();
                    $nuevoId = $stmt->insert_id;
                    $stmt->close();

                    iniciarSesion([
                        'id'     => $nuevoId,
                        'nombre' => $nombre,
                        'correo' => $correo,
                    ]);

                    $conexion->close();
                    header('Location: index.php');
                    exit;

                } catch (mysqli_sql_exception $ex) {
                    if ((int)$ex->getCode() === 1062) {
                        $errores['correo'] = 'Ese correo ya tiene una cuenta.';
                    } else {
                        $errores['general'] = 'No se pudo crear la cuenta.';
                    }
                }
            }

        /* ---------- INICIAR SESIÓN ---------- */
        } else {

            $modo = 'login';

            $stmt = $conexion->prepare(
                'SELECT id, nombre, correo, password_hash
                   FROM usuarios
                  WHERE correo = ?'
            );
            $stmt->bind_param('s', $correo);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                iniciarSesion($usuario);
                $conexion->close();
                header('Location: index.php');
                exit;
            }

            // Mensaje genérico: no revela si el correo existe
            $errores['general'] = 'Correo o contraseña incorrectos.';
        }
    }
}

$csrf = tokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión' ?> · AgendaWeb</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">

    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem("agendaweb_tema"); } catch (e) {}
            if (tema !== "light" && tema !== "dark") {
                tema = window.matchMedia("(prefers-color-scheme: light)").matches ? "light" : "dark";
            }
            document.documentElement.setAttribute("data-theme", tema);
        })();
    </script>

    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="cuenta.css">
</head>
<body class="pagina-auth">

<main class="auth">

    <div class="auth__marca">
        <h1>AgendaWeb</h1>
        <p>Organiza tus eventos y actividades</p>
    </div>

    <section class="auth__tarjeta">

        <nav class="auth__tabs" aria-label="Acceso">
            <a href="login.php"
               class="<?= $modo === 'login' ? 'activa' : '' ?>"
               <?= $modo === 'login' ? 'aria-current="page"' : '' ?>>Iniciar sesión</a>
            <a href="login.php?modo=registro"
               class="<?= $modo === 'registro' ? 'activa' : '' ?>"
               <?= $modo === 'registro' ? 'aria-current="page"' : '' ?>>Crear cuenta</a>
        </nav>

        <?php if (isset($errores['general'])): ?>
            <div class="mensaje-error" role="alert"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <?php if ($modo === 'login'): ?>

            <form class="formulario" method="post" action="login.php">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="login">

                <div class="campo">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo"
                           value="<?= e($correo) ?>" maxlength="120"
                           autocomplete="email" required autofocus>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password"
                           autocomplete="current-password" required>
                </div>

                <button class="boton-crear auth__boton" type="submit">Entrar</button>
            </form>

            <p class="auth__pie">
                ¿No tienes cuenta?
                <a href="login.php?modo=registro">Crea una</a>
            </p>

        <?php else: ?>

            <form class="formulario" method="post" action="login.php?modo=registro">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="registro">

                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre"
                           value="<?= e($nombre) ?>" maxlength="80"
                           autocomplete="name" required autofocus>
                    <?php if (isset($errores['nombre'])): ?>
                        <small class="error-campo"><?= e($errores['nombre']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="campo">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo"
                           value="<?= e($correo) ?>" maxlength="120"
                           autocomplete="email" required>
                    <?php if (isset($errores['correo'])): ?>
                        <small class="error-campo"><?= e($errores['correo']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password"
                           minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                    <?php if (isset($errores['password'])): ?>
                        <small class="error-campo"><?= e($errores['password']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="campo">
                    <label for="confirmar">Confirmar contraseña</label>
                    <input type="password" id="confirmar" name="confirmar"
                           minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                    <?php if (isset($errores['confirmar'])): ?>
                        <small class="error-campo"><?= e($errores['confirmar']) ?></small>
                    <?php endif; ?>
                </div>

                <button class="boton-crear auth__boton" type="submit">Crear cuenta</button>
            </form>

            <p class="auth__pie">
                ¿Ya tienes cuenta?
                <a href="login.php">Inicia sesión</a>
            </p>

        <?php endif; ?>

    </section>

    <p class="auth__volver"><a href="index.php">← Volver a AgendaWeb</a></p>

</main>

</body>
</html>
