import { ApiProperty, PartialType } from '@nestjs/swagger';
import { Transform } from 'class-transformer';
import {
  IsBoolean,
  IsEmail,
  IsEnum,
  IsOptional,
  IsString,
  Length,
  Matches,
} from 'class-validator';
import { Rol } from '../../common/enums';

const trim = ({ value }: { value: unknown }) =>
  typeof value === 'string' ? value.trim() : value;

/** Mínimo 8 caracteres, con al menos una letra y un número. */
export const PASSWORD_REGEX = /^(?=.*[A-Za-z])(?=.*\d).{8,72}$/;
export const PASSWORD_MENSAJE =
  'La contraseña debe tener entre 8 y 72 caracteres, con al menos una letra y un número.';

export class CrearUsuarioDto {
  @ApiProperty({ example: 'María López' })
  @Transform(trim)
  @IsString()
  @Length(3, 120, { message: 'El nombre debe tener entre 3 y 120 caracteres.' })
  nombre!: string;

  @ApiProperty({ example: 'maria@institucion.edu.mx' })
  @Transform(({ value }: { value: unknown }) =>
    typeof value === 'string' ? value.trim().toLowerCase() : value,
  )
  @IsEmail({}, { message: 'Escribe un correo electrónico válido.' })
  email!: string;

  @ApiProperty({ example: 'Segura2026' })
  @IsString()
  @Matches(PASSWORD_REGEX, { message: PASSWORD_MENSAJE })
  password!: string;

  @ApiProperty({ enum: Rol })
  @IsEnum(Rol, { message: 'Selecciona un rol válido.' })
  rol!: Rol;
}

export class ActualizarUsuarioDto extends PartialType(CrearUsuarioDto) {
  @ApiProperty({ required: false })
  @IsOptional()
  @IsBoolean()
  activo?: boolean;
}
