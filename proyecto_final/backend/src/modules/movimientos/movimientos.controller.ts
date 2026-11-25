import {
  Body,
  Controller,
  DefaultValuePipe,
  Get,
  Param,
  ParseIntPipe,
  Post,
  Query,
} from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles, UsuarioActual } from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import { RegistrarDecisionDto } from './movimientos.dto';
import { MovimientosService } from './movimientos.service';

@ApiTags('Servicio 1 · Equipos')
@Controller()
export class MovimientosController {
  constructor(private readonly movimientos: MovimientosService) {}

  @ApiOperation({ summary: 'Historial (trazabilidad) de un equipo' })
  @Roles(Rol.CAPTURISTA, Rol.RESPONSABLE, Rol.ADMIN)
  @Get('equipos/:equipoId/movimientos')
  historial(@Param('equipoId', ParseIntPipe) equipoId: number) {
    return this.movimientos.historial(equipoId);
  }

  @ApiOperation({
    summary:
      'Registra una decisión de destino: reparar, reasignar, conservar o entregar a gestor',
  })
  @Roles(Rol.RESPONSABLE, Rol.ADMIN)
  @Post('equipos/:equipoId/movimientos')
  decidir(
    @Param('equipoId', ParseIntPipe) equipoId: number,
    @Body() dto: RegistrarDecisionDto,
    @UsuarioActual() usuario: UsuarioSesion,
  ) {
    return this.movimientos.registrarDecision(equipoId, dto, usuario.id);
  }

  @ApiOperation({ summary: 'Actividad reciente en el inventario' })
  @Roles(Rol.CAPTURISTA, Rol.RESPONSABLE, Rol.ADMIN)
  @Get('movimientos/recientes')
  recientes(
    @Query('limite', new DefaultValuePipe(8), ParseIntPipe) limite: number,
  ) {
    return this.movimientos.recientes(Math.min(Math.max(limite, 1), 50));
  }
}
