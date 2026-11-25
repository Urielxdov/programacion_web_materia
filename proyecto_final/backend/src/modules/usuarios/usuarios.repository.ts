import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { RepositorioBase } from '../../common/repositories/repositorio-base';
import { Rol } from '../../common/enums';
import { Usuario } from './usuario.entity';

@Injectable()
export class UsuariosRepository extends RepositorioBase<Usuario> {
  constructor(dataSource: DataSource) {
    super(dataSource, Usuario, 'El usuario');
  }

  listar(): Promise<Usuario[]> {
    return this.repo.find({ order: { nombre: 'ASC' } });
  }

  existeEmail(email: string): Promise<boolean> {
    return this.repo.exists({ where: { email } });
  }

  /** Única consulta que incluye el hash de la contraseña (solo para autenticar). */
  buscarConPasswordPorEmail(email: string): Promise<Usuario | null> {
    return this.repo
      .createQueryBuilder('u')
      .addSelect('u.passwordHash')
      .where('u.email = :email', { email })
      .getOne();
  }

  contarAdminsActivos(): Promise<number> {
    return this.repo.count({ where: { rol: Rol.ADMIN, activo: true } });
  }
}
