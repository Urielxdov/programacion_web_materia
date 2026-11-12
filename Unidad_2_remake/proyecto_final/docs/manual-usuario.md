# Manual de usuario — E-Waste 360°

> 📸 **Para el equipo:** en cada sección hay una marca `[Captura: …]`. Reemplázala por la imagen de la pantalla
> correspondiente; tómala del sitio publicado o en local con los datos demo.

## Contenido
1. [Roles y acceso](#1-roles-y-acceso)
2. [Sitio público (visitantes)](#2-sitio-público)
3. [Iniciar y cerrar sesión](#3-iniciar-y-cerrar-sesión)
4. [Tablero](#4-tablero)
5. [Inventario de equipos](#5-inventario-de-equipos)
6. [Diagnóstico y recomendación de destino](#6-diagnóstico-y-recomendación-de-destino)
7. [Movimientos y su validación](#7-movimientos-y-su-validación)
8. [Campañas de recolección](#8-campañas-de-recolección)
9. [Indicadores y reportes](#9-indicadores-y-reportes)
10. [Administración](#10-administración)
11. [Preguntas frecuentes](#11-preguntas-frecuentes)

---

## 1. Roles y acceso

| Rol | Qué puede hacer |
|---|---|
| **Visitante** (sin cuenta) | Ver la información ambiental, las campañas y registrarse en una campaña activa. |
| **Capturista** | Registrar equipos, editar sus datos generales y registrar movimientos (quedan pendientes de validar). |
| **Responsable ambiental** | Todo lo del capturista, más: diagnosticar equipos, validar o rechazar movimientos, administrar campañas y consultar los indicadores. |
| **Administrador** | Todo lo anterior, más: usuarios y roles, categorías, gestores y configuración. |

El menú lateral solo muestra las opciones de tu rol. Aunque alguien escriba la dirección a mano, el sistema bloquea
el acceso con el mensaje **"Tu rol no tiene permiso…"**.

## 2. Sitio público

- **Inicio:** explica el problema de los residuos electrónicos, cómo funciona la plataforma y algunas cifras.
  Las cifras solo cuentan equipos reparados o reutilizados y entregas a gestores verificados. `[Captura: inicio]`
- **Aprende y actúa:**
  - consejos para prolongar la vida útil de los equipos;
  - cuándo conviene reparar, reutilizar o entregar;
  - una **lista de verificación** antes de entregar un equipo. Marca cada paso; la barra muestra tu avance y el navegador recuerda lo que marcaste. `[Captura: lista]`
- **Campañas:** filtra por *Activas* o *Cerradas*, o busca por nombre, lugar o tipo de equipo. `[Captura: campañas]`
- **Registrarse en una campaña:**
  1. Abre una campaña activa.
  2. Escribe tu nombre (o el de tu grupo) y cuántos equipos llevarás.
  3. El correo es opcional.
  4. Acepta el aviso de privacidad y pulsa **Registrarme**. `[Captura: registro]`

## 3. Iniciar y cerrar sesión

1. Pulsa **Iniciar sesión** en el menú superior.
2. Escribe tu usuario y tu contraseña. El ícono 👁 muestra u oculta la contraseña.
3. Tras 5 intentos fallidos, la cuenta se bloquea 15 minutos.
4. La sesión se cierra sola tras 30 minutos sin actividad.
5. Para salir, abre el menú con tu nombre (arriba a la derecha) y elige **Cerrar sesión**.

`[Captura: login]`

## 4. Tablero

Muestra:
- equipos registrados;
- equipos reparados o reutilizados;
- equipos pendientes de diagnóstico;
- ahorro estimado;
- una gráfica por clasificación y otra con los ingresos de los últimos 12 meses;
- los últimos equipos registrados.

El responsable además ve los **movimientos por validar**. El buscador de la barra superior encuentra equipos por
código, marca, modelo o número de serie. `[Captura: tablero]`

## 5. Inventario de equipos

### Buscar
En **Inventario**, escribe en *Buscar* o cambia los filtros (clasificación, estado, categoría, orden). La tabla se
actualiza mientras escribes, sin recargar la página. En el celular, cada equipo se muestra como una tarjeta.
`[Captura: inventario]`

### Registrar un equipo
1. Pulsa **Registrar equipo**.
2. Elige la **categoría**. Si el tipo de equipo guarda datos o contiene una batería, aparece un aviso.
3. Llena marca, modelo, fecha de ingreso y condición general (los campos con * son obligatorios).
4. Opcional, pero recomendado:
   - **año de fabricación:** permite calcular si el equipo superó su vida útil;
   - **peso:** se usa en los indicadores de kilos;
   - **campaña:** si el equipo se recibió en una campaña.
5. Pulsa **Registrar equipo**.

El sistema asigna un código único como `EW-2026-0014` y crea el movimiento de **ingreso** en el historial.
Si eres capturista, la clasificación queda como *Pendiente de evaluación* hasta el diagnóstico. `[Captura: formulario]`

### Consultar un equipo
La ficha del equipo muestra:
- sus datos y su clasificación y estado actuales;
- el recuadro **Manejo responsable**, que indica si requiere borrado de datos o contiene materiales peligrosos;
- el **historial de movimientos** en una línea de tiempo;
- sus **diagnósticos**.

Con el botón 🖨 la ficha se imprime sin el menú. `[Captura: ficha]`

## 6. Diagnóstico y recomendación de destino
*(Responsable ambiental y administrador)*

1. En la ficha del equipo, pulsa **Diagnosticar**.
2. Describe las pruebas realizadas y elige el **estado funcional**.
3. Captura el **costo estimado de reparación** y el **valor de un equipo equivalente nuevo**.
4. Activa **Riesgo de seguridad** si el equipo tiene una batería inflada, fugas o componentes expuestos.
5. Mientras llenas los datos, el recuadro **Recomendación del sistema** se actualiza solo:

| Situación | Recomendación |
|---|---|
| Funciona y está dentro de su vida útil | Reutilizar / reasignar |
| Funciona pero ya superó su vida útil | Conservar como respaldo |
| Falla y repararlo cuesta menos del umbral (60% por defecto) del valor nuevo | Reparar |
| Riesgo de seguridad, o reparar no es costeable | Gestión especializada |

6. Elige tu **decisión**. Si es distinta a la recomendación, escribe una **justificación** (es obligatoria).
   Con riesgo de seguridad solo se permite *gestión especializada*.
7. Pulsa **Guardar diagnóstico**. La clasificación del equipo cambia según tu decisión.

`[Captura: diagnóstico]`

## 7. Movimientos y su validación

### Registrar un movimiento (todos los roles del panel)
En la ficha del equipo, en **Registrar movimiento**, elige el tipo. Cada tipo pide datos distintos:

| Tipo | Datos que pide |
|---|---|
| Reparación realizada | Costo real (puede ser 0). |
| Reasignación / reutilización | Destino: laboratorio, área o usuario. |
| Conservación como respaldo | Ubicación (opcional). |
| Entrega a gestor | Gestor y folio o manifiesto de entrega. |

- Para laptops, computadoras y celulares hay que **confirmar el borrado seguro de datos** antes de reasignarlos o entregarlos.
- Si eliges un gestor **sin verificar**, aparece una advertencia: la entrega se registra, pero no cuenta como destino documentado.
- Un equipo para *gestión especializada* ya no puede repararse ni reasignarse.
- Un equipo *entregado a gestor* queda cerrado.
- Solo puede haber un movimiento pendiente por equipo.

`[Captura: movimiento]`

### Validar o rechazar (responsable y administrador)
1. Entra a **Validar movimientos** en el menú. Un número junto a la opción indica cuántos hay pendientes.
2. Revisa el detalle de cada movimiento.
3. Pulsa **Validar**: el movimiento se aplica al equipo y se calcula el ahorro estimado.
4. O pulsa **Rechazar** y escribe el motivo.

Si tú registras un movimiento con este rol, se valida automáticamente. `[Captura: validación]`

## 8. Campañas de recolección
*(Responsable ambiental y administrador)*

- **Nueva campaña:**
  - Captura nombre, descripción, fechas, lugar y los equipos aceptados (separados por coma; se muestran como etiquetas mientras escribes).
  - La meta es opcional.
  - Estado: *Borrador* (no se publica), *Activa* (recibe registros) o *Cerrada*.
- **Resultados:**
  - participantes, asistencia, equipos comprometidos frente a recibidos, kg y avance de la meta;
  - con el botón *Sí/No* de cada participante se marca su asistencia;
  - los correos se muestran parcialmente ocultos.
- Para que un equipo cuente como recibido en una campaña, selecciónala al registrarlo, en **Recibido en campaña**.

`[Captura: campaña]`

## 9. Indicadores y reportes
*(Responsable ambiental y administrador)*

La página **Indicadores** muestra:
- tasa de aprovechamiento, ahorro estimado e inversión en reparaciones;
- kg entregados a gestores verificados y entregas sin destino verificado;
- participaciones y movimientos pendientes;
- gráficas por estado, por categoría y por mes;
- tablas de aprovechamiento por categoría y de resultados por campaña.

Para exportar:
- **Imprimir / PDF** genera el reporte sin el menú lateral.
- **Inventario CSV** descarga el inventario para Excel.

`[Captura: indicadores]`

## 10. Administración
*(Solo administrador)*

- **Usuarios y roles:**
  - Crea cuentas con una contraseña temporal de mínimo 8 caracteres, con letras y números. Una barra indica qué tan segura es.
  - Cambia el rol, desactiva cuentas o asigna una nueva contraseña.
  - No puedes quitarte tu propio rol de administrador, y siempre debe quedar al menos un administrador activo.
- **Categorías:**
  - El *tipo* define el comportamiento del equipo (borrado de datos, peligrosidad).
  - No se puede cambiar el tipo de una categoría que ya tiene equipos.
- **Gestores:**
  - Marca como **verificado** solo al gestor cuya autorización ambiental revisaste; el sistema pide el número de autorización.
- **Configuración:**
  - **Umbral de reparación (%):** hasta qué porcentaje del valor nuevo conviene reparar.
  - **Tolerancia de vida útil:** factor sobre la vida útil de la categoría.
  - **Nombre de la institución.**
  - Los cambios afectan las recomendaciones a partir de ese momento.

`[Captura: administración]`

## 11. Preguntas frecuentes

- **"La página expiró o el formulario no es válido".** El formulario estuvo abierto mucho tiempo o tu sesión cambió. Recarga la página y vuelve a enviarlo.
- **No veo el botón Diagnosticar.** Tu rol es capturista. Pide a un responsable ambiental que lo diagnostique.
- **Registré un movimiento pero el estado del equipo no cambió.** Está pendiente de validación.
- **¿Por qué una entrega no aparece en "kg a gestores verificados"?** El gestor no está verificado. Un administrador debe revisar su autorización y marcarlo como verificado.
