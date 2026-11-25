// Obtener elementos del DOM
const formulario = document.getElementById("formulario");
const contenedor = document.getElementById("contenedorMensajes");

// Crea un párrafo "Etiqueta: valor" usando textContent (evita inyección de HTML)
function crearLinea(etiqueta, valor) {
    const p = document.createElement("p");
    const strong = document.createElement("strong");
    strong.textContent = etiqueta + ":";
    p.appendChild(strong);
    p.appendChild(document.createTextNode(" " + valor));
    return p;
}

// Evento submit
formulario.addEventListener("submit", function (e) {
    e.preventDefault(); // Evita recargar la página

    // Obtener valores
    const nombre = document.getElementById("nombre").value;
    const email = document.getElementById("email").value;
    const mensaje = document.getElementById("mensaje").value;

    // Crear elemento HTML
    const nuevoMensaje = document.createElement("div");
    nuevoMensaje.classList.add("mensaje");
    nuevoMensaje.appendChild(crearLinea("Nombre", nombre));
    nuevoMensaje.appendChild(crearLinea("Email", email));
    nuevoMensaje.appendChild(crearLinea("Mensaje", mensaje));

    // Agregar al contenedor
    contenedor.appendChild(nuevoMensaje);

    // Limpiar formulario
    formulario.reset();
});
