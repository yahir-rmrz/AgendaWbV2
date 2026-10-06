<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conexion = new mysqli(
        "HOST",
        "USUARIO",
        "CONTRASEÑA",
        "sugary_bedroom_giy_db"
    );

    $conexion->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    die("No se pudo conectar a la base de datos.");

}