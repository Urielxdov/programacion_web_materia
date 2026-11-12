# Arquitectura — E-Waste 360°

## Capas (arquitectura en capas + MVC)

| Capa | Carpeta | Responsabilidad | Nunca hace |
|---|---|---|---|
| **Presentación** | `app/Views`, `public/assets` | Plantillas HTML5 responsivas (Bootstrap 5) y JavaScript que manipula el DOM y consume la API. | Consultar la base de datos ni aplicar reglas de negocio. |
| **Aplicación** | `app/Core`, `app/Middleware`, `app/Controllers`, `config/rutas.php` | Front Controller, enrutamiento, sesión, CSRF, autenticación y autorización. Los controladores reciben la petición, llaman a un servicio y eligen la vista o el JSON. | SQL o reglas del dominio. |
| **Negocio** | `app/Services`, `app/Models`, `app/Factories`, `app/Strategies` | Casos de uso (registrar, diagnosticar, validar…), entidades con comportamiento, validación y transacciones. | Saber cómo se guarda la información ni cómo se muestra. |
| **Persistencia** | `app/Repositories`, `app/Core/Database.php`, `database/` | SQL parametrizado (PDO) que devuelve objetos del dominio. La misma clase funciona con MySQL y PostgreSQL. | Decidir reglas de negocio. |

**MVC:**
- **Modelo:** `app/Models` (entidades), junto con los servicios y repositorios.
- **Vista:** `app/Views`.
- **Controlador:** `app/Controllers` (web) y `app/Controllers/Api` (servicios REST). La API y el sitio web reutilizan los mismos servicios.

Recorrido de una petición:

`public/index.php` → `Aplicacion` (CSRF, manejo de errores, cabeceras de seguridad) → `Router` → middlewares (`auth`, `permiso:xxx`)
→ controlador → servicio → repositorio → `Database` → vista o JSON.

## Patrones de diseño implementados

| Patrón | Dónde | Problema que resuelve |
|---|---|---|
| **Repository** | `app/Repositories/*Repository.php` | Todo el SQL de cada entidad está en un solo lugar. Los servicios piden objetos (`porId`, `buscar`, `insertar`) sin conocer el SQL ni el motor de base de datos. Por eso la app corre sin cambios con MySQL (local) y PostgreSQL (Neon). |
| **Strategy** | `app/Strategies` | Clasificar el destino de un equipo depende de criterios que cambian (umbral de costo, vida útil, riesgo). Cada destino es una estrategia (`EstrategiaReutilizacion`, `EstrategiaConservacion`, `EstrategiaReparacion`, `EstrategiaGestionEspecializada`) y `ClasificadorDestino` las evalúa en orden. Cambiar la política no requiere tocar los controladores ni los servicios: el administrador ajusta el umbral en *Configuración*. |
| **Factory Method** | `app/Factories/EquipoFactory.php` | Cada categoría de dispositivo se comporta distinto (borrado de datos, materiales peligrosos, recomendaciones de entrega). La fábrica crea la subclase correcta de `Equipo` a partir de la categoría, así que el resto del código trabaja con `Equipo` sin usar `if`/`switch` por tipo. |
| **Singleton** | `app/Core/Database.php` | Una sola conexión PDO por petición, compartida por todos los repositorios. |
| **Front Controller** | `public/index.php` + `app/Core/Aplicacion.php` | Un único punto de entrada aplica la seguridad (CSRF, cabeceras, errores) a todas las rutas. |
| **Middleware / cadena de filtros** | `app/Middleware` | La autenticación y la autorización se declaran por ruta, sin repetir código en cada controlador. |

## Programación orientada a objetos

- **Encapsulamiento:**
  - `Equipo` tiene sus atributos privados. Su estado solo cambia con métodos que validan reglas (`aplicarMovimiento`, `asignarClasificacion`).
  - `Usuario` nunca expone el hash de la contraseña en `toArray()`.
- **Herencia y polimorfismo:**
  - `Equipo` es abstracta y tiene 9 subclases (`Laptop`, `Bateria`, `Monitor`…). Cada una redefine `requiereBorradoDatos()`, `contieneMaterialesPeligrosos()` y `recomendacionesEntrega()`.
  - Los controladores heredan de `Controller` y los repositorios de `Repositorio`.
- **Interfaces:** `EstrategiaDestino` y `Middleware`.
- **Objetos de valor inmutables:** `EvaluacionTecnica`, `Recomendacion`, y las entidades con propiedades `readonly` (`Diagnostico`, `Movimiento`, `Campana`…).
- **Inyección de dependencias:** los servicios reciben sus repositorios por el constructor (con valores por defecto), lo que permite probarlos de forma aislada.

## Seguridad

| Medida | Implementación |
|---|---|
| Contraseñas | `password_hash` (bcrypt) y `password_verify`; se vuelven a cifrar si cambia el algoritmo (`password_needs_rehash`). |
| Fuerza bruta | La cuenta se bloquea 15 minutos tras 5 intentos fallidos. Se responde siempre "Usuario o contraseña incorrectos" y se tarda lo mismo exista o no el usuario. |
| Sesiones | Cookie `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS. Se usa `session.use_strict_mode` y se regenera el id al iniciar sesión. Expira tras 30 minutos de inactividad y en cada petición se verifica que la cuenta siga activa. |
| CSRF | Token sincronizado en todos los formularios (`_token`) y en la cabecera `X-CSRF-Token` para fetch y la API. Si no es válido, responde 419. |
| Autorización | Matriz de permisos por rol (`config/permisos.php`) validada en el servidor con el middleware `TienePermiso` y dentro de los servicios (por ejemplo, el capturista no puede reclasificar). |
| Inyección SQL | Todas las consultas son preparadas con parámetros (PDO con `EMULATE_PREPARES = false`). |
| XSS | Todo dato se imprime con `e()` (`htmlspecialchars`). El JS crea nodos con `textContent`, nunca con `innerHTML`. Además hay una cabecera **Content-Security-Policy**. |
| Validación | En el cliente (HTML5 + JS) y siempre en el servidor (`Validator` + reglas de dominio). |
| Errores | Las excepciones se registran en el log; el usuario ve un mensaje genérico, sin rutas ni SQL (salvo con `APP_DEBUG=true` en local). |
| Cabeceras | `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` y, bajo HTTPS, `Strict-Transport-Security`. |
| Datos personales | La participación pública solo pide nombre y, opcionalmente, correo, con aviso de privacidad. En el panel el correo se muestra parcialmente oculto. |
| CSV | Se neutralizan las celdas que empiezan con `= + - @` para evitar la inyección de fórmulas en Excel. |
| HTTPS | Render lo provee. `TRUST_PROXY=true` hace que la app reconozca `X-Forwarded-Proto` para activar `Secure` y HSTS. |

## Responsabilidad ambiental en el diseño

- **Jerarquía de residuos:** las estrategias intentan primero reutilizar, conservar y reparar; la gestión especializada es el último recurso.
- **Trazabilidad:** cada equipo tiene un historial de movimientos que no se borra. Solo los movimientos validados cambian su estado.
- **Destinos sin verificar:** si se entrega un equipo a un gestor sin verificar, la entrega queda registrada pero marcada aparte y **no cuenta como destino documentado**.
- **Ahorro estimado:** solo se calcula cuando hay un diagnóstico con valor de reemplazo; no se inventan cifras.
