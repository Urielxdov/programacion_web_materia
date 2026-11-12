<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Movimiento;

final class MovimientoRepository extends Repositorio
{
    private const SELECT = 'SELECT m.*, e.codigo AS equipo_codigo, e.marca AS equipo_marca, e.modelo AS equipo_modelo,
            g.nombre AS gestor_nombre, ur.nombre AS registrado_nombre, uv.nombre AS validado_nombre
        FROM movimientos m
        JOIN equipos e ON e.id = m.equipo_id
        LEFT JOIN gestores g ON g.id = m.gestor_id
        JOIN usuarios ur ON ur.id = m.registrado_por
        LEFT JOIN usuarios uv ON uv.id = m.validado_por';

    /** @return Movimiento[] Historial del equipo, del mas reciente al mas antiguo. */
    public function porEquipo(int $equipoId): array
    {
        return $this->mapear($this->db->consultar(self::SELECT . ' WHERE m.equipo_id = ? ORDER BY m.fecha DESC, m.id DESC', [$equipoId]));
    }

    public function porId(int $id): ?Movimiento
    {
        $filas = $this->mapear($this->db->consultar(self::SELECT . ' WHERE m.id = ?', [$id]));
        return $filas[0] ?? null;
    }

    /** @return Movimiento[] */
    public function pendientes(): array
    {
        return $this->mapear($this->db->consultar(self::SELECT . " WHERE m.estado_validacion = 'pendiente' ORDER BY m.fecha, m.id"));
    }

    /** @return Movimiento[] */
    public function recientesResueltos(int $limite = 15): array
    {
        return $this->mapear($this->db->consultar(
            self::SELECT . " WHERE m.estado_validacion <> 'pendiente' AND m.tipo <> 'ingreso' ORDER BY m.validado_en DESC, m.id DESC LIMIT ?",
            [$limite]
        ));
    }

    public function tienePendiente(int $equipoId): bool
    {
        return (int) $this->db->valor(
            "SELECT COUNT(*) FROM movimientos WHERE equipo_id = ? AND estado_validacion = 'pendiente'",
            [$equipoId]
        ) > 0;
    }

    public function contarPendientes(): int
    {
        return (int) $this->db->valor("SELECT COUNT(*) FROM movimientos WHERE estado_validacion = 'pendiente'");
    }

    public function insertar(Movimiento $m): int
    {
        return $this->db->insertar(
            'INSERT INTO movimientos (equipo_id, tipo, fecha, destino, gestor_id, costo, ahorro_estimado, evidencia, datos_borrados,
                destino_verificado, estado_validacion, observaciones, registrado_por, validado_por, validado_en, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$m->equipoId, $m->tipo, $m->fecha, $m->destino, $m->gestorId,
             $m->costo === null ? null : number_format($m->costo, 2, '.', ''),
             number_format($m->ahorroEstimado, 2, '.', ''), $m->evidencia, $m->datosBorrados ? 1 : 0,
             $m->destinoVerificado ? 1 : 0, $m->estadoValidacion, $m->observaciones, $m->registradoPor,
             $m->validadoPor, $m->validadoEn, $this->ahora()]
        );
    }

    public function resolver(int $id, string $estado, int $validadoPor, float $ahorro, ?string $observaciones = null): void
    {
        $this->db->ejecutar(
            'UPDATE movimientos SET estado_validacion = ?, validado_por = ?, validado_en = ?, ahorro_estimado = ?,
                observaciones = COALESCE(?, observaciones)
             WHERE id = ? AND estado_validacion = ?',
            [$estado, $validadoPor, $this->ahora(), number_format($ahorro, 2, '.', ''), $observaciones, $id, 'pendiente']
        );
    }

    /** @return Movimiento[] */
    private function mapear(array $filas): array
    {
        return array_map(static function (array $f): Movimiento {
            $f['equipo_desc'] = trim(($f['equipo_marca'] ?? '') . ' ' . ($f['equipo_modelo'] ?? ''));
            return Movimiento::desdeFila($f);
        }, $filas);
    }
}
