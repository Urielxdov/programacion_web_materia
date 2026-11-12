<?php
declare(strict_types=1);

namespace App\Models;

/** Cuenta con acceso al panel. La contrasena solo existe como hash (password_hash/bcrypt). */
final class Usuario
{
    public const ROLES = [
        'capturista'  => 'Capturista',
        'responsable' => 'Responsable ambiental',
        'admin'       => 'Administrador',
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $usuario,
        public readonly ?string $email,
        private string $passwordHash,
        public readonly string $rol,
        public readonly bool $activo = true,
        public readonly int $intentosFallidos = 0,
        public readonly ?string $bloqueadoHasta = null,
        public readonly ?string $ultimoAcceso = null,
        public readonly ?string $creadoEn = null,
    ) {
        if (!isset(self::ROLES[$rol])) {
            throw new \InvalidArgumentException("Rol no válido: $rol");
        }
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id'],
            $f['nombre'],
            $f['usuario'],
            $f['email'],
            $f['password_hash'],
            $f['rol'],
            (bool) $f['activo'],
            (int) $f['intentos_fallidos'],
            $f['bloqueado_hasta'],
            $f['ultimo_acceso'],
            $f['creado_en'],
        );
    }

    public static function hashear(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verificarPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function necesitaRehash(): bool
    {
        return password_needs_rehash($this->passwordHash, PASSWORD_DEFAULT);
    }

    /** Solo el repositorio necesita el hash para guardarlo. */
    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function estaBloqueado(?\DateTimeImmutable $ahora = null): bool
    {
        if ($this->bloqueadoHasta === null) {
            return false;
        }
        return new \DateTimeImmutable($this->bloqueadoHasta) > ($ahora ?? new \DateTimeImmutable());
    }

    public function nombreRol(): string
    {
        return self::ROLES[$this->rol];
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /** Nunca incluye el hash de la contrasena. */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'nombre'        => $this->nombre,
            'usuario'       => $this->usuario,
            'email'         => $this->email,
            'rol'           => $this->rol,
            'rol_texto'     => $this->nombreRol(),
            'activo'        => $this->activo,
            'ultimo_acceso' => $this->ultimoAcceso,
        ];
    }
}
