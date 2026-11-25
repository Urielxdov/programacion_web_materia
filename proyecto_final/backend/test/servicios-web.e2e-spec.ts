import { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import request from 'supertest';
import { DataSource } from 'typeorm';
import { AppModule } from '../src/app.module';
import { configurarApp } from '../src/app.setup';
import { Rol, TipoCategoria } from '../src/common/enums';
import {
  CategoriasService,
  GestoresService,
} from '../src/modules/catalogos/catalogos.service';
import { UsuariosService } from '../src/modules/usuarios/usuarios.service';

/**
 * Pruebas de los servicios web (API de equipos e indicadores) de punta a punta:
 * HTTP real → guards (sesión, rol, CSRF) → controladores → servicios → PostgreSQL.
 */
describe('Servicios web E-Waste 360°', () => {
  let app: INestApplication;
  let laptopId: number;
  let bateriaId: number;
  let gestorVerificadoId: number;
  let gestorSinVerificarId: number;
  const PASS = 'Prueba2026';

  /** Agente con cookies propias que inicia sesión y guarda su token CSRF. */
  async function sesion(email?: string) {
    const agente = request.agent(app.getHttpServer());
    let token = (await agente.get('/api/auth/csrf')).body.csrfToken as string;
    if (email) {
      const r = await agente
        .post('/api/auth/login')
        .set('X-CSRF-Token', token)
        .send({ email, password: PASS })
        .expect(200);
      token = r.body.csrfToken;
    }
    const conToken = (req: request.Test) => req.set('X-CSRF-Token', token);
    return {
      agente,
      get: (url: string) => agente.get(url),
      post: (url: string, body?: object) =>
        conToken(agente.post(url)).send(body ?? {}),
      patch: (url: string, body: object) =>
        conToken(agente.patch(url)).send(body),
    };
  }

  beforeAll(async () => {
    const modulo = await Test.createTestingModule({
      imports: [AppModule],
    }).compile();
    app = modulo.createNestApplication();
    configurarApp(app);
    await app.init();

    // Base de pruebas limpia en cada corrida.
    const ds = app.get(DataSource);
    await ds.query(
      'TRUNCATE movimientos, reparaciones, diagnosticos, participaciones, campana_categorias, equipos, campanas, gestores, categorias, usuarios RESTART IDENTITY CASCADE',
    );
    const usuarios = app.get(UsuariosService);
    const admin = await usuarios.crear({
      nombre: 'Admin Prueba',
      email: 'admin@test.mx',
      password: PASS,
      rol: Rol.ADMIN,
    });
    await usuarios.crear({
      nombre: 'Resp Prueba',
      email: 'resp@test.mx',
      password: PASS,
      rol: Rol.RESPONSABLE,
    });
    await usuarios.crear({
      nombre: 'Capt Prueba',
      email: 'capt@test.mx',
      password: PASS,
      rol: Rol.CAPTURISTA,
    });

    const categorias = app.get(CategoriasService);
    await categorias.crear({
      nombre: 'Laptop',
      tipo: TipoCategoria.COMPUTO,
      pesoPromedioKg: 2,
      valorReposicion: 14000,
    });
    await categorias.crear({
      nombre: 'Batería',
      tipo: TipoCategoria.ENERGIA,
      pesoPromedioKg: 0.5,
      valorReposicion: 600,
    });

    const gestores = app.get(GestoresService);
    const g1 = await gestores.crear({
      nombre: 'Reciclador Verificado',
      tipo: 'Reciclador',
    });
    const g2 = await gestores.crear({
      nombre: 'Chatarrería',
      tipo: 'Chatarra',
    });
    await gestores.verificar(
      g1.id,
      { documentoVerificacion: 'AUT-001' },
      admin.id,
    );
    gestorVerificadoId = g1.id;
    gestorSinVerificarId = g2.id;
  });

  afterAll(async () => {
    await app.close();
  });

  describe('Autenticación, sesiones y CSRF', () => {
    it('rechaza rutas privadas sin sesión (401)', async () => {
      await request(app.getHttpServer()).get('/api/equipos').expect(401);
    });

    it('rechaza peticiones de escritura sin token CSRF (403)', async () => {
      await request(app.getHttpServer())
        .post('/api/auth/login')
        .send({ email: 'admin@test.mx', password: PASS })
        .expect(403);
    });

    it('rechaza credenciales incorrectas con un mensaje genérico', async () => {
      const { agente } = await sesion();
      const token = (await agente.get('/api/auth/csrf')).body.csrfToken;
      const r = await agente
        .post('/api/auth/login')
        .set('X-CSRF-Token', token)
        .send({ email: 'admin@test.mx', password: 'mala' });
      expect(r.status).toBe(401);
      expect(r.body.message).toBe('Correo o contraseña incorrectos.');
    });

    it('la cookie de sesión es HttpOnly', async () => {
      const agente = request.agent(app.getHttpServer());
      const token = (await agente.get('/api/auth/csrf')).body.csrfToken;
      const r = await agente
        .post('/api/auth/login')
        .set('X-CSRF-Token', token)
        .send({ email: 'capt@test.mx', password: PASS });
      const cookies = ([] as string[]).concat(r.headers['set-cookie'] ?? []);
      expect(cookies.find((c) => c.startsWith('ew.sid'))).toMatch(/HttpOnly/i);
    });

    it('una cuenta desactivada pierde el acceso aunque tenga sesión abierta', async () => {
      const usuarios = app.get(UsuariosService);
      const temporal = await usuarios.crear({
        nombre: 'Temporal',
        email: 'temp@test.mx',
        password: PASS,
        rol: Rol.CAPTURISTA,
      });
      const s = await sesion('temp@test.mx');
      await s.get('/api/equipos').expect(200);
      await usuarios.actualizar(temporal.id, { activo: false }, 0);
      await s.get('/api/equipos').expect(401);
    });

    it('GET /api/auth/me devuelve el usuario sin datos sensibles', async () => {
      const s = await sesion('resp@test.mx');
      const r = await s.get('/api/auth/me').expect(200);
      expect(r.body.usuario).toMatchObject({
        email: 'resp@test.mx',
        rol: 'RESPONSABLE',
      });
      expect(r.body.usuario.passwordHash).toBeUndefined();
    });
  });

  describe('Servicio 1: API de equipos', () => {
    it('POST /api/equipos valida los datos (400)', async () => {
      const s = await sesion('capt@test.mx');
      const r = await s
        .post('/api/equipos', {
          categoriaId: 1,
          marca: '',
          modelo: 'X',
          fechaIngreso: 'ayer',
        })
        .expect(400);
      expect(Array.isArray(r.body.message)).toBe(true);
    });

    it('rechaza campos no permitidos (whitelist)', async () => {
      const s = await sesion('capt@test.mx');
      await s
        .post('/api/equipos', {
          categoriaId: 1,
          marca: 'Dell',
          modelo: 'X',
          numeroSerie: 'A1',
          fechaIngreso: '2026-11-01',
          condicion: 'FUNCIONAL',
        })
        .expect(400);
    });

    it('aplica la regla de la Factory: cómputo exige número de serie (422)', async () => {
      const s = await sesion('capt@test.mx');
      await s
        .post('/api/equipos', {
          categoriaId: 1,
          marca: 'Dell',
          modelo: 'Latitude',
          fechaIngreso: '2026-11-01',
        })
        .expect(422);
    });

    it('POST /api/equipos registra un equipo con folio y movimiento de ingreso (201)', async () => {
      const s = await sesion('capt@test.mx');
      const r = await s
        .post('/api/equipos', {
          categoriaId: 1,
          marca: 'Dell',
          modelo: 'Latitude 5490',
          numeroSerie: 'SN-E2E-1',
          fechaIngreso: '2026-11-01',
        })
        .expect(201);
      expect(r.body.folio).toMatch(/^EW-\d{4}-0001$/);
      expect(r.body.condicion).toBe('PENDIENTE');
      expect(r.body.movimientos.map((m: { tipo: string }) => m.tipo)).toEqual([
        'INGRESO',
      ]);
      laptopId = r.body.id;

      const b = await s
        .post('/api/equipos', {
          categoriaId: 2,
          marca: 'Varios',
          modelo: 'Pilas',
          fechaIngreso: '2026-11-01',
        })
        .expect(201);
      expect(b.body.condicion).toBe('GESTION_ESPECIALIZADA');
      bateriaId = b.body.id;
    });

    it('no permite números de serie duplicados (409)', async () => {
      const s = await sesion('capt@test.mx');
      await s
        .post('/api/equipos', {
          categoriaId: 1,
          marca: 'HP',
          modelo: 'X',
          numeroSerie: 'sn-e2e-1',
          fechaIngreso: '2026-11-01',
        })
        .expect(409);
    });

    it('GET /api/equipos busca con filtros y paginación', async () => {
      const s = await sesion('capt@test.mx');
      const r = await s.get('/api/equipos?q=latitude&porPagina=5').expect(200);
      expect(r.body.total).toBe(1);
      expect(r.body.datos[0].id).toBe(laptopId);
    });

    it('GET /api/equipos/:id devuelve el detalle o 404', async () => {
      const s = await sesion('capt@test.mx');
      const r = await s.get(`/api/equipos/${laptopId}`).expect(200);
      expect(r.body.accionesDisponibles).toContain('DIAGNOSTICAR');
      expect(r.body.recomendacionesEntrega.length).toBeGreaterThan(0);
      await s.get('/api/equipos/99999').expect(404);
    });

    it('PATCH /api/equipos/:id actualiza solo campos permitidos', async () => {
      const s = await sesion('capt@test.mx');
      const r = await s
        .patch(`/api/equipos/${laptopId}`, { ubicacion: 'Almacén B' })
        .expect(200);
      expect(r.body.ubicacion).toBe('Almacén B');
      await s
        .patch(`/api/equipos/${laptopId}`, { estadoDestino: 'REASIGNADO' })
        .expect(400);
    });

    it('el capturista no puede diagnosticar (403: autorización en el servidor)', async () => {
      const s = await sesion('capt@test.mx');
      await s
        .post(`/api/equipos/${laptopId}/diagnosticos`, {
          descripcion: 'No enciende',
          enciende: false,
          danoEstructural: false,
          componentesPeligrosos: false,
          costoReparacionEstimado: 1000,
        })
        .expect(403);
    });

    it('ciclo completo: diagnóstico (Strategy) → reparación → reasignación', async () => {
      const s = await sesion('resp@test.mx');
      const d = await s
        .post(`/api/equipos/${laptopId}/diagnosticos`, {
          descripcion: 'No enciende',
          enciende: false,
          danoEstructural: false,
          componentesPeligrosos: false,
          costoReparacionEstimado: 2000,
        })
        .expect(201);
      expect(d.body.recomendacion).toBe('REPARABLE');

      await s
        .post(`/api/equipos/${laptopId}/movimientos`, {
          tipo: 'REASIGNACION',
          destino: 'Lab',
          evidencia: 'OF-1',
          datosBorrados: true,
        })
        .expect(422);
      await s
        .post(`/api/equipos/${laptopId}/movimientos`, {
          tipo: 'ENVIO_REPARACION',
        })
        .expect(201);
      const rep = await s
        .post(`/api/equipos/${laptopId}/reparaciones`, {
          descripcion: 'Cambio de fuente',
          costo: 1500,
          resultado: 'EXITOSA',
          fecha: '2026-11-10',
        })
        .expect(201);
      expect(rep.body.ahorroEstimado).toBe(12500);

      await s
        .post(`/api/equipos/${laptopId}/movimientos`, {
          tipo: 'REASIGNACION',
          destino: 'Lab',
          evidencia: 'OF-1',
        })
        .expect(422);
      await s
        .post(`/api/equipos/${laptopId}/movimientos`, {
          tipo: 'REASIGNACION',
          destino: 'Laboratorio de redes',
          evidencia: 'OF-1',
          datosBorrados: true,
        })
        .expect(201);

      const historial = await s
        .get(`/api/equipos/${laptopId}/movimientos`)
        .expect(200);
      expect(historial.body.map((m: { tipo: string }) => m.tipo)).toEqual([
        'REASIGNACION',
        'REPARACION',
        'ENVIO_REPARACION',
        'DIAGNOSTICO',
        'INGRESO',
      ]);
    });

    it('solo permite entregar a gestores verificados y con evidencia', async () => {
      const s = await sesion('resp@test.mx');
      await s
        .post(`/api/equipos/${bateriaId}/movimientos`, {
          tipo: 'ENTREGA_GESTOR',
          gestorId: gestorVerificadoId,
        })
        .expect(400);
      await s
        .post(`/api/equipos/${bateriaId}/movimientos`, {
          tipo: 'ENTREGA_GESTOR',
          gestorId: gestorSinVerificarId,
          evidencia: 'ME-1',
        })
        .expect(422);
      await s
        .post(`/api/equipos/${bateriaId}/movimientos`, {
          tipo: 'ENTREGA_GESTOR',
          gestorId: gestorVerificadoId,
          evidencia: 'ME-1',
        })
        .expect(201);
    });
  });

  describe('Servicio 2: API de indicadores', () => {
    it('GET /api/indicadores devuelve totales coherentes', async () => {
      const s = await sesion('capt@test.mx');
      const r = await s.get('/api/indicadores').expect(200);
      expect(r.body.equipos.total).toBe(2);
      expect(r.body.equipos.reasignados).toBe(1);
      expect(r.body.gestores.equiposEntregados).toBe(1);
      expect(r.body.economia.ahorroEstimado).toBe(12500);
    });

    it('GET /api/indicadores/reutilizacion y /campanas requieren rol de gestión', async () => {
      const capt = await sesion('capt@test.mx');
      await capt.get('/api/indicadores/reutilizacion').expect(403);
      await capt.get('/api/indicadores/campanas').expect(403);

      const resp = await sesion('resp@test.mx');
      const r = await resp.get('/api/indicadores/reutilizacion').expect(200);
      expect(r.body.reparaciones).toMatchObject({
        total: 1,
        exitosas: 1,
        tasaExito: 100,
      });
      expect(r.body.serieMensual).toHaveLength(12);
      await resp.get('/api/indicadores/campanas').expect(200);
    });

    it('GET /api/indicadores/publico es accesible sin sesión', async () => {
      const r = await request(app.getHttpServer())
        .get('/api/indicadores/publico')
        .expect(200);
      expect(r.body).toMatchObject({
        equiposRegistrados: 2,
        equiposRecuperados: 1,
      });
    });
  });
});
