<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\UsuarioService;

/** Administracion de usuarios y roles. */
final class UsuarioController extends Controller
{
    private UsuarioService $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioService();
    }

    public function index(Request $request): Response
    {
        return $this->vista('usuarios/index', ['titulo' => 'Usuarios', 'usuarios' => $this->usuarios->todos()]);
    }

    public function crear(Request $request): Response
    {
        return $this->vista('usuarios/formulario', ['titulo' => 'Nuevo usuario', 'usuario' => null]);
    }

    public function guardar(Request $request): Response
    {
        $usuario = $this->usuarios->crear($request->todo());
        return $this->redirigir('panel/usuarios', "Usuario {$usuario->usuario} creado.");
    }

    public function editar(Request $request, int $id): Response
    {
        return $this->vista('usuarios/formulario', ['titulo' => 'Editar usuario', 'usuario' => $this->usuarios->obtener($id)]);
    }

    public function actualizar(Request $request, int $id): Response
    {
        $usuario = $this->usuarios->actualizar($id, $request->todo(), $this->usuarioId());
        return $this->redirigir('panel/usuarios', "Usuario {$usuario->usuario} actualizado.");
    }
}
