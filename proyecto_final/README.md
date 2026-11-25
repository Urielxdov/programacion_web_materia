# E-Waste 360°

Plataforma web para la gestión sustentable de residuos electrónicos y tecnológicos (RAEE) en una institución educativa.
Permite **registrar** equipos fuera de uso, **diagnosticarlos** para decidir si se reparan o reutilizan, **dar seguimiento**
a su destino con un historial verificable, **organizar campañas de recolección** y **generar indicadores** ambientales,
sociales y económicos.

> Proyecto integrador · Programación Web y Desarrollo Sustentable · AGO-DIC 2026

## Tecnologías

| Capa | Tecnología |
|---|---|
| Presentación (Vista) | React 19 + TypeScript, Vite, Tailwind CSS 4, React Router 7 |
| Estado del servidor | TanStack Query 5 (caché, carga, errores, invalidación) |
| Estado de interfaz | Zustand 5 (tamaño de letra, contraste, menú, borrador del registro por pasos) |
| Aplicación (Controlador) | NestJS 11 (controladores, DTOs con class-validator, guards de sesión, rol y CSRF) |
| Negocio (Modelo) | Entidades con reglas de dominio, servicios, patrones Repository, Strategy, Factory y Observer |
| Persistencia | PostgreSQL 16 con TypeORM (10 tablas relacionadas, migraciones versionadas) |
| Servicios web | API REST JSON documentada con Swagger (`/api/docs`) |
| Seguridad | Sesiones en servidor (express-session + PostgreSQL), argon2id, CSRF double-submit, Helmet, rate limiting |
| Despliegue | Render (Web Service con HTTPS) + Neon (PostgreSQL administrado) |

## Estructura

```
proyecto_final/
├── backend/            API NestJS (sirve también el frontend compilado en producción)
│   ├── src/common/     guards, decoradores, filtros, eventos, repositorio base
│   ├── src/database/   configuración TypeORM, migraciones y datos de demostración
│   ├── src/modules/    auth, usuarios, catalogos, equipos, diagnosticos, reparaciones,
│   │                   movimientos, campanas, indicadores
│   └── test/           pruebas e2e de los servicios web
├── frontend/           SPA React
│   └── src/            api/ (cliente + hooks TanStack), stores/ (Zustand), components/, features/
├── docs/               arquitectura, UML, modelo de datos, API, manual de usuario, reporte
├── docker-compose.yml  PostgreSQL local
└── render.yaml         despliegue en Render
```

## Instalación local

Requisitos: **Node.js 22+**, **Docker** (o un PostgreSQL 16 propio).

```bash
# 1. Base de datos local (puerto 5544)
docker compose up -d db

# 2. Backend
cd backend
cp .env.example .env        # ajusta los secretos si quieres
npm install
npm run seed                # crea las tablas (migraciones) y los datos de demostración
npm run start:dev           # API en http://localhost:3000/api — Swagger en /api/docs

# 3. Frontend (otra terminal)
cd frontend
npm install
npm run dev                 # http://localhost:5173 (redirige /api al backend)
```

`npm run seed -- --reset` borra todo y vuelve a crear los datos de demostración.

### Cuentas de prueba

El seed crea tres cuentas (la contraseña es el valor de `SEED_DEMO_PASSWORD`, por defecto `Demo2026!` en local):

| Rol | Correo |
|---|---|
| Administrador | admin@ewaste360.mx |
| Responsable ambiental | responsable@ewaste360.mx |
| Capturista | capturista@ewaste360.mx |

> En el despliegue público se usa otra contraseña, entregada al docente por un medio privado.

### Pruebas

```bash
cd backend
npm test            # pruebas unitarias (Strategy, Factory, reglas del Equipo)
npm run test:e2e    # pruebas de los servicios web contra la base ewaste360_test
```

Para las e2e, crea antes la base de pruebas:
`docker exec ewaste360-db psql -U ewaste -d ewaste360 -c "CREATE DATABASE ewaste360_test"`.
Los resultados están en [`docs/evidencia-pruebas-e2e.txt`](docs/evidencia-pruebas-e2e.txt) y
[`docs/evidencia-pruebas-unitarias.txt`](docs/evidencia-pruebas-unitarias.txt).

## Despliegue (Render + Neon, plan gratuito)

1. Crea un proyecto en [Neon](https://neon.tech) y copia la cadena de conexión (`postgres://…?sslmode=require`).
2. En [Render](https://render.com): **New → Blueprint** y selecciona este repositorio. `render.yaml` crea el
   Web Service con `npm run build` / `npm start`. Si el proyecto no está en la raíz del repositorio, indica
   `proyecto_final` como *Root Directory*.
3. Captura `DATABASE_URL` con la cadena de Neon. `SESSION_SECRET` y `CSRF_SECRET` se generan solos.
4. Al arrancar, la app ejecuta las migraciones automáticamente.
5. Carga los datos de demostración desde tu computadora, apuntando a Neon:
   ```bash
   cd backend
   DATABASE_URL="postgres://…neon…" DB_SSL=true SEED_DEMO_PASSWORD="<contraseña-segura>" npm run seed
   ```
   (En PowerShell: `$env:DATABASE_URL="…"; $env:DB_SSL="true"; $env:SEED_DEMO_PASSWORD="…"; npm run seed`.)

> El plan gratuito de Render apaga el servicio tras 15 minutos sin visitas; la primera carga tarda ~50 s.
> Abre la URL unos minutos antes de la demostración.

## Documentación

- [Arquitectura, MVC y patrones de diseño (con diagramas UML)](docs/arquitectura.md)
- [Modelo de datos (entidad-relación)](docs/modelo-datos.md)
- [Servicios web (API REST)](docs/api.md)
- [Seguridad](docs/seguridad.md)
- [Manual de usuario](docs/manual-usuario.md)
- [Reporte de sustentabilidad](docs/reporte-sustentabilidad.md)
- [Cumplimiento de la rúbrica y guion de la demostración](docs/rubrica-y-demo.md)
