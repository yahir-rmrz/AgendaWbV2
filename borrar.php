<?php
require 'conexion.php';

// Solo se acepta POST (el botón Borrar de index.php es un formulario)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $conexion->prepare('DELETE FROM eventos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

$conexion->close();

header('Location: index.php');
exit;