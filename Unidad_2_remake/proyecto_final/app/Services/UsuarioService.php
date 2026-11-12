<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

/** Administracion de cuentas y roles (solo administrador). */
final class UsuarioService
{
    private const ETIQUETAS = [
        'nombre'   => 'nombre',
        'usuario'  => 'usuario',
        'email'    => 'correo',
        'rol'      => 'rol',
        'password' => 'contraseña',
    ];

    private UsuarioRepository $usuarios;

    public function __construct(?UsuarioRepository $usuarios = null)
    {
        $this->usuarios = $usuarios ?? new UsuarioRepository();
    }

    /** @return Usuario[] */
    public function todos(): array
    {
        return $this->usuarios->todos();
    }

    public function obtener(int $id): Usuario
    {
        return $this->usuarios->porId($id) ?? throw new HttpException(404, 'El usuario no existe.');
    }

    public function crear(array $entrada): Usuario
    {
        $d = Validator::validar($entrada, [
            'nombre'   => 'requerido|texto:120',
            'usuario'  => 'requerido|usuario',
            'email'    => 'opcional|email|texto:150',
            'rol'      => 'requerido|en:' . implode(',', array_keys(Usuario::ROLES)),
            'password' => 'requerido|password',
            'activo'   => 'booleano',
        ], self::ETIQUETAS);
        $this->verificarUnicos($d['usuario'], $d['email'], null);

        $usuario = new Usuario(null, $d['nombre'], $d['usuario'], $d['email'], Usuario::hashear($d['password']), $d['rol'], (bool) $d['activo']);
        return $this->obtener($this->usuarios->insertar($usuario));
    }

    /** Un administrador no puede quitarse su rol ni desactivarse (evita quedar sin acceso). */
    public function actualizar(int $id, array $entrada, int $actorId): Usuario
    {
        $usuario = $this->obtener($id);
        $d = Validator::validar($entrada, [
            'nombre'   => 'requerido|texto:120',
            'email'    => 'opcional|email|texto:150',
            'rol'      => 'requerido|en:' . implode(',', array_keys(Usuario::ROLES)),
            'password' => 'opcional|password',
            'activo'   => 'booleano',
        ], self::ETIQUETAS);
        $this->verificarUnicos(null, $d['email'], $id);

        $dejaDeSerAdminActivo = $usuario->esAdmin() && $usuario->activo && ($d['rol'] !== 'admin' || !$d['activo']);
        if ($dejaDeSerAdminActivo && $id === $actorId) {
            throw ValidacionException::campo('rol', 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
        }
        if ($dejaDeSerAdminActivo && $this->usuarios->contarAdminsActivos() <= 1) {
            throw ValidacionException::campo('rol', 'Debe quedar al menos un administrador activo.');
        }

        $this->usuarios->actualizarPerfil($id, $d['nombre'], $d['email'], $d['rol'], (bool) $d['activo']);
        if ($d['password'] !== null) {
            $this->usuarios->cambiarPassword($id, Usuario::hashear($d['password']));
        }
        return $this->obtener($id);
    }

    private function verificarUnicos(?string $usuario, ?string $email, ?int $exceptoId): void
    {
        if ($usuario !== null && $this->usuarios->existeUsuario($usuario, $exceptoId)) {
            throw ValidacionException::campo('usuario', 'Ese nombre de usuario ya está en uso.');
        }
        if ($email !== null && $this->usuarios->existeEmail($email, $exceptoId)) {
            throw ValidacionException::campo('email', 'Ese correo ya está registrado.');
        }
    }
}
