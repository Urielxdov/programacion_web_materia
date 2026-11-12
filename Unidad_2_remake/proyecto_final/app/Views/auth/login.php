<div class="pagina-login">
    <div class="tarjeta tarjeta-login">
        <div class="text-center mb-4">
            <img src="<?= asset('img/logo.svg') ?>" alt="" width="56" height="56" class="mb-2">
            <h1 class="h4 mb-1">Acceso del personal</h1>
            <p class="small text-secondary mb-0">Capturistas, responsables ambientales y administradores.</p>
        </div>
        <form method="post" action="<?= url('login') ?>" novalidate data-validar>
            <?= csrf_campo() ?>
            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input class="form-control" id="usuario" name="usuario" autocomplete="username" required maxlength="50" value="<?= e(viejo('usuario')) ?>" autofocus>
                    <div class="invalid-feedback">Escribe tu usuario.</div>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" data-mostrar-password="#password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                    <div class="invalid-feedback">Escribe tu contraseña.</div>
                </div>
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right"></i> Entrar</button>
        </form>
        <p class="small text-secondary text-center mt-4 mb-0"><i class="bi bi-shield-lock"></i> Tras 5 intentos fallidos la cuenta se bloquea 15 minutos.</p>
    </div>
</div>
