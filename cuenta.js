
/* Menú desplegable del icono de cuenta */

(function () {

    const menu  = document.getElementById("menuCuenta");
    const boton = document.getElementById("botonCuenta");
    const panel = document.getElementById("panelCuenta");

    if (!menu || !boton || !panel) {
        return;
    }

    // Mostrar el panel de cuenta automáticamente al cargar la página.
    panel.hidden = false;
    boton.setAttribute("aria-expanded", "true");

    function cerrar() {
        panel.hidden = true;
        boton.setAttribute("aria-expanded", "false");
    }

    boton.addEventListener("click", function () {
        const abrir = panel.hidden;
        panel.hidden = !abrir;
        boton.setAttribute("aria-expanded", String(abrir));
    });

    document.addEventListener("click", function (evento) {
        if (!menu.contains(evento.target)) {
            cerrar();
        }
    });

    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape") {
            cerrar();
            boton.focus();
        }
    });

})();
