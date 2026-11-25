# Documentación técnica de E-Waste 360°

## Seguridad

E-Waste 360° aplica controles de seguridad en la API, el manejo de credenciales, las sesiones, la validación y la exposición de datos. Los controles del navegador complementan las comprobaciones del servidor, que decide si una solicitud puede ejecutarse.

Este apartado describe la implementación encontrada en el repositorio. La configuración de producción se distingue de una comprobación real del despliegue: no se verificaron aquí certificados, cabeceras ni cookies de una instancia pública en ejecución.

### 1. Autenticación y manejo de sesiones

#### 1.1. Verificación de credenciales

La autenticación utiliza correo y contraseña mediante `POST /api/auth/login`. `AuthService` normaliza el correo, recupera el usuario con su hash y delega la verificación en `PasswordService`.

Si el correo no existe o la contraseña es incorrecta, responde **401** con el mismo mensaje: `Correo o contraseña incorrectos.`. Para un correo inexistente también ejecuta una verificación de hash contra una contraseña aleatoria generada al construir el servicio. Esto reduce diferencias evidentes de trabajo entre ambos casos, pero no constituye una garantía de tiempos de respuesta idénticos.

Si la contraseña es correcta pero la cuenta está desactivada, responde 401 con un mensaje específico sobre la desactivación. No existe un registro público de cuentas de personal; las crea el administrador.

**Referencias:** [auth.service.ts](../backend/src/modules/auth/auth.service.ts), [auth.controller.ts](../backend/src/modules/auth/auth.controller.ts) y [auth.dto.ts](../backend/src/modules/auth/auth.dto.ts).

#### 1.2. Creación y conservación de la sesión

Se utiliza `express-session` con `connect-pg-simple`. La cookie **`ew.sid`** identifica una sesión cuyos datos se guardan en la tabla PostgreSQL `sesiones`; la API no utiliza JWT ni un token Bearer para autenticar estos flujos.

Después de verificar las credenciales, el controlador regenera el identificador de sesión para reducir el riesgo de fijación de sesión:

```typescript
const usuario = await this.auth.autenticar(dto);
await promesa((cb) => req.session.regenerate(cb));
req.session.usuario = usuario;
await promesa((cb) => req.session.save(cb));
return { usuario, csrfToken: this.csrf.generar(req, res) };
```

| Configuración | Valor implementado | Propósito |
|---|---|---|
| Almacén | PostgreSQL, tabla `sesiones` | Conservar sesiones fuera de la memoria del proceso del backend. |
| `resave` | `false` | Evitar forzar el guardado de sesiones sin cambios. |
| `saveUninitialized` | `false` | Evitar guardar sesiones nuevas que no se han inicializado. |
| `rolling` | `true` | Renovar la cookie durante el uso de la sesión. |
| `cookie.maxAge` | 28 800 000 ms | Configurar ocho horas de inactividad, no un límite absoluto de ocho horas desde el login. |
| `SESSION_SECRET` | Variable de entorno | Secreto utilizado por el middleware para firmar la cookie de sesión. |

Persistir sesiones permite conservarlas tras reinicios del proceso si el almacén, los secretos y las cookies siguen siendo válidos. La persistencia no sustituye las comprobaciones de vigencia ni protege por sí misma frente al robo de una sesión.

En cada petición privada, `SessionAuthGuard` consulta al usuario y comprueba que siga existiendo y activo. Actualiza los datos de sesión con el rol vigente; si la cuenta ya no es válida, destruye la sesión y devuelve 401. El cambio de rol se utiliza en esa misma petición para evaluar la autorización.

`GET /api/auth/me` devuelve el usuario actualizado o `{"usuario":null}` cuando no hay sesión válida. `POST /api/auth/logout` destruye la sesión en el almacén y solicita al navegador eliminar `ew.sid`.

En el frontend, el cliente envía las cookies mediante `credentials: 'include'`. Los hooks de login y logout limpian la caché de TanStack Query; al cerrar sesión también eliminan el token CSRF guardado en memoria. Limpiar la interfaz no sustituye la destrucción de la sesión en el servidor si la solicitud de logout falla.

