<?php
require 'conexion.php';

if (!function_exists('e')) {
    function e($texto) {
        return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    }
}

// Obtener las categorías de la base de datos
$resultado = $conexion->query(
    'SELECT id, nombre FROM categorias ORDER BY nombre'
);

$categorias = $resultado->fetch_all(MYSQLI_ASSOC);

$idsValidos = array_map(
    'intval',
    array_column($categorias, 'id')
);

$titulo = '';
$fecha = '';
$hora = '';
$categoriaId = 0;
$descripcion = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($titulo === '') {
        $errores['titulo'] = 'Escribe el título del evento.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'Selecciona una fecha.';
    }

    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    if (empty($errores)) {
        $horaBD = $hora !== '' ? $hora : null;
        $descBD = $descripcion !== '' ? $descripcion : null;

        $sql = 'INSERT INTO eventos
                (titulo, fecha, hora, categoria_id, descripcion)
                VALUES (?, ?, ?, ?, ?)';

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            'sssis',
            $titulo,
            $fecha,
            $horaBD,
            $categoriaId,
            $descBD
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conexion->close();

            header('Location: index.php');
            exit;
        } else {
            $errores['general'] = 'No se pudo guardar el evento.';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar evento</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<main class="container">
    <h1>Registrar evento</h1>

    <?php if (isset($errores['general'])): ?>
        <p><?= e($errores['general']) ?></p>
    <?php endif; ?>

    <form method="POST" action="registrar.php">

        <label for="titulo">Título</label>
        <input
            type="text"
            id="titulo"
            name="titulo"
            maxlength="120"
            value="<?= e($titulo) ?>"
            required
        >

        <?php if (isset($errores['titulo'])): ?>
            <p><?= e($errores['titulo']) ?></p>
        <?php endif; ?>

        <label for="fecha">Fecha</label>
        <input
            type="date"
            id="fecha"
            name="fecha"
            value="<?= e($fecha) ?>"
            required
        >

        <?php if (isset($errores['fecha'])): ?>
            <p><?= e($errores['fecha']) ?></p>
        <?php endif; ?>

        <label for="hora">Hora</label>
        <input
            type="time"
            id="hora"
            name="hora"
            value="<?= e($hora) ?>"
        >

        <label for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">— Elige una —</option>

            <?php foreach ($categorias as $cat): ?>
                <option
                    value="<?= (int)$cat['id'] ?>"
                    <?= $categoriaId === (int)$cat['id']
                        ? 'selected'
                        : '' ?>
                >
                    <?= e($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (isset($errores['categoria_id'])): ?>
            <p><?= e($errores['categoria_id']) ?></p>
        <?php endif; ?>

        <label for="descripcion">Descripción</label>
        <textarea
            id="descripcion"
            name="descripcion"
            maxlength="500"
        ><?= e($descripcion) ?></textarea>

        <button type="submit">Guardar evento</button>
    </form>

    <p><a href="index.php">Volver a mis eventos</a></p>
</main>

</body>
</html>
