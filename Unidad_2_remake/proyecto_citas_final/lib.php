<?php
session_start();
$CFG = require __DIR__ . '/config.php';

const PAISES = ['México', 'Estados Unidos', 'Canadá', 'Guatemala', 'Colombia', 'Argentina', 'España', 'Otro'];
const ROLES = ['admin' => 'Administrador', 'asistente' => 'Asistente'];

function pdo(): PDO {
    global $CFG;
    static $pdo;
    if (!$pdo) {
        $d = $CFG['db'];
        try {
            $pdo = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        } catch (PDOException $e) {
            die('Error de conexión: ' . htmlspecialchars($e->getMessage()) . '. ¿Ejecutaste setup.php?');
        }
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $to): never { header("Location: $to"); exit; }

function flash(?string $msg = null, string $tipo = 'exito'): ?array {
    if ($msg !== null) {
        // un error previo (p. ej. fallo de correo) no se pisa con el mensaje de éxito
        if (($_SESSION['flash'][1] ?? '') !== 'error') $_SESSION['flash'] = [$msg, $tipo];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); die('Token inválido.'); }
}

// ---------- Sesión y permisos ----------
function usuario(): ?array { return $_SESSION['usuario'] ?? null; }
function es_admin(): bool { return (usuario()['rol'] ?? '') === 'admin'; }
function require_login(): array {
    $u = usuario() ?? redirect('login.php');
    // Releer de la BD para que un cambio de rol o una baja surta efecto de inmediato
    $st = pdo()->prepare('SELECT id, nombre, correo, rol FROM usuarios WHERE id = ?');
    $st->execute([$u['id']]);
    if (!$fresco = $st->fetch()) { $_SESSION = []; redirect('login.php'); }
    return $_SESSION['usuario'] = $fresco;
}
function require_admin(): array {
    $u = require_login();
    if ($u['rol'] !== 'admin') {
        flash('No tienes permiso para realizar esa acción (solo administrador).', 'error');
        redirect('citas.php');
    }
    return $u;
}

// ---------- Citas ----------
const CAMPOS_CITA = ['fecha', 'hora', 'nombre', 'apellido', 'direccion', 'direccion2', 'ciudad', 'region',
    'codigo_postal', 'pais', 'fecha_nacimiento', 'email', 'curp', 'telefono'];

function fecha_valida(string $f): bool {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

function validar_cita(array $in, bool $esAdmin = false, ?int $excluirId = null): array {
    global $CFG;
    $d = [];
    foreach (CAMPOS_CITA as $k) $d[$k] = trim($in[$k] ?? '');
    $d['curp'] = strtoupper($d['curp']);
    $d['email'] = strtolower($d['email']);
    $d['telefono'] = preg_replace('/[\s\-()]/', '', $d['telefono']);

    $err = [];
    // Día y hora
    if (!fecha_valida($d['fecha'])) $err[] = 'Día de la cita inválido.';
    elseif ($d['fecha'] < date('Y-m-d') && !$esAdmin) $err[] = 'El día de la cita no puede ser anterior a hoy.';
    $horaOk = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['hora']);
    if (!$horaOk) $err[] = 'Hora de la cita inválida.';
    else {
        $a = $CFG['agenda'];
        if ($d['hora'] < $a['inicio'] || $d['hora'] > $a['ultima']) { $err[] = "El horario de atención es de {$a['inicio']} a {$a['ultima']} (inicio de cita)."; $horaOk = false; }
    }
    // Datos personales
    $soloLetras = '/^[\p{L}\s\'.-]{2,60}$/u';
    if (!preg_match($soloLetras, $d['nombre'])) $err[] = 'Nombre inválido (solo letras).';
    if (!preg_match($soloLetras, $d['apellido'])) $err[] = 'Apellido inválido (solo letras).';
    if (mb_strlen($d['direccion']) < 5) $err[] = 'La dirección es obligatoria.';
    if ($d['ciudad'] === '') $err[] = 'La ciudad es obligatoria.';
    if ($d['region'] === '') $err[] = 'La región/estado es obligatoria.';
    if (!in_array($d['pais'], PAISES, true)) $err[] = 'Selecciona un país.';
    if ($d['pais'] === 'México' ? !preg_match('/^\d{5}$/', $d['codigo_postal']) : !preg_match('/^[A-Za-z0-9\s-]{3,10}$/', $d['codigo_postal']))
        $err[] = 'Código postal inválido' . ($d['pais'] === 'México' ? ' (5 dígitos).' : '.');
    if (!fecha_valida($d['fecha_nacimiento']) || $d['fecha_nacimiento'] > date('Y-m-d') || $d['fecha_nacimiento'] < '1900-01-01')
        $err[] = 'Fecha de nacimiento inválida.';
    // Campos agregados: email, CURP y teléfono
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Email inválido.';
    if (!curp_valida($d['curp'])) $err[] = 'CURP inválida (18 caracteres, formato oficial).';
    elseif (fecha_valida($d['fecha_nacimiento']) && substr($d['curp'], 4, 6) !== date('ymd', strtotime($d['fecha_nacimiento'])))
        $err[] = 'La fecha de nacimiento no coincide con la CURP.';
    if (!preg_match('/^\d{10}$/', $d['telefono'])) $err[] = 'El teléfono debe tener 10 dígitos.';

    // Disponibilidad de horario
    if (!$err && $horaOk) {
        $libre = primer_horario_libre($d['fecha'], $d['hora'], $excluirId);
        if ($libre !== $d['hora']) {
            $dur = $CFG['agenda']['duracion'];
            $err[] = $libre === null
                ? "El horario de las {$d['hora']} se encuentra reservado (las citas duran $dur minutos) y ya no hay horarios disponibles ese día."
                : "El horario de las {$d['hora']} se encuentra reservado (las citas duran $dur minutos), pero puede reservar a partir de las $libre.";
        }
    }
    if ($d['direccion2'] === '') $d['direccion2'] = null;
    return [$d, $err];
}

