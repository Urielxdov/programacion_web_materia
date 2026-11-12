<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Equipo electronico del inventario (clase abstracta).
 *
 * Cada categoria de dispositivo es una subclase (Laptop, Bateria, Monitor...) que redefine
 * su comportamiento: si guarda datos personales que deben borrarse, si contiene materiales
 * peligrosos y que recomendaciones aplican al entregarlo. La EquipoFactory decide que
 * subclase crear. Las reglas de cambio de estado viven aqui, no en los controladores.
 */
abstract class Equipo
{
    public const CLASIFICACIONES = [
        'pendiente'             => 'Pendiente de evaluación',
        'funcional'             => 'Funcional',
        'reparable'             => 'Reparable',
        'reutilizable'          => 'Reutilizable',
        'gestion_especializada' => 'Gestión especializada',
    ];

    public const ESTADOS = [
        'en_inventario'    => 'En inventario',
        'reasignado'       => 'Reasignado',
        'conservado'       => 'Conservado',
        'entregado_gestor' => 'Entregado a gestor',
    ];

    public const CONDICIONES = [
        'funciona'      => 'Enciende y funciona',
        'falla_parcial' => 'Funciona con fallas',
        'no_funciona'   => 'No enciende / no funciona',
        'desconocida'   => 'Sin revisar',
    ];

    /** Movimientos permitidos segun el estado actual. Entregado a gestor es un estado final. */
    private const TRANSICIONES = [
        'en_inventario'    => ['reparacion', 'reasignacion', 'conservacion', 'entrega_gestor'],
        'conservado'       => ['reparacion', 'reasignacion', 'entrega_gestor'],
        'reasignado'       => ['reparacion', 'reasignacion', 'conservacion', 'entrega_gestor'],
        'entregado_gestor' => [],
    ];

    private const ESTADO_TRAS_MOVIMIENTO = [
        'reparacion'     => 'en_inventario',
        'reasignacion'   => 'reasignado',
        'conservacion'   => 'conservado',
        'entrega_gestor' => 'entregado_gestor',
    ];

    private ?int $id;
    private ?string $codigo;
    private string $marca;
    private string $modelo;
    private ?string $numeroSerie;
    private ?int $anioFabricacion;
    private string $fechaIngreso;
    private string $condicion;
    private string $clasificacion;
    private string $estado;
    private ?float $pesoKg;
    private ?string $ubicacion;
    private ?string $observaciones;
    private ?int $campanaId;
    private ?int $registradoPor;
    private ?string $creadoEn;
    private ?string $actualizadoEn;

    public function __construct(private Categoria $categoria, array $datos)
    {
        $this->id              = isset($datos['id']) ? (int) $datos['id'] : null;
        $this->codigo          = $datos['codigo'] ?? null;
        $this->marca           = trim((string) ($datos['marca'] ?? ''));
        $this->modelo          = trim((string) ($datos['modelo'] ?? ''));
        $this->numeroSerie     = self::textoONulo($datos['numero_serie'] ?? null);
        $this->anioFabricacion = isset($datos['anio_fabricacion']) ? (int) $datos['anio_fabricacion'] : null;
        $this->fechaIngreso    = substr((string) ($datos['fecha_ingreso'] ?? date('Y-m-d')), 0, 10);
        $this->pesoKg          = isset($datos['peso_kg']) ? (float) $datos['peso_kg'] : null;
        $this->ubicacion       = self::textoONulo($datos['ubicacion'] ?? null);
        $this->observaciones   = self::textoONulo($datos['observaciones'] ?? null);
        $this->campanaId       = isset($datos['campana_id']) ? (int) $datos['campana_id'] : null;
        $this->registradoPor   = isset($datos['registrado_por']) ? (int) $datos['registrado_por'] : null;
        $this->creadoEn        = $datos['creado_en'] ?? null;
        $this->actualizadoEn   = $datos['actualizado_en'] ?? null;

        if ($this->marca === '' || $this->modelo === '') {
            throw new \InvalidArgumentException('La marca y el modelo son obligatorios.');
        }
        $this->condicion = self::enLista($datos['condicion'] ?? 'desconocida', self::CONDICIONES, 'condición');
        $this->clasificacion = self::enLista($datos['clasificacion'] ?? 'pendiente', self::CLASIFICACIONES, 'clasificación');
        $this->estado = self::enLista($datos['estado'] ?? 'en_inventario', self::ESTADOS, 'estado');
    }

