<?php

namespace App\Services\A25;

use App\Models\A25AnalyteMapping;
use App\Models\Admission;
use App\Models\VetAdmission;
use Illuminate\Support\Collection;

/**
 * Genera el archivo de worklist (import.txt) para el equipo Biosystems A25.
 *
 * Formato: columnas separadas por TAB, una línea por determinación pendiente.
 * Col 1: N  (flag fijo — indica "Normal request" en protocolo A25)
 * Col 2: SER (tipo de material — fijo o desde el mapeo)
 * Col 3: identificador de muestra (ID equipo A25 si está cargado; si no, número de protocolo)
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
            $sampleId = $this->resolveSampleIdForWorklist($admission);
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

                $materialType = A25AnalyteMapping::resolveMaterialType($at->test_id, $labBranchId);
                $line = implode("\t", [self::FLAG, $materialType, $sampleId, $analyteName, self::SUFFIX]);
                $admissionLines[] = $line;
                $lines[] = $line;
            }

            $skipped += $admissionSkipped;
            $detail[] = [
                'admission' => $admission,
                'vetAdmission' => null,
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

    /**
     * Worklist para protocolos veterinarios (mismo formato TAB que clínico).
     *
     * @param  Collection<int, VetAdmission>  $vetAdmissions  Con vetTests.test cargados
     */
    public function buildForVetAdmissions(Collection $vetAdmissions, ?int $labBranchId = null): array
    {
        $lines = [];
        $skipped = 0;
        $detail = [];

        foreach ($vetAdmissions as $vetAdmission) {
            $sampleId = $this->resolveSampleIdForWorklist($vetAdmission);
            $admissionLines = [];
            $admissionSkipped = 0;

            foreach ($vetAdmission->vetTests as $vt) {
                if ($vt->is_validated || $vt->hasResult()) {
                    continue;
                }

                $analyteName = A25AnalyteMapping::resolveAnalyteName($vt->test_id, $labBranchId);

                if ($analyteName === null) {
                    $admissionSkipped++;

                    continue;
                }

                $materialType = A25AnalyteMapping::resolveMaterialType($vt->test_id, $labBranchId);
                $line = implode("\t", [self::FLAG, $materialType, $sampleId, $analyteName, self::SUFFIX]);
                $admissionLines[] = $line;
                $lines[] = $line;
            }

            $skipped += $admissionSkipped;
            $detail[] = [
                'admission' => null,
                'vetAdmission' => $vetAdmission,
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

    /**
     * Combina worklist clínico + veterinario (un solo import.txt).
     */
    public function buildCombined(
        Collection $admissions,
        Collection $vetAdmissions,
        ?int $labBranchId = null,
    ): array {
        $clinical = $this->build($admissions, $labBranchId);
        $vet = $this->buildForVetAdmissions($vetAdmissions, $labBranchId);

        $pieces = [];
        if ($clinical['lines'] > 0) {
            $pieces[] = rtrim($clinical['content']);
        }
        if ($vet['lines'] > 0) {
            $pieces[] = rtrim($vet['content']);
        }

        $content = count($pieces) > 0 ? implode("\r\n", $pieces)."\r\n" : '';

        return [
            'content' => $content,
            'lines' => $clinical['lines'] + $vet['lines'],
            'skipped' => $clinical['skipped'] + $vet['skipped'],
            'detail' => array_merge($clinical['detail'], $vet['detail']),
        ];
    }

    private function resolveSampleIdForWorklist(Admission|VetAdmission $model): string
    {
        $external = $model->external_equipment_sample_id ?? null;
        if (is_string($external) && $external !== '') {
            return $external;
        }

        return (string) $model->protocol_number;
    }
}
