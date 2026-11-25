"""Genera el documento UML editable de draw.io usando solo la biblioteca estándar.

Ejecutar desde cualquier carpeta: python ruta/a/generar_uml.py
"""
from pathlib import Path
import xml.etree.ElementTree as ET
import textwrap

OUT = Path(__file__).resolve().parent
DOC = ET.Element('mxfile', host='app.diagrams.net', version='24.7.17', type='device')


class Page:
    def __init__(self, name, width=1600, height=1300):
        self.name, self.width, self.height = name, width, height
        d = ET.SubElement(DOC, 'diagram', id=f'p{len(DOC)+1:02}', name=name)
        model = ET.SubElement(d, 'mxGraphModel', dx='1600', dy='1000', grid='1', gridSize='10',
                              guides='1', tooltips='1', connect='1', arrows='1', fold='1',
                              page='1', pageScale='1', pageWidth=str(width), pageHeight=str(height), math='0', shadow='0')
        self.root = ET.SubElement(model, 'root')
        ET.SubElement(self.root, 'mxCell', id='0')
        ET.SubElement(self.root, 'mxCell', id='1', parent='0')
        self.count = 1
        self.text(name, 35, 20, width-70, 42, size=24, bold=True)

    def cell(self, value, x, y, w, h, style='', parent='1'):
        self.count += 1
        ident = f'n{self.count}'
        c = ET.SubElement(self.root, 'mxCell', id=ident, value=value, parent=parent, vertex='1',
                          style='fontFamily=Helvetica;fontSize=14;whiteSpace=wrap;html=0;'+style)
        ET.SubElement(c, 'mxGeometry', x=str(x), y=str(y), width=str(w), height=str(h), attrib={'as': 'geometry'})
        return ident

    def text(self, value, x, y, w, h, size=14, bold=False):
        return self.cell(value, x, y, w, h, f'text;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;fontSize={size};fontStyle={1 if bold else 0};')

    def note(self, value, x, y, w, h):
        return self.cell(value, x, y, w, h, 'shape=note;size=15;fillColor=#fff2cc;strokeColor=#d6b656;align=left;spacing=12;fontSize=13;')

    def edge(self, source=None, target=None, label='', style='', points=None, route=None, label_pos=None):
        self.count += 1
        attrs = dict(id=f'n{self.count}', value=label, parent='1', edge='1',
                     style='fontFamily=Helvetica;fontSize=12;whiteSpace=nowrap;html=0;labelBackgroundColor=#ffffff;strokeColor=#475569;endArrow=none;'+style)
        if source: attrs['source'] = source
        if target: attrs['target'] = target
        c = ET.SubElement(self.root, 'mxCell', **attrs)
        g = ET.SubElement(c, 'mxGeometry', relative='1', attrib={'as': 'geometry'})
        if label_pos is not None:
            g.set('x', str(label_pos))
        if points:
            for role, pt in zip(('sourcePoint', 'targetPoint'), points):
                ET.SubElement(g, 'mxPoint', x=str(pt[0]), y=str(pt[1]), attrib={'as': role})
        if route:
            arr = ET.SubElement(g, 'Array', attrib={'as': 'points'})
            for x, y in route:
                ET.SubElement(arr, 'mxPoint', x=str(x), y=str(y))
        return c

    def klass(self, name, attrs, methods, x, y, w=350, color='#dae8fc'):
        ah = max(42, 22*len(attrs)+16)
        mh = max(42, 22*len(methods)+16)
        h = 42+ah+mh
        root = self.cell(name, x, y, w, h, f'swimlane;startSize=42;horizontal=1;fillColor={color};swimlaneFillColor=#ffffff;strokeColor=#64748b;fontStyle=1;align=center;')
        self.cell('\n'.join(attrs) or ' ', 0, 42, w, ah, 'fillColor=#ffffff;strokeColor=#64748b;align=left;spacingLeft=12;verticalAlign=top;spacingTop=8;fontSize=13;', root)
        self.cell('\n'.join(methods) or ' ', 0, 42+ah, w, mh, 'fillColor=#ffffff;strokeColor=#64748b;align=left;spacingLeft=12;verticalAlign=top;spacingTop=8;fontSize=13;', root)
        return root


PUBLIC = ['Consultar información ambiental y guía', 'Consultar campañas y cifras públicas', 'Registrar participación en campaña']
CAPTURE = ['Iniciar y cerrar sesión', 'Registrar equipo', 'Buscar, consultar y actualizar equipo', 'Consultar historial e imprimir ficha', 'Consultar resumen del panel']
MANAGE = ['Simular y registrar diagnóstico', 'Enviar a reparación y registrar resultado', 'Reasignar, conservar o entregar a gestor', 'Crear y actualizar campañas', 'Consultar participantes y reportes', 'Registrar y actualizar gestores']
ADMIN = ['Administrar usuarios y roles', 'Administrar categorías', 'Verificar o revocar gestores']


