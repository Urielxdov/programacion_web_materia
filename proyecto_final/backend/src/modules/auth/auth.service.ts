import { Injectable, UnauthorizedException } from '@nestjs/common';
import { randomBytes } from 'crypto';
import type { UsuarioSesion } from '../../common/types/session.types';
import { PasswordService } from '../usuarios/password.service';
import { Usuario } from '../usuarios/usuario.entity';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import { LoginDto } from './auth.dto';

@Injectable()
export class AuthService {
  /** Hash de una contraseña aleatoria: iguala el tiempo de respuesta cuando el correo no existe. */
  private readonly hashSimulado: Promise<string>;

  constructor(
    private readonly usuarios: UsuariosRepository,
    private readonly passwords: PasswordService,
  ) {
    this.hashSimulado = this.passwords.hash(randomBytes(16).toString('hex'));
  }

  /**
   * Verifica las credenciales. El mensaje de error es el mismo si el correo
   * no existe o si la contraseña es incorrecta (no revela qué cuentas existen).
   */
  async autenticar(dto: LoginDto): Promise<UsuarioSesion> {
    const usuario = await this.usuarios.buscarConPasswordPorEmail(
      Usuario.normalizarEmail(dto.email),
    );
    const valida = await this.passwords.verificar(
      usuario?.passwordHash ?? (await this.hashSimulado),
      dto.password,
    );

    if (!usuario || !valida) {
      throw new UnauthorizedException('Correo o contraseña incorrectos.');
    }
    if (!usuario.activo) {
      throw new UnauthorizedException(
        'Tu cuenta está desactivada. Contacta al administrador.',
      );
    }
    return usuario.aSesion();
  }

  /** Confirma que el usuario de la sesión sigue existiendo y activo. */
  async refrescar(sesion: UsuarioSesion): Promise<UsuarioSesion | null> {
    const usuario = await this.usuarios.buscarPorId(sesion.id);
    return usuario && usuario.activo ? usuario.aSesion() : null;
  }
}
