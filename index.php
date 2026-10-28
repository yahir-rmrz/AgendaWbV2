<?php

require_once "conexion.php";
require_once "sesion.php";

$usuario = usuarioActual();

// Si no hay una sesión iniciada, mostrar el login.
if (!$usuario) {
    header("Location: login.php");
    exit;
}

/* =========================================
   FUNCIONES DE AYUDA
   ========================================= */

// Limpia un texto antes de mostrarlo (evita XSS)
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}


// '2026-10-08' -> '08/10/2026'
function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}


// Conservamos la función original para no eliminar
// compatibilidad con las claves de categorías anteriores.
function nombreCategoria(string $clave): string
{
    $nombres = [
        'trabajo'  => 'Trabajo',
        'personal' => 'Personal',
        'estudio'  => 'Escuela',
        'ocio'     => 'Ocio',
    ];

    return $nombres[strtolower($clave)] ?? $clave;
}


// '10:30:00' -> '10:30 AM'
function formatearHora(string $hora): string
{
    return date('g:i A', strtotime($hora));
}


/* =========================================
   TARJETA DE UN EVENTO
   ========================================= */

function mostrarEvento(array $ev): string
{
    $mesesCortos = [
        'ENE',
        'FEB',
        'MAR',
        'ABR',
        'MAY',
        'JUN',
        'JUL',
        'AGO',
        'SEP',
        'OCT',
        'NOV',
        'DIC'
    ];

    $marca = strtotime($ev['fecha']);

    $dia = (int) date('j', $marca);

    $mes = $mesesCortos[
        (int) date('n', $marca) - 1
    ];

    $id = (int) $ev['id'];


    /*
       La tarjeta conserva las clases CSS de P2/P3.

       data-mes:
       0 = enero
       1 = febrero
       ...
       11 = diciembre

       data-anio:
       año del evento
    */

    // En P5 la consulta obtiene el nombre de la categoría
    // desde la tabla categorias.
    $categoriaNombre = $ev['categoria'];

    // Conservamos las clases CSS de las categorías originales.
    $categoriaClase = strtolower(
        preg_replace('/[^a-zA-Z0-9_-]/', '', $categoriaNombre)
    );

    if (strpos(strtolower($categoriaNombre), 'trabajo') !== false) {
        $categoriaClase = 'trabajo';
    } elseif (strpos(strtolower($categoriaNombre), 'personal') !== false) {
        $categoriaClase = 'personal';
    } elseif (
        strpos(strtolower($categoriaNombre), 'estudio') !== false ||
        strpos(strtolower($categoriaNombre), 'escuela') !== false
    ) {
        $categoriaClase = 'estudio';
    } elseif (strpos(strtolower($categoriaNombre), 'ocio') !== false) {
        $categoriaClase = 'ocio';
    }

    $html =
        '<article class="evento"'
        . ' data-mes="' . ((int) date('n', $marca) - 1) . '"'
        . ' data-anio="' . (int) date('Y', $marca) . '">';


    /* =========================================
       FECHA
       ========================================= */

    $html .=
        '<div class="fecha">'
        . '<span class="dia">'
        . $dia
        . '</span>'
        . '<span class="mes-corto">'
        . $mes
        . '</span>'
        . '</div>';


    /* =========================================
       INFORMACIÓN
       ========================================= */

    $html .= '<div class="informacion">';


    // La hora es opcional
    if (!empty($ev['hora'])) {

        $html .=
            '<span class="hora">'
            . e(formatearHora($ev['hora']))
            . '</span>';

    }


    // Título
    $html .=
        '<h3>'
        . e($ev['titulo'])
        . '</h3>';


    // Descripción opcional
    if (!empty($ev['descripcion'])) {

        $html .=
            '<p>'
            . e($ev['descripcion'])
            . '</p>';

    }


    // Categoría obtenida desde la tabla categorias
    $html .=
        '<div class="datos">'
        . '<span class="etiqueta '
        . e($categoriaClase)
        . '">'
        . e(nombreCategoria($categoriaNombre))
        . '</span>'
        . '</div>';


    $html .= '</div>';


    /* =========================================
       ACCIONES
       ========================================= */

    $html .=
        '<div class="acciones-evento">'

        . '<a href="editar.php?id='
        . $id
        . '" class="boton-editar">'
        . 'Editar'
        . '</a>'

        . '<form method="post" action="borrar.php">'

        . '<input type="hidden" name="id" value="'
        . $id
        . '">'

        . '<button type="submit" class="boton-borrar">'
        . 'Borrar'
        . '</button>'

        . '</form>'

        . '</div>';


    return $html . '</article>';
}


