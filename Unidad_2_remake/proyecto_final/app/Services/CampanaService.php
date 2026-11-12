<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Models\Campana;
use App\Models\Participacion;
use App\Repositories\CampanaRepository;
use App\Repositories\EquipoRepository;
use App\Repositories\ParticipacionRepository;

/** Campanas de recoleccion: publicacion, participacion y resultados. */
final class CampanaService
{
    private const ETIQUETAS = [
        'nombre'                => 'nombre',
        'descripcion'           => 'descripción',
        'fecha_inicio'          => 'fecha de inicio',
        'fecha_fin'             => 'fecha de fin',
        'ubicacion'             => 'ubicación',
        'tipos_aceptados'       => 'equipos aceptados',
        'meta_equipos'          => 'meta de equipos',
        'estado'                => 'estado',
        'correo'                => 'correo',
        'equipos_comprometidos' => 'número de equipos',
        'tipos_equipo'          => 'tipo de equipos',
        'acepto_privacidad'     => 'aviso de privacidad',
    ];

    private CampanaRepository $campanas;
    private ParticipacionRepository $participaciones;
    private EquipoRepository $equipos;

    public function __construct(
        ?CampanaRepository $campanas = null,
        ?ParticipacionRepository $participaciones = null,
        ?EquipoRepository $equipos = null,
    ) {
        $this->campanas = $campanas ?? new CampanaRepository();
        $this->participaciones = $participaciones ?? new ParticipacionRepository();
        $this->equipos = $equipos ?? new EquipoRepository();
    }

    /** @return Campana[] */
    public function publicas(): array
    {
        return $this->campanas->publicas();
    }

    /** @return Campana[] */
    public function todas(): array
    {
        return $this->campanas->todas();
    }

    /** @return Campana[] */
    public function paraRecepcion(): array
    {
        return $this->campanas->paraRecepcion();
    }

    public function obtener(int $id): Campana
    {
        return $this->campanas->porId($id) ?? throw new HttpException(404, 'La campaña no existe.');
    }

    /** Los borradores no se muestran al publico. */
    public function obtenerPublica(int $id): Campana
    {
        $campana = $this->obtener($id);
        if (!$campana->esPublica()) {
            throw new HttpException(404, 'La campaña no existe.');
        }
        return $campana;
    }

    public function crear(array $entrada, int $usuarioId): Campana
    {
        $d = $this->validarCampana($entrada);
        $campana = new Campana(null, $d['nombre'], $d['descripcion'], $d['fecha_inicio'], $d['fecha_fin'], $d['ubicacion'],
            $d['tipos_aceptados'], $d['meta_equipos'], $d['estado'], $usuarioId);
        return $this->obtener($this->campanas->insertar($campana));
    }

    public function actualizar(int $id, array $entrada): Campana
    {
        $actual = $this->obtener($id);
        $d = $this->validarCampana($entrada);
        $this->campanas->actualizar(new Campana($id, $d['nombre'], $d['descripcion'], $d['fecha_inicio'], $d['fecha_fin'],
            $d['ubicacion'], $d['tipos_aceptados'], $d['meta_equipos'], $d['estado'], $actual->creadoPor));
        return $this->obtener($id);
    }

    /** Registro publico. Solo se piden los datos minimos y el consentimiento del aviso de privacidad. */
    public function participar(int $campanaId, array $entrada, ?int $usuarioId): Participacion
    {
        $campana = $this->obtenerPublica($campanaId);
        if (!$campana->aceptaParticipaciones()) {
            throw new HttpException(409, 'Esta campaña ya no recibe registros.');
        }
        $d = Validator::validar($entrada, [
            'nombre'                => 'requerido|texto:120',
            'correo'                => 'opcional|email|texto:150',
            'equipos_comprometidos' => 'requerido|entero|min:1|max:200',
            'tipos_equipo'          => 'opcional|texto:255',
            'acepto_privacidad'     => 'requerido|booleano',
        ], self::ETIQUETAS + ['nombre' => 'nombre']);
        if (!$d['acepto_privacidad']) {
            throw ValidacionException::campo('acepto_privacidad', 'Debes aceptar el aviso de privacidad para registrarte.');
        }

        $participacion = new Participacion(null, $campanaId, $usuarioId, $d['nombre'], $d['correo'],
            $d['equipos_comprometidos'], $d['tipos_equipo']);
        $this->participaciones->insertar($participacion);
        return $participacion;
    }

    /** Resultados de una campana para el panel. */
    public function resultados(int $id): array
    {
        $campana = $this->obtener($id);
        $participaciones = $this->participaciones->porCampana($id);
        $equipos = $this->equipos->buscar(['campana_id' => $id], 1, 100)['items'];

        $comprometidos = array_sum(array_map(static fn (Participacion $p): int => $p->equiposComprometidos, $participaciones));
        $porClasificacion = [];
        $kg = 0.0;
        foreach ($equipos as $e) {
            $porClasificacion[$e->nombreClasificacion()] = ($porClasificacion[$e->nombreClasificacion()] ?? 0) + 1;
            $kg += $e->pesoKg() ?? 0;
        }

        return [
            'campana'         => $campana,
            'participaciones' => $participaciones,
            'equipos'         => $equipos,
            'resumen'         => [
                'participantes'     => count($participaciones),
                'asistentes'        => count(array_filter($participaciones, static fn (Participacion $p): bool => $p->asistio)),
                'comprometidos'     => $comprometidos,
                'recibidos'         => count($equipos),
                'kg'                => $kg,
                'avance_meta'       => $campana->metaEquipos ? min(100, round(count($equipos) / $campana->metaEquipos * 100)) : null,
                'por_clasificacion' => $porClasificacion,
            ],
        ];
    }

    public function marcarAsistencia(int $campanaId, int $participacionId, bool $asistio): void
    {
        $this->obtener($campanaId);
        if (!$this->participaciones->marcarAsistencia($participacionId, $campanaId, $asistio)) {
            throw new HttpException(404, 'La participación no pertenece a esta campaña.');
        }
    }

    private function validarCampana(array $entrada): array
    {
        return Validator::validar($entrada, [
            'nombre'          => 'requerido|texto:120',
            'descripcion'     => 'requerido|texto:2000',
            'fecha_inicio'    => 'requerido|fecha',
            'fecha_fin'       => 'requerido|fecha|despues_o_igual:fecha_inicio',
            'ubicacion'       => 'requerido|texto:150',
            'tipos_aceptados' => 'requerido|texto:255',
            'meta_equipos'    => 'opcional|entero|min:1|max:100000',
            'estado'          => 'requerido|en:' . implode(',', array_keys(Campana::ESTADOS)),
        ], self::ETIQUETAS);
    }
}
