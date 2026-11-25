import { Column, Entity, Index, JoinColumn, ManyToOne } from 'typeorm';
import { EntidadBase } from '../../common/entities/base.entity';
import { EstadoDestino, TipoMovimiento } from '../../common/enums';
import { Gestor } from '../catalogos/gestor.entity';
import { Equipo } from '../equipos/equipo.entity';
import { Usuario } from '../usuarios/usuario.entity';

/**
 * Registro de trazabilidad. Los movimientos solo se insertan: no existe
 * endpoint para editarlos ni borrarlos, así el historial es verificable.
 */
@Entity('movimientos')
export class Movimiento extends EntidadBase {
  @Index()
  @ManyToOne(() => Equipo, (e) => e.movimientos, {
    nullable: false,
    onDelete: 'CASCADE',
  })
  @JoinColumn()
  equipo!: Equipo;

  @Index()
  @Column({ type: 'enum', enum: TipoMovimiento, enumName: 'tipo_movimiento' })
  tipo!: TipoMovimiento;

  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  responsable!: Usuario | null;

  @Column({ type: 'text' })
  descripcion!: string;

  @Column({
    type: 'enum',
    enum: EstadoDestino,
    enumName: 'estado_destino',
    nullable: true,
  })
  estadoAnterior!: EstadoDestino | null;

  @Column({ type: 'enum', enum: EstadoDestino, enumName: 'estado_destino' })
  estadoNuevo!: EstadoDestino;

  @Column({ type: 'varchar', length: 200, nullable: true })
  destino!: string | null;

  @ManyToOne(() => Gestor, { nullable: true, onDelete: 'RESTRICT' })
  @JoinColumn()
  gestor!: Gestor | null;

  /** Folio de entrega, número de oficio o referencia del documento que respalda el movimiento. */
  @Column({ type: 'varchar', length: 200, nullable: true })
  evidencia!: string | null;
}
