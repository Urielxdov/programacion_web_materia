<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

/** Usuario autenticado en la sesion y consulta de permisos por rol. */
final class Auth
{
    public static function iniciar(Usuario $usuario): void
    {
        Session::regenerar(); // evita fijacion de sesion
        self::guardar($usuario);
        Csrf::regenerar();
    }

    public static function guardar(Usuario $usuario): void
    {
        Session::guardar('auth', [
            'id'      => $usuario->id,
            'nombre'  => $usuario->nombre,
            'usuario' => $usuario->usuario,
            'rol'     => $usuario->rol,
        ]);
    }

    public static function cerrar(): void
    {
        Session::vaciar();
    }

    public static function usuario(): ?array
    {
        $datos = Session::obtener('auth');
        return is_array($datos) ? $datos : null;
    }

    public static function check(): bool
    {
        return self::usuario() !== null;
    }

    public static function id(): ?int
    {
        return isset(self::usuario()['id']) ? (int) self::usuario()['id'] : null;
    }

    public static function rol(): ?string
    {
        return self::usuario()['rol'] ?? null;
    }

    public static function puede(string $permiso): bool
    {
        $rol = self::rol();
        return $rol !== null && self::rolPuede($rol, $permiso);
    }

    public static function rolPuede(string $rol, string $permiso): bool
    {
        return in_array($permiso, config("permisos.$rol", []), true);
    }
}
