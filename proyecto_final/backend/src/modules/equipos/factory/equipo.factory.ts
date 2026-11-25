import { Injectable } from '@nestjs/common';
import { EstadoDestino, TipoCategoria } from '../../../common/enums';
import type { Campana } from '../../campanas/campana.entity';
import { Categoria } from '../../catalogos/categoria.entity';
import { Usuario } from '../../usuarios/usuario.entity';
import { Equipo } from '../equipo.entity';
import {
  PerfilCableado,
  PerfilComputo,
  PerfilEnergia,
  PerfilEquipo,
  PerfilGenerico,
  PerfilMovil,
  PerfilPeriferico,
} from './perfiles-equipo';

export interface ParametrosCreacion {
  folio: string;
  categoria: Categoria;
  marca: string;
  modelo: string;
  numeroSerie?: string | null;
  fechaIngreso: string;
  ubicacion?: string | null;
  observaciones?: string | null;
  campana?: Campana | null;
  registradoPor?: Usuario | null;
}

/**
 * Patrón Factory.
 *
 * Problema que resuelve: cada tipo de dispositivo se registra con reglas
 * distintas (las laptops exigen número de serie y borrado de datos, las
 * baterías entran directo a gestión especializada, etc.). En lugar de llenar
 * el servicio de if/switch, la fábrica elige el perfil adecuado según la
 * categoría y construye el Equipo ya configurado.
 */
@Injectable()
export class EquipoFactory {
  private readonly perfiles: Record<TipoCategoria, PerfilEquipo> = {
    [TipoCategoria.COMPUTO]: new PerfilComputo(),
    [TipoCategoria.MOVIL]: new PerfilMovil(),
    [TipoCategoria.ENERGIA]: new PerfilEnergia(),
    [TipoCategoria.PERIFERICO]: new PerfilPeriferico(),
    [TipoCategoria.CABLEADO]: new PerfilCableado(),
    [TipoCategoria.OTRO]: new PerfilGenerico(),
  };

  perfilPara(tipo: TipoCategoria): PerfilEquipo {
    return this.perfiles[tipo] ?? this.perfiles[TipoCategoria.OTRO];
  }

  crear(p: ParametrosCreacion): Equipo {
    const perfil = this.perfilPara(p.categoria.tipo);
    perfil.validar(p);

    const equipo = new Equipo();
    equipo.folio = p.folio;
    equipo.categoria = p.categoria;
    equipo.marca = p.marca;
    equipo.modelo = p.modelo;
    equipo.numeroSerie = p.numeroSerie?.trim() || null;
    equipo.fechaIngreso = p.fechaIngreso;
    equipo.ubicacion = p.ubicacion ?? null;
    equipo.observaciones = p.observaciones ?? null;
    equipo.campana = p.campana ?? null;
    equipo.registradoPor = p.registradoPor ?? null;
    equipo.condicion = perfil.condicionInicial();
    equipo.estadoDestino = EstadoDestino.EN_INVENTARIO;
    equipo.requiereBorradoDatos = perfil.requiereBorradoDatos();
    equipo.datosBorrados = false;
    equipo.destinoActual = null;
    return equipo;
  }
}
