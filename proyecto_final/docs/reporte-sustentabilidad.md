# Reporte técnico: contribución a la sustentabilidad

## Problema

La institución acumula computadoras, monitores, impresoras, celulares, cables y baterías sin saber cuántos hay, en
qué estado están ni cuál fue su destino. Esto provoca que equipos aprovechables se conviertan prematuramente en
residuos, eleva los costos de almacenamiento y reposición y crea el riesgo de entregar residuos peligrosos a
destinos no adecuados.

## Cómo responde E-Waste 360° a cada criterio de Desarrollo Sustentable

| Criterio (sección 12 del proyecto) | Respuesta de la plataforma |
|---|---|
| Identificar equipos que pueden repararse o reutilizarse **antes** de considerarlos residuos | Todo equipo entra como *pendiente* y pasa por un diagnóstico. La estrategia de clasificación prioriza la reutilización y la reparación cuando cuesta menos del 50 % de un equipo nuevo. La entrega a un gestor solo se permite para equipos en *gestión especializada*. |
| Historial verificable de movimientos y destinos | La tabla `movimientos` es de solo inserción. Cada cambio de estado se registra en la misma transacción, con responsable, fecha, estado anterior y nuevo, y evidencia documental (oficio o manifiesto). |
| Organización de campañas de recolección y educación ambiental | Módulo de campañas con página pública, registro de participación, tipos aceptados, recomendaciones de entrega segura y resultados por campaña. La guía pública enseña a cuidar, reparar y entregar. |
| Indicadores de desempeño | Equipos recuperados, tasa de éxito de reparaciones, tasa de reutilización, ahorro económico estimado, kg de residuo evitado, kg entregados a gestores, participación y serie mensual. Se pueden imprimir o descargar en CSV. |
| Consumo responsable y prolongación de la vida útil | Guía "Cuida tus equipos", reasignación documentada a otras áreas y conservación de equipos de respaldo. |
| No presentar como recuperados equipos sin destino documentado | Los indicadores solo cuentan como entregados los equipos con movimiento `ENTREGA_GESTOR`, gestor verificado y evidencia. Un gestor sin autorización documentada no puede recibir equipos. |
| Seguridad, protección de datos y accesibilidad | Borrado seguro de datos obligatorio antes de reasignar o entregar computadoras y móviles; recolección mínima de datos personales; diseño accesible (letra legible, tamaño ajustable, alto contraste, navegación por teclado, áreas de toque grandes). |

## Dimensiones de la sustentabilidad

- **Ambiental:** menos residuos gracias a la reparación y la reutilización, manejo adecuado de baterías y componentes
  peligrosos, y trazabilidad hasta gestores autorizados. El indicador *kg de residuo evitado* usa el peso promedio
  de cada categoría.
- **Social:** campañas abiertas a la comunidad, reasignación de equipos a laboratorios, bibliotecas o escuelas
  comunitarias, educación ambiental y una interfaz pensada para personas mayores.
- **Económica:** el *ahorro estimado* compara el valor de reposición de cada equipo recuperado con el costo real de
  repararlo, lo que permite justificar la inversión en mantenimiento frente a la compra de equipo nuevo.

## Limitaciones y trabajo futuro

- El peso y el valor de reposición son promedios por categoría que define el administrador; los indicadores son
  estimaciones, no mediciones.
- La verificación de gestores depende de que el personal revise el documento de autorización. El sistema registra
  quién lo verificó y cuándo, pero no consulta padrones oficiales.
- Se podrían agregar la carga de archivos de evidencia (fotos y manifiestos en PDF) y el cálculo de emisiones
  evitadas con factores de una fuente oficial.
