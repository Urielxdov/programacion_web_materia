<div class="encabezado-pagina">
    <div>
        <h1>Categorías de equipos</h1>
        <p class="text-secondary small mb-0">El <strong>tipo</strong> determina el comportamiento del equipo (borrado de datos, materiales peligrosos, recomendaciones de entrega).</p>
    </div>
    <a class="btn btn-primary" href="<?= url('panel/categorias/nueva') ?>"><i class="bi bi-plus-lg"></i> Nueva categoría</a>
</div>
<div class="tarjeta">
    <div class="table-responsive">
        <table class="table tabla-ew table-hover mb-0 tabla-responsiva-movil">
            <thead><tr><th>Nombre</th><th>Tipo</th><th class="text-end">Vida útil</th><th class="text-end">Equipos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categorias as $c): ?>
                <tr>
                    <td data-etiqueta="Nombre"><?= e($c->nombre) ?><div class="small text-secondary"><?= e($c->descripcion ?? '') ?></div></td>
                    <td data-etiqueta="Tipo" class="small"><?= e($c->nombreTipo()) ?></td>
                    <td data-etiqueta="Vida útil" class="text-end"><?= (int) $c->vidaUtilAnios ?> años</td>
                    <td data-etiqueta="Equipos" class="text-end"><?= (int) ($conteo[$c->id] ?? 0) ?></td>
                    <td data-etiqueta="Estado"><?= $c->activo ? '<span class="badge text-bg-success">Activa</span>' : '<span class="badge text-bg-secondary">Inactiva</span>' ?></td>
                    <td class="text-end"><a class="btn btn-outline-secondary btn-sm" href="<?= url('panel/categorias/' . $c->id . '/editar') ?>" aria-label="Editar"><i class="bi bi-pencil"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
