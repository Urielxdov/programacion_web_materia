<?php
require __DIR__.'/includes/app.php';
$errors = []; $submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$name = field('nombre'); $email = field('correo'); $age = field('edad'); $course = field('curso');
$courses = ['PHP', 'HTML', 'CSS', 'JavaScript'];
$interests = $_POST['intereses'] ?? [];
if (!is_array($interests)) $interests = [];
$interests = array_values(array_intersect($courses, array_filter($interests, 'is_string')));
if ($submitted) {
    check_csrf();
    if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Escribe un nombre de hasta 80 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors[] = 'Escribe un correo válido.';
    if (filter_var($age, FILTER_VALIDATE_INT, ['options' => ['min_range'=>1, 'max_range'=>120]]) === false) $errors[] = 'La edad debe ser un entero entre 1 y 120.';
    if (!in_array($course, $courses, true)) $errors[] = 'Selecciona un curso.';
    if (field('acepto') !== 'si') $errors[] = 'Debes aceptar el procesamiento de estos datos de práctica.';
}
page('Creación y análisis de formularios'); errors($errors);
?>
<form method="post" class="panel">
<?php csrf(); ?>
<label>Nombre<input name="nombre" maxlength="80" required value="<?= e($name) ?>" autocomplete="name"></label>
<label>Correo electrónico<input type="email" name="correo" maxlength="254" required value="<?= e($email) ?>" autocomplete="email"></label>
<label>Edad<input type="number" name="edad" min="1" max="120" required value="<?= e($age) ?>"></label>
<label>Curso preferido<select name="curso" required><option value="">Selecciona un curso</option><?php foreach ($courses as $c): ?><option <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
<fieldset><legend>Temas de interés (puedes elegir varios)</legend><?php foreach ($courses as $c): ?><label class="inline"><input type="checkbox" name="intereses[]" value="<?= e($c) ?>" <?= in_array($c, $interests, true) ? 'checked' : '' ?>><?= e($c) ?></label><?php endforeach; ?></fieldset>
<label class="inline"><input type="checkbox" name="acepto" value="si" required <?= field('acepto') === 'si' ? 'checked' : '' ?>>Acepto procesar estos datos de práctica.</label>
<button>Analizar formulario</button>
</form>
<?php if ($submitted && !$errors): ?>
<section class="panel"><h2>Datos recibidos mediante POST</h2><dl><dt>Nombre</dt><dd><?= e($name) ?></dd><dt>Correo</dt><dd><?= e($email) ?></dd><dt>Edad</dt><dd><?= e($age) ?></dd><dt>Curso</dt><dd><?= e($course) ?></dd><dt>Intereses</dt><dd><?= e(implode(', ', $interests) ?: 'Ninguno') ?></dd></dl><p>PHP validó los datos en el servidor y escapó la salida HTML. Estos datos no se guardan.</p></section>
<?php endif; endpage(); ?>
