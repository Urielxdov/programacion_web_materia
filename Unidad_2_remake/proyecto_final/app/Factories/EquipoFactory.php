<?php
declare(strict_types=1);

namespace App\Factories;

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\Equipos\Accesorio;
use App\Models\Equipos\Bateria;
use App\Models\Equipos\Celular;
use App\Models\Equipos\Computadora;
use App\Models\Equipos\EquipoGenerico;
use App\Models\Equipos\Impresora;
use App\Models\Equipos\Laptop;
use App\Models\Equipos\Monitor;
use App\Models\Equipos\Periferico;

/**
 * Patron Factory Method.
 *
 * Problema que resuelve: el resto del sistema (servicios, repositorios, API) trabaja con
 * "Equipo" sin saber que clase concreta necesita cada categoria. Agregar un tipo nuevo
 * solo requiere una subclase y una linea en este mapa; ningun if/switch se reparte por el codigo.
 */
final class EquipoFactory
{
    /** @var array<string, class-string<Equipo>> */
    private const CLASES = [
        'computadora' => Computadora::class,
        'laptop'      => Laptop::class,
        'monitor'     => Monitor::class,
        'impresora'   => Impresora::class,
        'celular'     => Celular::class,
        'bateria'     => Bateria::class,
        'periferico'  => Periferico::class,
        'accesorio'   => Accesorio::class,
        'otro'        => EquipoGenerico::class,
    ];

    public static function crear(Categoria $categoria, array $datos): Equipo
    {
        $clase = self::CLASES[$categoria->tipo] ?? EquipoGenerico::class;
        return new $clase($categoria, $datos);
    }

    /** Reconstruye un equipo desde una fila de equipos JOIN categorias (columnas cat_*). */
    public static function desdeFila(array $fila): Equipo
    {
        return self::crear(Categoria::desdeFila($fila, 'cat_'), $fila);
    }

    /** @return string[] */
    public static function tiposSoportados(): array
    {
        return array_keys(self::CLASES);
    }
}
