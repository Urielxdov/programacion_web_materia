import clsx from 'clsx';
import {
  AlertTriangle,
  Archive,
  CalendarClock,
  CheckCircle2,
  CircleDashed,
  Clock,
  PackageCheck,
  Recycle,
  RefreshCcw,
  Truck,
  Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import type { Condicion, EstadoCampana, EstadoDestino } from '../../api/tipos';
import { CONDICION, ESTADO_CAMPANA, ESTADO_DESTINO } from '../../lib/formato';
import type { Tono } from '../../lib/formato';

const tonos: Record<Tono, string> = {
  neutro: 'bg-tierra-100 text-tinta border-tierra-300',
  exito: 'bg-marca-100 text-marca-900 border-marca-300',
  aviso: 'bg-amber-100 text-amber-900 border-amber-300',
  peligro: 'bg-red-100 text-red-900 border-red-300',
  info: 'bg-sky-100 text-sky-900 border-sky-300',
  morado: 'bg-violet-100 text-violet-900 border-violet-300',
};

/** Insignia de estado: siempre combina color, ícono y texto (no depende solo del color). */
export function Insignia({ tono, icono, children, className }: { tono: Tono; icono?: ReactNode; children: ReactNode; className?: string }) {
  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-sm font-bold',
        tonos[tono],
        className,
      )}
    >
      {icono}
      {children}
    </span>
  );
}

const iconoCondicion: Record<Condicion, ReactNode> = {
  PENDIENTE: <CircleDashed className="size-4" aria-hidden />,
  FUNCIONAL: <CheckCircle2 className="size-4" aria-hidden />,
  REPARABLE: <Wrench className="size-4" aria-hidden />,
  REUTILIZABLE: <RefreshCcw className="size-4" aria-hidden />,
  GESTION_ESPECIALIZADA: <AlertTriangle className="size-4" aria-hidden />,
};

export function InsigniaCondicion({ condicion }: { condicion: Condicion }) {
  const c = CONDICION[condicion];
  return (
    <Insignia tono={c.tono} icono={iconoCondicion[condicion]}>
      {c.etiqueta}
    </Insignia>
  );
}

const iconoDestino: Record<EstadoDestino, ReactNode> = {
  EN_INVENTARIO: <Archive className="size-4" aria-hidden />,
  EN_REPARACION: <Wrench className="size-4" aria-hidden />,
  REASIGNADO: <PackageCheck className="size-4" aria-hidden />,
  CONSERVADO: <Archive className="size-4" aria-hidden />,
  ENTREGADO_GESTOR: <Truck className="size-4" aria-hidden />,
};

export function InsigniaDestino({ estado }: { estado: EstadoDestino }) {
  const e = ESTADO_DESTINO[estado];
  return (
    <Insignia tono={e.tono} icono={iconoDestino[estado]}>
      {e.etiqueta}
    </Insignia>
  );
}

const iconoCampana: Record<EstadoCampana, ReactNode> = {
  PROGRAMADA: <CalendarClock className="size-4" aria-hidden />,
  ACTIVA: <Recycle className="size-4" aria-hidden />,
  CERRADA: <Clock className="size-4" aria-hidden />,
};

export function InsigniaCampana({ estado }: { estado: EstadoCampana }) {
  const e = ESTADO_CAMPANA[estado];
  return (
    <Insignia tono={e.tono} icono={iconoCampana[estado]}>
      {e.etiqueta}
    </Insignia>
  );
}
