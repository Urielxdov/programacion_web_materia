<?php
declare(strict_types=1);

// Front Controller: unica puerta de entrada de la aplicacion.
require dirname(__DIR__) . '/app/bootstrap.php';

(new App\Core\Aplicacion(require BASE_PATH . '/config/rutas.php'))->ejecutar();
