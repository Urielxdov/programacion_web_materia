# Unidad 2 · Prácticas de PHP

Implementación de las siete prácticas indicadas en la consigna, compatible con PHP 8.2 o posterior. No requiere Composer ni una base de datos.

## Ejecutar en VS Code

Desde la terminal, dentro de `Unidad 2`:

```powershell
php -S localhost:8000
```

Abre <http://localhost:8000>. También puedes usar Apache/XAMPP y abrir la carpeta desde localhost. Live Server no ejecuta PHP. La carpeta `data` debe permitir escritura al usuario que ejecuta PHP. Necesitas las extensiones `mbstring` y `SimpleXML`; `curl` y certificados CA para el RSS remoto.

## Prácticas

| Archivo | Práctica | Páginas indicadas |
| --- | --- | --- |
| formularios.php | Botones de opción Sr./Sra., texto, lista, casillas y comentario con `nl2br`; validación y análisis | 27–36 |
| proteccion.php | Dos contraseñas (`if/elseif` y `switch`), dos secciones protegidas, sesión y cierre de sesión | 37–39 |
| opinion.php | Mailer de opinión y formmailer universal (`foreach` sobre `$_POST`) | 44–48 |
| contador.php | Contador persistente de cargas de la página | 49–51 |
| visitas.php | Libro con nombre, e-mail, IP, protección contra recarga y saludo según la hora | 52–58 |
| encuesta.php | Votos persistentes y gráfica rojo, amarillo, morado y azul cielo | 62–66 |
| rss.php | Lectura de un canal RSS local o BBC Mundo | 67–72 |

Las contraseñas de demostración son **unidad2** (sección 1) y **practica2** (sección 2). Para cambiarlas genera un hash con `php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT);"` y establece `UNIDAD2_PASSWORD_HASH` y `UNIDAD2_PASSWORD_HASH_2` antes de iniciar el servidor. Es un ejercicio local; el acceso no tiene recuperación de contraseña ni limitación de intentos.

Los datos se guardan como JSON dentro de archivos PHP que comienzan con `<?php exit; ?>`, para no mostrarlos si se solicitan por HTTP a un servidor PHP. Apache también bloquea `data` con `.htaccess`. No sirvas esta carpeta con un servidor estático. Puedes situar los datos fuera de la carpeta pública mediante `UNIDAD2_DATA_DIR`. El contador aumenta con cada carga; la encuesta permite un voto por sesión, sin identificar personas.

## Correo

El modo predeterminado `archivo` guarda la opinión en `data/opiniones.php` y avisa que no se envió correo. Para enviar realmente:

```powershell
$env:UNIDAD2_MAIL_MODE = 'mail'
$env:UNIDAD2_MAIL_TO = 'destinatario@tu-dominio.com'
$env:UNIDAD2_MAIL_FROM = 'remitente@tu-dominio.com'
php -S localhost:8000
```

### Modo `smtp` (recomendado)

`includes/smtp.php` es un cliente SMTP propio (STARTTLS o SSL, autenticación LOGIN) que no depende de `php.ini` ni de Composer. Copia `.env.example` a `.env` (ignorado por Git), completa los valores y exporta las variables antes de iniciar el servidor, o pásalas a Docker con `--env-file .env`:

```powershell
docker run -d --name practicas_php -p 8000:8000 --env-file .env -v "${PWD}:/app" -w /app php:8.2-cli php -S 0.0.0.0:8000
```

Variables: `UNIDAD2_MAIL_MODE=smtp`, `UNIDAD2_MAIL_TO`, `UNIDAD2_MAIL_FROM`, `UNIDAD2_SMTP_HOST`, `UNIDAD2_SMTP_PORT`, `UNIDAD2_SMTP_SECURE` (`tls` para 587, `ssl` para 465), `UNIDAD2_SMTP_USER` y `UNIDAD2_SMTP_PASS`. Con Gmail o Outlook con verificación en dos pasos se necesita una contraseña de aplicación.

### Modo `mail`

Configura además el transporte de `mail()` en `php.ini`: en Windows, `SMTP`, `smtp_port` y `sendmail_from` para un relay compatible, o `sendmail_path` para un transporte instalado. `mail()` no proporciona por sí solo autenticación SMTP moderna. Usa un transporte local que gestione TLS/autenticación si tu proveedor lo requiere. No guardes credenciales en el repositorio. Que `mail()` devuelva éxito indica aceptación por el transporte, no entrega en la bandeja del destinatario.

## RSS y evidencia

El canal local `assets/noticias.xml` funciona sin Internet y está rotulado como demostración. El canal BBC Mundo requiere conexión; los errores se muestran sin sustituirlos por noticias inventadas. El lector procesa XML con SimpleXML, limita la descarga y escapa los textos antes de generar HTML.

Para la entrega abre `rss.php` en un navegador y captura su ejecución. La evidencia generada, cuando está disponible, se encuentra en `evidencias/lector-rss.png`. Entrega también `rss.php`, el XML y `includes/app.php` para mostrar el código utilizado.

## Relación con el libro

Cada práctica sigue el capítulo del cuaderno `PC_Cuadernos_28_-_PHP_5.pdf` que indica la consigna. Las diferencias son de seguridad: contraseñas guardadas como hash, salida escapada con `htmlspecialchars`, token CSRF en los formularios, datos con bloqueo de archivo y sesión en lugar de cookies sueltas para la encuesta.
