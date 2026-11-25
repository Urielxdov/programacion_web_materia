import { Column, Entity, JoinColumn, ManyToOne } from 'typeorm';
import { EntidadBase } from '../../common/entities/base.entity';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { Usuario } from '../usuarios/usuario.entity';

/**
 * Gestor externo que recibe residuos electrónicos (reciclador, centro de acopio, etc.).
 * Solo un gestor verificado puede recibir equipos: el sistema no afirma que un
 * destino es autorizado sin una verificación documentada.
 */
@Entity('gestores')
export class Gestor extends EntidadBase {
  @Column({ length: 160 })
  nombre!: string;

  @Column({ length: 120 })
  tipo!: string;

  @Column({ type: 'varchar', length: 160, nullable: true })
  contacto!: string | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  direccion!: string | null;

  @Column({ default: false })
  verificado!: boolean;

  /** Número de autorización / documento que respalda la verificación. */
  @Column({ type: 'varchar', length: 160, nullable: true })
  documentoVerificacion!: string | null;

  @Column({ type: 'timestamptz', nullable: true })
  fechaVerificacion!: Date | null;

  @ManyToOne(() => Usuario, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn()
  verificadoPor!: Usuario | null;

  verificar(documento: string, responsable: Usuario): void {
    if (!documento?.trim()) {
      throw new ReglaNegocioError(
        'Se requiere el documento de autorización para verificar al gestor.',
      );
    }
    this.verificado = true;
    this.documentoVerificacion = documento.trim();
    this.fechaVerificacion = new Date();
    this.verificadoPor = responsable;
  }

  revocarVerificacion(): void {
    this.verificado = false;
    this.fechaVerificacion = null;
    this.verificadoPor = null;
  }

  puedeRecibirEquipos(): boolean {
    return this.verificado && !!this.documentoVerificacion;
  }
}
