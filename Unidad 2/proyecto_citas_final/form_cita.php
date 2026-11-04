<?php
// Formulario compartido (usuario y admin). $c = valores actuales, $usuarios = lista para el select del admin.
function form_cita(array $c, string $action, bool $editando, ?array $usuarios = null): void {
    global $CFG;
    ?>
    <form method="POST" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="<?= $editando ? 'modificar' : 'crear' ?>">
        <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">

        <?php if ($usuarios !== null): ?>
            <label>Usuario dueño de la cita:</label>
            <select name="usuario_id">
                <option value="">— Sin usuario (paciente de mostrador) —</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($c['usuario_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= e($u['nombre']) ?> (<?= e($u['correo']) ?>)</option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label>Nombre del paciente:</label>
        <input type="text" name="paciente" value="<?= e($c['paciente'] ?? '') ?>" required>

        <label>Número de teléfono:</label>
        <input type="tel" name="telefono" value="<?= e($c['telefono'] ?? '') ?>" placeholder="Ej. 5512345678" required>

        <label>Correo electrónico:</label>
        <input type="email" name="correo" value="<?= e($c['correo'] ?? '') ?>" placeholder="Recibirás la confirmación aquí" required>

        <label>Fecha:</label>
        <input type="date" name="fecha" value="<?= e($c['fecha'] ?? '') ?>" <?= $usuarios === null ? 'min="' . date('Y-m-d') . '"' : '' ?> required>

        <label>Hora de inicio:</label>
        <input type="time" name="hora" value="<?= e($c['hora'] ?? '') ?>" min="<?= $CFG['agenda']['inicio'] ?>" max="<?= $CFG['agenda']['ultima'] ?>" step="300" required>
        <div class="aviso">⚠ Cada cita reserva <strong><?= $CFG['agenda']['duracion'] ?> minutos</strong> desde su hora de inicio. Si alguien reservó a las 10:00, el intervalo 10:00–10:30 de ese día no está disponible.
            Atendemos de <?= $CFG['agenda']['inicio'] ?> a <?= $CFG['agenda']['ultima'] ?> (hora de inicio).</div>

        <label>Breve descripción del problema:</label>
        <textarea name="problema" rows="3"><?= e($c['problema'] ?? '') ?></textarea>

        <button type="submit"><?= $editando ? 'Actualizar cita' : 'Agendar y confirmar' ?></button>
        <?php if ($editando): ?><div class="centro"><a href="<?= e($action) ?>">Cancelar edición</a></div><?php endif; ?>
    </form>
    <?php
}
