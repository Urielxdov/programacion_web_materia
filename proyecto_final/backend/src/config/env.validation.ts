import { plainToInstance } from 'class-transformer';
import {
  IsBooleanString,
  IsIn,
  IsNumberString,
  IsOptional,
  IsString,
  MinLength,
  validateSync,
} from 'class-validator';

/**
 * Variables de entorno requeridas. Si falta alguna o es inválida,
 * la aplicación no arranca (falla rápido en lugar de fallar en producción).
 */
class EnvironmentVariables {
  @IsIn(['development', 'production', 'test'])
  NODE_ENV: string = 'development';

  @IsOptional()
  @IsNumberString()
  PORT?: string;

  @IsString()
  DATABASE_URL!: string;

  @IsOptional()
  @IsBooleanString()
  DB_SSL?: string;

  @IsString()
  @MinLength(24)
  SESSION_SECRET!: string;

  @IsString()
  @MinLength(24)
  CSRF_SECRET!: string;
}

export function validateEnv(config: Record<string, unknown>) {
  const validated = plainToInstance(EnvironmentVariables, config, {
    enableImplicitConversion: false,
  });
  const errors = validateSync(validated, { skipMissingProperties: false });
  if (errors.length > 0) {
    const detalle = errors
      .map(
        (e) =>
          `${e.property}: ${Object.values(e.constraints ?? {}).join(', ')}`,
      )
      .join('\n');
    throw new Error(`Configuración inválida:\n${detalle}`);
  }
  return config;
}
