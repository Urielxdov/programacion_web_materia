<?php
// Copia este archivo como config.local.php y llena tus datos.
// Gmail: activa verificación en 2 pasos y crea una "contraseña de aplicación" en myaccount.google.com/apppasswords
return [
    'mail' => [
        'mode' => 'smtp',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
        'user' => 'tucorreo@gmail.com',
        'pass' => 'contraseña-de-aplicacion',
        'from' => 'tucorreo@gmail.com',
    ],
];
