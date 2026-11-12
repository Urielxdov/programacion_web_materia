<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Models\Movimiento;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\EquipoRepository;
use App\Repositories\GestorRepository;
use App\Repositories\MovimientoRepository;

/**
 * Trazabilidad del destino de cada equipo. Un movimiento registrado por un capturista queda
 * pendiente; al validarlo el responsable se aplica al equipo y se calcula el ahorro.
 */
final class MovimientoService
{
    private const ETIQUETAS = [
        'tipo'           => 'tipo de movimiento',
        'fecha'          => 'fecha',
        'destino'        => 'destino',
        'gestor_id'      => 'gestor',
        'costo'          => 'costo',
        'evidencia'      => 'evidencia',
        'datos_borrados' => 'borrado de datos',
        'observaciones'  => 'observaciones',
    ];

    private EquipoService $equipos;
    private EquipoRepository $equipoRepo;
    private MovimientoRepository $movimientos;
    private GestorRepository $gestores;
    private DiagnosticoRepository $diagnosticos;

    public function __construct(
        ?EquipoService $equipos = null,
        ?EquipoRepository $equipoRepo = null,
        ?MovimientoRepository $movimientos = null,
        ?GestorRepository $gestores = null,
        ?DiagnosticoRepository $diagnosticos = null,
    ) {
        $this->equipos = $equipos ?? new EquipoService();
        $this->equipoRepo = $equipoRepo ?? new EquipoRepository();
        $this->movimientos = $movimientos ?? new MovimientoRepository();
        $this->gestores = $gestores ?? new GestorRepository();
        $this->diagnosticos = $diagnosticos ?? new DiagnosticoRepository();
    }

    public function registrar(int $equipoId, array $entrada, int $usuarioId, bool $puedeValidar): Movimiento
    {
        $equipo = $this->equipos->obtener($equipoId);
        $datos = Validator::validar($entrada, [
            'tipo'           => 'requerido|en:' . implode(',', Movimiento::TIPOS_REGISTRABLES),
            'fecha'          => 'requerido|fecha|no_futura',
            'destino'        => 'opcional|texto:150',
            'gestor_id'      => 'opcional|entero',
            'costo'          => 'opcional|decimal|min:0|max:1000000',
            'evidencia'      => 'opcional|texto:255',
            'datos_borrados' => 'booleano',
            'observaciones'  => 'opcional|texto:1000',
        ], self::ETIQUETAS);

        $tipo = $datos['tipo'];
        if (!$equipo->puedeRecibir($tipo)) {
            throw ValidacionException::campo('tipo', sprintf(
                'Un equipo «%s» con clasificación «%s» no puede recibir «%s».',
                $equipo->nombreEstado(), $equipo->nombreClasificacion(), Movimiento::TIPOS[$tipo]
            ));
        }
        if ($datos['fecha'] < $equipo->fechaIngreso()) {
            throw ValidacionException::campo('fecha', 'La fecha no puede ser anterior al ingreso del equipo.');
        }
        if ($this->movimientos->tienePendiente($equipoId)) {
            throw new HttpException(409, 'Este equipo ya tiene un movimiento pendiente de validar.');
        }

        $verificado = false;
        $gestorId = null;
        switch ($tipo) {
            case 'reparacion':
                if ($datos['costo'] === null) {
                    throw ValidacionException::campo('costo', 'Registra el costo real de la reparación (0 si no tuvo costo).');
                }
                break;
            case 'reasignacion':
                if ($datos['destino'] === null) {
                    throw ValidacionException::campo('destino', 'Indica el laboratorio, departamento o usuario que recibe el equipo.');
                }
                break;
            case 'entrega_gestor':
                $gestor = $datos['gestor_id'] !== null ? $this->gestores->porId($datos['gestor_id']) : null;
                if ($gestor === null || !$gestor->activo) {
                    throw ValidacionException::campo('gestor_id', 'Selecciona el gestor que recibe el equipo.');
                }
                if ($datos['evidencia'] === null) {
                    throw ValidacionException::campo('evidencia', 'Registra el folio o manifiesto de entrega como evidencia.');
                }
                $gestorId = $gestor->id;
                // Solo se documenta como destino verificado si el gestor tiene autorizacion revisada
                $verificado = $gestor->verificado;
                $datos['destino'] ??= $gestor->nombre;
                break;
        }

        if (in_array($tipo, ['reasignacion', 'entrega_gestor'], true) && $equipo->requiereBorradoDatos() && !$datos['datos_borrados']) {
            throw ValidacionException::campo('datos_borrados', 'Este equipo almacena información: confirma que se realizó el borrado seguro de datos.');
        }

        $movimiento = new Movimiento(
            null, $equipoId, $tipo, $datos['fecha'], $datos['destino'], $gestorId,
            $datos['costo'], 0.0, $datos['evidencia'],
            (bool) $datos['datos_borrados'], $verificado, 'pendiente', $datos['observaciones'], $usuarioId
        );

        return Database::obtener()->transaccion(function () use ($movimiento, $usuarioId, $puedeValidar): Movimiento {
            $id = $this->movimientos->insertar($movimiento);
            return $puedeValidar ? $this->validar($id, $usuarioId) : $this->obtener($id);
        });
    }

