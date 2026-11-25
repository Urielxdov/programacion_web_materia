<?php
// Parametros de conexion (XAMPP: usuario root sin contraseña).
// En esta PC el MariaDB de XAMPP escucha en el puerto 3307; en un XAMPP por defecto es 3306.
$host   = getenv('CITAS_DB_HOST') ?: '127.0.0.1';
$port   = getenv('CITAS_DB_PORT') ?: '3307';
$dbname = 'sistema_citas';
$user   = getenv('CITAS_DB_USER') ?: 'root';
$pass   = getenv('CITAS_DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $e->getMessage(),
    ]);
    exit;
}
