<?php
declare(strict_types=1);

namespace App\Models;

/** Registro de una persona en una campana (datos personales minimos: nombre y correo opcional). */
final class Participacion
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $campanaId,
        public readonly ?int $usuarioId,
        public readonly string $nombre,
        public readonly ?string $correo,
        public readonly int $equiposComprometidos,
        public readonly ?string $tiposEquipo,
        public readonly bool $asistio = false,
        public readonly ?string $fechaRegistro = null,
    ) {
        if ($equiposComprometidos < 1) {
            throw new \InvalidArgumentException('Debe comprometerse al menos un equipo.');
        }
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id'],
            (int) $f['campana_id'],
            $f['usuario_id'] !== null ? (int) $f['usuario_id'] : null,
            $f['nombre'],
            $f['correo'],
            (int) $f['equipos_comprometidos'],
            $f['tipos_equipo'],
            (bool) $f['asistio'],
            $f['fecha_registro'],
        );
    }

    /** Correo parcialmente oculto para listados (proteccion de datos). */
    public function correoOculto(): ?string
    {
        if ($this->correo === null) {
            return null;
        }
        [$local, $dominio] = array_pad(explode('@', $this->correo, 2), 2, '');
        return mb_substr($local, 0, 2) . str_repeat('•', max(1, mb_strlen($local) - 2)) . '@' . $dominio;
    }
}
