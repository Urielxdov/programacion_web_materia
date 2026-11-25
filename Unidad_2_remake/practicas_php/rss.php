<?php
require __DIR__.'/includes/app.php';
$sources = ['ejemplo'=>['Canal de la Unidad 2', __DIR__.'/assets/noticias.xml'], 'bbc'=>['BBC Mundo (requiere Internet)', 'https://feeds.bbci.co.uk/mundo/rss.xml']];
$source = is_string($_GET['fuente'] ?? null) ? $_GET['fuente'] : 'ejemplo';
$errors = []; $items = []; $title = '';
if (!isset($sources[$source])) { $errors[] = 'Selecciona una fuente disponible.'; $source = 'ejemplo'; }
try {
    if ($source === 'ejemplo') $xml = file_get_contents($sources[$source][1]);
    else {
        if (!function_exists('curl_init')) throw new RuntimeException('La fuente remota requiere la extensión cURL de PHP.');
        $curl = curl_init($sources[$source][1]);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>4, CURLOPT_TIMEOUT=>8, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_USERAGENT=>'Unidad2-RSS/1.0']);
        curl_setopt($curl, CURLOPT_XFERINFOFUNCTION, function ($handle, $total, $downloaded) { return $downloaded > 2000000 ? 1 : 0; });
        curl_setopt($curl, CURLOPT_NOPROGRESS, false);
        $xml = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($xml === false || $status !== 200) throw new RuntimeException('No se pudo descargar el canal remoto. Prueba el canal local o revisa tu conexión.');
    }
    if ($xml === false || strlen($xml) > 2000000 || stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) throw new RuntimeException('El canal XML no es válido.');
    libxml_use_internal_errors(true);
    $feed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
    if ($feed === false || !isset($feed->channel)) throw new RuntimeException('El archivo no contiene un canal RSS válido.');
    $title = (string)$feed->channel->title;
    foreach ($feed->channel->item as $item) {
        $link = (string)$item->link;
        if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($link, PHP_URL_SCHEME) ?: ''), ['http','https'], true)) $link = '';
        $items[] = ['titulo'=>(string)$item->title, 'descripcion'=>strip_tags((string)$item->description), 'enlace'=>$link, 'fecha'=>(string)$item->pubDate];
        if (count($items) >= 20) break;
    }
    libxml_clear_errors();
} catch (RuntimeException $error) { $errors[] = $error->getMessage(); }
page('Lector de hilos RSS'); errors($errors);
?>
<form method="get" class="panel"><label>Fuente de noticias<select name="fuente"><?php foreach ($sources as $key => [$label]): ?><option value="<?= $key ?>" <?= $key === $source ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><button>Leer canal</button></form>
<?php if ($source === 'ejemplo'): ?><p class="notice">Canal local de demostración: las noticias son material de práctica, no noticias en vivo.</p><?php endif; ?>
<h2><?= e($title) ?></h2>
<?php foreach ($items as $item): ?><article class="panel news"><span class="tag">RSS · XML</span><h3><?= e($item['titulo']) ?></h3><small><?= e($item['fecha']) ?></small><p><?= e($item['descripcion']) ?></p><?php if ($item['enlace']): ?><a href="<?= e($item['enlace']) ?>" target="_blank" rel="noopener noreferrer">Abrir noticia ↗</a><?php endif; ?></article><?php endforeach; ?>
<?php if (!$items && !$errors): ?><p>Este canal todavía no tiene noticias.</p><?php endif; endpage(); ?>
