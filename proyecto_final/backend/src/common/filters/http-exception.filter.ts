import {
  ArgumentsHost,
  Catch,
  ExceptionFilter,
  HttpException,
  HttpStatus,
  Logger,
} from '@nestjs/common';
import type { Request, Response } from 'express';
import { QueryFailedError } from 'typeorm';
import { ReglaNegocioError } from '../errors/regla-negocio.error';

interface RespuestaError {
  statusCode: number;
  error: string;
  message: string | string[];
  path: string;
  timestamp: string;
}

/**
 * Filtro global de errores: responde siempre JSON con un formato uniforme
 * y nunca expone stack traces, SQL ni credenciales al cliente.
 */
@Catch()
export class HttpExceptionFilter implements ExceptionFilter {
  private readonly logger = new Logger(HttpExceptionFilter.name);

  catch(exception: unknown, host: ArgumentsHost) {
    const ctx = host.switchToHttp();
    const res = ctx.getResponse<Response>();
    const req = ctx.getRequest<Request>();

    let status: number = HttpStatus.INTERNAL_SERVER_ERROR;
    let message: string | string[] =
      'Ocurrió un error inesperado. Intenta de nuevo.';

    if (exception instanceof HttpException) {
      status = exception.getStatus();
      const body = exception.getResponse();
      message =
        typeof body === 'string'
          ? body
          : ((body as { message?: string | string[] }).message ??
            exception.message);
    } else if (exception instanceof ReglaNegocioError) {
      status = HttpStatus.UNPROCESSABLE_ENTITY;
      message = exception.message;
    } else if (exception instanceof QueryFailedError) {
      const code = (exception as QueryFailedError & { code?: string }).code;
      if (code === '23505') {
        status = HttpStatus.CONFLICT;
        message = 'Ya existe un registro con esos datos.';
      } else if (code === '23503') {
        status = HttpStatus.CONFLICT;
        message =
          'El registro está relacionado con otros datos y no puede modificarse así.';
      } else if (code === '23514') {
        status = HttpStatus.BAD_REQUEST;
        message = 'Los datos no cumplen las reglas de integridad.';
      }
    }

    if (status >= 500) {
      // Se registra el detalle solo en el servidor.
      this.logger.error(
        exception instanceof Error ? exception.stack : exception,
      );
    }

    const payload: RespuestaError = {
      statusCode: status,
      error: HttpStatus[status] ?? 'Error',
      message,
      path: req.url,
      timestamp: new Date().toISOString(),
    };
    res.status(status).json(payload);
  }
}
