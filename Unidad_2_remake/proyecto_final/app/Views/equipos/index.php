<?php
use App\Models\Equipo;
$opcionesCategorias = [];
foreach ($categorias as $c) {
    $opcionesCategorias[$c->id] = $c->nombre;
}
?>
<div class="encabezado-pagina">
    <div>
        <h1>Inventario de equipos</h1>
        <p class="text-secondary mb-0 small"><span data-total-equipos><?= numero($resultado['total']) ?></span> equipo(s) encontrados.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (puede('reportes.ver')): ?>
            <a class="btn btn-outline-secondary" href="<?= url('panel/reportes/inventario.csv') ?>"><i class="bi bi-filetype-csv"></i> Exportar</a>
        <?php endif; ?>
        <?php if (puede('equipos.crear')): ?>
            <a class="btn btn-primary" href="<?= url('panel/equipos/nuevo') ?>"><i class="bi bi-plus-lg"></i> Registrar equipo</a>
        <?php endif; ?>
    </div>
</div>

<form class="tarjeta p-3 mb-3" method="get" action="<?= url('panel/equipos') ?>" id="filtrosEquipos" data-api="<?= url('api/equipos') ?>" role="search">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1" for="q">Buscar</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Código, marca, modelo o serie" autocomplete="off">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="clasificacion">Clasificación</label>
            <select class="form-select" id="clasificacion" name="clasificacion"><?= opciones(Equipo::CLASIFICACIONES, $filtros['clasificacion'], 'Todas') ?></select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="estado">Estado</label>
            <select class="form-select" id="estado" name="estado"><?= opciones(Equipo::ESTADOS, $filtros['estado'], 'Todos') ?></select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="categoria_id">Categoría</label>
            <select class="form-select" id="categoria_id" name="categoria_id"><?= opciones($opcionesCategorias, $filtros['categoria_id'], 'Todas') ?></select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="orden">Orden</label>
            <select class="form-select" id="orden" name="orden"><?= opciones(['recientes' => 'Más recientes', 'antiguos' => 'Más antiguos', 'codigo' => 'Código', 'marca' => 'Marca'], $filtros['orden']) ?></select>
        </div>
    </div>
    <noscript><button class="btn btn-primary mt-2" type="submit">Filtrar</button></noscript>
</form>

<div class="tarjeta" id="contenedorTabla">
    <div class="table-responsive">
        <table class="table tabla-ew table-hover tabla-responsiva-movil mb-0">
            <thead>
            <tr><th>Código</th><th>Equipo</th><th>Categoría</th><th>Condición</th><th>Clasificación</th><th>Estado</th><th>Ingreso</th></tr>
            </thead>
            <tbody id="cuerpoEquipos">
            <?php foreach ($resultado['items'] as $e): ?>
                <tr>
                    <td data-etiqueta="Código"><a class="codigo" href="<?= url('panel/equipos/' . $e->id()) ?>"><?= e($e->codigo()) ?></a></td>
                    <td data-etiqueta="Equipo"><span><i class="bi bi-<?= $e->icono() ?> text-secondary"></i> <?= e($e->marca() . ' ' . $e->modelo()) ?></span></td>
                    <td data-etiqueta="Categoría" class="small"><?= e($e->categoria()->nombre) ?></td>
                    <td data-etiqueta="Condición" class="small"><?= e($e->nombreCondicion()) ?></td>
                    <td data-etiqueta="Clasificación"><?= insignia($e->clasificacion(), $e->nombreClasificacion()) ?></td>
                    <td data-etiqueta="Estado"><?= insignia($e->estado(), $e->nombreEstado()) ?></td>
                    <td data-etiqueta="Ingreso" class="small"><?= fecha($e->fechaIngreso()) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$resultado['items']): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No hay equipos que coincidan con los filtros.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="paginacionEquipos">
    <?= App\Core\View::parcial('paginacion', ['pagina' => $resultado['pagina'], 'paginas' => $resultado['paginas'], 'query' => $filtros, 'ruta' => 'panel/equipos']) ?>
</div>
