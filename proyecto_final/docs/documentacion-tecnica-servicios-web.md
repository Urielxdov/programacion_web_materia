# Documentación técnica de E-Waste 360°

## Servicios web

Este apartado documenta los dos servicios REST principales: equipos e indicadores. Incluye las rutas del ciclo de gestión del equipo, aunque sus controladores se encuentren distribuidos entre varios módulos del backend.

Los contratos se contrastaron con los controladores, DTO, servicios, repositorios, guards y filtro global de errores. Los ejemplos son ilustrativos; no son capturas de peticiones ejecutadas durante la elaboración de este documento. Los identificadores deben sustituirse por registros existentes y las operaciones requieren que el equipo se encuentre en un estado compatible.

### 1. Convenciones generales

#### 1.1. URL base y documentación interactiva

La URL base es **`<origen>/api`**. Todas las rutas de este documento incluyen el prefijo `/api`.

| Entorno | URL base prevista |
|---|---|
| Backend local, con puerto predeterminado | `http://localhost:3000/api` |
| Docker, mediante el frontend Nginx y su puerto predeterminado | `http://localhost:8080/api` |
| Despliegue | `https://<dominio-configurado>/api` |

El puerto puede cambiar mediante configuración. La documentación Swagger se publica en **`<origen>/api/docs`**. La presencia de archivos de despliegue en el repositorio no confirma una URL pública activa.

#### 1.2. Formato de peticiones y respuestas

- Los cuerpos de las peticiones son JSON: enviar `Content-Type: application/json`.
- Las respuestas de estos endpoints se serializan a JSON. Los nombres de propiedades utilizan `camelCase`.
- Las consultas GET no requieren cuerpo. Los filtros se envían en la cadena de consulta.
- Los identificadores son números enteros; los parámetros de ruta pasan por `ParseIntPipe`.
- Las fechas de negocio se envían como `AAAA-MM-DD`; las marcas de auditoría se serializan como fechas ISO 8601.
- Los booleanos del cuerpo se envían como `true` o `false`, no como cadenas.
- Los importes representan MXN y los pesos representan kg. Las columnas numéricas de entidades usan un transformador y los agregados de indicadores se convierten a números.
- No existe una envoltura de éxito universal: unas rutas devuelven objetos, otras arreglos y el listado de equipos un objeto paginado.

`ValidationPipe` transforma los DTO, admite solo propiedades declaradas y rechaza las desconocidas. `stopAtFirstError` detiene las comprobaciones adicionales de cada propiedad fallida; pueden recibirse mensajes de varias propiedades en la misma respuesta.

Los ejemplos de entidades muestran **extractos de respuestas** con los campos relevantes; pueden existir campos adicionales de auditoría y relaciones. No contienen claves ficticias de puntos suspensivos. Los ejemplos de indicadores muestran la estructura del resultado; la serie mensual se abrevia expresamente en su apartado.

#### 1.3. Autenticación, autorización y CSRF

Se utiliza una sesión de servidor almacenada en PostgreSQL y la cookie **`ew.sid`**. La API no utiliza un token Bearer/JWT para estos endpoints. La cookie es `HttpOnly`, tiene `SameSite=Lax` y es `Secure` en producción; se configura una duración de ocho horas de inactividad con renovación.

El cliente debe conservar y reenviar las cookies. En `fetch`, el cliente del proyecto utiliza `credentials: 'include'`. No es necesario ni posible leer desde JavaScript la cookie `HttpOnly` para copiar su valor a un encabezado.

Flujo de acceso:

1. Solicitar `GET /api/auth/csrf` sin cuerpo. Responde `200` con `{"csrfToken":"<token>"}` y establece la cookie de protección CSRF.
2. Enviar `POST /api/auth/login` con `{"email":"responsable@ewaste360.mx","password":"<contraseña>"}`, las cookies y `X-CSRF-Token: <token>`.
3. El login responde `200` con `usuario` y un **nuevo** `csrfToken`, después de regenerar la sesión. Utilizar ese nuevo token para las siguientes escrituras.
4. Enviar cookies en las consultas privadas y, además, `X-CSRF-Token` en todos los POST y PATCH aquí documentados, incluida la simulación de diagnóstico.

La ruta pública de login también requiere CSRF. El login puede responder `400` por validación, `401` por credenciales incorrectas o cuenta inactiva, `403` por CSRF, `429` por límite y `500` por error interno; su límite específico es ocho solicitudes por minuto. Estos endpoints de autenticación se describen como prerrequisito de los dos servicios.

En cada solicitud privada, el guard comprueba que la cuenta siga activa y actualiza el rol desde la base de datos. Los permisos son:

| Denominación utilizada | Roles permitidos |
|---|---|
| Todos los autenticados | `CAPTURISTA`, `RESPONSABLE`, `ADMIN` |
| Gestión ambiental | `RESPONSABLE`, `ADMIN` |
| Público | No requiere sesión ni rol. |

El límite general configurado es de 200 solicitudes por ventana de 60 segundos según el seguimiento del guard de NestJS. No se aplica cuando `NODE_ENV=test`. Los endpoints privados pueden responder `401` por falta de sesión o cuenta desactivada y `403` por autorización; las escrituras también pueden responder `403` por CSRF.

