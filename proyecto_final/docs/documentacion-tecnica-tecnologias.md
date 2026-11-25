# Documentación técnica de E-Waste 360°

## Tecnologías utilizadas

E-Waste 360° utiliza TypeScript en el cliente y el servidor, una interfaz desarrollada con React, una API REST construida con NestJS y una base de datos PostgreSQL. Esta combinación permite separar la presentación, las reglas de negocio y el almacenamiento de información.

Las versiones de las bibliotecas indicadas a continuación corresponden a las ramas principales declaradas en los archivos `package.json`. Las versiones exactas resueltas se registran en los respectivos archivos `package-lock.json`. Las versiones de imágenes y del entorno de despliegue se indican según su configuración en el repositorio.

### 1. Frontend

El frontend es una aplicación de página única o **SPA**, organizada en páginas, componentes reutilizables, funciones de acceso a la API y almacenes de estado. React actualiza el DOM según el estado de la interfaz y las acciones del usuario.

| Tecnología | Versión declarada | Uso en el proyecto |
|---|---|---|
| React y React DOM | 19 | Construcción de páginas y componentes, renderizado de la interfaz y actualización del DOM. |
| TypeScript | 5.8 | Definición de tipos para componentes, formularios, respuestas de la API y funciones del cliente. |
| Vite | 6 | Servidor de desarrollo y generación de los archivos estáticos de producción en `frontend/dist`. |
| Tailwind CSS | 4 | Estilos de la interfaz, diseño responsivo, distribución de componentes y variantes visuales. Se integra con Vite mediante `@tailwindcss/vite`. |
| React Router | 7 | Navegación entre páginas, rutas anidadas y organización de las secciones pública y privada. |
| TanStack Query | 5 | Consulta y actualización de datos del servidor, caché, estados de carga y error e invalidación después de guardar cambios. |
| Zustand | 5 | Gestión del estado de interfaz: preferencias de lectura, avisos y borrador del registro de equipos. |
| Recharts | 3 | Representación de indicadores mediante gráficas en la interfaz de reportes. |
| Lucide React | 1 | Iconos utilizados en menús, botones, indicadores y mensajes. |
| clsx | 2 | Composición condicional de clases CSS en componentes. |
| Atkinson Hyperlegible, mediante Fontsource | Paquete 5 | Tipografía incluida en el proyecto para favorecer la legibilidad. |

La interfaz utiliza elementos HTML semánticos y estilos CSS, junto con operaciones de JavaScript para manejar foco, ventanas modales, desplazamiento, exportación de archivos e impresión. Estas funciones complementan las actualizaciones del DOM realizadas por React.

La comunicación con el servidor se concentra en `frontend/src/api/`. En desarrollo, Vite sirve la aplicación en el puerto **5173** y redirige las solicitudes `/api` al backend del puerto **3000**, de modo que el navegador utiliza un mismo origen para la interfaz y las solicitudes.

### 2. Backend

El backend expone servicios REST bajo el prefijo `/api`. Se organiza en módulos de autenticación, usuarios, catálogos, equipos, diagnósticos, reparaciones, movimientos, campañas e indicadores. Cada módulo distribuye sus responsabilidades entre controladores, servicios, entidades y repositorios según corresponda.

| Tecnología | Versión declarada | Uso en el proyecto |
|---|---|---|
| Node.js | 22 o superior como requisito del proyecto | Entorno de ejecución del servidor y de las herramientas de compilación. |
| NestJS | 11 | Organización modular, controladores HTTP, inyección de dependencias, servicios, guards y filtros de errores. |
| Adaptador Express de NestJS | 11 | Integración del servidor con Express y sus middlewares de cookies, sesiones y seguridad. |
| TypeScript | 5.7 | Implementación de clases, entidades, servicios, contratos de datos y reglas de negocio con tipos estáticos. |
| class-validator | 0.15 | Validación de los datos recibidos mediante DTO y decoradores. |
| class-transformer | 0.5 | Transformación de datos de las solicitudes para su validación y procesamiento. |
| `@nestjs/config` y dotenv | 4 y 18 | Carga y acceso a variables de entorno, con validación de configuración definida en el proyecto. |
| `@nestjs/swagger` | 11 | Generación de la descripción OpenAPI y de la interfaz de documentación de servicios en `/api/docs`. |
| `@nestjs/event-emitter` | 3 | Emisión y recepción de eventos de equipos para registrar movimientos mediante el patrón Observer. |
| `@nestjs/serve-static` | 5 | Servicio de los archivos compilados de React desde NestJS en la modalidad de despliegue unificada. |

