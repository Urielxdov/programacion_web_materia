<?php
declare(strict_types=1);

/** Mini marco de pruebas sin dependencias (no requiere Composer). */
final class MiniTest
{
    private int $ok = 0;
    private array $fallos = [];

    public function __construct(private string $titulo)
    {
        echo "== {$this->titulo} ==" . PHP_EOL;
    }

    public function prueba(string $nombre, callable $fn): void
    {
        try {
            $fn();
            $this->ok++;
            echo "  [OK]    $nombre" . PHP_EOL;
        } catch (Throwable $e) {
            $this->fallos[] = $nombre;
            echo "  [FALLA] $nombre" . PHP_EOL . '          ' . $e->getMessage() . PHP_EOL;
        }
    }

    public function igual(mixed $esperado, mixed $real, string $mensaje = ''): void
    {
        if ($esperado !== $real) {
            throw new RuntimeException(trim($mensaje . ' Se esperaba ' . var_export($esperado, true) . ' y se obtuvo ' . var_export($real, true)));
        }
    }

    public function verdadero(bool $condicion, string $mensaje = 'La condición no se cumplió'): void
    {
        if (!$condicion) {
            throw new RuntimeException($mensaje);
        }
    }

    public function lanza(string $clase, callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            if ($e instanceof $clase) {
                return;
            }
            throw new RuntimeException("Se esperaba $clase y se lanzó " . get_class($e) . ': ' . $e->getMessage());
        }
        throw new RuntimeException("Se esperaba la excepción $clase");
    }

    public function fallar(string $mensaje): never
    {
        throw new RuntimeException($mensaje);
    }

    public function resumen(): int
    {
        $total = $this->ok + count($this->fallos);
        echo PHP_EOL . "Resultado: {$this->ok}/$total pruebas correctas." . PHP_EOL;
        return $this->fallos === [] ? 0 : 1;
    }
}
