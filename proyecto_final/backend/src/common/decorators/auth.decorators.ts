import {
  createParamDecorator,
  ExecutionContext,
  SetMetadata,
} from '@nestjs/common';
import type { Request } from 'express';
import { Rol } from '../enums';
import type { UsuarioSesion } from '../types/session.types';

export const IS_PUBLIC_KEY = 'isPublic';
export const ROLES_KEY = 'roles';

/** Marca un endpoint como público (no requiere sesión). Todo lo demás es privado por defecto. */
export const Public = () => SetMetadata(IS_PUBLIC_KEY, true);

/** Restringe un endpoint a los roles indicados (se valida en el servidor con RolesGuard). */
export const Roles = (...roles: Rol[]) => SetMetadata(ROLES_KEY, roles);

/** Inyecta en el controlador el usuario de la sesión actual. */
export const UsuarioActual = createParamDecorator(
  (_data: unknown, ctx: ExecutionContext): UsuarioSesion | undefined => {
    const req = ctx.switchToHttp().getRequest<Request>();
    return req.session?.usuario;
  },
);
