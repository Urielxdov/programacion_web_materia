import { ApiProperty, ApiPropertyOptional, PartialType } from '@nestjs/swagger';
import { Transform } from 'class-transformer';
import {
  IsEnum,
  IsNumber,
  IsOptional,
  IsString,
  Length,
  Max,
  MaxLength,
  Min,
} from 'class-validator';
import { TipoCategoria } from '../../common/enums';

const trim = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() : value;

export class CrearCategoriaDto {
  @ApiProperty({ example: 'Laptop' })
  @Transform(trim)
  @IsString()
  @Length(2, 80, { message: 'El nombre debe tener entre 2 y 80 caracteres.' })
  nombre!: string;

  @ApiProperty({ enum: TipoCategoria })
  @IsEnum(TipoCategoria, { message: 'Selecciona un tipo de categoría válido.' })
  tipo!: TipoCategoria;

  @ApiProperty({ example: 2.2 })
  @IsNumber({ maxDecimalPlaces: 2 }, { message: 'El peso debe ser un número.' })
  @Min(0)
  @Max(5000)
  pesoPromedioKg!: number;

  @ApiProperty({ example: 14000 })
  @IsNumber(
    { maxDecimalPlaces: 2 },
    { message: 'El valor de reposición debe ser un número.' },
  )
  @Min(0)
  @Max(10_000_000)
  valorReposicion!: number;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(trim)
  @IsString()
  @MaxLength(500)
  descripcion?: string;
}

export class ActualizarCategoriaDto extends PartialType(CrearCategoriaDto) {}

export class CrearGestorDto {
  @ApiProperty({ example: 'Recicladora Ecológica del Norte' })
  @Transform(trim)
  @IsString()
  @Length(3, 160, { message: 'El nombre debe tener entre 3 y 160 caracteres.' })
  nombre!: string;

  @ApiProperty({ example: 'Reciclador de RAEE' })
  @Transform(trim)
  @IsString()
  @Length(3, 120)
  tipo!: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(trim)
  @IsString()
  @MaxLength(160)
  contacto?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(trim)
  @IsString()
  @MaxLength(255)
  direccion?: string;
}

export class ActualizarGestorDto extends PartialType(CrearGestorDto) {}

export class VerificarGestorDto {
  @ApiProperty({
    example: 'SEMARNAT 19-II-123-2026',
    description:
      'Número de autorización o documento que respalda la verificación',
  })
  @Transform(trim)
  @IsString()
  @Length(3, 160, {
    message: 'Escribe el número de autorización o documento de verificación.',
  })
  documentoVerificacion!: string;
}
