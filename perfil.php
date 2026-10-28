<?php
require 'conexion.php';
require 'sesion.php';

if (!function_exists('e')) {
    function e($texto) {
        return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    }
}

requerirLogin();

$idUsuario = usuarioActual()['id'];

// Datos frescos desde la base de datos
$stmt = $conexion->prepare(
    'SELECT id, nombre, correo, password_hash, creado_en
       FROM usuarios WHERE id = ?'
);
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// La cuenta ya no existe: cerrar sesión
if (!$usuario) {
    cerrarSesion();
    header('Location: login.php');
    exit;
}

$errores = [];
$exito   = '';

if (isset($_GET['error']) && $_GET['error'] === 'eliminar') {
    $errores['general'] = 'No se pudo eliminar la cuenta. Inténtalo de nuevo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf($_POST['csrf'] ?? '')) {
        $errores['general'] = 'La sesión expiró. Inténtalo de nuevo.';
    } else {

        $accion = $_POST['accion'] ?? '';

        /* ---------- CAMBIAR NOMBRE ---------- */
        if ($accion === 'nombre') {

            $nuevo = trim($_POST['nombre'] ?? '');

            if ($nuevo === '' || mb_strlen($nuevo) > 80) {
                $errores['nombre'] = 'Escribe tu nombre (máx. 80 caracteres).';
            } else {
                $stmt = $conexion->prepare('UPDATE usuarios SET nombre = ? WHERE id = ?');
                $stmt->bind_param('si', $nuevo, $idUsuario);
                $stmt->execute();
                $stmt->close();

                $usuario['nombre']           = $nuevo;
                $_SESSION['usuario']['nombre'] = $nuevo;
                $exito = 'Nombre actualizado.';
            }
        }

        /* ---------- CAMBIAR CONTRASEÑA ---------- */
        if ($accion === 'password') {

            $actual    = $_POST['actual'] ?? '';
            $nueva     = $_POST['nueva'] ?? '';
            $confirmar = $_POST['confirmar'] ?? '';

            if (!password_verify($actual, $usuario['password_hash'])) {
                $errores['actual'] = 'La contraseña actual no es correcta.';
            }

            if (strlen($nueva) < 8) {
                $errores['nueva'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif (strlen($nueva) > 72) {
                $errores['nueva'] = 'La contraseña es demasiado larga (máx. 72).';
            }

            if ($nueva !== $confirmar) {
                $errores['confirmar'] = 'Las contraseñas no coinciden.';
            }

            if (empty($errores)) {
                $hash = password_hash($nueva, PASSWORD_DEFAULT);

                $stmt = $conexion->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
                $stmt->bind_param('si', $hash, $idUsuario);
                $stmt->execute();
                $stmt->close();

                $usuario['password_hash'] = $hash;
                session_regenerate_id(true);
                $exito = 'Contraseña actualizada.';
            }
        }
    }
}

$conexion->close();
$csrf = tokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil · AgendaWeb</title>

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

<main class="auth auth--ancho">

    <div class="auth__marca">
        <h1>Mi perfil</h1>
        <p><?= e($usuario['correo']) ?></p>
    </div>

    <?php if ($exito !== ''): ?>
        <div class="mensaje-exito" role="status"><?= e($exito) ?></div>
    <?php endif; ?>

    <?php if (isset($errores['general'])): ?>
        <div class="mensaje-error" role="alert"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <section class="auth__tarjeta">
        <h2 class="auth__subtitulo">Datos de la cuenta</h2>

        <form class="formulario" method="post" action="perfil.php">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="accion" value="nombre">

            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre"
                       value="<?= e($usuario['nombre']) ?>" maxlength="80" required>
                <?php if (isset($errores['nombre'])): ?>
                    <small class="error-campo"><?= e($errores['nombre']) ?></small>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" value="<?= e($usuario['correo']) ?>" disabled>
            </div>

            <p class="auth__nota">
                Cuenta creada el <?= e(date('d/m/Y', strtotime($usuario['creado_en']))) ?>
            </p>

            <button class="boton-crear auth__boton" type="submit">Guardar nombre</button>
        </form>
    </section>

    <section class="auth__tarjeta">
        <h2 class="auth__subtitulo">Cambiar contraseña</h2>

        <form class="formulario" method="post" action="perfil.php">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="accion" value="password">

            <div class="campo">
                <label for="actual">Contraseña actual</label>
                <input type="password" id="actual" name="actual"
                       autocomplete="current-password" required>
                <?php if (isset($errores['actual'])): ?>
                    <small class="error-campo"><?= e($errores['actual']) ?></small>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="nueva">Nueva contraseña</label>
                <input type="password" id="nueva" name="nueva"
                       minlength="8" maxlength="72"
                       autocomplete="new-password" required>
                <?php if (isset($errores['nueva'])): ?>
                    <small class="error-campo"><?= e($errores['nueva']) ?></small>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="confirmar">Confirmar nueva contraseña</label>
                <input type="password" id="confirmar" name="confirmar"
                       minlength="8" maxlength="72"
                       autocomplete="new-password" required>
                <?php if (isset($errores['confirmar'])): ?>
                    <small class="error-campo"><?= e($errores['confirmar']) ?></small>
                <?php endif; ?>
            </div>

            <button class="boton-crear auth__boton" type="submit">Cambiar contraseña</button>
        </form>
    </section>

    <section class="auth__tarjeta">
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <button class="boton-cancelar auth__boton" type="submit">Cerrar sesión</button>
        </form>
    </section>

    <!-- ---------- ELIMINAR CUENTA ---------- -->
    <section class="auth__tarjeta">
        <h2 class="auth__subtitulo">Eliminar cuenta</h2>

        <p class="auth__nota">
            Esta acción es permanente. Se eliminarán tu cuenta y los eventos asociados.
            Después podrás registrar una cuenta nueva utilizando el mismo correo.
        </p>

        <form
            method="post"
            action="eliminar_cuenta.php"
            onsubmit="return confirm('¿Estás seguro de que deseas eliminar tu cuenta? Esta acción es permanente y no se puede deshacer.');"
        >
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

            <button class="boton-cancelar auth__boton" type="submit">
                Eliminar cuenta permanentemente
            </button>
        </form>
    </section>

    <p class="auth__volver"><a href="index.php">← Volver a mis eventos</a></p>

</main>

</body>
</html>