#### 1.4. Formato uniforme de errores

El filtro global produce los siguientes campos:

```json
{
  "statusCode": 422,
  "error": "UNPROCESSABLE_ENTITY",
  "message": "Solo los equipos diagnosticados como reparables pueden enviarse a reparación.",
  "path": "/api/equipos/47/movimientos",
  "timestamp": "2026-11-25T18:00:00.000Z"
}
```

| Campo | Tipo | Significado |
|---|---|---|
| `statusCode` | Número | Código HTTP de la respuesta. |
| `error` | Cadena | Nombre simbólico del código según `HttpStatus`, por ejemplo `BAD_REQUEST`. |
| `message` | Cadena o arreglo de cadenas | Descripción del error o mensajes de validación. |
| `path` | Cadena | URL de la solicitud tomada de `req.url`; puede incluir su consulta. |
| `timestamp` | Cadena ISO 8601 | Momento de generación de la respuesta. |

El guard CSRF genera internamente `code: 'CSRF_INVALIDO'`, pero el filtro construye un nuevo objeto con los cinco campos anteriores y **no conserva `code`**. El contrato HTTP actual no garantiza ese campo adicional.

### 2. API de equipos

#### 2.1. Resumen de endpoints

| Método | Ruta | Rol requerido | Éxito |
|---|---|---|---|
| GET | `/api/equipos` | Todos los autenticados | 200 |
| GET | `/api/equipos/:id` | Todos los autenticados | 200 |
| POST | `/api/equipos` | Todos los autenticados | 201 |
| PATCH | `/api/equipos/:id` | Todos los autenticados | 200 |
| GET | `/api/equipos/:equipoId/movimientos` | Todos los autenticados | 200 |
| POST | `/api/equipos/:equipoId/diagnosticos/simular` | Gestión ambiental | 200 |
| POST | `/api/equipos/:equipoId/diagnosticos` | Gestión ambiental | 201 |
| POST | `/api/equipos/:equipoId/reparaciones` | Gestión ambiental | 201 |
| POST | `/api/equipos/:equipoId/movimientos` | Gestión ambiental | 201 |
| GET | `/api/movimientos/recientes` | Todos los autenticados | 200 |

Todas estas rutas están sujetas a errores comunes **401, 403, 429 y 500**. En cada apartado se repiten los códigos aplicables para facilitar su consulta. Los códigos de integridad que dependen de fallos de PostgreSQL se explican en la sección 4.

#### 2.2. GET `/api/equipos` — búsqueda paginada

**Rol:** todos los autenticados. **Cuerpo:** ninguno.

| Parámetro de consulta | Tipo y validación | Predeterminado o comportamiento |
|---|---|---|
| `q` | Cadena de hasta 100 caracteres | Busca sin distinguir mayúsculas en folio, marca, modelo, serie y ubicación. |
| `categoriaId` | Entero | Filtra por categoría; el DTO no declara mínimo. |
| `condicion` | Enum de condición | Filtra por condición técnica. |
| `estadoDestino` | Enum de destino | Filtra por destino actual. |
| `campanaId` | Entero | Filtra por campaña; el DTO no declara mínimo. |
| `orden` | `recientes` o `antiguos` | Recientes; ordena por fecha de ingreso y luego por id. |
| `pagina` | Entero ≥ 1 | 1 |
| `porPagina` | Entero de 1 a 100 | 20 |

Condiciones: `PENDIENTE`, `FUNCIONAL`, `REPARABLE`, `REUTILIZABLE`, `GESTION_ESPECIALIZADA`. Destinos: `EN_INVENTARIO`, `EN_REPARACION`, `REASIGNADO`, `CONSERVADO`, `ENTREGADO_GESTOR`.

Ejemplo: `GET /api/equipos?q=Dell&condicion=PENDIENTE&pagina=1&porPagina=20`.

**Respuesta 200, con el objeto de equipo abreviado:**

```json
{
  "datos": [
    {
      "id": 47,
      "folio": "EW-2026-0047",
      "marca": "Dell",
      "modelo": "Latitude 5490",
      "condicion": "PENDIENTE",
      "estadoDestino": "EN_INVENTARIO",
      "categoria": { "id": 2, "nombre": "Laptop", "tipo": "COMPUTO" },
      "campana": null
    }
  ],
  "total": 1,
  "pagina": 1,
  "porPagina": 20,
  "totalPaginas": 1
}
```

El listado incluye categoría y campaña, pero no agrega `accionesDisponibles`, `recomendacionesEntrega` ni el historial completo. Sin coincidencias devuelve `datos: []`, `total: 0` y `totalPaginas: 1`; no devuelve 404.

**Códigos:** `200`; `400` por filtros inválidos o propiedades de consulta no admitidas; `401`, `403`, `429`, `500`.

#### 2.3. GET `/api/equipos/:id` — detalle

**Rol:** todos los autenticados. **Cuerpo:** ninguno. **Parámetro:** `id`, entero.

**Respuesta 200, extracto:**

