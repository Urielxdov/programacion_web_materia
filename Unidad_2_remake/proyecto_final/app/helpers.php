<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Url;

/* Funciones de apoyo para las vistas. Todo texto que viene del usuario se imprime con e(). */

function config(string $clave, mixed $defecto = null): mixed
{
    return Config::obtener($clave, $defecto);
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta = ''): string
{
    return Url::a($ruta);
}

function asset(string $ruta): string
{
    return Url::a('assets/' . ltrim($ruta, '/')) . '?v=' . config('app.version_assets');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function metodo_campo(string $metodo): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($metodo)) . '">';
}

/** Valor anterior del formulario (tras un error de validacion) o el valor por defecto. */
function viejo(string $campo, mixed $defecto = ''): mixed
{
    $entrada = Session::leerFlash('entrada', []);
    return array_key_exists($campo, $entrada) ? $entrada[$campo] : $defecto;
}

function error_de(string $campo): ?string
{
    return Session::leerFlash('errores', [])[$campo] ?? null;
}

function clase_error(string $campo): string
{
    return error_de($campo) !== null ? ' is-invalid' : '';
}

function mensaje_error(string $campo): string
{
    $error = error_de($campo);
    return $error === null ? '' : '<div class="invalid-feedback d-block">' . e($error) . '</div>';
}

function flash(string $clave): mixed
{
    return Session::leerFlash($clave);
}

function usuario_actual(): ?array
{
    return Auth::usuario();
}

function puede(string $permiso): bool
{
    return Auth::puede($permiso);
}

function dinero(mixed $cantidad): string
{
    return '$' . number_format((float) $cantidad, 2);
}

function numero(mixed $cantidad, int $decimales = 0): string
{
    return number_format((float) $cantidad, $decimales);
}

function fecha(?string $fecha, bool $conHora = false): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    $marca = strtotime($fecha);
    if ($marca === false) {
        return e($fecha);
    }
    return date($conHora ? 'd/m/Y H:i' : 'd/m/Y', $marca);
}

/** Opciones <option> a partir de [valor => etiqueta]. */
function opciones(array $lista, mixed $seleccionado, ?string $vacio = null): string
{
    $html = $vacio !== null ? '<option value="">' . e($vacio) . '</option>' : '';
    foreach ($lista as $valor => $etiqueta) {
        $sel = (string) $valor === (string) $seleccionado ? ' selected' : '';
        $html .= '<option value="' . e($valor) . '"' . $sel . '>' . e($etiqueta) . '</option>';
    }
    return $html;
}

/** Colores de Bootstrap para los estados del dominio. */
function color_estado(string $valor): string
{
    return match ($valor) {
        'funcional', 'validado', 'activa', 'reutilizar', 'reutilizable', 'reasignado', 'conservar' => 'success',
        'reparable', 'reparar', 'pendiente', 'borrador'                                             => 'warning',
        'gestion_especializada', 'rechazado', 'entregado_gestor'                                    => 'danger',
        'conservado', 'cerrada'                                                                     => 'secondary',
        default                                                                                     => 'info',
    };
}

function insignia(string $valor, string $texto): string
{
    $color = color_estado($valor);
    $textoOscuro = $color === 'warning' || $color === 'info' ? ' text-dark' : '';
    return '<span class="badge rounded-pill text-bg-' . $color . $textoOscuro . '">' . e($texto) . '</span>';
}

/** Marca como activo el enlace del menu lateral. */
function activo(string $prefijo): string
{
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $objetivo = rtrim(url($prefijo), '/');
    return ($ruta === $objetivo || str_starts_with($ruta, $objetivo . '/')) ? ' active' : '';
}
