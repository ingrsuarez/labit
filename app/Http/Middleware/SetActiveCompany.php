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
            if (! session()->has('active_company_id')) {
                $default = auth()->user()->defaultCompany();
                if ($default) {
                    session(['active_company_id' => $default->id]);
                }
            }

            view()->share('activeCompany', active_company());
            view()->share('userCompanies', auth()->user()->companies);
        }

        return $next($request);
    }
}
