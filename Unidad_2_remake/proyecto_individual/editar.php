<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
require 'conexion.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM invitados WHERE id_invitado = ?');
$stmt->execute([$id]);
$inv = $stmt->fetch();

if (!$inv) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Invitado</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Editar Invitado #<?= (int) $inv['id_invitado'] ?></h1>
        <form id="formEditar" method="POST" action="acciones.php">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_invitado" value="<?= (int) $inv['id_invitado'] ?>">
            <?php include 'form_campos.php'; ?>
            <div class="botones">
                <button type="submit" class="btn">Guardar Cambios</button>
                <a href="dashboard.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    </div>
    <script src="app.js"></script>
</body>
</html>
