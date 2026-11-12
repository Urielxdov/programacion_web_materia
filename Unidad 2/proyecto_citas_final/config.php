<?php
// Configuración. Cada valor se puede sobreescribir con variables de entorno.
$config = [
    'db' => [
        'host' => getenv('CITAS_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('CITAS_DB_PORT') ?: '3307',
        'name' => getenv('CITAS_DB_NAME') ?: 'clinica_citas',
        'user' => getenv('CITAS_DB_USER') ?: 'root',
        'pass' => getenv('CITAS_DB_PASS') ?: '',
    ],
    'mail' => [
        // archivo: guarda los correos en storage/correos.log (no envía nada)
        // smtp:    envía por SMTP real (Gmail, Outlook, etc.)
        // mail:    usa la función mail() de PHP
        'mode' => getenv('CITAS_MAIL_MODE') ?: 'archivo',
        'from' => getenv('CITAS_MAIL_FROM') ?: 'citas@clinica.local',
        'from_name' => 'Clínica Médica',
        'host' => getenv('CITAS_SMTP_HOST') ?: 'smtp.gmail.com',
        'port' => (int)(getenv('CITAS_SMTP_PORT') ?: 587),
        'secure' => getenv('CITAS_SMTP_SECURE') ?: 'tls', // tls (STARTTLS), ssl o vacío
        'user' => getenv('CITAS_SMTP_USER') ?: '',
        'pass' => getenv('CITAS_SMTP_PASS') ?: '',
    ],
    // Horario de atención: última hora de inicio permitida y duración de cada cita (minutos)
    'agenda' => ['inicio' => '10:00', 'ultima' => '14:00', 'duracion' => 30],
    // Usuarios iniciales que crea setup.php
    'admin' => ['nombre' => 'Administrador', 'correo' => 'admin@clinica.local', 'pass' => 'admin123'],
    'asistente' => ['nombre' => 'Asistente', 'correo' => 'asistente@clinica.local', 'pass' => 'asistente123'],
];

// Overrides locales (no se versiona): copia config.local.example.php a config.local.php
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;
