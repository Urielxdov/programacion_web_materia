# Documentación técnica de E-Waste 360°

## Introducción

### 1. Propósito del documento

El propósito de este documento es describir el funcionamiento y la organización técnica de **E-Waste 360°**, una plataforma web para la gestión sustentable de equipos electrónicos y tecnológicos en una institución educativa. La documentación establece el contexto, el alcance y los conceptos necesarios para comprender la solución y sirve como base para explicar su arquitectura, modelo de datos, componentes, servicios web y mecanismos de seguridad.

Está dirigida a los integrantes del equipo de desarrollo, docentes evaluadores y personas que requieran instalar, mantener o ampliar el sistema. También permite relacionar las decisiones de implementación con los requisitos del proyecto integrador de las asignaturas de Programación Web y Desarrollo Sustentable.

La documentación técnica se complementa con las instrucciones de instalación, el manual de usuario, los diagramas del sistema y las evidencias de pruebas. Su finalidad es facilitar la comprensión y revisión del software, así como apoyar su mantenimiento y continuidad.

### 2. Descripción general de E-Waste 360°

E-Waste 360° es una aplicación web que centraliza el registro y seguimiento de equipos electrónicos fuera de uso o sujetos a evaluación. Atiende la necesidad de conocer qué dispositivos tiene la institución, en qué condiciones se encuentran, qué posibilidades de aprovechamiento presentan y cuál es su destino documentado.

El sistema permite registrar equipos, consultar su información, incorporar diagnósticos, documentar reparaciones y registrar decisiones de reasignación, conservación o entrega a un gestor verificado. Además, facilita la organización de campañas de recolección y la consulta de indicadores sobre inventario, reparación, reutilización, participación y ahorro estimado.

La plataforma se organiza en dos secciones:

- **Sección pública:** presenta información de concientización, recomendaciones para prolongar la vida útil de los dispositivos y campañas de recolección en las que los visitantes pueden registrar su participación.
- **Sección privada:** proporciona las funciones de gestión mediante autenticación y permisos asociados a los roles de capturista, responsable ambiental y administrador.

Desde el punto de vista técnico, utiliza React y TypeScript para la interfaz, NestJS para el servidor y PostgreSQL como base de datos relacional. La comunicación entre la interfaz y el servidor se realiza mediante una API REST con intercambio de datos en formato JSON. Su organización separa la presentación, la aplicación, las reglas de negocio y la persistencia, con una distribución de responsabilidades conforme al enfoque Modelo–Vista–Controlador (MVC).

Su contribución a la sustentabilidad consiste en aportar información para priorizar la reparación y reutilización, prolongar el aprovechamiento de los dispositivos y documentar su entrega cuando requieren gestión especializada.

### 3. Alcance del sistema

El sistema comprende la gestión de información de equipos electrónicos dentro del contexto institucional, desde su ingreso al inventario hasta el registro de su aprovechamiento o entrega a un gestor. Incluye las siguientes funciones:

| Área | Funciones comprendidas |
|---|---|
| Información y concientización | Publicación de información sobre residuos electrónicos, cuidado de dispositivos y entrega responsable. |
| Inventario | Registro con folio único, categoría, marca, modelo, número de serie cuando corresponda, fecha de ingreso, ubicación y observaciones; búsqueda, filtrado, consulta y actualización de datos permitidos. |
| Diagnóstico y clasificación | Registro de evaluaciones y generación de recomendaciones de clasificación según el funcionamiento, los riesgos y el costo estimado de reparación. |
| Reparación y reutilización | Envío a reparación, registro de resultados y costos, estimación de ahorro, reasignación a un área y conservación como respaldo. |
| Movimientos y trazabilidad | Consulta del historial de ingresos, diagnósticos, reparaciones y decisiones de destino, con responsables y referencias de evidencia según la operación. |
| Gestión especializada | Registro de gestores y de su verificación, así como documentación de entregas a gestores verificados. |
| Campañas de recolección | Publicación y administración de campañas, registro de participaciones y seguimiento de equipos recibidos y resultados. |
| Indicadores y reportes | Consulta de totales, condiciones y destinos de equipos, reparaciones, reutilización, costos, ahorro estimado y participación; visualización de gráficas, exportación CSV e impresión. |
| Administración y acceso | Inicio y cierre de sesión, autorización por roles y administración de usuarios y categorías. |

Los usuarios contemplados son:

- **Visitante:** consulta la información pública y registra su participación en campañas sin iniciar sesión.
- **Capturista:** registra, consulta y actualiza los datos permitidos de los equipos.
- **Responsable ambiental:** además de las funciones de captura, registra diagnósticos, reparaciones y movimientos; administra campañas y registros de gestores, y consulta reportes.
- **Administrador:** dispone de las funciones de gestión y administra usuarios, roles y categorías; también registra o revoca la verificación de gestores.