El cambio de contraseña recalcula el hash, pero el código no invalida de forma general todas las sesiones existentes de esa cuenta. La revocación implementada en cada solicitud privada depende de la existencia y el estado activo del usuario.

**Referencias:** [app.setup.ts](../backend/src/app.setup.ts), [session-auth.guard.ts](../backend/src/common/guards/session-auth.guard.ts), [hooks.ts](../frontend/src/api/hooks.ts) y [cliente.ts](../frontend/src/api/cliente.ts).

### 2. Hash de contraseñas

`PasswordService` concentra las operaciones de hash y verificación utilizando **Argon2id**:

```typescript
hash(plano: string): Promise<string> {
  return argon2.hash(plano, { type: argon2.argon2id });
}

async verificar(hash: string, plano: string): Promise<boolean> {
  try {
    return await argon2.verify(hash, plano);
  } catch {
    return false;
  }
}
```

El proyecto especifica el tipo Argon2id; no fija explícitamente parámetros de memoria, iteraciones o paralelismo, por lo que utiliza los valores de la dependencia instalada. Tampoco proporciona una sal manual al llamar a `hash()`; su generación y codificación se delegan a la biblioteca.

La creación y actualización de cuentas pasan la contraseña por este servicio antes de asignar `passwordHash`. La contraseña no se almacena como texto plano ni se recupera descifrando el hash: el inicio de sesión verifica la contraseña presentada contra el valor almacenado.

| Control | Implementación |
|---|---|
| Política al crear o cambiar contraseña | Entre 8 y 72 caracteres, al menos una letra ASCII y un dígito, mediante `PASSWORD_REGEX`. |
| Validación del login | Cadena de 1 a 72 caracteres; no exige nuevamente la política de creación antes de verificarla. |
| Selección del hash | `@Column({ select: false, length: 255 })` en `Usuario.passwordHash`. |
| Consulta de autenticación | `buscarConPasswordPorEmail()` añade explícitamente el hash con `addSelect()`. |
| Respuesta de cuentas creadas o modificadas | El servicio consulta otra vez la entidad sin seleccionar el hash. |
| Datos de sesión | `Usuario.aSesion()` devuelve solo id, nombre, email y rol. |

`select: false` controla la selección ordinaria del ORM; no vuelve privado el atributo de TypeScript ni impide que una consulta específica lo seleccione. La protección depende también de no serializar una instancia que contenga el hash seleccionado.

La limitación de solicitudes complementa la verificación: el login tiene un límite específico de **ocho solicitudes por 60 segundos**. El límite general es 200 y la participación pública tiene un límite de cinco por la misma ventana; el código desactiva estos controles cuando `NODE_ENV=test`.

**Referencias:** [password.service.ts](../backend/src/modules/usuarios/password.service.ts), [usuarios.dto.ts](../backend/src/modules/usuarios/usuarios.dto.ts), [usuarios.service.ts](../backend/src/modules/usuarios/usuarios.service.ts), [usuarios.repository.ts](../backend/src/modules/usuarios/usuarios.repository.ts), [usuario.entity.ts](../backend/src/modules/usuarios/usuario.entity.ts) y [app.module.ts](../backend/src/app.module.ts).

### 3. Autorización por roles validada en el servidor

`SessionAuthGuard` exige sesión en las rutas de controladores salvo las marcadas con `@Public()`. `RolesGuard` obtiene los roles declarados con `@Roles()` y comprueba el rol vigente del usuario. Una ruta privada sin lista de roles sigue exigiendo sesión, pero no agrega una restricción de rol específica; cerrar sesión es un ejemplo.

