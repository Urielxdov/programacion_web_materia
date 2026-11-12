<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CatalogoService;
use App\Services\ConfiguracionService;

/** Catalogos (categorias, gestores) y configuracion del sistema. Solo administrador. */
final class CatalogoController extends Controller
{
    private CatalogoService $catalogos;

    public function __construct()
    {
        $this->catalogos = new CatalogoService();
    }

    // ---------------------------------------------------------- categorias

    public function categorias(Request $request): Response
    {
        return $this->vista('catalogos/categorias', [
            'titulo'     => 'Categorías de equipos',
            'categorias' => $this->catalogos->categorias(),
            'conteo'     => $this->catalogos->conteoEquiposPorCategoria(),
        ]);
    }

    public function nuevaCategoria(Request $request): Response
    {
        return $this->vista('catalogos/categoria_form', ['titulo' => 'Nueva categoría', 'categoria' => null]);
    }

    public function guardarCategoria(Request $request): Response
    {
        $categoria = $this->catalogos->guardarCategoria($request->todo());
        return $this->redirigir('panel/categorias', "Categoría «{$categoria->nombre}» creada.");
    }

    public function editarCategoria(Request $request, int $id): Response
    {
        return $this->vista('catalogos/categoria_form', ['titulo' => 'Editar categoría', 'categoria' => $this->catalogos->categoria($id)]);
    }

    public function actualizarCategoria(Request $request, int $id): Response
    {
        $categoria = $this->catalogos->guardarCategoria($request->todo(), $id);
        return $this->redirigir('panel/categorias', "Categoría «{$categoria->nombre}» actualizada.");
    }

    // ---------------------------------------------------------- gestores

    public function gestores(Request $request): Response
    {
        return $this->vista('catalogos/gestores', ['titulo' => 'Gestores de residuos', 'gestores' => $this->catalogos->gestores()]);
    }

    public function nuevoGestor(Request $request): Response
    {
        return $this->vista('catalogos/gestor_form', ['titulo' => 'Nuevo gestor', 'gestor' => null]);
    }

    public function guardarGestor(Request $request): Response
    {
        $gestor = $this->catalogos->guardarGestor($request->todo());
        return $this->redirigir('panel/gestores', "Gestor «{$gestor->nombre}» registrado.");
    }

    public function editarGestor(Request $request, int $id): Response
    {
        return $this->vista('catalogos/gestor_form', ['titulo' => 'Editar gestor', 'gestor' => $this->catalogos->gestor($id)]);
    }

    public function actualizarGestor(Request $request, int $id): Response
    {
        $gestor = $this->catalogos->guardarGestor($request->todo(), $id);
        return $this->redirigir('panel/gestores', "Gestor «{$gestor->nombre}» actualizado.");
    }

    // ---------------------------------------------------------- configuracion

    public function configuracion(Request $request): Response
    {
        return $this->vista('catalogos/configuracion', [
            'titulo'     => 'Configuración',
            'parametros' => (new ConfiguracionService())->todos(),
        ]);
    }

    public function guardarConfiguracion(Request $request): Response
    {
        (new ConfiguracionService())->guardar($request->todo());
        return $this->redirigir('panel/configuracion', 'Configuración guardada. Las nuevas recomendaciones usarán estos valores.');
    }
}
