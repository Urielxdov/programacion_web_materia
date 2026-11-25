import { Download, Leaf, PiggyBank, Printer, Recycle, Wrench } from 'lucide-react';
import type { ReactNode } from 'react';
import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import { useIndicadores, useIndicadoresCampanas, useIndicadoresReutilizacion } from '../../api/hooks';
import { Boton } from '../../components/ui/Boton';
import { Cargando, ErrorCarga } from '../../components/ui/Estados';
import { InsigniaCampana } from '../../components/ui/Insignia';
import { EncabezadoPagina, Indicador } from '../../components/ui/Pagina';
import { descargarCsv } from '../../lib/exportar';
import { CONDICION, ESTADO_DESTINO, formato, hoyISO } from '../../lib/formato';
import type { Condicion, EstadoDestino } from '../../api/tipos';

const C = { verde: '#1d7d45', cielo: '#0284c7', ambar: '#d97706', violeta: '#7c3aed', gris: '#8a8f87' };
const ejes = { fontSize: 14, fill: '#4a5850' };

function Seccion({ id, titulo, descripcion, children }: { id: string; titulo: string; descripcion?: string; children: ReactNode }) {
  return (
    <section className="tarjeta p-6" aria-labelledby={id}>
      <h2 id={id} className="text-2xl">
        {titulo}
      </h2>
      {descripcion && <p className="mt-1 text-tinta-suave">{descripcion}</p>}
      <div className="mt-5">{children}</div>
    </section>
  );
}

