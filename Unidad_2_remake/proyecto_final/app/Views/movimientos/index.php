<div class="encabezado-pagina">
    <div>
        <h1>Validación de movimientos</h1>
        <p class="text-secondary small mb-0">Solo los movimientos validados cambian el estado del equipo y cuentan en los indicadores.</p>
    </div>
</div>

<div class="tarjeta mb-4">
    <div class="p-3 pb-2"><h2 class="h6 mb-0">Pendientes (<?= count($pendientes) ?>)</h2></div>
    <div class="table-responsive">
        <table class="table tabla-ew align-middle mb-0 tabla-responsiva-movil">
            <thead><tr><th>Equipo</th><th>Movimiento</th><th>Detalle</th><th>Registró</th><th>Fecha</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
            <?php foreach ($pendientes as $m): ?>
                <tr>
                    <td data-etiqueta="Equipo"><a class="codigo" href="<?= url('panel/equipos/' . $m->equipoId) ?>"><?= e($m->extra['equipo_codigo']) ?></a><div class="small text-secondary"><?= e($m->extra['equipo_desc']) ?></div></td>
                    <td data-etiqueta="Movimiento"><?= e($m->nombreTipo()) ?></td>
                    <td data-etiqueta="Detalle" class="small">
                        <?= e($m->destino ?? '') ?>
                        <?= $m->costo !== null ? '<br>Costo: ' . dinero($m->costo) : '' ?>
                        <?= $m->evidencia ? '<br>Evidencia: ' . e($m->evidencia) : '' ?>
                        <?php if ($m->tipo === 'entrega_gestor' && !$m->destinoVerificado): ?><br><span class="badge text-bg-warning text-dark">Gestor sin verificar</span><?php endif; ?>
                    </td>
                    <td data-etiqueta="Registró" class="small"><?= e($m->extra['registrado_por']) ?></td>
                    <td data-etiqueta="Fecha" class="small"><?= fecha($m->fecha) ?></td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                            <form method="post" action="<?= url('panel/movimientos/' . $m->id . '/validar') ?>" data-confirmar="¿Validar este movimiento? Se aplicará al equipo.">
                                <?= csrf_campo() ?>
                                <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-check-lg"></i> Validar</button>
                            </form>
                            <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#rechazo<?= $m->id ?>" aria-expanded="false"><i class="bi bi-x-lg"></i> Rechazar</button>
                        </div>
                        <form class="collapse mt-2" id="rechazo<?= $m->id ?>" method="post" action="<?= url('panel/movimientos/' . $m->id . '/rechazar') ?>">
                            <?= csrf_campo() ?>
                            <div class="input-group input-group-sm">
                                <input class="form-control" name="motivo" required maxlength="900" placeholder="Motivo del rechazo" aria-label="Motivo del rechazo">
                                <button class="btn btn-danger" type="submit">Confirmar</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pendientes): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4"><i class="bi bi-check2-all"></i> No hay movimientos pendientes.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="tarjeta">
    <div class="p-3 pb-2"><h2 class="h6 mb-0">Resueltos recientemente</h2></div>
    <div class="table-responsive">
        <table class="table tabla-ew mb-0 tabla-responsiva-movil">
            <thead><tr><th>Equipo</th><th>Movimiento</th><th>Resultado</th><th>Validó</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($resueltos as $m): ?>
                <tr>
                    <td data-etiqueta="Equipo"><a class="codigo" href="<?= url('panel/equipos/' . $m->equipoId) ?>"><?= e($m->extra['equipo_codigo']) ?></a></td>
                    <td data-etiqueta="Movimiento"><?= e($m->nombreTipo()) ?></td>
                    <td data-etiqueta="Resultado"><?= insignia($m->estadoValidacion, $m->nombreEstado()) ?></td>
                    <td data-etiqueta="Validó" class="small"><?= e($m->extra['validado_por'] ?? '—') ?></td>
                    <td data-etiqueta="Fecha" class="small"><?= fecha($m->validadoEn, true) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
