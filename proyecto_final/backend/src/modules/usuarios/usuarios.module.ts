import { Module } from '@nestjs/common';
import { PasswordService } from './password.service';
import { UsuariosController } from './usuarios.controller';
import { UsuariosRepository } from './usuarios.repository';
import { UsuariosService } from './usuarios.service';

@Module({
  controllers: [UsuariosController],
  providers: [UsuariosRepository, UsuariosService, PasswordService],
  exports: [UsuariosRepository, UsuariosService, PasswordService],
})
export class UsuariosModule {}
