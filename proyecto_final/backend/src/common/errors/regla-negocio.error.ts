/**
 * Error lanzado por los objetos de dominio cuando una operación viola una
 * regla de negocio (p. ej. entregar un equipo a un gestor no verificado).
 * El filtro global lo traduce a HTTP 422 con un mensaje claro para el usuario.
 */
export class ReglaNegocioError extends Error {
  constructor(mensaje: string) {
    super(mensaje);
    this.name = 'ReglaNegocioError';
  }
}
