import {
  Archive,
  ClipboardCheck,
  FileText,
  Info,
  PackageCheck,
  Pencil,
  Printer,
  ShieldCheck,
  Stethoscope,
  Truck,
  Wrench,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ApiError } from '../../api/cliente';
import { useEquipo, useRegistrarDecision, useSesion } from '../../api/hooks';
import type { AccionEquipo, EquipoDetalle, TipoMovimiento } from '../../api/tipos';
import { Boton, BotonEnlace } from '../../components/ui/Boton';
import { Cargando, ErrorCarga } from '../../components/ui/Estados';
import { InsigniaCondicion, InsigniaDestino } from '../../components/ui/Insignia';
import { Confirmar } from '../../components/ui/Modal';
import { Dato, EncabezadoPagina } from '../../components/ui/Pagina';
import { CONDICION, ESTADO_DESTINO, formato, TIPO_MOVIMIENTO } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';
import { ModalDecision, ModalDiagnostico, ModalReparacion } from './AccionesEquipo';

const ACCIONES: Record<AccionEquipo, { etiqueta: string; descripcion: string; icono: ReactNode }> = {
  DIAGNOSTICAR: {
    etiqueta: 'Registrar diagnóstico',
    descripcion: 'Revisa el equipo; el sistema recomienda su destino.',
    icono: <Stethoscope className="size-6" aria-hidden />,
  },
  ENVIAR_REPARACION: {
    etiqueta: 'Enviar a reparación',
    descripcion: 'Conviene repararlo antes de considerarlo residuo.',
    icono: <Wrench className="size-6" aria-hidden />,
  },
  REGISTRAR_REPARACION: {
    etiqueta: 'Registrar resultado de la reparación',
    descripcion: 'Indica si quedó funcionando y cuánto costó.',
    icono: <ClipboardCheck className="size-6" aria-hidden />,
  },
  REASIGNAR: {
    etiqueta: 'Reasignar a un área',
    descripcion: 'Darle una segunda vida en otro departamento o laboratorio.',
    icono: <PackageCheck className="size-6" aria-hidden />,
  },
  CONSERVAR: {
    etiqueta: 'Conservar como respaldo',
    descripcion: 'Guardarlo para usarlo más adelante.',
    icono: <Archive className="size-6" aria-hidden />,
  },
  ENTREGAR_GESTOR: {
    etiqueta: 'Entregar a gestor verificado',
    descripcion: 'Para equipos que ya no pueden aprovecharse.',
    icono: <Truck className="size-6" aria-hidden />,
  },
};

const ICONO_MOV: Partial<Record<TipoMovimiento, ReactNode>> = {
  INGRESO: <FileText className="size-5" aria-hidden />,
  DIAGNOSTICO: <Stethoscope className="size-5" aria-hidden />,
  ENVIO_REPARACION: <Wrench className="size-5" aria-hidden />,
  REPARACION: <ClipboardCheck className="size-5" aria-hidden />,
  REASIGNACION: <PackageCheck className="size-5" aria-hidden />,
  CONSERVACION: <Archive className="size-5" aria-hidden />,
  ENTREGA_GESTOR: <Truck className="size-5" aria-hidden />,
};

function LineaDeTiempo({ equipo }: { equipo: EquipoDetalle }) {
  return (
    <ol className="relative space-y-6 border-l-4 border-marca-200 pl-8">
      {equipo.movimientos.map((m) => (
        <li key={m.id} className="relative">
          <span className="absolute -left-[3.05rem] flex size-10 items-center justify-center rounded-full border-4 border-tierra-50 bg-marca-700 text-white">
            {ICONO_MOV[m.tipo]}
          </span>
          <div className="rounded-xl bg-tierra-50 p-4">
            <p className="flex flex-wrap items-baseline justify-between gap-2">
              <strong className="text-lg">{TIPO_MOVIMIENTO[m.tipo]}</strong>
              <time dateTime={m.creadoEn} className="text-sm text-tinta-suave">
                {formato.fechaHora(m.creadoEn)}
              </time>
            </p>
            <p className="mt-1">{m.descripcion}</p>
            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-tinta-suave">
              {m.responsable && <span>Responsable: {m.responsable.nombre}</span>}
              {m.estadoAnterior && m.estadoAnterior !== m.estadoNuevo && (
                <span>
                  {ESTADO_DESTINO[m.estadoAnterior].etiqueta} → {ESTADO_DESTINO[m.estadoNuevo].etiqueta}
                </span>
              )}
              {m.evidencia && (
                <span className="font-bold text-marca-800">
                  <ShieldCheck className="mr-1 inline size-4" aria-hidden />
                  Respaldo: {m.evidencia}
                </span>
              )}
            </p>
          </div>
        </li>
      ))}
    </ol>
  );
}