```json
{
  "id": 47,
  "folio": "EW-2026-0047",
  "marca": "Dell",
  "modelo": "Latitude 5490",
  "numeroSerie": "CN-0X1Y2Z",
  "fechaIngreso": "2026-11-20",
  "condicion": "PENDIENTE",
  "estadoDestino": "EN_INVENTARIO",
  "ubicacion": "Almacén B",
  "requiereBorradoDatos": true,
  "datosBorrados": false,
  "categoria": { "id": 2, "nombre": "Laptop", "tipo": "COMPUTO" },
  "campana": null,
  "registradoPor": { "id": 3, "nombre": "Carla Capturista", "rol": "CAPTURISTA" },
  "diagnosticos": [],
  "reparaciones": [],
  "movimientos": [
    { "id": 101, "tipo": "INGRESO", "descripcion": "Ingreso del equipo EW-2026-0047 al inventario." }
  ],
  "accionesDisponibles": ["DIAGNOSTICAR", "CONSERVAR"],
  "recomendacionesEntrega": [
    "Respalda la información importante.",
    "Borra los datos de forma segura (formateo completo o herramienta de borrado).",
    "Cierra sesión en cuentas institucionales y retira contraseñas.",
    "Entrega el equipo completo, sin desarmarlo.",
    "Anota el folio del equipo en la hoja de entrega."
  ]
}
```

Los diagnósticos, reparaciones y movimientos se ordenan del más reciente al más antiguo. Las acciones dependen del estado del equipo; los permisos de rol se comprueban adicionalmente en el servidor. Las relaciones de usuario seleccionadas ordinariamente no incluyen `passwordHash`.

**Códigos:** `200`; `400` si `id` no se interpreta como entero; `404` si el equipo no existe; `401`, `403`, `429`, `500`.

#### 2.4. POST `/api/equipos` — registro

**Rol:** todos los autenticados. **CSRF:** requerido.

| Campo del cuerpo | Obligatorio | Validación o significado |
|---|---|---|
| `categoriaId` | Sí | Entero ≥ 1; debe existir. |
| `marca` | Sí | Cadena de 1 a 80 caracteres, tras recorte. |
| `modelo` | Sí | Cadena de 1 a 120 caracteres, tras recorte. |
| `numeroSerie` | Según categoría | Cadena de hasta 120; obligatoria en cómputo y comprobada contra series existentes. |
| `fechaIngreso` | Sí | Fecha válida; el servicio rechaza fechas futuras. |
| `ubicacion` | No | Cadena de hasta 160 caracteres. |
| `observaciones` | No | Cadena de hasta 1000 caracteres. |
| `campanaId` | No | Entero ≥ 1; la campaña debe existir y aceptar la categoría. |

**Petición:**

```json
{
  "categoriaId": 2,
  "marca": "Dell",
  "modelo": "Latitude 5490",
  "numeroSerie": "CN-0X1Y2Z",
  "fechaIngreso": "2026-11-20",
  "ubicacion": "Almacén B"
}
```

**Respuesta 201, extracto:**

```json
{
  "id": 47,
  "folio": "EW-2026-0047",
  "marca": "Dell",
  "modelo": "Latitude 5490",
  "condicion": "PENDIENTE",
  "estadoDestino": "EN_INVENTARIO",
  "requiereBorradoDatos": true,
  "datosBorrados": false,
  "accionesDisponibles": ["DIAGNOSTICAR", "CONSERVAR"],
  "movimientos": [{ "id": 101, "tipo": "INGRESO" }],
  "diagnosticos": [],
  "reparaciones": []
}
```

Devuelve el detalle del equipo, incluidas sus recomendaciones de entrega. El folio, la condición y el usuario que registra se determinan en el servidor. Para categorías de energía la condición inicial es `GESTION_ESPECIALIZADA`; el destino inicial sigue siendo `EN_INVENTARIO`.

**Códigos:** `201`; `400` por DTO inválido o campos adicionales; `404` por categoría o campaña inexistente; `409` por serie duplicada o conflicto de unicidad; `422` por reglas del perfil, fecha futura o incompatibilidad con la campaña; `401`, `403`, `429`, `500`.

#### 2.5. PATCH `/api/equipos/:id` — actualización

**Rol:** todos los autenticados. **CSRF:** requerido. **Parámetro:** `id`, entero.

El cuerpo permite únicamente `marca`, `modelo`, `numeroSerie`, `fechaIngreso`, `ubicacion` y `observaciones`, todos opcionales y con los límites del registro. No admite editar categoría, campaña, folio, condición ni destino por esta ruta. El DTO no exige al menos un campo; un objeto vacío supera esa comprobación, aunque continúan aplicándose las reglas del servicio.

**Petición:**

```json
{
  "ubicacion": "Laboratorio de redes",
  "observaciones": "Pendiente de revisión técnica."
}
```

**Respuesta 200, extracto:**

```json
{
  "id": 47,
  "folio": "EW-2026-0047",
  "ubicacion": "Laboratorio de redes",
  "observaciones": "Pendiente de revisión técnica.",
  "condicion": "PENDIENTE",
  "estadoDestino": "EN_INVENTARIO",
  "accionesDisponibles": ["DIAGNOSTICAR", "CONSERVAR"]
}
```

