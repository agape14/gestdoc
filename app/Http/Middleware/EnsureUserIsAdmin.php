<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Solo el Administrador puede acceder a Config y gestión de usuarios.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->role !== 'Administrador') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Solo el Administrador puede acceder a Configuración.');
        }
        return $next($request);
    }
}