def use_cases(name, actor, cases):
    height = max(750, 220+len(cases)*92)
    p = Page(name, 1050, height)
    boundary = p.cell('E-Waste 360°', 360, 105, 620, height-160,
                      'fillColor=none;strokeColor=#94a3b8;align=left;verticalAlign=top;spacing=14;fontStyle=1;')
    # Casos son elementos independientes para facilitar edición en draw.io.
    a = p.cell(actor, 80, height/2-70, 95, 150, 'shape=umlActor;fillColor=#ffffff;strokeColor=#334155;verticalLabelPosition=bottom;verticalAlign=top;')
    for i, label in enumerate(cases):
        uc = p.cell(label, 435, 160+i*92, 470, 65, 'ellipse;fillColor=#e2f0d9;strokeColor=#548235;')
        p.edge(a, uc, style='exitX=1;exitY=0.5;entryX=0;entryY=0.5;')
    return p


p = Page('01 - Casos de uso general', 1250, 1230)
p.text('Asociaciones explícitas por permisos; no se presupone herencia entre roles.', 35, 70, 1500, 35)
p.cell('E-Waste 360°', 455, 125, 735, 1050, 'fillColor=none;strokeColor=#94a3b8;align=left;verticalAlign=top;spacing=15;fontStyle=1;')
actors = {}
for i, role in enumerate(['Visitante', 'Capturista', 'Responsable ambiental', 'Administrador']):
    actors[role] = p.cell(role, 65, 175+i*235, 105, 140, 'shape=umlActor;fillColor=#ffffff;strokeColor=#334155;verticalLabelPosition=bottom;verticalAlign=top;')
general = [
    ('Consultar información y campañas públicas', 430, 195, list(actors)),
    ('Registrar participación en campaña', 1060, 195, list(actors)),
    ('Autenticarse y gestionar la sesión', 430, 420, list(actors)[1:]),
    ('Gestionar inventario e historial; ver resumen', 1060, 420, list(actors)[1:]),
    ('Gestionar diagnósticos y reparaciones', 430, 645, list(actors)[2:]),
    ('Registrar decisiones de destino', 1060, 645, list(actors)[2:]),
    ('Gestionar campañas y consultar reportes', 430, 860, list(actors)[2:]),
    ('Registrar y actualizar gestores', 1060, 860, list(actors)[2:]),
    ('Administrar usuarios y categorías', 430, 1020, ['Administrador']),
    ('Verificar o revocar gestores', 1060, 1020, ['Administrador']),
]
for index, (label, x, y, roles) in enumerate(general):
    uc = p.cell(label, 530, 175+index*96, 580, 65, 'ellipse;fillColor=#e2f0d9;strokeColor=#548235;')
    for role in roles:
        p.edge(actors[role], uc, style='entryX=0;entryY=0.5;opacity=55;')

use_cases('02 - Casos de uso - Visitante', 'Visitante', PUBLIC)
use_cases('03 - Casos de uso - Capturista', 'Capturista', PUBLIC+CAPTURE)
use_cases('04 - Casos de uso - Responsable', 'Responsable ambiental', PUBLIC+CAPTURE+MANAGE)
use_cases('05 - Casos de uso - Administrador', 'Administrador', PUBLIC+CAPTURE+MANAGE+ADMIN)

p = Page('06 - Clases - Entidades', 1750, 1570)
p.note('Vista selectiva de atributos y operaciones. + público, - privado. passwordHash es público en TypeScript, pero está excluido de las consultas por defecto (select: false).', 35, 80, 1680, 75)
base = p.klass('«abstract» EntidadBase', ['+ id: number', '+ creadoEn: Date', '+ actualizadoEn: Date'], [], 690, 185, 370)
data = [
    ('Usuario', ['+ nombre: string', '+ email: string', '+ passwordHash: string {select:false}', '+ rol: Rol', '+ activo: boolean'], ['+ aSesion(): UsuarioSesion', '+ tieneRol(...roles): boolean', '+ desactivar(): void'], 40, 420),
    ('Categoria', ['+ nombre: string', '+ tipo: TipoCategoria', '+ pesoPromedioKg: number', '+ valorReposicion: number'], ['+ esPeligrosa(): boolean'], 690, 420),
    ('Gestor', ['+ nombre: string', '+ verificado: boolean', '+ documentoVerificacion: string?', '+ verificadoPor: Usuario?'], ['+ verificar(documento, responsable)', '+ revocarVerificacion()', '+ puedeRecibirEquipos(): boolean'], 1340, 420),
    ('Campana', ['+ nombre: string', '+ fechaInicio / fechaFin: string', '+ estado: EstadoCampana'], ['+ aceptaParticipaciones(): boolean', '+ acepta(categoriaId): boolean', '+ exigirAbierta(): void'], 40, 850),
    ('Equipo', ['+ folio: string', '+ condicion: CondicionEquipo', '+ estadoDestino: EstadoDestino', '+ requiereBorradoDatos: boolean', '+ datosBorrados: boolean'], ['+ aplicarDiagnostico(condicion)', '+ enviarAReparacion()', '+ registrarResultadoReparacion(ok)', '+ reasignar(destino, borrado)', '+ conservar(ubicacion)', '+ entregarAGestor(gestor, borrado)', '- exigirDisponible()', '- exigirBorradoDatos(confirmado)'], 690, 800),
    ('Movimiento', ['+ tipo: TipoMovimiento', '+ estadoAnterior: EstadoDestino?', '+ estadoNuevo: EstadoDestino', '+ evidencia: string?'], [], 1340, 850),
    ('Participacion', ['+ nombreParticipante: string', '+ equiposDeclarados: number', '+ contacto: string?'], [], 40, 1250),
    ('Diagnostico', ['+ descripcion: string', '+ recomendacion: CondicionEquipo', '+ estrategiaAplicada: string', '+ costoReparacionEstimado: number'], [], 690, 1310),
    ('Reparacion', ['+ resultado: ResultadoReparacion', '+ costo / ahorroEstimado: number', '+ fecha: string'], ['+ calcularAhorro(valor, costo, ok) {static}'], 1340, 1250),
]
ids = {}
for name, attrs, methods, x, y in data:
    ids[name] = p.klass(name, attrs, methods, x, y, 370)
    gutter = 20 if x == 40 else (650 if x == 690 else 1730)
    side = 1 if x == 1340 else 0
    p.edge(ids[name], base, style=f'endArrow=block;endFill=0;opacity=35;exitX={side};exitY=0.1;entryX=0.5;entryY=1;',
           route=[(gutter,y+20),(gutter,375),(875,375)])
