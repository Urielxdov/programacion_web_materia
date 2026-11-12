<?php
declare(strict_types=1);

/*
 * Arranque comun para la web (public/index.php), el instalador y las pruebas.
 * Registra el autoloader PSR-4 (App\ => app/), carga .env y la configuracion.
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $clase): void {
    $prefijo = 'App\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $archivo = BASE_PATH . '/app/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

require BASE_PATH . '/app/helpers.php';

App\Core\Env::cargar(BASE_PATH . '/.env');
App\Core\Config::cargar(BASE_PATH . '/config');

date_default_timezone_set((string) config('app.zona_horaria'));
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
