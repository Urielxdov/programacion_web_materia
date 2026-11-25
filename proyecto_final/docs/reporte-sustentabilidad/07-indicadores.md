# 7. Indicadores

[Portada e índice](../reporte-sustentabilidad.md)

Los indicadores provienen de [IndicadoresRepository](../../backend/src/modules/indicadores/indicadores.repository.ts) y [IndicadoresService](../../backend/src/modules/indicadores/indicadores.service.ts). La pantalla [Reportes e indicadores](../../frontend/src/features/reportes/ReportesPage.tsx) presenta sus resultados y exporta un subconjunto en CSV.

## 7.1. Definición de cada indicador y su fórmula

### Notación

- **E:** conjunto de equipos registrados; **N = |E|**.
- **R:** registros de reparación; **R+** exitosas y **R−** fallidas.
- **S:** equipos cuyo destino actual es REASIGNADO.
- **K:** equipos cuyo destino actual es CONSERVADO.
- **D:** equipos con destino ENTREGADO_GESTOR y al menos un movimiento ENTREGA_GESTOR con gestor y evidencia no nulos.
- **p(e):** peso promedio vigente de la categoría del equipo, en kg.
- **v(e):** valor de reposición de su categoría, en MXN.
- **c(r):** costo real de una reparación, en MXN.
- **P:** registros de participación; **C:** campañas.
- **round(x):** redondeo al entero más próximo; en importes guardados se usa precisión de centavos.

Los conjuntos de equipos cuentan identificadores distintos. Los registros de reparación o participación cuentan eventos y pueden repetirse para una misma persona o equipo.

### Inventario y recuperación

| Indicador | Definición y fórmula | Unidad / interpretación |
|---|---|---|
| Equipos registrados | N = número de filas de equipos. | Equipos; inventario acumulado, no necesariamente residuos generados durante un periodo. |
| Pendientes de diagnóstico | Cantidad con condición PENDIENTE. | Equipos por revisar; no depende únicamente de su destino. |
| En reparación | Cantidad con destino EN_REPARACION. | Equipos actualmente en proceso. |
| Reasignados | Número de equipos en S. | Equipos con destino actual reasignado. |
| Conservados | Número de equipos en K. | Equipos almacenados como respaldo, sin demostrar uso posterior. |
| Distribución por condición o destino | Conteo de equipos por cada clave. | Equipos; dos clasificaciones diferentes. |
| Recuperados | Número de equipos distintos en la unión S ∪ {e con alguna reparación exitosa}. | No suma dos veces un equipo reparado y reasignado; sí puede incluir reparados sin destino final. |
| Reparaciones exitosas / fallidas | Número de registros en R+ y R−. | Eventos; el resumen los etiqueta también como “reparados”. No siempre equivale a equipos únicos. |
| Resultados por categoría | Registrados, reasignados, equipos con reparación exitosa y entregados, agrupados por categoría. | La consulta utiliza COUNT(DISTINCT equipo_id) para reparados; otros conteos pueden multiplicarse si hay varias reparaciones exitosas. |
| Destinos de reasignación | Conteo de equipos en S agrupado por destino_actual. | La consulta devuelve hasta los seis destinos con más equipos. |

### Tasas

| Indicador | Fórmula implementada | Tratamiento especial |
|---|---|---|
| Éxito de reparaciones | round(100 × número de R+ / número de R). | Si no hay reparaciones, devuelve 0. No significa fracaso; puede representar ausencia de datos. |
| Tasa de reutilización | round(100 × número de S / N). | Si N = 0, devuelve 0. No cuenta conservados ni solo reparados. |
| Avance de meta de campaña | round(100 × equipos recibidos / meta). | Sin meta devuelve null. El API puede superar 100 %; la barra del listado limita visualmente su avance a 100 %. |

Las tasas de éxito y reutilización usan poblaciones distintas y no deben sumarse. Una mide eventos de reparación y la otra equipos con destino reasignado respecto a todo el inventario.

### Masa y entregas