| Acciones | Roles autorizados |
|---|---|
| Información pública, categorías, campañas, indicadores públicos y registro de participación | Público, según las rutas declaradas. Las escrituras públicas siguen requiriendo CSRF. |
| Consulta, registro y actualización de equipos; detalle e historial; resumen general de indicadores | `CAPTURISTA`, `RESPONSABLE`, `ADMIN`. |
| Diagnósticos, reparaciones, decisiones de destino, gestión de campañas, consulta de participantes, gestión ordinaria de gestores e indicadores detallados | `RESPONSABLE`, `ADMIN`. |
| Administración de usuarios, creación y actualización de categorías, verificación y revocación de gestores | `ADMIN`. |

Ejemplo de restricción declarada en un controlador:

```typescript
@Roles(Rol.RESPONSABLE, Rol.ADMIN)
@Controller('equipos/:equipoId/diagnosticos')
export class DiagnosticosController {
  // ... endpoints de simulación y registro ...
}
```

El guard devuelve **403** cuando el rol no está permitido. Las reglas se aplican aunque una persona envíe peticiones directamente a la API o modifique el frontend. El parámetro de usuario responsable se obtiene de la sesión mediante `@UsuarioActual()`, no de un identificador libre enviado en el cuerpo del registro.

La autorización es por rol sobre las operaciones del inventario; no se implementa en estas rutas una separación por propietario, departamento o institución. Un capturista autorizado puede consultar los equipos del inventario y actualizar sus campos permitidos, sin un filtro que limite la operación a los que registró.

`RutaProtegida` controla la navegación del frontend y muestra una pantalla de acceso denegado cuando corresponde. Su estado puede permanecer en caché temporalmente; la comprobación efectiva vuelve a ocurrir en la API.

`UsuariosService` también comprueba que un administrador no se quite su propio rol ni desactive su propia cuenta y que no se retire el último administrador activo. Estas comprobaciones son reglas del servicio; no son restricciones de rol almacenadas como permisos por fila en PostgreSQL.

**Referencias:** [roles.guard.ts](../backend/src/common/guards/roles.guard.ts), [auth.decorators.ts](../backend/src/common/decorators/auth.decorators.ts), [diagnosticos.controller.ts](../backend/src/modules/diagnosticos/diagnosticos.controller.ts) y [RutaProtegida.tsx](../frontend/src/app/RutaProtegida.tsx).

### 4. Protección CSRF

El proyecto utiliza `csrf-csrf` con un mecanismo de **double-submit cookie firmado con HMAC**. El token se valida junto con su cookie y, para usuarios autenticados, queda asociado al identificador de sesión y al id del usuario. Para solicitudes anónimas se utiliza el identificador `anonimo`.

| Elemento | Implementación |
|---|---|
| Secreto | `CSRF_SECRET`, obtenido desde configuración. |
| Obtención | `GET /api/auth/csrf` devuelve `csrfToken` y establece la cookie correspondiente. |
| Encabezado de envío | `X-CSRF-Token`. |
| Cookie en desarrollo | `ew.csrf`. |
| Cookie en producción | `__Host-ew.csrf`, con `Secure`, `HttpOnly`, `SameSite=Lax` y `Path=/`. |
| Métodos que omiten la comprobación | `GET`, `HEAD`, `OPTIONS`. |
| Métodos sujetos al guard | Los demás, incluidos los POST y PATCH del proyecto. |

El login necesita el token anónimo obtenido previamente; después de regenerar la sesión devuelve uno nuevo. Los hooks del frontend conservan ese nuevo token en una variable del módulo HTTP y lo adjuntan a las escrituras. El token se obtiene desde el JSON, no leyendo la cookie `HttpOnly`.

La condición `@Public()` omite la exigencia de autenticación, pero no la comprobación CSRF: tanto el login como el registro público de participación la requieren. La simulación de diagnóstico también la exige porque utiliza POST, aunque no guarde el diagnóstico.

Si la validación falla, `CsrfGuard` responde 403 con `La sesión del formulario expiró. Intenta de nuevo.`. El guard incluye internamente `code: 'CSRF_INVALIDO'`, pero el filtro global no incorpora ese campo a su respuesta final. El cliente HTTP tiene un reintento condicionado a recibir ese código; con el formato actual del filtro, **esa renovación automática no se activa por esa respuesta**. El envío normal de tokens válidos y su actualización después del login sí están implementados.

