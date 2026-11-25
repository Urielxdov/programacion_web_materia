import { Injectable } from '@nestjs/common';
import { DataSource } from 'typeorm';

/** Fila cruda de PostgreSQL: COUNT y SUM llegan como texto. */
type Fila = Record<string, string | number | null>;

const num = (v: unknown) => Number(v ?? 0);

export interface ResultadoCampana {
  id: number;
  nombre: string;
  estado: string;
  fechaInicio: string;
  fechaFin: string;
  metaEquipos: number | null;
  avanceMeta: number | null;
  participaciones: number;
  equiposDeclarados: number;
  equiposRecibidos: number;
  equiposReasignados: number;
  kgRecolectados: number;
}

/**
 * Repositorio de consultas agregadas para los indicadores.
 * Todas las consultas son de solo lectura y usan parámetros posicionales ($1),
 * nunca concatenación de valores.
 *
 * Regla ambiental: un equipo solo cuenta como "entregado a gestor" si existe
 * un movimiento ENTREGA_GESTOR con gestor y evidencia documentada.
 */
@Injectable()
export class IndicadoresRepository {
  constructor(private readonly dataSource: DataSource) {}

  /** Ejecuta una consulta parametrizada de solo lectura. */
  private filas(sql: string, parametros: unknown[] = []): Promise<Fila[]> {
    return this.dataSource.query<Fila[]>(sql, parametros);
  }

  private async fila(sql: string, parametros: unknown[] = []): Promise<Fila> {
    const [primera] = await this.filas(sql, parametros);
    return primera ?? {};
  }

  async totalesEquipos() {
    const r = await this.fila(`
      SELECT COUNT(*) AS total,
             COUNT(*) FILTER (WHERE condicion = 'PENDIENTE') AS pendientes,
             COUNT(*) FILTER (WHERE estado_destino = 'EN_REPARACION') AS en_reparacion,
             COUNT(*) FILTER (WHERE estado_destino = 'REASIGNADO') AS reasignados,
             COUNT(*) FILTER (WHERE estado_destino = 'CONSERVADO') AS conservados
      FROM equipos`);
    return {
      total: num(r.total),
      pendientesDiagnostico: num(r.pendientes),
      enReparacion: num(r.en_reparacion),
      reasignados: num(r.reasignados),
      conservados: num(r.conservados),
    };
  }

  async agruparPor(columna: 'condicion' | 'estado_destino') {
    // La columna proviene de una lista cerrada (no del usuario).
    const filas = await this.filas(
      `SELECT ${columna} AS clave, COUNT(*) AS total FROM equipos GROUP BY ${columna} ORDER BY total DESC`,
    );
    return filas.map((f) => ({ clave: String(f.clave), total: num(f.total) }));
  }

  async porCategoria() {
    const filas = await this.filas(`
      SELECT c.nombre AS categoria, c.tipo,
             COUNT(e.id) AS registrados,
             COUNT(e.id) FILTER (WHERE e.estado_destino = 'REASIGNADO') AS reasignados,
             COUNT(DISTINCT r.equipo_id) AS reparados,
             COUNT(e.id) FILTER (WHERE e.estado_destino = 'ENTREGADO_GESTOR') AS entregados
      FROM categorias c
      LEFT JOIN equipos e ON e.categoria_id = c.id
      LEFT JOIN reparaciones r ON r.equipo_id = e.id AND r.resultado = 'EXITOSA'
      GROUP BY c.id
      HAVING COUNT(e.id) > 0
      ORDER BY registrados DESC`);
    return filas.map((f) => ({
      categoria: f.categoria as string,
      tipo: f.tipo as string,
      registrados: num(f.registrados),
      reasignados: num(f.reasignados),
      reparados: num(f.reparados),
      entregados: num(f.entregados),
    }));
  }

  async entregasDocumentadas() {
    const r = await this.fila(`
      SELECT COUNT(DISTINCT e.id) AS equipos,
             COALESCE(SUM(c.peso_promedio_kg), 0) AS kg
      FROM equipos e
      JOIN categorias c ON c.id = e.categoria_id
      WHERE e.estado_destino = 'ENTREGADO_GESTOR'
        AND EXISTS (
          SELECT 1 FROM movimientos m
          WHERE m.equipo_id = e.id AND m.tipo = 'ENTREGA_GESTOR'
            AND m.gestor_id IS NOT NULL AND m.evidencia IS NOT NULL
        )`);
    return { equipos: num(r.equipos), kg: num(r.kg) };
  }

  async reparaciones() {
    const r = await this.fila(`
      SELECT COUNT(*) AS total,
             COUNT(*) FILTER (WHERE resultado = 'EXITOSA') AS exitosas,
             COUNT(*) FILTER (WHERE resultado = 'FALLIDA') AS fallidas,
             COALESCE(SUM(costo), 0) AS costo_total,
             COALESCE(AVG(costo), 0) AS costo_promedio,
             COALESCE(SUM(ahorro_estimado), 0) AS ahorro
      FROM reparaciones`);
    return {
      total: num(r.total),
      exitosas: num(r.exitosas),
      fallidas: num(r.fallidas),
      costoTotal: num(r.costo_total),
      costoPromedio: Math.round(num(r.costo_promedio) * 100) / 100,
      ahorroReparaciones: num(r.ahorro),
    };
  }

  /** Valor de reposición de los equipos reasignados que NO pasaron por una reparación exitosa. */
  async ahorroReutilizacionDirecta() {
    const r = await this.fila(`
      SELECT COALESCE(SUM(c.valor_reposicion), 0) AS ahorro
      FROM equipos e JOIN categorias c ON c.id = e.categoria_id
      WHERE e.estado_destino = 'REASIGNADO'
        AND NOT EXISTS (SELECT 1 FROM reparaciones r WHERE r.equipo_id = e.id AND r.resultado = 'EXITOSA')`);
    return num(r.ahorro);
  }

