<?php
require __DIR__.'/includes/app.php';
require __DIR__.'/includes/smtp.php';
$errors = []; $submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$treat = field('trato'); $name = field('nombre'); $email = field('correo'); $age = field('edad'); $course = field('curso'); $comment = field('comentario');
$courses = ['PHP', 'HTML', 'CSS', 'JavaScript'];
$interests = $_POST['intereses'] ?? [];
if (!is_array($interests)) $interests = [];
$interests = array_values(array_intersect($courses, array_filter($interests, 'is_string')));
if ($submitted) {
    check_csrf();
    if (!in_array($treat, ['Sr.', 'Sra.'], true)) $errors[] = 'Selecciona Sr. o Sra.';
    if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Escribe un nombre de hasta 80 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors[] = 'Escribe un correo válido.';
    if (filter_var($age, FILTER_VALIDATE_INT, ['options' => ['min_range'=>1, 'max_range'=>120]]) === false) $errors[] = 'La edad debe ser un entero entre 1 y 120.';
    if (!in_array($course, $courses, true)) $errors[] = 'Selecciona un curso.';
    if (mb_strlen($comment) > 1000) $errors[] = 'El comentario admite hasta 1000 caracteres.';
    if (field('acepto') !== 'si') $errors[] = 'Debes aceptar el procesamiento de estos datos de práctica.';
}
// Si el modo SMTP está activo, el resumen se envía al correo que escribió la persona (máximo uno por minuto y sesión).
$mailNotice = null;
if ($submitted && !$errors && smtp_enabled()) {
    if (time() - ($_SESSION['formulario_envio'] ?? 0) < 60) $mailNotice = 'Espera un minuto antes de pedir otro correo.';
    else {
        $_SESSION['formulario_envio'] = time();
        $body = "Hola $name,\n\nEstos son los datos que enviaste en el formulario de práctica:\n\nTratamiento: $treat\nCorreo: $email\nEdad: $age\nCurso preferido: $course\nIntereses: ".(implode(', ', $interests) ?: 'Ninguno')."\nComentario: ".($comment ?: 'Ninguno')."\n";
        $err = smtp_send(smtp_config(), $email, getenv('UNIDAD2_MAIL_FROM'), getenv('UNIDAD2_MAIL_FROM'), 'Resumen de tu formulario - Unidad 2', $body);
        if ($err !== null) { error_log('SMTP: '.$err); $mailNotice = 'No se pudo enviar el resumen por correo. Revisa la configuración SMTP y el registro de PHP.'; }
        else $mailNotice = 'El servidor SMTP aceptó el resumen para enviarlo a '.$email.'. Esto no confirma su entrega.';
    }
}
page('Creación y análisis de formularios'); errors($errors);
if ($mailNotice !== null) echo '<p class="notice" role="status">'.e($mailNotice).'</p>';
?>
<form method="post" class="panel">
<?php csrf(); ?>
<fieldset><legend>Tratamiento</legend><label class="inline"><input type="radio" name="trato" value="Sr." required <?= $treat === 'Sr.' ? 'checked' : '' ?>>Sr.</label><label class="inline"><input type="radio" name="trato" value="Sra." <?= $treat === 'Sra.' ? 'checked' : '' ?>>Sra.</label></fieldset>
<label>Nombre<input name="nombre" maxlength="80" required value="<?= e($name) ?>" autocomplete="name"></label>
<label>Correo electrónico<input type="email" name="correo" maxlength="254" required value="<?= e($email) ?>" autocomplete="email"></label>
<label>Edad<input type="number" name="edad" min="1" max="120" required value="<?= e($age) ?>"></label>
<label>Curso preferido<select name="curso" required><option value="">Selecciona un curso</option><?php foreach ($courses as $c): ?><option <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
<fieldset><legend>Temas de interés (puedes elegir varios)</legend><?php foreach ($courses as $c): ?><label class="inline"><input type="checkbox" name="intereses[]" value="<?= e($c) ?>" <?= in_array($c, $interests, true) ? 'checked' : '' ?>><?= e($c) ?></label><?php endforeach; ?></fieldset>
<label>Comentario (opcional)<textarea name="comentario" rows="4" maxlength="1000"><?= e($comment) ?></textarea></label>
<label class="inline"><input type="checkbox" name="acepto" value="si" required <?= field('acepto') === 'si' ? 'checked' : '' ?>>Acepto procesar estos datos de práctica.</label>
<button>Analizar formulario</button>
</form>
<?php if ($submitted && !$errors): ?>
<section class="panel"><h2>Datos recibidos mediante POST</h2><p>Hola <?= e($treat) ?> <strong><?= e($name) ?></strong>, encantado de saludarte.</p><dl><dt>Nombre</dt><dd><?= e($name) ?></dd><dt>Correo</dt><dd><?= e($email) ?></dd><dt>Edad</dt><dd><?= e($age) ?></dd><dt>Curso</dt><dd><?= e($course) ?></dd><dt>Intereses</dt><dd><?= e(implode(', ', $interests) ?: 'Ninguno') ?></dd></dl><?php if ($comment !== ''): ?><h3>Has dicho:</h3><p><?= nl2br(e($comment)) ?></p><?php endif; ?><p>PHP validó los datos en el servidor y escapó la salida HTML. Estos datos no se guardan<?= smtp_enabled() ? '; solo se envía un resumen al correo indicado' : '' ?>.</p></section>
<?php endif; endpage(); ?>
