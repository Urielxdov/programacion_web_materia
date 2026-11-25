# Documentación técnica de E-Waste 360°

## Requerimientos

Este apartado define las funciones que debe ofrecer E-Waste 360°, las condiciones de calidad de su operación y los permisos de sus usuarios. Se basa en los requisitos académicos del proyecto y en las funciones y restricciones identificadas en el código fuente.

Los identificadores **RF** corresponden a requerimientos funcionales y los identificadores **RNF** a requerimientos no funcionales. Su descripción permite utilizarlos como referencia para el diseño, las pruebas y la evaluación. La existencia de una función en el código no sustituye su validación mediante pruebas ni acredita por sí sola su funcionamiento en el sitio publicado.

### 1. Requerimientos funcionales por módulo

#### 1.1. Inicio y concientización

| ID | Requerimiento |
|---|---|
| RF-01 | El sistema debe permitir consultar información pública sobre la problemática de los residuos electrónicos y sus impactos ambientales y sociales. |
| RF-02 | El sistema debe presentar recomendaciones para prolongar la vida útil de los dispositivos, favorecer su reparación y reutilización y preparar su entrega responsable. |
| RF-03 | El sistema debe proporcionar acceso a las campañas públicas y mostrar cifras agregadas de equipos registrados, equipos recuperados, residuo evitado estimado y participación. |

#### 1.2. Inventario de equipos electrónicos

| ID | Requerimiento |
|---|---|
| RF-04 | El sistema debe permitir registrar equipos indicando categoría, marca, modelo y fecha de ingreso, además de número de serie, ubicación, observaciones y campaña de origen cuando correspondan. |
| RF-05 | El sistema debe generar un folio único para cada equipo y asociar el registro con el usuario que lo realizó. |
| RF-06 | El sistema debe validar los datos de registro y actualización, rechazar fechas de ingreso futuras y números de serie duplicados, y exigir los datos adicionales definidos por el tipo de equipo. |
| RF-07 | El sistema debe permitir buscar equipos por folio, marca, modelo, número de serie o ubicación, aplicar filtros y consultar resultados paginados. |
| RF-08 | El sistema debe mostrar el detalle de cada equipo, su condición, estado de destino, diagnósticos, reparaciones, historial y acciones disponibles. |
| RF-09 | El sistema debe permitir actualizar los datos descriptivos autorizados de los equipos sin destino final documentado. La condición y el estado de destino deben modificarse mediante las operaciones de negocio correspondientes. |
| RF-10 | El sistema debe distinguir las condiciones pendiente, funcional, reparable, reutilizable y gestión especializada de los estados de destino en inventario, en reparación, reasignado, conservado y entregado a gestor. |

#### 1.3. Reparación y reutilización

| ID | Requerimiento |
|---|---|
| RF-11 | El sistema debe permitir registrar diagnósticos con descripción, funcionamiento, daños, presencia de componentes peligrosos y costo estimado de reparación, vinculados con el equipo y el responsable. |
| RF-12 | El sistema debe generar una recomendación de clasificación mediante reglas que consideren riesgos, funcionamiento y costo de reparación respecto al valor de reposición. Debe permitir obtener una vista previa sin guardar el diagnóstico. |
| RF-13 | El sistema debe permitir enviar a reparación únicamente equipos disponibles cuya condición sea reparable. |
| RF-14 | El sistema debe permitir registrar el resultado, costo y fecha de una reparación de un equipo que se encuentre en reparación, y calcular el ahorro estimado correspondiente. |
| RF-15 | El sistema debe devolver el equipo reparado al inventario y clasificarlo como reutilizable si la reparación fue exitosa o como gestión especializada si fue fallida. |
| RF-16 | El sistema debe permitir reasignar equipos funcionales o reutilizables a un destino documentado, y conservar equipos disponibles que no requieran gestión especializada indicando su ubicación. |
| RF-17 | El sistema debe exigir la confirmación del borrado seguro de datos antes de reasignar o entregar un equipo que lo requiera y cuyo borrado no se haya confirmado previamente. |

#### 1.4. Movimientos y gestión especializada

