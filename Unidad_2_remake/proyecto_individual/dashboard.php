<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
require 'conexion.php';

$username = $_SESSION['username'];
$rol      = $_SESSION['rol'];

// ---- Filtros de busqueda y orden ----
$busqueda = trim($_GET['busqueda'] ?? '');
$filtro   = $_GET['filtro'] ?? 'reciente';

$query = "SELECT * FROM invitados";
$params = [];
if ($busqueda !== '') {
    $query .= " WHERE nombres LIKE ? OR apellidos LIKE ?";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
switch ($filtro) {
    case 'alfabetico': $query .= " ORDER BY nombres ASC, apellidos ASC"; break;
    case 'antiguo':    $query .= " ORDER BY id_invitado ASC"; break;
    default:           $query .= " ORDER BY id_invitado DESC"; break;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invitados = $stmt->fetchAll();

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Invitados</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container ancho">
        <div class="barra-usuario">
            Bienvenido, <strong><?= e($username) ?></strong> (Rol: <?= e($rol) ?>) |
            <a href="logout.php">Cerrar Sesión</a>
        </div>

        <div class="toolbar">
            <?php if ($rol === 'admin'): ?>
                <a href="altas.php" class="btn">+ Nuevo Invitado</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <form method="GET" class="filtros">
                <input type="text" name="busqueda" placeholder="Buscar nombre..." value="<?= e($busqueda) ?>">
                <select name="filtro">
                    <option value="reciente"   <?= $filtro === 'reciente' ? 'selected' : '' ?>>Más recientes</option>
                    <option value="antiguo"    <?= $filtro === 'antiguo' ? 'selected' : '' ?>>Más antiguos</option>
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
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Llegada</th>
                        <th>Salida</th>
                        <th>No. Inv</th>
                        <th>Habitación</th>
                        <th>Transporte</th>
                        <th>Dieta</th>
                        <?php if ($rol === 'admin'): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$invitados): ?>
                    <tr><td colspan="12" class="vacio">No hay invitados registrados aún</td></tr>
                <?php endif; ?>
                <?php foreach ($invitados as $invitado): ?>
                    <tr>
                        <td><?= e($invitado['id_invitado']) ?></td>
                        <td><?= e($invitado['nombres']) ?></td>
                        <td><?= e($invitado['apellidos']) ?></td>
                        <td><?= e($invitado['correo']) ?></td>
                        <td><?= e($invitado['telefono']) ?></td>
                        <td><?= e($invitado['fecha_llegada']) ?></td>
                        <td><?= e($invitado['fecha_salida']) ?></td>
                        <td><?= e($invitado['num_invitados']) ?></td>
                        <td><?= e($invitado['tipo_habitacion']) ?></td>
                        <td><?= e($invitado['transporte']) ?></td>
                        <td><?= e($invitado['dieta']) ?></td>
                        <?php if ($rol === 'admin'): ?>
                        <td class="acciones">
                            <button onclick="editarInvitado(<?= (int) $invitado['id_invitado'] ?>)" class="btn btn-editar">Editar</button>
                            <button onclick="eliminarInvitado(<?= (int) $invitado['id_invitado'] ?>)" class="btn btn-eliminar">Eliminar</button>
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
