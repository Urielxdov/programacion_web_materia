import {
  Body,
  Controller,
  Get,
  Param,
  ParseIntPipe,
  Patch,
  Post,
} from '@nestjs/common';
import { ApiTags } from '@nestjs/swagger';
import { Roles, UsuarioActual } from '../../common/decorators/auth.decorators';
import { Rol } from '../../common/enums';
import type { UsuarioSesion } from '../../common/types/session.types';
import { ActualizarUsuarioDto, CrearUsuarioDto } from './usuarios.dto';
import { UsuariosService } from './usuarios.service';

@ApiTags('Usuarios (admin)')
@Roles(Rol.ADMIN)
@Controller('usuarios')
export class UsuariosController {
  constructor(private readonly usuarios: UsuariosService) {}

  @Get()
  listar() {
    return this.usuarios.listar();
  }

  @Get(':id')
  obtener(@Param('id', ParseIntPipe) id: number) {
    return this.usuarios.obtener(id);
  }

  @Post()
  crear(@Body() dto: CrearUsuarioDto) {
    return this.usuarios.crear(dto);
  }

  @Patch(':id')
  actualizar(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: ActualizarUsuarioDto,
    @UsuarioActual() actual: UsuarioSesion,
  ) {
    return this.usuarios.actualizar(id, dto, actual.id);
  }
}
