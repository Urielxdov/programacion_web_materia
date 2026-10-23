// Obtener los elementos que se utilizarán para capturar y mostrar información.
const formulario = document.getElementById("formulario");
const contenedor = document.getElementById("contenedorMensajes");
const nombre = document.getElementById("nombre");
const email = document.getElementById("email");
const mensaje = document.getElementById("mensaje");
const estadoVacio = document.getElementById("estadoVacio");
const contador = document.getElementById("contadorMensajes");
const estado = document.getElementById("estado");
let totalMensajes = 0;

// Evitar que un campo formado únicamente por espacios se considere válido.
for (const campo of [nombre, email, mensaje]) {
  campo.addEventListener("input", function () {
    campo.setCustomValidity("");
  });
}

formulario.addEventListener("submit", function (evento) {
  evento.preventDefault(); // Mostrar los datos sin recargar la página.

  for (const campo of [nombre, email, mensaje]) {
    campo.setCustomValidity(campo.value.trim() ? "" : "Completa este campo.");
  }
  if (!formulario.reportValidity()) return;

  const nuevoMensaje = document.createElement("div");
  nuevoMensaje.classList.add("mensaje");

  // Crear los elementos del mensaje. textContent muestra los datos como texto
  // y evita interpretar como HTML cualquier contenido escrito por el usuario.
  const datos = [
    ["Nombre", nombre.value.trim()],
    ["Email", email.value.trim()],
    ["Mensaje", mensaje.value.trim()],
  ];

  for (const [etiqueta, valor] of datos) {
    const parrafo = document.createElement("p");
    const titulo = document.createElement("strong");
    titulo.textContent = etiqueta + ": ";
    parrafo.append(titulo, document.createTextNode(valor));
    nuevoMensaje.appendChild(parrafo);
  }

  contenedor.appendChild(nuevoMensaje);
  totalMensajes += 1;
  contador.textContent = totalMensajes === 1 ? "1 mensaje" : totalMensajes + " mensajes";
  estadoVacio.hidden = true;
  estado.textContent = "Mensaje " + totalMensajes + " agregado correctamente.";

  formulario.reset();
  nombre.focus();
});
