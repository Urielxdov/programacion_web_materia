import clsx from 'clsx';
import { CheckCircle2, Info, X, XCircle } from 'lucide-react';
import { useAvisos } from '../../stores/avisos.store';

const estilos = {
  exito: { clase: 'border-marca-600 bg-marca-50', icono: <CheckCircle2 className="size-7 text-marca-700" aria-hidden /> },
  error: { clase: 'border-red-600 bg-red-50', icono: <XCircle className="size-7 text-red-700" aria-hidden /> },
  info: { clase: 'border-sky-600 bg-sky-50', icono: <Info className="size-7 text-sky-700" aria-hidden /> },
};

/** Región de avisos: los lectores de pantalla anuncian cada mensaje nuevo. */
export function Avisos() {
  const { avisos, cerrar } = useAvisos();
  return (
    <div
      aria-live="polite"
      aria-relevant="additions"
      className="no-imprimir pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-center gap-3 sm:inset-x-auto sm:right-6 sm:items-end"
    >
      {avisos.map((a) => (
        <div
          key={a.id}
          role={a.tipo === 'error' ? 'alert' : 'status'}
          className={clsx(
            'animar-aparecer pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-2xl border-l-8 p-4 shadow-xl',
            estilos[a.tipo].clase,
          )}
        >
          {estilos[a.tipo].icono}
          <div className="flex-1">
            <p className="text-lg font-bold">{a.titulo}</p>
            {a.detalle && <p className="text-tinta-suave">{a.detalle}</p>}
          </div>
          <button
            type="button"
            onClick={() => cerrar(a.id)}
            className="rounded-lg p-2 hover:bg-black/5"
            aria-label="Cerrar aviso"
          >
            <X className="size-5" aria-hidden />
          </button>
        </div>
      ))}
    </div>
  );
}
