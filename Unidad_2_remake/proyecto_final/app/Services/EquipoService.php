<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Factories\EquipoFactory;
use App\Models\Equipo;
use App\Models\Movimiento;
use App\Repositories\CampanaRepository;
use App\Repositories\CategoriaRepository;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\EquipoRepository;
use App\Repositories\MovimientoRepository;

/** Casos de uso del inventario: registrar, consultar, buscar y actualizar equipos. */
final class EquipoService
{
    public const ETIQUETAS = [
        'categoria_id'     => 'categoría',
        'marca'            => 'marca',
        'modelo'           => 'modelo',
        'numero_serie'     => 'número de serie',
        'anio_fabricacion' => 'año de fabricación',
        'fecha_ingreso'    => 'fecha de ingreso',
        'condicion'        => 'condición',
        'clasificacion'    => 'clasificación',
        'peso_kg'          => 'peso (kg)',
        'ubicacion'        => 'ubicación',
        'observaciones'    => 'observaciones',
        'campana_id'       => 'campaña',
    ];

    private EquipoRepository $equipos;
    private CategoriaRepository $categorias;
    private MovimientoRepository $movimientos;
    private DiagnosticoRepository $diagnosticos;
    private CampanaRepository $campanas;

    public function __construct(
        ?EquipoRepository $equipos = null,
        ?CategoriaRepository $categorias = null,
        ?MovimientoRepository $movimientos = null,
        ?DiagnosticoRepository $diagnosticos = null,
        ?CampanaRepository $campanas = null,
    ) {
        $this->equipos = $equipos ?? new EquipoRepository();
        $this->categorias = $categorias ?? new CategoriaRepository();
        $this->movimientos = $movimientos ?? new MovimientoRepository();
        $this->diagnosticos = $diagnosticos ?? new DiagnosticoRepository();
        $this->campanas = $campanas ?? new CampanaRepository();
    }

    public static function reglas(): array
    {
        return [
            'categoria_id'     => 'requerido|entero',
            'marca'            => 'requerido|texto:80',
            'modelo'           => 'requerido|texto:80',
            'numero_serie'     => 'opcional|texto:80',
            'anio_fabricacion' => 'opcional|entero|min:1970|max:' . date('Y'),
            'fecha_ingreso'    => 'requerido|fecha|no_futura',
            'condicion'        => 'requerido|en:' . implode(',', array_keys(Equipo::CONDICIONES)),
            'clasificacion'    => 'opcional|en:' . implode(',', array_keys(Equipo::CLASIFICACIONES)),
            'peso_kg'          => 'opcional|decimal|min:0|max:5000',
            'ubicacion'        => 'opcional|texto:120',
            'observaciones'    => 'opcional|texto:1000',
            'campana_id'       => 'opcional|entero',
        ];
    }

    /** @return array{items: Equipo[], total: int, pagina: int, paginas: int, por_pagina: int} */
    public function buscar(array $filtros, int $pagina = 1, int $porPagina = 12): array
    {
        $filtros = array_intersect_key($filtros, array_flip(['q', 'clasificacion', 'estado', 'categoria_id', 'campana_id', 'orden']));
        $resultado = $this->equipos->buscar($filtros, $pagina, $porPagina);
        $paginas = max(1, (int) ceil($resultado['total'] / $porPagina));
        return $resultado + ['pagina' => max(1, $pagina), 'paginas' => $paginas, 'por_pagina' => $porPagina];
    }

    public function obtener(int $id): Equipo
    {
        return $this->equipos->porId($id) ?? throw new HttpException(404, 'El equipo solicitado no existe.');
    }

    /** @return Equipo[] */
    public function recientes(int $limite = 5): array
    {
        return $this->equipos->recientes($limite);
    }

