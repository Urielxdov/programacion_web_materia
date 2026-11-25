import { Check, Column, Entity, JoinColumn, ManyToOne } from 'typeorm';
import { EntidadBase } from '../../common/entities/base.entity';
import { Usuario } from '../usuarios/usuario.entity';
import { Campana } from './campana.entity';

/**
 * Registro de participación en una campaña. Se piden solo los datos mínimos
 * (nombre y, opcionalmente, un contacto) para proteger la privacidad.
 */
@Entity('participaciones')
@Check('"equipos_declarados" >= 0')
export class Participacion extends EntidadBase {
  @ManyToOne(() => Campana, (c) => c.participaciones, {
    nullable: false,
    onDelete: 'CASCADE',
  })
  @JoinColumn()
  campana!: Campana;

  @Column({ length: 120 })
  nombreParticipante!: string;

  @Column({ type: 'varchar', length: 160, nullable: true })
  contacto!: string | null;

  /** Si quien participa tiene cuenta en el sistema. */
  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  usuario!: Usuario | null;

  @Column({ type: 'int', default: 1 })
  equiposDeclarados!: number;

  @Column({ type: 'text', nullable: true })
  comentario!: string | null;
}
