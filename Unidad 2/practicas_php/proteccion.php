<?php
require __DIR__.'/includes/app.php'; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (field('accion') === 'salir') { $_SESSION = []; session_regenerate_id(true); redirect('proteccion.php'); }
    $hash = getenv('UNIDAD2_PASSWORD_HASH') ?: '$2y$12$nrInCcJqLy4XW5xBhrCRXOervaDbghetf4rXFl7qHIxiE0r1K/dD2';
    if (password_verify(field('clave'), $hash)) { session_regenerate_id(true); $_SESSION['autorizado'] = true; redirect('proteccion.php'); }
    $errors[] = 'Contraseña incorrecta. Intenta de nuevo.';
}
page('Protección con contraseña'); errors($errors);
if (!empty($_SESSION['autorizado'])): ?>
<section class="panel"><span class="tag">Sesión iniciada</span><h2>Bienvenido al área protegida</h2><p>Este contenido se genera únicamente después de comprobar la contraseña en el servidor.</p><p>Las sesiones conservan tu autorización entre solicitudes. Cierra la sesión al terminar.</p><form method="post"><?php csrf(); ?><input type="hidden" name="accion" value="salir"><button>Cerrar sesión</button></form></section>
<?php else: ?>
<form method="post" class="panel"><?php csrf(); ?><label>Contraseña<input type="password" name="clave" required autocomplete="current-password"></label><button>Entrar</button></form>
<?php endif; endpage(); ?>
