<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CampanaService;
use App\Services\IndicadorService;

/** Modulo 1: sitio publico de concientizacion. */
final class InicioController extends Controller
{
    public function index(Request $request): Response
    {
        $campanas = array_filter((new CampanaService())->publicas(), static fn ($c): bool => $c->estado === 'activa');
        return $this->vista('inicio/index', [
            'titulo'      => 'Inicio',
            'cifras'      => (new IndicadorService())->publicos(),
            'campanas'    => array_slice($campanas, 0, 3),
        ], 'layouts/publico');
    }

    public function informacion(Request $request): Response
    {
        return $this->vista('inicio/informacion', ['titulo' => 'Aprende y actúa'], 'layouts/publico');
    }
}
