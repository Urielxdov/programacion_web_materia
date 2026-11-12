<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Bateria o pila: residuo peligroso por definicion. */
final class Bateria extends Equipo
{
    public function tipo(): string
    {
        return 'bateria';
    }

    public function icono(): string
    {
        return 'battery-half';
    }

    public function contieneMaterialesPeligrosos(): bool
    {
        return true;
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'Cubre los polos con cinta aislante para evitar cortocircuitos.',
            'Nunca la tires a la basura común: puede provocar incendios.',
        ], parent::recomendacionesEntrega());
    }
}
