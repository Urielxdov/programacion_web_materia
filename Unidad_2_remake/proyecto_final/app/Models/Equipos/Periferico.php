<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Teclado, raton, bocinas: sin datos ni materiales peligrosos relevantes. */
final class Periferico extends Equipo
{
    public function tipo(): string
    {
        return 'periferico';
    }

    public function icono(): string
    {
        return 'keyboard';
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'Si funciona, considera donarlo: los periféricos suelen tener una segunda vida útil.',
        ], parent::recomendacionesEntrega());
    }
}
