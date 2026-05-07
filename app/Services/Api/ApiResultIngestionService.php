<?php

namespace App\Services\Api;

use App\Enums\ProtocolType;
use App\Models\AdmissionTest;
use App\Models\ApiClient;
use App\Models\ResultBatch;
use App\Models\ResultIngestion;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\VetAdmissionTest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiResultIngestionService
{
    public const STATUS_INGESTED = 'ingested';

    public const STATUS_OVERWRITTEN = 'overwritten';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_DUPLICATE = 'duplicate';

    public const REASON_ALREADY_VALIDATED = 'ALREADY_VALIDATED';

    public const REASON_DETERMINATION_NOT_FOUND = 'DETERMINATION_NOT_FOUND';

    public const REASON_PROTOCOL_NOT_FOUND = 'PROTOCOL_NOT_FOUND';

    public const REASON_PROTOCOL_OUT_OF_BRANCH = 'PROTOCOL_OUT_OF_BRANCH';

    /**
     * Procesa un batch completo. Devuelve estructura para serializar al cliente.
     */
    public function process(ApiClient $client, array $payload): array
    {
        return DB::transaction(function () use ($client, $payload) {
            $existingBatch = ResultBatch::where('api_client_id', $client->id)
                ->where('external_batch_id', $payload['batch_id'])
                ->first();

            if ($existingBatch) {
                Log::channel('api')->info('api.results.batch.duplicate', [
                    'api_client' => $client->id,
                    'batch_id' => $payload['batch_id'],
                ]);

                return $this->serializeExistingBatch($existingBatch);
            }

            $batch = ResultBatch::create([
                'api_client_id' => $client->id,
                'external_batch_id' => $payload['batch_id'],
                'source_app' => 'LISCOM',
                'items_total' => count($payload['items']),
                'raw_request' => $this->truncatePayload($payload),
            ]);

            $itemsResponse = [];
            $counters = ['ingested' => 0, 'overwritten' => 0, 'rejected' => 0, 'duplicate' => 0];

            foreach ($payload['items'] as $item) {
                $itemResult = $this->processItem($client, $batch, $item);
                $itemsResponse[] = $itemResult['response'];
                $counters[$itemResult['counter']]++;
            }

            $batch->update([
                'items_ingested' => $counters['ingested'],
                'items_overwritten' => $counters['overwritten'],
                'items_rejected' => $counters['rejected'],
                'items_duplicate' => $counters['duplicate'],
            ]);

            Log::channel('api')->info('api.results.batch.processed', [
                'api_client' => $client->id,
                'batch_id' => $payload['batch_id'],
                'counters' => $counters,
            ]);

            return [
                'batch_id' => $payload['batch_id'],
                'items' => $itemsResponse,
            ];
        });
    }

    private function processItem(ApiClient $client, ResultBatch $batch, array $item): array
    {
        $equipmentName = $item['equipment_name'] ?? null;
        $protocolNumber = $item['protocol_number'];

        $dedupQuery = ResultIngestion::where('api_client_id', $client->id)
            ->where('hl7_control_id', $item['hl7_control_id'])
            ->where('protocol_number', $protocolNumber)
            ->whereIn('status', [self::STATUS_INGESTED, self::STATUS_OVERWRITTEN, self::STATUS_DUPLICATE]);

        // Scope por equipo: dos equipos distintos enviando resultados para el mismo protocolo
        // con el mismo hl7_control_id (MSH-10 derivado del número de protocolo) NO son duplicados.
        if ($equipmentName !== null && $equipmentName !== '') {
            $dedupQuery->where('equipment_name', $equipmentName);
        }

        $existingIngestion = $dedupQuery->first();

        if ($existingIngestion) {
            return [
                'counter' => 'duplicate',
                'response' => [
                    'external_message_id' => $item['external_message_id'] ?? null,
                    'hl7_control_id' => $item['hl7_control_id'],
                    'status' => self::STATUS_DUPLICATE,
                    'original_batch_id' => $existingIngestion->batch->external_batch_id,
                    'results' => $existingIngestion->items_summary,
                ],
            ];
        }

        $protocolNumber = $item['protocol_number'];
        $protocol = $this->resolveProtocol($protocolNumber);

        if (! $protocol) {
            return $this->rejectMessage($client, $batch, $item, self::REASON_PROTOCOL_NOT_FOUND);
        }

        if (! $client->isGlobal()
            && isset($protocol['model']->lab_branch_id)
            && $protocol['model']->lab_branch_id !== $client->lab_branch_id
        ) {
            return $this->rejectMessage($client, $batch, $item, self::REASON_PROTOCOL_OUT_OF_BRANCH);
        }

        $resultsSummary = [];
        $allRejected = true;

        foreach ($item['results'] as $result) {
            $itemSummary = $this->processSingleResult($protocol['type'], $protocol['model'], $result);
            $resultsSummary[] = $itemSummary;

            if (in_array($itemSummary['status'], [self::STATUS_INGESTED, self::STATUS_OVERWRITTEN], true)) {
                $allRejected = false;
            }
        }

        $messageStatus = $allRejected ? self::STATUS_REJECTED : self::STATUS_INGESTED;
        $hasPartialRejection = ! $allRejected
            && collect($resultsSummary)->contains('status', self::STATUS_REJECTED);

        if ($hasPartialRejection) {
            $messageStatus = 'partial';
        }

        ResultIngestion::create([
            'result_batch_id' => $batch->id,
            'api_client_id' => $client->id,
            'external_message_id' => $item['external_message_id'] ?? null,
            'hl7_control_id' => $item['hl7_control_id'],
            'protocol_number' => $protocolNumber,
            'protocol_type' => $protocol['type'],
            'equipment_name' => $item['equipment_name'] ?? null,
            'status' => $messageStatus === 'partial' ? self::STATUS_INGESTED : $messageStatus,
            'items_summary' => $resultsSummary,
        ]);

        return [
            'counter' => $allRejected ? 'rejected' : 'ingested',
            'response' => [
                'external_message_id' => $item['external_message_id'] ?? null,
                'hl7_control_id' => $item['hl7_control_id'],
                'status' => $messageStatus,
                'protocol_number' => $protocolNumber,
                'results' => $resultsSummary,
            ],
        ];
    }

    /**
     * Detecta tipo por prefijo del protocol_number usando ProtocolType enum (consistente con v1.47.0).
     * Prefijos: C → clinical, A → sample, V → vet.
     */
    private function resolveProtocol(string $protocolNumber): ?array
    {
        $type = ProtocolType::fromBarcodePrefix($protocolNumber);

        if (! $type) {
            return null;
        }

        $modelClass = $type->modelClass();
        $model = $modelClass::where('protocol_number', $protocolNumber)->first();

        return $model ? ['type' => $type->value, 'model' => $model] : null;
    }

    private function loadProtocol(string $modelClass, string $protocolNumber, string $type): ?array
    {
        $model = $modelClass::where('protocol_number', $protocolNumber)->first();

        return $model ? ['type' => $type, 'model' => $model] : null;
    }

    /**
     * Procesa un result individual.
     * Regla crítica: si is_validated = true → rechazar con ALREADY_VALIDATED.
     */
    private function processSingleResult(string $protocolType, mixed $protocol, array $result): array
    {
        $determination = $this->findDetermination($protocolType, $protocol, $result['labit_test_id']);

        if (! $determination) {
            return [
                'obx_index' => $result['obx_index'],
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_DETERMINATION_NOT_FOUND,
                'labit_test_id' => $result['labit_test_id'],
            ];
        }

        if ($determination->is_validated) {
            Log::channel('api')->info('api.results.item.rejected_already_validated', [
                'protocol_type' => $protocolType,
                'determination_id' => $determination->id,
                'incoming_value' => $result['value'],
                'existing_value' => $determination->result,
                'validated_by' => $determination->validated_by,
                'validated_at' => $determination->validated_at?->toIso8601String(),
            ]);

            $validatedByName = $determination->validated_by
                ? \App\Models\User::find($determination->validated_by)?->name
                : null;

            return [
                'obx_index' => $result['obx_index'],
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_ALREADY_VALIDATED,
                'determination_id' => $determination->id,
                'validated_at' => $determination->validated_at?->toIso8601String(),
                'validated_by_name' => $validatedByName,
            ];
        }

        $hadValue = ! empty($determination->result);
        $previousValue = $hadValue ? $determination->result : null;

        $this->persistResult($determination, $result);

        $finalStatus = $hadValue ? self::STATUS_OVERWRITTEN : self::STATUS_INGESTED;

        if ($hadValue) {
            Log::channel('api')->info('api.results.item.overwritten', [
                'protocol_type' => $protocolType,
                'determination_id' => $determination->id,
                'previous_value' => $previousValue,
                'new_value' => $result['value'],
            ]);
        }

        return [
            'obx_index' => $result['obx_index'],
            'status' => $finalStatus,
            'determination_id' => $determination->id,
            'previous_value' => $previousValue,
        ];
    }

    private function findDetermination(string $protocolType, mixed $protocol, int $testId): mixed
    {
        return match ($protocolType) {
            'clinical' => AdmissionTest::where('admission_id', $protocol->id)->where('test_id', $testId)->first(),
            'sample' => SampleDetermination::where('sample_id', $protocol->id)->where('test_id', $testId)->first(),
            'vet' => VetAdmissionTest::where('vet_admission_id', $protocol->id)->where('test_id', $testId)->first(),
            default => null,
        };
    }

    private function persistResult(mixed $determination, array $result): void
    {
        $determination->result = $result['value'];

        if (in_array('unit', $determination->getFillable(), true) && isset($result['unit'])) {
            $determination->unit = $result['unit'];
        }

        if (in_array('analyzed_at', $determination->getFillable(), true)) {
            $determination->analyzed_at = now();
        }

        if (in_array('observations', $determination->getFillable(), true)) {
            $current = $determination->observations ?? '';
            $note = '[API LISCOM '.now()->format('Y-m-d H:i').']';
            $determination->observations = trim($current."\n".$note, "\n");
        }

        $determination->save();
    }

    private function rejectMessage(ApiClient $client, ResultBatch $batch, array $item, string $reason): array
    {
        ResultIngestion::create([
            'result_batch_id' => $batch->id,
            'api_client_id' => $client->id,
            'external_message_id' => $item['external_message_id'] ?? null,
            'hl7_control_id' => $item['hl7_control_id'],
            'protocol_number' => $item['protocol_number'],
            'equipment_name' => $item['equipment_name'] ?? null,
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'items_summary' => [],
        ]);

        return [
            'counter' => 'rejected',
            'response' => [
                'external_message_id' => $item['external_message_id'] ?? null,
                'hl7_control_id' => $item['hl7_control_id'],
                'status' => self::STATUS_REJECTED,
                'reason' => $reason,
                'protocol_number' => $item['protocol_number'],
            ],
        ];
    }

    private function serializeExistingBatch(ResultBatch $batch): array
    {
        $batch->load('ingestions');

        return [
            'batch_id' => $batch->external_batch_id,
            'duplicate' => true,
            'items' => $batch->ingestions->map(fn ($ing) => [
                'external_message_id' => $ing->external_message_id,
                'hl7_control_id' => $ing->hl7_control_id,
                'status' => $ing->status,
                'protocol_number' => $ing->protocol_number,
                'results' => $ing->items_summary,
            ])->all(),
        ];
    }

    private function truncatePayload(array $payload): array
    {
        $encoded = json_encode($payload);
        if (strlen($encoded) <= 64 * 1024) {
            return $payload;
        }

        return [
            '_truncated' => true,
            'batch_id' => $payload['batch_id'] ?? null,
            'items_count' => count($payload['items'] ?? []),
        ];
    }
}
