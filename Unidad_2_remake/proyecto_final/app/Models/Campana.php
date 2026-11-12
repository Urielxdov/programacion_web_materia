<?php
declare(strict_types=1);

namespace App\Models;

/** Campana de recoleccion de residuos electronicos. */
final class Campana
{
    public const ESTADOS = [
        'borrador' => 'Borrador',
        'activa'   => 'Activa',
        'cerrada'  => 'Cerrada',
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
        public readonly string $ubicacion,
        public readonly string $tiposAceptados,
        public readonly ?int $metaEquipos,
        public readonly string $estado,
        public readonly ?int $creadoPor = null,
    ) {
        if (!isset(self::ESTADOS[$estado])) {
            throw new \InvalidArgumentException("Estado de campaña no válido: $estado");
        }
        if ($fechaFin < $fechaInicio) {
            throw new \InvalidArgumentException('La fecha de fin no puede ser anterior a la de inicio.');
        }
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id'],
            $f['nombre'],
            $f['descripcion'],
            substr((string) $f['fecha_inicio'], 0, 10),
            substr((string) $f['fecha_fin'], 0, 10),
            $f['ubicacion'],
            $f['tipos_aceptados'],
            $f['meta_equipos'] !== null ? (int) $f['meta_equipos'] : null,
            $f['estado'],
            isset($f['creado_por']) ? (int) $f['creado_por'] : null,
        );
    }

    public function esPublica(): bool
    {
        return $this->estado !== 'borrador';
    }

    /** Se aceptan registros mientras este activa y no haya terminado. */
    public function aceptaParticipaciones(?string $hoy = null): bool
    {
        return $this->estado === 'activa' && ($hoy ?? date('Y-m-d')) <= $this->fechaFin;
    }

    /** "proxima", "en_curso" o "finalizada" segun las fechas. */
    public function vigencia(?string $hoy = null): string
    {
        $hoy ??= date('Y-m-d');
        return match (true) {
            $hoy < $this->fechaInicio => 'proxima',
            $hoy > $this->fechaFin    => 'finalizada',
            default                   => 'en_curso',
        };
    }

    /** @return string[] */
    public function listaTiposAceptados(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->tiposAceptados))));
    }

    public function nombreEstado(): string
    {
        return self::ESTADOS[$this->estado];
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'descripcion'     => $this->descripcion,
            'fecha_inicio'    => $this->fechaInicio,
            'fecha_fin'       => $this->fechaFin,
            'ubicacion'       => $this->ubicacion,
            'tipos_aceptados' => $this->listaTiposAceptados(),
            'meta_equipos'    => $this->metaEquipos,
            'estado'          => $this->estado,
            'vigencia'        => $this->vigencia(),
        ];
    }
}
