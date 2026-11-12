<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Categoria;

final class CategoriaRepository extends Repositorio
{
    /** @return Categoria[] */
    public function todas(bool $soloActivas = false): array
    {
        $sql = 'SELECT * FROM categorias' . ($soloActivas ? ' WHERE activo = 1' : '') . ' ORDER BY nombre';
        return array_map(static fn (array $f): Categoria => Categoria::desdeFila($f), $this->db->consultar($sql));
    }

    public function porId(int $id): ?Categoria
    {
        $fila = $this->db->uno('SELECT * FROM categorias WHERE id = ?', [$id]);
        return $fila ? Categoria::desdeFila($fila) : null;
    }

    public function insertar(Categoria $c): int
    {
        return $this->db->insertar(
            'INSERT INTO categorias (nombre, tipo, vida_util_anios, descripcion, activo) VALUES (?, ?, ?, ?, ?)',
            [$c->nombre, $c->tipo, $c->vidaUtilAnios, $c->descripcion, $c->activo ? 1 : 0]
        );
    }

    public function actualizar(Categoria $c): void
    {
        $this->db->ejecutar(
            'UPDATE categorias SET nombre = ?, tipo = ?, vida_util_anios = ?, descripcion = ?, activo = ? WHERE id = ?',
            [$c->nombre, $c->tipo, $c->vidaUtilAnios, $c->descripcion, $c->activo ? 1 : 0, $c->id]
        );
    }

    public function existeNombre(string $nombre, ?int $exceptoId = null): bool
    {
        return (int) $this->db->valor(
            'SELECT COUNT(*) FROM categorias WHERE LOWER(nombre) = LOWER(?) AND id <> ?',
            [$nombre, $exceptoId ?? 0]
        ) > 0;
    }

    /** Cuantos equipos usan cada categoria: [categoria_id => total]. */
    public function conteoEquipos(): array
    {
        $conteo = [];
        foreach ($this->db->consultar('SELECT categoria_id, COUNT(*) AS total FROM equipos GROUP BY categoria_id') as $f) {
            $conteo[(int) $f['categoria_id']] = (int) $f['total'];
        }
        return $conteo;
    }
}
