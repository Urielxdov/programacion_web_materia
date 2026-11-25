# Seguridad

| Requisito | Implementación | Dónde |
|---|---|---|
| HTTPS | Render termina TLS. En producción las cookies son `Secure`, Helmet envía HSTS y `trust proxy` está activo. | `render.yaml`, `app.setup.ts` |
| Autenticación | Correo y contraseña. El mismo mensaje para correo inexistente y contraseña incorrecta, con tiempo de respuesta equivalente (se verifica un hash simulado). | `auth.service.ts` |
| Contraseñas | Hash **argon2id**. El hash nunca se selecciona por defecto (`select: false`) ni se envía al cliente. Política: 8 caracteres o más, con letras y números. | `password.service.ts`, `usuario.entity.ts` |
| Sesiones | `express-session` guardada en PostgreSQL (tabla `sesiones`). Cookie `HttpOnly`, `Secure`, `SameSite=Lax`, expira tras 8 h de inactividad. **El id de sesión se regenera al iniciar sesión** (contra fijación de sesión). Si un usuario se desactiva, su sesión deja de ser válida en la siguiente consulta a `/auth/me`. | `app.setup.ts`, `auth.controller.ts` |
| Autorización | Guards globales: **todo es privado por defecto**, salvo lo marcado con `@Public()`. `@Roles()` se valida en el servidor; el menú del frontend solo oculta opciones por comodidad. | `session-auth.guard.ts`, `roles.guard.ts` |
| CSRF | Patrón *double-submit cookie* firmado con HMAC (`csrf-csrf`) y ligado a la sesión. Toda petición que modifica datos exige `X-CSRF-Token`. | `csrf.service.ts`, `csrf.guard.ts` |
| Inyección SQL | Solo ORM y consultas parametrizadas (`$1`, `:param`). La única interpolación es un nombre de columna tomado de una lista cerrada. | `*.repository.ts` |
| Validación | En el cliente (`useFormulario`, mensajes en vivo) **y** en el servidor (DTOs con class-validator, `whitelist` y `forbidNonWhitelisted`, transformaciones, `ParseIntPipe`). | `*.dto.ts`, `hooks/useFormulario.ts` |
| Manejo de errores | El filtro global responde JSON uniforme y nunca expone stack traces, SQL ni credenciales. Los errores 500 solo se registran en el servidor. | `http-exception.filter.ts` |
| Fuerza bruta / abuso | Rate limiting global (200/min), login (8/min) y participación pública (5/min). | `@nestjs/throttler` |
| Cabeceras | Helmet con CSP estricta (`script-src 'self'`, `frame-ancestors 'none'`, `object-src 'none'`), `X-Content-Type-Options` y HSTS. | `app.setup.ts` |
| Redirecciones abiertas | El parámetro `?volver=` del login solo acepta rutas internas `/panel…`. | `LoginPage.tsx` |
| Datos personales | Las participaciones piden solo nombre y un contacto opcional. Solo Responsable/Admin pueden verlas. La respuesta pública solo confirma el registro. | `campanas.service.ts` |
| Configuración | Las variables de entorno se validan al arrancar (los secretos deben tener 24 caracteres o más). `.env` está en `.gitignore`. | `env.validation.ts` |
| Datos de los equipos | Los equipos de cómputo y móviles exigen confirmar el borrado seguro antes de reasignarse o entregarse. | `equipo.entity.ts` |