for a, b, label in [
    ('Categoria','Equipo','1 categoría / 0..* equipos'),
    ('Campana','Equipo','0..1 campaña / 0..* equipos'),
    ('Campana','Categoria','0..* ↔ 0..* acepta'),
    ('Campana','Participacion','1 campaña / 0..* participaciones'),
    ('Equipo','Diagnostico','1 equipo / 0..* diagnósticos'),
    ('Equipo','Reparacion','1 equipo / 0..* reparaciones'),
    ('Equipo','Movimiento','1 equipo / 0..* movimientos'),
    ('Gestor','Movimiento','0..1 gestor / 0..* movimientos'),
    ('Diagnostico','Reparacion','0..1 diagnóstico / 0..* reparaciones'),
]:
    if (a,b) == ('Campana','Categoria'):
        p.edge(ids[a], ids[b], label, 'exitX=0.5;exitY=0;entryX=1;entryY=0.6;', route=[(225,755),(1160,755),(1160,535)])
    elif (a,b) == ('Equipo','Reparacion'):
        p.edge(ids[a], ids[b], label, 'exitX=1;exitY=0.8;entryX=0;entryY=0.45;', route=[(1230,1088),(1230,1325)])
    else:
        p.edge(ids[a], ids[b], label, 'edgeStyle=orthogonalEdgeStyle;rounded=0;')
p.note('Las nueve entidades heredan de EntidadBase (triángulo vacío). Asociaciones de Usuario como responsable, creador o registrador son opcionales (0..1) y se detallan en el Markdown para reducir cruces.', 35, 1480, 610, 65)

