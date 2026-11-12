<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\CampanaService;

/** Modulo 4 (parte publica): consulta de campanas y registro de participacion. */
final class CampanaPublicaController extends Controller
{
    private CampanaService $campanas;

    public function __construct()
    {
        $this->campanas = new CampanaService();
    }

    public function index(Request $request): Response
    {
        return $this->vista('campanas_publicas/index', [
            'titulo'   => 'Campañas de recolección',
            'campanas' => $this->campanas->publicas(),
        ], 'layouts/publico');
    }

    public function mostrar(Request $request, int $id): Response
    {
        return $this->vista('campanas_publicas/mostrar', [
            'titulo'  => 'Campaña',
            'campana' => $this->campanas->obtenerPublica($id),
            'usuario' => Auth::usuario(),
        ], 'layouts/publico');
    }

    public function participar(Request $request, int $id): Response
    {
        $this->campanas->participar($id, $request->todo(), Auth::id());
        return $this->redirigir("campanas/$id", '¡Gracias! Tu participación quedó registrada. Revisa las recomendaciones antes de llevar tus equipos.');
    }
}
