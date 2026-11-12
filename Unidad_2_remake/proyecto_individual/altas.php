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
$inv = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Invitados</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Registro de Invitado</h1>
        <form id="formAltas" method="POST" action="acciones.php">
            <input type="hidden" name="accion" value="alta">
            <?php include 'form_campos.php'; ?>
            <div class="botones">
                <button type="submit" class="btn">Guardar Registro</button>
                <a href="dashboard.php" class="btn btn-secundario">Volver al panel</a>
            </div>
        </form>
    </div>
    <script src="app.js"></script>
</body>
</html>
