# Arquitectura de E-Waste 360°

## 1. Arquitectura en capas y MVC

La aplicación es una **SPA (React) que consume una API REST (NestJS)**. El patrón MVC se reparte así:

| MVC | Dónde está | Responsabilidad |
|---|---|---|
| **Vista** | `frontend/src/features/**`, `components/**` | Mostrar información y capturar acciones. **Nunca accede a la base de datos**: solo llama a la API mediante `frontend/src/api/cliente.ts`. |
| **Controlador** | `backend/src/modules/*/*.controller.ts` | Recibir la petición HTTP, validar el DTO, aplicar guards (sesión, rol, CSRF) y delegar en el servicio. No contiene reglas de negocio. |
| **Modelo** | Entidades (`*.entity.ts`) + servicios (`*.service.ts`) + repositorios (`*.repository.ts`) | Reglas de negocio, estado del dominio y persistencia. |

```mermaid
flowchart TB
  subgraph P["Capa de presentación (React)"]
    V["Páginas y componentes<br/>features/*, components/*"]
    Q["TanStack Query<br/>api/hooks.ts"]
    Z["Zustand<br/>stores/*"]
    C["Cliente HTTP<br/>api/cliente.ts (CSRF, errores)"]
    V --> Q --> C
    V --> Z
  end
  subgraph A["Capa de aplicación (NestJS)"]
    G["Guards globales<br/>Throttler → Sesión → Rol → CSRF"]
    CT["Controladores + DTOs<br/>ValidationPipe"]
    F["Filtro global de errores"]
    G --> CT
  end
  subgraph N["Capa de negocio"]
    S["Servicios<br/>EquiposService, DiagnosticosService…"]
    E["Entidades con reglas<br/>Equipo, Gestor, Campana…"]
    PT["Patrones: Factory, Strategy, Observer"]
    S --> E
    S --> PT
  end
  subgraph D["Capa de persistencia"]
    R["Repositorios<br/>RepositorioBase&lt;T&gt;"]
    ORM["TypeORM (consultas parametrizadas)"]
    DB[("PostgreSQL")]
    R --> ORM --> DB
  end
  C -- "HTTPS + JSON<br/>cookie de sesión" --> G
  CT --> S
  S --> R
```

**Regla de dependencias:** cada capa solo conoce a la siguiente. Los controladores no usan repositorios
directamente y los servicios no construyen SQL: lo hacen los repositorios.

Esta separación facilita:
- **Mantenimiento:** cambiar la regla para decidir si un equipo se repara solo toca una clase Strategy.
- **Pruebas:** las reglas del dominio se prueban sin base de datos (`equipo.spec.ts`, `clasificador-destino.spec.ts`).
- **Ampliación:** un nuevo tipo de dispositivo es un nuevo `PerfilEquipo` en la Factory.

### Módulos del backend

| Módulo | Contenido |
|---|---|
| `auth` | Inicio y cierre de sesión, `GET /auth/me`, token CSRF |
| `usuarios` | Cuentas del personal y `PasswordService` (argon2id) |
| `catalogos` | Categorías de equipo y gestores de residuos (con verificación) |
| `equipos` | Inventario, `EquipoFactory` y los perfiles por tipo de dispositivo |
| `diagnosticos` | Diagnóstico y estrategias de clasificación de destino |
| `reparaciones` | Resultado de reparaciones y cálculo de ahorro |
| `movimientos` | Trazabilidad: decisiones de destino y `MovimientosListener` (Observer) |
| `campanas` | Campañas de recolección y participaciones |
| `indicadores` | Consultas agregadas para reportes |

## 2. Patrones de diseño

### 2.1 Repository (`common/repositories/repositorio-base.ts`)

**Problema:** si cada servicio arma sus consultas, el acceso a datos queda disperso y repetido, y cambiar el ORM o
una consulta obliga a tocar la lógica de negocio.

**Solución:** `RepositorioBase<T>` es una clase abstracta genérica con las operaciones comunes (`obtenerPorId` con
404 automático, `guardar`, `transaccion`). Cada repositorio concreto hereda de ella y agrega sus consultas, por ejemplo
`EquiposRepository.buscar()` (búsqueda paginada con filtros), `siguienteFolio()` y `obtenerParaActualizar()`
(bloqueo de fila). `IndicadoresRepository` concentra todas las consultas agregadas.

### 2.2 Strategy (`modules/diagnosticos/strategies/`)

