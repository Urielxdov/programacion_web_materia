import {
  Body,
  Controller,
  Get,
  HttpCode,
  Post,
  Req,
  Res,
} from '@nestjs/common';
import { ApiTags } from '@nestjs/swagger';
import { Throttle } from '@nestjs/throttler';
import type { Request, Response } from 'express';
import { Public } from '../../common/decorators/auth.decorators';
import type { UsuarioSesion } from '../../common/types/session.types';
import { LoginDto } from './auth.dto';
import { AuthService } from './auth.service';
import { CsrfService } from './csrf.service';

export const NOMBRE_COOKIE_SESION = 'ew.sid';

/** Envuelve los métodos de express-session basados en callbacks en promesas. */
const promesa = (fn: (cb: (err?: unknown) => void) => void) =>
  new Promise<void>((resolve, reject) =>
    fn((err) =>
      err
        ? reject(
            err instanceof Error
              ? err
              : new Error('Error al manejar la sesión.'),
          )
        : resolve(),
    ),
  );

@ApiTags('Autenticación')
@Controller('auth')
export class AuthController {
  constructor(
    private readonly auth: AuthService,
    private readonly csrf: CsrfService,
  ) {}

  /** Entrega un token CSRF nuevo; el cliente lo manda en X-CSRF-Token. */
  @Public()
  @Get('csrf')
  token(@Req() req: Request, @Res({ passthrough: true }) res: Response) {
    return { csrfToken: this.csrf.generar(req, res) };
  }

  @Public()
  @Throttle({ default: { limit: 8, ttl: 60_000 } })
  @Post('login')
  @HttpCode(200)
  async login(
    @Body() dto: LoginDto,
    @Req() req: Request,
    @Res({ passthrough: true }) res: Response,
  ): Promise<{ usuario: UsuarioSesion; csrfToken: string }> {
    const usuario = await this.auth.autenticar(dto);
    // Se regenera el id de sesión para evitar fijación de sesión.
    await promesa((cb) => req.session.regenerate(cb));
    req.session.usuario = usuario;
    await promesa((cb) => req.session.save(cb));
    return { usuario, csrfToken: this.csrf.generar(req, res) };
  }

  @Post('logout')
  @HttpCode(200)
  async logout(@Req() req: Request, @Res({ passthrough: true }) res: Response) {
    await promesa((cb) => req.session.destroy(cb));
    res.clearCookie(NOMBRE_COOKIE_SESION, { path: '/' });
    return { ok: true };
  }

  /** Devuelve el usuario de la sesión actual, o null si no hay sesión. */
  @Public()
  @Get('me')
  async me(@Req() req: Request): Promise<{ usuario: UsuarioSesion | null }> {
    const actual = req.session?.usuario;
    if (!actual) return { usuario: null };
    const refrescado = await this.auth.refrescar(actual);
    if (!refrescado) {
      await promesa((cb) => req.session.destroy(cb));
      return { usuario: null };
    }
    req.session.usuario = refrescado;
    return { usuario: refrescado };
  }
}
