<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Evaluacion tecnica de un equipo. Guarda la recomendacion que calculo el sistema
 * (Strategy) y la decision final del responsable ambiental.
 */
final class Diagnostico
{
    public const ESTADOS_FUNCIONALES = [
        'funciona'      => 'Funciona correctamente',
        'falla_parcial' => 'Falla parcial',
        'no_funciona'   => 'No funciona',
    ];

    public const DECISIONES = [
        'reutilizar'            => 'Reutilizar / reasignar',
        'conservar'             => 'Conservar como respaldo',
        'reparar'               => 'Reparar',
        'gestion_especializada' => 'Enviar a gestión especializada',
    ];

    /** Clasificacion que recibe el equipo segun la decision tomada. */
    public const CLASIFICACION_POR_DECISION = [
        'reutilizar'            => 'reutilizable',
        'conservar'             => 'funcional',
        'reparar'               => 'reparable',
        'gestion_especializada' => 'gestion_especializada',
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly int $equipoId,
        public readonly string $descripcion,
        public readonly string $estadoFuncional,
        public readonly float $costoReparacion,
        public readonly float $valorReemplazo,
        public readonly bool $riesgoSeguridad,
        public readonly string $recomendacionSistema,
        public readonly string $decision,
        public readonly ?string $justificacion,
        public readonly string $fecha,
        public readonly int $responsableId,
        public readonly ?string $responsableNombre = null,
    ) {
        if (!isset(self::ESTADOS_FUNCIONALES[$estadoFuncional])) {
            throw new \InvalidArgumentException("Estado funcional no válido: $estadoFuncional");
        }
        foreach ([$recomendacionSistema, $decision] as $d) {
            if (!isset(self::DECISIONES[$d])) {
                throw new \InvalidArgumentException("Decisión no válida: $d");
            }
        }
        if ($costoReparacion < 0 || $valorReemplazo < 0) {
            throw new \InvalidArgumentException('Los montos no pueden ser negativos.');
        }
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id'],
            (int) $f['equipo_id'],
            $f['descripcion'],
            $f['estado_funcional'],
            (float) $f['costo_reparacion'],
            (float) $f['valor_reemplazo'],
            (bool) $f['riesgo_seguridad'],
            $f['recomendacion_sistema'],
            $f['decision'],
            $f['justificacion'],
            substr((string) $f['fecha'], 0, 10),
            (int) $f['responsable_id'],
            $f['responsable_nombre'] ?? null,
        );
    }

    public function clasificacionResultante(): string
    {
        return self::CLASIFICACION_POR_DECISION[$this->decision];
    }

    public function siguioRecomendacion(): bool
    {
        return $this->decision === $this->recomendacionSistema;
    }

    /** Lo que se evita gastar si se repara en lugar de comprar uno nuevo. */
    public function ahorroPotencial(): float
    {
        return max(0.0, $this->valorReemplazo - $this->costoReparacion);
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'equipo_id'             => $this->equipoId,
            'descripcion'           => $this->descripcion,
            'estado_funcional'      => $this->estadoFuncional,
            'costo_reparacion'      => $this->costoReparacion,
            'valor_reemplazo'       => $this->valorReemplazo,
            'riesgo_seguridad'      => $this->riesgoSeguridad,
            'recomendacion_sistema' => $this->recomendacionSistema,
            'decision'              => $this->decision,
            'justificacion'         => $this->justificacion,
            'fecha'                 => $this->fecha,
            'responsable'           => $this->responsableNombre,
        ];
    }
}
