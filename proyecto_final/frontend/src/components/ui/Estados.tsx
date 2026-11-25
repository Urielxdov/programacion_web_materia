import { AlertOctagon, Inbox, Loader2, RotateCw } from 'lucide-react';
import type { ReactNode } from 'react';
import { Boton } from './Boton';

export function Cargando({ texto = 'Cargando información…' }: { texto?: string }) {
  return (
    <div role="status" className="flex flex-col items-center justify-center gap-3 py-16 text-tinta-suave">
      <Loader2 className="size-10 animate-spin text-marca-600" aria-hidden />
      <p className="text-lg">{texto}</p>
    </div>
  );
}

export function ErrorCarga({ error, reintentar }: { error: unknown; reintentar?: () => void }) {
  const mensaje = error instanceof Error ? error.message : 'No se pudo cargar la información.';
  return (
    <div role="alert" className="tarjeta flex flex-col items-center gap-4 p-8 text-center">
      <AlertOctagon className="size-12 text-red-700" aria-hidden />
      <div>
        <p className="text-xl font-bold">No pudimos cargar esta información</p>
        <p className="mt-1 text-tinta-suave">{mensaje}</p>
      </div>
      {reintentar && (
        <Boton variante="secundario" onClick={reintentar} icono={<RotateCw className="size-5" aria-hidden />}>
          Intentar de nuevo
        </Boton>
      )}
    </div>
  );
}

export function Vacio({ titulo, descripcion, accion }: { titulo: string; descripcion?: string; accion?: ReactNode }) {
  return (
    <div className="flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed border-tierra-300 bg-white/60 px-6 py-12 text-center">
      <Inbox className="size-12 text-tierra-300" aria-hidden />
      <p className="text-xl font-bold">{titulo}</p>
      {descripcion && <p className="max-w-md text-tinta-suave">{descripcion}</p>}
      {accion}
    </div>
  );
}
