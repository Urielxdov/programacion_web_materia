import {
  Column,
  Entity,
  Index,
  JoinColumn,
  ManyToOne,
  OneToMany,
} from 'typeorm';
import { EntidadBase } from '../../common/entities/base.entity';
import { CondicionEquipo, EstadoDestino } from '../../common/enums';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { Categoria } from '../catalogos/categoria.entity';
import type { Gestor } from '../catalogos/gestor.entity';
import { Usuario } from '../usuarios/usuario.entity';
import type { Campana } from '../campanas/campana.entity';
import type { Movimiento } from '../movimientos/movimiento.entity';
import type { Diagnostico } from '../diagnosticos/diagnostico.entity';
import type { Reparacion } from '../reparaciones/reparacion.entity';

export type AccionEquipo =
  | 'DIAGNOSTICAR'
  | 'ENVIAR_REPARACION'
  | 'REGISTRAR_REPARACION'
  | 'REASIGNAR'
  | 'CONSERVAR'
  | 'ENTREGAR_GESTOR';

/**
 * Equipo electrónico registrado en el inventario.
 * Encapsula las reglas que gobiernan su ciclo de vida: un equipo solo cambia
 * de estado a través de estos métodos, nunca asignando campos sueltos.
 */
@Entity('equipos')
export class Equipo extends EntidadBase {
  /** Identificador único legible, p. ej. EW-2026-0001. */
  @Index({ unique: true })
  @Column({ length: 20 })
  folio!: string;

  @ManyToOne(() => Categoria, {
    nullable: false,
    onDelete: 'RESTRICT',
    eager: true,
  })
  @JoinColumn()
  categoria!: Categoria;

  @Column({ length: 80 })
  marca!: string;

  @Column({ length: 120 })
  modelo!: string;

  @Column({ type: 'varchar', length: 120, nullable: true })
  numeroSerie!: string | null;

  @Column({ type: 'date' })
  fechaIngreso!: string;

  @Index()
  @Column({
    type: 'enum',
    enum: CondicionEquipo,
    enumName: 'condicion_equipo',
    default: CondicionEquipo.PENDIENTE,
  })
  condicion!: CondicionEquipo;

  @Index()
  @Column({
    type: 'enum',
    enum: EstadoDestino,
    enumName: 'estado_destino',
    default: EstadoDestino.EN_INVENTARIO,
  })
  estadoDestino!: EstadoDestino;

  @Column({ type: 'varchar', length: 160, nullable: true })
  ubicacion!: string | null;

  /** Área, laboratorio o gestor donde terminó el equipo (documentado). */
  @Column({ type: 'varchar', length: 200, nullable: true })
  destinoActual!: string | null;

  @Column({ type: 'text', nullable: true })
  observaciones!: string | null;

  /** Lo decide la EquipoFactory según la categoría (cómputo y móviles guardan datos). */
  @Column({ default: false })
  requiereBorradoDatos!: boolean;

  @Column({ default: false })
  datosBorrados!: boolean;

  @ManyToOne('Campana', 'equiposRecibidos', {
    nullable: true,
    onDelete: 'SET NULL',
  })
  @JoinColumn()
  campana!: Campana | null;

  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  registradoPor!: Usuario | null;

  @OneToMany('Movimiento', 'equipo')
  movimientos?: Movimiento[];

  @OneToMany('Diagnostico', 'equipo')
  diagnosticos?: Diagnostico[];

  @OneToMany('Reparacion', 'equipo')
  reparaciones?: Reparacion[];

  // ───────────── Reglas de dominio ─────────────

  /** Acciones de gestión permitidas en el estado actual (la interfaz las usa para mostrar botones). */
  accionesDisponibles(): AccionEquipo[] {
    if (this.tieneDestinoFinal()) return [];
    if (this.estadoDestino === EstadoDestino.EN_REPARACION)
      return ['REGISTRAR_REPARACION'];

    const acciones: AccionEquipo[] = ['DIAGNOSTICAR'];
    switch (this.condicion) {
      case CondicionEquipo.REPARABLE:
        acciones.push('ENVIAR_REPARACION');
        break;
      case CondicionEquipo.FUNCIONAL:
      case CondicionEquipo.REUTILIZABLE:
        acciones.push('REASIGNAR');
        break;
      case CondicionEquipo.GESTION_ESPECIALIZADA:
        acciones.push('ENTREGAR_GESTOR');
        break;
    }
    if (
      this.estadoDestino !== EstadoDestino.CONSERVADO &&
      this.condicion !== CondicionEquipo.GESTION_ESPECIALIZADA
    ) {
      acciones.push('CONSERVAR');
    }
    return acciones;
  }

