<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sesion PHP con cookie HttpOnly + SameSite=Lax (+ Secure bajo HTTPS),
 * expiracion por inactividad y mensajes "flash" que duran una sola peticion.
 */
final class Session
{
    /** Flash recibidos de la peticion anterior. */
    private static array $flashActual = [];

    public static function iniciar(bool $segura, string $basePath = ''): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_name('ewaste_sesion');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $basePath === '' ? '/' : $basePath . '/',
            'secure'   => $segura,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $ahora = time();
        $limite = max(5, (int) config('app.sesion_minutos')) * 60;
        if (isset($_SESSION['_ultimo_uso']) && $ahora - $_SESSION['_ultimo_uso'] > $limite && isset($_SESSION['auth'])) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['_flash'] = ['aviso' => 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.'];
        }
        $_SESSION['_ultimo_uso'] = $ahora;

        self::$flashActual = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function guardar(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function olvidar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    public static function regenerar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function vaciar(): void
    {
        $_SESSION = [];
        self::regenerar();
    }

    /** Guarda un dato para la siguiente peticion (patron Post/Redirect/Get). */
    public static function flash(string $clave, mixed $valor): void
    {
        $_SESSION['_flash'][$clave] = $valor;
    }

    public static function leerFlash(string $clave, mixed $defecto = null): mixed
    {
        return self::$flashActual[$clave] ?? $defecto;
    }
}
