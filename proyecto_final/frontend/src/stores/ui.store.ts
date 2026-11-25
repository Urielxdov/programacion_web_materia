import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

/**
 * Estado del frontend que NO viene del servidor: preferencias de lectura
 * (tamaño de letra y contraste) y el menú móvil. Las preferencias se guardan
 * en localStorage para que se mantengan al volver a la página.
 */
export type EscalaTexto = 'normal' | 'grande' | 'muy-grande';

export const ESCALAS: Record<EscalaTexto, { porcentaje: string; etiqueta: string; muestra: string }> = {
  normal: { porcentaje: '112.5%', etiqueta: 'Tamaño de letra normal', muestra: 'A' },
  grande: { porcentaje: '125%', etiqueta: 'Letra grande', muestra: 'A+' },
  'muy-grande': { porcentaje: '137.5%', etiqueta: 'Letra muy grande', muestra: 'A++' },
};

interface UiState {
  escala: EscalaTexto;
  altoContraste: boolean;
  menuAbierto: boolean;
  setEscala: (e: EscalaTexto) => void;
  alternarContraste: () => void;
  setMenuAbierto: (abierto: boolean) => void;
}

export const useUiStore = create<UiState>()(
  persist(
    (set) => ({
      escala: 'normal',
      altoContraste: false,
      menuAbierto: false,
      setEscala: (escala) => set({ escala }),
      alternarContraste: () => set((s) => ({ altoContraste: !s.altoContraste })),
      setMenuAbierto: (menuAbierto) => set({ menuAbierto }),
    }),
    {
      name: 'ewaste360-preferencias',
      storage: createJSONStorage(() => localStorage),
      partialize: (s) => ({ escala: s.escala, altoContraste: s.altoContraste }),
    },
  ),
);

/**
 * Aplica las preferencias directamente al DOM: cambia el tamaño de fuente
 * raíz (todo el diseño usa rem, así que escala completo) y el atributo de contraste.
 */
function aplicarPreferencias(estado: Pick<UiState, 'escala' | 'altoContraste'>) {
  const raiz = document.documentElement;
  raiz.style.fontSize = ESCALAS[estado.escala].porcentaje;
  if (estado.altoContraste) raiz.setAttribute('data-contraste', 'alto');
  else raiz.removeAttribute('data-contraste');
}

aplicarPreferencias(useUiStore.getState());
useUiStore.subscribe(aplicarPreferencias);
