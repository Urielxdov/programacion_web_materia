<?php
declare(strict_types=1);

namespace App\Models\Equipos;

use App\Models\Equipo;

/** Celular o tableta: datos personales y bateria de litio. */
final class Celular extends Equipo
{
    public function tipo(): string
    {
        return 'celular';
    }

    public function icono(): string
    {
        return 'phone';
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
            'Cierra tus cuentas, retira la SIM y la memoria y restablece de fábrica.',
            'No cargues ni uses equipos con la batería inflada.',
        ], parent::recomendacionesEntrega());
    }
}
