/* =========================================
   AGENDAWEB
   P4: PHP genera las tarjetas (foreach + mostrarEvento).
   JavaScript solo se encarga de:
   - filtrar las tarjetas por mes
   - dibujar el calendario y los contadores
   - mostrar / ocultar el formulario
   - buscar eventos por título con autocompletado
   ========================================= */


/* Mes que se está mostrando (0 = enero ... 11 = diciembre) */

let mesActual = 8;      // septiembre

let anioActual = 2026;


/*
   Si hoy cae dentro de los meses de la barra lateral
   (sep 2026 - feb 2027), empezamos en el mes actual.
*/

(function () {

    const hoy = new Date();

    const indice = (hoy.getFullYear() - 2026) * 12 + hoy.getMonth();

    if (indice >= 8 && indice <= 13) {

        mesActual = hoy.getMonth();

        anioActual = hoy.getFullYear();

    }

})();


/*
   Eventos que PHP imprimió en index.php
   (vienen de MySQL con json_encode).
*/

const eventos =
    typeof eventosDesdePHP !== "undefined"
        ? eventosDesdePHP
        : [];


const nombresMeses = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];


/* Texto "1 evento" / "3 eventos" */

function textoEventos(cantidad) {

    return `${cantidad} ${cantidad === 1 ? "evento" : "eventos"}`;

}


/* "2026-10-08" -> { anio: 2026, mes: 9, dia: 8 } */

function partesFecha(fecha) {

    const [anio, mes, dia] = fecha.split("-").map(Number);

    return { anio: anio, mes: mes - 1, dia: dia };

}


/* =========================================
   ACTUALIZAR TODA LA INTERFAZ
   ========================================= */

function actualizarInterfaz() {

    actualizarMesSeleccionado();

    actualizarTituloMes();

    filtrarTarjetas();

    generarCalendario();

    actualizarContadoresMeses();

}


/* =========================================
   MES SELECCIONADO EN LA BARRA LATERAL
   ========================================= */

function actualizarMesSeleccionado() {

    document.querySelectorAll(".mes").forEach(function (elemento) {

        const esActual =
            parseInt(elemento.dataset.mes) === mesActual &&
            parseInt(elemento.dataset.anio) === anioActual;

        elemento.classList.toggle("seleccionado", esActual);

    });

}


function seleccionarMes(mes, anio) {

    /* Elegir un mes es volver al filtro por mes: se borra la búsqueda */

    limpiarBusqueda();

    mesActual = mes;

    anioActual = anio;

    actualizarInterfaz();

}


function mesAnterior() {

    limpiarBusqueda();

    mesActual--;

    if (mesActual < 0) {

        mesActual = 11;

        anioActual--;

    }

    actualizarInterfaz();

}


function mesSiguiente() {

    limpiarBusqueda();

    mesActual++;

    if (mesActual > 11) {

        mesActual = 0;

        anioActual++;

    }

    actualizarInterfaz();

}


function actualizarTituloMes() {

    const textoMes = `${nombresMeses[mesActual]} ${anioActual}`;

    document.getElementById("tituloCalendario").textContent = textoMes;

    /* Con una búsqueda activa la lista ya no es de un solo mes */

    document.getElementById("subtituloEventos").textContent =
        busquedaActiva()
            ? "RESULTADOS DE BÚSQUEDA"
            : textoMes.toUpperCase();

}


/* =========================================
   MOSTRAR SOLO LAS TARJETAS DEL MES
   (las tarjetas ya existen: las hizo PHP)
   ========================================= */

function filtrarTarjetas() {

    const tarjetas = document.querySelectorAll("#listaEventos .evento");

    let visibles = 0;

    const buscando = busquedaActiva();

    tarjetas.forEach(function (tarjeta) {

        const delMes =
            parseInt(tarjeta.dataset.mes) === mesActual &&
            parseInt(tarjeta.dataset.anio) === anioActual;

        /*
           Sin búsqueda: se filtra por mes (comportamiento original).
           Con búsqueda: manda la búsqueda y se revisan todos los meses.
        */

        let visible = delMes;

        if (idEventoSeleccionado !== null) {

            visible = tarjeta.dataset.id === idEventoSeleccionado;

        } else if (terminoBusqueda !== "") {

            visible =
                normalizarTexto(tarjeta.dataset.titulo)
                    .includes(terminoBusqueda);

        }

        tarjeta.hidden = !visible;

        tarjeta.classList.toggle(
            "evento-resaltado",
            idEventoSeleccionado !== null && visible
        );

        if (visible) {

            visibles++;

        }

    });

    /* Aviso "sin eventos este mes" (solo existe si hay eventos en total) */

    const aviso = document.getElementById("sinEventosMes");

    if (aviso) {

        aviso.hidden = buscando || visibles > 0;

    }

    /* Aviso "no se encontraron resultados" de la búsqueda */

    const avisoBusqueda = document.getElementById("sinResultadosBusqueda");

    if (avisoBusqueda) {

        avisoBusqueda.hidden = !buscando || visibles > 0;

    }

    document.getElementById("contadorEventos").textContent =
        textoEventos(visibles);

}


