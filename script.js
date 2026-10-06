/* =========================================
   AGENDAWEB
   Sistema de agenda
   ========================================= */


/* =========================================
   CONFIGURACIÓN
   ========================================= */

const CLAVE_EVENTOS = "agendaweb_eventos";


/*
   Mes que se está mostrando actualmente.

   8 = septiembre
   9 = octubre
   10 = noviembre
   11 = diciembre
   0 = enero
   1 = febrero
*/

let mesActual = 8;

let anioActual = 2026;


/*
   ID del evento que estamos editando.

   null = estamos creando un evento nuevo.
*/

let eventoEditando = null;


/*
   Lista de eventos.
*/

let eventos = [];



/* =========================================
   NOMBRES DE MESES
   ========================================= */

const nombresMeses = [

    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre"

];


const mesesCortos = [

    "ENE",
    "FEB",
    "MAR",
    "ABR",
    "MAY",
    "JUN",
    "JUL",
    "AGO",
    "SEP",
    "OCT",
    "NOV",
    "DIC"

];



/* =========================================
   EVENTOS INICIALES
   ========================================= */

const eventosIniciales = [

    {
        id: 1,
        titulo: "Reunión de proyecto",
        fecha: "2026-09-23",
        hora: "10:00",
        lugar: "Sala de juntas",
        categoria: "Trabajo",
        descripcion:
            "Revisión de avances y organización de actividades."
    },

    {
        id: 2,
        titulo: "Entrega de actividad",
        fecha: "2026-09-24",
        hora: "12:00",
        lugar: "Plataforma escolar",
        categoria: "Escuela",
        descripcion:
            "Entrega de proyecto de Desarrollo de Aplicaciones Web."
    },

    {
        id: 3,
        titulo: "Partida con amigos",
        fecha: "2026-09-26",
        hora: "19:00",
        lugar: "En línea",
        categoria: "Personal",
        descripcion:
            "Sesión de juego en línea con amigos."
    },

    {
        id: 4,
        titulo: "Planificación semanal",
        fecha: "2026-09-28",
        hora: "09:00",
        lugar: "Oficina",
        categoria: "Trabajo",
        descripcion:
            "Organización de pendientes y actividades de la semana."
    }

];



/* =========================================
   CARGAR EVENTOS
   ========================================= */

function cargarEventos() {

    const guardados =
        localStorage.getItem(CLAVE_EVENTOS);


    if (guardados) {

        eventos = JSON.parse(guardados);

    } else {

        eventos = eventosIniciales;

        guardarEventos();

    }


    actualizarInterfaz();

}



/* =========================================
   GUARDAR EVENTOS
   ========================================= */

function guardarEventos() {

    localStorage.setItem(
        CLAVE_EVENTOS,
        JSON.stringify(eventos)
    );

}



/* =========================================
   ACTUALIZAR TODA LA INTERFAZ
   ========================================= */

function actualizarInterfaz() {

    actualizarMesSeleccionado();

    actualizarTituloMes();

    mostrarEventos();

    generarCalendario();

    actualizarContadoresMeses();

}



/* =========================================
   ACTUALIZAR MES SELECCIONADO
   ========================================= */

function actualizarMesSeleccionado() {

    const meses =
        document.querySelectorAll(".mes");


    meses.forEach(function (elemento) {

        const mes =
            parseInt(elemento.dataset.mes);


        const anio =
            parseInt(elemento.dataset.anio);


        if (
            mes === mesActual &&
            anio === anioActual
        ) {

            elemento.classList.add(
                "seleccionado"
            );

        } else {

            elemento.classList.remove(
                "seleccionado"
            );

        }

    });

}



/* =========================================
   SELECCIONAR MES DESDE LA BARRA
   ========================================= */

function seleccionarMes(mes, anio) {

    mesActual = mes;

    anioActual = anio;

    actualizarInterfaz();

}



/* =========================================
   MES ANTERIOR
   ========================================= */

function mesAnterior() {

    mesActual--;


    if (mesActual < 0) {

        mesActual = 11;

        anioActual--;

    }


    actualizarInterfaz();

}



/* =========================================
   MES SIGUIENTE
   ========================================= */

function mesSiguiente() {

    mesActual++;


    if (mesActual > 11) {

        mesActual = 0;

        anioActual++;

    }


    actualizarInterfaz();

}



/* =========================================
   TÍTULO DEL MES
   ========================================= */

