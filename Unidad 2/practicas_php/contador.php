<?php
require __DIR__.'/includes/app.php';
$count = store('contador', 0, fn($n) => (int)$n + 1);
page('Contador de visitas');
?>
<section class="panel"><p>Visitas acumuladas a esta práctica</p><p class="counter"><?= number_format($count) ?></p><p>Cada carga de esta página incrementa el contador, incluidas las recargas. El total se conserva en un archivo del servidor.</p><a class="button" href="contador.php">Registrar otra visita</a></section>
<?php endpage(); ?>