Devuelve nuevamente el detalle. Los equipos con destino `REASIGNADO` o `ENTREGADO_GESTOR` no admiten edición. Para omitir un dato opcional, omitir la propiedad de la petición; no debe asumirse una operación uniforme de borrado con `null` o cadena vacía, ya que las transformaciones y asignaciones varían por campo.

**Códigos:** `200`; `400` por id o cuerpo inválido; `404` por equipo inexistente; `409` por serie duplicada o conflicto de unicidad; `422` por destino final, fecha futura o validación del perfil; `401`, `403`, `429`, `500`.

#### 2.6. GET `/api/equipos/:equipoId/movimientos` — historial

**Rol:** todos los autenticados. **Cuerpo:** ninguno. **Parámetro:** `equipoId`, entero.

**Respuesta 200, extracto de un elemento:**

```json
[
  {
    "id": 101,
    "tipo": "INGRESO",
    "descripcion": "Ingreso del equipo EW-2026-0047 al inventario.",
    "estadoAnterior": null,
    "estadoNuevo": "EN_INVENTARIO",
    "destino": null,
    "evidencia": null,
    "responsable": { "id": 3, "nombre": "Carla Capturista" },
    "gestor": null,
    "creadoEn": "2026-11-25T18:00:00.000Z"
  }
]
```

Devuelve un arreglo sin paginación, ordenado por `creadoEn DESC, id DESC`. Verifica primero que exista el equipo; un equipo existente sin movimientos devuelve `[]`.

**Códigos:** `200`; `400` por id inválido; `404` por equipo inexistente; `401`, `403`, `429`, `500`.

#### 2.7. POST `/api/equipos/:equipoId/diagnosticos/simular` — simulación

**Rol:** gestión ambiental. **CSRF:** requerido aunque no persiste el diagnóstico. **Parámetro:** `equipoId`, entero.

Este cuerpo es compartido con el registro de diagnóstico:

| Campo | Obligatorio | Validación |
|---|---|---|
| `descripcion` | Sí | Cadena de 5 a 2000 caracteres tras recorte; también se exige al simular. |
| `enciende` | Sí | Booleano. |
| `danoEstructural` | Sí | Booleano. |
| `componentesPeligrosos` | Sí | Booleano. |
| `costoReparacionEstimado` | Sí | Número de 0 a 10 000 000, hasta dos decimales. |

**Petición:**

```json
{
  "descripcion": "El equipo enciende y funciona correctamente.",
  "enciende": true,
  "danoEstructural": false,
  "componentesPeligrosos": false,
  "costoReparacionEstimado": 0
}
```

**Respuesta 200**, para una categoría de cómputo sin peligrosidad:

```json
{
  "condicion": "FUNCIONAL",
  "justificacion": "Funciona correctamente y no requiere reparación. Puede reasignarse a otra área.",
  "estrategia": "ReutilizacionDirecta"
}
```

La simulación consulta la categoría y ejecuta el clasificador; no cambia el equipo ni verifica las mismas restricciones de transición que el registro. Una recomendación simulada no garantiza que posteriormente pueda guardarse si el equipo está en reparación o tiene destino final.

**Códigos:** `200`; `400` por id o DTO inválido; `404` por equipo inexistente; `401`, `403`, `429`, `500`.

#### 2.8. POST `/api/equipos/:equipoId/diagnosticos` — registro del diagnóstico

**Rol:** gestión ambiental. **CSRF:** requerido. **Cuerpo:** los cinco campos obligatorios de la sección 2.7.

**Petición:**

```json
{
  "descripcion": "El equipo enciende y funciona correctamente.",
  "enciende": true,
  "danoEstructural": false,
  "componentesPeligrosos": false,
  "costoReparacionEstimado": 0
}
```

**Respuesta 201, extracto:**

```json
{
  "id": 24,
  "descripcion": "El equipo enciende y funciona correctamente.",
  "enciende": true,
  "danoEstructural": false,
  "componentesPeligrosos": false,
  "costoReparacionEstimado": 0,
  "recomendacion": "FUNCIONAL",
  "estrategiaAplicada": "ReutilizacionDirecta",
  "justificacion": "Funciona correctamente y no requiere reparación. Puede reasignarse a otra área.",
  "equipo": { "id": 47, "condicion": "FUNCIONAL", "estadoDestino": "EN_INVENTARIO" },
  "responsable": { "id": 2, "nombre": "Rubén Responsable" }
}
```

Actualiza la condición y guarda el diagnóstico; después publica el evento que genera el movimiento de historial. No permite diagnosticar mientras el equipo está en reparación o después de un destino final.

**Códigos:** `201`; `400` por id o DTO inválido; `422` por transición no permitida; `401`, `403`, `429`, `500`. Un equipo inexistente puede producir `500` por el comportamiento de bloqueo explicado en la sección 4.3; no se garantiza `404` para ese caso.

#### 2.9. POST `/api/equipos/:equipoId/reparaciones` — resultado de reparación

