<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('America/Mexico_City');
const PRACTICAS = [
    'formularios.php' => ['Creación y análisis de formularios', '27–36', 'Captura y validación de datos mediante POST.'],
    'proteccion.php' => ['Protección con contraseña', '37–39', 'Acceso a contenido privado mediante una sesión.'],
    'opinion.php' => ['Formulario de opinión', '44–48', 'Validación y preparación de un correo electrónico.'],
    'contador.php' => ['Contador de visitas', '49–51', 'Un contador persistente almacenado en un archivo.'],
    'visitas.php' => ['Libro de visitas', '52–58', 'Publica un mensaje y consulta las firmas anteriores.'],
    'encuesta.php' => ['Encuesta web', '62–66', 'Vota y consulta una gráfica con los cuatro colores solicitados.'],
    'rss.php' => ['Lector de hilos RSS', '67–72', 'Lee un documento XML y presenta sus noticias.'],
];
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function field(string $name): string { $v = $_POST[$name] ?? ''; return is_string($v) ? trim($v) : ''; }
function csrf(): void { $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); echo '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function check_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(403); page('Solicitud caducada'); echo '<p>Recarga la página e intenta de nuevo.</p>'; endpage(); exit;
    }
}
function redirect(string $path): never { header('Location: '.$path, true, 303); exit; }
function data_dir(): string {
    $dir = getenv('UNIDAD2_DATA_DIR') ?: __DIR__.'/../data';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('No se pudo crear la carpeta de datos.');
    return $dir;
}
// El bloqueo cubre la lectura y la escritura para evitar perder cambios simultáneos.
function store(string $name, mixed $initial, ?callable $update = null): mixed {
    $file = fopen(data_dir().'/'.$name.'.php', 'c+');
    if ($file === false) throw new RuntimeException('No se pudo abrir el archivo de datos.');
    try {
        if (!flock($file, LOCK_EX)) throw new RuntimeException('No se pudo bloquear el archivo.');
        $raw = stream_get_contents($file);
        $value = $raw === '' ? $initial : json_decode(substr($raw, strlen("<?php exit; ?>\n")), true, 512, JSON_THROW_ON_ERROR);
        if ($update !== null) {
            $value = $update($value);
            $content = "<?php exit; ?>\n".json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, $content) !== strlen($content) || !fflush($file)) throw new RuntimeException('No se pudieron guardar los datos.');
        }
        return $value;
    } finally { flock($file, LOCK_UN); fclose($file); }
}
function page(string $title): void {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.e($title).' · Unidad 2</title><link rel="stylesheet" href="assets/estilos.css"></head><body><header><a class="brand" href="index.php">PHP <span>/ Unidad 2</span></a><span>Programación web</span></header><div class="layout"><nav aria-label="Prácticas"><a href="index.php">Inicio</a>';
    foreach (PRACTICAS as $path => $p) echo '<a '.(basename($_SERVER['SCRIPT_NAME']) === $path ? 'aria-current="page"' : '').' href="'.$path.'">'.e($p[0]).'</a>';
    echo '</nav><main><p class="eyebrow">CUADERNO DE PRÁCTICAS · PHP</p><h1>'.e($title).'</h1>';
    if (isset(PRACTICAS[basename($_SERVER['SCRIPT_NAME'])])) echo '<p class="intro">'.e(PRACTICAS[basename($_SERVER['SCRIPT_NAME'])][2]).'</p>';
}
function endpage(): void { echo '</main></div><footer>Unidad 2 · Formularios, archivos, sesiones y XML</footer></body></html>'; }
function errors(array $errors): void { if ($errors) echo '<div class="notice error" role="alert">'.implode('<br>', array_map('e', $errors)).'</div>'; }
set_exception_handler(function (Throwable $error): void {
    error_log((string)$error); http_response_code(500);
    echo '<p role="alert">No se pudo completar la operación. Comprueba los permisos de escritura de data y el registro de PHP.</p>';
});
