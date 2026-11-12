<?php
require __DIR__.'/includes/app.php'; $errors = []; $name = field('nombre'); $email = field('correo'); $message = field('mensaje');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$hour = (int)date('H');
if ($hour <= 11) $greeting = '¡Buenos días!';
elseif ($hour <= 14) $greeting = '¡Tómate un descanso!';
elseif ($hour <= 17) $greeting = '¡Buenas tardes!';
else $greeting = '¡Buenas noches!';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Escribe un nombre de hasta 80 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors[] = 'Escribe un correo válido.';
    if ($message === '' || mb_strlen($message) > 1000) $errors[] = 'Escribe un mensaje de hasta 1000 caracteres.';
    if (!$errors) {
        $all = store('visitas', []); $last = $all ? end($all) : null;
        // Mismo visitante y mismo texto que la última firma: se trata como una recarga y no se guarda de nuevo.
        if ($last && ($last['ip'] ?? '') === $ip && $last['nombre'] === $name && $last['mensaje'] === $message) $errors[] = 'Ese comentario ya está en el libro.';
        else {
            store('visitas', [], function ($items) use ($name, $email, $message, $ip) { $items[] = ['nombre'=>$name, 'correo'=>$email, 'mensaje'=>$message, 'fecha'=>date('d/m/Y H:i'), 'ip'=>$ip]; return $items; });
            $_SESSION['firma_ok'] = true; redirect('visitas.php');
        }
    }
}
$items = store('visitas', []); page('Libro de visitas'); errors($errors);
if (isset($_SESSION['firma_ok'])) { echo '<p class="notice" role="status">Tu firma se guardó correctamente.</p>'; unset($_SESSION['firma_ok']); }
?>
<p class="notice"><?= e($greeting) ?> Tu IP: <?= e($ip) ?></p>
<form method="post" class="panel"><?php csrf(); ?><label>Nombre<input name="nombre" required maxlength="80" value="<?= e($name) ?>"></label><label>Correo electrónico<input type="email" name="correo" required maxlength="254" value="<?= e($email) ?>"></label><label>Mensaje público<textarea name="mensaje" required maxlength="1000" rows="4"><?= e($message) ?></textarea></label><button>Firmar el libro</button></form>
<h2>Firmas <span class="muted">(<?= count($items) ?>)</span></h2>
<?php if (!$items): ?><p class="panel">El libro aún no tiene firmas. Escribe la primera.</p><?php endif; ?>
<?php foreach (array_reverse($items) as $item): ?><article class="panel"><h3><?= e($item['nombre']) ?><?php if (!empty($item['correo'])): ?> <small>(<a href="mailto:<?= e($item['correo']) ?>"><?= e($item['correo']) ?></a>)</small><?php endif; ?></h3><small><?= e($item['fecha']) ?></small><p class="message"><?= nl2br(e($item['mensaje'])) ?></p></article><?php endforeach; endpage(); ?>
