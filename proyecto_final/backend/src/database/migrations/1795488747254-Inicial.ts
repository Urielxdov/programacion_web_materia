import { MigrationInterface, QueryRunner } from 'typeorm';

export class Inicial1795488747254 implements MigrationInterface {
  name = 'Inicial1795488747254';

  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(
      `CREATE TYPE "public"."rol_usuario" AS ENUM('CAPTURISTA', 'RESPONSABLE', 'ADMIN')`,
    );
    await queryRunner.query(
      `CREATE TABLE "usuarios" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "nombre" character varying(120) NOT NULL, "email" character varying(160) NOT NULL, "password_hash" character varying(255) NOT NULL, "rol" "public"."rol_usuario" NOT NULL DEFAULT 'CAPTURISTA', "activo" boolean NOT NULL DEFAULT true, CONSTRAINT "PK_d7281c63c176e152e4c531594a8" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE UNIQUE INDEX "IDX_446adfc18b35418aac32ae0b7b" ON "usuarios" ("email") `,
    );
    await queryRunner.query(
      `CREATE TABLE "gestores" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "nombre" character varying(160) NOT NULL, "tipo" character varying(120) NOT NULL, "contacto" character varying(160), "direccion" character varying(255), "verificado" boolean NOT NULL DEFAULT false, "documento_verificacion" character varying(160), "fecha_verificacion" TIMESTAMP WITH TIME ZONE, "verificado_por_id" integer, CONSTRAINT "PK_bd3055e02e8ff03ffc118b8c473" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE TYPE "public"."tipo_categoria" AS ENUM('COMPUTO', 'PERIFERICO', 'MOVIL', 'ENERGIA', 'CABLEADO', 'OTRO')`,
    );
    await queryRunner.query(
      `CREATE TABLE "categorias" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "nombre" character varying(80) NOT NULL, "tipo" "public"."tipo_categoria" NOT NULL, "peso_promedio_kg" numeric(8,2) NOT NULL DEFAULT '0', "valor_reposicion" numeric(12,2) NOT NULL DEFAULT '0', "descripcion" text, CONSTRAINT "CHK_f74942c130521e8e55aa27fcac" CHECK ("valor_reposicion" >= 0), CONSTRAINT "CHK_694f3f994e36d1cddb03ea85dd" CHECK ("peso_promedio_kg" >= 0), CONSTRAINT "PK_3886a26251605c571c6b4f861fe" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE UNIQUE INDEX "IDX_ccdf6cd1a34ea90a7233325063" ON "categorias" ("nombre") `,
    );
    await queryRunner.query(
      `CREATE TYPE "public"."condicion_equipo" AS ENUM('PENDIENTE', 'FUNCIONAL', 'REPARABLE', 'REUTILIZABLE', 'GESTION_ESPECIALIZADA')`,
    );
    await queryRunner.query(
      `CREATE TYPE "public"."estado_destino" AS ENUM('EN_INVENTARIO', 'EN_REPARACION', 'REASIGNADO', 'CONSERVADO', 'ENTREGADO_GESTOR')`,
    );
    await queryRunner.query(
      `CREATE TABLE "equipos" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "folio" character varying(20) NOT NULL, "marca" character varying(80) NOT NULL, "modelo" character varying(120) NOT NULL, "numero_serie" character varying(120), "fecha_ingreso" date NOT NULL, "condicion" "public"."condicion_equipo" NOT NULL DEFAULT 'PENDIENTE', "estado_destino" "public"."estado_destino" NOT NULL DEFAULT 'EN_INVENTARIO', "ubicacion" character varying(160), "destino_actual" character varying(200), "observaciones" text, "requiere_borrado_datos" boolean NOT NULL DEFAULT false, "datos_borrados" boolean NOT NULL DEFAULT false, "categoria_id" integer NOT NULL, "campana_id" integer, "registrado_por_id" integer, CONSTRAINT "PK_451fffd8d175b5b7aadbf5ba760" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE UNIQUE INDEX "IDX_b341f6fa0e08dfe9444bc79fc2" ON "equipos" ("folio") `,
    );
    await queryRunner.query(
      `CREATE INDEX "IDX_7c6f0813a8c232cee5b8a92f0c" ON "equipos" ("condicion") `,
    );
    await queryRunner.query(
      `CREATE INDEX "IDX_62d8b513756c8b610ca1f17963" ON "equipos" ("estado_destino") `,
    );
    await queryRunner.query(
      `CREATE TYPE "public"."tipo_movimiento" AS ENUM('INGRESO', 'DIAGNOSTICO', 'ENVIO_REPARACION', 'REPARACION', 'REASIGNACION', 'CONSERVACION', 'ENTREGA_GESTOR')`,
    );
    await queryRunner.query(
      `CREATE TABLE "movimientos" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "tipo" "public"."tipo_movimiento" NOT NULL, "descripcion" text NOT NULL, "estado_anterior" "public"."estado_destino", "estado_nuevo" "public"."estado_destino" NOT NULL, "destino" character varying(200), "evidencia" character varying(200), "equipo_id" integer NOT NULL, "responsable_id" integer, "gestor_id" integer, CONSTRAINT "PK_519702aa97def3e7c1b6cc5e2f9" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE INDEX "IDX_ebd21b2c277dfcb5dcf40df7a8" ON "movimientos" ("equipo_id") `,
    );
    await queryRunner.query(
      `CREATE INDEX "IDX_5d0f805d3a33f9d4c0c9c23874" ON "movimientos" ("tipo") `,
    );
    await queryRunner.query(
      `CREATE TABLE "diagnosticos" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "descripcion" text NOT NULL, "enciende" boolean NOT NULL, "dano_estructural" boolean NOT NULL DEFAULT false, "componentes_peligrosos" boolean NOT NULL DEFAULT false, "costo_reparacion_estimado" numeric(12,2) NOT NULL DEFAULT '0', "recomendacion" "public"."condicion_equipo" NOT NULL, "estrategia_aplicada" character varying(60) NOT NULL, "justificacion" text NOT NULL, "equipo_id" integer NOT NULL, "responsable_id" integer, CONSTRAINT "CHK_224c873fe2646da594e00d981c" CHECK ("costo_reparacion_estimado" >= 0), CONSTRAINT "PK_d8400650a85355bdd4ada0f0c5a" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE TYPE "public"."resultado_reparacion" AS ENUM('EXITOSA', 'FALLIDA')`,
    );
    await queryRunner.query(
      `CREATE TABLE "reparaciones" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "descripcion" text NOT NULL, "costo" numeric(12,2) NOT NULL, "resultado" "public"."resultado_reparacion" NOT NULL, "ahorro_estimado" numeric(12,2) NOT NULL DEFAULT '0', "fecha" date NOT NULL, "equipo_id" integer NOT NULL, "diagnostico_id" integer, "responsable_id" integer, CONSTRAINT "CHK_8e08ecaf8dbbf0bd019e4f0030" CHECK ("costo" >= 0), CONSTRAINT "PK_f140249d410849757959b39b185" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE TABLE "participaciones" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "nombre_participante" character varying(120) NOT NULL, "contacto" character varying(160), "equipos_declarados" integer NOT NULL DEFAULT '1', "comentario" text, "campana_id" integer NOT NULL, "usuario_id" integer, CONSTRAINT "CHK_329be3be8e3cc575585138768f" CHECK ("equipos_declarados" >= 0), CONSTRAINT "PK_ca27c8f51edaec5a8a341698e93" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE TYPE "public"."estado_campana" AS ENUM('PROGRAMADA', 'ACTIVA', 'CERRADA')`,
    );
    await queryRunner.query(
      `CREATE TABLE "campanas" ("id" SERIAL NOT NULL, "creado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "actualizado_en" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(), "nombre" character varying(160) NOT NULL, "descripcion" text NOT NULL, "fecha_inicio" date NOT NULL, "fecha_fin" date NOT NULL, "ubicacion" character varying(200) NOT NULL, "horario" character varying(120), "estado" "public"."estado_campana" NOT NULL DEFAULT 'PROGRAMADA', "meta_equipos" integer, "creada_por_id" integer, CONSTRAINT "CHK_47d48e41bdf032639f26452842" CHECK ("fecha_fin" >= "fecha_inicio"), CONSTRAINT "PK_3ea606bfebfb23646dbc654c57d" PRIMARY KEY ("id"))`,
    );
    await queryRunner.query(
      `CREATE TABLE "campana_categorias" ("campanas_id" integer NOT NULL, "categorias_id" integer NOT NULL, CONSTRAINT "PK_4daa87fc77e7774c41b7aa90003" PRIMARY KEY ("campanas_id", "categorias_id"))`,
    );
    await queryRunner.query(
      `CREATE INDEX "IDX_85ff17564ed4aabb664e219d07" ON "campana_categorias" ("campanas_id") `,
    );
    await queryRunner.query(
      `CREATE INDEX "IDX_1a432d0edcdd5ec081953e9905" ON "campana_categorias" ("categorias_id") `,
    );
    await queryRunner.query(
      `ALTER TABLE "gestores" ADD CONSTRAINT "FK_4f8f8039f1718462c53b8a9cc4f" FOREIGN KEY ("verificado_por_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "equipos" ADD CONSTRAINT "FK_4759e3fb958b8d736d61f2ceee4" FOREIGN KEY ("categoria_id") REFERENCES "categorias"("id") ON DELETE RESTRICT ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "equipos" ADD CONSTRAINT "FK_ec3ef046f5eec349aef35c5f1fd" FOREIGN KEY ("campana_id") REFERENCES "campanas"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "equipos" ADD CONSTRAINT "FK_4920d05a629796f0a2b20f2effc" FOREIGN KEY ("registrado_por_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "movimientos" ADD CONSTRAINT "FK_ebd21b2c277dfcb5dcf40df7a88" FOREIGN KEY ("equipo_id") REFERENCES "equipos"("id") ON DELETE CASCADE ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "movimientos" ADD CONSTRAINT "FK_db46d02db136fb436eaa24fcdc0" FOREIGN KEY ("responsable_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "movimientos" ADD CONSTRAINT "FK_170ebadfc6b7c37c8aeb2ef2a05" FOREIGN KEY ("gestor_id") REFERENCES "gestores"("id") ON DELETE RESTRICT ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "diagnosticos" ADD CONSTRAINT "FK_c1d686bd814c45f16ab00d6b45b" FOREIGN KEY ("equipo_id") REFERENCES "equipos"("id") ON DELETE CASCADE ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "diagnosticos" ADD CONSTRAINT "FK_a4dadc5cb4c66ec30ffafc3950e" FOREIGN KEY ("responsable_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "reparaciones" ADD CONSTRAINT "FK_de1d395421f6c900ac8d7cb65dc" FOREIGN KEY ("equipo_id") REFERENCES "equipos"("id") ON DELETE CASCADE ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "reparaciones" ADD CONSTRAINT "FK_432aa3dad3cfbb029aabe0add4c" FOREIGN KEY ("diagnostico_id") REFERENCES "diagnosticos"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "reparaciones" ADD CONSTRAINT "FK_c4bbae28f7e10e5fc63a739974a" FOREIGN KEY ("responsable_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "participaciones" ADD CONSTRAINT "FK_93d7179f1fc460364db3f28c73c" FOREIGN KEY ("campana_id") REFERENCES "campanas"("id") ON DELETE CASCADE ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "participaciones" ADD CONSTRAINT "FK_fbb5a860c4286de553b4eddfd47" FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "campanas" ADD CONSTRAINT "FK_b99c4adde515331a7778a2b3c90" FOREIGN KEY ("creada_por_id") REFERENCES "usuarios"("id") ON DELETE SET NULL ON UPDATE NO ACTION`,
    );
    await queryRunner.query(
      `ALTER TABLE "campana_categorias" ADD CONSTRAINT "FK_85ff17564ed4aabb664e219d075" FOREIGN KEY ("campanas_id") REFERENCES "campanas"("id") ON DELETE CASCADE ON UPDATE CASCADE`,
    );
    await queryRunner.query(
      `ALTER TABLE "campana_categorias" ADD CONSTRAINT "FK_1a432d0edcdd5ec081953e9905b" FOREIGN KEY ("categorias_id") REFERENCES "categorias"("id") ON DELETE CASCADE ON UPDATE CASCADE`,
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(
      `ALTER TABLE "campana_categorias" DROP CONSTRAINT "FK_1a432d0edcdd5ec081953e9905b"`,
    );
    await queryRunner.query(
      `ALTER TABLE "campana_categorias" DROP CONSTRAINT "FK_85ff17564ed4aabb664e219d075"`,
    );
    await queryRunner.query(
      `ALTER TABLE "campanas" DROP CONSTRAINT "FK_b99c4adde515331a7778a2b3c90"`,
    );
    await queryRunner.query(
      `ALTER TABLE "participaciones" DROP CONSTRAINT "FK_fbb5a860c4286de553b4eddfd47"`,
    );
    await queryRunner.query(
      `ALTER TABLE "participaciones" DROP CONSTRAINT "FK_93d7179f1fc460364db3f28c73c"`,
    );
    await queryRunner.query(
      `ALTER TABLE "reparaciones" DROP CONSTRAINT "FK_c4bbae28f7e10e5fc63a739974a"`,
    );
    await queryRunner.query(
      `ALTER TABLE "reparaciones" DROP CONSTRAINT "FK_432aa3dad3cfbb029aabe0add4c"`,
    );
    await queryRunner.query(
      `ALTER TABLE "reparaciones" DROP CONSTRAINT "FK_de1d395421f6c900ac8d7cb65dc"`,
    );
    await queryRunner.query(
      `ALTER TABLE "diagnosticos" DROP CONSTRAINT "FK_a4dadc5cb4c66ec30ffafc3950e"`,
    );
    await queryRunner.query(
      `ALTER TABLE "diagnosticos" DROP CONSTRAINT "FK_c1d686bd814c45f16ab00d6b45b"`,
    );
    await queryRunner.query(
      `ALTER TABLE "movimientos" DROP CONSTRAINT "FK_170ebadfc6b7c37c8aeb2ef2a05"`,
    );
    await queryRunner.query(
      `ALTER TABLE "movimientos" DROP CONSTRAINT "FK_db46d02db136fb436eaa24fcdc0"`,
    );
    await queryRunner.query(
      `ALTER TABLE "movimientos" DROP CONSTRAINT "FK_ebd21b2c277dfcb5dcf40df7a88"`,
    );
    await queryRunner.query(
      `ALTER TABLE "equipos" DROP CONSTRAINT "FK_4920d05a629796f0a2b20f2effc"`,
    );
    await queryRunner.query(
      `ALTER TABLE "equipos" DROP CONSTRAINT "FK_ec3ef046f5eec349aef35c5f1fd"`,
    );
    await queryRunner.query(
      `ALTER TABLE "equipos" DROP CONSTRAINT "FK_4759e3fb958b8d736d61f2ceee4"`,
    );
    await queryRunner.query(
      `ALTER TABLE "gestores" DROP CONSTRAINT "FK_4f8f8039f1718462c53b8a9cc4f"`,
    );
    await queryRunner.query(
      `DROP INDEX "public"."IDX_1a432d0edcdd5ec081953e9905"`,
    );
    await queryRunner.query(
      `DROP INDEX "public"."IDX_85ff17564ed4aabb664e219d07"`,
    );
    await queryRunner.query(`DROP TABLE "campana_categorias"`);
    await queryRunner.query(`DROP TABLE "campanas"`);
    await queryRunner.query(`DROP TABLE "participaciones"`);
    await queryRunner.query(`DROP TABLE "reparaciones"`);
    await queryRunner.query(`DROP TABLE "diagnosticos"`);
    await queryRunner.query(
      `DROP INDEX "public"."IDX_5d0f805d3a33f9d4c0c9c23874"`,
    );
    await queryRunner.query(
      `DROP INDEX "public"."IDX_ebd21b2c277dfcb5dcf40df7a8"`,
    );
    await queryRunner.query(`DROP TABLE "movimientos"`);
    await queryRunner.query(
      `DROP INDEX "public"."IDX_62d8b513756c8b610ca1f17963"`,
    );
    await queryRunner.query(
      `DROP INDEX "public"."IDX_7c6f0813a8c232cee5b8a92f0c"`,
    );
    await queryRunner.query(
      `DROP INDEX "public"."IDX_b341f6fa0e08dfe9444bc79fc2"`,
    );
    await queryRunner.query(`DROP TABLE "equipos"`);
    await queryRunner.query(
      `DROP INDEX "public"."IDX_ccdf6cd1a34ea90a7233325063"`,
    );
    await queryRunner.query(`DROP TABLE "categorias"`);
    await queryRunner.query(`DROP TABLE "gestores"`);
    await queryRunner.query(
      `DROP INDEX "public"."IDX_446adfc18b35418aac32ae0b7b"`,
    );
    await queryRunner.query(`DROP TABLE "usuarios"`);
    await queryRunner.query(`DROP TYPE "public"."estado_campana"`);
    await queryRunner.query(`DROP TYPE "public"."resultado_reparacion"`);
    await queryRunner.query(`DROP TYPE "public"."condicion_equipo"`);
    await queryRunner.query(`DROP TYPE "public"."estado_destino"`);
    await queryRunner.query(`DROP TYPE "public"."tipo_movimiento"`);
    await queryRunner.query(`DROP TYPE "public"."tipo_categoria"`);
    await queryRunner.query(`DROP TYPE "public"."rol_usuario"`);
  }
}
