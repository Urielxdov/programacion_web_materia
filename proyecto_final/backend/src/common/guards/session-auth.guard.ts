import {
  CanActivate,
  ExecutionContext,
  Injectable,
  UnauthorizedException,
} from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import type { Request } from 'express';
import { UsuariosRepository } from '../../modules/usuarios/usuarios.repository';
import { IS_PUBLIC_KEY } from '../decorators/auth.decorators';
import '../types/session.types';

/**
 * Guard global: exige una sesión válida en el servidor para cualquier
 * endpoint que no esté marcado con @Public().
 *
 * En cada petición confirma que la cuenta siga activa y toma su rol vigente:
 * si el administrador desactiva una cuenta o le cambia el rol, el cambio
 * aplica de inmediato aunque la persona ya tuviera una sesión abierta.
 */
@Injectable()
export class SessionAuthGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly usuarios: UsuariosRepository,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const esPublico = this.reflector.getAllAndOverride<boolean>(IS_PUBLIC_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);
    if (esPublico) return true;

    const req = context.switchToHttp().getRequest<Request>();
    const sesion = req.session?.usuario;
    if (!sesion) {
      throw new UnauthorizedException('Debes iniciar sesión para continuar.');
    }

    const usuario = await this.usuarios.buscarPorId(sesion.id);
    if (!usuario || !usuario.activo) {
      await new Promise<void>((resolve) =>
        req.session.destroy(() => resolve()),
      );
      throw new UnauthorizedException(
        'Tu sesión ya no es válida. Inicia sesión de nuevo.',
      );
    }
    req.session.usuario = usuario.aSesion();
    return true;
  }
}