function curp_valida(string $c): bool {
    return (bool)preg_match('/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HMX]'
        . '(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)'
        . '[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/', $c);
}

function a_minutos(string $h): int { [$hh, $mm] = explode(':', $h); return $hh * 60 + $mm; }
function a_hora(int $m): string { return sprintf('%02d:%02d', intdiv($m, 60), $m % 60); }

// Devuelve $hora si no choca con ninguna cita confirmada del día; si choca, la primera hora
// posterior disponible; null si ya no cabe ninguna cita ese día.
function primer_horario_libre(string $fecha, string $hora, ?int $excluirId = null): ?string {
    global $CFG;
    $dur = $CFG['agenda']['duracion'];
    $st = pdo()->prepare("SELECT hora FROM citas WHERE fecha = ? AND estado = 'confirmada' AND id <> ?");
    $st->execute([$fecha, $excluirId ?? 0]);
    $ocupadas = array_map('a_minutos', $st->fetchAll(PDO::FETCH_COLUMN));
    sort($ocupadas);
    $t = a_minutos($hora);
    foreach ($ocupadas as $o) {
        if ($t < $o + $dur && $t + $dur > $o) $t = $o + $dur; // traslape: saltar al final de esa cita
    }
    return $t > a_minutos($CFG['agenda']['ultima']) ? null : a_hora($t);
}

function crear_cita(array $d, int $creadoPor): int {
    $cols = implode(', ', CAMPOS_CITA);
    $ph = implode(', ', array_fill(0, count(CAMPOS_CITA), '?'));
    pdo()->prepare("INSERT INTO citas ($cols, creado_por) VALUES ($ph, ?)")
        ->execute([...array_map(fn($k) => $d[$k], CAMPOS_CITA), $creadoPor]);
    $id = (int)pdo()->lastInsertId();
    correo_cita(obtener_cita($id), 'confirmada');
    return $id;
}

function modificar_cita(int $id, array $d): void {
    $set = implode(', ', array_map(fn($k) => "$k = ?", CAMPOS_CITA));
    pdo()->prepare("UPDATE citas SET $set WHERE id = ?")
        ->execute([...array_map(fn($k) => $d[$k], CAMPOS_CITA), $id]);
    correo_cita(obtener_cita($id), 'modificada');
}

function cancelar_cita(int $id): void {
    pdo()->prepare("UPDATE citas SET estado='cancelada' WHERE id=?")->execute([$id]);
    correo_cita(obtener_cita($id), 'cancelada');
}

function eliminar_cita(int $id): void {
    pdo()->prepare('DELETE FROM citas WHERE id = ?')->execute([$id]);
}

