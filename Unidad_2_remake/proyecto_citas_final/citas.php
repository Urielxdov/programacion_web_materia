<?php
// Consulta de citas (administrador y asistente).
// Modificar, cancelar y eliminar: solo administrador.
require __DIR__ . '/lib.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_admin();
    $id = (int)($_POST['id'] ?? 0);
    $c = $id ? obtener_cita($id) : null;
    $accion = $_POST['accion'] ?? '';
    if ($c && $accion === 'cancelar' && $c['estado'] === 'confirmada') {
        cancelar_cita($id);
        flash("Cita #$id cancelada y notificada por correo.");
    } elseif ($c && $accion === 'eliminar') {
        eliminar_cita($id);
        flash("Cita #$id eliminada definitivamente.");
    }
    redirect('citas.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'fecha', 'estado']))));
}

// Filtros de búsqueda
$q = trim($_GET['q'] ?? '');
$fecha = $_GET['fecha'] ?? '';
$estado = $_GET['estado'] ?? '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.curp LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)";
    array_push($params, ...array_fill(0, 4, "%$q%"));
}
if (fecha_valida($fecha)) { $where[] = 'c.fecha = ?'; $params[] = $fecha; }
if (in_array($estado, ['confirmada', 'cancelada'], true)) { $where[] = 'c.estado = ?'; $params[] = $estado; }

$sql = 'SELECT c.*, u.nombre AS registro FROM citas c LEFT JOIN usuarios u ON u.id = c.creado_por'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY c.fecha DESC, c.hora';
$st = pdo()->prepare($sql);
$st->execute($params);
$citas = $st->fetchAll();
$admin = es_admin();
$qs = e(http_build_query(['q' => $q, 'fecha' => $fecha, 'estado' => $estado]));

layout_ini('Consultar citas');
?>
<h1>Consultar citas</h1>

<form method="GET" class="filtros">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, CURP, email o teléfono" aria-label="Buscar">
    <input type="date" name="fecha" value="<?= e($fecha) ?>" aria-label="Día de la cita">
    <select name="estado" aria-label="Estado">
        <option value="">Todos los estados</option>
        <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
    </select>
    <button type="submit">Buscar</button>
    <a href="citas.php">Limpiar</a>
</form>

<p class="total"><?= count($citas) ?> cita(s) encontrada(s)</p>
<div class="tabla-scroll">
<table>
    <tr>
        <th>#</th><th>Día</th><th>Hora</th><th>Paciente</th><th>F. nacimiento</th><th>CURP</th><th>Email</th><th>Teléfono</th>
        <th>Dirección</th><th>Estado</th><th>Registró</th><?php if ($admin): ?><th>Acciones</th><?php endif; ?>
    </tr>
    <?php foreach ($citas as $c): ?>
    <tr>
        <td><?= $c['id'] ?></td>
        <td><?= fecha_mx($c['fecha']) ?></td>
        <td><?= e($c['hora']) ?></td>
        <td><?= e($c['nombre'] . ' ' . $c['apellido']) ?></td>
        <td><?= fecha_mx($c['fecha_nacimiento']) ?></td>
        <td class="mono"><?= e($c['curp']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td><?= e($c['telefono']) ?></td>
        <td><?= e(implode(', ', array_filter([$c['direccion'], $c['direccion2'], $c['ciudad'], $c['region'], 'C.P. ' . $c['codigo_postal'], $c['pais']]))) ?></td>
        <td class="estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></td>
        <td><?= e($c['registro'] ?? '—') ?></td>
        <?php if ($admin): ?>
        <td class="acciones">
            <?php if ($c['estado'] === 'confirmada'): ?>
                <a class="btn btn-editar" href="cita.php?id=<?= $c['id'] ?>">Modificar</a>
                <form class="inline" method="POST" action="citas.php?<?= $qs ?>" onsubmit="return confirm('¿Cancelar la cita #<?= $c['id'] ?>?');">
                    <?= csrf_field() ?><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button class="btn btn-cancelar" type="submit">Cancelar</button>
                </form>
            <?php endif; ?>
            <form class="inline" method="POST" action="citas.php?<?= $qs ?>" onsubmit="return confirm('¿Eliminar definitivamente la cita #<?= $c['id'] ?>?');">
                <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="btn btn-eliminar" type="submit">Eliminar</button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$citas): ?><tr><td colspan="<?= $admin ? 12 : 11 ?>" class="centro">No hay citas que coincidan.</td></tr><?php endif; ?>
</table>
</div>
<?php layout_fin();