    // ---------------------------------------------------------------- polimorfismo

    /** Clave del tipo (coincide con Categoria::TIPOS). */
    abstract public function tipo(): string;

    /** Icono de Bootstrap Icons para la interfaz. */
    abstract public function icono(): string;

    /** Los equipos que guardan informacion exigen borrado seguro antes de salir. */
    public function requiereBorradoDatos(): bool
    {
        return false;
    }

    /** Baterias, pantallas con retroiluminacion, toner... requieren manejo especial. */
    public function contieneMaterialesPeligrosos(): bool
    {
        return false;
    }

    /** @return string[] Recomendaciones para entregar el equipo de forma segura. */
    public function recomendacionesEntrega(): array
    {
        $recomendaciones = [
            'Entrégalo completo: no lo desarmes ni retires piezas por tu cuenta.',
            'Pide un comprobante (folio o manifiesto) al gestor que lo recibe.',
        ];
        if ($this->requiereBorradoDatos()) {
            array_unshift($recomendaciones, 'Respalda tu información y realiza un borrado seguro antes de entregarlo.');
        }
        return $recomendaciones;
    }

    // ---------------------------------------------------------------- reglas de negocio

    public function puedeRecibir(string $tipoMovimiento): bool
    {
        if (!in_array($tipoMovimiento, self::TRANSICIONES[$this->estado], true)) {
            return false;
        }
        // Un equipo marcado para gestion especializada ya no se repara ni se reasigna
        if ($this->clasificacion === 'gestion_especializada' && in_array($tipoMovimiento, ['reparacion', 'reasignacion'], true)) {
            return false;
        }
        return true;
    }

    /** Aplica un movimiento ya validado. */
    public function aplicarMovimiento(string $tipoMovimiento): void
    {
        if (!$this->puedeRecibir($tipoMovimiento)) {
            throw new \DomainException(sprintf(
                'Un equipo con estado «%s» y clasificación «%s» no puede recibir el movimiento «%s».',
                $this->nombreEstado(),
                $this->nombreClasificacion(),
                Movimiento::TIPOS[$tipoMovimiento] ?? $tipoMovimiento
            ));
        }
        $this->estado = self::ESTADO_TRAS_MOVIMIENTO[$tipoMovimiento];
        if ($tipoMovimiento === 'reparacion') {
            $this->clasificacion = 'funcional';
        }
    }

    /** @return string[] */
    public function movimientosPermitidos(): array
    {
        return array_values(array_filter(Movimiento::TIPOS_REGISTRABLES, fn (string $t): bool => $this->puedeRecibir($t)));
    }

    public function asignarClasificacion(string $clasificacion): void
    {
        if ($this->estado === 'entregado_gestor') {
            throw new \DomainException('El equipo ya fue entregado a un gestor; su clasificación no puede cambiar.');
        }
        $this->clasificacion = self::enLista($clasificacion, self::CLASIFICACIONES, 'clasificación');
    }

    public function asignarIdentidad(int $id, string $codigo): void
    {
        if ($this->id !== null && $this->id !== $id) {
            throw new \LogicException('El equipo ya tiene un identificador.');
        }
        $this->id = $id;
        $this->codigo = $codigo;
    }

    /** Identificador unico legible: EW-2026-0007. */
    public static function generarCodigo(int $id, string $fechaIngreso): string
    {
        return sprintf('EW-%s-%04d', substr($fechaIngreso, 0, 4), $id);
    }

    public function antiguedadAnios(?int $anioActual = null): ?int
    {
        if ($this->anioFabricacion === null) {
            return null;
        }
        return max(0, ($anioActual ?? (int) date('Y')) - $this->anioFabricacion);
    }

    /** true si la antiguedad supera la vida util de la categoria multiplicada por $factor. */
    public function superaVidaUtil(float $factor = 1.0, ?int $anioActual = null): bool
    {
        $antiguedad = $this->antiguedadAnios($anioActual);
        return $antiguedad !== null && $antiguedad > $this->categoria->vidaUtilAnios * $factor;
    }

