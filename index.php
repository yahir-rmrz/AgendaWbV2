<?php

require_once "conexion.php";


/* =========================================
   FUNCIONES DE AYUDA
   ========================================= */

// Limpia un texto antes de mostrarlo (evita XSS)
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}


// 'estudio' -> 'Escuela'
function nombreCategoria(string $clave): string
{
    $nombres = [
        'trabajo'  => 'Trabajo',
        'personal' => 'Personal',
        'estudio'  => 'Escuela',
        'ocio'     => 'Ocio',
    ];

    return $nombres[$clave] ?? $clave;
}


// '10:30:00' -> '10:30 AM'   |   '19:00:00' -> '7:00 PM'
function formatearHora(string $hora): string
{
    return date('g:i A', strtotime($hora));
}


/* =========================================
   TARJETA DE UN EVENTO
   Recibe un evento (arreglo asociativo)
   y devuelve el HTML de su tarjeta.
   ========================================= */

function mostrarEvento(array $ev): string
{
    $mesesCortos = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN',
                    'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

    $marca = strtotime($ev['fecha']);
    $dia   = (int) date('j', $marca);
    $mes   = $mesesCortos[(int) date('n', $marca) - 1];

    $id = (int) $ev['id'];   // el id SIEMPRE como número

    // data-mes (0-11) y data-anio los usa script.js para filtrar por mes
    $html  = '<article class="evento"'
           . ' data-mes="' . ((int) date('n', $marca) - 1) . '"'
           . ' data-anio="' . (int) date('Y', $marca) . '">';

    $html .= '<div class="fecha">'
           . '<span class="dia">' . $dia . '</span>'
           . '<span class="mes-corto">' . $mes . '</span>'
           . '</div>';

    $html .= '<div class="informacion">';

    if ($ev['hora']) {                       // la hora es opcional
        $html .= '<span class="hora">' . e(formatearHora($ev['hora'])) . '</span>';
    }

    $html .= '<h3>' . e($ev['titulo']) . '</h3>';

    if ($ev['descripcion']) {                // la descripción también
        $html .= '<p>' . e($ev['descripcion']) . '</p>';
    }

    $html .= '<div class="datos">'
           . '<span class="etiqueta ' . e($ev['categoria']) . '">'
           . e(nombreCategoria($ev['categoria']))
           . '</span></div>';

    $html .= '</div>';

    $html .= '<div class="acciones-evento">'
           . '<a href="editar.php?id=' . $id . '" class="boton-editar">Editar</a>'
           . '<form method="post" action="borrar.php">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="boton-borrar">Borrar</button>'
           . '</form></div>';

    return $html . '</article>';
}



/* =========================================
   OBTENER EVENTOS DE MYSQL
   ========================================= */

$sql = "
    SELECT
        id,
        titulo,
        fecha,
        hora,
        categoria,
        descripcion
    FROM eventos
    ORDER BY fecha ASC, hora ASC
";

$resultado = $conexion->query($sql);

$eventos = $resultado->fetch_all(MYSQLI_ASSOC);

$conexion->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fuentes -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap"
        rel="stylesheet">

    <!-- TEMA: se aplica antes de pintar para evitar parpadeo -->

    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem("agendaweb_tema"); } catch (e) {}
            if (tema !== "light" && tema !== "dark") {
                tema = window.matchMedia("(prefers-color-scheme: light)").matches
                    ? "light"
                    : "dark";
            }
            document.documentElement.setAttribute("data-theme", tema);
        })();
    </script>

    <!-- CSS -->

    <link rel="stylesheet" href="estilos.css">

    <title>AgendaWeb</title>

</head>


