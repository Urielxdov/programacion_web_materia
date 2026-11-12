<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Capa de presentacion: renderiza plantillas PHP de app/Views.
 * Las vistas solo reciben datos ya preparados por el controlador; nunca consultan la base.
 */
final class View
{
    public static function render(string $vista, array $datos = [], ?string $layout = 'layouts/panel'): string
    {
        $contenido = self::archivo($vista, $datos);
        if ($layout === null) {
            return $contenido;
        }
        return self::archivo($layout, $datos + ['contenido' => $contenido]);
    }

    public static function parcial(string $vista, array $datos = []): string
    {
        return self::archivo('parciales/' . $vista, $datos);
    }

    private static function archivo(string $vista, array $datos): string
    {
        $ruta = BASE_PATH . '/app/Views/' . $vista . '.php';
        if (!is_file($ruta)) {
            throw new \RuntimeException("No existe la vista $vista");
        }
        extract($datos, EXTR_SKIP);
        ob_start();
        try {
            include $ruta;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
