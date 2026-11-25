import { Body, Controller, Param, ParseIntPipe, Post } from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles, UsuarioActual } from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import { CrearReparacionDto } from './reparaciones.dto';
import { ReparacionesService } from './reparaciones.service';

@ApiTags('Servicio 1 · Equipos')
@Roles(Rol.RESPONSABLE, Rol.ADMIN)
@Controller('equipos/:equipoId/reparaciones')
export class ReparacionesController {
  constructor(private readonly reparaciones: ReparacionesService) {}

  @ApiOperation({
    summary:
      'Registra el resultado de la reparación de un equipo enviado a reparar',
  })
  @Post()
  registrar(
    @Param('equipoId', ParseIntPipe) equipoId: number,
    @Body() dto: CrearReparacionDto,
    @UsuarioActual() usuario: UsuarioSesion,
  ) {
    return this.reparaciones.registrar(equipoId, dto, usuario.id);
  }
}
