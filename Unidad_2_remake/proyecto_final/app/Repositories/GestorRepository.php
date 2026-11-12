<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Gestor;

final class GestorRepository extends Repositorio
{
    /** @return Gestor[] */
    public function todos(bool $soloActivos = false): array
    {
        $sql = 'SELECT * FROM gestores' . ($soloActivos ? ' WHERE activo = 1' : '') . ' ORDER BY verificado DESC, nombre';
        return array_map([Gestor::class, 'desdeFila'], $this->db->consultar($sql));
    }

    public function porId(int $id): ?Gestor
    {
        $fila = $this->db->uno('SELECT * FROM gestores WHERE id = ?', [$id]);
        return $fila ? Gestor::desdeFila($fila) : null;
    }

    public function insertar(Gestor $g): int
    {
        return $this->db->insertar(
            'INSERT INTO gestores (nombre, tipo, contacto, direccion, numero_autorizacion, verificado, activo, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$g->nombre, $g->tipo, $g->contacto, $g->direccion, $g->numeroAutorizacion, $g->verificado ? 1 : 0, $g->activo ? 1 : 0, $this->ahora()]
        );
    }

    public function actualizar(Gestor $g): void
    {
        $this->db->ejecutar(
            'UPDATE gestores SET nombre = ?, tipo = ?, contacto = ?, direccion = ?, numero_autorizacion = ?, verificado = ?, activo = ?
             WHERE id = ?',
            [$g->nombre, $g->tipo, $g->contacto, $g->direccion, $g->numeroAutorizacion, $g->verificado ? 1 : 0, $g->activo ? 1 : 0, $g->id]
        );
    }
}
