<?php
declare(strict_types=1);

namespace App\Core;

/** Token sincronizado contra CSRF: va en formularios (_token) o en la cabecera X-CSRF-Token. */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::obtener('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::guardar('_csrf', $token);
        }
        return $token;
    }

    public static function regenerar(): string
    {
        Session::olvidar('_csrf');
        return self::token();
    }

    public static function valido(?string $token): bool
    {
        $esperado = Session::obtener('_csrf');
        return is_string($token) && is_string($esperado) && $esperado !== '' && hash_equals($esperado, $token);
    }
}
