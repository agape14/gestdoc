<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    /** Segmentos de ruta que corresponden a menús (claves). Dashboard no se verifica: todos pueden entrar al inicio. */
    protected const MENU_SEGMENTS = [
        'licitaciones', 'consultor-obras', 'ejecutor-obra', 'proveedor-servicios',
        'proveedor-bienes', 'especialistas-ejecucion', 'especialistas-consultoria',
        'inmobiliaria', 'topografia', 'tecnologia', 'plantillas-ing', 'cvs', 'folders',
    ];

    /**
     * Verifica que el usuario tenga acceso al menú correspondiente a la ruta.
     * Administrador siempre tiene acceso. Operador/Visualizador solo si el menú está en allowed_menus.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }
        if ($user->role === 'Administrador') {
            return $next($request);
        }

        $segment = $request->segment(1);
        if (!in_array($segment, self::MENU_SEGMENTS, true)) {
            return $next($request);
        }

        if (!$user->canAccessMenu($segment)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tiene acceso a este módulo.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'No tiene acceso a este módulo.');
        }

        return $next($request);
    }
}
