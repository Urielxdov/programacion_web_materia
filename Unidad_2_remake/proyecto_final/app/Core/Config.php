<?php
declare(strict_types=1);

namespace App\Core;

/** Acceso a config/*.php con notacion de puntos: config('db.host'). */
final class Config
{
    private static array $valores = [];

    public static function cargar(string $directorio): void
    {
        foreach (glob($directorio . '/*.php') as $archivo) {
            if (basename($archivo) === 'rutas.php') {
                continue;
            }
            $nombre = basename($archivo, '.php');
            $datos = require $archivo;
            // config.php define las secciones principales (app, db, seguridad); el resto
            // de archivos se guardan con su nombre (permisos.php => config('permisos')).
            if ($nombre === 'config') {
                self::$valores = array_merge(self::$valores, $datos);
            } else {
                self::$valores[$nombre] = $datos;
            }
        }
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        $valor = self::$valores;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $defecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
