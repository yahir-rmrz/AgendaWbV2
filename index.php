<?php

require_once "conexion.php";


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


            <button
                class="boton-agregar"
                type="button"
                onclick="mostrarFormulario()">

                + Agregar evento

            </button>

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


            <div class="titulo-meses">

                <span class="icono-calendario">
                    ▣
                </span>


                <div>

                    <h2>
                        Mis meses
                    </h2>

                    <p>
                        2026 - 2027
                    </p>

                </div>

            </div>



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



                <!-- AQUÍ SE GENERAN LOS EVENTOS -->

                <div
                    class="eventos"
                    id="listaEventos">

                </div>


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
                JSON_UNESCAPED_SLASHES
            ) ?>;

    </script>


    <!-- JAVASCRIPT -->

    <script src="script.js"></script>


</body>

</html>