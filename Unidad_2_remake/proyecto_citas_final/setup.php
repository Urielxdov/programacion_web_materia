<?php
// Crea la base de datos, las tablas y los usuarios iniciales (admin y asistente).
// Uso: php setup.php   (o abrir setup.php en el navegador)
$cfg = require __DIR__ . '/config.php';
$d = $cfg['db'];
$pdo = new PDO("mysql:host={$d['host']};port={$d['port']}", $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = str_replace('clinica_citas', $d['name'], file_get_contents(__DIR__ . '/schema.sql'));
$sql = preg_replace('/^\s*--.*$/m', '', $sql); // quitar comentarios
foreach (array_filter(array_map('trim', explode(';', $sql))) as $s) $pdo->exec($s);

$msg = '';
foreach (['admin', 'asistente'] as $rol) {
    $a = $cfg[$rol];
    $st = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ?');
    $st->execute([$a['correo']]);
    if (!$st->fetch()) {
        $pdo->prepare('INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (?,?,?,?)')
            ->execute([$a['nombre'], $a['correo'], password_hash($a['pass'], PASSWORD_DEFAULT), $rol]);
        $msg .= "Usuario $rol creado: {$a['correo']} / {$a['pass']}\n";
    } else $msg .= "El usuario $rol ({$a['correo']}) ya existía.\n";
}
header('Content-Type: text/plain; charset=utf-8');
echo "Base de datos '{$d['name']}' lista.\n$msg";
