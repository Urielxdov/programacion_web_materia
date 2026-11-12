/* E-Waste 360° — interacción del panel privado (consume la API REST con fetch). */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    inventarioEnVivo();
    formularioEquipo();
    formularioMovimiento();
    formularioDiagnostico();
    tablero();
    reportes();
    contadorPendientes();
});

/* ------------------------------------------------------------------ Inventario: búsqueda sin recargar */
function inventarioEnVivo() {
    const form = document.getElementById('filtrosEquipos');
    if (!form) return;
    const cuerpo = document.getElementById('cuerpoEquipos');
    const contenedor = document.getElementById('contenedorTabla');
    const paginacion = document.getElementById('paginacionEquipos');
    const total = document.querySelector('[data-total-equipos]');
    let pagina = 1;
    let solicitud = 0;

    const fila = (e) => EW.el('tr', {},
        EW.el('td', { 'data-etiqueta': 'Código' }, EW.el('a', { class: 'codigo', href: e.url }, e.codigo)),
        EW.el('td', { 'data-etiqueta': 'Equipo' }, `${e.marca} ${e.modelo}`),
        EW.el('td', { 'data-etiqueta': 'Categoría', class: 'small' }, e.categoria.nombre),
        EW.el('td', { 'data-etiqueta': 'Condición', class: 'small' }, e.condicion.replace('_', ' ')),
        EW.el('td', { 'data-etiqueta': 'Clasificación' }, EW.insignia(e.clasificacion, e.clasificacion_texto)),
        EW.el('td', { 'data-etiqueta': 'Estado' }, EW.insignia(e.estado, e.estado_texto)),
        EW.el('td', { 'data-etiqueta': 'Ingreso', class: 'small' }, EW.fecha(e.fecha_ingreso)),
    );

    const pintarPaginacion = (meta) => {
        if (meta.paginas <= 1) { paginacion.replaceChildren(); return; }
        const item = (texto, p, deshabilitado, activo) => EW.el('li', { class: `page-item${deshabilitado ? ' disabled' : ''}${activo ? ' active' : ''}` },
            EW.el('a', { class: 'page-link', href: '#', 'data-pagina': p }, texto));
        const items = [item('Anterior', meta.pagina - 1, meta.pagina <= 1)];
        for (let p = Math.max(1, meta.pagina - 2); p <= Math.min(meta.paginas, meta.pagina + 2); p++) items.push(item(p, p, false, p === meta.pagina));
        items.push(item('Siguiente', meta.pagina + 1, meta.pagina >= meta.paginas));
        paginacion.replaceChildren(EW.el('nav', { class: 'd-flex justify-content-center mt-3', 'aria-label': 'Paginación' },
            EW.el('ul', { class: 'pagination pagination-sm mb-0' }, items)));
    };

    const buscar = async () => {
        const parametros = new URLSearchParams(new FormData(form));
        [...parametros.keys()].forEach((k) => { if (!parametros.get(k)) parametros.delete(k); });
        if (pagina > 1) parametros.set('pagina', pagina);
        const id = ++solicitud;
        contenedor.classList.add('cargando');
        try {
            const respuesta = await EW.api(`${form.dataset.api}?${parametros}`);
            if (id !== solicitud) return; // llegó una respuesta más nueva
            cuerpo.replaceChildren(...(respuesta.data.length
                ? respuesta.data.map(fila)
                : [EW.el('tr', {}, EW.el('td', { colspan: 7, class: 'text-center text-secondary py-4' }, 'No hay equipos que coincidan con los filtros.'))]));
            total.textContent = EW.numero(respuesta.meta.total);
            pintarPaginacion(respuesta.meta);
            history.replaceState(null, '', `${location.pathname}${parametros.toString() ? '?' + parametros : ''}`);
        } catch (error) {
            cuerpo.replaceChildren(EW.el('tr', {}, EW.el('td', { colspan: 7, class: 'text-danger py-3' }, error.message)));
        } finally {
            contenedor.classList.remove('cargando');
        }
    };

    const reiniciarYBuscar = () => { pagina = 1; buscar(); };
    form.querySelector('#q').addEventListener('input', EW.debounce(reiniciarYBuscar, 350));
    form.querySelectorAll('select').forEach((s) => s.addEventListener('change', reiniciarYBuscar));
    form.addEventListener('submit', (e) => { e.preventDefault(); reiniciarYBuscar(); });
    paginacion.addEventListener('click', (e) => {
        const enlace = e.target.closest('[data-pagina]');
        if (!enlace || enlace.parentElement.classList.contains('disabled')) return;
        e.preventDefault();
        pagina = Number(enlace.dataset.pagina);
        buscar();
    });
}