function actualizarTituloMes() {

    const nombre =
        nombresMeses[mesActual];


    const textoMes =
        `${nombre} ${anioActual}`;


    document.getElementById(
        "tituloCalendario"
    ).textContent = textoMes;


    document.getElementById(
        "subtituloEventos"
    ).textContent =
        textoMes.toUpperCase();

}



/* =========================================
   MOSTRAR EVENTOS DEL MES
   ========================================= */

function mostrarEventos() {

    const lista =
        document.getElementById("listaEventos");


    const contador =
        document.getElementById("contadorEventos");


    lista.innerHTML = "";


    /*
       Solo obtenemos los eventos
       del mes actualmente seleccionado.
    */

    const eventosDelMes =
        eventos.filter(function (evento) {

            const fecha =
                new Date(`${evento.fecha}T00:00:00`);


            return (

                fecha.getMonth() === mesActual &&

                fecha.getFullYear() === anioActual

            );

        });


    /*
       Ordenar por fecha y hora.
    */

    eventosDelMes.sort(function (a, b) {

        const fechaA =
            new Date(`${a.fecha}T${a.hora}`);


        const fechaB =
            new Date(`${b.fecha}T${b.hora}`);


        return fechaA - fechaB;

    });



    /* =====================================
       SI NO HAY EVENTOS
       ===================================== */

    if (eventosDelMes.length === 0) {

        lista.innerHTML = `

            <div class="sin-eventos">

                <h3>
                    No tienes eventos registrados
                </h3>

                <p>
                    No hay eventos para este mes.
                </p>

            </div>

        `;

    }



    /* =====================================
       CREAR TARJETAS
       ===================================== */

    eventosDelMes.forEach(function (evento) {

        const fecha =
            new Date(`${evento.fecha}T00:00:00`);


        const dia =
            fecha.getDate();


        const mes =
            mesesCortos[fecha.getMonth()];


        const hora =
            convertirHora(evento.hora);


        /*
           Clase de categoría.
        */

        let categoriaClase =
            evento.categoria
                .toLowerCase()
                .normalize("NFD")
                .replace(
                    /[\u0300-\u036f]/g,
                    ""
                );


        const tarjeta =
            document.createElement("article");


        tarjeta.className = "evento";


        tarjeta.innerHTML = `

            <div class="fecha">

                <span class="dia">
                    ${dia}
                </span>

                <span class="mes-corto">
                    ${mes}
                </span>

            </div>


            <div class="informacion">

                <span class="hora">
                    ${hora}
                </span>


                <h3>
                    ${evento.titulo}
                </h3>


                <p>
                    ${evento.descripcion}
                </p>


                <div class="datos">

                    <span>
                        ● ${evento.lugar}
                    </span>


                    <span class="etiqueta ${categoriaClase}">
                        ${evento.categoria}
                    </span>

                </div>

            </div>


            <div class="acciones-evento">

                <button
                    type="button"
                    class="boton-editar"
                    onclick="editarEvento(${evento.id})">

                    Editar

                </button>


                <button
                    type="button"
                    class="boton-borrar"
                    onclick="borrarEvento(${evento.id})">

                    Borrar

                </button>

            </div>

        `;


        lista.appendChild(tarjeta);

    });


    /*
       Actualizar contador.
    */

    const cantidad =
        eventosDelMes.length;


    contador.textContent =
        `${cantidad} ${
            cantidad === 1
                ? "evento"
                : "eventos"
        }`;

}



/* =========================================
   CONVERTIR HORA
   ========================================= */

function convertirHora(hora) {

    const partes =
        hora.split(":");


    let horas =
        parseInt(partes[0]);


    const minutos =
        partes[1];


    let periodo = "AM";


    if (horas >= 12) {

        periodo = "PM";

    }


    if (horas === 0) {

        horas = 12;

    }

    else if (horas > 12) {

        horas -= 12;

    }


    return `${horas}:${minutos} ${periodo}`;

}



/* =========================================
   GENERAR CALENDARIO
   ========================================= */

