import { Module } from '@nestjs/common';
import { CatalogosModule } from '../catalogos/catalogos.module';
import { EquiposModule } from '../equipos/equipos.module';
import { UsuariosModule } from '../usuarios/usuarios.module';
import { MovimientosController } from './movimientos.controller';
import { MovimientosListener } from './movimientos.listener';
import { MovimientosRepository } from './movimientos.repository';
import { MovimientosService } from './movimientos.service';

@Module({
  imports: [EquiposModule, CatalogosModule, UsuariosModule],
  controllers: [MovimientosController],
  providers: [MovimientosRepository, MovimientosService, MovimientosListener],
})
export class MovimientosModule {}
