import { useRef, useState } from 'react';
import type { FormEvent } from 'react';

export type Validador<V> = (valor: V[keyof V], todos: V) => string | null;
export type Reglas<V> = Partial<Record<keyof V, Validador<V>[]>>;
type Errores<V> = Partial<Record<keyof V, string>>;

/**
 * Manejo de formularios con validación en el cliente.
 * - Valida cada campo al salir de él (blur) y todos al enviar.
 * - Mientras se corrige un campo con error, se revalida en vivo.
 * - Al enviar con errores, mueve el foco al primer campo inválido y lo
 *   desplaza a la vista (manipulación directa del DOM con refs).
 * - El servidor vuelve a validar todo: esto solo mejora la experiencia.
 */
export function useFormulario<V extends Record<string, unknown>>(inicial: V, reglas: Reglas<V>) {
  const [valores, setValoresEstado] = useState<V>(inicial);
  const [errores, setErrores] = useState<Errores<V>>({});
  const valoresRef = useRef<V>(inicial);
  const tocados = useRef<Partial<Record<keyof V, boolean>>>({});
  const nodos = useRef<Partial<Record<keyof V, HTMLElement | null>>>({});

  const validarCampo = (campo: keyof V, todos: V): string | undefined => {
    for (const regla of reglas[campo] ?? []) {
      const error = regla(todos[campo], todos);
      if (error) return error;
    }
    return undefined;
  };

  const setValores = (nuevos: V) => {
    valoresRef.current = nuevos;
    setValoresEstado(nuevos);
  };

  const cambiar = <K extends keyof V>(campo: K, valor: V[K]) => {
    const nuevos = { ...valoresRef.current, [campo]: valor };
    setValores(nuevos);
    if (tocados.current[campo]) {
      setErrores((e) => ({ ...e, [campo]: validarCampo(campo, nuevos) }));
    }
  };

  const salir = (campo: keyof V) => {
    tocados.current[campo] = true;
    setErrores((e) => ({ ...e, [campo]: validarCampo(campo, valoresRef.current) }));
  };

  /** Valida un subconjunto de campos (útil en formularios por pasos). */
  const validar = (campos: (keyof V)[] = Object.keys(reglas) as (keyof V)[]): boolean => {
    const nuevos: Errores<V> = {};
    for (const c of campos) {
      tocados.current[c] = true;
      nuevos[c] = validarCampo(c, valoresRef.current);
    }
    setErrores((e) => ({ ...e, ...nuevos }));

    const primero = campos.find((c) => nuevos[c]);
    if (primero) {
      const nodo = nodos.current[primero];
      nodo?.focus();
      nodo?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }
    return true;
  };

  const alEnviar = (enviar: (v: V) => void) => (e: FormEvent) => {
    e.preventDefault();
    if (validar()) enviar(valoresRef.current);
  };

  /** Propiedades para conectar un campo: nombre, ref, error y evento de salida. */
  const campo = <K extends keyof V>(nombre: K) => ({
    name: String(nombre),
    ref: (el: HTMLElement | null) => {
      nodos.current[nombre] = el;
    },
    error: errores[nombre],
    onBlur: () => salir(nombre),
  });

  /** Muestra errores que devolvió el servidor junto al campo correspondiente. */
  const marcarErrores = (e: Errores<V>) => setErrores((prev) => ({ ...prev, ...e }));

  return { valores, setValores, errores, cambiar, validar, alEnviar, campo, marcarErrores };
}

const hoyLocal = () => {
  const hoy = new Date();
  return new Date(hoy.getTime() - hoy.getTimezoneOffset() * 60_000).toISOString().slice(0, 10);
};

/** Validadores reutilizables con mensajes claros. */
export const v = {
  requerido:
    (mensaje = 'Este campo es obligatorio.') =>
    (valor: unknown) =>
      valor === undefined || valor === null || (typeof valor === 'string' && valor.trim() === '') ? mensaje : null,
  minimo: (n: number, mensaje?: string) => (valor: unknown) =>
    typeof valor === 'string' && valor.trim().length > 0 && valor.trim().length < n
      ? (mensaje ?? `Escribe al menos ${n} caracteres.`)
      : null,
  maximo: (n: number) => (valor: unknown) =>
    typeof valor === 'string' && valor.length > n ? `Máximo ${n} caracteres.` : null,
  email: () => (valor: unknown) =>
    typeof valor === 'string' && valor && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor.trim())
      ? 'Escribe un correo válido, por ejemplo: nombre@correo.com'
      : null,
  numeroMin: (min: number, mensaje: string) => (valor: unknown) =>
    valor === '' || valor === undefined || Number.isNaN(Number(valor)) || Number(valor) < min ? mensaje : null,
  fechaNoFutura: () => (valor: unknown) =>
    typeof valor === 'string' && valor && valor > hoyLocal() ? 'La fecha no puede ser posterior a hoy.' : null,
  password: () => (valor: unknown) =>
    typeof valor === 'string' && valor && !/^(?=.*[A-Za-z])(?=.*\d).{8,72}$/.test(valor)
      ? 'Usa al menos 8 caracteres, con letras y números.'
      : null,
};
