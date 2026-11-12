<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lee un archivo .env (CLAVE=valor) y lo expone con getenv().
 * Las variables que ya existen en el entorno (por ejemplo, las de Render) tienen prioridad.
 */
final class Env
{
    public static function cargar(string $archivo): void
    {
        if (!is_file($archivo)) {
            return;
        }
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            if ((str_starts_with($valor, '"') && str_ends_with($valor, '"'))
                || (str_starts_with($valor, "'") && str_ends_with($valor, "'"))) {
                $valor = substr($valor, 1, -1);
            }
            if (getenv($clave) === false) {
                putenv("$clave=$valor");
                $_ENV[$clave] = $valor;
            }
        }
    }

    public static function obtener(string $clave, ?string $defecto = null): ?string
    {
        $valor = getenv($clave);
        return ($valor === false || $valor === '') ? $defecto : $valor;
    }
}
