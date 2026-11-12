<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Factories\EquipoFactory;
use App\Models\Equipo;

final class EquipoRepository extends Repositorio
{
    private const SELECT = 'SELECT e.*, c.id AS cat_id, c.nombre AS cat_nombre, c.tipo AS cat_tipo,
            c.vida_util_anios AS cat_vida_util_anios, c.descripcion AS cat_descripcion, c.activo AS cat_activo
        FROM equipos e
        JOIN categorias c ON c.id = e.categoria_id';

    private const ORDENES = [
        'recientes' => 'e.id DESC',
        'antiguos'  => 'e.id ASC',
        'codigo'    => 'e.codigo ASC',
        'marca'     => 'e.marca ASC, e.modelo ASC',
    ];

    /**
     * Busqueda con filtros opcionales: q (codigo, marca, modelo, serie), clasificacion,
     * estado, categoria_id, campana_id y orden.
     * @return array{items: Equipo[], total: int}
     */
    public function buscar(array $filtros, int $pagina = 1, int $porPagina = 15): array
    {
        $condiciones = [];
        $parametros = [];

        $texto = trim((string) ($filtros['q'] ?? ''));
        if ($texto !== '') {
            $condiciones[] = '(LOWER(e.codigo) LIKE ? OR LOWER(e.marca) LIKE ? OR LOWER(e.modelo) LIKE ? OR LOWER(e.numero_serie) LIKE ?)';
            $like = '%' . mb_strtolower(addcslashes($texto, '%_\\')) . '%';
            array_push($parametros, $like, $like, $like, $like);
        }
        foreach (['clasificacion', 'estado'] as $campo) {
            if (!empty($filtros[$campo])) {
                $condiciones[] = "e.$campo = ?";
                $parametros[] = (string) $filtros[$campo];
            }
        }
        foreach (['categoria_id', 'campana_id'] as $campo) {
            if (!empty($filtros[$campo])) {
                $condiciones[] = "e.$campo = ?";
                $parametros[] = (int) $filtros[$campo];
            }
        }

        $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
        $orden = self::ORDENES[$filtros['orden'] ?? 'recientes'] ?? self::ORDENES['recientes'];
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        $total = (int) $this->db->valor('SELECT COUNT(*) FROM equipos e' . $where, $parametros);
        $filas = $this->db->consultar(
            self::SELECT . $where . " ORDER BY $orden LIMIT ? OFFSET ?",
            [...$parametros, $porPagina, ($pagina - 1) * $porPagina]
        );

        return ['items' => array_map([EquipoFactory::class, 'desdeFila'], $filas), 'total' => $total];
    }

    public function porId(int $id): ?Equipo
    {
        $fila = $this->db->uno(self::SELECT . ' WHERE e.id = ?', [$id]);
        return $fila ? EquipoFactory::desdeFila($fila) : null;
    }

    /** @return Equipo[] */
    public function recientes(int $limite = 5): array
    {
        return array_map(
            [EquipoFactory::class, 'desdeFila'],
            $this->db->consultar(self::SELECT . ' ORDER BY e.id DESC LIMIT ?', [$limite])
        );
    }

    /** Inserta y asigna el codigo EW-AAAA-NNNN a partir del id generado. */
    public function insertar(Equipo $equipo): void
    {
        $d = $equipo->toPersistencia();
        $ahora = $this->ahora();
        $id = $this->db->insertar(
            'INSERT INTO equipos (categoria_id, marca, modelo, numero_serie, anio_fabricacion, fecha_ingreso, condicion,
                clasificacion, estado, peso_kg, ubicacion, observaciones, campana_id, registrado_por, creado_en, actualizado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['categoria_id'], $d['marca'], $d['modelo'], $d['numero_serie'], $d['anio_fabricacion'], $d['fecha_ingreso'],
             $d['condicion'], $d['clasificacion'], $d['estado'], self::decimal($d['peso_kg']), $d['ubicacion'],
             $d['observaciones'], $d['campana_id'], $d['registrado_por'], $ahora, $ahora]
        );
        $codigo = Equipo::generarCodigo($id, $d['fecha_ingreso']);
        $this->db->ejecutar('UPDATE equipos SET codigo = ? WHERE id = ?', [$codigo, $id]);
        $equipo->asignarIdentidad($id, $codigo);
    }

    public function actualizar(Equipo $equipo): void
    {
        $d = $equipo->toPersistencia();
        $this->db->ejecutar(
            'UPDATE equipos SET categoria_id = ?, marca = ?, modelo = ?, numero_serie = ?, anio_fabricacion = ?, fecha_ingreso = ?,
                condicion = ?, clasificacion = ?, estado = ?, peso_kg = ?, ubicacion = ?, observaciones = ?, campana_id = ?,
                actualizado_en = ?
             WHERE id = ?',
            [$d['categoria_id'], $d['marca'], $d['modelo'], $d['numero_serie'], $d['anio_fabricacion'], $d['fecha_ingreso'],
             $d['condicion'], $d['clasificacion'], $d['estado'], self::decimal($d['peso_kg']), $d['ubicacion'],
             $d['observaciones'], $d['campana_id'], $this->ahora(), $d['id']]
        );
    }

    /** Todos los equipos (para exportar a CSV). */
    public function todosParaReporte(): array
    {
        return $this->db->consultar(
            'SELECT e.codigo, c.nombre AS categoria, e.marca, e.modelo, e.numero_serie, e.anio_fabricacion, e.fecha_ingreso,
                    e.condicion, e.clasificacion, e.estado, e.peso_kg, e.ubicacion
             FROM equipos e JOIN categorias c ON c.id = e.categoria_id
             ORDER BY e.id'
        );
    }

    private static function decimal(?float $valor): ?string
    {
        return $valor === null ? null : number_format($valor, 2, '.', '');
    }
}
