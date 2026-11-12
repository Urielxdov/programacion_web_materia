<!doctype html>
<html lang="es">
<head>
    <?= App\Core\View::parcial('cabeza', ['titulo' => $titulo ?? null]) ?>
</head>
<body class="d-flex flex-column">
<a class="visually-hidden-focusable btn btn-light position-absolute m-2" href="#contenido">Saltar al contenido</a>

<nav class="navbar navbar-expand-lg navbar-publica sticky-top" aria-label="Navegación principal">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('') ?>">
            <img src="<?= asset('img/logo.svg') ?>" alt="">
            <span>E-Waste 360°</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico" aria-controls="menuPublico" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPublico">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" data-ruta="/" href="<?= url('') ?>">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" data-ruta="/informacion" href="<?= url('informacion') ?>">Aprende y actúa</a></li>
                <li class="nav-item"><a class="nav-link" data-ruta="/campanas" href="<?= url('campanas') ?>">Campañas</a></li>
                <?php if (usuario_actual()): ?>
                    <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="<?= url('panel') ?>"><i class="bi bi-speedometer2"></i> Ir al panel</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-outline-primary btn-sm px-3" href="<?= url('login') ?>"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main id="contenido" class="flex-grow-1">
    <?php $alertas = trim(App\Core\View::parcial('alertas')); ?>
    <?php if ($alertas !== ''): ?><div class="container mt-3"><?= $alertas ?></div><?php endif; ?>
    <?= $contenido ?>
</main>

<footer class="pie-publico mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="<?= asset('img/logo.svg') ?>" alt="" width="30" height="30">
                    <strong class="text-white">E-Waste 360°</strong>
                </div>
                <p class="small mb-0">Plataforma para registrar, reparar, reutilizar y entregar de forma responsable los equipos electrónicos de la institución.</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="h6 text-white">Sitio</h2>
                <ul class="list-unstyled small mb-0">
                    <li><a href="<?= url('informacion') ?>">Aprende y actúa</a></li>
                    <li><a href="<?= url('campanas') ?>">Campañas</a></li>
                    <li><a href="<?= url('login') ?>">Acceso del personal</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="h6 text-white">Proyecto integrador</h2>
                <p class="small mb-0">Programación Web · Desarrollo Sustentable<br>Ingeniería en Sistemas Computacionales</p>
            </div>
        </div>
        <hr class="border-light opacity-25">
        <p class="small mb-0 text-center">© <?= date('Y') ?> E-Waste 360°. Datos de demostración con fines académicos.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/publico.js') ?>"></script>
</body>
</html>