**Rol:** gestión ambiental. **CSRF:** requerido. **Parámetro:** `equipoId`, entero.

| Campo | Obligatorio | Validación |
|---|---|---|
| `descripcion` | Sí | Cadena de 5 a 2000 caracteres tras recorte. |
| `costo` | Sí | Número de 0 a 10 000 000, hasta dos decimales. |
| `resultado` | Sí | `EXITOSA` o `FALLIDA`. |
| `fecha` | Sí | Fecha válida; el servicio rechaza fechas futuras. |

**Petición:**

```json
{
  "descripcion": "Se reemplazó la fuente de poder.",
  "costo": 650,
  "resultado": "EXITOSA",
  "fecha": "2026-11-25"
}
```

**Respuesta 201, extracto**, suponiendo valor de reposición de 14 000 MXN y un equipo previamente enviado a reparación:

```json
{
  "id": 12,
  "descripcion": "Se reemplazó la fuente de poder.",
  "costo": 650,
  "resultado": "EXITOSA",
  "fecha": "2026-11-25",
  "ahorroEstimado": 13350,
  "equipo": { "id": 47, "condicion": "REUTILIZABLE", "estadoDestino": "EN_INVENTARIO" },
  "diagnostico": { "id": 24 },
  "responsable": { "id": 2, "nombre": "Rubén Responsable" }
}
```

Exige destino `EN_REPARACION`. Ambos resultados devuelven el destino a `EN_INVENTARIO`: una reparación exitosa deja condición `REUTILIZABLE`; una fallida, `GESTION_ESPECIALIZADA`. El ahorro es cero en fallidas; en exitosas es `max(0, valorReposicion - costo)`, redondeado a dos decimales. La entrega a gestor requiere una decisión posterior.

**Códigos:** `201`; `400` por id o DTO inválido; `422` si el equipo no está en reparación o la fecha es futura; `401`, `403`, `429`, `500`. Equipo inexistente: limitación de la sección 4.3.

#### 2.10. POST `/api/equipos/:equipoId/movimientos` — decisión de destino

**Rol:** gestión ambiental. **CSRF:** requerido. **Parámetro:** `equipoId`, entero.

| Campo | Requisito | Validación |
|---|---|---|
| `tipo` | Siempre obligatorio | `ENVIO_REPARACION`, `REASIGNACION`, `CONSERVACION` o `ENTREGA_GESTOR`. |
| `destino` | Obligatorio en reasignación y conservación | Cadena de 3 a 200 caracteres. |
| `gestorId` | Obligatorio en entrega a gestor | Entero ≥ 1. |
| `evidencia` | Obligatoria en reasignación y entrega a gestor | Cadena de 3 a 200 caracteres; referencia textual a un oficio o documento. |
| `datosBorrados` | Opcional en DTO; exigido por dominio cuando procede | Booleano; confirmar `true` al entregar o reasignar un equipo que requiere borrado y aún no tiene confirmación. |
| `notas` | Opcional | Cadena de hasta 1000 caracteres; se agrega a la descripción del movimiento. |

Las validaciones de `destino`, `gestorId` y `evidencia` se activan por tipo mediante `ValidateIf`. Enviar únicamente los campos correspondientes a la decisión.

**Ejemplos alternativos de cuerpo:**

```json
{ "tipo": "ENVIO_REPARACION" }
```

```json
{
  "tipo": "REASIGNACION",
  "destino": "Laboratorio de redes",
  "evidencia": "Oficio RG-145/2026",
  "datosBorrados": true
}
```

```json
{ "tipo": "CONSERVACION", "destino": "Almacén de respaldo" }
```

```json
{
  "tipo": "ENTREGA_GESTOR",
  "gestorId": 1,
  "evidencia": "Manifiesto ME-501",
  "datosBorrados": true
}
```

**Respuesta 201, extracto para envío a reparación:**

```json
{
  "id": 103,
  "tipo": "ENVIO_REPARACION",
  "descripcion": "Enviado a reparación.",
  "estadoAnterior": "EN_INVENTARIO",
  "estadoNuevo": "EN_REPARACION",
  "destino": null,
  "gestor": null,
  "evidencia": null,
  "equipo": { "id": 47, "condicion": "REPARABLE", "estadoDestino": "EN_REPARACION" },
  "responsable": { "id": 2, "nombre": "Rubén Responsable" }
}
```

Enviar a reparación exige condición `REPARABLE`; reasignar exige `FUNCIONAL` o `REUTILIZABLE`; conservar no permite condición de gestión especializada; entregar a gestor exige esa condición y un gestor verificado con documento. El equipo debe estar disponible y sin destino final. El cambio del equipo y su movimiento se guardan en una sola transacción.

**Códigos:** `201`; `400` por id o cuerpo inválido, incluidos campos condicionales faltantes; `404` por gestor inexistente en una entrega; `422` por transición, gestor no verificado o borrado sin confirmar; `401`, `403`, `429`, `500`. Equipo inexistente: limitación de la sección 4.3. Un conflicto SQL de FK o unicidad puede traducirse a `409`.

