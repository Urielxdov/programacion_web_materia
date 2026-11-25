import { ConflictException, Injectable } from '@nestjs/common';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { EquipoRegistradoEvent } from '../../common/events/equipo.events';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { Campana } from '../campanas/campana.entity';
import { CampanasRepository } from '../campanas/campanas.repository';
import { CategoriasRepository } from '../catalogos/catalogos.repository';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import { AccionEquipo, Equipo } from './equipo.entity';
import {
  ActualizarEquipoDto,
  CrearEquipoDto,
  FiltrosEquiposDto,
} from './equipos.dto';
import { EquiposRepository } from './equipos.repository';
import { EquipoFactory } from './factory/equipo.factory';

export type DetalleEquipo = Equipo & {
  accionesDisponibles: AccionEquipo[];
  recomendacionesEntrega: string[];
};

@Injectable()
export class EquiposService {
  constructor(
    private readonly equipos: EquiposRepository,
    private readonly categorias: CategoriasRepository,
    private readonly usuarios: UsuariosRepository,
    private readonly factory: EquipoFactory,
    private readonly campanas: CampanasRepository,
    private readonly eventos: EventEmitter2,
  ) {}

  buscar(filtros: FiltrosEquiposDto) {
    return this.equipos.buscar(filtros);
  }

  async detalle(id: number): Promise<DetalleEquipo> {
    const equipo = await this.equipos.detalle(id);
    return Object.assign(equipo, {
      accionesDisponibles: equipo.accionesDisponibles(),
      recomendacionesEntrega: this.factory
        .perfilPara(equipo.categoria.tipo)
        .recomendacionesEntrega(),
    });
  }

  async registrar(
    dto: CrearEquipoDto,
    usuarioId: number,
  ): Promise<DetalleEquipo> {
    this.validarFecha(dto.fechaIngreso);
    if (
      dto.numeroSerie &&
      (await this.equipos.existeNumeroSerie(dto.numeroSerie))
    ) {
      throw new ConflictException(
        'Ya existe un equipo registrado con ese número de serie.',
      );
    }
    const categoria = await this.categorias.obtenerPorId(dto.categoriaId);
    const registradoPor = await this.usuarios.obtenerPorId(usuarioId);

    let campana: Campana | null = null;
    if (dto.campanaId) {
      campana = await this.campanas.obtenerPorId(dto.campanaId);
      if (!campana.acepta(categoria.id)) {
        throw new ReglaNegocioError(
          `La campaña "${campana.nombre}" no acepta equipos de tipo ${categoria.nombre}.`,
        );
      }
    }

    const guardado = await this.equipos.transaccion(async (manager) => {
      // Bloqueo a nivel transacción para que dos registros simultáneos no generen el mismo folio.
      await manager.query('SELECT pg_advisory_xact_lock(360)');
      const folio = await this.equipos.siguienteFolio(new Date(), manager);
      const equipo = this.factory.crear({
        ...dto,
        folio,
        categoria,
        campana,
        registradoPor,
      });
      return this.equipos.guardar(equipo, manager);
    });

    await this.eventos.emitAsync(
      'equipo.registrado',
      new EquipoRegistradoEvent(
        guardado.id,
        usuarioId,
        guardado.folio,
        campana ? `recibido en la campaña "${campana.nombre}"` : null,
      ),
    );
    return this.detalle(guardado.id);
  }

  async actualizar(
    id: number,
    dto: ActualizarEquipoDto,
  ): Promise<DetalleEquipo> {
    const equipo = await this.equipos.obtenerPorId(id);
    if (equipo.tieneDestinoFinal()) {
      throw new ReglaNegocioError(
        'No se pueden editar equipos que ya tienen un destino final documentado.',
      );
    }
    if (dto.fechaIngreso) this.validarFecha(dto.fechaIngreso);
    if (
      dto.numeroSerie &&
      (await this.equipos.existeNumeroSerie(dto.numeroSerie, id))
    ) {
      throw new ConflictException(
        'Ya existe un equipo registrado con ese número de serie.',
      );
    }
    if (dto.marca !== undefined) equipo.marca = dto.marca;
    if (dto.modelo !== undefined) equipo.modelo = dto.modelo;
    if (dto.numeroSerie !== undefined)
      equipo.numeroSerie = dto.numeroSerie || null;
    if (dto.fechaIngreso !== undefined) equipo.fechaIngreso = dto.fechaIngreso;
    if (dto.ubicacion !== undefined) equipo.ubicacion = dto.ubicacion || null;
    if (dto.observaciones !== undefined)
      equipo.observaciones = dto.observaciones || null;

    // Se vuelve a validar con el perfil del tipo (p. ej. cómputo exige número de serie).
    this.factory.perfilPara(equipo.categoria.tipo).validar(equipo);
    await this.equipos.guardar(equipo);
    return this.detalle(id);
  }

  private validarFecha(fecha: string): void {
    const hoy = new Date().toISOString().slice(0, 10);
    if (fecha > hoy)
      throw new ReglaNegocioError('La fecha de ingreso no puede ser futura.');
  }
}
