import { ChevronRight, FilterX, PlusCircle, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useCampanas, useCategorias, useEquipos } from '../../api/hooks';
import type { Condicion, EstadoDestino } from '../../api/tipos';
import { BotonEnlace } from '../../components/ui/Boton';
import { CampoSelect } from '../../components/ui/Campo';
import { Cargando, ErrorCarga, Vacio } from '../../components/ui/Estados';
import { InsigniaCondicion, InsigniaDestino } from '../../components/ui/Insignia';
import { EncabezadoPagina, Paginacion } from '../../components/ui/Pagina';
import { useRetraso } from '../../hooks/dom';
import { CONDICION, ESTADO_DESTINO, formato } from '../../lib/formato';

/** Los filtros viven en la URL: se pueden compartir, guardar y respetan el botón "Atrás". */
export function EquiposPage() {
  const [params, setParams] = useSearchParams();
  const [texto, setTexto] = useState(params.get('q') ?? '');
  const q = useRetraso(texto);
  const { data: categorias } = useCategorias();
  const { data: campanas } = useCampanas();

  const filtros = {
    q: params.get('q') ?? undefined,
    categoriaId: params.get('categoriaId') ? Number(params.get('categoriaId')) : undefined,
    condicion: params.get('condicion') ?? undefined,
    estadoDestino: params.get('estadoDestino') ?? undefined,
    campanaId: params.get('campanaId') ? Number(params.get('campanaId')) : undefined,
    pagina: Number(params.get('pagina') ?? 1),
    porPagina: 15,
  };
  const { data, isLoading, isFetching, error, refetch } = useEquipos(filtros);

  const actualizar = (clave: string, valor: string) => {
    setParams(
      (p) => {
        const n = new URLSearchParams(p);
        if (valor) n.set(clave, valor);
        else n.delete(clave);
        if (clave !== 'pagina') n.delete('pagina');
        return n;
      },
      { replace: clave === 'q' },
    );
  };

  // La búsqueda se aplica cuando se deja de escribir (para no consultar en cada tecla).
  useEffect(() => {
    if ((params.get('q') ?? '') !== q) actualizar('q', q);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q]);

  const hayFiltros = ['q', 'categoriaId', 'condicion', 'estadoDestino', 'campanaId'].some((k) => params.get(k));

  return (
    <>
      <EncabezadoPagina
        titulo="Equipos"
        descripcion="Busca un equipo por folio, marca, modelo, número de serie o ubicación."
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Equipos' }]}
        acciones={
          <BotonEnlace to="/panel/equipos/nuevo" icono={<PlusCircle className="size-5" aria-hidden />}>
            Registrar equipo
          </BotonEnlace>
        }
      />

      <section className="tarjeta mb-6 space-y-4 p-5" aria-label="Búsqueda y filtros">
        <div className="relative">
          <label htmlFor="buscar" className="mb-1.5 block font-bold">
            Buscar
          </label>
          <Search className="pointer-events-none absolute bottom-3.5 left-4 size-6 text-tinta-suave" aria-hidden />
          <input
            id="buscar"
            type="search"
            value={texto}
            onChange={(e) => setTexto(e.target.value)}
            placeholder="Ejemplo: EW-2026-0012, Dell, laboratorio…"
            className="block w-full rounded-xl border-2 border-tierra-300 bg-white py-3 pl-13 pr-4 text-base focus:border-marca-600 focus:outline-none focus:ring-4 focus:ring-marca-200"
          />
        </div>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <CampoSelect etiqueta="Tipo de equipo" value={params.get('categoriaId') ?? ''} onChange={(e) => actualizar('categoriaId', e.target.value)}>
            <option value="">Todos</option>
            {categorias?.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nombre}
              </option>
            ))}
          </CampoSelect>
          <CampoSelect etiqueta="Condición" value={params.get('condicion') ?? ''} onChange={(e) => actualizar('condicion', e.target.value)}>
            <option value="">Todas</option>
            {(Object.keys(CONDICION) as Condicion[]).map((c) => (
              <option key={c} value={c}>
                {CONDICION[c].etiqueta}
              </option>
            ))}
          </CampoSelect>
          <CampoSelect etiqueta="Destino" value={params.get('estadoDestino') ?? ''} onChange={(e) => actualizar('estadoDestino', e.target.value)}>
            <option value="">Todos</option>
            {(Object.keys(ESTADO_DESTINO) as EstadoDestino[]).map((c) => (
              <option key={c} value={c}>
                {ESTADO_DESTINO[c].etiqueta}
              </option>
            ))}
          </CampoSelect>
          <CampoSelect etiqueta="Campaña" value={params.get('campanaId') ?? ''} onChange={(e) => actualizar('campanaId', e.target.value)}>
            <option value="">Cualquiera</option>
            {campanas?.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nombre}
              </option>
            ))}
          </CampoSelect>
        </div>
        {hayFiltros && (
          <button
            type="button"
            onClick={() => {
              setTexto('');
              setParams({});
            }}
            className="flex min-h-11 items-center gap-2 rounded-lg px-2 font-bold text-marca-800 hover:bg-marca-50"
          >
            <FilterX className="size-5" aria-hidden /> Quitar filtros
          </button>
        )}
      </section>

      {isLoading && <Cargando texto="Buscando equipos…" />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      {data && data.datos.length === 0 && (
        <Vacio
          titulo="No encontramos equipos"
          descripcion={hayFiltros ? 'Prueba con otras palabras o quita los filtros.' : 'Aún no hay equipos registrados.'}
          accion={<BotonEnlace to="/panel/equipos/nuevo">Registrar el primero</BotonEnlace>}
        />
      )}

      {data && data.datos.length > 0 && (
        <div className={isFetching ? 'opacity-60 transition-opacity' : 'transition-opacity'} aria-busy={isFetching}>
          {/* Tabla en pantallas medianas y grandes */}
          <div className="tarjeta hidden overflow-x-auto md:block">
            <table className="w-full text-left">
              <caption className="sr-only">Lista de equipos</caption>
              <thead className="border-b-2 border-tierra-200 bg-tierra-50 text-sm uppercase tracking-wide text-tinta-suave">
                <tr>
                  <th scope="col" className="px-5 py-4">Folio</th>
                  <th scope="col" className="px-5 py-4">Equipo</th>
                  <th scope="col" className="px-5 py-4">Condición</th>
                  <th scope="col" className="px-5 py-4">Destino</th>
                  <th scope="col" className="px-5 py-4">Ingreso</th>
                  <th scope="col" className="px-5 py-4"><span className="sr-only">Acciones</span></th>
                </tr>
              </thead>
              <tbody>
                {data.datos.map((e) => (
                  <tr key={e.id} className="border-b border-tierra-100 last:border-0 hover:bg-marca-50/50">
                    <td className="px-5 py-4 font-bold">
                      <Link to={`/panel/equipos/${e.id}`} className="enlace">{e.folio}</Link>
                    </td>
                    <td className="px-5 py-4">
                      <span className="block font-bold">{e.marca} {e.modelo}</span>
                      <span className="text-sm text-tinta-suave">{e.categoria.nombre}</span>
                    </td>
                    <td className="px-5 py-4"><InsigniaCondicion condicion={e.condicion} /></td>
                    <td className="px-5 py-4"><InsigniaDestino estado={e.estadoDestino} /></td>
                    <td className="whitespace-nowrap px-5 py-4">{formato.fechaCorta(e.fechaIngreso)}</td>
                    <td className="px-5 py-4 text-right">
                      <Link to={`/panel/equipos/${e.id}`} className="inline-flex min-h-11 items-center gap-1 rounded-lg px-3 font-bold text-marca-800 hover:bg-marca-100">
                        Ver <ChevronRight className="size-5" aria-hidden />
                        <span className="sr-only">equipo {e.folio}</span>
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Tarjetas en celulares */}
          <ul className="space-y-4 md:hidden">
            {data.datos.map((e) => (
              <li key={e.id}>
                <Link to={`/panel/equipos/${e.id}`} className="tarjeta block space-y-3 p-5">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <p className="text-lg font-bold text-marca-800">{e.folio}</p>
                      <p className="font-bold">{e.marca} {e.modelo}</p>
                      <p className="text-sm text-tinta-suave">{e.categoria.nombre} · {formato.fechaCorta(e.fechaIngreso)}</p>
                    </div>
                    <ChevronRight className="size-6 shrink-0 text-marca-700" aria-hidden />
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <InsigniaCondicion condicion={e.condicion} />
                    <InsigniaDestino estado={e.estadoDestino} />
                  </div>
                </Link>
              </li>
            ))}
          </ul>

          <div className="mt-6">
            <Paginacion
              pagina={data.pagina}
              totalPaginas={data.totalPaginas}
              total={data.total}
              alCambiar={(p) => actualizar('pagina', String(p))}
            />
          </div>
        </div>
      )}
    </>
  );
}
