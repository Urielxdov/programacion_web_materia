import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { RepositorioBase } from '../../common/repositories/repositorio-base';
import { Categoria } from './categoria.entity';
import { Gestor } from './gestor.entity';

@Injectable()
export class CategoriasRepository extends RepositorioBase<Categoria> {
  constructor(dataSource: DataSource) {
    super(dataSource, Categoria, 'La categoría');
  }

  listar(): Promise<Categoria[]> {
    return this.repo.find({ order: { nombre: 'ASC' } });
  }

  buscarVarias(ids: number[]): Promise<Categoria[]> {
    if (ids.length === 0) return Promise.resolve([]);
    return this.repo
      .createQueryBuilder('c')
      .where('c.id IN (:...ids)', { ids })
      .getMany();
  }

  existeNombre(nombre: string, excluirId?: number): Promise<boolean> {
    const qb = this.repo
      .createQueryBuilder('c')
      .where('LOWER(c.nombre) = LOWER(:nombre)', { nombre });
    if (excluirId) qb.andWhere('c.id <> :excluirId', { excluirId });
    return qb.getExists();
  }
}

@Injectable()
export class GestoresRepository extends RepositorioBase<Gestor> {
  constructor(dataSource: DataSource) {
    super(dataSource, Gestor, 'El gestor');
  }

  listar(soloVerificados = false): Promise<Gestor[]> {
    return this.repo.find({
      where: soloVerificados ? { verificado: true } : {},
      relations: { verificadoPor: true },
      order: { verificado: 'DESC', nombre: 'ASC' },
    });
  }
}
