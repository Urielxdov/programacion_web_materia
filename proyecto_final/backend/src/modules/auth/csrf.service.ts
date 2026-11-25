import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { doubleCsrf, DoubleCsrfUtilities } from 'csrf-csrf';
import type { Request, Response } from 'express';
import '../../common/types/session.types';

/**
 * Protección CSRF con el patrón "double submit cookie" firmado (HMAC).
 * El token queda ligado a la sesión del usuario autenticado, así que se
 * renueva al iniciar o cerrar sesión.
 */
@Injectable()
export class CsrfService {
  private readonly utilidades: DoubleCsrfUtilities;

  constructor(config: ConfigService) {
    const produccion = config.get('NODE_ENV') === 'production';
    this.utilidades = doubleCsrf({
      getSecret: () => config.getOrThrow<string>('CSRF_SECRET'),
      getSessionIdentifier: (req) =>
        req.session?.usuario
          ? `${req.session.id}:${req.session.usuario.id}`
          : 'anonimo',
      cookieName: produccion ? '__Host-ew.csrf' : 'ew.csrf',
      cookieOptions: {
        sameSite: 'lax',
        secure: produccion,
        path: '/',
        httpOnly: true,
      },
      getCsrfTokenFromRequest: (req) => req.headers['x-csrf-token'],
    });
  }

  generar(req: Request, res: Response): string {
    return this.utilidades.generateCsrfToken(req, res, { overwrite: true });
  }

  esValida(req: Request): boolean {
    return this.utilidades.validateRequest(req);
  }
}
