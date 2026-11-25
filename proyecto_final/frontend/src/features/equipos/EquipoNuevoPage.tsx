import clsx from 'clsx';
import { ArrowLeft, ArrowRight, Check, Info } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ApiError } from '../../api/cliente';
import { useCampanas, useCategorias, useRegistrarEquipo } from '../../api/hooks';
import { Boton } from '../../components/ui/Boton';
import { CampoArea, CampoSelect, CampoTexto, OpcionesTarjeta } from '../../components/ui/Campo';
import { Cargando } from '../../components/ui/Estados';
import { Dato, EncabezadoPagina } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { formato, hoyISO } from '../../lib/formato';
import { ICONO_TIPO } from '../../lib/visual';
import { avisar } from '../../stores/avisos.store';
import { useBorradorEquipo } from '../../stores/borrador-equipo.store';
import type { BorradorEquipo } from '../../stores/borrador-equipo.store';

const PASOS = ['Tipo de equipo', 'Datos del equipo', 'Revisar y guardar'];

const vacio: BorradorEquipo = {
  categoriaId: '',
  marca: '',
  modelo: '',
  numeroSerie: '',
  fechaIngreso: hoyISO(),
  ubicacion: '',
  observaciones: '',
  campanaId: '',
};

function Pasos({ actual }: { actual: number }) {
  return (
    <ol className="mb-8 grid gap-3 sm:grid-cols-3" aria-label="Pasos del registro">
      {PASOS.map((p, i) => {
        const n = i + 1;
        const hecho = n < actual;
        const activo = n === actual;
        return (
          <li
            key={p}
            aria-current={activo ? 'step' : undefined}
            className={clsx(
              'flex items-center gap-3 rounded-xl border-2 p-3',
              activo ? 'border-marca-600 bg-marca-50' : hecho ? 'border-marca-200 bg-white' : 'border-tierra-200 bg-white text-tinta-suave',
            )}
          >
            <span
              className={clsx(
                'flex size-10 shrink-0 items-center justify-center rounded-full text-lg font-bold',
                activo || hecho ? 'bg-marca-700 text-white' : 'bg-tierra-200',
              )}
            >
              {hecho ? <Check className="size-5" aria-hidden /> : n}
            </span>
            <span className="font-bold">
              <span className="sr-only">Paso {n}: </span>
              {p}
              {hecho && <span className="sr-only"> (completado)</span>}
            </span>
          </li>
        );
      })}
    </ol>
  );
}

