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
});