# Servicios y repositorios: una fila por módulo, sin cruces entre módulos.
p = Page('07 - Clases - Servicios y repositorios', 1750, 2300)
p.note('Dependencia discontinua: utiliza. Generalización con triángulo vacío: hereda. Métodos seleccionados; dependencias secundarias se enumeran en la nota final.', 35, 80, 1680, 65)
base = p.klass('«abstract» RepositorioBase<T>', ['# repo: Repository<T>', '# dataSource: DataSource'], ['+ obtenerPorId(id, relaciones?, manager?)', '+ guardar(entidad, manager?)', '+ transaccion(trabajo)', '+ crear(datos)'], 1310, 180, 390, '#fff2cc')
rows = [
    ('AuthService', ['+ autenticar(dto)', '+ refrescar(sesion)'], 'UsuariosRepository', ['+ buscarConPasswordPorEmail(email)', '+ contarAdminsActivos()'], 'Usuario'),
    ('UsuariosService', ['+ listar() / obtener(id)', '+ crear(dto) / actualizar(id, dto, idActual)'], None, [], 'Usuario'),
    ('CategoriasService', ['+ listar() / crear(dto)', '+ actualizar(id, dto)'], 'CategoriasRepository', ['+ listar() / buscarVarias(ids)', '+ existeNombre(nombre, excluirId?)'], 'Categoria'),
    ('GestoresService', ['+ listar() / crear(dto) / actualizar(id, dto)', '+ verificar(id, dto, usuarioId) / revocar(id)'], 'GestoresRepository', ['+ listar(soloVerificados)'], 'Gestor'),
    ('EquiposService', ['+ buscar(filtros) / detalle(id)', '+ registrar(dto, usuarioId)', '+ actualizar(id, dto)'], 'EquiposRepository', ['+ buscar(filtros) / detalle(id)', '+ siguienteFolio(fecha, manager?)', '+ obtenerParaActualizar(id, manager)'], 'Equipo'),
    ('DiagnosticosService', ['+ simular(equipoId, dto)', '+ registrar(equipoId, dto, usuarioId)'], 'DiagnosticosRepository', ['+ ultimoDeEquipo(equipoId)'], 'Diagnostico'),
    ('ReparacionesService', ['+ registrar(equipoId, dto, usuarioId)'], 'ReparacionesRepository', [], 'Reparacion'),
    ('MovimientosService', ['+ historial(equipoId) / recientes(limite)', '+ registrarDecision(equipoId, dto, usuarioId)', '+ registrarEvento(evento)'], 'MovimientosRepository', ['+ deEquipo(equipoId)', '+ recientes(limite)'], 'Movimiento'),
    ('CampanasService', ['+ listar(estado?) / detalle(id)', '+ crear(dto, usuarioId) / actualizar(id, dto)', '+ participar(id, dto, sesion?)', '+ participantes(campanaId)'], 'CampanasRepository', ['+ listarConResumen(estado?)', '+ obtenerConResumen(id)'], 'Campana'),
    ('IndicadoresService', ['+ resumen() / campanas()', '+ reutilizacion() / publico()'], 'IndicadoresRepository', ['- filas(sql, parametros)', '+ totalesEquipos() / reparaciones()', '+ resultadosCampanas()'], None),
]
repos = {}
for i, (svc, methods, repo, rm, entity) in enumerate(rows):
    y = 180+i*190
    a = p.klass(svc, [], methods, 35, y, 490, '#e2f0d9')
    if repo:
        b = p.klass(repo, [], rm, 680, y, 470)
        repos[repo] = b
        if entity:
            p.edge(b, base, f'T = {entity}', 'endArrow=block;endFill=0;edgeStyle=orthogonalEdgeStyle;opacity=45;', label_pos=-0.8)
    else:
        b = repos['UsuariosRepository']
    p.edge(a, b, 'utiliza', 'dashed=1;endArrow=open;')
    if svc == 'CampanasService':
        pr = p.klass('ParticipacionesRepository', [], ['+ listarPorCampana(campanaId)'], 1310, 1900, 390)
        p.edge(a, pr, 'utiliza', 'dashed=1;endArrow=open;exitX=1;exitY=0.7;entryX=0;entryY=0.5;', route=[(600,1830),(600,1865),(1245,1865),(1245,1963)])
        p.edge(pr, base, 'T = Participacion', 'endArrow=block;endFill=0;exitX=0;exitY=0.1;entryX=1;entryY=1;opacity=45;', route=[(1255,1915),(1255,425),(1720,425),(1720,390)], label_pos=-0.8)
p.note('Dependencias complementarias: AuthService y UsuariosService → PasswordService; EquiposService → EquipoFactory, categorías, usuarios y campañas; DiagnosticosService → equipos, usuarios y ClasificadorDestino; ReparacionesService → equipos, diagnósticos y usuarios; MovimientosService → equipos, gestores y usuarios; CampanasService → categorías y usuarios; GestoresService → usuarios.\nEquiposService, DiagnosticosService y ReparacionesService emiten eventos; MovimientosListener utiliza MovimientosService. IndicadoresRepository accede a DataSource y no hereda de RepositorioBase.', 35, 2130, 1665, 140)


class Sequence:
    def __init__(self, name, participants, precondition):
        self.p = Page(name, max(1600, len(participants)*245+80), 1800)
        self.x = [160+i*245 for i in range(len(participants))]
        self.p.note(precondition, 35, 80, self.p.width-70, 72)
        for i, label in enumerate(participants):
            self.p.cell(label, self.x[i]-110, 180, 220, 65, 'fillColor=#dae8fc;strokeColor=#6c8ebf;')
            self.p.edge(points=[(self.x[i], 245), (self.x[i], 1645)], style='dashed=1;strokeColor=#94a3b8;')
        self.y = 295
        self.n = 0

    def msg(self, src, dst, value, ret=False):
        self.n += 1
        columns = max(27, min(65, abs(dst-src)*30))
        value = '\n'.join(textwrap.fill(line, width=columns, break_long_words=False, break_on_hyphens=False) for line in value.split('\n'))
        if src == dst:
            self.p.cell(f'{self.n}. {value}', self.x[src]+10, self.y-23, 210, 48, 'rounded=1;fillColor=#f1f5f9;strokeColor=#94a3b8;fontSize=12;align=left;spacing=6;')
        else:
            self.p.edge(label=f'{self.n}. {value}', points=[(self.x[src],self.y),(self.x[dst],self.y)],
                        style=f'endArrow={"open" if ret else "block"};endFill={0 if ret else 1};dashed={1 if ret else 0};verticalAlign=bottom;spacingBottom=5;')
        self.y += 70

    def note(self, value):
        self.p.note(value, 35, self.y-12, self.p.width-70, 58)
        self.y += 92


