<?php

namespace App\Services;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Test;

/**
 * Precios por obra social para prácticas de admisión clínica (misma lógica que LabAdmissionController::getTestPrice).
 */
class AdmissionInsuranceTestPricing
{
    /**
     * @return array{price: float, nbu_units: float, requires_authorization: bool, copago: float, in_nomenclator: bool, source: string}
     */
    public static function resolve(Insurance $insurance, Test $test, ?float $nbuValueOverride = null): array
    {
        $nbuValue = $nbuValueOverride ?? ($insurance->nbu_value ?? 0);
        $insuranceId = $insurance->id;
        $testId = $test->id;

        $ownItem = InsuranceTest::where('insurance_id', $insuranceId)
            ->where('test_id', $testId)
            ->first();

        if ($ownItem) {
            return [
                'price' => (float) ($ownItem->price ?? 0),
                'nbu_units' => (float) ($ownItem->nbu_units ?? 0),
                'requires_authorization' => (bool) ($ownItem->requires_authorization ?? false),
                'copago' => (float) ($ownItem->copago ?? 0),
                'in_nomenclator' => true,
                'source' => 'own',
            ];
        }

        if ($insurance->nomenclator_id) {
            $baseItem = InsuranceTest::where('insurance_id', $insurance->nomenclator_id)
                ->where('test_id', $testId)
                ->first();

            if ($baseItem) {
                $price = $baseItem->nbu_units * $nbuValue;

                return [
                    'price' => round((float) $price, 2),
                    'nbu_units' => (float) ($baseItem->nbu_units ?? 0),
                    'requires_authorization' => (bool) ($baseItem->requires_authorization ?? false),
                    'copago' => (float) ($baseItem->copago ?? 0),
                    'in_nomenclator' => true,
                    'source' => 'base',
                ];
            }
        }

        $nbuUnits = (float) ($test->nbu ?? 1);
        $price = $nbuUnits * $nbuValue;

        return [
            'price' => round((float) $price, 2),
            'nbu_units' => $nbuUnits,
            'requires_authorization' => false,
            'copago' => 0.0,
            'in_nomenclator' => false,
            'source' => 'fallback',
        ];
    }
}
