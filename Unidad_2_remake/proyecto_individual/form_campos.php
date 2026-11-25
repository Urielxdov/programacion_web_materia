<?php
// Campos compartidos por altas.php y editar.php.
// Espera $inv (arreglo con los valores actuales o vacio para altas).
require_once __DIR__ . '/catalogos.php';
$v = fn($campo, $def = '') => htmlspecialchars((string) ($inv[$campo] ?? $def), ENT_QUOTES, 'UTF-8');
$hoy = date('Y-m-d');
// Solo el administrador puede registrar o corregir citas con fecha pasada
$minFecha = ($_SESSION['rol'] ?? '') === 'admin' ? '' : 'min="' . $hoy . '"';
$paisActual = $inv['pais'] ?? 'México';
?>
<div class="grid-form">
    <label>Día de la cita
        <input type="date" name="fecha_cita" value="<?= $v('fecha_cita') ?>" <?= $minFecha ?> required>
    </label>
    <label>Hora de la cita
        <input type="time" name="hora_cita" step="300" value="<?= $v('hora_cita') ?>" required>
    </label>
    <label>Nombre
        <input type="text" name="nombre" maxlength="60" placeholder="Nombre" value="<?= $v('nombre') ?>" required>
    </label>
    <label>Apellido
        <input type="text" name="apellido" maxlength="60" placeholder="Apellido" value="<?= $v('apellido') ?>" required>
    </label>
    <label class="completo">Dirección
        <input type="text" name="direccion" maxlength="150" placeholder="Dirección" value="<?= $v('direccion') ?>" required>
    </label>
    <label class="completo">Dirección 2
        <input type="text" name="direccion2" maxlength="150" placeholder="Dirección 2" value="<?= $v('direccion2') ?>">
    </label>
    <label>Ciudad
        <input type="text" name="ciudad" maxlength="80" placeholder="Ciudad" value="<?= $v('ciudad') ?>" required>
    </label>
    <label>Región
        <input type="text" name="region" maxlength="80" placeholder="Región" value="<?= $v('region') ?>" required>
    </label>
    <label>Código postal
        <input type="text" name="codigo_postal" maxlength="10" placeholder="Código postal" value="<?= $v('codigo_postal') ?>" required>
    </label>
    <label>País
        <select name="pais" required>
            <?php foreach (PAISES as $p): ?>
                <option value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>" <?= $paisActual === $p ? 'selected' : '' ?>><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Fecha de nacimiento
        <input type="date" name="fecha_nacimiento" min="1900-01-01" max="<?= $hoy ?>" value="<?= $v('fecha_nacimiento') ?>" required>
    </label>
    <label>Email
        <input type="email" name="email" maxlength="150" placeholder="correo@ejemplo.com" value="<?= $v('email') ?>" required>
    </label>
    <label>CURP
        <input type="text" name="curp" class="mayus" minlength="18" maxlength="18" placeholder="18 caracteres"
               pattern="[A-Za-z]{4}\d{6}[HMXhmx][A-Za-z]{5}[A-Za-z\d]\d" title="CURP de 18 caracteres: 4 letras, fecha AAMMDD, sexo (H, M o X), entidad de 2 letras, 3 consonantes y 2 caracteres finales. Ejemplo: HEGE040605HGTRNR09" value="<?= $v('curp') ?>" required>
    </label>
    <label>Teléfono
        <input type="tel" name="telefono" maxlength="14" placeholder="10 dígitos" value="<?= $v('telefono') ?>" required>
    </label>
</div>