s = Sequence('08 - Secuencia - Inicio de sesión', ['Usuario / interfaz', 'API: guards + pipes', 'AuthController', 'AuthService', 'UsuariosRepository', 'PasswordService', 'Sesión + CSRF', 'PostgreSQL'],
             'Sin sesión previa. El cliente obtiene un token CSRF si no lo tiene. Los retornos discontinuos resumen respuestas; los participantes agrupados representan componentes del código indicados en el Markdown.')
s.msg(0,6,'GET /api/auth/csrf\n(vía la API)')
s.msg(6,0,'Cookie CSRF y token',True)
s.msg(0,1,'POST /api/auth/login\ncorreo, contraseña y token')
s.msg(1,2,'Validar frecuencia, CSRF y LoginDto')
s.msg(2,3,'autenticar(dto)')
s.msg(3,4,'buscarConPasswordPorEmail(email normalizado)')
s.msg(4,7,'SELECT usuario con passwordHash')
s.msg(7,3,'Usuario o ausencia (vía repositorio)',True)
s.msg(3,5,'verificar(hash real o simulado, password)')
s.msg(5,3,'Resultado de comprobación',True)
s.note('Alternativa de rechazo: credenciales incorrectas o cuenta desactivada → HTTP 401. Sin token válido → 403; límite excedido → 429; DTO inválido → 400. No se crea sesión autenticada.')
s.msg(3,2,'UsuarioSesion de una cuenta activa',True)
s.msg(2,6,'regenerate() y save(); generar token CSRF')
s.msg(6,7,'Guardar sesión con connect-pg-simple')
s.msg(2,0,'HTTP 200: usuario, token y cookie de sesión',True)
s.msg(0,0,'Actualizar caché y navegar al panel')

s = Sequence('09 - Secuencia - Registro de equipo', ['Interfaz / cliente HTTP', 'API: guards + controller', 'EquiposService', 'Repositorios de apoyo', 'EquiposRepository', 'EquipoFactory', 'Eventos + historial', 'PostgreSQL'],
             'Sesión activa de capturista, responsable o administrador. Repositorios de apoyo: categorías, usuarios y campañas. Eventos + historial: EventEmitter2 → MovimientosListener → MovimientosService → MovimientosRepository.')
s.msg(0,1,'POST /api/equipos\nDTO + cookie + token CSRF')
s.msg(1,2,'Validar acceso y DTO; registrar(dto, usuarioId)')
s.msg(2,4,'Comprobar número de serie duplicado')
s.msg(2,3,'Obtener categoría, usuario y campaña opcional')
s.note('Si la fecha es futura, falta un dato del perfil, la serie está duplicada o la campaña no acepta la categoría, se rechaza el registro con 400 o 409 según el caso.')
s.msg(2,4,'transaccion(trabajo)')
s.msg(4,7,'BEGIN; bloqueo asesor transaccional (360)')
s.msg(2,4,'siguienteFolio(fecha, manager)')
s.msg(4,7,'Consultar último folio del año')
s.msg(2,5,'crear(datos + folio + categoría)')
s.msg(5,2,'Equipo validado y configurado por perfil',True)
s.msg(2,4,'guardar(equipo, manager)')
s.msg(4,7,'INSERT equipo; COMMIT')
s.msg(2,6,'emitAsync(equipo.registrado)')
s.msg(6,7,'INSERT movimiento de ingreso')
s.msg(2,4,'detalle(id): cargar relaciones e historial')
s.msg(1,0,'HTTP 201: detalle del equipo',True)
s.note('El historial se escribe después del COMMIT del equipo. Un fallo del listener puede devolver un error aunque el equipo ya se haya guardado; no hay una transacción única para ambos registros.')

s = Sequence('10 - Secuencia - Diagnóstico', ['Interfaz / cliente HTTP', 'API: guards + controller', 'DiagnosticosService', 'Repositorios', 'ClasificadorDestino', 'Equipo', 'Eventos + historial', 'PostgreSQL'],
             'Sesión activa de responsable o administrador. Repositorios agrupa EquiposRepository, DiagnosticosRepository y UsuariosRepository. El diagrama describe el guardado; simular no persiste registros.')
