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

if (!function_exists('e')) {
    function e($texto) {
        return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    }
}

// Categorías para el select
$resultado = $conexion->query(
    'SELECT id, nombre FROM categorias ORDER BY nombre'
);
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);

$idsValidos = array_map('intval', array_column($categorias, 'id'));

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar cambios
    $id          = (int)($_POST['id'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $fecha       = $_POST['fecha'] ?? '';
    $hora        = $_POST['hora'] ?? '';
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($id <= 0) {
        $errores['general'] = 'Evento no válido.';
    }

    if ($titulo === '') {
        $errores['titulo'] = 'Escribe el título del evento.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'Selecciona una fecha.';
    }

    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    // Comprobar que el evento pertenece al usuario actual.
    if (empty($errores)) {
        $stmt = $conexion->prepare(
            'SELECT id
               FROM eventos
              WHERE id = ? AND usuario_id = ?'
        );

        $stmt->bind_param('ii', $id, $idUsuario);
        $stmt->execute();

        $eventoPropio = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if (!$eventoPropio) {
            $errores['general'] = 'El evento no existe o no tienes permiso para editarlo.';
        }
    }

    if (empty($errores)) {
        $horaBD = $hora !== '' ? $hora : null;
        $descBD = $descripcion !== '' ? $descripcion : null;

        $stmt = $conexion->prepare(
            'UPDATE eventos
                SET titulo = ?, fecha = ?, hora = ?,
                    categoria_id = ?, descripcion = ?
              WHERE id = ? AND usuario_id = ?'
        );

        $stmt->bind_param(
            'sssisii',
            $titulo,
            $fecha,
            $horaBD,
            $categoriaId,
            $descBD,
            $id,
            $idUsuario
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conexion->close();
            header('Location: index.php');
            exit;
        }

        $errores['general'] = 'No se pudo actualizar el evento.';
        $stmt->close();
    }

} else {
    // Cargar el evento a editar
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $conexion->prepare(
        'SELECT id, titulo, fecha, hora, categoria_id, descripcion
           FROM eventos
          WHERE id = ? AND usuario_id = ?'
    );

    $stmt->bind_param('ii', $id, $idUsuario);
    $stmt->execute();
    $evento = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$evento) {
        $conexion->close();
        header('Location: index.php?error=' . urlencode('El evento no existe o no tienes permiso para editarlo.'));
        exit;
    }

    $titulo      = $evento['titulo'];
    $fecha       = $evento['fecha'];
    $hora        = $evento['hora'] ? substr($evento['hora'], 0, 5) : '';
    $categoriaId = (int)$evento['categoria_id'];
    $descripcion = $evento['descripcion'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar evento</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<main class="container">
    <h1>Editar evento</h1>

    <?php if (isset($errores['general'])): ?>
        <p><?= e($errores['general']) ?></p>
    <?php endif; ?>

    <form method="POST" action="editar.php">

        <input type="hidden" name="id" value="<?= (int)$id ?>">

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
                    <?= $categoriaId === (int)$cat['id'] ? 'selected' : '' ?>
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

        <button type="submit">Guardar cambios</button>
    </form>

    <p><a href="index.php">Volver a mis eventos</a></p>
</main>

</body>
</html>
