import { ArrowRight, Boxes, CircleDashed, Leaf, PiggyBank, PlusCircle, Recycle, Search, Wrench } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import { useIndicadores, useMovimientosRecientes, useSesion } from '../../api/hooks';
import type { Condicion } from '../../api/tipos';
import { Cargando, ErrorCarga, Vacio } from '../../components/ui/Estados';
import { EncabezadoPagina, Indicador } from '../../components/ui/Pagina';
import { CONDICION, formato, ROL, TIPO_MOVIMIENTO } from '../../lib/formato';
import { COLORES_CONDICION } from '../../lib/visual';

function Acceso({ a, icono, titulo, texto }: { a: string; icono: ReactNode; titulo: string; texto: string }) {
  return (
    <Link to={a} className="tarjeta group flex items-center gap-4 p-5 transition-shadow hover:shadow-md">
      <span className="rounded-xl bg-marca-100 p-3 text-marca-800">{icono}</span>
      <span className="flex-1">
        <span className="block text-lg font-bold">{titulo}</span>
        <span className="block text-tinta-suave">{texto}</span>
      </span>
      <ArrowRight className="size-6 text-marca-700 transition-transform group-hover:translate-x-1" aria-hidden />
    </Link>
  );
}

export function PanelPage() {
  const { data: usuario } = useSesion();
  const { data, isLoading, error, refetch } = useIndicadores();
  const { data: recientes } = useMovimientosRecientes(8);
  const gestiona = usuario?.rol === 'RESPONSABLE' || usuario?.rol === 'ADMIN';

  if (!usuario) return null;

  return (
    <>
      <EncabezadoPagina
        titulo="Panel principal"
        descripcion={`Sesión iniciada como ${ROL[usuario.rol].etiqueta}. ${ROL[usuario.rol].descripcion}`}
      />

      <section aria-labelledby="titulo-accesos" className="mb-10">
        <h2 id="titulo-accesos" className="sr-only">
          Accesos rápidos
        </h2>
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <Acceso a="/panel/equipos/nuevo" icono={<PlusCircle className="size-7" aria-hidden />} titulo="Registrar un equipo" texto="Dar de alta un aparato en el inventario" />
          <Acceso a="/panel/equipos" icono={<Search className="size-7" aria-hidden />} titulo="Buscar en el inventario" texto="Consultar y actualizar equipos" />
          {gestiona && (
            <Acceso
              a="/panel/equipos?condicion=PENDIENTE"
              icono={<CircleDashed className="size-7" aria-hidden />}
              titulo="Equipos por diagnosticar"
              texto={data ? `${data.equipos.pendientesDiagnostico} esperan revisión` : 'Revisar pendientes'}
            />
          )}
        </div>
      </section>

      {isLoading && <Cargando />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      {data && (
        <>
          <section aria-labelledby="titulo-indicadores" className="mb-10">
            <h2 id="titulo-indicadores" className="mb-4 text-2xl">
              Resumen del inventario
            </h2>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <Indicador titulo="Equipos registrados" valor={data.equipos.total} icono={<Boxes className="size-6" aria-hidden />} />
              <Indicador
                titulo="Equipos recuperados"
                valor={data.equipos.recuperados}
                detalle={`${data.equipos.reasignados} reasignados · ${data.equipos.reparados} reparados`}
                icono={<Recycle className="size-6" aria-hidden />}
              />
              <Indicador
                titulo="Ahorro estimado"
                valor={data.economia.ahorroEstimado}
                formatear={formato.moneda}
                detalle={`Costo de reparaciones: ${formato.moneda(data.economia.costoReparaciones)}`}
                icono={<PiggyBank className="size-6" aria-hidden />}
                tono="ambar"
              />
              <Indicador
                titulo="Residuo evitado"
                valor={data.ambiente.kgResiduoEvitado}
                formatear={formato.kg}
                detalle={`${data.gestores.equiposEntregados} equipos entregados a gestores verificados`}
                icono={<Leaf className="size-6" aria-hidden />}
                tono="cielo"
              />
            </div>
          </section>

          <div className="grid gap-6 lg:grid-cols-2">
            <section className="tarjeta p-6" aria-labelledby="titulo-condicion">
              <h2 id="titulo-condicion" className="mb-2 text-2xl">
                Equipos por condición
              </h2>
              <div className="grid items-center gap-4 sm:grid-cols-2">
                <div className="h-56" role="img" aria-label="Gráfica de pastel de equipos por condición; los valores están en la lista de al lado.">
                  <ResponsiveContainer>
                    <PieChart>
                      <Pie data={data.porCondicion} dataKey="total" nameKey="clave" innerRadius="55%" outerRadius="90%" paddingAngle={2}>
                        {data.porCondicion.map((c) => (
                          <Cell key={c.clave} fill={COLORES_CONDICION[c.clave as Condicion]} />
                        ))}
                      </Pie>
                      <Tooltip formatter={(v, n) => [v, CONDICION[n as Condicion]?.etiqueta ?? n]} />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
                <ul className="space-y-2">
                  {data.porCondicion.map((c) => (
                    <li key={c.clave}>
                      <Link
                        to={`/panel/equipos?condicion=${c.clave}`}
                        className="flex items-center gap-3 rounded-lg p-1.5 hover:bg-tierra-100"
                      >
                        <span className="size-4 shrink-0 rounded" style={{ background: COLORES_CONDICION[c.clave as Condicion] }} />
                        <span className="flex-1">{CONDICION[c.clave as Condicion]?.etiqueta}</span>
                        <strong>{c.total}</strong>
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            </section>

            <section className="tarjeta p-6" aria-labelledby="titulo-actividad">
              <h2 id="titulo-actividad" className="mb-4 text-2xl">
                Actividad reciente
              </h2>
              {!recientes?.length ? (
                <Vacio titulo="Sin movimientos todavía" />
              ) : (
                <ol className="space-y-3">
                  {recientes.map((m) => (
                    <li key={m.id} className="flex gap-3 border-b border-tierra-100 pb-3 last:border-0">
                      <Wrench className="mt-1 size-5 shrink-0 text-marca-700" aria-hidden />
                      <div className="min-w-0">
                        <p>
                          <strong>{TIPO_MOVIMIENTO[m.tipo]}</strong>
                          {m.equipo && (
                            <>
                              {' · '}
                              <Link to={`/panel/equipos/${m.equipo.id}`} className="enlace">
                                {m.equipo.folio}
                              </Link>
                            </>
                          )}
                        </p>
                        <p className="text-sm text-tinta-suave">
                          {formato.fechaHora(m.creadoEn)}
                          {m.responsable && ` · ${m.responsable.nombre}`}
                        </p>
                      </div>
                    </li>
                  ))}
                </ol>
              )}
            </section>
          </div>
        </>
      )}
    </>
  );
}
