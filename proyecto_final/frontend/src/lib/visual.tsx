import { Battery, Cable, Laptop, Monitor, Package, Smartphone } from 'lucide-react';
import type { ReactNode } from 'react';
import type { Condicion, TipoCategoria } from '../api/tipos';

/** Ícono representativo de cada tipo de equipo. */
export const ICONO_TIPO: Record<TipoCategoria, ReactNode> = {
  COMPUTO: <Laptop className="size-5" aria-hidden />,
  PERIFERICO: <Monitor className="size-5" aria-hidden />,
  MOVIL: <Smartphone className="size-5" aria-hidden />,
  ENERGIA: <Battery className="size-5" aria-hidden />,
  CABLEADO: <Cable className="size-5" aria-hidden />,
  OTRO: <Package className="size-5" aria-hidden />,
};

/** Colores de las gráficas por condición (coinciden con las insignias). */
export const COLORES_CONDICION: Record<Condicion, string> = {
  PENDIENTE: '#8a8f87',
  FUNCIONAL: '#1d7d45',
  REPARABLE: '#d97706',
  REUTILIZABLE: '#0284c7',
  GESTION_ESPECIALIZADA: '#b91c1c',
};
