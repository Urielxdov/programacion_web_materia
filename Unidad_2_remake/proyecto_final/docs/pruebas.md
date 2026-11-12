# Pruebas — E-Waste 360°

No requieren Composer ni librerías externas. Hay dos niveles:

| Archivo | Tipo | Qué cubre |
|---|---|---|
| `tests/unitarias.php` | Unitarias (sin base ni servidor) | Factory y polimorfismo, transiciones de estado, todas las estrategias de destino, Validator y matriz de permisos. |
| `tests/integracion.php` | Integración HTTP (servidor + base de datos) | Páginas públicas, cabeceras de seguridad, login, CSRF, ambos servicios REST, validación 422, permisos 401/403, flujo diagnóstico → movimiento → validación, CSV, administración y participación pública. |

## Cómo ejecutarlas

```bash
# Unitarias
php tests/unitarias.php

# Integración: base recién instalada con datos demo y la app corriendo
php database/instalar.php --reiniciar          # en local; borra y recrea los datos
php -S 127.0.0.1:8000 -t public public/router.php
SEED_PASSWORD=TuContraseña php tests/integracion.php http://127.0.0.1:8000
```

En PowerShell, la variable se define así: `$env:SEED_PASSWORD="TuContraseña"; php tests/integracion.php http://127.0.0.1:8000`.

## Resultados obtenidos (10/11/2026)

| Entorno | Unitarias | Integración |
|---|---|---|
| PHP 8.5 + MariaDB 10.11 (XAMPP) | 17/17 ✔ | 21/21 ✔ |
| PHP 8.5 + PostgreSQL 17 (mismo motor que Neon) | — | 21/21 ✔ |

Con los datos demo, ambos motores dieron exactamente los mismos indicadores (13 equipos, ahorro estimado
$23,150.00 y 3.6 kg entregados a gestores verificados).

También se revisaron las 29 pantallas del sistema con cada rol:
- Todas responden **200** al rol autorizado.
- Responden **403** a los roles no autorizados (por ejemplo, el capturista no puede abrir reportes ni la administración).
- Ninguna muestra avisos ni errores de PHP.

> **Pendiente del equipo:** volver a correr `tests/integracion.php` contra la URL de Render una vez desplegada y
> agregar aquí el resultado y una captura como evidencia.

## Casos destacados

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | POST sin token CSRF | 419 |
| 2 | Capturista envía `clasificacion: funcional` al crear | Se guarda como `pendiente` |
| 3 | Capturista hace PATCH de `clasificacion` | 403 |
| 4 | PATCH de un campo protegido (`estado`) | 422 |
| 5 | Diagnóstico con riesgo de seguridad y decisión "reutilizar" | Se rechaza; la clasificación no cambia |
| 6 | Reasignar una laptop sin confirmar el borrado de datos | Se rechaza; no se crea el movimiento |
| 7 | Movimiento registrado por un capturista | Queda pendiente; el estado del equipo no cambia |
| 8 | El capturista intenta validar | 403 |
| 9 | El responsable valida una reparación de $750 (valor nuevo $9,000) | Equipo funcional; ahorro de $8,250 |
| 10 | El admin intenta quitarse su propio rol | Se rechaza |
| 11 | Usuario inexistente y contraseña incorrecta | El mismo mensaje genérico (401) |
| 12 | Peticiones después de cerrar sesión | 401 |
