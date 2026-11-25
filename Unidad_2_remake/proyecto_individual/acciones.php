<?php
session_start();
require 'conexion.php';
require_once 'catalogos.php';
header('Content-Type: application/json; charset=utf-8');

function responder($status, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(['status' => $status, 'mensaje' => $mensaje]);
    exit;
}

if (!isset($_SESSION['id_usuario'])) {
    responder('error', 'Sesión no válida. Inicia sesión de nuevo.', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['accion'])) {
    responder('error', 'Solicitud no válida.', 400);
}

// El asistente solo puede dar de alta citas (y consultarlas en el panel);
// editar y eliminar son exclusivos del administrador.
$esAdmin = $_SESSION['rol'] === 'admin';
if ($_POST['accion'] !== 'alta' && !$esAdmin) {
    responder('error', 'No tienes permisos para realizar esta acción.', 403);
}

// Recoge y valida los campos del formulario de cita
function datosCita($esAdmin) {
    $d = [];
    foreach (['fecha_cita', 'hora_cita', 'nombre', 'apellido', 'direccion', 'direccion2', 'ciudad', 'region',
              'codigo_postal', 'pais', 'fecha_nacimiento', 'email', 'curp', 'telefono'] as $c) {
        $d[$c] = trim($_POST[$c] ?? '');
    }
    $d['curp']     = strtoupper($d['curp']);
    $d['email']    = strtolower($d['email']);
    $d['telefono'] = preg_replace('/[\s\-()]/', '', $d['telefono']);

    if (!fecha_valida($d['fecha_cita'])) {
        responder('error', 'El día de la cita no es válido.', 422);
    }
    if ($d['fecha_cita'] < date('Y-m-d') && !$esAdmin) {
        responder('error', 'El día de la cita no puede ser anterior a hoy.', 422);
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['hora_cita'])) {
        responder('error', 'La hora de la cita no es válida.', 422);
    }
    $letras = "/^[\p{L}\s'.-]{2,60}$/u";
    if (!preg_match($letras, $d['nombre']))   responder('error', 'Nombre inválido (solo letras).', 422);
    if (!preg_match($letras, $d['apellido'])) responder('error', 'Apellido inválido (solo letras).', 422);
    if (mb_strlen($d['direccion']) < 5)       responder('error', 'La dirección es obligatoria.', 422);
    if ($d['ciudad'] === '')                  responder('error', 'La ciudad es obligatoria.', 422);
    if ($d['region'] === '')                  responder('error', 'La región es obligatoria.', 422);
    if (!in_array($d['pais'], PAISES, true))  responder('error', 'Selecciona un país.', 422);
    $cpValido = $d['pais'] === 'México'
        ? preg_match('/^\d{5}$/', $d['codigo_postal'])
        : preg_match('/^[A-Za-z0-9\s-]{3,10}$/', $d['codigo_postal']);
    if (!$cpValido) {
        responder('error', 'Código postal inválido' . ($d['pais'] === 'México' ? ' (5 dígitos).' : '.'), 422);
    }
    if (!fecha_valida($d['fecha_nacimiento']) || $d['fecha_nacimiento'] > date('Y-m-d') || $d['fecha_nacimiento'] < '1900-01-01') {
        responder('error', 'La fecha de nacimiento no es válida.', 422);
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        responder('error', 'El email no es válido.', 422);
    }
    if (!curp_valida($d['curp'])) {
        responder('error', 'La CURP no es válida (18 caracteres, formato oficial).', 422);
    }
    if (substr($d['curp'], 4, 6) !== date('ymd', strtotime($d['fecha_nacimiento']))) {
        responder('error', 'La fecha de nacimiento no coincide con la CURP.', 422);
    }
    if (!preg_match('/^\d{10}$/', $d['telefono'])) {
        responder('error', 'El teléfono debe tener 10 dígitos.', 422);
    }
    if ($d['direccion2'] === '') {
        $d['direccion2'] = null;
    }
    return $d;
}

try {
    switch ($_POST['accion']) {
        case 'alta':
            $d = datosCita($esAdmin);
            $stmt = $pdo->prepare(
                'INSERT INTO citas (fecha_cita, hora_cita, nombre, apellido, direccion, direccion2, ciudad, region,
                 codigo_postal, pais, fecha_nacimiento, email, curp, telefono, creado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$d['fecha_cita'], $d['hora_cita'], $d['nombre'], $d['apellido'], $d['direccion'],
                $d['direccion2'], $d['ciudad'], $d['region'], $d['codigo_postal'], $d['pais'],
                $d['fecha_nacimiento'], $d['email'], $d['curp'], $d['telefono'], $_SESSION['id_usuario']]);
            responder('success', 'Cita registrada correctamente.');

        case 'eliminar':
            $id = (int) ($_POST['id_cita'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM citas WHERE id_cita = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                responder('error', 'La cita no existe o ya fue eliminada.', 404);
            }
            responder('success', 'Cita eliminada correctamente.');

        case 'editar':
            $id = (int) ($_POST['id_cita'] ?? 0);
            $existe = $pdo->prepare('SELECT COUNT(*) FROM citas WHERE id_cita = ?');
            $existe->execute([$id]);
            if ((int) $existe->fetchColumn() === 0) {
                responder('error', 'La cita no existe.', 404);
            }
            $d = datosCita($esAdmin);
            $stmt = $pdo->prepare(
                'UPDATE citas SET fecha_cita = ?, hora_cita = ?, nombre = ?, apellido = ?, direccion = ?, direccion2 = ?,
                 ciudad = ?, region = ?, codigo_postal = ?, pais = ?, fecha_nacimiento = ?, email = ?, curp = ?, telefono = ?
                 WHERE id_cita = ?'
            );
            $stmt->execute([$d['fecha_cita'], $d['hora_cita'], $d['nombre'], $d['apellido'], $d['direccion'],
                $d['direccion2'], $d['ciudad'], $d['region'], $d['codigo_postal'], $d['pais'],
                $d['fecha_nacimiento'], $d['email'], $d['curp'], $d['telefono'], $id]);
            responder('success', 'Cita actualizada correctamente.');

        default:
            responder('error', 'Acción no reconocida.', 400);
    }
} catch (PDOException $e) {
    // 1062: ya existe una cita en ese dia y hora (clave unica)
    if (($e->errorInfo[1] ?? 0) === 1062) {
        responder('error', 'Ya hay una cita registrada en ese día y hora.', 409);
    }
    responder('error', 'Error en la base de datos: ' . $e->getMessage(), 500);
}
