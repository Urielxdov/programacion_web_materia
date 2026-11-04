<?php
// Crea la base de datos, las tablas y el administrador inicial.
// Uso: php setup.php   (o abrir setup.php en el navegador)
$cfg = require __DIR__ . '/config.php';
$d = $cfg['db'];
$pdo = new PDO("mysql:host={$d['host']};port={$d['port']}", $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = str_replace('clinica_final', $d['name'], file_get_contents(__DIR__ . '/schema.sql'));
foreach (array_filter(array_map('trim', explode(';', $sql))) as $s) $pdo->exec($s);

$a = $cfg['admin'];
$st = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ?');
$st->execute([$a['correo']]);
if (!$st->fetch()) {
    $pdo->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES (?,?,?,?, 'admin')")
        ->execute([$a['nombre'], $a['correo'], '0000000000', password_hash($a['pass'], PASSWORD_DEFAULT)]);
    $msg = "Administrador creado: {$a['correo']} / {$a['pass']}";
} else $msg = 'El administrador ya existía.';
header('Content-Type: text/plain; charset=utf-8');
echo "Base de datos '{$d['name']}' lista.\n$msg\n";
