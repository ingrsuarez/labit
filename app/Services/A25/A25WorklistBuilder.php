<?php

namespace App\Services\A25;

use App\Models\A25AnalyteMapping;
use App\Models\Admission;
use Illuminate\Support\Collection;

/**
 * Genera el archivo de worklist (import.txt) para el equipo Biosystems A25.
 *
 * Formato: columnas separadas por TAB, una línea por determinación pendiente.
 * Col 1: N  (flag fijo — indica "Normal request" en protocolo A25)
 * Col 2: SER (tipo de material — fijo o desde el mapeo)
 * Col 3: protocol_number de la admisión (identificador de muestra)
 * Col 4: nombre del analito exactamente como espera el equipo (from A25AnalyteMapping)
 * Col 5: T13 (sufijo fijo — identificador de rack/posición en A25)
 *
 * Solo se incluyen determinaciones sin resultado y no validadas (pendientes).
 * Solo se incluyen determinaciones con un mapeo A25 configurado.
 *
 * Encoding: UTF-8 (confirmar con equipo real; A25 legacy puede usar Windows-1252).
 */
class A25WorklistBuilder
{
    public const FLAG = 'N';

    public const SUFFIX = 'T13';

    /**
     * Genera el contenido del archivo worklist para una colección de admisiones.
     *
     * Usa protocol_number como identificador de muestra en el equipo A25.
     *
     * @param  Collection<int, Admission>  $admissions  Admisiones con eager-load de admissionTests.test
     * @param  int|null  $labBranchId  Para resolver mapeos por sede
     * @return array{content: string, lines: int, skipped: int, detail: array}
     */
    public function build(Collection $admissions, ?int $labBranchId = null): array
    {
        $lines = [];
        $skipped = 0;
        $detail = [];

        foreach ($admissions as $admission) {
            $sampleId = $admission->protocol_number;
            $admissionLines = [];
            $admissionSkipped = 0;

            foreach ($admission->admissionTests as $at) {
                if ($at->is_validated || $at->hasResult()) {
                    continue;
                }

                $analyteName = A25AnalyteMapping::resolveAnalyteName($at->test_id, $labBranchId);

                if ($analyteName === null) {
                    $admissionSkipped++;

                    continue;
                }

                $materialType = $this->resolveMaterialType($at->test_id, $labBranchId);
                $line = implode("\t", [self::FLAG, $materialType, $sampleId, $analyteName, self::SUFFIX]);
                $admissionLines[] = $line;
                $lines[] = $line;
            }

            $skipped += $admissionSkipped;
            $detail[] = [
                'admission' => $admission,
                'lines' => $admissionLines,
                'skipped' => $admissionSkipped,
                'reason' => null,
            ];
        }

        return [
            'content' => implode("\r\n", $lines).($lines ? "\r\n" : ''),
            'lines' => count($lines),
            'skipped' => $skipped,
            'detail' => $detail,
        ];
    }

    private function resolveMaterialType(int $testId, ?int $labBranchId): string
    {
        $query = A25AnalyteMapping::where('test_id', $testId);

        if ($labBranchId) {
            $mapping = (clone $query)->where('lab_branch_id', $labBranchId)->first();
            if ($mapping) {
                return $mapping->material_type ?: 'SER';
            }
        }

        return $query->whereNull('lab_branch_id')->value('material_type') ?: 'SER';
    }
}
