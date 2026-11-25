import { Injectable } from '@nestjs/common';
import * as argon2 from 'argon2';

/**
 * Encapsula el algoritmo de hash de contraseñas (argon2id).
 * Si mañana se cambia el algoritmo, solo cambia esta clase.
 */
@Injectable()
export class PasswordService {
  hash(plano: string): Promise<string> {
    return argon2.hash(plano, { type: argon2.argon2id });
  }

  async verificar(hash: string, plano: string): Promise<boolean> {
    try {
      return await argon2.verify(hash, plano);
    } catch {
      return false;
    }
  }
}
