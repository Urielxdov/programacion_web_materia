import { Injectable } from '@nestjs/common';
import { Brackets, DataSource, EntityManager } from 'typeorm';
import {
  crearPagina,
  Pagina,
  RepositorioBase,
} from '../../common/repositories/repositorio-base';
import { Equipo } from './equipo.entity';
import { FiltrosEquiposDto } from './equipos.dto';

@Injectable()
export class EquiposRepository extends RepositorioBase<Equipo> {
  constructor(dataSource: DataSource) {
    super(dataSource, Equipo, 'El equipo');
  }

  /** Búsqueda paginada con filtros. Todos los valores van como parámetros (sin concatenar SQL). */
  async buscar(f: FiltrosEquiposDto): Promise<Pagina<Equipo>> {
    const pagina = f.pagina ?? 1;
    const porPagina = f.porPagina ?? 20;

    const qb = this.repo
      .createQueryBuilder('e')
      .leftJoinAndSelect('e.categoria', 'categoria')
      .leftJoinAndSelect('e.campana', 'campana');

    if (f.q) {
      const texto = `%${f.q.trim()}%`;
      qb.andWhere(
        new Brackets((w) =>
          w
            .where('e.folio ILIKE :texto', { texto })
            .orWhere('e.marca ILIKE :texto', { texto })
            .orWhere('e.modelo ILIKE :texto', { texto })
            .orWhere('e.numeroSerie ILIKE :texto', { texto })
            .orWhere('e.ubicacion ILIKE :texto', { texto }),
        ),
      );
    }
    if (f.categoriaId)
      qb.andWhere('categoria.id = :cat', { cat: f.categoriaId });
    if (f.condicion)
      qb.andWhere('e.condicion = :condicion', { condicion: f.condicion });
    if (f.estadoDestino)
      qb.andWhere('e.estadoDestino = :estado', { estado: f.estadoDestino });
    if (f.campanaId)
      qb.andWhere('campana.id = :campana', { campana: f.campanaId });

    const orden = f.orden === 'antiguos' ? 'ASC' : 'DESC';
    qb.orderBy('e.fechaIngreso', orden).addOrderBy('e.id', orden);

    const [datos, total] = await qb
      .skip((pagina - 1) * porPagina)
      .take(porPagina)
      .getManyAndCount();
    return crearPagina(datos, total, pagina, porPagina);
  }

  /** Detalle con todo el historial del equipo, ordenado cronológicamente. */
  async detalle(id: number): Promise<Equipo> {
    const equipo = await this.obtenerPorId(id, {
      categoria: true,
      campana: true,
      registradoPor: true,
      movimientos: { responsable: true, gestor: true },
      diagnosticos: { responsable: true },
      reparaciones: { responsable: true },
    });
    const porFecha = (a: { creadoEn: Date }, b: { creadoEn: Date }) =>
      b.creadoEn.getTime() - a.creadoEn.getTime();
    equipo.movimientos?.sort(porFecha);
    equipo.diagnosticos?.sort(porFecha);
    equipo.reparaciones?.sort(porFecha);
    return equipo;
  }

  /** Bloquea la fila del equipo dentro de una transacción para evitar cambios simultáneos. */
  obtenerParaActualizar(id: number, manager: EntityManager): Promise<Equipo> {
    return manager
      .getRepository(Equipo)
      .createQueryBuilder('e')
      .setLock('pessimistic_write')
      .where('e.id = :id', { id })
      .getOneOrFail()
      .then(() => this.obtenerPorId(id, { categoria: true }, manager));
  }

  /** Siguiente folio consecutivo del año: EW-2026-0001, EW-2026-0002, … */
  async siguienteFolio(
    fecha = new Date(),
    manager?: EntityManager,
  ): Promise<string> {
    const prefijo = `EW-${fecha.getFullYear()}-`;
    const ultimo = await this.con(manager)
      .createQueryBuilder('e')
      .select('e.folio', 'folio')
      .where('e.folio LIKE :p', { p: `${prefijo}%` })
      .orderBy('e.folio', 'DESC')
      .limit(1)
      .getRawOne<{ folio: string }>();
    const consecutivo = ultimo
      ? Number(ultimo.folio.slice(prefijo.length)) + 1
      : 1;
    return `${prefijo}${String(consecutivo).padStart(4, '0')}`;
  }

  existeNumeroSerie(numeroSerie: string, excluirId?: number): Promise<boolean> {
    const qb = this.repo
      .createQueryBuilder('e')
      .where('LOWER(e.numeroSerie) = LOWER(:ns)', { ns: numeroSerie.trim() });
    if (excluirId) qb.andWhere('e.id <> :excluirId', { excluirId });
    return qb.getExists();
  }
}
