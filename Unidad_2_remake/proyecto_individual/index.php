<?php
session_start();
require 'conexion.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$tipo = '';

// ---- Registro de usuario ----
if (isset($_POST['registro'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($password) < 4) {
        $mensaje = 'Escribe un usuario y una contraseña de al menos 4 caracteres.';
        $tipo = 'error';
    } else {
        // El primer usuario registrado es admin, los demas asistentes
        $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        $rol = $total === 0 ? 'admin' : 'asistente';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO usuarios (username, password, rol) VALUES (?, ?, ?)');
            $stmt->execute([$username, $hash, $rol]);
            $mensaje = "Usuario registrado correctamente con rol: $rol. Ya puedes iniciar sesión.";
            $tipo = 'exito';
        } catch (PDOException $e) {
            $mensaje = 'Ese nombre de usuario ya existe.';
            $tipo = 'error';
        }
    }
}

// ---- Inicio de sesion ----
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = ?');
    $stmt->execute([$username]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['username']   = $usuario['username'];
        $_SESSION['rol']        = $usuario['rol'];
        header('Location: dashboard.php');
        exit;
    }
    $mensaje = 'Usuario o contraseña incorrectos.';
    $tipo = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Invitados - Acceso</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Sistema de Invitados</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="forms-acceso">
            <form method="POST" class="card">
                <h2>Iniciar sesión</h2>
                <label>Usuario
                    <input type="text" name="username" required>
                </label>
                <label>Contraseña
                    <input type="password" name="password" required>
                </label>
                <button type="submit" name="login" class="btn">Entrar</button>
            </form>

            <form method="POST" class="card">
                <h2>Registrarse</h2>
                <label>Usuario
                    <input type="text" name="username" maxlength="50" required>
                </label>
                <label>Contraseña
                    <input type="password" name="password" minlength="4" required>
                </label>
                <button type="submit" name="registro" class="btn">Crear cuenta</button>
                <p class="nota">El primer usuario registrado será administrador.</p>
            </form>
        </div>
    </div>
</body>
</html>
