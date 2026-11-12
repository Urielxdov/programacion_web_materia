<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Diagnostico;

/** Resultado del ClasificadorDestino (objeto de valor). */
final class Recomendacion
{
    public function __construct(
        public readonly string $decision,
        public readonly string $justificacion,
        public readonly string $estrategia,
    ) {
    }

    public function toArray(): array
    {
        return [
            'decision'       => $this->decision,
            'decision_texto' => Diagnostico::DECISIONES[$this->decision],
            'clasificacion'  => Diagnostico::CLASIFICACION_POR_DECISION[$this->decision],
            'justificacion'  => $this->justificacion,
            'estrategia'     => $this->estrategia,
        ];
    }
}
