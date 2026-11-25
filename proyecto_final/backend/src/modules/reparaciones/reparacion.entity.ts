import { Check, Column, Entity, JoinColumn, ManyToOne } from 'typeorm';
import {
  EntidadBase,
  numericTransformer,
} from '../../common/entities/base.entity';
import { ResultadoReparacion } from '../../common/enums';
import { Diagnostico } from '../diagnosticos/diagnostico.entity';
import { Equipo } from '../equipos/equipo.entity';
import { Usuario } from '../usuarios/usuario.entity';

@Entity('reparaciones')
@Check('"costo" >= 0')
export class Reparacion extends EntidadBase {
  @ManyToOne(() => Equipo, (e) => e.reparaciones, {
    nullable: false,
    onDelete: 'CASCADE',
  })
  @JoinColumn()
  equipo!: Equipo;

  @ManyToOne(() => Diagnostico, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  diagnostico!: Diagnostico | null;

  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  responsable!: Usuario | null;

  @Column({ type: 'text' })
  descripcion!: string;

  @Column({
    type: 'numeric',
    precision: 12,
    scale: 2,
    transformer: numericTransformer,
  })
  costo!: number;

  @Column({
    type: 'enum',
    enum: ResultadoReparacion,
    enumName: 'resultado_reparacion',
  })
  resultado!: ResultadoReparacion;

  /** Valor de reposición evitado menos el costo de la reparación (solo si fue exitosa). */
  @Column({
    type: 'numeric',
    precision: 12,
    scale: 2,
    default: 0,
    transformer: numericTransformer,
  })
  ahorroEstimado!: number;

  @Column({ type: 'date' })
  fecha!: string;

  /** Ahorro = lo que costaría reponer el equipo − lo que costó repararlo (mínimo 0). */
  static calcularAhorro(
    valorReposicion: number,
    costo: number,
    exitosa: boolean,
  ): number {
    if (!exitosa) return 0;
    return Math.max(0, Math.round((valorReposicion - costo) * 100) / 100);
  }
}
