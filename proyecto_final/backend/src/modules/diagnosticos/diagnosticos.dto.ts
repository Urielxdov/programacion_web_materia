import { ApiProperty } from '@nestjs/swagger';
import { Transform, Type } from 'class-transformer';
import {
  IsBoolean,
  IsNumber,
  IsString,
  Length,
  Max,
  Min,
} from 'class-validator';

export class CrearDiagnosticoDto {
  @ApiProperty({ example: 'No enciende; fuente de poder dañada.' })
  @Transform(({ value }: { value: unknown }) =>
    typeof value === 'string' ? value.trim() : value,
  )
  @IsString()
  @Length(5, 2000, {
    message: 'Describe el diagnóstico (al menos 5 caracteres).',
  })
  descripcion!: string;

  @ApiProperty({ description: '¿El equipo enciende / funciona?' })
  @IsBoolean({ message: 'Indica si el equipo enciende.' })
  enciende!: boolean;

  @ApiProperty({
    description:
      '¿Tiene daño estructural grave (carcasa rota, placa quemada, pantalla estrellada)?',
  })
  @IsBoolean()
  danoEstructural!: boolean;

  @ApiProperty({
    description: '¿Tiene componentes peligrosos (batería inflada, fugas)?',
  })
  @IsBoolean()
  componentesPeligrosos!: boolean;

  @ApiProperty({
    example: 850,
    description: 'Costo estimado de reparación en MXN (0 si no requiere)',
  })
  @Type(() => Number)
  @IsNumber(
    { maxDecimalPlaces: 2 },
    { message: 'El costo debe ser un número.' },
  )
  @Min(0, { message: 'El costo no puede ser negativo.' })
  @Max(10_000_000)
  costoReparacionEstimado!: number;
}

export class SimularDiagnosticoDto extends CrearDiagnosticoDto {}
