<?php

namespace App\Services\A25;

use App\Models\A25AnalyteMapping;
use App\Models\Admission;
use App\Models\AdmissionTest;
use Illuminate\Support\Facades\Log;

/**
 * Parsea el archivo de resultados exportado por el equipo Biosystems A25
 * y aplica los valores en las determinaciones de Labit.
 *
 * Formato del export (columnas TAB):
 * Col 1: external_equipment_sample_id (ej. C000638S)
 * Col 2: nombre del analito (ej. Got wiener)
 * Col 3: tipo material (ej. SER)
 * Col 4: valor numérico (ej. 3, -0.21)
 * Col 5: unidad (ej. U/L)
 * Col 6: fecha/hora (ej. 17/11/2025 12:10:31)
 *
 * Reglas de negocio:
 * - Si la determinación ya está validada → rechazar con ALREADY_VALIDATED.
 * - Si el analito no tiene mapeo configurado → rechazar con ANALYTE_NOT_MAPPED.
 * - Si la muestra no se encuentra en Labit → rechazar con SAMPLE_NOT_FOUND.
 * - Si ninguna de las determinaciones mapeadas existe en el protocolo → DETERMINATION_NOT_IN_PROTOCOL.
 * - Un analito puede mapear a MÚLTIPLES determinaciones; el resultado se aplica a todas las que
 *   estén en el protocolo y no estén validadas.
 * - Política: parcial (se aplican las líneas válidas, se reportan las rechazadas).
 */
class A25ResultParser
{
    public const STATUS_INGESTED = 'ingested';

    public const STATUS_OVERWRITTEN = 'overwritten';

    public const STATUS_REJECTED = 'rejected';

    public const REASON_ALREADY_VALIDATED = 'ALREADY_VALIDATED';

    public const REASON_ANALYTE_NOT_MAPPED = 'ANALYTE_NOT_MAPPED';

    public const REASON_SAMPLE_NOT_FOUND = 'SAMPLE_NOT_FOUND';

    public const REASON_DETERMINATION_NOT_IN_PROTOCOL = 'DETERMINATION_NOT_IN_PROTOCOL';

    public const REASON_INVALID_LINE = 'INVALID_LINE';

    /**
     * Parsea el contenido de un archivo de export y aplica resultados.
     *
     * @param  string  $fileContent  Contenido del archivo TXT (UTF-8 o Windows-1252)
     * @param  int|null  $labBranchId  Para resolver mapeos por sede
     * @return array{ingested: int, overwritten: int, rejected: int, lines: list<array>}
     */
    public function import(string $fileContent, ?int $labBranchId = null): array
    {
        $content = mb_convert_encoding($fileContent, 'UTF-8', 'UTF-8, Windows-1252');
        $rawLines = preg_split('/\r\n|\r|\n/', trim($content));

        $results = [];
        $counters = ['ingested' => 0, 'overwritten' => 0, 'rejected' => 0];

        foreach ($rawLines as $lineIndex => $rawLine) {
            $rawLine = trim($rawLine);
            if ($rawLine === '') {
                continue;
            }

            $lineResults = $this->processLine($lineIndex + 1, $rawLine, $labBranchId);

            foreach ($lineResults as $result) {
                $results[] = $result;
                $counters[$result['status'] === self::STATUS_REJECTED ? 'rejected' : $result['status']]++;
            }
        }

        Log::channel('api')->info('a25.import.completed', array_merge($counters, [
            'lab_branch_id' => $labBranchId,
            'total_lines' => count($results),
        ]));

        return array_merge($counters, ['lines' => $results]);
    }

    /**
     * Procesa una línea del export y devuelve un array de resultados
     * (uno por cada determinación a la que se aplicó el valor).
     *
     * @return list<array>
     */
    private function processLine(int $lineNumber, string $rawLine, ?int $labBranchId): array
    {
        $base = ['line' => $lineNumber, 'raw' => $rawLine];

        $cols = explode("\t", $rawLine);

        if (count($cols) < 5) {
            return [array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_INVALID_LINE,
                'message' => 'Línea con menos de 5 columnas TAB',
            ])];
        }

        [$sampleId, $analyteName, $materialType, $value, $unit] = $cols;
        $sampleId = trim($sampleId);
        $analyteName = trim($analyteName);
        $value = trim($value);
        $unit = trim($unit);

        // Resolver todos los test_ids mapeados para este analito
        $testIds = A25AnalyteMapping::resolveTestIds($analyteName, $labBranchId);

        if (empty($testIds)) {
            return [array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_ANALYTE_NOT_MAPPED,
                'analyte' => $analyteName,
                'message' => "Analito \"{$analyteName}\" no tiene equivalencia configurada.",
            ])];
        }

        // Resolver protocolo por external_equipment_sample_id
        $admission = Admission::where('external_equipment_sample_id', $sampleId)->first();

        if (! $admission) {
            return [array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_SAMPLE_NOT_FOUND,
                'sample_id' => $sampleId,
                'message' => "No se encontró protocolo con id de equipo \"{$sampleId}\".",
            ])];
        }

        // Aplicar el resultado a TODAS las determinaciones mapeadas que estén en el protocolo
        $lineResults = [];
        $anyFound = false;

        foreach ($testIds as $testId) {
            $determination = AdmissionTest::where('admission_id', $admission->id)
                ->where('test_id', $testId)
                ->first();

            if (! $determination) {
                continue;
            }

            $anyFound = true;

            if ($determination->is_validated) {
                $lineResults[] = array_merge($base, [
                    'status' => self::STATUS_REJECTED,
                    'reason' => self::REASON_ALREADY_VALIDATED,
                    'determination_id' => $determination->id,
                    'analyte' => $analyteName,
                    'test_id' => $testId,
                    'message' => 'La determinación ya fue validada.',
                ]);

                continue;
            }

            $hadValue = ! empty($determination->result);
            $previousValue = $hadValue ? $determination->result : null;

            $determination->result = $value;

            if (in_array('unit', $determination->getFillable(), true)) {
                $determination->unit = $unit;
            }

            if (in_array('analyzed_at', $determination->getFillable(), true)) {
                $determination->analyzed_at = now();
            }

            if (in_array('observations', $determination->getFillable(), true)) {
                $current = $determination->observations ?? '';
                $note = '[A25 '.now()->format('Y-m-d H:i').']';
                $determination->observations = trim($current."\n".$note, "\n");
            }

            $determination->save();

            $status = $hadValue ? self::STATUS_OVERWRITTEN : self::STATUS_INGESTED;

            $lineResults[] = array_merge($base, [
                'status' => $status,
                'determination_id' => $determination->id,
                'analyte' => $analyteName,
                'test_id' => $testId,
                'value' => $value,
                'unit' => $unit,
                'previous_value' => $previousValue,
                'protocol_number' => $admission->protocol_number,
            ]);
        }

        if (! $anyFound) {
            return [array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_DETERMINATION_NOT_IN_PROTOCOL,
                'sample_id' => $sampleId,
                'test_ids' => $testIds,
                'analyte' => $analyteName,
                'message' => 'Ninguna de las determinaciones mapeadas está en el protocolo.',
            ])];
        }

        return $lineResults;
    }
}
