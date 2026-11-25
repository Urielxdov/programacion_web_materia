import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { Transform, Type } from 'class-transformer';
import {
  IsBoolean,
  IsIn,
  IsInt,
  IsOptional,
  IsString,
  Length,
  MaxLength,
  Min,
  ValidateIf,
} from 'class-validator';
import { TipoMovimiento } from '../../common/enums';

const vacioAUndefined = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() || undefined : value;

/** Decisiones de destino que una persona responsable puede registrar. */
export const DECISIONES = [
  TipoMovimiento.ENVIO_REPARACION,
  TipoMovimiento.REASIGNACION,
  TipoMovimiento.CONSERVACION,
  TipoMovimiento.ENTREGA_GESTOR,
] as const;
export type TipoDecision = (typeof DECISIONES)[number];

export class RegistrarDecisionDto {
  @ApiProperty({ enum: DECISIONES })
  @IsIn(DECISIONES, { message: 'Selecciona una decisión válida.' })
  tipo!: TipoDecision;

  @ApiPropertyOptional({ example: 'Laboratorio de redes, edificio C' })
  @ValidateIf(
    (o: RegistrarDecisionDto) =>
      o.tipo === TipoMovimiento.REASIGNACION ||
      o.tipo === TipoMovimiento.CONSERVACION,
  )
  @Transform(vacioAUndefined)
  @IsString({ message: 'Indica el área o ubicación de destino.' })
  @Length(3, 200, { message: 'Indica el área o ubicación de destino.' })
  destino?: string;

  @ApiPropertyOptional({
    description: 'Gestor verificado (obligatorio para ENTREGA_GESTOR)',
  })
  @ValidateIf(
    (o: RegistrarDecisionDto) => o.tipo === TipoMovimiento.ENTREGA_GESTOR,
  )
  @Type(() => Number)
  @IsInt({ message: 'Selecciona el gestor que recibe el equipo.' })
  @Min(1, { message: 'Selecciona el gestor que recibe el equipo.' })
  gestorId?: number;

  @ApiPropertyOptional({
    example: 'Oficio DA-145/2026',
    description:
      'Documento que respalda el movimiento (obligatorio para ENTREGA_GESTOR y REASIGNACION)',
  })
  @ValidateIf(
    (o: RegistrarDecisionDto) =>
      o.tipo === TipoMovimiento.ENTREGA_GESTOR ||
      o.tipo === TipoMovimiento.REASIGNACION,
  )
  @Transform(vacioAUndefined)
  @IsString({ message: 'Escribe el folio u oficio que respalda la entrega.' })
  @Length(3, 200, {
    message: 'Escribe el folio u oficio que respalda la entrega.',
  })
  evidencia?: string;

  @ApiPropertyOptional({
    description: 'Confirmación de borrado seguro de datos',
  })
  @IsOptional()
  @IsBoolean()
  datosBorrados?: boolean;

  @ApiPropertyOptional()
  @IsOptional()
  @Transform(vacioAUndefined)
  @IsString()
  @MaxLength(1000)
  notas?: string;
}
