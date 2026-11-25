import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import { DataSource } from 'typeorm';
import {
  CondicionEquipo,
  EstadoCampana,
  ResultadoReparacion,
  Rol,
  TipoCategoria,
  TipoMovimiento,
} from '../../common/enums';
import { CampanasService } from '../../modules/campanas/campanas.service';
import type { Categoria } from '../../modules/catalogos/categoria.entity';
import {
  CategoriasService,
  GestoresService,
} from '../../modules/catalogos/catalogos.service';
import { DiagnosticosService } from '../../modules/diagnosticos/diagnosticos.service';
import { EquiposService } from '../../modules/equipos/equipos.service';
import { MovimientosService } from '../../modules/movimientos/movimientos.service';
import { ReparacionesService } from '../../modules/reparaciones/reparaciones.service';
import { UsuariosService } from '../../modules/usuarios/usuarios.service';
import { SeedModule } from './seed.module';

/**
 * Datos de demostración. Se crean con los servicios de la aplicación para que
 * respeten todas las reglas de negocio (Factory, Strategy, trazabilidad).
 *
 *   npm run seed            → solo si la base está vacía
 *   npm run seed -- --reset → borra todo y vuelve a poblar
 */

const hoy = new Date();
const haceDias = (d: number) => {
  const f = new Date(hoy);
  f.setDate(f.getDate() - d);
  return f.toISOString().slice(0, 10);
};

/** Generador pseudoaleatorio determinista: el seed produce siempre los mismos datos. */
let semilla = 360;
const azar = () =>
  ((semilla = (semilla * 16807) % 2147483647) - 1) / 2147483646;
const elegir = <T>(lista: T[]) => lista[Math.floor(azar() * lista.length)];

const CATEGORIAS = [
  {
    nombre: 'Computadora de escritorio',
    tipo: TipoCategoria.COMPUTO,
    pesoPromedioKg: 8.5,
    valorReposicion: 15000,
  },
  {
    nombre: 'Laptop',
    tipo: TipoCategoria.COMPUTO,
    pesoPromedioKg: 2.2,
    valorReposicion: 14000,
  },
  {
    nombre: 'Monitor',
    tipo: TipoCategoria.PERIFERICO,
    pesoPromedioKg: 4.5,
    valorReposicion: 3200,
  },
  {
    nombre: 'Impresora',
    tipo: TipoCategoria.PERIFERICO,
    pesoPromedioKg: 7,
    valorReposicion: 4500,
  },
  {
    nombre: 'Teclado y ratón',
    tipo: TipoCategoria.PERIFERICO,
    pesoPromedioKg: 0.9,
    valorReposicion: 450,
  },
  {
    nombre: 'Proyector',
    tipo: TipoCategoria.PERIFERICO,
    pesoPromedioKg: 3,
    valorReposicion: 9000,
  },
  {
    nombre: 'Celular',
    tipo: TipoCategoria.MOVIL,
    pesoPromedioKg: 0.2,
    valorReposicion: 5000,
  },
  {
    nombre: 'Tableta',
    tipo: TipoCategoria.MOVIL,
    pesoPromedioKg: 0.5,
    valorReposicion: 6000,
  },
  {
    nombre: 'Batería / pila',
    tipo: TipoCategoria.ENERGIA,
    pesoPromedioKg: 0.4,
    valorReposicion: 600,
  },
  {
    nombre: 'No-break (UPS)',
    tipo: TipoCategoria.ENERGIA,
    pesoPromedioKg: 9,
    valorReposicion: 2500,
  },
  {
    nombre: 'Cables y cargadores',
    tipo: TipoCategoria.CABLEADO,
    pesoPromedioKg: 0.3,
    valorReposicion: 200,
  },
];

