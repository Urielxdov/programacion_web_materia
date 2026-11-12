<?php
use App\Models\Usuario;

/** @var ?Usuario $usuario */
$editando = $usuario !== null;
?>
<div class="encabezado-pagina">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= url('panel/usuarios') ?>">Usuarios</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $editando ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editando ? 'Editar ' . e($usuario->usuario) : 'Nuevo usuario' ?></h1>
    </div>
</div>

<form class="tarjeta p-4" style="max-width: 760px" method="post" action="<?= url($editando ? 'panel/usuarios/' . $usuario->id : 'panel/usuarios') ?>" novalidate data-validar>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="nombre">Nombre completo *</label>
            <input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" required maxlength="120" value="<?= e(viejo('nombre', $usuario?->nombre ?? '')) ?>">
            <?= mensaje_error('nombre') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="usuario">Usuario *</label>
            <input class="form-control<?= clase_error('usuario') ?>" id="usuario" name="usuario" <?= $editando ? 'disabled' : 'required' ?> pattern="[A-Za-z0-9._\-]{3,50}" maxlength="50" value="<?= e(viejo('usuario', $usuario?->usuario ?? '')) ?>" autocomplete="off">
            <div class="invalid-feedback">De 3 a 50 caracteres: letras, números, punto o guiones.</div>
            <?= mensaje_error('usuario') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Correo</label>
            <input class="form-control<?= clase_error('email') ?>" type="email" id="email" name="email" maxlength="150" value="<?= e(viejo('email', $usuario?->email ?? '')) ?>">
            <?= mensaje_error('email') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="rol">Rol *</label>
            <select class="form-select<?= clase_error('rol') ?>" id="rol" name="rol" required><?= opciones(Usuario::ROLES, viejo('rol', $usuario?->rol ?? 'capturista')) ?></select>
            <?= mensaje_error('rol') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password"><?= $editando ? 'Nueva contraseña' : 'Contraseña temporal *' ?></label>
            <div class="input-group has-validation">
                <input class="form-control<?= clase_error('password') ?>" type="password" id="password" name="password" minlength="8" <?= $editando ? '' : 'required' ?> autocomplete="new-password" data-fuerza-password>
                <button class="btn btn-outline-secondary" type="button" data-mostrar-password="#password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                <div class="invalid-feedback">Mínimo 8 caracteres con letras y números.</div>
            </div>
            <?= mensaje_error('password') ?>
            <div class="progress mt-2" style="height: 6px" aria-hidden="true"><div class="progress-bar" data-barra-fuerza style="width: 0%"></div></div>
            <div class="form-text"><?= $editando ? 'Déjala vacía para conservar la actual.' : 'Compártela por un medio seguro.' ?></div>
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <div class="form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" role="switch" value="1" id="activo" name="activo" <?= viejo('activo', $usuario?->activo ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Cuenta activa</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= url('panel/usuarios') ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar</button>
    </div>
</form>
