<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Diagnostico;

/** Datos del diagnostico que usan las estrategias para recomendar un destino (objeto de valor). */
final class EvaluacionTecnica
{
    public function __construct(
        public readonly string $estadoFuncional,
        public readonly float $costoReparacion,
        public readonly float $valorReemplazo,
        public readonly bool $riesgoSeguridad,
    ) {
        if (!isset(Diagnostico::ESTADOS_FUNCIONALES[$estadoFuncional])) {
            throw new \InvalidArgumentException("Estado funcional no válido: $estadoFuncional");
        }
        if ($costoReparacion < 0 || $valorReemplazo < 0) {
            throw new \InvalidArgumentException('Los montos no pueden ser negativos.');
        }
    }

    public function funciona(): bool
    {
        return $this->estadoFuncional === 'funciona';
    }

    /** Costo de reparar como porcentaje del valor de un equipo equivalente nuevo. */
    public function porcentajeCosto(): ?float
    {
        return $this->valorReemplazo > 0 ? $this->costoReparacion / $this->valorReemplazo * 100 : null;
    }
}
