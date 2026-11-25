# 8. Responsabilidad y ética

[Portada e índice](../reporte-sustentabilidad.md)

## 8.1. Trazabilidad

Cada equipo dispone de folio e historial con tipo de movimiento, fecha, responsable, estados y referencias disponibles. El sistema no expone una función para editar o eliminar movimientos desde la interfaz.

Las decisiones de destino actualizan equipo y movimiento en una misma transacción. Otros eventos, como ingreso, diagnóstico o reparación, notifican al historial mediante un listener después de guardar su operación. Por ello no debe afirmarse que todos los registros y movimientos son atómicos.

El historial no es una bitácora criptográficamente inmutable: usuarios con acceso administrativo a la base podrían alterarlo. Se recomienda control de privilegios, respaldos, auditoría independiente y mecanismos para detectar eventos sin movimiento.

Fuentes internas: [servicio de movimientos](../../backend/src/modules/movimientos/movimientos.service.ts), [listener](../../backend/src/modules/movimientos/movimientos.listener.ts) y [servicio de reparaciones](../../backend/src/modules/reparaciones/reparaciones.service.ts).

## 8.2. No declarar destinos que no estén verificados

Una entrega exige gestor habilitado y referencia de evidencia. La entidad valida su marca de verificación y la existencia de un documento; el responsable administrativo debe haberlo revisado externamente.

El agregado de entregas documentadas exige estado final y movimiento con gestor y evidencia no nulos. No vuelve a comprobar la vigencia actual del permiso ni demuestra tratamiento posterior. Una revocación interna tampoco sustituye un historial de autorizaciones.

La expresión correcta es **“entregado a gestor con referencia documental”**. Para declarar reciclaje concluido se necesitan certificados o comprobaciones del proceso posterior.

Existe además una diferencia respecto al criterio 12.6: **“equipos recuperados” incluye reparaciones exitosas sin exigir reasignación posterior**. Se recomienda separar “reparados”, “reasignados con respaldo” y “tratados con comprobante”, para evitar afirmar destinos que el sistema todavía no demuestra.

## 8.3. Protección de datos

La participación pública solicita nombre y cantidad de equipos; contacto y comentarios son opcionales. El listado de participantes es privado. Las contraseñas se almacenan mediante hash, y las operaciones cuentan con control de sesión, rol y protección CSRF.

Los equipos que pueden almacenar información requieren confirmación de borrado antes de su reasignación o entrega, cuando esté pendiente. Esa confirmación no ejecuta ni certifica el procedimiento sobre el dispositivo.

La institución debe definir avisos de privacidad, finalidades, retención, acceso a respaldos y atención de solicitudes. No se acredita cumplimiento integral de protección de datos por la sola existencia de controles técnicos.

Las capturas de este reporte deben utilizar únicamente datos de demostración y omitir contraseñas, tokens y datos personales reales.

Fuentes: [cliente de la aplicación](../../frontend/src/api/cliente.ts), [configuración del servidor](../../backend/src/app.setup.ts) y [reglas del equipo](../../backend/src/modules/equipos/equipo.entity.ts).

## 8.4. Accesibilidad

La implementación incluye controles de tamaño de letra y contraste, etiquetas, foco hacia errores, navegación por teclado, ventanas con manejo del foco y diseño que se adapta a pantallas pequeñas.

Estas características reducen barreras de uso. No constituyen una certificación WCAG ni prueban por sí solas la compatibilidad con todas las tecnologías de asistencia. Se requieren revisión de contraste, pruebas con lector de pantalla, teclado, zoom y personas usuarias.

Fuentes: [controles de lectura](../../frontend/src/components/layout/ControlesLectura.tsx), [formularios](../../frontend/src/hooks/useFormulario.ts) y [ventanas](../../frontend/src/components/ui/Modal.tsx).