| ID | Requerimiento |
|---|---|
| RF-18 | El sistema debe registrar en el historial los ingresos, diagnósticos, envíos a reparación, resultados de reparación, reasignaciones, conservaciones y entregas a gestores. |
| RF-19 | Cada movimiento debe identificar al equipo, el tipo de operación, la fecha, el responsable y la información de seguimiento correspondiente, incluyendo destino, gestor o referencia de evidencia cuando aplique. |
| RF-20 | El sistema debe permitir entregar a un gestor únicamente equipos disponibles clasificados para gestión especializada, con la referencia de evidencia requerida y un gestor habilitado mediante verificación. |
| RF-21 | El sistema debe rechazar cambios de estado y ediciones de datos de equipos que ya estén reasignados o entregados a un gestor. El historial debe poder consultarse sin ofrecer operaciones de edición o eliminación desde la aplicación. |
| RF-22 | El sistema debe permitir consultar el historial por equipo, consultar movimientos recientes e imprimir la ficha del equipo con su historial. |

#### 1.5. Campañas de recolección

| ID | Requerimiento |
|---|---|
| RF-23 | El sistema debe permitir crear y actualizar campañas con nombre, descripción, fechas, ubicación, categorías aceptadas y, de manera opcional, horario y meta de equipos. Debe rechazar una fecha de fin anterior a la de inicio. |
| RF-24 | El sistema debe permitir administrar los estados programada, activa y cerrada de las campañas, así como consultar públicamente su información y resultados agregados. |
| RF-25 | El sistema debe permitir registrar participaciones sin exigir una cuenta, solicitando nombre y cantidad de equipos declarados, con contacto y comentario opcionales, siempre que la campaña admita participaciones. |
| RF-26 | El sistema debe permitir asociar un equipo recibido a una campaña y verificar que su categoría se encuentre entre las aceptadas. |
| RF-27 | El sistema debe distinguir las participaciones y los equipos declarados de los equipos efectivamente registrados como recibidos. La consulta del listado de participantes debe restringirse a los roles de gestión. |

#### 1.6. Indicadores y reportes

| ID | Requerimiento |
|---|---|
| RF-28 | El sistema debe calcular un resumen con totales de equipos, distribución por condición y destino, reparaciones exitosas, entregas documentadas, costos, ahorro estimado, residuo evitado estimado y participación. |
| RF-29 | El sistema debe proporcionar resultados por campaña, incluyendo participaciones, equipos declarados, equipos recibidos y peso estimado recolectado. |
| RF-30 | El sistema debe proporcionar indicadores de reparación y reutilización, tasas de éxito y reasignación, resultados por categoría, destinos de reasignación y una serie mensual de actividad. |
| RF-31 | El sistema debe presentar gráficas y permitir exportar reportes en CSV e imprimirlos desde la interfaz de reportes. |
| RF-32 | El sistema debe identificar como estimaciones los valores calculados a partir de pesos promedio y valores de reposición. Una entrega documentada a un gestor no debe presentarse por sí sola como prueba de reciclaje efectivo. |

#### 1.7. Autenticación, usuarios y catálogos

| ID | Requerimiento |
|---|---|
| RF-33 | El sistema debe permitir iniciar sesión con correo y contraseña, consultar el usuario de la sesión y cerrar sesión. Las cuentas desactivadas no deben acceder a las funciones privadas. |
| RF-34 | El sistema debe permitir al administrador crear y actualizar usuarios, asignar roles, restablecer contraseñas y activar o desactivar cuentas. Debe impedir correos duplicados. |
| RF-35 | El sistema debe impedir que un administrador se desactive o retire su propio rol de administrador, y debe preservar al menos una cuenta administradora activa. |
| RF-36 | El sistema debe permitir al administrador crear y actualizar categorías, incluyendo su tipo, peso promedio y valor de reposición. El catálogo de categorías debe poder consultarse públicamente. |
| RF-37 | El sistema debe permitir al responsable ambiental y al administrador consultar, registrar y actualizar gestores. La verificación y su revocación deben estar reservadas al administrador. |
| RF-38 | El sistema debe ofrecer al menos los servicios web de equipos e indicadores mediante una API REST con respuestas JSON, validación de entradas, códigos HTTP y documentación de sus operaciones. |

### 2. Requerimientos no funcionales

#### 2.1. Seguridad