**Problema:** decidir si un equipo se **repara**, se **reutiliza** o va a **gestión especializada** depende de varios
criterios que cambian con el tiempo (umbrales de costo, materiales peligrosos…). Si todo vive en un `if/else` dentro
del servicio, cada cambio arriesga romper los demás.

**Solución:** cada criterio es una clase que hereda de `EstrategiaClasificacion`:

| Prioridad | Estrategia | Aplica cuando | Resultado |
|---|---|---|---|
| 1 | `GestionEspecializadaStrategy` | Categoría de energía, componentes peligrosos o daño estructural | Gestión especializada |
| 2 | `ReutilizacionDirectaStrategy` | Enciende y no requiere reparación | Funcional |
| 3 | `ReparacionStrategy` | Costo de reparación ≤ 50 % del valor de reposición | Reparable |
| 99 | `ReparacionNoRentableStrategy` | Siempre (respaldo) | Gestión especializada |

`ClasificadorDestino` (el *contexto*) recibe la lista inyectada por Nest, la ordena por prioridad y aplica la primera
que corresponda. Para agregar un criterio nuevo basta con registrarlo en `diagnosticos.module.ts`. La interfaz
muestra la recomendación **en vivo** mientras se captura el diagnóstico (`POST /equipos/:id/diagnosticos/simular`).

### 2.3 Factory (`modules/equipos/factory/`)

**Problema:** cada tipo de dispositivo se registra con reglas distintas. Las computadoras exigen número de serie y
borrado de datos, los celulares también requieren borrado y las baterías entran directo a gestión especializada.

**Solución:** `EquipoFactory.crear()` elige un `PerfilEquipo` según el tipo de categoría (`PerfilComputo`,
`PerfilMovil`, `PerfilEnergia`, `PerfilPeriferico`, `PerfilCableado`, `PerfilGenerico`) y construye el `Equipo` ya
configurado. Cada perfil redefine por **polimorfismo** `validar()`, `condicionInicial()`, `requiereBorradoDatos()` y
`recomendacionesEntrega()`.

### 2.4 Observer (`common/events/equipo.events.ts`, `movimientos/movimientos.listener.ts`)

**Problema:** registrar, diagnosticar y reparar equipos debe quedar en el historial, pero esos módulos no deberían
depender del módulo de movimientos.

**Solución:** los servicios emiten eventos de dominio (`EquipoRegistradoEvent`, `EquipoDiagnosticadoEvent`,
`EquipoReparadoEvent`, subclases de `EventoEquipo`) y `MovimientosListener` los observa con `@OnEvent('equipo.*')`
y crea el movimiento. Cada evento sabe describirse a sí mismo (`describir()`).

### 2.5 Otros patrones presentes

- **Inyección de dependencias / Singleton:** los servicios y repositorios son instancias únicas que administra Nest.
- **Decorator:** `@Public()`, `@Roles()`, `@UsuarioActual()`.
- **Chain of Responsibility:** los guards globales se ejecutan en cadena (Throttler → Sesión → Rol → CSRF).

## 3. Programación orientada a objetos

- **Encapsulamiento:** el estado de un `Equipo` solo cambia mediante sus métodos (`aplicarDiagnostico`,
  `enviarAReparacion`, `registrarResultadoReparacion`, `reasignar`, `conservar`, `entregarAGestor`), que validan las
  transiciones y lanzan `ReglaNegocioError` si se viola una regla. Los métodos auxiliares (`exigirDisponible`,
  `exigirBorradoDatos`) son privados. `Usuario.passwordHash` nunca se selecciona por defecto.
- **Herencia:** todas las entidades heredan de `EntidadBase` (id y marcas de tiempo); los repositorios de
  `RepositorioBase<T>`; las estrategias de `EstrategiaClasificacion`; los perfiles de `PerfilEquipo`; los eventos de
  `EventoEquipo`.
- **Polimorfismo:** `ClasificadorDestino` y `EquipoFactory` trabajan con la clase base sin conocer la subclase concreta.
- **Abstracción:** clases abstractas con métodos abstractos (`aplica`, `clasificar`, `describir`).
- **Responsabilidades claras:** `PasswordService` solo cifra; `CsrfService` solo maneja tokens; `Reparacion.calcularAhorro`
  concentra el cálculo de ahorro.
- **Relaciones:** asociaciones 1:N (Categoría–Equipo, Equipo–Movimiento), N:M (Campaña–Categoría), composición
  del historial (los movimientos se borran en cascada con su equipo).

