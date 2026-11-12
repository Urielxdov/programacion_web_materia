<?php
declare(strict_types=1);

namespace App\Core;

/** Error que se traduce a una respuesta HTTP (pagina de error o JSON en la API). */
class HttpException extends \RuntimeException
{
    public function __construct(private int $estado, string $mensaje = '')
    {
        parent::__construct($mensaje);
    }

    public function estado(): int
    {
        return $this->estado;
    }
}
