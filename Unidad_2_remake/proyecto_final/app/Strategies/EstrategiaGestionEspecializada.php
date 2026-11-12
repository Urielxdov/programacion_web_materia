<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Equipo;

/** Ultimo recurso (siempre aplica): riesgo de seguridad o reparacion no costeable. */
final class EstrategiaGestionEspecializada implements EstrategiaDestino
{
    public function decision(): string
    {
        return 'gestion_especializada';
    }

    public function aplica(Equipo $equipo, EvaluacionTecnica $evaluacion): bool
    {
        return true;
    }

    public function justificacion(Equipo $equipo, EvaluacionTecnica $evaluacion): string
    {
        if ($evaluacion->riesgoSeguridad) {
            $motivo = 'Presenta un riesgo de seguridad (por ejemplo, batería inflada, fugas o componentes expuestos)';
        } elseif ($evaluacion->valorReemplazo <= 0) {
            $motivo = 'No funciona y no se registró un valor de reemplazo que justifique repararlo';
        } else {
            $motivo = 'No funciona y repararlo costaría el ' . number_format((float) $evaluacion->porcentajeCosto(), 1)
                . '% del valor de un equipo nuevo';
        }
        $extra = $equipo->contieneMaterialesPeligrosos()
            ? ' Contiene materiales peligrosos: entrégalo solo a un gestor con autorización verificada.'
            : ' Entrégalo a un gestor verificado y conserva el comprobante.';
        return $motivo . '.' . $extra;
    }
}
