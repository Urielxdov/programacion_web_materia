<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validacion del lado del servidor (la del cliente puede saltarse).
 * Reglas separadas por "|": requerido, opcional, texto:N, entero, decimal, min:N, max:N,
 * en:a,b,c, fecha, no_futura, despues_o_igual:campo, email, booleano, usuario, password.
 */
final class Validator
{
    private array $errores = [];
    private array $validos = [];

    private function __construct(private array $datos, array $reglas, private array $etiquetas)
    {
        foreach ($reglas as $campo => $regla) {
            $this->validarCampo($campo, is_array($regla) ? $regla : explode('|', $regla));
        }
    }

    /**
     * Valida y devuelve solo los campos declarados, ya convertidos (int, float, bool).
     * @throws ValidacionException
     */
    public static function validar(array $datos, array $reglas, array $etiquetas = []): array
    {
        $v = new self($datos, $reglas, $etiquetas);
        if ($v->errores !== []) {
            throw new ValidacionException($v->errores);
        }
        return $v->validos;
    }

    private function validarCampo(string $campo, array $reglas): void
    {
        $valor = $this->datos[$campo] ?? null;
        if (is_bool($valor)) {
            $valor = $valor ? '1' : '0';
        } elseif (is_int($valor) || is_float($valor)) {
            $valor = (string) $valor;
        } elseif (is_array($valor)) {
            $this->errores[$campo] = 'El valor de ' . $this->nombre($campo) . ' no es válido.';
            return;
        } elseif (is_string($valor)) {
            $valor = trim($valor);
        }

        $esBooleano = in_array('booleano', $reglas, true);
        if ($valor === null || $valor === '') {
            if (in_array('requerido', $reglas, true)) {
                $this->errores[$campo] = 'El campo ' . $this->nombre($campo) . ' es obligatorio.';
            } else {
                $this->validos[$campo] = $esBooleano ? false : null;
            }
            return;
        }

        foreach ($reglas as $regla) {
            [$nombreRegla, $parametro] = array_pad(explode(':', $regla, 2), 2, null);
            $error = $this->aplicar($nombreRegla, $parametro, $valor, $campo);
            if ($error !== null) {
                $this->errores[$campo] = $error;
                return;
            }
        }

        $this->validos[$campo] = match (true) {
            in_array('entero', $reglas, true)  => (int) $valor,
            in_array('decimal', $reglas, true) => round((float) $valor, 2),
            $esBooleano                        => in_array(strtolower($valor), ['1', 'on', 'true', 'si', 'sí'], true),
            default                            => $valor,
        };
    }

    private function aplicar(string $regla, ?string $param, string $valor, string $campo): ?string
    {
        $n = $this->nombre($campo);
        return match ($regla) {
            'requerido', 'opcional', 'booleano' => null,
            'texto'   => mb_strlen($valor) > (int) $param ? "El campo $n admite como máximo $param caracteres." : null,
            'entero'  => filter_var($valor, FILTER_VALIDATE_INT) === false ? "El campo $n debe ser un número entero." : null,
            'decimal' => !is_numeric($valor) ? "El campo $n debe ser un número." : null,
            'min'     => is_numeric($valor) && (float) $valor < (float) $param ? "El campo $n no puede ser menor que $param." : null,
            'max'     => is_numeric($valor) && (float) $valor > (float) $param ? "El campo $n no puede ser mayor que $param." : null,
            'en'      => !in_array($valor, explode(',', (string) $param), true) ? "El valor de $n no es válido." : null,
            'fecha'   => !self::esFecha($valor) ? "El campo $n debe ser una fecha válida (AAAA-MM-DD)." : null,
            'no_futura' => self::esFecha($valor) && $valor > date('Y-m-d') ? "El campo $n no puede ser una fecha futura." : null,
            'despues_o_igual' => $this->compararFecha($valor, (string) $param, $n),
            'email'   => filter_var($valor, FILTER_VALIDATE_EMAIL) === false ? "El campo $n debe ser un correo válido." : null,
            'usuario' => !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $valor)
                ? "El $n debe tener de 3 a 50 caracteres: letras, números, punto, guion o guion bajo." : null,
            'password' => (mb_strlen($valor) < 8 || !preg_match('/[A-Za-z]/', $valor) || !preg_match('/\d/', $valor))
                ? 'La contraseña debe tener al menos 8 caracteres e incluir letras y números.' : null,
            default => throw new \LogicException("Regla de validación desconocida: $regla"),
        };
    }

    private function compararFecha(string $valor, string $otroCampo, string $nombre): ?string
    {
        $otro = $this->validos[$otroCampo] ?? null;
        if (!self::esFecha($valor) || !is_string($otro)) {
            return null; // el otro campo ya reporto su propio error
        }
        return $valor < $otro ? "El campo $nombre debe ser igual o posterior a " . $this->nombre($otroCampo) . '.' : null;
    }

    public static function esFecha(string $valor): bool
    {
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $fecha !== false && $fecha->format('Y-m-d') === $valor;
    }

    private function nombre(string $campo): string
    {
        return $this->etiquetas[$campo] ?? str_replace('_', ' ', $campo);
    }
}
