<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Laptop: guarda datos y lleva bateria de litio (material peligroso). */
final class Laptop extends Equipo
{
    public function tipo(): string
    {
        return 'laptop';
    }

    public function icono(): string
    {
        return 'laptop';
    }

    public function requiereBorradoDatos(): bool
    {
        return true;
    }

    public function contieneMaterialesPeligrosos(): bool
    {
        return true;
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'La batería de litio no debe perforarse ni exponerse al calor; si está inflada, sepárala y avísalo al gestor.',
        ], parent::recomendacionesEntrega());
    }
}
