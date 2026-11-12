<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Diagnostico;

final class DiagnosticoRepository extends Repositorio
{
    private const SELECT = 'SELECT d.*, u.nombre AS responsable_nombre
        FROM diagnosticos d JOIN usuarios u ON u.id = d.responsable_id';

    /** @return Diagnostico[] Del mas reciente al mas antiguo. */
    public function porEquipo(int $equipoId): array
    {
        return array_map(
            [Diagnostico::class, 'desdeFila'],
            $this->db->consultar(self::SELECT . ' WHERE d.equipo_id = ? ORDER BY d.fecha DESC, d.id DESC', [$equipoId])
        );
    }

    public function ultimoDeEquipo(int $equipoId): ?Diagnostico
    {
        $fila = $this->db->uno(self::SELECT . ' WHERE d.equipo_id = ? ORDER BY d.fecha DESC, d.id DESC LIMIT 1', [$equipoId]);
        return $fila ? Diagnostico::desdeFila($fila) : null;
    }

    public function insertar(Diagnostico $d): int
    {
        return $this->db->insertar(
            'INSERT INTO diagnosticos (equipo_id, descripcion, estado_funcional, costo_reparacion, valor_reemplazo, riesgo_seguridad,
                recomendacion_sistema, decision, justificacion, fecha, responsable_id, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d->equipoId, $d->descripcion, $d->estadoFuncional, number_format($d->costoReparacion, 2, '.', ''),
             number_format($d->valorReemplazo, 2, '.', ''), $d->riesgoSeguridad ? 1 : 0, $d->recomendacionSistema,
             $d->decision, $d->justificacion, $d->fecha, $d->responsableId, $this->ahora()]
        );
    }
}