| Indicador | Fórmula implementada | Alcance |
|---|---|---|
| Equipos entregados documentados | Número de equipos en D. | Exige estado y existencia de movimiento con gestor y evidencia; no acredita tratamiento posterior. |
| Kg entregados documentados | Suma de p(e) para e en D. | Masa estimada, no pesaje certificado. |
| Kg de residuo evitado | Suma de p(e) para equipos en S o K cuya condición sea FUNCIONAL o REUTILIZABLE. | Conservación útil registrada y reasignación; no resta masa real de una línea base ni exige reparación exitosa previa. |
| Kg recolectados por campaña | Suma de p(e) de todos los equipos asociados a esa campaña. | Incluye cualquier condición o destino; es recepción estimada, no recuperación ni reciclaje. |

Un equipo reparado aún en inventario puede contar como recuperado y no como kg de residuo evitado. Un equipo funcional conservado puede contar en kg evitados y no en recuperados. Por tanto, **kg evitados no es recuperados multiplicado por un peso único**.

### Costos y ahorro

| Indicador | Fórmula implementada | Alcance |
|---|---|---|
| Costo total de reparaciones | Suma de c(r) para todos los registros R. | Incluye exitosas y fallidas. |
| Costo promedio | Costo total / número de R, redondeado a dos decimales. | Devuelve 0 si no hay reparaciones. |
| Ahorro de una reparación | Si es exitosa: max(0, v(e) − c(r)), redondeado a centavos; si falla: 0. | Se calcula y guarda al registrar el resultado. |
| Ahorro por reparaciones | Suma de los ahorros guardados de todos los registros R. | No descuenta después los costos fallidos ni corrige repetición de beneficios por reparaciones sucesivas. |
| Ahorro por reutilización directa | Suma de v(e) de equipos en S sin ninguna reparación exitosa. | Utiliza valor vigente de categoría; evita añadir este componente al equipo ya contado por reparación exitosa. |
| Ahorro total estimado | Ahorro por reparaciones + ahorro por reutilización directa. | Referencia de reposición evitada, no ahorro neto contable ni flujo de caja. |

La fórmula individual se encuentra en [Reparacion.calcularAhorro](../../backend/src/modules/reparaciones/reparacion.entity.ts). Su límite inferior en cero impide mostrar pérdidas en ese indicador; las reparaciones costosas requieren evaluación económica adicional.

### Participación, campañas y actividad temporal

| Indicador | Fórmula o regla | Interpretación |
|---|---|---|
| Participaciones | Número de registros P. | No elimina duplicados de personas. |
| Equipos declarados | Suma de equipos_declarados en P. | Intención de entrega. |
| Equipos recibidos de campañas | Cantidad de equipos con campana_id no nulo. | Inventario asociado, no participación declarada. |
| Campañas activas / totales | Conteo con estado ACTIVA / número total de C. | La actividad se basa en el estado registrado. |
| Resultados por campaña | Conteos anteriores restringidos a su identificador, más equipos reasignados y kg recolectados. | Permiten comparar intención y recepción por campaña. |
| Serie mensual de registrados | Equipos cuya fecha_ingreso cae en cada mes. | Fecha de ingreso declarada. |
| Serie mensual de reparaciones | Movimientos REPARACION creados en cada mes. | Incluye resultados exitosos y fallidos. |
| Serie mensual de reasignaciones y entregas | Movimientos REASIGNACION o ENTREGA_GESTOR creados en cada mes. | Fecha de creación del movimiento, no necesariamente fecha física de la operación. |

La serie incluye el mes actual del servidor y los once anteriores. No usa un periodo personalizado elegido por el usuario. La mezcla de fecha de ingreso y fecha de creación debe considerarse al interpretar movimientos registrados con retraso.

## 7.2. Cómo los calcula el sistema

1. Los módulos operativos guardan equipos, categorías, reparaciones, campañas y movimientos.
2. El repositorio ejecuta consultas agregadas en PostgreSQL: COUNT, SUM, AVG, agrupaciones y verificaciones EXISTS.
3. El servicio combina los resultados, calcula tasas y normaliza los casos sin datos.
4. La interfaz consulta el resumen, reutilización y campañas y construye tablas, gráficas e impresión.
5. La exportación CSV utiliza datos ya recibidos por la pantalla. No recalcula una contabilidad alternativa.

### Servicios disponibles

