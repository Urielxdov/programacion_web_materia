/*
 * E-Waste 360° — utilidades comunes del cliente.
 * Todo el contenido dinámico se inserta con textContent/createElement (nunca innerHTML con datos
 * del servidor) para evitar XSS.
 */
'use strict';

const EW = (() => {
    const meta = (nombre) => document.querySelector(`meta[name="${nombre}"]`)?.content ?? '';

    /** fetch con sesión, token CSRF y manejo uniforme de errores JSON. */
    async function api(url, opciones = {}) {
        const cabeceras = { Accept: 'application/json', ...(opciones.headers || {}) };
        if (opciones.body && !(opciones.body instanceof FormData)) {
            cabeceras['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(opciones.body);
        }
        if (opciones.method && opciones.method !== 'GET') {
            cabeceras['X-CSRF-Token'] = meta('csrf-token');
        }
        const respuesta = await fetch(url, { credentials: 'same-origin', ...opciones, headers: cabeceras });
        const datos = await respuesta.json().catch(() => ({}));
        if (!respuesta.ok) {
            const error = new Error(datos.error?.mensaje || `Error ${respuesta.status}`);
            error.estado = respuesta.status;
            error.detalles = datos.error?.detalles || {};
            throw error;
        }
        return datos;
    }

    /** Crea un elemento con atributos e hijos (texto o nodos). */
    function el(etiqueta, atributos = {}, ...hijos) {
        const nodo = document.createElement(etiqueta);
        for (const [clave, valor] of Object.entries(atributos)) {
            if (valor === null || valor === undefined || valor === false) continue;
            if (clave === 'class') nodo.className = valor;
            else if (clave === 'dataset') Object.assign(nodo.dataset, valor);
            else nodo.setAttribute(clave, valor === true ? '' : valor);
        }
        for (const hijo of hijos.flat()) {
            if (hijo === null || hijo === undefined || hijo === false) continue;
            nodo.append(hijo instanceof Node ? hijo : document.createTextNode(String(hijo)));
        }
        return nodo;
    }

    const debounce = (fn, ms = 300) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    const dinero = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(n) || 0);
    const numero = (n, d = 0) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: d, maximumFractionDigits: d }).format(Number(n) || 0);
    const fecha = (iso) => {
        if (!iso) return '—';
        const [a, m, d] = String(iso).slice(0, 10).split('-');
        return `${d}/${m}/${a}`;
    };

    const COLORES = {
        funcional: '#16a34a', validado: '#16a34a', reutilizable: '#22c55e', reasignado: '#22c55e',
        reparable: '#f59e0b', pendiente: '#facc15', gestion_especializada: '#dc2626', entregado_gestor: '#dc2626',
        conservado: '#64748b', en_inventario: '#0f766e',
    };
    const colorBadge = (valor) => ({
        funcional: 'success', validado: 'success', activa: 'success', reutilizable: 'success', reasignado: 'success',
        reparable: 'warning', pendiente: 'warning', borrador: 'warning',
        gestion_especializada: 'danger', rechazado: 'danger', entregado_gestor: 'danger',
        conservado: 'secondary', cerrada: 'secondary',
    }[valor] || 'info');

    function insignia(valor, texto) {
        const color = colorBadge(valor);
        const oscuro = color === 'warning' || color === 'info' ? ' text-dark' : '';
        return el('span', { class: `badge rounded-pill text-bg-${color}${oscuro}` }, texto);
    }

    /** Anima un número desde 0 (respeta prefers-reduced-motion). */
    function contar(nodo, destino, formato = (v) => numero(v)) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            nodo.textContent = formato(destino);
            return;
        }
        const inicio = performance.now();
        const duracion = 900;
        const paso = (ahora) => {
            const avance = Math.min(1, (ahora - inicio) / duracion);
            nodo.textContent = formato(destino * (1 - Math.pow(1 - avance, 3)));
            if (avance < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
    }

    return { api, el, debounce, dinero, numero, fecha, insignia, contar, COLORES, base: meta('base-url') };
})();

