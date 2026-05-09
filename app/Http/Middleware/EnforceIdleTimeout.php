<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    /**
     * Cierra la sesión si el usuario superó el tiempo máximo de inactividad.
     *
     * Usa `users.last_activity_at` para que el cierre aplique también cuando
     * «recordarme» reabre sesión tras expirar el almacén de sesión del servidor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $idleMinutes = (int) config('session.idle_timeout_minutes', 30);

        if ($idleMinutes <= 0) {
            return $next($request);
        }

        $user = $user->fresh();

        if ($user === null) {
            return $next($request);
        }

        if (
            $user->last_activity_at !== null
            && $user->last_activity_at->lte(now()->subMinutes($idleMinutes))
        ) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')
                ->with('status', 'Tu sesión se cerró por inactividad. Volvé a iniciar sesión.');
        }

        $now = now();

        if ($user->last_activity_at === null || $user->last_activity_at->lt($now->copy()->subMinute())) {
            $user->forceFill(['last_activity_at' => $now])->saveQuietly();
        }

        return $next($request);
    }
}
