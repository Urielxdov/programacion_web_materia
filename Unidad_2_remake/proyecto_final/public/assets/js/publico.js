/* E-Waste 360° — interacción del sitio público. */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    // ---- Resalta la sección actual en el menú
    const ruta = location.pathname.replace(EW.base, '') || '/';
    document.querySelectorAll('.navbar-publica [data-ruta]').forEach((enlace) => {
        const destino = enlace.dataset.ruta;
        const activo = destino === '/' ? ruta === '/' : ruta.startsWith(destino);
        enlace.classList.toggle('active', activo);
        if (activo) enlace.setAttribute('aria-current', 'page');
    });

    // ---- Lista de verificación antes de entregar un equipo (se recuerda en este navegador)
    const lista = document.getElementById('listaEntrega');
    if (lista) {
        const casillas = [...lista.querySelectorAll('input[type="checkbox"]')];
        const barra = lista.querySelector('.progress-bar');
        const progreso = lista.querySelector('.progress');
        const completa = lista.querySelector('[data-lista-completa]');
        let guardado = [];
        try { guardado = JSON.parse(localStorage.getItem('ew-lista-entrega') || '[]'); } catch { /* sin almacenamiento */ }
        casillas.forEach((c, i) => { c.checked = Boolean(guardado[i]); });

        const actualizar = () => {
            const marcadas = casillas.filter((c) => c.checked).length;
            const porcentaje = Math.round((marcadas / casillas.length) * 100);
            barra.style.width = `${porcentaje}%`;
            progreso.setAttribute('aria-valuenow', String(porcentaje));
            completa.classList.toggle('d-none', marcadas !== casillas.length);
            try { localStorage.setItem('ew-lista-entrega', JSON.stringify(casillas.map((c) => c.checked))); } catch { /* sin almacenamiento */ }
        };
        casillas.forEach((c) => c.addEventListener('change', actualizar));
        actualizar();
    }

    // ---- Filtro y búsqueda de campañas sin recargar la página
    const filtros = document.getElementById('filtrosCampanas');
    if (filtros) {
        const tarjetas = [...document.querySelectorAll('[data-tarjeta-campana]')];
        const buscador = filtros.querySelector('[data-buscar-campanas]');
        const vacio = document.querySelector('[data-sin-resultados]');
        let estado = 'todas';

        const aplicar = () => {
            const texto = buscador.value.trim().toLowerCase();
            let visibles = 0;
            tarjetas.forEach((col) => {
                const art = col.querySelector('article');
                const coincide = (estado === 'todas' || art.dataset.estado === estado) && art.dataset.texto.includes(texto);
                col.classList.toggle('d-none', !coincide);
                if (coincide) visibles++;
            });
            vacio?.classList.toggle('d-none', visibles > 0);
        };

        filtros.querySelectorAll('[data-filtro]').forEach((boton) => {
            boton.addEventListener('click', () => {
                filtros.querySelectorAll('[data-filtro]').forEach((b) => b.classList.toggle('active', b === boton));
                estado = boton.dataset.filtro;
                aplicar();
            });
        });
        buscador.addEventListener('input', aplicar);
    }
});
