<?php
$iconos = [403 => 'shield-x', 404 => 'signpost-split', 405 => 'slash-circle', 429 => 'hourglass-split', 500 => 'tools'];
?>
<section class="seccion">
    <div class="container text-center" style="max-width: 560px">
        <span class="icono-circulo mb-3" style="width:72px;height:72px;font-size:2rem"><i class="bi bi-<?= $iconos[$codigo] ?? 'exclamation-circle' ?>"></i></span>
        <h1 class="h2">Error <?= (int) $codigo ?></h1>
        <p class="text-secondary"><?= e($mensaje) ?></p>
        <div class="d-flex gap-2 justify-content-center">
            <a class="btn btn-primary" href="<?= url(usuario_actual() ? 'panel' : '') ?>">Volver al inicio</a>
            <button class="btn btn-outline-secondary" type="button" data-regresar>Regresar</button>
        </div>
    </div>
</section>
