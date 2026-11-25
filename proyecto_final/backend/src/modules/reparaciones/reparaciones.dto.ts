import { ApiProperty } from '@nestjs/swagger';
import { Transform, Type } from 'class-transformer';
import {
  IsDateString,
  IsEnum,
  IsNumber,
  IsString,
  Length,
  Max,
  Min,
} from 'class-validator';
import { ResultadoReparacion } from '../../common/enums';

export class CrearReparacionDto {
  @ApiProperty({ example: 'Se reemplazó la fuente de poder.' })
  @Transform(({ value }: { value: unknown }) =>
    typeof value === 'string' ? value.trim() : value,
  )
  @IsString()
  @Length(5, 2000, {
    message: 'Describe la reparación (al menos 5 caracteres).',
  })
  descripcion!: string;

  @ApiProperty({ example: 650 })
  @Type(() => Number)
  @IsNumber(
    { maxDecimalPlaces: 2 },
    { message: 'El costo debe ser un número.' },
  )
  @Min(0, { message: 'El costo no puede ser negativo.' })
  @Max(10_000_000)
  costo!: number;

  @ApiProperty({ enum: ResultadoReparacion })
  @IsEnum(ResultadoReparacion, {
    message: 'Indica si la reparación fue exitosa.',
  })
  resultado!: ResultadoReparacion;

  @ApiProperty({ example: '2026-11-20' })
  @IsDateString({ strict: true }, { message: 'Escribe una fecha válida.' })
  fecha!: string;
}
