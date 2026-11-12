<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Impresora/escaner: cartuchos y toner se gestionan aparte. */
final class Impresora extends Equipo
{
    public function tipo(): string
    {
        return 'impresora';
    }

    public function icono(): string
    {
        return 'printer';
    }

    public function contieneMaterialesPeligrosos(): bool
    {
        return true;
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'Retira cartuchos y tóner y entrégalos por separado en su propio programa de reciclaje.',
        ], parent::recomendacionesEntrega());
    }
}
