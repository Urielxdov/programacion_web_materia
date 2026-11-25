import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

export type BorradorEquipo = {
  categoriaId: string;
  marca: string;
  modelo: string;
  numeroSerie: string;
  fechaIngreso: string;
  ubicacion: string;
  observaciones: string;
  campanaId: string;
};

interface BorradorState {
  paso: number;
  datos: BorradorEquipo | null;
  guardar: (paso: number, datos: BorradorEquipo) => void;
  limpiar: () => void;
}

/**
 * Borrador del registro de equipo por pasos. Se guarda en sessionStorage:
 * si la persona sale de la página por accidente, al volver continúa donde iba.
 * Se borra al registrar el equipo o al cerrar la pestaña.
 */
export const useBorradorEquipo = create<BorradorState>()(
  persist(
    (set) => ({
      paso: 1,
      datos: null,
      guardar: (paso, datos) => set({ paso, datos }),
      limpiar: () => set({ paso: 1, datos: null }),
    }),
    { name: 'ewaste360-borrador-equipo', storage: createJSONStorage(() => sessionStorage) },
  ),
);
