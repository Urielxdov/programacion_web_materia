# Modelo de datos — E-Waste 360°

Base de datos relacional con **9 tablas** (la rúbrica pide al menos 5). El esquema existe en dos variantes equivalentes:
`database/mysql/schema.sql` (XAMPP) y `database/pgsql/schema.sql` (Neon/PostgreSQL).

## Diagrama entidad-relación

```mermaid
erDiagram
    USUARIOS ||--o{ EQUIPOS : "registra"
    USUARIOS ||--o{ DIAGNOSTICOS : "elabora"
    USUARIOS ||--o{ MOVIMIENTOS : "registra"
    USUARIOS |o--o{ MOVIMIENTOS : "valida"
    USUARIOS ||--o{ CAMPANAS : "crea"
    USUARIOS |o--o{ PARTICIPACIONES : "puede participar"
    CATEGORIAS ||--o{ EQUIPOS : "clasifica"
    CAMPANAS |o--o{ EQUIPOS : "recibe"
    CAMPANAS ||--o{ PARTICIPACIONES : "tiene"
    EQUIPOS ||--o{ DIAGNOSTICOS : "tiene"
    EQUIPOS ||--o{ MOVIMIENTOS : "historial"
    GESTORES |o--o{ MOVIMIENTOS : "recibe entregas"

    USUARIOS {
        int id PK
        varchar nombre
        varchar usuario UK
        varchar email UK
        varchar password_hash
        varchar rol "capturista | responsable | admin"
        smallint activo
        int intentos_fallidos
        timestamp bloqueado_hasta
        timestamp ultimo_acceso
    }
    CATEGORIAS {
        int id PK
        varchar nombre UK
        varchar tipo "decide la subclase (Factory)"
        smallint vida_util_anios
        varchar descripcion
        smallint activo
    }
    EQUIPOS {
        int id PK
        varchar codigo UK "EW-AAAA-NNNN"
        int categoria_id FK
        varchar marca
        varchar modelo
        varchar numero_serie
        smallint anio_fabricacion
        date fecha_ingreso
        varchar condicion
        varchar clasificacion
        varchar estado
        decimal peso_kg
        varchar ubicacion
        int campana_id FK
        int registrado_por FK
    }
    DIAGNOSTICOS {
        int id PK
        int equipo_id FK
        text descripcion
        varchar estado_funcional
        decimal costo_reparacion
        decimal valor_reemplazo
        smallint riesgo_seguridad
        varchar recomendacion_sistema
        varchar decision
        text justificacion
        date fecha
        int responsable_id FK
    }
    MOVIMIENTOS {
        int id PK
        int equipo_id FK
        varchar tipo
        date fecha
        varchar destino
        int gestor_id FK
        decimal costo
        decimal ahorro_estimado
        varchar evidencia
        smallint datos_borrados
        smallint destino_verificado
        varchar estado_validacion
        int registrado_por FK
        int validado_por FK
        timestamp validado_en
    }
    GESTORES {
        int id PK
        varchar nombre
        varchar tipo
        varchar contacto
        varchar numero_autorizacion
        smallint verificado
        smallint activo
    }
    CAMPANAS {
        int id PK
        varchar nombre
        text descripcion
        date fecha_inicio
        date fecha_fin
        varchar ubicacion
        varchar tipos_aceptados
        int meta_equipos
        varchar estado
        int creado_por FK
    }
    PARTICIPACIONES {
        int id PK
        int campana_id FK
        int usuario_id FK
        varchar nombre
        varchar correo
        int equipos_comprometidos
        varchar tipos_equipo
        smallint asistio
        timestamp fecha_registro
    }
    CONFIGURACION {
        varchar clave PK
        varchar valor
        varchar descripcion
    }
```

## Diccionario de datos

| Tabla | Propósito | Relaciones |
|---|---|---|
| `usuarios` | Cuentas del personal. La contraseña se guarda solo como hash (`password_hash`, bcrypt). `intentos_fallidos` y `bloqueado_hasta` implementan el bloqueo por fuerza bruta. | Registra equipos, diagnósticos y movimientos; crea campañas. |
| `categorias` | Catálogo de tipos de dispositivo con su vida útil de referencia. `tipo` decide qué subclase crea la `EquipoFactory`. | 1:N con `equipos`. |
| `equipos` | Inventario. `clasificacion` la fija el diagnóstico; `estado` cambia solo con movimientos validados. | N:1 con `categorias`, `campanas` (opcional) y `usuarios`. 1:N con `diagnosticos` y `movimientos`. |
| `diagnosticos` | Evaluaciones técnicas: lo que recomendó el sistema (`recomendacion_sistema`) y lo que decidió el responsable (`decision`). | N:1 con `equipos` y `usuarios`. |
| `movimientos` | Historial verificable de cada equipo (trazabilidad): ingreso, reparación, reasignación, conservación y entrega. | N:1 con `equipos`, `gestores` (opcional) y `usuarios` (registra y valida). |
| `gestores` | Destinos para la gestión especializada. `verificado` indica que se revisó su autorización ambiental. | 1:N con `movimientos`. |
| `campanas` | Campañas de recolección con fechas, lugar, equipos aceptados y meta. | 1:N con `participaciones` y con `equipos` recibidos. |
| `participaciones` | Registro público de participantes, con datos mínimos (nombre y correo opcional). | N:1 con `campanas` (en cascada) y opcionalmente con `usuarios`. |
| `configuracion` | Parámetros de la política institucional (umbral de reparación, factor de vida útil, nombre de la institución). | — |

## Restricciones de integridad y su justificación

- **Llaves foráneas** en todas las relaciones: no puede existir un diagnóstico o movimiento de un equipo inexistente,
  ni un equipo con una categoría inexistente.
- **Sin borrado físico de equipos:** `diagnosticos` y `movimientos` referencian a `equipos` sin `ON DELETE CASCADE`.
  El historial de un equipo es evidencia ambiental y no debe desaparecer. El cierre de un registro se modela con el
  estado final `entregado_gestor`.
- **`ON DELETE SET NULL`** en `equipos.campana_id` y `participaciones.usuario_id`: si se elimina la campaña o la cuenta,
  el equipo y la participación se conservan.
- **`ON DELETE CASCADE`** en `participaciones.campana_id`: una participación no tiene sentido sin su campaña.
- **`CHECK`** sobre los valores de catálogo (rol, condición, clasificación, estado, tipo de movimiento, decisión),
  montos no negativos, `fecha_fin >= fecha_inicio`, vida útil entre 1 y 50 años y entre 1 y 200 equipos por participación.
- **`UNIQUE`** en `usuarios.usuario`, `usuarios.email`, `categorias.nombre` y `equipos.codigo`.
- **Índices** sobre `equipos.clasificacion`, `equipos.estado` y `movimientos.estado_validacion`, que son los filtros más usados.

Las reglas que dependen de varias tablas se validan en la capa de negocio dentro de una **transacción**. Por ejemplo:
"no se permite un segundo movimiento pendiente", "un equipo con riesgo solo va a gestión especializada" y
"una entrega exige un gestor y una evidencia".
