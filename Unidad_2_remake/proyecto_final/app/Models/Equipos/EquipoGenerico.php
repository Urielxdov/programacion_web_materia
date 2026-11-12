<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Cualquier dispositivo sin categoria especifica. */
final class EquipoGenerico extends Equipo
{
    public function tipo(): string
    {
        return 'otro';
    }

    public function icono(): string
    {
        return 'cpu';
    }
}
