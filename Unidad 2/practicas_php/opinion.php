<?php
require __DIR__.'/includes/app.php'; $errors = [];
$name = field('nombre'); $email = field('correo'); $message = field('mensaje');
$univ = ['Nombre' => field('Nombre'), 'Mail' => field('Mail'), 'Comentario' => field('Comentario')];

function enviar(string $name, string $email, string $subject, string $body): ?string {
    $mode = getenv('UNIDAD2_MAIL_MODE') ?: 'archivo';
    if ($mode === 'archivo') {
        store('opiniones', [], function ($items) use ($name, $email, $body) { $items[] = ['fecha'=>date(DATE_ATOM), 'nombre'=>$name, 'correo'=>$email, 'mensaje'=>$body]; return $items; });
        $_SESSION['opinion_ok'] = 'Opinión guardada localmente para la práctica. No se envió un correo; el envío real requiere configurar el servidor SMTP.';
        return null;
    }
    if ($mode !== 'mail' && $mode !== 'smtp') return 'UNIDAD2_MAIL_MODE debe ser archivo, mail o smtp.';
    $to = getenv('UNIDAD2_MAIL_TO'); $from = getenv('UNIDAD2_MAIL_FROM');
    if (!$to || !$from || !filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to.$from)) return 'Configura UNIDAD2_MAIL_TO y UNIDAD2_MAIL_FROM con correos válidos.';
    if ($mode === 'mail') {
        // El @ evita que PHP muestre el aviso técnico si no hay servidor de correo; el resultado se comprueba con el valor devuelto.
        if (!@mail($to, $subject, $body, ['From'=>$from, 'Reply-To'=>$email, 'MIME-Version'=>'1.0', 'Content-Type'=>'text/plain; charset=UTF-8'])) return 'El servidor de correo no aceptó el mensaje. Revisa la configuración SMTP de PHP.';
        $_SESSION['opinion_ok'] = 'El servidor de correo aceptó tu opinión para envío. Esto no confirma su entrega.';
        return null;
    }
    require_once __DIR__.'/includes/smtp.php';
    $err = smtp_send(smtp_config(), $to, $from, $email, $subject, $body);
    if ($err !== null) { error_log('SMTP: '.$err); return 'No se pudo enviar el correo por SMTP. Revisa la configuración y el registro de PHP.'; }
    $_SESSION['opinion_ok'] = 'El servidor SMTP aceptó tu opinión para envío. Esto no confirma su entrega.';
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (field('formulario') === 'universal') {
        if ($univ['Mail'] === '') $errors[] = 'No te olvides de rellenar tu dirección de correo electrónico.';
        elseif (!filter_var($univ['Mail'], FILTER_VALIDATE_EMAIL) || strlen($univ['Mail']) > 254 || preg_match('/[\r\n]/', $univ['Mail'])) $errors[] = 'Escribe un correo válido.';
        else {
            $text = "Datos enviados:\n\n"; $fields = 0;
            foreach ($_POST as $key => $value) {
                if ($key === 'csrf' || $key === 'formulario' || !preg_match('/^\w{1,40}$/', (string)$key) || ++$fields > 30) continue;
                if (is_array($value)) $value = implode(', ', array_filter($value, 'is_string'));
                if (is_string($value)) $text .= "$key: ".mb_substr(trim($value), 0, 3000)."\n";
            }
            $err = enviar($univ['Nombre'] ?: 'Formmailer', $univ['Mail'], 'Datos enviados por formulario - Unidad 2', $text);
            if ($err !== null) $errors[] = $err; else redirect('opinion.php');
        }
    } else {
        if ($name === '' || mb_strlen($name) > 80 || preg_match('/[\r\n]/', $name)) $errors[] = 'Escribe un nombre válido de hasta 80 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || preg_match('/[\r\n]/', $email)) $errors[] = 'Escribe un correo válido.';
        if (mb_strlen($message) < 5 || mb_strlen($message) > 3000) $errors[] = 'La opinión debe contener entre 5 y 3000 caracteres.';
        if (!$errors) {
            $err = enviar($name, $email, 'Opinion de la web - Unidad 2', "Nombre: $name\nCorreo: $email\n\n$message");
            if ($err !== null) $errors[] = $err; else redirect('opinion.php');
        }
    }
}
page('Formulario de opinión'); errors($errors);
if (isset($_SESSION['opinion_ok'])) { echo '<p class="notice" role="status">'.e($_SESSION['opinion_ok']).'</p>'; unset($_SESSION['opinion_ok']); }
?>
<p class="notice">Modo actual: <strong><?= e(getenv('UNIDAD2_MAIL_MODE') ?: 'archivo') ?></strong>. En modo archivo, la opinión se conserva en el servidor sin enviar correo.</p>
<h2>Mailer de opinión</h2>
<form method="post" class="panel"><?php csrf(); ?><label>Nombre<input name="nombre" required maxlength="80" value="<?= e($name) ?>"></label><label>Correo para responder<input type="email" name="correo" required maxlength="254" value="<?= e($email) ?>"></label><label>Tu opinión<textarea name="mensaje" required minlength="5" maxlength="3000" rows="6"><?= e($message) ?></textarea></label><button>Enviar opinión</button></form>
<h2>Formmailer universal</h2>
<p>Recorre con <code>foreach</code> todos los campos que llegan por POST y arma el mensaje con ellos. Solo exige un campo llamado <code>Mail</code> con la dirección del remitente.</p>
<form method="post" class="panel"><?php csrf(); ?><input type="hidden" name="formulario" value="universal"><label>Nombre<input name="Nombre" maxlength="80" value="<?= e($univ['Nombre']) ?>"></label><label>Mail<input type="email" name="Mail" required maxlength="254" value="<?= e($univ['Mail']) ?>"></label><label>Comentario<textarea name="Comentario" rows="4" maxlength="3000"><?= e($univ['Comentario']) ?></textarea></label><label class="inline"><input type="checkbox" name="partner" value="newsletter">Quiero recibir el boletín</label><button>Enviar</button></form>
<?php endpage(); ?>