/* =========================================
   OBTENER EVENTOS DE MYSQL - P5
   ========================================= */

// INNER JOIN relaciona cada evento con su categoría.
// Se agrega el filtro para obtener únicamente los eventos
// pertenecientes al usuario que inició sesión.

$idUsuario = (int) $usuario['id'];

$sqlEventos = '
    SELECT e.id, e.titulo, e.fecha, e.hora,
           e.categoria_id, e.descripcion,
           c.nombre AS categoria
      FROM eventos e
      INNER JOIN categorias c
              ON c.id = e.categoria_id
     WHERE e.usuario_id = ?
     ORDER BY e.fecha, e.hora
';

$stmtEventos = $conexion->prepare($sqlEventos);
$stmtEventos->bind_param('i', $idUsuario);
$stmtEventos->execute();

$resultado = $stmtEventos->get_result();

$eventos = $resultado->fetch_all(MYSQLI_ASSOC);

$stmtEventos->close();


/*
   MySQL devuelve un arreglo de arreglos asociativos.

   Ahora el nombre de la categoría se obtiene de la
   tabla categorias, en lugar de eventos.categoria.
*/

/* =========================================
   RESUMEN DE EVENTOS POR CATEGORÍA - P5
   ========================================= */

// LEFT JOIN permite mostrar también las categorías
// que todavía no tienen eventos registrados.
// El filtro limita el conteo a los eventos del usuario actual.

$sqlResumen = '
    SELECT c.id, c.nombre, COUNT(e.id) AS total
      FROM categorias c
      LEFT JOIN eventos e
             ON e.categoria_id = c.id
            AND e.usuario_id = ?
     GROUP BY c.id, c.nombre
     ORDER BY c.nombre
';

$stmtResumen = $conexion->prepare($sqlResumen);
$stmtResumen->bind_param('i', $idUsuario);
$stmtResumen->execute();

$resultadoResumen = $stmtResumen->get_result();

$resumenCategorias = $resultadoResumen->fetch_all(MYSQLI_ASSOC);

$stmtResumen->close();


/* =========================================
   OBTENER CATEGORÍAS PARA EL FORMULARIO - P5
   ========================================= */

// Las opciones se cargan desde MySQL.
// Si agregas una categoría, aparecerá automáticamente.
$resultadoCategorias = $conexion->query(
    'SELECT id, nombre
       FROM categorias
      ORDER BY nombre'
);

$categorias = $resultadoCategorias->fetch_all(MYSQLI_ASSOC);


$conexion->close();

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <!-- Fuentes -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap"
        rel="stylesheet">


    <!-- Tema -->

    <script>

        (function () {

            var tema = null;

            try {

                tema =
                    localStorage.getItem(
                        "agendaweb_tema"
                    );

            } catch (e) {}


            if (
                tema !== "light" &&
                tema !== "dark"
            ) {

                tema =
                    window.matchMedia(
                        "(prefers-color-scheme: light)"
                    ).matches
                        ? "light"
                        : "dark";

            }


            document.documentElement.setAttribute(
                "data-theme",
                tema
            );

        })();

    </script>


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="estilos.css">

    <link
        rel="stylesheet"
        href="cuenta.css">


    <title><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></title>

</head>