const MARCAS: Record<string, [string, string][]> = {
  'Computadora de escritorio': [
    ['Dell', 'OptiPlex 3050'],
    ['HP', 'ProDesk 400 G4'],
    ['Lenovo', 'ThinkCentre M710'],
  ],
  Laptop: [
    ['Lenovo', 'ThinkPad T470'],
    ['HP', 'ProBook 440 G5'],
    ['Dell', 'Latitude 5490'],
    ['Acer', 'Aspire 5'],
  ],
  Monitor: [
    ['Samsung', 'S22F350'],
    ['LG', '22MK400'],
    ['Dell', 'E2216H'],
  ],
  Impresora: [
    ['HP', 'LaserJet P1102w'],
    ['Epson', 'L3150'],
    ['Brother', 'HL-1212W'],
  ],
  'Teclado y ratón': [
    ['Logitech', 'MK270'],
    ['Genius', 'KM-160'],
  ],
  Proyector: [
    ['Epson', 'PowerLite S41'],
    ['BenQ', 'MS535'],
  ],
  Celular: [
    ['Samsung', 'Galaxy A10'],
    ['Motorola', 'Moto G7'],
    ['Xiaomi', 'Redmi 8'],
  ],
  Tableta: [
    ['Samsung', 'Galaxy Tab A'],
    ['Lenovo', 'Tab M8'],
  ],
  'Batería / pila': [
    ['Varios', 'Lote de pilas AA'],
    ['Genérica', 'Batería de laptop'],
  ],
  'No-break (UPS)': [
    ['APC', 'BX600'],
    ['CyberPower', 'UT750'],
  ],
  'Cables y cargadores': [
    ['Varios', 'Lote de cables VGA/HDMI'],
    ['Genérico', 'Cargadores de laptop'],
  ],
};

const AREAS = [
  'Laboratorio de cómputo 2',
  'Biblioteca central',
  'Departamento de Ciencias Básicas',
  'Laboratorio de redes',
  'Coordinación de Servicios Escolares',
  'Escuela primaria comunitaria (donación)',
];
const UBICACIONES = [
  'Almacén edificio B',
  'Bodega de mantenimiento',
  'Centro de cómputo',
  'Almacén general',
];

