import {
  CanActivate,
  ExecutionContext,
  ForbiddenException,
  Injectable,
} from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import type { Request } from 'express';
import { ROLES_KEY } from '../decorators/auth.decorators';
import { Rol } from '../enums';
import '../types/session.types';

/**
 * Guard global: si el endpoint declara @Roles(...), verifica que el rol
 * guardado en la sesión esté permitido. La autorización ocurre en el servidor,
 * no solo ocultando botones en la interfaz.
 */
@Injectable()
export class RolesGuard implements CanActivate {
  constructor(private readonly reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    const permitidos = this.reflector.getAllAndOverride<Rol[] | undefined>(
      ROLES_KEY,
      [context.getHandler(), context.getClass()],
    );
    if (!permitidos || permitidos.length === 0) return true;

    const req = context.switchToHttp().getRequest<Request>();
    const rol = req.session?.usuario?.rol;
    if (!rol || !permitidos.includes(rol)) {
      throw new ForbiddenException(
        'No tienes permiso para realizar esta acción.',
      );
    }
    return true;
  }
}
