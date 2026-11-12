<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Equipo;
use App\Repositories\CampanaRepository;
use App\Repositories\IndicadorRepository;
use App\Repositories\MovimientoRepository;
use App\Repositories\ParticipacionRepository;

/**
 * Indicadores ambientales, sociales y economicos (Modulo 5). Alimenta la API de indicadores,
 * el tablero y la pagina publica. Solo cuenta movimientos VALIDADOS y distingue las entregas
 * a gestores verificados de las no verificadas para no sobrestimar resultados.
 */
final class IndicadorService
{
    private IndicadorRepository $repo;

    public function __construct(?IndicadorRepository $repo = null)
    {
        $this->repo = $repo ?? new IndicadorRepository();
    }

    public function generales(): array
    {
        $total = $this->repo->totalEquipos();
        $movs = $this->repo->movimientosValidadosPorTipo();
        $entregas = $this->repo->entregasGestor();
        $reparados = $movs['reparacion']['equipos'] ?? 0;
        $reutilizados = $movs['reasignacion']['equipos'] ?? 0;

        return [
            'equipos' => [
                'total'             => $total,
                'por_clasificacion' => $this->conEtiquetas($this->repo->conteoPor('clasificacion'), Equipo::CLASIFICACIONES),
                'por_estado'        => $this->conEtiquetas($this->repo->conteoPor('estado'), Equipo::ESTADOS),
                'por_categoria'     => $this->repo->porCategoria(),
            ],
            'reparados'            => $reparados,
            'reutilizados'         => $reutilizados,
            'conservados'          => $movs['conservacion']['equipos'] ?? 0,
            'tasa_aprovechamiento' => $total > 0 ? round(($reparados + $reutilizados) / $total * 100, 1) : 0.0,
            'entregas_gestor'      => $entregas + ['total' => $entregas['verificadas'] + $entregas['no_verificadas']],
            'costos'               => [
                'reparacion'      => round($movs['reparacion']['costo'] ?? 0, 2),
                'ahorro_estimado' => round(($movs['reparacion']['ahorro'] ?? 0) + ($movs['reasignacion']['ahorro'] ?? 0), 2),
            ],
            'campanas' => [
                'activas'         => (new CampanaRepository())->contarActivas(),
                'participaciones' => (new ParticipacionRepository())->contarTotal(),
            ],
            'pendientes' => [
                'diagnostico' => $this->repo->pendientesDiagnostico(),
                'movimientos' => (new MovimientoRepository())->contarPendientes(),
            ],
            'ingresos_por_mes' => $this->serieMensual($this->repo->ingresosPorMes($this->hace12Meses())),
        ];
    }

    public function campanas(): array
    {
        $campanas = $this->repo->resultadosCampanas();
        $totales = ['participantes' => 0, 'asistentes' => 0, 'comprometidos' => 0, 'recibidos' => 0, 'kg_recibidos' => 0.0];
        foreach ($campanas as $c) {
            foreach ($totales as $clave => $valor) {
                $totales[$clave] = $valor + $c[$clave];
            }
        }
        return ['campanas' => $campanas, 'totales' => $totales];
    }

    public function reutilizacion(): array
    {
        $movs = $this->repo->movimientosValidadosPorTipo();
        $total = $this->repo->totalEquipos();
        $reparados = $movs['reparacion']['equipos'] ?? 0;
        $reutilizados = $movs['reasignacion']['equipos'] ?? 0;
        $reparaciones = $movs['reparacion']['total'] ?? 0;
        $desde = $this->hace12Meses();
        $repMes = $this->repo->movimientosPorMes('reparacion', $desde);
        $reaMes = $this->repo->movimientosPorMes('reasignacion', $desde);

        $porMes = [];
        foreach ($this->mesesRecientes() as $mes) {
            $porMes[] = ['periodo' => $mes, 'reparaciones' => $repMes[$mes] ?? 0, 'reasignaciones' => $reaMes[$mes] ?? 0];
        }

        return [
            'reparados'                => $reparados,
            'reutilizados'             => $reutilizados,
            'conservados'              => $movs['conservacion']['equipos'] ?? 0,
            'tasa_aprovechamiento'     => $total > 0 ? round(($reparados + $reutilizados) / $total * 100, 1) : 0.0,
            'costo_reparacion_total'   => round($movs['reparacion']['costo'] ?? 0, 2),
            'costo_promedio_reparacion' => $reparaciones > 0 ? round(($movs['reparacion']['costo'] ?? 0) / $reparaciones, 2) : 0.0,
            'ahorro_estimado_total'    => round(($movs['reparacion']['ahorro'] ?? 0) + ($movs['reasignacion']['ahorro'] ?? 0), 2),
            'por_mes'                  => $porMes,
            'por_categoria'            => $this->repo->aprovechamientoPorCategoria(),
        ];
    }

    /** Cifras para la pagina publica (sin datos personales). */
    public function publicos(): array
    {
        $movs = $this->repo->movimientosValidadosPorTipo();
        $entregas = $this->repo->entregasGestor();
        return [
            'aprovechados'   => ($movs['reparacion']['equipos'] ?? 0) + ($movs['reasignacion']['equipos'] ?? 0),
            'kg_gestionados' => $entregas['kg_verificados'],
            'equipos'        => $this->repo->totalEquipos(),
        ];
    }

    private function conEtiquetas(array $conteo, array $etiquetas): array
    {
        $resultado = [];
        foreach ($etiquetas as $clave => $etiqueta) {
            $resultado[$clave] = ['etiqueta' => $etiqueta, 'total' => $conteo[$clave] ?? 0];
        }
        return $resultado;
    }

    private function serieMensual(array $datos): array
    {
        return array_map(static fn (string $mes): array => ['periodo' => $mes, 'total' => $datos[$mes] ?? 0], $this->mesesRecientes());
    }

    /** @return string[] Ultimos 12 meses en formato AAAA-MM. */
    private function mesesRecientes(): array
    {
        $meses = [];
        $inicio = new \DateTimeImmutable('first day of this month');
        for ($i = 11; $i >= 0; $i--) {
            $meses[] = $inicio->modify("-$i months")->format('Y-m');
        }
        return $meses;
    }

    private function hace12Meses(): string
    {
        return (new \DateTimeImmutable('first day of this month'))->modify('-11 months')->format('Y-m-d');
    }
}
