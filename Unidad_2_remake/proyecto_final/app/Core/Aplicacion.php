<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Front Controller: toda peticion entra por public/index.php y pasa por aqui.
 * Inicia la sesion, verifica CSRF, despacha la ruta y convierte las excepciones
 * en respuestas (pagina de error o JSON) sin exponer detalles internos.
 */
final class Aplicacion
{
    /** Rutas que no requieren token CSRF (la API de login no tiene sesion previa). */
    private const SIN_CSRF = ['/api/auth/login'];

    public function __construct(private \Closure $definirRutas)
    {
    }

    public function ejecutar(): void
    {
        $request = Request::capturar();
        Url::establecerBase($request->basePath());
        Session::iniciar($request->esHttps(), $request->basePath());

        $respuesta = $this->manejar($request);
        $this->cabecerasSeguridad($respuesta, $request)->enviar();
    }

    public function manejar(Request $request): Response
    {
        $router = new Router();
        ($this->definirRutas)($router);

        try {
            if ($request->jsonInvalido()) {
                throw new HttpException(400, 'El cuerpo de la petición no es JSON válido.');
            }
            $this->verificarCsrf($request);
            return $router->despachar($request);
        } catch (ValidacionException $e) {
            return $this->respuestaValidacion($e, $request);
        } catch (HttpException $e) {
            return $this->respuestaError($e->estado(), $e->getMessage(), $request);
        } catch (\Throwable $e) {
            error_log('[E-Waste 360] ' . $e);
            $mensaje = config('app.debug')
                ? $e->getMessage()
                : 'Ocurrió un error interno. Intenta de nuevo más tarde.';
            return $this->respuestaError(500, $mensaje, $request);
        }
    }

    private function verificarCsrf(Request $request): void
    {
        if ($request->esSegura() || in_array($request->ruta(), self::SIN_CSRF, true)) {
            return;
        }
        $token = $request->input('_token') ?? $request->cabecera('x-csrf-token');
        if (!Csrf::valido(is_string($token) ? $token : null)) {
            throw new HttpException(419, 'La página expiró o el formulario no es válido. Recarga la página e intenta de nuevo.');
        }
    }

    private function respuestaValidacion(ValidacionException $e, Request $request): Response
    {
        if ($request->esApi()) {
            return Response::json(['error' => [
                'codigo'   => 422,
                'mensaje'  => $e->getMessage(),
                'detalles' => $e->errores(),
            ]], 422);
        }
        Session::flash('errores', $e->errores());
        Session::flash('entrada', array_diff_key($request->todo(), ['password' => 1, 'password_confirmacion' => 1]));
        Session::flash('error', $e->getMessage());
        return Response::redirigir($request->refererLocal() ?? Url::a('panel'));
    }

    private function respuestaError(int $codigo, string $mensaje, Request $request): Response
    {
        if ($request->esApi()) {
            return Response::json(['error' => ['codigo' => $codigo, 'mensaje' => $mensaje]], $codigo);
        }
        if ($codigo === 401) {
            if ($request->metodo() === 'GET') {
                Session::guardar('url_destino', $request->ruta());
            }
            Session::flash('aviso', $mensaje);
            return Response::redirigir(Url::a('login'));
        }
        if ($codigo === 419 || ($codigo === 409 && !$request->esSegura())) {
            Session::flash('error', $mensaje);
            return Response::redirigir($request->refererLocal() ?? Url::a(''));
        }
        $html = View::render('errores/error', [
            'titulo'  => 'Error ' . $codigo,
            'codigo'  => $codigo,
            'mensaje' => $mensaje,
        ], 'layouts/publico');
        return Response::html($html, $codigo);
    }

    private function cabecerasSeguridad(Response $respuesta, Request $request): Response
    {
        $csp = "default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
            . "img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'";
        $respuesta->conCabecera('Content-Security-Policy', $csp)
            ->conCabecera('X-Content-Type-Options', 'nosniff')
            ->conCabecera('X-Frame-Options', 'SAMEORIGIN')
            ->conCabecera('Referrer-Policy', 'same-origin')
            ->conCabecera('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->esHttps()) {
            $respuesta->conCabecera('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if (Auth::check() || $request->esApi()) {
            $respuesta->conCabecera('Cache-Control', 'no-store');
        }
        return $respuesta;
    }
}