document.addEventListener('DOMContentLoaded', () => {
    // ---- Validación de formularios en el cliente (el servidor vuelve a validar todo)
    document.querySelectorAll('form[data-validar]').forEach((form) => {
        const inicio = form.querySelector('[data-fecha-inicio]');
        const fin = form.querySelector('[data-fecha-fin]');
        const revisarFechas = () => {
            if (!inicio || !fin) return;
            fin.setCustomValidity(inicio.value && fin.value && fin.value < inicio.value ? 'La fecha de fin debe ser posterior al inicio.' : '');
        };
        inicio?.addEventListener('change', revisarFechas);
        fin?.addEventListener('change', revisarFechas);

        form.addEventListener('submit', (evento) => {
            revisarFechas();
            if (!form.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
                form.querySelector(':invalid')?.focus();
            } else {
                // Evita doble envío
                form.querySelectorAll('button[type="submit"]').forEach((b) => {
                    b.disabled = true;
                    b.prepend(EW.el('span', { class: 'spinner-border spinner-border-sm me-1', 'aria-hidden': 'true' }));
                });
            }
            form.classList.add('was-validated');
        });
    });

    // ---- Confirmación antes de acciones importantes
    document.querySelectorAll('form[data-confirmar]').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            if (!window.confirm(form.dataset.confirmar)) evento.preventDefault();
        });
    });

    // ---- Mostrar/ocultar contraseña
    document.querySelectorAll('[data-mostrar-password]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const campo = document.querySelector(boton.dataset.mostrarPassword);
            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            boton.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
            boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    });

    // ---- Indicador de fuerza de contraseña
    document.querySelectorAll('[data-fuerza-password]').forEach((campo) => {
        const barra = campo.closest('.col-md-6')?.querySelector('[data-barra-fuerza]');
        campo.addEventListener('input', () => {
            const v = campo.value;
            const puntos = [v.length >= 8, /[a-z]/.test(v) && /[A-Z]/.test(v), /\d/.test(v), /[^A-Za-z0-9]/.test(v), v.length >= 12]
                .filter(Boolean).length;
            if (!barra) return;
            barra.style.width = `${puntos * 20}%`;
            barra.className = `progress-bar ${puntos <= 2 ? 'bg-danger' : puntos === 3 ? 'bg-warning' : 'bg-success'}`;
        });
    });

    // ---- Contador de caracteres
    document.querySelectorAll('[data-contador-caracteres]').forEach((campo) => {
        const salida = document.querySelector(`[data-contador-para="${campo.id}"]`);
        const actualizar = () => { if (salida) salida.textContent = `${campo.value.length} / ${campo.maxLength}`; };
        campo.addEventListener('input', actualizar);
        actualizar();
    });

    // ---- Vista previa de etiquetas (equipos aceptados en campañas)
    document.querySelectorAll('[data-vista-chips]').forEach((contenedor) => {
        const campo = contenedor.parentElement.querySelector('input');
        const pintar = () => {
            contenedor.replaceChildren(...campo.value.split(',').map((t) => t.trim()).filter(Boolean).map((t) => EW.el('span', { class: 'chip' }, t)));
        };
        campo.addEventListener('input', pintar);
        pintar();
    });

    // ---- Cierre automático de alertas de éxito
    document.querySelectorAll('[data-autocerrar]').forEach((alerta) => {
        const ms = Number(alerta.dataset.autocerrar);
        if (ms > 0) setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerta).close(), ms);
    });

    document.querySelectorAll('[data-imprimir]').forEach((b) => b.addEventListener('click', () => window.print()));
    document.querySelectorAll('[data-regresar]').forEach((b) => b.addEventListener('click', () => history.back()));

    // ---- Números animados
    document.querySelectorAll('[data-contar]').forEach((nodo) => {
        const decimales = Number(nodo.dataset.decimales || 0);
        EW.contar(nodo, Number(nodo.dataset.contar), (v) => EW.numero(v, decimales));
    });
});
