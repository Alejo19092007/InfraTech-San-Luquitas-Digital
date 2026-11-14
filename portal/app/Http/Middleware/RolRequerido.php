<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: ->middleware('rol:admin') o ->middleware('rol:admin,profesor'). */
class RolRequerido
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && $usuario->esRol(...$roles), 403, 'No tienes permiso para entrar a esta sección.');

        return $next($request);
    }
}