| Ruta | Contenido |
|---|---|
| /api/indicadores | Resumen de inventario, estados, economía, masa y participación. |
| /api/indicadores/reutilizacion | Tasas, reparaciones, categorías, destinos y serie mensual. |
| /api/indicadores/campanas | Totales y resultados por campaña. |
| /api/indicadores/publico | Agregados anónimos para la página pública. |

El resumen puede consultarlo el personal autenticado, incluido el capturista. Los reportes detallados de reutilización y campañas requieren responsable o administrador; la información pública no incluye participantes identificados.

### Supuestos y controles de interpretación

- Los pesos son promedios y los valores de reposición son referencias del catálogo.
- Modificar el catálogo puede cambiar estimaciones de masa y reutilización directa. El ahorro de una reparación ya guardada no se recalcula automáticamente.
- El resumen de entregas documentadas comprueba movimiento y evidencia, pero no revalida el permiso vigente del gestor.
- Los resultados por categoría y la serie mensual usan consultas diferentes del resumen documentado de entregas. No debe suponerse que todos aplican exactamente el mismo filtro de evidencia.
- Las consultas agregadas se ejecutan separadamente; una operación concurrente puede producir diferencias momentáneas entre respuestas.
- Una tasa en cero puede indicar ausencia de registros. Antes de evaluar desempeño, revisa el denominador.
- Los valores no representan reducción de emisiones ni porcentaje real de reciclaje.

## 7.3. Resultados de ejemplo: datos de prueba

Los siguientes valores se toman de **registros conservados de una ejecución de pruebas del 25 de noviembre de 2026**. No se ejecutaron nuevamente para elaborar este reporte y no pertenecen a una institución real.

Fuentes: [HTTP-06 y HTTP-24](../evidencias/pruebas-2026-11-25/peticiones-http.json), [fixture y aserciones](../../backend/test/servicios-web.e2e-spec.ts), [reporte visual](../evidencias/pruebas-2026-11-25/02-servicios-web.png).

### Escenario del fixture

| Equipo | Referencias de categoría | Resultado de prueba |
|---|---|---|
| Laptop | Peso 2 kg; reposición $14 000 MXN. | Reparación exitosa por $1500 y reasignación a Laboratorio de redes con referencia OF-1. |
| Batería | Peso 0.5 kg; reposición $600 MXN. | Gestión especializada y entrega con gestor de prueba y referencia ME-1. |

Los nombres y documentos son ficticios. AUT-001 no acredita una autorización real.

### Indicadores obtenidos y comprobación

| Indicador | Resultado | Comprobación |
|---|---|---|
| Equipos registrados | 2 | Laptop + batería. |
| Reparaciones registradas / exitosas | 1 / 1 | Una reparación exitosa. |
| Éxito de reparación | 100 % | round(1 / 1 × 100). |
| Reasignados | 1 | Laptop. |
| Tasa de reutilización | 50 % | round(1 / 2 × 100). |
| Recuperados | 1 | La laptop está reparada y reasignada; la unión la cuenta una vez. |
| Entregados documentados | 1 | Batería con movimiento y evidencia. |
| Kg entregados estimados | 0.5 kg | Peso promedio de la batería. |
| Kg de residuo evitado estimado | 2 kg | Laptop reutilizable reasignada. |
| Costo total / promedio de reparación | $1500 / $1500 MXN | Una operación. |
| Ahorro estimado | $12 500 MXN | max(0, 14 000 − 1500); reutilización directa adicional = 0. |

HTTP-06 se capturó antes de las operaciones complementarias de campañas. HTTP-24 refleja una consulta posterior con **1 participación, 2 equipos declarados, 0 recibidos y 0 kg recolectados**. Son cortes distintos del mismo conjunto documental; no deben mezclarse como una única fotografía temporal.

La diferencia declarados/recibidos es coherente: registrar intención no incorpora automáticamente equipos al inventario de una campaña.

### Procedimiento de reproducción

En una base aislada, ejecutar la suite de servicios web según la [documentación de pruebas](../documentacion-tecnica-pruebas.md) y después el procedimiento de captura HTTP descrito en el repositorio. Comparar los campos con los valores anteriores y revisar los movimientos, no únicamente los códigos de respuesta.

La suite limpia sus tablas de prueba: debe utilizarse exclusivamente en una base destinada a pruebas. La reproducción no debe apuntar a registros institucionales.
