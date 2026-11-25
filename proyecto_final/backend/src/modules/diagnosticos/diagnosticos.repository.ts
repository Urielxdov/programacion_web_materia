import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { RepositorioBase } from '../../common/repositories/repositorio-base';
import { Diagnostico } from './diagnostico.entity';

@Injectable()
export class DiagnosticosRepository extends RepositorioBase<Diagnostico> {
  constructor(dataSource: DataSource) {
    super(dataSource, Diagnostico, 'El diagnóstico');
  }

  ultimoDeEquipo(equipoId: number): Promise<Diagnostico | null> {
    return this.repo.findOne({
      where: { equipo: { id: equipoId } },
      order: { creadoEn: 'DESC' },
    });
  }
}
