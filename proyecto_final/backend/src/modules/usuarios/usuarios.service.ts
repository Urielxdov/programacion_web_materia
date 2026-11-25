import { ConflictException, Injectable } from '@nestjs/common';
import { Rol } from '../../common/enums';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { PasswordService } from './password.service';
import { Usuario } from './usuario.entity';
import { ActualizarUsuarioDto, CrearUsuarioDto } from './usuarios.dto';
import { UsuariosRepository } from './usuarios.repository';

@Injectable()
export class UsuariosService {
  constructor(
    private readonly usuarios: UsuariosRepository,
    private readonly passwords: PasswordService,
  ) {}

  listar(): Promise<Usuario[]> {
    return this.usuarios.listar();
  }

  obtener(id: number): Promise<Usuario> {
    return this.usuarios.obtenerPorId(id);
  }

  async crear(dto: CrearUsuarioDto): Promise<Usuario> {
    const email = Usuario.normalizarEmail(dto.email);
    if (await this.usuarios.existeEmail(email)) {
      throw new ConflictException('Ya existe una cuenta con ese correo.');
    }
    const usuario = this.usuarios.crear({
      nombre: dto.nombre,
      email,
      rol: dto.rol,
      activo: true,
      passwordHash: await this.passwords.hash(dto.password),
    });
    const guardado = await this.usuarios.guardar(usuario);
    return this.usuarios.obtenerPorId(guardado.id);
  }

  async actualizar(
    id: number,
    dto: ActualizarUsuarioDto,
    idActual: number,
  ): Promise<Usuario> {
    const usuario = await this.usuarios.obtenerPorId(id);

    const pierdeAdmin =
      usuario.rol === Rol.ADMIN &&
      ((dto.rol && dto.rol !== Rol.ADMIN) || dto.activo === false);
    if (pierdeAdmin) {
      if (id === idActual) {
        throw new ReglaNegocioError(
          'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.',
        );
      }
      if ((await this.usuarios.contarAdminsActivos()) <= 1) {
        throw new ReglaNegocioError(
          'Debe existir al menos un administrador activo.',
        );
      }
    }

    if (dto.email) {
      const email = Usuario.normalizarEmail(dto.email);
      if (email !== usuario.email && (await this.usuarios.existeEmail(email))) {
        throw new ConflictException('Ya existe una cuenta con ese correo.');
      }
      usuario.email = email;
    }
    if (dto.nombre) usuario.nombre = dto.nombre;
    if (dto.rol) usuario.rol = dto.rol;
    if (dto.activo !== undefined) usuario.activo = dto.activo;
    if (dto.password)
      usuario.passwordHash = await this.passwords.hash(dto.password);

    await this.usuarios.guardar(usuario);
    return this.usuarios.obtenerPorId(id);
  }
}
