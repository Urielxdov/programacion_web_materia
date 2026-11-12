<?php
use App\Models\Categoria;
use App\Models\Equipo;

/** @var ?Equipo $equipo */
$editando = $equipo !== null;
$v = static fn (string $campo, mixed $actual = '') => viejo($campo, $actual);
$datos = $editando ? $equipo->toPersistencia() : [];
$opcionesCategorias = [];
foreach ($categorias as $c) {
    $opcionesCategorias[$c->id] = $c->nombre . ' (' . $c->nombreTipo() . ')';
}
$opcionesCampanas = [];
foreach ($campanas as $c) {
    $opcionesCampanas[$c->id] = $c->nombre . ' · ' . fecha($c->fechaInicio);
}
$tipos = [];
foreach ($categorias as $c) {
    $tipos[$c->id] = $c->tipo;
}
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/equipos') ?>">Inventario</a></li>
            <?php if ($editando): ?><li class="breadcrumb-item"><a href="<?= url('panel/equipos/' . $equipo->id()) ?>"><?= e($equipo->codigo()) ?></a></li><?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $editando ? 'Editar' : 'Registrar' ?></li>
        </ol></nav>
        <h1><?= $editando ? 'Editar ' . e($equipo->codigo()) : 'Registrar equipo' ?></h1>
    </div>
</div>

<form method="post" action="<?= url($editando ? 'panel/equipos/' . $equipo->id() : 'panel/equipos') ?>" class="tarjeta p-4" novalidate data-validar
      data-tipos-categoria='<?= e(json_encode($tipos)) ?>'>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-12"><div class="form-seccion mt-0">Identificación</div></div>
        <div class="col-md-6">
            <label class="form-label" for="categoria_id">Categoría *</label>
            <select class="form-select<?= clase_error('categoria_id') ?>" id="categoria_id" name="categoria_id" required>
                <?= opciones($opcionesCategorias, $v('categoria_id', $datos['categoria_id'] ?? ''), 'Selecciona…') ?>
            </select>
            <div class="invalid-feedback">Selecciona una categoría.</div>
            <?= mensaje_error('categoria_id') ?>
            <div class="form-text d-none" data-aviso-tipo></div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="marca">Marca *</label>
            <input class="form-control<?= clase_error('marca') ?>" id="marca" name="marca" required maxlength="80" value="<?= e($v('marca', $datos['marca'] ?? '')) ?>">
            <div class="invalid-feedback">La marca es obligatoria.</div>
            <?= mensaje_error('marca') ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="modelo">Modelo *</label>
            <input class="form-control<?= clase_error('modelo') ?>" id="modelo" name="modelo" required maxlength="80" value="<?= e($v('modelo', $datos['modelo'] ?? '')) ?>">
            <div class="invalid-feedback">El modelo es obligatorio.</div>
            <?= mensaje_error('modelo') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="numero_serie">Número de serie</label>
            <input class="form-control<?= clase_error('numero_serie') ?>" id="numero_serie" name="numero_serie" maxlength="80" value="<?= e($v('numero_serie', $datos['numero_serie'] ?? '')) ?>">
            <?= mensaje_error('numero_serie') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="anio_fabricacion">Año de fabricación</label>
            <input class="form-control<?= clase_error('anio_fabricacion') ?>" type="number" id="anio_fabricacion" name="anio_fabricacion" min="1970" max="<?= date('Y') ?>" value="<?= e($v('anio_fabricacion', $datos['anio_fabricacion'] ?? '')) ?>">
            <div class="invalid-feedback">Entre 1970 y <?= date('Y') ?>.</div>
            <?= mensaje_error('anio_fabricacion') ?>
            <div class="form-text">Se usa para estimar si superó su vida útil.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="peso_kg">Peso aproximado (kg)</label>
            <input class="form-control<?= clase_error('peso_kg') ?>" type="number" step="0.01" min="0" max="5000" id="peso_kg" name="peso_kg" value="<?= e($v('peso_kg', $datos['peso_kg'] ?? '')) ?>">
            <?= mensaje_error('peso_kg') ?>
        </div>

        <div class="col-12"><div class="form-seccion">Ingreso y estado</div></div>
        <div class="col-md-4">
            <label class="form-label" for="fecha_ingreso">Fecha de ingreso *</label>
            <input class="form-control<?= clase_error('fecha_ingreso') ?>" type="date" id="fecha_ingreso" name="fecha_ingreso" required max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_ingreso', $datos['fecha_ingreso'] ?? date('Y-m-d'))) ?>">
            <div class="invalid-feedback">Indica una fecha que no sea futura.</div>
            <?= mensaje_error('fecha_ingreso') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="condicion">Condición general *</label>
            <select class="form-select<?= clase_error('condicion') ?>" id="condicion" name="condicion" required>
                <?= opciones(Equipo::CONDICIONES, $v('condicion', $datos['condicion'] ?? ''), 'Selecciona…') ?>
            </select>
            <div class="invalid-feedback">Selecciona la condición.</div>
            <?= mensaje_error('condicion') ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="clasificacion">Clasificación</label>
            <?php if ($puedeClasificar): ?>
                <select class="form-select<?= clase_error('clasificacion') ?>" id="clasificacion" name="clasificacion">
                    <?= opciones(Equipo::CLASIFICACIONES, $v('clasificacion', $datos['clasificacion'] ?? 'pendiente')) ?>
                </select>
                <?= mensaje_error('clasificacion') ?>
            <?php else: ?>
                <input class="form-control" id="clasificacion" value="<?= e(Equipo::CLASIFICACIONES[$datos['clasificacion'] ?? 'pendiente']) ?>" disabled>
                <div class="form-text">La asigna un responsable ambiental con el diagnóstico.</div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="ubicacion">Ubicación actual</label>
            <input class="form-control<?= clase_error('ubicacion') ?>" id="ubicacion" name="ubicacion" maxlength="120" value="<?= e($v('ubicacion', $datos['ubicacion'] ?? '')) ?>" placeholder="Ej. Bodega de TI, Laboratorio 2">
            <?= mensaje_error('ubicacion') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="campana_id">Recibido en campaña</label>
            <select class="form-select<?= clase_error('campana_id') ?>" id="campana_id" name="campana_id">
                <?= opciones($opcionesCampanas, $v('campana_id', $datos['campana_id'] ?? ''), 'No proviene de una campaña') ?>
            </select>
            <?= mensaje_error('campana_id') ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="observaciones">Observaciones</label>
            <textarea class="form-control<?= clase_error('observaciones') ?>" id="observaciones" name="observaciones" rows="3" maxlength="1000" data-contador-caracteres><?= e($v('observaciones', $datos['observaciones'] ?? '')) ?></textarea>
            <div class="form-text text-end" data-contador-para="observaciones"></div>
            <?= mensaje_error('observaciones') ?>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= url($editando ? 'panel/equipos/' . $equipo->id() : 'panel/equipos') ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> <?= $editando ? 'Guardar cambios' : 'Registrar equipo' ?></button>
    </div>
</form>
