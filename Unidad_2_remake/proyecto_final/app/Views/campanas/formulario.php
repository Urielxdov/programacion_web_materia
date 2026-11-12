<?php
use App\Models\Campana;

/** @var ?Campana $campana */
$editando = $campana !== null;
$valor = static fn (string $campo, mixed $actual) => viejo($campo, $actual);
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/campanas') ?>">Campañas</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $editando ? 'Editar' : 'Nueva' ?></li>
        </ol></nav>
        <h1><?= $editando ? 'Editar campaña' : 'Nueva campaña' ?></h1>
    </div>
</div>

<form class="tarjeta p-4" method="post" action="<?= url($editando ? 'panel/campanas/' . $campana->id : 'panel/campanas') ?>" novalidate data-validar>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="nombre">Nombre *</label>
            <input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" required maxlength="120" value="<?= e($valor('nombre', $campana?->nombre ?? '')) ?>">
            <div class="invalid-feedback">El nombre es obligatorio.</div>
            <?= mensaje_error('nombre') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="estado">Estado *</label>
            <select class="form-select<?= clase_error('estado') ?>" id="estado" name="estado" required><?= opciones(Campana::ESTADOS, $valor('estado', $campana?->estado ?? 'borrador')) ?></select>
            <?= mensaje_error('estado') ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="descripcion">Descripción *</label>
            <textarea class="form-control<?= clase_error('descripcion') ?>" id="descripcion" name="descripcion" rows="3" required maxlength="2000"><?= e($valor('descripcion', $campana?->descripcion ?? '')) ?></textarea>
            <div class="invalid-feedback">Describe la campaña.</div>
            <?= mensaje_error('descripcion') ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fecha_inicio">Inicio *</label>
            <input class="form-control<?= clase_error('fecha_inicio') ?>" type="date" id="fecha_inicio" name="fecha_inicio" required value="<?= e($valor('fecha_inicio', $campana?->fechaInicio ?? '')) ?>" data-fecha-inicio>
            <?= mensaje_error('fecha_inicio') ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fecha_fin">Fin *</label>
            <input class="form-control<?= clase_error('fecha_fin') ?>" type="date" id="fecha_fin" name="fecha_fin" required value="<?= e($valor('fecha_fin', $campana?->fechaFin ?? '')) ?>" data-fecha-fin>
            <div class="invalid-feedback">Debe ser igual o posterior al inicio.</div>
            <?= mensaje_error('fecha_fin') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="ubicacion">Ubicación *</label>
            <input class="form-control<?= clase_error('ubicacion') ?>" id="ubicacion" name="ubicacion" required maxlength="150" value="<?= e($valor('ubicacion', $campana?->ubicacion ?? '')) ?>">
            <?= mensaje_error('ubicacion') ?>
        </div>
        <div class="col-md-9">
            <label class="form-label" for="tipos_aceptados">Equipos aceptados * <span class="small text-secondary">(separados por coma)</span></label>
            <input class="form-control<?= clase_error('tipos_aceptados') ?>" id="tipos_aceptados" name="tipos_aceptados" required maxlength="255" value="<?= e($valor('tipos_aceptados', $campana?->tiposAceptados ?? '')) ?>" placeholder="Laptops, Monitores, Celulares">
            <?= mensaje_error('tipos_aceptados') ?>
            <div class="mt-2" data-vista-chips></div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="meta_equipos">Meta de equipos</label>
            <input class="form-control<?= clase_error('meta_equipos') ?>" type="number" min="1" id="meta_equipos" name="meta_equipos" value="<?= e($valor('meta_equipos', $campana?->metaEquipos ?? '')) ?>">
            <?= mensaje_error('meta_equipos') ?>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= url('panel/campanas') ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar</button>
    </div>
</form>
