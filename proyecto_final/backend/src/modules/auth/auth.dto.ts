import { ApiProperty } from '@nestjs/swagger';
import { Transform } from 'class-transformer';
import { IsEmail, IsString, Length } from 'class-validator';

export class LoginDto {
  @ApiProperty({ example: 'admin@ewaste360.mx' })
  @Transform(({ value }: { value: unknown }) =>
    typeof value === 'string' ? value.trim().toLowerCase() : value,
  )
  @IsEmail({}, { message: 'Escribe un correo electrónico válido.' })
  email!: string;

  @ApiProperty({ example: 'Demo2026!' })
  @IsString()
  @Length(1, 72, { message: 'Escribe tu contraseña.' })
  password!: string;
}
