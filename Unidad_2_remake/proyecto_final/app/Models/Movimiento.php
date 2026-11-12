<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Evento en la vida del equipo (trazabilidad). Los registra un capturista y los valida
 * el responsable ambiental; solo los validados cambian el estado del equipo.
 */
final class Movimiento
{
    public const TIPOS = [
        'ingreso'        => 'Ingreso al inventario',
        'reparacion'     => 'Reparación realizada',
        'reasignacion'   => 'Reasignación / reutilización',
        'conservacion'   => 'Conservación como respaldo',
        'entrega_gestor' => 'Entrega a gestor',
    ];

    /** Tipos que el usuario puede registrar (el ingreso lo crea el sistema). */
    public const TIPOS_REGISTRABLES = ['reparacion', 'reasignacion', 'conservacion', 'entrega_gestor'];

    public const ESTADOS = [
        'pendiente' => 'Pendiente de validar',
        'validado'  => 'Validado',
        'rechazado' => 'Rechazado',
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly int $equipoId,
        public readonly string $tipo,
        public readonly string $fecha,
        public readonly ?string $destino,
        public readonly ?int $gestorId,
        public readonly ?float $costo,
        public readonly float $ahorroEstimado,
        public readonly ?string $evidencia,
        public readonly bool $datosBorrados,
        public readonly bool $destinoVerificado,
        public readonly string $estadoValidacion,
        public readonly ?string $observaciones,
        public readonly int $registradoPor,
        public readonly ?int $validadoPor = null,
        public readonly ?string $validadoEn = null,
        public readonly array $extra = [],
    ) {
        if (!isset(self::TIPOS[$tipo])) {
            throw new \InvalidArgumentException("Tipo de movimiento no válido: $tipo");
        }
        if (!isset(self::ESTADOS[$estadoValidacion])) {
            throw new \InvalidArgumentException("Estado de validación no válido: $estadoValidacion");
        }
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id'],
            (int) $f['equipo_id'],
            $f['tipo'],
            substr((string) $f['fecha'], 0, 10),
            $f['destino'],
            $f['gestor_id'] !== null ? (int) $f['gestor_id'] : null,
            $f['costo'] !== null ? (float) $f['costo'] : null,
            (float) $f['ahorro_estimado'],
            $f['evidencia'],
            (bool) $f['datos_borrados'],
            (bool) $f['destino_verificado'],
            $f['estado_validacion'],
            $f['observaciones'],
            (int) $f['registrado_por'],
            $f['validado_por'] !== null ? (int) $f['validado_por'] : null,
            $f['validado_en'],
            [
                'equipo_codigo'  => $f['equipo_codigo'] ?? null,
                'equipo_desc'    => $f['equipo_desc'] ?? null,
                'gestor_nombre'  => $f['gestor_nombre'] ?? null,
                'registrado_por' => $f['registrado_nombre'] ?? null,
                'validado_por'   => $f['validado_nombre'] ?? null,
            ],
        );
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo];
    }

    public function nombreEstado(): string
    {
        return self::ESTADOS[$this->estadoValidacion];
    }

    public function estaPendiente(): bool
    {
        return $this->estadoValidacion === 'pendiente';
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'equipo_id'          => $this->equipoId,
            'tipo'               => $this->tipo,
            'tipo_texto'         => $this->nombreTipo(),
            'fecha'              => $this->fecha,
            'destino'            => $this->destino,
            'gestor'             => $this->extra['gestor_nombre'],
            'costo'              => $this->costo,
            'ahorro_estimado'    => $this->ahorroEstimado,
            'destino_verificado' => $this->destinoVerificado,
            'estado_validacion'  => $this->estadoValidacion,
        ];
    }
}
