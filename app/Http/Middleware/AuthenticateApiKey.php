<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Valida el header X-API-Key contra los hashes guardados en `api_clients`,
     * inyecta el cliente en el request, registra uso en background y loguea
     * cada request en el canal `api`. La key plana NUNCA se loguea.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');

        if (! $key) {
            return response()->json([
                'error' => 'API key required',
                'code' => 'API_KEY_MISSING',
            ], 401);
        }

        $hash = ApiClient::hashKey($key);
        $client = ApiClient::where('api_key_hash', $hash)->first();

        if (! $client || ! $client->active) {
            return response()->json([
                'error' => 'Invalid or inactive API key',
                'code' => 'API_KEY_INVALID',
            ], 401);
        }

        $request->merge(['api_client' => $client]);
        $request->setUserResolver(fn () => null);

        Log::channel('api')->info('api_request', [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'lab_branch_id' => $client->lab_branch_id,
            'company_id' => $client->company_id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        // Tracking en background para no penalizar latencia. Con queue
        // driver `sync` se ejecuta inline después de enviar la respuesta.
        dispatch(function () use ($client) {
            $client->newQuery()
                ->whereKey($client->id)
                ->update([
                    'requests_count' => $client->requests_count + 1,
                    'last_used_at' => now(),
                ]);
        })->afterResponse();

        return $next($request);
    }
}
