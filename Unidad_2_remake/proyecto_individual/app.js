document.addEventListener('DOMContentLoaded', function () {
    // ---- Alta de invitados via fetch (AJAX) ----
    const formAltas = document.getElementById('formAltas');
    if (formAltas) {
        formAltas.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validarFechas(this)) return;
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

    // ---- Edicion de invitados via fetch ----
    const formEditar = document.getElementById('formEditar');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validarFechas(this)) return;
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

// La fecha de salida no puede ser anterior a la de llegada
function validarFechas(form) {
    const llegada = form.fecha_llegada.value;
    const salida = form.fecha_salida.value;
    if (llegada && salida && salida < llegada) {
        alert('La fecha de salida no puede ser anterior a la de llegada.');
        return false;
    }
    return true;
}

// ---- Eliminacion con confirmacion ----
function eliminarInvitado(id) {
    if (!confirm('¿Seguro que deseas eliminar este invitado?')) {
        return;
    }
    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('id_invitado', id);

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

// ---- Ir al formulario de edicion ----
function editarInvitado(id) {
    window.location.href = 'editar.php?id=' + id;
}
