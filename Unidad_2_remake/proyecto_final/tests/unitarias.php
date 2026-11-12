<?php
declare(strict_types=1);

/*
 * Pruebas unitarias del dominio (no necesitan base de datos ni servidor).
 *   php tests/unitarias.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/MiniTest.php';

use App\Core\Auth;
use App\Core\ValidacionException;
use App\Core\Validator;
use App\Factories\EquipoFactory;
use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\Equipos\Bateria;
use App\Models\Equipos\Laptop;
use App\Models\Equipos\Periferico;
use App\Strategies\ClasificadorDestino;
use App\Strategies\EvaluacionTecnica;

$t = new MiniTest('Pruebas unitarias');

$laptopCat = new Categoria(1, 'Laptop', 'laptop', 4);
$bateriaCat = new Categoria(2, 'Batería', 'bateria', 3);
$tecladoCat = new Categoria(3, 'Teclado', 'periferico', 5);
$base = ['marca' => 'Dell', 'modelo' => 'X', 'fecha_ingreso' => '2026-01-10', 'condicion' => 'funciona'];

// ---------------------------------------------------------------- Factory
$t->prueba('Factory crea la subclase correcta según el tipo de categoría', function () use ($t, $laptopCat, $bateriaCat, $tecladoCat, $base) {
    $t->igual(Laptop::class, get_class(EquipoFactory::crear($laptopCat, $base)));
    $t->igual(Bateria::class, get_class(EquipoFactory::crear($bateriaCat, $base)));
    $t->igual(Periferico::class, get_class(EquipoFactory::crear($tecladoCat, $base)));
    $t->igual(9, count(EquipoFactory::tiposSoportados()));
});

$t->prueba('Polimorfismo: cada subclase define borrado de datos y materiales peligrosos', function () use ($t, $laptopCat, $bateriaCat, $tecladoCat, $base) {
    $laptop = EquipoFactory::crear($laptopCat, $base);
    $bateria = EquipoFactory::crear($bateriaCat, $base);
    $teclado = EquipoFactory::crear($tecladoCat, $base);
    $t->verdadero($laptop->requiereBorradoDatos() && $laptop->contieneMaterialesPeligrosos());
    $t->verdadero(!$bateria->requiereBorradoDatos() && $bateria->contieneMaterialesPeligrosos());
    $t->verdadero(!$teclado->requiereBorradoDatos() && !$teclado->contieneMaterialesPeligrosos());
    $t->verdadero(count($bateria->recomendacionesEntrega()) > count($teclado->recomendacionesEntrega()));
});

// ---------------------------------------------------------------- Reglas del equipo
$t->prueba('Código único con formato EW-AAAA-NNNN', function () use ($t) {
    $t->igual('EW-2026-0007', Equipo::generarCodigo(7, '2026-03-01'));
    $t->igual('EW-2025-12345', Equipo::generarCodigo(12345, '2025-12-31'));
});

$t->prueba('Transiciones de estado: entregado a gestor es final', function () use ($t, $laptopCat, $base) {
    $e = EquipoFactory::crear($laptopCat, $base);
    $t->verdadero($e->puedeRecibir('reasignacion'));
    $e->aplicarMovimiento('entrega_gestor');
    $t->igual('entregado_gestor', $e->estado());
    $t->igual([], $e->movimientosPermitidos());
    $t->lanza(\DomainException::class, fn () => $e->aplicarMovimiento('reparacion'));
    $t->lanza(\DomainException::class, fn () => $e->asignarClasificacion('funcional'));
});

$t->prueba('Un equipo para gestión especializada no se repara ni se reasigna', function () use ($t, $laptopCat, $base) {
    $e = EquipoFactory::crear($laptopCat, $base + ['clasificacion' => 'gestion_especializada']);
    $t->verdadero(!$e->puedeRecibir('reparacion'));
    $t->verdadero(!$e->puedeRecibir('reasignacion'));
    $t->verdadero($e->puedeRecibir('entrega_gestor'));
});

$t->prueba('La reparación validada deja el equipo funcional y en inventario', function () use ($t, $laptopCat, $base) {
    $e = EquipoFactory::crear($laptopCat, $base + ['clasificacion' => 'reparable']);
    $e->aplicarMovimiento('reparacion');
    $t->igual('funcional', $e->clasificacion());
    $t->igual('en_inventario', $e->estado());
});

$t->prueba('Valores fuera de catálogo se rechazan', function () use ($t, $laptopCat, $base) {
    $t->lanza(\InvalidArgumentException::class, fn () => EquipoFactory::crear($laptopCat, ['estado' => 'robado'] + $base));
    $t->lanza(\InvalidArgumentException::class, fn () => new Categoria(null, 'X', 'nave-espacial', 3));
});

// ---------------------------------------------------------------- Strategy
$clasificador = ClasificadorDestino::politicaInstitucional(60, 1.5, 2026);
$equipoNuevo = EquipoFactory::crear($laptopCat, $base + ['anio_fabricacion' => 2024]);
$equipoViejo = EquipoFactory::crear($laptopCat, $base + ['anio_fabricacion' => 2015]);

$t->prueba('Strategy: funciona y dentro de vida útil → reutilizar', function () use ($t, $clasificador, $equipoNuevo) {
    $r = $clasificador->recomendar($equipoNuevo, new EvaluacionTecnica('funciona', 0, 9000, false));
    $t->igual('reutilizar', $r->decision);
    $t->igual('EstrategiaReutilizacion', $r->estrategia);
});

$t->prueba('Strategy: funciona pero superó la vida útil → conservar', function () use ($t, $clasificador, $equipoViejo) {
    $t->igual('conservar', $clasificador->recomendar($equipoViejo, new EvaluacionTecnica('funciona', 0, 9000, false))->decision);
});

$t->prueba('Strategy: falla con reparación barata (≤ umbral) → reparar', function () use ($t, $clasificador, $equipoNuevo) {
    $t->igual('reparar', $clasificador->recomendar($equipoNuevo, new EvaluacionTecnica('falla_parcial', 1000, 9000, false))->decision);
    $t->igual('reparar', $clasificador->recomendar($equipoNuevo, new EvaluacionTecnica('no_funciona', 5400, 9000, false))->decision); // 60% exacto
});

$t->prueba('Strategy: reparación cara o sin valor de reemplazo → gestión especializada', function () use ($t, $clasificador, $equipoNuevo) {
    $t->igual('gestion_especializada', $clasificador->recomendar($equipoNuevo, new EvaluacionTecnica('no_funciona', 5500, 9000, false))->decision);
    $t->igual('gestion_especializada', $clasificador->recomendar($equipoNuevo, new EvaluacionTecnica('no_funciona', 100, 0, false))->decision);
});

$t->prueba('Strategy: el riesgo de seguridad siempre manda a gestión especializada', function () use ($t, $clasificador, $equipoNuevo) {
    $r = $clasificador->recomendar($equipoNuevo, new EvaluacionTecnica('funciona', 0, 9000, true));
    $t->igual('gestion_especializada', $r->decision);
    $t->verdadero(str_contains($r->justificacion, 'riesgo de seguridad'));
});

$t->prueba('Strategy: cambiar la política (umbral) cambia la recomendación', function () use ($t, $equipoNuevo) {
    $estricta = ClasificadorDestino::politicaInstitucional(30, 1.5, 2026);
    $t->igual('gestion_especializada', $estricta->recomendar($equipoNuevo, new EvaluacionTecnica('falla_parcial', 4000, 9000, false))->decision);
});

// ---------------------------------------------------------------- Validator
$t->prueba('Validator convierte tipos y aplica reglas', function () use ($t) {
    $d = Validator::validar(
        ['n' => ' 5 ', 'p' => '2.456', 'b' => 'on', 'f' => '2026-02-28', 'x' => ''],
        ['n' => 'requerido|entero|min:1', 'p' => 'decimal', 'b' => 'booleano', 'f' => 'fecha', 'x' => 'opcional|texto:5']
    );
    $t->igual(5, $d['n']);
    $t->igual(2.46, $d['p']);
    $t->igual(true, $d['b']);
    $t->igual(null, $d['x']);
});

$t->prueba('Validator rechaza datos inválidos con mensajes por campo', function () use ($t) {
    try {
        Validator::validar(
            ['correo' => 'no-es-correo', 'fecha' => '2026-02-30', 'fin' => '2026-01-01', 'inicio' => '2026-02-01', 'tipo' => 'x'],
            ['correo' => 'email', 'fecha' => 'fecha', 'inicio' => 'fecha', 'fin' => 'fecha|despues_o_igual:inicio', 'tipo' => 'en:a,b', 'falta' => 'requerido']
        );
        $t->fallar('Debió lanzar ValidacionException');
    } catch (ValidacionException $e) {
        $t->igual(['correo', 'fecha', 'fin', 'tipo', 'falta'], array_keys($e->errores()));
    }
});

$t->prueba('Contraseñas: mínimo 8 caracteres con letras y números', function () use ($t) {
    $t->lanza(ValidacionException::class, fn () => Validator::validar(['p' => 'abcdefgh'], ['p' => 'password']));
    $t->igual('abc12345', Validator::validar(['p' => 'abc12345'], ['p' => 'password'])['p']);
});

// ---------------------------------------------------------------- Permisos
$t->prueba('Matriz de permisos por rol', function () use ($t) {
    $t->verdadero(Auth::rolPuede('capturista', 'equipos.crear'));
    $t->verdadero(!Auth::rolPuede('capturista', 'diagnosticos.registrar'));
    $t->verdadero(!Auth::rolPuede('capturista', 'reportes.ver'));
    $t->verdadero(Auth::rolPuede('responsable', 'movimientos.validar'));
    $t->verdadero(!Auth::rolPuede('responsable', 'usuarios.gestionar'));
    $t->verdadero(Auth::rolPuede('admin', 'configuracion.gestionar'));
});

exit($t->resumen());