s.msg(0,1,'POST /api/equipos/:id/diagnosticos\nDTO + cookie + token CSRF')
s.msg(1,2,'Validar acceso y DTO; registrar(id, dto, usuarioId)')
s.msg(2,3,'transaccion(); obtenerParaActualizar(id, manager)')
s.msg(3,7,'BEGIN; SELECT equipo FOR UPDATE\ny cargar categoría')
s.msg(2,4,'clasificar(contexto con categoría)')
s.msg(4,4,'Primera estrategia aplicable\nsegún prioridad')
s.msg(4,2,'Condición, justificación y estrategia',True)
s.msg(2,5,'aplicarDiagnostico(condicion)')
s.note('Si el equipo está en reparación o tiene destino final, la regla de negocio rechaza el cambio y se revierte la transacción. La clasificación da prioridad a riesgos y daños graves.')
s.msg(2,3,'guardar equipo y crear/guardar diagnóstico\ncon el responsable')
s.msg(3,7,'UPDATE equipo; INSERT diagnóstico; COMMIT')
s.msg(2,6,'emitAsync(equipo.diagnosticado)')
s.msg(6,7,'INSERT movimiento de diagnóstico')
s.msg(1,0,'HTTP 201: diagnóstico guardado',True)
s.msg(0,0,'Invalidar consultas y actualizar la vista')
s.note('El movimiento se genera después del COMMIT del diagnóstico. La vista previa POST .../diagnosticos/simular consulta la categoría y aplica el clasificador; devuelve HTTP 200 sin guardar diagnóstico ni movimiento.')

s = Sequence('11 - Secuencia - Movimiento', ['Interfaz / cliente HTTP', 'API: guards + controller', 'MovimientosService', 'EquiposRepository', 'GestoresRepository', 'Equipo', 'MovimientosRepository', 'PostgreSQL'],
             'Ejemplo: ENTREGA_GESTOR. Sesión de responsable o administrador; equipo disponible, condición GESTION_ESPECIALIZADA, gestor verificado y referencia de evidencia. Borrado confirmado cuando aplica.')
s.msg(0,1,'POST /api/equipos/:id/movimientos\nDTO + cookie + token CSRF')
s.msg(1,2,'Validar acceso y DTO; registrarDecision(...)')
s.msg(2,6,'transaccion(trabajo)')
s.msg(6,7,'BEGIN')
s.msg(2,3,'obtenerParaActualizar(id, manager)')
s.msg(3,7,'SELECT equipo FOR UPDATE\ny cargar categoría')
s.msg(2,4,'obtenerPorId(gestorId, ..., manager)')
s.msg(4,7,'SELECT gestor')
s.msg(2,5,'entregarAGestor(gestor, datosBorrados)')
s.note('Rechazo de negocio → rollback: equipo no disponible, condición incorrecta, gestor sin verificación o borrado requerido sin confirmar. El DTO exige gestorId y evidencia para esta decisión.')
s.msg(5,2,'Estado ENTREGADO_GESTOR y destinoActual',True)
s.msg(2,3,'guardar(equipo, manager)')
s.msg(3,7,'UPDATE equipo')
s.msg(2,6,'registrar movimiento con manager\ny responsable (UsuariosRepository)')
s.msg(6,7,'INSERT movimiento; COMMIT')
s.msg(1,0,'HTTP 201: movimiento guardado',True)
s.note('El cambio de destino y el movimiento comparten transacción. Las decisiones ENVIO_REPARACION, REASIGNACION y CONSERVACION usan el mismo servicio con métodos y validaciones específicos de Equipo; no necesitan emitir un evento adicional para este historial.')

