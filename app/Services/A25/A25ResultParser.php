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
 * - Si la determinación no existe en el protocolo → rechazar con DETERMINATION_NOT_IN_PROTOCOL.
 * - Las demás líneas se ingresan; si ya tenía resultado previo → OVERWRITTEN.
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
        // Normalizar saltos de línea y encoding
        $content = mb_convert_encoding($fileContent, 'UTF-8', 'UTF-8, Windows-1252');
        $rawLines = preg_split('/\r\n|\r|\n/', trim($content));

        $results = [];
        $counters = ['ingested' => 0, 'overwritten' => 0, 'rejected' => 0];

        foreach ($rawLines as $lineIndex => $rawLine) {
            $rawLine = trim($rawLine);
            if ($rawLine === '') {
                continue;
            }

            $result = $this->processLine($lineIndex + 1, $rawLine, $labBranchId);
            $results[] = $result;
            $counters[$result['status'] === self::STATUS_REJECTED ? 'rejected' : $result['status']]++;
        }

        Log::channel('api')->info('a25.import.completed', array_merge($counters, [
            'lab_branch_id' => $labBranchId,
            'total_lines' => count($results),
        ]));

        return array_merge($counters, ['lines' => $results]);
    }

    private function processLine(int $lineNumber, string $rawLine, ?int $labBranchId): array
    {
        $base = ['line' => $lineNumber, 'raw' => $rawLine];

        $cols = explode("\t", $rawLine);

        if (count($cols) < 5) {
            return array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_INVALID_LINE,
                'message' => 'Línea con menos de 5 columnas TAB',
            ]);
        }

        [$sampleId, $analyteName, $materialType, $value, $unit] = $cols;
        $sampleId = trim($sampleId);
        $analyteName = trim($analyteName);
        $value = trim($value);
        $unit = trim($unit);

        // Resolver test_id desde equivalencias
        $testId = A25AnalyteMapping::resolveTestId($analyteName, $labBranchId);

        if ($testId === null) {
            return array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_ANALYTE_NOT_MAPPED,
                'analyte' => $analyteName,
                'message' => "Analito \"{$analyteName}\" no tiene equivalencia configurada.",
            ]);
        }

        // Resolver protocolo por external_equipment_sample_id
        $admission = Admission::where('external_equipment_sample_id', $sampleId)->first();

        if (! $admission) {
            return array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_SAMPLE_NOT_FOUND,
                'sample_id' => $sampleId,
                'message' => "No se encontró protocolo con id de equipo \"{$sampleId}\".",
            ]);
        }

        // Buscar la determinación en el protocolo
        $determination = AdmissionTest::where('admission_id', $admission->id)
            ->where('test_id', $testId)
            ->first();

        if (! $determination) {
            return array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_DETERMINATION_NOT_IN_PROTOCOL,
                'sample_id' => $sampleId,
                'test_id' => $testId,
                'analyte' => $analyteName,
                'message' => 'La determinación no está en el protocolo.',
            ]);
        }

        if ($determination->is_validated) {
            return array_merge($base, [
                'status' => self::STATUS_REJECTED,
                'reason' => self::REASON_ALREADY_VALIDATED,
                'determination_id' => $determination->id,
                'analyte' => $analyteName,
                'message' => 'La determinación ya fue validada.',
            ]);
        }

        $hadValue = ! empty($determination->result);
        $previousValue = $hadValue ? $determination->result : null;

        // Aplicar resultado
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

        return array_merge($base, [
            'status' => $status,
            'determination_id' => $determination->id,
            'analyte' => $analyteName,
            'value' => $value,
            'unit' => $unit,
            'previous_value' => $previousValue,
            'protocol_number' => $admission->protocol_number,
        ]);
    }
}
