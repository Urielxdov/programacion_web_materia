<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Models\Diagnostico;
use App\Models\Equipo;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\EquipoRepository;
use App\Strategies\EvaluacionTecnica;
use App\Strategies\Recomendacion;

/**
 * Diagnostico y decision de destino. El sistema recomienda (Strategy) y el responsable decide;
 * si se aparta de la recomendacion debe justificarlo.
 */
final class DiagnosticoService
{
    private const ETIQUETAS = [
        'descripcion'      => 'diagnóstico',
        'estado_funcional' => 'estado funcional',
        'costo_reparacion' => 'costo de reparación',
        'valor_reemplazo'  => 'valor de reemplazo',
        'riesgo_seguridad' => 'riesgo de seguridad',
        'decision'         => 'decisión',
        'justificacion'    => 'justificación',
        'fecha'            => 'fecha',
    ];

    private EquipoService $equipos;
    private EquipoRepository $equipoRepo;
    private DiagnosticoRepository $diagnosticos;
    private ConfiguracionService $configuracion;

    public function __construct(
        ?EquipoService $equipos = null,
        ?EquipoRepository $equipoRepo = null,
        ?DiagnosticoRepository $diagnosticos = null,
        ?ConfiguracionService $configuracion = null,
    ) {
        $this->equipos = $equipos ?? new EquipoService();
        $this->equipoRepo = $equipoRepo ?? new EquipoRepository();
        $this->diagnosticos = $diagnosticos ?? new DiagnosticoRepository();
        $this->configuracion = $configuracion ?? new ConfiguracionService();
    }

    private static function reglasEvaluacion(): array
    {
        return [
            'estado_funcional' => 'requerido|en:' . implode(',', array_keys(Diagnostico::ESTADOS_FUNCIONALES)),
            'costo_reparacion' => 'requerido|decimal|min:0|max:1000000',
            'valor_reemplazo'  => 'requerido|decimal|min:0|max:1000000',
            'riesgo_seguridad' => 'booleano',
        ];
    }

    /** Vista previa de la recomendacion (la usa la API mientras se llena el formulario). */
    public function recomendar(int $equipoId, array $entrada): Recomendacion
    {
        $equipo = $this->equipos->obtener($equipoId);
        $datos = Validator::validar($entrada, self::reglasEvaluacion(), self::ETIQUETAS);
        return $this->configuracion->clasificador()->recomendar($equipo, $this->evaluacion($datos));
    }

    public function registrar(int $equipoId, array $entrada, int $responsableId): Diagnostico
    {
        $equipo = $this->equipos->obtener($equipoId);
        if ($equipo->estaEntregado()) {
            throw new HttpException(409, 'El equipo ya fue entregado a un gestor; no admite nuevos diagnósticos.');
        }

        $reglas = self::reglasEvaluacion() + [
            'descripcion'   => 'requerido|texto:2000',
            'decision'      => 'requerido|en:' . implode(',', array_keys(Diagnostico::DECISIONES)),
            'justificacion' => 'opcional|texto:1000',
            'fecha'         => 'requerido|fecha|no_futura',
        ];
        $datos = Validator::validar($entrada, $reglas, self::ETIQUETAS);
        if ($datos['fecha'] < $equipo->fechaIngreso()) {
            throw ValidacionException::campo('fecha', 'La fecha del diagnóstico no puede ser anterior al ingreso del equipo.');
        }

        $recomendacion = $this->configuracion->clasificador()->recomendar($equipo, $this->evaluacion($datos));

        if ($datos['riesgo_seguridad'] && $datos['decision'] !== 'gestion_especializada') {
            throw ValidacionException::campo('decision', 'Un equipo con riesgo de seguridad solo puede enviarse a gestión especializada.');
        }
        if ($datos['decision'] !== $recomendacion->decision && $datos['justificacion'] === null) {
            throw ValidacionException::campo('justificacion', 'Explica por qué tu decisión es distinta a la recomendación del sistema.');
        }

        $diagnostico = new Diagnostico(
            null, $equipoId, $datos['descripcion'], $datos['estado_funcional'], $datos['costo_reparacion'],
            $datos['valor_reemplazo'], $datos['riesgo_seguridad'], $recomendacion->decision, $datos['decision'],
            $datos['justificacion'] ?? $recomendacion->justificacion, $datos['fecha'], $responsableId
        );

        Database::obtener()->transaccion(function () use ($diagnostico, $equipo): void {
            $this->diagnosticos->insertar($diagnostico);
            $equipo->asignarClasificacion($diagnostico->clasificacionResultante());
            $this->equipoRepo->actualizar($equipo);
        });

        return $diagnostico;
    }

    public function ultimo(int $equipoId): ?Diagnostico
    {
        return $this->diagnosticos->ultimoDeEquipo($equipoId);
    }

    private function evaluacion(array $datos): EvaluacionTecnica
    {
        return new EvaluacionTecnica(
            $datos['estado_funcional'],
            (float) $datos['costo_reparacion'],
            (float) $datos['valor_reemplazo'],
            (bool) $datos['riesgo_seguridad']
        );
    }

    /** Para el formulario: el equipo que se diagnostica. */
    public function equipo(int $id): Equipo
    {
        return $this->equipos->obtener($id);
    }
}
