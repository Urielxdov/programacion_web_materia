<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/** Se ejecuta antes del controlador: devuelve una respuesta para cortar la peticion o null para continuar. */
interface Middleware
{
    public function manejar(Request $request): ?Response;
}
