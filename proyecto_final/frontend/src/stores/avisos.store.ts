import { create } from 'zustand';

export type TipoAviso = 'exito' | 'error' | 'info';

export interface Aviso {
  id: number;
  tipo: TipoAviso;
  titulo: string;
  detalle?: string;
}

interface AvisosState {
  avisos: Aviso[];
  mostrar: (aviso: Omit<Aviso, 'id'>) => void;
  cerrar: (id: number) => void;
}

let siguienteId = 1;

/** Mensajes emergentes de éxito o error (se cierran solos después de unos segundos). */
export const useAvisos = create<AvisosState>((set, get) => ({
  avisos: [],
  mostrar: (aviso) => {
    const id = siguienteId++;
    set((s) => ({ avisos: [...s.avisos, { ...aviso, id }] }));
    // Los errores permanecen más tiempo para que dé tiempo de leerlos.
    window.setTimeout(() => get().cerrar(id), aviso.tipo === 'error' ? 9000 : 6000);
  },
  cerrar: (id) => set((s) => ({ avisos: s.avisos.filter((a) => a.id !== id) })),
}));

export const avisar = {
  exito: (titulo: string, detalle?: string) => useAvisos.getState().mostrar({ tipo: 'exito', titulo, detalle }),
  error: (titulo: string, detalle?: string) => useAvisos.getState().mostrar({ tipo: 'error', titulo, detalle }),
  info: (titulo: string, detalle?: string) => useAvisos.getState().mostrar({ tipo: 'info', titulo, detalle }),
};