| ID | Requerimiento | Criterio de verificación |
|---|---|---|
| RNF-SEG-01 | El despliegue público debe utilizar HTTPS y la cookie de sesión debe tener los atributos `HttpOnly`, `Secure` en producción y `SameSite=Lax`. | Abrir el sitio publicado mediante HTTPS e inspeccionar los atributos de la cookie. |
| RNF-SEG-02 | Las contraseñas deben almacenarse mediante hash Argon2id y excluirse de las respuestas de la API. | Revisar el servicio de contraseñas, el almacenamiento y las respuestas de usuarios y autenticación. |
| RNF-SEG-03 | Toda operación privada debe validar la sesión, el estado activo de la cuenta y los permisos del rol en el servidor. | Intentar operaciones sin sesión, con un rol insuficiente y después de desactivar la cuenta; comprobar el rechazo correspondiente. |
| RNF-SEG-04 | El identificador de sesión debe regenerarse al iniciar sesión y la sesión debe destruirse al cerrarla. La cookie debe expirar tras ocho horas de inactividad. | Revisar la configuración y comprobar la renovación del identificador, el cierre y la caducidad. |
| RNF-SEG-05 | Las solicitudes de escritura deben incluir un token CSRF válido, incluso cuando correspondan a formularios públicos. | Enviar solicitudes POST o PATCH sin token o con un token inválido y comprobar su rechazo. |
| RNF-SEG-06 | Los formularios deben validar datos en el cliente y la API debe aplicar sus propias validaciones, rechazando campos no admitidos. El acceso a datos debe realizarse mediante ORM o consultas parametrizadas. | Probar entradas inválidas y campos adicionales; revisar DTO, validación global y consultas. |
| RNF-SEG-07 | La aplicación debe limitar la frecuencia de solicitudes, con una restricción específica para el inicio de sesión. | Revisar los límites configurados y verificar respuestas HTTP 429 al excederlos en un entorno con el limitador habilitado. |
| RNF-SEG-08 | Los errores enviados al cliente deben ser comprensibles y omitir credenciales, contraseñas y detalles internos del servidor. | Revisar el filtro de errores y las respuestas ante fallos de validación, permisos y errores internos. |
| RNF-SEG-09 | Los datos personales de participantes deben restringirse a las funciones de gestión; las consultas públicas deben presentar información agregada y las confirmaciones de participación no deben revelar datos de otras personas. | Consultar las rutas públicas y verificar el rechazo del listado de participantes para visitantes y capturistas. |

#### 2.2. Responsividad y accesibilidad de la interfaz

| ID | Requerimiento | Criterio de verificación |
|---|---|---|
| RNF-RES-01 | La interfaz debe adaptarse a computadoras, tabletas y teléfonos, manteniendo legibles y utilizables los contenidos y controles. | Revisar las páginas principales en anchos representativos, por ejemplo 360, 768 y 1280 píxeles; comprobar que no se recortan controles ni se pierde información. |
| RNF-RES-02 | Los menús, formularios, tablas y tarjetas deben reorganizarse según el espacio disponible y conservar una navegación consistente. | Completar los flujos de registro, consulta y gestión en móvil y escritorio. |
| RNF-RES-03 | La interfaz debe permitir ajustar el tamaño de letra y activar alto contraste, conservando la preferencia del usuario. | Cambiar ambas opciones, navegar y recargar la página para comprobar su persistencia. |
| RNF-RES-04 | Las acciones principales deben poder realizarse mediante teclado, con foco visible y manejo del foco en formularios, cambios de ruta y ventanas modales. | Recorrer los flujos con Tab, Enter y Escape; verificar que el foco permanece accesible y regresa al elemento correspondiente al cerrar un modal. |
| RNF-RES-05 | Los formularios y operaciones deben mostrar estados de carga, errores y confirmaciones claros, con etiquetas y mensajes asociados a los campos. | Provocar errores de captura y de comunicación y comprobar que la interfaz explica el resultado y permite corregir o reintentar. |

Los anchos indicados son referencias para la verificación de la interfaz; no constituyen una lista exclusiva de dispositivos compatibles ni una certificación de accesibilidad.

#### 2.3. Disponibilidad

| ID | Requerimiento | Criterio de verificación |
|---|---|---|
| RNF-DIS-01 | La aplicación debe publicarse en un servidor externo y ser accesible mediante una URL para su evaluación. | Abrir la URL desde fuera del entorno local y completar los flujos principales con cuentas de prueba. |
| RNF-DIS-02 | La interfaz y la API deben conectarse a una base de datos persistente y conservar los registros confirmados después de un reinicio normal del servidor. | Crear un registro de prueba, reiniciar el servicio y comprobar que continúa disponible. |
| RNF-DIS-03 | El despliegue debe disponer de una ruta de comprobación de salud que permita detectar si el servidor puede atender consultas a la base de datos. | Consultar `GET /api/indicadores/publico`, configurada como ruta de salud en `render.yaml`, y comprobar una respuesta correcta. |
| RNF-DIS-04 | Ante una interrupción de comunicación, la interfaz debe informar el error y permitir reintentar la operación sin mostrarla como completada. | Simular una pérdida de conexión y comprobar el mensaje y el resultado antes de volver a enviar datos. |
| RNF-DIS-05 | Antes de la evaluación debe verificarse el acceso al sitio, la autenticación y las operaciones de registro, actualización y consulta de reportes. | Ejecutar y registrar una comprobación de los flujos principales sobre la URL publicada. |

