<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Monitor o pantalla: el panel y la retroiluminacion requieren manejo especial. */
final class Monitor extends Equipo
{
    public function tipo(): string
    {
        return 'monitor';
    }

    public function icono(): string
    {
        return 'display';
    }

    public function contieneMaterialesPeligrosos(): bool
    {
        return true;
    }

    public function recomendacionesEntrega(): array
    {
        return array_merge([
            'Transpórtalo con la pantalla protegida: un panel roto libera vidrio y, en monitores antiguos (CRT), plomo.',
        ], parent::recomendacionesEntrega());
    }
}
