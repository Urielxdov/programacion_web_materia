import {
  Body,
  Controller,
  Get,
  Param,
  ParseIntPipe,
  Patch,
  Post,
  Query,
  Req,
} from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { Throttle } from '@nestjs/throttler';
import type { Request } from 'express';
import {
  Public,
  Roles,
  UsuarioActual,
} from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import {
  ActualizarCampanaDto,
  CrearCampanaDto,
  CrearParticipacionDto,
  FiltroCampanasDto,
} from './campanas.dto';
import { CampanasService } from './campanas.service';

@ApiTags('Campañas')
@Controller('campanas')
export class CampanasController {
  constructor(private readonly campanas: CampanasService) {}

  @ApiOperation({ summary: 'Lista pública de campañas con sus totales' })
  @Public()
  @Get()
  listar(@Query() filtro: FiltroCampanasDto) {
    return this.campanas.listar(filtro.estado);
  }

  @Public()
  @Get(':id')
  detalle(@Param('id', ParseIntPipe) id: number) {
    return this.campanas.detalle(id);
  }

  @Roles(Rol.RESPONSABLE, Rol.ADMIN)
  @Post()
  crear(@Body() dto: CrearCampanaDto, @UsuarioActual() usuario: UsuarioSesion) {
    return this.campanas.crear(dto, usuario.id);
  }

  @Roles(Rol.RESPONSABLE, Rol.ADMIN)
  @Patch(':id')
  actualizar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: ActualizarCampanaDto,
  ) {
    return this.campanas.actualizar(id, dto);
  }

  @ApiOperation({
    summary:
      'Registro público de participación (limitado a 5 por minuto por IP)',
  })
  @Public()
  @Throttle({ default: { limit: 5, ttl: 60_000 } })
  @Post(':id/participaciones')
  participar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: CrearParticipacionDto,
    @Req() req: Request,
  ) {
    return this.campanas.participar(id, dto, req.session?.usuario);
  }

  @Roles(Rol.RESPONSABLE, Rol.ADMIN)
  @Get(':id/participaciones')
  participantes(@Param('id', ParseIntPipe) id: number) {
    return this.campanas.participantes(id);
  }
}
