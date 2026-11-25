<?php
// Equipo 1: formulario de opinion que envia un correo con mail() de PHP.
// Correo destino: cambia $receiverMail (o define la variable de entorno EQUIPO1_RECEIVER).
$receiverMail = getenv('EQUIPO1_RECEIVER') ?: 'Urieledgar878@gmail.com';

$status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['correo'] ?? '');
    $message = trim($_POST['mensaje'] ?? '');

    if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $name . $email)) {
        $status = 'error:Completa todos los campos con datos válidos.';
    } else {
        $subject = 'Opinión de ' . $name;
        $body = "Nombre: $name\nCorreo: $email\n\n$message";
        $headers = "From: $email\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
        $status = mail($receiverMail, $subject, $body, $headers)
            ? 'ok:Tu opinión fue enviada.'
            : 'error:No se pudo enviar el correo. Revisa la configuración de sendmail (ver README.md).';
    }
}
[$type, $text] = $status ? explode(':', $status, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulario de opinión</title>
    <style>
        body { font-family: sans-serif; max-width: 480px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin-bottom: 1rem; }
        input, textarea { width: 100%; padding: .5rem; box-sizing: border-box; }
        .ok { color: #1a7f37; } .error { color: #c62828; }
        small { color: #555; }
    </style>
</head>
<body>
    <h1>Formulario de opinión</h1>
    <?php if ($text): ?><p class="<?= $type ?>"><?= htmlspecialchars($text) ?></p><?php endif; ?>
    <form method="post">
        <label>Nombre<input name="nombre" required></label>
        <label>Correo<input type="email" name="correo" required></label>
        <label>Opinión<textarea name="mensaje" rows="5" required></textarea></label>
        <button>Enviar</button>
    </form>
    <p><small>Con Docker: si existe <code>practicas_php/.env</code> se envía por Gmail real; si no, el correo queda en la bandeja local
        <a href="http://localhost:8025" target="_blank">http://localhost:8025</a> (Mailpit).</small></p>
    <p><small><a href="Guia enviar correo con VS Code.pdf">Guía para configurar el envío con VS Code (PDF)</a></small></p>
</body>
</html>
