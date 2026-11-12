<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\MovimientoService;

/** Modulo 3: movimientos (reparacion, reasignacion, conservacion, entrega) y su validacion. */
final class MovimientoController extends Controller
{
    private MovimientoService $movimientos;

    public function __construct()
    {
        $this->movimientos = new MovimientoService();
    }

    public function index(Request $request): Response
    {
        return $this->vista('movimientos/index', [
            'titulo'     => 'Validación de movimientos',
            'pendientes' => $this->movimientos->pendientes(),
            'resueltos'  => $this->movimientos->recientesResueltos(),
        ]);
    }

    public function guardar(Request $request, int $id): Response
    {
        $movimiento = $this->movimientos->registrar($id, $request->todo(), $this->usuarioId(), Auth::puede('movimientos.validar'));
        $mensaje = $movimiento->estaPendiente()
            ? 'Movimiento registrado. Queda pendiente hasta que un responsable ambiental lo valide.'
            : 'Movimiento registrado y validado.';
        if ($movimiento->tipo === 'entrega_gestor' && !$movimiento->destinoVerificado) {
            $mensaje .= ' Atención: el gestor no está verificado, por lo que la entrega no cuenta como destino documentado.';
        }
        return $this->redirigir("panel/equipos/$id", $mensaje);
    }

    public function validar(Request $request, int $id): Response
    {
        $movimiento = $this->movimientos->validar($id, $this->usuarioId());
        return $this->redirigir('panel/movimientos', "Movimiento de {$movimiento->extra['equipo_codigo']} validado.");
    }

    public function rechazar(Request $request, int $id): Response
    {
        $movimiento = $this->movimientos->rechazar($id, $this->usuarioId(), (string) $request->input('motivo', ''));
        return $this->redirigir('panel/movimientos', "Movimiento de {$movimiento->extra['equipo_codigo']} rechazado.");
    }
}