function generarCalendario() {

    const calendario =
        document.getElementById(
            "diasCalendario"
        );


    calendario.innerHTML = "";


    /*
       Primer día del mes.

       JavaScript:
       Domingo = 0
       Lunes = 1
       ...
    */

    const primerDia =
        new Date(
            anioActual,
            mesActual,
            1
        );


    let posicionPrimerDia =
        primerDia.getDay();


    /*
       Convertimos para que:
       Lunes = 0
       Martes = 1
       ...
       Domingo = 6
    */

    posicionPrimerDia =
        posicionPrimerDia === 0
            ? 6
            : posicionPrimerDia - 1;


    /*
       Cantidad de días del mes.
    */

    const cantidadDias =
        new Date(
            anioActual,
            mesActual + 1,
            0
        ).getDate();



    /*
       Espacios antes del primer día.
    */

    for (
        let i = 0;
        i < posicionPrimerDia;
        i++
    ) {

        const vacio =
            document.createElement("div");


        vacio.className =
            "dia-vacio";


        calendario.appendChild(vacio);

    }



    /*
       Crear cada día.
    */

    for (
        let dia = 1;
        dia <= cantidadDias;
        dia++
    ) {

        const elemento =
            document.createElement("div");


        elemento.textContent =
            dia;


        /*
           Comprobar si hoy es este día.
        */

        const hoy =
            new Date();


        if (

            hoy.getFullYear() === anioActual &&

            hoy.getMonth() === mesActual &&

            hoy.getDate() === dia

        ) {

            elemento.classList.add(
                "hoy"
            );

        }



        /*
           Comprobar si existe un evento.
        */

        const tieneEvento =
            eventos.some(function (evento) {

                const fecha =
                    new Date(
                        `${evento.fecha}T00:00:00`
                    );


                return (

                    fecha.getFullYear() === anioActual &&

                    fecha.getMonth() === mesActual &&

                    fecha.getDate() === dia

                );

            });


        if (tieneEvento) {

            elemento.classList.add(
                "evento-dia"
            );


            const punto =
                document.createElement("span");


            punto.className =
                "punto-evento";


            elemento.appendChild(punto);

        }


        calendario.appendChild(elemento);

    }

}



/* =========================================
   CONTADORES DE LA BARRA LATERAL
   ========================================= */

function actualizarContadoresMeses() {

    const elementos =
        document.querySelectorAll(
            ".mes[data-mes]"
        );


    elementos.forEach(function (elemento) {

        const mes =
            parseInt(elemento.dataset.mes);


        const anio =
            parseInt(elemento.dataset.anio);


        const cantidad =
            eventos.filter(function (evento) {

                const fecha =
                    new Date(
                        `${evento.fecha}T00:00:00`
                    );


                return (

                    fecha.getMonth() === mes &&

                    fecha.getFullYear() === anio

                );

            }).length;


        const contador =
            elemento.querySelector("small");


        contador.textContent =
            `${cantidad} ${
                cantidad === 1
                    ? "evento"
                    : "eventos"
            }`;

    });

}



/* =========================================
   MOSTRAR FORMULARIO
   ========================================= */

function mostrarFormulario() {

    /*
       Siempre comenzamos con un formulario
       completamente limpio.
    */

    eventoEditando = null;

    limpiarFormulario();


    document.getElementById(
        "tituloFormulario"
    ).textContent =
        "Agregar evento";


    document.getElementById(
        "descripcionFormulario"
    ).textContent =
        "Completa la información de tu nuevo evento.";


    document.getElementById(
        "botonGuardar"
    ).textContent =
        "Crear evento";


    document.getElementById(
        "seccionEventos"
    ).style.display =
        "none";


    document.getElementById(
        "calendario"
    ).style.display =
        "none";


    document.getElementById(
        "formularioEvento"
    ).style.display =
        "block";

}



/* =========================================
   REGRESAR DEL FORMULARIO
   ========================================= */

function ocultarFormulario() {

    /*
       MUY IMPORTANTE:

       Aquí NO guardamos nada.

       Simplemente abandonamos el formulario.
    */


    eventoEditando = null;


    /*
       Vaciar todas las cajas.
    */

    limpiarFormulario();


    /*
       Ocultar formulario.
    */

    document.getElementById(
        "formularioEvento"
    ).style.display =
        "none";


    /*
       Volver a mostrar eventos.
    */

    document.getElementById(
        "seccionEventos"
    ).style.display =
        "block";


    /*
       Volver a mostrar calendario.
    */

    document.getElementById(
        "calendario"
    ).style.display =
        "block";

}



/* =========================================
   LIMPIAR FORMULARIO
   ========================================= */

function limpiarFormulario() {

    document.getElementById(
        "titulo"
    ).value = "";


    document.getElementById(
        "fecha"
    ).value = "";


    document.getElementById(
        "hora"
    ).value = "";


    document.getElementById(
        "lugar"
    ).value = "";


    document.getElementById(
        "categoria"
    ).value = "";


    document.getElementById(
        "descripcion"
    ).value = "";

}



