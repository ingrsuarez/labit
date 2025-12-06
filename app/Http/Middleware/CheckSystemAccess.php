<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemAccess
{
    /**
     * Verifica que el usuario tenga acceso al sistema.
     * Un usuario nuevo sin roles/permisos ni empleado asociado no puede acceder.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si el usuario tiene un empleado asociado, puede acceder al portal
        if ($user->employee) {
            return $next($request);
        }

        // Si el usuario tiene al menos un rol, puede acceder
        if ($user->roles->count() > 0) {
            return $next($request);
        }

        // Si el usuario tiene al menos un permiso directo, puede acceder
        if ($user->permissions->count() > 0) {
            return $next($request);
        }

        // Usuario sin acceso - mostrar página de espera
        return redirect()->route('access.pending');
    }
}
