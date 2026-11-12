<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Models\Categoria;
use App\Models\Gestor;
use App\Repositories\CategoriaRepository;
use App\Repositories\GestorRepository;

/** Catalogos administrables: categorias de equipos y gestores de residuos. */
final class CatalogoService
{
    private CategoriaRepository $categorias;
    private GestorRepository $gestores;

    public function __construct(?CategoriaRepository $categorias = null, ?GestorRepository $gestores = null)
    {
        $this->categorias = $categorias ?? new CategoriaRepository();
        $this->gestores = $gestores ?? new GestorRepository();
    }

    // ------------------------------------------------------------ categorias

    /** @return Categoria[] */
    public function categorias(bool $soloActivas = false): array
    {
        return $this->categorias->todas($soloActivas);
    }

    public function conteoEquiposPorCategoria(): array
    {
        return $this->categorias->conteoEquipos();
    }

    public function categoria(int $id): Categoria
    {
        return $this->categorias->porId($id) ?? throw new HttpException(404, 'La categoría no existe.');
    }

    public function guardarCategoria(array $entrada, ?int $id = null): Categoria
    {
        $actual = $id !== null ? $this->categoria($id) : null;
        $d = Validator::validar($entrada, [
            'nombre'          => 'requerido|texto:80',
            'tipo'            => 'requerido|en:' . implode(',', array_keys(Categoria::TIPOS)),
            'vida_util_anios' => 'requerido|entero|min:1|max:50',
            'descripcion'     => 'opcional|texto:255',
            'activo'          => 'booleano',
        ], ['vida_util_anios' => 'vida útil (años)', 'descripcion' => 'descripción']);
        if ($this->categorias->existeNombre($d['nombre'], $id)) {
            throw ValidacionException::campo('nombre', 'Ya existe una categoría con ese nombre.');
        }
        if ($actual !== null && $actual->tipo !== $d['tipo'] && ($this->conteoEquiposPorCategoria()[$id] ?? 0) > 0) {
            throw ValidacionException::campo('tipo', 'No se puede cambiar el tipo de una categoría que ya tiene equipos registrados.');
        }

        $categoria = new Categoria($id, $d['nombre'], $d['tipo'], $d['vida_util_anios'], $d['descripcion'], (bool) $d['activo']);
        if ($id === null) {
            $id = $this->categorias->insertar($categoria);
        } else {
            $this->categorias->actualizar($categoria);
        }
        return $this->categoria($id);
    }

    // ------------------------------------------------------------ gestores

    /** @return Gestor[] */
    public function gestores(bool $soloActivos = false): array
    {
        return $this->gestores->todos($soloActivos);
    }

    public function gestor(int $id): Gestor
    {
        return $this->gestores->porId($id) ?? throw new HttpException(404, 'El gestor no existe.');
    }

    public function guardarGestor(array $entrada, ?int $id = null): Gestor
    {
        if ($id !== null) {
            $this->gestor($id);
        }
        $d = Validator::validar($entrada, [
            'nombre'              => 'requerido|texto:150',
            'tipo'                => 'requerido|en:' . implode(',', array_keys(Gestor::TIPOS)),
            'contacto'            => 'opcional|texto:150',
            'direccion'           => 'opcional|texto:255',
            'numero_autorizacion' => 'opcional|texto:80',
            'verificado'          => 'booleano',
            'activo'              => 'booleano',
        ], ['numero_autorizacion' => 'número de autorización', 'direccion' => 'dirección']);
        if ($d['verificado'] && $d['numero_autorizacion'] === null) {
            throw ValidacionException::campo('numero_autorizacion', 'Para marcarlo como verificado registra su número de autorización ambiental.');
        }

        $gestor = new Gestor($id, $d['nombre'], $d['tipo'], $d['contacto'], $d['direccion'], $d['numero_autorizacion'],
            (bool) $d['verificado'], (bool) $d['activo']);
        if ($id === null) {
            $id = $this->gestores->insertar($gestor);
        } else {
            $this->gestores->actualizar($gestor);
        }
        return $this->gestor($id);
    }
}
