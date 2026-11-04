<?php
require __DIR__ . '/lib.php';
if (usuario()) redirect('citas.php');

$errores = [];
$v = ['nombre' => '', 'correo' => '', 'telefono' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($v['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($v['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $v['telefono'])) $errores[] = 'Número de teléfono inválido.';
    if (strlen($pass) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($pass !== ($_POST['password2'] ?? '')) $errores[] = 'Las contraseñas no coinciden.';
    if (!$errores) {
        $st = pdo()->prepare('SELECT id FROM usuarios WHERE correo = ?');
        $st->execute([$v['correo']]);
        if ($st->fetch()) $errores[] = 'Ese correo ya está registrado.';
    }
    if (!$errores) {
        pdo()->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES (?,?,?,?, 'usuario')")
            ->execute([$v['nombre'], $v['correo'], $v['telefono'], password_hash($pass, PASSWORD_DEFAULT)]);
        flash('Cuenta creada. Ya puedes iniciar sesión.');
        redirect('login.php');
    }
}

layout_ini('Registro');
?>
<div class="login">
    <h1>Crear cuenta</h1>
    <?php foreach ($errores as $er): ?><div class="mensaje error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= e($v['nombre']) ?>" required>
        <label>Correo:</label>
        <input type="email" name="correo" value="<?= e($v['correo']) ?>" required>
        <label>Número de teléfono:</label>
        <input type="tel" name="telefono" value="<?= e($v['telefono']) ?>" required>
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <label>Repite la contraseña:</label>
        <input type="password" name="password2" required>
        <button type="submit">Registrarme</button>
    </form>
    <div class="centro">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></div>
</div>
<?php layout_fin();
