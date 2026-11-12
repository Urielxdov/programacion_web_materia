<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Equipo;

/** Funciona pero ya rebaso su vida util: se conserva como respaldo o para refacciones. */
final class EstrategiaConservacion implements EstrategiaDestino
{
    public function decision(): string
    {
        return 'conservar';
    }

    public function aplica(Equipo $equipo, EvaluacionTecnica $evaluacion): bool
    {
        return $evaluacion->funciona() && !$evaluacion->riesgoSeguridad;
    }

    public function justificacion(Equipo $equipo, EvaluacionTecnica $evaluacion): string
    {
        return 'El equipo funciona pero ya superó su vida útil de referencia ('
            . $equipo->categoria()->vidaUtilAnios . ' años): conviene conservarlo como respaldo '
            . 'o fuente de refacciones antes de considerarlo residuo.';
    }
}
