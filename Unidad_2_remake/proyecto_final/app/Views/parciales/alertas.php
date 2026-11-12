<?php
$alertas = [
    'exito' => ['success', 'check-circle-fill'],
    'aviso' => ['warning', 'info-circle-fill'],
    'error' => ['danger', 'exclamation-triangle-fill'],
];
foreach ($alertas as $clave => [$color, $icono]):
    $mensaje = flash($clave);
    if (!$mensaje) {
        continue;
    }
?>
<div class="alert alert-<?= $color ?> alert-dismissible fade show d-flex gap-2 align-items-start" role="alert" data-autocerrar="<?= $clave === 'exito' ? '6000' : '' ?>">
    <i class="bi bi-<?= $icono ?> mt-1" aria-hidden="true"></i>
    <div><?= e($mensaje) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endforeach; ?>
