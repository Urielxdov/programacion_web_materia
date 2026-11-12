# Unidad 2 (remake)

Menú con frames igual al de Unidad 1: `PresentacionActividades.html` (superior + índice + principal).

| Carpeta | Actividad |
|---|---|
| `practica_1/` | Menú responsivo, menú hamburguesa (checkbox hack) y efecto de imagen con `clip-path` |
| `sitio_web_responsivo/` | Sitio responsivo (menú escritorio/móvil, video, galería WORK, about, contact) |
| `proyecto_individual/` | CRUD de invitados en PHP + PDO + MySQL + AJAX (fetch) |
| `equipo_3/` | Guía didáctica de arquitectura web (página instalable como app: `manifest.json`) y la guía en PDF |
| `equipo_4/` | API REST de tareas con Django REST Framework + JWT + PostgreSQL, lista para Render. Incluye la guía de la práctica (`.docx`). Ver `equipo_4/README.md` |
| `equipo_6/` | Laboratorio de Prompt Injection (chat con Gemini; pide tu propia API key en la página) |
| `proyecto_final/` | **E-Waste 360°**: PHP MVC + MySQL/PostgreSQL, API REST, despliegue en Render. Ver `proyecto_final/README.md` |

## Cómo verlo

Las prácticas HTML abren directo en el navegador. Para que funcione también el CRUD (PHP) desde el menú,
sirve toda la carpeta con PHP:

```bash
cd Unidad_2_remake
php -S localhost:8080
# abrir http://localhost:8080/PresentacionActividades.html
```

### Base de datos del CRUD

1. Arranca MySQL en XAMPP.
2. Ejecuta `proyecto_individual/schema.sql` en phpMyAdmin (crea `sistema_invitados`).
3. Si tu MySQL no está en el puerto 3307, cambia `$port` en `proyecto_individual/conexion.php`.
4. El primer usuario que se registre será **admin**; los siguientes, **asistente**.

## Archivos de muestra por reemplazar

- `practica_1/img/stranger-things.jpg`: imagen generada de muestra.
- `sitio_web_responsivo/img/portada-01.jpg` … `portada-09.jpg`: portadas de muestra.
- `sitio_web_responsivo/video/video.mp4`: no incluido; mientras tanto se muestra `img/poster.jpg`.

## Equipo 4 (API de tareas, Django)

Es un proyecto de Python, no se sirve con PHP. Corre en el puerto **8001** para no chocar con el proyecto final (8000):

```bash
cd Unidad_2_remake/equipo_4
python -m venv .venv && .venv\Scripts\activate     # Linux/macOS: source .venv/bin/activate
pip install -r requirements.txt
copy .env.example .env                            # ajusta SECRET_KEY y los datos de PostgreSQL (DB_*)
python manage.py migrate                          # requiere PostgreSQL encendido
python manage.py crear_demo                       # usuario demo (DEMO_USER / DEMO_PASSWORD)
python manage.py runserver 8001
```

Con Docker: `docker compose up --build`. Los detalles y el despliegue están en `equipo_4/README.md`.

## Proyecto final (E-Waste 360°)

Es una aplicación aparte con su propio Front Controller, así que no se abre dentro de los frames:

```bash
cd Unidad_2_remake/proyecto_final
php database/instalar.php --crear-bd     # solo la primera vez
php -S localhost:8000 -t public public/router.php
```

El enlace del índice abre <http://localhost:8000> en otra pestaña. Las instrucciones completas y el despliegue están en `proyecto_final/README.md`.
