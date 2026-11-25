"""Registra respuestas reales del backend aislado; no captura la pantalla del navegador.

Usa las cuentas de fixture de servicios-web.e2e-spec.ts, no cuentas del seed.
Ejecutar después de esa suite y con el backend de pruebas en 127.0.0.1:3001.
"""
import json
from datetime import datetime
from http.cookiejar import CookieJar
from pathlib import Path
from urllib.request import build_opener, HTTPCookieProcessor, Request
from urllib.error import HTTPError

OUT = Path(__file__).resolve().parent
BASE = 'http://127.0.0.1:3001'
registros = []


def cliente(email=None):
    opener = build_opener(HTTPCookieProcessor(CookieJar()))
    token = pedir(opener, 'GET', '/api/auth/csrf')[1]['csrfToken']
    if email:
        status, body = pedir(opener, 'POST', '/api/auth/login', {'email': email, 'password': 'Prueba2026'}, token)
        assert status == 200, status
        token = body['csrfToken']
    return opener, token


def pedir(opener, metodo, ruta, cuerpo=None, token=None):
    headers = {'Accept': 'application/json'}
    if cuerpo is not None:
        headers['Content-Type'] = 'application/json'
    if token:
        headers['X-CSRF-Token'] = token
    req = Request(BASE + ruta, data=json.dumps(cuerpo).encode() if cuerpo is not None else None, headers=headers, method=metodo)
    try:
        res = opener.open(req)
    except HTTPError as error:
        res = error
    with res:
        return res.code, json.load(res)


def caso(ident, titulo, cli, metodo, ruta, esperado, cuerpo=None, csrf=True):
    opener, token = cli
    status, body = pedir(opener, metodo, ruta, cuerpo, token if csrf else None)
    registro = dict(id=ident, titulo=titulo, fecha=datetime.now().astimezone().isoformat(), metodo=metodo, ruta=ruta,
                    cuerpo=cuerpo, esperado=esperado, recibido=status, aprobado=status == esperado, respuesta=body)
    registros.append(registro)
    assert registro['aprobado'], registro
    return body


anonimo = cliente()
capt = cliente('capt@test.mx')
resp = cliente('resp@test.mx')
admin = cliente('admin@test.mx')
caso('HTTP-01', 'Acceso sin sesión', anonimo, 'GET', '/api/equipos', 401)
lista = caso('HTTP-02', 'Consulta de equipos autenticada', capt, 'GET', '/api/equipos?pagina=1&porPagina=5', 200)
equipo = lista['datos'][0]['id']
caso('HTTP-03', 'Datos inválidos', capt, 'POST', '/api/equipos', 400,
     {'categoriaId': 1, 'marca': '', 'modelo': 'X', 'fechaIngreso': 'ayer'})
caso('HTTP-04', 'Rol capturista sin permiso de diagnóstico', capt, 'POST', f'/api/equipos/{equipo}/diagnosticos', 403,
     {'descripcion': 'Revisión de prueba', 'enciende': True, 'danoEstructural': False, 'componentesPeligrosos': False, 'costoReparacionEstimado': 0})
caso('HTTP-05', 'Escritura autenticada sin CSRF', capt, 'PATCH', f'/api/equipos/{equipo}', 403,
     {'ubicacion': 'No debe guardarse'}, csrf=False)
caso('HTTP-06', 'Indicadores generales', resp, 'GET', '/api/indicadores', 200)
caso('HTTP-07', 'Indicadores públicos sin sesión', anonimo, 'GET', '/api/indicadores/publico', 200)
caso('HTTP-08', 'Equipo inexistente en consulta', capt, 'GET', '/api/equipos/99999', 404)
caso('HTTP-09', 'Capturista sin acceso a reporte detallado', capt, 'GET', '/api/indicadores/reutilizacion', 403)
categoria = caso('HTTP-10', 'Administrador crea categoría', admin, 'POST', '/api/catalogos/categorias', 201,
                 {'nombre': 'Cable de prueba documental', 'tipo': 'CABLEADO', 'pesoPromedioKg': 0.3, 'valorReposicion': 200})
caso('HTTP-11', 'Catálogo público', anonimo, 'GET', '/api/catalogos/categorias', 200)
campana = caso('HTTP-12', 'Responsable crea campaña', resp, 'POST', '/api/campanas', 201,
               {'nombre': 'Campaña de prueba documental', 'descripcion': 'Campaña temporal para comprobar la API de acopio.',
                'fechaInicio': '2026-11-25', 'fechaFin': '2026-11-26', 'ubicacion': 'Punto de prueba', 'categoriasIds': [categoria['id']]})
cid = campana['id']
caso('HTTP-13', 'Activación de campaña', resp, 'PATCH', f'/api/campanas/{cid}', 200, {'estado': 'ACTIVA'})
caso('HTTP-14', 'Participación pública con CSRF', anonimo, 'POST', f'/api/campanas/{cid}/participaciones', 201,
     {'nombreParticipante': 'Participante de prueba', 'equiposDeclarados': 2})
caso('HTTP-15', 'Responsable consulta participantes', resp, 'GET', f'/api/campanas/{cid}/participaciones', 200)
caso('HTTP-16', 'Capturista no consulta participantes', capt, 'GET', f'/api/campanas/{cid}/participaciones', 403)
caso('HTTP-17', 'Consulta pública de campaña', anonimo, 'GET', f'/api/campanas/{cid}', 200)
usuario = caso('HTTP-18', 'Administrador crea usuario', admin, 'POST', '/api/usuarios', 201,
               {'nombre': 'Usuario documental', 'email': 'documental@test.mx', 'password': 'Prueba2026', 'rol': 'CAPTURISTA'})
# La contraseña del fixture no se incorpora al registro de evidencia.
registros[-1]['cuerpo']['password'] = '<omitida>'
caso('HTTP-19', 'Administrador desactiva usuario temporal', admin, 'PATCH', f'/api/usuarios/{usuario["id"]}', 200, {'activo': False})
caso('HTTP-20', 'Capturista no administra usuarios', capt, 'GET', '/api/usuarios', 403)
gestor = caso('HTTP-21', 'Responsable crea gestor', resp, 'POST', '/api/catalogos/gestores', 201,
              {'nombre': 'Gestor de prueba documental', 'tipo': 'Reciclador de prueba'})
gid = gestor['id']
caso('HTTP-22', 'Administrador verifica gestor', admin, 'POST', f'/api/catalogos/gestores/{gid}/verificar', 201,
     {'documentoVerificacion': 'DOCUMENTO-DE-PRUEBA'})
caso('HTTP-23', 'Administrador revoca verificación', admin, 'POST', f'/api/catalogos/gestores/{gid}/revocar', 201)
caso('HTTP-24', 'Reporte de campañas después de participar', resp, 'GET', '/api/indicadores/campanas', 200)

destino = OUT / 'peticiones-http.json'
destino.write_text(json.dumps({'baseUrl': BASE, 'entorno': 'test aislado; PostgreSQL 5545', 'casos': registros}, ensure_ascii=False, indent=2), encoding='utf-8')
print(f'{len(registros)} comprobaciones HTTP aprobadas. Cookies, contraseñas y tokens omitidos del registro.')
