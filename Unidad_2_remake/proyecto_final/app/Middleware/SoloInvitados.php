<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;

/** Paginas como /login solo tienen sentido sin sesion. */
final class SoloInvitados implements Middleware
{
    public function manejar(Request $request): ?Response
    {
        return Auth::check() ? Response::redirigir(Url::a('panel')) : null;
    }
}
