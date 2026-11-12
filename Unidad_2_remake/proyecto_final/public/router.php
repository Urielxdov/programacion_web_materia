<?php
// Solo para el servidor embebido de PHP:
//   php -S localhost:8000 -t public public/router.php
// Sirve los archivos estaticos tal cual y manda todo lo demas al Front Controller.
$ruta = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($ruta !== '/' && is_file(__DIR__ . $ruta) && !str_ends_with($ruta, '.php')) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
