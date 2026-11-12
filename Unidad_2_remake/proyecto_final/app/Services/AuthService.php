<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

/**
 * Autenticacion: verifica la contrasena con password_verify, bloquea la cuenta tras varios
 * intentos fallidos y responde siempre el mismo mensaje para no revelar que usuarios existen.
 */
final class AuthService
{
    private const MENSAJE_GENERICO = 'Usuario o contraseña incorrectos.';

    private UsuarioRepository $usuarios;

    public function __construct(?UsuarioRepository $usuarios = null)
    {
        $this->usuarios = $usuarios ?? new UsuarioRepository();
    }

    /** @throws HttpException 401 credenciales invalidas, 429 cuenta bloqueada */
    public function autenticar(string $usuario, string $password): Usuario
    {
        $usuario = trim($usuario);
        if ($usuario === '' || $password === '') {
            throw new HttpException(401, 'Escribe tu usuario y contraseña.');
        }

        $cuenta = $this->usuarios->porUsuario($usuario);
        if ($cuenta === null || !$cuenta->activo) {
            // Mismo tiempo de respuesta exista o no el usuario
            password_verify($password, self::hashFicticio());
            throw new HttpException(401, self::MENSAJE_GENERICO);
        }

        if ($cuenta->estaBloqueado()) {
            throw new HttpException(429, 'Demasiados intentos fallidos. Espera unos minutos e intenta de nuevo.');
        }

        if (!$cuenta->verificarPassword($password)) {
            $intentos = $cuenta->intentosFallidos + 1;
            $maximo = (int) config('seguridad.intentos_login', 5);
            $bloqueo = null;
            if ($intentos >= $maximo) {
                $bloqueo = date('Y-m-d H:i:s', time() + 60 * (int) config('seguridad.bloqueo_minutos', 15));
                $intentos = 0;
            }
            $this->usuarios->registrarIntentoFallido((int) $cuenta->id, $intentos, $bloqueo);
            throw new HttpException(401, self::MENSAJE_GENERICO);
        }

        if ($cuenta->necesitaRehash()) {
            $this->usuarios->cambiarPassword((int) $cuenta->id, Usuario::hashear($password));
        }
        $this->usuarios->registrarAcceso((int) $cuenta->id);
        return $cuenta;
    }

    private static function hashFicticio(): string
    {
        static $hash = null;
        return $hash ??= password_hash('contraseña-inexistente', PASSWORD_DEFAULT);
    }
}
