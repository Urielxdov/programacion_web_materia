<?php
session_start();
require 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

function responder($status, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(['status' => $status, 'mensaje' => $mensaje]);
    exit;
}

// Solo usuarios con sesion y rol admin pueden modificar datos
if (!isset($_SESSION['id_usuario'])) {
    responder('error', 'Sesión no válida. Inicia sesión de nuevo.', 401);
}
if ($_SESSION['rol'] !== 'admin') {
    responder('error', 'No tienes permisos para realizar esta acción.', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['accion'])) {
    responder('error', 'Solicitud no válida.', 400);
}

// Recoge y valida los campos del formulario de invitado
function datosInvitado() {
    $d = [];
    foreach (['nombres', 'apellidos', 'correo', 'telefono', 'direccion', 'fecha_llegada', 'fecha_salida'] as $c) {
        $d[$c] = trim($_POST[$c] ?? '');
        if ($d[$c] === '') {
            responder('error', "El campo $c es obligatorio.", 422);
        }
    }
    if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
        responder('error', 'El correo no es válido.', 422);
    }
    $llegada = DateTime::createFromFormat('Y-m-d', $d['fecha_llegada']);
    $salida  = DateTime::createFromFormat('Y-m-d', $d['fecha_salida']);
    if (!$llegada || !$salida) {
        responder('error', 'Las fechas no son válidas.', 422);
    }
    if ($salida < $llegada) {
        responder('error', 'La fecha de salida no puede ser anterior a la de llegada.', 422);
    }
    $d['num_invitados'] = (int) ($_POST['num_invitados'] ?? 0);
    if ($d['num_invitados'] < 1) {
        responder('error', 'El número de invitados debe ser al menos 1.', 422);
    }
    $d['transporte']      = ($_POST['transporte'] ?? '') === 'SI' ? 'SI' : 'NO';
    $d['dieta']           = ($_POST['dieta'] ?? '') === 'SI' ? 'SI' : 'NO';
    $d['tipo_habitacion'] = in_array($_POST['tipo_habitacion'] ?? '', ['Individual', 'Doble', 'Suite'], true)
        ? $_POST['tipo_habitacion'] : 'Individual';
    return $d;
}

try {
    switch ($_POST['accion']) {
        case 'alta':
            $d = datosInvitado();
            $stmt = $pdo->prepare(
                'INSERT INTO invitados (nombres, apellidos, correo, telefono, direccion, fecha_llegada, fecha_salida,
                 num_invitados, transporte, tipo_habitacion, dieta)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$d['nombres'], $d['apellidos'], $d['correo'], $d['telefono'], $d['direccion'],
                $d['fecha_llegada'], $d['fecha_salida'], $d['num_invitados'], $d['transporte'],
                $d['tipo_habitacion'], $d['dieta']]);
            responder('success', 'Invitado registrado correctamente.');

        case 'eliminar':
            $id = (int) ($_POST['id_invitado'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM invitados WHERE id_invitado = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                responder('error', 'El invitado no existe o ya fue eliminado.', 404);
            }
            responder('success', 'Invitado eliminado correctamente.');

        case 'editar':
            $id = (int) ($_POST['id_invitado'] ?? 0);
            $existe = $pdo->prepare('SELECT COUNT(*) FROM invitados WHERE id_invitado = ?');
            $existe->execute([$id]);
            if ((int) $existe->fetchColumn() === 0) {
                responder('error', 'El invitado no existe.', 404);
            }
            $d = datosInvitado();
            $stmt = $pdo->prepare(
                'UPDATE invitados SET nombres = ?, apellidos = ?, correo = ?, telefono = ?, direccion = ?,
                 fecha_llegada = ?, fecha_salida = ?, num_invitados = ?, transporte = ?, tipo_habitacion = ?, dieta = ?
                 WHERE id_invitado = ?'
            );
            $stmt->execute([$d['nombres'], $d['apellidos'], $d['correo'], $d['telefono'], $d['direccion'],
                $d['fecha_llegada'], $d['fecha_salida'], $d['num_invitados'], $d['transporte'],
                $d['tipo_habitacion'], $d['dieta'], $id]);
            responder('success', 'Invitado actualizado correctamente.');

        default:
            responder('error', 'Acción no reconocida.', 400);
    }
} catch (PDOException $e) {
    responder('error', 'Error en la base de datos: ' . $e->getMessage(), 500);
}
