<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Campana;

final class CampanaRepository extends Repositorio
{
    /** @return Campana[] Campanas visibles al publico (activas y cerradas). */
    public function publicas(): array
    {
        return $this->mapear($this->db->consultar(
            "SELECT * FROM campanas WHERE estado <> 'borrador' ORDER BY CASE WHEN estado = 'activa' THEN 0 ELSE 1 END, fecha_inicio DESC"
        ));
    }

    /** @return Campana[] */
    public function todas(): array
    {
        return $this->mapear($this->db->consultar('SELECT * FROM campanas ORDER BY fecha_inicio DESC, id DESC'));
    }

    /** @return Campana[] Campanas a las que se puede asociar un equipo recibido. */
    public function paraRecepcion(): array
    {
        return $this->mapear($this->db->consultar("SELECT * FROM campanas WHERE estado <> 'borrador' ORDER BY fecha_inicio DESC"));
    }

    public function porId(int $id): ?Campana
    {
        $fila = $this->db->uno('SELECT * FROM campanas WHERE id = ?', [$id]);
        return $fila ? Campana::desdeFila($fila) : null;
    }

    public function contarActivas(): int
    {
        return (int) $this->db->valor("SELECT COUNT(*) FROM campanas WHERE estado = 'activa'");
    }

    public function insertar(Campana $c): int
    {
        return $this->db->insertar(
            'INSERT INTO campanas (nombre, descripcion, fecha_inicio, fecha_fin, ubicacion, tipos_aceptados, meta_equipos, estado, creado_por, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$c->nombre, $c->descripcion, $c->fechaInicio, $c->fechaFin, $c->ubicacion, $c->tiposAceptados,
             $c->metaEquipos, $c->estado, $c->creadoPor, $this->ahora()]
        );
    }

    public function actualizar(Campana $c): void
    {
        $this->db->ejecutar(
            'UPDATE campanas SET nombre = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, ubicacion = ?, tipos_aceptados = ?,
                meta_equipos = ?, estado = ?
             WHERE id = ?',
            [$c->nombre, $c->descripcion, $c->fechaInicio, $c->fechaFin, $c->ubicacion, $c->tiposAceptados,
             $c->metaEquipos, $c->estado, $c->id]
        );
    }

    /** @return Campana[] */
    private function mapear(array $filas): array
    {
        return array_map([Campana::class, 'desdeFila'], $filas);
    }
}
