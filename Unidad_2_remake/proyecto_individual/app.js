document.addEventListener('DOMContentLoaded', function () {
    // La CURP siempre en mayusculas
    document.querySelectorAll('input[name="curp"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = campo.value.toUpperCase();
        });
    });

    // ---- Alta de citas via fetch (AJAX) ----
    const formAltas = document.getElementById('formAltas');
    if (formAltas) {
        formAltas.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('acciones.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.mensaje);
                        formAltas.reset();
                    } else {
                        alert('Error: ' + data.mensaje);
                    }
                })
                .catch(() => alert('Error de conexión'));
        });
    }

    // ---- Modificacion de citas via fetch ----
    const formEditar = document.getElementById('formEditar');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('acciones.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.mensaje);
                        window.location.href = 'dashboard.php';
                    } else {
                        alert('Error: ' + data.mensaje);
                    }
                })
                .catch(() => alert('Error de conexión'));
        });
    }
});

// ---- Eliminacion con confirmacion ----
function eliminarCita(id) {
    if (!confirm('¿Seguro que deseas eliminar esta cita?')) {
        return;
    }
    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('id_cita', id);

    fetch('acciones.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.mensaje);
                location.reload();
            } else {
                alert('Error: ' + data.mensaje);
            }
        })
        .catch(() => alert('Error de conexión'));
}

// ---- Ir al formulario de modificacion ----
function editarCita(id) {
    window.location.href = 'editar.php?id=' + id;
}