#### 2.11. GET `/api/movimientos/recientes` — actividad reciente

**Rol:** todos los autenticados. **Cuerpo:** ninguno.

`limite` es un entero opcional, predeterminado `8`. El controlador ajusta los enteros recibidos al intervalo de 1 a 50: `limite=0` se convierte en 1 y `limite=100` en 50; una cadena que no se interprete como entero produce 400.

Ejemplo: `GET /api/movimientos/recientes?limite=8`.

**Respuesta 200, extracto:**

```json
[
  {
    "id": 103,
    "tipo": "ENVIO_REPARACION",
    "descripcion": "Enviado a reparación.",
    "estadoAnterior": "EN_INVENTARIO",
    "estadoNuevo": "EN_REPARACION",
    "creadoEn": "2026-11-25T18:10:00.000Z",
    "equipo": { "id": 47, "folio": "EW-2026-0047" },
    "responsable": { "id": 2, "nombre": "Rubén Responsable" }
  }
]
```

Ordena por `creadoEn DESC, id DESC`; puede devolver `[]`. Incluye equipo y responsable; no carga explícitamente la relación gestor como lo hace el historial por equipo.

**Códigos:** `200`; `400` por límite no entero; `401`, `403`, `429`, `500`.

### 3. API de indicadores

Las cuatro rutas son GET y **no requieren cuerpo ni CSRF**. No declaran filtros de fechas, paginación u otros parámetros de consulta; no deben enviarse cuerpos ni suponerse filtros inexistentes. Devuelven agregados del conjunto de datos almacenado.

#### 3.1. GET `/api/indicadores` — resumen general

**Rol:** todos los autenticados. **Cuerpo:** ninguno.

**Respuesta 200, ejemplo ilustrativo:**

```json
{
  "equipos": {
    "total": 10,
    "pendientesDiagnostico": 2,
    "enReparacion": 1,
    "reasignados": 3,
    "conservados": 1,
    "recuperados": 4,
    "reparados": 2
  },
  "porCondicion": [
    { "clave": "FUNCIONAL", "total": 3 },
    { "clave": "GESTION_ESPECIALIZADA", "total": 3 },
    { "clave": "PENDIENTE", "total": 2 },
    { "clave": "REPARABLE", "total": 1 },
    { "clave": "REUTILIZABLE", "total": 1 }
  ],
  "porEstado": [
    { "clave": "EN_INVENTARIO", "total": 4 },
    { "clave": "REASIGNADO", "total": 3 },
    { "clave": "CONSERVADO", "total": 1 },
    { "clave": "EN_REPARACION", "total": 1 },
    { "clave": "ENTREGADO_GESTOR", "total": 1 }
  ],
  "gestores": { "equiposEntregados": 1, "kgEntregados": 2.2 },
  "economia": { "costoReparaciones": 1300, "ahorroEstimado": 41350 },
  "ambiente": { "kgResiduoEvitado": 8.8 },
  "participacion": {
    "participaciones": 5,
    "equiposDeclarados": 8,
    "equiposRecibidos": 4,
    "campanasActivas": 1,
    "campanasTotales": 2
  }
}
```

`porCondicion` y `porEstado` incluyen los grupos presentes, ordenados por total descendente. Las métricas pueden solaparse: `recuperados` cuenta equipos distintos reasignados o con reparación exitosa; `reparados` toma el número de registros de reparación exitosa, por lo que no implica por sí solo equipos distintos. Las categorías sin registros no se rellenan con cero en estos dos arreglos.

**Códigos:** `200`; `401`, `403`, `429`, `500`. No hay una condición de negocio que produzca 404 cuando la base no tiene equipos: devuelve totales cero y agrupaciones vacías.

#### 3.2. GET `/api/indicadores/campanas` — resultados de campañas

**Rol:** gestión ambiental. **Cuerpo:** ninguno.

**Respuesta 200:**

```json
{
  "totales": {
    "participaciones": 5,
    "equiposDeclarados": 8,
    "equiposRecibidos": 4,
    "kgRecolectados": 8.8
  },
  "campanas": [
    {
      "id": 2,
      "nombre": "Dale otra vida a tu celular",
      "estado": "ACTIVA",
      "fechaInicio": "2026-11-10",
      "fechaFin": "2026-12-15",
      "metaEquipos": 10,
      "avanceMeta": 40,
      "participaciones": 5,
      "equiposDeclarados": 8,
      "equiposRecibidos": 4,
      "equiposReasignados": 1,
      "kgRecolectados": 8.8
    }
  ]
}
```

Las campañas se ordenan por fecha de inicio descendente. `avanceMeta` es el porcentaje entero redondeado de recibidos/meta; puede superar 100 y es `null` si no hay una meta utilizable. `kgRecolectados` suma pesos promedio de las categorías de equipos registrados, no los equipos declarados por participantes. Las fechas del ejemplo se muestran como fechas de negocio; la serialización de valores `date` de consultas SQL crudas depende del parser de PostgreSQL utilizado.

**Códigos:** `200`; `401`, `403`, `429`, `500`. Sin campañas devuelve `campanas: []` y totales cero.

