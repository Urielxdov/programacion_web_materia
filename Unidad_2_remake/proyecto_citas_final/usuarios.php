<?php
// Gestión de usuarios del sistema (solo administrador).
require __DIR__ . '/lib.php';
$yo = require_admin();

$errores = [];
$editId = (int)($_GET['editar'] ?? $_POST['id'] ?? 0);
$v = ['nombre' => '', 'correo' => '', 'rol' => 'asistente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$yo['id']) flash('No puedes eliminar tu propia cuenta.', 'error');
        else { pdo()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]); flash('Usuario eliminado.'); }
        redirect('usuarios.php');
    }

    if ($accion === 'guardar') {
        foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($v['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if (!filter_var($v['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';
        if (!isset(ROLES[$v['rol']])) $errores[] = 'Rol inválido.';
        if ($editId === (int)$yo['id'] && $v['rol'] !== 'admin') $errores[] = 'No puedes quitarte el rol de administrador.';
        if (!$editId || $pass !== '') {
            if (strlen($pass) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
            if ($pass !== ($_POST['password2'] ?? '')) $errores[] = 'Las contraseñas no coinciden.';
        }
        if (!$errores) {
            $st = pdo()->prepare('SELECT id FROM usuarios WHERE correo = ? AND id <> ?');
            $st->execute([$v['correo'], $editId]);
            if ($st->fetch()) $errores[] = 'Ese correo ya está registrado.';
        }
        if (!$errores) {
            if ($editId) {
                pdo()->prepare('UPDATE usuarios SET nombre = ?, correo = ?, rol = ? WHERE id = ?')->execute([$v['nombre'], $v['correo'], $v['rol'], $editId]);
                if ($pass !== '') pdo()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $editId]);
                if ($editId === (int)$yo['id']) $_SESSION['usuario'] = array_merge($yo, $v);
                flash('Usuario actualizado.');
            } else {
                pdo()->prepare('INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (?,?,?,?)')
                    ->execute([$v['nombre'], $v['correo'], password_hash($pass, PASSWORD_DEFAULT), $v['rol']]);
                flash('Usuario creado.');
            }
            redirect('usuarios.php');
        }
    }
} elseif ($editId) {
    $st = pdo()->prepare('SELECT nombre, correo, rol FROM usuarios WHERE id = ?');
    $st->execute([$editId]);
    $v = $st->fetch() ?: redirect('usuarios.php');
}

$usuarios = pdo()->query('SELECT u.*, (SELECT COUNT(*) FROM citas c WHERE c.creado_por = u.id) AS citas FROM usuarios u ORDER BY u.rol, u.nombre')->fetchAll();

layout_ini('Usuarios');
?>
<h1>Usuarios del sistema</h1>
<div class="login">
    <h2><?= $editId ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
    <?php foreach ($errores as $er): ?><div class="mensaje error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= $editId ?: '' ?>">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($v['nombre']) ?>" required>
        <label for="correo">Correo (usuario de acceso):</label>
        <input type="email" id="correo" name="correo" value="<?= e($v['correo']) ?>" required>
        <label for="rol">Rol:</label>
        <select id="rol" name="rol">
            <?php foreach (ROLES as $k => $nom): ?>
                <option value="<?= $k ?>" <?= $v['rol'] === $k ? 'selected' : '' ?>><?= $nom ?><?= $k === 'admin' ? ' (todos los privilegios)' : ' (alta y consulta de citas)' ?></option>
            <?php endforeach; ?>
        </select>
        <label for="password">Contraseña<?= $editId ? ' (vacía = no cambiar)' : '' ?>:</label>
        <input type="password" id="password" name="password" <?= $editId ? '' : 'required' ?>>
        <label for="password2">Repite la contraseña:</label>
        <input type="password" id="password2" name="password2" <?= $editId ? '' : 'required' ?>>
        <button type="submit"><?= $editId ? 'Guardar cambios' : 'Crear usuario' ?></button>
        <?php if ($editId): ?><div class="centro"><a href="usuarios.php">Cancelar edición</a></div><?php endif; ?>
    </form>
</div>

<div class="tabla-scroll">
<table>
    <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Citas registradas</th><th>Alta</th><th>Acciones</th></tr>
    <?php foreach ($usuarios as $us): ?>
    <tr>
        <td><?= e($us['nombre']) ?></td>
        <td><?= e($us['correo']) ?></td>
        <td><span class="rol rol-<?= e($us['rol']) ?>"><?= e(ROLES[$us['rol']]) ?></span></td>
        <td><?= $us['citas'] ?></td>
        <td><?= fecha_mx($us['creado']) ?></td>
        <td class="acciones">
            <a class="btn btn-editar" href="?editar=<?= $us['id'] ?>">Editar</a>
            <?php if ((int)$us['id'] !== (int)$yo['id']): ?>
            <form class="inline" method="POST" onsubmit="return confirm('¿Eliminar al usuario <?= e($us['nombre']) ?>?');">
                <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $us['id'] ?>">
                <button class="btn btn-eliminar" type="submit">Eliminar</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php layout_fin();
