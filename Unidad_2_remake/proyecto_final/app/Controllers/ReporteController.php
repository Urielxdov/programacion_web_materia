<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Equipo;
use App\Repositories\EquipoRepository;

/** Modulo 5: reportes. Las graficas se dibujan en el navegador con datos de la API. */
final class ReporteController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->vista('reportes/index', ['titulo' => 'Indicadores y reportes', 'graficas' => true]);
    }

    /** Inventario en CSV (UTF-8 con BOM para que Excel respete los acentos). */
    public function exportar(Request $request): Response
    {
        $salida = fopen('php://temp', 'w+');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Código', 'Categoría', 'Marca', 'Modelo', 'No. de serie', 'Año', 'Fecha de ingreso',
            'Condición', 'Clasificación', 'Estado', 'Peso (kg)', 'Ubicación'], ',', '"', '');
        foreach ((new EquipoRepository())->todosParaReporte() as $f) {
            $f['condicion'] = Equipo::CONDICIONES[$f['condicion']] ?? $f['condicion'];
            $f['clasificacion'] = Equipo::CLASIFICACIONES[$f['clasificacion']] ?? $f['clasificacion'];
            $f['estado'] = Equipo::ESTADOS[$f['estado']] ?? $f['estado'];
            fputcsv($salida, array_map([self::class, 'celdaSegura'], array_values($f)), ',', '"', '');
        }
        rewind($salida);
        $csv = (string) stream_get_contents($salida);
        fclose($salida);
        return Response::descarga($csv, 'inventario-ewaste360-' . date('Y-m-d') . '.csv', 'text/csv; charset=utf-8');
    }

    /** Evita inyeccion de formulas en hojas de calculo (=, +, -, @). */
    private static function celdaSegura(mixed $valor): string
    {
        $texto = (string) ($valor ?? '');
        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'" . $texto : $texto;
    }
}
