<?php
require __DIR__.'/includes/app.php'; $errors = [];
$colors = ['rojo'=>['Rojo','#ed2024'], 'amarillo'=>['Amarillo','#ffe500'], 'morado'=>['Morado','#800080'], 'azul'=>['Azul cielo','#87ceeb']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $choice = field('color');
    if (!isset($colors[$choice])) $errors[] = 'Selecciona uno de los cuatro colores.';
    elseif (isset($_SESSION['voto'])) $errors[] = 'Ya votaste en esta sesión.';
    else {
        store('encuesta', array_fill_keys(array_keys($colors), 0), function ($votes) use ($choice) { $votes[$choice]++; return $votes; });
        $_SESSION['voto'] = $choice; redirect('encuesta.php');
    }
}
$votes = store('encuesta', array_fill_keys(array_keys($colors), 0)); $total = array_sum($votes);
page('Encuesta web'); errors($errors);
?>
<div class="columns"><section class="panel"><h2>¿Cuál es tu color favorito?</h2>
<?php if (isset($_SESSION['voto'])): ?><p class="notice" role="status">Votaste por <?= e($colors[$_SESSION['voto']][0]) ?>.</p><p>Se permite un voto por sesión de navegador. Este control es de práctica; otra sesión puede volver a votar.</p>
<?php else: ?><form method="post"><?php csrf(); ?><fieldset><legend>Elige un color</legend><?php foreach ($colors as $key => [$label, $hex]): ?><label class="inline"><input type="radio" name="color" required value="<?= $key ?>"><span class="swatch" style="background:<?= $hex ?>"></span><?= $label ?></label><?php endforeach; ?></fieldset><button>Registrar voto</button></form><?php endif; ?></section>
<section class="panel"><h2>Resultados</h2><p><?= $total ?> voto(s) en total</p>
<?php foreach ($colors as $key => [$label, $hex]): $percent = $total ? round($votes[$key] / $total * 100, 1) : 0; ?>
<div class="result"><p><strong><?= $label ?></strong><span><?= $votes[$key] ?> · <?= $percent ?> %</span></p><div class="track" role="meter" aria-label="<?= $label ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>"><div style="width:<?= $percent ?>%;background:<?= $hex ?>"></div></div></div>
<?php endforeach; ?></section></div>
<?php endpage(); ?>
