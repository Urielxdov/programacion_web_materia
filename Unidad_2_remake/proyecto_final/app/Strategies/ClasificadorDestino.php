<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Equipo;

/**
 * Contexto del patron Strategy. Recorre las estrategias en orden de prioridad y usa la
 * primera que aplica. La politica institucional (umbral de reparacion, tolerancia de vida
 * util) se configura en el panel de administracion y llega por el constructor.
 */
final class ClasificadorDestino
{
    /** @param EstrategiaDestino[] $estrategias */
    public function __construct(private array $estrategias)
    {
        if ($estrategias === []) {
            throw new \InvalidArgumentException('Se necesita al menos una estrategia.');
        }
    }

    /** Politica predeterminada: primero aprovechar el equipo y al final gestionarlo como residuo. */
    public static function politicaInstitucional(float $umbralReparacion, float $factorVidaUtil, ?int $anioActual = null): self
    {
        return new self([
            new EstrategiaReutilizacion($factorVidaUtil, $anioActual),
            new EstrategiaConservacion(),
            new EstrategiaReparacion($umbralReparacion),
            new EstrategiaGestionEspecializada(),
        ]);
    }

    public function recomendar(Equipo $equipo, EvaluacionTecnica $evaluacion): Recomendacion
    {
        foreach ($this->estrategias as $estrategia) {
            if ($estrategia->aplica($equipo, $evaluacion)) {
                return new Recomendacion(
                    $estrategia->decision(),
                    $estrategia->justificacion($equipo, $evaluacion),
                    (new \ReflectionClass($estrategia))->getShortName()
                );
            }
        }
        throw new \LogicException('Ninguna estrategia aplicó; agrega una estrategia de respaldo.');
    }
}
