<?php
session_start();
$CFG = require __DIR__ . '/config.php';

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

function usuario(): ?array { return $_SESSION['usuario'] ?? null; }
function require_login(): array { return usuario() ?? redirect('login.php'); }
function require_admin(): array {
    $u = require_login();
    if ($u['rol'] !== 'admin') redirect('citas.php');
    return $u;
}

function validar_cita(array $in, bool $esAdmin = false, ?int $excluirId = null): array {
    global $CFG;
    $d = [
        'paciente' => trim($in['paciente'] ?? ''),
        'telefono' => trim($in['telefono'] ?? ''),
        'correo' => trim($in['correo'] ?? ''),
        'fecha' => trim($in['fecha'] ?? ''),
        'hora' => trim($in['hora'] ?? ''),
        'problema' => trim($in['problema'] ?? ''),
    ];
    $err = [];
    if ($d['paciente'] === '') $err[] = 'El nombre es obligatorio.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $d['telefono'])) $err[] = 'Ingresa un número de teléfono válido.';
    if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) $err[] = 'Ingresa un correo válido.';
    $f = DateTime::createFromFormat('Y-m-d', $d['fecha']);
    if (!$f || $f->format('Y-m-d') !== $d['fecha']) $err[] = 'Fecha inválida.';
    elseif ($d['fecha'] < date('Y-m-d') && !$esAdmin) $err[] = 'La fecha no puede ser anterior a hoy.';
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['hora'])) $err[] = 'Horario inválido.';
    else {
        $a = $CFG['agenda'];
        if ($d['hora'] < $a['inicio'] || $d['hora'] > $a['ultima']) $err[] = "El horario de atención es de {$a['inicio']} a {$a['ultima']} (inicio de cita).";
    }
    if (!$err) {
        $libre = primer_horario_libre($d['fecha'], $d['hora'], $excluirId);
        if ($libre !== $d['hora']) {
            $dur = $CFG['agenda']['duracion'];
            $err[] = $libre === null
                ? "El horario de las {$d['hora']} se encuentra reservado (las citas duran $dur minutos) y ya no hay horarios disponibles ese día."
                : "El horario de las {$d['hora']} se encuentra reservado (las citas duran $dur minutos), pero puede reservar a partir de las $libre.";
        }
    }
    return [$d, $err];
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

function crear_cita(array $d, ?int $usuarioId): int {
    pdo()->prepare('INSERT INTO citas (usuario_id, paciente, telefono, correo, fecha, hora, problema) VALUES (?,?,?,?,?,?,?)')
        ->execute([$usuarioId, $d['paciente'], $d['telefono'], $d['correo'], $d['fecha'], $d['hora'], $d['problema']]);
    $id = (int)pdo()->lastInsertId();
    correo_cita(obtener_cita($id), 'confirmada');
    return $id;
}

function modificar_cita(int $id, array $d): void {
    pdo()->prepare('UPDATE citas SET paciente=?, telefono=?, correo=?, fecha=?, hora=?, problema=? WHERE id=?')
        ->execute([$d['paciente'], $d['telefono'], $d['correo'], $d['fecha'], $d['hora'], $d['problema'], $id]);
    correo_cita(obtener_cita($id), 'modificada');
}

function cancelar_cita(int $id): void {
    pdo()->prepare("UPDATE citas SET estado='cancelada' WHERE id=?")->execute([$id]);
    correo_cita(obtener_cita($id), 'cancelada');
}

function obtener_cita(int $id): ?array {
    $st = pdo()->prepare('SELECT * FROM citas WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function correo_cita(array $c, string $accion): void {
    $titulos = ['confirmada' => 'Cita confirmada', 'modificada' => 'Cita modificada', 'cancelada' => 'Cita cancelada'];
    $intro = [
        'confirmada' => 'Tu cita ha sido confirmada.',
        'modificada' => 'Tu cita ha sido modificada. Estos son los nuevos datos.',
        'cancelada' => 'Tu cita ha sido cancelada.',
    ];
    $cuerpo = "Hola {$c['paciente']},\n\n{$intro[$accion]}\n\n"
        . "Folio:    #{$c['id']}\nFecha:    {$c['fecha']}\nHora:     {$c['hora']}\n"
        . "Teléfono: {$c['telefono']}\nMotivo:   " . ($c['problema'] ?: '—') . "\n\nClínica Médica";
    $ok = enviar_correo($c['correo'], $titulos[$accion] . " #{$c['id']}", $cuerpo);
    if (!$ok) flash('La cita se guardó, pero no se pudo enviar el correo (revisa storage/correos.log o la config SMTP).', 'error');
}

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

function layout_ini(string $titulo): void {
    $u = usuario();
    $f = flash();
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . e($titulo) . '</title><link rel="stylesheet" href="style.css"></head><body><div class="contenedor">';
    if ($u) {
        echo '<div class="barra"><span>' . e($u['nombre']) . ' <small>(' . e($u['rol']) . ')</small></span><span>';
        if ($u['rol'] === 'admin') echo '<a href="admin.php">Panel admin</a> · ';
        echo '<a href="citas.php">Mis citas</a> · <a href="logout.php">Salir</a></span></div>';
    }
    if ($f) echo '<div class="mensaje ' . e($f[1]) . '">' . e($f[0]) . '</div>';
}
function layout_fin(): void { echo '</div></body></html>'; }