    /**
     * Registra un equipo. Solo quien tiene "equipos.clasificar" puede fijar la clasificacion;
     * para el capturista queda "pendiente" hasta el diagnostico.
     */
    public function registrar(array $entrada, int $usuarioId, bool $puedeClasificar): Equipo
    {
        $datos = Validator::validar($entrada, self::reglas(), self::ETIQUETAS);
        $categoria = $this->categoriaActiva((int) $datos['categoria_id']);
        $this->validarCampana($datos['campana_id']);

        if (!$puedeClasificar || $datos['clasificacion'] === null) {
            $datos['clasificacion'] = 'pendiente';
        }
        $datos['estado'] = 'en_inventario';
        $datos['registrado_por'] = $usuarioId;

        $equipo = EquipoFactory::crear($categoria, $datos);

        Database::obtener()->transaccion(function () use ($equipo, $usuarioId): void {
            $this->equipos->insertar($equipo);
            // El ingreso queda en el historial desde el primer momento (trazabilidad)
            $this->movimientos->insertar(new Movimiento(
                null, (int) $equipo->id(), 'ingreso', $equipo->fechaIngreso(),
                $equipo->campanaId() ? 'Recibido en campaña de recolección' : 'Inventario',
                null, null, 0.0, null, false, false, 'validado', null, $usuarioId, $usuarioId, date('Y-m-d H:i:s')
            ));
        });

        return $equipo;
    }

    /**
     * Actualiza los datos permitidos. Con $parcial (PATCH) solo se validan los campos enviados
     * y se rechazan los que no existen o no se pueden modificar (codigo, estado...).
     */
    public function actualizar(int $id, array $entrada, bool $puedeClasificar, bool $parcial = false): Equipo
    {
        $equipo = $this->obtener($id);
        if ($equipo->estaEntregado()) {
            throw new HttpException(409, 'El equipo ya fue entregado a un gestor; su registro quedó cerrado.');
        }

        $reglas = self::reglas();
        if ($parcial) {
            $desconocidos = array_diff(array_keys($entrada), array_keys($reglas));
            if ($desconocidos !== []) {
                throw new ValidacionException(
                    array_fill_keys($desconocidos, 'Este campo no existe o no puede modificarse.'),
                    'La petición incluye campos que no se pueden modificar: ' . implode(', ', $desconocidos) . '.'
                );
            }
            if ($entrada === []) {
                throw new ValidacionException([], 'No se envió ningún campo para actualizar.');
            }
            $reglas = array_intersect_key($reglas, $entrada);
        }

        $actual = $equipo->toPersistencia();
        $quiereClasificar = array_key_exists('clasificacion', $entrada)
            && (string) $entrada['clasificacion'] !== ''
            && (string) $entrada['clasificacion'] !== $equipo->clasificacion();
        if ($quiereClasificar && !$puedeClasificar) {
            throw new HttpException(403, 'Tu rol no puede cambiar la clasificación; se asigna con el diagnóstico.');
        }

        $datos = Validator::validar($entrada, $reglas, self::ETIQUETAS);
        if (array_key_exists('clasificacion', $datos) && $datos['clasificacion'] === null) {
            unset($datos['clasificacion']);
        }
        $nuevos = array_merge($actual, $datos);

        $categoria = (int) $nuevos['categoria_id'] === $equipo->categoria()->id
            ? $equipo->categoria()
            : $this->categoriaActiva((int) $nuevos['categoria_id']);
        if (array_key_exists('campana_id', $datos)) {
            $this->validarCampana($datos['campana_id']);
        }

        $actualizado = EquipoFactory::crear($categoria, $nuevos);
        $this->equipos->actualizar($actualizado);
        return $this->obtener($id);
    }

    /** Historial completo del equipo. */
    public function historial(int $id): array
    {
        return [
            'diagnosticos' => $this->diagnosticos->porEquipo($id),
            'movimientos'  => $this->movimientos->porEquipo($id),
        ];
    }

    private function categoriaActiva(int $id): \App\Models\Categoria
    {
        $categoria = $this->categorias->porId($id);
        if ($categoria === null || !$categoria->activo) {
            throw ValidacionException::campo('categoria_id', 'Selecciona una categoría válida.');
        }
        return $categoria;
    }

    private function validarCampana(?int $campanaId): void
    {
        if ($campanaId === null) {
            return;
        }
        $campana = $this->campanas->porId($campanaId);
        if ($campana === null || !$campana->esPublica()) {
            throw ValidacionException::campo('campana_id', 'La campaña seleccionada no existe o no está publicada.');
        }
    }
}
