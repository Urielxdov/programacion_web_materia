<div class="encabezado-pagina">
    <div>
        <h1>Indicadores y reportes</h1>
        <p class="text-secondary small mb-0">Generado con datos validados al <span data-fecha-reporte>—</span>. Solo cuentan los movimientos validados.</p>
    </div>
    <div class="d-flex gap-2 no-imprimir">
        <a class="btn btn-outline-secondary" href="<?= url('panel/reportes/inventario.csv') ?>"><i class="bi bi-filetype-csv"></i> Inventario CSV</a>
        <button class="btn btn-primary" type="button" data-imprimir><i class="bi bi-printer"></i> Imprimir / PDF</button>
    </div>
</div>

<div id="reportes"
     data-api-generales="<?= url('api/indicadores') ?>"
     data-api-reutilizacion="<?= url('api/indicadores/reutilizacion') ?>"
     data-api-campanas="<?= url('api/indicadores/campanas') ?>">

    <div class="row g-3 mb-3">
        <?php foreach ([
            ['total', 'pc-display', '', 'Equipos registrados'],
            ['tasa', 'arrow-repeat', 'verde', 'Tasa de aprovechamiento'],
            ['ahorro', 'piggy-bank', 'verde', 'Ahorro estimado'],
            ['costo', 'tools', 'ambar', 'Invertido en reparaciones'],
            ['kg_verificados', 'truck', '', 'Kg a gestores verificados'],
            ['no_verificadas', 'exclamation-triangle', 'rojo', 'Entregas sin destino verificado'],
            ['participaciones', 'people', 'ambar', 'Participaciones en campañas'],
            ['pendientes', 'hourglass-split', 'rojo', 'Movimientos por validar'],
        ] as [$clave, $icono, $color, $texto]): ?>
            <div class="col-sm-6 col-xl-3">
                <div class="tarjeta kpi"><span class="icono-circulo <?= $color ?>"><i class="bi bi-<?= $icono ?>"></i></span>
                    <div><div class="valor" data-indicador="<?= $clave ?>"><span class="placeholder col-6"></span></div><div class="texto"><?= e($texto) ?></div></div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="tarjeta p-3 h-100"><h2 class="h6">Equipos por estado</h2><div class="contenedor-grafica"><canvas id="graficaEstado" role="img" aria-label="Equipos por estado"></canvas></div></div>
        </div>
        <div class="col-lg-6">
            <div class="tarjeta p-3 h-100"><h2 class="h6">Equipos por categoría</h2><div class="contenedor-grafica"><canvas id="graficaCategoria" role="img" aria-label="Equipos por categoría"></canvas></div></div>
        </div>
        <div class="col-lg-7">
            <div class="tarjeta p-3 h-100"><h2 class="h6">Reparaciones y reasignaciones por mes</h2><div class="contenedor-grafica"><canvas id="graficaAprovechamiento" role="img" aria-label="Reparaciones y reasignaciones por mes"></canvas></div></div>
        </div>
        <div class="col-lg-5">
            <div class="tarjeta p-3 h-100">
                <h2 class="h6">Aprovechamiento por categoría</h2>
                <div class="table-responsive">
                    <table class="table table-sm tabla-ew mb-0">
                        <thead><tr><th>Categoría</th><th class="text-end">Reparados</th><th class="text-end">Reutilizados</th></tr></thead>
                        <tbody id="tablaAprovechamiento"><tr><td colspan="3" class="text-secondary small">Cargando…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="tarjeta">
                <div class="p-3 pb-2"><h2 class="h6 mb-0">Resultados por campaña</h2></div>
                <div class="table-responsive">
                    <table class="table tabla-ew mb-0">
                        <thead><tr><th>Campaña</th><th>Estado</th><th class="text-end">Participantes</th><th class="text-end">Asistieron</th><th class="text-end">Comprometidos</th><th class="text-end">Recibidos</th><th class="text-end">Kg</th><th>Meta</th></tr></thead>
                        <tbody id="tablaCampanas"><tr><td colspan="8" class="text-secondary small">Cargando…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
