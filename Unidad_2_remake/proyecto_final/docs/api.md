# Servicios web (API REST) — E-Waste 360°

La aplicación expone **dos servicios web** que responden JSON, más un servicio auxiliar para iniciar sesión.

| Servicio | Base | Uso dentro de la app |
|---|---|---|
| 1. API de equipos | `/api/equipos` | Búsqueda en vivo del inventario y recomendación de destino en el formulario de diagnóstico. |
| 2. API de indicadores | `/api/indicadores` | Tablero, página de indicadores y contador de pendientes del menú. |
| Autenticación | `/api/auth` | Clientes externos (Postman, pruebas automatizadas). |

Colección de Postman lista para importar: [`docs/api/E-Waste360.postman_collection.json`](api/E-Waste360.postman_collection.json).

## Autenticación, autorización y CSRF

- La API usa la misma **sesión** que el sitio: una cookie `HttpOnly`, `SameSite=Lax` y `Secure` cuando hay HTTPS.
- Desde fuera del navegador primero se llama a `POST /api/auth/login`, que devuelve la cookie y un `csrf_token`.
- Toda petición `POST` o `PATCH` debe enviar la cabecera `X-CSRF-Token: <token>`. La única excepción es el login.
- Cada ruta exige un **permiso por rol**, y el servidor lo valida aunque la interfaz oculte el botón:

| Endpoint | Capturista | Responsable | Admin |
|---|:-:|:-:|:-:|
| `GET /api/equipos`, `GET /api/equipos/{id}` | ✔ | ✔ | ✔ |
| `POST /api/equipos`, `PATCH /api/equipos/{id}` | ✔ (no puede cambiar la clasificación) | ✔ | ✔ |
| `GET /api/equipos/{id}/recomendacion` | ✖ 403 | ✔ | ✔ |
| `GET /api/indicadores` | ✔ | ✔ | ✔ |
| `GET /api/indicadores/campanas`, `/reutilizacion` | ✖ 403 | ✔ | ✔ |

## Formato de errores

```json
{ "error": { "codigo": 422, "mensaje": "Revisa los datos marcados en el formulario.",
             "detalles": { "marca": "El campo marca es obligatorio." } } }
```

| Código | Significado |
|---|---|
| 200 / 201 | Correcto / recurso creado (incluye la cabecera `Location`). |
| 400 | El cuerpo no es JSON válido. |
| 401 | No hay sesión, o el usuario o la contraseña son incorrectos (mensaje genérico). |
| 403 | El rol no tiene permiso. |
| 404 | El recurso no existe. |
| 405 | Método no permitido en esa ruta. |
| 409 | Conflicto con el estado actual (por ejemplo, el equipo ya fue entregado o hay un movimiento pendiente). |
| 419 | Falta el token CSRF o no es válido. |
| 422 | Error de validación; `detalles` indica el problema de cada campo. |
| 429 | Cuenta bloqueada temporalmente tras 5 intentos fallidos. |
| 500 | Error interno; no se exponen detalles del servidor. |

---

## Autenticación

### `POST /api/auth/login`
```bash
curl -c cookies.txt -H "Content-Type: application/json" \
     -d '{"usuario":"responsable","password":"TU_CONTRASEÑA"}' https://TU-APP.onrender.com/api/auth/login
```
```json
{ "usuario": { "id": 2, "nombre": "Responsable ambiental (demo)", "usuario": "responsable",
               "rol": "responsable", "rol_texto": "Responsable ambiental", "activo": true },
  "csrf_token": "9f2c…" }
```

### `POST /api/auth/logout`
Requiere `X-CSRF-Token`. Responde `{"mensaje": "Sesión cerrada."}`.

---

## Servicio 1: API de equipos

### `GET /api/equipos`
Parámetros opcionales (todos combinables):

| Parámetro | Descripción |
|---|---|
| `q` | Busca en código, marca, modelo y número de serie (sin distinguir mayúsculas). |
| `clasificacion` | `pendiente`, `funcional`, `reparable`, `reutilizable` o `gestion_especializada`. |
| `estado` | `en_inventario`, `reasignado`, `conservado` o `entregado_gestor`. |
| `categoria_id`, `campana_id` | Filtran por categoría o por campaña de origen. |
| `orden` | `recientes` (por defecto), `antiguos`, `codigo` o `marca`. |
| `pagina`, `por_pagina` | Paginación (por_pagina va de 1 a 100; por defecto 12). |

```bash
curl -b cookies.txt "https://TU-APP.onrender.com/api/equipos?q=dell&clasificacion=reutilizable"
```
```json
{ "data": [ { "id": 1, "codigo": "EW-2026-0001", "tipo": "laptop",
              "categoria": { "id": 2, "nombre": "Laptop", "tipo": "laptop", "vida_util_anios": 4 },
              "marca": "Dell", "modelo": "Latitude 5490", "clasificacion": "reutilizable",
              "estado": "reasignado", "requiere_borrado_datos": true, "materiales_peligrosos": true,
              "movimientos_permitidos": ["reparacion", "reasignacion", "conservacion", "entrega_gestor"], "…": "…" } ],
  "meta": { "total": 1, "pagina": 1, "paginas": 1, "por_pagina": 12 } }
```

