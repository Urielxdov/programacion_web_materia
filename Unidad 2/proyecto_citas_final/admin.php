<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/form_cita.php';
require_admin();

$errores = [];
$valores = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $c = $id ? obtener_cita($id) : null;

    if ($accion === 'cancelar' && $c && $c['estado'] === 'confirmada') {
        cancelar_cita($id);
        flash('Cita cancelada y notificada por correo.');
        redirect('admin.php');
    }
    if ($accion === 'eliminar' && $c) {
        pdo()->prepare('DELETE FROM citas WHERE id = ?')->execute([$id]);
        flash('Cita eliminada definitivamente.');
        redirect('admin.php');
    }
    if ($accion === 'crear' || $accion === 'modificar') {
        [$d, $errores] = validar_cita($_POST, true, $accion === 'modificar' ? $id : null);
        $uidSel = ($_POST['usuario_id'] ?? '') !== '' ? (int)$_POST['usuario_id'] : null;
        if (!$errores) {
            if ($accion === 'crear') { crear_cita($d, $uidSel); flash('Cita creada y confirmada por correo.'); }
            elseif ($c) {
                pdo()->prepare('UPDATE citas SET usuario_id = ? WHERE id = ?')->execute([$uidSel, $id]);
                modificar_cita($id, $d);
                flash('Cita modificada y notificada por correo.');
            }
            redirect('admin.php');
        }
        $valores = $d + ['id' => $id ?: '', 'usuario_id' => $uidSel];
    }
}

$editar = isset($_GET['editar']) ? obtener_cita((int)$_GET['editar']) : null;
$form = $valores ?? $editar ?? [];
$editando = !empty($form['id']);

$usuarios = pdo()->query('SELECT id, nombre, correo FROM usuarios ORDER BY nombre')->fetchAll();
$citas = pdo()->query('SELECT c.*, u.nombre AS usuario FROM citas c LEFT JOIN usuarios u ON u.id = c.usuario_id ORDER BY c.fecha DESC, c.hora')->fetchAll();

layout_ini('Panel de administración');
?>
<h1>Panel de administración</h1>
<?php foreach ($errores as $er): ?><div class="mensaje error"><?= e($er) ?></div><?php endforeach; ?>
<?php form_cita($form, 'admin.php', $editando, $usuarios); ?>

<h2>Todas las citas</h2>
<table>
    <tr><th>#</th><th>Paciente</th><th>Usuario</th><th>Teléfono</th><th>Correo</th><th>Fecha</th><th>Hora</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr>
    <?php foreach ($citas as $c): ?>
    <tr>
        <td><?= $c['id'] ?></td><td><?= e($c['paciente']) ?></td><td><?= e($c['usuario'] ?? '—') ?></td>
        <td><?= e($c['telefono']) ?></td><td><?= e($c['correo']) ?></td>
        <td><?= e($c['fecha']) ?></td><td><?= e($c['hora']) ?></td><td><?= e($c['problema']) ?></td>
        <td class="estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></td>
        <td>
            <a class="btn btn-editar" href="?editar=<?= $c['id'] ?>">Editar</a>
            <?php if ($c['estado'] === 'confirmada'): ?>
            <form class="inline" method="POST" onsubmit="return confirm('¿Cancelar la cita?');">
                <?= csrf_field() ?><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="btn btn-cancelar" type="submit">Cancelar</button>
            </form>
            <?php endif; ?>
            <form class="inline" method="POST" onsubmit="return confirm('¿Eliminar definitivamente?');">
                <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="btn btn-eliminar" type="submit">Eliminar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$citas): ?><tr><td colspan="10" class="centro">No hay citas.</td></tr><?php endif; ?>
</table>
<?php layout_fin();
