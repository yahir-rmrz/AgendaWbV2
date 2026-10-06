/* =========================================
   AGENDAWEB
   P4: PHP genera las tarjetas (foreach + mostrarEvento).
   JavaScript solo se encarga de:
   - filtrar las tarjetas por mes
   - dibujar el calendario y los contadores
   - mostrar / ocultar el formulario
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

    mesActual = mes;

    anioActual = anio;

    actualizarInterfaz();

}


function mesAnterior() {

    mesActual--;

    if (mesActual < 0) {

        mesActual = 11;

        anioActual--;

    }

    actualizarInterfaz();

}


function mesSiguiente() {

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

    document.getElementById("subtituloEventos").textContent =
        textoMes.toUpperCase();

}


/* =========================================
   MOSTRAR SOLO LAS TARJETAS DEL MES
   (las tarjetas ya existen: las hizo PHP)
   ========================================= */

function filtrarTarjetas() {

    const tarjetas = document.querySelectorAll("#listaEventos .evento");

    let visibles = 0;

    tarjetas.forEach(function (tarjeta) {

        const delMes =
            parseInt(tarjeta.dataset.mes) === mesActual &&
            parseInt(tarjeta.dataset.anio) === anioActual;

        tarjeta.hidden = !delMes;

        if (delMes) {

            visibles++;

        }

    });

    /* Aviso "sin eventos este mes" (solo existe si hay eventos en total) */

    const aviso = document.getElementById("sinEventosMes");

    if (aviso) {

        aviso.hidden = visibles > 0;

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
   INICIAR
   ========================================= */

actualizarInterfaz();