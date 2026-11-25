import { Controller, Get } from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { Public, Roles } from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import { IndicadoresService } from './indicadores.service';

/** Servicio web 2: API de indicadores. */
@ApiTags('Servicio 2 · Indicadores')
@Controller('indicadores')
export class IndicadoresController {
  constructor(private readonly indicadores: IndicadoresService) {}

  @ApiOperation({
    summary:
      'Totales de equipos por estado, condición, ahorro e impacto ambiental',
  })
  @Roles(Rol.CAPTURISTA, Rol.RESPONSABLE, Rol.ADMIN)
  @Get()
  resumen() {
    return this.indicadores.resumen();
  }

  @ApiOperation({ summary: 'Resultados por campaña de recolección' })
  @Roles(Rol.RESPONSABLE, Rol.ADMIN)
  @Get('campanas')
  campanas() {
    return this.indicadores.campanas();
  }

  @ApiOperation({ summary: 'Métricas de reparación y reutilización' })
  @Roles(Rol.RESPONSABLE, Rol.ADMIN)
  @Get('reutilizacion')
  reutilizacion() {
    return this.indicadores.reutilizacion();
  }

  @ApiOperation({ summary: 'Cifras públicas agregadas (página de inicio)' })
  @Public()
  @Get('publico')
  publico() {
    return this.indicadores.publico();
  }
}
