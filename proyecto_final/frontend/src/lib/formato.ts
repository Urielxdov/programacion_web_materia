import type { Condicion, EstadoCampana, EstadoDestino, Rol, TipoCategoria, TipoMovimiento } from '../api/tipos';

/** Etiquetas en lenguaje sencillo para cada valor que devuelve la API. */

export const CONDICION: Record<Condicion, { etiqueta: string; descripcion: string; tono: Tono }> = {
  PENDIENTE: { etiqueta: 'Pendiente de revisar', descripcion: 'Aún no tiene diagnóstico.', tono: 'neutro' },
  FUNCIONAL: { etiqueta: 'Funcional', descripcion: 'Funciona y puede usarse.', tono: 'exito' },
  REPARABLE: { etiqueta: 'Reparable', descripcion: 'Conviene repararlo.', tono: 'aviso' },
  REUTILIZABLE: { etiqueta: 'Reutilizable', descripcion: 'Recuperado y listo para otro uso.', tono: 'info' },
  GESTION_ESPECIALIZADA: {
    etiqueta: 'Gestión especializada',
    descripcion: 'Debe entregarse a un gestor verificado.',
    tono: 'peligro',
  },
};

export const ESTADO_DESTINO: Record<EstadoDestino, { etiqueta: string; tono: Tono }> = {
  EN_INVENTARIO: { etiqueta: 'En inventario', tono: 'neutro' },
  EN_REPARACION: { etiqueta: 'En reparación', tono: 'aviso' },
  REASIGNADO: { etiqueta: 'Reasignado', tono: 'exito' },
  CONSERVADO: { etiqueta: 'Conservado', tono: 'info' },
  ENTREGADO_GESTOR: { etiqueta: 'Entregado a gestor', tono: 'morado' },
};

export const TIPO_MOVIMIENTO: Record<TipoMovimiento, string> = {
  INGRESO: 'Ingreso al inventario',
  DIAGNOSTICO: 'Diagnóstico',
  ENVIO_REPARACION: 'Enviado a reparación',
  REPARACION: 'Reparación',
  REASIGNACION: 'Reasignación',
  CONSERVACION: 'Conservación',
  ENTREGA_GESTOR: 'Entrega a gestor',
};

export const TIPO_CATEGORIA: Record<TipoCategoria, string> = {
  COMPUTO: 'Cómputo',
  PERIFERICO: 'Periférico',
  MOVIL: 'Móvil',
  ENERGIA: 'Energía (baterías)',
  CABLEADO: 'Cables y cargadores',
  OTRO: 'Otro',
};

export const ROL: Record<Rol, { etiqueta: string; descripcion: string }> = {
  CAPTURISTA: { etiqueta: 'Capturista', descripcion: 'Registra equipos y actualiza sus datos.' },
  RESPONSABLE: {
    etiqueta: 'Responsable ambiental',
    descripcion: 'Diagnostica, decide destinos, administra campañas y consulta reportes.',
  },
  ADMIN: { etiqueta: 'Administrador', descripcion: 'Gestiona usuarios, catálogos y gestores.' },
};

export const ESTADO_CAMPANA: Record<EstadoCampana, { etiqueta: string; tono: Tono }> = {
  PROGRAMADA: { etiqueta: 'Próximamente', tono: 'info' },
  ACTIVA: { etiqueta: 'Activa', tono: 'exito' },
  CERRADA: { etiqueta: 'Concluida', tono: 'neutro' },
};

export type Tono = 'neutro' | 'exito' | 'aviso' | 'peligro' | 'info' | 'morado';

const fmtFecha = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
const fmtFechaCorta = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
const fmtFechaHora = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
const fmtMoneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const fmtNumero = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });
const fmtMes = new Intl.DateTimeFormat('es-MX', { month: 'short', year: '2-digit', timeZone: 'UTC' });

/** Fechas "AAAA-MM-DD" (sin hora) se interpretan en UTC para que no se recorran un día. */
const aFecha = (valor: string) => new Date(valor.length === 10 ? `${valor}T00:00:00Z` : valor);

export const formato = {
  fecha: (v: string) => fmtFecha.format(aFecha(v)),
  fechaCorta: (v: string) => fmtFechaCorta.format(aFecha(v)),
  fechaHora: (v: string) => fmtFechaHora.format(new Date(v)),
  moneda: (n: number) => fmtMoneda.format(n),
  numero: (n: number) => fmtNumero.format(n),
  kg: (n: number) => `${fmtNumero.format(n)} kg`,
  mes: (aaaaMm: string) => fmtMes.format(new Date(`${aaaaMm}-01T00:00:00Z`)),
};

/** Fecha de hoy en formato AAAA-MM-DD según la zona horaria local. */
export function hoyISO(): string {
  const d = new Date();
  return new Date(d.getTime() - d.getTimezoneOffset() * 60_000).toISOString().slice(0, 10);
}
