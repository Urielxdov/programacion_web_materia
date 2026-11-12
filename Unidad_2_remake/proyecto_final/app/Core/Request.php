<?php
declare(strict_types=1);

namespace App\Core;

/** Encapsula la peticion HTTP (metodo, ruta, query, cuerpo, cabeceras). */
final class Request
{
    private function __construct(
        private string $metodo,
        private string $ruta,
        private string $basePath,
        private array $query,
        private array $cuerpo,
        private array $cabeceras,
        private array $servidor,
        private bool $jsonInvalido = false,
    ) {
    }

    public static function capturar(): self
    {
        $servidor = $_SERVER;
        $script = str_replace('\\', '/', $servidor['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(dirname($script), '/');
        if ($base === '.' || $base === '\\') {
            $base = '';
        }

        $uri = parse_url($servidor['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $ruta = rawurldecode($uri);
        if ($base !== '' && str_starts_with($ruta, $base)) {
            $ruta = substr($ruta, strlen($base));
        }
        if (str_starts_with($ruta, '/index.php')) {
            $ruta = substr($ruta, strlen('/index.php'));
        }
        $ruta = '/' . trim($ruta, '/');

        $cabeceras = [];
        foreach ($servidor as $clave => $valor) {
            if (str_starts_with($clave, 'HTTP_')) {
                $cabeceras[strtolower(str_replace('_', '-', substr($clave, 5)))] = $valor;
            }
        }
        if (isset($servidor['CONTENT_TYPE'])) {
            $cabeceras['content-type'] = $servidor['CONTENT_TYPE'];
        }

        $metodo = strtoupper($servidor['REQUEST_METHOD'] ?? 'GET');
        $cuerpo = $_POST;
        $jsonInvalido = false;
        if (str_contains($cabeceras['content-type'] ?? '', 'application/json')) {
            $crudo = file_get_contents('php://input');
            $cuerpo = [];
            if ($crudo !== '' && $crudo !== false) {
                $decodificado = json_decode($crudo, true);
                if (is_array($decodificado)) {
                    $cuerpo = $decodificado;
                } else {
                    $jsonInvalido = true;
                }
            }
        }
        // Los formularios HTML solo envian GET/POST: _method permite PATCH/PUT/DELETE
        if ($metodo === 'POST' && isset($cuerpo['_method'])) {
            $sobrescrito = strtoupper((string) $cuerpo['_method']);
            if (in_array($sobrescrito, ['PATCH', 'PUT', 'DELETE'], true)) {
                $metodo = $sobrescrito;
            }
        }

        return new self($metodo, $ruta, $base, $_GET, $cuerpo, $cabeceras, $servidor, $jsonInvalido);
    }

    /** Construye una peticion a mano (pruebas). */
    public static function crear(string $metodo, string $ruta, array $cuerpo = [], array $query = [], array $cabeceras = []): self
    {
        return new self(strtoupper($metodo), $ruta, '', $query, $cuerpo, $cabeceras, []);
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function query(string $clave, mixed $defecto = null): mixed
    {
        $valor = $this->query[$clave] ?? $defecto;
        return is_string($valor) ? trim($valor) : $valor;
    }

    public function todoQuery(): array
    {
        return $this->query;
    }

    public function input(string $clave, mixed $defecto = null): mixed
    {
        return $this->cuerpo[$clave] ?? $defecto;
    }

    /** Cuerpo sin los campos de control (_token, _method). */
    public function todo(): array
    {
        return array_diff_key($this->cuerpo, ['_token' => 1, '_method' => 1]);
    }

    public function cabecera(string $nombre, ?string $defecto = null): ?string
    {
        return $this->cabeceras[strtolower($nombre)] ?? $defecto;
    }

    public function jsonInvalido(): bool
    {
        return $this->jsonInvalido;
    }

    public function esApi(): bool
    {
        return $this->ruta === '/api' || str_starts_with($this->ruta, '/api/');
    }

    public function esSegura(): bool
    {
        return in_array($this->metodo, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    public function esHttps(): bool
    {
        $https = $this->servidor['HTTPS'] ?? '';
        if ($https !== '' && strtolower((string) $https) !== 'off') {
            return true;
        }
        if (config('app.confiar_proxy')) {
            return strtolower($this->cabecera('x-forwarded-proto', '')) === 'https';
        }
        return false;
    }

    /** Referer solo si apunta a este mismo sitio (evita redirecciones abiertas). */
    public function refererLocal(): ?string
    {
        $referer = $this->cabecera('referer');
        if ($referer === null) {
            return null;
        }
        $host = $this->servidor['HTTP_HOST'] ?? '';
        $partes = parse_url($referer);
        if (($partes['host'] ?? '') === '' || ($partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : '')) !== $host) {
            return null;
        }
        return $referer;
    }
}