Los permisos de las operaciones privadas se validan en el servidor. Asimismo, el sistema distingue la **condición del equipo** de su **estado de destino**: la primera expresa su situación técnica y el segundo indica su situación dentro del proceso de gestión.

El alcance corresponde al registro, seguimiento y apoyo a la toma de decisiones. Las reparaciones, el transporte, el tratamiento de residuos y el borrado seguro de datos se realizan fuera de la aplicación; el sistema registra la información o confirmación correspondiente. La verificación de un gestor depende de la revisión documental realizada por el administrador y no constituye una certificación emitida por la plataforma. Del mismo modo, una entrega registrada no demuestra por sí sola que el equipo haya sido reciclado.

Los indicadores de peso y ahorro deben interpretarse como estimaciones cuando se calculan a partir de los pesos promedio y valores de reposición registrados en los catálogos.

### 4. Glosario de términos

| Término | Definición |
|---|---|
| RAEE | Residuos de aparatos eléctricos y electrónicos. Comprenden dispositivos y componentes descartados que requieren manejo adecuado. |
| Gestión sustentable | Administración de equipos y residuos que considera sus efectos ambientales, sociales y económicos, y favorece el aprovechamiento responsable de los recursos. |
| Economía circular | Enfoque que busca prolongar el uso de productos y materiales mediante mantenimiento, reparación, reutilización y recuperación. |
| Equipo | Dispositivo o componente registrado individualmente en el inventario de la plataforma. |
| Folio | Identificador único asignado a un equipo para facilitar su consulta y seguimiento. |
| Categoría | Clasificación del equipo que agrupa dispositivos y establece datos de referencia, como peso promedio y valor de reposición. |
| Condición del equipo | Clasificación técnica utilizada por el sistema: pendiente, funcional, reparable, reutilizable o de gestión especializada. |
| Estado de destino | Situación del equipo en el proceso de gestión: en inventario, en reparación, reasignado, conservado o entregado a un gestor. |
| Diagnóstico | Evaluación registrada sobre el funcionamiento, daños, riesgos y posibilidades de aprovechamiento de un equipo. |
| Reparación | Intervención destinada a recuperar el funcionamiento de un equipo, cuyo resultado y costo se documentan en el sistema. |
| Reutilización | Aprovechamiento de un equipo para continuar su uso, directamente o después de una reparación. |
| Reasignación | Entrega documentada de un equipo funcional o reutilizable a un área o destinatario para su uso. |
| Gestión especializada | Destino previsto para equipos que requieren manejo específico por sus riesgos, daños o falta de viabilidad de reparación. |
| Gestor verificado | Persona u organización receptora cuya información documental ha sido revisada y registrada como verificada por un administrador. |
| Movimiento | Registro de una acción relevante en la trayectoria de un equipo, asociado a un responsable y a la información de la operación. |
| Trazabilidad | Capacidad de consultar la secuencia de registros y movimientos para conocer la trayectoria y el destino documentado de un equipo. |
| Campaña de recolección | Actividad organizada con fechas, ubicación y categorías aceptadas para recibir equipos electrónicos. |
| Participación | Registro de la intención de una persona de colaborar en una campaña; no equivale a la recepción efectiva de los equipos declarados. |
| Indicador | Medida calculada a partir de los registros del sistema para apoyar la evaluación y la toma de decisiones. |
| Ahorro estimado | Aproximación del beneficio económico del aprovechamiento de equipos, calculada con valores de reposición y costos de reparación según corresponda. |
| Autenticación | Proceso de comprobación de la identidad de un usuario mediante sus credenciales. |
| Autorización por roles | Validación de las operaciones que un usuario puede realizar según el rol asignado. |
| Sesión | Mecanismo que permite mantener identificado a un usuario entre solicitudes después de iniciar sesión. |
| MVC | Modelo–Vista–Controlador: enfoque que distribuye las responsabilidades entre los datos y reglas de negocio, la presentación y la atención de solicitudes. |
| API REST | Interfaz de servicios web que permite consultar o modificar recursos mediante solicitudes HTTP. |
| JSON | Formato de texto estructurado utilizado para intercambiar datos entre la interfaz y el servidor. |
| Persistencia | Almacenamiento de información en una base de datos para conservarla entre ejecuciones de la aplicación. |
| ORM | Herramienta que relaciona objetos del programa con tablas de una base de datos; el proyecto utiliza TypeORM. |
| HTTPS | Protocolo de comunicación HTTP protegido mediante cifrado TLS. |
| CSRF | Ataque que intenta ejecutar acciones con la sesión de un usuario desde otro sitio; la aplicación incorpora validación de tokens para proteger las operaciones correspondientes. |
