<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemAccess
{
    protected array $portalOnlyRoles = ['empleado', 'employee'];

    protected array $labOnlyRoles = ['recepcion-lab', 'tecnico-lab', 'bioquimico'];

    protected array $adminCapableRoles = ['admin', 'contador', 'compras', 'ventas'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRoles = $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->toArray();

        $hasAdminCapable = ! empty(array_intersect($userRoles, $this->adminCapableRoles));
        $hasLabOnly = ! empty(array_intersect($userRoles, $this->labOnlyRoles));
        $hasOnlyPortalRoles = ! empty($userRoles)
            && empty(array_diff($userRoles, $this->portalOnlyRoles));

        if ($hasAdminCapable) {
            return $next($request);
        }

        if ($hasLabOnly) {
            return redirect()->route('lab.section.clinico');
        }

        if ($hasOnlyPortalRoles) {
            if ($user->employee) {
                return redirect()->route('portal.dashboard');
            }

            return redirect()->route('access.pending');
        }

        if (! empty($userRoles)) {
            return $next($request);
        }

        if ($user->permissions->count() > 0) {
            return $next($request);
        }

        if ($user->employee) {
            return redirect()->route('portal.dashboard');
        }

        return redirect()->route('access.pending');
    }
}
