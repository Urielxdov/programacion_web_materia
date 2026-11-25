"""Renderiza evidencia de JSON reales. Las imágenes son reportes, no capturas de una UI."""
from pathlib import Path
from datetime import datetime, timezone, timedelta
import json
import textwrap
from PIL import Image, ImageDraw, ImageFont

OUT = Path(__file__).resolve().parent
u = json.loads((OUT / 'unitarias.json').read_text(encoding='utf-8'))
e = json.loads((OUT / 'servicios-web.json').read_text(encoding='utf-8'))
h = json.loads((OUT / 'peticiones-http.json').read_text(encoding='utf-8'))
casos = {c['id']: c for c in h['casos']}
font = ImageFont.truetype('C:/Windows/Fonts/consola.ttf', 19)
titlefont = ImageFont.truetype('C:/Windows/Fonts/arialbd.ttf', 32)
smallfont = ImageFont.truetype('C:/Windows/Fonts/arial.ttf', 20)
fecha = datetime.fromtimestamp(e['startTime']/1000, timezone(timedelta(hours=-6))).isoformat(timespec='seconds')


def lienzo(titulo, alto=1450):
    img = Image.new('RGB', (1750, alto), '#f3f6fa')
    d = ImageDraw.Draw(img)
    d.text((45, 30), titulo, font=titlefont, fill='#132c42')
    d.text((45, 80), 'E-Waste 360° · ejecución aislada · ' + fecha + ' · America/Mexico_City', font=smallfont, fill='#324c60')
    d.text((45, 115), 'Reporte visual generado desde registros reales; no es una captura de pantalla del navegador.', font=smallfont, fill='#324c60')
    return img, d


def texto(d, texto, x, y, ancho=130):
    for linea in texto.splitlines():
        for fragmento in textwrap.wrap(linea, ancho, replace_whitespace=False, drop_whitespace=False) or ['']:
            d.text((x, y), fragmento, font=font, fill='#203548')
            y += 25
    return y


img, d = lienzo('01 · Resultados automatizados', 1500)
y = texto(d, f"Jest unitarias: {u['numPassedTests']}/{u['numTotalTests']} aprobadas · {u['numPassedTestSuites']} suites\nJest servicios web: {e['numPassedTests']}/{e['numTotalTests']} aprobadas · {e['numPassedTestSuites']} suite\nHTTP complementarias: {sum(c['aprobado'] for c in h['casos'])}/{len(h['casos'])} estados esperados\n\nCasos de la suite de servicios web:", 45, 175)
for suite in e['testResults']:
    for i, test in enumerate(suite['assertionResults'], 1):
        y = texto(d, f"{i:02} [{test['status']}] {test['title']}", 45, y, 133)
        y += 6
texto(d, '\nFuentes: unitarias.json · servicios-web.json · peticiones-http.json\nLos casos HTTP complementarios validan estados; Jest contiene aserciones de contenido.\nNo se midió cobertura porcentual ni se realizaron pruebas de carga o TLS de producción.', 45, y + 10)
img.save(OUT / '01-resultados.png')


def tarjeta(d, ident, x, y, ancho=75, campos=None):
    c = casos[ident]
    y = texto(d, f"{ident} · {c['titulo']}\n{c['metodo']} {c['ruta']}\nEsperado: {c['esperado']} · Recibido: {c['recibido']}\nCuerpo de petición:", x, y, ancho)
    y = texto(d, json.dumps(c['cuerpo'], ensure_ascii=False, indent=2), x, y, ancho)
    body = c['respuesta']
    if campos:
        body = {k: body[k] for k in campos}
    y = texto(d, '\nRespuesta JSON' + (' (campos seleccionados)' if campos else '') + ':', x, y, ancho)
    y = texto(d, json.dumps(body, ensure_ascii=False, indent=2), x, y, ancho)
    return y + 50


img, d = lienzo('02 · Evidencia HTTP de equipos e indicadores', 1750)
y = tarjeta(d, 'HTTP-02', 45, 180, 73, ['total','pagina','porPagina','totalPaginas'])
y = texto(d, 'El listado contiene dos equipos del fixture e2e.\nEl JSON íntegro se conserva en peticiones-http.json.', 45, y, 73)
tarjeta(d, 'HTTP-08', 45, y + 50, 73)
tarjeta(d, 'HTTP-06', 910, 180, 69)
img.save(OUT / '02-servicios-web.png')

img, d = lienzo('03 · Evidencia HTTP de seguridad', 1750)
y = tarjeta(d, 'HTTP-01', 45, 180, 73)
tarjeta(d, 'HTTP-04', 45, y, 73)
y = tarjeta(d, 'HTTP-03', 910, 180, 69)
tarjeta(d, 'HTTP-05', 910, y, 69)
img.save(OUT / '03-seguridad.png')

img, d = lienzo('04 · Evidencia funcional complementaria', 1500)
y = 180
for c in h['casos'][9:]:
    y = texto(d, f"{c['id']} · {c['titulo']}\n{c['metodo']} {c['ruta']} · esperado {c['esperado']} / recibido {c['recibido']}", 45, y, 133)
    y += 22
texto(d, '\nEstos controles comprueban estados HTTP; sus cuerpos y respuestas completos están en el JSON.\nLa contraseña del usuario de fixture fue sustituida por <omitida>. No se guardaron cookies ni tokens.', 45, y, 133)
img.save(OUT / '04-modulos.png')
print('Generados cuatro reportes PNG a partir de resultados y respuestas reales.')
