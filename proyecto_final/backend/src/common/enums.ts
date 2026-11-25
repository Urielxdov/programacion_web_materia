/** Roles de usuarios autenticados. El "Visitante" es cualquier persona sin sesión. */
export enum Rol {
  CAPTURISTA = 'CAPTURISTA',
  RESPONSABLE = 'RESPONSABLE',
  ADMIN = 'ADMIN',
}

/** Tipo de categoría: determina qué objeto crea la EquipoFactory. */
export enum TipoCategoria {
  COMPUTO = 'COMPUTO',
  PERIFERICO = 'PERIFERICO',
  MOVIL = 'MOVIL',
  ENERGIA = 'ENERGIA',
  CABLEADO = 'CABLEADO',
  OTRO = 'OTRO',
}

/** Condición del equipo según su diagnóstico. */
export enum CondicionEquipo {
  PENDIENTE = 'PENDIENTE',
  FUNCIONAL = 'FUNCIONAL',
  REPARABLE = 'REPARABLE',
  REUTILIZABLE = 'REUTILIZABLE',
  GESTION_ESPECIALIZADA = 'GESTION_ESPECIALIZADA',
}

/** Dónde se encuentra / qué destino tuvo el equipo. */
export enum EstadoDestino {
  EN_INVENTARIO = 'EN_INVENTARIO',
  EN_REPARACION = 'EN_REPARACION',
  REASIGNADO = 'REASIGNADO',
  CONSERVADO = 'CONSERVADO',
  ENTREGADO_GESTOR = 'ENTREGADO_GESTOR',
}

export enum TipoMovimiento {
  INGRESO = 'INGRESO',
  DIAGNOSTICO = 'DIAGNOSTICO',
  ENVIO_REPARACION = 'ENVIO_REPARACION',
  REPARACION = 'REPARACION',
  REASIGNACION = 'REASIGNACION',
  CONSERVACION = 'CONSERVACION',
  ENTREGA_GESTOR = 'ENTREGA_GESTOR',
}

export enum ResultadoReparacion {
  EXITOSA = 'EXITOSA',
  FALLIDA = 'FALLIDA',
}

export enum EstadoCampana {
  PROGRAMADA = 'PROGRAMADA',
  ACTIVA = 'ACTIVA',
  CERRADA = 'CERRADA',
}
