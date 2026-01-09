<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemAccess
{
    /**
     * Roles que solo permiten acceso al portal de empleados (no al sistema administrativo)
     */
    protected array $portalOnlyRoles = ['empleado', 'employee'];

    /**
     * Verifica que el usuario tenga acceso al sistema administrativo.
     * Usuarios con rol "empleado" solo pueden acceder al portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Obtener los nombres de los roles del usuario
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Verificar si el usuario SOLO tiene roles de portal (empleado)
        $hasOnlyPortalRoles = !empty($userRoles) && 
            empty(array_diff(array_map('strtolower', $userRoles), $this->portalOnlyRoles));

        // Si solo tiene rol de empleado
        if ($hasOnlyPortalRoles) {
            // Si tiene empleado asociado, redirigir al portal
            if ($user->employee) {
                return redirect()->route('portal.dashboard');
            }
            // Si no tiene empleado asociado, mostrar página de espera
            return redirect()->route('access.pending');
        }

        // Si tiene roles administrativos (cualquier rol que no sea solo "empleado")
        if (!empty($userRoles)) {
            return $next($request);
        }

        // Si tiene permisos directos, puede acceder
        if ($user->permissions->count() > 0) {
            return $next($request);
        }

        // Si tiene empleado asociado pero sin roles, redirigir al portal
        if ($user->employee) {
            return redirect()->route('portal.dashboard');
        }

        // Usuario sin acceso - mostrar página de espera
        return redirect()->route('access.pending');
    }
}













