import { Module } from '@nestjs/common';
import { CampanasModule } from '../campanas/campanas.module';
import { CatalogosModule } from '../catalogos/catalogos.module';
import { UsuariosModule } from '../usuarios/usuarios.module';
import { EquiposController } from './equipos.controller';
import { EquiposRepository } from './equipos.repository';
import { EquiposService } from './equipos.service';
import { EquipoFactory } from './factory/equipo.factory';

@Module({
  imports: [CatalogosModule, UsuariosModule, CampanasModule],
  controllers: [EquiposController],
  providers: [EquiposRepository, EquiposService, EquipoFactory],
  exports: [EquiposRepository, EquipoFactory],
})
export class EquiposModule {}
