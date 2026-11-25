import { Save } from 'lucide-react';
import { useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../../api/cliente';
import { useActualizarEquipo, useEquipo } from '../../api/hooks';
import type { EquipoDetalle } from '../../api/tipos';
import { Boton, BotonEnlace } from '../../components/ui/Boton';
import { CampoArea, CampoTexto } from '../../components/ui/Campo';
import { Cargando, ErrorCarga } from '../../components/ui/Estados';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { hoyISO } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';

function Formulario({ equipo }: { equipo: EquipoDetalle }) {
  const navigate = useNavigate();
  const actualizar = useActualizarEquipo(equipo.id);
  const esComputo = equipo.categoria.tipo === 'COMPUTO';
  const form = useFormulario(
    {
      marca: equipo.marca,
      modelo: equipo.modelo,
      numeroSerie: equipo.numeroSerie ?? '',
      fechaIngreso: equipo.fechaIngreso,
      ubicacion: equipo.ubicacion ?? '',
      observaciones: equipo.observaciones ?? '',
    },
    {
      marca: [v.requerido('Escribe la marca.'), v.maximo(80)],
      modelo: [v.requerido('Escribe el modelo.'), v.maximo(120)],
      numeroSerie: esComputo ? [v.requerido('Las computadoras requieren número de serie.')] : [v.maximo(120)],
      fechaIngreso: [v.requerido('Indica la fecha de ingreso.'), v.fechaNoFutura()],
    },
  );

  return (
    <form
      noValidate
      className="tarjeta space-y-6 p-6 sm:p-8"
      onSubmit={form.alEnviar((d) =>
        actualizar.mutate(
          {
            marca: d.marca.trim(),
            modelo: d.modelo.trim(),
            numeroSerie: d.numeroSerie.trim(),
            fechaIngreso: d.fechaIngreso,
            ubicacion: d.ubicacion.trim(),
            observaciones: d.observaciones.trim(),
          },
          {
            onSuccess: () => {
              avisar.exito('Cambios guardados');
              navigate(`/panel/equipos/${equipo.id}`);
            },
            onError: (e) => {
              const err = e as ApiError;
              avisar.error('No se pudieron guardar los cambios', err.mensajes.join(' '));
              if (/serie/i.test(err.message)) form.marcarErrores({ numeroSerie: err.message });
            },
          },
        ),
      )}
    >
      <p className="rounded-xl bg-sky-50 p-4 text-sky-900">
        La condición y el destino del equipo no se editan aquí: cambian al registrar diagnósticos, reparaciones o
        movimientos, para conservar el historial.
      </p>
      <div className="grid gap-5 md:grid-cols-2">
        <CampoTexto etiqueta="Marca" obligatorio value={form.valores.marca} onChange={(e) => form.cambiar('marca', e.target.value)} {...form.campo('marca')} />
        <CampoTexto etiqueta="Modelo" obligatorio value={form.valores.modelo} onChange={(e) => form.cambiar('modelo', e.target.value)} {...form.campo('modelo')} />
        <CampoTexto
          etiqueta="Número de serie"
          obligatorio={esComputo}
          value={form.valores.numeroSerie}
          onChange={(e) => form.cambiar('numeroSerie', e.target.value)}
          {...form.campo('numeroSerie')}
        />
        <CampoTexto
          etiqueta="Fecha de ingreso"
          obligatorio
          type="date"
          max={hoyISO()}
          value={form.valores.fechaIngreso}
          onChange={(e) => form.cambiar('fechaIngreso', e.target.value)}
          {...form.campo('fechaIngreso')}
        />
        <div className="md:col-span-2">
          <CampoTexto etiqueta="Ubicación" value={form.valores.ubicacion} onChange={(e) => form.cambiar('ubicacion', e.target.value)} {...form.campo('ubicacion')} />
        </div>
        <div className="md:col-span-2">
          <CampoArea etiqueta="Observaciones" value={form.valores.observaciones} onChange={(e) => form.cambiar('observaciones', e.target.value)} {...form.campo('observaciones')} />
        </div>
      </div>
      <div className="flex flex-col-reverse gap-3 border-t border-tierra-200 pt-6 sm:flex-row sm:justify-end">
        <BotonEnlace to={`/panel/equipos/${equipo.id}`} variante="secundario">
          Cancelar
        </BotonEnlace>
        <Boton type="submit" cargando={actualizar.isPending} icono={<Save className="size-5" aria-hidden />}>
          Guardar cambios
        </Boton>
      </div>
    </form>
  );
}

export function EquipoEditarPage() {
  const id = Number(useParams().id);
  const { data: equipo, isLoading, error, refetch } = useEquipo(id);
  if (isLoading) return <Cargando />;
  if (error || !equipo) return <ErrorCarga error={error} reintentar={() => refetch()} />;
  return (
    <>
      <EncabezadoPagina
        titulo={`Editar ${equipo.folio}`}
        migas={[
          { etiqueta: 'Panel', a: '/panel' },
          { etiqueta: 'Equipos', a: '/panel/equipos' },
          { etiqueta: equipo.folio, a: `/panel/equipos/${equipo.id}` },
          { etiqueta: 'Editar' },
        ]}
      />
      <Formulario equipo={equipo} />
    </>
  );
}
