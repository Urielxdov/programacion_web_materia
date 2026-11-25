import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';
import { EstadoCampana } from '../../common/enums';
import { RepositorioBase } from '../../common/repositories/repositorio-base';
import { Campana } from './campana.entity';
import { Participacion } from './participacion.entity';

export interface CampanaConResumen extends Campana {
  totalParticipaciones: number;
  equiposDeclarados: number;
  equiposRecibidosTotal: number;
}

@Injectable()
export class CampanasRepository extends RepositorioBase<Campana> {
  constructor(dataSource: DataSource) {
    super(dataSource, Campana, 'La campaña');
  }

  /** Lista campañas con sus totales de participación y equipos recibidos. */
  async listarConResumen(estado?: EstadoCampana): Promise<CampanaConResumen[]> {
    const qb = this.repo
      .createQueryBuilder('c')
      .leftJoinAndSelect('c.categoriasAceptadas', 'cat')
      .loadRelationCountAndMap('c.totalParticipaciones', 'c.participaciones')
      .loadRelationCountAndMap('c.equiposRecibidosTotal', 'c.equiposRecibidos')
      .orderBy('c.fechaInicio', 'DESC');
    if (estado) qb.where('c.estado = :estado', { estado });
    const campanas = (await qb.getMany()) as CampanaConResumen[];

    const declarados = await this.dataSource
      .getRepository(Participacion)
      .createQueryBuilder('p')
      .select('p.campana_id', 'campanaId')
      .addSelect('COALESCE(SUM(p.equiposDeclarados), 0)', 'total')
      .groupBy('p.campana_id')
      .getRawMany<{ campanaId: number; total: string }>();
    const mapa = new Map(
      declarados.map((d) => [Number(d.campanaId), Number(d.total)]),
    );
    campanas.forEach((c) => (c.equiposDeclarados = mapa.get(c.id) ?? 0));
    return campanas;
  }

  async obtenerConResumen(id: number): Promise<CampanaConResumen> {
    const campana = (await this.repo
      .createQueryBuilder('c')
      .leftJoinAndSelect('c.categoriasAceptadas', 'cat')
      .loadRelationCountAndMap('c.totalParticipaciones', 'c.participaciones')
      .loadRelationCountAndMap('c.equiposRecibidosTotal', 'c.equiposRecibidos')
      .where('c.id = :id', { id })
      .getOne()) as CampanaConResumen | null;
    if (!campana) await this.obtenerPorId(id); // lanza 404
    const { total } = (await this.dataSource
      .getRepository(Participacion)
      .createQueryBuilder('p')
      .select('COALESCE(SUM(p.equiposDeclarados), 0)', 'total')
      .where('p.campana_id = :id', { id })
      .getRawOne<{ total: string }>()) ?? { total: '0' };
    campana!.equiposDeclarados = Number(total);
    return campana!;
  }
}

@Injectable()
export class ParticipacionesRepository extends RepositorioBase<Participacion> {
  constructor(dataSource: DataSource) {
    super(dataSource, Participacion, 'La participación');
  }

  listarPorCampana(campanaId: number): Promise<Participacion[]> {
    return this.repo.find({
      where: { campana: { id: campanaId } },
      order: { creadoEn: 'DESC' },
    });
  }
}
