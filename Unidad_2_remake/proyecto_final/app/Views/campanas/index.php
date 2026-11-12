<div class="encabezado-pagina">
    <div>
        <h1>Campañas de recolección</h1>
        <p class="text-secondary small mb-0">Los borradores no se muestran en el sitio público.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('panel/campanas/nueva') ?>"><i class="bi bi-plus-lg"></i> Nueva campaña</a>
</div>

<div class="tarjeta">
    <div class="table-responsive">
        <table class="table tabla-ew table-hover mb-0 tabla-responsiva-movil">
            <thead><tr><th>Campaña</th><th>Fechas</th><th>Ubicación</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($campanas as $c): ?>
                <tr>
                    <td data-etiqueta="Campaña"><a href="<?= url('panel/campanas/' . $c->id) ?>" class="fw-semibold"><?= e($c->nombre) ?></a></td>
                    <td data-etiqueta="Fechas" class="small"><?= fecha($c->fechaInicio) ?> – <?= fecha($c->fechaFin) ?></td>
                    <td data-etiqueta="Ubicación" class="small"><?= e($c->ubicacion) ?></td>
                    <td data-etiqueta="Estado"><?= insignia($c->estado, $c->nombreEstado()) ?></td>
                    <td class="text-end">
                        <a class="btn btn-outline-primary btn-sm" href="<?= url('panel/campanas/' . $c->id) ?>">Resultados</a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('panel/campanas/' . $c->id . '/editar') ?>" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$campanas): ?>
                <tr><td colspan="5" class="text-center text-secondary py-4">No hay campañas registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
