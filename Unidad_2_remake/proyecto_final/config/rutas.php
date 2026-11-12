<?php
declare(strict_types=1);

use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\EquipoApiController;
use App\Controllers\Api\IndicadorApiController;
use App\Controllers\AuthController;
use App\Controllers\CampanaController;
use App\Controllers\CampanaPublicaController;
use App\Controllers\CatalogoController;
use App\Controllers\DiagnosticoController;
use App\Controllers\EquipoController;
use App\Controllers\InicioController;
use App\Controllers\MovimientoController;
use App\Controllers\PanelController;
use App\Controllers\ReporteController;
use App\Controllers\UsuarioController;
use App\Core\Router;

/*
 * Tabla de rutas. Middlewares: auth (sesion), invitado (sin sesion), permiso:xxx (rol).
 * Todo POST/PATCH exige token CSRF (lo verifica App\Core\Aplicacion).
 */
return static function (Router $r): void {
    // ------------------------------------------------------------ sitio publico
    $r->get('/', [InicioController::class, 'index']);
    $r->get('/informacion', [InicioController::class, 'informacion']);
    $r->get('/campanas', [CampanaPublicaController::class, 'index']);
    $r->get('/campanas/{id}', [CampanaPublicaController::class, 'mostrar']);
    $r->post('/campanas/{id}/participar', [CampanaPublicaController::class, 'participar']);

    // ------------------------------------------------------------ autenticacion
    $r->get('/login', [AuthController::class, 'formulario'], ['invitado']);
    $r->post('/login', [AuthController::class, 'login'], ['invitado']);
    $r->post('/logout', [AuthController::class, 'logout'], ['auth']);

    // ------------------------------------------------------------ panel privado
    $r->get('/panel', [PanelController::class, 'index'], ['auth', 'permiso:panel.ver']);

    $r->get('/panel/equipos', [EquipoController::class, 'index'], ['auth', 'permiso:equipos.ver']);
    $r->get('/panel/equipos/nuevo', [EquipoController::class, 'crear'], ['auth', 'permiso:equipos.crear']);
    $r->post('/panel/equipos', [EquipoController::class, 'guardar'], ['auth', 'permiso:equipos.crear']);
    $r->get('/panel/equipos/{id}', [EquipoController::class, 'mostrar'], ['auth', 'permiso:equipos.ver']);
    $r->get('/panel/equipos/{id}/editar', [EquipoController::class, 'editar'], ['auth', 'permiso:equipos.editar']);
    $r->post('/panel/equipos/{id}', [EquipoController::class, 'actualizar'], ['auth', 'permiso:equipos.editar']);

    $r->get('/panel/equipos/{id}/diagnostico', [DiagnosticoController::class, 'crear'], ['auth', 'permiso:diagnosticos.registrar']);
    $r->post('/panel/equipos/{id}/diagnostico', [DiagnosticoController::class, 'guardar'], ['auth', 'permiso:diagnosticos.registrar']);

    $r->post('/panel/equipos/{id}/movimientos', [MovimientoController::class, 'guardar'], ['auth', 'permiso:movimientos.registrar']);
    $r->get('/panel/movimientos', [MovimientoController::class, 'index'], ['auth', 'permiso:movimientos.validar']);
    $r->post('/panel/movimientos/{id}/validar', [MovimientoController::class, 'validar'], ['auth', 'permiso:movimientos.validar']);
    $r->post('/panel/movimientos/{id}/rechazar', [MovimientoController::class, 'rechazar'], ['auth', 'permiso:movimientos.validar']);

    $r->get('/panel/campanas', [CampanaController::class, 'index'], ['auth', 'permiso:campanas.gestionar']);
    $r->get('/panel/campanas/nueva', [CampanaController::class, 'crear'], ['auth', 'permiso:campanas.gestionar']);
    $r->post('/panel/campanas', [CampanaController::class, 'guardar'], ['auth', 'permiso:campanas.gestionar']);
    $r->get('/panel/campanas/{id}', [CampanaController::class, 'mostrar'], ['auth', 'permiso:campanas.gestionar']);
    $r->get('/panel/campanas/{id}/editar', [CampanaController::class, 'editar'], ['auth', 'permiso:campanas.gestionar']);
    $r->post('/panel/campanas/{id}', [CampanaController::class, 'actualizar'], ['auth', 'permiso:campanas.gestionar']);
    $r->post('/panel/campanas/{id}/participaciones/{pid}/asistencia', [CampanaController::class, 'asistencia'], ['auth', 'permiso:campanas.gestionar']);

    $r->get('/panel/reportes', [ReporteController::class, 'index'], ['auth', 'permiso:reportes.ver']);
    $r->get('/panel/reportes/inventario.csv', [ReporteController::class, 'exportar'], ['auth', 'permiso:reportes.ver']);

    $r->get('/panel/usuarios', [UsuarioController::class, 'index'], ['auth', 'permiso:usuarios.gestionar']);
    $r->get('/panel/usuarios/nuevo', [UsuarioController::class, 'crear'], ['auth', 'permiso:usuarios.gestionar']);
    $r->post('/panel/usuarios', [UsuarioController::class, 'guardar'], ['auth', 'permiso:usuarios.gestionar']);
    $r->get('/panel/usuarios/{id}/editar', [UsuarioController::class, 'editar'], ['auth', 'permiso:usuarios.gestionar']);
    $r->post('/panel/usuarios/{id}', [UsuarioController::class, 'actualizar'], ['auth', 'permiso:usuarios.gestionar']);

    $r->get('/panel/categorias', [CatalogoController::class, 'categorias'], ['auth', 'permiso:catalogos.gestionar']);
    $r->get('/panel/categorias/nueva', [CatalogoController::class, 'nuevaCategoria'], ['auth', 'permiso:catalogos.gestionar']);
    $r->post('/panel/categorias', [CatalogoController::class, 'guardarCategoria'], ['auth', 'permiso:catalogos.gestionar']);
    $r->get('/panel/categorias/{id}/editar', [CatalogoController::class, 'editarCategoria'], ['auth', 'permiso:catalogos.gestionar']);
    $r->post('/panel/categorias/{id}', [CatalogoController::class, 'actualizarCategoria'], ['auth', 'permiso:catalogos.gestionar']);

    $r->get('/panel/gestores', [CatalogoController::class, 'gestores'], ['auth', 'permiso:catalogos.gestionar']);
    $r->get('/panel/gestores/nuevo', [CatalogoController::class, 'nuevoGestor'], ['auth', 'permiso:catalogos.gestionar']);
    $r->post('/panel/gestores', [CatalogoController::class, 'guardarGestor'], ['auth', 'permiso:catalogos.gestionar']);
    $r->get('/panel/gestores/{id}/editar', [CatalogoController::class, 'editarGestor'], ['auth', 'permiso:catalogos.gestionar']);
    $r->post('/panel/gestores/{id}', [CatalogoController::class, 'actualizarGestor'], ['auth', 'permiso:catalogos.gestionar']);

    $r->get('/panel/configuracion', [CatalogoController::class, 'configuracion'], ['auth', 'permiso:configuracion.gestionar']);
    $r->post('/panel/configuracion', [CatalogoController::class, 'guardarConfiguracion'], ['auth', 'permiso:configuracion.gestionar']);

    // ------------------------------------------------------------ API REST (JSON)
    $r->post('/api/auth/login', [AuthApiController::class, 'login']);
    $r->post('/api/auth/logout', [AuthApiController::class, 'logout'], ['auth']);

    // Servicio 1: equipos
    $r->get('/api/equipos', [EquipoApiController::class, 'index'], ['auth', 'permiso:equipos.ver']);
    $r->post('/api/equipos', [EquipoApiController::class, 'guardar'], ['auth', 'permiso:equipos.crear']);
    $r->get('/api/equipos/{id}', [EquipoApiController::class, 'mostrar'], ['auth', 'permiso:equipos.ver']);
    $r->patch('/api/equipos/{id}', [EquipoApiController::class, 'actualizar'], ['auth', 'permiso:equipos.editar']);
    $r->get('/api/equipos/{id}/recomendacion', [EquipoApiController::class, 'recomendacion'], ['auth', 'permiso:diagnosticos.registrar']);

    // Servicio 2: indicadores (acepta tambien las rutas con acentos del documento)
    $r->get('/api/indicadores', [IndicadorApiController::class, 'generales'], ['auth', 'permiso:panel.ver']);
    foreach (['/api/indicadores/campanas', '/api/indicadores/campañas'] as $ruta) {
        $r->get($ruta, [IndicadorApiController::class, 'campanas'], ['auth', 'permiso:reportes.ver']);
    }
    foreach (['/api/indicadores/reutilizacion', '/api/indicadores/reutilización'] as $ruta) {
        $r->get($ruta, [IndicadorApiController::class, 'reutilizacion'], ['auth', 'permiso:reportes.ver']);
    }
};
