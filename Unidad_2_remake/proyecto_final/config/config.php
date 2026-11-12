<?php
declare(strict_types=1);

use App\Core\Env;

/*
 * Configuracion por variables de entorno (.env en local, panel de Render en produccion).
 * DATABASE_URL (formato de Neon/Render: postgres://usuario:clave@host:puerto/bd?sslmode=require)
 * tiene prioridad sobre las variables DB_* sueltas.
 */

$db = [
    'driver'   => Env::obtener('DB_DRIVER', 'mysql'),
    'host'     => Env::obtener('DB_HOST', '127.0.0.1'),
    'puerto'   => Env::obtener('DB_PORT', '3307'),
    'nombre'   => Env::obtener('DB_NAME', 'ewaste360'),
    'usuario'  => Env::obtener('DB_USER', 'root'),
    'clave'    => Env::obtener('DB_PASSWORD', ''),
    'sslmode'  => Env::obtener('DB_SSLMODE', 'prefer'),
];

$url = Env::obtener('DATABASE_URL');
if ($url !== null) {
    $partes = parse_url($url);
    if ($partes === false || empty($partes['host'])) {
        throw new RuntimeException('DATABASE_URL no tiene un formato valido.');
    }
    parse_str($partes['query'] ?? '', $query);
    $esquema = $partes['scheme'] ?? 'postgres';
    $db = [
        'driver'  => in_array($esquema, ['postgres', 'postgresql', 'pgsql'], true) ? 'pgsql' : 'mysql',
        'host'    => $partes['host'],
        'puerto'  => (string) ($partes['port'] ?? ($esquema === 'mysql' ? 3306 : 5432)),
        'nombre'  => ltrim($partes['path'] ?? '', '/'),
        'usuario' => urldecode($partes['user'] ?? ''),
        'clave'   => urldecode($partes['pass'] ?? ''),
        'sslmode' => $query['sslmode'] ?? 'require',
    ];
}

return [
    'app' => [
        'nombre'         => 'E-Waste 360°',
        'institucion'    => Env::obtener('APP_INSTITUCION', 'Instituto Tecnológico'),
        'entorno'        => Env::obtener('APP_ENV', 'local'),
        'debug'          => Env::obtener('APP_DEBUG', 'false') === 'true',
        'zona_horaria'   => Env::obtener('APP_TIMEZONE', 'America/Mexico_City'),
        // Detras de un proxy (Render) el HTTPS llega en la cabecera X-Forwarded-Proto
        'confiar_proxy'  => Env::obtener('TRUST_PROXY', 'false') === 'true',
        'sesion_minutos' => (int) Env::obtener('SESSION_MINUTES', '30'),
        'version_assets' => '1.0.0',
    ],
    'db' => $db,
    'seguridad' => [
        'intentos_login'    => 5,
        'bloqueo_minutos'   => 15,
    ],
];
