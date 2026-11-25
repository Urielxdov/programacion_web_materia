"""Documenta la migración inicial y genera un ER editable; no conecta con PostgreSQL."""
from pathlib import Path
import re
import xml.etree.ElementTree as ET

OUT = Path(__file__).resolve().parent
PROYECTO = OUT.parent.parent
MIG = PROYECTO / 'backend/src/database/migrations/1795488747254-Inicial.ts'
sql = MIG.read_text(encoding='utf-8').split('public async down', 1)[0]
tablas = {}
for tabla, cuerpo in re.findall(r'CREATE TABLE "(\w+)" \((.*?)\)`', sql):
    campos = []
    for parte in re.split(r', (?="|CONSTRAINT)', cuerpo):
        m = re.match(r'"(\w+)" (.*)', parte)
        if not m:
            continue
        nombre, definicion = m.groups()
        tipo = re.split(r' NOT NULL| DEFAULT ', definicion)[0]
        tipo = tipo.replace('character varying', 'varchar').replace('TIMESTAMP WITH TIME ZONE', 'timestamptz').replace('SERIAL', 'serial').replace('"public".', '').replace('"', '')
        default = re.search(r' DEFAULT (.*)', definicion)
        campos.append(dict(nombre=nombre, tipo=tipo, nn='NOT NULL' in definicion, default=default[1] if default else None))
    pk = re.search(r'PRIMARY KEY \((.*?)\)', cuerpo)[1]
    checks = re.findall(r'CHECK \((.*?)\)', cuerpo)
    tablas[tabla] = dict(campos=campos, pk=re.findall(r'"(\w+)"', pk), checks=checks)

fks = [dict(zip(('hijo', 'campo', 'padre', 'clave', 'borrado', 'actualizacion'), x)) for x in re.findall(
    r'ALTER TABLE "(\w+)" ADD CONSTRAINT "[^"]+" FOREIGN KEY \("(\w+)"\) REFERENCES "(\w+)"\("(\w+)"\) ON DELETE (.*?) ON UPDATE (.*?)`', sql)]
unicos = re.findall(r'CREATE UNIQUE INDEX "[^"]+" ON "(\w+)" \("(\w+)"\)', sql)
indices = re.findall(r'CREATE INDEX "[^"]+" ON "(\w+)" \("(\w+)"\)', sql)
enums = re.findall(r'CREATE TYPE "public"\."(\w+)" AS ENUM\((.*?)\)', sql)
assert len(tablas) == 10 and len(fks) == 17 and len(enums) == 7

