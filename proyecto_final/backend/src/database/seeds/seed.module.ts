import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { EventEmitterModule } from '@nestjs/event-emitter';
import { TypeOrmModule } from '@nestjs/typeorm';
import { CampanasModule } from '../../modules/campanas/campanas.module';
import { CatalogosModule } from '../../modules/catalogos/catalogos.module';
import { DiagnosticosModule } from '../../modules/diagnosticos/diagnosticos.module';
import { EquiposModule } from '../../modules/equipos/equipos.module';
import { MovimientosModule } from '../../modules/movimientos/movimientos.module';
import { ReparacionesModule } from '../../modules/reparaciones/reparaciones.module';
import { UsuariosModule } from '../../modules/usuarios/usuarios.module';
import { crearOpcionesTypeOrm } from '../typeorm.config';

/** Módulo mínimo (sin HTTP) para poblar la base con datos de demostración usando los servicios reales. */
@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    TypeOrmModule.forRootAsync({ useFactory: () => crearOpcionesTypeOrm() }),
    EventEmitterModule.forRoot({ wildcard: true, delimiter: '.' }),
    UsuariosModule,
    CatalogosModule,
    CampanasModule,
    EquiposModule,
    DiagnosticosModule,
    ReparacionesModule,
    MovimientosModule,
  ],
})
export class SeedModule {}
