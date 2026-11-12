<?php
use App\Models\Gestor;

/** @var ?Gestor $gestor */
$editando = $gestor !== null;
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/gestores') ?>">Gestores</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $editando ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editando ? 'Editar gestor' : 'Nuevo gestor' ?></h1>
    </div>
</div>
<form class="tarjeta p-4" style="max-width: 760px" method="post" action="<?= url($editando ? 'panel/gestores/' . $gestor->id : 'panel/gestores') ?>" novalidate data-validar>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="nombre">Nombre o razón social *</label>
            <input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" required maxlength="150" value="<?= e(viejo('nombre', $gestor?->nombre ?? '')) ?>">
            <?= mensaje_error('nombre') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="tipo">Tipo *</label>
            <select class="form-select<?= clase_error('tipo') ?>" id="tipo" name="tipo" required><?= opciones(Gestor::TIPOS, viejo('tipo', $gestor?->tipo ?? 'reciclador')) ?></select>
            <?= mensaje_error('tipo') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="contacto">Contacto</label>
            <input class="form-control<?= clase_error('contacto') ?>" id="contacto" name="contacto" maxlength="150" value="<?= e(viejo('contacto', $gestor?->contacto ?? '')) ?>">
            <?= mensaje_error('contacto') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="numero_autorizacion">Número de autorización ambiental</label>
            <input class="form-control<?= clase_error('numero_autorizacion') ?>" id="numero_autorizacion" name="numero_autorizacion" maxlength="80" value="<?= e(viejo('numero_autorizacion', $gestor?->numeroAutorizacion ?? '')) ?>">
            <?= mensaje_error('numero_autorizacion') ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="direccion">Dirección</label>
            <input class="form-control<?= clase_error('direccion') ?>" id="direccion" name="direccion" maxlength="255" value="<?= e(viejo('direccion', $gestor?->direccion ?? '')) ?>">
            <?= mensaje_error('direccion') ?>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" value="1" id="verificado" name="verificado" <?= viejo('verificado', $gestor?->verificado ?? false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="verificado">Autorización revisada (verificado)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" value="1" id="activo" name="activo" <?= viejo('activo', $gestor?->activo ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Activo</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= url('panel/gestores') ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar</button>
    </div>
</form>
