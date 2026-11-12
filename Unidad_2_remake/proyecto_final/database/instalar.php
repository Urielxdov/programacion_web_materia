<?php
declare(strict_types=1);

/*
 * Instalador de la base de datos (MySQL o PostgreSQL segun DB_DRIVER / DATABASE_URL).
 *
 *   php database/instalar.php                 crea las tablas y carga catalogos + datos de demostracion
 *   php database/instalar.php --crear-bd      (MySQL) crea antes la base de datos si no existe
 *   php database/instalar.php --reiniciar     BORRA las tablas y las vuelve a crear (solo desarrollo)
 *   php database/instalar.php --si-vacia      no hace nada si ya existen las tablas (lo usa Docker al arrancar)
 *   php database/instalar.php --sin-demo      solo catalogos y un administrador, sin datos de ejemplo
 *
 * Contrasena de las cuentas demo: variable SEED_PASSWORD. Si no existe se genera una aleatoria
 * y se imprime UNA vez en la consola (nunca se guarda en el repositorio).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Usuario;
use App\Services\CampanaService;
use App\Services\DiagnosticoService;
use App\Services\EquipoService;
use App\Services\MovimientoService;

$opciones = array_flip(array_slice($argv, 1));
$cfg = config('db');

function salida(string $mensaje): void
{
    fwrite(STDOUT, $mensaje . PHP_EOL);
}

try {
    if (isset($opciones['--crear-bd']) && $cfg['driver'] === 'mysql') {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $cfg['host'], $cfg['puerto']), $cfg['usuario'], $cfg['clave']);
        $nombre = str_replace('`', '', $cfg['nombre']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$nombre` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        salida("Base de datos «{$cfg['nombre']}» lista.");
    }

    $db = Database::obtener();
    $existe = tablaExiste($db, 'usuarios');

    if ($existe && isset($opciones['--si-vacia'])) {
        salida('Las tablas ya existen; no se modificó nada.');
        exit(0);
    }
    if ($existe && !isset($opciones['--reiniciar'])) {
        salida('Las tablas ya existen. Usa --reiniciar para borrarlas y crearlas de nuevo (se pierden los datos).');
        exit(1);
    }
    if ($existe) {
        if (config('app.entorno') === 'production') {
            throw new RuntimeException('--reiniciar está bloqueado cuando APP_ENV=production.');
        }
        foreach (['participaciones', 'movimientos', 'diagnosticos', 'equipos', 'campanas', 'gestores', 'categorias', 'configuracion', 'usuarios'] as $tabla) {
            $db->pdo()->exec("DROP TABLE IF EXISTS $tabla" . ($db->driver() === 'pgsql' ? ' CASCADE' : ''));
        }
        salida('Tablas anteriores eliminadas.');
    }

    $schema = file_get_contents(__DIR__ . '/' . $db->driver() . '/schema.sql');
    $sinComentarios = preg_replace('/^\s*--.*$/m', '', $schema);
    foreach (array_filter(array_map('trim', explode(';', $sinComentarios))) as $sentencia) {
        $db->pdo()->exec($sentencia);
    }
    salida('Esquema creado (' . $db->driver() . ').');

    $password = getenv('SEED_PASSWORD') ?: null;
    $generada = false;
    if ($password === null) {
        $password = 'Ew' . bin2hex(random_bytes(5)) . '9';
        $generada = true;
    }

    cargarCatalogos($db);
    $ids = crearUsuarios($db, $password, isset($opciones['--sin-demo']));
    if (!isset($opciones['--sin-demo'])) {
        cargarDemo($db, $ids);
    }

    salida('');
    salida('Instalación terminada. Cuentas creadas:');
    foreach ($ids as $usuario => $id) {
        salida("  - $usuario");
    }
    salida($generada
        ? "  Contraseña generada (guárdala, no se vuelve a mostrar): $password"
        : '  Contraseña: la definida en SEED_PASSWORD.');
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function tablaExiste(Database $db, string $tabla): bool
{
    $sql = $db->driver() === 'pgsql'
        ? "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = ?"
        : "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
    return (int) $db->valor($sql, [$tabla]) > 0;
}

function cargarCatalogos(Database $db): void
{
    $categorias = [
        ['Computadora de escritorio', 'computadora', 5, 'CPU, all-in-one y estaciones de trabajo'],
        ['Laptop', 'laptop', 4, 'Portátiles y ultrabooks'],
        ['Monitor', 'monitor', 7, 'Monitores LCD/LED y pantallas'],
        ['Impresora', 'impresora', 5, 'Impresoras, multifuncionales y escáneres'],
        ['Celular o tableta', 'celular', 3, 'Teléfonos inteligentes y tabletas'],
        ['Batería', 'bateria', 3, 'Baterías de laptop, no-break y pilas recargables'],
        ['Teclado y ratón', 'periferico', 5, 'Periféricos de entrada'],
        ['Cables y cargadores', 'accesorio', 5, 'Cables, eliminadores y adaptadores'],
        ['Proyector', 'otro', 6, 'Proyectores y otros equipos audiovisuales'],
    ];
    foreach ($categorias as [$nombre, $tipo, $vida, $desc]) {
        $db->ejecutar('INSERT INTO categorias (nombre, tipo, vida_util_anios, descripcion, activo) VALUES (?, ?, ?, ?, 1)', [$nombre, $tipo, $vida, $desc]);
    }
    foreach (App\Services\ConfiguracionService::PARAMETROS as $clave => $p) {
        $valor = $clave === 'nombre_institucion' ? (string) config('app.institucion') : $p['defecto'];
        $db->ejecutar('INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)', [$clave, $valor, $p['descripcion']]);
    }
    salida('Catálogos cargados (9 categorías, configuración).');
}

/** @return array<string, int> */
function crearUsuarios(Database $db, string $password, bool $soloAdmin): array
{
    $cuentas = [['Administrador del sistema', 'admin', 'admin@ewaste360.test', 'admin']];
    if (!$soloAdmin) {
        $cuentas[] = ['Responsable ambiental (demo)', 'responsable', 'responsable@ewaste360.test', 'responsable'];
        $cuentas[] = ['Capturista (demo)', 'capturista', 'capturista@ewaste360.test', 'capturista'];
    }
    $repo = new App\Repositories\UsuarioRepository($db);
    $ids = [];
    foreach ($cuentas as [$nombre, $usuario, $email, $rol]) {
        $ids[$usuario] = $repo->insertar(new Usuario(null, $nombre, $usuario, $email, Usuario::hashear($password), $rol));
    }
    return $ids;
}

