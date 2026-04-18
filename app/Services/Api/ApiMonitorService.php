<?php

namespace App\Services\Api;

use App\Models\Admission;
use App\Models\ApiClient;
use App\Models\ResultBatch;
use App\Models\ResultIngestion;
use App\Models\Sample;
use App\Models\VetAdmission;
use Carbon\Carbon;

class ApiMonitorService
{
    /**
     * Counters globales de los últimos N días.
     * Usa contadores agregados de result_batches (no JSON) para evitar agregación lenta.
     */
    public function getCounters(int $days = 1): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        $ingestionsQuery = ResultIngestion::where('created_at', '>=', $since);
        $batchesQuery = ResultBatch::where('created_at', '>=', $since);

        return [
            'batches_total' => (clone $batchesQuery)->count(),
            'messages_total' => (clone $ingestionsQuery)->count(),
            'messages_ingested' => (clone $ingestionsQuery)->where('status', 'ingested')->count(),
            'messages_partial' => (clone $ingestionsQuery)->where('status', 'partial')->count(),
            'messages_rejected' => (clone $ingestionsQuery)->where('status', 'rejected')->count(),
            'messages_duplicate' => (clone $ingestionsQuery)->where('status', 'duplicate')->count(),
            'rejected_already_validated' => (clone $ingestionsQuery)
                ->where('status', 'rejected')
                ->where('rejection_reason', 'ALREADY_VALIDATED')
                ->count(),
            'rejected_protocol_not_found' => (clone $ingestionsQuery)
                ->where('status', 'rejected')
                ->where('rejection_reason', 'PROTOCOL_NOT_FOUND')
                ->count(),
            // Usa columnas materializadas de result_batches (no JSON)
            'items_ingested' => (clone $batchesQuery)->sum('items_ingested'),
            'items_overwritten' => (clone $batchesQuery)->sum('items_overwritten'),
            'items_rejected' => (clone $batchesQuery)->sum('items_rejected'),
            'period_days' => $days,
            'period_start' => $since->toIso8601String(),
        ];
    }

    /**
     * Estado por sede: ApiClients activos con última actividad y conteo de batches recientes.
     */
    public function getClientsStatus(int $days = 7): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $now = Carbon::now();

        return ApiClient::with('labBranch:id,name')
            ->where('active', true)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function (ApiClient $client) use ($since, $now) {
                $batchesRecent = ResultBatch::where('api_client_id', $client->id)
                    ->where('created_at', '>=', $since)
                    ->count();

                $lastBatch = ResultBatch::where('api_client_id', $client->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $minutesSinceLastUse = $client->last_used_at
                    ? $now->diffInMinutes($client->last_used_at)
                    : null;

                return [
                    'client' => $client,
                    'lab_branch_name' => $client->labBranch?->name ?? '-',
                    'batches_recent' => $batchesRecent,
                    'last_batch_at' => $lastBatch?->created_at,
                    'minutes_since_last_use' => $minutesSinceLastUse,
                    'health' => $this->classifyHealth($minutesSinceLastUse),
                ];
            })
            ->all();
    }

    /**
     * Clasificar salud de una sede según última actividad.
     */
    public function classifyHealth(?int $minutesSinceLastUse): string
    {
        if ($minutesSinceLastUse === null) {
            return 'never_used';
        }
        if ($minutesSinceLastUse <= 60) {
            return 'healthy';
        }
        if ($minutesSinceLastUse <= 60 * 12) {
            return 'idle';
        }
        if ($minutesSinceLastUse <= 60 * 24 * 3) {
            return 'stale';
        }

        return 'inactive';
    }

    /**
     * Listado paginado de batches con filtros.
     */
    public function getBatches(array $filters = [], int $perPage = 20)
    {
        $query = ResultBatch::with(['apiClient.labBranch']);

        if (! empty($filters['client_id'])) {
            $query->where('api_client_id', $filters['client_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['has_rejections'])) {
            $query->where('items_rejected', '>', 0);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Listado paginado de ingestions con filtros.
     */
    public function getIngestions(array $filters = [], int $perPage = 30)
    {
        $query = ResultIngestion::with(['batch.apiClient.labBranch']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['rejection_reason'])) {
            $query->where('rejection_reason', $filters['rejection_reason']);
        }
        if (! empty($filters['protocol_number'])) {
            $query->where('protocol_number', 'like', '%'.$filters['protocol_number'].'%');
        }
        if (! empty($filters['client_id'])) {
            $query->where('api_client_id', $filters['client_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['equipment_name'])) {
            $query->where('equipment_name', $filters['equipment_name']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Genera URL al protocolo en labit buscando el modelo por protocol_number.
     * Prefijos: C → clínico (Admission → lab.admissions.show)
     *           A → muestra (Sample → sample.show)
     *           V → vet (VetAdmission → vet.admissions.show)
     * Retorna null si no se puede resolver (prefijo desconocido, modelo no encontrado, ruta no existe).
     */
    public function getProtocolUrl(string $protocolNumber, ?string $protocolType = null): ?string
    {
        $type = $protocolType ?? $this->detectProtocolType($protocolNumber);

        try {
            return match ($type) {
                'clinical' => $this->urlForClinical($protocolNumber),
                'sample' => $this->urlForSample($protocolNumber),
                'vet' => $this->urlForVet($protocolNumber),
                default => null,
            };
        } catch (\Exception) {
            return null;
        }
    }

    private function urlForClinical(string $protocolNumber): ?string
    {
        $admission = Admission::where('protocol_number', $protocolNumber)->first();

        return $admission ? route('lab.admissions.show', $admission) : null;
    }

    private function urlForSample(string $protocolNumber): ?string
    {
        $sample = Sample::where('protocol_number', $protocolNumber)->first();

        return $sample ? route('sample.show', $sample) : null;
    }

    private function urlForVet(string $protocolNumber): ?string
    {
        $vetAdmission = VetAdmission::where('protocol_number', $protocolNumber)->first();

        return $vetAdmission ? route('vet.admissions.show', $vetAdmission) : null;
    }

    private function detectProtocolType(string $protocolNumber): ?string
    {
        return match (strtoupper(substr($protocolNumber, 0, 1))) {
            'C' => 'clinical',
            'A' => 'sample',
            'V' => 'vet',
            default => null,
        };
    }
}
