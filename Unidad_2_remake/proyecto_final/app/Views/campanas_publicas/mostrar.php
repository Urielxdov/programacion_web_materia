<?php /** @var App\Models\Campana $campana */ ?>
<section class="seccion pb-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= url('campanas') ?>">Campañas</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($campana->nombre) ?></li>
            </ol>
        </nav>
        <h1 class="titulo-seccion"><?= e($campana->nombre) ?></h1>
        <p class="subtitulo-seccion"><?= nl2br(e($campana->descripcion)) ?></p>
    </div>
</section>

<section class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="tarjeta p-4 mb-4">
                <div class="row g-3">
                    <div class="col-sm-6 d-flex gap-3">
                        <span class="icono-circulo"><i class="bi bi-calendar3"></i></span>
                        <div><div class="small text-secondary">Fechas</div><strong><?= fecha($campana->fechaInicio) ?> – <?= fecha($campana->fechaFin) ?></strong></div>
                    </div>
                    <div class="col-sm-6 d-flex gap-3">
                        <span class="icono-circulo ambar"><i class="bi bi-geo-alt"></i></span>
                        <div><div class="small text-secondary">Lugar</div><strong><?= e($campana->ubicacion) ?></strong></div>
                    </div>
                </div>
                <hr>
                <h2 class="h6">Equipos que se reciben</h2>
                <?php foreach ($campana->listaTiposAceptados() as $tipo): ?>
                    <span class="chip"><?= e($tipo) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="tarjeta p-4">
                <h2 class="h5"><i class="bi bi-shield-check text-success"></i> Entrega tus dispositivos de forma segura</h2>
                <ul class="small mb-0">
                    <li>Respalda tu información y haz un borrado seguro o restablecimiento de fábrica.</li>
                    <li>Retira la tarjeta SIM, memorias y discos externos.</li>
                    <li>Cubre los polos de baterías y pilas con cinta aislante; no entregues baterías infladas junto con otros equipos.</li>
                    <li>Lleva los equipos completos: no los desarmes.</li>
                    <li>Los equipos que funcionan se reasignan o donan; los demás se entregan a gestores con autorización verificada.</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="tarjeta p-4">
                <?php if ($campana->aceptaParticipaciones()): ?>
                    <h2 class="h5">Registra tu participación</h2>
                    <p class="small text-secondary">Solo pedimos lo indispensable para planear la recolección.</p>
                    <form method="post" action="<?= url('campanas/' . $campana->id . '/participar') ?>" class="needs-validation" novalidate data-validar>
                        <?= csrf_campo() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nombre">Nombre o grupo *</label>
                            <input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" required maxlength="120"
                                   value="<?= e(viejo('nombre', $usuario['nombre'] ?? '')) ?>">
                            <div class="invalid-feedback">Escribe tu nombre o el de tu grupo.</div>
                            <?= mensaje_error('nombre') ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="correo">Correo <span class="text-secondary small">(opcional, para avisos de la campaña)</span></label>
                            <input class="form-control<?= clase_error('correo') ?>" type="email" id="correo" name="correo" maxlength="150" value="<?= e(viejo('correo')) ?>">
                            <div class="invalid-feedback">Escribe un correo válido.</div>
                            <?= mensaje_error('correo') ?>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-5">
                                <label class="form-label" for="equipos_comprometidos">Equipos *</label>
                                <input class="form-control<?= clase_error('equipos_comprometidos') ?>" type="number" min="1" max="200" id="equipos_comprometidos" name="equipos_comprometidos" required value="<?= e(viejo('equipos_comprometidos', '1')) ?>">
                                <div class="invalid-feedback">Entre 1 y 200.</div>
                                <?= mensaje_error('equipos_comprometidos') ?>
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="tipos_equipo">¿Qué llevarás?</label>
                                <input class="form-control" id="tipos_equipo" name="tipos_equipo" maxlength="255" list="tiposSugeridos" value="<?= e(viejo('tipos_equipo')) ?>" placeholder="Ej. laptop y cables">
                                <datalist id="tiposSugeridos">
                                    <?php foreach ($campana->listaTiposAceptados() as $tipo): ?><option value="<?= e($tipo) ?>"><?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input<?= clase_error('acepto_privacidad') ?>" type="checkbox" value="1" id="acepto_privacidad" name="acepto_privacidad" required>
                            <label class="form-check-label small" for="acepto_privacidad">Acepto que mis datos se usen solo para organizar esta campaña y se muestren de forma parcial al personal responsable.</label>
                            <?= mensaje_error('acepto_privacidad') ?>
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle"></i> Registrarme</button>
                    </form>
                <?php else: ?>
                    <div class="text-center py-3">
                        <span class="icono-circulo mb-3"><i class="bi bi-calendar-x"></i></span>
                        <h2 class="h5">Registro no disponible</h2>
                        <p class="small text-secondary mb-0">Esta campaña ya no recibe registros. Consulta las <a href="<?= url('campanas') ?>">campañas activas</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
