<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Patron Repository: concentra el SQL de cada entidad y devuelve objetos del dominio.
 * Los servicios nunca escriben SQL y las vistas nunca tocan la base.
 * Todas las consultas son parametrizadas (prevencion de inyeccion SQL).
 */
abstract class Repositorio
{
    protected Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::obtener();
    }

    protected function ahora(): string
    {
        return date('Y-m-d H:i:s');
    }
}
