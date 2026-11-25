import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, establecerTokenCsrf, query } from './cliente';
import type {
  CampanaResumen,
  Categoria,
  Diagnostico,
  Equipo,
  EquipoDetalle,
  EstadoCampana,
  Gestor,
  IndicadoresCampanas,
  IndicadoresPublicos,
  IndicadoresResumen,
  IndicadoresReutilizacion,
  Movimiento,
  Pagina,
  Participacion,
  Reparacion,
  ResultadoClasificacion,
  Usuario,
  UsuarioSesion,
} from './tipos';

/**
 * Hooks de TanStack Query: todo el estado que viene del servidor vive aquí
 * (caché, carga, errores, reintentos e invalidación tras cada mutación).
 */
export const claves = {
  sesion: ['sesion'] as const,
  equipos: (f?: object) => ['equipos', f ?? {}] as const,
  equipo: (id: number) => ['equipos', 'detalle', id] as const,
  categorias: ['categorias'] as const,
  gestores: (verificados?: boolean) => ['gestores', { verificados }] as const,
  campanas: (estado?: string) => ['campanas', { estado }] as const,
  campana: (id: number) => ['campanas', 'detalle', id] as const,
  participaciones: (id: number) => ['campanas', id, 'participaciones'] as const,
  usuarios: ['usuarios'] as const,
  indicadores: ['indicadores'] as const,
  movimientosRecientes: ['movimientos', 'recientes'] as const,
};

// ───────────── Sesión ─────────────

export function useSesion() {
  return useQuery({
    queryKey: claves.sesion,
    queryFn: () => api.get<{ usuario: UsuarioSesion | null }>('/auth/me').then((r) => r.usuario),
    staleTime: 5 * 60_000,
  });
}

export function useLogin() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (datos: { email: string; password: string }) =>
      api.post<{ usuario: UsuarioSesion; csrfToken: string }>('/auth/login', datos),
    onSuccess: ({ usuario, csrfToken }) => {
      establecerTokenCsrf(csrfToken);
      qc.clear();
      qc.setQueryData(claves.sesion, usuario);
    },
  });
}

export function useLogout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => api.post<{ ok: boolean }>('/auth/logout'),
    onSettled: () => {
      establecerTokenCsrf(null);
      qc.clear();
      qc.setQueryData(claves.sesion, null);
    },
  });
}

// ───────────── Equipos ─────────────

export interface FiltrosEquipos {
  q?: string;
  categoriaId?: number;
  condicion?: string;
  estadoDestino?: string;
  campanaId?: number;
  orden?: 'recientes' | 'antiguos';
  pagina?: number;
  porPagina?: number;
}

export function useEquipos(filtros: FiltrosEquipos) {
  return useQuery({
    queryKey: claves.equipos(filtros),
    queryFn: () => api.get<Pagina<Equipo>>(`/equipos${query({ ...filtros })}`),
    placeholderData: keepPreviousData,
  });
}

export function useEquipo(id: number) {
  return useQuery({
    queryKey: claves.equipo(id),
    queryFn: () => api.get<EquipoDetalle>(`/equipos/${id}`),
    enabled: Number.isFinite(id) && id > 0,
  });
}

export interface DatosEquipo {
  categoriaId: number;
  marca: string;
  modelo: string;
  numeroSerie?: string;
  fechaIngreso: string;
  ubicacion?: string;
  observaciones?: string;
  campanaId?: number;
}

/** Invalida todo lo que depende del inventario después de un cambio. */
function useInvalidarInventario() {
  const qc = useQueryClient();
  return (id?: number) => {
    void qc.invalidateQueries({ queryKey: ['equipos'] });
    void qc.invalidateQueries({ queryKey: claves.indicadores });
    void qc.invalidateQueries({ queryKey: ['movimientos'] });
    void qc.invalidateQueries({ queryKey: ['campanas'] });
    if (id) void qc.invalidateQueries({ queryKey: claves.equipo(id) });
  };
}

