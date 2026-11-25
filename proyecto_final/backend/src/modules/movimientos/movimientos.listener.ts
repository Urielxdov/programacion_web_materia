import { Injectable } from '@nestjs/common';
import { OnEvent } from '@nestjs/event-emitter';
import { EventoEquipo } from '../../common/events/equipo.events';
import { MovimientosService } from './movimientos.service';

/**
 * Observador (patrón Observer): escucha todos los eventos "equipo.*" y deja
 * constancia en el historial de movimientos. Los módulos que emiten los eventos
 * no saben que este listener existe.
 */
@Injectable()
export class MovimientosListener {
  constructor(private readonly movimientos: MovimientosService) {}

  @OnEvent(EventoEquipo.PATRON, { promisify: true, suppressErrors: false })
  async alOcurrirEventoDeEquipo(evento: EventoEquipo): Promise<void> {
    await this.movimientos.registrarEvento(evento);
  }
}
