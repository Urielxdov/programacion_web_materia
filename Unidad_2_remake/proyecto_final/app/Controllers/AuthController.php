<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function formulario(Request $request): Response
    {
        return $this->vista('auth/login', ['titulo' => 'Iniciar sesión'], 'layouts/publico');
    }

    public function login(Request $request): Response
    {
        $usuario = (string) $request->input('usuario', '');
        try {
            $cuenta = (new AuthService())->autenticar($usuario, (string) $request->input('password', ''));
        } catch (HttpException $e) {
            Session::flash('error', $e->getMessage());
            Session::flash('entrada', ['usuario' => $usuario]);
            return Response::redirigir(Url::a('login'));
        }

        Auth::iniciar($cuenta);
        $destino = Session::obtener('url_destino');
        Session::olvidar('url_destino');
        // Solo se redirige a rutas internas del panel (evita redirecciones abiertas)
        $ruta = is_string($destino) && str_starts_with($destino, '/panel') ? $destino : '/panel';
        Session::flash('exito', 'Bienvenido(a), ' . $cuenta->nombre . '.');
        return Response::redirigir(Url::a($ruta));
    }

    public function logout(Request $request): Response
    {
        Auth::cerrar();
        return $this->redirigir('', 'Cerraste sesión correctamente.');
    }
}
