<?php
use App\Models\Diagnostico;

/** @var App\Models\Equipo $equipo */
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/equipos') ?>">Inventario</a></li>
            <li class="breadcrumb-item"><a href="<?= url('panel/equipos/' . $equipo->id()) ?>"><?= e($equipo->codigo()) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Diagnóstico</li>
        </ol></nav>
        <h1>Diagnóstico de <span class="codigo"><?= e($equipo->codigo()) ?></span></h1>
        <p class="small text-secondary mb-0"><?= e($equipo->descripcionCorta()) ?> · antigüedad: <?= $equipo->antiguedadAnios() !== null ? $equipo->antiguedadAnios() . ' años' : 'sin dato' ?> · vida útil de referencia: <?= $equipo->categoria()->vidaUtilAnios ?> años</p>
    </div>
</div>

<form method="post" action="<?= url('panel/equipos/' . $equipo->id() . '/diagnostico') ?>" novalidate data-validar id="formDiagnostico"
      data-api="<?= url('api/equipos/' . $equipo->id() . '/recomendacion') ?>">
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="tarjeta p-4">
                <div class="mb-3">
                    <label class="form-label" for="descripcion">Diagnóstico técnico *</label>
                    <textarea class="form-control<?= clase_error('descripcion') ?>" id="descripcion" name="descripcion" rows="4" required maxlength="2000" placeholder="Describe las pruebas realizadas y las fallas encontradas"><?= e(viejo('descripcion')) ?></textarea>
                    <div class="invalid-feedback">Describe el diagnóstico.</div>
                    <?= mensaje_error('descripcion') ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="estado_funcional">Estado funcional *</label>
                        <select class="form-select<?= clase_error('estado_funcional') ?>" id="estado_funcional" name="estado_funcional" required data-evaluacion>
                            <?= opciones(Diagnostico::ESTADOS_FUNCIONALES, viejo('estado_funcional'), 'Selecciona…') ?>
                        </select>
                        <?= mensaje_error('estado_funcional') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="fecha">Fecha *</label>
                        <input class="form-control<?= clase_error('fecha') ?>" type="date" id="fecha" name="fecha" required min="<?= e($equipo->fechaIngreso()) ?>" max="<?= date('Y-m-d') ?>" value="<?= e(viejo('fecha', date('Y-m-d'))) ?>">
                        <?= mensaje_error('fecha') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="costo_reparacion">Costo estimado de reparación ($) *</label>
                        <input class="form-control<?= clase_error('costo_reparacion') ?>" type="number" min="0" step="0.01" id="costo_reparacion" name="costo_reparacion" required value="<?= e(viejo('costo_reparacion', '0')) ?>" data-evaluacion>
                        <?= mensaje_error('costo_reparacion') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="valor_reemplazo">Valor de un equipo equivalente nuevo ($) *</label>
                        <input class="form-control<?= clase_error('valor_reemplazo') ?>" type="number" min="0" step="0.01" id="valor_reemplazo" name="valor_reemplazo" required value="<?= e(viejo('valor_reemplazo', $ultimo?->valorReemplazo ?? '')) ?>" data-evaluacion>
                        <?= mensaje_error('valor_reemplazo') ?>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" value="1" id="riesgo_seguridad" name="riesgo_seguridad" data-evaluacion <?= viejo('riesgo_seguridad') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="riesgo_seguridad">Presenta riesgo de seguridad (batería inflada, fugas, carcasa rota con componentes expuestos)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="tarjeta p-4 mb-3">
                <h2 class="h6"><i class="bi bi-cpu"></i> Recomendación del sistema</h2>
                <div class="recomendacion" id="recomendacion" aria-live="polite">
                    <p class="small text-secondary mb-0">Completa el estado funcional y los costos para ver la recomendación.</p>
                </div>
                <div class="small text-secondary mt-2">Se calcula con la política institucional (umbral de reparación y tolerancia de vida útil) que define el administrador.</div>
            </div>
            <div class="tarjeta p-4">
                <label class="form-label" for="decision">Decisión *</label>
                <select class="form-select<?= clase_error('decision') ?>" id="decision" name="decision" required>
                    <?= opciones(Diagnostico::DECISIONES, viejo('decision'), 'Selecciona…') ?>
                </select>
                <div class="invalid-feedback">Selecciona la decisión.</div>
                <?= mensaje_error('decision') ?>
                <div class="mt-3">
                    <label class="form-label" for="justificacion">Justificación <span class="small text-secondary" data-justificacion-obligatoria>(obligatoria si no sigues la recomendación)</span></label>
                    <textarea class="form-control<?= clase_error('justificacion') ?>" id="justificacion" name="justificacion" rows="3" maxlength="1000"><?= e(viejo('justificacion')) ?></textarea>
                    <div class="invalid-feedback">Explica por qué te apartas de la recomendación.</div>
                    <?= mensaje_error('justificacion') ?>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-3">
                    <a class="btn btn-outline-secondary" href="<?= url('panel/equipos/' . $equipo->id()) ?>">Cancelar</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar diagnóstico</button>
                </div>
            </div>
        </div>
    </div>
</form>