<body>


    <!-- ========================= -->
    <!-- ENCABEZADO -->
    <!-- ========================= -->

    <header>

        <div class="encabezado">

            <div>

                <h1><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>

                <p>
                    Organiza tus eventos y actividades
                </p>

            </div>


            <div class="encabezado-acciones">


                <!-- TEMA -->

                <button
                    class="selector-tema"
                    id="selectorTema"
                    type="button"
                    role="switch"
                    aria-checked="true"
                    aria-label="Tema oscuro"
                    title="Cambiar entre tema claro y oscuro">

                    <span
                        class="selector-tema__icono selector-tema__icono--sol"
                        aria-hidden="true">
                        ☀️
                    </span>

                    <span
                        class="selector-tema__icono selector-tema__icono--luna"
                        aria-hidden="true">
                        🌙
                    </span>

                    <span
                        class="selector-tema__perilla"
                        aria-hidden="true">
                    </span>

                </button>


                <button
                    class="boton-agregar"
                    type="button"
                    onclick="mostrarFormulario()">

                    + Agregar evento

                </button>


                <!-- CUENTA -->

                <div class="menu-cuenta" id="menuCuenta">

                    <button
                        type="button"
                        class="boton-cuenta"
                        id="botonCuenta"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="panelCuenta"
                        aria-label="Mi cuenta">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                        </svg>

                    </button>


                    <div
                        class="menu-cuenta__panel"
                        id="panelCuenta"
                        hidden>

                        <?php if ($usuario): ?>

                            <div class="menu-cuenta__datos">

                                <strong><?= e($usuario['nombre']) ?></strong>

                                <span><?= e($usuario['correo']) ?></span>

                            </div>

                            <a
                                class="menu-cuenta__item"
                                href="perfil.php">

                                Mi perfil

                            </a>

                            <form
                                method="post"
                                action="logout.php">

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= e(tokenCsrf()) ?>">

                                <button
                                    type="submit"
                                    class="menu-cuenta__item menu-cuenta__item--salir">

                                    Cerrar sesión

                                </button>

                            </form>

                        <?php else: ?>

                            <a
                                class="menu-cuenta__item"
                                href="login.php">

                                Iniciar sesión

                            </a>

                            <a
                                class="menu-cuenta__item"
                                href="login.php?modo=registro">

                                Crear cuenta

                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </header>


    <!-- ========================= -->
    <!-- CONTENEDOR -->
    <!-- ========================= -->

    <div class="contenedor">


        <!-- ========================= -->
        <!-- BARRA LATERAL -->
        <!-- ========================= -->

        <aside class="barra-lateral">


            <button
                class="titulo-meses"
                id="botonMeses"
                type="button"
                aria-expanded="true"
                aria-controls="listaMeses">

                <span
                    class="icono-calendario"
                    aria-hidden="true">
                    📅
                </span>


                <span class="titulo-meses-texto">

                    <span class="titulo-meses-nombre">
                        Mis meses
                    </span>

                    <span class="titulo-meses-rango">
                        2026 - 2027
                    </span>

                </span>


                <span
                    class="indicador-meses"
                    aria-hidden="true">
                    ▼
                </span>

            </button>


            <div
                class="meses-colapsable"
                id="listaMeses">

                <div class="meses-interior">

                    <div class="meses">


                        <!-- SEPTIEMBRE -->

                        <div
                            class="mes seleccionado"
                            data-mes="8"
                            data-anio="2026"
                            onclick="seleccionarMes(8, 2026)">

                            <span>SEP</span>

                            <strong>
                                Septiembre
                            </strong>

                            <small id="contadorMes8">
                                0 eventos
                            </small>

                        </div>


                        <!-- OCTUBRE -->

                        <div
                            class="mes"
                            data-mes="9"
                            data-anio="2026"
                            onclick="seleccionarMes(9, 2026)">

                            <span>OCT</span>

                            <strong>
                                Octubre
                            </strong>

                            <small id="contadorMes9">
                                0 eventos
                            </small>

                        </div>


                        <!-- NOVIEMBRE -->

                        <div
                            class="mes"
                            data-mes="10"
                            data-anio="2026"
                            onclick="seleccionarMes(10, 2026)">

                            <span>NOV</span>

                            <strong>
                                Noviembre
                            </strong>

                            <small id="contadorMes10">
                                0 eventos
                            </small>

                        </div>


                        <!-- DICIEMBRE -->

                        <div
                            class="mes"
                            data-mes="11"
                            data-anio="2026"
                            onclick="seleccionarMes(11, 2026)">

                            <span>DIC</span>

                            <strong>
                                Diciembre
                            </strong>

                            <small id="contadorMes11">
                                0 eventos
                            </small>

                        </div>


                        <!-- ENERO -->

                        <div
                            class="mes"
                            data-mes="0"
                            data-anio="2027"
                            onclick="seleccionarMes(0, 2027)">

                            <span>ENE</span>

                            <strong>
                                Enero
                            </strong>

                            <small id="contadorMes0">
                                0 eventos
                            </small>

                        </div>


                        <!-- FEBRERO -->

                        <div
                            class="mes"
                            data-mes="1"
                            data-anio="2027"
                            onclick="seleccionarMes(1, 2027)">

                            <span>FEB</span>

                            <strong>
                                Febrero
                            </strong>

                            <small id="contadorMes1">
                                0 eventos
                            </small>

                        </div>


                    </div>

                </div>

            </div>

        </aside>


        <!-- ========================= -->
        <!-- CONTENIDO PRINCIPAL -->
        <!-- ========================= -->

        <main>


            <!-- MENSAJE DE ÉXITO -->

            <?php if (isset($_GET["ok"]) && $_GET["ok"] === "1"): ?>

                <div
                    class="mensaje-exito"
                    role="status">

                    Evento guardado correctamente.

                </div>

            <?php endif; ?>


            <!-- MENSAJE DE ERROR -->

            <?php if (isset($_GET["error"])): ?>

                <div class="mensaje-error">

                    <?= e($_GET["error"]) ?>

                </div>

            <?php endif; ?>


            <!-- ========================= -->
            <!-- RESUMEN DE CATEGORÍAS - P5 -->
            <!-- ========================= -->

            <section class="resumen-categorias">

                <div class="titulo-seccion">

                    <div>

                        <span class="subtitulo">
                            ORGANIZACIÓN
                        </span>

                        <h2>
                            Eventos por categoría
                        </h2>

                    </div>

                </div>

                <div class="resumen">

                    <?php foreach ($resumenCategorias as $cat): ?>

                        <span class="etiqueta">

                            <?= e($cat['nombre']) ?>:
                            <?= (int) $cat['total'] ?>

                        </span>

                    <?php endforeach; ?>

                </div>

            </section>


            <!-- ========================= -->
            <!-- EVENTOS -->
            <!-- ========================= -->

            <section
                class="seccion-eventos"
                id="seccionEventos">


                <div class="titulo-seccion">

                    <div>

                        <span
                            class="subtitulo"
                            id="subtituloEventos">

                            SEPTIEMBRE 2026

                        </span>


                        <h2>
                            Próximos eventos
                        </h2>


                        <p id="descripcionMes">
                            Tienes varias actividades programadas.
                        </p>

                    </div>


                    <span
                        class="contador"
                        id="contadorEventos">

                        <?= count($eventos) ?>
                        <?= count($eventos) === 1 ? 'evento' : 'eventos' ?>

                    </span>

                </div>


                <!-- ========================= -->
                <!-- ESTADO VACÍO / FOREACH -->
                <!-- ========================= -->

                <?php if (empty($eventos)): ?>

                    <div class="sin-eventos">

                        <h3>
                            No tienes eventos registrados
                        </h3>

                        <p>
                            Pulsa «+ Agregar evento» para crear el primero.
                        </p>

                    </div>

                <?php else: ?>


                    <div
                        class="eventos"
                        id="listaEventos">


                        <?php foreach ($eventos as $ev): ?>

                            <?= mostrarEvento($ev) ?>

                        <?php endforeach; ?>


                    </div>


                    <!-- Se muestra cuando el mes no tiene eventos -->

                    <div
                        class="sin-eventos"
                        id="sinEventosMes"
                        hidden>

                        <h3>
                            Sin eventos este mes
                        </h3>

                        <p>
                            No hay eventos para este mes.
                        </p>

                    </div>


                <?php endif; ?>


            </section>


            <!-- ========================= -->
            <!-- CALENDARIO -->
            <!-- ========================= -->

            <section
                class="calendario"
                id="calendario">


                <div class="titulo-calendario">

                    <div>

                        <span class="subtitulo">
                            VISTA MENSUAL
                        </span>


                        <h2 id="tituloCalendario">
                            Septiembre 2026
                        </h2>

                    </div>


                    <div class="navegacion">

                        <button
                            type="button"
                            onclick="mesAnterior()"
                            title="Mes anterior">

                            &lt;

                        </button>


                        <button
                            type="button"
                            onclick="mesSiguiente()"
                            title="Mes siguiente">

                            &gt;

                        </button>

                    </div>

                </div>


                <!-- DÍAS DE LA SEMANA -->

                <div class="dias-semana">

                    <span>Lun</span>
                    <span>Mar</span>
                    <span>Mié</span>
                    <span>Jue</span>
                    <span>Vie</span>
                    <span>Sáb</span>
                    <span>Dom</span>

                </div>


                <!-- DÍAS GENERADOS POR JS -->

                <div
                    class="dias"
                    id="diasCalendario">
                </div>


            </section>


            <!-- ========================= -->
            <!-- FORMULARIO -->
            <!-- ========================= -->

            <section
                class="formulario-evento"
                id="formularioEvento">


                <div class="titulo-formulario">

                    <span class="subtitulo">
                        NUEVO EVENTO
                    </span>


                    <h2 id="tituloFormulario">
                        Agregar evento
                    </h2>


                    <p id="descripcionFormulario">
                        Completa la información de tu nuevo evento.
                    </p>

                </div>


                <form
                    class="formulario"
                    method="post"
                    action="registrar.php">


                    <div class="campo">

                        <label for="titulo">
                            Título del evento
                        </label>


                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            placeholder="Ej. Reunión de proyecto"
                            maxlength="120"
                            required>

                    </div>


                    <div class="fila-formulario">

                        <div class="campo">

                            <label for="fecha">
                                Fecha
                            </label>


                            <input
                                type="date"
                                id="fecha"
                                name="fecha"
                                required>

                        </div>


                        <div class="campo">

                            <label for="hora">
                                Hora
                            </label>


                            <input
                                type="time"
                                id="hora"
                                name="hora">

                        </div>

                    </div>


                    <!-- CATEGORÍAS DINÁMICAS - P5 -->

                    <div class="campo">

                        <label for="categoria_id">
                            Categoría
                        </label>


                        <select
                            id="categoria_id"
                            name="categoria_id"
                            required>

                            <option value="">
                                Selecciona una categoría
                            </option>

                            <?php foreach ($categorias as $cat): ?>

                                <option value="<?= (int) $cat['id'] ?>">

                                    <?= e($cat['nombre']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="campo">

                        <label for="descripcion">
                            Descripción
                        </label>


                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                            maxlength="500"
                            placeholder="Escribe una descripción del evento..."></textarea>

                    </div>


                    <div class="botones-formulario">


                        <button
                            class="boton-cancelar"
                            type="button"
                            onclick="ocultarFormulario()">

                            ← Regresar

                        </button>


                        <button
                            class="boton-crear"
                            type="submit">

                            Crear evento

                        </button>


                    </div>


                </form>


            </section>


        </main>

    </div>


    <!-- ========================= -->
    <!-- PIE DE PÁGINA -->
    <!-- ========================= -->

    <footer>

        <p>
            AgendaWeb · Organiza tu tiempo de manera sencilla
        </p>

    </footer>


    <!-- ========================= -->
    <!-- EVENTOS PARA JAVASCRIPT -->
    <!-- ========================= -->

    <script>

        const eventosDesdePHP =
            <?= json_encode(
                $eventos,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT
            ) ?>;

    </script>


    <!-- JAVASCRIPT -->

    <script src="script.js"></script>

    <script src="cuenta.js"></script>


</body>

</html>
