import 'reflect-metadata';
import { Logger } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { NestExpressApplication } from '@nestjs/platform-express';
import { AppModule } from './app.module';
import { configurarApp } from './app.setup';

async function bootstrap() {
  const app = await NestFactory.create<NestExpressApplication>(AppModule);
  configurarApp(app);
  const puerto = Number(process.env.PORT ?? 3000);
  await app.listen(puerto, '0.0.0.0');
  Logger.log(
    `E-Waste 360° escuchando en http://localhost:${puerto} (API en /api, docs en /api/docs)`,
    'Bootstrap',
  );
}

void bootstrap();