  /** Kg de residuo evitado: equipos útiles que se reasignaron o se conservan. */
  async kgResiduoEvitado() {
    const r = await this.fila(`
      SELECT COALESCE(SUM(c.peso_promedio_kg), 0) AS kg
      FROM equipos e JOIN categorias c ON c.id = e.categoria_id
      WHERE e.estado_destino IN ('REASIGNADO', 'CONSERVADO')
        AND e.condicion IN ('FUNCIONAL', 'REUTILIZABLE')`);
    return num(r.kg);
  }

  async equiposRecuperados() {
    const r = await this.fila(`
      SELECT COUNT(DISTINCT e.id) AS total FROM equipos e
      WHERE e.estado_destino = 'REASIGNADO'
         OR EXISTS (SELECT 1 FROM reparaciones r WHERE r.equipo_id = e.id AND r.resultado = 'EXITOSA')`);
    return num(r.total);
  }

  async participacion() {
    const r = await this.fila(`
      SELECT (SELECT COUNT(*) FROM participaciones) AS participaciones,
             (SELECT COALESCE(SUM(equipos_declarados), 0) FROM participaciones) AS declarados,
             (SELECT COUNT(*) FROM equipos WHERE campana_id IS NOT NULL) AS recibidos,
             (SELECT COUNT(*) FROM campanas WHERE estado = 'ACTIVA') AS activas,
             (SELECT COUNT(*) FROM campanas) AS campanas`);
    return {
      participaciones: num(r.participaciones),
      equiposDeclarados: num(r.declarados),
      equiposRecibidos: num(r.recibidos),
      campanasActivas: num(r.activas),
      campanasTotales: num(r.campanas),
    };
  }

  /** Serie de los últimos N meses: equipos registrados, reasignados y entregados a gestor. */
  async serieMensual(meses: number) {
    const filas = await this.filas(
      `
      WITH meses AS (
        SELECT date_trunc('month', CURRENT_DATE) - (n || ' month')::interval AS mes
        FROM generate_series(0, $1::int - 1) AS n
      )
      SELECT to_char(meses.mes, 'YYYY-MM') AS mes,
        (SELECT COUNT(*) FROM equipos e
           WHERE date_trunc('month', e.fecha_ingreso) = meses.mes) AS registrados,
        (SELECT COUNT(*) FROM movimientos m
           WHERE m.tipo = 'REASIGNACION' AND date_trunc('month', m.creado_en) = meses.mes) AS reasignados,
        (SELECT COUNT(*) FROM movimientos m
           WHERE m.tipo = 'REPARACION' AND date_trunc('month', m.creado_en) = meses.mes) AS reparaciones,
        (SELECT COUNT(*) FROM movimientos m
           WHERE m.tipo = 'ENTREGA_GESTOR' AND date_trunc('month', m.creado_en) = meses.mes) AS entregados
      FROM meses ORDER BY meses.mes`,
      [meses],
    );
    return filas.map((f) => ({
      mes: f.mes as string,
      registrados: num(f.registrados),
      reasignados: num(f.reasignados),
      reparaciones: num(f.reparaciones),
      entregados: num(f.entregados),
    }));
  }

  async resultadosCampanas(): Promise<ResultadoCampana[]> {
    const filas = await this.filas(`
      SELECT c.id, c.nombre, c.estado, c.fecha_inicio, c.fecha_fin, c.meta_equipos,
        (SELECT COUNT(*) FROM participaciones p WHERE p.campana_id = c.id) AS participaciones,
        (SELECT COALESCE(SUM(p.equipos_declarados), 0) FROM participaciones p WHERE p.campana_id = c.id) AS declarados,
        (SELECT COUNT(*) FROM equipos e WHERE e.campana_id = c.id) AS recibidos,
        (SELECT COUNT(*) FROM equipos e WHERE e.campana_id = c.id AND e.estado_destino = 'REASIGNADO') AS reasignados,
        (SELECT COALESCE(SUM(cat.peso_promedio_kg), 0) FROM equipos e JOIN categorias cat ON cat.id = e.categoria_id
           WHERE e.campana_id = c.id) AS kg
      FROM campanas c ORDER BY c.fecha_inicio DESC`);
    return filas.map((f): ResultadoCampana => {
      const recibidos = num(f.recibidos);
      const meta = f.meta_equipos === null ? null : num(f.meta_equipos);
      return {
        id: num(f.id),
        nombre: f.nombre as string,
        estado: f.estado as string,
        fechaInicio: f.fecha_inicio as string,
        fechaFin: f.fecha_fin as string,
        metaEquipos: meta,
        avanceMeta: meta ? Math.round((recibidos / meta) * 100) : null,
        participaciones: num(f.participaciones),
        equiposDeclarados: num(f.declarados),
        equiposRecibidos: recibidos,
        equiposReasignados: num(f.reasignados),
        kgRecolectados: num(f.kg),
      };
    });
  }

  async destinosReasignacion(limite = 6) {
    const filas = await this.filas(
      `SELECT destino_actual AS destino, COUNT(*) AS total FROM equipos
       WHERE estado_destino = 'REASIGNADO' AND destino_actual IS NOT NULL
       GROUP BY destino_actual ORDER BY total DESC LIMIT $1`,
      [limite],
    );
    return filas.map((f) => ({
      destino: String(f.destino),
      total: num(f.total),
    }));
  }
}