p = Page('12 - Estados - Ciclo de vida del equipo', 1850, 1720)
p.note('El estado real es el par (estadoDestino, condicion). «Registrado», «diagnosticado» y «reparado» describen hitos, no valores adicionales de los enums. Los nodos de condición de esta vista agrupan EN_INVENTARIO o CONSERVADO.', 35, 80, 1780, 75)
start = p.cell('', 60, 220, 30, 30, 'ellipse;fillColor=#111827;strokeColor=#111827;')
choice = p.cell('', 245, 205, 60, 60, 'rhombus;fillColor=#ffffff;strokeColor=#334155;')
pending = p.cell('Registrado / pendiente\nEN_INVENTARIO + PENDIENTE', 445, 190, 410, 90, 'rounded=1;arcSize=20;fillColor=#dae8fc;strokeColor=#6c8ebf;')
evaluate = p.cell('Diagnosticar disponible\nStrategy clasifica la condición', 445, 390, 410, 90, 'rounded=1;arcSize=20;fillColor=#f5f5f5;strokeColor=#666666;')
functional = p.cell('Funcional\nDisponible + FUNCIONAL', 40, 650, 410, 90, 'rounded=1;arcSize=20;fillColor=#e2f0d9;strokeColor=#548235;')
repairable = p.cell('Reparable\nDisponible + REPARABLE', 555, 650, 410, 90, 'rounded=1;arcSize=20;fillColor=#fff2cc;strokeColor=#d6b656;')
special = p.cell('Gestión especializada\nDisponible + GESTION_ESPECIALIZADA', 1355, 650, 440, 90, 'rounded=1;arcSize=20;fillColor=#f8cecc;strokeColor=#b85450;')
repair = p.cell('En reparación\nEN_REPARACION + REPARABLE', 555, 850, 410, 90, 'rounded=1;arcSize=20;fillColor=#fff2cc;strokeColor=#d6b656;')
reused = p.cell('Reparado exitosamente / reutilizable\nEN_INVENTARIO + REUTILIZABLE', 40, 1050, 440, 90, 'rounded=1;arcSize=20;fillColor=#e2f0d9;strokeColor=#548235;')
assigned = p.cell('Reutilizado mediante reasignación\nREASIGNADO\nDestino final documentado', 40, 1240, 440, 100, 'rounded=1;arcSize=20;fillColor=#d5e8d4;strokeColor=#548235;strokeWidth=3;')
delivered = p.cell('Entrega documentada a gestor\nENTREGADO_GESTOR\nDestino final documentado', 1355, 1240, 440, 100, 'rounded=1;arcSize=20;fillColor=#f8cecc;strokeColor=#b85450;strokeWidth=3;')
for a,b,label in [
    (start,choice,'registrar'),
    (choice,pending,'[perfil distinto de energía]'),
    (choice,special,'[perfil energía]'),
    (pending,evaluate,'registrar diagnóstico'),
    (evaluate,functional,'[enciende y costo = 0; sin riesgos]'),
    (evaluate,repairable,'[sin riesgos; costo ≤ 50 % reposición]'),
    (evaluate,special,'[riesgo, daño grave o reparación no rentable]'),
    (repairable,repair,'enviarAReparacion()'),
    (repair,reused,'registrarResultadoReparacion(true)'),
    (repair,special,'registrarResultadoReparacion(false)\n→ EN_INVENTARIO'),
    (functional,assigned,'reasignar(destino, borrado)\n[evidencia y borrado si aplica]'),
    (reused,assigned,'reasignar(destino, borrado)\n[evidencia y borrado si aplica]'),
    (special,delivered,'entregarAGestor(...)\n[gestor verificado, evidencia y borrado si aplica]'),
]:
    route, anchors = None, ''
    if a == choice and b == special:
        route = [(275,175),(1820,175),(1820,695)]
        anchors = 'exitX=0.5;exitY=0;entryX=1;entryY=0.5;'
    elif a == evaluate and b == special:
        route = [(1000,435),(1000,550),(1575,550)]
        anchors = 'exitX=1;exitY=0.5;entryX=0.5;entryY=0;'
        label = '[riesgo, daño grave\no reparación no rentable]'
    elif a == functional and b == assigned:
        route = [(500,695),(500,1290)]
        anchors = 'exitX=1;exitY=0.5;entryX=1;entryY=0.5;'
        label = 'reasignar\n[evidencia y borrado si aplica]'
    elif a == repair and b == reused:
        route = [(515,913),(515,1000),(260,1000)]
        anchors = 'exitX=0;exitY=0.7;entryX=0.5;entryY=0;'
        label = 'reparación exitosa'
    p.edge(a,b,label,'endArrow=block;endFill=1;edgeStyle=orthogonalEdgeStyle;rounded=0;'+anchors, route=route)
p.note('Disponible = EN_INVENTARIO o CONSERVADO.\nconservar(ubicacion): disponible + condición distinta de GESTION_ESPECIALIZADA → CONSERVADO; preserva la condición.\nUn equipo conservado puede volver a diagnosticarse o seguir a reparación / reasignación / entrega si cumple las reglas.\nDiagnosticar no cambia estadoDestino; puede repetirse mientras no esté en reparación ni tenga destino final.\nLa reparación devuelve EN_INVENTARIO y actualiza la condición.\nREASIGNADO y ENTREGADO_GESTOR no admiten nuevos cambios. La entrega no prueba reciclaje efectivo.', 35, 1440, 1780, 210)
p.note('Diagrama de estados resumido con condiciones agrupadas. Todas las transiciones requieren autorización en el servidor; las guardas se completan con las reglas descritas en el Markdown.', 555, 1225, 740, 120)

