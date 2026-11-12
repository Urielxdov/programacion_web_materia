<?php
declare(strict_types=1);

/*
 * Matriz de permisos por rol. Se valida SIEMPRE en el servidor (middleware TienePermiso
 * y servicios); la interfaz solo oculta lo que el rol no puede usar.
 * El visitante (sin sesion) solo accede a las paginas publicas.
 */

$capturista = [
    'panel.ver',
    'equipos.ver',
    'equipos.crear',
    'equipos.editar',
    'movimientos.registrar',
];

$responsable = array_merge($capturista, [
    'equipos.clasificar',
    'diagnosticos.registrar',
    'movimientos.validar',
    'campanas.gestionar',
    'reportes.ver',
]);

$admin = array_merge($responsable, [
    'usuarios.gestionar',
    'catalogos.gestionar',
    'configuracion.gestionar',
]);

return [
    'capturista'  => $capturista,
    'responsable' => $responsable,
    'admin'       => $admin,
];
