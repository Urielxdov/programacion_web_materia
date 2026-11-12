<?php
declare(strict_types=1);

/*
 * Pruebas de integracion contra la app en ejecucion (HTTP real, con sesiones por rol).
 * Requiere la base recien instalada con datos demo y la extension curl.
 *
 *   SEED_PASSWORD=... php tests/integracion.php http://127.0.0.1:8000
 *
 * Tambien sirve contra el despliegue en Render (usa una base de pruebas, crea registros).
 */

require __DIR__ . '/MiniTest.php';

$base = rtrim($argv[1] ?? (getenv('EW_URL') ?: 'http://127.0.0.1:8000'), '/');
$password = getenv('SEED_PASSWORD');
if (!$password) {
    fwrite(STDERR, "Define SEED_PASSWORD con la contraseña de las cuentas demo.\n");
    exit(1);
}

/** Navegador minimo: guarda cookies y sabe obtener el token CSRF. */
final class Cliente
{
    private string $cookies;
    public ?string $csrf = null;

    public function __construct(private string $base)
    {
        $this->cookies = tempnam(sys_get_temp_dir(), 'ew');
    }

    public function __destruct()
    {
        @unlink($this->cookies);
    }

    /** @return array{codigo:int, cuerpo:string, json:?array, ubicacion:?string, tipo:string} */
    public function pedir(string $metodo, string $ruta, ?array $datos = null, bool $json = true, bool $conCsrf = true): array
    {
        $ch = curl_init($this->base . $ruta);
        $cabeceras = ['Accept: ' . ($json ? 'application/json' : 'text/html')];
        if ($conCsrf && $this->csrf && $metodo !== 'GET') {
            $cabeceras[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        $opciones = [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEJAR      => $this->cookies,
            CURLOPT_COOKIEFILE     => $this->cookies,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        if ($datos !== null) {
            if ($json) {
                $cabeceras[] = 'Content-Type: application/json';
                $opciones[CURLOPT_POSTFIELDS] = json_encode($datos);
            } else {
                $opciones[CURLOPT_POSTFIELDS] = http_build_query($datos);
            }
        }
        $opciones[CURLOPT_HTTPHEADER] = $cabeceras;
        curl_setopt_array($ch, $opciones);
        $respuesta = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $tamCabecera = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $tipo = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $cabecera = substr((string) $respuesta, 0, $tamCabecera);
        $cuerpo = substr((string) $respuesta, $tamCabecera);
        preg_match('/^Location:\s*(.+)$/mi', $cabecera, $m);
        return [
            'codigo'    => $codigo,
            'cuerpo'    => $cuerpo,
            'json'      => json_decode($cuerpo, true),
            'ubicacion' => isset($m[1]) ? trim($m[1]) : null,
            'tipo'      => $tipo,
        ];
    }

    public function loginApi(string $usuario, string $password): array
    {
        $r = $this->pedir('POST', '/api/auth/login', ['usuario' => $usuario, 'password' => $password]);
        $this->csrf = $r['json']['csrf_token'] ?? null;
        return $r;
    }

    /** Envia un formulario HTML con el token CSRF de la pagina. */
    public function formulario(string $paginaFormulario, string $accion, array $datos): array
    {
        $pagina = $this->pedir('GET', $paginaFormulario, null, false);
        preg_match('/name="csrf-token" content="([a-f0-9]+)"/', $pagina['cuerpo'], $m);
        return $this->pedir('POST', $accion, $datos + ['_token' => $m[1] ?? ''], false, false);
    }
}

$t = new MiniTest("Pruebas de integración contra $base");
$anonimo = new Cliente($base);
$capturista = new Cliente($base);
$responsable = new Cliente($base);
$admin = new Cliente($base);
$estado = [];

// ---------------------------------------------------------------- sitio publico y acceso
$t->prueba('Páginas públicas responden 200', function () use ($t, $anonimo) {
    foreach (['/', '/informacion', '/campanas', '/login'] as $ruta) {
        $t->igual(200, $anonimo->pedir('GET', $ruta, null, false)['codigo'], $ruta);
    }
});

$t->prueba('Cabeceras de seguridad y cookie HttpOnly', function () use ($t, $base) {
    $cabeceras = implode("\n", get_headers($base . '/'));
    foreach (['Content-Security-Policy', 'X-Frame-Options', 'X-Content-Type-Options', 'HttpOnly', 'SameSite=Lax'] as $buscado) {
        $t->verdadero(stripos($cabeceras, $buscado) !== false, "Falta $buscado");
    }
});

$t->prueba('Sin sesión: el panel redirige al login y la API responde 401', function () use ($t, $anonimo) {
    $r = $anonimo->pedir('GET', '/panel', null, false);
    $t->igual(302, $r['codigo']);
    $t->verdadero(str_ends_with((string) $r['ubicacion'], '/login'));
    $t->igual(401, $anonimo->pedir('GET', '/api/equipos')['codigo']);
});

$t->prueba('Login con contraseña incorrecta: 401 con mensaje genérico', function () use ($t, $anonimo) {
    $r = $anonimo->loginApi('capturista', 'incorrecta123');
    $t->igual(401, $r['codigo']);
    $t->igual('Usuario o contraseña incorrectos.', $r['json']['error']['mensaje']);
    $t->igual('Usuario o contraseña incorrectos.', $anonimo->loginApi('noexiste', 'x12345678')['json']['error']['mensaje']);
});

$t->prueba('Login correcto de los tres roles', function () use ($t, $capturista, $responsable, $admin, $password) {
    foreach (['capturista' => $capturista, 'responsable' => $responsable, 'admin' => $admin] as $usuario => $cliente) {
        $r = $cliente->loginApi($usuario, $password);
        $t->igual(200, $r['codigo'], $usuario);
        $t->igual($usuario, $r['json']['usuario']['rol'] === 'admin' ? 'admin' : $r['json']['usuario']['usuario']);
        $t->verdadero(!isset($r['json']['usuario']['password_hash']), 'No debe exponer el hash');
    }
});

// ---------------------------------------------------------------- API de equipos
$t->prueba('POST sin token CSRF se rechaza (419)', function () use ($t, $capturista) {
    $r = $capturista->pedir('POST', '/api/equipos', ['marca' => 'X'], true, false);
    $t->igual(419, $r['codigo']);
});

$t->prueba('Capturista registra un equipo (201) y la clasificación queda pendiente', function () use ($t, $capturista, &$estado) {
    $html = $capturista->pedir('GET', '/panel/equipos/nuevo', null, false)['cuerpo'];
    preg_match('/<option value="(\d+)">Laptop \(Laptop\)/', $html, $m);
    $t->verdadero(isset($m[1]), 'No se encontró la categoría Laptop');
    $estado['laptop'] = (int) $m[1];
    $r = $capturista->pedir('POST', '/api/equipos', [
        'categoria_id' => $estado['laptop'], 'marca' => 'HP', 'modelo' => 'ProBook 440 G5', 'anio_fabricacion' => 2020,
        'fecha_ingreso' => date('Y-m-d'), 'condicion' => 'falla_parcial', 'clasificacion' => 'funcional', 'peso_kg' => 1.7,
    ]);
    $t->igual(201, $r['codigo']);
    $t->verdadero((bool) preg_match('/^EW-\d{4}-\d{4,}$/', $r['json']['data']['codigo']));
    $t->igual('pendiente', $r['json']['data']['clasificacion']);
    $t->igual(true, $r['json']['data']['requiere_borrado_datos']);
    $estado['equipo'] = $r['json']['data']['id'];
});

$t->prueba('Validación del servidor: 422 con detalle por campo', function () use ($t, $capturista, &$estado) {
    $r = $capturista->pedir('POST', '/api/equipos', [
        'categoria_id' => $estado['laptop'], 'marca' => '', 'modelo' => 'X', 'fecha_ingreso' => '2999-01-01', 'condicion' => 'rota',
    ]);
    $t->igual(422, $r['codigo']);
    foreach (['marca', 'fecha_ingreso', 'condicion'] as $campo) {
        $t->verdadero(isset($r['json']['error']['detalles'][$campo]), "Falta error de $campo");
    }
});

$t->prueba('GET /api/equipos filtra y pagina', function () use ($t, $capturista) {
    $r = $capturista->pedir('GET', '/api/equipos?q=probook&por_pagina=5');
    $t->igual(200, $r['codigo']);
    $t->verdadero($r['json']['meta']['total'] >= 1);
    $t->igual(5, $r['json']['meta']['por_pagina']);
    $r = $capturista->pedir('GET', '/api/equipos?clasificacion=gestion_especializada');
    foreach ($r['json']['data'] as $e) {
        $t->igual('gestion_especializada', $e['clasificacion']);
    }
});

$t->prueba('PATCH: capturista edita datos pero no la clasificación ni campos protegidos', function () use ($t, $capturista, &$estado) {
    $id = $estado['equipo'];
    $t->igual(200, $capturista->pedir('PATCH', "/api/equipos/$id", ['ubicacion' => 'Laboratorio 4'])['codigo']);
    $t->igual(403, $capturista->pedir('PATCH', "/api/equipos/$id", ['clasificacion' => 'funcional'])['codigo']);
    $t->igual(422, $capturista->pedir('PATCH', "/api/equipos/$id", ['estado' => 'entregado_gestor'])['codigo']);
    $t->igual(404, $capturista->pedir('GET', '/api/equipos/999999')['codigo']);
});

// ---------------------------------------------------------------- permisos por rol
$t->prueba('Autorización por rol validada en el servidor', function () use ($t, $capturista, $responsable, &$estado) {
    $t->igual(200, $capturista->pedir('GET', '/api/indicadores')['codigo']);
    $t->igual(403, $capturista->pedir('GET', '/api/indicadores/campanas')['codigo']);
    $t->igual(403, $capturista->pedir('GET', "/api/equipos/{$estado['equipo']}/recomendacion?estado_funcional=funciona")['codigo']);
    $t->igual(403, $capturista->pedir('GET', '/panel/reportes', null, false)['codigo']);
    $t->igual(403, $responsable->pedir('GET', '/panel/usuarios', null, false)['codigo']);
});

// ---------------------------------------------------------------- Strategy via API
$t->prueba('Recomendación de destino (Strategy) desde la API', function () use ($t, $responsable, &$estado) {
    $id = $estado['equipo'];
    $r = $responsable->pedir('GET', "/api/equipos/$id/recomendacion?estado_funcional=falla_parcial&costo_reparacion=1500&valor_reemplazo=9000");
    $t->igual('reparar', $r['json']['data']['decision']);
    $r = $responsable->pedir('GET', "/api/equipos/$id/recomendacion?estado_funcional=funciona&costo_reparacion=0&valor_reemplazo=9000&riesgo_seguridad=1");
    $t->igual('gestion_especializada', $r['json']['data']['decision']);
});

// ---------------------------------------------------------------- flujo web: diagnostico y movimientos
$t->prueba('Diagnóstico: con riesgo de seguridad no se permite reutilizar', function () use ($t, $responsable, &$estado) {
    $id = $estado['equipo'];
    $r = $responsable->formulario("/panel/equipos/$id/diagnostico", "/panel/equipos/$id/diagnostico", [
        'descripcion' => 'Batería inflada', 'estado_funcional' => 'funciona', 'costo_reparacion' => '0', 'valor_reemplazo' => '9000',
        'riesgo_seguridad' => '1', 'decision' => 'reutilizar', 'justificacion' => 'prueba', 'fecha' => date('Y-m-d'),
    ]);
    $t->igual(302, $r['codigo']);
    $t->igual('pendiente', $responsable->pedir('GET', "/api/equipos/$id")['json']['data']['clasificacion']);
});

$t->prueba('Diagnóstico válido actualiza la clasificación del equipo', function () use ($t, $responsable, &$estado) {
    $id = $estado['equipo'];
    $r = $responsable->formulario("/panel/equipos/$id/diagnostico", "/panel/equipos/$id/diagnostico", [
        'descripcion' => 'Falla en bisagra', 'estado_funcional' => 'falla_parcial', 'costo_reparacion' => '800', 'valor_reemplazo' => '9000',
        'decision' => 'reparar', 'fecha' => date('Y-m-d'),
    ]);
    $t->igual(302, $r['codigo']);
    $datos = $responsable->pedir('GET', "/api/equipos/$id")['json']['data'];
    $t->igual('reparable', $datos['clasificacion']);
    $t->igual('reparar', $datos['diagnosticos'][0]['recomendacion_sistema']);
});

$t->prueba('Reasignar una laptop exige confirmar el borrado de datos', function () use ($t, $capturista, &$estado) {
    $id = $estado['equipo'];
    $capturista->formulario("/panel/equipos/$id", "/panel/equipos/$id/movimientos", [
        'tipo' => 'reasignacion', 'fecha' => date('Y-m-d'), 'destino' => 'Laboratorio de Redes',
    ]);
    $movs = $capturista->pedir('GET', "/api/equipos/$id")['json']['data']['movimientos'];
    $t->igual(1, count($movs), 'Solo debe existir el ingreso');
});

$t->prueba('Movimiento del capturista queda pendiente; el responsable lo valida', function () use ($t, $capturista, $responsable, &$estado) {
    $id = $estado['equipo'];
    $capturista->formulario("/panel/equipos/$id", "/panel/equipos/$id/movimientos", [
        'tipo' => 'reparacion', 'fecha' => date('Y-m-d'), 'costo' => '750',
    ]);
    $datos = $capturista->pedir('GET', "/api/equipos/$id")['json']['data'];
    $pendiente = $datos['movimientos'][0];
    $t->igual('pendiente', $pendiente['estado_validacion']);
    $t->igual('en_inventario', $datos['estado']);

    $t->igual(403, $capturista->formulario('/panel', "/panel/movimientos/{$pendiente['id']}/validar", [])['codigo']);
    $t->igual(302, $responsable->formulario('/panel/movimientos', "/panel/movimientos/{$pendiente['id']}/validar", [])['codigo']);

    $datos = $responsable->pedir('GET', "/api/equipos/$id")['json']['data'];
    $t->igual('validado', $datos['movimientos'][0]['estado_validacion']);
    $t->igual('funcional', $datos['clasificacion']);
    $t->igual(8250.0, (float) $datos['movimientos'][0]['ahorro_estimado']);
});

// ---------------------------------------------------------------- API de indicadores
$t->prueba('API de indicadores (incluye las rutas con acentos)', function () use ($t, $responsable) {
    $g = $responsable->pedir('GET', '/api/indicadores');
    $t->igual(200, $g['codigo']);
    $t->verdadero($g['json']['data']['reparados'] >= 1);
    $t->igual(12, count($g['json']['data']['ingresos_por_mes']));
    $t->igual(200, $responsable->pedir('GET', '/api/indicadores/' . rawurlencode('campañas'))['codigo']);
    $r = $responsable->pedir('GET', '/api/indicadores/' . rawurlencode('reutilización'));
    $t->igual(200, $r['codigo']);
    $t->verdadero(isset($r['json']['data']['ahorro_estimado_total']));
});

$t->prueba('Exportación CSV del inventario', function () use ($t, $responsable) {
    $r = $responsable->pedir('GET', '/panel/reportes/inventario.csv', null, false);
    $t->igual(200, $r['codigo']);
    $t->verdadero(str_contains($r['tipo'], 'text/csv'));
    $t->verdadero(str_contains($r['cuerpo'], 'ProBook 440 G5'));
});

// ---------------------------------------------------------------- administracion
$t->prueba('Admin: no puede quitarse su propio rol', function () use ($t, $admin) {
    $html = $admin->pedir('GET', '/panel/usuarios', null, false)['cuerpo'];
    $t->verdadero(str_contains($html, 'Usuarios y roles'));
    preg_match('#/panel/usuarios/(\d+)/editar" aria-label="Editar admin"#', $html, $m);
    $r = $admin->formulario("/panel/usuarios/{$m[1]}/editar", "/panel/usuarios/{$m[1]}", ['nombre' => 'Admin', 'rol' => 'capturista', 'activo' => '1']);
    $t->igual(302, $r['codigo']);
    $t->verdadero(str_contains($admin->pedir('GET', '/panel/usuarios', null, false)['cuerpo'], 'Administrador'));
});

$t->prueba('Participación pública en una campaña activa', function () use ($t, $anonimo) {
    $html = $anonimo->pedir('GET', '/campanas', null, false)['cuerpo'];
    preg_match_all('#href="[^"]*/campanas/(\d+)"#', $html, $m);
    $registrado = false;
    foreach (array_unique($m[1]) as $id) {
        $pagina = $anonimo->pedir('GET', "/campanas/$id", null, false)['cuerpo'];
        if (str_contains($pagina, '/participar')) {
            $r = $anonimo->formulario("/campanas/$id", "/campanas/$id/participar", [
                'nombre' => 'Prueba automatizada', 'equipos_comprometidos' => '2', 'acepto_privacidad' => '1',
            ]);
            $t->igual(302, $r['codigo']);
            $registrado = true;
            break;
        }
    }
    $t->verdadero($registrado, 'No hay campañas activas con registro abierto');
});

$t->prueba('Cerrar sesión invalida el acceso a la API', function () use ($t, $capturista) {
    $t->igual(200, $capturista->pedir('POST', '/api/auth/logout')['codigo']);
    $t->igual(401, $capturista->pedir('GET', '/api/equipos')['codigo']);
});

exit($t->resumen());
