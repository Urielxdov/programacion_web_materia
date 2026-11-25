import { existsSync } from 'fs';
import { join } from 'path';
import { DynamicModule, Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { APP_FILTER, APP_GUARD } from '@nestjs/core';
import { EventEmitterModule } from '@nestjs/event-emitter';
import { ServeStaticModule } from '@nestjs/serve-static';
import { ThrottlerGuard, ThrottlerModule } from '@nestjs/throttler';
import { TypeOrmModule } from '@nestjs/typeorm';
import { HttpExceptionFilter } from './common/filters/http-exception.filter';
import { CsrfGuard } from './common/guards/csrf.guard';
import { RolesGuard } from './common/guards/roles.guard';
import { SessionAuthGuard } from './common/guards/session-auth.guard';
import { validateEnv } from './config/env.validation';
import { crearOpcionesTypeOrm } from './database/typeorm.config';
import { AuthModule } from './modules/auth/auth.module';
import { CampanasModule } from './modules/campanas/campanas.module';
import { CatalogosModule } from './modules/catalogos/catalogos.module';
import { DiagnosticosModule } from './modules/diagnosticos/diagnosticos.module';
import { EquiposModule } from './modules/equipos/equipos.module';
import { IndicadoresModule } from './modules/indicadores/indicadores.module';
import { MovimientosModule } from './modules/movimientos/movimientos.module';
import { ReparacionesModule } from './modules/reparaciones/reparaciones.module';
import { UsuariosModule } from './modules/usuarios/usuarios.module';

/** Carpeta del build de React. En producción Nest sirve el frontend desde el mismo dominio. */
const FRONTEND_DIST = join(__dirname, '..', '..', 'frontend', 'dist');

function frontendEstatico(): DynamicModule[] {
  if (!existsSync(FRONTEND_DIST)) return [];
  return [
    ServeStaticModule.forRoot({
      rootPath: FRONTEND_DIST,
      exclude: ['/api/{*ruta}'],
    }),
  ];
}

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true, validate: validateEnv }),
    TypeOrmModule.forRootAsync({ useFactory: () => crearOpcionesTypeOrm() }),
    EventEmitterModule.forRoot({ wildcard: true, delimiter: '.' }),
    ThrottlerModule.forRoot({
      throttlers: [{ ttl: 60_000, limit: 200 }],
      // En las pruebas automatizadas se hacen muchos logins seguidos.
      skipIf: () => process.env.NODE_ENV === 'test',
    }),
    ...frontendEstatico(),
    AuthModule,
    UsuariosModule,
    CatalogosModule,
    EquiposModule,
    DiagnosticosModule,
    ReparacionesModule,
    MovimientosModule,
    CampanasModule,
    IndicadoresModule,
  ],
  providers: [
    // Guards globales (se ejecutan en este orden en cada petición).
    { provide: APP_GUARD, useClass: ThrottlerGuard },
    { provide: APP_GUARD, useClass: SessionAuthGuard },
    { provide: APP_GUARD, useClass: RolesGuard },
    { provide: APP_GUARD, useClass: CsrfGuard },
    { provide: APP_FILTER, useClass: HttpExceptionFilter },
  ],
})
export class AppModule {}