Los mecanismos de seguridad se apoyan en las siguientes bibliotecas:

| Tecnología | Versión declarada | Uso en el proyecto |
|---|---|---|
| Argon2 | 0.45 | Generación y comprobación de hashes de contraseñas mediante Argon2id. |
| express-session | 1 | Administración de sesiones en el servidor y de la cookie identificadora del usuario. |
| connect-pg-simple | 10 | Almacenamiento de sesiones en PostgreSQL. |
| cookie-parser | 1 | Lectura de cookies recibidas por el servidor. |
| csrf-csrf | 4 | Generación y validación de tokens para proteger las solicitudes de escritura frente a CSRF. |
| Helmet | 8 | Configuración de encabezados HTTP de seguridad y de la política de seguridad de contenido. |
| `@nestjs/throttler` | 6 | Limitación de la frecuencia de solicitudes, con una restricción específica para el inicio de sesión. |

La autorización se implementa mediante guards propios que comprueban la sesión, el estado de la cuenta y el rol vigente. Las reglas de negocio se implementan en clases del dominio y servicios; las bibliotecas de seguridad complementan esas validaciones.

### 3. Base de datos y ORM

La persistencia utiliza **PostgreSQL**, con **TypeORM** para relacionar las entidades de TypeScript con las tablas y ejecutar las operaciones de acceso a datos.

| Tecnología o mecanismo | Versión o configuración | Uso en el proyecto |
|---|---|---|
| PostgreSQL | 16 en Docker Compose | Almacenamiento relacional de usuarios, equipos, diagnósticos, reparaciones, movimientos, campañas, participaciones y catálogos. |
| TypeORM | 0.3 | Definición de entidades y relaciones, repositorios, consultas, transacciones y migraciones. |
| `@nestjs/typeorm` | 11 | Integración de TypeORM con los módulos y la inyección de dependencias de NestJS. |
| pg | 8 | Controlador de conexión de Node.js con PostgreSQL, utilizado por la persistencia y el almacenamiento de sesiones. |
| Migraciones versionadas | `synchronize: false` y `migrationsRun: true` | Aplicación de cambios de esquema mediante archivos de migración, evitando la sincronización automática de entidades. |
| Script de datos de demostración | `npm run seed` en el backend | Carga de cuentas y registros de ejemplo para desarrollo y demostración. |

El modelo de negocio incluye nueve entidades principales y una tabla de relación entre campañas y categorías, para un total de **diez tablas de dominio y asociación**. El almacenamiento de sesiones utiliza adicionalmente la tabla `sesiones`, y TypeORM mantiene el registro de migraciones en su tabla de control.

La integridad se apoya en claves primarias y foráneas, restricciones de unicidad, validaciones y transacciones. La configuración compartida de conexión se encuentra en `backend/src/database/typeorm.config.ts`, mientras que las operaciones de acceso a datos se organizan en los repositorios de cada módulo.

### 4. Despliegue e infraestructura

El repositorio contempla tres modalidades de ejecución: desarrollo con procesos locales, ejecución mediante contenedores y una configuración de publicación en Render con PostgreSQL externo en Neon.

| Modalidad | Componentes | Funcionamiento |
|---|---|---|
| Desarrollo local | Node.js, Vite, NestJS y PostgreSQL | Vite sirve el frontend en el puerto 5173; NestJS atiende la API en el 3000. La base puede ejecutarse con Docker Compose, expuesta en el puerto 5544. |
| Aplicación en contenedores | Docker Compose, PostgreSQL, NestJS y nginx | Compose levanta tres servicios: `db`, `backend` y `frontend`. nginx sirve la interfaz y reenvía las solicitudes `/api` al backend. |
| Publicación configurada | Render y Neon | Render compila el proyecto y ejecuta NestJS, que sirve tanto la API como el frontend compilado. Neon se contempla como proveedor de PostgreSQL mediante `DATABASE_URL`. |

#### 4.1. Configuración de contenedores

- **Base de datos:** imagen `postgres:16-alpine`, con un volumen nombrado para conservar los datos.
- **Backend:** compilación y ejecución en etapas separadas basadas en `node:22-slim`; la imagen final instala dependencias de producción y ejecuta el proceso con el usuario `node`.
- **Frontend:** compilación con `node:22-slim` y servicio de archivos estáticos mediante `nginx:1.27-alpine`.
- **Orquestación:** `docker-compose.yml` define puertos, variables, volumen, verificaciones de salud y dependencias entre servicios.

