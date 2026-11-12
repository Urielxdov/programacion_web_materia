<div class="encabezado-pagina">
    <div>
        <h1>Gestores de residuos</h1>
        <p class="text-secondary small mb-0">Marca como <strong>verificado</strong> solo al gestor cuya autorización ambiental hayas revisado. Las entregas a gestores sin verificar no cuentan como destino documentado.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('panel/gestores/nuevo') ?>"><i class="bi bi-plus-lg"></i> Nuevo gestor</a>
</div>
<div class="tarjeta">
    <div class="table-responsive">
        <table class="table tabla-ew table-hover mb-0 tabla-responsiva-movil">
            <thead><tr><th>Nombre</th><th>Tipo</th><th>Contacto</th><th>Autorización</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($gestores as $g): ?>
                <tr>
                    <td data-etiqueta="Nombre"><?= e($g->nombre) ?><div class="small text-secondary"><?= e($g->direccion ?? '') ?></div></td>
                    <td data-etiqueta="Tipo" class="small"><?= e($g->nombreTipo()) ?></td>
                    <td data-etiqueta="Contacto" class="small"><?= e($g->contacto ?? '—') ?></td>
                    <td data-etiqueta="Autorización" class="small"><?= e($g->numeroAutorizacion ?? '—') ?></td>
                    <td data-etiqueta="Estado">
                        <?= $g->verificado ? '<span class="badge text-bg-success"><i class="bi bi-patch-check"></i> Verificado</span>' : '<span class="badge text-bg-warning text-dark">Sin verificar</span>' ?>
                        <?= $g->activo ? '' : '<span class="badge text-bg-secondary">Inactivo</span>' ?>
                    </td>
                    <td class="text-end"><a class="btn btn-outline-secondary btn-sm" href="<?= url('panel/gestores/' . $g->id . '/editar') ?>" aria-label="Editar"><i class="bi bi-pencil"></i></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$gestores): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No hay gestores registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
