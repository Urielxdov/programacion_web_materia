<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Repositories\ConfiguracionRepository;
use App\Strategies\ClasificadorDestino;

/** Parametros institucionales que usa la politica de clasificacion (Strategy). */
final class ConfiguracionService
{
    public const PARAMETROS = [
        'umbral_reparacion' => [
            'defecto'     => '60',
            'regla'       => 'requerido|decimal|min:1|max:100',
            'etiqueta'    => 'Umbral de reparación (%)',
            'descripcion' => 'Se recomienda reparar si el costo no supera este porcentaje del valor de un equipo nuevo.',
        ],
        'factor_vida_util' => [
            'defecto'     => '1.5',
            'regla'       => 'requerido|decimal|min:0.5|max:5',
            'etiqueta'    => 'Tolerancia de vida útil (factor)',
            'descripcion' => 'Un equipo funcional se recomienda reutilizar mientras su antigüedad no supere la vida útil × este factor.',
        ],
        'nombre_institucion' => [
            'defecto'     => 'Instituto Tecnológico',
            'regla'       => 'requerido|texto:120',
            'etiqueta'    => 'Nombre de la institución',
            'descripcion' => 'Se muestra en el sitio público y en los reportes.',
        ],
    ];

    private ConfiguracionRepository $repo;

    public function __construct(?ConfiguracionRepository $repo = null)
    {
        $this->repo = $repo ?? new ConfiguracionRepository();
    }

    public function valor(string $clave): string
    {
        return $this->repo->obtener($clave, self::PARAMETROS[$clave]['defecto']);
    }

    /** @return array<string, array{valor:string, etiqueta:string, descripcion:string}> */
    public function todos(): array
    {
        $guardados = $this->repo->todas();
        $resultado = [];
        foreach (self::PARAMETROS as $clave => $p) {
            $resultado[$clave] = [
                'valor'       => $guardados[$clave]['valor'] ?? $p['defecto'],
                'etiqueta'    => $p['etiqueta'],
                'descripcion' => $p['descripcion'],
            ];
        }
        return $resultado;
    }

    public function guardar(array $entrada): void
    {
        $reglas = array_map(static fn (array $p): string => $p['regla'], self::PARAMETROS);
        $etiquetas = array_map(static fn (array $p): string => $p['etiqueta'], self::PARAMETROS);
        $datos = Validator::validar($entrada, $reglas, $etiquetas);
        foreach ($datos as $clave => $valor) {
            $this->repo->guardar($clave, (string) $valor, self::PARAMETROS[$clave]['descripcion']);
        }
    }

    public function clasificador(): ClasificadorDestino
    {
        return ClasificadorDestino::politicaInstitucional(
            (float) $this->valor('umbral_reparacion'),
            (float) $this->valor('factor_vida_util')
        );
    }
}