    public function estaEntregado(): bool
    {
        return $this->estado === 'entregado_gestor';
    }

    // ---------------------------------------------------------------- getters

    public function id(): ?int { return $this->id; }
    public function codigo(): ?string { return $this->codigo; }
    public function categoria(): Categoria { return $this->categoria; }
    public function marca(): string { return $this->marca; }
    public function modelo(): string { return $this->modelo; }
    public function numeroSerie(): ?string { return $this->numeroSerie; }
    public function anioFabricacion(): ?int { return $this->anioFabricacion; }
    public function fechaIngreso(): string { return $this->fechaIngreso; }
    public function condicion(): string { return $this->condicion; }
    public function clasificacion(): string { return $this->clasificacion; }
    public function estado(): string { return $this->estado; }
    public function pesoKg(): ?float { return $this->pesoKg; }
    public function ubicacion(): ?string { return $this->ubicacion; }
    public function observaciones(): ?string { return $this->observaciones; }
    public function campanaId(): ?int { return $this->campanaId; }
    public function registradoPor(): ?int { return $this->registradoPor; }
    public function creadoEn(): ?string { return $this->creadoEn; }
    public function actualizadoEn(): ?string { return $this->actualizadoEn; }

    public function nombreCondicion(): string { return self::CONDICIONES[$this->condicion]; }
    public function nombreClasificacion(): string { return self::CLASIFICACIONES[$this->clasificacion]; }
    public function nombreEstado(): string { return self::ESTADOS[$this->estado]; }

    public function descripcionCorta(): string
    {
        return $this->categoria->nombre . ' ' . $this->marca . ' ' . $this->modelo;
    }

    /** Columnas tal como se guardan en la tabla equipos. */
    public function toPersistencia(): array
    {
        return [
            'id'               => $this->id,
            'codigo'           => $this->codigo,
            'categoria_id'     => $this->categoria->id,
            'marca'            => $this->marca,
            'modelo'           => $this->modelo,
            'numero_serie'     => $this->numeroSerie,
            'anio_fabricacion' => $this->anioFabricacion,
            'fecha_ingreso'    => $this->fechaIngreso,
            'condicion'        => $this->condicion,
            'clasificacion'    => $this->clasificacion,
            'estado'           => $this->estado,
            'peso_kg'          => $this->pesoKg,
            'ubicacion'        => $this->ubicacion,
            'observaciones'    => $this->observaciones,
            'campana_id'       => $this->campanaId,
            'registrado_por'   => $this->registradoPor,
        ];
    }

    /** Representacion publica para la API REST. */
    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'codigo'                 => $this->codigo,
            'tipo'                   => $this->tipo(),
            'categoria'              => $this->categoria->toArray(),
            'marca'                  => $this->marca,
            'modelo'                 => $this->modelo,
            'numero_serie'           => $this->numeroSerie,
            'anio_fabricacion'       => $this->anioFabricacion,
            'fecha_ingreso'          => $this->fechaIngreso,
            'condicion'              => $this->condicion,
            'clasificacion'          => $this->clasificacion,
            'clasificacion_texto'    => $this->nombreClasificacion(),
            'estado'                 => $this->estado,
            'estado_texto'           => $this->nombreEstado(),
            'peso_kg'                => $this->pesoKg,
            'ubicacion'              => $this->ubicacion,
            'observaciones'          => $this->observaciones,
            'campana_id'             => $this->campanaId,
            'requiere_borrado_datos' => $this->requiereBorradoDatos(),
            'materiales_peligrosos'  => $this->contieneMaterialesPeligrosos(),
            'movimientos_permitidos' => $this->movimientosPermitidos(),
        ];
    }

    private static function enLista(string $valor, array $lista, string $nombre): string
    {
        if (!isset($lista[$valor])) {
            throw new \InvalidArgumentException("Valor de $nombre no válido: $valor");
        }
        return $valor;
    }

    private static function textoONulo(mixed $valor): ?string
    {
        $valor = $valor === null ? null : trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}