    public function validar(int $id, int $validadorId): Movimiento
    {
        return Database::obtener()->transaccion(function () use ($id, $validadorId): Movimiento {
            $movimiento = $this->pendiente($id);
            $equipo = $this->equipos->obtener($movimiento->equipoId);
            try {
                $equipo->aplicarMovimiento($movimiento->tipo);
            } catch (\DomainException $e) {
                throw new HttpException(409, $e->getMessage());
            }
            $ahorro = $this->calcularAhorro($movimiento);
            $this->equipoRepo->actualizar($equipo);
            $this->movimientos->resolver($id, 'validado', $validadorId, $ahorro);
            return $this->obtener($id);
        });
    }

    public function rechazar(int $id, int $validadorId, ?string $motivo): Movimiento
    {
        $motivo = trim((string) $motivo);
        if ($motivo === '') {
            throw ValidacionException::campo('motivo', 'Escribe el motivo del rechazo.');
        }
        $this->pendiente($id);
        $this->movimientos->resolver($id, 'rechazado', $validadorId, 0.0, 'Rechazado: ' . mb_substr($motivo, 0, 900));
        return $this->obtener($id);
    }

    /** @return Movimiento[] */
    public function pendientes(): array
    {
        return $this->movimientos->pendientes();
    }

    /** @return Movimiento[] */
    public function recientesResueltos(): array
    {
        return $this->movimientos->recientesResueltos();
    }

    public function obtener(int $id): Movimiento
    {
        return $this->movimientos->porId($id) ?? throw new HttpException(404, 'El movimiento no existe.');
    }

    private function pendiente(int $id): Movimiento
    {
        $movimiento = $this->obtener($id);
        if (!$movimiento->estaPendiente()) {
            throw new HttpException(409, 'Este movimiento ya fue ' . mb_strtolower($movimiento->nombreEstado()) . '.');
        }
        return $movimiento;
    }

    /**
     * Ahorro = valor de un equipo nuevo (ultimo diagnostico) - lo que costo aprovecharlo.
     * Sin diagnostico no se estima ahorro (no se inventan cifras).
     */
    private function calcularAhorro(Movimiento $m): float
    {
        if (!in_array($m->tipo, ['reparacion', 'reasignacion'], true)) {
            return 0.0;
        }
        $diagnostico = $this->diagnosticos->ultimoDeEquipo($m->equipoId);
        if ($diagnostico === null) {
            return 0.0;
        }
        return max(0.0, $diagnostico->valorReemplazo - (float) ($m->costo ?? 0));
    }
}
