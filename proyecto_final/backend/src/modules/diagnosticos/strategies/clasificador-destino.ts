import { Inject, Injectable } from '@nestjs/common';
import {
  ContextoClasificacion,
  EstrategiaClasificacion,
  ResultadoClasificacion,
} from './estrategia-clasificacion';

export const ESTRATEGIAS_CLASIFICACION = Symbol('ESTRATEGIAS_CLASIFICACION');

/**
 * Contexto del patrón Strategy: recorre las estrategias en orden de prioridad
 * y aplica la primera que corresponda. No conoce los detalles de ninguna.
 */
@Injectable()
export class ClasificadorDestino {
  private readonly estrategias: EstrategiaClasificacion[];

  constructor(
    @Inject(ESTRATEGIAS_CLASIFICACION) estrategias: EstrategiaClasificacion[],
  ) {
    this.estrategias = [...estrategias].sort(
      (a, b) => a.prioridad - b.prioridad,
    );
  }

  clasificar(ctx: ContextoClasificacion): ResultadoClasificacion {
    const estrategia = this.estrategias.find((e) => e.aplica(ctx));
    if (!estrategia) {
      throw new Error('No hay una estrategia de clasificación aplicable.');
    }
    return estrategia.clasificar(ctx);
  }
}
