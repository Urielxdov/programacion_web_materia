import { INestApplication, ValidationPipe } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { NestExpressApplication } from '@nestjs/platform-express';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import connectPgSimple from 'connect-pg-simple';
import cookieParser from 'cookie-parser';
import session from 'express-session';
import helmet from 'helmet';
import { Pool } from 'pg';
import { NOMBRE_COOKIE_SESION } from './modules/auth/auth.controller';

/**
 * Configuración HTTP de la aplicación (seguridad, sesiones, validación, docs).
 * Se comparte entre main.ts y las pruebas e2e para probar exactamente lo mismo.
 */
export function configurarApp(app: INestApplication): void {
  const config = app.get(ConfigService);
  const produccion = config.get('NODE_ENV') === 'production';
  const express = app as NestExpressApplication;

  // Render y otros PaaS terminan HTTPS en un proxy: necesario para cookies "secure" e IPs reales.
  express.set('trust proxy', 1);
  express.disable('x-powered-by');

  app.use(
    helmet({
      contentSecurityPolicy: {
        directives: {
          defaultSrc: ["'self'"],
          scriptSrc: ["'self'"],
          styleSrc: ["'self'", "'unsafe-inline'"],
          imgSrc: ["'self'", 'data:'],
          fontSrc: ["'self'", 'data:'],
          connectSrc: ["'self'"],
          objectSrc: ["'none'"],
          frameAncestors: ["'none'"],
          upgradeInsecureRequests: produccion ? [] : null,
        },
      },
    }),
  );
  app.use(cookieParser());

  // Sesiones guardadas en PostgreSQL (sobreviven reinicios del servidor).
  const PgStore = connectPgSimple(session);
  const pool = new Pool({
    connectionString: config.getOrThrow<string>('DATABASE_URL'),
    ssl:
      config.get('DB_SSL') === 'true'
        ? { rejectUnauthorized: false }
        : undefined,
    max: 5,
  });
  // Libera las conexiones del almacén de sesiones al apagar la aplicación.
  const cerrar = app.close.bind(app);
  app.close = async () => {
    await cerrar();
    await pool.end();
  };
  app.use(
    session({
      name: NOMBRE_COOKIE_SESION,
      secret: config.getOrThrow<string>('SESSION_SECRET'),
      store: new PgStore({
        pool,
        tableName: 'sesiones',
        createTableIfMissing: true,
      }),
      resave: false,
      saveUninitialized: false,
      rolling: true,
      cookie: {
        httpOnly: true,
        secure: produccion,
        sameSite: 'lax',
        maxAge: 1000 * 60 * 60 * 8, // 8 horas de inactividad
      },
    }),
  );

  app.setGlobalPrefix('api');
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: true,
      transform: true,
      stopAtFirstError: true,
    }),
  );

  const documento = SwaggerModule.createDocument(
    app,
    new DocumentBuilder()
      .setTitle('E-Waste 360° API')
      .setDescription(
        'Servicios web REST de E-Waste 360°. Las rutas privadas requieren iniciar sesión (cookie de sesión) ' +
          'y las operaciones de escritura el encabezado X-CSRF-Token obtenido en GET /api/auth/csrf.',
      )
      .setVersion('1.0')
      .addCookieAuth(NOMBRE_COOKIE_SESION)
      .addApiKey({ type: 'apiKey', in: 'header', name: 'X-CSRF-Token' }, 'csrf')
      .build(),
  );
  SwaggerModule.setup('api/docs', app, documento, {
    swaggerOptions: { withCredentials: true },
  });

  app.enableShutdownHooks();
}
