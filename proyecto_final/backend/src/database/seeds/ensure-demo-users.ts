import { Logger } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { Rol } from '../../common/enums';
import { PasswordService } from '../../modules/usuarios/password.service';
import { Usuario } from '../../modules/usuarios/usuario.entity';

/** Crea solo las cuentas faltantes; conserva contraseñas, roles y estado existentes. */
export async function asegurarUsuariosDemo(
  ds: DataSource,
  passwords: PasswordService,
  password: string,
): Promise<void> {
  const repo = ds.getRepository(Usuario);
  const cuentas = [
    {
      nombre: 'Ana Administradora',
      email: 'admin@ewaste360.mx',
      rol: Rol.ADMIN,
    },
    {
      nombre: 'Rubén Responsable',
      email: 'responsable@ewaste360.mx',
      rol: Rol.RESPONSABLE,
    },
    {
      nombre: 'Carla Capturista',
      email: 'capturista@ewaste360.mx',
      rol: Rol.CAPTURISTA,
    },
  ];

  for (const cuenta of cuentas) {
    if (await repo.existsBy({ email: cuenta.email })) continue;
    // ON CONFLICT DO NOTHING también permite arranques simultáneos.
    const resultado = await repo
      .createQueryBuilder()
      .insert()
      .values({
        ...cuenta,
        activo: true,
        passwordHash: await passwords.hash(password),
      })
      .orIgnore()
      .returning('id')
      .execute();
    if ((resultado.raw as unknown[]).length > 0) {
      Logger.log(`Cuenta demo creada: ${cuenta.email}`, 'Bootstrap');
    }
  }
}
