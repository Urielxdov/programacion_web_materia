# Equipo 1 – Enviar correos con PHP

Práctica: formulario de opinión que envía un correo con `mail()` de PHP.
La guía del equipo (`Guia enviar correo con VS Code.pdf`) explica la configuración con XAMPP + sendmail + Gmail.

| Archivo | Qué es |
|---|---|
| `index.php` | Formulario (nombre, correo, opinión). Envía a `$receiverMail` o a la variable `EQUIPO1_RECEIVER` |
| `start.sh` | Arranque del contenedor: genera la configuración de sendmail (equivale a `sendmail.ini` del PDF) |
| `msmtprc` | Configuración de respaldo (entrega a Mailpit) cuando no hay credenciales SMTP |
| `Guia enviar correo con VS Code.pdf` | Guía paso a paso del equipo |

## Con Docker
```
cd Unidad_2_remake
docker compose up -d
```
Formulario: <http://localhost:8080/equipo_1/index.php>

**Cómo decide a dónde enviar** (`start.sh`):
- Si existe `practicas_php/.env` con `UNIDAD2_SMTP_USER` y `UNIDAD2_SMTP_PASS` (contraseña de aplicación de Gmail), **envía por Gmail real**. Ese archivo está ignorado por git; no copies la contraseña a otros archivos.
- Si no existe, el correo llega a la bandeja local <http://localhost:8025> (Mailpit) y no sale a internet.

Gmail envía siempre desde la cuenta autenticada; el correo que escribe quien llena el formulario queda como `Reply-To`.

## Con XAMPP / VS Code (como el PDF)
Configura `sendmail.ini` y `php.ini` (`sendmail_path`) según la guía, cambia `$receiverMail` y sirve la carpeta con PHP Server.