/* ------------------------------------------------------------------ Registro de equipo: avisos por tipo */
function formularioEquipo() {
    const form = document.querySelector('form[data-tipos-categoria]');
    if (!form) return;
    const tipos = JSON.parse(form.dataset.tiposCategoria || '{}');
    const aviso = form.querySelector('[data-aviso-tipo]');
    const mensajes = {
        computadora: 'Guarda datos: antes de reasignarla o entregarla se pedirá confirmar el borrado seguro.',
        laptop: 'Guarda datos y contiene batería de litio: requiere borrado seguro y manejo especial.',
        celular: 'Guarda datos personales y contiene batería: requiere borrado seguro y manejo especial.',
        bateria: 'Residuo peligroso: almacénala con los polos cubiertos y lejos del calor.',
        monitor: 'Transpórtalo con la pantalla protegida; un panel roto requiere manejo especial.',
        impresora: 'Retira cartuchos y tóner; se gestionan por separado.',
    };
    const select = form.querySelector('#categoria_id');
    const actualizar = () => {
        const texto = mensajes[tipos[select.value]];
        aviso.textContent = texto ? `ⓘ ${texto}` : '';
        aviso.classList.toggle('d-none', !texto);
    };
    select.addEventListener('change', actualizar);
    actualizar();
}

/* ------------------------------------------------------------------ Movimientos: campos según el tipo */
function formularioMovimiento() {
    const form = document.getElementById('formMovimiento');
    if (!form) return;
    const verificados = JSON.parse(form.dataset.gestoresVerificados || '{}');
    const requiereBorrado = form.dataset.requiereBorrado === '1';
    const tipo = form.querySelector('#tipo');
    const gestor = form.querySelector('#gestor_id');
    const aviso = form.querySelector('[data-aviso-gestor]');
    const campos = (nombre) => form.querySelector(`[data-campo-mov="${nombre}"]`);

    const visibles = {
        reparacion: ['costo'],
        reasignacion: ['destino', 'borrado'],
        conservacion: ['destino'],
        entrega_gestor: ['gestor', 'evidencia', 'borrado'],
    };
    const obligatorios = { reparacion: ['costo'], reasignacion: ['destino'], entrega_gestor: ['gestor_id', 'evidencia'] };

    const actualizar = () => {
        const actuales = visibles[tipo.value] || ['destino'];
        ['destino', 'costo', 'gestor', 'evidencia', 'borrado'].forEach((n) => {
            const mostrar = actuales.includes(n) && (n !== 'borrado' || requiereBorrado);
            campos(n).classList.toggle('d-none', !mostrar);
        });
        ['destino', 'costo', 'gestor_id', 'evidencia'].forEach((id) => { form.querySelector(`#${id}`).required = false; });
        (obligatorios[tipo.value] || []).forEach((id) => { form.querySelector(`#${id}`).required = true; });
        form.querySelector('#datos_borrados').required = requiereBorrado && ['reasignacion', 'entrega_gestor'].includes(tipo.value);
        aviso.classList.toggle('d-none', !(tipo.value === 'entrega_gestor' && gestor.value && !verificados[gestor.value]));
    };
    tipo.addEventListener('change', actualizar);
    gestor.addEventListener('change', actualizar);
    actualizar();
}

