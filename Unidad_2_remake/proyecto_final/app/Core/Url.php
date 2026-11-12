<?php
declare(strict_types=1);

namespace App\Core;

/** Genera URLs respetando la carpeta donde vive la app (por ejemplo, htdocs/proyecto_final/public). */
final class Url
{
    private static string $base = '';

    public static function establecerBase(string $base): void
    {
        self::$base = rtrim($base, '/');
    }

    public static function a(string $ruta = ''): string
    {
        return self::$base . '/' . ltrim($ruta, '/');
    }
}
