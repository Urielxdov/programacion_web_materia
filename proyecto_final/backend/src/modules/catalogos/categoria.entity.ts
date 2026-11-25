import { Check, Column, Entity, Index } from 'typeorm';
import {
  EntidadBase,
  numericTransformer,
} from '../../common/entities/base.entity';
import { TipoCategoria } from '../../common/enums';

@Entity('categorias')
@Check('"peso_promedio_kg" >= 0')
@Check('"valor_reposicion" >= 0')
export class Categoria extends EntidadBase {
  @Index({ unique: true })
  @Column({ length: 80 })
  nombre!: string;

  @Column({ type: 'enum', enum: TipoCategoria, enumName: 'tipo_categoria' })
  tipo!: TipoCategoria;

  /** Peso promedio por unidad, para estimar kg de residuo evitado. */
  @Column({
    type: 'numeric',
    precision: 8,
    scale: 2,
    default: 0,
    transformer: numericTransformer,
  })
  pesoPromedioKg!: number;

  /** Costo estimado de comprar un equipo nuevo equivalente (MXN), para calcular ahorro. */
  @Column({
    type: 'numeric',
    precision: 12,
    scale: 2,
    default: 0,
    transformer: numericTransformer,
  })
  valorReposicion!: number;

  @Column({ type: 'text', nullable: true })
  descripcion!: string | null;

  /** Las categorías de energía (baterías, pilas) contienen materiales peligrosos. */
  esPeligrosa(): boolean {
    return this.tipo === TipoCategoria.ENERGIA;
  }
}
