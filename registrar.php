<?php

require_once "conexion.php";

// Verificar que el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Recibir los datos
$titulo = trim($_POST["titulo"] ?? "");
$fecha = trim($_POST["fecha"] ?? "");
$hora = trim($_POST["hora"] ?? "");
$lugar = trim($_POST["lugar"] ?? "");
$categoria = trim($_POST["categoria"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");

// Categorías permitidas
$categoriasPermitidas = [
    "personal",
    "trabajo",
    "estudio",
    "otro"
];

// Validar campos obligatorios
if ($titulo === "" || $fecha === "" || $categoria === "") {
    die("Error: faltan campos obligatorios.");
}

// Validar categoría
if (!in_array($categoria, $categoriasPermitidas, true)) {
    die("Error: categoría no válida.");
}

// Preparar la consulta
$sql = "INSERT INTO eventos
        (titulo, fecha, hora, lugar, categoria, descripcion)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

// Vincular los datos
$stmt->bind_param(
    "ssssss",
    $titulo,
    $fecha,
    $hora,
    $lugar,
    $categoria,
    $descripcion
);

// Ejecutar
if ($stmt->execute()) {

    // Redirigir al calendario después de guardar
    header("Location: index.php?ok=1");
    exit;

} else {
    die("Error al guardar el evento: " . $stmt->error);
}

// Cerrar
$stmt->close();
$conexion->close();

?>