<?php
require __DIR__.'/includes/app.php'; $errors = []; $name = field('nombre'); $message = field('mensaje');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Escribe un nombre de hasta 80 caracteres.';
    if ($message === '' || mb_strlen($message) > 1000) $errors[] = 'Escribe un mensaje de hasta 1000 caracteres.';
    if (!$errors) {
        store('visitas', [], function ($items) use ($name, $message) { $items[] = ['nombre'=>$name, 'mensaje'=>$message, 'fecha'=>date('d/m/Y H:i')]; return $items; });
        $_SESSION['firma_ok'] = true; redirect('visitas.php');
    }
}
$items = store('visitas', []); page('Libro de visitas'); errors($errors);
if (isset($_SESSION['firma_ok'])) { echo '<p class="notice" role="status">Tu firma se guardó correctamente.</p>'; unset($_SESSION['firma_ok']); }
?>
<form method="post" class="panel"><?php csrf(); ?><label>Nombre<input name="nombre" required maxlength="80" value="<?= e($name) ?>"></label><label>Mensaje público<textarea name="mensaje" required maxlength="1000" rows="4"><?= e($message) ?></textarea></label><button>Firmar el libro</button></form>
<h2>Firmas <span class="muted">(<?= count($items) ?>)</span></h2>
<?php if (!$items): ?><p class="panel">El libro aún no tiene firmas. Escribe la primera.</p><?php endif; ?>
<?php foreach (array_reverse($items) as $item): ?><article class="panel"><h3><?= e($item['nombre']) ?></h3><small><?= e($item['fecha']) ?></small><p class="message"><?= nl2br(e($item['mensaje'])) ?></p></article><?php endforeach; endpage(); ?>
