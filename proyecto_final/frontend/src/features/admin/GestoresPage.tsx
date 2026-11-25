import { BadgeCheck, MapPin, Pencil, Phone, PlusCircle, ShieldAlert, ShieldOff } from 'lucide-react';
import { useState } from 'react';
import { ApiError } from '../../api/cliente';
import { useGestores, useGuardarGestor, useSesion, useVerificarGestor } from '../../api/hooks';
import type { Gestor } from '../../api/tipos';
import { Boton } from '../../components/ui/Boton';
import { CampoTexto } from '../../components/ui/Campo';
import { Cargando, ErrorCarga, Vacio } from '../../components/ui/Estados';
import { Insignia } from '../../components/ui/Insignia';
import { Confirmar, Modal } from '../../components/ui/Modal';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { formato } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';

const fallo = (titulo: string) => (e: unknown) => avisar.error(titulo, (e as ApiError).mensajes.join(' '));

function FormularioGestor({ gestor, alCerrar }: { gestor?: Gestor; alCerrar: () => void }) {
  const guardar = useGuardarGestor();
  const form = useFormulario(
    { nombre: gestor?.nombre ?? '', tipo: gestor?.tipo ?? '', contacto: gestor?.contacto ?? '', direccion: gestor?.direccion ?? '' },
    {
      nombre: [v.requerido('Escribe el nombre.'), v.minimo(3)],
      tipo: [v.requerido('Indica el tipo, por ejemplo: Reciclador o Centro de acopio.'), v.minimo(3)],
    },
  );
  return (
    <form
      noValidate
      className="space-y-5"
      onSubmit={form.alEnviar((d) =>
        guardar.mutate(
          { id: gestor?.id, nombre: d.nombre.trim(), tipo: d.tipo.trim(), contacto: d.contacto.trim() || undefined, direccion: d.direccion.trim() || undefined },
          {
            onSuccess: () => {
              avisar.exito('Gestor guardado');
              alCerrar();
            },
            onError: fallo('No se pudo guardar el gestor'),
          },
        ),
      )}
    >
      <CampoTexto etiqueta="Nombre" obligatorio value={form.valores.nombre} onChange={(e) => form.cambiar('nombre', e.target.value)} {...form.campo('nombre')} />
      <CampoTexto etiqueta="Tipo de gestor" obligatorio value={form.valores.tipo} onChange={(e) => form.cambiar('tipo', e.target.value)} {...form.campo('tipo')} />
      <CampoTexto etiqueta="Contacto" value={form.valores.contacto} onChange={(e) => form.cambiar('contacto', e.target.value)} {...form.campo('contacto')} />
      <CampoTexto etiqueta="Dirección" value={form.valores.direccion} onChange={(e) => form.cambiar('direccion', e.target.value)} {...form.campo('direccion')} />
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

function FormularioVerificar({ gestor, alCerrar }: { gestor: Gestor; alCerrar: () => void }) {
  const verificar = useVerificarGestor();
  const form = useFormulario({ documento: '' }, { documento: [v.requerido('Escribe el número de autorización o documento.'), v.minimo(3)] });
  return (
    <form
      noValidate
      className="space-y-5"
      onSubmit={form.alEnviar((d) =>
        verificar.mutate(
          { id: gestor.id, documento: d.documento.trim() },
          {
            onSuccess: () => {
              avisar.exito('Gestor verificado', 'Ya puede recibir equipos.');
              alCerrar();
            },
            onError: fallo('No se pudo verificar'),
          },
        ),
      )}
    >
      <p>
        Verifica solo si revisaste la autorización vigente de <strong>{gestor.nombre}</strong> para manejar residuos
        electrónicos.
      </p>
      <CampoTexto
        etiqueta="Número de autorización o documento"
        ayuda="Por ejemplo, la autorización ambiental o el convenio firmado."
        obligatorio
        value={form.valores.documento}
        onChange={(e) => form.cambiar('documento', e.target.value)}
        {...form.campo('documento')}
      />
      <div className="flex flex-col-reverse gap-3 border-t border-tierra-200 pt-5 sm:flex-row sm:justify-end">
        <Boton variante="secundario" onClick={alCerrar}>
          Cancelar
        </Boton>
        <Boton type="submit" cargando={verificar.isPending} icono={<BadgeCheck className="size-5" aria-hidden />}>
          Marcar como verificado
        </Boton>
      </div>
    </form>
  );
}

export function GestoresPage() {
  const { data: yo } = useSesion();
  const esAdmin = yo?.rol === 'ADMIN';
  const { data, isLoading, error, refetch } = useGestores();
  const revocar = useVerificarGestor();
  const [modal, setModal] = useState<{ tipo: 'editar' | 'verificar' | 'revocar'; gestor?: Gestor } | null>(null);
  const cerrar = () => setModal(null);

  return (
    <>
      <EncabezadoPagina
        titulo="Gestores de residuos"
        descripcion="Empresas y centros que reciben equipos sin posibilidad de reutilización. Solo los gestores verificados pueden recibir entregas."
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Gestores' }]}
        acciones={
          <Boton onClick={() => setModal({ tipo: 'editar' })} icono={<PlusCircle className="size-5" aria-hidden />}>
            Nuevo gestor
          </Boton>
        }
      />
      {isLoading && <Cargando />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      {data?.length === 0 && <Vacio titulo="No hay gestores registrados" />}
      <ul className="grid gap-4 md:grid-cols-2">
        {data?.map((g) => (
          <li key={g.id} className="tarjeta flex flex-col gap-3 p-5">
            <div className="flex flex-wrap items-start justify-between gap-2">
              <div>
                <p className="text-lg font-bold">{g.nombre}</p>
                <p className="text-tinta-suave">{g.tipo}</p>
              </div>
              {g.verificado ? (
                <Insignia tono="exito" icono={<BadgeCheck className="size-4" aria-hidden />}>
                  Verificado
                </Insignia>
              ) : (
                <Insignia tono="aviso" icono={<ShieldAlert className="size-4" aria-hidden />}>
                  Sin verificar
                </Insignia>
              )}
            </div>
            <ul className="space-y-1 text-tinta-suave">
              {g.contacto && (
                <li className="flex items-center gap-2">
                  <Phone className="size-4" aria-hidden /> {g.contacto}
                </li>
              )}
              {g.direccion && (
                <li className="flex items-center gap-2">
                  <MapPin className="size-4" aria-hidden /> {g.direccion}
                </li>
              )}
            </ul>
            {g.verificado && (
              <p className="rounded-lg bg-marca-50 p-3 text-sm text-marca-900">
                Autorización: <strong>{g.documentoVerificacion}</strong>
                {g.fechaVerificacion && ` · verificado el ${formato.fechaCorta(g.fechaVerificacion)}`}
                {g.verificadoPor && ` por ${g.verificadoPor.nombre}`}
              </p>
            )}
            <div className="mt-auto flex flex-wrap gap-2 pt-2">
              <Boton variante="fantasma" onClick={() => setModal({ tipo: 'editar', gestor: g })} icono={<Pencil className="size-5" aria-hidden />}>
                Editar
              </Boton>
              {esAdmin &&
                (g.verificado ? (
                  <Boton variante="fantasma" onClick={() => setModal({ tipo: 'revocar', gestor: g })} icono={<ShieldOff className="size-5" aria-hidden />}>
                    Revocar verificación
                  </Boton>
                ) : (
                  <Boton variante="secundario" onClick={() => setModal({ tipo: 'verificar', gestor: g })} icono={<BadgeCheck className="size-5" aria-hidden />}>
                    Verificar
                  </Boton>
                ))}
            </div>
          </li>
        ))}
      </ul>
      {!esAdmin && <p className="mt-6 text-tinta-suave">Solo el administrador puede verificar o revocar gestores.</p>}

      {modal?.tipo === 'editar' && (
        <Modal abierto alCerrar={cerrar} titulo={modal.gestor ? `Editar ${modal.gestor.nombre}` : 'Nuevo gestor'}>
          <FormularioGestor gestor={modal.gestor} alCerrar={cerrar} />
        </Modal>
      )}
      {modal?.tipo === 'verificar' && modal.gestor && (
        <Modal abierto alCerrar={cerrar} titulo="Verificar gestor">
          <FormularioVerificar gestor={modal.gestor} alCerrar={cerrar} />
        </Modal>
      )}
      <Confirmar
        abierto={modal?.tipo === 'revocar'}
        alCerrar={cerrar}
        peligro
        titulo="¿Revocar la verificación?"
        mensaje={<p>Ya no se podrán registrar entregas a <strong>{modal?.gestor?.nombre}</strong> hasta verificarlo de nuevo.</p>}
        textoConfirmar="Sí, revocar"
        cargando={revocar.isPending}
        alConfirmar={() =>
          modal?.gestor &&
          revocar.mutate(
            { id: modal.gestor.id, revocar: true },
            {
              onSuccess: () => {
                avisar.info('Verificación revocada');
                cerrar();
              },
              onError: fallo('No se pudo revocar'),
            },
          )
        }
      />
    </>
  );
}