## 4. Diagramas UML

### 4.1 Casos de uso

```mermaid
flowchart LR
  V(("Visitante"))
  C(("Capturista"))
  R(("Responsable<br/>ambiental"))
  A(("Administrador"))

  subgraph Sistema["E-Waste 360°"]
    UC1["Consultar información ambiental"]
    UC2["Ver campañas y participar"]
    UC3["Iniciar / cerrar sesión"]
    UC4["Registrar equipo"]
    UC5["Buscar y actualizar equipos"]
    UC6["Registrar diagnóstico"]
    UC7["Enviar a reparación / registrar resultado"]
    UC8["Reasignar, conservar o entregar a gestor"]
    UC9["Administrar campañas"]
    UC10["Consultar reportes"]
    UC11["Administrar gestores"]
    UC12["Verificar gestores"]
    UC13["Administrar usuarios y categorías"]
  end

  V --> UC1 & UC2
  C --> UC3 & UC4 & UC5
  R --> UC3 & UC4 & UC5 & UC6 & UC7 & UC8 & UC9 & UC10 & UC11
  A --> UC3 & UC4 & UC5 & UC6 & UC7 & UC8 & UC9 & UC10 & UC11 & UC12 & UC13
```

### 4.2 Diagrama de clases (dominio y patrones)

```mermaid
classDiagram
  class EntidadBase {
    <<abstract>>
    +id: number
    +creadoEn: Date
    +actualizadoEn: Date
  }
  class Usuario {
    +nombre
    +email
    -passwordHash
    +rol: Rol
    +activo
    +aSesion() UsuarioSesion
    +tieneRol(roles) bool
  }
  class Categoria {
    +nombre
    +tipo: TipoCategoria
    +pesoPromedioKg
    +valorReposicion
    +esPeligrosa() bool
  }
  class Equipo {
    +folio
    +marca
    +modelo
    +condicion: CondicionEquipo
    +estadoDestino: EstadoDestino
    +requiereBorradoDatos
    +accionesDisponibles() AccionEquipo[]
    +aplicarDiagnostico(c)
    +enviarAReparacion()
    +registrarResultadoReparacion(ok)
    +reasignar(destino, borrado)
    +conservar(ubicacion)
    +entregarAGestor(g, borrado)
    -exigirDisponible()
    -exigirBorradoDatos(ok)
  }
  class Gestor {
    +nombre
    +verificado
    +documentoVerificacion
    +verificar(doc, usuario)
    +revocarVerificacion()
    +puedeRecibirEquipos() bool
  }
  class Diagnostico {
    +enciende
    +costoReparacionEstimado
    +recomendacion
    +estrategiaAplicada
  }
  class Reparacion {
    +costo
    +resultado
    +ahorroEstimado
    +calcularAhorro(valor, costo, ok)$
  }
  class Movimiento {
    +tipo: TipoMovimiento
    +descripcion
    +estadoAnterior
    +estadoNuevo
    +evidencia
  }
  class Campana {
    +nombre
    +fechaInicio
    +fechaFin
    +estado
    +acepta(categoriaId) bool
    +exigirAbierta()
  }
  class Participacion {
    +nombreParticipante
    +equiposDeclarados
  }

  EntidadBase <|-- Usuario
  EntidadBase <|-- Categoria
  EntidadBase <|-- Equipo
  EntidadBase <|-- Gestor
  EntidadBase <|-- Diagnostico
  EntidadBase <|-- Reparacion
  EntidadBase <|-- Movimiento
  EntidadBase <|-- Campana
  EntidadBase <|-- Participacion

  Categoria "1" <-- "*" Equipo
  Campana "0..1" <-- "*" Equipo : recibido en
  Equipo "1" *-- "*" Movimiento
  Equipo "1" *-- "*" Diagnostico
  Equipo "1" *-- "*" Reparacion
  Movimiento "*" --> "0..1" Gestor
  Campana "*" --> "*" Categoria : acepta
  Campana "1" *-- "*" Participacion
  Usuario "1" <-- "*" Movimiento : responsable

  class EstrategiaClasificacion {
    <<abstract>>
    +prioridad
    +nombre
    +aplica(ctx)* bool
    +clasificar(ctx) Resultado
  }
  class ClasificadorDestino {
    -estrategias
    +clasificar(ctx) Resultado
  }
  EstrategiaClasificacion <|-- GestionEspecializadaStrategy
  EstrategiaClasificacion <|-- ReutilizacionDirectaStrategy
  EstrategiaClasificacion <|-- ReparacionStrategy
  EstrategiaClasificacion <|-- ReparacionNoRentableStrategy
  ClasificadorDestino o-- EstrategiaClasificacion

  class PerfilEquipo {
    <<abstract>>
    +requiereBorradoDatos() bool
    +condicionInicial()
    +validar(datos)
    +recomendacionesEntrega()
  }
  class EquipoFactory {
    +perfilPara(tipo) PerfilEquipo
    +crear(params) Equipo
  }
  PerfilEquipo <|-- PerfilComputo
  PerfilEquipo <|-- PerfilMovil
  PerfilEquipo <|-- PerfilEnergia
  PerfilEquipo <|-- PerfilPeriferico
  EquipoFactory o-- PerfilEquipo
  EquipoFactory ..> Equipo : crea

  class RepositorioBase~T~ {
    <<abstract>>
    #repo
    +obtenerPorId(id) T
    +guardar(e) T
    +transaccion(fn)
  }
  RepositorioBase <|-- EquiposRepository
  RepositorioBase <|-- MovimientosRepository
  RepositorioBase <|-- CampanasRepository
```

