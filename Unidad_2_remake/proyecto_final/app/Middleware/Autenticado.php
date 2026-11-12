<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UsuarioRepository;

/**
 * Exige sesion iniciada. En cada peticion vuelve a leer al usuario de la base para que
 * un cambio de rol o una cuenta desactivada surtan efecto de inmediato.
 */
final class Autenticado implements Middleware
{
    public function manejar(Request $request): ?Response
    {
        $id = Auth::id();
        if ($id === null) {
            throw new HttpException(401, 'Inicia sesión para continuar.');
        }
        $usuario = (new UsuarioRepository())->porId($id);
        if ($usuario === null || !$usuario->activo) {
            Auth::cerrar();
            throw new HttpException(401, 'Tu cuenta no está disponible. Contacta al administrador.');
        }
        Auth::guardar($usuario);
        return null;
    }
}
