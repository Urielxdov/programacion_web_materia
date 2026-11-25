import 'reflect-metadata';
import { config } from 'dotenv';
import { DataSource } from 'typeorm';
import { crearOpcionesTypeOrm } from './typeorm.config';

config();

/** DataSource usado por la CLI de TypeORM (generar / ejecutar migraciones) y por el seed. */
export default new DataSource({
  ...crearOpcionesTypeOrm(),
  migrationsRun: false,
});