### 4.3 Secuencia: registrar un diagnóstico

```mermaid
sequenceDiagram
  actor R as Responsable
  participant UI as React (ModalDiagnostico)
  participant API as DiagnosticosController
  participant G as Guards (sesión, rol, CSRF)
  participant S as DiagnosticosService
  participant RE as EquiposRepository
  participant CL as ClasificadorDestino
  participant E as Equipo
  participant EV as EventEmitter
  participant L as MovimientosListener
  participant DB as PostgreSQL

  R->>UI: Llena el formulario
  UI->>API: POST /api/equipos/7/diagnosticos (cookie + X-CSRF-Token)
  API->>G: canActivate()
  G-->>API: permitido (rol RESPONSABLE)
  API->>S: registrar(7, dto, usuarioId)
  S->>RE: transaccion(...)
  RE->>DB: SELECT ... FOR UPDATE
  S->>CL: clasificar(contexto)
  CL-->>S: REPARABLE (ReparacionStrategy)
  S->>E: aplicarDiagnostico(REPARABLE)
  S->>DB: UPDATE equipos / INSERT diagnosticos (COMMIT)
  S->>EV: emitAsync(EquipoDiagnosticadoEvent)
  EV->>L: alOcurrirEventoDeEquipo(evento)
  L->>DB: INSERT movimientos
  S-->>API: Diagnostico
  API-->>UI: 201 Created (JSON)
  UI->>UI: invalida caché (TanStack Query) y muestra aviso
```

### 4.4 Estados de un equipo

```mermaid
stateDiagram-v2
  [*] --> EN_INVENTARIO: registrar (Factory)
  EN_INVENTARIO --> EN_INVENTARIO: diagnosticar (Strategy)
  EN_INVENTARIO --> EN_REPARACION: enviar a reparación [reparable]
  EN_REPARACION --> EN_INVENTARIO: resultado (reutilizable / gestión especializada)
  EN_INVENTARIO --> REASIGNADO: reasignar [funcional o reutilizable + evidencia + borrado de datos]
  EN_INVENTARIO --> CONSERVADO: conservar [no peligroso]
  CONSERVADO --> REASIGNADO: reasignar
  EN_INVENTARIO --> ENTREGADO_GESTOR: entregar [gestión especializada + gestor verificado + evidencia]
  REASIGNADO --> [*]
  ENTREGADO_GESTOR --> [*]
```

### 4.5 Despliegue

```mermaid
flowchart LR
  U["Navegador<br/>(computadora, tableta, celular)"]
  subgraph Render["Render · Web Service (HTTPS)"]
    N["Node.js 22<br/>NestJS"]
    SPA["frontend/dist<br/>(ServeStaticModule)"]
    N --- SPA
  end
  subgraph Neon["Neon · PostgreSQL 16"]
    DB[("ewaste360<br/>10 tablas + sesiones")]
  end
  U -- "HTTPS (TLS)" --> N
  N -- "TLS · pg" --> DB
```

Front y API comparten el mismo dominio, así que la cookie de sesión puede ser `HttpOnly`, `Secure` y
`SameSite=Lax` sin configurar CORS.
