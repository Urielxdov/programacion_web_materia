import { ArrowRight, BatteryWarning, Droplets, HeartHandshake, Leaf, Recycle, Wrench } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useCampanas, useIndicadoresPublicos } from '../../api/hooks';
import {
  IlustracionCircular,
  IlustracionCuidar,
  IlustracionEntregar,
  IlustracionReparar,
} from '../../components/ilustraciones/Ilustraciones';
import { BotonEnlace } from '../../components/ui/Boton';
import { Indicador } from '../../components/ui/Pagina';
import { useTituloPagina } from '../../hooks/dom';
import { formato } from '../../lib/formato';
import { TarjetaCampana } from './CampanasPublicasPage';

const impactos = [
  {
    icono: <Droplets className="size-7" aria-hidden />,
    titulo: 'Contaminan el agua y el suelo',
    texto: 'Plomo, mercurio y cadmio de pantallas, tarjetas y baterías pueden filtrarse cuando terminan en un tiradero.',
  },
  {
    icono: <BatteryWarning className="size-7" aria-hidden />,
    titulo: 'Riesgo de incendios',
    texto: 'Las baterías de litio dañadas pueden calentarse e incendiarse si se tiran con la basura común.',
  },
  {
    icono: <HeartHandshake className="size-7" aria-hidden />,
    titulo: 'Afectan a las personas',
    texto: 'Quemar cables o desarmar equipos sin protección daña la salud de quienes recuperan materiales de forma informal.',
  },
  {
    icono: <Leaf className="size-7" aria-hidden />,
    titulo: 'Se desperdician recursos',
    texto: 'Un equipo contiene metales valiosos que se pierden si no se reparan, reutilizan o reciclan correctamente.',
  },
];

const caminos = [
  {
    ilustracion: <IlustracionCuidar className="h-24" />,
    titulo: '1. Cuídalo',
    texto: 'Un mantenimiento sencillo alarga años la vida de tus equipos.',
    a: '/guia#cuidar',
  },
  {
    ilustracion: <IlustracionReparar className="h-24" />,
    titulo: '2. Repáralo o reutilízalo',
    texto: 'Si aún sirve, otra persona o área puede aprovecharlo.',
    a: '/guia#reparar',
  },
  {
    ilustracion: <IlustracionEntregar className="h-24" />,
    titulo: '3. Entrégalo bien',
    texto: 'Cuando ya no tiene arreglo, llévalo a un gestor verificado.',
    a: '/guia#entregar',
  },
];

