import { Module } from '@nestjs/common';
import { UsuariosModule } from '../usuarios/usuarios.module';
import {
  CategoriasController,
  GestoresController,
} from './catalogos.controller';
import {
  CategoriasRepository,
  GestoresRepository,
} from './catalogos.repository';
import { CategoriasService, GestoresService } from './catalogos.service';

@Module({
  imports: [UsuariosModule],
  controllers: [CategoriasController, GestoresController],
  providers: [
    CategoriasRepository,
    GestoresRepository,
    CategoriasService,
    GestoresService,
  ],
  exports: [CategoriasRepository, GestoresRepository],
})
export class CatalogosModule {}
