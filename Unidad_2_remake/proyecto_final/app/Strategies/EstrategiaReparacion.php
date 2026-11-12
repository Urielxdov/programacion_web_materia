<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Equipo;

/** Tiene fallas, es seguro y repararlo cuesta menos que el umbral (% del valor de reemplazo). */
final class EstrategiaReparacion implements EstrategiaDestino
{
    public function __construct(private float $umbralPorcentaje = 60.0)
    {
        if ($umbralPorcentaje <= 0 || $umbralPorcentaje > 100) {
            throw new \InvalidArgumentException('El umbral de reparación debe estar entre 1 y 100.');
        }
    }

    public function decision(): string
    {
        return 'reparar';
    }

    public function aplica(Equipo $equipo, EvaluacionTecnica $evaluacion): bool
    {
        $porcentaje = $evaluacion->porcentajeCosto();
        return !$evaluacion->funciona()
            && !$evaluacion->riesgoSeguridad
            && $porcentaje !== null
            && $porcentaje <= $this->umbralPorcentaje;
    }

    public function justificacion(Equipo $equipo, EvaluacionTecnica $evaluacion): string
    {
        $porcentaje = number_format((float) $evaluacion->porcentajeCosto(), 1);
        $ahorro = number_format(max(0, $evaluacion->valorReemplazo - $evaluacion->costoReparacion), 2);
        return "Repararlo cuesta el $porcentaje% del valor de un equipo nuevo (umbral: "
            . number_format($this->umbralPorcentaje, 0) . "%). Reparar evita un residuo y ahorra aproximadamente \$$ahorro.";
    }
}
