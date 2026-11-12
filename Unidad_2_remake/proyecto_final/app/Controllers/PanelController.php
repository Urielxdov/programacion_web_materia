<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\EquipoService;
use App\Services\MovimientoService;

/** Tablero inicial del panel privado. Los indicadores se cargan con fetch desde la API. */
final class PanelController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->vista('panel/index', [
            'titulo'     => 'Tablero',
            'graficas'   => true,
            'recientes'  => (new EquipoService())->recientes(6),
            'pendientes' => Auth::puede('movimientos.validar') ? array_slice((new MovimientoService())->pendientes(), 0, 5) : [],
        ]);
    }
}
