import {
  Check,
  Column,
  Entity,
  JoinColumn,
  JoinTable,
  ManyToMany,
  ManyToOne,
  OneToMany,
} from 'typeorm';
import { EntidadBase } from '../../common/entities/base.entity';
import { EstadoCampana } from '../../common/enums';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { Categoria } from '../catalogos/categoria.entity';
import { Equipo } from '../equipos/equipo.entity';
import { Usuario } from '../usuarios/usuario.entity';
import { Participacion } from './participacion.entity';

@Entity('campanas')
@Check('"fecha_fin" >= "fecha_inicio"')
export class Campana extends EntidadBase {
  @Column({ length: 160 })
  nombre!: string;

  @Column({ type: 'text' })
  descripcion!: string;

  @Column({ type: 'date' })
  fechaInicio!: string;

  @Column({ type: 'date' })
  fechaFin!: string;

  @Column({ length: 200 })
  ubicacion!: string;

  @Column({ type: 'varchar', length: 120, nullable: true })
  horario!: string | null;

  @Column({
    type: 'enum',
    enum: EstadoCampana,
    enumName: 'estado_campana',
    default: EstadoCampana.PROGRAMADA,
  })
  estado!: EstadoCampana;

  @Column({ type: 'int', nullable: true })
  metaEquipos!: number | null;

  /** Tipos de equipos aceptados (relación muchos a muchos con categorías). */
  @ManyToMany(() => Categoria, { eager: true })
  @JoinTable({ name: 'campana_categorias' })
  categoriasAceptadas!: Categoria[];

  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  creadaPor!: Usuario | null;

  @OneToMany(() => Participacion, (p) => p.campana)
  participaciones?: Participacion[];

  @OneToMany(() => Equipo, (e) => e.campana)
  equiposRecibidos?: Equipo[];

  aceptaParticipaciones(): boolean {
    return this.estado !== EstadoCampana.CERRADA;
  }

  acepta(categoriaId: number): boolean {
    return this.categoriasAceptadas.some((c) => c.id === categoriaId);
  }

  exigirAbierta(): void {
    if (!this.aceptaParticipaciones()) {
      throw new ReglaNegocioError('La campaña ya está cerrada.');
    }
  }
}
