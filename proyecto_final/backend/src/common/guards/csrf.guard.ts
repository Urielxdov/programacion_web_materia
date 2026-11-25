import {
  CanActivate,
  ExecutionContext,
  ForbiddenException,
  Injectable,
} from '@nestjs/common';
import type { Request } from 'express';
import { CsrfService } from '../../modules/auth/csrf.service';

const METODOS_SEGUROS = new Set(['GET', 'HEAD', 'OPTIONS']);

/**
 * Guard global: toda petición que modifica datos (POST, PATCH, PUT, DELETE)
 * debe incluir un token CSRF válido en el encabezado X-CSRF-Token.
 */
@Injectable()
export class CsrfGuard implements CanActivate {
  constructor(private readonly csrf: CsrfService) {}

  canActivate(context: ExecutionContext): boolean {
    const req = context.switchToHttp().getRequest<Request>();
    if (METODOS_SEGUROS.has(req.method)) return true;
    if (!this.csrf.esValida(req)) {
      throw new ForbiddenException({
        message: 'La sesión del formulario expiró. Intenta de nuevo.',
        code: 'CSRF_INVALIDO',
      });
    }
    return true;
  }
}
