import { CalendarDays, CheckCircle2, Clock, MapPin, PackageCheck, ShieldCheck, Users } from 'lucide-react';
import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { ApiError } from '../../api/cliente';
import { useCampana, useParticipar } from '../../api/hooks';
import { Boton, BotonEnlace } from '../../components/ui/Boton';
import { CampoArea, CampoTexto } from '../../components/ui/Campo';
import { Cargando, ErrorCarga } from '../../components/ui/Estados';
import { InsigniaCampana } from '../../components/ui/Insignia';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { formato } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';

const recomendaciones = [
  'Respalda tus fotos y documentos antes de entregar tu equipo.',
  'Borra tu información: restablece celulares y tabletas a valores de fábrica.',
  'Retira la tarjeta SIM y la memoria microSD.',
  'Cubre las terminales de las baterías con cinta aislante.',
  'No desarmes los equipos: así se recuperan mejor sus materiales.',
];

function FormularioParticipacion({ campanaId }: { campanaId: number }) {
  const participar = useParticipar(campanaId);
  const [confirmacion, setConfirmacion] = useState<{ equipos: number } | null>(null);
  const form = useFormulario(
    { nombreParticipante: '', contacto: '', equiposDeclarados: '1', comentario: '' },
    {
      nombreParticipante: [v.requerido('Escribe tu nombre.'), v.minimo(2)],
      equiposDeclarados: [v.numeroMin(1, 'Indica cuántos equipos llevarás (al menos 1).')],
      contacto: [v.maximo(160)],
      comentario: [v.maximo(500)],
    },
  );

  if (confirmacion) {
    return (
      <div role="status" className="animar-aparecer flex flex-col items-center gap-3 rounded-2xl bg-marca-50 p-8 text-center">
        <CheckCircle2 className="size-14 text-marca-700" aria-hidden />
        <p className="text-2xl font-bold">¡Gracias por participar!</p>
        <p className="text-lg text-tinta-suave">
          Registramos que llevarás {confirmacion.equipos} equipo(s). Recuerda revisar las recomendaciones antes de
          entregarlos.
        </p>
        <Boton variante="secundario" onClick={() => setConfirmacion(null)}>
          Registrar otra participación
        </Boton>
      </div>
    );
  }

  return (
    <form
      noValidate
      className="space-y-5"
      onSubmit={form.alEnviar((valores) =>
        participar.mutate(
          {
            nombreParticipante: valores.nombreParticipante.trim(),
            contacto: valores.contacto.trim() || undefined,
            equiposDeclarados: Number(valores.equiposDeclarados),
            comentario: valores.comentario.trim() || undefined,
          },
          {
            onSuccess: (r) => {
              setConfirmacion({ equipos: r.equiposDeclarados });
              avisar.exito('Participación registrada', `Te esperamos en "${r.campana}".`);
            },
            onError: (e) => avisar.error('No se pudo registrar tu participación', (e as ApiError).message),
          },
        ),
      )}
    >
      <CampoTexto
        etiqueta="Tu nombre"
        obligatorio
        autoComplete="name"
        value={form.valores.nombreParticipante}
        onChange={(e) => form.cambiar('nombreParticipante', e.target.value)}
        {...form.campo('nombreParticipante')}
      />
      <CampoTexto
        etiqueta="Correo o teléfono"
        ayuda="Solo para avisarte si cambia la fecha o el lugar."
        autoComplete="email"
        value={form.valores.contacto}
        onChange={(e) => form.cambiar('contacto', e.target.value)}
        {...form.campo('contacto')}
      />
      <CampoTexto
        etiqueta="¿Cuántos equipos llevarás?"
        obligatorio
        type="number"
        inputMode="numeric"
        min={1}
        max={500}
        value={form.valores.equiposDeclarados}
        onChange={(e) => form.cambiar('equiposDeclarados', e.target.value)}
        {...form.campo('equiposDeclarados')}
      />
      <CampoArea
        etiqueta="Comentarios"
        ayuda="Por ejemplo: qué tipo de equipos son."
        rows={3}
        value={form.valores.comentario}
        onChange={(e) => form.cambiar('comentario', e.target.value)}
        {...form.campo('comentario')}
      />
      <Boton type="submit" tamano="grande" className="w-full" cargando={participar.isPending}>
        Registrar mi participación
      </Boton>
      <p className="flex items-start gap-2 text-sm text-tinta-suave">
        <ShieldCheck className="mt-0.5 size-5 shrink-0" aria-hidden />
        Tus datos solo se usan para organizar esta campaña.
      </p>
    </form>
  );
}

