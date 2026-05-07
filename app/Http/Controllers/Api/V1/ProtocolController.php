<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProtocolType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProtocolResource;
use App\Services\Api\ProtocolLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints públicos GET de protocolos para integraciones externas (LISCOM).
 *
 * Todos los endpoints están detrás del middleware `auth.api_key` (v1.46.0)
 * que inyecta el `ApiClient` autenticado en `$request->get('api_client')`.
 * El service `ProtocolLookupService` aplica el filtrado por sede.
 */
class ProtocolController extends Controller
{
    public function __construct(
        private readonly ProtocolLookupService $service,
    ) {}

    /**
     * Listado unificado de los 3 tipos de protocolo, con filtros por
     * fecha (default: hoy), estado (default: pending,in_progress), tipo,
     * `updated_since` para sync incremental, y `protocol_number` exacto.
     * Devuelve un bloque `meta` con paginación derivada manualmente
     * porque la colección final es un merge de 3 queries.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $client = $request->get('api_client');

        $filters = $request->only([
            'date_from', 'date_to', 'status', 'type',
            'updated_since', 'protocol_number',
        ]);

        $perPage = (int) $request->input('per_page', 100);
        $perPage = max(1, min($perPage, 500));
        $page = max(1, (int) $request->input('page', 1));

        $all = $this->service->query($filters, $client);
        $items = $all->forPage($page, $perPage)->values();

        return ProtocolResource::collection($items)->additional([
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $all->count(),
                'last_page' => max(1, (int) ceil($all->count() / $perPage)),
            ],
        ]);
    }

    /**
     * Lookup directo por `protocol_number` (lo que el equipo HL7 acaba de
     * escanear). Usa el primer carácter del code para detectar el tipo y
     * cae a fallback en los 3 modelos si el prefijo no es reconocido.
     */
    public function showByBarcode(Request $request, string $code): JsonResponse|ProtocolResource
    {
        $client = $request->get('api_client');
        $found = $this->service->findByBarcode($code, $client);

        if (! $found) {
            return response()->json(['error' => 'Protocol not found'], 404);
        }

        if ($found->status === 'pending') {
            $found->update(['status' => 'in_progress']);
        }

        return new ProtocolResource($found);
    }

    /**
     * Detalle por tipo + id (URL bonita / link `self` del listado).
     */
    public function show(Request $request, string $type, int $id): JsonResponse|ProtocolResource
    {
        $protocolType = ProtocolType::tryFrom($type);
        if (! $protocolType) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $client = $request->get('api_client');
        $modelClass = $protocolType->modelClass();
        $query = $modelClass::query()->where('id', $id);
        if (! $client->isGlobal()) {
            $query->where('lab_branch_id', $client->lab_branch_id);
        }
        $found = $query->first();

        if (! $found) {
            return response()->json(['error' => 'Protocol not found'], 404);
        }

        if ($found->status === 'pending') {
            $found->update(['status' => 'in_progress']);
        }

        return new ProtocolResource($found);
    }
}
