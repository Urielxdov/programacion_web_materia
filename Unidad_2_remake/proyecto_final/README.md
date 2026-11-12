# E-Waste 360°

Plataforma web para la gestión sustentable de residuos electrónicos.

**Proyecto integrador:** Programación Web + Desarrollo Sustentable (AGO-DIC 2026), Ingeniería en Sistemas Computacionales.

**Equipo:** _(completar)_
| Integrante | No. de control | Rol en el proyecto |
|---|---|---|
| | | |
| | | |

**Sitio publicado:** _(completar con la URL de Render: https://….onrender.com)_

---

## ¿Qué hace?

E-Waste 360° registra los equipos electrónicos fuera de uso de la institución y sigue cada uno desde su ingreso hasta
su reasignación, reparación o entrega a un gestor:
- evalúa con reglas configurables si conviene **reutilizarlo, conservarlo, repararlo o enviarlo a gestión especializada**;
- documenta cada movimiento con validación de un responsable;
- organiza **campañas de recolección**;
- genera **indicadores ambientales y económicos**.

| Módulo | Dónde |
|---|---|
| 1. Inicio y concientización | `/`, `/informacion` |
| 2. Inventario de equipos | `/panel/equipos` |
| 3. Reparación y reutilización (diagnósticos y movimientos) | Ficha del equipo, `/panel/movimientos` |
| 4. Campañas de recolección | `/campanas` (público), `/panel/campanas` |
| 5. Indicadores y reportes | `/panel/reportes`, `/api/indicadores` |

## Cumplimiento de la rúbrica

| Criterio | Cómo se cumple | Evidencia |
|---|---|---|
| Maquetación (10) | HTML5 semántico y Bootstrap 5 responsivo; menú lateral que se oculta en el celular; las tablas se vuelven tarjetas en pantallas chicas | `app/Views`, `public/assets/css/app.css` |
| Aspecto visual (10) | Misma identidad en todas las páginas: paleta verde/turquesa/ámbar, tipografías Poppins e Inter, ilustraciones SVG propias | `public/assets` |
| Navegabilidad y seguridad (10) | HTTPS (Render), sesiones seguras, autenticación con bloqueo por intentos, autorización por rol en el servidor, CSRF y CSP | [`docs/arquitectura.md`](docs/arquitectura.md#seguridad) |
| Manejo del DOM (10) | Búsqueda en vivo, recomendación en tiempo real, formularios dinámicos, gráficas con Chart.js, validación, lista de verificación, filtros | `public/assets/js` |
| Arquitectura y diseño (15) | Capas + MVC con Front Controller; patrones **Repository, Strategy, Factory** y Singleton; 11 diagramas UML | [`docs/arquitectura.md`](docs/arquitectura.md), [`docs/uml.md`](docs/uml.md) |
| Orientación a objetos (15) | Clase abstracta `Equipo` con 9 subclases polimórficas, interfaces, encapsulamiento, objetos de valor e inyección de dependencias | `app/Models`, `app/Strategies` |
| Interoperabilidad (5) | 2 servicios REST (equipos e indicadores) en JSON, documentados y con pruebas automatizadas | [`docs/api.md`](docs/api.md), colección de Postman |
| Persistencia (15) | 9 tablas relacionadas con llaves foráneas, CHECK, UNIQUE y transacciones; CRUD completo | [`docs/modelo-datos.md`](docs/modelo-datos.md) |
| Despliegue (10) | Docker + Render (HTTPS) + Neon (PostgreSQL) | `Dockerfile`, `render.yaml` |

## Tecnologías

- **Back end:** PHP 8.3+ sin framework (MVC propio), PDO.
- **Front end:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, JavaScript (ES2020), Chart.js 4.
- **Base de datos:**
  - **MySQL/MariaDB** en local (XAMPP);
  - **PostgreSQL** en producción (Neon).
  - El mismo código funciona con ambas.
- **Despliegue:** Docker (php:8.3-apache) en Render.

## Estructura

```
proyecto_final/
├── public/            ← única carpeta accesible desde el navegador
│   ├── index.php      ← Front Controller
│   ├── router.php     ← solo para el servidor embebido de PHP
│   └── assets/        ← css, js, img
├── app/
│   ├── Core/          ← Router, Request, Response, View, Session, Auth, Csrf, Validator, Database (Singleton)
│   ├── Middleware/    ← Autenticado, TienePermiso, SoloInvitados
│   ├── Controllers/   ← controladores web y Api/ (servicios REST)
│   ├── Services/      ← casos de uso (capa de negocio)
│   ├── Models/        ← entidades; Equipo + Equipos/ (9 subclases)
│   ├── Factories/     ← EquipoFactory (Factory Method)
│   ├── Strategies/    ← ClasificadorDestino + estrategias (Strategy)
│   ├── Repositories/  ← acceso a datos (Repository)
│   └── Views/         ← plantillas PHP
├── config/            ← config.php (variables de entorno), permisos.php, rutas.php
├── database/          ← schema.sql de mysql/ y pgsql/, instalar.php
├── docker/            ← entrypoint, Apache y php.ini de producción
├── docs/              ← UML, modelo de datos, API, arquitectura, manual, pruebas
├── tests/             ← unitarias.php, integracion.php
├── Dockerfile
└── render.yaml
```

## Instalación local (XAMPP)

**Requisitos:**
- PHP 8.1 o superior con `pdo_mysql`. El de XAMPP sirve.
- MySQL o MariaDB encendido desde el panel de XAMPP.

1. Copia la configuración y ajusta el puerto de tu MySQL (XAMPP usa 3306 por defecto):
   ```bash
   cd proyecto_final
   cp .env.example .env        # en Windows: copy .env.example .env
   ```
2. Crea la base, las tablas y los datos de demostración. Define antes la contraseña de las cuentas demo:
   ```bash
   # PowerShell:  $env:SEED_PASSWORD="Demo2026x"
   # bash:        export SEED_PASSWORD=Demo2026x
   php database/instalar.php --crear-bd
   ```
   Si no defines `SEED_PASSWORD`, el instalador genera una contraseña y la muestra **una sola vez** en la consola.
3. Levanta el servidor y abre <http://localhost:8000>:
   ```bash
   php -S localhost:8000 -t public public/router.php
   ```
   Otra opción es copiar la carpeta a `htdocs` y abrir `http://localhost/proyecto_final/public/`. El `.htaccess` ya
   está incluido y la raíz del proyecto queda protegida.

**Cuentas demo** (todas con la contraseña de `SEED_PASSWORD`):
- `admin`
- `responsable`
- `capturista`

**Opciones del instalador:**
- `--reiniciar` borra y vuelve a crear todo; está bloqueado en producción.
- `--sin-demo` solo crea los catálogos y el usuario `admin`.

### Usar PostgreSQL en local (opcional)
Define `DATABASE_URL=postgresql://usuario:clave@127.0.0.1:5432/ewaste360?sslmode=disable` en `.env`, crea la base
`ewaste360` y habilita la extensión `pdo_pgsql` en tu `php.ini`.

## Despliegue en Render + Neon (HTTPS)

> Docker no estaba instalado en la máquina donde se preparó el proyecto, así que **la imagen no se construyó en local**.
> La aplicación sí se probó completa con PostgreSQL, que es lo que usa Neon. Si el primer *deploy* falla, revisa los
> *Logs* de Render.

1. **Base de datos (Neon):**
   1. Crea un proyecto gratuito en <https://neon.tech>.
   2. Copia la *connection string*. Debe terminar en `?sslmode=require`.
2. **Repositorio:**
   - Sube a GitHub el **contenido de esta carpeta** como raíz del repositorio. Así lo recomendamos para el equipo.
   - Si prefieres usar el repositorio actual, en `render.yaml` descomenta `rootDir` con la ruta de la carpeta.
3. **Render:**
   1. En <https://render.com> elige **New → Blueprint** y selecciona el repositorio. Render lee `render.yaml`.
   2. Captura `DATABASE_URL` (la de Neon) y `SEED_PASSWORD` (la contraseña de las cuentas demo para el profesor).
4. En el primer arranque, el contenedor ejecuta `database/instalar.php --si-vacia`: crea las tablas y los datos demo.
   En los arranques siguientes no modifica nada.
5. Abre la URL `https://….onrender.com` y verifica el sitio. Opcionalmente, corre las pruebas contra producción:
   ```bash
   SEED_PASSWORD=... php tests/integracion.php https://TU-APP.onrender.com
   ```
   Estas pruebas crean registros de prueba.

**Notas sobre el plan gratuito de Render:**
- El servicio se duerme tras unos 15 minutos sin uso; la primera visita después tarda alrededor de un minuto.
  Ábrelo unos minutos antes de la presentación.
- Las sesiones se guardan en el contenedor, así que tras un redeploy hay que volver a iniciar sesión.

## Pruebas

```bash
php tests/unitarias.php                                      # 17 pruebas del dominio
SEED_PASSWORD=... php tests/integracion.php http://127.0.0.1:8000   # 21 pruebas HTTP de punta a punta
```
Los resultados están en [`docs/pruebas.md`](docs/pruebas.md).

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/arquitectura.md`](docs/arquitectura.md) | Capas, MVC, patrones de diseño, POO y seguridad |
| [`docs/uml.md`](docs/uml.md) | Casos de uso, clases, secuencias, estados, actividad y despliegue (Mermaid) |
| [`docs/diagramas.html`](docs/diagramas.html) | Los mismos diagramas listos para ver, imprimir o descargar en SVG |
| [`docs/modelo-datos.md`](docs/modelo-datos.md) | Diagrama entidad-relación, diccionario de datos y restricciones |
| [`docs/api.md`](docs/api.md) | Servicios web, ejemplos y códigos de respuesta |
| [`docs/api/E-Waste360.postman_collection.json`](docs/api/E-Waste360.postman_collection.json) | Colección de Postman |
| [`docs/manual-usuario.md`](docs/manual-usuario.md) | Manual de usuario por rol |
| [`docs/pruebas.md`](docs/pruebas.md) | Pruebas y resultados |

## Pendiente para el equipo

- [ ] Completar los integrantes y la URL en este README (y el nombre de la institución en `render.yaml`, *Configuración*).
- [ ] Crear las cuentas de Neon y Render y desplegar (sección anterior).
- [ ] Entregar al profesor las credenciales de prueba de forma segura, sin usar contraseñas personales.
- [ ] Tomar capturas y ponerlas en el manual (marcas `[Captura: …]`).
- [ ] Escribir el **reporte técnico de Desarrollo Sustentable**: cómo la plataforma apoya la economía circular y qué indicadores la miden.
- [ ] Ejecutar las pruebas contra la URL publicada y registrar la evidencia en `docs/pruebas.md`.
- [ ] Preparar la demostración de 10 a 15 minutos. Guion sugerido:
  1. Inicio de sesión de cada rol.
  2. Registrar un equipo.
  3. Diagnosticarlo y mostrar la recomendación en vivo.
  4. Registrar y validar un movimiento.
  5. Mostrar los indicadores y un acceso denegado (403).
- [ ] Reemplazar los datos de demostración por datos reales de la institución, si se cuenta con ellos.
