import { BatteryCharging, Brush, HardDrive, Plug, ShieldCheck, ThermometerSun, Wrench } from 'lucide-react';
import type { ReactNode } from 'react';
import {
  IlustracionCuidar,
  IlustracionEntregar,
  IlustracionReparar,
} from '../../components/ilustraciones/Ilustraciones';
import { BotonEnlace } from '../../components/ui/Boton';
import { EncabezadoPagina } from '../../components/ui/Pagina';

function Consejo({ icono, titulo, children }: { icono: ReactNode; titulo: string; children: ReactNode }) {
  return (
    <li className="flex gap-4 rounded-2xl bg-white p-5 shadow-sm">
      <span className="h-fit rounded-xl bg-marca-100 p-3 text-marca-800">{icono}</span>
      <div>
        <h3 className="text-xl">{titulo}</h3>
        <p className="mt-1 text-tinta-suave">{children}</p>
      </div>
    </li>
  );
}

const indice = [
  { id: 'cuidar', etiqueta: 'Cuida tus equipos' },
  { id: 'reparar', etiqueta: 'Repara y reutiliza' },
  { id: 'entregar', etiqueta: 'Entrega responsable' },
];

export function GuiaPage() {
  return (
    <div className="contenedor py-10">
      <EncabezadoPagina
        titulo="¿Qué hago con mi equipo?"
        descripcion="Una guía sencilla para alargar la vida de tus aparatos y, cuando ya no sirvan, entregarlos de forma segura."
        migas={[{ etiqueta: 'Inicio', a: '/' }, { etiqueta: 'Cuida tus equipos' }]}
      />

      <nav aria-label="Contenido de la guía" className="mb-10 rounded-2xl bg-white p-5 shadow-sm">
        <p className="mb-3 font-bold">En esta página:</p>
        <ol className="flex flex-col gap-2 sm:flex-row sm:gap-6">
          {indice.map((s, i) => (
            <li key={s.id}>
              <a href={`#${s.id}`} className="enlace text-lg">
                {i + 1}. {s.etiqueta}
              </a>
            </li>
          ))}
        </ol>
      </nav>

      <section id="cuidar" className="scroll-mt-28 space-y-6 py-6">
        <div className="flex items-center gap-4">
          <IlustracionCuidar className="h-20 shrink-0" />
          <h2 className="text-3xl">1. Cuida tus equipos para que duren más</h2>
        </div>
        <ul className="grid gap-4 md:grid-cols-2">
          <Consejo icono={<Brush className="size-6" aria-hidden />} titulo="Límpialos con regularidad">
            El polvo tapa los ventiladores y calienta el equipo. Limpia rejillas y teclado con una brocha seca.
          </Consejo>
          <Consejo icono={<BatteryCharging className="size-6" aria-hidden />} titulo="Carga la batería con calma">
            Evita dejarla siempre al 100 % o descargarla por completo. Desconecta el cargador cuando no lo necesites.
          </Consejo>
          <Consejo icono={<ThermometerSun className="size-6" aria-hidden />} titulo="Protégelos del calor y la humedad">
            No los dejes al sol ni dentro del coche. Usa la laptop sobre superficies firmes, no sobre la cama.
          </Consejo>
          <Consejo icono={<Plug className="size-6" aria-hidden />} titulo="Usa un regulador o no-break">
            Los picos de voltaje dañan fuentes y tarjetas. Un regulador es más barato que una reparación.
          </Consejo>
          <Consejo icono={<HardDrive className="size-6" aria-hidden />} titulo="Mantén el sistema al día">
            Actualizar y liberar espacio hace que un equipo "lento" vuelva a ser útil sin cambiarlo.
          </Consejo>
          <Consejo icono={<ShieldCheck className="size-6" aria-hidden />} titulo="Usa fundas y protectores">
            Una funda y un protector de pantalla evitan la mayoría de los daños por caídas.
          </Consejo>
        </ul>
      </section>

      <section id="reparar" className="scroll-mt-28 space-y-6 py-10">
        <div className="flex items-center gap-4">
          <IlustracionReparar className="h-20 shrink-0" />
          <h2 className="text-3xl">2. Antes de desecharlo: repáralo o reutilízalo</h2>
        </div>
        <div className="grid gap-6 lg:grid-cols-2">
          <article className="tarjeta space-y-3 p-6">
            <h3 className="flex items-center gap-2 text-2xl">
              <Wrench className="size-6 text-marca-700" aria-hidden /> ¿Vale la pena repararlo?
            </h3>
            <p className="text-lg">
              En la institución aplicamos una regla clara: si repararlo cuesta <strong>menos de la mitad</strong> de lo
              que costaría uno nuevo, se repara. Muchas fallas comunes (batería, cargador, disco, pantalla) tienen
              arreglo.
            </p>
          </article>
          <article className="tarjeta space-y-3 p-6">
            <h3 className="text-2xl">Dale una segunda vida</h3>
            <p className="text-lg">
              Un equipo que ya no te sirve puede ser útil en un laboratorio, una biblioteca o una escuela comunitaria.
              En E-Waste 360° registramos cada reasignación con un documento de respaldo.
            </p>
          </article>
        </div>
      </section>

      <section id="entregar" className="scroll-mt-28 space-y-6 py-6">
        <div className="flex items-center gap-4">
          <IlustracionEntregar className="h-20 shrink-0" />
          <h2 className="text-3xl">3. Entrégalo de forma responsable</h2>
        </div>
        <div className="grid gap-6 lg:grid-cols-[1fr_22rem]">
          <ol className="space-y-4">
            {[
              ['Respalda tu información', 'Guarda tus fotos y documentos en otro lugar.'],
              ['Borra tus datos', 'Restablece el equipo a valores de fábrica o formatea el disco.'],
              ['Retira accesorios personales', 'Tarjeta SIM, memorias, discos externos.'],
              ['Protege las baterías', 'Cubre las terminales con cinta y no las perfores ni golpees.'],
              ['Entrégalo a un gestor verificado', 'Nunca lo tires con la basura común ni lo quemes.'],
            ].map(([titulo, texto], i) => (
              <li key={titulo} className="flex gap-4 rounded-2xl bg-white p-5 shadow-sm">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-marca-700 text-xl font-bold text-white">
                  {i + 1}
                </span>
                <div>
                  <h3 className="text-xl">{titulo}</h3>
                  <p className="text-tinta-suave">{texto}</p>
                </div>
              </li>
            ))}
          </ol>
          <aside className="h-fit space-y-4 rounded-2xl bg-marca-800 p-6 text-white">
            <h3 className="text-2xl text-white">¿Qué es un gestor verificado?</h3>
            <p className="text-marca-100">
              Es una empresa o centro de acopio que cuenta con autorización para manejar residuos electrónicos. En
              nuestra plataforma solo registramos entregas a gestores cuya autorización fue revisada y documentada.
            </p>
            <BotonEnlace to="/campanas" variante="secundario">
              Ver campañas de recolección
            </BotonEnlace>
          </aside>
        </div>
      </section>
    </div>
  );
}
