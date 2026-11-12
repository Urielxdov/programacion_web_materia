<?php
declare(strict_types=1);

namespace App\Repositories;

/** Parametros del sistema editables por el administrador (tabla clave/valor). */
final class ConfiguracionRepository extends Repositorio
{
    /** @return array<string, array{valor:string, descripcion:?string}> */
    public function todas(): array
    {
        $config = [];
        foreach ($this->db->consultar('SELECT * FROM configuracion ORDER BY clave') as $f) {
            $config[$f['clave']] = ['valor' => $f['valor'], 'descripcion' => $f['descripcion']];
        }
        return $config;
    }

    public function obtener(string $clave, string $defecto): string
    {
        $valor = $this->db->valor('SELECT valor FROM configuracion WHERE clave = ?', [$clave]);
        return $valor === null ? $defecto : (string) $valor;
    }

    public function guardar(string $clave, string $valor, ?string $descripcion = null): void
    {
        $existe = (int) $this->db->valor('SELECT COUNT(*) FROM configuracion WHERE clave = ?', [$clave]) > 0;
        if ($existe) {
            $this->db->ejecutar('UPDATE configuracion SET valor = ? WHERE clave = ?', [$valor, $clave]);
        } else {
            $this->db->ejecutar(
                'INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)',
                [$clave, $valor, $descripcion]
            );
        }
    }
}
