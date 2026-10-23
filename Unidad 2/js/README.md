# Práctica: despliegue de información mediante JavaScript

Desarrollada a partir de `../Ejercicio_ JavaScript.docx`.

## Ejecutar

Abre `index.html` en un navegador. No necesita servidor, PHP ni dependencias.

## Archivos

- `index.html`: formulario con nombre, email y mensaje, y contenedor de resultados.
- `styles.css`: presentación adaptable a computadora y celular.
- `script.js`: captura de valores, validación, manipulación del DOM y evento `submit`.

## Comprobar la práctica

1. Completa los tres campos y presiona **Enviar mensaje**.
2. Comprueba que los datos aparecen debajo sin recargar la página.
3. Comprueba que se limpia el formulario y el cursor vuelve al nombre.
4. Agrega otro mensaje: ambos deben permanecer visibles y el contador debe indicar dos mensajes.
5. Intenta enviar campos vacíos, solo espacios o un email sin formato válido: el formulario debe solicitar la corrección sin agregar mensajes.
6. Escribe `<b>Hola</b>` en el mensaje: debe aparecer literalmente como texto.

Los mensajes se mantienen en el DOM durante la sesión de la página. Se borran al recargar y no se envían a un servidor.

Se corrigió el orden de las instrucciones del ejemplo del documento, en particular el evento `submit`, el agregado al contenedor y la limpieza del formulario. Los datos se insertan con `textContent` y nodos de texto.