descripciones = dict(line.split('|', 1) for line in '''id|Identificador autogenerado del registro.
creado_en|Fecha y hora de creación del registro.
actualizado_en|Fecha y hora de la última actualización gestionada por el ORM.
nombre|Nombre del registro.
descripcion|Descripción del registro u operación.
email|Correo de acceso normalizado por la aplicación.
password_hash|Hash de la contraseña; la entidad excluye su selección ordinaria.
rol|Rol de autorización de la cuenta.
activo|Indica si la cuenta puede operar en el sistema.
contacto|Medio de contacto, como correo o teléfono.
direccion|Domicilio del gestor.
verificado|Indica si se ha documentado la verificación del gestor.
documento_verificacion|Referencia del documento que respalda la verificación.
fecha_verificacion|Fecha y hora de la verificación del gestor.
verificado_por_id|Usuario que documentó la verificación.
peso_promedio_kg|Peso promedio por unidad para estimaciones ambientales, en kg.
valor_reposicion|Valor estimado de un equipo nuevo equivalente, en MXN.
folio|Identificador legible y único del equipo, generado por la aplicación.
marca|Marca del dispositivo.
modelo|Modelo del dispositivo.
numero_serie|Número de serie; la aplicación lo exige para cómputo.
fecha_ingreso|Fecha de ingreso declarada para el inventario.
condicion|Condición técnica actual del equipo.
estado_destino|Situación actual del equipo en su ciclo de gestión.
ubicacion|Ubicación física del equipo o de la campaña, según la tabla.
destino_actual|Área o nombre del gestor de destino documentado.
observaciones|Notas adicionales del inventario.
requiere_borrado_datos|Indica si el perfil del dispositivo exige borrado antes de la entrega.
datos_borrados|Confirmación registrada del borrado de datos.
categoria_id|Categoría a la que pertenece el equipo.
campana_id|Campaña asociada al registro.
registrado_por_id|Usuario que registró el equipo.
estado_anterior|Destino previo al movimiento; puede faltar en un ingreso.
estado_nuevo|Destino registrado tras el movimiento.
destino|Área o destino indicado en el movimiento.
evidencia|Referencia textual al documento de respaldo; no almacena un archivo binario.
equipo_id|Equipo al que pertenece el registro del historial.
responsable_id|Usuario responsable de la operación.
gestor_id|Gestor asociado a una entrega documentada.
enciende|Resultado de la comprobación de encendido.
dano_estructural|Indica daño que afecta la integridad física del dispositivo.
componentes_peligrosos|Indica presencia de componentes peligrosos evaluada en el diagnóstico.
costo_reparacion_estimado|Costo estimado de reparación, en MXN.
recomendacion|Condición recomendada por el clasificador.
estrategia_aplicada|Nombre de la estrategia que produjo la recomendación.
justificacion|Explicación de la recomendación del diagnóstico.
costo|Costo de la reparación, en MXN.
resultado|Resultado exitoso o fallido de la reparación.
ahorro_estimado|Ahorro calculado por la aplicación, en MXN; no es una columna calculada por SQL.
fecha|Fecha de la intervención de reparación.
diagnostico_id|Diagnóstico vinculado a la reparación, cuando existe.
nombre_participante|Nombre proporcionado al registrar la participación.
equipos_declarados|Cantidad de equipos que la persona declara que llevará.
comentario|Comentario opcional de la persona participante.
usuario_id|Cuenta asociada a la participación, si se proporciona.
fecha_inicio|Fecha de inicio de la campaña.
fecha_fin|Fecha de finalización de la campaña.
horario|Texto del horario de atención de la campaña.
estado|Estado operativo de la campaña.
meta_equipos|Objetivo opcional de equipos por recibir.
creada_por_id|Usuario que creó la campaña.
campanas_id|Campaña de la pareja de categorías admitidas.
categorias_id|Categoría aceptada por la campaña.'''.splitlines())
tipos = {'categorias': 'Tipo del dispositivo en el catálogo.', 'gestores': 'Tipo de organización receptora, como reciclador o centro de acopio.', 'movimientos': 'Tipo de operación registrada en el historial.'}
descripciones_especificas = {
    ('usuarios', 'nombre'): 'Nombre de la persona titular de la cuenta.',
    ('categorias', 'nombre'): 'Nombre único de la categoría de dispositivos.',
    ('gestores', 'nombre'): 'Nombre de la organización receptora.',
    ('campanas', 'nombre'): 'Nombre de la campaña de acopio.',
    ('categorias', 'descripcion'): 'Descripción opcional de la categoría.',
    ('campanas', 'descripcion'): 'Descripción y propósito de la campaña.',
    ('diagnosticos', 'descripcion'): 'Descripción de la revisión técnica realizada.',
    ('reparaciones', 'descripcion'): 'Descripción de la intervención de reparación.',
    ('movimientos', 'descripcion'): 'Descripción del ingreso, evaluación o cambio de destino registrado.',
    ('equipos', 'ubicacion'): 'Ubicación física actual del equipo en inventario.',
    ('campanas', 'ubicacion'): 'Lugar de recepción de equipos de la campaña.',
    ('equipos', 'campana_id'): 'Campaña de origen del equipo, si ingresó por una campaña.',
    ('participaciones', 'campana_id'): 'Campaña a la que corresponde la participación.',
}
orden = ['usuarios', 'categorias', 'gestores', 'equipos', 'diagnosticos', 'reparaciones', 'movimientos', 'campanas', 'participaciones', 'campana_categorias']


