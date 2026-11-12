<?php
require __DIR__.'/includes/app.php'; $errors = [];
$hash1 = getenv('UNIDAD2_PASSWORD_HASH') ?: '$2y$12$nrInCcJqLy4XW5xBhrCRXOervaDbghetf4rXFl7qHIxiE0r1K/dD2';
$hash2 = getenv('UNIDAD2_PASSWORD_HASH_2') ?: '$2y$12$Um4h4nqHCTCZDWgi/FRlNOZJr8q8V0EUjmgBbXlPOuyrm/bOJEzva';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (field('accion') === 'salir') { $_SESSION = []; session_regenerate_id(true); redirect('proteccion.php'); }
    $clave = field('clave'); $seccion = 0;
    if (password_verify($clave, $hash1)) $seccion = 1;
    elseif (password_verify($clave, $hash2)) $seccion = 2;
    if ($seccion > 0) { session_regenerate_id(true); $_SESSION['seccion'] = $seccion; redirect('proteccion.php'); }
    $errors[] = 'Lo siento, no puedes entrar. Contraseña incorrecta.';
}
switch ((int)($_SESSION['seccion'] ?? 0)) {
    case 1: $titulo = 'Sección 1'; $texto = 'Contenido interesante de la primera sección: solo se genera si la contraseña coincide con la primera.'; break;
    case 2: $titulo = 'Sección 2'; $texto = 'Contenido interesante de la segunda sección: esta página responde distinto según la contraseña recibida.'; break;
    default: $titulo = null;
}
page('Protección con contraseña'); errors($errors);
if ($titulo !== null): ?>
<section class="panel"><span class="tag">Sesión iniciada</span><h2><?= e($titulo) ?> protegida</h2><p><?= e($texto) ?></p><p>Las sesiones conservan tu autorización entre solicitudes. Cierra la sesión al terminar.</p><form method="post"><?php csrf(); ?><input type="hidden" name="accion" value="salir"><button>Cerrar sesión</button></form></section>
<?php else: ?>
<form method="post" class="panel"><?php csrf(); ?><label>¿Conoces la contraseña?<input type="password" name="clave" required autocomplete="current-password"></label><button>Entrar</button></form>
<?php endif; endpage(); ?>
