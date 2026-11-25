# Servicios web (API REST)

- Base: `/api`. Respuestas en **JSON**. Documentación interactiva con **Swagger** en `/api/docs`.
- **Autenticación:** cookie de sesión `ew.sid` (HttpOnly). Se obtiene con `POST /api/auth/login`.
- **CSRF:** toda petición `POST`/`PATCH` debe incluir el encabezado `X-CSRF-Token`, que se obtiene en
  `GET /api/auth/csrf` y se renueva al iniciar sesión (viene en la respuesta del login).
- **Validación:** los cuerpos se validan con DTOs. Los campos desconocidos se rechazan (`forbidNonWhitelisted`).
- **Errores:** siempre con el mismo formato y sin detalles internos del servidor:

```json
{ "statusCode": 422, "error": "UNPROCESSABLE_ENTITY",
  "message": "El gestor \"X\" no está verificado. No es posible documentar la entrega.",
  "path": "/api/equipos/12/movimientos", "timestamp": "2026-11-24T03:05:02.105Z" }
```

| Código | Cuándo |
|---|---|
| 200 / 201 | Consulta correcta / recurso creado |
| 400 | Datos inválidos (con la lista de mensajes por campo) |
| 401 | Sin sesión o credenciales incorrectas |
| 403 | Rol sin permiso o token CSRF inválido |
| 404 | Recurso inexistente |
| 409 | Duplicado (correo, número de serie, nombre de categoría) |
| 422 | Regla de negocio violada (p. ej. reasignar un equipo reparable) |
| 429 | Demasiados intentos (login: 8 por minuto; participación: 5 por minuto) |

## Servicio 1 · API de equipos

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/equipos?q&categoriaId&condicion&estadoDestino&campanaId&orden&pagina&porPagina` | Todos los autenticados | Búsqueda paginada |
| GET | `/equipos/:id` | Todos | Detalle con historial, `accionesDisponibles` y `recomendacionesEntrega` |
| POST | `/equipos` | Todos | Registra un equipo (Factory) y genera su folio |
| PATCH | `/equipos/:id` | Todos | Actualiza marca, modelo, serie, fecha, ubicación y observaciones |
| GET | `/equipos/:id/movimientos` | Todos | Historial de trazabilidad |
| POST | `/equipos/:id/diagnosticos/simular` | Responsable, Admin | Vista previa de la recomendación (Strategy), sin guardar |
| POST | `/equipos/:id/diagnosticos` | Responsable, Admin | Registra el diagnóstico y actualiza la condición |
| POST | `/equipos/:id/reparaciones` | Responsable, Admin | Resultado de la reparación y ahorro estimado |
| POST | `/equipos/:id/movimientos` | Responsable, Admin | Decisión: `ENVIO_REPARACION`, `REASIGNACION`, `CONSERVACION`, `ENTREGA_GESTOR` |
| GET | `/movimientos/recientes?limite=8` | Todos | Actividad reciente |

Ejemplo, registrar un equipo:

```http
POST /api/equipos
Content-Type: application/json
X-CSRF-Token: <token>

{ "categoriaId": 2, "marca": "Dell", "modelo": "Latitude 5490",
  "numeroSerie": "CN-0X1Y2Z", "fechaIngreso": "2026-11-20", "ubicacion": "Almacén B" }
```

```json
201 Created
{ "id": 47, "folio": "EW-2026-0047", "condicion": "PENDIENTE", "estadoDestino": "EN_INVENTARIO",
  "requiereBorradoDatos": true, "accionesDisponibles": ["DIAGNOSTICAR", "CONSERVAR"],
  "movimientos": [{ "tipo": "INGRESO", "descripcion": "Ingreso del equipo EW-2026-0047 al inventario." }], "…": "…" }
```

Ejemplo, entregar a un gestor:

```json
POST /api/equipos/48/movimientos
{ "tipo": "ENTREGA_GESTOR", "gestorId": 1, "evidencia": "Manifiesto ME-501", "datosBorrados": true }
```

## Servicio 2 · API de indicadores

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/indicadores` | Todos los autenticados | Totales por condición y destino, recuperados, entregas documentadas, costo, ahorro y kg de residuo evitado |
| GET | `/indicadores/campanas` | Responsable, Admin | Resultados por campaña: participaciones, declarados, recibidos, kg y avance de meta |
| GET | `/indicadores/reutilizacion` | Responsable, Admin | Reparaciones (tasa de éxito, costos, ahorro), reutilización, resultados por categoría, destinos y serie de 12 meses |
| GET | `/indicadores/publico` | Público | Cifras agregadas y anónimas para la página de inicio |

## Otros recursos

| Método | Ruta | Roles |
|---|---|---|
| GET | `/auth/csrf` | Público |
| POST | `/auth/login` · `/auth/logout` | Público · Autenticado |
| GET | `/auth/me` | Público (devuelve `null` sin sesión) |
| GET / POST / PATCH | `/usuarios[/:id]` | Admin |
| GET | `/catalogos/categorias` | Público |
| POST / PATCH | `/catalogos/categorias[/:id]` | Admin |
| GET / POST / PATCH | `/catalogos/gestores[/:id]` | Responsable, Admin |
| POST | `/catalogos/gestores/:id/verificar` · `/revocar` | Admin |
| GET | `/campanas[/:id]` | Público |
| POST / PATCH | `/campanas[/:id]` | Responsable, Admin |
| POST | `/campanas/:id/participaciones` | Público (limitado por IP) |
| GET | `/campanas/:id/participaciones` | Responsable, Admin |

## Pruebas

`backend/test/servicios-web.e2e-spec.ts` prueba los dos servicios con peticiones HTTP reales contra PostgreSQL
(20 casos): autenticación, CSRF, cookie HttpOnly, validación (400), lista blanca de campos, reglas de la Factory
(422), duplicados (409), búsqueda y paginación, detalle y 404, autorización por rol (403), el ciclo completo
diagnóstico → reparación → reasignación con su historial, la entrega solo a gestores verificados, la revocación inmediata de cuentas desactivadas y la coherencia
de los indicadores. Resultado: [`evidencia-pruebas-e2e.txt`](evidencia-pruebas-e2e.txt).
