<div class="encabezado-pagina">
    <div>
        <h1>Configuración del sistema</h1>
        <p class="text-secondary small mb-0">Estos parámetros definen la política con la que el sistema recomienda el destino de cada equipo.</p>
    </div>
</div>
<form class="tarjeta p-4" style="max-width: 760px" method="post" action="<?= url('panel/configuracion') ?>" novalidate data-validar>
    <?= csrf_campo() ?>
    <?php foreach ($parametros as $clave => $p): ?>
        <div class="mb-4">
            <label class="form-label fw-semibold" for="<?= e($clave) ?>"><?= e($p['etiqueta']) ?></label>
            <input class="form-control<?= clase_error($clave) ?>" id="<?= e($clave) ?>" name="<?= e($clave) ?>" required
                   <?= $clave === 'nombre_institucion' ? 'maxlength="120"' : 'type="number" step="0.1"' ?>
                   value="<?= e(viejo($clave, $p['valor'])) ?>">
            <?= mensaje_error($clave) ?>
            <div class="form-text"><?= e($p['descripcion']) ?></div>
        </div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar configuración</button>
    </div>
</form>