export function EquipoNuevoPage() {
  const navigate = useNavigate();
  const borrador = useBorradorEquipo();
  const [paso, setPaso] = useState(borrador.paso);
  const { data: categorias, isLoading } = useCategorias();
  const { data: campanas } = useCampanas();
  const registrar = useRegistrarEquipo();
  const tituloPaso = useRef<HTMLHeadingElement>(null);

  const form = useFormulario<BorradorEquipo>(borrador.datos ?? vacio, {
    categoriaId: [v.requerido('Selecciona el tipo de equipo.')],
    marca: [v.requerido('Escribe la marca. Si no la conoces, escribe "Sin marca".'), v.maximo(80)],
    modelo: [v.requerido('Escribe el modelo. Si no lo conoces, escribe "Desconocido".'), v.maximo(120)],
    numeroSerie: [
      (valor, todos) => {
        const cat = categorias?.find((c) => String(c.id) === todos.categoriaId);
        return cat?.tipo === 'COMPUTO' && !String(valor).trim()
          ? 'Las computadoras requieren número de serie (suele estar en una etiqueta debajo del equipo).'
          : null;
      },
      v.maximo(120),
    ],
    fechaIngreso: [v.requerido('Indica la fecha de ingreso.'), v.fechaNoFutura()],
    ubicacion: [v.maximo(160)],
    observaciones: [v.maximo(1000)],
  });

  const categoria = categorias?.find((c) => String(c.id) === form.valores.categoriaId);
  const campanasCompatibles = useMemo(
    () =>
      (campanas ?? []).filter(
        (c) => c.estado !== 'PROGRAMADA' && c.categoriasAceptadas.some((cat) => String(cat.id) === form.valores.categoriaId),
      ),
    [campanas, form.valores.categoriaId],
  );

  // Guarda el borrador en cada cambio y, al cambiar de paso, lleva el foco al título.
  useEffect(() => {
    borrador.guardar(paso, form.valores);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [paso, form.valores]);
  const pasoAnterior = useRef(paso);
  useEffect(() => {
    if (pasoAnterior.current !== paso) tituloPaso.current?.focus();
    pasoAnterior.current = paso;
  }, [paso]);

  const avanzar = () => {
    const campos: (keyof BorradorEquipo)[][] = [['categoriaId'], ['marca', 'modelo', 'numeroSerie', 'fechaIngreso', 'ubicacion', 'observaciones']];
    if (form.validar(campos[paso - 1])) setPaso(paso + 1);
  };

  const guardar = () => {
    const d = form.valores;
    registrar.mutate(
      {
        categoriaId: Number(d.categoriaId),
        marca: d.marca.trim(),
        modelo: d.modelo.trim(),
        numeroSerie: d.numeroSerie.trim() || undefined,
        fechaIngreso: d.fechaIngreso,
        ubicacion: d.ubicacion.trim() || undefined,
        observaciones: d.observaciones.trim() || undefined,
        campanaId: d.campanaId ? Number(d.campanaId) : undefined,
      },
      {
        onSuccess: (equipo) => {
          borrador.limpiar();
          avisar.exito(`Equipo registrado con el folio ${equipo.folio}`, 'Anota el folio en una etiqueta y pégala en el equipo.');
          navigate(`/panel/equipos/${equipo.id}`);
        },
        onError: (e) => {
          const err = e as ApiError;
          avisar.error('No se pudo registrar el equipo', err.mensajes.join(' '));
          if (err.status === 409 || /serie/i.test(err.message)) {
            setPaso(2);
            form.marcarErrores({ numeroSerie: err.message });
          }
        },
      },
    );
  };

  if (isLoading) return <Cargando />;

  return (
    <>
      <EncabezadoPagina
        titulo="Registrar equipo"
        descripcion="Completa los tres pasos. Al guardar, el sistema asigna un folio único al equipo."
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Equipos', a: '/panel/equipos' }, { etiqueta: 'Registrar' }]}
      />
      <Pasos actual={paso} />

      <div className="tarjeta p-6 sm:p-8">
        <h2 ref={tituloPaso} tabIndex={-1} className="mb-6 text-2xl focus:outline-none">
          Paso {paso}. {PASOS[paso - 1]}
        </h2>

        {paso === 1 && (
          <div className="space-y-4 focus:outline-none" tabIndex={-1} ref={form.campo('categoriaId').ref}>
            <OpcionesTarjeta
              leyenda="¿Qué tipo de equipo vas a registrar?"
              valor={form.valores.categoriaId}
              onChange={(val) => form.cambiar('categoriaId', val)}
              error={form.errores.categoriaId}
              columnas={3}
              opciones={(categorias ?? []).map((c) => ({
                valor: String(c.id),
                etiqueta: c.nombre,
                icono: ICONO_TIPO[c.tipo],
              }))}
            />
            {categoria?.tipo === 'ENERGIA' && (
              <p className="flex items-start gap-2 rounded-xl bg-amber-50 p-4 text-amber-900">
                <Info className="mt-0.5 size-5 shrink-0" aria-hidden />
                Las baterías contienen materiales peligrosos: se registrarán directamente para gestión especializada.
              </p>
            )}
          </div>
        )}

        {paso === 2 && (
          <div className="grid gap-5 md:grid-cols-2">
            <CampoTexto etiqueta="Marca" obligatorio value={form.valores.marca} onChange={(e) => form.cambiar('marca', e.target.value)} {...form.campo('marca')} />
            <CampoTexto etiqueta="Modelo" obligatorio value={form.valores.modelo} onChange={(e) => form.cambiar('modelo', e.target.value)} {...form.campo('modelo')} />
            <CampoTexto
              etiqueta="Número de serie"
              obligatorio={categoria?.tipo === 'COMPUTO'}
              ayuda="Lo encuentras en una etiqueta en la parte de abajo o de atrás."
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
            <CampoTexto
              etiqueta="¿Dónde está guardado?"
              ayuda="Por ejemplo: Almacén edificio B."
              value={form.valores.ubicacion}
              onChange={(e) => form.cambiar('ubicacion', e.target.value)}
              {...form.campo('ubicacion')}
            />
            <CampoSelect
              etiqueta="¿Llegó en una campaña?"
              ayuda={campanasCompatibles.length ? 'Solo aparecen campañas que aceptan este tipo de equipo.' : 'No hay campañas activas para este tipo de equipo.'}
              value={form.valores.campanaId}
              onChange={(e) => form.cambiar('campanaId', e.target.value)}
              disabled={campanasCompatibles.length === 0}
            >
              <option value="">No, ingreso directo</option>
              {campanasCompatibles.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.nombre}
                </option>
              ))}
            </CampoSelect>
            <div className="md:col-span-2">
              <CampoArea
                etiqueta="Observaciones"
                ayuda="Estado general a simple vista: golpes, piezas faltantes, accesorios incluidos."
                value={form.valores.observaciones}
                onChange={(e) => form.cambiar('observaciones', e.target.value)}
                {...form.campo('observaciones')}
              />
            </div>
          </div>
        )}

        {paso === 3 && (
          <div className="space-y-6">
            <p className="text-lg">Revisa que los datos sean correctos antes de guardar.</p>
            <dl className="grid gap-5 rounded-2xl bg-tierra-50 p-6 sm:grid-cols-2">
              <Dato etiqueta="Tipo de equipo">{categoria?.nombre}</Dato>
              <Dato etiqueta="Marca y modelo">{`${form.valores.marca} ${form.valores.modelo}`}</Dato>
              <Dato etiqueta="Número de serie">{form.valores.numeroSerie || 'No registrado'}</Dato>
              <Dato etiqueta="Fecha de ingreso">{form.valores.fechaIngreso && formato.fecha(form.valores.fechaIngreso)}</Dato>
              <Dato etiqueta="Ubicación">{form.valores.ubicacion || 'No indicada'}</Dato>
              <Dato etiqueta="Campaña">
                {campanasCompatibles.find((c) => String(c.id) === form.valores.campanaId)?.nombre ?? 'Ingreso directo'}
              </Dato>
              {form.valores.observaciones && (
                <div className="sm:col-span-2">
                  <Dato etiqueta="Observaciones">{form.valores.observaciones}</Dato>
                </div>
              )}
            </dl>
            {(categoria?.tipo === 'COMPUTO' || categoria?.tipo === 'MOVIL') && (
              <p className="flex items-start gap-2 rounded-xl bg-sky-50 p-4 text-sky-900">
                <Info className="mt-0.5 size-5 shrink-0" aria-hidden />
                Este equipo puede contener información. Antes de reasignarlo o entregarlo se pedirá confirmar el borrado
                seguro de datos.
              </p>
            )}
          </div>
        )}

        <div className="mt-8 flex flex-col-reverse gap-3 border-t border-tierra-200 pt-6 sm:flex-row sm:justify-between">
          {paso > 1 ? (
            <Boton variante="secundario" onClick={() => setPaso(paso - 1)} icono={<ArrowLeft className="size-5" aria-hidden />}>
              Regresar
            </Boton>
          ) : (
            <Boton
              variante="fantasma"
              onClick={() => {
                borrador.limpiar();
                navigate('/panel/equipos');
              }}
            >
              Cancelar
            </Boton>
          )}
          {paso < 3 ? (
            <Boton onClick={avanzar}>
              Continuar <ArrowRight className="size-5" aria-hidden />
            </Boton>
          ) : (
            <Boton onClick={guardar} cargando={registrar.isPending} icono={<Check className="size-5" aria-hidden />}>
              Guardar equipo
            </Boton>
          )}
        </div>
      </div>
    </>
  );
}
