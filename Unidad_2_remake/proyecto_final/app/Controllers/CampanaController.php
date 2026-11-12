<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CampanaService;

/** Modulo 4 (panel): administracion de campanas y control de resultados. */
final class CampanaController extends Controller
{
    private CampanaService $campanas;

    public function __construct()
    {
        $this->campanas = new CampanaService();
    }

    public function index(Request $request): Response
    {
        return $this->vista('campanas/index', ['titulo' => 'Campañas', 'campanas' => $this->campanas->todas()]);
    }

    public function crear(Request $request): Response
    {
        return $this->vista('campanas/formulario', ['titulo' => 'Nueva campaña', 'campana' => null]);
    }

    public function guardar(Request $request): Response
    {
        $campana = $this->campanas->crear($request->todo(), $this->usuarioId());
        return $this->redirigir('panel/campanas/' . $campana->id, 'Campaña creada.');
    }

    public function mostrar(Request $request, int $id): Response
    {
        $resultados = $this->campanas->resultados($id);
        return $this->vista('campanas/mostrar', ['titulo' => $resultados['campana']->nombre] + $resultados);
    }

    public function editar(Request $request, int $id): Response
    {
        $campana = $this->campanas->obtener($id);
        return $this->vista('campanas/formulario', ['titulo' => 'Editar campaña', 'campana' => $campana]);
    }

    public function actualizar(Request $request, int $id): Response
    {
        $this->campanas->actualizar($id, $request->todo());
        return $this->redirigir("panel/campanas/$id", 'Campaña actualizada.');
    }

    public function asistencia(Request $request, int $id, int $pid): Response
    {
        $this->campanas->marcarAsistencia($id, $pid, $request->input('asistio') === '1');
        return $this->redirigir("panel/campanas/$id", 'Asistencia actualizada.');
    }
}
