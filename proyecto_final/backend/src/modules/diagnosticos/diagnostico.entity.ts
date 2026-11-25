import { Check, Column, Entity, JoinColumn, ManyToOne } from 'typeorm';
import {
  EntidadBase,
  numericTransformer,
} from '../../common/entities/base.entity';
import { CondicionEquipo } from '../../common/enums';
import { Equipo } from '../equipos/equipo.entity';
import { Usuario } from '../usuarios/usuario.entity';

@Entity('diagnosticos')
@Check('"costo_reparacion_estimado" >= 0')
export class Diagnostico extends EntidadBase {
  @ManyToOne(() => Equipo, (e) => e.diagnosticos, {
    nullable: false,
    onDelete: 'CASCADE',
  })
  @JoinColumn()
  equipo!: Equipo;

  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  responsable!: Usuario | null;

  @Column({ type: 'text' })
  descripcion!: string;

  @Column()
  enciende!: boolean;

  @Column({ default: false })
  danoEstructural!: boolean;

  @Column({ default: false })
  componentesPeligrosos!: boolean;

  @Column({
    type: 'numeric',
    precision: 12,
    scale: 2,
    default: 0,
    transformer: numericTransformer,
  })
  costoReparacionEstimado!: number;

  /** Resultado calculado por la estrategia de clasificación. */
  @Column({ type: 'enum', enum: CondicionEquipo, enumName: 'condicion_equipo' })
  recomendacion!: CondicionEquipo;

  @Column({ length: 60 })
  estrategiaAplicada!: string;

  @Column({ type: 'text' })
  justificacion!: string;
}
