import {
  Body,
  Controller,
  Get,
  Param,
  ParseBoolPipe,
  ParseIntPipe,
  Patch,
  Post,
  Query,
} from '@nestjs/common';
import { ApiQuery, ApiTags } from '@nestjs/swagger';
import {
  Public,
  Roles,
  UsuarioActual,
} from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import {
  ActualizarCategoriaDto,
  ActualizarGestorDto,
  CrearCategoriaDto,
  CrearGestorDto,
  VerificarGestorDto,
} from './catalogos.dto';
import { CategoriasService, GestoresService } from './catalogos.service';

@ApiTags('Catálogos')
@Controller('catalogos/categorias')
export class CategoriasController {
  constructor(private readonly categorias: CategoriasService) {}

  /** Público: la página de campañas muestra los tipos de equipo aceptados. */
  @Public()
  @Get()
  listar() {
    return this.categorias.listar();
  }

  @Roles(Rol.ADMIN)
  @Post()
  crear(@Body() dto: CrearCategoriaDto) {
    return this.categorias.crear(dto);
  }

  @Roles(Rol.ADMIN)
  @Patch(':id')
  actualizar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: ActualizarCategoriaDto,
  ) {
    return this.categorias.actualizar(id, dto);
  }
}

@ApiTags('Catálogos')
@Roles(Rol.RESPONSABLE, Rol.ADMIN)
@Controller('catalogos/gestores')
export class GestoresController {
  constructor(private readonly gestores: GestoresService) {}

  @ApiQuery({ name: 'verificados', required: false, type: Boolean })
  @Get()
  listar(
    @Query('verificados', new ParseBoolPipe({ optional: true }))
    verificados?: boolean,
  ) {
    return this.gestores.listar(verificados ?? false);
  }

  @Post()
  crear(@Body() dto: CrearGestorDto) {
    return this.gestores.crear(dto);
  }

  @Patch(':id')
  actualizar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: ActualizarGestorDto,
  ) {
    return this.gestores.actualizar(id, dto);
  }

  /** Solo el administrador puede verificar o revocar a un gestor. */
  @Roles(Rol.ADMIN)
  @Post(':id/verificar')
  verificar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: VerificarGestorDto,
    @UsuarioActual() usuario: UsuarioSesion,
  ) {
    return this.gestores.verificar(id, dto, usuario.id);
  }

  @Roles(Rol.ADMIN)
  @Post(':id/revocar')
  revocar(@Param('id', ParseIntPipe) id: number) {
    return this.gestores.revocar(id);
  }
}
