<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\DiagnosticoService;

/** Modulo 3: diagnosticos y decision de destino. */
final class DiagnosticoController extends Controller
{
    private DiagnosticoService $diagnosticos;

    public function __construct()
    {
        $this->diagnosticos = new DiagnosticoService();
    }

    public function crear(Request $request, int $id): Response
    {
        $equipo = $this->diagnosticos->equipo($id);
        return $this->vista('diagnosticos/formulario', [
            'titulo' => 'Diagnóstico de ' . $equipo->codigo(),
            'equipo' => $equipo,
            'ultimo' => $this->diagnosticos->ultimo($id),
        ]);
    }

    public function guardar(Request $request, int $id): Response
    {
        $diagnostico = $this->diagnosticos->registrar($id, $request->todo(), $this->usuarioId());
        $mensaje = 'Diagnóstico registrado. Clasificación del equipo: '
            . \App\Models\Equipo::CLASIFICACIONES[$diagnostico->clasificacionResultante()] . '.';
        return $this->redirigir("panel/equipos/$id", $mensaje);
    }
}
