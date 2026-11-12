<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        private string $cuerpo = '',
        private int $codigo = 200,
        private array $cabeceras = [],
    ) {
    }

    public static function html(string $html, int $codigo = 200): self
    {
        return new self($html, $codigo, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function json(mixed $datos, int $codigo = 200): self
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        return new self((string) $json, $codigo, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function redirigir(string $url, int $codigo = 302): self
    {
        return new self('', $codigo, ['Location' => $url]);
    }

    public static function descarga(string $contenido, string $nombreArchivo, string $tipo): self
    {
        return new self($contenido, 200, [
            'Content-Type'        => $tipo,
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
        ]);
    }

    public function conCabecera(string $nombre, string $valor): self
    {
        $this->cabeceras[$nombre] = $valor;
        return $this;
    }

    public function codigo(): int
    {
        return $this->codigo;
    }

    public function cuerpo(): string
    {
        return $this->cuerpo;
    }

    public function cabecera(string $nombre): ?string
    {
        return $this->cabeceras[$nombre] ?? null;
    }

    public function enviar(): void
    {
        http_response_code($this->codigo);
        foreach ($this->cabeceras as $nombre => $valor) {
            header("$nombre: $valor");
        }
        echo $this->cuerpo;
    }
}
