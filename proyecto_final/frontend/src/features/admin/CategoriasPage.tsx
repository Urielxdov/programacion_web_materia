import { Pencil, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { ApiError } from '../../api/cliente';
import { useCategorias, useGuardarCategoria } from '../../api/hooks';
import type { Categoria, TipoCategoria } from '../../api/tipos';
import { Boton } from '../../components/ui/Boton';
import { CampoArea, CampoSelect, CampoTexto } from '../../components/ui/Campo';
import { Cargando, ErrorCarga } from '../../components/ui/Estados';
import { Modal } from '../../components/ui/Modal';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { formato, TIPO_CATEGORIA } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';
import { ICONO_TIPO } from '../../lib/visual';

function FormularioCategoria({ categoria, alCerrar }: { categoria?: Categoria; alCerrar: () => void }) {
  const guardar = useGuardarCategoria();
  const form = useFormulario(
    {
      nombre: categoria?.nombre ?? '',
      tipo: (categoria?.tipo ?? '') as TipoCategoria | '',
      pesoPromedioKg: categoria ? String(categoria.pesoPromedioKg) : '',
      valorReposicion: categoria ? String(categoria.valorReposicion) : '',
      descripcion: categoria?.descripcion ?? '',
    },
    {
      nombre: [v.requerido('Escribe el nombre.'), v.minimo(2), v.maximo(80)],
      tipo: [v.requerido('Selecciona el tipo.')],
      pesoPromedioKg: [v.numeroMin(0, 'Escribe el peso promedio en kg.')],
      valorReposicion: [v.numeroMin(0, 'Escribe el valor de reposición.')],
    },
  );
  return (
    <form
      noValidate
      className="space-y-5"
      onSubmit={form.alEnviar((d) =>
        guardar.mutate(
          {
            id: categoria?.id,
            nombre: d.nombre.trim(),
            tipo: d.tipo as TipoCategoria,
            pesoPromedioKg: Number(d.pesoPromedioKg),
            valorReposicion: Number(d.valorReposicion),
            descripcion: d.descripcion.trim() || undefined,
          },
          {
            onSuccess: () => {
              avisar.exito('Categoría guardada');
              alCerrar();
            },
            onError: (e) => avisar.error('No se pudo guardar', (e as ApiError).mensajes.join(' ')),
          },
        ),
      )}
    >
      <CampoTexto etiqueta="Nombre" obligatorio value={form.valores.nombre} onChange={(e) => form.cambiar('nombre', e.target.value)} {...form.campo('nombre')} />
      <CampoSelect
        etiqueta="Tipo"
        obligatorio
        ayuda="Define las reglas de registro: p. ej. cómputo exige número de serie y energía va a gestión especializada."
        value={form.valores.tipo}
        onChange={(e) => form.cambiar('tipo', e.target.value as TipoCategoria)}
        {...form.campo('tipo')}
      >
        <option value="">Selecciona…</option>
        {(Object.keys(TIPO_CATEGORIA) as TipoCategoria[]).map((t) => (
          <option key={t} value={t}>
            {TIPO_CATEGORIA[t]}
          </option>
        ))}
      </CampoSelect>
      <div className="grid gap-5 sm:grid-cols-2">
        <CampoTexto etiqueta="Peso promedio (kg)" obligatorio type="number" min={0} step="0.01" value={form.valores.pesoPromedioKg} onChange={(e) => form.cambiar('pesoPromedioKg', e.target.value)} {...form.campo('pesoPromedioKg')} />
        <CampoTexto
          etiqueta="Valor de reposición (MXN)"
          obligatorio
          ayuda="Costo de un equipo nuevo equivalente."
          type="number"
          min={0}
          step="0.01"
          value={form.valores.valorReposicion}
          onChange={(e) => form.cambiar('valorReposicion', e.target.value)}
          {...form.campo('valorReposicion')}
        />
      </div>
      <CampoArea etiqueta="Descripción" rows={2} value={form.valores.descripcion} onChange={(e) => form.cambiar('descripcion', e.target.value)} {...form.campo('descripcion')} />
      <div className="flex flex-col-reverse gap-3 border-t border-tierra-200 pt-5 sm:flex-row sm:justify-end">
        <Boton variante="secundario" onClick={alCerrar}>
          Cancelar
        </Boton>
        <Boton type="submit" cargando={guardar.isPending}>
          Guardar
        </Boton>
      </div>
    </form>
  );
}

export function CategoriasPage() {
  const { data, isLoading, error, refetch } = useCategorias();
  const [editando, setEditando] = useState<Categoria | 'nueva' | null>(null);
  return (
    <>
      <EncabezadoPagina
        titulo="Categorías de equipos"
        descripcion="Catálogo de tipos de equipo. El peso y el valor de reposición se usan para calcular los indicadores."
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Categorías' }]}
        acciones={
          <Boton onClick={() => setEditando('nueva')} icono={<PlusCircle className="size-5" aria-hidden />}>
            Nueva categoría
          </Boton>
        }
      />
      {isLoading && <Cargando />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      {data && (
        <div className="tarjeta overflow-x-auto">
          <table className="w-full min-w-[40rem] text-left">
            <thead className="border-b-2 border-tierra-200 bg-tierra-50 text-sm uppercase text-tinta-suave">
              <tr>
                <th scope="col" className="px-5 py-3">Nombre</th>
                <th scope="col" className="px-5 py-3">Tipo</th>
                <th scope="col" className="px-5 py-3 text-right">Peso</th>
                <th scope="col" className="px-5 py-3 text-right">Valor de reposición</th>
                <th scope="col" className="px-5 py-3"><span className="sr-only">Acciones</span></th>
              </tr>
            </thead>
            <tbody>
              {data.map((c) => (
                <tr key={c.id} className="border-b border-tierra-100 last:border-0">
                  <th scope="row" className="px-5 py-3 font-bold">{c.nombre}</th>
                  <td className="px-5 py-3">
                    <span className="flex items-center gap-2">
                      {ICONO_TIPO[c.tipo]}
                      {TIPO_CATEGORIA[c.tipo]}
                    </span>
                  </td>
                  <td className="px-5 py-3 text-right">{formato.kg(c.pesoPromedioKg)}</td>
                  <td className="px-5 py-3 text-right">{formato.moneda(c.valorReposicion)}</td>
                  <td className="px-5 py-3 text-right">
                    <Boton variante="fantasma" onClick={() => setEditando(c)} icono={<Pencil className="size-5" aria-hidden />}>
                      Editar<span className="sr-only"> {c.nombre}</span>
                    </Boton>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {editando && (
        <Modal abierto alCerrar={() => setEditando(null)} titulo={editando === 'nueva' ? 'Nueva categoría' : `Editar ${editando.nombre}`}>
          <FormularioCategoria categoria={editando === 'nueva' ? undefined : editando} alCerrar={() => setEditando(null)} />
        </Modal>
      )}
    </>
  );
}
