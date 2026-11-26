import 'reflect-metadata';
import { Logger } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { NestExpressApplication } from '@nestjs/platform-express';
import { DataSource } from 'typeorm';
import { AppModule } from './app.module';
import { configurarApp } from './app.setup';
import { asegurarUsuariosDemo } from './database/seeds/ensure-demo-users';
import { PasswordService } from './modules/usuarios/password.service';

async function bootstrap() {
  const app = await NestFactory.create<NestExpressApplication>(AppModule);
  if (process.env.SEED_ON_START === 'true') {
    try {
      await asegurarUsuariosDemo(
        app.get(DataSource),
        app.get(PasswordService),
        process.env.SEED_DEMO_PASSWORD || 'Demo2026!',
      );
    } catch (error) {
      await app.close();
      throw error;
    }
  }
  configurarApp(app);
  const puerto = Number(process.env.PORT ?? 3000);
  await app.listen(puerto, '0.0.0.0');
  Logger.log(
    `E-Waste 360° escuchando en http://localhost:${puerto} (API en /api, docs en /api/docs)`,
    'Bootstrap',
  );
}

void bootstrap();
