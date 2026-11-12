<?php
use App\Models\Diagnostico;
use App\Models\Gestor;
use App\Models\Movimiento;

/** @var App\Models\Equipo $equipo */
$permitidos = $equipo->movimientosPermitidos();
$hayPendiente = (bool) array_filter($movimientos, static fn (Movimiento $m): bool => $m->estaPendiente());
$iconosMov = ['ingreso' => 'box-arrow-in-down', 'reparacion' => 'tools', 'reasignacion' => 'arrow-left-right', 'conservacion' => 'archive', 'entrega_gestor' => 'truck'];
$opcionesGestores = [];
$gestoresVerificados = [];
foreach ($gestores as $g) {
    $opcionesGestores[$g->id] = $g->nombre . ($g->verificado ? ' ✓ verificado' : ' (sin verificar)');
    $gestoresVerificados[$g->id] = $g->verificado;
}
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/equipos') ?>">Inventario</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($equipo->codigo()) ?></li>
        </ol></nav>
        <h1 class="d-flex align-items-center gap-2 flex-wrap">
            <i class="bi bi-<?= $equipo->icono() ?> text-secondary"></i>
            <span class="codigo"><?= e($equipo->codigo()) ?></span>
            <span class="fs-5 text-secondary fw-normal"><?= e($equipo->marca() . ' ' . $equipo->modelo()) ?></span>
        </h1>
    </div>
    <div class="d-flex gap-2 flex-wrap no-imprimir">
        <?php if (puede('diagnosticos.registrar') && !$equipo->estaEntregado()): ?>
            <a class="btn btn-primary" href="<?= url('panel/equipos/' . $equipo->id() . '/diagnostico') ?>"><i class="bi bi-clipboard2-pulse"></i> Diagnosticar</a>
        <?php endif; ?>
        <?php if (puede('equipos.editar') && !$equipo->estaEntregado()): ?>
            <a class="btn btn-outline-primary" href="<?= url('panel/equipos/' . $equipo->id() . '/editar') ?>"><i class="bi bi-pencil"></i> Editar</a>
        <?php endif; ?>
        <button class="btn btn-outline-secondary" type="button" data-imprimir><i class="bi bi-printer"></i></button>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="tarjeta p-4 mb-3">
            <div class="d-flex gap-2 flex-wrap mb-3">
                <?= insignia($equipo->clasificacion(), $equipo->nombreClasificacion()) ?>
                <?= insignia($equipo->estado(), $equipo->nombreEstado()) ?>
            </div>
            <dl class="row small mb-0">
                <dt class="col-5 text-secondary fw-normal">Categoría</dt><dd class="col-7"><?= e($equipo->categoria()->nombre) ?></dd>
                <dt class="col-5 text-secondary fw-normal">No. de serie</dt><dd class="col-7"><?= e($equipo->numeroSerie() ?? '—') ?></dd>
                <dt class="col-5 text-secondary fw-normal">Año</dt><dd class="col-7"><?= e($equipo->anioFabricacion() ?? '—') ?>
                    <?php if ($equipo->antiguedadAnios() !== null): ?><span class="text-secondary">(<?= $equipo->antiguedadAnios() ?> años; vida útil de referencia: <?= $equipo->categoria()->vidaUtilAnios ?>)</span><?php endif; ?></dd>
                <dt class="col-5 text-secondary fw-normal">Ingreso</dt><dd class="col-7"><?= fecha($equipo->fechaIngreso()) ?></dd>
                <dt class="col-5 text-secondary fw-normal">Condición</dt><dd class="col-7"><?= e($equipo->nombreCondicion()) ?></dd>
                <dt class="col-5 text-secondary fw-normal">Peso</dt><dd class="col-7"><?= $equipo->pesoKg() !== null ? numero($equipo->pesoKg(), 2) . ' kg' : '—' ?></dd>
                <dt class="col-5 text-secondary fw-normal">Ubicación</dt><dd class="col-7"><?= e($equipo->ubicacion() ?? '—') ?></dd>
            </dl>
            <?php if ($equipo->observaciones()): ?>
                <hr><p class="small mb-0"><?= nl2br(e($equipo->observaciones())) ?></p>
            <?php endif; ?>
        </div>

        <div class="tarjeta p-4">
            <h2 class="h6"><i class="bi bi-shield-exclamation text-warning"></i> Manejo responsable</h2>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <?php if ($equipo->requiereBorradoDatos()): ?><span class="badge text-bg-info">Requiere borrado de datos</span><?php endif; ?>
                <?php if ($equipo->contieneMaterialesPeligrosos()): ?><span class="badge text-bg-danger">Materiales peligrosos</span><?php endif; ?>
            </div>
            <ul class="small mb-0 ps-3">
                <?php foreach ($equipo->recomendacionesEntrega() as $r): ?><li><?= e($r) ?></li><?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if (puede('movimientos.registrar') && $permitidos && !$hayPendiente): ?>
            <div class="tarjeta p-4 mb-3 no-imprimir">
                <h2 class="h6 mb-3"><i class="bi bi-signpost-2"></i> Registrar movimiento</h2>
                <form method="post" action="<?= url('panel/equipos/' . $equipo->id() . '/movimientos') ?>" novalidate data-validar id="formMovimiento"
                      data-gestores-verificados='<?= e(json_encode($gestoresVerificados)) ?>'
                      data-requiere-borrado="<?= $equipo->requiereBorradoDatos() ? '1' : '0' ?>">
                    <?= csrf_campo() ?>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small" for="tipo">Tipo *</label>
                            <select class="form-select<?= clase_error('tipo') ?>" id="tipo" name="tipo" required>
                                <?= opciones(array_intersect_key(Movimiento::TIPOS, array_flip($permitidos)), viejo('tipo'), 'Selecciona…') ?>
                            </select>
                            <?= mensaje_error('tipo') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="fecha">Fecha *</label>
                            <input class="form-control<?= clase_error('fecha') ?>" type="date" id="fecha" name="fecha" required min="<?= e($equipo->fechaIngreso()) ?>" max="<?= date('Y-m-d') ?>" value="<?= e(viejo('fecha', date('Y-m-d'))) ?>">
                            <?= mensaje_error('fecha') ?>
                        </div>
                        <div class="col-md-4" data-campo-mov="destino">
                            <label class="form-label small" for="destino">Destino</label>
                            <input class="form-control<?= clase_error('destino') ?>" id="destino" name="destino" maxlength="150" value="<?= e(viejo('destino')) ?>" placeholder="Laboratorio, área o usuario">
                            <?= mensaje_error('destino') ?>
                        </div>
                        <div class="col-md-4 d-none" data-campo-mov="costo">
                            <label class="form-label small" for="costo">Costo real ($)</label>
                            <input class="form-control<?= clase_error('costo') ?>" type="number" min="0" step="0.01" id="costo" name="costo" value="<?= e(viejo('costo')) ?>">
                            <?= mensaje_error('costo') ?>
                        </div>
                        <div class="col-md-5 d-none" data-campo-mov="gestor">
                            <label class="form-label small" for="gestor_id">Gestor *</label>
                            <select class="form-select<?= clase_error('gestor_id') ?>" id="gestor_id" name="gestor_id"><?= opciones($opcionesGestores, viejo('gestor_id'), 'Selecciona…') ?></select>
                            <?= mensaje_error('gestor_id') ?>
                        </div>
                        <div class="col-md-3 d-none" data-campo-mov="evidencia">
                            <label class="form-label small" for="evidencia">Folio / manifiesto *</label>
                            <input class="form-control<?= clase_error('evidencia') ?>" id="evidencia" name="evidencia" maxlength="255" value="<?= e(viejo('evidencia')) ?>">
                            <?= mensaje_error('evidencia') ?>
                        </div>
                        <div class="col-12 d-none" data-aviso-gestor>
                            <div class="alert alert-warning small py-2 mb-0"><i class="bi bi-exclamation-triangle"></i> Este gestor no está verificado: la entrega quedará registrada pero <strong>no contará como destino documentado</strong> en los indicadores.</div>
                        </div>
                        <div class="col-12 d-none" data-campo-mov="borrado">
                            <div class="form-check">
                                <input class="form-check-input<?= clase_error('datos_borrados') ?>" type="checkbox" value="1" id="datos_borrados" name="datos_borrados">
                                <label class="form-check-label small" for="datos_borrados">Confirmo que se realizó el borrado seguro de la información del equipo.</label>
                                <?= mensaje_error('datos_borrados') ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small" for="obs_mov">Observaciones</label>
                            <input class="form-control" id="obs_mov" name="observaciones" maxlength="1000" value="<?= e(viejo('observaciones')) ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                        <span class="small text-secondary"><?= puede('movimientos.validar') ? 'Se validará automáticamente con tu rol.' : 'Quedará pendiente hasta que un responsable lo valide.' ?></span>
                        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2"></i> Registrar</button>
                    </div>
                </form>
            </div>
        <?php elseif ($hayPendiente): ?>
            <div class="alert alert-warning small"><i class="bi bi-hourglass-split"></i> Hay un movimiento pendiente de validación para este equipo.</div>
        <?php elseif ($equipo->estaEntregado()): ?>
            <div class="alert alert-secondary small"><i class="bi bi-lock"></i> El equipo fue entregado a un gestor: su registro está cerrado.</div>
        <?php endif; ?>

        <div class="tarjeta p-4 mb-3">
            <h2 class="h6 mb-3"><i class="bi bi-clock-history"></i> Historial de movimientos</h2>
            <ul class="linea-tiempo">
                <?php foreach ($movimientos as $m): ?>
                    <li>
                        <span class="punto"><i class="bi bi-<?= $iconosMov[$m->tipo] ?>"></i></span>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <strong><?= e($m->nombreTipo()) ?></strong>
                            <?= insignia($m->estadoValidacion, $m->nombreEstado()) ?>
                            <?php if ($m->tipo === 'entrega_gestor'): ?>
                                <?= $m->destinoVerificado ? '<span class="badge text-bg-success">Destino verificado</span>' : '<span class="badge text-bg-warning text-dark">Destino sin verificar</span>' ?>
                            <?php endif; ?>
                            <span class="small text-secondary ms-auto"><?= fecha($m->fecha) ?></span>
                        </div>
                        <div class="small text-secondary">
                            <?= $m->destino ? 'Destino: ' . e($m->destino) . ' · ' : '' ?>
                            <?= $m->costo !== null ? 'Costo: ' . dinero($m->costo) . ' · ' : '' ?>
                            <?= $m->ahorroEstimado > 0 ? 'Ahorro estimado: ' . dinero($m->ahorroEstimado) . ' · ' : '' ?>
                            <?= $m->evidencia ? 'Evidencia: ' . e($m->evidencia) . ' · ' : '' ?>
                            Registró: <?= e($m->extra['registrado_por']) ?><?= $m->extra['validado_por'] ? ' · Validó: ' . e($m->extra['validado_por']) : '' ?>
                        </div>
                        <?php if ($m->observaciones): ?><div class="small fst-italic"><?= e($m->observaciones) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="tarjeta p-4">
            <h2 class="h6 mb-3"><i class="bi bi-clipboard2-pulse"></i> Diagnósticos</h2>
            <?php if (!$diagnosticos): ?>
                <p class="small text-secondary mb-0">Este equipo aún no tiene diagnóstico.</p>
            <?php endif; ?>
            <?php foreach ($diagnosticos as $d): ?>
                <div class="border rounded-3 p-3 mb-2">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                        <strong><?= e(Diagnostico::DECISIONES[$d->decision]) ?></strong>
                        <?php if (!$d->siguioRecomendacion()): ?>
                            <span class="badge text-bg-light border">El sistema recomendaba: <?= e(Diagnostico::DECISIONES[$d->recomendacionSistema]) ?></span>
                        <?php endif; ?>
                        <?php if ($d->riesgoSeguridad): ?><span class="badge text-bg-danger">Riesgo de seguridad</span><?php endif; ?>
                        <span class="small text-secondary ms-auto"><?= fecha($d->fecha) ?> · <?= e($d->responsableNombre) ?></span>
                    </div>
                    <p class="small mb-1"><?= nl2br(e($d->descripcion)) ?></p>
                    <div class="small text-secondary">
                        <?= e(Diagnostico::ESTADOS_FUNCIONALES[$d->estadoFuncional]) ?> ·
                        Reparación: <?= dinero($d->costoReparacion) ?> · Equipo nuevo: <?= dinero($d->valorReemplazo) ?>
                    </div>
                    <?php if ($d->justificacion): ?><div class="small fst-italic mt-1"><?= e($d->justificacion) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
