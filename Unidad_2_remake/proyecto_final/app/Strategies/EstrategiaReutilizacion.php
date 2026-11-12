<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Equipo;

/** Funciona, es seguro y no ha rebasado su vida util: se reasigna a otra area. */
final class EstrategiaReutilizacion implements EstrategiaDestino
{
    public function __construct(private float $factorVidaUtil = 1.0, private ?int $anioActual = null)
    {
    }

    public function decision(): string
    {
        return 'reutilizar';
    }

    public function aplica(Equipo $equipo, EvaluacionTecnica $evaluacion): bool
    {
        return $evaluacion->funciona()
            && !$evaluacion->riesgoSeguridad
            && !$equipo->superaVidaUtil($this->factorVidaUtil, $this->anioActual);
    }

    public function justificacion(Equipo $equipo, EvaluacionTecnica $evaluacion): string
    {
        $antiguedad = $equipo->antiguedadAnios($this->anioActual);
        $vida = $equipo->categoria()->vidaUtilAnios;
        $detalle = $antiguedad === null
            ? 'no tiene año de fabricación registrado, así que se asume dentro de su vida útil'
            : "tiene $antiguedad año(s) de antigüedad frente a una vida útil de referencia de $vida";
        return "El equipo funciona y $detalle: puede reasignarse a un laboratorio, departamento o usuario autorizado.";
    }
}
