import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { RepositorioBase } from '../../common/repositories/repositorio-base';
import { Reparacion } from './reparacion.entity';

@Injectable()
export class ReparacionesRepository extends RepositorioBase<Reparacion> {
  constructor(dataSource: DataSource) {
    super(dataSource, Reparacion, 'La reparación');
  }
}
