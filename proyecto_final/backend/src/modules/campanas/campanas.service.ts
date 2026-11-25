import { Injectable } from '@nestjs/common';
import { EstadoCampana } from '../../common/enums';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import type { UsuarioSesion } from '../../common/types/session.types';
import { CategoriasRepository } from '../catalogos/catalogos.repository';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import {
  ActualizarCampanaDto,
  CrearCampanaDto,
  CrearParticipacionDto,
} from './campanas.dto';
import {
  CampanasRepository,
  ParticipacionesRepository,
} from './campanas.repository';
import { Participacion } from './participacion.entity';

@Injectable()
export class CampanasService {
  constructor(
    private readonly campanas: CampanasRepository,
    private readonly participaciones: ParticipacionesRepository,
    private readonly categorias: CategoriasRepository,
    private readonly usuarios: UsuariosRepository,
  ) {}

  listar(estado?: EstadoCampana) {
    return this.campanas.listarConResumen(estado);
  }

  detalle(id: number) {
    return this.campanas.obtenerConResumen(id);
  }

  async crear(dto: CrearCampanaDto, usuarioId: number) {
    this.validarFechas(dto.fechaInicio, dto.fechaFin);
    const categorias = await this.obtenerCategorias(dto.categoriasIds);
    const campana = this.campanas.crear({
      nombre: dto.nombre,
      descripcion: dto.descripcion,
      fechaInicio: dto.fechaInicio,
      fechaFin: dto.fechaFin,
      ubicacion: dto.ubicacion,
      horario: dto.horario ?? null,
      metaEquipos: dto.metaEquipos ?? null,
      estado: EstadoCampana.PROGRAMADA,
      categoriasAceptadas: categorias,
      creadaPor: await this.usuarios.obtenerPorId(usuarioId),
    });
    const guardada = await this.campanas.guardar(campana);
    return this.detalle(guardada.id);
  }

  async actualizar(id: number, dto: ActualizarCampanaDto) {
    const campana = await this.campanas.obtenerPorId(id);
    const { categoriasIds, ...resto } = dto;
    Object.assign(campana, resto);
    this.validarFechas(campana.fechaInicio, campana.fechaFin);
    if (categoriasIds)
      campana.categoriasAceptadas = await this.obtenerCategorias(categoriasIds);
    await this.campanas.guardar(campana);
    return this.detalle(id);
  }

  async participar(
    campanaId: number,
    dto: CrearParticipacionDto,
    sesion?: UsuarioSesion,
  ) {
    const campana = await this.campanas.obtenerPorId(campanaId);
    campana.exigirAbierta();
    const participacion = this.participaciones.crear({
      campana,
      nombreParticipante: dto.nombreParticipante,
      contacto: dto.contacto ?? null,
      equiposDeclarados: dto.equiposDeclarados,
      comentario: dto.comentario ?? null,
      usuario: sesion ? await this.usuarios.buscarPorId(sesion.id) : null,
    });
    const guardada = await this.participaciones.guardar(participacion);
    // Al público solo se le devuelve una confirmación, no datos de otras personas.
    return {
      id: guardada.id,
      campana: campana.nombre,
      equiposDeclarados: guardada.equiposDeclarados,
    };
  }

  async participantes(campanaId: number): Promise<Participacion[]> {
    await this.campanas.obtenerPorId(campanaId);
    return this.participaciones.listarPorCampana(campanaId);
  }

  private async obtenerCategorias(ids: number[]) {
    const unicos = [...new Set(ids)];
    const categorias = await this.categorias.buscarVarias(unicos);
    if (categorias.length !== unicos.length) {
      throw new ReglaNegocioError(
        'Alguna de las categorías seleccionadas no existe.',
      );
    }
    return categorias;
  }

  private validarFechas(inicio: string, fin: string) {
    if (fin < inicio) {
      throw new ReglaNegocioError(
        'La fecha de fin no puede ser anterior a la fecha de inicio.',
      );
    }
  }
}
