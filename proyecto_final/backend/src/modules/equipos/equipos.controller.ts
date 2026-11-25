import {
  Body,
  Controller,
  Get,
  Param,
  ParseIntPipe,
  Patch,
  Post,
  Query,
} from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles, UsuarioActual } from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import {
  ActualizarEquipoDto,
  CrearEquipoDto,
  FiltrosEquiposDto,
} from './equipos.dto';
import { EquiposService } from './equipos.service';

/**
 * Servicio web 1: API de equipos.
 * Todos los endpoints requieren sesión; la escritura está limitada por rol.
 */
@ApiTags('Servicio 1 · Equipos')
@Roles(Rol.CAPTURISTA, Rol.RESPONSABLE, Rol.ADMIN)
@Controller('equipos')
export class EquiposController {
  constructor(private readonly equipos: EquiposService) {}

  @ApiOperation({
    summary:
      'Consulta equipos registrados (con búsqueda, filtros y paginación)',
  })
  @Get()
  buscar(@Query() filtros: FiltrosEquiposDto) {
    return this.equipos.buscar(filtros);
  }

  @ApiOperation({ summary: 'Detalle de un equipo con su historial completo' })
  @Get(':id')
  detalle(@Param('id', ParseIntPipe) id: number) {
    return this.equipos.detalle(id);
  }

  @ApiOperation({ summary: 'Registra un equipo (se le asigna un folio único)' })
  @Post()
  registrar(
    @Body() dto: CrearEquipoDto,
    @UsuarioActual() usuario: UsuarioSesion,
  ) {
    return this.equipos.registrar(dto, usuario.id);
  }

  @ApiOperation({ summary: 'Actualiza los campos permitidos de un equipo' })
  @Patch(':id')
  actualizar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: ActualizarEquipoDto,
  ) {
    return this.equipos.actualizar(id, dto);
  }
}
