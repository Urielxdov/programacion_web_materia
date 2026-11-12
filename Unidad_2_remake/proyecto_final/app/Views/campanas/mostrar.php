<?php /** @var App\Models\Campana $campana */ ?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/campanas') ?>">Campañas</a></li>
            <li class="breadcrumb-item active" aria-current="page">Resultados</li>
        </ol></nav>
        <h1><?= e($campana->nombre) ?> <?= insignia($campana->estado, $campana->nombreEstado()) ?></h1>
        <p class="small text-secondary mb-0"><?= fecha($campana->fechaInicio) ?> – <?= fecha($campana->fechaFin) ?> · <?= e($campana->ubicacion) ?></p>
    </div>
    <div class="d-flex gap-2 no-imprimir">
        <?php if ($campana->esPublica()): ?><a class="btn btn-outline-secondary" href="<?= url('campanas/' . $campana->id) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Página pública</a><?php endif; ?>
        <a class="btn btn-outline-primary" href="<?= url('panel/campanas/' . $campana->id . '/editar') ?>"><i class="bi bi-pencil"></i> Editar</a>
        <button class="btn btn-outline-secondary" type="button" data-imprimir><i class="bi bi-printer"></i></button>
    </div>
</div>

<div class="row g-3 mb-3">
    <?php foreach ([
        ['people', '', $resumen['participantes'], 'Participantes registrados'],
        ['person-check', 'verde', $resumen['asistentes'], 'Asistieron'],
        ['box-seam', 'ambar', $resumen['recibidos'] . ' / ' . $resumen['comprometidos'], 'Equipos recibidos / comprometidos'],
        ['speedometer', 'verde', numero($resumen['kg'], 1) . ' kg', 'Peso recibido'],
    ] as [$icono, $color, $valor, $texto]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="tarjeta kpi"><span class="icono-circulo <?= $color ?>"><i class="bi bi-<?= $icono ?>"></i></span>
                <div><div class="valor"><?= e($valor) ?></div><div class="texto"><?= e($texto) ?></div></div></div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($resumen['avance_meta'] !== null): ?>
    <div class="tarjeta p-3 mb-3">
        <div class="d-flex justify-content-between small mb-1"><span>Avance de la meta (<?= (int) $campana->metaEquipos ?> equipos)</span><strong><?= (int) $resumen['avance_meta'] ?>%</strong></div>
        <div class="progress" role="progressbar" aria-valuenow="<?= (int) $resumen['avance_meta'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Avance de la meta">
            <div class="progress-bar bg-success" style="width: <?= (int) $resumen['avance_meta'] ?>%"></div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="tarjeta">
            <div class="p-3 pb-2"><h2 class="h6 mb-0">Participaciones</h2></div>
            <div class="table-responsive">
                <table class="table tabla-ew mb-0 tabla-responsiva-movil">
                    <thead><tr><th>Nombre</th><th>Correo</th><th>Equipos</th><th>Registro</th><th>Asistió</th></tr></thead>
                    <tbody>
                    <?php foreach ($participaciones as $p): ?>
                        <tr>
                            <td data-etiqueta="Nombre"><?= e($p->nombre) ?><div class="small text-secondary"><?= e($p->tiposEquipo ?? '') ?></div></td>
                            <td data-etiqueta="Correo" class="small"><?= e($p->correoOculto() ?? '—') ?></td>
                            <td data-etiqueta="Equipos"><?= (int) $p->equiposComprometidos ?></td>
                            <td data-etiqueta="Registro" class="small"><?= fecha($p->fechaRegistro) ?></td>
                            <td data-etiqueta="Asistió">
                                <form method="post" action="<?= url('panel/campanas/' . $campana->id . '/participaciones/' . $p->id . '/asistencia') ?>">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="asistio" value="<?= $p->asistio ? '0' : '1' ?>">
                                    <button class="btn btn-sm <?= $p->asistio ? 'btn-success' : 'btn-outline-secondary' ?>" type="submit" title="Cambiar asistencia">
                                        <i class="bi bi-<?= $p->asistio ? 'check-lg' : 'dash' ?>"></i> <?= $p->asistio ? 'Sí' : 'No' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$participaciones): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">Sin participaciones registradas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="tarjeta p-3 mb-3">
            <h2 class="h6">Equipos recibidos por clasificación</h2>
            <?php if (!$resumen['por_clasificacion']): ?>
                <p class="small text-secondary mb-0">Aún no se registran equipos de esta campaña. Al registrar un equipo, selecciona la campaña en el campo «Recibido en campaña».</p>
            <?php endif; ?>
            <?php foreach ($resumen['por_clasificacion'] as $nombre => $total): ?>
                <div class="d-flex justify-content-between border-bottom py-1 small"><span><?= e($nombre) ?></span><strong><?= (int) $total ?></strong></div>
            <?php endforeach; ?>
        </div>
        <div class="tarjeta">
            <div class="p-3 pb-2"><h2 class="h6 mb-0">Equipos recibidos</h2></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($equipos as $e): ?>
                    <li class="list-group-item d-flex justify-content-between gap-2">
                        <span><a class="codigo" href="<?= url('panel/equipos/' . $e->id()) ?>"><?= e($e->codigo()) ?></a> <span class="small"><?= e($e->marca() . ' ' . $e->modelo()) ?></span></span>
                        <?= insignia($e->estado(), $e->nombreEstado()) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
