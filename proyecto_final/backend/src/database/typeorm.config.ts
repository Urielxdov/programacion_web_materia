import { join } from 'path';
import type { DataSourceOptions } from 'typeorm';
import { SnakeNamingStrategy } from './snake-naming.strategy';

/**
 * Configuración única de TypeORM, compartida por la aplicación Nest,
 * la CLI de migraciones y el script de datos de prueba (seed).
 */
export function crearOpcionesTypeOrm(
  env: NodeJS.ProcessEnv = process.env,
): DataSourceOptions {
  return {
    type: 'postgres',
    url: env.DATABASE_URL,
    ssl: env.DB_SSL === 'true' ? { rejectUnauthorized: false } : false,
    entities: [join(__dirname, '..', 'modules', '**', '*.entity.{ts,js}')],
    migrations: [join(__dirname, 'migrations', '*.{ts,js}')],
    namingStrategy: new SnakeNamingStrategy(),
    // El esquema solo cambia mediante migraciones versionadas.
    synchronize: false,
    migrationsRun: true,
    logging: env.NODE_ENV === 'development' ? ['error', 'warn'] : ['error'],
  };
}