**Referencias:** [csrf.service.ts](../backend/src/modules/auth/csrf.service.ts), [csrf.guard.ts](../backend/src/common/guards/csrf.guard.ts), [auth.controller.ts](../backend/src/modules/auth/auth.controller.ts), [cliente.ts](../frontend/src/api/cliente.ts) y [http-exception.filter.ts](../backend/src/common/filters/http-exception.filter.ts).

### 5. Validación en cliente y servidor

#### 5.1. Cliente

`useFormulario` agrupa las reglas de los formularios y sus mensajes. Valida al salir de un campo, revalida los campos que se corrigen y revisa el formulario al enviarlo. Si hay errores, mueve el foco al primer campo inválido. Los validadores reutilizables comprueban obligatoriedad, longitudes, correo, mínimos numéricos, fechas no futuras y política de contraseña.

El formulario de login utiliza `noValidate` para gestionar sus mensajes mediante React; no depende únicamente de la validación nativa del navegador. Estas comprobaciones mejoran la interacción, pero pueden omitirse enviando una petición externa.

#### 5.2. Servidor

El backend vuelve a validar mediante DTO con `class-validator`, transformaciones de `class-transformer` y pipes de parámetros. La configuración global es:

```typescript
app.useGlobalPipes(
  new ValidationPipe({
    whitelist: true,
    forbidNonWhitelisted: true,
    transform: true,
    stopAtFirstError: true,
  }),
);
```

| Mecanismo | Ejemplo y alcance |
|---|---|
| Tipos, rangos y longitudes | `@IsEmail`, `@IsEnum`, `@IsBoolean`, `@IsInt`, `@Length`, `@Min` y `@Max` en los DTO. |
| Transformación | Recorte de cadenas, normalización de correo y conversión explícita de determinados valores numéricos. No equivale a sanear arbitrariamente todo texto recibido. |
| Lista de propiedades permitidas | Un cuerpo con campos no declarados produce 400 en los endpoints que reciben un DTO validado. |
| Campos editables separados | `ActualizarEquipoDto` permite datos descriptivos y no expone la modificación directa de condición o destino. |
| Validación condicional | `RegistrarDecisionDto` exige destino, gestor o evidencia según el tipo de movimiento. |
| Reglas de dominio | `Equipo` comprueba estados, disponibilidad, gestor verificado y confirmación de borrado de datos. Los incumplimientos producen 422. |
| Integridad SQL | FKs, claves únicas, enums y restricciones `CHECK` agregan comprobaciones de almacenamiento. |

`stopAtFirstError` detiene comprobaciones adicionales de cada propiedad fallida; pueden aparecer errores de varias propiedades. Las reglas del cliente y el servidor son implementaciones separadas: no debe asumirse que sean idénticas. Por ejemplo, el cliente compara fechas contra el día local y los servicios usan el día obtenido mediante `toISOString()`, que corresponde a UTC.

La lista blanca se aplica a parámetros enlazados a un DTO; no significa que cada endpoint tenga un esquema de validación para cualquier query o cuerpo. Tampoco garantiza reglas que no se hayan declarado: la comprobación de series duplicadas, por ejemplo, no está respaldada por un índice único de `numero_serie`.

**Referencias:** [useFormulario.ts](../frontend/src/hooks/useFormulario.ts), [LoginPage.tsx](../frontend/src/features/auth/LoginPage.tsx), [equipos.dto.ts](../backend/src/modules/equipos/equipos.dto.ts), [movimientos.dto.ts](../backend/src/modules/movimientos/movimientos.dto.ts), [equipo.entity.ts](../backend/src/modules/equipos/equipo.entity.ts) y [Base de datos](documentacion-tecnica-base-datos.md).

### 6. Prevención de inyección SQL

Los repositorios utilizan TypeORM y consultas con parámetros para los valores recibidos. La búsqueda de equipos, por ejemplo, envía el texto como un parámetro independiente:

```typescript
const texto = `%${f.q.trim()}%`;
qb.andWhere(
  new Brackets((w) =>
    w
      .where('e.folio ILIKE :texto', { texto })
      .orWhere('e.marca ILIKE :texto', { texto })
      .orWhere('e.modelo ILIKE :texto', { texto })
      .orWhere('e.numeroSerie ILIKE :texto', { texto })
      .orWhere('e.ubicacion ILIKE :texto', { texto }),
  ),
);
```

La autenticación utiliza `.where('u.email = :email', { email })`. Las consultas SQL crudas de indicadores utilizan parámetros posicionales cuando requieren valores, por ejemplo `LIMIT $1` con un arreglo de parámetros. Los demás textos SQL fijos, como el bloqueo de folios, no concatenan entradas de la petición.

Existe una interpolación de identificador en `IndicadoresRepository.agruparPor()`: la columna se toma del conjunto interno `'condicion' | 'estado_destino'` y los puntos de llamada suministran esos nombres fijos. No hay un parámetro HTTP que permita elegir libremente una columna SQL. El orden de búsqueda también se convierte a `ASC` o `DESC` mediante una elección cerrada.

Los comodines `%` y `_` del texto de búsqueda pueden conservar su significado dentro de `ILIKE`; parametrizar evita que el valor pase a ser sintaxis SQL, pero no convierte automáticamente esos caracteres en una búsqueda literal.

Estos mecanismos reducen el riesgo de inyección en las consultas revisadas; usar un ORM no protege una consulta futura si se concatenan valores externos dentro del SQL.

**Referencias:** [equipos.repository.ts](../backend/src/modules/equipos/equipos.repository.ts), [usuarios.repository.ts](../backend/src/modules/usuarios/usuarios.repository.ts) e [indicadores.repository.ts](../backend/src/modules/indicadores/indicadores.repository.ts).

### 7. HTTPS y cookies HttpOnly/Secure

#### 7.1. Configuración de transporte y cookies

El despliegue previsto en [render.yaml](../render.yaml) utiliza `NODE_ENV=production` y sirve el frontend y la API desde un mismo origen. La terminación HTTPS depende de la infraestructura del servicio desplegado; NestJS no configura certificados TLS propios en este proyecto.

`app.setup.ts` establece `trust proxy` en `1`, para reconocer el proxy previsto delante del backend. Ese ajuste debe corresponder con la topología real de despliegue; no configura por sí solo una red de proxies confiables.

| Atributo | Cookie de sesión `ew.sid` | Cookie CSRF |
|---|---|---|
| `HttpOnly` | `true` | `true` |
| `Secure` | `true` en producción; `false` fuera de producción | Mismo comportamiento. |
| `SameSite` | `lax` | `lax` |
| `Path` | `/`, comportamiento del middleware utilizado | `/`, configurado explícitamente. |
| Duración | Ocho horas de inactividad, con renovación | No se fija `maxAge` en las opciones CSRF del proyecto. |

`HttpOnly` evita que JavaScript lea directamente esas cookies; no elimina la posibilidad de que un script ejecutado en la página realice solicitudes con ellas. `Secure` restringe el envío a HTTPS; no cifra los datos guardados en PostgreSQL. `SameSite` complementa la protección CSRF, que se comprueba además mediante token.

En desarrollo local y en la configuración Docker de desarrollo se permite HTTP con cookies sin `Secure`. Ese comportamiento facilita ejecutar el proyecto localmente y no equivale a HTTPS verificado en producción.

#### 7.2. Cabeceras y conexión a PostgreSQL

La aplicación utiliza Helmet y configura una CSP con scripts, fuentes y conexiones del mismo origen, `object-src 'none'` y `frame-ancestors 'none'`. Los estilos admiten `'unsafe-inline'`; por ello, la política no prohíbe todos los recursos inline. La directiva de actualización de peticiones inseguras se habilita en producción y se desactiva explícitamente fuera de ese entorno. También se deshabilita la cabecera de Express `X-Powered-By`.

