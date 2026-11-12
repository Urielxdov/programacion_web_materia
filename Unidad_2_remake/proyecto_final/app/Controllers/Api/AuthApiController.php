<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Inicio de sesion para clientes de la API (Postman, pruebas). Devuelve la cookie de sesion
 * y el token CSRF que se debe enviar en la cabecera X-CSRF-Token en POST/PATCH.
 */
final class AuthApiController extends Controller
{
    public function login(Request $request): Response
    {
        $cuenta = (new AuthService())->autenticar((string) $request->input('usuario', ''), (string) $request->input('password', ''));
        Auth::iniciar($cuenta);
        return $this->json(['usuario' => $cuenta->toArray(), 'csrf_token' => Csrf::token()]);
    }

    public function logout(Request $request): Response
    {
        Auth::cerrar();
        return $this->json(['mensaje' => 'Sesión cerrada.']);
    }
}
