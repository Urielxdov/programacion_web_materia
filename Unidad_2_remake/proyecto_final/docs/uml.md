# Diagramas UML — E-Waste 360°

Los diagramas están escritos en [Mermaid](https://mermaid.js.org/): GitHub los dibuja automáticamente y
`docs/diagramas.html` los muestra todos en el navegador, listos para copiarlos o exportarlos como imagen al reporte.
Si necesitas PNG/SVG, pega cada bloque en <https://mermaid.live> y usa *Actions → PNG/SVG*.

1. [Casos de uso](#1-casos-de-uso)
2. [Clases del dominio (Factory y Strategy)](#2-clases-del-dominio)
3. [Clases por capas (MVC + Repository)](#3-clases-por-capas)
4. [Secuencia: iniciar sesión](#4-secuencia-iniciar-sesión)
5. [Secuencia: registrar un equipo](#5-secuencia-registrar-un-equipo)
6. [Secuencia: diagnóstico con recomendación](#6-secuencia-diagnóstico-con-recomendación)
7. [Secuencia: validar un movimiento](#7-secuencia-validar-un-movimiento)
8. [Estados del equipo](#8-estados-del-equipo)
9. [Actividad: decisión de destino](#9-actividad-decisión-de-destino)
10. [Componentes y despliegue](#10-componentes-y-despliegue)

---

## 1. Casos de uso

Mermaid no tiene un tipo "casos de uso", así que se representa con un diagrama de flujo: los actores a la izquierda,
los casos de uso como óvalos y la herencia de roles con flechas punteadas (un rol superior puede hacer todo lo del inferior).

```mermaid
flowchart LR
    V([👤 Visitante])
    C([👤 Capturista])
    R([👤 Responsable ambiental])
    A([👤 Administrador])

    R -. hereda .-> C
    A -. hereda .-> R

    subgraph Sitio publico
        UC1([Consultar información ambiental])
        UC2([Consultar campañas])
        UC3([Registrar participación en campaña])
    end

    subgraph Panel privado
        UC4([Iniciar / cerrar sesión])
        UC5([Registrar equipo])
        UC6([Buscar y consultar equipos])
        UC7([Actualizar datos del equipo])
        UC8([Registrar movimiento])
        UC9([Diagnosticar equipo])
        UC10([Validar o rechazar movimiento])
        UC11([Administrar campañas])
        UC12([Consultar indicadores y reportes])
        UC13([Gestionar usuarios y roles])
        UC14([Gestionar categorías y gestores])
        UC15([Configurar política de clasificación])
    end

    V --- UC1 & UC2 & UC3
    C --- UC4 & UC5 & UC6 & UC7 & UC8
    R --- UC9 & UC10 & UC11 & UC12
    A --- UC13 & UC14 & UC15
    UC9 -. include .-> UC16([Obtener recomendación del sistema])
    UC10 -. include .-> UC17([Calcular ahorro estimado])
```

| Caso de uso | Actor principal | Descripción breve |
|---|---|---|
| Registrar equipo | Capturista | Da de alta un equipo; el sistema genera el código `EW-AAAA-NNNN` y un movimiento de ingreso. |
| Registrar movimiento | Capturista | Reparación, reasignación, conservación o entrega a gestor. Queda pendiente si no tiene permiso de validar. |
| Diagnosticar equipo | Responsable | Registra fallas y costos; el sistema recomienda un destino (Strategy) y el responsable decide. |
| Validar movimiento | Responsable | Aplica el movimiento al equipo, cambia su estado y calcula el ahorro estimado. |
| Administrar campañas | Responsable | Crea campañas, controla participaciones, asistencia y equipos recibidos. |
| Consultar indicadores | Responsable | Tablero con gráficas, tablas y exportación CSV, alimentado por la API de indicadores. |
| Gestionar usuarios | Administrador | Alta de cuentas, roles, activación y cambio de contraseña. |
| Configurar política | Administrador | Umbral de reparación y tolerancia de vida útil que usan las estrategias. |

---

## 2. Clases del dominio

**Factory:** `EquipoFactory` crea la subclase de `Equipo` según el tipo de la categoría.
**Strategy:** `ClasificadorDestino` delega en las estrategias que implementan `EstrategiaDestino`.

```mermaid
classDiagram
    direction TB

    class Equipo {
        <<abstract>>
        -int id
        -string codigo
        -string marca
        -string modelo
        -int anioFabricacion
        -string condicion
        -string clasificacion
        -string estado
        -float pesoKg
        +tipo()* string
        +icono()* string
        +requiereBorradoDatos() bool
        +contieneMaterialesPeligrosos() bool
        +recomendacionesEntrega() array
        +puedeRecibir(tipoMovimiento) bool
        +aplicarMovimiento(tipoMovimiento) void
        +asignarClasificacion(clasificacion) void
        +movimientosPermitidos() array
        +antiguedadAnios(anioActual) int
        +superaVidaUtil(factor, anioActual) bool
        +generarCodigo(id, fechaIngreso)$ string
        +toArray() array
    }
    class Categoria {
        +int id
        +string nombre
        +string tipo
        +int vidaUtilAnios
        +nombreTipo() string
    }
    class Computadora {
        +requiereBorradoDatos() bool
    }
    class Laptop {
        +requiereBorradoDatos() bool
        +contieneMaterialesPeligrosos() bool
    }
    class Celular {
        +requiereBorradoDatos() bool
        +contieneMaterialesPeligrosos() bool
    }
    class Monitor {
        +contieneMaterialesPeligrosos() bool
    }
    class Impresora {
        +contieneMaterialesPeligrosos() bool
    }
    class Bateria {
        +contieneMaterialesPeligrosos() bool
    }
    class Periferico
    class Accesorio
    class EquipoGenerico

    Equipo <|-- Computadora
    Equipo <|-- Laptop
    Equipo <|-- Celular
    Equipo <|-- Monitor
    Equipo <|-- Impresora
    Equipo <|-- Bateria
    Equipo <|-- Periferico
    Equipo <|-- Accesorio
    Equipo <|-- EquipoGenerico
    Equipo "*" --> "1" Categoria : pertenece a

    class EquipoFactory {
        <<factory>>
        +crear(Categoria, datos)$ Equipo
        +desdeFila(fila)$ Equipo
        +tiposSoportados()$ array
    }
    EquipoFactory ..> Equipo : crea

    class EstrategiaDestino {
        <<interface>>
        +decision() string
        +aplica(Equipo, EvaluacionTecnica) bool
        +justificacion(Equipo, EvaluacionTecnica) string
    }
    class EstrategiaReutilizacion {
        -float factorVidaUtil
    }
    class EstrategiaConservacion
    class EstrategiaReparacion {
        -float umbralPorcentaje
    }
    class EstrategiaGestionEspecializada
    EstrategiaReutilizacion ..|> EstrategiaDestino
    EstrategiaConservacion ..|> EstrategiaDestino
    EstrategiaReparacion ..|> EstrategiaDestino
    EstrategiaGestionEspecializada ..|> EstrategiaDestino

    class ClasificadorDestino {
        -array estrategias
        +politicaInstitucional(umbral, factor)$ ClasificadorDestino
        +recomendar(Equipo, EvaluacionTecnica) Recomendacion
    }
    class EvaluacionTecnica {
        +string estadoFuncional
        +float costoReparacion
        +float valorReemplazo
        +bool riesgoSeguridad
        +porcentajeCosto() float
    }
    class Recomendacion {
        +string decision
        +string justificacion
        +string estrategia
    }
    ClasificadorDestino o-- "1..*" EstrategiaDestino : usa en orden
    ClasificadorDestino ..> EvaluacionTecnica
    ClasificadorDestino ..> Recomendacion : devuelve

    class Diagnostico {
        +int equipoId
        +string estadoFuncional
        +float costoReparacion
        +float valorReemplazo
        +string recomendacionSistema
        +string decision
        +clasificacionResultante() string
        +siguioRecomendacion() bool
        +ahorroPotencial() float
    }
    class Movimiento {
        +int equipoId
        +string tipo
        +string estadoValidacion
        +bool destinoVerificado
        +float ahorroEstimado
        +estaPendiente() bool
    }
    class Campana {
        +string nombre
        +string fechaInicio
        +string fechaFin
        +string estado
        +aceptaParticipaciones() bool
        +vigencia() string
    }
    class Participacion {
        +string nombre
        +int equiposComprometidos
        +bool asistio
        +correoOculto() string
    }
    class Gestor {
        +string nombre
        +bool verificado
    }
    class Usuario {
        -string passwordHash
        +string rol
        +bool activo
        +verificarPassword(password) bool
        +estaBloqueado() bool
        +hashear(password)$ string
    }

    Equipo "1" --> "*" Diagnostico
    Equipo "1" --> "*" Movimiento
    Movimiento "*" --> "0..1" Gestor
    Campana "1" --> "*" Participacion
    Equipo "*" --> "0..1" Campana : recibido en
```

---

## 3. Clases por capas

Las flechas muestran que cada capa solo conoce a la de abajo: las vistas reciben datos del controlador y nunca consultan la base.

```mermaid
classDiagram
    direction TB

    class Aplicacion {
        <<front controller>>
        +ejecutar() void
        +manejar(Request) Response
    }
    class Router {
        +get(patron, accion, middlewares) void
        +post(patron, accion, middlewares) void
        +patch(patron, accion, middlewares) void
        +despachar(Request) Response
    }
    class Middleware {
        <<interface>>
        +manejar(Request) Response
    }
    class Autenticado
    class TienePermiso
    class SoloInvitados
    Autenticado ..|> Middleware
    TienePermiso ..|> Middleware
    SoloInvitados ..|> Middleware
    Aplicacion --> Router
    Router --> Middleware

    class Controller {
        <<abstract>>
        #vista() Response
        #redirigir() Response
        #json() Response
    }
    class EquipoController
    class DiagnosticoController
    class MovimientoController
    class CampanaController
    class EquipoApiController
    class IndicadorApiController
    EquipoController --|> Controller
    DiagnosticoController --|> Controller
    MovimientoController --|> Controller
    CampanaController --|> Controller
    EquipoApiController --|> Controller
    IndicadorApiController --|> Controller
    Router --> Controller : invoca

    class View {
        +render(vista, datos, layout)$ string
    }
    Controller ..> View

    class EquipoService {
        +buscar() array
        +registrar() Equipo
        +actualizar() Equipo
        +historial() array
    }
    class DiagnosticoService {
        +recomendar() Recomendacion
        +registrar() Diagnostico
    }
    class MovimientoService {
        +registrar() Movimiento
        +validar() Movimiento
        +rechazar() Movimiento
    }
    class CampanaService
    class IndicadorService {
        +generales() array
        +campanas() array
        +reutilizacion() array
    }
    class ConfiguracionService {
        +clasificador() ClasificadorDestino
    }
    class Validator {
        +validar(datos, reglas)$ array
    }

    EquipoController --> EquipoService
    EquipoApiController --> EquipoService
    DiagnosticoController --> DiagnosticoService
    MovimientoController --> MovimientoService
    CampanaController --> CampanaService
    IndicadorApiController --> IndicadorService
    DiagnosticoService --> ConfiguracionService
    EquipoService ..> Validator
    MovimientoService ..> Validator

    class Repositorio {
        <<abstract>>
        #Database db
    }
    class EquipoRepository {
        +buscar() array
        +porId() Equipo
        +insertar() void
        +actualizar() void
    }
    class DiagnosticoRepository
    class MovimientoRepository
    class CampanaRepository
    class IndicadorRepository
    EquipoRepository --|> Repositorio
    DiagnosticoRepository --|> Repositorio
    MovimientoRepository --|> Repositorio
    CampanaRepository --|> Repositorio
    IndicadorRepository --|> Repositorio

    EquipoService --> EquipoRepository
    DiagnosticoService --> DiagnosticoRepository
    MovimientoService --> MovimientoRepository
    CampanaService --> CampanaRepository
    IndicadorService --> IndicadorRepository
    EquipoRepository ..> EquipoFactory

    class Database {
        <<singleton>>
        -Database instancia$
        +obtener()$ Database
        +consultar(sql, parametros) array
        +insertar(sql, parametros) int
        +transaccion(operacion) mixed
    }
    Repositorio --> Database
```

---

## 4. Secuencia: iniciar sesión

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant FC as Aplicacion (Front Controller)
    participant AC as AuthController
    participant AS as AuthService
    participant UR as UsuarioRepository
    participant DB as Base de datos

    U->>N: Escribe usuario y contraseña
    N->>FC: POST /login (_token CSRF)
    FC->>FC: Verifica token CSRF
    FC->>AC: login(request)
    AC->>AS: autenticar(usuario, password)
    AS->>UR: porUsuario(usuario)
    UR->>DB: SELECT ... WHERE LOWER(usuario) = LOWER(?)
    DB-->>UR: fila
    UR-->>AS: Usuario
    alt Cuenta bloqueada
        AS-->>AC: HttpException 429
    else Contraseña incorrecta
        AS->>UR: registrarIntentoFallido (bloqueo tras 5)
        AS-->>AC: HttpException 401 (mensaje genérico)
    else Correcta
        AS->>UR: registrarAcceso()
        AS-->>AC: Usuario
        AC->>AC: Auth::iniciar() — session_regenerate_id + nuevo token CSRF
    end
    AC-->>N: 302 a /panel (o de vuelta a /login con el error)
```

## 5. Secuencia: registrar un equipo

```mermaid
sequenceDiagram
    autonumber
    actor C as Capturista
    participant FC as Aplicacion
    participant MW as Middlewares (auth, permiso)
    participant EC as EquipoController
    participant ES as EquipoService
    participant V as Validator
    participant F as EquipoFactory
    participant ER as EquipoRepository
    participant MR as MovimientoRepository
    participant DB as Base de datos

    C->>FC: POST /panel/equipos
    FC->>MW: auth + permiso:equipos.crear
    MW-->>FC: OK
    FC->>EC: guardar(request)
    EC->>ES: registrar(datos, usuarioId, puedeClasificar=false)
    ES->>V: validar(datos, reglas)
    V-->>ES: datos limpios (o ValidacionException 422)
    ES->>F: crear(categoria, datos)
    F-->>ES: Laptop (subclase de Equipo)
    ES->>DB: BEGIN
    ES->>ER: insertar(equipo)
    ER->>DB: INSERT equipos / UPDATE codigo = EW-2026-0013
    ES->>MR: insertar(movimiento "ingreso" validado)
    ES->>DB: COMMIT
    ES-->>EC: Equipo
    EC-->>C: 302 a /panel/equipos/13 + mensaje de éxito
```

## 6. Secuencia: diagnóstico con recomendación

```mermaid
sequenceDiagram
    autonumber
    actor R as Responsable ambiental
    participant JS as panel.js (DOM)
    participant API as EquipoApiController
    participant DS as DiagnosticoService
    participant CS as ConfiguracionService
    participant CD as ClasificadorDestino
    participant E as Estrategias

    R->>JS: Cambia estado funcional y costos
    JS->>API: GET /api/equipos/5/recomendacion?estado_funcional=...
    API->>DS: recomendar(5, datos)
    DS->>CS: clasificador()
    CS-->>DS: ClasificadorDestino(umbral 60%, factor 1.5)
    DS->>CD: recomendar(equipo, evaluacion)
    loop En orden de prioridad
        CD->>E: aplica(equipo, evaluacion)?
    end
    E-->>CD: EstrategiaReparacion aplica
    CD-->>API: Recomendacion(reparar, justificación)
    API-->>JS: 200 JSON
    JS->>JS: Pinta la recomendación y preselecciona la decisión
    R->>API: POST /panel/equipos/5/diagnostico (decisión + justificación)
    Note over DS: Si la decisión difiere de la recomendación<br/>la justificación es obligatoria.<br/>Con riesgo de seguridad solo se permite<br/>gestión especializada.
```

## 7. Secuencia: validar un movimiento

```mermaid
sequenceDiagram
    autonumber
    actor R as Responsable ambiental
    participant MC as MovimientoController
    participant MS as MovimientoService
    participant MR as MovimientoRepository
    participant EQ as Equipo
    participant DR as DiagnosticoRepository
    participant DB as Base de datos

    R->>MC: POST /panel/movimientos/8/validar
    MC->>MS: validar(8, responsableId)
    MS->>DB: BEGIN
    MS->>MR: porId(8) (debe estar pendiente)
    MS->>EQ: aplicarMovimiento("reparacion")
    alt Transición no permitida
        EQ-->>MS: DomainException
        MS-->>MC: HttpException 409
    else Permitida
        EQ->>EQ: estado = en_inventario, clasificación = funcional
        MS->>DR: ultimoDeEquipo() → valor de reemplazo
        MS->>MS: ahorro = valor de reemplazo − costo real
        MS->>DB: UPDATE equipos / UPDATE movimientos (validado, ahorro)
        MS->>DB: COMMIT
    end
    MC-->>R: 302 a /panel/movimientos + mensaje
```

---

## 8. Estados del equipo

```mermaid
stateDiagram-v2
    [*] --> en_inventario : registro (movimiento de ingreso)
    en_inventario --> reasignado : reasignación validada
    en_inventario --> conservado : conservación validada
    en_inventario --> en_inventario : reparación validada
    en_inventario --> entregado_gestor : entrega validada
    conservado --> reasignado : reasignación
    conservado --> en_inventario : reparación
    conservado --> entregado_gestor : entrega
    reasignado --> reasignado : nueva reasignación
    reasignado --> conservado : conservación
    reasignado --> en_inventario : reparación
    reasignado --> entregado_gestor : entrega
    entregado_gestor --> [*]

    note right of entregado_gestor
        Estado final: el registro se cierra.
        Cuenta como destino documentado solo
        si el gestor está verificado.
    end note
```

Clasificación (independiente del estado): `pendiente → funcional | reparable | reutilizable | gestion_especializada`,
asignada por el diagnóstico. Un equipo en `gestion_especializada` ya no admite reparación ni reasignación.

## 9. Actividad: decisión de destino

```mermaid
flowchart TD
    A([Inicio: diagnóstico]) --> B{¿Riesgo de seguridad?}
    B -- Sí --> G[Gestión especializada]
    B -- No --> C{¿Funciona?}
    C -- Sí --> D{¿Antigüedad ≤ vida útil × factor?}
    D -- Sí --> R1[Reutilizar / reasignar]
    D -- No --> R2[Conservar como respaldo]
    C -- No --> E{¿Costo de reparar ≤ umbral % del valor nuevo?}
    E -- Sí --> R3[Reparar]
    E -- No --> G
    R1 & R2 & R3 & G --> F[El responsable confirma o justifica otra decisión]
    F --> H([Fin: clasificación del equipo actualizada])
```

## 10. Componentes y despliegue

```mermaid
flowchart LR
    subgraph Cliente
        B[Navegador<br/>HTML5 + Bootstrap 5 + JS]
    end
    subgraph CDN
        J[jsDelivr: Bootstrap, Bootstrap Icons, Chart.js]
        GF[Google Fonts]
    end
    subgraph Render["Render (Docker, HTTPS)"]
        AP[Apache + mod_rewrite]
        subgraph App["PHP 8.3 — E-Waste 360°"]
            FC[public/index.php<br/>Front Controller]
            CTRL[Controladores web y API]
            SRV[Servicios + Strategy + Factory]
            REPO[Repositorios]
            VIEWS[Vistas PHP]
        end
    end
    subgraph Neon["Neon (PostgreSQL administrado)"]
        PG[(ewaste360)]
    end

    B -- HTTPS --> AP --> FC --> CTRL
    CTRL --> SRV --> REPO
    CTRL --> VIEWS
    REPO -- PDO + SSL --> PG
    B -. recursos estáticos .-> J
    B -. tipografías .-> GF
```