/* =========================================
   CALENDARIO
   ========================================= */

function generarCalendario() {

    const calendario = document.getElementById("diasCalendario");

    calendario.innerHTML = "";

    /* Lunes = 0 ... Domingo = 6 */

    let posicionPrimerDia = new Date(anioActual, mesActual, 1).getDay();

    posicionPrimerDia =
        posicionPrimerDia === 0 ? 6 : posicionPrimerDia - 1;

    const cantidadDias = new Date(anioActual, mesActual + 1, 0).getDate();

    for (let i = 0; i < posicionPrimerDia; i++) {

        const vacio = document.createElement("div");

        vacio.className = "dia-vacio";

        calendario.appendChild(vacio);

    }

    const hoy = new Date();

    for (let dia = 1; dia <= cantidadDias; dia++) {

        const elemento = document.createElement("div");

        elemento.textContent = dia;

        if (
            hoy.getFullYear() === anioActual &&
            hoy.getMonth() === mesActual &&
            hoy.getDate() === dia
        ) {

            elemento.classList.add("hoy");

        }

        const tieneEvento = eventos.some(function (evento) {

            const f = partesFecha(evento.fecha);

            return (
                f.anio === anioActual &&
                f.mes === mesActual &&
                f.dia === dia
            );

        });

        if (tieneEvento) {

            elemento.classList.add("evento-dia");

            const punto = document.createElement("span");

            punto.className = "punto-evento";

            elemento.appendChild(punto);

        }

        calendario.appendChild(elemento);

    }

}


/* =========================================
   CONTADORES DE LA BARRA LATERAL
   ========================================= */

function actualizarContadoresMeses() {

    document.querySelectorAll(".mes[data-mes]").forEach(function (elemento) {

        const mes = parseInt(elemento.dataset.mes);

        const anio = parseInt(elemento.dataset.anio);

        const cantidad = eventos.filter(function (evento) {

            const f = partesFecha(evento.fecha);

            return f.mes === mes && f.anio === anio;

        }).length;

        elemento.querySelector("small").textContent =
            textoEventos(cantidad);

    });

}


/* =========================================
   FORMULARIO (mostrar / ocultar)
   El guardado lo hace registrar.php
   ========================================= */

function mostrarFormulario() {

    document.getElementById("seccionEventos").style.display = "none";

    document.getElementById("calendario").style.display = "none";

    document.getElementById("formularioEvento").style.display = "block";

}


function ocultarFormulario() {

    document.getElementById("formularioEvento").style.display = "none";

    document.getElementById("seccionEventos").style.display = "block";

    document.getElementById("calendario").style.display = "block";

}


/* =========================================
   TEMA CLARO / OSCURO
   (index.php ya puso data-theme antes de pintar)
   ========================================= */

const CLAVE_TEMA = "agendaweb_tema";

const selectorTema = document.getElementById("selectorTema");


function temaActual() {

    return document.documentElement.getAttribute("data-theme") === "light"
        ? "light"
        : "dark";

}


function marcarSelector() {

    const oscuro = temaActual() === "dark";

    selectorTema.setAttribute("aria-checked", String(oscuro));

    selectorTema.setAttribute(
        "aria-label",
        oscuro ? "Tema oscuro activado" : "Tema claro activado"
    );

}


function cambiarTema() {

    const raiz = document.documentElement;

    const nuevo = temaActual() === "dark" ? "light" : "dark";

    /* La transición solo existe durante el cambio */

    raiz.classList.add("tema-transicion");

    raiz.setAttribute("data-theme", nuevo);

    try {

        localStorage.setItem(CLAVE_TEMA, nuevo);

    } catch (error) { /* sin almacenamiento: el tema sigue funcionando */ }

    marcarSelector();

    setTimeout(function () {

        raiz.classList.remove("tema-transicion");

    }, 400);

}


selectorTema.addEventListener("click", cambiarTema);

marcarSelector();


/* Si el usuario nunca eligió, seguir al sistema operativo en vivo */

window
    .matchMedia("(prefers-color-scheme: light)")
    .addEventListener("change", function (evento) {

        let guardado = null;

        try { guardado = localStorage.getItem(CLAVE_TEMA); } catch (error) {}

        if (guardado === "light" || guardado === "dark") {

            return;

        }

        document.documentElement.setAttribute(
            "data-theme",
            evento.matches ? "light" : "dark"
        );

        marcarSelector();

    });