def restricciones(tabla, campo):
    n = campo['nombre']
    salida = ['NN' if campo['nn'] else 'Admite NULL']
    if n in tablas[tabla]['pk']:
        salida.insert(0, 'PK compuesta' if len(tablas[tabla]['pk']) > 1 else 'PK')
    if (tabla, n) in unicos:
        salida.append('UQ')
    if (tabla, n) in indices:
        salida.append('Índice')
    if campo['default'] is not None:
        salida.append('DEFAULT ' + campo['default'])
    if campo['tipo'] == 'serial':
        salida.append('Generado por secuencia')
    for fk in fks:
        if (fk['hijo'], fk['campo']) == (tabla, n):
            salida.append(f"FK → {fk['padre']}.{fk['clave']}; DELETE {fk['borrado']}; UPDATE {fk['actualizacion']}")
    for check in tablas[tabla]['checks']:
        if '"' + n + '"' in check:
            salida.append('CHECK (' + check.replace('"', '') + ')')
    return '; '.join(salida)


diccionario = ['#### 2.0. Tipos enumerados de PostgreSQL', '', '| Tipo | Valores permitidos |', '|---|---|']
for tipo, valores in enums:
    diccionario.append(f"| `{tipo}` | " + ', '.join('`' + v + '`' for v in re.findall(r"'([^']+)'", valores)) + ' |')
for i, tabla in enumerate(orden, 1):
    diccionario += ['', f'#### 2.{i}. Tabla `{tabla}`', '', '| Campo | Tipo PostgreSQL | Restricciones | Descripción |', '|---|---|---|---|']
    for campo in tablas[tabla]['campos']:
        n = campo['nombre']
        desc = tipos[tabla] if n == 'tipo' else descripciones_especificas.get((tabla, n), descripciones[n])
        diccionario.append(f"| `{n}` | `{campo['tipo']}` | {restricciones(tabla, campo)} | {desc} |")

relaciones = ['| Padre | Hijo y FK | Cardinalidad padre : hijos | ON DELETE | ON UPDATE |', '|---|---|---|---|---|']
for fk in fks:
    campo = next(c for c in tablas[fk['hijo']]['campos'] if c['nombre'] == fk['campo'])
    card = '1 : 0..*' if campo['nn'] else '0..1 : 0..*'
    relaciones.append(f"| `{fk['padre']}.{fk['clave']}` | `{fk['hijo']}.{fk['campo']}` | `{card}` | {fk['borrado']} | {fk['actualizacion']} |")

md = OUT.parent / 'documentacion-tecnica-base-datos.md'
contenido = md.read_text(encoding='utf-8')
for etiqueta, lineas in [('DICCIONARIO', diccionario), ('RELACIONES', relaciones)]:
    a = f'<!-- INICIO {etiqueta} GENERAD' + ('O' if etiqueta == 'DICCIONARIO' else 'AS') + ' -->'
    b = a.replace('INICIO', 'FIN')
    inicio, resto = contenido.split(a, 1)
    _, fin = resto.split(b, 1)
    contenido = inicio + a + '\n\n' + '\n'.join(lineas) + '\n\n' + b + fin
md.write_text(contenido, encoding='utf-8')

doc = ET.Element('mxfile', host='app.diagrams.net', type='device')


