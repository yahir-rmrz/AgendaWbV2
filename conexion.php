<?php

$servidor = "localhost";
$usuario = "root";
$password = "123456";
$baseDatos = "agenda";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $password,
    $baseDatos
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

echo "Conexión correcta";