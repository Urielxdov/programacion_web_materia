import { Injectable } from '@nestjs/common';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { ResultadoReparacion } from '../../common/enums';
import { ReglaNegocioError } from '../../common/errors/regla-negocio.error';
import { EquipoReparadoEvent } from '../../common/events/equipo.events';
import { DiagnosticosRepository } from '../diagnosticos/diagnosticos.repository';
import { EquiposRepository } from '../equipos/equipos.repository';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import { Reparacion } from './reparacion.entity';
import { CrearReparacionDto } from './reparaciones.dto';
import { ReparacionesRepository } from './reparaciones.repository';

@Injectable()
export class ReparacionesService {
  constructor(
    private readonly reparaciones: ReparacionesRepository,
    private readonly equipos: EquiposRepository,
    private readonly diagnosticos: DiagnosticosRepository,
    private readonly usuarios: UsuariosRepository,
    private readonly eventos: EventEmitter2,
  ) {}

  async registrar(
    equipoId: number,
    dto: CrearReparacionDto,
    usuarioId: number,
  ): Promise<Reparacion> {
    if (dto.fecha > new Date().toISOString().slice(0, 10)) {
      throw new ReglaNegocioError(
        'La fecha de la reparación no puede ser futura.',
      );
    }
    const exitosa = dto.resultado === ResultadoReparacion.EXITOSA;

    const guardada = await this.reparaciones.transaccion(async (manager) => {
      const equipo = await this.equipos.obtenerParaActualizar(
        equipoId,
        manager,
      );
      equipo.registrarResultadoReparacion(exitosa);
      await this.equipos.guardar(equipo, manager);

      const reparacion = this.reparaciones.crear({
        equipo,
        diagnostico: await this.diagnosticos.ultimoDeEquipo(equipoId),
        responsable: await this.usuarios.obtenerPorId(
          usuarioId,
          undefined,
          manager,
        ),
        descripcion: dto.descripcion,
        costo: dto.costo,
        resultado: dto.resultado,
        fecha: dto.fecha,
        ahorroEstimado: Reparacion.calcularAhorro(
          equipo.categoria.valorReposicion,
          dto.costo,
          exitosa,
        ),
      });
      return this.reparaciones.guardar(reparacion, manager);
    });

    await this.eventos.emitAsync(
      'equipo.reparado',
      new EquipoReparadoEvent(equipoId, usuarioId, exitosa, dto.costo),
    );
    return guardada;
  }
}
