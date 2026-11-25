import {
  Body,
  Controller,
  HttpCode,
  Param,
  ParseIntPipe,
  Post,
} from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles, UsuarioActual } from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import { CrearDiagnosticoDto, SimularDiagnosticoDto } from './diagnosticos.dto';
import { DiagnosticosService } from './diagnosticos.service';

@ApiTags('Servicio 1 · Equipos')
@Roles(Rol.RESPONSABLE, Rol.ADMIN)
@Controller('equipos/:equipoId/diagnosticos')
export class DiagnosticosController {
  constructor(private readonly diagnosticos: DiagnosticosService) {}

  @ApiOperation({
    summary: 'Calcula la recomendación de destino sin guardar (vista previa)',
  })
  @Post('simular')
  @HttpCode(200)
  simular(
    @Param('equipoId', ParseIntPipe) equipoId: number,
    @Body() dto: SimularDiagnosticoDto,
  ) {
    return this.diagnosticos.simular(equipoId, dto);
  }

  @ApiOperation({
    summary:
      'Registra un diagnóstico; la estrategia de clasificación define la condición',
  })
  @Post()
  registrar(
    @Param('equipoId', ParseIntPipe) equipoId: number,
    @Body() dto: CrearDiagnosticoDto,
    @UsuarioActual() usuario: UsuarioSesion,
  ) {
    return this.diagnosticos.registrar(equipoId, dto, usuario.id);
  }
}
