import { Injectable } from '@nestjs/common';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { EquipoDiagnosticadoEvent } from '../../common/events/equipo.events';
import { EquiposRepository } from '../equipos/equipos.repository';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import { Diagnostico } from './diagnostico.entity';
import { CrearDiagnosticoDto } from './diagnosticos.dto';
import { DiagnosticosRepository } from './diagnosticos.repository';
import { ClasificadorDestino } from './strategies/clasificador-destino';
import { ResultadoClasificacion } from './strategies/estrategia-clasificacion';

const ETIQUETAS: Record<string, string> = {
  FUNCIONAL: 'funcional',
  REPARABLE: 'reparable',
  REUTILIZABLE: 'reutilizable',
  GESTION_ESPECIALIZADA: 'gestión especializada',
};

@Injectable()
export class DiagnosticosService {
  constructor(
    private readonly diagnosticos: DiagnosticosRepository,
    private readonly equipos: EquiposRepository,
    private readonly usuarios: UsuariosRepository,
    private readonly clasificador: ClasificadorDestino,
    private readonly eventos: EventEmitter2,
  ) {}

  /** Vista previa: calcula la recomendación sin guardar nada (la interfaz la muestra en vivo). */
  async simular(
    equipoId: number,
    dto: CrearDiagnosticoDto,
  ): Promise<ResultadoClasificacion> {
    const equipo = await this.equipos.obtenerPorId(equipoId);
    return this.clasificador.clasificar({
      ...dto,
      categoria: equipo.categoria,
    });
  }

  async registrar(
    equipoId: number,
    dto: CrearDiagnosticoDto,
    usuarioId: number,
  ): Promise<Diagnostico> {
    const resultado = await this.equipos.transaccion(async (manager) => {
      const equipo = await this.equipos.obtenerParaActualizar(
        equipoId,
        manager,
      );
      const clasificacion = this.clasificador.clasificar({
        ...dto,
        categoria: equipo.categoria,
      });
      equipo.aplicarDiagnostico(clasificacion.condicion);
      await this.equipos.guardar(equipo, manager);

      const diagnostico = this.diagnosticos.crear({
        equipo,
        responsable: await this.usuarios.obtenerPorId(
          usuarioId,
          undefined,
          manager,
        ),
        descripcion: dto.descripcion,
        enciende: dto.enciende,
        danoEstructural: dto.danoEstructural,
        componentesPeligrosos: dto.componentesPeligrosos,
        costoReparacionEstimado: dto.costoReparacionEstimado,
        recomendacion: clasificacion.condicion,
        estrategiaAplicada: clasificacion.estrategia,
        justificacion: clasificacion.justificacion,
      });
      const guardado = await this.diagnosticos.guardar(diagnostico, manager);
      return { guardado, equipo, clasificacion };
    });

    await this.eventos.emitAsync(
      'equipo.diagnosticado',
      new EquipoDiagnosticadoEvent(
        equipoId,
        usuarioId,
        resultado.equipo.estadoDestino,
        ETIQUETAS[resultado.clasificacion.condicion] ??
          resultado.clasificacion.condicion,
        resultado.clasificacion.justificacion,
      ),
    );
    return resultado.guardado;
  }
}