#### 3.3. GET `/api/indicadores/reutilizacion` — reparación y reutilización

**Rol:** gestión ambiental. **Cuerpo:** ninguno.

**Respuesta 200, con la serie mensual abreviada a un elemento:**

```json
{
  "reparaciones": {
    "total": 3,
    "exitosas": 2,
    "fallidas": 1,
    "costoTotal": 1300,
    "costoPromedio": 433.33,
    "ahorroReparaciones": 13350,
    "tasaExito": 67
  },
  "reutilizacion": {
    "reasignados": 3,
    "tasaReutilizacion": 30,
    "ahorroReutilizacionDirecta": 28000,
    "ahorroTotal": 41350,
    "kgResiduoEvitado": 8.8
  },
  "porCategoria": [
    {
      "categoria": "Laptop",
      "tipo": "COMPUTO",
      "registrados": 4,
      "reasignados": 2,
      "reparados": 1,
      "entregados": 1
    },
    {
      "categoria": "Monitor",
      "tipo": "PERIFERICO",
      "registrados": 6,
      "reasignados": 1,
      "reparados": 1,
      "entregados": 0
    }
  ],
  "destinos": [{ "destino": "Laboratorio de redes", "total": 3 }],
  "serieMensual": [
    { "mes": "2026-11", "registrados": 4, "reasignados": 2, "reparaciones": 1, "entregados": 1 }
  ]
}
```

La respuesta real incluye **doce meses**, ordenados del más antiguo al actual, también cuando los contadores son cero. Los registros mensuales usan `fechaIngreso` para ingresos y `creadoEn` de movimientos para reasignaciones, reparaciones y entregas. El contador mensual `reparaciones` incluye movimientos de reparación, sin filtrar únicamente resultados exitosos.

`tasaExito` es el porcentaje entero redondeado de reparaciones exitosas/total de reparaciones; `tasaReutilizacion`, el de equipos actualmente reasignados/total de equipos. Si el denominador es cero, la tasa es cero. `destinos` muestra hasta seis destinos de reasignación ordenados por total descendente.

`porCategoria.reparados` cuenta equipos distintos con reparación exitosa. Los otros contadores de esa consulta usan `COUNT(e.id)` después del join con reparaciones exitosas; si un equipo tiene varias reparaciones exitosas pueden contarlo más de una vez. El endpoint documenta la consulta actual; no debe suponerse que todos esos contadores sean de equipos distintos.

**Códigos:** `200`; `401`, `403`, `429`, `500`.

#### 3.4. GET `/api/indicadores/publico` — cifras públicas

**Rol:** público; no requiere cookie de sesión. **Cuerpo:** ninguno.

**Respuesta 200:**

```json
{
  "equiposRegistrados": 10,
  "equiposRecuperados": 4,
  "kgResiduoEvitado": 8.8,
  "campanasActivas": 1,
  "participaciones": 5
}
```

Devuelve únicamente agregados, sin nombres de usuarios o participantes, contactos ni identificadores de equipos. Las campañas activas se cuentan por su estado almacenado `ACTIVA`; la consulta no las reclasifica por fecha.

**Códigos:** `200`, `429`, `500`. No requiere autenticación ni autorización por rol. Sin registros devuelve cifras cero.

#### 3.5. Interpretación de las métricas

- El ahorro total combina el ahorro registrado en reparaciones y el valor de reposición de equipos reasignados sin reparación exitosa previa.
- Los kg de residuo evitado se estiman con pesos promedio del catálogo para equipos funcionales o reutilizables actualmente reasignados o conservados.
- El resumen de entregas documentadas exige destino `ENTREGADO_GESTOR` y la existencia de un movimiento de entrega con gestor y evidencia no nulos. La consulta no comprueba de nuevo el estado actual de verificación del gestor ni la cadena completa del tratamiento posterior.
- Otros contadores de entregas, como la serie mensual o el desglose por categoría, usan sus propias consultas; no todos aplican el filtro documental del resumen.
- Las cantidades declaradas por participantes, los equipos recibidos y las reparaciones son conceptos diferentes. Los indicadores ambientales y económicos son estimaciones del sistema, no mediciones o certificaciones externas.

### 4. Códigos HTTP y manejo de errores

#### 4.1. Catálogo de códigos

| Código | Nombre en `error` cuando corresponde | Uso en estos servicios |
|---|---|---|
| 200 | No hay objeto de error | Consultas, actualización de equipo y simulación de diagnóstico correctas. |
| 201 | No hay objeto de error | Equipo, diagnóstico, reparación o movimiento creado. |
| 400 | `BAD_REQUEST` | DTO o parámetro inválido, campo desconocido o restricción SQL `CHECK` traducida por el filtro. |
| 401 | `UNAUTHORIZED` | Falta de sesión o cuenta inexistente/inactiva en una ruta privada. |
| 403 | `FORBIDDEN` | Rol no autorizado o token CSRF ausente/inválido. |
| 404 | `NOT_FOUND` | Recurso ausente cuando la búsqueda utiliza `obtenerPorId()`, o ruta no encontrada. |
| 409 | `CONFLICT` | Serie duplicada comprobada por el servicio o errores PostgreSQL de unicidad/FK traducidos por el filtro. |
| 422 | `UNPROCESSABLE_ENTITY` | Incumplimiento de una regla de negocio. |
| 429 | `TOO_MANY_REQUESTS` | Límite de solicitudes excedido. |
| 500 | `INTERNAL_SERVER_ERROR` | Error no clasificado o fallo interno de base de datos, sesión o procesamiento. |

