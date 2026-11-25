import { Injectable } from '@nestjs/common';
import { IndicadoresRepository } from './indicadores.repository';

@Injectable()
export class IndicadoresService {
  constructor(private readonly repo: IndicadoresRepository) {}

  async resumen() {
    const [
      equipos,
      porCondicion,
      porEstado,
      entregas,
      reparaciones,
      ahorroDirecto,
      kgEvitado,
      participacion,
      recuperados,
    ] = await Promise.all([
      this.repo.totalesEquipos(),
      this.repo.agruparPor('condicion'),
      this.repo.agruparPor('estado_destino'),
      this.repo.entregasDocumentadas(),
      this.repo.reparaciones(),
      this.repo.ahorroReutilizacionDirecta(),
      this.repo.kgResiduoEvitado(),
      this.repo.participacion(),
      this.repo.equiposRecuperados(),
    ]);

    return {
      equipos: { ...equipos, recuperados, reparados: reparaciones.exitosas },
      porCondicion,
      porEstado,
      gestores: {
        equiposEntregados: entregas.equipos,
        kgEntregados: entregas.kg,
      },
      economia: {
        costoReparaciones: reparaciones.costoTotal,
        ahorroEstimado: reparaciones.ahorroReparaciones + ahorroDirecto,
      },
      ambiente: { kgResiduoEvitado: kgEvitado },
      participacion,
    };
  }

  async campanas() {
    const campanas = await this.repo.resultadosCampanas();
    const totales = campanas.reduce(
      (acc, c) => ({
        participaciones: acc.participaciones + c.participaciones,
        equiposDeclarados: acc.equiposDeclarados + c.equiposDeclarados,
        equiposRecibidos: acc.equiposRecibidos + c.equiposRecibidos,
        kgRecolectados: acc.kgRecolectados + c.kgRecolectados,
      }),
      {
        participaciones: 0,
        equiposDeclarados: 0,
        equiposRecibidos: 0,
        kgRecolectados: 0,
      },
    );
    return { totales, campanas };
  }

  async reutilizacion() {
    const [
      reparaciones,
      ahorroDirecto,
      porCategoria,
      destinos,
      serie,
      totales,
      kgEvitado,
    ] = await Promise.all([
      this.repo.reparaciones(),
      this.repo.ahorroReutilizacionDirecta(),
      this.repo.porCategoria(),
      this.repo.destinosReasignacion(),
      this.repo.serieMensual(12),
      this.repo.totalesEquipos(),
      this.repo.kgResiduoEvitado(),
    ]);
    const tasaExito = reparaciones.total
      ? Math.round((reparaciones.exitosas / reparaciones.total) * 100)
      : 0;
    const tasaReutilizacion = totales.total
      ? Math.round((totales.reasignados / totales.total) * 100)
      : 0;

    return {
      reparaciones: { ...reparaciones, tasaExito },
      reutilizacion: {
        reasignados: totales.reasignados,
        tasaReutilizacion,
        ahorroReutilizacionDirecta: ahorroDirecto,
        ahorroTotal: reparaciones.ahorroReparaciones + ahorroDirecto,
        kgResiduoEvitado: kgEvitado,
      },
      porCategoria,
      destinos,
      serieMensual: serie,
    };
  }

  /** Cifras agregadas y anónimas para la página pública. */
  async publico() {
    const [equipos, recuperados, kgEvitado, participacion] = await Promise.all([
      this.repo.totalesEquipos(),
      this.repo.equiposRecuperados(),
      this.repo.kgResiduoEvitado(),
      this.repo.participacion(),
    ]);
    return {
      equiposRegistrados: equipos.total,
      equiposRecuperados: recuperados,
      kgResiduoEvitado: kgEvitado,
      campanasActivas: participacion.campanasActivas,
      participaciones: participacion.participaciones,
    };
  }
}
