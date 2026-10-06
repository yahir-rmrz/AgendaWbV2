<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}


/* =========================================
   RECIBIR DATOS
   ========================================= */

$titulo = trim($_POST["titulo"] ?? "");
$fecha = trim($_POST["fecha"] ?? "");
$hora = trim($_POST["hora"] ?? "");
$categoria = trim($_POST["categoria"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");


/* =========================================
   VALIDAR
   ========================================= */

$errores = [];

$categoriasOK = [
    "trabajo",
    "personal",
    "estudio",
    "ocio"
];


if ($titulo === "") {

    $errores["titulo"] =
        "El título es obligatorio.";

} elseif (mb_strlen($titulo) > 120) {

    $errores["titulo"] =
        "Máximo 120 caracteres.";

}


if ($fecha === "") {

    $errores["fecha"] =
        "La fecha es obligatoria.";

} else {

    $fechaValida =
        DateTime::createFromFormat(
            "Y-m-d",
            $fecha
        );

    if (
        !$fechaValida ||
        $fechaValida->format("Y-m-d") !== $fecha
    ) {

        $errores["fecha"] =
            "La fecha no es válida.";

    }

}


if (!in_array(
    $categoria,
    $categoriasOK,
    true
)) {

    $errores["categoria"] =
        "Elige una categoría válida.";

}


if (mb_strlen($descripcion) > 500) {

    $errores["descripcion"] =
        "Máximo 500 caracteres.";

}


/* =========================================
   SI HAY ERRORES
   ========================================= */

if (!empty($errores)) {

    $mensaje =
        implode(
            " ",
            $errores
        );

    header(
        "Location: index.php?error=" .
        urlencode($mensaje)
    );

    exit;
}


/* =========================================
   GUARDAR EN MYSQL
   ========================================= */

$sql = "
    INSERT INTO eventos
    (
        titulo,
        fecha,
        hora,
        categoria,
        descripcion
    )
    VALUES (?, ?, ?, ?, ?)
";


// Hora y descripción son opcionales: vacío -> NULL en MySQL
$hora = ($hora === "") ? null : $hora;
$descripcion = ($descripcion === "") ? null : $descripcion;

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "sssss",
    $titulo,
    $fecha,
    $hora,
    $categoria,
    $descripcion
);

$stmt->execute();

$stmt->close();

$conexion->close();


/* =========================================
   REDIRECCIÓN PRG
   ========================================= */

header("Location: index.php?ok=1");
exit;

?>