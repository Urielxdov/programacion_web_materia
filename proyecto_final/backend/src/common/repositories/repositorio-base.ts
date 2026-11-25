import { NotFoundException } from '@nestjs/common';
import {
  DataSource,
  EntityManager,
  EntityTarget,
  FindOptionsRelations,
  FindOptionsWhere,
  Repository,
} from 'typeorm';
import { EntidadBase } from '../entities/base.entity';

/**
 * Patrón Repository (clase base genérica).
 *
 * Centraliza el acceso a datos: los servicios de negocio trabajan con
 * repositorios y nunca construyen consultas ni tocan el DataSource directamente.
 * Cada repositorio concreto agrega las consultas propias de su entidad.
 */
export abstract class RepositorioBase<T extends EntidadBase> {
  protected readonly repo: Repository<T>;

  protected constructor(
    protected readonly dataSource: DataSource,
    private readonly entidad: EntityTarget<T>,
    private readonly nombreLegible: string,
  ) {
    this.repo = dataSource.getRepository(entidad);
  }

  /** Repositorio TypeORM ligado a una transacción, si se proporciona un EntityManager. */
  protected con(manager?: EntityManager): Repository<T> {
    return manager ? manager.getRepository(this.entidad) : this.repo;
  }

  crear(datos: Partial<T>): T {
    return this.repo.create(datos as T);
  }

  buscarPorId(
    id: number,
    relaciones?: FindOptionsRelations<T>,
    manager?: EntityManager,
  ): Promise<T | null> {
    return this.con(manager).findOne({
      where: { id } as FindOptionsWhere<T>,
      relations: relaciones,
    });
  }

  /** Igual que buscarPorId, pero lanza 404 con un mensaje claro si no existe. */
  async obtenerPorId(
    id: number,
    relaciones?: FindOptionsRelations<T>,
    manager?: EntityManager,
  ): Promise<T> {
    const entidad = await this.buscarPorId(id, relaciones, manager);
    if (!entidad) {
      throw new NotFoundException(
        `${this.nombreLegible} con id ${id} no existe.`,
      );
    }
    return entidad;
  }

  guardar(entidad: T, manager?: EntityManager): Promise<T> {
    return this.con(manager).save(entidad);
  }

  /** Ejecuta un bloque dentro de una transacción de base de datos. */
  transaccion<R>(trabajo: (manager: EntityManager) => Promise<R>): Promise<R> {
    return this.dataSource.transaction(trabajo);
  }
}

export interface Pagina<T> {
  datos: T[];
  total: number;
  pagina: number;
  porPagina: number;
  totalPaginas: number;
}

export function crearPagina<T>(
  datos: T[],
  total: number,
  pagina: number,
  porPagina: number,
): Pagina<T> {
  return {
    datos,
    total,
    pagina,
    porPagina,
    totalPaginas: Math.max(1, Math.ceil(total / porPagina)),
  };
}
