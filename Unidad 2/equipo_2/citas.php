<?php

try {
    $conexion = new PDO('mysql:host=127.0.0.1;port=3307;dbname=clinica_medica', 'root', '');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_GET['eliminar'])) {
    $stmt = $conexion->prepare("DELETE FROM citas_medicas WHERE id = :id");
    $stmt->execute([':id' => $_GET['eliminar']]);
    header("Location: citas.php"); // Recarga la página limpia
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? '';
    $nombre = htmlspecialchars($_POST['paciente']); 
    $problema = htmlspecialchars($_POST['problema']);
    $horaElegida = $_POST['hora'];

    if (!empty($id)) {
        // Si hay un ID, significa que estamos ACTUALIZANDO
        $sql = "UPDATE citas_medicas SET paciente = :paciente, hora = :hora, problema = :problema WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':paciente' => $nombre, ':hora' => $horaElegida, ':problema' => $problema, ':id' => $id]);
        header("Location: citas.php");
        exit;
    } else {
        // Si no hay ID, es un registro NUEVO
        $stmt = $conexion->prepare("SELECT * FROM citas_medicas WHERE hora = :hora");
        $stmt->execute([':hora' => $horaElegida]);
        
        if ($stmt->fetch()) {
            $mensaje = "Error: El horario de las $horaElegida ya se encuentra ocupado.";
            $tipo_mensaje = "error";
        } else {
            // Hora libre, procedemos a INSERTAR
            $sqlInsert = "INSERT INTO citas_medicas (paciente, hora, problema) VALUES (:paciente, :hora, :problema)";
            $stmtInsert = $conexion->prepare($sqlInsert);
            $stmtInsert->execute([':paciente' => $nombre, ':hora' => $horaElegida, ':problema' => $problema]);
            $mensaje = "¡Cita guardada con éxito!";
            $tipo_mensaje = "exito";
        }
    }
}

$cita_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM citas_medicas WHERE id = :id");
    $stmt->execute([':id' => $_GET['editar']]);
    $cita_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$citas = $conexion->query("SELECT * FROM citas_medicas ORDER BY hora ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita Médica</title> 
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f0f4f8; 
            display: flex; 
            justify-content: center; 
            padding: 40px; 
        }
        .contenedor {
            background-color: #ffffff; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1); 
            width: 100%; 
            max-width: 800px; /* Lo hice más ancho para que quepa bien la tabla */
        }
        h1, h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
        label { font-weight: bold; color: #34495e; display: block; margin-bottom: 5px; }
        input[type="text"], select, textarea {
            width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc;
            border-radius: 5px; box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #3498db; color: white; border: none; padding: 12px; width: 100%;
            font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s;
        }
        input[type="submit"]:hover { background-color: #2980b9; }
        
        /* Alertas */
        .mensaje { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .mensaje.exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        /* Estilos de la tabla (CRUD) */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .btn-editar { background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 14px; margin-right: 5px;}
        .btn-eliminar { background-color: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 14px;}
        .cancelar { display: block; text-align: center; margin-top: 10px; color: #7f8c8d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="contenedor">
        
        <!-- Mostrar mensajes de éxito o error -->
        <?php if($mensaje != ""): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <h1><?php echo $cita_editar ? 'Editar Cita' : 'Agendar Cita Médica'; ?></h1>
        
        <!-- FORMULARIO Crea y Actualiza -->
        <form action="" method="POST">
            <!-- ID oculto: si tiene valor, PHP sabra que debe actualizar y si está vacio, creara uno nuevo -->
            <input type="hidden" name="id" value="<?php echo $cita_editar['id'] ?? ''; ?>">

            <label>Nombre del paciente:</label>
            <input type="text" name="paciente" placeholder="Ej. Uriel" value="<?php echo $cita_editar['paciente'] ?? ''; ?>" required>
            
            <label>Horario de cita:</label> 
            <select name="hora">
                <?php 
                $horas = ['10:00', '11:00', '12:00', '13:00', '14:00'];
                foreach($horas as $h) {
                    // Si estamos editando, marcamos como 'selected' la hora que ya tenia el paciente
                    $seleccionado = (isset($cita_editar['hora']) && $cita_editar['hora'] == $h) ? 'selected' : '';
                    echo "<option value='$h' $seleccionado>$h</option>";
                }
                ?>
            </select>
            
            <label>Breve descripción del problema:</label>
            <textarea name="problema" rows="4" placeholder="Describa sus síntomas..."><?php echo $cita_editar['problema'] ?? ''; ?></textarea>
            
            <input type="submit" value="<?php echo $cita_editar ? 'Actualizar Cita' : 'Guardar y Comprobar'; ?>">
            
            <?php if($cita_editar): ?>
                <a href="citas.php" class="cancelar">Cancelar edición</a>
            <?php endif; ?>
        </form>

        <hr style="margin: 30px 0; border: 1px solid #ecf0f1;">

        <!-- TABLA Lee y Elimina -->
        <h2>Citas Registradas</h2>
        <table>
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Hora</th>
                    <th>Síntomas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($citas as $cita): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cita['paciente']); ?></td>
                    <td><?php echo htmlspecialchars($cita['hora']); ?></td>
                    <td><?php echo htmlspecialchars($cita['problema']); ?></td>
                    <td>
                        <!-- Botones que mandan variables por la URL (GET) -->
                        <a href="?editar=<?php echo $cita['id']; ?>" class="btn-editar">Editar</a>
                        <a href="?eliminar=<?php echo $cita['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas borrar esta cita?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($citas) == 0): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No hay citas registradas aún.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
    </div> 
</body>
</html>