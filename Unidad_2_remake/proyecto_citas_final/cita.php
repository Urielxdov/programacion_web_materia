<?php
// Formulario de cita.
//   Alta (sin ?id):     administrador y asistente.
//   Modificar (?id=N):  solo administrador.
require __DIR__ . '/lib.php';
$u = require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$actual = null;
if ($id) {
    require_admin();
    $actual = obtener_cita($id);
    if (!$actual || $actual['estado'] === 'cancelada') {
        flash('La cita no existe o está cancelada.', 'error');
        redirect('citas.php');
    }
}

$errores = [];
$c = $actual ?? ['pais' => 'México'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$d, $errores] = validar_cita($_POST, es_admin(), $id ?: null);
    if (!$errores) {
        if ($id) {
            modificar_cita($id, $d);
            flash("Cita #$id modificada. Se notificó al paciente por correo.");
        } else {
            $nuevo = crear_cita($d, (int)$u['id']);
            flash("Cita #$nuevo registrada. Se envió la confirmación a {$d['email']}.");
        }
        redirect('citas.php');
    }
    $c = $d;
}

global $CFG;
$a = $CFG['agenda'];
$hoy = date('Y-m-d');
$v = fn(string $k) => e($c[$k] ?? '');

layout_ini($id ? "Modificar cita #$id" : 'Formulario de cita');
?>
<h1 class="titulo-form"><?= $id ? "Modificar cita #$id" : 'Formulario de cita' ?></h1>
<p class="intro">Si desea concertar una cita de consulta con el Dr. Xavier, complete este formulario de cita y nos pondremos en contacto con usted lo antes posible.</p>
<hr>

<?php if ($errores): ?>
<div class="mensaje error"><ul><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" class="form-cita">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?: '' ?>">

    <div class="fila">
        <label for="fecha">Día de la cita</label>
        <div class="campos"><input type="date" id="fecha" name="fecha" value="<?= $v('fecha') ?>" <?= es_admin() ? '' : "min=\"$hoy\"" ?> required></div>
    </div>

    <div class="fila">
        <label for="hora">Hora de la cita</label>
        <div class="campos">
            <input type="time" id="hora" name="hora" value="<?= $v('hora') ?>" min="<?= $a['inicio'] ?>" max="<?= $a['ultima'] ?>" step="300" required>
            <small class="ancho ayuda">Horario: <?= $a['inicio'] ?> a <?= $a['ultima'] ?>. Cada cita dura <?= $a['duracion'] ?> minutos.</small>
        </div>
    </div>

    <div class="fila">
        <label for="nombre">Nombre</label>
        <div class="campos">
            <input type="text" id="nombre" name="nombre" placeholder="Nombre" value="<?= $v('nombre') ?>" maxlength="60" required>
            <input type="text" name="apellido" placeholder="Apellido" aria-label="Apellido" value="<?= $v('apellido') ?>" maxlength="60" required>
        </div>
    </div>

    <div class="fila">
        <label for="direccion">Dirección</label>
        <div class="campos">
            <input class="ancho" type="text" id="direccion" name="direccion" placeholder="Dirección" value="<?= $v('direccion') ?>" maxlength="150" required>
            <input class="ancho" type="text" name="direccion2" placeholder="Dirección 2" aria-label="Dirección 2" value="<?= $v('direccion2') ?>" maxlength="150">
            <input type="text" name="ciudad" placeholder="Ciudad" aria-label="Ciudad" value="<?= $v('ciudad') ?>" maxlength="80" required>
            <input type="text" name="region" placeholder="Región" aria-label="Región" value="<?= $v('region') ?>" maxlength="80" required>
            <input type="text" name="codigo_postal" placeholder="Código postal" aria-label="Código postal" value="<?= $v('codigo_postal') ?>" maxlength="10" required>
            <select name="pais" aria-label="País" required>
                <option value="">País</option>
                <?php foreach (PAISES as $p): ?>
                    <option <?= ($c['pais'] ?? '') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="fila">
        <label for="fecha_nacimiento">Fecha de nacimiento</label>
        <div class="campos"><input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= $v('fecha_nacimiento') ?>" min="1900-01-01" max="<?= $hoy ?>" required></div>
    </div>

    <div class="fila">
        <label for="email">Email</label>
        <div class="campos"><input class="ancho" type="email" id="email" name="email" placeholder="correo@ejemplo.com" value="<?= $v('email') ?>" maxlength="150" required></div>
    </div>

    <div class="fila">
        <label for="curp">CURP</label>
        <div class="campos"><input class="ancho mayus" type="text" id="curp" name="curp" placeholder="18 caracteres" value="<?= $v('curp') ?>" minlength="18" maxlength="18" pattern="[A-Za-z]{4}\d{6}[HMXhmx][A-Za-z]{5}[A-Za-z\d]\d" title="CURP de 18 caracteres" required></div>
    </div>

    <div class="fila">
        <label for="telefono">Teléfono</label>
        <div class="campos"><input type="tel" id="telefono" name="telefono" placeholder="10 dígitos" value="<?= $v('telefono') ?>" maxlength="14" required></div>
    </div>

    <div class="acciones-form">
        <button type="submit" class="btn-enviar"><?= $id ? 'Guardar cambios' : 'Enviar' ?></button>
        <?php if ($id): ?><a href="citas.php">Cancelar edición</a><?php endif; ?>
    </div>
</form>
<?php layout_fin();
