import { CondicionEquipo, TipoCategoria } from '../../../common/enums';
import { ReglaNegocioError } from '../../../common/errors/regla-negocio.error';

export interface DatosNuevoEquipo {
  marca: string;
  modelo: string;
  numeroSerie?: string | null;
}

/**
 * Perfil de un tipo de dispositivo. Cada subclase define el comportamiento
 * propio de su tipo (polimorfismo): validaciones, condición inicial, si guarda
 * datos personales y recomendaciones de entrega segura.
 */
export abstract class PerfilEquipo {
  abstract readonly tipo: TipoCategoria;
  abstract readonly descripcion: string;

  /** ¿El dispositivo puede almacenar información que deba borrarse antes de entregarlo? */
  requiereBorradoDatos(): boolean {
    return false;
  }

  /** Condición con la que entra al inventario antes del diagnóstico. */
  condicionInicial(): CondicionEquipo {
    return CondicionEquipo.PENDIENTE;
  }

  /** Validaciones específicas del tipo (además de las del DTO). */
  validar(_datos: DatosNuevoEquipo): void {}

  recomendacionesEntrega(): string[] {
    return [
      'Entrega el equipo completo, sin desarmarlo.',
      'Anota el folio del equipo en la hoja de entrega.',
    ];
  }
}

export class PerfilComputo extends PerfilEquipo {
  readonly tipo = TipoCategoria.COMPUTO;
  readonly descripcion =
    'Equipo de cómputo (computadoras, laptops, servidores)';

  override requiereBorradoDatos(): boolean {
    return true;
  }

  override validar(datos: DatosNuevoEquipo): void {
    if (!datos.numeroSerie?.trim()) {
      throw new ReglaNegocioError(
        'Los equipos de cómputo requieren número de serie para asegurar su trazabilidad.',
      );
    }
  }

  override recomendacionesEntrega(): string[] {
    return [
      'Respalda la información importante.',
      'Borra los datos de forma segura (formateo completo o herramienta de borrado).',
      'Cierra sesión en cuentas institucionales y retira contraseñas.',
      ...super.recomendacionesEntrega(),
    ];
  }
}

export class PerfilMovil extends PerfilEquipo {
  readonly tipo = TipoCategoria.MOVIL;
  readonly descripcion = 'Dispositivo móvil (celulares, tabletas)';

  override requiereBorradoDatos(): boolean {
    return true;
  }

  override recomendacionesEntrega(): string[] {
    return [
      'Restablece el dispositivo a valores de fábrica.',
      'Retira la tarjeta SIM y la memoria microSD.',
      'Desvincula la cuenta (Google / Apple) del dispositivo.',
      ...super.recomendacionesEntrega(),
    ];
  }
}

export class PerfilEnergia extends PerfilEquipo {
  readonly tipo = TipoCategoria.ENERGIA;
  readonly descripcion = 'Baterías, pilas y fuentes de energía';

  /** Las baterías contienen materiales peligrosos: siempre van a gestión especializada. */
  override condicionInicial(): CondicionEquipo {
    return CondicionEquipo.GESTION_ESPECIALIZADA;
  }

  override recomendacionesEntrega(): string[] {
    return [
      'Cubre las terminales con cinta aislante para evitar cortocircuitos.',
      'No perfores, golpees ni expongas las baterías al calor.',
      'Si una batería está inflada, colócala en un recipiente no inflamable.',
      'Entrégalas solo a un gestor verificado.',
    ];
  }
}

export class PerfilPeriferico extends PerfilEquipo {
  readonly tipo = TipoCategoria.PERIFERICO;
  readonly descripcion =
    'Periféricos (monitores, impresoras, teclados, ratones)';

  override recomendacionesEntrega(): string[] {
    return [
      'Retira cartuchos y tóner de las impresoras y entrégalos por separado.',
      'No rompas las pantallas: algunos monitores antiguos contienen sustancias peligrosas.',
      ...super.recomendacionesEntrega(),
    ];
  }
}

export class PerfilCableado extends PerfilEquipo {
  readonly tipo = TipoCategoria.CABLEADO;
  readonly descripcion = 'Cables, cargadores y adaptadores';

  override recomendacionesEntrega(): string[] {
    return [
      'Agrupa y enrolla los cables por tipo.',
      'Separa los cargadores que tengan daños visibles.',
    ];
  }
}

export class PerfilGenerico extends PerfilEquipo {
  readonly tipo = TipoCategoria.OTRO;
  readonly descripcion = 'Otro dispositivo electrónico';
}
