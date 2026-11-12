<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Cables, cargadores y adaptadores. */
final class Accesorio extends Equipo
{
    public function tipo(): string
    {
        return 'accesorio';
    }

    public function icono(): string
    {
        return 'plug';
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'Agrupa cables y cargadores por tipo; el cobre que contienen es recuperable.',
        ], parent::recomendacionesEntrega());
    }
}
