<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
// Administrador y asistente pueden dar de alta citas
$inv = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de cita</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Formulario de cita</h1>
        <p class="intro">Si desea concertar una cita de consulta con el Dr. Xavier, complete este formulario de cita y nos pondremos en contacto con usted lo antes posible.</p>
        <form id="formAltas" method="POST" action="acciones.php">
            <input type="hidden" name="accion" value="alta">
            <?php include 'form_campos.php'; ?>
            <div class="botones">
                <button type="submit" class="btn">Enviar</button>
                <a href="dashboard.php" class="btn btn-secundario">Volver al panel</a>
            </div>
        </form>
    </div>
    <script src="app.js"></script>
</body>
</html>
