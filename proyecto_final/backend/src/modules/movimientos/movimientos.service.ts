import { Injectable } from '@nestjs/common';
import { EntityManager } from 'typeorm';
import { EstadoDestino, TipoMovimiento } from '../../common/enums';
import { EventoEquipo } from '../../common/events/equipo.events';
import { GestoresRepository } from '../catalogos/catalogos.repository';
import type { Gestor } from '../catalogos/gestor.entity';
import { Equipo } from '../equipos/equipo.entity';
import { EquiposRepository } from '../equipos/equipos.repository';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import { Movimiento } from './movimiento.entity';
import { RegistrarDecisionDto } from './movimientos.dto';
import { MovimientosRepository } from './movimientos.repository';

interface NuevoMovimiento {
  equipo: Equipo;
  tipo: TipoMovimiento;
  responsableId: number | null;
  descripcion: string;
  estadoAnterior: EstadoDestino | null;
  estadoNuevo: EstadoDestino;
  destino?: string | null;
  gestor?: Gestor | null;
  evidencia?: string | null;
}

@Injectable()
export class MovimientosService {
  constructor(
    private readonly movimientos: MovimientosRepository,
    private readonly equipos: EquiposRepository,
    private readonly gestores: GestoresRepository,
    private readonly usuarios: UsuariosRepository,
  ) {}

  async historial(equipoId: number): Promise<Movimiento[]> {
    await this.equipos.obtenerPorId(equipoId);
    return this.movimientos.deEquipo(equipoId);
  }

  recientes(limite: number) {
    return this.movimientos.recientes(limite);
  }

  /**
   * Registra una decisión de destino. El cambio de estado del equipo y el
   * movimiento se guardan en la misma transacción: no puede existir uno sin el otro.
   */
  async registrarDecision(
    equipoId: number,
    dto: RegistrarDecisionDto,
    usuarioId: number,
  ): Promise<Movimiento> {
    return this.movimientos.transaccion(async (manager) => {
      const equipo = await this.equipos.obtenerParaActualizar(
        equipoId,
        manager,
      );
      const anterior = equipo.estadoDestino;
      let gestor: Gestor | null = null;
      let descripcion: string;

      switch (dto.tipo) {
        case TipoMovimiento.ENVIO_REPARACION:
          equipo.enviarAReparacion();
          descripcion = 'Enviado a reparación.';
          break;
        case TipoMovimiento.REASIGNACION:
          equipo.reasignar(dto.destino!, dto.datosBorrados ?? false);
          descripcion = `Reasignado a: ${dto.destino}.`;
          break;
        case TipoMovimiento.CONSERVACION:
          equipo.conservar(dto.destino!);
          descripcion = `Se conserva en: ${dto.destino}.`;
          break;
        case TipoMovimiento.ENTREGA_GESTOR:
          gestor = await this.gestores.obtenerPorId(
            dto.gestorId!,
            undefined,
            manager,
          );
          equipo.entregarAGestor(gestor, dto.datosBorrados ?? false);
          descripcion = `Entregado al gestor verificado "${gestor.nombre}" (autorización ${gestor.documentoVerificacion}).`;
          break;
      }
      if (dto.notas) descripcion += ` Notas: ${dto.notas}`;

      await this.equipos.guardar(equipo, manager);
      return this.registrar(
        {
          equipo,
          tipo: dto.tipo,
          responsableId: usuarioId,
          descripcion,
          estadoAnterior: anterior,
          estadoNuevo: equipo.estadoDestino,
          destino: equipo.destinoActual ?? dto.destino ?? null,
          gestor,
          evidencia: dto.evidencia ?? null,
        },
        manager,
      );
    });
  }

  /** Registra en el historial un evento emitido por otro módulo (Observer). */
  async registrarEvento(evento: EventoEquipo): Promise<Movimiento> {
    const equipo = await this.equipos.obtenerPorId(evento.equipoId);
    return this.registrar({
      equipo,
      tipo: evento.tipoMovimiento,
      responsableId: evento.responsableId,
      descripcion: evento.describir(),
      estadoAnterior: evento.estadoAnterior,
      estadoNuevo: evento.estadoNuevo,
    });
  }

  private async registrar(
    datos: NuevoMovimiento,
    manager?: EntityManager,
  ): Promise<Movimiento> {
    const movimiento = this.movimientos.crear({
      equipo: datos.equipo,
      tipo: datos.tipo,
      responsable: datos.responsableId
        ? await this.usuarios.buscarPorId(
            datos.responsableId,
            undefined,
            manager,
          )
        : null,
      descripcion: datos.descripcion,
      estadoAnterior: datos.estadoAnterior,
      estadoNuevo: datos.estadoNuevo,
      destino: datos.destino ?? null,
      gestor: datos.gestor ?? null,
      evidencia: datos.evidencia ?? null,
    });
    return this.movimientos.guardar(movimiento, manager);
  }
}
