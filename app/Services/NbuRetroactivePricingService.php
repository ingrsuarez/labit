<?php

namespace App\Services;

use App\Http\Controllers\VetAdmissionController;
use App\Models\Admission;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\VetAdmission;
use Illuminate\Support\Facades\DB;

class NbuRetroactivePricingService
{
    /**
     * @return array{admissions_count: int, rows_count: int, excluded_invoiced_count: int}
     */
    public function previewClinical(Insurance $insurance, float $newNbuValue, string $fromDate): array
    {
        $baseQuery = Admission::query()
            ->where('insurance', $insurance->id)
            ->where('date', '>=', $fromDate);

        $excludedInvoiced = (clone $baseQuery)->invoiced()->count();

        $admissions = (clone $baseQuery)
            ->uninvoiced()
            ->with('admissionTests')
            ->get();

        $rowsCount = 0;
        foreach ($admissions as $admission) {
            $rowsCount += $admission->admissionTests
                ->filter(fn ($row) => (float) $row->price > 0)
                ->count();
        }

        return [
            'admissions_count' => $admissions->count(),
            'rows_count' => $rowsCount,
            'excluded_invoiced_count' => $excludedInvoiced,
        ];
    }

    /**
     * @return array{admissions_updated: int, rows_updated: int, excluded_invoiced: int}
     */
    public function applyClinical(Insurance $insurance, float $newNbuValue, string $fromDate): array
    {
        $excludedInvoiced = Admission::query()
            ->where('insurance', $insurance->id)
            ->where('date', '>=', $fromDate)
            ->invoiced()
            ->count();

        $admissions = Admission::query()
            ->where('insurance', $insurance->id)
            ->where('date', '>=', $fromDate)
            ->uninvoiced()
            ->with(['admissionTests.test'])
            ->get();

        $rowsUpdated = 0;
        $admissionsUpdated = 0;

        DB::transaction(function () use ($admissions, $insurance, $newNbuValue, &$rowsUpdated, &$admissionsUpdated) {
            foreach ($admissions as $admission) {
                $admissionRowsUpdated = 0;

                foreach ($admission->admissionTests as $admissionTest) {
                    if ((float) $admissionTest->price <= 0) {
                        continue;
                    }

                    $test = $admissionTest->test;
                    if (! $test) {
                        continue;
                    }

                    $pricing = AdmissionInsuranceTestPricing::resolve($insurance, $test, $newNbuValue);

                    $admissionTest->update([
                        'price' => $pricing['price'],
                        'nbu_units' => $pricing['nbu_units'] ?: ($test->nbu ?? 1),
                        'copago' => $pricing['copago'],
                    ]);

                    $rowsUpdated++;
                    $admissionRowsUpdated++;
                }

                if ($admissionRowsUpdated > 0) {
                    $admission->load('admissionTests');
                    $admission->calculateTotals();
                    $admissionsUpdated++;
                }
            }
        });

        return [
            'admissions_updated' => $admissionsUpdated,
            'rows_updated' => $rowsUpdated,
            'excluded_invoiced' => $excludedInvoiced,
        ];
    }

    /**
     * @return array{admissions_count: int, rows_count: int, excluded_invoiced_count: int}
     */
    public function previewVet(Customer $customer, float $newRate, string $fromDate): array
    {
        $baseQuery = VetAdmission::query()
            ->where('customer_id', $customer->id)
            ->where('date', '>=', $fromDate);

        $excludedInvoiced = (clone $baseQuery)->invoiced()->count();

        $admissions = (clone $baseQuery)
            ->uninvoiced()
            ->with('vetTests')
            ->get();

        $rowsCount = 0;
        foreach ($admissions as $admission) {
            $rowsCount += $admission->vetTests
                ->filter(fn ($row) => (float) $row->price > 0)
                ->count();
        }

        return [
            'admissions_count' => $admissions->count(),
            'rows_count' => $rowsCount,
            'excluded_invoiced_count' => $excludedInvoiced,
        ];
    }

    /**
     * @return array{admissions_updated: int, rows_updated: int, excluded_invoiced: int}
     */
    public function applyVet(Customer $customer, float $newRate, string $fromDate): array
    {
        $excludedInvoiced = VetAdmission::query()
            ->where('customer_id', $customer->id)
            ->where('date', '>=', $fromDate)
            ->invoiced()
            ->count();

        $admissions = VetAdmission::query()
            ->where('customer_id', $customer->id)
            ->where('date', '>=', $fromDate)
            ->uninvoiced()
            ->with(['vetTests.test'])
            ->get();

        $rowsUpdated = 0;
        $admissionsUpdated = 0;

        DB::transaction(function () use ($admissions, $newRate, &$rowsUpdated, &$admissionsUpdated) {
            foreach ($admissions as $vetAdmission) {
                $admissionRowsUpdated = 0;

                foreach ($vetAdmission->vetTests as $vetTest) {
                    if ((float) $vetTest->price <= 0) {
                        continue;
                    }

                    $nbu = (float) ($vetTest->nbu_units ?? $vetTest->test?->nbu ?? 0);
                    $newPrice = VetAdmissionController::veterinaryPriceFromNbu($newRate, $nbu);

                    $vetTest->update(['price' => $newPrice]);
                    $rowsUpdated++;
                    $admissionRowsUpdated++;
                }

                if ($admissionRowsUpdated > 0) {
                    $totalPrice = $vetAdmission->vetTests()->sum('price');
                    $vetAdmission->update(['total_price' => round((float) $totalPrice, 2)]);
                    $admissionsUpdated++;
                }
            }
        });

        return [
            'admissions_updated' => $admissionsUpdated,
            'rows_updated' => $rowsUpdated,
            'excluded_invoiced' => $excludedInvoiced,
        ];
    }

    public function applyClinicalIfRequested(\Illuminate\Http\Request $request, Insurance $insurance, float $newNbuValue, float $oldNbuValue): ?array
    {
        if (abs($oldNbuValue - $newNbuValue) < 0.001) {
            return null;
        }

        if (! $request->boolean('retroactive_update') || ! $request->filled('retroactive_from')) {
            return null;
        }

        return $this->applyClinical($insurance, $newNbuValue, $request->string('retroactive_from')->toString());
    }

    public function applyVetIfRequested(\Illuminate\Http\Request $request, Customer $customer, float $newRate, float $oldRate): ?array
    {
        if (abs($oldRate - $newRate) < 0.001) {
            return null;
        }

        if (! $request->boolean('retroactive_update') || ! $request->filled('retroactive_from')) {
            return null;
        }

        return $this->applyVet($customer, $newRate, $request->string('retroactive_from')->toString());
    }

    public static function flashMessage(?array $retroResult, string $entityLabel = 'admisiones'): string
    {
        if ($retroResult === null) {
            return 'Valor NBU actualizado correctamente.';
        }

        if ($retroResult['admissions_updated'] === 0 && $retroResult['rows_updated'] === 0) {
            return 'Valor NBU guardado. No había '.$entityLabel.' sin facturar elegibles para actualizar.';
        }

        $message = 'Valor NBU actualizado. Se recalcularon '
            .$retroResult['admissions_updated'].' '.$entityLabel
            .' ('.$retroResult['rows_updated'].' determinaciones).';

        if (($retroResult['excluded_invoiced'] ?? 0) > 0) {
            $message .= ' '.$retroResult['excluded_invoiced'].' facturadas no se modificaron.';
        }

        return $message;
    }
}
