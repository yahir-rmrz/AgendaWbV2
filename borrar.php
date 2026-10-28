<?php
require 'conexion.php';
require_once 'sesion.php';

// Obtener el usuario que inició sesión.
$usuario = usuarioActual();

// Si no hay una sesión iniciada, regresar al login.
if (!$usuario) {
    header('Location: login.php');
    exit;
}

// Obtener el ID del usuario actual.
$idUsuario = (int)$usuario['id'];

// Solo se acepta POST (el botón Borrar de index.php es un formulario)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $conexion->prepare(
        'DELETE FROM eventos
          WHERE id = ? AND usuario_id = ?'
    );

    $stmt->bind_param('ii', $id, $idUsuario);
    $stmt->execute();
    $stmt->close();
}

$conexion->close();

header('Location: index.php');
exit;
