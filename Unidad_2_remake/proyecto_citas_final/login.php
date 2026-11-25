<?php
require __DIR__ . '/lib.php';
if (usuario()) redirect('citas.php');

$error = '';
$correo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $correo = trim($_POST['correo'] ?? '');
    $st = pdo()->prepare('SELECT * FROM usuarios WHERE correo = ?');
    $st->execute([$correo]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        unset($u['password_hash']);
        $_SESSION['usuario'] = $u;
        redirect('citas.php');
    }
    $error = 'Correo o contraseña incorrectos.';
}

layout_ini('Iniciar sesión');
?>
<div class="login">
    <h1>Iniciar sesión</h1>
    <p class="centro">Sistema de citas · Consultorio del Dr. Xavier</p>
    <?php if ($error): ?><div class="mensaje error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" style="margin-bottom:15px">
        <?= csrf_field() ?>
        <label>Correo:</label>
        <input type="email" name="correo" value="<?= e($correo) ?>" required>
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <button type="submit">Entrar</button>
    </form>
    <div class="aviso">Cuentas de prueba (setup.php):<br>
        <strong>Administrador:</strong> admin@clinica.local / admin123<br>
        <strong>Asistente:</strong> asistente@clinica.local / asistente123</div>
</div>
<?php layout_fin();
