<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\View;

/**
 * Capa de aplicacion: los controladores reciben la peticion, llaman a los servicios
 * y eligen la vista o el JSON de respuesta. No contienen SQL ni reglas de negocio.
 */
abstract class Controller
{
    protected function vista(string $vista, array $datos = [], string $layout = 'layouts/panel', int $codigo = 200): Response
    {
        return Response::html(View::render($vista, $datos, $layout), $codigo);
    }

    protected function redirigir(string $ruta, ?string $exito = null): Response
    {
        if ($exito !== null) {
            Session::flash('exito', $exito);
        }
        return Response::redirigir(Url::a($ruta));
    }

    protected function json(mixed $datos, int $codigo = 200): Response
    {
        return Response::json($datos, $codigo);
    }

    protected function usuarioId(): int
    {
        return (int) Auth::id();
    }
}
