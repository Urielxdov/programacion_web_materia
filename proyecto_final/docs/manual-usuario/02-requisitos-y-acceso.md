# 2. Requisitos y acceso

[Volver a portada e índice](../manual-usuario.md)

## 2.1. Navegadores y dispositivos compatibles

La interfaz se adapta a computadoras, celulares y tabletas. Necesitas conexión con el servidor y un navegador moderno con JavaScript, cookies y almacenamiento del sitio habilitados.

Se recomienda utilizar una versión actualizada de Chrome, Edge, Firefox o Safari. Esta es una recomendación de uso: el repositorio no documenta una matriz de compatibilidad probada para versiones específicas.

No necesitas instalar una aplicación móvil. Para consultar las exportaciones CSV, utiliza una hoja de cálculo. La impresión depende de las opciones disponibles en el navegador y el dispositivo.

## 2.2. Dirección del sitio

Utiliza la dirección proporcionada por la institución. La documentación revisada no identifica una URL pública definitiva.

| Entorno preparado por el personal técnico | Dirección desde la misma computadora |
|---|---|
| Docker, puerto predeterminado | <http://localhost:8080> |
| Desarrollo local con Vite, puerto predeterminado | <http://localhost:5173> |

Estas direcciones requieren que la instalación esté iniciada. El puerto puede variar según su configuración. Para entrar desde otro dispositivo, solicita una dirección accesible por la red: `localhost` identifica al dispositivo desde el que navegas.

Las rutas principales son inicio (`/`), guía (`/guia`), campañas (`/campanas`), acceso del personal (`/entrar`) y panel (`/panel`).

## 2.3. Tipos de usuario y lo que puede hacer cada uno

| Función | Visitante | Capturista | Responsable ambiental | Administrador |
|---|---|---|---|---|
| Consultar inicio, guía y campañas públicas | Sí | Sí | Sí | Sí |
| Registrar participación pública | Sí | Sí | Sí | Sí |
| Consultar panel, equipos e historial | No | Sí | Sí | Sí |
| Registrar equipos y editar datos permitidos | No | Sí | Sí | Sí |
| Diagnosticar, registrar reparaciones y destinos | No | No | Sí | Sí |
| Gestionar campañas y consultar participantes | No | No | Sí | Sí |
| Consultar reportes y descargar CSV | No | No | Sí | Sí |
| Crear y editar gestores | No | No | Sí | Sí |
| Verificar gestores o revocar su verificación | No | No | No | Sí |
| Gestionar usuarios, roles y categorías | No | No | No | Sí |

El visitante no necesita cuenta. Los otros perfiles deben iniciar sesión. El menú muestra las opciones del rol y el servidor valida los permisos de cada operación. Las acciones sobre un equipo también dependen de su condición y destino.
