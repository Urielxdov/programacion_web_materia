# Guía de proyectos – Unidad 2 (remake)

Qué es cada carpeta, cómo prepararla y cómo entrar a verla.

## Resumen

| Carpeta | Qué es | Necesita | Cómo se abre |
|---|---|---|---|
| `assets/` | Logos del menú (no se abre solo) | – | – |
| `practica_1/` | Práctica 1: menús responsivos y efecto de imagen | Nada | Doble clic al `.html` |
| `sitio_web_responsivo/` | Sitio web responsivo (menú, galería, about, contact) | Nada | Doble clic a `index.html` |
| `equipo_3/` | "Periódico Web" instalable (PWA) + guía PDF | Nada | Doble clic a `index.html` |
| `equipo_6/` | Laboratorio de Prompt Injection (chat con Gemini) | API key de Gemini | Doble clic a `index.html` |
| `proyecto_individual/` | Sistema de citas (PHP + MySQL + AJAX) | PHP + MySQL | `php -S` → <http://localhost:8080> |
| `equipo_4/` | API REST de tareas (Django + JWT + PostgreSQL) | Python o Docker + PostgreSQL | <http://127.0.0.1:8001> (o 8000 con Docker) |
| `proyecto_final/` | E-Waste 360° (PHP MVC + API REST) | PHP 8.1+ + MySQL/PostgreSQL | <http://localhost:8000> |

El **menú con frames** que une todo es `PresentacionActividades.html`. Los enlaces a `equipo_4` y `proyecto_final` apuntan a `localhost`, así que solo funcionan con esos servidores encendidos.

---

## Qué es cada una

### `practica_1/`
Tres páginas sueltas de HTML/CSS: `menu_responsivo.html`, `menu_hamburguesa.html` (checkbox hack) y `efecto de imagen.html` (`clip-path`, con `img/stranger-things.jpg`).

### `sitio_web_responsivo/`
Sitio responsivo "Mi sitio web responsivo": `index.html`, `css/main.css`, `js/main.js`, logos y redes en SVG, `portada-01…09.jpg` y `poster.jpg`. Falta `video/video.mp4`; mientras tanto se muestra el poster.

### `proyecto_individual/` – Sistema de citas del Dr. Xavier
CRUD en PHP + PDO + MySQL con AJAX (`fetch`).
- `index.php` login y registro; `dashboard.php` panel; `altas.php` y `editar.php` formularios; `form_campos.php` campos compartidos; `catalogos.php` listas y validaciones (CURP, fechas, países); `acciones.php` backend JSON; `app.js` frontend; `schema.sql` base de datos.
- Roles: el **primer usuario que se registre es `admin`** (alta, consulta, modificación y borrado); los siguientes son `asistente` (solo alta y consulta).

### `equipo_3/` – Periódico Web
Página `index.html` empaquetada como PWA (`manifest.json`, `workbox-config.js`), con capturas (`web1.jpg`, `web2.jpg`) y la guía `GUÍA_PW.pdf`. Su `README.md` está prácticamente vacío.

### `equipo_4/` – API REST de tareas
Django REST Framework + JWT + PostgreSQL. `config/` (ajustes), `tasks/` (modelo, serializer, vistas, frontend en `templates/index.html`), `clientes/cliente.py` (cliente con `requests`). Incluye Docker, `render.yaml`, `DEPLOY_GCP.md` y la guía `Guia_Practica_Programacion_Web.docx`.

### `equipo_6/` – Laboratorio de Seguridad en IA
Un solo `index.html`: chat contra Gemini para practicar prompt injection. La API key se pega en la propia página (no se guarda en el repo).

### `proyecto_final/` – E-Waste 360°
Proyecto integrador (Programación Web + Desarrollo Sustentable, AGO-DIC 2026). Registra equipos electrónicos en desuso, evalúa si reutilizarlos, repararlos o enviarlos a gestión, documenta movimientos, organiza campañas e indicadores. PHP con MVC (Front Controller, Repository, Strategy, Factory), API REST, MySQL o PostgreSQL, Docker y Render. Documentación en `proyecto_final/docs/`.

---

## Cómo prepararlos y entrar

### 0. Requisitos de la PC
| Herramienta | Para qué proyecto |
|---|---|
| Navegador | Todos |
| PHP 8.1+ con `pdo_mysql` (XAMPP sirve) | `proyecto_individual`, `proyecto_final` |
| MySQL/MariaDB encendido | `proyecto_individual`, `proyecto_final` |
| Python 3 **o** Docker Desktop, y PostgreSQL | `equipo_4` |

