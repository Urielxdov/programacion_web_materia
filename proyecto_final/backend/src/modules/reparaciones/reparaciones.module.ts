import { Module } from '@nestjs/common';
import { DiagnosticosModule } from '../diagnosticos/diagnosticos.module';
import { EquiposModule } from '../equipos/equipos.module';
import { UsuariosModule } from '../usuarios/usuarios.module';
import { ReparacionesController } from './reparaciones.controller';
import { ReparacionesRepository } from './reparaciones.repository';
import { ReparacionesService } from './reparaciones.service';

@Module({
  imports: [EquiposModule, DiagnosticosModule, UsuariosModule],
  controllers: [ReparacionesController],
  providers: [ReparacionesRepository, ReparacionesService],
})
export class ReparacionesModule {}