/* ------------------------------------------------------------------ Diagnóstico: recomendación en tiempo real (Strategy vía API) */
function formularioDiagnostico() {
    const form = document.getElementById('formDiagnostico');
    if (!form) return;
    const caja = document.getElementById('recomendacion');
    const decision = form.querySelector('#decision');
    const justificacion = form.querySelector('#justificacion');
    const estado = form.querySelector('#estado_funcional');
    let recomendada = null;
    let decisionTocada = decision.value !== '';

    const revisarJustificacion = () => {
        justificacion.required = Boolean(recomendada && decision.value && decision.value !== recomendada);
    };

    const consultar = EW.debounce(async () => {
        if (!estado.value) return;
        const parametros = new URLSearchParams({
            estado_funcional: estado.value,
            costo_reparacion: form.querySelector('#costo_reparacion').value || '0',
            valor_reemplazo: form.querySelector('#valor_reemplazo').value || '0',
            riesgo_seguridad: form.querySelector('#riesgo_seguridad').checked ? '1' : '0',
        });
        caja.classList.add('cargando');
        try {
            const { data } = await EW.api(`${form.dataset.api}?${parametros}`);
            recomendada = data.decision;
            caja.className = `recomendacion ${data.decision}`;
            caja.replaceChildren(
                EW.el('div', { class: 'fw-semibold mb-1' }, EW.el('i', { class: 'bi bi-lightbulb' }), ' ', data.decision_texto),
                EW.el('p', { class: 'small mb-1' }, data.justificacion),
                EW.el('div', { class: 'small text-secondary' }, `Clasificación resultante: ${data.clasificacion.replace('_', ' ')} · ${data.estrategia}`),
            );
            if (!decisionTocada) decision.value = data.decision;
        } catch (error) {
            caja.className = 'recomendacion';
            const mensaje = Object.values(error.detalles)[0] || error.message;
            caja.replaceChildren(EW.el('p', { class: 'small text-danger mb-0' }, mensaje));
        } finally {
            caja.classList.remove('cargando');
            revisarJustificacion();
        }
    }, 350);

    form.querySelectorAll('[data-evaluacion]').forEach((campo) => {
        campo.addEventListener('input', consultar);
        campo.addEventListener('change', consultar);
    });
    decision.addEventListener('change', () => { decisionTocada = true; revisarJustificacion(); });
    consultar();
}

/* ------------------------------------------------------------------ Gráficas (Chart.js) */
const PALETA = ['#0f766e', '#16a34a', '#f59e0b', '#dc2626', '#64748b', '#0ea5e9', '#a855f7', '#84cc16', '#f97316'];

function grafica(id, configuracion) {
    const lienzo = document.getElementById(id);
    if (!lienzo || typeof Chart === 'undefined') return;
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#5b6b61';
    new Chart(lienzo, { ...configuracion, options: { responsive: true, maintainAspectRatio: false, ...configuracion.options } });
}

const nombreMes = (periodo) => {
    const [a, m] = periodo.split('-');
    return new Date(Number(a), Number(m) - 1, 1).toLocaleDateString('es-MX', { month: 'short', year: '2-digit' });
};

function graficaDona(id, mapa) {
    const claves = Object.keys(mapa);
    grafica(id, {
        type: 'doughnut',
        data: {
            labels: claves.map((k) => mapa[k].etiqueta),
            datasets: [{ data: claves.map((k) => mapa[k].total), backgroundColor: claves.map((k, i) => EW.COLORES[k] || PALETA[i]), borderWidth: 2 }],
        },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '62%' },
    });
}

