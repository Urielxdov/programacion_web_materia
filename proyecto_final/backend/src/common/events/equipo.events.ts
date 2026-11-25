import { EstadoDestino, TipoMovimiento } from '../enums';

/**
 * Patrón Observer: eventos de dominio que emiten los módulos de equipos,
 * diagnósticos y reparaciones. El MovimientosListener los "observa" y deja
 * el registro de trazabilidad sin que esos módulos dependan de él.
 */
export abstract class EventoEquipo {
  /** Todos los eventos se publican bajo el prefijo "equipo." */
  static readonly PATRON = 'equipo.*';

  abstract readonly nombre: string;
  abstract readonly tipoMovimiento: TipoMovimiento;

  constructor(
    readonly equipoId: number,
    readonly responsableId: number | null,
    readonly estadoAnterior: EstadoDestino | null,
    readonly estadoNuevo: EstadoDestino,
  ) {}

  abstract describir(): string;
}

export class EquipoRegistradoEvent extends EventoEquipo {
  readonly nombre = 'equipo.registrado';
  readonly tipoMovimiento = TipoMovimiento.INGRESO;

  constructor(
    equipoId: number,
    responsableId: number | null,
    private readonly folio: string,
    private readonly origen: string | null,
  ) {
    super(equipoId, responsableId, null, EstadoDestino.EN_INVENTARIO);
  }

  describir(): string {
    return `Ingreso del equipo ${this.folio} al inventario${this.origen ? ` (${this.origen})` : ''}.`;
  }
}

export class EquipoDiagnosticadoEvent extends EventoEquipo {
  readonly nombre = 'equipo.diagnosticado';
  readonly tipoMovimiento = TipoMovimiento.DIAGNOSTICO;

  constructor(
    equipoId: number,
    responsableId: number | null,
    estado: EstadoDestino,
    private readonly condicion: string,
    private readonly justificacion: string,
  ) {
    super(equipoId, responsableId, estado, estado);
  }

  describir(): string {
    return `Diagnóstico: ${this.condicion}. ${this.justificacion}`;
  }
}

export class EquipoReparadoEvent extends EventoEquipo {
  readonly nombre = 'equipo.reparado';
  readonly tipoMovimiento = TipoMovimiento.REPARACION;

  constructor(
    equipoId: number,
    responsableId: number | null,
    private readonly exitosa: boolean,
    private readonly costo: number,
  ) {
    super(
      equipoId,
      responsableId,
      EstadoDestino.EN_REPARACION,
      EstadoDestino.EN_INVENTARIO,
    );
  }

  describir(): string {
    const costo = this.costo.toLocaleString('es-MX', {
      style: 'currency',
      currency: 'MXN',
    });
    return this.exitosa
      ? `Reparación exitosa (costo ${costo}). El equipo vuelve al inventario como reutilizable.`
      : `Reparación sin éxito (costo ${costo}). El equipo pasa a gestión especializada.`;
  }
}
