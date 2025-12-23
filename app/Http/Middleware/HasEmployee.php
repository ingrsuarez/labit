<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasEmployee
{
    /**
     * Verifica que el usuario autenticado tenga un empleado asociado.
     * Este middleware es necesario para acceder al portal de empleados.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->employee) {
            // Redirigir a access.pending en lugar de dashboard para evitar loops
            return redirect()->route('access.pending')
                ->with('error', 'No tienes un empleado asociado a tu cuenta. Contacta al administrador.');
        }

        return $next($request);
    }
}










