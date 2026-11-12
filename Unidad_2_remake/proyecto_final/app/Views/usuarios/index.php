<div class="encabezado-pagina">
    <div>
        <h1>Usuarios y roles</h1>
        <p class="text-secondary small mb-0">Capturista: registra equipos · Responsable ambiental: diagnostica, valida y consulta reportes · Administrador: todo lo anterior y la configuración.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('panel/usuarios/nuevo') ?>"><i class="bi bi-person-plus"></i> Nuevo usuario</a>
</div>

<div class="tarjeta">
    <div class="table-responsive">
        <table class="table tabla-ew table-hover mb-0 tabla-responsiva-movil">
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr class="<?= $u->activo ? '' : 'text-secondary' ?>">
                    <td data-etiqueta="Nombre"><?= e($u->nombre) ?></td>
                    <td data-etiqueta="Usuario"><code><?= e($u->usuario) ?></code></td>
                    <td data-etiqueta="Correo" class="small"><?= e($u->email ?? '—') ?></td>
                    <td data-etiqueta="Rol"><?= e($u->nombreRol()) ?></td>
                    <td data-etiqueta="Estado"><?= $u->activo ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>' ?>
                        <?= $u->estaBloqueado() ? '<span class="badge text-bg-danger">Bloqueado</span>' : '' ?></td>
                    <td data-etiqueta="Último acceso" class="small"><?= fecha($u->ultimoAcceso, true) ?></td>
                    <td class="text-end"><a class="btn btn-outline-secondary btn-sm" href="<?= url('panel/usuarios/' . $u->id . '/editar') ?>" aria-label="Editar <?= e($u->usuario) ?>"><i class="bi bi-pencil"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
