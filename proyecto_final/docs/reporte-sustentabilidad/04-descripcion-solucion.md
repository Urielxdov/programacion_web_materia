# 4. Descripción de la solución desde la perspectiva sustentable

[Portada e índice](../reporte-sustentabilidad.md)

E-Waste 360° organiza información para decidir sobre los equipos antes de descartarlos. Su aportación consiste en conectar identificación, evaluación, acción, destino y seguimiento de resultados.

## Flujo de gestión implementado

| Etapa | Función del sistema | Propósito sustentable |
|---|---|---|
| Sensibilización | Inicio y guía pública de cuidado, reparación y entrega responsable. | Favorecer prevención y consumo responsable. |
| Recepción | Inventario con folio, categoría, fecha, ubicación y campaña de origen. | Evitar pérdida de información y facilitar identificación física. |
| Evaluación | Diagnóstico con funcionamiento, daño, componentes peligrosos y costo estimado. | Distinguir aprovechamiento y atención especializada. |
| Reparación | Envío, resultado, costo real y fecha. | Documentar recuperación técnica y recursos utilizados. |
| Aprovechamiento | Reasignación con área y respaldo o conservación con ubicación. | Mantener valor de uso y justificar el almacenamiento. |
| Gestión especializada | Entrega a receptor verificado con referencia documental. | Documentar la canalización de residuos no aprovechables. |
| Seguimiento | Historial, indicadores, gráficas, CSV e impresión. | Apoyar revisión de resultados y decisiones institucionales. |

## Reglas que orientan las decisiones

La [clasificación implementada](../../backend/src/modules/diagnosticos/strategies/estrategia-clasificacion.ts) prioriza riesgos: categoría peligrosa, componentes peligrosos o daño estructural grave conducen a gestión especializada. Sin esos riesgos, un equipo que funciona y no requiere inversión se clasifica funcional.

Para los demás casos, el sistema admite reparación cuando el costo estimado es **menor o igual al 50 %** del valor de reposición de la categoría. Si excede el límite, recomienda gestión especializada. La igualdad está admitida por el código, aunque algunos textos de la interfaz dicen “menos de la mitad”.

Esta regla es una política simplificada del proyecto. No sustituye una evaluación profesional ni considera todos los costos, necesidades de uso, riesgos específicos o consumo energético.

## Participación y distribución de responsabilidades

La sección pública informa y recibe intenciones de participación. El capturista registra equipos. El responsable ambiental evalúa, documenta operaciones y administra campañas. El administrador mantiene cuentas, categorías y verificaciones de gestores.

La separación de funciones limita cambios no autorizados. No sustituye la asignación institucional de responsables de recepción, seguridad, transporte o revisión documental.

## Qué demuestra y qué no demuestra

El sistema puede demostrar que una persona autorizada registró una operación y los datos que la respaldan. La entrega física, la ejecución del borrado de datos, la vigencia de una autorización y el tratamiento posterior requieren comprobaciones externas.

La aplicación no ofrece un certificado de reciclaje final. Tampoco mide directamente vida útil adicional, emisiones, reducción de enfermedad o ahorro presupuestario. Los beneficios deben evaluarse mediante registros consistentes y seguimiento fuera de la plataforma.
