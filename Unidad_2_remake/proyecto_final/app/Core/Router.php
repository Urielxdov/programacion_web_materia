<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\Autenticado;
use App\Middleware\Middleware;
use App\Middleware\SoloInvitados;
use App\Middleware\TienePermiso;

/**
 * Enrutador del Front Controller: asocia "METODO /ruta/{id}" con Controlador::accion
 * y ejecuta los middlewares de la ruta (auth, invitado, permiso:xxx) antes del controlador.
 */
final class Router
{
    /** @var array<int, array{metodo:string, patron:string, regex:string, accion:array, middlewares:array}> */
    private array $rutas = [];

    public function get(string $patron, array $accion, array $middlewares = []): void
    {
        $this->agregar('GET', $patron, $accion, $middlewares);
    }

    public function post(string $patron, array $accion, array $middlewares = []): void
    {
        $this->agregar('POST', $patron, $accion, $middlewares);
    }

    public function patch(string $patron, array $accion, array $middlewares = []): void
    {
        $this->agregar('PATCH', $patron, $accion, $middlewares);
    }

    public function agregar(string $metodo, string $patron, array $accion, array $middlewares = []): void
    {
        // Los parametros {id}, {pid}... solo aceptan digitos; el resto del patron es literal.
        $regex = '';
        foreach (preg_split('/(\{\w+\})/', $patron, -1, PREG_SPLIT_DELIM_CAPTURE) as $parte) {
            $regex .= preg_match('/^\{(\w+)\}$/', $parte, $m)
                ? '(?P<' . $m[1] . '>\d+)'
                : preg_quote($parte, '#');
        }
        $this->rutas[] = [
            'metodo'      => $metodo,
            'patron'      => $patron,
            'regex'       => '#^' . $regex . '$#u',
            'accion'      => $accion,
            'middlewares' => $middlewares,
        ];
    }

    public function despachar(Request $request): Response
    {
        $metodoEncontrado = false;
        foreach ($this->rutas as $ruta) {
            if (!preg_match($ruta['regex'], $request->ruta(), $coincidencias)) {
                continue;
            }
            $metodoPeticion = $request->metodo() === 'HEAD' ? 'GET' : $request->metodo();
            if ($ruta['metodo'] !== $metodoPeticion) {
                $metodoEncontrado = true;
                continue;
            }

            foreach ($ruta['middlewares'] as $definicion) {
                $respuesta = $this->middleware($definicion)->manejar($request);
                if ($respuesta !== null) {
                    return $respuesta;
                }
            }

            $parametros = array_map('intval', array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY));
            [$clase, $metodo] = $ruta['accion'];
            $controlador = new $clase();
            return $controlador->$metodo($request, ...array_values($parametros));
        }

        if ($metodoEncontrado) {
            throw new HttpException(405, 'Método no permitido para esta ruta.');
        }
        throw new HttpException(404, 'La página que buscas no existe.');
    }

    private function middleware(string $definicion): Middleware
    {
        [$nombre, $parametro] = array_pad(explode(':', $definicion, 2), 2, null);
        return match ($nombre) {
            'auth'     => new Autenticado(),
            'invitado' => new SoloInvitados(),
            'permiso'  => new TienePermiso((string) $parametro),
            default    => throw new \LogicException("Middleware desconocido: $nombre"),
        };
    }

    /** @return array<int, array{metodo:string, patron:string}> Para documentar/probar las rutas. */
    public function rutas(): array
    {
        return array_map(static fn (array $r): array => ['metodo' => $r['metodo'], 'patron' => $r['patron']], $this->rutas);
    }
}
