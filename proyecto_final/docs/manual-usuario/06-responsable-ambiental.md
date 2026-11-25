# 6. Responsable ambiental

[Volver a portada e índice](../manual-usuario.md)

**Requisito:** iniciar sesión como responsable ambiental o administrador. También puedes realizar las tareas de [Capturista](05-capturista.md).

## 6.1. Registrar diagnósticos

1. Abre **Equipos** y selecciona el equipo revisado físicamente.
2. En **¿Qué sigue?**, pulsa **Registrar diagnóstico**.
3. Responde **¿El equipo enciende y funciona?**.
4. Indica si presenta **daño estructural grave** o **componentes peligrosos**.
5. Captura el **Costo estimado de reparación (MXN)**, igual o mayor que cero.
6. Completa **Descripción del diagnóstico**, con al menos cinco caracteres.
7. Revisa **Recomendación del sistema** y su justificación.
8. Pulsa **Guardar diagnóstico** y comprueba la condición en la ficha.

El sistema aplica estos criterios en orden:

| Situación | Clasificación |
|---|---|
| Categoría peligrosa, componentes peligrosos o daño estructural grave | Gestión especializada. |
| Enciende y funciona, sin costo de reparación ni los riesgos anteriores | Funcional. |
| Sin esos riesgos y costo estimado de hasta el 50 % del valor de reposición de su categoría | Reparable. |
| Costo superior al límite | Gestión especializada. |

Por ejemplo, con valor de reposición de $10 000 MXN, una reparación estimada en $5 000 entra en el límite si no existen riesgos. Aunque algunos textos dicen “menos de la mitad”, la regla implementada admite exactamente el 50 %.

La recomendación depende de los datos capturados y del catálogo. No se puede diagnosticar un equipo mientras esté en reparación o después de documentar su destino final.

## 6.2. Registrar reparaciones y decisiones

### Enviar a reparación

1. Abre un equipo **Reparable**.
2. Selecciona **Enviar a reparación**.
3. Confirma con **Sí, enviar a reparación**.
4. Comprueba el estado **En reparación**.

### Registrar el resultado

1. Al terminar el trabajo, abre el equipo y pulsa **Registrar resultado de la reparación**.
2. Elige **Quedó funcionando** o **No se pudo reparar**.
3. Captura **Costo real (MXN)**, **Fecha** y **¿Qué se hizo?**.
4. Utiliza un costo no negativo y una fecha que no sea futura.
5. Pulsa **Guardar resultado**.

Una reparación exitosa cambia la condición a **Reutilizable**; una fallida, a **Gestión especializada**. Ambas terminan el estado **En reparación**.

### Elegir una decisión

| Decisión | Cuándo utilizarla |
|---|---|
| Reasignar a un área | Equipo funcional o reutilizable que tendrá un nuevo uso. |
| Conservar como respaldo | Equipo disponible para guardar; no procede si requiere gestión especializada. |
| Entregar a gestor verificado | Equipo clasificado para gestión especializada. |

La ficha ofrece únicamente las acciones permitidas en **¿Qué sigue?**.

## 6.3. Registrar movimientos y destinos

Los movimientos se generan al realizar cada operación desde la ficha; no se capturan de nuevo en otra pantalla.

| Acción | Procedimiento |
|---|---|
| **Reasignar a un área** | Escribe el área o persona receptora, el oficio o folio de resguardo y las notas necesarias. Pulsa **Confirmar reasignación**. |
| **Conservar como respaldo** | Indica el lugar donde se guardará y las notas que correspondan. Pulsa **Confirmar**. |
| **Entregar a gestor verificado** | Elige el gestor y captura el folio del manifiesto o comprobante. Pulsa **Confirmar entrega**. |

Si el equipo requiere borrado de información y está pendiente, debes confirmar que se realizó antes de reasignarlo o entregarlo. **La casilla documenta la confirmación; la plataforma no borra el dispositivo.**

La evidencia solicitada es una referencia escrita al documento de respaldo. Estos formularios no incluyen carga de archivos. Conserva el documento conforme al procedimiento institucional.

Después de guardar, revisa **Historial del equipo**. Reasignar o entregar al gestor documenta un destino final y bloquea nuevas operaciones. Conservar permite continuar la gestión después.

Desde **Gestores** puedes crear y editar empresas y centros receptores. Solo el administrador puede verificar o revocar su verificación; únicamente los verificados aparecen para documentar entregas.

## 6.4. Gestionar campañas: crear, recibir equipos y ver resultados

### Crear una campaña

1. Abre **Campañas → Nueva campaña**.
2. Completa nombre, descripción, fechas de inicio y fin y lugar.
3. Añade horario y meta de equipos si corresponde.
4. Selecciona al menos un tipo de equipo aceptado.
5. Comprueba que el fin no sea anterior al inicio y que la meta, si existe, sea de al menos un equipo.
6. Pulsa **Publicar campaña**.

Se crea como **Próximamente**. Para comenzar la recepción, cambia su estado a **Activa** y pulsa **Guardar cambios**. Al concluir, cambia a **Concluida**. Las fechas no sustituyen esta actualización manual del estado.

Utiliza **Ver página pública** para comprobar lo que verá el visitante.

### Registrar equipos recibidos

1. Abre **Registrar equipo**.
2. Selecciona el tipo y captura los datos.
3. En **¿Llegó en una campaña?**, selecciona el origen real.
4. Guarda y repite por cada aparato.
5. Regresa al detalle de la campaña y comprueba **Equipos recibidos**.

El selector excluye campañas **Próximamente** y exige compatibilidad con el tipo seleccionado. También admite campañas concluidas para documentar recepciones anteriores.

### Consultar resultados

El detalle muestra **Participaciones**, **Equipos declarados**, **Equipos recibidos** y la información privada de participantes autorizada para tu rol.

Los declarados representan intención de entrega; los recibidos provienen de registros del inventario. El detalle presenta hasta 50 equipos asociados. Para consultar un volumen mayor, abre **Equipos**, filtra por **Campaña** y utiliza la paginación.

El listado de campañas muestra el avance frente a la meta cuando se ha definido una.

## 6.5. Consultar reportes e indicadores y exportarlos

1. Abre **Reportes**.
2. Revisa equipos recuperados, éxito en reparaciones, ahorro estimado y residuo evitado.
3. Consulta actividad de los últimos 12 meses, resultados por tipo, áreas de reasignación y campañas.
4. Pulsa **Descargar CSV** para obtener un archivo como `ewaste360-reporte-2026-11-25.csv`, con columnas **Sección**, **Indicador** y **Valor**.
5. Pulsa **Imprimir reporte** para imprimir o guardar como PDF si el navegador lo permite.

La pantalla no ofrece filtros de fechas personalizados. El CSV exporta los indicadores del reporte; no es una ficha individual por equipo. El ahorro y los kilogramos son estimaciones basadas en los registros y en los valores de las categorías.