async function main() {
  const reset = process.argv.includes('--reset');
  const app = await NestFactory.createApplicationContext(SeedModule, {
    logger: ['error', 'warn'],
  });
  const ds = app.get(DataSource);

  const [{ total }] = await ds.query<{ total: number }[]>(
    'SELECT COUNT(*)::int AS total FROM usuarios',
  );
  if (total > 0 && !reset) {
    console.log(
      'La base ya tiene datos. Usa "npm run seed -- --reset" para reiniciarla.',
    );
    await app.close();
    return;
  }
  if (reset) {
    await ds.query(
      'TRUNCATE movimientos, reparaciones, diagnosticos, participaciones, campana_categorias, equipos, campanas, gestores, categorias, usuarios RESTART IDENTITY CASCADE',
    );
    console.log('Base de datos reiniciada.');
  }

  const password = process.env.SEED_DEMO_PASSWORD || 'Demo2026!';
  const usuarios = app.get(UsuariosService);
  const admin = await usuarios.crear({
    nombre: 'Ana Administradora',
    email: 'admin@ewaste360.mx',
    password,
    rol: Rol.ADMIN,
  });
  const resp = await usuarios.crear({
    nombre: 'Rubén Responsable',
    email: 'responsable@ewaste360.mx',
    password,
    rol: Rol.RESPONSABLE,
  });
  const capt = await usuarios.crear({
    nombre: 'Carla Capturista',
    email: 'capturista@ewaste360.mx',
    password,
    rol: Rol.CAPTURISTA,
  });
  console.log('✔ Usuarios demo creados');

  const categoriasSrv = app.get(CategoriasService);
  const categorias: Categoria[] = [];
  for (const c of CATEGORIAS) categorias.push(await categoriasSrv.crear(c));
  const cat = (nombre: string) => categorias.find((c) => c.nombre === nombre)!;
  console.log('✔ Categorías');

  const gestoresSrv = app.get(GestoresService);
  const g1 = await gestoresSrv.crear({
    nombre: 'Recicladora Ecológica del Norte',
    tipo: 'Reciclador de RAEE',
    contacto: 'contacto@recicladoranorte.example',
    direccion: 'Parque Industrial, Saltillo, Coah.',
  });
  const g2 = await gestoresSrv.crear({
    nombre: 'Centro de Acopio Municipal',
    tipo: 'Centro de acopio',
    contacto: '844 000 0000',
    direccion: 'Blvd. Ecología 100',
  });
  await gestoresSrv.crear({
    nombre: 'Compra-venta de chatarra "El Güero"',
    tipo: 'Chatarrería',
    contacto: 'Sin datos',
  });
  await gestoresSrv.verificar(
    g1.id,
    { documentoVerificacion: 'Autorización SEMARNAT 05-II-004-2026 (demo)' },
    admin.id,
  );
  await gestoresSrv.verificar(
    g2.id,
    { documentoVerificacion: 'Convenio municipal CM-118/2026 (demo)' },
    admin.id,
  );
  console.log('✔ Gestores (2 verificados, 1 sin verificar)');

  const campanasSrv = app.get(CampanasService);
  const todas = categorias.map((c) => c.id);
  const c1 = await campanasSrv.crear(
    {
      nombre: 'Reciclatón de primavera',
      descripcion:
        'Jornada de recolección de aparatos electrónicos en desuso para la comunidad estudiantil y vecinos.',
      fechaInicio: haceDias(220),
      fechaFin: haceDias(216),
      ubicacion: 'Explanada principal, edificio A',
      horario: '9:00 a 14:00 h',
      metaEquipos: 40,
      categoriasIds: todas,
    },
    resp.id,
  );
  const c2 = await campanasSrv.crear(
    {
      nombre: 'Dale otra vida a tu celular',
      descripcion:
        'Recibimos celulares, tabletas, cargadores y baterías. Borra tus datos antes de entregarlos; te ayudamos si lo necesitas.',
      fechaInicio: haceDias(15),
      fechaFin: haceDias(-20),
      ubicacion: 'Vestíbulo de la biblioteca central',
      horario: 'Lunes a viernes, 10:00 a 16:00 h',
      metaEquipos: 60,
      categoriasIds: [
        cat('Celular').id,
        cat('Tableta').id,
        cat('Cables y cargadores').id,
        cat('Batería / pila').id,
      ],
    },
    resp.id,
  );
  await campanasSrv.actualizar(c2.id, { estado: EstadoCampana.ACTIVA });
  await campanasSrv.crear(
    {
      nombre: 'Recolección de fin de semestre',
      descripcion:
        'Entrega de equipo de cómputo y periféricos de los departamentos antes del cierre del semestre.',
      fechaInicio: haceDias(-26),
      fechaFin: haceDias(-30),
      ubicacion: 'Estacionamiento norte',
      horario: '8:00 a 13:00 h',
      metaEquipos: 80,
      categoriasIds: todas.filter((id) => id !== cat('Batería / pila').id),
    },
    resp.id,
  );
  const nombres = [
    'Luis Hernández',
    'María García',
    'José Martínez',
    'Guadalupe López',
    'Juan Ramírez',
    'Rosa Flores',
    'Pedro Sánchez',
    'Carmen Torres',
    'Jorge Rivera',
    'Elena Gómez',
    'Miguel Díaz',
    'Patricia Cruz',
  ];
  for (const [i, n] of nombres.entries()) {
    const campana = i < 8 ? c1 : c2;
    await campanasSrv.participar(campana.id, {
      nombreParticipante: n,
      equiposDeclarados: 1 + (i % 3),
    });
  }
  await campanasSrv.actualizar(c1.id, { estado: EstadoCampana.CERRADA });
  console.log('✔ Campañas y participaciones');

  const equiposSrv = app.get(EquiposService);
  const diagnosticosSrv = app.get(DiagnosticosService);
  const reparacionesSrv = app.get(ReparacionesService);
  const movimientosSrv = app.get(MovimientosService);

  // Plan de destinos: cada equipo sigue un recorrido realista.
  type Ruta =
    | 'funcional-reasignar'
    | 'reparar-ok-reasignar'
    | 'reparar-ok'
    | 'reparar-falla-gestor'
    | 'en-reparacion'
    | 'gestor'
    | 'conservar'
    | 'pendiente'
    | 'reparable';
  const rutas: Ruta[] = [
    'funcional-reasignar',
    'funcional-reasignar',
    'funcional-reasignar',
    'reparar-ok-reasignar',
    'reparar-ok-reasignar',
    'reparar-ok',
    'reparar-falla-gestor',
    'en-reparacion',
    'gestor',
    'gestor',
    'conservar',
    'pendiente',
    'pendiente',
    'reparable',
  ];

  let n = 0;
  for (let i = 0; i < 46; i++) {
    const categoria = elegir(categorias);
    const [marca, modelo] = elegir(MARCAS[categoria.nombre]);
    let dias = Math.floor(azar() * 330) + 5;
    let esCampana: typeof c1 | null = null;
    if (i % 4 === 0) {
      // Equipo recibido en el Reciclatón de primavera.
      esCampana = c1;
      dias = 216 + (i % 5);
    } else if (
      i % 7 === 0 &&
      c2.categoriasAceptadas.some((x) => x.id === categoria.id)
    ) {
      esCampana = c2;
      dias = 2 + (i % 12);
    }
    const usuario = i % 3 === 0 ? resp : capt;

    const equipo = await equiposSrv.registrar(
      {
        categoriaId: categoria.id,
        marca,
        modelo,
        numeroSerie:
          categoria.tipo === TipoCategoria.COMPUTO || azar() > 0.5
            ? `SN-${(100000 + i * 7919).toString(36).toUpperCase()}`
            : undefined,
        fechaIngreso: haceDias(dias),
        ubicacion: elegir(UBICACIONES),
        campanaId: esCampana?.id,
      },
      usuario.id,
    );

    const ruta: Ruta =
      equipo.condicion === CondicionEquipo.GESTION_ESPECIALIZADA
        ? azar() > 0.3
          ? 'gestor'
          : 'pendiente'
        : elegir(rutas);
    const valor = categoria.valorReposicion;
    const diag = (
      d: Partial<Parameters<DiagnosticosService['registrar']>[1]>,
    ) =>
      diagnosticosSrv.registrar(
        equipo.id,
        {
          descripcion: 'Revisión técnica general.',
          enciende: true,
          danoEstructural: false,
          componentesPeligrosos: false,
          costoReparacionEstimado: 0,
          ...d,
        },
        resp.id,
      );
    const decidir = (
      d: Parameters<MovimientosService['registrarDecision']>[1],
    ) =>
      movimientosSrv.registrarDecision(
        equipo.id,
        { datosBorrados: true, ...d },
        resp.id,
      );
    const fechaRep = haceDias(Math.max(dias - 12, 0));

    switch (ruta) {
      case 'funcional-reasignar':
        await diag({
          descripcion:
            'Enciende y funciona correctamente. Limpieza general realizada.',
        });
        await decidir({
          tipo: TipoMovimiento.REASIGNACION,
          destino: elegir(AREAS),
          evidencia: `Oficio de resguardo RG-${200 + i}/2026`,
        });
        break;
      case 'reparar-ok-reasignar':
      case 'reparar-ok':
        await diag({
          descripcion: 'No enciende: falla en fuente de poder / pantalla.',
          enciende: false,
          costoReparacionEstimado: Math.round(valor * 0.2),
        });
        await decidir({ tipo: TipoMovimiento.ENVIO_REPARACION });
        await reparacionesSrv.registrar(
          equipo.id,
          {
            descripcion:
              'Reemplazo del componente dañado y pruebas de funcionamiento.',
            costo: Math.round(valor * 0.18),
            resultado: ResultadoReparacion.EXITOSA,
            fecha: fechaRep,
          },
          resp.id,
        );
        if (ruta === 'reparar-ok-reasignar') {
          await decidir({
            tipo: TipoMovimiento.REASIGNACION,
            destino: elegir(AREAS),
            evidencia: `Oficio de resguardo RG-${300 + i}/2026`,
          });
        }
        break;
      case 'reparar-falla-gestor':
        await diag({
          descripcion: 'Falla intermitente en tarjeta madre.',
          enciende: false,
          costoReparacionEstimado: Math.round(valor * 0.3),
        });
        await decidir({ tipo: TipoMovimiento.ENVIO_REPARACION });
        await reparacionesSrv.registrar(
          equipo.id,
          {
            descripcion: 'Se intentó reparar la tarjeta madre sin éxito.',
            costo: Math.round(valor * 0.1),
            resultado: ResultadoReparacion.FALLIDA,
            fecha: fechaRep,
          },
          resp.id,
        );
        await decidir({
          tipo: TipoMovimiento.ENTREGA_GESTOR,
          gestorId: g1.id,
          evidencia: `Manifiesto de entrega ME-${500 + i}`,
        });
        break;
      case 'en-reparacion':
        await diag({
          descripcion: 'Teclado y bisagras dañados.',
          enciende: true,
          costoReparacionEstimado: Math.round(valor * 0.15),
        });
        await decidir({ tipo: TipoMovimiento.ENVIO_REPARACION });
        break;
      case 'reparable':
        await diag({
          descripcion: 'Requiere cambio de disco duro.',
          enciende: false,
          costoReparacionEstimado: Math.round(valor * 0.25),
        });
        break;
      case 'gestor':
        if (equipo.condicion !== CondicionEquipo.GESTION_ESPECIALIZADA) {
          await diag({
            descripcion: 'Carcasa rota y placa quemada.',
            enciende: false,
            danoEstructural: true,
          });
        }
        await decidir({
          tipo: TipoMovimiento.ENTREGA_GESTOR,
          gestorId: elegir([g1, g2]).id,
          evidencia: `Manifiesto de entrega ME-${600 + i}`,
        });
        break;
      case 'conservar':
        await diag({
          descripcion: 'Funciona; se guarda como equipo de respaldo.',
        });
        await decidir({
          tipo: TipoMovimiento.CONSERVACION,
          destino: 'Almacén de equipos de respaldo',
        });
        break;
      case 'pendiente':
        break;
    }
    n++;
  }

  // Los servicios registran todo con la fecha de hoy; se distribuye el historial en el tiempo
  // a partir de la fecha de ingreso de cada equipo para que las gráficas mensuales sean realistas.
  await ds.query(`
    WITH orden AS (
      SELECT m.id, e.fecha_ingreso,
             ROW_NUMBER() OVER (PARTITION BY m.equipo_id ORDER BY m.id) - 1 AS paso
      FROM movimientos m JOIN equipos e ON e.id = m.equipo_id
    )
    UPDATE movimientos m
    SET creado_en = LEAST(orden.fecha_ingreso + (orden.paso * 6) * INTERVAL '1 day' + INTERVAL '10 hour', NOW())
    FROM orden WHERE orden.id = m.id`);
  await ds.query(`
    UPDATE diagnosticos d SET creado_en = LEAST(e.fecha_ingreso + INTERVAL '6 day 11 hour', NOW())
    FROM equipos e WHERE e.id = d.equipo_id`);
  await ds.query(`
    UPDATE reparaciones r SET creado_en = r.fecha + INTERVAL '15 hour'
    FROM equipos e WHERE e.id = r.equipo_id`);
  await ds.query(
    `UPDATE equipos SET creado_en = fecha_ingreso + INTERVAL '9 hour'`,
  );

  console.log(`✔ ${n} equipos con su historial`);
  console.log('\nCuentas demo (contraseña definida en SEED_DEMO_PASSWORD):');
  console.log(
    '  admin@ewaste360.mx · responsable@ewaste360.mx · capturista@ewaste360.mx',
  );
  await app.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
