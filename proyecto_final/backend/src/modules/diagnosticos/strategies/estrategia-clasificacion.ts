import { CondicionEquipo } from '../../../common/enums';
import type { Categoria } from '../../catalogos/categoria.entity';

/** Datos que las estrategias evalúan para decidir el destino de un equipo. */
export interface ContextoClasificacion {
  categoria: Categoria;
  enciende: boolean;
  danoEstructural: boolean;
  componentesPeligrosos: boolean;
  costoReparacionEstimado: number;
}

export interface ResultadoClasificacion {
  condicion: CondicionEquipo;
  justificacion: string;
  estrategia: string;
}

/**
 * Patrón Strategy: contrato común de todas las reglas de clasificación.
 *
 * Problema que resuelve: los criterios para decidir si un equipo se repara,
 * se reutiliza o va a gestión especializada cambian con el tiempo y entre
 * instituciones. Cada criterio vive en su propia clase; agregar uno nuevo no
 * obliga a modificar el servicio de diagnósticos (principio abierto/cerrado).
 */
export abstract class EstrategiaClasificacion {
  /** Menor número = se evalúa primero. */
  abstract readonly prioridad: number;
  abstract readonly nombre: string;

  abstract aplica(ctx: ContextoClasificacion): boolean;

  protected abstract condicion(): CondicionEquipo;
  protected abstract justificar(ctx: ContextoClasificacion): string;

  clasificar(ctx: ContextoClasificacion): ResultadoClasificacion {
    return {
      condicion: this.condicion(),
      justificacion: this.justificar(ctx),
      estrategia: this.nombre,
    };
  }
}

/** Porcentaje del valor de reposición por debajo del cual conviene reparar. */
export const UMBRAL_REPARACION = 0.5;

const mxn = (n: number) =>
  n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

/** 1. Seguridad primero: materiales peligrosos o daño estructural → gestión especializada. */
export class GestionEspecializadaStrategy extends EstrategiaClasificacion {
  readonly prioridad = 1;
  readonly nombre = 'GestionEspecializada';

  aplica(ctx: ContextoClasificacion): boolean {
    return (
      ctx.categoria.esPeligrosa() ||
      ctx.componentesPeligrosos ||
      ctx.danoEstructural
    );
  }

  protected condicion() {
    return CondicionEquipo.GESTION_ESPECIALIZADA;
  }

  protected justificar(ctx: ContextoClasificacion): string {
    if (ctx.categoria.esPeligrosa() || ctx.componentesPeligrosos) {
      return 'Contiene componentes peligrosos (baterías, químicos). Debe entregarse a un gestor verificado.';
    }
    return 'Presenta daño estructural que impide repararlo de forma segura. Requiere gestión especializada.';
  }
}

/** 2. Si funciona y no necesita inversión, se puede usar de inmediato. */
export class ReutilizacionDirectaStrategy extends EstrategiaClasificacion {
  readonly prioridad = 2;
  readonly nombre = 'ReutilizacionDirecta';

  aplica(ctx: ContextoClasificacion): boolean {
    return ctx.enciende && ctx.costoReparacionEstimado === 0;
  }

  protected condicion() {
    return CondicionEquipo.FUNCIONAL;
  }

  protected justificar(): string {
    return 'Funciona correctamente y no requiere reparación. Puede reasignarse a otra área.';
  }
}

/** 3. Si repararlo cuesta menos que el umbral del valor de reposición, conviene repararlo. */
export class ReparacionStrategy extends EstrategiaClasificacion {
  readonly prioridad = 3;
  readonly nombre = 'Reparacion';

  aplica(ctx: ContextoClasificacion): boolean {
    const limite = ctx.categoria.valorReposicion * UMBRAL_REPARACION;
    return ctx.costoReparacionEstimado <= limite;
  }

  protected condicion() {
    return CondicionEquipo.REPARABLE;
  }

  protected justificar(ctx: ContextoClasificacion): string {
    const ahorro = ctx.categoria.valorReposicion - ctx.costoReparacionEstimado;
    return `Repararlo cuesta ${mxn(ctx.costoReparacionEstimado)}, menos del ${UMBRAL_REPARACION * 100}% de un equipo nuevo. Ahorro estimado: ${mxn(ahorro)}.`;
  }
}

/** 4. Respaldo: la reparación no es rentable. */
export class ReparacionNoRentableStrategy extends EstrategiaClasificacion {
  readonly prioridad = 99;
  readonly nombre = 'ReparacionNoRentable';

  aplica(): boolean {
    return true;
  }

  protected condicion() {
    return CondicionEquipo.GESTION_ESPECIALIZADA;
  }

  protected justificar(ctx: ContextoClasificacion): string {
    return `Repararlo (${mxn(ctx.costoReparacionEstimado)}) cuesta más del ${UMBRAL_REPARACION * 100}% de un equipo nuevo (${mxn(ctx.categoria.valorReposicion)}). Se recomienda gestión especializada y aprovechar sus componentes.`;
  }
}