  /** Un equipo reasignado o entregado a un gestor ya no admite cambios de estado. */
  tieneDestinoFinal(): boolean {
    return (
      this.estadoDestino === EstadoDestino.REASIGNADO ||
      this.estadoDestino === EstadoDestino.ENTREGADO_GESTOR
    );
  }

  estaDisponible(): boolean {
    return (
      this.estadoDestino === EstadoDestino.EN_INVENTARIO ||
      this.estadoDestino === EstadoDestino.CONSERVADO
    );
  }

  aplicarDiagnostico(condicion: CondicionEquipo): void {
    this.exigirNoFinalizado();
    if (this.estadoDestino === EstadoDestino.EN_REPARACION) {
      throw new ReglaNegocioError(
        'El equipo está en reparación. Registra primero el resultado de la reparación.',
      );
    }
    this.condicion = condicion;
  }

  enviarAReparacion(): void {
    this.exigirDisponible();
    if (this.condicion !== CondicionEquipo.REPARABLE) {
      throw new ReglaNegocioError(
        'Solo los equipos diagnosticados como reparables pueden enviarse a reparación.',
      );
    }
    this.estadoDestino = EstadoDestino.EN_REPARACION;
  }

  registrarResultadoReparacion(exitosa: boolean): void {
    if (this.estadoDestino !== EstadoDestino.EN_REPARACION) {
      throw new ReglaNegocioError('El equipo no está en reparación.');
    }
    this.estadoDestino = EstadoDestino.EN_INVENTARIO;
    this.condicion = exitosa
      ? CondicionEquipo.REUTILIZABLE
      : CondicionEquipo.GESTION_ESPECIALIZADA;
  }

  reasignar(destino: string, datosBorrados: boolean): void {
    this.exigirDisponible();
    if (
      this.condicion !== CondicionEquipo.FUNCIONAL &&
      this.condicion !== CondicionEquipo.REUTILIZABLE
    ) {
      throw new ReglaNegocioError(
        'Solo se pueden reasignar equipos funcionales o reutilizables.',
      );
    }
    this.exigirBorradoDatos(datosBorrados);
    this.estadoDestino = EstadoDestino.REASIGNADO;
    this.destinoActual = destino;
  }

  conservar(ubicacion: string): void {
    this.exigirDisponible();
    if (this.condicion === CondicionEquipo.GESTION_ESPECIALIZADA) {
      throw new ReglaNegocioError(
        'Un equipo que requiere gestión especializada no debe almacenarse: entrégalo a un gestor verificado.',
      );
    }
    this.estadoDestino = EstadoDestino.CONSERVADO;
    this.ubicacion = ubicacion;
    this.destinoActual = null;
  }

  entregarAGestor(gestor: Gestor, datosBorrados: boolean): void {
    this.exigirDisponible();
    if (this.condicion !== CondicionEquipo.GESTION_ESPECIALIZADA) {
      throw new ReglaNegocioError(
        'Solo se entregan a gestores los equipos que no pueden repararse ni reutilizarse.',
      );
    }
    if (!gestor.puedeRecibirEquipos()) {
      throw new ReglaNegocioError(
        `El gestor "${gestor.nombre}" no está verificado. No es posible documentar la entrega.`,
      );
    }
    this.exigirBorradoDatos(datosBorrados);
    this.estadoDestino = EstadoDestino.ENTREGADO_GESTOR;
    this.destinoActual = gestor.nombre;
  }

  private exigirNoFinalizado(): void {
    if (this.tieneDestinoFinal()) {
      throw new ReglaNegocioError(
        'El equipo ya tiene un destino final documentado.',
      );
    }
  }

  private exigirDisponible(): void {
    this.exigirNoFinalizado();
    if (!this.estaDisponible()) {
      throw new ReglaNegocioError(
        'El equipo no está disponible en inventario para esta acción.',
      );
    }
  }

  private exigirBorradoDatos(confirmado: boolean): void {
    if (this.requiereBorradoDatos && !this.datosBorrados) {
      if (!confirmado) {
        throw new ReglaNegocioError(
          'Este equipo puede contener información. Confirma que los datos fueron borrados de forma segura.',
        );
      }
      this.datosBorrados = true;
    }
  }
}