export function EquipoDetallePage() {
  const id = Number(useParams().id);
  const { data: usuario } = useSesion();
  const { data: equipo, isLoading, error, refetch } = useEquipo(id);
  const [accion, setAccion] = useState<AccionEquipo | null>(null);
  const enviar = useRegistrarDecision(id);

  if (isLoading) return <Cargando />;
  if (error || !equipo) return <ErrorCarga error={error} reintentar={() => refetch()} />;

  const gestiona = usuario?.rol === 'RESPONSABLE' || usuario?.rol === 'ADMIN';
  const cerrar = () => setAccion(null);
  const finalizado = equipo.estadoDestino === 'REASIGNADO' || equipo.estadoDestino === 'ENTREGADO_GESTOR';

  return (
    <>
      <EncabezadoPagina
        titulo={`${equipo.folio} · ${equipo.marca} ${equipo.modelo}`}
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Equipos', a: '/panel/equipos' }, { etiqueta: equipo.folio }]}
        acciones={
          <>
            <Boton variante="secundario" onClick={() => window.print()} icono={<Printer className="size-5" aria-hidden />}>
              Imprimir ficha
            </Boton>
            {!finalizado && (
              <BotonEnlace to={`/panel/equipos/${equipo.id}/editar`} variante="secundario" icono={<Pencil className="size-5" aria-hidden />}>
                Editar datos
              </BotonEnlace>
            )}
          </>
        }
      />

      <div className="mb-8 flex flex-wrap items-center gap-3">
        <InsigniaCondicion condicion={equipo.condicion} />
        <InsigniaDestino estado={equipo.estadoDestino} />
        {equipo.destinoActual && <span className="text-lg">→ {equipo.destinoActual}</span>}
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_22rem]">
        <div className="space-y-8">
          <section className="tarjeta p-6" aria-labelledby="titulo-ficha">
            <h2 id="titulo-ficha" className="mb-5 text-2xl">
              Ficha del equipo
            </h2>
            <dl className="grid gap-5 sm:grid-cols-2">
              <Dato etiqueta="Tipo">{equipo.categoria.nombre}</Dato>
              <Dato etiqueta="Número de serie">{equipo.numeroSerie ?? 'No registrado'}</Dato>
              <Dato etiqueta="Fecha de ingreso">{formato.fecha(equipo.fechaIngreso)}</Dato>
              <Dato etiqueta="Ubicación">{equipo.ubicacion ?? 'No indicada'}</Dato>
              <Dato etiqueta="Condición">{CONDICION[equipo.condicion].descripcion}</Dato>
              <Dato etiqueta="Campaña de origen">
                {equipo.campana ? equipo.campana.nombre : 'Ingreso directo'}
              </Dato>
              <Dato etiqueta="Registrado por">{equipo.registradoPor?.nombre ?? '—'}</Dato>
              <Dato etiqueta="Borrado de datos">
                {!equipo.requiereBorradoDatos ? 'No aplica' : equipo.datosBorrados ? 'Confirmado' : 'Pendiente'}
              </Dato>
              {equipo.observaciones && (
                <div className="sm:col-span-2">
                  <Dato etiqueta="Observaciones">{equipo.observaciones}</Dato>
                </div>
              )}
            </dl>
          </section>

          {equipo.diagnosticos.length > 0 && (
            <section className="tarjeta p-6" aria-labelledby="titulo-diag">
              <h2 id="titulo-diag" className="mb-5 text-2xl">
                Diagnósticos
              </h2>
              <ul className="space-y-4">
                {equipo.diagnosticos.map((d) => (
                  <li key={d.id} className="space-y-2 rounded-xl border-2 border-tierra-200 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <InsigniaCondicion condicion={d.recomendacion} />
                      <span className="text-sm text-tinta-suave">
                        {formato.fechaHora(d.creadoEn)} · {d.responsable?.nombre}
                      </span>
                    </div>
                    <p>{d.descripcion}</p>
                    <p className="rounded-lg bg-marca-50 p-3 text-marca-900">
                      <strong>Recomendación:</strong> {d.justificacion}
                    </p>
                    <p className="text-sm text-tinta-suave">
                      {d.enciende ? 'Enciende' : 'No enciende'} · Costo estimado {formato.moneda(d.costoReparacionEstimado)}
                      {d.danoEstructural && ' · Daño estructural'}
                      {d.componentesPeligrosos && ' · Componentes peligrosos'}
                    </p>
                  </li>
                ))}
              </ul>
            </section>
          )}

          {equipo.reparaciones.length > 0 && (
            <section className="tarjeta p-6" aria-labelledby="titulo-rep">
              <h2 id="titulo-rep" className="mb-5 text-2xl">
                Reparaciones
              </h2>
              <ul className="space-y-4">
                {equipo.reparaciones.map((r) => (
                  <li key={r.id} className="grid gap-2 rounded-xl border-2 border-tierra-200 p-4 sm:grid-cols-3">
                    <Dato etiqueta="Resultado">{r.resultado === 'EXITOSA' ? 'Exitosa' : 'Sin éxito'}</Dato>
                    <Dato etiqueta="Costo">{formato.moneda(r.costo)}</Dato>
                    <Dato etiqueta="Ahorro estimado">{formato.moneda(r.ahorroEstimado)}</Dato>
                    <p className="sm:col-span-3">{r.descripcion}</p>
                  </li>
                ))}
              </ul>
            </section>
          )}

          <section className="tarjeta p-6" aria-labelledby="titulo-historial">
            <h2 id="titulo-historial" className="mb-2 text-2xl">
              Historial del equipo
            </h2>
            <p className="mb-6 text-tinta-suave">Cada movimiento queda registrado y no puede modificarse.</p>
            <LineaDeTiempo equipo={equipo} />
          </section>
        </div>

        <aside className="no-imprimir space-y-6">
          <section className="tarjeta p-6" aria-labelledby="titulo-sigue">
            <h2 id="titulo-sigue" className="mb-4 text-2xl">
              ¿Qué sigue?
            </h2>
            {finalizado ? (
              <p className="flex items-start gap-2 text-lg">
                <ShieldCheck className="mt-1 size-6 shrink-0 text-marca-700" aria-hidden />
                Este equipo ya tiene un destino final documentado.
              </p>
            ) : !gestiona ? (
              <p className="flex items-start gap-2 text-lg">
                <Info className="mt-1 size-6 shrink-0 text-sky-700" aria-hidden />
                Una persona responsable ambiental revisará este equipo y decidirá su destino.
              </p>
            ) : (
              <ul className="space-y-3">
                {equipo.accionesDisponibles.map((a) => (
                  <li key={a}>
                    <button
                      type="button"
                      onClick={() => setAccion(a)}
                      className="flex w-full items-start gap-3 rounded-xl border-2 border-tierra-200 bg-white p-4 text-left transition-colors hover:border-marca-600 hover:bg-marca-50"
                    >
                      <span className="rounded-lg bg-marca-100 p-2 text-marca-800">{ACCIONES[a].icono}</span>
                      <span>
                        <span className="block text-lg font-bold">{ACCIONES[a].etiqueta}</span>
                        <span className="block text-sm text-tinta-suave">{ACCIONES[a].descripcion}</span>
                      </span>
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-2xl border-2 border-amber-300 bg-amber-50 p-6" aria-labelledby="titulo-recom">
            <h2 id="titulo-recom" className="mb-3 text-xl">
              Recomendaciones para entregar este equipo
            </h2>
            <ul className="list-disc space-y-2 pl-5">
              {equipo.recomendacionesEntrega.map((r) => (
                <li key={r}>{r}</li>
              ))}
            </ul>
          </section>

          <Link to="/panel/equipos" className="enlace block">
            ← Volver a la lista de equipos
          </Link>
        </aside>
      </div>

      {accion === 'DIAGNOSTICAR' && <ModalDiagnostico equipo={equipo} abierto alCerrar={cerrar} />}
      {accion === 'REGISTRAR_REPARACION' && <ModalReparacion equipo={equipo} abierto alCerrar={cerrar} />}
      {accion === 'REASIGNAR' && <ModalDecision equipo={equipo} abierto alCerrar={cerrar} tipo="REASIGNACION" />}
      {accion === 'CONSERVAR' && <ModalDecision equipo={equipo} abierto alCerrar={cerrar} tipo="CONSERVACION" />}
      {accion === 'ENTREGAR_GESTOR' && <ModalDecision equipo={equipo} abierto alCerrar={cerrar} tipo="ENTREGA_GESTOR" />}
      <Confirmar
        abierto={accion === 'ENVIAR_REPARACION'}
        alCerrar={cerrar}
        titulo="¿Enviar a reparación?"
        mensaje={
          <p>
            El equipo <strong>{equipo.folio}</strong> quedará marcado como "en reparación" hasta que registres el
            resultado.
          </p>
        }
        textoConfirmar="Sí, enviar a reparación"
        cargando={enviar.isPending}
        alConfirmar={() =>
          enviar.mutate(
            { tipo: 'ENVIO_REPARACION' },
            {
              onSuccess: () => {
                avisar.exito('Equipo enviado a reparación');
                cerrar();
              },
              onError: (e) => avisar.error('No se pudo enviar a reparación', (e as ApiError).message),
            },
          )
        }
      />
    </>
  );
}
