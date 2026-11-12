<section class="seccion pb-3">
    <div class="container">
        <h1 class="titulo-seccion">Campañas de recolección</h1>
        <p class="subtitulo-seccion">Consulta fechas, lugares y qué equipos se reciben. Regístrate para que podamos preparar la logística.</p>

        <div class="d-flex flex-wrap gap-2 align-items-center mt-4" id="filtrosCampanas">
            <div class="btn-group" role="group" aria-label="Filtrar por estado">
                <button type="button" class="btn btn-outline-primary btn-sm active" data-filtro="todas">Todas</button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-filtro="activa">Activas</button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-filtro="cerrada">Cerradas</button>
            </div>
            <input type="search" class="form-control form-control-sm ms-md-auto" style="max-width: 280px" placeholder="Buscar por nombre, lugar o equipo" aria-label="Buscar campañas" data-buscar-campanas>
        </div>
    </div>
</section>

<section class="container pb-5">
    <?php if (!$campanas): ?>
        <div class="tarjeta p-4 text-center text-secondary">Aún no hay campañas publicadas.</div>
    <?php else: ?>
        <div class="row g-3" id="listaCampanas">
            <?php foreach ($campanas as $c): ?>
                <div class="col-md-6 col-lg-4" data-tarjeta-campana>
                    <?= App\Core\View::parcial('tarjeta_campana', ['c' => $c]) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-secondary mt-4 d-none" data-sin-resultados>No hay campañas que coincidan con tu búsqueda.</p>
    <?php endif; ?>
</section>
