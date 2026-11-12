<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Computadora de escritorio: almacena datos personales en su disco. */
final class Computadora extends Equipo
{
    public function tipo(): string
    {
        return 'computadora';
    }

    public function icono(): string
    {
        return 'pc-display';
    }

    public function requiereBorradoDatos(): bool
    {
        return true;
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'Retira el disco duro o aplica un borrado seguro (no basta con formatear).',
        ], parent::recomendacionesEntrega());
    }
}
