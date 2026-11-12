<?php
$u = usuario_actual();
$roles = App\Models\Usuario::ROLES;
$menu = [
    'Operación' => [
        ['panel', 'speedometer2', 'Tablero', 'panel.ver', true],
        ['panel/equipos', 'pc-display-horizontal', 'Inventario', 'equipos.ver', false],
        ['panel/movimientos', 'clipboard-check', 'Validar movimientos', 'movimientos.validar', false],
        ['panel/campanas', 'megaphone', 'Campañas', 'campanas.gestionar', false],
        ['panel/reportes', 'bar-chart-line', 'Indicadores', 'reportes.ver', false],
    ],
    'Administración' => [
        ['panel/usuarios', 'people', 'Usuarios y roles', 'usuarios.gestionar', false],
        ['panel/categorias', 'tags', 'Categorías', 'catalogos.gestionar', false],
        ['panel/gestores', 'recycle', 'Gestores', 'catalogos.gestionar', false],
        ['panel/configuracion', 'sliders', 'Configuración', 'configuracion.gestionar', false],
    ],
];
$rutaActual = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', strlen(rtrim(url(''), '/'))), '/');
?>
<!doctype html>
<html lang="es">
<head>
    <?= App\Core\View::parcial('cabeza', ['titulo' => $titulo ?? null]) ?>
</head>
<body>
<a class="visually-hidden-focusable btn btn-light position-absolute m-2" href="#contenido">Saltar al contenido</a>
<div class="panel-contenedor">
    <aside class="barra-lateral offcanvas-lg offcanvas-start" tabindex="-1" id="menuPanel" aria-label="Menú del panel">
        <div class="d-flex align-items-center justify-content-between">
            <a class="marca" href="<?= url('panel') ?>">
                <img src="<?= asset('img/logo.svg') ?>" alt="">
                <span class="fw-semibold">E-Waste 360°</span>
            </a>
            <button type="button" class="btn-close btn-close-white me-3 d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#menuPanel" aria-label="Cerrar menú"></button>
        </div>
        <nav class="pb-4">
            <?php foreach ($menu as $seccion => $enlaces):
                $visibles = array_filter($enlaces, static fn (array $l): bool => puede($l[3]));
                if (!$visibles) { continue; } ?>
                <div class="seccion-menu"><?= e($seccion) ?></div>
                <?php foreach ($visibles as [$ruta, $icono, $texto, , $exacta]):
                    $activo = $exacta ? $rutaActual === '/' . $ruta : str_starts_with($rutaActual . '/', '/' . $ruta . '/'); ?>
                    <a class="nav-link<?= $activo ? ' active' : '' ?>" href="<?= url($ruta) ?>"<?= $activo ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-<?= $icono ?>" aria-hidden="true"></i> <?= e($texto) ?>
                        <?php if ($ruta === 'panel/movimientos'): ?>
                            <span class="badge rounded-pill text-bg-warning contador d-none" data-contador="movimientos"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <div class="seccion-menu">Sitio</div>
            <a class="nav-link" href="<?= url('') ?>"><i class="bi bi-globe2" aria-hidden="true"></i> Sitio público</a>
        </nav>
    </aside>

    <div class="panel-principal">
        <header class="barra-superior">
            <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPanel" aria-controls="menuPanel" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <form class="d-none d-md-flex flex-grow-1" role="search" action="<?= url('panel/equipos') ?>" method="get" style="max-width: 420px">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input class="form-control" type="search" name="q" placeholder="Buscar por código, marca, modelo o serie" aria-label="Buscar equipos">
                </div>
            </form>
            <div class="ms-auto dropdown">
                <button class="btn btn-link text-decoration-none text-reset d-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['nombre'] ?? '?', 0, 1))) ?></span>
                    <span class="d-none d-sm-inline text-start lh-sm">
                        <span class="d-block fw-semibold small"><?= e($u['nombre'] ?? '') ?></span>
                        <span class="d-block text-secondary" style="font-size:.75rem"><?= e($roles[$u['rol'] ?? ''] ?? '') ?></span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-secondary">Sesión: <?= e($u['usuario'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="<?= url('logout') ?>" method="post">
                            <?= csrf_campo() ?>
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main id="contenido" class="panel-cuerpo">
            <?= App\Core\View::parcial('alertas') ?>
            <?= $contenido ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($graficas)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/panel.js') ?>"></script>
</body>
</html>
