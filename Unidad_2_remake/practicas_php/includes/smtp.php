<?php
declare(strict_types=1);

/** Configuración SMTP tomada de las variables de entorno UNIDAD2_SMTP_*. */
function smtp_config(): array
{
    return [
        'host' => getenv('UNIDAD2_SMTP_HOST'), 'port' => getenv('UNIDAD2_SMTP_PORT') ?: 587,
        'secure' => getenv('UNIDAD2_SMTP_SECURE') ?: 'tls',
        'user' => getenv('UNIDAD2_SMTP_USER'), 'pass' => getenv('UNIDAD2_SMTP_PASS'),
    ];
}

/** True si UNIDAD2_MAIL_MODE=smtp y el remitente configurado es válido. */
function smtp_enabled(): bool
{
    $from = getenv('UNIDAD2_MAIL_FROM');
    return getenv('UNIDAD2_MAIL_MODE') === 'smtp' && $from && filter_var($from, FILTER_VALIDATE_EMAIL);
}

/**
 * Cliente SMTP mínimo (sin Composer). Soporta SSL implícito (465), STARTTLS (587) y AUTH LOGIN/PLAIN.
 * Devuelve null si el servidor aceptó el mensaje, o un texto con el error.
 *
 * $cfg: host, port, secure ('ssl' | 'tls' | 'none'), user, pass, timeout
 */
function smtp_send(array $cfg, string $to, string $from, string $replyTo, string $subject, string $body): ?string
{
    $host = (string)($cfg['host'] ?? ''); $port = (int)($cfg['port'] ?? 587);
    $secure = strtolower((string)($cfg['secure'] ?? 'tls')); $timeout = (int)($cfg['timeout'] ?? 15);
    if ($host === '') return 'Falta UNIDAD2_SMTP_HOST.';

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://').$host.':'.$port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return "No se pudo conectar a $host:$port ($errstr).";
    stream_set_timeout($fp, $timeout);

    // Lee una respuesta (posiblemente de varias líneas) y devuelve [código, texto].
    $read = function () use ($fp): array {
        $text = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $text .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') break;
        }
        return [(int)substr($text, 0, 3), trim($text)];
    };
    $cmd = function (string $line, array $ok) use ($fp, $read): ?string {
        fwrite($fp, $line."\r\n");
        [$code, $text] = $read();
        return in_array($code, $ok, true) ? null : $text;
    };
    $fail = function (string $msg) use ($fp): string { @fwrite($fp, "QUIT\r\n"); @fclose($fp); return $msg; };

    [$code, $text] = $read();
    if ($code !== 220) return $fail("Saludo inesperado del servidor: $text");

    $ehlo = 'EHLO '.(gethostname() ?: 'localhost');
    if ($err = $cmd($ehlo, [250])) return $fail("EHLO rechazado: $err");

    if ($secure === 'tls') {
        if ($err = $cmd('STARTTLS', [220])) return $fail("STARTTLS rechazado: $err");
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $fail('No se pudo establecer la conexión TLS.');
        if ($err = $cmd($ehlo, [250])) return $fail("EHLO tras STARTTLS rechazado: $err");
    }

    $user = (string)($cfg['user'] ?? ''); $pass = (string)($cfg['pass'] ?? '');
    if ($user !== '') {
        if ($err = $cmd('AUTH LOGIN', [334])) return $fail("AUTH LOGIN no disponible: $err");
        if ($err = $cmd(base64_encode($user), [334])) return $fail("Usuario rechazado: $err");
        if ($err = $cmd(base64_encode($pass), [235])) return $fail("Autenticación fallida: $err");
    }

    foreach (['MAIL FROM:<'.$from.'>', 'RCPT TO:<'.$to.'>'] as $line) {
        if ($err = $cmd($line, [250, 251])) return $fail("El servidor rechazó el remitente o destinatario: $err");
    }
    if ($err = $cmd('DATA', [354])) return $fail("DATA rechazado: $err");

    $headers = [
        'Date: '.date('r'),
        'From: '.$from,
        'To: '.$to,
        'Reply-To: '.$replyTo,
        'Subject: =?UTF-8?B?'.base64_encode($subject).'?=',
        'Message-ID: <'.bin2hex(random_bytes(12)).'@'.(preg_replace('/^.*@/', '', $from) ?: 'localhost').'>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    $message = implode("\r\n", $headers)."\r\n\r\n".chunk_split(base64_encode($body), 76, "\r\n");
    // La carga es base64, así que ninguna línea comienza con "." y no se necesita dot-stuffing.
    fwrite($fp, $message."\r\n.\r\n");
    [$code, $text] = $read();
    if ($code !== 250) return $fail("El servidor no aceptó el mensaje: $text");

    @fwrite($fp, "QUIT\r\n"); @fclose($fp);
    return null;
}
