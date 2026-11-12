<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Catalogo de categorias. El "tipo" decide que subclase de Equipo crea la EquipoFactory.
 */
final class Categoria
{
    public const TIPOS = [
        'computadora' => 'Computadora de escritorio',
        'laptop'      => 'Laptop',
        'monitor'     => 'Monitor / pantalla',
        'impresora'   => 'Impresora / escáner',
        'celular'     => 'Celular / tableta',
        'bateria'     => 'Batería / pila',
        'periferico'  => 'Periférico (teclado, ratón, bocina)',
        'accesorio'   => 'Cable / cargador / accesorio',
        'otro'        => 'Otro dispositivo',
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $tipo,
        public readonly int $vidaUtilAnios,
        public readonly ?string $descripcion = null,
        public readonly bool $activo = true,
    ) {
        if (!isset(self::TIPOS[$tipo])) {
            throw new \InvalidArgumentException("Tipo de categoría no válido: $tipo");
        }
        if ($vidaUtilAnios < 1) {
            throw new \InvalidArgumentException('La vida útil debe ser de al menos 1 año.');
        }
    }

    /** @param string $prefijo prefijo de columnas cuando viene de un JOIN (cat_) */
    public static function desdeFila(array $f, string $prefijo = ''): self
    {
        return new self(
            (int) $f[$prefijo . 'id'],
            $f[$prefijo . 'nombre'],
            $f[$prefijo . 'tipo'],
            (int) $f[$prefijo . 'vida_util_anios'],
            $f[$prefijo . 'descripcion'] ?? null,
            (bool) ($f[$prefijo . 'activo'] ?? true),
        );
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo];
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'tipo'            => $this->tipo,
            'vida_util_anios' => $this->vidaUtilAnios,
        ];
    }
}