Ejemplo de validación:

```json
{
  "statusCode": 400,
  "error": "BAD_REQUEST",
  "message": ["Escribe la marca (máximo 80 caracteres)."],
  "path": "/api/equipos",
  "timestamp": "2026-11-25T18:00:00.000Z"
}
```

Ejemplo de equipo inexistente consultado por detalle:

```json
{
  "statusCode": 404,
  "error": "NOT_FOUND",
  "message": "El equipo con id 9999 no existe.",
  "path": "/api/equipos/9999",
  "timestamp": "2026-11-25T18:00:00.000Z"
}
```

#### 4.2. Traducción de errores del backend

El [filtro global](../backend/src/common/filters/http-exception.filter.ts) conserva el estado de las excepciones HTTP de NestJS. Traduce `ReglaNegocioError` a 422 y estos errores de PostgreSQL:

| Código PostgreSQL | HTTP | Mensaje |
|---|---|---|
| `23505` | 409 | `Ya existe un registro con esos datos.` |
| `23503` | 409 | `El registro está relacionado con otros datos y no puede modificarse así.` |
| `23514` | 400 | `Los datos no cumplen las reglas de integridad.` |

Los errores no clasificados responden `500` con `Ocurrió un error inesperado. Intenta de nuevo.`. El filtro registra detalles de errores 5xx en el servidor y no incluye SQL, stack trace o credenciales en el objeto JSON de respuesta. Los posibles errores de infraestructura externa al backend, por ejemplo un proxy no disponible, quedan fuera de este formato.

#### 4.3. Consideraciones del comportamiento actual

**Equipo inexistente en escrituras con bloqueo.** `EquiposRepository.obtenerParaActualizar()` utiliza `getOneOrFail()`. Cuando no hay fila, TypeORM lanza un error de entidad inexistente que el filtro actual no convierte en 404. Por eso, registrar diagnóstico, reparación o decisión sobre un id entero inexistente puede responder 500. Las consultas por detalle, la simulación, el historial y la actualización ordinaria sí pasan por búsquedas con manejo explícito de 404.

**Operaciones confirmadas antes del historial.** El registro de equipo, diagnóstico y resultado de reparación confirma su transacción principal antes de emitir el evento de historial. Un fallo del listener puede producir un error HTTP aunque el registro principal ya exista. Antes de repetir una escritura tras un fallo de respuesta, consultar el detalle para evitar duplicar operaciones. Las decisiones de destino guardan el cambio y su movimiento en una sola transacción.

**Orden de validación.** Se ejecutan primero los guards globales de límite, sesión, rol y CSRF, y después los pipes de parámetros y DTO. Una petición con varios problemas puede recibir primero 401 o 403 aunque también tenga un cuerpo inválido.

### 5. Archivos de referencia

- Contratos del inventario: [equipos.controller.ts](../backend/src/modules/equipos/equipos.controller.ts), [equipos.dto.ts](../backend/src/modules/equipos/equipos.dto.ts) y [equipos.service.ts](../backend/src/modules/equipos/equipos.service.ts).
- Diagnósticos: [diagnosticos.controller.ts](../backend/src/modules/diagnosticos/diagnosticos.controller.ts) y [diagnosticos.dto.ts](../backend/src/modules/diagnosticos/diagnosticos.dto.ts).
- Reparaciones: [reparaciones.controller.ts](../backend/src/modules/reparaciones/reparaciones.controller.ts) y [reparaciones.dto.ts](../backend/src/modules/reparaciones/reparaciones.dto.ts).
- Historial y decisiones: [movimientos.controller.ts](../backend/src/modules/movimientos/movimientos.controller.ts) y [movimientos.dto.ts](../backend/src/modules/movimientos/movimientos.dto.ts).
- Indicadores: [indicadores.controller.ts](../backend/src/modules/indicadores/indicadores.controller.ts), [indicadores.service.ts](../backend/src/modules/indicadores/indicadores.service.ts) e [indicadores.repository.ts](../backend/src/modules/indicadores/indicadores.repository.ts).
- Configuración y autenticación: [app.setup.ts](../backend/src/app.setup.ts), [app.module.ts](../backend/src/app.module.ts), [auth.controller.ts](../backend/src/modules/auth/auth.controller.ts) y [csrf.guard.ts](../backend/src/common/guards/csrf.guard.ts).
- Pruebas existentes de los servicios: [servicios-web.e2e-spec.ts](../backend/test/servicios-web.e2e-spec.ts) y [evidencia-pruebas-e2e.txt](evidencia-pruebas-e2e.txt). La evidencia corresponde a una ejecución previa del proyecto; no se ejecutaron nuevas pruebas HTTP para redactar este documento.
