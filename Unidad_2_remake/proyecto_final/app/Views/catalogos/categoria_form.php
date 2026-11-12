<?php
use App\Models\Categoria;

/** @var ?Categoria $categoria */
$editando = $categoria !== null;
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/categorias') ?>">Categorías</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $editando ? 'Editar' : 'Nueva' ?></li>
        </ol></nav>
        <h1><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></h1>
    </div>
</div>
<form class="tarjeta p-4" style="max-width: 720px" method="post" action="<?= url($editando ? 'panel/categorias/' . $categoria->id : 'panel/categorias') ?>" novalidate data-validar>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-md-7">
            <label class="form-label" for="nombre">Nombre *</label>
            <input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" required maxlength="80" value="<?= e(viejo('nombre', $categoria?->nombre ?? '')) ?>">
            <?= mensaje_error('nombre') ?>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="vida_util_anios">Vida útil de referencia (años) *</label>
            <input class="form-control<?= clase_error('vida_util_anios') ?>" type="number" min="1" max="50" id="vida_util_anios" name="vida_util_anios" required value="<?= e(viejo('vida_util_anios', $categoria?->vidaUtilAnios ?? '5')) ?>">
            <?= mensaje_error('vida_util_anios') ?>
        </div>
        <div class="col-md-7">
            <label class="form-label" for="tipo">Tipo de dispositivo *</label>
            <select class="form-select<?= clase_error('tipo') ?>" id="tipo" name="tipo" required><?= opciones(Categoria::TIPOS, viejo('tipo', $categoria?->tipo ?? '')) ?></select>
            <?= mensaje_error('tipo') ?>
        </div>
        <div class="col-md-5 d-flex align-items-end">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" value="1" id="activo" name="activo" <?= viejo('activo', $categoria?->activo ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Disponible para nuevos registros</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="descripcion">Descripción</label>
            <input class="form-control<?= clase_error('descripcion') ?>" id="descripcion" name="descripcion" maxlength="255" value="<?= e(viejo('descripcion', $categoria?->descripcion ?? '')) ?>">
            <?= mensaje_error('descripcion') ?>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= url('panel/categorias') ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar</button>
    </div>
</form>
