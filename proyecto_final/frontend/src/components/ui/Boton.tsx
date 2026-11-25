import clsx from 'clsx';
import { Loader2 } from 'lucide-react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { Link } from 'react-router-dom';
import type { LinkProps } from 'react-router-dom';

type Variante = 'primario' | 'secundario' | 'peligro' | 'fantasma';
type Tamano = 'normal' | 'grande';

const base =
  'inline-flex items-center justify-center gap-2 rounded-xl font-bold transition-colors ' +
  'disabled:cursor-not-allowed disabled:opacity-60 select-none text-center';

const variantes: Record<Variante, string> = {
  primario: 'bg-marca-700 text-white hover:bg-marca-800 shadow-sm',
  secundario: 'border-2 border-marca-700 bg-white text-marca-800 hover:bg-marca-50',
  peligro: 'bg-red-700 text-white hover:bg-red-800 shadow-sm',
  fantasma: 'text-marca-800 hover:bg-marca-100',
};

// Áreas de toque de al menos 48 px de alto.
const tamanos: Record<Tamano, string> = {
  normal: 'min-h-12 px-5 py-2.5 text-base',
  grande: 'min-h-14 px-7 py-3 text-lg',
};

export function clasesBoton(variante: Variante = 'primario', tamano: Tamano = 'normal', extra?: string) {
  return clsx(base, variantes[variante], tamanos[tamano], extra);
}

interface BotonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variante?: Variante;
  tamano?: Tamano;
  cargando?: boolean;
  icono?: ReactNode;
}

export function Boton({ variante, tamano, cargando, icono, children, className, disabled, type = 'button', ...resto }: BotonProps) {
  return (
    <button
      type={type}
      className={clasesBoton(variante, tamano, className)}
      disabled={disabled || cargando}
      aria-busy={cargando || undefined}
      {...resto}
    >
      {cargando ? <Loader2 className="size-5 animate-spin" aria-hidden /> : icono}
      <span>{cargando ? 'Procesando…' : children}</span>
    </button>
  );
}

interface BotonEnlaceProps extends LinkProps {
  variante?: Variante;
  tamano?: Tamano;
  icono?: ReactNode;
}

export function BotonEnlace({ variante, tamano, icono, children, className, ...resto }: BotonEnlaceProps) {
  return (
    <Link className={clasesBoton(variante, tamano, className as string)} {...resto}>
      {icono}
      <span>{children as ReactNode}</span>
    </Link>
  );
}
