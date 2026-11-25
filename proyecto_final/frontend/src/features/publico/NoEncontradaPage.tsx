import { Compass } from 'lucide-react';
import { BotonEnlace } from '../../components/ui/Boton';
import { useTituloPagina } from '../../hooks/dom';

export function NoEncontradaPage() {
  useTituloPagina('Página no encontrada');
  return (
    <div className="contenedor flex flex-col items-center gap-5 py-20 text-center">
      <Compass className="size-16 text-marca-600" aria-hidden />
      <h1 className="text-4xl">No encontramos esta página</h1>
      <p className="max-w-lg text-lg text-tinta-suave">
        Es posible que la dirección esté mal escrita o que la página ya no exista.
      </p>
      <BotonEnlace to="/" tamano="grande">
        Volver al inicio
      </BotonEnlace>
    </div>
  );
}
