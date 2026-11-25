import {
  CreateDateColumn,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';

/**
 * Clase base abstracta de la que heredan todas las entidades.
 * Centraliza el identificador y las marcas de tiempo (herencia / reutilización).
 */
export abstract class EntidadBase {
  @PrimaryGeneratedColumn()
  id!: number;

  @CreateDateColumn({ type: 'timestamptz' })
  creadoEn!: Date;

  @UpdateDateColumn({ type: 'timestamptz' })
  actualizadoEn!: Date;
}

/** Transforma columnas numeric de Postgres (que llegan como string) a number. */
export const numericTransformer = {
  to: (value?: number | null) => value,
  from: (value?: string | null) =>
    value === null || value === undefined ? null : Number(value),
};
