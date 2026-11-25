import { config } from 'dotenv';

// Las pruebas e2e usan una base de datos separada para no tocar los datos de desarrollo.
config();
process.env.NODE_ENV = 'test';
process.env.DATABASE_URL =
  process.env.DATABASE_URL_TEST ??
  'postgres://ewaste:ewaste_dev@localhost:5544/ewaste360_test';
