import { Column, Entity, Index } from 'typeorm';
import { EntidadBase } from '../../common/entities/base.entity';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';

@Entity('usuarios')
export class Usuario extends EntidadBase {
  @Column({ length: 120 })
  nombre!: string;

  @Index({ unique: true })
  @Column({ length: 160 })
  email!: string;

  /** Hash argon2. Nunca se selecciona por defecto ni se envía al cliente. */
  @Column({ select: false, length: 255 })
  passwordHash!: string;

  @Column({
    type: 'enum',
    enum: Rol,
    enumName: 'rol_usuario',
    default: Rol.CAPTURISTA,
  })
  rol!: Rol;

  @Column({ default: true })
  activo!: boolean;

  /** Datos que se guardan en la sesión (recolección mínima de datos). */
  aSesion(): UsuarioSesion {
    return {
      id: this.id,
      nombre: this.nombre,
      email: this.email,
      rol: this.rol,
    };
  }

  tieneRol(...roles: Rol[]): boolean {
    return roles.includes(this.rol);
  }

  desactivar(): void {
    this.activo = false;
  }

  /** Normaliza el correo para que el login no dependa de mayúsculas. */
  static normalizarEmail(email: string): string {
    return email.trim().toLowerCase();
  }
}
