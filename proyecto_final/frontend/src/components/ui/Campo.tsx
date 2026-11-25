import clsx from 'clsx';
import { AlertCircle } from 'lucide-react';
import { useId } from 'react';
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';

const claseControl = (error?: string) =>
  clsx(
    'block w-full rounded-xl border-2 bg-white px-4 py-3 text-base text-tinta shadow-inner',
    'placeholder:text-tinta-suave/60 transition-colors',
    'focus:border-marca-600 focus:outline-none focus:ring-4 focus:ring-marca-200',
    error ? 'border-red-600 bg-red-50' : 'border-tierra-300',
  );

interface EnvolturaProps {
  id: string;
  etiqueta: string;
  ayuda?: ReactNode;
  error?: string;
  obligatorio?: boolean;
  children: ReactNode;
}

/** Etiqueta siempre visible, texto de ayuda y mensaje de error ligados al campo (accesible). */
function Envoltura({ id, etiqueta, ayuda, error, obligatorio, children }: EnvolturaProps) {
  return (
    <div className="space-y-1.5">
      <label htmlFor={id} className="block font-bold text-tinta">
        {etiqueta}
        {obligatorio ? (
          <span className="text-red-700" aria-hidden>
            {' '}
            *
          </span>
        ) : (
          <span className="font-normal text-tinta-suave"> (opcional)</span>
        )}
      </label>
      {ayuda && (
        <p id={`${id}-ayuda`} className="text-sm text-tinta-suave">
          {ayuda}
        </p>
      )}
      {children}
      {/* aria-live para que el lector de pantalla anuncie el error al aparecer */}
      <p id={`${id}-error`} aria-live="polite" className="min-h-0 text-sm font-bold text-red-700">
        {error && (
          <span className="flex items-start gap-1.5">
            <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
            {error}
          </span>
        )}
      </p>
    </div>
  );
}

const describir = (id: string, ayuda?: ReactNode, error?: string) =>
  [ayuda ? `${id}-ayuda` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;

type Base = { etiqueta: string; ayuda?: ReactNode; error?: string; obligatorio?: boolean; ref?: (el: HTMLElement | null) => void };

export function CampoTexto({ etiqueta, ayuda, error, obligatorio, ref, className, ...resto }: Base & Omit<InputHTMLAttributes<HTMLInputElement>, 'ref'>) {
  const id = useId();
  return (
    <Envoltura id={id} etiqueta={etiqueta} ayuda={ayuda} error={error} obligatorio={obligatorio}>
      <input
        id={id}
        ref={ref}
        className={clsx(claseControl(error), className)}
        aria-invalid={!!error}
        aria-required={obligatorio}
        aria-describedby={describir(id, ayuda, error)}
        {...resto}
      />
    </Envoltura>
  );
}

export function CampoArea({ etiqueta, ayuda, error, obligatorio, ref, ...resto }: Base & Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'ref'>) {
  const id = useId();
  return (
    <Envoltura id={id} etiqueta={etiqueta} ayuda={ayuda} error={error} obligatorio={obligatorio}>
      <textarea
        id={id}
        ref={ref}
        rows={4}
        className={claseControl(error)}
        aria-invalid={!!error}
        aria-required={obligatorio}
        aria-describedby={describir(id, ayuda, error)}
        {...resto}
      />
    </Envoltura>
  );
}

export function CampoSelect({
  etiqueta,
  ayuda,
  error,
  obligatorio,
  ref,
  children,
  ...resto
}: Base & Omit<SelectHTMLAttributes<HTMLSelectElement>, 'ref'>) {
  const id = useId();
  return (
    <Envoltura id={id} etiqueta={etiqueta} ayuda={ayuda} error={error} obligatorio={obligatorio}>
      <select
        id={id}
        ref={ref}
        className={clsx(claseControl(error), 'appearance-auto pr-10')}
        aria-invalid={!!error}
        aria-required={obligatorio}
        aria-describedby={describir(id, ayuda, error)}
        {...resto}
      >
        {children}
      </select>
    </Envoltura>
  );
}

/** Casilla grande y fácil de presionar, con descripción opcional. */
export function Casilla({
  etiqueta,
  descripcion,
  checked,
  onChange,
  ref,
}: {
  etiqueta: string;
  descripcion?: string;
  checked: boolean;
  onChange: (v: boolean) => void;
  ref?: (el: HTMLElement | null) => void;
}) {
  const id = useId();
  return (
    <label
      htmlFor={id}
      className={clsx(
        'flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition-colors',
        checked ? 'border-marca-600 bg-marca-50' : 'border-tierra-200 bg-white hover:border-tierra-300',
      )}
    >
      <input
        id={id}
        ref={ref}
        type="checkbox"
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
        className="mt-1 size-6 shrink-0 accent-marca-700"
      />
      <span>
        <span className="block font-bold">{etiqueta}</span>
        {descripcion && <span className="block text-sm text-tinta-suave">{descripcion}</span>}
      </span>
    </label>
  );
}

/** Opciones tipo tarjeta (radio) — más fáciles de leer y tocar que una lista desplegable. */
export function OpcionesTarjeta<T extends string>({
  leyenda,
  opciones,
  valor,
  onChange,
  error,
  columnas = 2,
}: {
  leyenda: string;
  opciones: { valor: T; etiqueta: string; descripcion?: string; icono?: ReactNode }[];
  valor: T | '';
  onChange: (v: T) => void;
  error?: string;
  columnas?: 2 | 3;
}) {
  const nombre = useId();
  return (
    <fieldset className="space-y-2">
      <legend className="mb-2 font-bold">{leyenda}</legend>
      <div className={clsx('grid gap-3', columnas === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2')}>
        {opciones.map((o) => (
          <label
            key={o.valor}
            className={clsx(
              'flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition-colors has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-marca-200',
              valor === o.valor ? 'border-marca-600 bg-marca-50' : 'border-tierra-200 bg-white hover:border-tierra-300',
            )}
          >
            <input
              type="radio"
              name={nombre}
              value={o.valor}
              checked={valor === o.valor}
              onChange={() => onChange(o.valor)}
              className="mt-1 size-5 shrink-0 accent-marca-700"
            />
            <span className="flex-1">
              <span className="flex items-center gap-2 font-bold">
                {o.icono}
                {o.etiqueta}
              </span>
              {o.descripcion && <span className="block text-sm text-tinta-suave">{o.descripcion}</span>}
            </span>
          </label>
        ))}
      </div>
      {error && (
        <p className="flex items-center gap-1.5 text-sm font-bold text-red-700" role="alert">
          <AlertCircle className="size-4" aria-hidden /> {error}
        </p>
      )}
    </fieldset>
  );
}
