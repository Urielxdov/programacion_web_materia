import { Logger } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { PasswordService } from '../../modules/usuarios/password.service';
import { asegurarUsuariosDemo } from './ensure-demo-users';

describe('asegurarUsuariosDemo', () => {
  const existentes = new Set<string>();
  const filas: Record<string, unknown>[] = [];
  const valores = jest
    .fn<unknown, [Record<string, unknown>]>()
    .mockReturnThis();
  const builder = {
    insert: jest.fn().mockReturnThis(),
    values: valores,
    orIgnore: jest.fn().mockReturnThis(),
    returning: jest.fn().mockReturnThis(),
    execute: jest.fn(() => {
      const pendiente = valores.mock.calls.at(-1)![0];
      existentes.add(pendiente.email as string);
      filas.push(pendiente);
      return Promise.resolve({ raw: [{ id: filas.length }] });
    }),
  };
  const repo = {
    existsBy: jest.fn(({ email }: { email: string }) =>
      Promise.resolve(existentes.has(email)),
    ),
    createQueryBuilder: jest.fn(() => builder),
  };
  const ds = { getRepository: () => repo } as unknown as DataSource;
  const hash = jest.fn((password: string) =>
    Promise.resolve(`hash:${password}`),
  );
  const passwords = { hash } as unknown as PasswordService;

  beforeEach(() => {
    existentes.clear();
    filas.length = 0;
    jest.clearAllMocks();
    jest.spyOn(Logger, 'log').mockImplementation(() => undefined);
  });

  afterEach(() => jest.restoreAllMocks());

  it('crea las tres cuentas con la contraseña configurada y no las modifica al reiniciar', async () => {
    await asegurarUsuariosDemo(ds, passwords, 'Inicial2026!');
    expect(filas).toHaveLength(3);
    expect(
      filas.every(
        (fila) =>
          fila.passwordHash === 'hash:Inicial2026!' && fila.activo === true,
      ),
    ).toBe(true);
    await asegurarUsuariosDemo(ds, passwords, 'Otra2026!');
    expect(filas).toHaveLength(3);
    expect(hash).toHaveBeenCalledTimes(3);
  });

  it('completa cuentas faltantes aunque ya existan otros usuarios', async () => {
    existentes.add('admin@ewaste360.mx');
    existentes.add('otro@example.com');
    await asegurarUsuariosDemo(ds, passwords, 'Inicial2026!');
    expect(filas.map((fila) => fila.email)).toEqual([
      'responsable@ewaste360.mx',
      'capturista@ewaste360.mx',
    ]);
    expect(builder.orIgnore).toHaveBeenCalledTimes(2);
  });

  it('propaga los errores de base de datos para impedir un arranque incompleto', async () => {
    repo.existsBy.mockRejectedValueOnce(new Error('Sin conexión'));
    await expect(
      asegurarUsuariosDemo(ds, passwords, 'Inicial2026!'),
    ).rejects.toThrow('Sin conexión');
    expect(hash).not.toHaveBeenCalled();
  });
});
