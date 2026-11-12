<?php
declare(strict_types=1);

namespace App\Repositories;

/**
 * Consultas de solo lectura para indicadores y reportes (agregaciones).
 * Usa SQL estandar (COUNT, SUM, COALESCE, EXTRACT) valido en MySQL y PostgreSQL.
 */
final class IndicadorRepository extends Repositorio
{
    public function totalEquipos(): int
    {
        return (int) $this->db->valor('SELECT COUNT(*) FROM equipos');
    }

    /** @return array<string, int> */
    public function conteoPor(string $columna): array
    {
        if (!in_array($columna, ['clasificacion', 'estado', 'condicion'], true)) {
            throw new \InvalidArgumentException("Columna no permitida: $columna");
        }
        $conteo = [];
        foreach ($this->db->consultar("SELECT $columna AS clave, COUNT(*) AS total FROM equipos GROUP BY $columna") as $f) {
            $conteo[$f['clave']] = (int) $f['total'];
        }
        return $conteo;
    }

    public function porCategoria(): array
    {
        return array_map(static fn (array $f): array => ['categoria' => $f['nombre'], 'total' => (int) $f['total']],
            $this->db->consultar(
                'SELECT c.nombre, COUNT(e.id) AS total
                 FROM categorias c LEFT JOIN equipos e ON e.categoria_id = c.id
                 GROUP BY c.id, c.nombre
                 HAVING COUNT(e.id) > 0
                 ORDER BY total DESC, c.nombre'
            ));
    }

    /** Movimientos validados agrupados por tipo: equipos distintos, costo y ahorro. */
    public function movimientosValidadosPorTipo(): array
    {
        $resultado = [];
        foreach ($this->db->consultar(
            "SELECT tipo, COUNT(*) AS total, COUNT(DISTINCT equipo_id) AS equipos,
                    COALESCE(SUM(costo), 0) AS costo, COALESCE(SUM(ahorro_estimado), 0) AS ahorro
             FROM movimientos WHERE estado_validacion = 'validado' GROUP BY tipo"
        ) as $f) {
            $resultado[$f['tipo']] = [
                'total'   => (int) $f['total'],
                'equipos' => (int) $f['equipos'],
                'costo'   => (float) $f['costo'],
                'ahorro'  => (float) $f['ahorro'],
            ];
        }
        return $resultado;
    }

    /** Entregas a gestores validadas, separando destino verificado y no verificado. */
    public function entregasGestor(): array
    {
        $datos = ['verificadas' => 0, 'no_verificadas' => 0, 'kg_verificados' => 0.0, 'kg_no_verificados' => 0.0];
        foreach ($this->db->consultar(
            "SELECT m.destino_verificado, COUNT(*) AS total, COALESCE(SUM(e.peso_kg), 0) AS kg
             FROM movimientos m JOIN equipos e ON e.id = m.equipo_id
             WHERE m.tipo = 'entrega_gestor' AND m.estado_validacion = 'validado'
             GROUP BY m.destino_verificado"
        ) as $f) {
            if ((int) $f['destino_verificado'] === 1) {
                $datos['verificadas'] = (int) $f['total'];
                $datos['kg_verificados'] = (float) $f['kg'];
            } else {
                $datos['no_verificadas'] = (int) $f['total'];
                $datos['kg_no_verificados'] = (float) $f['kg'];
            }
        }
        return $datos;
    }

    /** Equipos ingresados por mes desde $desde (AAAA-MM-DD). */
    public function ingresosPorMes(string $desde): array
    {
        return $this->porMes(
            'SELECT EXTRACT(YEAR FROM fecha_ingreso) AS anio, EXTRACT(MONTH FROM fecha_ingreso) AS mes, COUNT(*) AS total
             FROM equipos WHERE fecha_ingreso >= ?
             GROUP BY EXTRACT(YEAR FROM fecha_ingreso), EXTRACT(MONTH FROM fecha_ingreso)',
            [$desde]
        );
    }