async function tablero() {
    const kpis = document.getElementById('kpis');
    if (!kpis) return;
    try {
        const { data } = await EW.api(kpis.dataset.api);
        const poner = (clave, valor, formato) => {
            const nodo = kpis.querySelector(`[data-kpi="${clave}"]`);
            if (nodo) EW.contar(nodo, valor, formato);
        };
        poner('total', data.equipos.total);
        poner('aprovechados', data.reparados + data.reutilizados);
        poner('pendientes_diagnostico', data.pendientes.diagnostico);
        poner('ahorro', data.costos.ahorro_estimado, EW.dinero);

        graficaDona('graficaClasificacion', data.equipos.por_clasificacion);
        grafica('graficaIngresos', {
            type: 'bar',
            data: {
                labels: data.ingresos_por_mes.map((m) => nombreMes(m.periodo)),
                datasets: [{ label: 'Equipos ingresados', data: data.ingresos_por_mes.map((m) => m.total), backgroundColor: '#0f766e', borderRadius: 6 }],
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    } catch (error) {
        kpis.prepend(EW.el('div', { class: 'col-12' }, EW.el('div', { class: 'alert alert-warning small mb-0' }, `No se pudieron cargar los indicadores: ${error.message}`)));
    }
}

async function reportes() {
    const raiz = document.getElementById('reportes');
    if (!raiz) return;
    const poner = (clave, texto) => { const n = raiz.querySelector(`[data-indicador="${clave}"]`); if (n) n.textContent = texto; };
    try {
        const [generales, reutilizacion, campanas] = await Promise.all([
            EW.api(raiz.dataset.apiGenerales),
            EW.api(raiz.dataset.apiReutilizacion),
            EW.api(raiz.dataset.apiCampanas),
        ]);
        const g = generales.data;
        const r = reutilizacion.data;
        document.querySelector('[data-fecha-reporte]').textContent = new Date(generales.generado_en).toLocaleString('es-MX');
        poner('total', EW.numero(g.equipos.total));
        poner('tasa', `${EW.numero(r.tasa_aprovechamiento, 1)}%`);
        poner('ahorro', EW.dinero(r.ahorro_estimado_total));
        poner('costo', EW.dinero(r.costo_reparacion_total));
        poner('kg_verificados', `${EW.numero(g.entregas_gestor.kg_verificados, 1)} kg`);
        poner('no_verificadas', EW.numero(g.entregas_gestor.no_verificadas));
        poner('participaciones', EW.numero(g.campanas.participaciones));
        poner('pendientes', EW.numero(g.pendientes.movimientos));

        graficaDona('graficaEstado', g.equipos.por_estado);
        grafica('graficaCategoria', {
            type: 'bar',
            data: {
                labels: g.equipos.por_categoria.map((c) => c.categoria),
                datasets: [{ label: 'Equipos', data: g.equipos.por_categoria.map((c) => c.total), backgroundColor: PALETA, borderRadius: 6 }],
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
        grafica('graficaAprovechamiento', {
            type: 'bar',
            data: {
                labels: r.por_mes.map((m) => nombreMes(m.periodo)),
                datasets: [
                    { label: 'Reparaciones', data: r.por_mes.map((m) => m.reparaciones), backgroundColor: '#f59e0b', borderRadius: 6 },
                    { label: 'Reasignaciones', data: r.por_mes.map((m) => m.reasignaciones), backgroundColor: '#16a34a', borderRadius: 6 },
                ],
            },
            options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        const tablaA = document.getElementById('tablaAprovechamiento');
        tablaA.replaceChildren(...(r.por_categoria.length ? r.por_categoria.map((c) => EW.el('tr', {},
            EW.el('td', {}, c.categoria), EW.el('td', { class: 'text-end' }, c.reparados), EW.el('td', { class: 'text-end' }, c.reutilizados)))
            : [EW.el('tr', {}, EW.el('td', { colspan: 3, class: 'text-secondary small' }, 'Sin reparaciones ni reasignaciones validadas.'))]));

        const tablaC = document.getElementById('tablaCampanas');
        tablaC.replaceChildren(...(campanas.data.campanas.length ? campanas.data.campanas.map((c) => {
            const avance = c.meta_equipos ? Math.min(100, Math.round((c.recibidos / c.meta_equipos) * 100)) : null;
            return EW.el('tr', {},
                EW.el('td', {}, EW.el('a', { href: `${EW.base}/panel/campanas/${c.id}` }, c.nombre)),
                EW.el('td', {}, EW.insignia(c.estado, c.estado === 'activa' ? 'Activa' : 'Cerrada')),
                EW.el('td', { class: 'text-end' }, c.participantes),
                EW.el('td', { class: 'text-end' }, c.asistentes),
                EW.el('td', { class: 'text-end' }, c.comprometidos),
                EW.el('td', { class: 'text-end' }, c.recibidos),
                EW.el('td', { class: 'text-end' }, EW.numero(c.kg_recibidos, 1)),
                EW.el('td', { style: 'min-width:120px' }, avance === null ? '—' : EW.el('div', { class: 'progress', style: 'height:8px', title: `${avance}%` },
                    EW.el('div', { class: 'progress-bar bg-success', style: `width:${avance}%` }))),
            );
        }) : [EW.el('tr', {}, EW.el('td', { colspan: 8, class: 'text-secondary small' }, 'No hay campañas publicadas.'))]));
    } catch (error) {
        raiz.prepend(EW.el('div', { class: 'alert alert-danger' }, `No se pudieron cargar los indicadores: ${error.message}`));
    }
}

/* ------------------------------------------------------------------ Contador de pendientes en el menú */
async function contadorPendientes() {
    const insignia = document.querySelector('[data-contador="movimientos"]');
    if (!insignia || document.getElementById('kpis') || document.getElementById('reportes')) return;
    try {
        const { data } = await EW.api(`${EW.base}/api/indicadores`);
        if (data.pendientes.movimientos > 0) {
            insignia.textContent = data.pendientes.movimientos;
            insignia.classList.remove('d-none');
        }
    } catch { /* el contador es opcional */ }
}
