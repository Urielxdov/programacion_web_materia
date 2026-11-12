<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Participacion;

final class ParticipacionRepository extends Repositorio
{
    /** @return Participacion[] */
    public function porCampana(int $campanaId): array
    {
        return array_map(
            [Participacion::class, 'desdeFila'],
            $this->db->consultar('SELECT * FROM participaciones WHERE campana_id = ? ORDER BY fecha_registro DESC, id DESC', [$campanaId])
        );
    }

    public function insertar(Participacion $p): int
    {
        return $this->db->insertar(
            'INSERT INTO participaciones (campana_id, usuario_id, nombre, correo, equipos_comprometidos, tipos_equipo, asistio, fecha_registro)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$p->campanaId, $p->usuarioId, $p->nombre, $p->correo, $p->equiposComprometidos, $p->tiposEquipo,
             $p->asistio ? 1 : 0, $this->ahora()]
        );
    }

    /** Devuelve false si la participacion no pertenece a la campana. */
    public function marcarAsistencia(int $id, int $campanaId, bool $asistio): bool
    {
        $existe = (int) $this->db->valor('SELECT COUNT(*) FROM participaciones WHERE id = ? AND campana_id = ?', [$id, $campanaId]);
        if ($existe === 0) {
            return false;
        }
        $this->db->ejecutar('UPDATE participaciones SET asistio = ? WHERE id = ?', [$asistio ? 1 : 0, $id]);
        return true;
    }

    public function contarTotal(): int
    {
        return (int) $this->db->valor('SELECT COUNT(*) FROM participaciones');
    }
}