    /** Movimientos validados de un tipo por mes. */
    public function movimientosPorMes(string $tipo, string $desde): array
    {
        return $this->porMes(
            "SELECT EXTRACT(YEAR FROM fecha) AS anio, EXTRACT(MONTH FROM fecha) AS mes, COUNT(*) AS total
             FROM movimientos WHERE tipo = ? AND estado_validacion = 'validado' AND fecha >= ?
             GROUP BY EXTRACT(YEAR FROM fecha), EXTRACT(MONTH FROM fecha)",
            [$tipo, $desde]
        );
    }

    /** Reparados y reutilizados por categoria. */
    public function aprovechamientoPorCategoria(): array
    {
        return array_map(static fn (array $f): array => [
            'categoria'   => $f['nombre'],
            'reparados'   => (int) $f['reparados'],
            'reutilizados' => (int) $f['reutilizados'],
        ], $this->db->consultar(
            "SELECT c.nombre,
                    COUNT(DISTINCT CASE WHEN m.tipo = 'reparacion' THEN m.equipo_id END) AS reparados,
                    COUNT(DISTINCT CASE WHEN m.tipo = 'reasignacion' THEN m.equipo_id END) AS reutilizados
             FROM movimientos m
             JOIN equipos e ON e.id = m.equipo_id
             JOIN categorias c ON c.id = e.categoria_id
             WHERE m.estado_validacion = 'validado' AND m.tipo IN ('reparacion', 'reasignacion')
             GROUP BY c.id, c.nombre
             ORDER BY c.nombre"
        ));
    }

    /** Resultados por campana (no incluye borradores). */
    public function resultadosCampanas(): array
    {
        return array_map(static fn (array $f): array => [
            'id'           => (int) $f['id'],
            'nombre'       => $f['nombre'],
            'estado'       => $f['estado'],
            'fecha_inicio' => substr((string) $f['fecha_inicio'], 0, 10),
            'fecha_fin'    => substr((string) $f['fecha_fin'], 0, 10),
            'meta_equipos' => $f['meta_equipos'] !== null ? (int) $f['meta_equipos'] : null,
            'participantes' => (int) $f['participantes'],
            'asistentes'   => (int) $f['asistentes'],
            'comprometidos' => (int) $f['comprometidos'],
            'recibidos'    => (int) $f['recibidos'],
            'kg_recibidos' => (float) $f['kg'],
        ], $this->db->consultar(
            "SELECT c.id, c.nombre, c.estado, c.fecha_inicio, c.fecha_fin, c.meta_equipos,
                    (SELECT COUNT(*) FROM participaciones p WHERE p.campana_id = c.id) AS participantes,
                    (SELECT COUNT(*) FROM participaciones p WHERE p.campana_id = c.id AND p.asistio = 1) AS asistentes,
                    (SELECT COALESCE(SUM(p.equipos_comprometidos), 0) FROM participaciones p WHERE p.campana_id = c.id) AS comprometidos,
                    (SELECT COUNT(*) FROM equipos e WHERE e.campana_id = c.id) AS recibidos,
                    (SELECT COALESCE(SUM(e.peso_kg), 0) FROM equipos e WHERE e.campana_id = c.id) AS kg
             FROM campanas c
             WHERE c.estado <> 'borrador'
             ORDER BY c.fecha_inicio DESC"
        ));
    }

    public function pendientesDiagnostico(): int
    {
        return (int) $this->db->valor(
            "SELECT COUNT(*) FROM equipos WHERE clasificacion = 'pendiente' AND estado <> 'entregado_gestor'"
        );
    }

    /** @return array<string, int> ['2026-05' => 3, ...] */
    private function porMes(string $sql, array $parametros): array
    {
        $resultado = [];
        foreach ($this->db->consultar($sql, $parametros) as $f) {
            $resultado[sprintf('%04d-%02d', (int) $f['anio'], (int) $f['mes'])] = (int) $f['total'];
        }
        return $resultado;
    }
}