El proyecto no sobrescribe las demás opciones de cabeceras de Helmet, incluido su comportamiento predeterminado de HSTS. La recepción efectiva de esas cabeceras depende del servidor y los proxies en ejecución, y no se verificó en una URL pública durante esta documentación.

Para la base de datos, `DB_SSL=true` activa SSL tanto en TypeORM como en el pool de sesiones, pero ambos se configuran con **`rejectUnauthorized: false`**. Esta configuración solicita una conexión cifrada sin exigir la verificación de confianza del certificado del servidor; no debe describirse como validación TLS estricta del servidor PostgreSQL.

Los secretos `SESSION_SECRET` y `CSRF_SECRET` se validan con un mínimo de 24 caracteres. El blueprint de Render prevé generarlos mediante variables de entorno. `.gitignore` excluye `.env` y permite `.env.example`; esa regla no demuestra por sí sola que nunca se haya versionado un secreto.

**Referencias:** [app.setup.ts](../backend/src/app.setup.ts), [csrf.service.ts](../backend/src/modules/auth/csrf.service.ts), [typeorm.config.ts](../backend/src/database/typeorm.config.ts), [env.validation.ts](../backend/src/config/env.validation.ts) y [.gitignore](../.gitignore).

### 8. Manejo de errores sin exponer información interna

`HttpExceptionFilter` construye una respuesta JSON uniforme. Conserva mensajes de excepciones HTTP controladas, traduce las reglas de dominio a 422 y convierte determinados errores PostgreSQL en respuestas genéricas:

| Origen | Respuesta |
|---|---|
| Validación de DTO o parámetro | 400 con mensajes de validación. |
| Sesión o rol | 401 o 403 con una explicación de acceso. |
| Regla de negocio | 422 con el mensaje de dominio. |
| PostgreSQL `23505`, unicidad | 409: `Ya existe un registro con esos datos.` |
| PostgreSQL `23503`, clave foránea | 409 con mensaje genérico sobre relaciones. |
| PostgreSQL `23514`, `CHECK` | 400: `Los datos no cumplen las reglas de integridad.` |
| Excepción no clasificada | 500: `Ocurrió un error inesperado. Intenta de nuevo.` |

Ejemplo de respuesta interna controlada hacia el cliente:

```json
{
  "statusCode": 500,
  "error": "INTERNAL_SERVER_ERROR",
  "message": "Ocurrió un error inesperado. Intenta de nuevo.",
  "path": "/api/equipos",
  "timestamp": "2026-11-25T18:00:00.000Z"
}
```

El JSON no incluye stack trace, consulta SQL ni objeto completo de la excepción. Los detalles de errores 5xx se registran mediante `Logger` del lado del servidor. El código no incorpora un mecanismo adicional de depuración o anonimización de esos logs: su acceso y conservación no forman parte de una política implementada en este filtro.

`path` proviene de `req.url` y puede contener la cadena de consulta. Las contraseñas del flujo de login viajan en el cuerpo, no en la URL. Los mensajes de errores HTTP y de dominio se conservan, de modo que su redacción sigue siendo parte del control de información expuesta.

En el frontend, `ApiError` transforma el mensaje en una lista utilizable por la interfaz y ofrece mensajes de respaldo cuando la respuesta no tiene JSON. No se modifica el código HTTP del servidor.

Los fallos de infraestructura que ocurran antes de llegar a NestJS no tienen garantizado este formato. La protección de la respuesta tampoco implica que todos los errores estén clasificados de forma ideal: por ejemplo, un equipo inexistente en una operación con `getOneOrFail()` puede producir el mensaje genérico 500 en lugar de 404.

**Referencias:** [http-exception.filter.ts](../backend/src/common/filters/http-exception.filter.ts), [cliente.ts](../frontend/src/api/cliente.ts) y [Servicios web](documentacion-tecnica-servicios-web.md).

### 9. Minimización de datos personales

#### 9.1. Datos solicitados y expuestos

