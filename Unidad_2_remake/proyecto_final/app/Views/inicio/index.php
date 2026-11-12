<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="etiqueta"><i class="bi bi-recycle"></i> Economía circular en el campus</span>
                <h1 class="mb-3">Cada equipo merece una segunda oportunidad antes de ser residuo</h1>
                <p class="lead mb-4">E-Waste 360° registra los equipos electrónicos fuera de uso, evalúa si pueden repararse o reutilizarse y documenta su destino final con gestores verificados.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                    <a class="btn btn-ambar btn-lg" href="<?= url('campanas') ?>"><i class="bi bi-calendar-event"></i> Ver campañas</a>
                    <a class="btn btn-outline-light btn-lg" href="<?= url('informacion') ?>">¿Qué hago con mi equipo?</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="<?= asset('img/hero.svg') ?>" alt="Laptop, celular y batería dentro de un ciclo de reutilización" width="480" height="380">
            </div>
        </div>
    </div>
</section>

<section class="container cifras-publicas" aria-label="Resultados de la plataforma">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="tarjeta cifra h-100">
                <div class="valor" data-contar="<?= (int) $cifras['equipos'] ?>"><?= numero($cifras['equipos']) ?></div>
                <div class="texto">equipos registrados en el inventario</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tarjeta cifra h-100">
                <div class="valor" data-contar="<?= (int) $cifras['aprovechados'] ?>"><?= numero($cifras['aprovechados']) ?></div>
                <div class="texto">equipos reparados o reutilizados</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tarjeta cifra h-100">
                <div class="valor"><span data-contar="<?= (float) $cifras['kg_gestionados'] ?>" data-decimales="1"><?= numero($cifras['kg_gestionados'], 1) ?></span> kg</div>
                <div class="texto">entregados a gestores con autorización verificada</div>
            </div>
        </div>
    </div>
</section>

<section class="seccion">
    <div class="container">
        <h2 class="titulo-seccion">El problema de los residuos electrónicos</h2>
        <p class="subtitulo-seccion mb-4">Según el <em>Global E-waste Monitor 2024</em>, en 2022 se generaron alrededor de 62 millones de toneladas de residuos electrónicos en el mundo y solo cerca del 22% se recolectó y recicló de manera documentada.</p>
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="tarjeta tarjeta-info">
                    <span class="icono-circulo rojo mb-3"><i class="bi bi-droplet-half"></i></span>
                    <h3 class="h5">Impacto ambiental</h3>
                    <p class="small mb-0">Plomo, mercurio, cadmio y retardantes de flama pueden contaminar suelo y agua cuando los equipos se tiran a la basura o se queman.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="tarjeta tarjeta-info">
                    <span class="icono-circulo ambar mb-3"><i class="bi bi-people"></i></span>
                    <h3 class="h5">Impacto social</h3>
                    <p class="small mb-0">El desmantelamiento informal expone a trabajadores y comunidades a sustancias tóxicas sin ninguna protección.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="tarjeta tarjeta-info">
                    <span class="icono-circulo mb-3"><i class="bi bi-gem"></i></span>
                    <h3 class="h5">Recursos perdidos</h3>
                    <p class="small mb-0">Los equipos contienen cobre, oro, aluminio y tierras raras que se pierden si no llegan a un reciclador adecuado.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="tarjeta tarjeta-info">
                    <span class="icono-circulo verde mb-3"><i class="bi bi-shield-lock"></i></span>
                    <h3 class="h5">Datos personales</h3>
                    <p class="small mb-0">Un disco o celular desechado sin borrado seguro puede exponer información personal o institucional.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="seccion seccion-alterna">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <h2 class="titulo-seccion">Así funciona E-Waste 360°</h2>
                <p class="subtitulo-seccion">El sistema sigue la jerarquía de residuos: primero se intenta aprovechar el equipo y solo al final se envía a gestión especializada.</p>
                <a class="btn btn-primary mt-2" href="<?= url('informacion') ?>">Conoce las recomendaciones</a>
            </div>
            <div class="col-lg-7">
                <ol class="list-unstyled jerarquia tarjeta p-4 mb-0">
                    <li class="d-flex gap-3"><span class="icono-circulo verde"><i class="bi bi-1-circle"></i></span><div><strong>Registro</strong><br><span class="small text-secondary">Cada equipo recibe un identificador único (EW-AAAA-NNNN) y queda en el inventario.</span></div></li>
                    <li class="d-flex gap-3"><span class="icono-circulo"><i class="bi bi-2-circle"></i></span><div><strong>Diagnóstico</strong><br><span class="small text-secondary">Un responsable evalúa el equipo y el sistema recomienda reutilizar, conservar, reparar o enviar a gestión especializada.</span></div></li>
                    <li class="d-flex gap-3"><span class="icono-circulo ambar"><i class="bi bi-3-circle"></i></span><div><strong>Destino documentado</strong><br><span class="small text-secondary">Reparaciones, reasignaciones y entregas se validan y quedan en el historial del equipo.</span></div></li>
                    <li class="d-flex gap-3"><span class="icono-circulo rojo"><i class="bi bi-4-circle"></i></span><div><strong>Indicadores</strong><br><span class="small text-secondary">Se miden equipos aprovechados, ahorro estimado y kilos entregados a gestores verificados.</span></div></li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="seccion">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h2 class="titulo-seccion mb-1">Campañas activas</h2>
                <p class="subtitulo-seccion mb-0">Lleva tus equipos a una campaña de recolección y regístrate para ayudarnos a planear.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= url('campanas') ?>">Todas las campañas</a>
        </div>
        <?php if (!$campanas): ?>
            <div class="tarjeta p-4 text-center text-secondary">Por ahora no hay campañas activas. ¡Vuelve pronto!</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($campanas as $c): ?>
                    <div class="col-md-6 col-lg-4">
                        <?= App\Core\View::parcial('tarjeta_campana', ['c' => $c]) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