class Vista:
    def __init__(self, nombre, ancho=1950, alto=1750):
        self.n = 1
        self.ids = {}
        d = ET.SubElement(doc, 'diagram', id=str(len(doc) + 1), name=nombre)
        model = ET.SubElement(d, 'mxGraphModel', page='1', pageWidth=str(ancho), pageHeight=str(alto), grid='1', gridSize='10')
        self.root = ET.SubElement(model, 'root')
        ET.SubElement(self.root, 'mxCell', id='0')
        ET.SubElement(self.root, 'mxCell', id='1', parent='0')
        self.caja(nombre, 35, 20, ancho - 70, 50, 'text;strokeColor=none;fillColor=none;fontSize=25;fontStyle=1;')

    def caja(self, texto, x, y, w, h, estilo=''):
        self.n += 1
        c = ET.SubElement(self.root, 'mxCell', id=str(self.n), value=texto, vertex='1', parent='1', style='whiteSpace=wrap;html=0;fontFamily=Helvetica;fontSize=16;align=left;spacing=15;fillColor=#dae8fc;strokeColor=#6c8ebf;' + estilo)
        ET.SubElement(c, 'mxGeometry', x=str(x), y=str(y), width=str(w), height=str(h), attrib={'as': 'geometry'})
        return c.get('id')

    def tabla(self, nombre, x, y, w=510, solo=None):
        lineas = [nombre, '─' * min(25, int((w - 40) / 11.4))]
        for campo in tablas[nombre]['campos']:
            n = campo['nombre']
            if n in ('creado_en', 'actualizado_en') or (solo is not None and n not in solo):
                continue
            marcas = []
            if n in tablas[nombre]['pk']: marcas.append('PK')
            if any(f['hijo'] == nombre and f['campo'] == n for f in fks): marcas.append('FK')
            if (nombre, n) in unicos: marcas.append('UQ')
            tipo = campo['tipo']
            if tipo in dict(enums): tipo = 'enum'
            lineas.append(f"{','.join(marcas)} {n}{'' if campo['nn'] else '?'} : {tipo}".strip())
        h = len(lineas) * 24 + 35
        self.ids[nombre] = self.caja('\n'.join(lineas), x, y, w, h)
        return h

    def relacion(self, padre, hijo, campo, estilo='', puntos=None):
        fk = next(f for f in fks if f['padre'] == padre and f['hijo'] == hijo and f['campo'] == campo)
        c = next(c for c in tablas[hijo]['campos'] if c['nombre'] == campo)
        etiqueta = ('1' if c['nn'] else '0..1') + ' : 0..*'
        self.n += 1
        e = ET.SubElement(self.root, 'mxCell', id=str(self.n), value=etiqueta, edge='1', parent='1', source=self.ids[padre], target=self.ids[hijo], style='edgeStyle=orthogonalEdgeStyle;endArrow=none;startArrow=none;fontSize=16;fontFamily=Helvetica;labelBackgroundColor=#ffffff;' + estilo)
        g = ET.SubElement(e, 'mxGeometry', relative='1', attrib={'as': 'geometry'})
        if puntos:
            arr = ET.SubElement(g, 'Array', attrib={'as': 'points'})
            for x, y in puntos: ET.SubElement(arr, 'mxPoint', x=str(x), y=str(y))