p = Page('13 - Estados - Destinos implementados', 1850, 1770)
p.note('Máquina de estados de estadoDestino según Equipo. La condición técnica aparece en guardas y efectos. Notación: evento [guarda] / efecto. Disponible significa EN_INVENTARIO o CONSERVADO.', 35, 80, 1780, 75)
initial = p.cell('', 70, 270, 30, 30, 'ellipse;fillColor=#111827;strokeColor=#111827;')
inventory = p.cell('EN_INVENTARIO', 350, 235, 420, 120, 'rounded=1;arcSize=20;fillColor=#dae8fc;strokeColor=#6c8ebf;')
retained = p.cell('CONSERVADO', 350, 785, 420, 120, 'rounded=1;arcSize=20;fillColor=#dae8fc;strokeColor=#6c8ebf;')
repairing = p.cell('EN_REPARACION', 1290, 235, 420, 120, 'rounded=1;arcSize=20;fillColor=#fff2cc;strokeColor=#d6b656;')
assigned = p.cell('REASIGNADO\nDestino final documentado', 1290, 590, 420, 120, 'rounded=1;arcSize=20;fillColor=#e2f0d9;strokeColor=#548235;strokeWidth=3;')
delivered = p.cell('ENTREGADO_GESTOR\nDestino final documentado', 1290, 965, 420, 120, 'rounded=1;arcSize=20;fillColor=#f8cecc;strokeColor=#b85450;strokeWidth=3;')
p.edge(initial, inventory, 'registrar / condición según perfil', 'endArrow=block;endFill=1;')
transitions = [
    (inventory,retained,'conservar(ubicacion)\n[condición ≠ GESTION_ESPECIALIZADA]'),
    (inventory,repairing,'enviarAReparacion()\n[condición = REPARABLE]'),
    (retained,repairing,'enviarAReparacion()\n[condición = REPARABLE]'),
    (repairing,inventory,'registrarResultadoReparacion(ok)\n/ condición = ok ? REUTILIZABLE : GESTION_ESPECIALIZADA'),
    (inventory,assigned,'reasignar(...) [funcional o reutilizable; borrado si aplica]'),
    (retained,assigned,'reasignar(...) [funcional o reutilizable; borrado si aplica]'),
    (inventory,delivered,'entregarAGestor(...) [gestión especializada; gestor verificado; borrado si aplica]'),
    (retained,delivered,'entregarAGestor(...) [gestión especializada; gestor verificado; borrado si aplica]'),
]
routes = [
    ('exitX=0.3;exitY=1;entryX=0.3;entryY=0;', None),
    ('exitX=1;exitY=0.2;entryX=0;entryY=0.2;', None),
    ('exitX=1;exitY=0.2;entryX=0;entryY=0.7;', [(1230,809),(1230,319)]),
    ('exitX=0.3;exitY=1;entryX=0.5;entryY=1;', [(1416,435),(560,435)]),
    ('exitX=1;exitY=0.5;entryX=0;entryY=0.25;', [(870,295),(870,620)]),
    ('exitX=1;exitY=0.5;entryX=0;entryY=0.75;', [(940,845),(940,680)]),
    ('exitX=1;exitY=0.8;entryX=0;entryY=0.25;', [(1020,331),(1020,995)]),
    ('exitX=1;exitY=0.8;entryX=0;entryY=0.75;', [(1100,881),(1100,1055)]),
]
for index, ((a,b,label), (anchors, route)) in enumerate(zip(transitions,routes), 1):
    p.edge(a,b,f'T{index}','endArrow=block;endFill=1;edgeStyle=orthogonalEdgeStyle;'+anchors, route=route)
p.note('Transiciones internas de EN_INVENTARIO y CONSERVADO:\n• aplicarDiagnostico(c) / condición := c; se conserva estadoDestino.\n• Un diagnóstico puede volver a clasificar un equipo disponible.\nCONSERVADO también permite conservar(ubicacion) para actualizar el lugar si la condición sigue siendo admisible.\nLa interfaz oculta esta última acción cuando ya está conservado, pero el método de dominio la admite.', 35, 1040, 800, 170)
p.note('Reglas adicionales:\n• REASIGNADO y ENTREGADO_GESTOR no tienen transiciones salientes.\n• El DTO exige evidencia para reasignar y entregar; la entidad valida condición y borrado.\n• Un equipo conservado puede quedar en gestión especializada después de un nuevo diagnóstico y entonces entregarse al gestor.\n• No existe una operación específica de retorno de CONSERVADO a EN_INVENTARIO; se vuelve al inventario después de registrar una reparación.', 35, 1230, 1780, 145)
p.note('Leyenda de transiciones:\nT1: conservar(ubicacion) [condición distinta de GESTION_ESPECIALIZADA].\nT2 / T3: enviarAReparacion() [condición = REPARABLE].\nT4: registrarResultadoReparacion(ok) / condición := REUTILIZABLE si ok; GESTION_ESPECIALIZADA si falla.\nT5 / T6: reasignar(...) [condición FUNCIONAL o REUTILIZABLE; evidencia y borrado si aplica].\nT7 / T8: entregarAGestor(...) [GESTION_ESPECIALIZADA; gestor verificado; evidencia y borrado si aplica].', 35, 1410, 1780, 250)

OUT.mkdir(parents=True, exist_ok=True)
target = OUT / 'diseno-uml-ewaste360.drawio'
ET.indent(DOC, space='  ')
ET.ElementTree(DOC).write(target, encoding='utf-8', xml_declaration=True)

# Verificación estructural: identificadores únicos y referencias locales válidas.
tree = ET.parse(target)
for diagram in tree.findall('diagram'):
    cells = diagram.findall('.//mxCell')
    ids = [c.get('id') for c in cells]
    assert len(ids) == len(set(ids)), diagram.get('name')
    for cell in cells:
        for key in ('parent', 'source', 'target'):
            if cell.get(key):
                assert cell.get(key) in ids, (diagram.get('name'), key, cell.get(key))
print(f'{target.name}: {len(DOC)} páginas; XML, IDs y referencias válidos.')
