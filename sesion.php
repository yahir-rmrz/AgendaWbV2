<?php
// Inicia la sesión con cookies seguras y ofrece funciones de ayuda.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}


// Devuelve ['id', 'nombre', 'correo'] o null si no hay sesión
function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}


function iniciarSesion(array $u): void
{
    // Evita fijación de sesión
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id'     => (int) $u['id'],
        'nombre' => $u['nombre'],
        'correo' => $u['correo'],
    ];
}


function cerrarSesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}


function requerirLogin(): void
{
    if (!usuarioActual()) {
        header('Location: login.php');
        exit;
    }
}


// Token CSRF para los formularios POST
function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}


function validarCsrf($token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}
