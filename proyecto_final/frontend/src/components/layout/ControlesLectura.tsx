import clsx from 'clsx';
import { Contrast } from 'lucide-react';
import { ESCALAS, useUiStore } from '../../stores/ui.store';
import type { EscalaTexto } from '../../stores/ui.store';

/** Selector de tamaño de letra y alto contraste, visible en todas las páginas. */
export function ControlesLectura({ compacto = false }: { compacto?: boolean }) {
  const { escala, setEscala, altoContraste, alternarContraste } = useUiStore();
  return (
    <div className="flex flex-wrap items-center gap-2" role="group" aria-label="Opciones de lectura">
      {!compacto && <span className="text-sm font-bold text-tinta-suave">Tamaño de letra:</span>}
      <div className="flex overflow-hidden rounded-xl border-2 border-tierra-300 bg-white">
        {(Object.keys(ESCALAS) as EscalaTexto[]).map((e) => (
          <button
            key={e}
            type="button"
            onClick={() => setEscala(e)}
            aria-pressed={escala === e}
            aria-label={ESCALAS[e].etiqueta}
            title={ESCALAS[e].etiqueta}
            className={clsx(
              'min-h-11 min-w-11 px-2 font-bold transition-colors',
              escala === e ? 'bg-marca-700 text-white' : 'text-tinta hover:bg-tierra-100',
            )}
          >
            {ESCALAS[e].muestra}
          </button>
        ))}
      </div>
      <button
        type="button"
        onClick={alternarContraste}
        aria-pressed={altoContraste}
        className={clsx(
          'flex min-h-11 items-center gap-1.5 rounded-xl border-2 px-3 font-bold transition-colors',
          altoContraste ? 'border-tinta bg-tinta text-white' : 'border-tierra-300 bg-white hover:bg-tierra-100',
        )}
      >
        <Contrast className="size-5" aria-hidden />
        <span className={compacto ? 'sr-only' : ''}>Alto contraste</span>
      </button>
    </div>
  );
}
