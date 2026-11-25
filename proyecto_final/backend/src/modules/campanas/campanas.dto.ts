import { ApiProperty, ApiPropertyOptional, PartialType } from '@nestjs/swagger';
import { Transform, Type } from 'class-transformer';
import {
  ArrayMinSize,
  IsArray,
  IsDateString,
  IsEnum,
  IsInt,
  IsOptional,
  IsString,
  Length,
  Max,
  MaxLength,
  Min,
} from 'class-validator';
import { EstadoCampana } from '../../common/enums';

const trim = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() : value;
const vacioAUndefined = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() || undefined : value;

export class CrearCampanaDto {
  @ApiProperty({ example: 'Reciclatón de primavera 2026' })
  @Transform(trim)
  @IsString()
  @Length(3, 160, { message: 'El nombre debe tener entre 3 y 160 caracteres.' })
  nombre!: string;

  @ApiProperty()
  @Transform(trim)
  @IsString()
  @Length(10, 3000, {
    message: 'La descripción debe tener al menos 10 caracteres.',
  })
  descripcion!: string;

  @ApiProperty({ example: '2026-03-10' })
  @IsDateString(
    { strict: true },
    { message: 'Escribe una fecha de inicio válida.' },
  )
  fechaInicio!: string;

  @ApiProperty({ example: '2026-03-14' })
  @IsDateString(
    { strict: true },
    { message: 'Escribe una fecha de fin válida.' },
  )
  fechaFin!: string;

  @ApiProperty({ example: 'Explanada principal, edificio A' })
  @Transform(trim)
  @IsString()
  @Length(3, 200, { message: 'Escribe la ubicación.' })
  ubicacion!: string;

  @ApiPropertyOptional({ example: '9:00 a 14:00 h' })
  @IsOptional()
  @Transform(vacioAUndefined)
  @IsString()
  @MaxLength(120)
  horario?: string;

  @ApiPropertyOptional({ example: 150 })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(100000)
  metaEquipos?: number;

  @ApiProperty({
    example: [1, 2, 3],
    description: 'Ids de las categorías aceptadas',
  })
  @IsArray()
  @ArrayMinSize(1, {
    message: 'Selecciona al menos un tipo de equipo aceptado.',
  })
  @Type(() => Number)
  @IsInt({ each: true })
  categoriasIds!: number[];
}

export class ActualizarCampanaDto extends PartialType(CrearCampanaDto) {
  @ApiPropertyOptional({ enum: EstadoCampana })
  @IsOptional()
  @IsEnum(EstadoCampana, { message: 'Selecciona un estado válido.' })
  estado?: EstadoCampana;
}

export class FiltroCampanasDto {
  @ApiPropertyOptional({ enum: EstadoCampana })
  @IsOptional()
  @IsEnum(EstadoCampana)
  estado?: EstadoCampana;
}

/** Registro público de participación: solo datos mínimos. */
export class CrearParticipacionDto {
  @ApiProperty({ example: 'Juan Pérez' })
  @Transform(trim)
  @IsString()
  @Length(2, 120, { message: 'Escribe tu nombre (2 a 120 caracteres).' })
  nombreParticipante!: string;

  @ApiPropertyOptional({ example: 'juan@correo.com o 8441234567' })
  @IsOptional()
  @Transform(vacioAUndefined)
  @IsString()
  @MaxLength(160)
  contacto?: string;

  @ApiProperty({ example: 2 })
  @Type(() => Number)
  @IsInt({ message: 'Indica cuántos equipos llevarás.' })
  @Min(1, { message: 'Debes llevar al menos un equipo.' })
  @Max(500)
  equiposDeclarados!: number;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(vacioAUndefined)
  @IsString()
  @MaxLength(500)
  comentario?: string;
}
