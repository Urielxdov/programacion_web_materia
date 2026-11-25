"""Genera el mapa de rutas implementadas; no representa un prototipo previo al desarrollo."""
from pathlib import Path
import xml.etree.ElementTree as ET

OUT = Path(__file__).resolve().parent
doc = ET.Element('mxfile', host='app.diagrams.net', type='device')


class Pagina:
    def __init__(self, nombre):
        self.n = 1
        self.ids = {}
        d = ET.SubElement(doc, 'diagram', id=str(len(doc)+1), name=nombre)
        model = ET.SubElement(d, 'mxGraphModel', page='1', pageWidth='1800', pageHeight='1250', grid='1', gridSize='10')
        self.root = ET.SubElement(model, 'root')
        ET.SubElement(self.root, 'mxCell', id='0')
        ET.SubElement(self.root, 'mxCell', id='1', parent='0')
        self.caja('titulo', nombre, 35, 20, 1700, 55, 'text;fillColor=none;strokeColor=none;fontSize=26;fontStyle=1;')

    def caja(self, clave, texto, x, y, w=475, h=110, estilo=''):
        self.n += 1
        c = ET.SubElement(self.root, 'mxCell', id=str(self.n), value=texto, vertex='1', parent='1', style='rounded=1;whiteSpace=wrap;html=0;fontFamily=Helvetica;fontSize=18;fillColor=#dae8fc;strokeColor=#6c8ebf;spacing=12;' + estilo)
        ET.SubElement(c, 'mxGeometry', x=str(x), y=str(y), width=str(w), height=str(h), attrib={'as':'geometry'})
        self.ids[clave] = c.get('id')

    def unir(self, origen, destino, etiqueta='', estilo='', puntos=None):
        self.n += 1
        e = ET.SubElement(self.root, 'mxCell', id=str(self.n), value=etiqueta, edge='1', parent='1', source=self.ids[origen], target=self.ids[destino], style='edgeStyle=orthogonalEdgeStyle;endArrow=open;fontFamily=Helvetica;fontSize=14;labelBackgroundColor=#ffffff;' + estilo)
        g = ET.SubElement(e, 'mxGeometry', relative='1', attrib={'as':'geometry'})
        if puntos:
            arr = ET.SubElement(g, 'Array', attrib={'as':'points'})
            for x, y in puntos:
                ET.SubElement(arr, 'mxPoint', x=str(x), y=str(y))


p = Pagina('01 - Navegación pública y entrada al panel')
p.caja('publico', 'Sitio público\nPublicLayout', 650, 120)
for clave, texto, x, y in [
    ('inicio', 'Inicio\n/', 40, 360),
    ('guia', 'Guía ambiental\n/guia', 650, 360),
    ('campanas', 'Campañas públicas\n/campanas', 1260, 360),
    ('detalle', 'Detalle de campaña\n/campanas/:id\nFormulario de participación en esta pantalla', 1260, 610),
    ('entrar', 'Inicio de sesión\n/entrar', 40, 610),
    ('panel', 'Panel de personal\n/panel\nRequiere sesión válida', 40, 860),
    ('404', 'Ruta pública no encontrada\n* → NoEncontradaPage', 650, 860),
]:
    p.caja(clave, texto, x, y, h=135)
for clave in ('inicio', 'guia', 'campanas'):
    p.unir('publico', clave, 'ruta pública')
p.unir('publico', 'entrar', 'ruta pública', 'exitX=0;exitY=0.8;entryX=1;entryY=0.5;', [(600, 208), (600, 677.5)])
p.unir('campanas', 'detalle', 'abrir campaña')
p.unir('entrar', 'panel', 'login correcto')
p.caja('nota', 'Mapa elaborado a partir de router.tsx al documentar el proyecto. Las conexiones desde PublicLayout agrupan rutas; no representan todos los enlaces de la interfaz.\nSi se intenta abrir una ruta del panel sin sesión, RutaProtegida redirige a /entrar?volver=<ruta>. El login admite como retorno rutas que comienzan con /panel.\nLa participación es un formulario dentro del detalle de campaña; no existe una ruta independiente para enviarlo. El comodín muestra la página 404.', 40, 1060, 1695, 150, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;align=left;fontSize=16;')

p = Pagina('02 - Navegación privada por permisos')
p.caja('panel', 'Resumen del panel\n/panel\nPrivateLayout + RutaProtegida', 650, 110, h=135)
grupos = [('todos', 'Todos los roles autenticados\nCAPTURISTA / RESPONSABLE / ADMIN', 40, '#e2f0d9'),
          ('gestion', 'Gestión ambiental\nRESPONSABLE / ADMIN', 650, '#dae8fc'),
          ('admin', 'Administración\nADMIN', 1260, '#f8cecc')]
for clave, texto, x, color in grupos:
    p.caja(clave, texto, x, 360, estilo=f'fillColor={color};')
    p.unir('panel', clave, 'agrupa rutas por permiso')
p.caja('equipos', 'Inventario\n/panel/equipos', 40, 565)
p.caja('nuevo', 'Registro de equipo\n/panel/equipos/nuevo', 40, 755)
p.caja('detalle', 'Detalle e historial: /panel/equipos/:id\nEdición: /panel/equipos/:id/editar', 40, 945, h=130)
p.unir('todos', 'equipos')
p.unir('equipos', 'nuevo')
p.unir('equipos', 'detalle', estilo='exitX=0;exitY=0.5;entryX=0;entryY=0.5;', puntos=[(20, 620), (20, 1010)])
p.caja('campanas', 'Campañas\n/panel/campanas\n/panel/campanas/nueva\n/panel/campanas/:id', 650, 565, h=170)
p.caja('reportes', 'Reportes\n/panel/reportes', 650, 805)
p.caja('gestores', 'Gestores\n/panel/gestores', 650, 995)
p.unir('gestion', 'campanas')
p.unir('gestion', 'reportes', estilo='exitX=0;exitY=0.5;entryX=0;entryY=0.5;', puntos=[(625, 415), (625, 860)])
p.unir('gestion', 'gestores', estilo='exitX=0;exitY=0.5;entryX=0;entryY=0.5;', puntos=[(595, 415), (595, 1050)])
p.caja('usuarios', 'Usuarios\n/panel/usuarios', 1260, 565)
p.caja('categorias', 'Categorías\n/panel/categorias', 1260, 805)
p.unir('admin', 'usuarios')
p.unir('admin', 'categorias', estilo='exitX=0;exitY=0.5;entryX=0;entryY=0.5;', puntos=[(1225, 415), (1225, 860)])
p.caja('nota', 'Los grupos son organizativos: ADMIN también accede al inventario y a gestión ambiental. Las acciones de diagnóstico, reparación y destino viven en el detalle del equipo, no en rutas React propias.\nLa API valida los permisos de cada operación. Un rol incorrecto muestra acceso denegado en RutaProtegida; los comodines del panel muestran NoEncontradaPage.', 40, 1130, 1695, 100, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;align=left;fontSize=16;')

ET.indent(doc, space='  ')
destino = OUT / 'mapa-navegacion-ewaste360.drawio'
ET.ElementTree(doc).write(destino, encoding='utf-8', xml_declaration=True)
print(destino)
