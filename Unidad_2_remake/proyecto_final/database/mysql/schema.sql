-- E-Waste 360° - Esquema para MySQL 8 / MariaDB 10.4+ (XAMPP)
-- Crear la base antes:  CREATE DATABASE ewaste360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
-- (o ejecuta: php database/instalar.php --crear-bd)

CREATE TABLE usuarios (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nombre            VARCHAR(120) NOT NULL,
    usuario           VARCHAR(50)  NOT NULL,
    email             VARCHAR(150) NULL,
    password_hash     VARCHAR(255) NOT NULL,
    rol               VARCHAR(20)  NOT NULL,
    activo            SMALLINT     NOT NULL DEFAULT 1,
    intentos_fallidos INT          NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME     NULL,
    ultimo_acceso     DATETIME     NULL,
    creado_en         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_usuario UNIQUE (usuario),
    CONSTRAINT uq_usuarios_email UNIQUE (email),
    CONSTRAINT chk_usuarios_rol CHECK (rol IN ('capturista', 'responsable', 'admin')),
    CONSTRAINT chk_usuarios_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(80)  NOT NULL,
    tipo            VARCHAR(20)  NOT NULL,
    vida_util_anios SMALLINT     NOT NULL,
    descripcion     VARCHAR(255) NULL,
    activo          SMALLINT     NOT NULL DEFAULT 1,
    CONSTRAINT uq_categorias_nombre UNIQUE (nombre),
    CONSTRAINT chk_categorias_tipo CHECK (tipo IN ('computadora', 'laptop', 'monitor', 'impresora', 'celular', 'bateria', 'periferico', 'accesorio', 'otro')),
    CONSTRAINT chk_categorias_vida CHECK (vida_util_anios BETWEEN 1 AND 50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gestores (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(150) NOT NULL,
    tipo                VARCHAR(20)  NOT NULL,
    contacto            VARCHAR(150) NULL,
    direccion           VARCHAR(255) NULL,
    numero_autorizacion VARCHAR(80)  NULL,
    verificado          SMALLINT     NOT NULL DEFAULT 0,
    activo              SMALLINT     NOT NULL DEFAULT 1,
    creado_en           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_gestores_tipo CHECK (tipo IN ('reciclador', 'reacondicionador', 'fabricante', 'acopio', 'otro'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campanas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(120) NOT NULL,
    descripcion     TEXT         NOT NULL,
    fecha_inicio    DATE         NOT NULL,
    fecha_fin       DATE         NOT NULL,
    ubicacion       VARCHAR(150) NOT NULL,
    tipos_aceptados VARCHAR(255) NOT NULL,
    meta_equipos    INT          NULL,
    estado          VARCHAR(20)  NOT NULL DEFAULT 'borrador',
    creado_por      INT          NOT NULL,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_campanas_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id),
    CONSTRAINT chk_campanas_estado CHECK (estado IN ('borrador', 'activa', 'cerrada')),
    CONSTRAINT chk_campanas_fechas CHECK (fecha_fin >= fecha_inicio),
    CONSTRAINT chk_campanas_meta CHECK (meta_equipos IS NULL OR meta_equipos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(20)   NULL,
    categoria_id     INT           NOT NULL,
    marca            VARCHAR(80)   NOT NULL,
    modelo           VARCHAR(80)   NOT NULL,
    numero_serie     VARCHAR(80)   NULL,
    anio_fabricacion SMALLINT      NULL,
    fecha_ingreso    DATE          NOT NULL,
    condicion        VARCHAR(20)   NOT NULL,
    clasificacion    VARCHAR(30)   NOT NULL DEFAULT 'pendiente',
    estado           VARCHAR(20)   NOT NULL DEFAULT 'en_inventario',
    peso_kg          DECIMAL(8,2)  NULL,
    ubicacion        VARCHAR(120)  NULL,
    observaciones    TEXT          NULL,
    campana_id       INT           NULL,
    registrado_por   INT           NOT NULL,
    creado_en        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_equipos_codigo UNIQUE (codigo),
    CONSTRAINT fk_equipos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
    CONSTRAINT fk_equipos_campana FOREIGN KEY (campana_id) REFERENCES campanas (id) ON DELETE SET NULL,
    CONSTRAINT fk_equipos_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios (id),
    CONSTRAINT chk_equipos_condicion CHECK (condicion IN ('funciona', 'falla_parcial', 'no_funciona', 'desconocida')),
    CONSTRAINT chk_equipos_clasificacion CHECK (clasificacion IN ('pendiente', 'funcional', 'reparable', 'reutilizable', 'gestion_especializada')),
    CONSTRAINT chk_equipos_estado CHECK (estado IN ('en_inventario', 'reasignado', 'conservado', 'entregado_gestor')),
    CONSTRAINT chk_equipos_peso CHECK (peso_kg IS NULL OR peso_kg >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_equipos_clasificacion ON equipos (clasificacion);
CREATE INDEX idx_equipos_estado ON equipos (estado);

CREATE TABLE diagnosticos (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id             INT           NOT NULL,
    descripcion           TEXT          NOT NULL,
    estado_funcional      VARCHAR(20)   NOT NULL,
    costo_reparacion      DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_reemplazo       DECIMAL(10,2) NOT NULL DEFAULT 0,
    riesgo_seguridad      SMALLINT      NOT NULL DEFAULT 0,
    recomendacion_sistema VARCHAR(30)   NOT NULL,
    decision              VARCHAR(30)   NOT NULL,
    justificacion         TEXT          NULL,
    fecha                 DATE          NOT NULL,
    responsable_id        INT           NOT NULL,
    creado_en             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_diagnosticos_equipo FOREIGN KEY (equipo_id) REFERENCES equipos (id),
    CONSTRAINT fk_diagnosticos_usuario FOREIGN KEY (responsable_id) REFERENCES usuarios (id),
    CONSTRAINT chk_diagnosticos_estado CHECK (estado_funcional IN ('funciona', 'falla_parcial', 'no_funciona')),
    CONSTRAINT chk_diagnosticos_recomendacion CHECK (recomendacion_sistema IN ('reutilizar', 'conservar', 'reparar', 'gestion_especializada')),
    CONSTRAINT chk_diagnosticos_decision CHECK (decision IN ('reutilizar', 'conservar', 'reparar', 'gestion_especializada')),
    CONSTRAINT chk_diagnosticos_montos CHECK (costo_reparacion >= 0 AND valor_reemplazo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id          INT           NOT NULL,
    tipo               VARCHAR(20)   NOT NULL,
    fecha              DATE          NOT NULL,
    destino            VARCHAR(150)  NULL,
    gestor_id          INT           NULL,
    costo              DECIMAL(10,2) NULL,
    ahorro_estimado    DECIMAL(10,2) NOT NULL DEFAULT 0,
    evidencia          VARCHAR(255)  NULL,
    datos_borrados     SMALLINT      NOT NULL DEFAULT 0,
    destino_verificado SMALLINT      NOT NULL DEFAULT 0,
    estado_validacion  VARCHAR(20)   NOT NULL DEFAULT 'pendiente',
    observaciones      TEXT          NULL,
    registrado_por     INT           NOT NULL,
    validado_por       INT           NULL,
    validado_en        DATETIME      NULL,
    creado_en          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimientos_equipo FOREIGN KEY (equipo_id) REFERENCES equipos (id),
    CONSTRAINT fk_movimientos_gestor FOREIGN KEY (gestor_id) REFERENCES gestores (id),
    CONSTRAINT fk_movimientos_registro FOREIGN KEY (registrado_por) REFERENCES usuarios (id),
    CONSTRAINT fk_movimientos_validacion FOREIGN KEY (validado_por) REFERENCES usuarios (id),
    CONSTRAINT chk_movimientos_tipo CHECK (tipo IN ('ingreso', 'reparacion', 'reasignacion', 'conservacion', 'entrega_gestor')),
    CONSTRAINT chk_movimientos_estado CHECK (estado_validacion IN ('pendiente', 'validado', 'rechazado')),
    CONSTRAINT chk_movimientos_costo CHECK (costo IS NULL OR costo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_movimientos_validacion ON movimientos (estado_validacion);

CREATE TABLE participaciones (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    campana_id            INT          NOT NULL,
    usuario_id            INT          NULL,
    nombre                VARCHAR(120) NOT NULL,
    correo                VARCHAR(150) NULL,
    equipos_comprometidos INT          NOT NULL DEFAULT 1,
    tipos_equipo          VARCHAR(255) NULL,
    asistio               SMALLINT     NOT NULL DEFAULT 0,
    fecha_registro        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_participaciones_campana FOREIGN KEY (campana_id) REFERENCES campanas (id) ON DELETE CASCADE,
    CONSTRAINT fk_participaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT chk_participaciones_equipos CHECK (equipos_comprometidos BETWEEN 1 AND 200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion (
    clave       VARCHAR(60)  NOT NULL PRIMARY KEY,
    valor       VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
