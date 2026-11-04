<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/form_cita.php';
$u = require_login();
$uid = (int)$u['id'];

function cita_propia(int $id, int $uid): ?array {
    $c = obtener_cita($id);
    return ($c && (int)$c['usuario_id'] === $uid) ? $c : null;
}

$errores = [];
$valores = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'cancelar') {
        $c = cita_propia($id, $uid);
        if ($c && $c['estado'] === 'confirmada') {
            cancelar_cita($id);
            flash('Cita cancelada. Te enviamos un correo de confirmación.');
        }
        redirect('citas.php');
    }

    if ($accion === 'crear' || $accion === 'modificar') {
        if ($accion === 'modificar' && !($actual = cita_propia($id, $uid))) redirect('citas.php');
        if ($accion === 'modificar' && $actual['estado'] === 'cancelada') redirect('citas.php');
        [$d, $errores] = validar_cita($_POST, false, $accion === 'modificar' ? $id : null);
        if (!$errores) {
            if ($accion === 'crear') { crear_cita($d, $uid); flash('¡Cita agendada! Te enviamos la confirmación por correo.'); }
            else { modificar_cita($id, $d); flash('Cita modificada. Te enviamos un correo con los nuevos datos.'); }
            redirect('citas.php');
        }
        $valores = $d + ['id' => $id ?: ''];
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $editar = cita_propia((int)$_GET['editar'], $uid);
    if ($editar && $editar['estado'] === 'cancelada') $editar = null;
}
$form = $valores ?? $editar ?? ['paciente' => $u['nombre'], 'telefono' => $u['telefono'], 'correo' => $u['correo']];
$editando = !empty($form['id']);

$st = pdo()->prepare('SELECT * FROM citas WHERE usuario_id = ? ORDER BY fecha DESC, hora');
$st->execute([$uid]);
$citas = $st->fetchAll();

layout_ini('Mis citas');
?>
<h1><?= $editando ? 'Modificar cita' : 'Agendar cita médica' ?></h1>
<?php foreach ($errores as $er): ?><div class="mensaje error"><?= e($er) ?></div><?php endforeach; ?>
<?php form_cita($form, 'citas.php', $editando); ?>

<h2>Mis citas</h2>
<table>
    <tr><th>Fecha</th><th>Hora</th><th>Teléfono</th><th>Correo</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr>
    <?php foreach ($citas as $c): ?>
    <tr>
        <td><?= e($c['fecha']) ?></td><td><?= e($c['hora']) ?></td><td><?= e($c['telefono']) ?></td>
        <td><?= e($c['correo']) ?></td><td><?= e($c['problema']) ?></td>
        <td class="estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></td>
        <td>
            <?php if ($c['estado'] === 'confirmada'): ?>
                <a class="btn btn-editar" href="?editar=<?= $c['id'] ?>">Modificar</a>
                <form class="inline" method="POST" onsubmit="return confirm('¿Cancelar esta cita?');">
                    <?= csrf_field() ?><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button class="btn btn-cancelar" type="submit">Cancelar</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$citas): ?><tr><td colspan="7" class="centro">Aún no tienes citas.</td></tr><?php endif; ?>
</table>
<?php layout_fin();
