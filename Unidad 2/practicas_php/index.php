<?php require __DIR__.'/includes/app.php'; page('Siete prácticas para aprender PHP'); ?>
<p class="intro">Ejercicios de la Unidad 2: introduce datos, inicia sesión y descubre cómo PHP conserva información y procesa XML.</p>
<div class="cards">
<?php $i = 0; foreach (PRACTICAS as $path => [$title, $pages, $description]): ?>
<a class="card" href="<?= e($path) ?>"><span class="number"><?= sprintf('%02d', ++$i) ?></span><h2><?= e($title) ?></h2><p><?= e($description) ?></p><small>Páginas <?= e($pages) ?> <span aria-hidden="true">↗</span></small></a>
<?php endforeach; ?>
</div>
<?php endpage(); ?>
