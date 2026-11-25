import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { RepositorioBase } from '../../common/repositories/repositorio-base';
import { Movimiento } from './movimiento.entity';

@Injectable()
export class MovimientosRepository extends RepositorioBase<Movimiento> {
  constructor(dataSource: DataSource) {
    super(dataSource, Movimiento, 'El movimiento');
  }

  deEquipo(equipoId: number): Promise<Movimiento[]> {
    return this.repo.find({
      where: { equipo: { id: equipoId } },
      relations: { responsable: true, gestor: true },
      order: { creadoEn: 'DESC', id: 'DESC' },
    });
  }

  recientes(limite = 10): Promise<Movimiento[]> {
    return this.repo.find({
      relations: { equipo: true, responsable: true },
      order: { creadoEn: 'DESC', id: 'DESC' },
      take: limite,
    });
  }
}
