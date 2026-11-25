import { useEffect, useRef, useState } from 'react';

/** Cambia el título de la pestaña del navegador (document.title) en cada página. */
export function useTituloPagina(titulo: string) {
  useEffect(() => {
    const anterior = document.title;
    document.title = `${titulo} · E-Waste 360°`;
    return () => {
      document.title = anterior;
    };
  }, [titulo]);
}

/** Retrasa un valor (p. ej. la búsqueda mientras se escribe) para no saturar al servidor. */
export function useRetraso<T>(valor: T, ms = 350): T {
  const [retrasado, setRetrasado] = useState(valor);
  useEffect(() => {
    const t = window.setTimeout(() => setRetrasado(valor), ms);
    return () => window.clearTimeout(t);
  }, [valor, ms]);
  return retrasado;
}

/**
 * Anima un número de 0 a su valor cuando el elemento entra en pantalla.
 * Usa IntersectionObserver y escribe directamente en el nodo (textContent)
 * para no volver a renderizar el componente en cada cuadro.
 */
export function useContadorAnimado<T extends HTMLElement>(valor: number, formatear: (n: number) => string) {
  const ref = useRef<T>(null);
  const fmt = useRef(formatear);
  fmt.current = formatear;
  useEffect(() => {
    const nodo = ref.current;
    if (!nodo) return;
    const formatear = fmt.current;
    const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducido) {
      nodo.textContent = formatear(valor);
      return;
    }
    nodo.textContent = formatear(0);
    let cuadro = 0;
    const observador = new IntersectionObserver(
      ([entrada]) => {
        if (!entrada.isIntersecting) return;
        observador.disconnect();
        const inicio = performance.now();
        const duracion = 1200;
        const paso = (ahora: number) => {
          const t = Math.min(1, (ahora - inicio) / duracion);
          const suavizado = 1 - Math.pow(1 - t, 3);
          nodo.textContent = formatear(valor * suavizado);
          if (t < 1) cuadro = requestAnimationFrame(paso);
        };
        cuadro = requestAnimationFrame(paso);
      },
      { threshold: 0.4 },
    );
    observador.observe(nodo);
    return () => {
      observador.disconnect();
      cancelAnimationFrame(cuadro);
    };
  }, [valor]);
  return ref;
}

/** Ejecuta una función al presionar Escape (cerrar modales y menús). */
export function useTeclaEscape(activo: boolean, alPresionar: () => void) {
  useEffect(() => {
    if (!activo) return;
    const manejar = (e: KeyboardEvent) => {
      if (e.key === 'Escape') alPresionar();
    };
    document.addEventListener('keydown', manejar);
    return () => document.removeEventListener('keydown', manejar);
  }, [activo, alPresionar]);
}