p = Vista('01 - Inventario e historial')
p.tabla('categorias', 40, 140)
p.tabla('equipos', 700, 140)
p.tabla('gestores', 1360, 140)
p.tabla('diagnosticos', 40, 950)
p.tabla('reparaciones', 700, 950)
p.tabla('movimientos', 1360, 950)
p.relacion('categorias', 'equipos', 'categoria_id')
p.relacion('gestores', 'movimientos', 'gestor_id', 'exitX=0.8;exitY=1;entryX=0.8;entryY=0;')
p.relacion('equipos', 'diagnosticos', 'equipo_id', 'exitX=0.2;exitY=1;entryX=0.5;entryY=0;', [(802, 820), (295, 820)])
p.relacion('equipos', 'reparaciones', 'equipo_id', 'exitX=0.5;exitY=1;entryX=0.5;entryY=0;')
p.relacion('equipos', 'movimientos', 'equipo_id', 'exitX=0.8;exitY=1;entryX=0.2;entryY=0;', [(1108, 860), (1462, 860)])
p.relacion('diagnosticos', 'reparaciones', 'diagnostico_id')
p.caja('PK = clave primaria; FK = clave foránea; UQ = único; ? = admite NULL. Se omiten creado_en y actualizado_en.\nReferencias a usuarios: vista 03. Relación equipos–campañas: vista 02. Tipos enum y restricciones: diccionario del Markdown.', 40, 1540, 1830, 120, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;')

p = Vista('02 - Campañas y categorías admitidas', alto=1550)
p.tabla('campanas', 40, 140)
p.tabla('campana_categorias', 700, 140)
p.tabla('categorias', 1360, 140, solo=['id', 'nombre', 'tipo'])
p.tabla('participaciones', 40, 950)
p.tabla('equipos', 700, 950, solo=['id', 'folio', 'categoria_id', 'campana_id', 'registrado_por_id'])
p.relacion('campanas', 'campana_categorias', 'campanas_id')
p.relacion('categorias', 'campana_categorias', 'categorias_id')
p.relacion('campanas', 'participaciones', 'campana_id', 'exitX=0.3;exitY=1;entryX=0.3;entryY=0;')
p.relacion('campanas', 'equipos', 'campana_id', 'exitX=0.8;exitY=1;entryX=0.5;entryY=0;', [(448, 800), (955, 800)])
p.caja('campana_categorias resuelve la relación N:M. Su PK es (campanas_id, categorias_id).\nUna participación no se vincula a un equipo individual: equipos_declarados expresa una intención.\nLas tablas equipos y categorias se muestran aquí con campos seleccionados. Referencias de usuario: vista 03.', 40, 1330, 1830, 130, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;')

p = Vista('03 - Usuarios y referencias opcionales', ancho=2200, alto=1150)
p.tabla('usuarios', 830, 120)
refs = [(f['hijo'], f['campo']) for f in fks if f['padre'] == 'usuarios']
for i, (hijo, campo) in enumerate(refs):
    x = 35 + i * 305
    p.tabla(hijo, x, 740, 280, solo=['id', campo])
    p.relacion('usuarios', hijo, campo, 'exitX=0.5;exitY=1;entryX=0.5;entryY=0;', [(1085, 590), (x + 140, 590)])
p.caja('Todas estas FKs admiten NULL y usan ON DELETE SET NULL. La ausencia de una cuenta no elimina el registro de dominio.\nCada caja inferior muestra solo su PK y la referencia de usuario; el resto de los campos se documenta en el diccionario.\nLa coincidencia de cardinalidades no convierte estas referencias en una sola FK: son siete relaciones independientes.', 40, 960, 2100, 130, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;')

p = Vista('04 - Tablas técnicas', ancho=1500, alto=850)
p.caja('sesiones\n─────────────────────────\nPK sid : varchar\nsess : json, NOT NULL\nexpire : timestamp(6), NOT NULL\nÍndice sobre expire', 50, 180, 630, 270)
p.caja('migrations\n─────────────────────────\nPK id : integer autogenerado\ntimestamp : bigint, NOT NULL\nname : varchar, NOT NULL', 800, 180, 630, 270)
p.caja('Estructuras esperadas de connect-pg-simple y TypeORM, según sus fuentes oficiales. No pertenecen al CREATE TABLE de la migración inicial.\nNo existen FKs entre estas tablas ni hacia usuarios: la sesión almacena datos en JSON y migrations registra versiones de esquema.\nSu existencia efectiva depende del arranque y uso de los componentes correspondientes.', 50, 550, 1380, 170, 'shape=note;fillColor=#fff2cc;strokeColor=#d6b656;')

ET.indent(doc, space='  ')
ET.ElementTree(doc).write(OUT / 'base-datos-ewaste360.drawio', encoding='utf-8', xml_declaration=True)
print(f'Documentados {len(tablas)} tablas, {sum(len(t["campos"]) for t in tablas.values())} campos de dominio, {len(fks)} FKs y {len(enums)} enums. Generadas 4 vistas draw.io.')