### `GET /api/equipos/{id}`
Devuelve los datos del equipo, sus `diagnosticos`, sus `movimientos` (historial) y sus `recomendaciones_entrega`.
Las recomendaciones dependen de la subclase: una batería no recibe las mismas que un teclado.

### `POST /api/equipos`
```bash
curl -b cookies.txt -H "Content-Type: application/json" -H "X-CSRF-Token: $TOKEN" \
     -d '{"categoria_id":2,"marca":"HP","modelo":"ProBook 440","anio_fabricacion":2020,
          "fecha_ingreso":"2026-11-10","condicion":"falla_parcial","peso_kg":1.7}' \
     https://TU-APP.onrender.com/api/equipos
```
Responde **201** con el equipo y `Location: /api/equipos/{id}`. El sistema asigna el código `EW-AAAA-NNNN` y
registra el movimiento de ingreso.

| Campo | Regla |
|---|---|
| `categoria_id` | Obligatorio; debe ser una categoría activa. |
| `marca`, `modelo` | Obligatorios; máximo 80 caracteres. |
| `fecha_ingreso` | Obligatoria (AAAA-MM-DD); no puede ser una fecha futura. |
| `condicion` | `funciona`, `falla_parcial`, `no_funciona` o `desconocida`. |
| `numero_serie`, `ubicacion`, `observaciones` | Opcionales (80, 120 y 1000 caracteres como máximo). |
| `anio_fabricacion` | Opcional; entre 1970 y el año actual. |
| `peso_kg` | Opcional; número entre 0 y 5000. |
| `campana_id` | Opcional; una campaña publicada. |
| `clasificacion` | Solo la toman en cuenta el responsable y el admin. Para el capturista siempre queda `pendiente`. |

### `PATCH /api/equipos/{id}`
Actualiza solo los campos que se envían. Las reglas de validación son las mismas que en el `POST`.
- Si se envía un campo que no existe o que está protegido (`codigo`, `estado`, `id`…), responde **422**.
- Si un capturista intenta cambiar la clasificación, responde **403**.
- Si el equipo ya fue entregado a un gestor, responde **409**, porque su registro está cerrado.

### `GET /api/equipos/{id}/recomendacion`
Calcula con el patrón Strategy el destino recomendado. No guarda nada.

| Parámetro | Valores |
|---|---|
| `estado_funcional` | `funciona`, `falla_parcial` o `no_funciona` (obligatorio) |
| `costo_reparacion`, `valor_reemplazo` | Números mayores o iguales a 0 (obligatorios) |
| `riesgo_seguridad` | `1` o `0` |

```json
{ "data": { "decision": "reparar", "decision_texto": "Reparar", "clasificacion": "reparable",
            "justificacion": "Repararlo cuesta el 16.7% del valor de un equipo nuevo (umbral: 60%)…",
            "estrategia": "EstrategiaReparacion" } }
```

---

## Servicio 2: API de indicadores

Todas las cifras cuentan solo los movimientos **validados**. Las entregas a gestores sin verificar se reportan aparte
y no se suman como destino documentado.

### `GET /api/indicadores`
Devuelve:
- totales por clasificación, estado y categoría;
- equipos reparados, reutilizados y conservados;
- tasa de aprovechamiento;
- entregas a gestores (verificadas y no verificadas, en kg);
- costo de reparaciones y ahorro estimado;
- campañas activas y participaciones;
- pendientes de diagnóstico y de validación;
- ingresos al inventario de los últimos 12 meses.

### `GET /api/indicadores/campanas` (también `/api/indicadores/campañas`)
Por cada campaña publicada: participantes, asistentes, equipos comprometidos y recibidos, kg recibidos y meta. Incluye los totales.

### `GET /api/indicadores/reutilizacion` (también `/api/indicadores/reutilización`)
Devuelve:
- equipos reparados, reutilizados y conservados, y la tasa de aprovechamiento;
- costo total y promedio de reparación, y ahorro estimado;
- serie mensual de reparaciones y reasignaciones;
- aprovechamiento por categoría.

**Cómo se calcula el ahorro estimado:** al validar una reparación o una reasignación, se resta el costo real del valor
de reemplazo registrado en el último diagnóstico del equipo. Si el equipo no tiene diagnóstico, no se estima ahorro
para no inventar cifras.

---

## Pruebas de los servicios

`tests/integracion.php` prueba ambos servicios con peticiones HTTP reales: códigos de respuesta, validaciones,
permisos por rol, CSRF y cálculos. Los resultados están en [`pruebas.md`](pruebas.md).