export function useRegistrarEquipo() {
  const invalidar = useInvalidarInventario();
  return useMutation({
    mutationFn: (datos: DatosEquipo) => api.post<EquipoDetalle>('/equipos', datos),
    onSuccess: () => invalidar(),
  });
}

export function useActualizarEquipo(id: number) {
  const invalidar = useInvalidarInventario();
  return useMutation({
    mutationFn: (datos: Partial<DatosEquipo>) => api.patch<EquipoDetalle>(`/equipos/${id}`, datos),
    onSuccess: () => invalidar(id),
  });
}

export interface DatosDiagnostico {
  descripcion: string;
  enciende: boolean;
  danoEstructural: boolean;
  componentesPeligrosos: boolean;
  costoReparacionEstimado: number;
}

export function useSimularDiagnostico(equipoId: number) {
  return useMutation({
    mutationFn: (datos: DatosDiagnostico) =>
      api.post<ResultadoClasificacion>(`/equipos/${equipoId}/diagnosticos/simular`, datos),
  });
}

export function useRegistrarDiagnostico(equipoId: number) {
  const invalidar = useInvalidarInventario();
  return useMutation({
    mutationFn: (datos: DatosDiagnostico) => api.post<Diagnostico>(`/equipos/${equipoId}/diagnosticos`, datos),
    onSuccess: () => invalidar(equipoId),
  });
}

export function useRegistrarReparacion(equipoId: number) {
  const invalidar = useInvalidarInventario();
  return useMutation({
    mutationFn: (datos: { descripcion: string; costo: number; resultado: 'EXITOSA' | 'FALLIDA'; fecha: string }) =>
      api.post<Reparacion>(`/equipos/${equipoId}/reparaciones`, datos),
    onSuccess: () => invalidar(equipoId),
  });
}

export interface DatosDecision {
  tipo: 'ENVIO_REPARACION' | 'REASIGNACION' | 'CONSERVACION' | 'ENTREGA_GESTOR';
  destino?: string;
  gestorId?: number;
  evidencia?: string;
  datosBorrados?: boolean;
  notas?: string;
}

export function useRegistrarDecision(equipoId: number) {
  const invalidar = useInvalidarInventario();
  return useMutation({
    mutationFn: (datos: DatosDecision) => api.post<Movimiento>(`/equipos/${equipoId}/movimientos`, datos),
    onSuccess: () => invalidar(equipoId),
  });
}

export function useMovimientosRecientes(limite = 8) {
  return useQuery({
    queryKey: [...claves.movimientosRecientes, limite],
    queryFn: () => api.get<Movimiento[]>(`/movimientos/recientes?limite=${limite}`),
  });
}

// ───────────── Catálogos ─────────────

export function useCategorias() {
  return useQuery({
    queryKey: claves.categorias,
    queryFn: () => api.get<Categoria[]>('/catalogos/categorias'),
    staleTime: 10 * 60_000,
  });
}

export type DatosCategoria = Pick<Categoria, 'nombre' | 'tipo' | 'pesoPromedioKg' | 'valorReposicion'> & {
  descripcion?: string;
};

export function useGuardarCategoria() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...datos }: DatosCategoria & { id?: number }) =>
      id ? api.patch<Categoria>(`/catalogos/categorias/${id}`, datos) : api.post<Categoria>('/catalogos/categorias', datos),
    onSuccess: () => qc.invalidateQueries({ queryKey: claves.categorias }),
  });
}

export function useGestores(soloVerificados = false) {
  return useQuery({
    queryKey: claves.gestores(soloVerificados),
    queryFn: () => api.get<Gestor[]>(`/catalogos/gestores${soloVerificados ? '?verificados=true' : ''}`),
  });
}