Los requisitos proporcionados no establecen un porcentaje de disponibilidad, un tiempo máximo de respuesta ni objetivos de recuperación. Estos valores quedan por definir si se requiere un acuerdo de servicio. El archivo `render.yaml` configura un servicio en un plan gratuito; esta configuración no acredita que exista un despliegue activo ni garantiza disponibilidad continua. La operación real debe comprobarse en el entorno publicado.

### 3. Roles y permisos

La siguiente matriz representa los permisos identificados en los controladores del servidor y su relación con las funciones de la interfaz. **Sí** indica una acción permitida para el rol; **—** indica que no está autorizada. Los permisos quedan sujetos a una sesión válida cuando corresponda y a las reglas de negocio de cada operación.

| Acción | Visitante | Capturista | Responsable ambiental | Administrador |
|---|:---:|:---:|:---:|:---:|
| Consultar inicio, guía y recomendaciones | Sí | Sí | Sí | Sí |
| Consultar campañas públicas y sus resultados agregados | Sí | Sí | Sí | Sí |
| Registrar participación en una campaña abierta | Sí | Sí | Sí | Sí |
| Consultar categorías y cifras públicas agregadas | Sí | Sí | Sí | Sí |
| Iniciar sesión con una cuenta activa | Sí¹ | Sí | Sí | Sí |
| Acceder al panel privado y cerrar sesión | — | Sí | Sí | Sí |
| Registrar, buscar y consultar equipos | — | Sí | Sí | Sí |
| Actualizar datos descriptivos permitidos de equipos | — | Sí | Sí | Sí |
| Consultar historial, movimientos recientes e imprimir ficha | — | Sí | Sí | Sí |
| Consultar el resumen de indicadores del panel | — | Sí | Sí | Sí |
| Simular y registrar diagnósticos | — | — | Sí | Sí |
| Enviar equipos a reparación y registrar resultados | — | — | Sí | Sí |
| Reasignar o conservar equipos | — | — | Sí | Sí |
| Registrar entregas a gestores verificados | — | — | Sí | Sí |
| Crear y actualizar campañas y sus estados | — | — | Sí | Sí |
| Consultar el listado de participantes de campañas | — | — | Sí | Sí |
| Consultar reportes detallados de campañas y reutilización | — | — | Sí | Sí |
| Exportar CSV e imprimir desde la sección de reportes | — | — | Sí | Sí |
| Consultar, registrar y actualizar gestores | — | — | Sí | Sí |
| Verificar gestores o revocar su verificación | — | — | — | Sí |
| Crear y actualizar categorías | — | — | — | Sí |
| Consultar, crear y actualizar cuentas de usuarios | — | — | — | Sí |
| Asignar roles, restablecer contraseñas y activar o desactivar cuentas | — | — | — | Sí |

¹ Un visitante puede utilizar el formulario de acceso si dispone de credenciales válidas. Al autenticarse, opera con el rol de su cuenta. Visitante representa la ausencia de sesión y no es un rol almacenado en el catálogo de usuarios.

#### Reglas complementarias de autorización

- El servidor debe comprobar los permisos aunque el usuario intente invocar directamente una ruta de la API.
- Los cambios de rol o la desactivación de una cuenta deben aplicarse en la siguiente solicitud privada, aunque la sesión estuviera abierta.
- La autorización por rol no sustituye las reglas del equipo: por ejemplo, un administrador tampoco puede reasignar un equipo que requiera gestión especializada.
- La consulta del resumen de indicadores está disponible para el capturista mediante `GET /api/indicadores`; los indicadores detallados de campañas y reutilización y la página de reportes están reservados al responsable ambiental y al administrador.
- El registro de una referencia de evidencia o la confirmación de borrado de datos documenta una declaración del responsable. La aplicación no ejecuta el borrado ni comprueba físicamente la reparación, la entrega o el reciclaje.

La matriz se sustenta en los controladores de `backend/src/modules/`, los guards de sesión y roles de `backend/src/common/guards/` y la configuración de rutas de `frontend/src/app/router.tsx`.
