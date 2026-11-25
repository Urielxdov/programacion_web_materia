import clsx from 'clsx';
import { ExternalLink, Save } from 'lucide-react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../../api/cliente';
import { useCampana, useCategorias, useEquipos, useGuardarCampana, useParticipaciones } from '../../api/hooks';
import type { CampanaResumen, EstadoCampana } from '../../api/tipos';
import { Boton, BotonEnlace } from '../../components/ui/Boton';
import { CampoArea, CampoTexto, OpcionesTarjeta } from '../../components/ui/Campo';
import { Cargando, ErrorCarga, Vacio } from '../../components/ui/Estados';
import { InsigniaCondicion, InsigniaDestino } from '../../components/ui/Insignia';
import { EncabezadoPagina, Indicador } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { ESTADO_CAMPANA, formato } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';

function FormularioCampana({ campana }: { campana?: CampanaResumen }) {
  const navigate = useNavigate();
  const guardar = useGuardarCampana();
  const { data: categorias } = useCategorias();
  const form = useFormulario(
    {
      nombre: campana?.nombre ?? '',
      descripcion: campana?.descripcion ?? '',
      fechaInicio: campana?.fechaInicio ?? '',
      fechaFin: campana?.fechaFin ?? '',
      ubicacion: campana?.ubicacion ?? '',
      horario: campana?.horario ?? '',
      metaEquipos: campana?.metaEquipos ? String(campana.metaEquipos) : '',
      categoriasIds: campana?.categoriasAceptadas.map((c) => c.id) ?? ([] as number[]),
      estado: (campana?.estado ?? 'PROGRAMADA') as EstadoCampana,
    },
    {
      nombre: [v.requerido('Escribe el nombre de la campaña.'), v.minimo(3), v.maximo(160)],
      descripcion: [v.requerido('Escribe una descripción.'), v.minimo(10, 'La descripción debe tener al menos 10 caracteres.')],
      fechaInicio: [v.requerido('Indica la fecha de inicio.')],
      fechaFin: [
        v.requerido('Indica la fecha de fin.'),
        (fin, todos) => (fin && todos.fechaInicio && String(fin) < todos.fechaInicio ? 'La fecha de fin no puede ser anterior al inicio.' : null),
      ],
      ubicacion: [v.requerido('Indica el lugar.'), v.minimo(3)],
      metaEquipos: [(m) => (m && Number(m) < 1 ? 'La meta debe ser de al menos 1 equipo.' : null)],
      categoriasIds: [(ids) => ((ids as number[]).length === 0 ? 'Selecciona al menos un tipo de equipo.' : null)],
    },
  );

  const alternarCategoria = (id: number) => {
    const ids = form.valores.categoriasIds;
    form.cambiar('categoriasIds', ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]);
  };

  return (
    <form
      noValidate
      className="tarjeta space-y-6 p-6 sm:p-8"
      onSubmit={form.alEnviar((d) =>
        guardar.mutate(
          {
            id: campana?.id,
            nombre: d.nombre.trim(),
            descripcion: d.descripcion.trim(),
            fechaInicio: d.fechaInicio,
            fechaFin: d.fechaFin,
            ubicacion: d.ubicacion.trim(),
            horario: d.horario.trim() || undefined,
            metaEquipos: d.metaEquipos ? Number(d.metaEquipos) : undefined,
            categoriasIds: d.categoriasIds,
            ...(campana ? { estado: d.estado } : {}),
          },
          {
            onSuccess: (c) => {
              avisar.exito(campana ? 'Campaña actualizada' : 'Campaña creada', c.nombre);
              navigate(`/panel/campanas/${c.id}`);
            },
            onError: (e) => avisar.error('No se pudo guardar la campaña', (e as ApiError).mensajes.join(' ')),
          },
        ),
      )}
    >
      <div className="grid gap-5 md:grid-cols-2">
        <div className="md:col-span-2">
          <CampoTexto etiqueta="Nombre de la campaña" obligatorio value={form.valores.nombre} onChange={(e) => form.cambiar('nombre', e.target.value)} {...form.campo('nombre')} />
        </div>
        <div className="md:col-span-2">
          <CampoArea etiqueta="Descripción" obligatorio value={form.valores.descripcion} onChange={(e) => form.cambiar('descripcion', e.target.value)} {...form.campo('descripcion')} />
        </div>
        <CampoTexto etiqueta="Fecha de inicio" obligatorio type="date" value={form.valores.fechaInicio} onChange={(e) => form.cambiar('fechaInicio', e.target.value)} {...form.campo('fechaInicio')} />
        <CampoTexto etiqueta="Fecha de fin" obligatorio type="date" min={form.valores.fechaInicio || undefined} value={form.valores.fechaFin} onChange={(e) => form.cambiar('fechaFin', e.target.value)} {...form.campo('fechaFin')} />
        <CampoTexto etiqueta="Lugar" obligatorio value={form.valores.ubicacion} onChange={(e) => form.cambiar('ubicacion', e.target.value)} {...form.campo('ubicacion')} />
        <CampoTexto etiqueta="Horario" ayuda="Por ejemplo: 9:00 a 14:00 h" value={form.valores.horario} onChange={(e) => form.cambiar('horario', e.target.value)} {...form.campo('horario')} />
        <CampoTexto
          etiqueta="Meta de equipos"
          type="number"
          inputMode="numeric"
          min={1}
          value={form.valores.metaEquipos}
          onChange={(e) => form.cambiar('metaEquipos', e.target.value)}
          {...form.campo('metaEquipos')}
        />
      </div>

      <fieldset className="space-y-3" ref={form.campo('categoriasIds').ref as (el: HTMLFieldSetElement | null) => void} tabIndex={-1}>
        <legend className="mb-1 font-bold">
          Tipos de equipo que se reciben <span className="text-red-700" aria-hidden>*</span>
        </legend>
        <div className="flex flex-wrap gap-2">
          {categorias?.map((c) => {
            const activo = form.valores.categoriasIds.includes(c.id);
            return (
              <button
                key={c.id}
                type="button"
                aria-pressed={activo}
                onClick={() => alternarCategoria(c.id)}
                className={clsx(
                  'min-h-12 rounded-full border-2 px-4 font-bold transition-colors',
                  activo ? 'border-marca-700 bg-marca-700 text-white' : 'border-tierra-300 bg-white hover:bg-tierra-100',
                )}
              >
                {activo ? '✓ ' : ''}
                {c.nombre}
              </button>
            );
          })}
        </div>
        {form.errores.categoriasIds && (
          <p role="alert" className="text-sm font-bold text-red-700">
            {form.errores.categoriasIds}
          </p>
        )}
      </fieldset>

      {campana && (
        <OpcionesTarjeta
          leyenda="Estado de la campaña"
          valor={form.valores.estado}
          onChange={(val) => form.cambiar('estado', val)}
          columnas={3}
          opciones={(Object.keys(ESTADO_CAMPANA) as EstadoCampana[]).map((e) => ({ valor: e, etiqueta: ESTADO_CAMPANA[e].etiqueta }))}
        />
      )}

      <div className="flex flex-col-reverse gap-3 border-t border-tierra-200 pt-6 sm:flex-row sm:justify-end">
        <BotonEnlace to={campana ? `/panel/campanas/${campana.id}` : '/panel/campanas'} variante="secundario">
          Cancelar
        </BotonEnlace>
        <Boton type="submit" cargando={guardar.isPending} icono={<Save className="size-5" aria-hidden />}>
          {campana ? 'Guardar cambios' : 'Publicar campaña'}
        </Boton>
      </div>
    </form>
  );
}

