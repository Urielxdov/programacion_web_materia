<?php
// Campos compartidos por altas.php y editar.php.
// Espera $inv (arreglo con los valores actuales o vacio para altas).
$v = fn($campo, $def = '') => htmlspecialchars((string) ($inv[$campo] ?? $def), ENT_QUOTES, 'UTF-8');
$sel = fn($campo, $valor, $def) => (($inv[$campo] ?? $def) === $valor) ? 'selected' : '';
?>
<div class="grid-form">
    <label>Nombres
        <input type="text" name="nombres" maxlength="100" value="<?= $v('nombres') ?>" required>
    </label>
    <label>Apellidos
        <input type="text" name="apellidos" maxlength="100" value="<?= $v('apellidos') ?>" required>
    </label>
    <label>Correo
        <input type="email" name="correo" maxlength="100" value="<?= $v('correo') ?>" required>
    </label>
    <label>Teléfono
        <input type="tel" name="telefono" maxlength="20" pattern="[0-9 +\-]{7,20}" value="<?= $v('telefono') ?>" required>
    </label>
    <label class="completo">Dirección
        <textarea name="direccion" rows="2" required><?= $v('direccion') ?></textarea>
    </label>
    <label>Fecha de llegada
        <input type="date" name="fecha_llegada" value="<?= $v('fecha_llegada') ?>" required>
    </label>
    <label>Fecha de salida
        <input type="date" name="fecha_salida" value="<?= $v('fecha_salida') ?>" required>
    </label>
    <label>Número de invitados
        <input type="number" name="num_invitados" min="1" max="50" value="<?= $v('num_invitados', '1') ?>" required>
    </label>
    <label>Tipo de habitación
        <select name="tipo_habitacion" required>
            <option value="Individual" <?= $sel('tipo_habitacion', 'Individual', 'Individual') ?>>Individual</option>
            <option value="Doble" <?= $sel('tipo_habitacion', 'Doble', 'Individual') ?>>Doble</option>
            <option value="Suite" <?= $sel('tipo_habitacion', 'Suite', 'Individual') ?>>Suite</option>
        </select>
    </label>
    <label>¿Requiere transporte?
        <select name="transporte" required>
            <option value="NO" <?= $sel('transporte', 'NO', 'NO') ?>>NO</option>
            <option value="SI" <?= $sel('transporte', 'SI', 'NO') ?>>SI</option>
        </select>
    </label>
    <label>¿Dieta especial?
        <select name="dieta" required>
            <option value="NO" <?= $sel('dieta', 'NO', 'NO') ?>>NO</option>
            <option value="SI" <?= $sel('dieta', 'SI', 'NO') ?>>SI</option>
        </select>
    </label>
</div>
