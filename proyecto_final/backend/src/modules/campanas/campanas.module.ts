import { Module } from '@nestjs/common';
import { CatalogosModule } from '../catalogos/catalogos.module';
import { UsuariosModule } from '../usuarios/usuarios.module';
import { CampanasController } from './campanas.controller';
import {
  CampanasRepository,
  ParticipacionesRepository,
} from './campanas.repository';
import { CampanasService } from './campanas.service';

@Module({
  imports: [CatalogosModule, UsuariosModule],
  controllers: [CampanasController],
  providers: [CampanasRepository, ParticipacionesRepository, CampanasService],
  exports: [CampanasRepository],
})
export class CampanasModule {}
