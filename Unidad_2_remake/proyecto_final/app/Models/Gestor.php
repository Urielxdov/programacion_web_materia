<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Empresa o programa que recibe residuos. Solo los gestores "verificados" (autorizacion
 * revisada por el administrador) cuentan como destino documentado en los indicadores.
 */
final class Gestor
{
    public const TIPOS = [
        'reciclador'       => 'Reciclador',
        'reacondicionador' => 'Reacondicionador',
        'fabricante'       => 'Programa del fabricante',
        'acopio'           => 'Centro de acopio',
        'otro'             => 'Otro',
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $tipo,
        public readonly ?string $contacto,
        public readonly ?string $direccion,
        public readonly ?string $numeroAutorizacion,
        public readonly bool $verificado,
        public readonly bool $activo = true,
    ) {
        if (!isset(self::TIPOS[$tipo])) {
            throw new \InvalidArgumentException("Tipo de gestor no válido: $tipo");
        }
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id'],
            $f['nombre'],
            $f['tipo'],
            $f['contacto'],
            $f['direccion'],
            $f['numero_autorizacion'],
            (bool) $f['verificado'],
            (bool) $f['activo'],
        );
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo];
    }
}
