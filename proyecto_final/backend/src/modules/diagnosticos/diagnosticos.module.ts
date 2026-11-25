import { Module } from '@nestjs/common';
import { EquiposModule } from '../equipos/equipos.module';
import { UsuariosModule } from '../usuarios/usuarios.module';
import { DiagnosticosController } from './diagnosticos.controller';
import { DiagnosticosRepository } from './diagnosticos.repository';
import { DiagnosticosService } from './diagnosticos.service';
import {
  ClasificadorDestino,
  ESTRATEGIAS_CLASIFICACION,
} from './strategies/clasificador-destino';
import {
  GestionEspecializadaStrategy,
  ReparacionNoRentableStrategy,
  ReparacionStrategy,
  ReutilizacionDirectaStrategy,
} from './strategies/estrategia-clasificacion';

@Module({
  imports: [EquiposModule, UsuariosModule],
  controllers: [DiagnosticosController],
  providers: [
    DiagnosticosRepository,
    DiagnosticosService,
    ClasificadorDestino,
    {
      // Para agregar un criterio nuevo basta con registrar otra estrategia aquí.
      provide: ESTRATEGIAS_CLASIFICACION,
      useValue: [
        new GestionEspecializadaStrategy(),
        new ReutilizacionDirectaStrategy(),
        new ReparacionStrategy(),
        new ReparacionNoRentableStrategy(),
      ],
    },
  ],
  exports: [DiagnosticosRepository],
})
export class DiagnosticosModule {}
