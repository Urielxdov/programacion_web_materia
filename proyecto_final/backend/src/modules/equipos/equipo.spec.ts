import {
  CondicionEquipo,
  EstadoDestino,
  TipoCategoria,
} from '../../common/enums';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { Categoria } from '../catalogos/categoria.entity';
import { Gestor } from '../catalogos/gestor.entity';
import { Usuario } from '../usuarios/usuario.entity';
import { EquipoFactory } from './factory/equipo.factory';

const categoria = (tipo: TipoCategoria) =>
  Object.assign(new Categoria(), {
    id: 1,
    nombre: tipo,
    tipo,
    valorReposicion: 10000,
    pesoPromedioKg: 2,
  });

const nuevo = (tipo: TipoCategoria, numeroSerie: string | null = 'SN-1') =>
  new EquipoFactory().crear({
    folio: 'EW-2026-0001',
    categoria: categoria(tipo),
    marca: 'Marca',
    modelo: 'Modelo',
    numeroSerie,
    fechaIngreso: '2026-11-01',
  });

describe('EquipoFactory (patrón Factory)', () => {
  it('crea equipos de cómputo que requieren borrado de datos', () => {
    const e = nuevo(TipoCategoria.COMPUTO);
    expect(e.requiereBorradoDatos).toBe(true);
    expect(e.condicion).toBe(CondicionEquipo.PENDIENTE);
    expect(e.estadoDestino).toBe(EstadoDestino.EN_INVENTARIO);
  });

  it('exige número de serie a los equipos de cómputo', () => {
    expect(() => nuevo(TipoCategoria.COMPUTO, null)).toThrow(ReglaNegocioError);
  });

  it('las baterías nacen como gestión especializada', () => {
    expect(nuevo(TipoCategoria.ENERGIA, null).condicion).toBe(
      CondicionEquipo.GESTION_ESPECIALIZADA,
    );
  });

  it('cada perfil ofrece recomendaciones de entrega propias', () => {
    const f = new EquipoFactory();
    expect(
      f.perfilPara(TipoCategoria.MOVIL).recomendacionesEntrega().join(' '),
    ).toMatch(/SIM/);
    expect(
      f.perfilPara(TipoCategoria.ENERGIA).recomendacionesEntrega().join(' '),
    ).toMatch(/cinta/);
  });
});

describe('Equipo: reglas del ciclo de vida', () => {
  it('no se puede reasignar un equipo reparable', () => {
    const e = nuevo(TipoCategoria.PERIFERICO);
    e.aplicarDiagnostico(CondicionEquipo.REPARABLE);
    expect(() => e.reasignar('Lab', true)).toThrow('Solo se pueden reasignar');
  });

  it('flujo reparar → reasignar exige confirmar el borrado de datos', () => {
    const e = nuevo(TipoCategoria.COMPUTO);
    e.aplicarDiagnostico(CondicionEquipo.REPARABLE);
    e.enviarAReparacion();
    expect(e.accionesDisponibles()).toEqual(['REGISTRAR_REPARACION']);
    e.registrarResultadoReparacion(true);
    expect(e.condicion).toBe(CondicionEquipo.REUTILIZABLE);
    expect(() => e.reasignar('Lab', false)).toThrow(/borrados/);
    e.reasignar('Lab', true);
    expect(e.estadoDestino).toBe(EstadoDestino.REASIGNADO);
    expect(e.accionesDisponibles()).toEqual([]);
  });

  it('no se puede entregar a un gestor no verificado', () => {
    const e = nuevo(TipoCategoria.ENERGIA, null);
    const gestor = Object.assign(new Gestor(), {
      nombre: 'Chatarrería',
      verificado: false,
    });
    expect(() => e.entregarAGestor(gestor, true)).toThrow(/no está verificado/);

    gestor.verificar('AUT-123', new Usuario());
    e.entregarAGestor(gestor, true);
    expect(e.estadoDestino).toBe(EstadoDestino.ENTREGADO_GESTOR);
    expect(e.tieneDestinoFinal()).toBe(true);
  });
});
