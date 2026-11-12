<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;
use App\Models\Equipo;
use App\Services\DiagnosticoService;
use App\Services\EquipoService;

/**
 * Servicio web 1: API REST de equipos (JSON).
 *   GET    /api/equipos                    lista con filtros y paginacion
 *   GET    /api/equipos/{id}               detalle con historial
 *   POST   /api/equipos                    registra (201 + Location)
 *   PATCH  /api/equipos/{id}               actualiza campos permitidos
 *   GET    /api/equipos/{id}/recomendacion destino sugerido (Strategy)
 */
final class EquipoApiController extends Controller
{
    private EquipoService $equipos;

    public function __construct()
    {
        $this->equipos = new EquipoService();
    }

    public function index(Request $request): Response
    {
        $porPagina = max(1, min(100, (int) $request->query('por_pagina', 12)));
        $resultado = $this->equipos->buscar($request->todoQuery(), max(1, (int) $request->query('pagina', 1)), $porPagina);
        return $this->json([
            'data' => array_map(static fn (Equipo $e): array => $e->toArray() + ['url' => Url::a('panel/equipos/' . $e->id())], $resultado['items']),
            'meta' => [
                'total'      => $resultado['total'],
                'pagina'     => $resultado['pagina'],
                'paginas'    => $resultado['paginas'],
                'por_pagina' => $resultado['por_pagina'],
            ],
        ]);
    }

    public function mostrar(Request $request, int $id): Response
    {
        $equipo = $this->equipos->obtener($id);
        $historial = $this->equipos->historial($id);
        return $this->json(['data' => $equipo->toArray() + [
            'recomendaciones_entrega' => $equipo->recomendacionesEntrega(),
            'diagnosticos'            => array_map(static fn ($d): array => $d->toArray(), $historial['diagnosticos']),
            'movimientos'             => array_map(static fn ($m): array => $m->toArray(), $historial['movimientos']),
        ]]);
    }

    public function guardar(Request $request): Response
    {
        $equipo = $this->equipos->registrar($request->todo(), $this->usuarioId(), Auth::puede('equipos.clasificar'));
        return $this->json(['data' => $equipo->toArray()], 201)
            ->conCabecera('Location', Url::a('api/equipos/' . $equipo->id()));
    }

    public function actualizar(Request $request, int $id): Response
    {
        $equipo = $this->equipos->actualizar($id, $request->todo(), Auth::puede('equipos.clasificar'), true);
        return $this->json(['data' => $equipo->toArray()]);
    }

    public function recomendacion(Request $request, int $id): Response
    {
        $recomendacion = (new DiagnosticoService())->recomendar($id, $request->todoQuery());
        return $this->json(['data' => $recomendacion->toArray()]);
    }
}
