<?php
declare(strict_types=1);

namespace App\Core;

/** Datos invalidos: lleva los mensajes por campo (HTTP 422). */
final class ValidacionException extends HttpException
{
    /** @param array<string, string> $errores */
    public function __construct(private array $errores, string $mensaje = 'Revisa los datos marcados en el formulario.')
    {
        parent::__construct(422, $mensaje);
    }

    public static function campo(string $campo, string $mensaje): self
    {
        return new self([$campo => $mensaje], $mensaje);
    }

    /** @return array<string, string> */
    public function errores(): array
    {
        return $this->errores;
    }
}
