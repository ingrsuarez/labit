<?php

namespace App\Http\Middleware;

use App\Services\NavigationAccessService;
use App\Support\NavigationCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackNavigationAccess
{
    public function __construct(private NavigationAccessService $navigationAccess) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user || ! $request->isMethod('GET')) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        if (! $routeName || $routeName === 'dashboard') {
            return $response;
        }

        $shortcutKey = NavigationCatalog::shortcutKeyForRoute($routeName);
        if (! $shortcutKey) {
            return $response;
        }

        $this->navigationAccess->recordHit($user->id, $shortcutKey);

        return $response;
    }
}
