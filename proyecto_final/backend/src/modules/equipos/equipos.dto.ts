import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { Transform, Type } from 'class-transformer';
import {
  IsDateString,
  IsEnum,
  IsIn,
  IsInt,
  IsOptional,
  IsString,
  Length,
  Max,
  MaxLength,
  Min,
} from 'class-validator';
import { CondicionEquipo, EstadoDestino } from '../../common/enums';

const trim = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() : value;
const vacioANull = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() || undefined : value;

export class CrearEquipoDto {
  @ApiProperty({ example: 1, description: 'Id de la categoría' })
  @Type(() => Number)
  @IsInt({ message: 'Selecciona una categoría.' })
  @Min(1, { message: 'Selecciona una categoría.' })
  categoriaId!: number;

  @ApiProperty({ example: 'Dell' })
  @Transform(trim)
  @IsString()
  @Length(1, 80, { message: 'Escribe la marca (máximo 80 caracteres).' })
  marca!: string;

  @ApiProperty({ example: 'Latitude 5490' })
  @Transform(trim)
  @IsString()
  @Length(1, 120, { message: 'Escribe el modelo (máximo 120 caracteres).' })
  modelo!: string;

  @ApiPropertyOptional({ example: 'CN-0X1Y2Z' })
  @IsOptional()
  @Transform(vacioANull)
  @IsString()
  @MaxLength(120)
  numeroSerie?: string;

  @ApiProperty({
    example: '2026-11-20',
    description: 'Fecha de ingreso (AAAA-MM-DD)',
  })
  @IsDateString(
    { strict: true },
    { message: 'Escribe una fecha de ingreso válida.' },
  )
  fechaIngreso!: string;

  @ApiPropertyOptional({ example: 'Almacén edificio B' })
  @IsOptional()
  @Transform(vacioANull)
  @IsString()
  @MaxLength(160)
  ubicacion?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(vacioANull)
  @IsString()
  @MaxLength(1000)
  observaciones?: string;

  @ApiPropertyOptional({
    description: 'Campaña en la que se recibió el equipo',
  })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  campanaId?: number;
}

/**
 * Campos que se pueden editar directamente. La condición y el destino NO están
 * aquí: solo cambian mediante diagnósticos, reparaciones y movimientos.
 */
export class ActualizarEquipoDto {
  @ApiPropertyOptional()
  @IsOptional()
  @Transform(trim)
  @IsString()
  @Length(1, 80)
  marca?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(trim)
  @IsString()
  @Length(1, 120)
  modelo?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(vacioANull)
  @IsString()
  @MaxLength(120)
  numeroSerie?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsDateString(
    { strict: true },
    { message: 'Escribe una fecha de ingreso válida.' },
  )
  fechaIngreso?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(vacioANull)
  @IsString()
  @MaxLength(160)
  ubicacion?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(vacioANull)
  @IsString()
  @MaxLength(1000)
  observaciones?: string;
}

export class FiltrosEquiposDto {
  @ApiPropertyOptional({
    description: 'Texto a buscar en folio, marca, modelo, serie o ubicación',
  })
  @IsOptional()
  @IsString()
  @MaxLength(100)
  q?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  categoriaId?: number;

  @ApiPropertyOptional({ enum: CondicionEquipo })
  @IsOptional()
  @IsEnum(CondicionEquipo)
  condicion?: CondicionEquipo;

  @ApiPropertyOptional({ enum: EstadoDestino })
  @IsOptional()
  @IsEnum(EstadoDestino)
  estadoDestino?: EstadoDestino;

  @ApiPropertyOptional()
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  campanaId?: number;

  @ApiPropertyOptional({ enum: ['recientes', 'antiguos'] })
  @IsOptional()
  @IsIn(['recientes', 'antiguos'])
  orden?: 'recientes' | 'antiguos';

  @ApiPropertyOptional({ default: 1 })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  pagina?: number;

  @ApiPropertyOptional({ default: 20 })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(100)
  porPagina?: number;
}
