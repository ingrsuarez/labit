<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/test', function(){
    return ['name' => 'Rodrigo'];
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = Auth::user();
    return $user->name;
});

/*
|--------------------------------------------------------------------------
| API pública v1 (auth con API key — header X-API-Key)
|--------------------------------------------------------------------------
|
| Pensada para integraciones máquina-a-máquina (LISCOM, etc.). Una key por
| sede; el cliente queda disponible en `$request->get('api_client')`.
|
*/
Route::prefix('v1')->middleware('auth.api_key')->group(function () {
    Route::get('ping', function (Request $request) {
        $client = $request->get('api_client');

        return response()->json([
            'status' => 'ok',
            'client' => $client->name,
            'branch' => $client->labBranch?->name,
            'company' => $client->company?->name,
            'time' => now()->toIso8601String(),
        ]);
    })->name('api.v1.ping');

    // Protocolos unificados (clinical/sample/vet) — v1.47.0
    Route::get('protocols', [\App\Http\Controllers\Api\V1\ProtocolController::class, 'index'])
        ->name('api.v1.protocols.index');

    // El barcode puede contener caracteres especiales (ej: futuro `^material`).
    // `where('code', '.+')` evita que Laravel parta la URL por `/`.
    Route::get('protocols/by-barcode/{code}', [\App\Http\Controllers\Api\V1\ProtocolController::class, 'showByBarcode'])
        ->where('code', '.+')
        ->name('api.v1.protocols.by_barcode');

    Route::get('protocols/{type}/{id}', [\App\Http\Controllers\Api\V1\ProtocolController::class, 'show'])
        ->whereIn('type', ['clinical', 'sample', 'vet'])
        ->whereNumber('id')
        ->name('api.v1.protocols.show');
});