<body>


    <!-- ========================= -->
    <!-- ENCABEZADO -->
    <!-- ========================= -->

    <header>

        <div class="encabezado">

            <div>

                <h1>AgendaWeb</h1>

                <p>
                    Organiza tus eventos y actividades
                </p>

            </div>


            <div class="encabezado-acciones">

                <!-- INTERRUPTOR DE TEMA -->

                <button
                    class="selector-tema"
                    id="selectorTema"
                    type="button"
                    role="switch"
                    aria-checked="true"
                    aria-label="Tema oscuro"
                    title="Cambiar entre tema claro y oscuro">

                    <span class="selector-tema__icono selector-tema__icono--sol" aria-hidden="true">☀️</span>
                    <span class="selector-tema__icono selector-tema__icono--luna" aria-hidden="true">🌙</span>
                    <span class="selector-tema__perilla" aria-hidden="true"></span>

                </button>

                <button
                class="boton-agregar"
                type="button"
                onclick="mostrarFormulario()">

                + Agregar evento

            </button>

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

                <span class="icono-calendario" aria-hidden="true">
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

                <span class="indicador-meses" aria-hidden="true">
                    ▼
                </span>

            </button>

            <div class="meses-colapsable" id="listaMeses">

            <div class="meses-interior">

            <div class="meses">


                <!-- SEPTIEMBRE -->

                <div
                    class="mes seleccionado"
                    data-mes="8"
                    data-anio="2026"
                    onclick="seleccionarMes(8, 2026)">

                    <span>
                        SEP
                    </span>

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

                    <span>
                        OCT
                    </span>

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

                    <span>
                        NOV
                    </span>

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

                    <span>
                        DIC
                    </span>

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

                    <span>
                        ENE
                    </span>

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

                    <span>
                        FEB
                    </span>

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


            <!-- ========================= -->
            <!-- MENSAJES -->
            <!-- ========================= -->

            <?php if (isset($_GET["ok"]) && $_GET["ok"] === "1"): ?>

                <div class="mensaje-exito">
                    Evento guardado correctamente.
                </div>

            <?php endif; ?>


            <?php if (isset($_GET["error"])): ?>

                <div class="mensaje-error">
                    <?= htmlspecialchars(
                        $_GET["error"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </div>

            <?php endif; ?>


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

                        0 eventos

                    </span>


                </div>



                <!-- LISTA O ESTADO VACÍO, NUNCA LOS DOS -->

                <?php if (empty($eventos)): ?>

                    <div class="sin-eventos">
                        <h3>No tienes eventos registrados</h3>
                        <p>Pulsa «+ Agregar evento» para crear el primero.</p>
                    </div>

                <?php else: ?>

                    <div class="eventos" id="listaEventos">

                        <?php foreach ($eventos as $ev): ?>
                            <?= mostrarEvento($ev) ?>
                        <?php endforeach; ?>

                    </div>

                    <!-- Lo muestra script.js cuando el mes elegido no tiene eventos -->
                    <div class="sin-eventos" id="sinEventosMes" hidden>
                        <h3>Sin eventos este mes</h3>
                        <p>No hay eventos para este mes.</p>
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



                <!-- DÍAS GENERADOS POR JAVASCRIPT -->

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



                <!-- ========================= -->
                <!-- FORMULARIO PHP -->
                <!-- ========================= -->

                <form
                    class="formulario"
                    method="post"
                    action="registrar.php">


                    <!-- TÍTULO -->

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



                    <!-- FECHA Y HORA -->

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



                    <!-- CATEGORÍA -->

                    <div class="campo">

                        <label for="categoria">
                            Categoría
                        </label>


                        <select
                            id="categoria"
                            name="categoria"
                            required>

                            <option value="">
                                Selecciona una categoría
                            </option>

                            <option value="trabajo">
                                Trabajo
                            </option>

                            <option value="estudio">
                                Escuela
                            </option>

                            <option value="personal">
                                Personal
                            </option>

                            <option value="ocio">
                                Ocio
                            </option>

                        </select>

                    </div>



                    <!-- DESCRIPCIÓN -->

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



                    <!-- BOTONES -->

                    <div class="botones-formulario">


                        <!-- REGRESAR SIN GUARDAR -->

                        <button
                            class="boton-cancelar"
                            type="button"
                            onclick="ocultarFormulario()">

                            ← Regresar

                        </button>



                        <!-- GUARDAR -->

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



    <!-- EVENTOS DE MYSQL PARA JAVASCRIPT -->

    <script>

        const eventosDesdePHP =
            <?= json_encode(
                $eventos,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_HEX_TAG
            ) ?>;

    </script>


    <!-- JAVASCRIPT -->

    <script src="script.js"></script>


</body>

</html>