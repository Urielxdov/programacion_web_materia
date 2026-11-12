<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Equipo;

/**
 * Patron Strategy: cada destino posible (reutilizar, conservar, reparar, gestion especializada)
 * encapsula su propio criterio. El ClasificadorDestino no conoce las reglas; solo pregunta
 * a cada estrategia si aplica. Cambiar la politica = cambiar o reordenar estrategias.
 */
interface EstrategiaDestino
{
    /** Clave de la decision (Diagnostico::DECISIONES). */
    public function decision(): string;

    public function aplica(Equipo $equipo, EvaluacionTecnica $evaluacion): bool;

    public function justificacion(Equipo $equipo, EvaluacionTecnica $evaluacion): string;
}
