/**
 * Cliente HTTP de la aplicación.
 * - Envía siempre la cookie de sesión (credentials: 'include').
 * - Adjunta el token CSRF en las peticiones que modifican datos y lo renueva
 *   automáticamente si expiró (un reintento).
 * - Convierte las respuestas de error en ApiError con un mensaje legible.
 */

export class ApiError extends Error {
  readonly status: number;
  readonly mensajes: string[];
  readonly codigo?: string;

  constructor(status: number, mensajes: string[], codigo?: string) {
    super(mensajes[0] ?? 'Ocurrió un error inesperado.');
    this.status = status;
    this.mensajes = mensajes;
    this.codigo = codigo;
  }
}

let tokenCsrf: string | null = null;

export function establecerTokenCsrf(token: string | null) {
  tokenCsrf = token;
}

async function obtenerTokenCsrf(forzar = false): Promise<string> {
  if (tokenCsrf && !forzar) return tokenCsrf;
  const res = await fetch('/api/auth/csrf', { credentials: 'include' });
  const datos = (await res.json()) as { csrfToken: string };
  tokenCsrf = datos.csrfToken;
  return tokenCsrf;
}

type Metodo = 'GET' | 'POST' | 'PATCH' | 'DELETE';

async function solicitar<T>(metodo: Metodo, ruta: string, cuerpo?: unknown, reintento = false): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (cuerpo !== undefined) headers['Content-Type'] = 'application/json';
  if (metodo !== 'GET') headers['X-CSRF-Token'] = await obtenerTokenCsrf();

  let res: Response;
  try {
    res = await fetch(`/api${ruta}`, {
      method: metodo,
      headers,
      credentials: 'include',
      body: cuerpo !== undefined ? JSON.stringify(cuerpo) : undefined,
    });
  } catch {
    throw new ApiError(0, ['No hay conexión con el servidor. Revisa tu internet e intenta de nuevo.']);
  }

  if (res.ok) {
    if (res.status === 204) return undefined as T;
    return (await res.json()) as T;
  }

  let datos: { message?: string | string[]; code?: string } = {};
  try {
    datos = await res.json();
  } catch {
    /* respuesta sin cuerpo JSON */
  }

  // Token CSRF vencido (p. ej. tras reiniciar sesión): se pide uno nuevo y se reintenta una vez.
  if (res.status === 403 && datos.code === 'CSRF_INVALIDO' && !reintento) {
    await obtenerTokenCsrf(true);
    return solicitar<T>(metodo, ruta, cuerpo, true);
  }

  const mensajes = Array.isArray(datos.message)
    ? datos.message
    : [datos.message ?? mensajePorEstado(res.status)];
  throw new ApiError(res.status, mensajes, datos.code);
}

function mensajePorEstado(status: number): string {
  if (status === 401) return 'Tu sesión terminó. Inicia sesión de nuevo.';
  if (status === 403) return 'No tienes permiso para realizar esta acción.';
  if (status === 404) return 'No encontramos lo que buscas.';
  if (status === 429) return 'Hiciste demasiados intentos. Espera un minuto e intenta de nuevo.';
  return 'Ocurrió un error inesperado. Intenta de nuevo.';
}

export const api = {
  get: <T>(ruta: string) => solicitar<T>('GET', ruta),
  post: <T>(ruta: string, cuerpo?: unknown) => solicitar<T>('POST', ruta, cuerpo ?? {}),
  patch: <T>(ruta: string, cuerpo: unknown) => solicitar<T>('PATCH', ruta, cuerpo),
};

/** Construye un query string ignorando valores vacíos. */
export function query(params: Record<string, string | number | undefined | null>): string {
  const sp = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') sp.set(k, String(v));
  });
  const s = sp.toString();
  return s ? `?${s}` : '';
}