> Estado de esta PC al preparar la guía: **PHP y Python no están en el PATH**, MySQL (3306) y PostgreSQL (5432) están escuchando, y Docker Desktop está instalado pero **apagado**. Instala/activa lo que falte según el proyecto.

### 1. Solo abrir (sin instalar nada)
`practica_1/*.html`, `sitio_web_responsivo/index.html`, `equipo_3/index.html`, `equipo_6/index.html`: doble clic o arrastrar al navegador.
- `equipo_6`: pega tu API key de Google Gemini arriba y pulsa "Conectar al Servidor".

### 2. Menú con frames + CRUD de citas
```bash
cd Unidad_2_remake
php -S localhost:8080
```
Abre <http://localhost:8080/PresentacionActividades.html> (o directo <http://localhost:8080/proyecto_individual/index.php>).

Base de datos, una sola vez:
1. Enciende MySQL.
2. Ejecuta `proyecto_individual/schema.sql` (phpMyAdmin o `mysql < schema.sql`). Crea `sistema_citas` con `usuarios` y `citas`.
3. **Puerto:** `conexion.php` usa el **3307** por defecto. Si tu MySQL está en 3306, arranca así:
   ```powershell
   $env:CITAS_DB_PORT="3306"; php -S localhost:8080
   ```
   (también existen `CITAS_DB_HOST`, `CITAS_DB_USER`, `CITAS_DB_PASS`).
4. En el login, **regístrate primero**: ese usuario será admin.

### 3. `proyecto_final` (E-Waste 360°)
```powershell
cd Unidad_2_remake\proyecto_final
copy .env.example .env                   # ya está creado en esta PC
$env:SEED_PASSWORD="Demo2026x"           # mínimo 8 caracteres, letras y números
php database/instalar.php --crear-bd     # solo la primera vez
php -S localhost:8000 -t public public/router.php
```
Entra a <http://localhost:8000> con `admin`, `responsable` o `capturista` y la contraseña de `SEED_PASSWORD`.
- `.env` apunta a MySQL en `127.0.0.1:3306`, usuario `root` sin contraseña; ajústalo si tu MySQL es distinto.
- Para PostgreSQL, descomenta `DATABASE_URL` en `.env` y habilita `pdo_pgsql`.
- Reiniciar datos: `php database/instalar.php --reiniciar`.
- Documentación: `proyecto_final/docs/` (arquitectura, API, manual de usuario, diagramas en `diagramas.html`).

### 4. `equipo_4` (API de tareas)
**Opción A – Docker (recomendada)**
```powershell
cd Unidad_2_remake\equipo_4
copy .env.example .env      # edita DEMO_PASSWORD y DB_PASSWORD (y SECRET_KEY)
docker compose up --build   # abre Docker Desktop antes
```
Queda en <http://localhost:8000> (PostgreSQL en el puerto 5433).

**Opción B – Python local** (requiere PostgreSQL)
```powershell
cd Unidad_2_remake\equipo_4
python -m venv .venv; .venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env      # edita SECRET_KEY, DB_* y DEMO_PASSWORD
python manage.py migrate
python manage.py crear_demo
python manage.py runserver 8001
```
Queda en <http://127.0.0.1:8001>, que es el enlace que usa el menú.

> Ojo: Docker publica el **8000**, el mismo puerto que `proyecto_final`. No los corras a la vez, o usa la opción B (8001). Con Docker, el enlace del menú (8001) no coincide.

Detalles y despliegue (Render, Cloud Run): `equipo_4/README.md` y `equipo_4/DEPLOY_GCP.md`.

---

## Problemas comunes
| Síntoma | Causa / solución |
|---|---|
| `php` no se reconoce | PHP no está en el PATH; agrega `C:\xampp\php` o instala PHP. |
| "Error de conexión a la base de datos" en citas | Puerto equivocado: usa `CITAS_DB_PORT=3306` o enciende MySQL. |
| Los enlaces de equipo_4 / proyecto final del menú no cargan | Falta levantar su servidor (8001 / 8000). |
| `docker compose` falla al conectar | Docker Desktop está apagado. |
| El menú se ve vacío en el navegador | Los `frameset` son HTML antiguo; abre `indiceActividades.html` directo si tu navegador los bloquea. |
