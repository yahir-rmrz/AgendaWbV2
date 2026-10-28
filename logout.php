<?php
require 'sesion.php';

// Solo POST con token CSRF (el botón del menú es un formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validarCsrf($_POST['csrf'] ?? '')) {
    cerrarSesion();
}

header('Location: index.php');
exit;