| Área | Datos utilizados | Medida implementada |
|---|---|---|
| Cuenta del personal | Nombre, correo, hash, rol y estado; además de auditoría. | No solicita domicilio, identificación oficial o teléfono para crear la cuenta. La administración de usuarios requiere `ADMIN`. |
| Sesión de usuario | Id, nombre, correo y rol, junto con los datos técnicos del middleware. | `aSesion()` excluye el hash y otros campos de la entidad. |
| Participación pública | Nombre y cantidad declarada obligatorios; contacto y comentario opcionales. | Permite participar sin crear una cuenta ni entregar contacto. La asociación con usuario es opcional. |
| Confirmación de participación | Id de participación, nombre de campaña y cantidad declarada. | No devuelve nombre o contacto del participante ni datos de otras personas. |
| Consulta de participantes | Registros de la campaña. | Restringida a `RESPONSABLE` y `ADMIN`. |
| Campañas públicas | Descripción, categorías y cifras agregadas. | Las consultas públicas no cargan la lista de participaciones ni sus contactos. |
| Indicadores públicos | Totales de equipos, recuperación, kg estimados, campañas activas y participaciones. | Devuelve agregados sin identidades de participantes o personal. |
| Reasignación o entrega de equipos | Confirmación de borrado cuando el equipo lo requiere. | Cómputo y móviles deben tener confirmación antes de esas transiciones; el backend registra la declaración, no ejecuta ni certifica el borrado físico. |

El nombre y correo del personal pueden aparecer en relaciones de autoría o responsabilidad de respuestas privadas. Excluir el hash no equivale a anonimizar toda la entidad `Usuario`.

#### 9.2. Almacenamiento del navegador y conservación

El frontend mantiene el token CSRF en memoria. `localStorage` se utiliza para preferencias de interfaz; el borrador de alta de equipos se conserva en `sessionStorage` e incluye serie, ubicación y observaciones. Se limpia al completar el registro o al utilizar la acción de descarte. El flujo de logout revisado no elimina expresamente ese borrador.

Ese almacenamiento de borradores no está cifrado por la aplicación y es accesible al código del mismo origen. Los campos libres pueden contener información personal escrita por quien opera el sistema; el proyecto no implementa detección automática de esos datos.

La expiración de sesiones no equivale a una política de eliminación de datos de personas. No se encontró un procedimiento implementado de retención temporal, anonimización periódica o eliminación automática de participaciones y cuentas por antigüedad. Los usuarios se desactivan; las referencias de usuario con `ON DELETE SET NULL` describen la integridad de la base ante un borrado, no un proceso completo de supresión de datos personales.

**Referencias:** [usuario.entity.ts](../backend/src/modules/usuarios/usuario.entity.ts), [campanas.service.ts](../backend/src/modules/campanas/campanas.service.ts), [campanas.controller.ts](../backend/src/modules/campanas/campanas.controller.ts), [campanas.repository.ts](../backend/src/modules/campanas/campanas.repository.ts), [indicadores.service.ts](../backend/src/modules/indicadores/indicadores.service.ts), [borrador-equipo.store.ts](../frontend/src/stores/borrador-equipo.store.ts), [ui.store.ts](../frontend/src/stores/ui.store.ts) y [EquipoNuevoPage.tsx](../frontend/src/features/equipos/EquipoNuevoPage.tsx).

### Evidencia y alcance de la revisión

Las [pruebas e2e existentes](../backend/test/servicios-web.e2e-spec.ts) incluyen comprobaciones de acceso sin sesión, rechazo por CSRF, credenciales incorrectas, cookie HttpOnly, desactivación de cuentas, exclusión de datos sensibles de la sesión, validación de cuerpos, rechazo de campos adicionales y permisos de diagnóstico por rol. Hay una [evidencia de ejecución previa](evidencia-pruebas-e2e.txt) en el repositorio.

Este apartado se elaboró mediante revisión del código y de esos archivos de evidencia. No se ejecutaron nuevas pruebas de penetración ni una verificación de infraestructura, y la evidencia de pruebas en entorno de test no demuestra el comportamiento del límite de solicitudes, que está desactivado en ese entorno, ni los atributos de cookies exclusivos de producción.
