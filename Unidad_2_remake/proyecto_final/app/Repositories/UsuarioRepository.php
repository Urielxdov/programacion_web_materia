<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Usuario;

final class UsuarioRepository extends Repositorio
{
    public function porId(int $id): ?Usuario
    {
        $fila = $this->db->uno('SELECT * FROM usuarios WHERE id = ?', [$id]);
        return $fila ? Usuario::desdeFila($fila) : null;
    }

    public function porUsuario(string $usuario): ?Usuario
    {
        $fila = $this->db->uno('SELECT * FROM usuarios WHERE LOWER(usuario) = LOWER(?)', [$usuario]);
        return $fila ? Usuario::desdeFila($fila) : null;
    }

    /** @return Usuario[] */
    public function todos(): array
    {
        return array_map(
            [Usuario::class, 'desdeFila'],
            $this->db->consultar('SELECT * FROM usuarios ORDER BY activo DESC, nombre')
        );
    }

    public function insertar(Usuario $u): int
    {
        return $this->db->insertar(
            'INSERT INTO usuarios (nombre, usuario, email, password_hash, rol, activo, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$u->nombre, $u->usuario, $u->email, $u->passwordHash(), $u->rol, $u->activo ? 1 : 0, $this->ahora()]
        );
    }

    public function actualizarPerfil(int $id, string $nombre, ?string $email, string $rol, bool $activo): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?',
            [$nombre, $email, $rol, $activo ? 1 : 0, $id]
        );
    }

    public function cambiarPassword(int $id, string $hash): void
    {
        $this->db->ejecutar('UPDATE usuarios SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public function registrarIntentoFallido(int $id, int $intentos, ?string $bloqueadoHasta): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?',
            [$intentos, $bloqueadoHasta, $id]
        );
    }

    public function registrarAcceso(int $id): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = ? WHERE id = ?',
            [$this->ahora(), $id]
        );
    }

    public function existeUsuario(string $usuario, ?int $exceptoId = null): bool
    {
        return (int) $this->db->valor(
            'SELECT COUNT(*) FROM usuarios WHERE LOWER(usuario) = LOWER(?) AND id <> ?',
            [$usuario, $exceptoId ?? 0]
        ) > 0;
    }

    public function existeEmail(string $email, ?int $exceptoId = null): bool
    {
        return (int) $this->db->valor(
            'SELECT COUNT(*) FROM usuarios WHERE LOWER(email) = LOWER(?) AND id <> ?',
            [$email, $exceptoId ?? 0]
        ) > 0;
    }

    public function contarAdminsActivos(): int
    {
        return (int) $this->db->valor("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1");
    }
}
