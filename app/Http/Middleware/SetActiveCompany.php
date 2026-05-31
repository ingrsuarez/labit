<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $activeId = session('active_company_id');
            if ($activeId && ! \App\Models\Company::whereKey($activeId)->exists()) {
                session()->forget('active_company_id');
            }

            if (! session()->has('active_company_id')) {
                $default = auth()->user()->defaultCompany();
                if ($default) {
                    session(['active_company_id' => $default->id]);
                }
            }

            view()->share('activeCompany', active_company());
            view()->share('userCompanies', auth()->user()->accessibleCompanies());
        }

        return $next($request);
    }
}