export function CampanaPublicaPage() {
  const id = Number(useParams().id);
  const { data: c, isLoading, error, refetch } = useCampana(id);

  if (isLoading) return <Cargando />;
  if (error || !c)
    return (
      <div className="contenedor py-10">
        <ErrorCarga error={error} reintentar={() => refetch()} />
      </div>
    );

  return (
    <div className="contenedor py-10">
      <EncabezadoPagina
        titulo={c.nombre}
        migas={[{ etiqueta: 'Inicio', a: '/' }, { etiqueta: 'Campañas', a: '/campanas' }, { etiqueta: c.nombre }]}
      />
      <div className="grid gap-8 lg:grid-cols-[1fr_24rem]">
        <div className="space-y-8">
          <section className="tarjeta space-y-5 p-6">
            <InsigniaCampana estado={c.estado} />
            <p className="text-lg">{c.descripcion}</p>
            <ul className="grid gap-3 sm:grid-cols-2">
              <li className="flex items-start gap-3">
                <CalendarDays className="mt-1 size-6 shrink-0 text-marca-700" aria-hidden />
                <span>
                  <strong className="block">Fechas</strong>
                  {formato.fecha(c.fechaInicio)} al {formato.fecha(c.fechaFin)}
                </span>
              </li>
              {c.horario && (
                <li className="flex items-start gap-3">
                  <Clock className="mt-1 size-6 shrink-0 text-marca-700" aria-hidden />
                  <span>
                    <strong className="block">Horario</strong>
                    {c.horario}
                  </span>
                </li>
              )}
              <li className="flex items-start gap-3">
                <MapPin className="mt-1 size-6 shrink-0 text-marca-700" aria-hidden />
                <span>
                  <strong className="block">Lugar</strong>
                  {c.ubicacion}
                </span>
              </li>
              <li className="flex items-start gap-3">
                <Users className="mt-1 size-6 shrink-0 text-marca-700" aria-hidden />
                <span>
                  <strong className="block">Participación</strong>
                  {c.totalParticipaciones} personas · {c.equiposRecibidosTotal} equipos recibidos
                </span>
              </li>
            </ul>
          </section>

          <section className="space-y-4">
            <h2 className="text-2xl">Equipos que se reciben</h2>
            <ul className="flex flex-wrap gap-2">
              {c.categoriasAceptadas.map((cat) => (
                <li key={cat.id} className="flex items-center gap-2 rounded-full bg-marca-100 px-4 py-2 font-bold text-marca-900">
                  <PackageCheck className="size-5" aria-hidden />
                  {cat.nombre}
                </li>
              ))}
            </ul>
          </section>

          <section className="space-y-4 rounded-2xl border-2 border-amber-300 bg-amber-50 p-6">
            <h2 className="text-2xl">Antes de entregar tus equipos</h2>
            <ol className="list-decimal space-y-2 pl-6 text-lg marker:font-bold">
              {recomendaciones.map((r) => (
                <li key={r}>{r}</li>
              ))}
            </ol>
          </section>
        </div>

        <aside className="tarjeta h-fit space-y-4 p-6 lg:sticky lg:top-28">
          {c.estado === 'CERRADA' ? (
            <>
              <h2 className="text-2xl">Campaña concluida</h2>
              <p className="text-tinta-suave">
                Gracias a las {c.totalParticipaciones} personas que participaron. Se recibieron {c.equiposRecibidosTotal}{' '}
                equipos.
              </p>
              <BotonEnlace to="/campanas" variante="secundario">
                Ver otras campañas
              </BotonEnlace>
            </>
          ) : (
            <>
              <h2 className="text-2xl">Quiero participar</h2>
              <FormularioParticipacion campanaId={c.id} />
            </>
          )}
        </aside>
      </div>
    </div>
  );
}
