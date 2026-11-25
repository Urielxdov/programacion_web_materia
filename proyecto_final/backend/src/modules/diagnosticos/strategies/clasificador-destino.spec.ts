import { CondicionEquipo, TipoCategoria } from '../../../common/enums';
import { Categoria } from '../../catalogos/categoria.entity';
import { ClasificadorDestino } from './clasificador-destino';
import {
  ContextoClasificacion,
  GestionEspecializadaStrategy,
  ReparacionNoRentableStrategy,
  ReparacionStrategy,
  ReutilizacionDirectaStrategy,
} from './estrategia-clasificacion';

function categoria(tipo: TipoCategoria, valorReposicion = 10000): Categoria {
  return Object.assign(new Categoria(), {
    id: 1,
    nombre: 'Prueba',
    tipo,
    valorReposicion,
    pesoPromedioKg: 1,
  });
}

const base = (
  extra: Partial<ContextoClasificacion> = {},
): ContextoClasificacion => ({
  categoria: categoria(TipoCategoria.COMPUTO),
  enciende: true,
  danoEstructural: false,
  componentesPeligrosos: false,
  costoReparacionEstimado: 0,
  ...extra,
});

describe('ClasificadorDestino (patrón Strategy)', () => {
  // Se registran desordenadas a propósito: el clasificador debe ordenarlas por prioridad.
  const clasificador = new ClasificadorDestino([
    new ReparacionNoRentableStrategy(),
    new ReparacionStrategy(),
    new ReutilizacionDirectaStrategy(),
    new GestionEspecializadaStrategy(),
  ]);

  it('un equipo que funciona sin costo es FUNCIONAL', () => {
    const r = clasificador.clasificar(base());
    expect(r.condicion).toBe(CondicionEquipo.FUNCIONAL);
    expect(r.estrategia).toBe('ReutilizacionDirecta');
  });

  it('si repararlo cuesta menos del 50% del valor de reposición, es REPARABLE', () => {
    const r = clasificador.clasificar(
      base({ enciende: false, costoReparacionEstimado: 4999 }),
    );
    expect(r.condicion).toBe(CondicionEquipo.REPARABLE);
  });

  it('si repararlo cuesta más del 50%, va a GESTION_ESPECIALIZADA', () => {
    const r = clasificador.clasificar(
      base({ enciende: false, costoReparacionEstimado: 6000 }),
    );
    expect(r.condicion).toBe(CondicionEquipo.GESTION_ESPECIALIZADA);
    expect(r.estrategia).toBe('ReparacionNoRentable');
  });

  it('las baterías siempre van a gestión especializada aunque funcionen', () => {
    const r = clasificador.clasificar(
      base({ categoria: categoria(TipoCategoria.ENERGIA) }),
    );
    expect(r.condicion).toBe(CondicionEquipo.GESTION_ESPECIALIZADA);
    expect(r.estrategia).toBe('GestionEspecializada');
  });

  it('el daño estructural tiene prioridad sobre que el equipo encienda', () => {
    const r = clasificador.clasificar(base({ danoEstructural: true }));
    expect(r.condicion).toBe(CondicionEquipo.GESTION_ESPECIALIZADA);
  });
});
