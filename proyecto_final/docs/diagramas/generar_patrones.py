"""Genera cuatro diagramas editables en draw.io. No modifica los diagramas UML previos."""
from pathlib import Path
import xml.etree.ElementTree as ET

OUT = Path(__file__).resolve().parent
doc = ET.Element('mxfile', host='app.diagrams.net', type='device')


class Diagrama:
    def __init__(self, nombre):
        d = ET.SubElement(doc, 'diagram', id=str(len(doc) + 1), name=nombre)
        m = ET.SubElement(d, 'mxGraphModel', page='1', pageWidth='1440', pageHeight='950', grid='1', gridSize='10')
        self.root = ET.SubElement(m, 'root')
        ET.SubElement(self.root, 'mxCell', id='0')
        ET.SubElement(self.root, 'mxCell', id='1', parent='0')
        self.n = 1
        self.box(nombre, 35, 20, 1350, 50, 'text;fillColor=none;strokeColor=none;fontSize=25;fontStyle=1;align=left;')

    def box(self, texto, x, y, w=360, h=150, estilo=''):
        self.n += 1
        c = ET.SubElement(self.root, 'mxCell', id=str(self.n), value=texto, vertex='1', parent='1',
                          style='rounded=0;whiteSpace=wrap;html=0;fontFamily=Helvetica;fontSize=16;spacing=14;fillColor=#dae8fc;strokeColor=#6c8ebf;' + estilo)
        ET.SubElement(c, 'mxGeometry', x=str(x), y=str(y), width=str(w), height=str(h), attrib={'as': 'geometry'})
        return c.get('id')

    def link(self, origen, destino, texto='', herencia=False, estilo='', ruta=None):
        self.n += 1
        c = ET.SubElement(self.root, 'mxCell', id=str(self.n), value=texto, edge='1', parent='1', source=origen, target=destino,
                          style='edgeStyle=orthogonalEdgeStyle;rounded=0;html=0;fontFamily=Helvetica;fontSize=14;labelBackgroundColor=#ffffff;' +
                          ('endArrow=block;endFill=0;' if herencia else 'dashed=1;endArrow=open;') + estilo)
        g = ET.SubElement(c, 'mxGeometry', relative='1', attrib={'as': 'geometry'})
        if ruta:
            puntos = ET.SubElement(g, 'Array', attrib={'as': 'points'})
            for x, y in ruta:
                ET.SubElement(puntos, 'mxPoint', x=str(x), y=str(y))

    def nota(self, texto, y=780):
        self.box(texto, 40, y, 1340, 120, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;align=left;')


p = Diagrama('01 - Factory: creación de equipos por perfil')
s = p.box('EquiposService\n────────────────────\nregistrar(dto, usuarioId)', 40, 140)
f = p.box('EquipoFactory\n────────────────────\n- perfiles: Record<TipoCategoria, PerfilEquipo>\n+ perfilPara(tipo)\n+ crear(p): Equipo', 510, 120, 430, 190)
e = p.box('Equipo\n────────────────────\ncondicion / estadoDestino\nrequiereBorradoDatos / datosBorrados', 1030, 140, 350)
b = p.box('«abstract» PerfilEquipo\n────────────────────\nvalidar(datos)\ncondicionInicial()\nrequiereBorradoDatos()\nrecomendacionesEntrega()', 520, 400, 410, 220)
c = p.box('PerfilComputo / PerfilMovil\n────────────────────\nBorrado de datos requerido\nCómputo exige número de serie', 40, 650, 390, 110)
g = p.box('PerfilEnergia\n────────────────────\nCondición inicial:\nGESTION_ESPECIALIZADA', 520, 650, 410, 110)
o = p.box('PerfilPeriferico / PerfilCableado\nPerfilGenerico\n────────────────────\nValores y recomendaciones por tipo', 1020, 650, 360, 110)
p.link(s, f, 'usa')
p.link(f, e, '«create»')
p.link(f, b, 'selecciona y consulta')
for n, x in ((c, 235), (g, 725), (o, 1200)):
    p.link(n, b, herencia=True, estilo='exitX=0.5;exitY=0;entryX=0.5;entryY=1;', ruta=[(x, 635), (725, 635)])
p.nota('Fábrica simple con perfiles polimórficos: todos los productos son Equipo. Las cajas con varios nombres agrupan clases concretas. La fábrica construye y valida; EquiposRepository persiste. Toda creación inicia con destino EN_INVENTARIO.', 800)

p = Diagrama('02 - Strategy: clasificación del diagnóstico')
s = p.box('DiagnosticosService\n────────────────────\nsimular() / registrar()', 40, 130, 380)
c = p.box('ClasificadorDestino\n────────────────────\n- estrategias: EstrategiaClasificacion[]\n+ clasificar(ctx)', 530, 130, 390)
b = p.box('«abstract» EstrategiaClasificacion\n────────────────────\nprioridad / nombre\naplica(ctx)\nclasificar(ctx)\n# condicion() / justificar(ctx)', 530, 390, 390, 210)
configs = [('GestionEspecializadaStrategy\nPrioridad 1\nPeligrosidad o daño estructural', 40), ('ReutilizacionDirectaStrategy\nPrioridad 2\nEnciende y costo = 0', 390), ('ReparacionStrategy\nPrioridad 3\nCosto ≤ 50% de reposición', 740), ('ReparacionNoRentableStrategy\nPrioridad 99\nRespaldo: aplica siempre', 1090)]
p.link(s, c, 'usa')
p.link(c, b, 'ordena y selecciona')
for txt, x in configs:
    n = p.box(txt, x, 660, 310, 110)
    p.link(n, b, herencia=True, estilo='exitX=0.5;exitY=0;entryX=0.5;entryY=1;', ruta=[(x + 155, 630), (725, 630)])
r = p.box('ResultadoClasificacion\n────────────────────\ncondicion\njustificacion\nestrategia', 1040, 150, 350, 190)
p.link(c, r, 'devuelve')
p.nota('DiagnosticosModule registra las cuatro estrategias mediante ESTRATEGIAS_CLASIFICACION. Se elige solo la primera aplicable. La clasificación recomienda una condición técnica; no ejecuta una reparación ni una entrega a gestor.', 800)

p = Diagrama('03 - Observer: trazabilidad mediante eventos')
s = p.box('Emisores\n────────────────────\nEquiposService\nDiagnosticosService\nReparacionesService', 40, 140, 380, 210)
e = p.box('EventEmitter2\n────────────────────\nemitAsync(nombre, evento)\nwildcard: true', 530, 155, 380, 175)
l = p.box('MovimientosListener\n────────────────────\n@OnEvent("equipo.*")\nalOcurrirEventoDeEquipo(evento)', 1010, 155, 380, 175)
v = p.box('«abstract» EventoEquipo\n────────────────────\nequipoId / responsableId\nestadoAnterior / estadoNuevo\nnombre / tipoMovimiento\ndescribir()', 40, 450, 380, 220)
cc = p.box('EquipoRegistradoEvent\nEquipoDiagnosticadoEvent\nEquipoReparadoEvent', 40, 710, 380, 100)
ms = p.box('MovimientosService\n────────────────────\nregistrarEvento(evento)', 1010, 440, 380, 140)
mr = p.box('MovimientosRepository\n────────────────────\nguardar(movimiento)', 1010, 670, 380, 120)
p.link(s, e, 'publica')
p.link(e, l, 'notifica')
p.link(s, v, 'construye', estilo='exitX=0.5;exitY=1;entryX=0.5;entryY=0;')
p.link(cc, v, herencia=True)
p.link(l, ms, 'delega')
p.link(ms, mr, 'persiste historial')
p.nota('Observer mediante publicación/suscripción en el mismo proceso. emitAsync espera al listener. La transacción principal ya terminó: un fallo del historial puede propagarse sin revertirla. Las decisiones de destino se guardan junto con su movimiento en una sola transacción.', 830)

p = Diagrama('04 - Repository: acceso a datos encapsulado')
s = p.box('EquiposService\n────────────────────\nregistrar() / buscar() / detalle()', 40, 150, 380, 160)
r = p.box('EquiposRepository\n────────────────────\nbuscar(filtros) / detalle(id)\nsiguienteFolio()\nobtenerParaActualizar()', 530, 130, 390, 200)
b = p.box('«abstract» RepositorioBase<T>\n────────────────────\n# repo: Repository<T>\n# dataSource: DataSource\ncrear() / buscarPorId() / obtenerPorId()\nguardar() / transaccion() / # con()', 530, 470, 440, 230)
t = p.box('TypeORM\n────────────────────\nDataSource\nRepository<T>\nEntityManager', 1060, 480, 320, 200)
d = p.box('PostgreSQL\n────────────────────\nTablas / restricciones / transacciones', 1060, 150, 320, 160, 'shape=cylinder3;')
p.link(s, r, 'usa')
p.link(r, b, herencia=True)
p.link(b, t, 'delega persistencia')
p.link(t, d, 'consulta / guarda')
p.nota('Se muestra EquiposRepository como ejemplo. Otros repositorios heredan la misma base; IndicadoresRepository usa DataSource directamente. La separación es práctica: EquiposService ejecuta un bloqueo SQL de folios dentro de una transacción y algunos servicios coordinan EntityManager.', 790)

ET.indent(doc, space='  ')
destino = OUT / 'patrones-diseno-ewaste360.drawio'
ET.ElementTree(doc).write(destino, encoding='utf-8', xml_declaration=True)
print(destino)
