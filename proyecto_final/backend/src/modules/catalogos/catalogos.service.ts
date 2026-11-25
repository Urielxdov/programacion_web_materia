import { ConflictException, Injectable } from '@nestjs/common';
import { UsuariosRepository } from '../usuarios/usuarios.repository';
import {
  CategoriasRepository,
  GestoresRepository,
} from './catalogos.repository';
import {
  ActualizarCategoriaDto,
  ActualizarGestorDto,
  CrearCategoriaDto,
  CrearGestorDto,
  VerificarGestorDto,
} from './catalogos.dto';
import { Categoria } from './categoria.entity';
import { Gestor } from './gestor.entity';

@Injectable()
export class CategoriasService {
  constructor(private readonly categorias: CategoriasRepository) {}

  listar(): Promise<Categoria[]> {
    return this.categorias.listar();
  }

  async crear(dto: CrearCategoriaDto): Promise<Categoria> {
    if (await this.categorias.existeNombre(dto.nombre)) {
      throw new ConflictException('Ya existe una categoría con ese nombre.');
    }
    return this.categorias.guardar(
      this.categorias.crear({ ...dto, descripcion: dto.descripcion ?? null }),
    );
  }

  async actualizar(
    id: number,
    dto: ActualizarCategoriaDto,
  ): Promise<Categoria> {
    const categoria = await this.categorias.obtenerPorId(id);
    if (dto.nombre && (await this.categorias.existeNombre(dto.nombre, id))) {
      throw new ConflictException('Ya existe una categoría con ese nombre.');
    }
    Object.assign(categoria, dto);
    return this.categorias.guardar(categoria);
  }
}

@Injectable()
export class GestoresService {
  constructor(
    private readonly gestores: GestoresRepository,
    private readonly usuarios: UsuariosRepository,
  ) {}

  listar(soloVerificados: boolean): Promise<Gestor[]> {
    return this.gestores.listar(soloVerificados);
  }

  crear(dto: CrearGestorDto): Promise<Gestor> {
    // Todo gestor nace sin verificar: la verificación requiere un documento.
    return this.gestores.guardar(
      this.gestores.crear({
        nombre: dto.nombre,
        tipo: dto.tipo,
        contacto: dto.contacto ?? null,
        direccion: dto.direccion ?? null,
        verificado: false,
      }),
    );
  }

  async actualizar(id: number, dto: ActualizarGestorDto): Promise<Gestor> {
    const gestor = await this.gestores.obtenerPorId(id);
    Object.assign(gestor, dto);
    return this.gestores.guardar(gestor);
  }

  async verificar(
    id: number,
    dto: VerificarGestorDto,
    usuarioId: number,
  ): Promise<Gestor> {
    const gestor = await this.gestores.obtenerPorId(id);
    const responsable = await this.usuarios.obtenerPorId(usuarioId);
    gestor.verificar(dto.documentoVerificacion, responsable);
    await this.gestores.guardar(gestor);
    return this.gestores.obtenerPorId(id, { verificadoPor: true });
  }

  async revocar(id: number): Promise<Gestor> {
    const gestor = await this.gestores.obtenerPorId(id);
    gestor.revocarVerificacion();
    return this.gestores.guardar(gestor);
  }
}
