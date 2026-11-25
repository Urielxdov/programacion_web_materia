import { Global, Module } from '@nestjs/common';
import { UsuariosModule } from '../usuarios/usuarios.module';
import { AuthController } from './auth.controller';
import { AuthService } from './auth.service';
import { CsrfService } from './csrf.service';

@Global()
@Module({
  imports: [UsuariosModule],
  controllers: [AuthController],
  providers: [AuthService, CsrfService],
  exports: [CsrfService],
})
export class AuthModule {}