function obtener_cita(int $id): ?array {
    $st = pdo()->prepare('SELECT * FROM citas WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function fecha_mx(?string $f): string { return $f ? date('d/m/Y', strtotime($f)) : ''; }

function correo_cita(array $c, string $accion): void {
    $titulos = ['confirmada' => 'Cita confirmada', 'modificada' => 'Cita modificada', 'cancelada' => 'Cita cancelada'];
    $intro = [
        'confirmada' => 'Su cita con el Dr. Xavier ha sido registrada.',
        'modificada' => 'Su cita con el Dr. Xavier ha sido modificada. Estos son los nuevos datos.',
        'cancelada' => 'Su cita con el Dr. Xavier ha sido cancelada.',
    ];
    $cuerpo = "Hola {$c['nombre']} {$c['apellido']},\n\n{$intro[$accion]}\n\n"
        . "Folio:    #{$c['id']}\nDía:      " . fecha_mx($c['fecha']) . "\nHora:     {$c['hora']}\n"
        . "CURP:     {$c['curp']}\nTeléfono: {$c['telefono']}\n\nConsultorio del Dr. Xavier";
    $ok = enviar_correo($c['email'], $titulos[$accion] . " #{$c['id']}", $cuerpo);
    if (!$ok) flash('La cita se guardó, pero no se pudo enviar el correo (revisa storage/correos.log o la config SMTP).', 'error');
}

// ---------- Correo ----------
function enviar_correo(string $to, string $asunto, string $cuerpo): bool {
    global $CFG;
    $m = $CFG['mail'];
    $log = fn(string $estado) => file_put_contents(__DIR__ . '/storage/correos.log',
        '[' . date('Y-m-d H:i:s') . "] ($estado) Para: $to | Asunto: $asunto\n$cuerpo\n" . str_repeat('-', 60) . "\n", FILE_APPEND);
    if ($m['mode'] === 'archivo') { $log('archivo'); return true; }

    $subj = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
    $from = '=?UTF-8?B?' . base64_encode($m['from_name']) . "?= <{$m['from']}>";
    $headers = "From: $from\r\nTo: $to\r\nSubject: $subj\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
    $body = chunk_split(base64_encode($cuerpo));

    if ($m['mode'] === 'mail') {
        $ok = @mail($to, $subj, $body, "From: $from\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64");
        $log($ok ? 'mail ok' : 'mail error');
        return $ok;
    }
    $err = '';
    $ok = smtp_enviar($m, $to, $headers, $body, $err);
    $log($ok ? 'smtp ok' : "smtp error: $err");
    return $ok;
}

function smtp_enviar(array $m, string $to, string $headers, string $body, string &$err): bool {
    $host = ($m['secure'] === 'ssl' ? 'ssl://' : '') . $m['host'];
    $s = @stream_socket_client("$host:{$m['port']}", $no, $str, 15);
    if (!$s) { $err = "conexión: $str"; return false; }
    $leer = function () use ($s) {
        $r = '';
        while (($l = fgets($s, 515)) !== false) { $r .= $l; if (($l[3] ?? ' ') === ' ') break; }
        return $r;
    };
    $cmd = function (string $c, string $esperado) use ($s, $leer, &$err) {
        fwrite($s, $c . "\r\n");
        $r = $leer();
        if (strpos($r, $esperado) !== 0) { $err = trim($r); return false; }
        return true;
    };
    $leer();
    $ehlo = 'EHLO localhost';
    $ok = $cmd($ehlo, '250');
    if ($ok && $m['secure'] === 'tls') {
        $ok = $cmd('STARTTLS', '220') && stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) && $cmd($ehlo, '250');
    }
    if ($ok && $m['user'] !== '') {
        $ok = $cmd('AUTH LOGIN', '334') && $cmd(base64_encode($m['user']), '334') && $cmd(base64_encode($m['pass']), '235');
    }
    $ok = $ok && $cmd("MAIL FROM:<{$m['from']}>", '250') && $cmd("RCPT TO:<$to>", '250') && $cmd('DATA', '354');
    if ($ok) {
        fwrite($s, $headers . "\r\n" . $body . "\r\n.\r\n");
        $r = $leer();
        if (strpos($r, '250') !== 0) { $err = trim($r); $ok = false; }
    }
    @fwrite($s, "QUIT\r\n");
    fclose($s);
    return $ok;
}

// ---------- Plantilla ----------
function layout_ini(string $titulo): void {
    $u = usuario();
    $f = flash();
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . e($titulo) . '</title><link rel="stylesheet" href="style.css"></head><body><div class="contenedor">';
    if ($u) {
        echo '<nav class="barra"><span>' . e($u['nombre']) . ' <small class="rol rol-' . e($u['rol']) . '">' . e(ROLES[$u['rol']]) . '</small></span><span>'
            . '<a href="cita.php">Nueva cita</a> · <a href="citas.php">Consultar citas</a> · ';
        if ($u['rol'] === 'admin') echo '<a href="usuarios.php">Usuarios</a> · ';
        echo '<a href="logout.php">Salir</a></span></nav>';
    }
    if ($f) echo '<div class="mensaje ' . e($f[1]) . '">' . e($f[0]) . '</div>';
}
function layout_fin(): void { echo '</div></body></html>'; }
