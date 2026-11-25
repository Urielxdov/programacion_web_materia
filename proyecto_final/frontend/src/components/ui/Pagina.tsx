import clsx from 'clsx';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { useContadorAnimado, useTituloPagina } from '../../hooks/dom';

export interface Miga {
  etiqueta: string;
  a?: string;
}

/** Encabezado de página: migas de pan, título, descripción y acciones. */
export function EncabezadoPagina({
  titulo,
  descripcion,
  migas,
  acciones,
}: {
  titulo: string;
  descripcion?: ReactNode;
  migas?: Miga[];
  acciones?: ReactNode;
}) {
  useTituloPagina(titulo);
  return (
    <header className="mb-8 space-y-3">
      {migas && migas.length > 0 && (
        <nav aria-label="Ruta de navegación" className="no-imprimir">
          <ol className="flex flex-wrap items-center gap-1 text-base text-tinta-suave">
            {migas.map((m, i) => (
              <li key={m.etiqueta} className="flex items-center gap-1">
                {i > 0 && <ChevronRight className="size-4" aria-hidden />}
                {m.a ? (
                  <Link to={m.a} className="underline underline-offset-4 hover:text-marca-800">
                    {m.etiqueta}
                  </Link>
                ) : (
                  <span aria-current="page" className="font-bold text-tinta">
                    {m.etiqueta}
                  </span>
                )}
              </li>
            ))}
          </ol>
        </nav>
      )}
      <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div className="space-y-2">
          <h1 className="text-3xl sm:text-4xl">{titulo}</h1>
          {descripcion && <p className="max-w-3xl text-lg text-tinta-suave">{descripcion}</p>}
        </div>
        {acciones && <div className="no-imprimir flex flex-wrap gap-3">{acciones}</div>}
      </div>
    </header>
  );
}

/** Tarjeta de indicador con número grande y animado. */
export function Indicador({
  titulo,
  valor,
  formatear = (n) => Math.round(n).toLocaleString('es-MX'),
  detalle,
  icono,
  tono = 'marca',
}: {
  titulo: string;
  valor: number;
  formatear?: (n: number) => string;
  detalle?: ReactNode;
  icono?: ReactNode;
  tono?: 'marca' | 'ambar' | 'cielo' | 'violeta';
}) {
  const ref = useContadorAnimado<HTMLParagraphElement>(valor, formatear);
  const fondos = {
    marca: 'bg-marca-100 text-marca-800',
    ambar: 'bg-amber-100 text-amber-800',
    cielo: 'bg-sky-100 text-sky-800',
    violeta: 'bg-violet-100 text-violet-800',
  };
  return (
    <article className="tarjeta flex flex-col gap-3 p-5">
      <div className="flex items-start justify-between gap-3">
        <h3 className="text-base font-bold text-tinta-suave">{titulo}</h3>
        {icono && <span className={clsx('rounded-xl p-2.5', fondos[tono])}>{icono}</span>}
      </div>
      {/* El texto lo escribe useContadorAnimado directamente en el nodo. */}
      <p ref={ref} className="min-h-10 text-4xl font-bold tracking-tight text-tinta" aria-label={`${titulo}: ${formatear(valor)}`} />

      {detalle && <p className="text-sm text-tinta-suave">{detalle}</p>}
    </article>
  );
}

export function Paginacion({
  pagina,
  totalPaginas,
  total,
  alCambiar,
}: {
  pagina: number;
  totalPaginas: number;
  total: number;
  alCambiar: (p: number) => void;
}) {
  if (totalPaginas <= 1) return <p className="text-tinta-suave">{total} resultado(s)</p>;
  return (
    <nav aria-label="Paginación" className="flex flex-col items-center justify-between gap-3 sm:flex-row">
      <p className="text-tinta-suave">
        Página <strong className="text-tinta">{pagina}</strong> de {totalPaginas} · {total} resultados
      </p>
      <div className="flex gap-2">
        <button
          type="button"
          disabled={pagina <= 1}
          onClick={() => alCambiar(pagina - 1)}
          className="flex min-h-12 items-center gap-1 rounded-xl border-2 border-tierra-300 bg-white px-4 font-bold hover:bg-tierra-100 disabled:opacity-40"
        >
          <ChevronLeft className="size-5" aria-hidden /> Anterior
        </button>
        <button
          type="button"
          disabled={pagina >= totalPaginas}
          onClick={() => alCambiar(pagina + 1)}
          className="flex min-h-12 items-center gap-1 rounded-xl border-2 border-tierra-300 bg-white px-4 font-bold hover:bg-tierra-100 disabled:opacity-40"
        >
          Siguiente <ChevronRight className="size-5" aria-hidden />
        </button>
      </div>
    </nav>
  );
}

/** Par etiqueta/valor para fichas de detalle. */
export function Dato({ etiqueta, children }: { etiqueta: string; children: ReactNode }) {
  return (
    <div>
      <dt className="text-sm font-bold text-tinta-suave">{etiqueta}</dt>
      <dd className="text-lg">{children ?? '—'}</dd>
    </div>
  );
}
