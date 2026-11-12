<section class="seccion pb-4">
    <div class="container">
        <h1 class="titulo-seccion">Aprende y actúa</h1>
        <p class="subtitulo-seccion">Antes de desechar un equipo, piensa en el orden correcto: <strong>reducir, reparar, reutilizar</strong> y, solo al final, <strong>reciclar</strong> con un gestor adecuado.</p>
    </div>
</section>

<section class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="tarjeta p-4 mb-4">
                <h2 class="h4 mb-3"><i class="bi bi-hourglass-split text-success"></i> Prolonga la vida útil de tus dispositivos</h2>
                <div class="accordion" id="consejos">
                    <?php
                    $consejos = [
                        ['Cuida la batería', 'Evita descargarla por completo y no la dejes expuesta al calor. Si tu equipo lo permite, limita la carga al 80% cuando lo uses conectado.'],
                        ['Limpieza y ventilación', 'El polvo eleva la temperatura y acorta la vida de los componentes. Limpia rejillas y ventiladores al menos dos veces al año.'],
                        ['Actualiza antes de reemplazar', 'Más memoria RAM o un disco de estado sólido pueden darle varios años más a una computadora lenta por una fracción del precio de una nueva.'],
                        ['Protege físicamente', 'Fundas, protectores de pantalla y reguladores de voltaje previenen las fallas más comunes.'],
                        ['Repara con técnicos confiables', 'Pide un diagnóstico por escrito y compara el costo con el de un equipo nuevo. Muchas fallas (pantalla, batería, puerto de carga) son reparables.'],
                    ];
                    foreach ($consejos as $i => [$titulo, $texto]): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button<?= $i ? ' collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#consejo<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>" aria-controls="consejo<?= $i ?>"><?= e($titulo) ?></button>
                            </h3>
                            <div id="consejo<?= $i ?>" class="accordion-collapse collapse<?= $i ? '' : ' show' ?>" data-bs-parent="#consejos">
                                <div class="accordion-body small"><?= e($texto) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="tarjeta tarjeta-info">
                        <span class="icono-circulo ambar mb-2"><i class="bi bi-tools"></i></span>
                        <h3 class="h6">Reparación</h3>
                        <p class="small mb-0">Conviene cuando el costo es bajo frente a un equipo nuevo. Evita un residuo y ahorra dinero.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tarjeta tarjeta-info">
                        <span class="icono-circulo verde mb-2"><i class="bi bi-arrow-repeat"></i></span>
                        <h3 class="h6">Reutilización</h3>
                        <p class="small mb-0">Un equipo que ya no te sirve puede ser útil en un laboratorio, una oficina o una escuela.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tarjeta tarjeta-info">
                        <span class="icono-circulo rojo mb-2"><i class="bi bi-truck"></i></span>
                        <h3 class="h6">Entrega responsable</h3>
                        <p class="small mb-0">Si ya no tiene arreglo, entrégalo a un gestor con autorización y pide comprobante.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="tarjeta p-4 mb-4" id="listaEntrega">
                <h2 class="h5 mb-1"><i class="bi bi-list-check text-success"></i> Lista antes de entregar tu equipo</h2>
                <p class="small text-secondary">Marca cada paso conforme lo completes.</p>
                <div class="progress mb-3" role="progressbar" aria-label="Avance de la lista" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-bar bg-success" style="width: 0%"></div>
                </div>
                <div class="lista-verificacion">
                    <?php foreach ([
                        'Respaldé mi información (fotos, documentos, contactos).',
                        'Cerré sesión en mis cuentas y desvinculé el dispositivo.',
                        'Hice un borrado seguro o restablecí de fábrica.',
                        'Retiré la tarjeta SIM y la memoria externa.',
                        'Cubrí con cinta los polos de baterías y pilas sueltas.',
                        'Identifiqué una campaña o un gestor con autorización.',
                    ] as $i => $paso): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="paso<?= $i ?>">
                            <label class="form-check-label small" for="paso<?= $i ?>"><?= e($paso) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-success small mt-3 mb-0 d-none" data-lista-completa>
                    <i class="bi bi-emoji-smile"></i> ¡Listo! Tu equipo puede entregarse de forma segura.
                    <a href="<?= url('campanas') ?>" class="alert-link">Busca una campaña</a>.
                </div>
            </div>

            <div class="tarjeta p-4">
                <h2 class="h5"><i class="bi bi-book text-success"></i> Recursos educativos</h2>
                <ul class="small mb-0">
                    <li><a href="https://ewastemonitor.info/" target="_blank" rel="noopener noreferrer">Global E-waste Monitor</a> — estadísticas mundiales.</li>
                    <li><a href="https://www.gob.mx/semarnat" target="_blank" rel="noopener noreferrer">SEMARNAT</a> — normatividad ambiental en México.</li>
                    <li><a href="https://www.unep.org/" target="_blank" rel="noopener noreferrer">Programa de la ONU para el Medio Ambiente</a>.</li>
                </ul>
            </div>
        </div>
    </div>
</section>
