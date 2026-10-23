<?php
require __DIR__.'/includes/app.php'; $errors = [];
$name = field('nombre'); $email = field('correo'); $message = field('mensaje');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($name === '' || mb_strlen($name) > 80 || preg_match('/[\r\n]/', $name)) $errors[] = 'Escribe un nombre válido de hasta 80 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || preg_match('/[\r\n]/', $email)) $errors[] = 'Escribe un correo válido.';
    if (mb_strlen($message) < 5 || mb_strlen($message) > 3000) $errors[] = 'La opinión debe contener entre 5 y 3000 caracteres.';
    if (!$errors) {
        $mode = getenv('UNIDAD2_MAIL_MODE') ?: 'archivo';
        $body = "Nombre: $name\nCorreo: $email\n\n$message";
        if ($mode === 'mail') {
            $to = getenv('UNIDAD2_MAIL_TO'); $from = getenv('UNIDAD2_MAIL_FROM');
            if (!$to || !$from || !filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to.$from)) $errors[] = 'Configura UNIDAD2_MAIL_TO y UNIDAD2_MAIL_FROM con correos válidos.';
            elseif (!mail($to, 'Opinion de la web - Unidad 2', $body, ['From'=>$from, 'Reply-To'=>$email, 'MIME-Version'=>'1.0', 'Content-Type'=>'text/plain; charset=UTF-8'])) $errors[] = 'El servidor de correo no aceptó el mensaje. Revisa la configuración SMTP de PHP.';
            else { $_SESSION['opinion_ok'] = 'El servidor de correo aceptó tu opinión para envío. Esto no confirma su entrega.'; redirect('opinion.php'); }
        } elseif ($mode === 'archivo') {
            store('opiniones', [], function ($items) use ($name, $email, $message) { $items[] = ['fecha'=>date(DATE_ATOM), 'nombre'=>$name, 'correo'=>$email, 'mensaje'=>$message]; return $items; });
            $_SESSION['opinion_ok'] = 'Opinión guardada localmente para la práctica. No se envió un correo; el envío real requiere configurar el servidor SMTP.'; redirect('opinion.php');
        } else $errors[] = 'UNIDAD2_MAIL_MODE debe ser archivo o mail.';
    }
}
page('Formulario de opinión'); errors($errors);
if (isset($_SESSION['opinion_ok'])) { echo '<p class="notice" role="status">'.e($_SESSION['opinion_ok']).'</p>'; unset($_SESSION['opinion_ok']); }
?>
<p class="notice">Modo actual: <strong><?= e(getenv('UNIDAD2_MAIL_MODE') ?: 'archivo') ?></strong>. En modo archivo, la opinión se conserva en el servidor sin enviar correo.</p>
<form method="post" class="panel"><?php csrf(); ?><label>Nombre<input name="nombre" required maxlength="80" value="<?= e($name) ?>"></label><label>Correo para responder<input type="email" name="correo" required maxlength="254" value="<?= e($email) ?>"></label><label>Tu opinión<textarea name="mensaje" required minlength="5" maxlength="3000" rows="6"><?= e($message) ?></textarea></label><button>Enviar opinión</button></form>
<?php endpage(); ?>