/* =========================================
   MENÚ DE MESES DESPLEGABLE
   ========================================= */

const botonMeses = document.getElementById("botonMeses");

const barraLateral = document.querySelector(".barra-lateral");


botonMeses.addEventListener("click", function () {

    const cerrado = barraLateral.classList.toggle("meses-cerrados");

    botonMeses.setAttribute("aria-expanded", String(!cerrado));

});


/* =========================================
   BÚSQUEDA DE EVENTOS + AUTOCOMPLETADO
   Usa el arreglo "eventos" que PHP ya imprimió
   (solo del usuario con sesión iniciada), así que
   no hace ninguna consulta extra a la base de datos.
   ========================================= */

const campoBusqueda = document.getElementById("buscadorEventos");

const listaSugerencias = document.getElementById("sugerenciasEventos");

const botonLimpiarBusqueda = document.getElementById("buscadorLimpiar");


/* Texto buscado (ya normalizado) */

let terminoBusqueda = "";

/* id (texto) del evento elegido en las sugerencias, o null */

let idEventoSeleccionado = null;

/* Posición resaltada con el teclado dentro de las sugerencias */

let sugerenciaActiva = -1;


/* Sin mayúsculas, sin acentos y sin espacios en los extremos */

function normalizarTexto(texto) {

    return String(texto === null || texto === undefined ? "" : texto)
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .toLowerCase()
        .trim();

}


function busquedaActiva() {

    return idEventoSeleccionado !== null || terminoBusqueda !== "";

}


/* Eventos cuyo título contiene el texto (los que empiezan igual, primero) */

function buscarCoincidencias(termino) {

    const coincidencias = eventos.filter(function (evento) {

        return normalizarTexto(evento.titulo).includes(termino);

    });

    /* sort es estable: dentro de cada grupo se conserva el orden por fecha */

    return coincidencias.sort(function (a, b) {

        const aEmpieza = normalizarTexto(a.titulo).startsWith(termino);

        const bEmpieza = normalizarTexto(b.titulo).startsWith(termino);

        return Number(bEmpieza) - Number(aEmpieza);

    });

}


/* "2026-10-08" -> "08/10/2026" */

function fechaCorta(fecha) {

    const f = partesFecha(fecha);

    return (
        String(f.dia).padStart(2, "0") + "/" +
        String(f.mes + 1).padStart(2, "0") + "/" +
        f.anio
    );

}


/* Título con la parte coincidente en negritas (sin innerHTML: es seguro) */

function crearTituloSugerencia(titulo, termino) {

    const contenedor = document.createElement("span");

    contenedor.className = "sugerencia__titulo";

    const normalizado = normalizarTexto(titulo);

    /* Si la normalización cambió la longitud, no se puede resaltar */

    const inicio =
        normalizado.length === titulo.length
            ? normalizado.indexOf(termino)
            : -1;

    if (inicio === -1) {

        contenedor.textContent = titulo;

        return contenedor;

    }

    const fin = inicio + termino.length;

    contenedor.appendChild(
        document.createTextNode(titulo.slice(0, inicio))
    );

    const negrita = document.createElement("strong");

    negrita.textContent = titulo.slice(inicio, fin);

    contenedor.appendChild(negrita);

    contenedor.appendChild(
        document.createTextNode(titulo.slice(fin))
    );

    return contenedor;

}


function mostrarSugerencias() {

    listaSugerencias.innerHTML = "";

    sugerenciaActiva = -1;

    if (terminoBusqueda === "" || idEventoSeleccionado !== null) {

        cerrarSugerencias();

        return;

    }

    const coincidencias = buscarCoincidencias(terminoBusqueda);

    if (coincidencias.length === 0) {

        const vacio = document.createElement("li");

        vacio.className = "sugerencia sugerencia--vacia";

        vacio.textContent = "No se encontraron resultados";

        listaSugerencias.appendChild(vacio);

    }

    coincidencias.forEach(function (evento, posicion) {

        const item = document.createElement("li");

        item.className = "sugerencia";

        item.id = "sugerenciaEvento" + posicion;

        item.setAttribute("role", "option");

        item.dataset.id = String(evento.id);

        item.appendChild(
            crearTituloSugerencia(evento.titulo, terminoBusqueda)
        );

        const fecha = document.createElement("span");

        fecha.className = "sugerencia__fecha";

        fecha.textContent = fechaCorta(evento.fecha);

        item.appendChild(fecha);

        listaSugerencias.appendChild(item);

    });

    listaSugerencias.hidden = false;

    campoBusqueda.setAttribute("aria-expanded", "true");

}


