<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(
        "localhost",
        "yellow-convert-gas",
        "F-_91LpcB+P69wW9wg",
        "yellow-convert-gas_db"
    );

    $conexion->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    die("No se pudo conectar a la base de datos.");
}