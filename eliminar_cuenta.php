<?php
require 'conexion.php';
require 'sesion.php';

// Verificar que el usuario haya iniciado sesión.
requerirLogin();

// Obtener el usuario actual.
$usuario = usuarioActual();
$idUsuario = (int)$usuario['id'];

// Solo se acepta POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $conexion->close();
    header('Location: perfil.php');
    exit;
}

// Validar el token CSRF.
if (!validarCsrf($_POST['csrf'] ?? '')) {
    $conexion->close();
    header('Location: perfil.php');
    exit;
}

// Eliminar únicamente la cuenta del usuario actual.
$stmt = $conexion->prepare(
    'DELETE FROM usuarios WHERE id = ?'
);

$stmt->bind_param('i', $idUsuario);
$stmt->execute();

$eliminado = $stmt->affected_rows === 1;

$stmt->close();
$conexion->close();

// Si no se eliminó la cuenta, regresar al perfil con un error.
if (!$eliminado) {
    header('Location: perfil.php?error=eliminar');
    exit;
}

// Cerrar la sesión después de eliminar la cuenta.
cerrarSesion();

// Redirigir al inicio de sesión.
header('Location: login.php');
exit;