export function useGuardarGestor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...datos }: { id?: number; nombre: string; tipo: string; contacto?: string; direccion?: string }) =>
      id ? api.patch<Gestor>(`/catalogos/gestores/${id}`, datos) : api.post<Gestor>('/catalogos/gestores', datos),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['gestores'] }),
  });
}

export function useVerificarGestor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, documento, revocar }: { id: number; documento?: string; revocar?: boolean }) =>
      revocar
        ? api.post<Gestor>(`/catalogos/gestores/${id}/revocar`)
        : api.post<Gestor>(`/catalogos/gestores/${id}/verificar`, { documentoVerificacion: documento }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['gestores'] }),
  });
}

// ───────────── Campañas ─────────────

export function useCampanas(estado?: EstadoCampana) {
  return useQuery({
    queryKey: claves.campanas(estado),
    queryFn: () => api.get<CampanaResumen[]>(`/campanas${query({ estado })}`),
  });
}

export function useCampana(id: number) {
  return useQuery({
    queryKey: claves.campana(id),
    queryFn: () => api.get<CampanaResumen>(`/campanas/${id}`),
    enabled: Number.isFinite(id) && id > 0,
  });
}

export interface DatosCampana {
  nombre: string;
  descripcion: string;
  fechaInicio: string;
  fechaFin: string;
  ubicacion: string;
  horario?: string;
  metaEquipos?: number;
  categoriasIds: number[];
  estado?: EstadoCampana;
}

export function useGuardarCampana() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...datos }: DatosCampana & { id?: number }) =>
      id ? api.patch<CampanaResumen>(`/campanas/${id}`, datos) : api.post<CampanaResumen>('/campanas', datos),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['campanas'] });
      void qc.invalidateQueries({ queryKey: claves.indicadores });
    },
  });
}

export function useParticipar(campanaId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (datos: { nombreParticipante: string; contacto?: string; equiposDeclarados: number; comentario?: string }) =>
      api.post<{ id: number; campana: string; equiposDeclarados: number }>(`/campanas/${campanaId}/participaciones`, datos),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['campanas'] }),
  });
}

export function useParticipaciones(campanaId: number, habilitado = true) {
  return useQuery({
    queryKey: claves.participaciones(campanaId),
    queryFn: () => api.get<Participacion[]>(`/campanas/${campanaId}/participaciones`),
    enabled: habilitado,
  });
}

// ───────────── Usuarios ─────────────

export function useUsuarios() {
  return useQuery({ queryKey: claves.usuarios, queryFn: () => api.get<Usuario[]>('/usuarios') });
}

export function useGuardarUsuario() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...datos }: { id?: number; nombre?: string; email?: string; password?: string; rol?: string; activo?: boolean }) =>
      id ? api.patch<Usuario>(`/usuarios/${id}`, datos) : api.post<Usuario>('/usuarios', datos),
    onSuccess: () => qc.invalidateQueries({ queryKey: claves.usuarios }),
  });
}

// ───────────── Indicadores ─────────────

export function useIndicadores() {
  return useQuery({ queryKey: claves.indicadores, queryFn: () => api.get<IndicadoresResumen>('/indicadores') });
}

export function useIndicadoresCampanas(habilitado = true) {
  return useQuery({
    queryKey: [...claves.indicadores, 'campanas'],
    queryFn: () => api.get<IndicadoresCampanas>('/indicadores/campanas'),
    enabled: habilitado,
  });
}

export function useIndicadoresReutilizacion(habilitado = true) {
  return useQuery({
    queryKey: [...claves.indicadores, 'reutilizacion'],
    queryFn: () => api.get<IndicadoresReutilizacion>('/indicadores/reutilizacion'),
    enabled: habilitado,
  });
}

export function useIndicadoresPublicos() {
  return useQuery({
    queryKey: [...claves.indicadores, 'publico'],
    queryFn: () => api.get<IndicadoresPublicos>('/indicadores/publico'),
    staleTime: 5 * 60_000,
  });
}
