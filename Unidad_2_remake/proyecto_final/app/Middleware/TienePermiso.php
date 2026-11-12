<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Autorizacion por rol en el servidor: 403 si el rol no tiene el permiso. */
final class TienePermiso implements Middleware
{
    public function __construct(private string $permiso)
    {
    }

    public function manejar(Request $request): ?Response
    {
        if (!Auth::puede($this->permiso)) {
            throw new HttpException(403, 'Tu rol no tiene permiso para realizar esta acción.');
        }
        return null;
    }
}