function cerrarSugerencias() {

    listaSugerencias.hidden = true;

    sugerenciaActiva = -1;

    campoBusqueda.setAttribute("aria-expanded", "false");

    campoBusqueda.removeAttribute("aria-activedescendant");

}


/* Mueve el resaltado del teclado entre las sugerencias */

function moverSugerencia(paso) {

    const opciones = listaSugerencias.querySelectorAll(
        ".sugerencia[role='option']"
    );

    if (opciones.length === 0) {

        return;

    }

    if (sugerenciaActiva >= 0) {

        opciones[sugerenciaActiva].classList.remove("activa");

        opciones[sugerenciaActiva].removeAttribute("aria-selected");

    }

    sugerenciaActiva =
        (sugerenciaActiva + paso + opciones.length) % opciones.length;

    const actual = opciones[sugerenciaActiva];

    actual.classList.add("activa");

    actual.setAttribute("aria-selected", "true");

    campoBusqueda.setAttribute("aria-activedescendant", actual.id);

    actual.scrollIntoView({ block: "nearest" });

}


/* El usuario eligió una sugerencia: se muestra ese evento */

function seleccionarSugerencia(id) {

    const evento = eventos.find(function (e) {

        return String(e.id) === String(id);

    });

    if (!evento) {

        return;

    }

    idEventoSeleccionado = String(evento.id);

    terminoBusqueda = normalizarTexto(evento.titulo);

    campoBusqueda.value = evento.titulo;

    botonLimpiarBusqueda.hidden = false;

    cerrarSugerencias();

    /* El calendario y la barra lateral saltan al mes del evento */

    const f = partesFecha(evento.fecha);

    mesActual = f.mes;

    anioActual = f.anio;

    actualizarInterfaz();

    const tarjeta = document.querySelector(
        '#listaEventos .evento[data-id="' + idEventoSeleccionado + '"]'
    );

    if (tarjeta) {

        tarjeta.scrollIntoView({ behavior: "smooth", block: "nearest" });

    }

}


/* Borra la búsqueda. No redibuja: quien la llama decide cuándo */

function limpiarBusqueda() {

    terminoBusqueda = "";

    idEventoSeleccionado = null;

    if (!campoBusqueda) {

        return;

    }

    campoBusqueda.value = "";

    botonLimpiarBusqueda.hidden = true;

    listaSugerencias.innerHTML = "";

    cerrarSugerencias();

}


/* Solo se activa si index.php dibujó la barra (hay eventos) */

if (campoBusqueda) {

    /* Al escribir: sugerencias y lista se actualizan en tiempo real */

    campoBusqueda.addEventListener("input", function () {

        idEventoSeleccionado = null;

        terminoBusqueda = normalizarTexto(campoBusqueda.value);

        botonLimpiarBusqueda.hidden = campoBusqueda.value === "";

        mostrarSugerencias();

        actualizarTituloMes();

        filtrarTarjetas();

    });


    campoBusqueda.addEventListener("focus", function () {

        if (terminoBusqueda !== "" && idEventoSeleccionado === null) {

            mostrarSugerencias();

        }

    });


    campoBusqueda.addEventListener("keydown", function (evento) {

        if (evento.key === "ArrowDown") {

            evento.preventDefault();

            if (listaSugerencias.hidden) {

                mostrarSugerencias();

            }

            moverSugerencia(1);

        } else if (evento.key === "ArrowUp") {

            evento.preventDefault();

            moverSugerencia(-1);

        } else if (evento.key === "Enter") {

            evento.preventDefault();

            const activa = listaSugerencias.querySelector(
                ".sugerencia.activa"
            );

            if (activa) {

                seleccionarSugerencia(activa.dataset.id);

            } else {

                cerrarSugerencias();

            }

        } else if (evento.key === "Escape") {

            cerrarSugerencias();

        }

    });


    /* mousedown (y no click) evita que el campo pierda el foco antes */

    listaSugerencias.addEventListener("mousedown", function (evento) {

        evento.preventDefault();

        const item = evento.target.closest(".sugerencia[role='option']");

        if (item) {

            seleccionarSugerencia(item.dataset.id);

        }

    });


    botonLimpiarBusqueda.addEventListener("click", function () {

        limpiarBusqueda();

        actualizarInterfaz();

        campoBusqueda.focus();

    });


    /* Clic fuera de la barra: se cierran las sugerencias */

    document.addEventListener("mousedown", function (evento) {

        if (!evento.target.closest("#buscador")) {

            cerrarSugerencias();

        }

    });

}


/* =========================================
   INICIAR
   ========================================= */

actualizarInterfaz();