function DetalleCampana({ campana }: { campana: CampanaResumen }) {
  const { data: participantes } = useParticipaciones(campana.id);
  const { data: equipos } = useEquipos({ campanaId: campana.id, porPagina: 50 });
  return (
    <div className="space-y-8">
      <div className="grid gap-4 sm:grid-cols-3">
        <Indicador titulo="Participaciones" valor={campana.totalParticipaciones} />
        <Indicador titulo="Equipos declarados" valor={campana.equiposDeclarados} detalle="Lo que las personas dijeron que llevarían" />
        <Indicador
          titulo="Equipos recibidos"
          valor={campana.equiposRecibidosTotal}
          detalle={campana.metaEquipos ? `Meta: ${campana.metaEquipos}` : undefined}
        />
      </div>

      <section className="tarjeta p-6" aria-labelledby="t-recibidos">
        <h2 id="t-recibidos" className="mb-4 text-2xl">
          Equipos recibidos
        </h2>
        {!equipos?.datos.length ? (
          <Vacio titulo="Sin equipos registrados" descripcion="Al registrar un equipo, elige esta campaña como origen." />
        ) : (
          <ul className="divide-y divide-tierra-100">
            {equipos.datos.map((e) => (
              <li key={e.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                <Link to={`/panel/equipos/${e.id}`} className="enlace">
                  {e.folio} · {e.marca} {e.modelo}
                </Link>
                <span className="flex flex-wrap gap-2">
                  <InsigniaCondicion condicion={e.condicion} />
                  <InsigniaDestino estado={e.estadoDestino} />
                </span>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="tarjeta p-6" aria-labelledby="t-part">
        <h2 id="t-part" className="mb-1 text-2xl">
          Participantes
        </h2>
        <p className="mb-4 text-sm text-tinta-suave">Información privada: solo visible para el personal autorizado.</p>
        {!participantes?.length ? (
          <Vacio titulo="Aún no hay participaciones" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[36rem] text-left">
              <thead className="border-b-2 border-tierra-200 text-sm uppercase text-tinta-suave">
                <tr>
                  <th scope="col" className="py-3 pr-4">Nombre</th>
                  <th scope="col" className="py-3 pr-4">Contacto</th>
                  <th scope="col" className="py-3 pr-4">Equipos</th>
                  <th scope="col" className="py-3">Registro</th>
                </tr>
              </thead>
              <tbody>
                {participantes.map((p) => (
                  <tr key={p.id} className="border-b border-tierra-100">
                    <td className="py-3 pr-4 font-bold">{p.nombreParticipante}</td>
                    <td className="py-3 pr-4">{p.contacto ?? '—'}</td>
                    <td className="py-3 pr-4">{p.equiposDeclarados}</td>
                    <td className="py-3">{formato.fechaCorta(p.creadoEn)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}

export function CampanaGestionPage() {
  const idParam = useParams().id;
  const id = Number(idParam);
  const nueva = !idParam;
  const { data: campana, isLoading, error, refetch } = useCampana(nueva ? 0 : id);

  if (nueva) {
    return (
      <>
        <EncabezadoPagina
          titulo="Nueva campaña"
          migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Campañas', a: '/panel/campanas' }, { etiqueta: 'Nueva' }]}
        />
        <FormularioCampana />
      </>
    );
  }
  if (isLoading) return <Cargando />;
  if (error || !campana) return <ErrorCarga error={error} reintentar={() => refetch()} />;

  return (
    <>
      <EncabezadoPagina
        titulo={campana.nombre}
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Campañas', a: '/panel/campanas' }, { etiqueta: campana.nombre }]}
        acciones={
          <BotonEnlace to={`/campanas/${campana.id}`} variante="secundario" icono={<ExternalLink className="size-5" aria-hidden />}>
            Ver página pública
          </BotonEnlace>
        }
      />
      <div className="grid gap-8 xl:grid-cols-2">
        <div>
          <h2 className="mb-4 text-2xl">Datos de la campaña</h2>
          <FormularioCampana key={campana.actualizadoEn} campana={campana} />
        </div>
        <DetalleCampana campana={campana} />
      </div>
    </>
  );
}
