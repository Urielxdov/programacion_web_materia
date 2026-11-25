import { X } from 'lucide-react';
import { useEffect, useId, useRef } from 'react';
import type { ReactNode } from 'react';
import { Boton } from './Boton';

interface ModalProps {
  abierto: boolean;
  alCerrar: () => void;
  titulo: string;
  descripcion?: ReactNode;
  children: ReactNode;
  ancho?: 'normal' | 'amplio';
}

/**
 * Ventana modal accesible basada en el elemento nativo <dialog>.
 * Se abre con dialog.showModal() (manipulación directa del DOM): el navegador
 * atrapa el foco dentro, bloquea el fondo y cierra con Escape. Al cerrar,
 * el foco regresa al botón que la abrió.
 */
export function Modal({ abierto, alCerrar, titulo, descripcion, children, ancho = 'normal' }: ModalProps) {
  const ref = useRef<HTMLDialogElement>(null);
  const idTitulo = useId();
  const focoPrevio = useRef<HTMLElement | null>(null);

  useEffect(() => {
    const dialogo = ref.current;
    if (!dialogo) return;
    if (abierto && !dialogo.open) {
      focoPrevio.current = document.activeElement as HTMLElement;
      dialogo.showModal();
      document.body.style.overflow = 'hidden';
      // Enfoca el primer campo del formulario, si existe.
      const primero = dialogo.querySelector<HTMLElement>('input, select, textarea');
      primero?.focus();
    } else if (!abierto && dialogo.open) {
      dialogo.close();
    }
  }, [abierto]);

  useEffect(() => {
    const dialogo = ref.current;
    if (!dialogo) return;
    const alCerrarNativo = () => {
      document.body.style.overflow = '';
      focoPrevio.current?.focus();
      alCerrar();
    };
    dialogo.addEventListener('close', alCerrarNativo);
    return () => dialogo.removeEventListener('close', alCerrarNativo);
  }, [alCerrar]);

  return (
    <dialog
      ref={ref}
      aria-labelledby={idTitulo}
      className={`m-auto w-[calc(100%-2rem)] rounded-2xl bg-white p-0 text-tinta shadow-2xl backdrop:bg-tinta/60 ${
        ancho === 'amplio' ? 'max-w-3xl' : 'max-w-xl'
      }`}
      onClick={(e) => {
        // Clic en el fondo (fuera del contenido) cierra la ventana.
        if (e.target === ref.current) ref.current?.close();
      }}
    >
      {abierto && (
        <div className="flex max-h-[90vh] flex-col">
          <header className="flex items-start justify-between gap-4 border-b border-tierra-200 px-6 py-5">
            <div>
              <h2 id={idTitulo} className="text-2xl">
                {titulo}
              </h2>
              {descripcion && <div className="mt-1 text-tinta-suave">{descripcion}</div>}
            </div>
            <button
              type="button"
              onClick={() => ref.current?.close()}
              className="flex min-h-12 items-center gap-1 rounded-xl px-3 font-bold text-tinta-suave hover:bg-tierra-100"
            >
              <X className="size-6" aria-hidden />
              <span>Cerrar</span>
            </button>
          </header>
          <div className="overflow-y-auto px-6 py-5">{children}</div>
        </div>
      )}
    </dialog>
  );
}

/** Confirmación antes de una acción importante o irreversible. */
export function Confirmar({
  abierto,
  alCerrar,
  alConfirmar,
  titulo,
  mensaje,
  textoConfirmar,
  peligro,
  cargando,
}: {
  abierto: boolean;
  alCerrar: () => void;
  alConfirmar: () => void;
  titulo: string;
  mensaje: ReactNode;
  textoConfirmar: string;
  peligro?: boolean;
  cargando?: boolean;
}) {
  return (
    <Modal abierto={abierto} alCerrar={alCerrar} titulo={titulo}>
      <div className="space-y-6">
        <div className="text-lg">{mensaje}</div>
        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <Boton variante="secundario" onClick={alCerrar}>
            Cancelar
          </Boton>
          <Boton variante={peligro ? 'peligro' : 'primario'} onClick={alConfirmar} cargando={cargando}>
            {textoConfirmar}
          </Boton>
        </div>
      </div>
    </Modal>
  );
}
