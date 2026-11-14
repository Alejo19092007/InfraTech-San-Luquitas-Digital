<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege la creación, edición y eliminación de novedades.
 *
 * Mientras no exista el login (módulo de Samuel Guzmán), la edición está abierta
 * para poder demostrar el registro desde la web. Cuando el login esté listo,
 * PORTAL_EDICION_ABIERTA=false en el .env exige un usuario con rol "admin".
 */
class PuedeEditarContenido
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('portal.edicion_abierta')) {
            return $next($request);
        }

        $usuario = $request->user();

        abort_unless($usuario && ($usuario->rol ?? null) === 'admin', 403, 'Solo la administración puede modificar el contenido.');

        return $next($request);
    }
}