/* =========================================
   GUARDAR EVENTO
   ========================================= */

function guardarEvento() {

    /*
       Obtener valores.
    */

    const titulo =
        document.getElementById(
            "titulo"
        ).value.trim();


    const fecha =
        document.getElementById(
            "fecha"
        ).value;


    const hora =
        document.getElementById(
            "hora"
        ).value;


    const lugar =
        document.getElementById(
            "lugar"
        ).value.trim();


    const categoria =
        document.getElementById(
            "categoria"
        ).value;


    const descripcion =
        document.getElementById(
            "descripcion"
        ).value.trim();



    /*
       Validar.
    */

    if (

        !titulo ||
        !fecha ||
        !hora ||
        !lugar ||
        !categoria ||
        !descripcion

    ) {

        alert(
            "Completa todos los campos antes de guardar el evento."
        );

        return;

    }



    /* =====================================
       EDITAR EVENTO
       ===================================== */

    if (eventoEditando !== null) {

        const evento =
            eventos.find(function (evento) {

                return evento.id === eventoEditando;

            });


        if (evento) {

            evento.titulo =
                titulo;

            evento.fecha =
                fecha;

            evento.hora =
                hora;

            evento.lugar =
                lugar;

            evento.categoria =
                categoria;

            evento.descripcion =
                descripcion;

        }

    }



    /* =====================================
       CREAR EVENTO
       ===================================== */

    else {

        const nuevoEvento = {

            id: Date.now(),

            titulo: titulo,

            fecha: fecha,

            hora: hora,

            lugar: lugar,

            categoria: categoria,

            descripcion: descripcion

        };


        eventos.push(
            nuevoEvento
        );

    }



    /*
       Guardar.
    */

    guardarEventos();


    /*
       Actualizar interfaz.
    */

    actualizarInterfaz();


    /*
       Si el evento fue creado o editado
       desde otro mes, mostramos el mes
       donde quedó guardado.
    */

    const fechaEvento =
        new Date(
            `${fecha}T00:00:00`
        );


    mesActual =
        fechaEvento.getMonth();


    anioActual =
        fechaEvento.getFullYear();


    actualizarInterfaz();


    /*
       Regresar.
    */

    ocultarFormulario();

}



/* =========================================
   EDITAR EVENTO
   ========================================= */

function editarEvento(id) {

    const evento =
        eventos.find(function (evento) {

            return evento.id === id;

        });


    if (!evento) {

        return;

    }


    /*
       Guardar ID.
    */

    eventoEditando = id;


    /*
       Cargar datos.
    */

    document.getElementById(
        "titulo"
    ).value =
        evento.titulo;


    document.getElementById(
        "fecha"
    ).value =
        evento.fecha;


    document.getElementById(
        "hora"
    ).value =
        evento.hora;


    document.getElementById(
        "lugar"
    ).value =
        evento.lugar;


    document.getElementById(
        "categoria"
    ).value =
        evento.categoria;


    document.getElementById(
        "descripcion"
    ).value =
        evento.descripcion;


    /*
       Cambiar textos.
    */

    document.getElementById(
        "tituloFormulario"
    ).textContent =
        "Editar evento";


    document.getElementById(
        "descripcionFormulario"
    ).textContent =
        "Modifica la información de tu evento.";


    document.getElementById(
        "botonGuardar"
    ).textContent =
        "Guardar cambios";


    /*
       Mostrar formulario.
    */

    document.getElementById(
        "seccionEventos"
    ).style.display =
        "none";


    document.getElementById(
        "calendario"
    ).style.display =
        "none";


    document.getElementById(
        "formularioEvento"
    ).style.display =
        "block";

}



/* =========================================
   BORRAR EVENTO
   ========================================= */

function borrarEvento(id) {

    const evento =
        eventos.find(function (evento) {

            return evento.id === id;

        });


    if (!evento) {

        return;

    }


    const confirmar =
        confirm(
            `¿Quieres borrar el evento "${evento.titulo}"?`
        );


    if (!confirmar) {

        return;

    }


    /*
       Eliminar.
    */

    eventos =
        eventos.filter(function (evento) {

            return evento.id !== id;

        });


    /*
       Guardar.
    */

    guardarEventos();


    /*
       Actualizar.
    */

    actualizarInterfaz();

}



/* =========================================
   INICIAR AGENDAWEB
   ========================================= */

cargarEventos();