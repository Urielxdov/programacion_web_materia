<?php
/** @var App\Models\Campana $c */
$vigencias = ['proxima' => ['info', 'Próximamente'], 'en_curso' => ['success', 'En curso'], 'finalizada' => ['secondary', 'Finalizada']];
[$color, $texto] = $c->estado === 'cerrada' ? ['secondary', 'Cerrada'] : $vigencias[$c->vigencia()];
?>
<article class="tarjeta tarjeta-campana" data-estado="<?= e($c->estado) ?>" data-texto="<?= e(mb_strtolower($c->nombre . ' ' . $c->ubicacion . ' ' . $c->tiposAceptados)) ?>">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <h3 class="h5 mb-0"><?= e($c->nombre) ?></h3>
        <span class="badge text-bg-<?= $color ?>"><?= $texto ?></span>
    </div>
    <p class="fechas mb-2"><i class="bi bi-calendar3"></i> <?= fecha($c->fechaInicio) ?> – <?= fecha($c->fechaFin) ?><br>
        <i class="bi bi-geo-alt"></i> <?= e($c->ubicacion) ?></p>
    <div class="mb-3">
        <?php foreach (array_slice($c->listaTiposAceptados(), 0, 4) as $tipo): ?>
            <span class="chip"><?= e($tipo) ?></span>
        <?php endforeach; ?>
    </div>
    <a class="btn btn-outline-primary btn-sm mt-auto align-self-start" href="<?= url('campanas/' . $c->id) ?>">Ver detalles<?= $c->aceptaParticipaciones() ? ' y registrarme' : '' ?></a>
</article>
