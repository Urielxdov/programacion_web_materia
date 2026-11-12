<?php
/** @var int $pagina @var int $paginas @var array $query @var string $ruta */
if ($paginas <= 1) {
    return;
}
$query = array_diff_key($query, ['pagina' => 1]);
$enlace = static fn (int $p): string => url($ruta) . '?' . http_build_query(array_filter($query + ['pagina' => $p], static fn ($v) => $v !== '' && $v !== null));
?>
<nav aria-label="Paginación" class="d-flex justify-content-center mt-3">
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e($enlace(max(1, $pagina - 1))) ?>" data-pagina="<?= max(1, $pagina - 1) ?>">Anterior</a></li>
        <?php for ($p = max(1, $pagina - 2); $p <= min($paginas, $pagina + 2); $p++): ?>
            <li class="page-item<?= $p === $pagina ? ' active' : '' ?>"><a class="page-link" href="<?= e($enlace($p)) ?>" data-pagina="<?= $p ?>"><?= $p ?></a></li>
        <?php endfor; ?>
        <li class="page-item<?= $pagina >= $paginas ? ' disabled' : '' ?>"><a class="page-link" href="<?= e($enlace(min($paginas, $pagina + 1))) ?>" data-pagina="<?= min($paginas, $pagina + 1) ?>">Siguiente</a></li>
    </ul>
</nav>
