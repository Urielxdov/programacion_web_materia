import { CalendarDays, ChevronRight, MapPin, PlusCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useCampanas } from '../../api/hooks';
import { BotonEnlace } from '../../components/ui/Boton';
import { Cargando, ErrorCarga, Vacio } from '../../components/ui/Estados';
import { InsigniaCampana } from '../../components/ui/Insignia';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { formato } from '../../lib/formato';

export function CampanasGestionPage() {
  const { data, isLoading, error, refetch } = useCampanas();
  return (
    <>
      <EncabezadoPagina
        titulo="Campañas de recolección"
        descripcion="Publica campañas, revisa quién participó y cuántos equipos se recibieron."
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Campañas' }]}
        acciones={
          <BotonEnlace to="/panel/campanas/nueva" icono={<PlusCircle className="size-5" aria-hidden />}>
            Nueva campaña
          </BotonEnlace>
        }
      />
      {isLoading && <Cargando />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      {data?.length === 0 && <Vacio titulo="Aún no hay campañas" accion={<BotonEnlace to="/panel/campanas/nueva">Crear la primera</BotonEnlace>} />}
      <ul className="space-y-4">
        {data?.map((c) => {
          const avance = c.metaEquipos ? Math.min(100, Math.round((c.equiposRecibidosTotal / c.metaEquipos) * 100)) : null;
          return (
            <li key={c.id}>
              <Link to={`/panel/campanas/${c.id}`} className="tarjeta group grid gap-4 p-5 transition-shadow hover:shadow-md md:grid-cols-[1fr_16rem_auto] md:items-center">
                <div className="space-y-2">
                  <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-xl">{c.nombre}</h2>
                    <InsigniaCampana estado={c.estado} />
                  </div>
                  <p className="flex flex-wrap gap-x-5 gap-y-1 text-tinta-suave">
                    <span className="flex items-center gap-1.5">
                      <CalendarDays className="size-4" aria-hidden />
                      {formato.fechaCorta(c.fechaInicio)} – {formato.fechaCorta(c.fechaFin)}
                    </span>
                    <span className="flex items-center gap-1.5">
                      <MapPin className="size-4" aria-hidden />
                      {c.ubicacion}
                    </span>
                  </p>
                </div>
                <div className="space-y-1.5">
                  <p className="text-sm">
                    <strong>{c.totalParticipaciones}</strong> participaciones · <strong>{c.equiposRecibidosTotal}</strong> equipos
                    recibidos
                  </p>
                  {avance !== null && (
                    <>
                      <div
                        className="h-3 overflow-hidden rounded-full bg-tierra-200"
                        role="progressbar"
                        aria-valuenow={avance}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-label={`Avance de la meta de ${c.metaEquipos} equipos`}
                      >
                        <div className="h-full rounded-full bg-marca-600" style={{ width: `${avance}%` }} />
                      </div>
                      <p className="text-sm text-tinta-suave">
                        {avance}% de la meta ({c.metaEquipos} equipos)
                      </p>
                    </>
                  )}
                </div>
                <ChevronRight className="hidden size-7 text-marca-700 transition-transform group-hover:translate-x-1 md:block" aria-hidden />
              </Link>
            </li>
          );
        })}
      </ul>
    </>
  );
}
