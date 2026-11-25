<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
require 'conexion.php';

$username = $_SESSION['username'];
$rol      = $_SESSION['rol'];

// ---- Filtros de busqueda, dia y orden ----
$busqueda = trim($_GET['busqueda'] ?? '');
$dia      = $_GET['dia'] ?? '';
$filtro   = $_GET['filtro'] ?? 'proximas';

$query  = "SELECT c.*, u.username AS registro FROM citas c LEFT JOIN usuarios u ON u.id_usuario = c.creado_por";
$where  = [];
$params = [];
if ($busqueda !== '') {
    $where[] = "(CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.curp LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)";
    array_push($params, ...array_fill(0, 4, "%$busqueda%"));
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
    $where[] = "c.fecha_cita = ?";
    $params[] = $dia;
} else {
    $dia = '';
}
if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}
switch ($filtro) {
    case 'alfabetico': $query .= " ORDER BY c.apellido ASC, c.nombre ASC"; break;
    case 'reciente':   $query .= " ORDER BY c.id_cita DESC"; break;
    default:           $query .= " ORDER BY c.fecha_cita ASC, c.hora_cita ASC"; break;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$citas = $stmt->fetchAll();

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function fecha_mx($f) { return $f ? date('d/m/Y', strtotime($f)) : ''; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Citas</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container ancho">
        <div class="barra-usuario">
            Bienvenido, <strong><?= e($username) ?></strong> (Rol: <?= e($rol) ?>) |
            <a href="logout.php">Cerrar Sesión</a>
        </div>

        <div class="toolbar">
            <a href="altas.php" class="btn">+ Nueva cita</a>

            <form method="GET" class="filtros">
                <input type="text" name="busqueda" placeholder="Nombre, CURP, email o teléfono" value="<?= e($busqueda) ?>">
                <input type="date" name="dia" value="<?= e($dia) ?>" title="Día de la cita">
                <select name="filtro">
                    <option value="proximas"   <?= $filtro === 'proximas' ? 'selected' : '' ?>>Próximas primero</option>
                    <option value="reciente"   <?= $filtro === 'reciente' ? 'selected' : '' ?>>Más recientes</option>
                    <option value="alfabetico" <?= $filtro === 'alfabetico' ? 'selected' : '' ?>>Alfabéticamente</option>
                </select>
                <button type="submit" class="btn">Filtrar</button>
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Día</th>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>F. nacimiento</th>
                        <th>CURP</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Registró</th>
                        <?php if ($rol === 'admin'): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$citas): ?>
                    <tr><td colspan="11" class="vacio">No hay citas registradas aún</td></tr>
                <?php endif; ?>
                <?php foreach ($citas as $cita): ?>
                    <tr>
                        <td><?= e($cita['id_cita']) ?></td>
                        <td><?= e(fecha_mx($cita['fecha_cita'])) ?></td>
                        <td><?= e($cita['hora_cita']) ?></td>
                        <td><?= e($cita['nombre'] . ' ' . $cita['apellido']) ?></td>
                        <td><?= e(fecha_mx($cita['fecha_nacimiento'])) ?></td>
                        <td class="mono"><?= e($cita['curp']) ?></td>
                        <td><?= e($cita['email']) ?></td>
                        <td><?= e($cita['telefono']) ?></td>
                        <td><?= e(implode(', ', array_filter([$cita['direccion'], $cita['direccion2'], $cita['ciudad'], $cita['region'], 'C.P. ' . $cita['codigo_postal'], $cita['pais']]))) ?></td>
                        <td><?= e($cita['registro'] ?? '—') ?></td>
                        <?php if ($rol === 'admin'): ?>
                        <td class="acciones">
                            <button onclick="editarCita(<?= (int) $cita['id_cita'] ?>)" class="btn btn-editar">Editar</button>
                            <button onclick="eliminarCita(<?= (int) $cita['id_cita'] ?>)" class="btn btn-eliminar">Eliminar</button>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