export function ReportesPage() {
  const resumen = useIndicadores();
  const reutil = useIndicadoresReutilizacion();
  const campanas = useIndicadoresCampanas();

  if (resumen.isLoading || reutil.isLoading || campanas.isLoading) return <Cargando texto="Preparando reportes…" />;
  const error = resumen.error ?? reutil.error ?? campanas.error;
  if (error || !resumen.data || !reutil.data || !campanas.data) {
    return <ErrorCarga error={error} reintentar={() => void Promise.all([resumen.refetch(), reutil.refetch(), campanas.refetch()])} />;
  }
  const r = resumen.data;
  const u = reutil.data;
  const cp = campanas.data;
  const serie = u.serieMensual.map((m) => ({ ...m, etiqueta: formato.mes(m.mes) }));

  const exportar = () => {
    const filas: (string | number | null)[][] = [
      ['Resumen', 'Equipos registrados', r.equipos.total],
      ['Resumen', 'Equipos recuperados (reparados o reasignados)', r.equipos.recuperados],
      ['Resumen', 'Equipos reasignados', r.equipos.reasignados],
      ['Resumen', 'Reparaciones exitosas', u.reparaciones.exitosas],
      ['Resumen', 'Reparaciones sin éxito', u.reparaciones.fallidas],
      ['Resumen', 'Equipos entregados a gestores verificados', r.gestores.equiposEntregados],
      ['Resumen', 'Kg entregados a gestores', r.gestores.kgEntregados],
      ['Resumen', 'Costo total de reparaciones (MXN)', r.economia.costoReparaciones],
      ['Resumen', 'Ahorro estimado (MXN)', r.economia.ahorroEstimado],
      ['Resumen', 'Kg de residuo evitado', r.ambiente.kgResiduoEvitado],
      ...r.porCondicion.map((c) => ['Por condición', CONDICION[c.clave as Condicion]?.etiqueta ?? c.clave, c.total]),
      ...r.porEstado.map((c) => ['Por destino', ESTADO_DESTINO[c.clave as EstadoDestino]?.etiqueta ?? c.clave, c.total]),
      ...u.porCategoria.map((c) => ['Por categoría', `${c.categoria} (registrados/reparados/reasignados/entregados)`, `${c.registrados}/${c.reparados}/${c.reasignados}/${c.entregados}`]),
      ...cp.campanas.map((c) => ['Campañas', `${c.nombre} (participaciones/recibidos)`, `${c.participaciones}/${c.equiposRecibidos}`]),
      ...u.serieMensual.map((m) => ['Mensual', `${m.mes} (registrados/reparaciones/reasignados/entregados)`, `${m.registrados}/${m.reparaciones}/${m.reasignados}/${m.entregados}`]),
    ];
    descargarCsv(`ewaste360-reporte-${hoyISO()}.csv`, ['Sección', 'Indicador', 'Valor'], filas);
  };

  return (
    <>
      <EncabezadoPagina
        titulo="Reportes e indicadores"
        descripcion={`Resultados ambientales, sociales y económicos. Generado el ${formato.fecha(hoyISO())}.`}
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Reportes' }]}
        acciones={
          <>
            <Boton variante="secundario" onClick={exportar} icono={<Download className="size-5" aria-hidden />}>
              Descargar CSV
            </Boton>
            <Boton onClick={() => window.print()} icono={<Printer className="size-5" aria-hidden />}>
              Imprimir reporte
            </Boton>
          </>
        }
      />

      <div className="space-y-8">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <Indicador titulo="Equipos recuperados" valor={r.equipos.recuperados} detalle={`de ${r.equipos.total} registrados`} icono={<Recycle className="size-6" aria-hidden />} />
          <Indicador
            titulo="Éxito en reparaciones"
            valor={u.reparaciones.tasaExito}
            formatear={(n) => `${Math.round(n)} %`}
            detalle={`${u.reparaciones.exitosas} de ${u.reparaciones.total} reparaciones`}
            icono={<Wrench className="size-6" aria-hidden />}
            tono="cielo"
          />
          <Indicador
            titulo="Ahorro estimado"
            valor={u.reutilizacion.ahorroTotal}
            formatear={formato.moneda}
            detalle={`Inversión en reparaciones: ${formato.moneda(u.reparaciones.costoTotal)}`}
            icono={<PiggyBank className="size-6" aria-hidden />}
            tono="ambar"
          />
          <Indicador
            titulo="Residuo evitado"
            valor={u.reutilizacion.kgResiduoEvitado}
            formatear={formato.kg}
            detalle={`${formato.kg(r.gestores.kgEntregados)} entregados a gestores verificados`}
            icono={<Leaf className="size-6" aria-hidden />}
            tono="violeta"
          />
        </div>

        <Seccion id="r-mensual" titulo="Actividad de los últimos 12 meses" descripcion="Equipos que ingresaron y cómo se gestionaron cada mes.">
          <div className="h-80" role="img" aria-label="Gráfica de barras mensual. Los datos también están disponibles en el archivo CSV.">
            <ResponsiveContainer>
              <BarChart data={serie} margin={{ left: -16 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="etiqueta" tick={ejes} />
                <YAxis allowDecimals={false} tick={ejes} />
                <Tooltip />
                <Legend wrapperStyle={{ fontSize: 15 }} />
                <Bar dataKey="registrados" name="Registrados" fill={C.gris} radius={[4, 4, 0, 0]} />
                <Bar dataKey="reparaciones" name="Reparaciones" fill={C.cielo} radius={[4, 4, 0, 0]} />
                <Bar dataKey="reasignados" name="Reasignados" fill={C.verde} radius={[4, 4, 0, 0]} />
                <Bar dataKey="entregados" name="Entregados a gestor" fill={C.violeta} radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Seccion>

        <div className="grid gap-8 xl:grid-cols-2">
          <Seccion id="r-categoria" titulo="Resultados por tipo de equipo">
            <div className="h-80" role="img" aria-label="Gráfica de barras por tipo de equipo; los valores están en la tabla de abajo.">
              <ResponsiveContainer>
                <BarChart data={u.porCategoria} layout="vertical" margin={{ left: 40 }}>
                  <CartesianGrid strokeDasharray="3 3" horizontal={false} />
                  <XAxis type="number" allowDecimals={false} tick={ejes} />
                  <YAxis type="category" dataKey="categoria" width={120} tick={{ ...ejes, fontSize: 13 }} />
                  <Tooltip />
                  <Legend wrapperStyle={{ fontSize: 15 }} />
                  <Bar dataKey="reasignados" name="Reasignados" stackId="a" fill={C.verde} />
                  <Bar dataKey="reparados" name="Reparados" stackId="a" fill={C.cielo} />
                  <Bar dataKey="entregados" name="Entregados" stackId="a" fill={C.violeta} />
                </BarChart>
              </ResponsiveContainer>
            </div>
            <div className="mt-4 overflow-x-auto">
              <table className="w-full min-w-[30rem] text-left">
                <thead className="border-b-2 border-tierra-200 text-sm uppercase text-tinta-suave">
                  <tr>
                    <th scope="col" className="py-2 pr-3">Tipo</th>
                    <th scope="col" className="py-2 pr-3 text-right">Registrados</th>
                    <th scope="col" className="py-2 pr-3 text-right">Reparados</th>
                    <th scope="col" className="py-2 pr-3 text-right">Reasignados</th>
                    <th scope="col" className="py-2 text-right">Entregados</th>
                  </tr>
                </thead>
                <tbody>
                  {u.porCategoria.map((c) => (
                    <tr key={c.categoria} className="border-b border-tierra-100">
                      <th scope="row" className="py-2 pr-3 font-bold">{c.categoria}</th>
                      <td className="py-2 pr-3 text-right">{c.registrados}</td>
                      <td className="py-2 pr-3 text-right">{c.reparados}</td>
                      <td className="py-2 pr-3 text-right">{c.reasignados}</td>
                      <td className="py-2 text-right">{c.entregados}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Seccion>

          <div className="space-y-8">
            <Seccion id="r-destinos" titulo="¿A dónde se reasignaron?" descripcion="Áreas que recibieron equipos recuperados.">
              {u.destinos.length === 0 ? (
                <p className="text-tinta-suave">Aún no hay reasignaciones.</p>
              ) : (
                <ul className="space-y-3">
                  {u.destinos.map((d) => {
                    const max = Math.max(...u.destinos.map((x) => x.total));
                    return (
                      <li key={d.destino}>
                        <div className="mb-1 flex justify-between gap-3">
                          <span>{d.destino}</span>
                          <strong>{d.total}</strong>
                        </div>
                        <div className="h-3 rounded-full bg-tierra-100" aria-hidden>
                          <div className="h-full rounded-full bg-marca-600" style={{ width: `${(d.total / max) * 100}%` }} />
                        </div>
                      </li>
                    );
                  })}
                </ul>
              )}
            </Seccion>

            <Seccion id="r-economia" titulo="Balance económico">
              <dl className="grid gap-4 sm:grid-cols-2">
                <div className="rounded-xl bg-tierra-50 p-4">
                  <dt className="text-sm font-bold text-tinta-suave">Ahorro por reparaciones</dt>
                  <dd className="text-2xl font-bold">{formato.moneda(u.reparaciones.ahorroReparaciones)}</dd>
                </div>
                <div className="rounded-xl bg-tierra-50 p-4">
                  <dt className="text-sm font-bold text-tinta-suave">Ahorro por reutilización directa</dt>
                  <dd className="text-2xl font-bold">{formato.moneda(u.reutilizacion.ahorroReutilizacionDirecta)}</dd>
                </div>
                <div className="rounded-xl bg-tierra-50 p-4">
                  <dt className="text-sm font-bold text-tinta-suave">Costo promedio por reparación</dt>
                  <dd className="text-2xl font-bold">{formato.moneda(u.reparaciones.costoPromedio)}</dd>
                </div>
                <div className="rounded-xl bg-tierra-50 p-4">
                  <dt className="text-sm font-bold text-tinta-suave">Tasa de reutilización</dt>
                  <dd className="text-2xl font-bold">{u.reutilizacion.tasaReutilizacion} %</dd>
                </div>
              </dl>
              <p className="mt-4 text-sm text-tinta-suave">
                El ahorro se estima con el valor de reposición de cada tipo de equipo menos el costo real de reparación.
              </p>
            </Seccion>
          </div>
        </div>

        <Seccion id="r-campanas" titulo="Resultados por campaña" descripcion={`${cp.totales.participaciones} participaciones · ${cp.totales.equiposRecibidos} equipos recibidos · ${formato.kg(cp.totales.kgRecolectados)} recolectados`}>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[44rem] text-left">
              <thead className="border-b-2 border-tierra-200 text-sm uppercase text-tinta-suave">
                <tr>
                  <th scope="col" className="py-2 pr-3">Campaña</th>
                  <th scope="col" className="py-2 pr-3">Estado</th>
                  <th scope="col" className="py-2 pr-3 text-right">Participaciones</th>
                  <th scope="col" className="py-2 pr-3 text-right">Declarados</th>
                  <th scope="col" className="py-2 pr-3 text-right">Recibidos</th>
                  <th scope="col" className="py-2 pr-3 text-right">Kg</th>
                  <th scope="col" className="py-2">Avance de meta</th>
                </tr>
              </thead>
              <tbody>
                {cp.campanas.map((c) => (
                  <tr key={c.id} className="border-b border-tierra-100">
                    <th scope="row" className="py-3 pr-3 font-bold">{c.nombre}</th>
                    <td className="py-3 pr-3"><InsigniaCampana estado={c.estado} /></td>
                    <td className="py-3 pr-3 text-right">{c.participaciones}</td>
                    <td className="py-3 pr-3 text-right">{c.equiposDeclarados}</td>
                    <td className="py-3 pr-3 text-right">{c.equiposRecibidos}</td>
                    <td className="py-3 pr-3 text-right">{formato.numero(c.kgRecolectados)}</td>
                    <td className="py-3">{c.avanceMeta === null ? '—' : `${c.avanceMeta} % de ${c.metaEquipos}`}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Seccion>
      </div>
    </>
  );
}