En esta modalidad, la interfaz se expone por defecto en **http://localhost:8080**, la API en **http://localhost:3000/api** y PostgreSQL en **localhost:5544**. El puerto de la interfaz puede modificarse mediante `FRONTEND_PORT`.

#### 4.2. Configuración de publicación

El archivo `render.yaml` define un Web Service con Node.js **22.16.0**, `npm run build` como comando de compilación y `npm start` como comando de inicio. La ruta `/api/indicadores/publico` se configura para comprobar la salud del servicio.

La publicación unificada mantiene la interfaz y la API bajo un mismo dominio. HTTPS se contempla en el servicio externo y las cookies se configuran como seguras en producción. La conexión con PostgreSQL utiliza las variables `DATABASE_URL` y `DB_SSL`; los secretos de sesión y CSRF se suministran mediante variables de entorno.

Estos archivos describen la infraestructura preparada en el repositorio. La URL pública, la versión efectiva de PostgreSQL en Neon y el funcionamiento del despliegue deben comprobarse en el entorno publicado.

### 5. Herramientas de desarrollo

| Herramienta | Uso en el proyecto | Evidencia o configuración |
|---|---|---|
| Git | Control de versiones del código y seguimiento de cambios. | `proyecto_final` forma parte del repositorio Git de `programacion_web_materia`. |
| npm | Instalación de dependencias y ejecución de scripts de desarrollo, compilación y pruebas. | Archivos `package.json` y `package-lock.json` del frontend y backend; scripts de coordinación en la raíz del proyecto. |
| Docker y Docker Compose | Construcción de imágenes y ejecución coordinada de la base de datos, API e interfaz. | Dockerfiles y `docker-compose.yml`. |
| ESLint 9 | Análisis estático para detectar problemas de código JavaScript y TypeScript. | `frontend/eslint.config.js` y `backend/eslint.config.mjs`. |
| typescript-eslint 8 | Reglas de análisis específicas para TypeScript; en el backend incluye reglas que utilizan información de tipos. | Configuraciones ESLint de ambas aplicaciones. |
| Plugins de React para ESLint | Comprobación de reglas de hooks y compatibilidad de componentes con React Refresh. | `eslint-plugin-react-hooks` y `eslint-plugin-react-refresh` en el frontend. |
| Prettier 3 | Formato consistente del código del backend, integrado también con ESLint. | Script `format`, `eslint-plugin-prettier` y `eslint-config-prettier`. |
| Nest CLI 11 | Compilación, ejecución en desarrollo y soporte para la estructura de NestJS. | Scripts del backend y `nest-cli.json`. |
| Jest 30 y ts-jest 29 | Ejecución de pruebas unitarias y transformación de TypeScript durante las pruebas. | Configuración Jest en `backend/package.json` y archivos `*.spec.ts`. |
| Supertest 7 y `@nestjs/testing` 11 | Pruebas HTTP de la API utilizando una aplicación NestJS de prueba. | `backend/test/servicios-web.e2e-spec.ts` y configuración e2e. |
| ts-node y CLI de TypeORM | Ejecución de scripts TypeScript y administración de migraciones. | Scripts `seed`, `typeorm`, `migration:generate`, `migration:run` y `migration:revert`. |

Los principales comandos disponibles son:

| Ubicación de ejecución | Comando | Propósito |
|---|---|---|
| Raíz de `proyecto_final` | `npm run install:all` | Instalar las dependencias del backend y frontend con `npm ci`. |
| Raíz de `proyecto_final` | `npm run build` | Instalar dependencias y compilar ambas aplicaciones para el despliegue unificado. |
| Raíz de `proyecto_final` | `docker compose up -d --build` | Construir y levantar los tres servicios en contenedores. |
| `frontend/` | `npm run dev` | Iniciar el servidor Vite de desarrollo. |
| `frontend/` | `npm run lint` | Analizar el código del frontend. |
| `backend/` | `npm run start:dev` | Iniciar NestJS con recompilación ante cambios. |
| `backend/` | `npm run lint` | Analizar el código del backend y aplicar las correcciones automáticas disponibles. |
| `backend/` | `npm run format` | Aplicar formato al código fuente y a las pruebas del backend. |
| `backend/` | `npm test` | Ejecutar pruebas unitarias. |
| `backend/` | `npm run test:e2e` | Ejecutar pruebas de los servicios web; requiere la base de datos de pruebas configurada. |

La documentación de esta sección se sustenta en los manifiestos de dependencias, las configuraciones de Vite, ESLint y TypeORM, los Dockerfiles, `docker-compose.yml` y `render.yaml` presentes en el proyecto.
