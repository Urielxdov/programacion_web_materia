import 'express-session';
import { Rol } from '../enums';

/** Datos mínimos del usuario que se guardan en la sesión del servidor. */
export interface UsuarioSesion {
  id: number;
  nombre: string;
  email: string;
  rol: Rol;
}

declare module 'express-session' {
  interface SessionData {
    usuario?: UsuarioSesion;
  }
}
