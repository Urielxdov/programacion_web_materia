<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Patron Singleton: una sola conexion PDO por peticion.
 * Soporta MySQL/MariaDB (local con XAMPP) y PostgreSQL (Neon en produccion) con el mismo codigo:
 * los repositorios solo usan SQL estandar y esta clase resuelve las diferencias (por ejemplo,
 * como obtener el id insertado).
 */
final class Database
{
    private static ?Database $instancia = null;

    private PDO $pdo;
    private string $driver;

    private function __construct(array $cfg)
    {
        $this->driver = $cfg['driver'];
        $dsn = match ($this->driver) {
            'mysql' => sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['puerto'], $cfg['nombre']),
            'pgsql' => sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $cfg['host'], $cfg['puerto'], $cfg['nombre'], $cfg['sslmode']),
            default => throw new \RuntimeException("Driver de base de datos no soportado: {$this->driver}"),
        };
        $this->pdo = new PDO($dsn, $cfg['usuario'], $cfg['clave'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        if ($this->driver === 'pgsql') {
            $this->pdo->exec("SET TIME ZONE '" . date_default_timezone_get() . "'");
        }
    }

    public static function obtener(): self
    {
        return self::$instancia ??= new self(config('db'));
    }

    /** Permite al instalador y a las pruebas reemplazar la configuracion. */
    public static function conectarCon(array $cfg): self
    {
        return self::$instancia = new self($cfg);
    }

    private function __clone()
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    /** @return array<int, array<string, mixed>> */
    public function consultar(string $sql, array $parametros = []): array
    {
        return $this->preparar($sql, $parametros)->fetchAll();
    }

    public function uno(string $sql, array $parametros = []): ?array
    {
        $fila = $this->preparar($sql, $parametros)->fetch();
        return $fila === false ? null : $fila;
    }

    public function valor(string $sql, array $parametros = []): mixed
    {
        $valor = $this->preparar($sql, $parametros)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    public function ejecutar(string $sql, array $parametros = []): int
    {
        return $this->preparar($sql, $parametros)->rowCount();
    }

    /** Ejecuta un INSERT y devuelve el id generado (columna "id"). */
    public function insertar(string $sql, array $parametros = []): int
    {
        if ($this->driver === 'pgsql') {
            return (int) $this->preparar($sql . ' RETURNING id', $parametros)->fetchColumn();
        }
        $this->preparar($sql, $parametros);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Ejecuta $operacion dentro de una transaccion; si lanza una excepcion se hace rollback.
     * Si ya hay una transaccion abierta, se reutiliza.
     */
    public function transaccion(callable $operacion): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $operacion($this);
        }
        $this->pdo->beginTransaction();
        try {
            $resultado = $operacion($this);
            $this->pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function preparar(string $sql, array $parametros): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $i = 1;
        foreach ($parametros as $clave => $valor) {
            $nombre = is_int($clave) ? $i++ : (str_starts_with($clave, ':') ? $clave : ':' . $clave);
            $tipo = match (true) {
                is_int($valor)  => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_INT,
                $valor === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($nombre, is_bool($valor) ? (int) $valor : $valor, $tipo);
        }
        $stmt->execute();
        return $stmt;
    }
}
