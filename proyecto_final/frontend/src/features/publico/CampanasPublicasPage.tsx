import clsx from 'clsx';
import { CalendarDays, Clock, MapPin } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useCampanas } from '../../api/hooks';
import type { CampanaResumen, EstadoCampana } from '../../api/tipos';
import { Cargando, ErrorCarga, Vacio } from '../../components/ui/Estados';
import { InsigniaCampana } from '../../components/ui/Insignia';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { formato } from '../../lib/formato';

export function TarjetaCampana({ campana: c }: { campana: CampanaResumen }) {
  return (
    <article className="tarjeta flex flex-col gap-4 p-6">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <InsigniaCampana estado={c.estado} />
        <span className="text-sm text-tinta-suave">{c.totalParticipaciones} participaciones</span>
      </div>
      <h3 className="text-2xl">
        <Link to={`/campanas/${c.id}`} className="hover:underline">
          {c.nombre}
        </Link>
      </h3>
      <p className="line-clamp-3 text-tinta-suave">{c.descripcion}</p>
      <ul className="space-y-1.5">
        <li className="flex items-start gap-2">
          <CalendarDays className="mt-1 size-5 shrink-0 text-marca-700" aria-hidden />
          {formato.fecha(c.fechaInicio)} al {formato.fecha(c.fechaFin)}
        </li>
        {c.horario && (
          <li className="flex items-start gap-2">
            <Clock className="mt-1 size-5 shrink-0 text-marca-700" aria-hidden />
            {c.horario}
          </li>
        )}
        <li className="flex items-start gap-2">
          <MapPin className="mt-1 size-5 shrink-0 text-marca-700" aria-hidden />
          {c.ubicacion}
        </li>
      </ul>
      <Link
        to={`/campanas/${c.id}`}
        className="mt-auto inline-flex min-h-12 items-center justify-center rounded-xl bg-marca-700 px-5 font-bold text-white hover:bg-marca-800"
      >
        {c.estado === 'CERRADA' ? 'Ver resultados' : 'Ver detalles y participar'}
      </Link>
    </article>
  );
}

const filtros: { valor?: EstadoCampana; etiqueta: string }[] = [
  { etiqueta: 'Todas' },
  { valor: 'ACTIVA', etiqueta: 'Activas' },
  { valor: 'PROGRAMADA', etiqueta: 'Próximas' },
  { valor: 'CERRADA', etiqueta: 'Concluidas' },
];

export function CampanasPublicasPage() {
  const [estado, setEstado] = useState<EstadoCampana | undefined>();
  const { data, isLoading, error, refetch } = useCampanas(estado);

  return (
    <div className="contenedor py-10">
      <EncabezadoPagina
        titulo="Campañas de recolección"
        descripcion="Lleva tus aparatos en desuso a una de nuestras jornadas. Revisamos cada equipo para repararlo o reutilizarlo antes de considerarlo residuo."
        migas={[{ etiqueta: 'Inicio', a: '/' }, { etiqueta: 'Campañas' }]}
      />

      <div role="group" aria-label="Filtrar campañas" className="mb-8 flex flex-wrap gap-2">
        {filtros.map((f) => (
          <button
            key={f.etiqueta}
            type="button"
            aria-pressed={estado === f.valor}
            onClick={() => setEstado(f.valor)}
            className={clsx(
              'min-h-12 rounded-full border-2 px-5 font-bold transition-colors',
              estado === f.valor ? 'border-marca-700 bg-marca-700 text-white' : 'border-tierra-300 bg-white hover:bg-tierra-100',
            )}
          >
            {f.etiqueta}
          </button>
        ))}
      </div>

      {isLoading && <Cargando />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      {data && data.length === 0 && (
        <Vacio titulo="No hay campañas en esta categoría" descripcion="Prueba con otro filtro o vuelve pronto." />
      )}
      {data && data.length > 0 && (
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {data.map((c) => (
            <TarjetaCampana key={c.id} campana={c} />
          ))}
        </div>
      )}
    </div>
  );
}
