/** Tipos de las respuestas de la API (reflejan las entidades del backend). */

export type Rol = 'CAPTURISTA' | 'RESPONSABLE' | 'ADMIN';
export type TipoCategoria = 'COMPUTO' | 'PERIFERICO' | 'MOVIL' | 'ENERGIA' | 'CABLEADO' | 'OTRO';
export type Condicion = 'PENDIENTE' | 'FUNCIONAL' | 'REPARABLE' | 'REUTILIZABLE' | 'GESTION_ESPECIALIZADA';
export type EstadoDestino = 'EN_INVENTARIO' | 'EN_REPARACION' | 'REASIGNADO' | 'CONSERVADO' | 'ENTREGADO_GESTOR';
export type TipoMovimiento =
  | 'INGRESO'
  | 'DIAGNOSTICO'
  | 'ENVIO_REPARACION'
  | 'REPARACION'
  | 'REASIGNACION'
  | 'CONSERVACION'
  | 'ENTREGA_GESTOR';
export type EstadoCampana = 'PROGRAMADA' | 'ACTIVA' | 'CERRADA';
export type AccionEquipo =
  | 'DIAGNOSTICAR'
  | 'ENVIAR_REPARACION'
  | 'REGISTRAR_REPARACION'
  | 'REASIGNAR'
  | 'CONSERVAR'
  | 'ENTREGAR_GESTOR';

interface Base {
  id: number;
  creadoEn: string;
  actualizadoEn: string;
}

export interface UsuarioSesion {
  id: number;
  nombre: string;
  email: string;
  rol: Rol;
}

export interface Usuario extends Base, UsuarioSesion {
  activo: boolean;
}

export interface Categoria extends Base {
  nombre: string;
  tipo: TipoCategoria;
  pesoPromedioKg: number;
  valorReposicion: number;
  descripcion: string | null;
}

export interface Gestor extends Base {
  nombre: string;
  tipo: string;
  contacto: string | null;
  direccion: string | null;
  verificado: boolean;
  documentoVerificacion: string | null;
  fechaVerificacion: string | null;
  verificadoPor?: Usuario | null;
}

export interface CampanaResumen extends Base {
  nombre: string;
  descripcion: string;
  fechaInicio: string;
  fechaFin: string;
  ubicacion: string;
  horario: string | null;
  estado: EstadoCampana;
  metaEquipos: number | null;
  categoriasAceptadas: Categoria[];
  totalParticipaciones: number;
  equiposDeclarados: number;
  equiposRecibidosTotal: number;
}

export interface Participacion extends Base {
  nombreParticipante: string;
  contacto: string | null;
  equiposDeclarados: number;
  comentario: string | null;
}

export interface Movimiento extends Base {
  tipo: TipoMovimiento;
  descripcion: string;
  estadoAnterior: EstadoDestino | null;
  estadoNuevo: EstadoDestino;
  destino: string | null;
  evidencia: string | null;
  responsable: Usuario | null;
  gestor: Gestor | null;
  equipo?: Equipo;
}

export interface Diagnostico extends Base {
  descripcion: string;
  enciende: boolean;
  danoEstructural: boolean;
  componentesPeligrosos: boolean;
  costoReparacionEstimado: number;
  recomendacion: Condicion;
  estrategiaAplicada: string;
  justificacion: string;
  responsable: Usuario | null;
}

export interface Reparacion extends Base {
  descripcion: string;
  costo: number;
  resultado: 'EXITOSA' | 'FALLIDA';
  ahorroEstimado: number;
  fecha: string;
  responsable: Usuario | null;
}

export interface Equipo extends Base {
  folio: string;
  categoria: Categoria;
  marca: string;
  modelo: string;
  numeroSerie: string | null;
  fechaIngreso: string;
  condicion: Condicion;
  estadoDestino: EstadoDestino;
  ubicacion: string | null;
  destinoActual: string | null;
  observaciones: string | null;
  requiereBorradoDatos: boolean;
  datosBorrados: boolean;
  campana: CampanaResumen | null;
}

export interface EquipoDetalle extends Equipo {
  registradoPor: Usuario | null;
  movimientos: Movimiento[];
  diagnosticos: Diagnostico[];
  reparaciones: Reparacion[];
  accionesDisponibles: AccionEquipo[];
  recomendacionesEntrega: string[];
}

export interface Pagina<T> {
  datos: T[];
  total: number;
  pagina: number;
  porPagina: number;
  totalPaginas: number;
}

export interface ResultadoClasificacion {
  condicion: Condicion;
  justificacion: string;
  estrategia: string;
}

export interface Conteo {
  clave: string;
  total: number;
}

export interface IndicadoresResumen {
  equipos: {
    total: number;
    pendientesDiagnostico: number;
    enReparacion: number;
    reasignados: number;
    conservados: number;
    recuperados: number;
    reparados: number;
  };
  porCondicion: Conteo[];
  porEstado: Conteo[];
  gestores: { equiposEntregados: number; kgEntregados: number };
  economia: { costoReparaciones: number; ahorroEstimado: number };
  ambiente: { kgResiduoEvitado: number };
  participacion: {
    participaciones: number;
    equiposDeclarados: number;
    equiposRecibidos: number;
    campanasActivas: number;
    campanasTotales: number;
  };
}

export interface ResultadoCampana {
  id: number;
  nombre: string;
  estado: EstadoCampana;
  fechaInicio: string;
  fechaFin: string;
  metaEquipos: number | null;
  avanceMeta: number | null;
  participaciones: number;
  equiposDeclarados: number;
  equiposRecibidos: number;
  equiposReasignados: number;
  kgRecolectados: number;
}

export interface IndicadoresCampanas {
  totales: { participaciones: number; equiposDeclarados: number; equiposRecibidos: number; kgRecolectados: number };
  campanas: ResultadoCampana[];
}

export interface IndicadoresReutilizacion {
  reparaciones: {
    total: number;
    exitosas: number;
    fallidas: number;
    costoTotal: number;
    costoPromedio: number;
    ahorroReparaciones: number;
    tasaExito: number;
  };
  reutilizacion: {
    reasignados: number;
    tasaReutilizacion: number;
    ahorroReutilizacionDirecta: number;
    ahorroTotal: number;
    kgResiduoEvitado: number;
  };
  porCategoria: {
    categoria: string;
    tipo: TipoCategoria;
    registrados: number;
    reasignados: number;
    reparados: number;
    entregados: number;
  }[];
  destinos: { destino: string; total: number }[];
  serieMensual: { mes: string; registrados: number; reasignados: number; reparaciones: number; entregados: number }[];
}

export interface IndicadoresPublicos {
  equiposRegistrados: number;
  equiposRecuperados: number;
  kgResiduoEvitado: number;
  campanasActivas: number;
  participaciones: number;
}
