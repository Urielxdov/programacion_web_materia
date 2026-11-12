<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\CampanaService;
use App\Services\CatalogoService;
use App\Services\DiagnosticoService;
use App\Services\EquipoService;

/** Modulo 2: inventario de equipos. */
final class EquipoController extends Controller
{
    private EquipoService $equipos;

    public function __construct()
    {
        $this->equipos = new EquipoService();
    }

    public function index(Request $request): Response
    {
        $filtros = [
            'q'             => (string) $request->query('q', ''),
            'clasificacion' => (string) $request->query('clasificacion', ''),
            'estado'        => (string) $request->query('estado', ''),
            'categoria_id'  => (string) $request->query('categoria_id', ''),
            'orden'         => (string) $request->query('orden', 'recientes'),
        ];
        return $this->vista('equipos/index', [
            'titulo'     => 'Inventario',
            'filtros'    => $filtros,
            'resultado'  => $this->equipos->buscar($filtros, max(1, (int) $request->query('pagina', 1))),
            'categorias' => (new CatalogoService())->categorias(),
        ]);
    }

    public function crear(Request $request): Response
    {
        return $this->vista('equipos/formulario', $this->datosFormulario() + ['titulo' => 'Registrar equipo', 'equipo' => null]);
    }

    public function guardar(Request $request): Response
    {
        $equipo = $this->equipos->registrar($request->todo(), $this->usuarioId(), Auth::puede('equipos.clasificar'));
        return $this->redirigir('panel/equipos/' . $equipo->id(), "Equipo {$equipo->codigo()} registrado correctamente.");
    }

    public function mostrar(Request $request, int $id): Response
    {
        $equipo = $this->equipos->obtener($id);
        $historial = $this->equipos->historial($id);
        return $this->vista('equipos/mostrar', [
            'titulo'       => $equipo->codigo(),
            'equipo'       => $equipo,
            'diagnosticos' => $historial['diagnosticos'],
            'movimientos'  => $historial['movimientos'],
            'gestores'     => (new CatalogoService())->gestores(true),
        ]);
    }

    public function editar(Request $request, int $id): Response
    {
        $equipo = $this->equipos->obtener($id);
        return $this->vista('equipos/formulario', $this->datosFormulario() + ['titulo' => 'Editar ' . $equipo->codigo(), 'equipo' => $equipo]);
    }

    public function actualizar(Request $request, int $id): Response
    {
        $equipo = $this->equipos->actualizar($id, $request->todo(), Auth::puede('equipos.clasificar'));
        return $this->redirigir("panel/equipos/$id", "Equipo {$equipo->codigo()} actualizado.");
    }

    private function datosFormulario(): array
    {
        return [
            'categorias'      => (new CatalogoService())->categorias(true),
            'campanas'        => (new CampanaService())->paraRecepcion(),
            'puedeClasificar' => Auth::puede('equipos.clasificar'),
        ];
    }
}