export function InicioPage() {
  useTituloPagina('Inicio');
  const { data: cifras } = useIndicadoresPublicos();
  const { data: campanas } = useCampanas();
  const vigentes = (campanas ?? []).filter((c) => c.estado !== 'CERRADA').slice(0, 2);

  return (
    <>
      {/* Presentación */}
      <section className="bg-gradient-to-b from-white to-tierra-50">
        <div className="contenedor grid items-center gap-10 py-14 lg:grid-cols-2 lg:py-20">
          <div className="animar-aparecer space-y-6">
            <p className="inline-flex items-center gap-2 rounded-full bg-marca-100 px-4 py-1.5 font-bold text-marca-800">
              <Recycle className="size-5" aria-hidden /> Economía circular en nuestra institución
            </p>
            <h1 className="text-4xl sm:text-5xl">
              Que ningún equipo se convierta en basura <span className="text-marca-700">antes de tiempo</span>
            </h1>
            <p className="text-xl text-tinta-suave">
              E-Waste 360° registra las computadoras, celulares, monitores y demás aparatos que ya no se usan, revisa si
              pueden repararse o reutilizarse y documenta su destino final de forma responsable.
            </p>
            <div className="flex flex-col gap-3 sm:flex-row">
              <BotonEnlace to="/campanas" tamano="grande" icono={<Recycle className="size-6" aria-hidden />}>
                Ver campañas de recolección
              </BotonEnlace>
              <BotonEnlace to="/guia" tamano="grande" variante="secundario">
                ¿Qué hago con mi equipo?
              </BotonEnlace>
            </div>
          </div>
          <IlustracionCircular className="mx-auto w-full max-w-md" />
        </div>
      </section>

      {/* Cifras */}
      {cifras && (
        <section aria-labelledby="titulo-cifras" className="contenedor py-12">
          <h2 id="titulo-cifras" className="mb-6 text-3xl">
            Lo que llevamos logrado
          </h2>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Indicador titulo="Equipos registrados" valor={cifras.equiposRegistrados} icono={<Recycle className="size-6" aria-hidden />} />
            <Indicador
              titulo="Equipos recuperados"
              valor={cifras.equiposRecuperados}
              detalle="Reparados o reasignados a otra área"
              icono={<Wrench className="size-6" aria-hidden />}
            />
            <Indicador
              titulo="Residuo evitado"
              valor={cifras.kgResiduoEvitado}
              formatear={formato.kg}
              detalle="Peso estimado de equipos que siguen en uso"
              icono={<Leaf className="size-6" aria-hidden />}
            />
            <Indicador
              titulo="Participaciones en campañas"
              valor={cifras.participaciones}
              detalle={`${cifras.campanasActivas} campaña(s) activa(s)`}
              icono={<HeartHandshake className="size-6" aria-hidden />}
              tono="cielo"
            />
          </div>
        </section>
      )}

      {/* Problema e impactos */}
      <section className="bg-white py-14" aria-labelledby="titulo-problema">
        <div className="contenedor space-y-8">
          <div className="max-w-3xl space-y-3">
            <h2 id="titulo-problema" className="text-3xl">
              ¿Por qué importan los residuos electrónicos?
            </h2>
            <p className="text-lg text-tinta-suave">
              Los residuos de aparatos eléctricos y electrónicos (RAEE) son de los que más crecen en el mundo. Bien
              manejados son una fuente de materiales; mal manejados, un riesgo para la salud y el ambiente.
            </p>
          </div>
          <div className="grid gap-5 md:grid-cols-2">
            {impactos.map((i) => (
              <article key={i.titulo} className="flex gap-4 rounded-2xl bg-tierra-50 p-6">
                <span className="h-fit rounded-xl bg-marca-100 p-3 text-marca-800">{i.icono}</span>
                <div>
                  <h3 className="text-xl">{i.titulo}</h3>
                  <p className="mt-1 text-tinta-suave">{i.texto}</p>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* Qué hacer */}
      <section className="contenedor py-14" aria-labelledby="titulo-caminos">
        <h2 id="titulo-caminos" className="mb-8 text-3xl">
          Tres pasos para un consumo responsable
        </h2>
        <div className="grid gap-6 md:grid-cols-3">
          {caminos.map((c) => (
            <Link key={c.titulo} to={c.a} className="tarjeta group flex flex-col gap-3 p-6 transition-shadow hover:shadow-md">
              {c.ilustracion}
              <h3 className="text-2xl">{c.titulo}</h3>
              <p className="flex-1 text-tinta-suave">{c.texto}</p>
              <span className="inline-flex items-center gap-2 font-bold text-marca-700">
                Leer más <ArrowRight className="size-5 transition-transform group-hover:translate-x-1" aria-hidden />
              </span>
            </Link>
          ))}
        </div>
      </section>

      {/* Campañas */}
      {vigentes.length > 0 && (
        <section className="bg-marca-50 py-14" aria-labelledby="titulo-campanas">
          <div className="contenedor space-y-8">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <h2 id="titulo-campanas" className="text-3xl">
                Campañas de recolección
              </h2>
              <Link to="/campanas" className="enlace text-lg">
                Ver todas las campañas
              </Link>
            </div>
            <div className="grid gap-6 md:grid-cols-2">
              {vigentes.map((c) => (
                <TarjetaCampana key={c.id} campana={c} />
              ))}
            </div>
          </div>
        </section>
      )}
    </>
  );
}
