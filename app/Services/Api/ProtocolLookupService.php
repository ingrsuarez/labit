<?php

namespace App\Services\Api;

use App\Enums\ProtocolType;
use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resuelve consultas unificadas de protocolos sobre los 3 modelos
 * (Admission/Sample/VetAdmission) aplicando filtrado de seguridad por
 * `lab_branch_id` de la API key.
 *
 * NOTA DE DISEÑO (DD-006):
 * El prompt original asumía filtrado por `company_id` en cada modelo, pero
 * los protocolos en este proyecto NO tienen esa columna (ni `lab_branches`
 * la tiene). La segregación multi-empresa NO se aplica a nivel de query —
 * se confía en que cada empresa tiene sedes propias y la sede de la key ya
 * limita el alcance. La columna `company_id` en `api_clients` queda como
 * metadata de a quién pertenece la key.
 *
 * NOTA DE PERFORMANCE:
 * Estrategia simple: 3 queries paralelas + merge en PHP. Si en producción
 * con datos reales (>500 protocolos/día/sede) el endpoint excede 200ms p95,
 * migrar a vista SQL `protocols_unified` en hotfix v1.47.1.
 */
class ProtocolLookupService
{
    /**
     * Busca un protocolo por `protocol_number` exacto, restringido a la sede
     * de la API key. Si el primer carácter del code mapea a un tipo conocido
     * (C/A/V) hace lookup directo; si no, recorre los 3 modelos.
     */
    public function findByBarcode(string $code, ApiClient $client): ?Model
    {
        $type = ProtocolType::fromBarcodePrefix($code);

        if ($type) {
            $modelClass = $type->modelClass();
            $found = $modelClass::query()
                ->where('protocol_number', $code)
                ->where('lab_branch_id', $client->lab_branch_id)
                ->first();
            if ($found) {
                return $found;
            }
        }

        foreach (ProtocolType::cases() as $candidate) {
            if ($type && $candidate === $type) {
                continue;
            }
            $modelClass = $candidate->modelClass();
            $found = $modelClass::query()
                ->where('protocol_number', $code)
                ->where('lab_branch_id', $client->lab_branch_id)
                ->first();
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Listado unificado con filtrado por sede de la key.
     * Aplica filtros en cada modelo, hace eager loading específico, mergea
     * en una colección y la ordena por `updated_at desc` para que LISCOM
     * pueda hacer sync incremental viendo los cambios más recientes primero.
     */
    public function query(array $filters, ApiClient $client): Collection
    {
        $results = collect();
        $types = $this->resolveTypes($filters['type'] ?? null);

        foreach ($types as $type) {
            $modelClass = $type->modelClass();
            $query = $modelClass::query()
                ->where('lab_branch_id', $client->lab_branch_id)
                ->with($this->eagerLoadFor($type));

            $this->applyFilters($query, $filters, $type);

            /** @var EloquentCollection<int, Model> $rows */
            $rows = $query->get();
            $results = $results->concat($rows);
        }

        return $results
            ->sortByDesc(fn (Model $m) => $m->updated_at?->getTimestamp() ?? 0)
            ->values();
    }

    private function applyFilters($query, array $filters, ProtocolType $type): void
    {
        $dateColumn = $type === ProtocolType::Sample ? 'entry_date' : 'date';

        $dateFrom = $filters['date_from'] ?? today()->toDateString();
        $dateTo = $filters['date_to'] ?? today()->toDateString();
        // whereDate (en lugar de whereBetween) garantiza comparación correcta
        // tanto en MySQL (columna DATE) como en SQLite (columna TEXT con
        // formato 'YYYY-MM-DD HH:MM:SS') que en tests usan los timestamps.
        $query->whereDate($dateColumn, '>=', $dateFrom)
            ->whereDate($dateColumn, '<=', $dateTo);

        $statuses = $filters['status'] ?? 'pending,in_progress';
        if ($statuses !== '*' && $statuses !== '') {
            $list = array_filter(array_map('trim', explode(',', $statuses)));
            if (count($list) > 0) {
                $query->whereIn('status', $list);
            }
        }

        if (! empty($filters['updated_since'])) {
            try {
                $query->where('updated_at', '>', Carbon::parse($filters['updated_since']));
            } catch (\Throwable) {
                // Filtro inválido se ignora silenciosamente (validación laxa por diseño v1.47.0).
            }
        }

        if (! empty($filters['protocol_number'])) {
            $query->where('protocol_number', $filters['protocol_number']);
        }
    }

    /**
     * @return ProtocolType[]
     */
    private function resolveTypes(?string $typeFilter): array
    {
        if (! $typeFilter) {
            return ProtocolType::cases();
        }

        $parsed = collect(explode(',', $typeFilter))
            ->map(fn (string $t) => ProtocolType::tryFrom(trim($t)))
            ->filter()
            ->values()
            ->all();

        return count($parsed) > 0 ? $parsed : ProtocolType::cases();
    }

    /**
     * Eager loading específico por tipo. Sin esto el endpoint genera N+1
     * y se cae con 100+ protocolos. La lista es la mínima necesaria para
     * que el ProtocolResource arme la response sin queries adicionales.
     */
    private function eagerLoadFor(ProtocolType $type): array
    {
        return match ($type) {
            ProtocolType::Clinical => [
                'patient',
                'labBranch',
                'admissionTests.test.materialRelation',
            ],
            ProtocolType::Sample => [
                'customer',
                'labBranch',
                'determinations.test.materialRelation',
            ],
            ProtocolType::Vet => [
                'customer',
                'species',
                'labBranch',
                'vetTests.test.materialRelation',
            ],
        };
    }
}