/** Datos de ejemplo creados con los mismos servicios que usa la app (se respetan sus reglas). */
function cargarDemo(Database $db, array $ids): void
{
    $admin = $ids['admin'];
    $responsable = $ids['responsable'];
    $capturista = $ids['capturista'];
    $hoy = new DateTimeImmutable('today');
    $f = static fn (int $dias): string => $hoy->modify("-$dias days")->format('Y-m-d');

    $gestores = [
        ['Recicladora Ejemplo del Bajío (demo)', 'reciclador', 'contacto@recicladora.test', 'Parque industrial, nave 4', 'AUT-DEMO-0001', 1],
        ['Reacondicionadora Segunda Vida (demo)', 'reacondicionador', 'tel. 000 000 0000', 'Av. Tecnológico 100', 'AUT-DEMO-0002', 1],
        ['Chatarrera sin documentación (demo)', 'otro', null, null, null, 0],
    ];
    $gestorIds = [];
    foreach ($gestores as [$nombre, $tipo, $contacto, $direccion, $aut, $verificado]) {
        $gestorIds[] = $db->insertar(
            'INSERT INTO gestores (nombre, tipo, contacto, direccion, numero_autorizacion, verificado, activo, creado_en) VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
            [$nombre, $tipo, $contacto, $direccion, $aut, $verificado, date('Y-m-d H:i:s')]
        );
    }

    $campanas = new CampanaService();
    $c1 = $campanas->crear([
        'nombre' => 'Reciclatón tecnológico de primavera', 'descripcion' => 'Jornada de acopio de equipos electrónicos de la comunidad estudiantil y vecinos.',
        'fecha_inicio' => $f(200), 'fecha_fin' => $f(198), 'ubicacion' => 'Explanada principal del campus',
        'tipos_aceptados' => 'Computadoras, Laptops, Monitores, Celulares, Cables y cargadores', 'meta_equipos' => 40, 'estado' => 'activa',
    ], $responsable);
    $c2 = $campanas->crear([
        'nombre' => 'Acopio permanente de pilas y baterías', 'descripcion' => 'Contenedor seguro para pilas y baterías en la biblioteca. Cubre los polos con cinta antes de depositarlas.',
        'fecha_inicio' => $f(30), 'fecha_fin' => $hoy->modify('+60 days')->format('Y-m-d'), 'ubicacion' => 'Biblioteca, planta baja',
        'tipos_aceptados' => 'Pilas, Baterías de laptop, Baterías de celular', 'meta_equipos' => 150, 'estado' => 'activa',
    ], $responsable);
    $c3 = $campanas->crear([
        'nombre' => 'Dona tu equipo funcional', 'descripcion' => 'Recibimos equipos que aún funcionan para reacondicionarlos y donarlos a escuelas.',
        'fecha_inicio' => $hoy->modify('+20 days')->format('Y-m-d'), 'fecha_fin' => $hoy->modify('+27 days')->format('Y-m-d'),
        'ubicacion' => 'Laboratorio de cómputo 3', 'tipos_aceptados' => 'Laptops, Monitores, Teclados y ratones', 'meta_equipos' => 25, 'estado' => 'borrador',
    ], $responsable);
    // La primera campana ya termino: se cierra tras registrar participantes
    foreach ([['Ana López', 'ana@example.com', 2], ['Luis Pérez', null, 1], ['Grupo 5A de Sistemas', 'grupo5a@example.com', 6]] as [$nombre, $correo, $n]) {
        $db->insertar(
            'INSERT INTO participaciones (campana_id, usuario_id, nombre, correo, equipos_comprometidos, tipos_equipo, asistio, fecha_registro) VALUES (?, NULL, ?, ?, ?, ?, 1, ?)',
            [$c1->id, $nombre, $correo, $n, 'Varios', $f(201) . ' 10:00:00']
        );
    }
    $campanas->participar($c2->id, ['nombre' => 'María Hernández', 'equipos_comprometidos' => 4, 'tipos_equipo' => 'Pilas AA', 'acepto_privacidad' => '1'], null);
    $db->ejecutar("UPDATE campanas SET estado = 'cerrada' WHERE id = ?", [$c1->id]);

    $equipos = new EquipoService();
    $diag = new DiagnosticoService();
    $movs = new MovimientoService();
    $cat = [];
    foreach ($db->consultar('SELECT id, tipo FROM categorias') as $fila) {
        $cat[$fila['tipo']] = (int) $fila['id'];
    }

    // [categoria, marca, modelo, anio, dias, condicion, peso, campana, diagnostico, movimiento]
    $lista = [
        ['laptop', 'Dell', 'Latitude 5490', 2019, 190, 'funciona', 1.6, $c1->id, ['funciona', 0, 9000, false, 'reutilizar'], ['reasignacion', 'Laboratorio de Redes', true]],
        ['computadora', 'HP', 'ProDesk 400 G4', 2017, 185, 'falla_parcial', 6.5, $c1->id, ['falla_parcial', 1200, 7000, false, 'reparar'], ['reparacion', 1100]],
        ['monitor', 'Samsung', 'S22F350', 2016, 180, 'no_funciona', 3.2, $c1->id, ['no_funciona', 2500, 2400, false, 'gestion_especializada'], ['entrega_gestor', 0]],
        ['bateria', 'Genérica', 'Batería de laptop 6 celdas', null, 25, 'no_funciona', 0.4, $c2->id, ['no_funciona', 0, 0, true, 'gestion_especializada'], ['entrega_gestor', 0]],
        ['celular', 'Motorola', 'Moto G7', 2019, 170, 'no_funciona', 0.2, $c1->id, ['no_funciona', 900, 3500, false, 'reparar'], null],
        ['impresora', 'Epson', 'L3150', 2020, 120, 'funciona', 3.9, null, ['funciona', 0, 4200, false, 'reutilizar'], ['reasignacion', 'Coordinación académica', false]],
        ['computadora', 'Lenovo', 'ThinkCentre M700', 2013, 95, 'funciona', 5.8, null, ['funciona', 0, 7500, false, 'conservar'], ['conservacion', 'Bodega de TI', false]],
        ['periferico', 'Logitech', 'Teclado K120', 2021, 60, 'funciona', 0.5, null, null, null],
        ['accesorio', 'Varios', 'Lote de 15 cables VGA/HDMI', null, 40, 'desconocida', 2.1, null, null, null],
        ['laptop', 'Acer', 'Aspire 3', 2018, 15, 'no_funciona', 1.9, null, ['no_funciona', 4800, 8500, false, 'gestion_especializada'], ['entrega_gestor', 2]],
        ['otro', 'Epson', 'Proyector PowerLite X41', 2016, 10, 'falla_parcial', 2.5, null, null, null],
        ['monitor', 'LG', '24MK430H', 2020, 5, 'funciona', 3.0, null, null, null],
    ];

    foreach ($lista as $i => [$tipo, $marca, $modelo, $anio, $dias, $condicion, $peso, $campana, $d, $m]) {
        $equipo = $equipos->registrar([
            'categoria_id' => $cat[$tipo], 'marca' => $marca, 'modelo' => $modelo, 'anio_fabricacion' => $anio,
            'fecha_ingreso' => $f($dias), 'condicion' => $condicion, 'peso_kg' => $peso, 'campana_id' => $campana,
            'ubicacion' => $campana ? 'Almacén de acopio' : 'Bodega de TI', 'numero_serie' => sprintf('SN-DEMO-%03d', $i + 1),
        ], $i % 2 === 0 ? $capturista : $responsable, false);

        if ($d !== null) {
            [$estado, $costo, $valor, $riesgo, $decision] = $d;
            $diag->registrar((int) $equipo->id(), [
                'descripcion' => 'Revisión técnica de ejemplo.', 'estado_funcional' => $estado, 'costo_reparacion' => $costo,
                'valor_reemplazo' => $valor, 'riesgo_seguridad' => $riesgo ? '1' : '0', 'decision' => $decision,
                'justificacion' => 'Decisión del comité de TI (dato de demostración).', 'fecha' => $f(max(0, $dias - 2)),
            ], $responsable);
        }
        if ($m !== null) {
            $tipoMov = $m[0];
            $entrada = ['tipo' => $tipoMov, 'fecha' => $f(max(0, $dias - 5))];
            if ($tipoMov === 'reasignacion' || $tipoMov === 'conservacion') {
                $entrada['destino'] = $m[1];
                $entrada['datos_borrados'] = '1';
            } elseif ($tipoMov === 'reparacion') {
                $entrada['costo'] = $m[1];
            } else {
                $entrada['gestor_id'] = $gestorIds[$m[1]];
                $entrada['evidencia'] = 'Manifiesto DEMO-' . (100 + $i);
                $entrada['datos_borrados'] = '1';
            }
            // El ultimo movimiento de la lista queda pendiente para mostrar el flujo de validacion
            $pendiente = $tipoMov === 'reasignacion' && ($m[2] ?? false) === false;
            $movs->registrar((int) $equipo->id(), $entrada, $pendiente ? $capturista : $responsable, !$pendiente);
        }
    }
    salida('Datos de demostración cargados (3 gestores, 3 campañas, 12 equipos con diagnósticos y movimientos).');
}
