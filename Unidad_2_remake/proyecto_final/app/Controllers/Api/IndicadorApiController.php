<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\IndicadorService;

/**
 * Servicio web 2: API de indicadores (JSON, solo lectura).
 *   GET /api/indicadores                  totales por clasificacion, estado y categoria
 *   GET /api/indicadores/campanas         resultados de las campanas
 *   GET /api/indicadores/reutilizacion    metricas de reparacion y reutilizacion
 */
final class IndicadorApiController extends Controller
{
    private IndicadorService $indicadores;

    public function __construct()
    {
        $this->indicadores = new IndicadorService();
    }

    public function generales(Request $request): Response
    {
        return $this->json(['data' => $this->indicadores->generales(), 'generado_en' => date(DATE_ATOM)]);
    }

    public function campanas(Request $request): Response
    {
        return $this->json(['data' => $this->indicadores->campanas(), 'generado_en' => date(DATE_ATOM)]);
    }

    public function reutilizacion(Request $request): Response
    {
        return $this->json(['data' => $this->indicadores->reutilizacion(), 'generado_en' => date(DATE_ATOM)]);
    }
}
