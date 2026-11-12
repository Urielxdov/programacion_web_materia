<div class="encabezado-pagina">
    <div>
        <h1>Tablero</h1>
        <p class="text-secondary mb-0 small">Resumen de la gestión de equipos electrónicos.</p>
    </div>
    <?php if (puede('equipos.crear')): ?>
        <a class="btn btn-primary" href="<?= url('panel/equipos/nuevo') ?>"><i class="bi bi-plus-lg"></i> Registrar equipo</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4" id="kpis" data-api="<?= url('api/indicadores') ?>">
    <?php foreach ([
        ['total', 'pc-display', '', 'Equipos registrados'],
        ['aprovechados', 'arrow-repeat', 'verde', 'Reparados o reutilizados'],
        ['pendientes_diagnostico', 'clipboard-pulse', 'ambar', 'Pendientes de diagnóstico'],
        ['ahorro', 'piggy-bank', 'verde', 'Ahorro estimado'],
    ] as [$clave, $icono, $color, $texto]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="tarjeta kpi">
                <span class="icono-circulo <?= $color ?>"><i class="bi bi-<?= $icono ?>"></i></span>
                <div>
                    <div class="valor" data-kpi="<?= $clave ?>"><span class="placeholder col-6"></span></div>
                    <div class="texto"><?= e($texto) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="tarjeta p-3 h-100">
            <h2 class="h6 mb-3">Equipos por clasificación</h2>
            <div class="contenedor-grafica"><canvas id="graficaClasificacion" aria-label="Gráfica de equipos por clasificación" role="img"></canvas></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="tarjeta p-3 h-100">
            <h2 class="h6 mb-3">Ingresos al inventario (últimos 12 meses)</h2>
            <div class="contenedor-grafica"><canvas id="graficaIngresos" aria-label="Gráfica de ingresos por mes" role="img"></canvas></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="tarjeta h-100">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h2 class="h6 mb-0">Registrados recientemente</h2>
                <a class="small" href="<?= url('panel/equipos') ?>">Ver inventario</a>
            </div>
            <div class="table-responsive">
                <table class="table tabla-ew table-hover">
                    <thead><tr><th>Código</th><th>Equipo</th><th>Clasificación</th><th>Ingreso</th></tr></thead>
                    <tbody>
                    <?php foreach ($recientes as $e): ?>
                        <tr>
                            <td><a class="codigo" href="<?= url('panel/equipos/' . $e->id()) ?>"><?= e($e->codigo()) ?></a></td>
                            <td><i class="bi bi-<?= $e->icono() ?> text-secondary"></i> <?= e($e->marca() . ' ' . $e->modelo()) ?></td>
                            <td><?= insignia($e->clasificacion(), $e->nombreClasificacion()) ?></td>
                            <td class="small"><?= fecha($e->fechaIngreso()) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recientes): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">Aún no hay equipos registrados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="tarjeta p-3 h-100">
            <?php if (puede('movimientos.validar')): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Movimientos por validar</h2>
                    <a class="small" href="<?= url('panel/movimientos') ?>">Ir a validación</a>
                </div>
                <?php if (!$pendientes): ?>
                    <p class="text-secondary small mb-0"><i class="bi bi-check2-all"></i> No hay movimientos pendientes.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendientes as $m): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                                <span><a class="codigo" href="<?= url('panel/equipos/' . $m->equipoId) ?>"><?= e($m->extra['equipo_codigo']) ?></a>
                                    <span class="small d-block text-secondary"><?= e($m->nombreTipo()) ?> · <?= e($m->extra['registrado_por']) ?></span></span>
                                <span class="small text-secondary"><?= fecha($m->fecha) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <h2 class="h6">Tu rol: capturista</h2>
                <p class="small text-secondary">Registras equipos y movimientos. Un responsable ambiental diagnostica los equipos y valida los movimientos que registres.</p>
                <a class="btn btn-outline-primary btn-sm" href="<?= url('panel/equipos?clasificacion=pendiente') ?>">Equipos pendientes de evaluación</a>
            <?php endif; ?>
        </div>
    </div>
</div>
