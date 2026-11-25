# Cumplimiento de la rúbrica

| # | Criterio (peso) | Nivel Excelente pide | Evidencia en el proyecto |
|---|---|---|---|
| 1 | Maquetación (10 %) | Diseño responsivo y distribución clara | Tailwind *mobile-first*; layouts público y privado; barra lateral fija en escritorio y menú deslizable en móvil; tablas que se convierten en tarjetas en celular; formularios en una o dos columnas según el ancho. |
| 2 | Aspecto visual (10 %) | Imágenes, colores y tipografía combinados y coherentes | Design tokens (paleta verde "hoja" y neutros cálidos) en `index.css`; una sola tipografía (Atkinson Hyperlegible); ilustraciones SVG propias; íconos lucide; insignias que combinan color, ícono y texto; los mismos componentes en todas las páginas. |
| 3 | Navegabilidad y seguridad (10 %) | HTTPS, sesiones, control de acceso, autenticación, navegación excelente | HTTPS en Render; sesiones en servidor; guards de sesión, rol y CSRF; menús consistentes, migas de pan, títulos de pestaña por página, enlace para saltar al contenido, página 404, mensajes claros de éxito y error. Ver [seguridad.md](seguridad.md). |
| 4 | Manejo del DOM (10 %) | Dominio de JavaScript para manipular el DOM | Ver la tabla de abajo. |
| 5 | Arquitectura y diseño (15 %) | Capas, MVC, 2 o más patrones, UML | Capas y MVC, patrones **Repository, Strategy, Factory y Observer**, y diagramas de casos de uso, clases, secuencia, estados y despliegue en [arquitectura.md](arquitectura.md). |
| 6 | Orientación a objetos (15 %) | Clases, encapsulamiento, responsabilidades, relaciones | Entidades con reglas de dominio y métodos privados, herencia (`EntidadBase`, `RepositorioBase<T>`, `EstrategiaClasificacion`, `PerfilEquipo`, `EventoEquipo`), polimorfismo y relaciones 1:N, N:M y composición. |
| 7 | Interoperabilidad (5 %) | 2 o más servicios diseñados, implementados y probados | API de equipos y API de indicadores (más campañas, catálogos, usuarios y auth), documentadas en Swagger y con 20 pruebas e2e. Ver [api.md](api.md). |
| 8 | Persistencia (15 %) | 5 o más tablas relacionadas, integridad, CRUD | 10 tablas en PostgreSQL con FK, `UNIQUE`, `CHECK`, enums, índices, transacciones y migraciones. Ver [modelo-datos.md](modelo-datos.md). |
| 9 | Despliegue (10 %) | Servidor externo funcionando | Render + Neon con `render.yaml`; migraciones automáticas al arrancar. |

## Manipulación del DOM (criterio 4)

React actualiza el DOM a partir del estado. Además, el proyecto manipula el DOM directamente con JavaScript en estos
puntos:

| Técnica | Archivo |
|---|---|
| Cambiar el tamaño de letra raíz (`document.documentElement.style.fontSize`) y el atributo `data-contraste` | `stores/ui.store.ts` |
| Validación en vivo con mensajes junto a cada campo, `aria-invalid` y `aria-live`; al enviar, `focus()` y `scrollIntoView()` en el primer campo con error | `hooks/useFormulario.ts`, `components/ui/Campo.tsx` |
| Ventanas modales con el elemento nativo `<dialog>` (`showModal()` / `close()`), cierre al hacer clic en el fondo y foco que regresa al botón de origen | `components/ui/Modal.tsx` |
| Contadores animados con `IntersectionObserver` + `requestAnimationFrame`, escritos con `textContent` | `hooks/dom.ts`, `components/ui/Pagina.tsx` |
| `document.title` en cada página | `hooks/dom.ts` |
| Al cambiar de ruta: `scrollTo`, foco en `#contenido` y desplazamiento a anclas (`scrollIntoView`) | `app/router.tsx` |
| Exportar CSV: `Blob`, `URL.createObjectURL` y un `<a>` creado con `document.createElement`, clic programático y eliminación | `lib/exportar.ts` |
| `window.print()` con estilos de impresión que ocultan menús | `ReportesPage.tsx`, `EquipoDetallePage.tsx`, `index.css` |
| Listeners de teclado (`keydown` con Escape) en `document` para cerrar menús | `hooks/dom.ts` |
| Vista previa en vivo del diagnóstico (con retraso para no saturar la API) | `features/equipos/AccionesEquipo.tsx` |
| Foco programático en el mensaje de error del login y en el título de cada paso del registro | `LoginPage.tsx`, `EquipoNuevoPage.tsx` |

# Guion de la demostración (10–15 min)

> Abre la URL de Render 5 minutos antes (el plan gratuito se apaga tras 15 min sin uso).

1. **Sitio público (2 min).** Inicio: problema, impactos y cifras animadas. Muestra **A++** y **Alto contraste**.
   Guía con anclas. Campaña activa: registra una participación y muestra la validación con un campo vacío.
2. **Seguridad (1.5 min).** Abre `/panel` sin sesión: redirige al login. Intenta una contraseña incorrecta.
   Entra como **capturista**. En DevTools → Aplicación → Cookies: `ew.sid` es `HttpOnly` y `Secure` bajo HTTPS.
3. **Registro de equipos (2 min).** Registra una laptop: en el paso 2 omite el número de serie y muestra el error;
   luego complétalo. Muestra el folio generado y el historial con "Ingreso". Señala que el capturista no ve el menú
   de Reportes y que, si abre `/panel/reportes`, ve "No tienes acceso".
4. **Gestión (3 min).** Cierra sesión y entra como **responsable**. En el equipo nuevo, **Registrar diagnóstico**:
   cambia el costo y muestra cómo cambia la recomendación en vivo (Strategy). Envía a reparación, registra el
   resultado exitoso y luego reasigna (pide confirmar el borrado de datos). Muestra el historial completo.
5. **Destino responsable (1 min).** Abre una batería e intenta entregarla: solo aparecen gestores verificados.
6. **Reportes (2 min).** Indicadores, gráfica mensual, resultados por categoría y campañas. **Descargar CSV** e
   **Imprimir reporte**.
7. **Administrador (1 min).** Usuarios (desactivar una cuenta), Categorías y verificación de gestores.
8. **API (1 min).** `/api/docs` (Swagger) con los dos servicios, y la salida de las pruebas e2e.
