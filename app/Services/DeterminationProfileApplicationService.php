<?php

namespace App\Services;

use App\Enums\DeterminationProfileLabType;
use App\Http\Controllers\VetAdmissionController;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\DeterminationProfile;
use App\Models\DeterminationProfileApplication;
use App\Models\Insurance;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeterminationProfileApplicationService
{
    /**
     * @return Collection<int, DeterminationProfile>
     */
    public function loadProfilesOrdered(array $profileIds, DeterminationProfileLabType $labType): Collection
    {
        if ($profileIds === []) {
            return collect();
        }

        $profiles = DeterminationProfile::query()
            ->active()
            ->forLabType($labType)
            ->whereIn('id', $profileIds)
            ->with(['tests' => fn ($q) => $q->orderBy('determination_profile_test.sort_order')])
            ->get()
            ->keyBy('id');

        return collect($profileIds)
            ->map(fn (int|string $id) => $profiles->get((int) $id))
            ->filter();
    }

    /**
     * @return array<int> IDs de tests en orden (sin repetir; orden = orden de perfiles y pivot).
     */
    public function orderedUniqueTestIds(Collection $profiles): array
    {
        $seen = [];
        $ordered = [];
        foreach ($profiles as $profile) {
            foreach ($profile->tests as $test) {
                $tid = $test->id;
                if (! isset($seen[$tid])) {
                    $seen[$tid] = true;
                    $ordered[] = $tid;
                }
            }
        }

        return $ordered;
    }

    /**
     * Preview para admisión clínica (Alta / edición en cliente).
     *
     * @param  array<int>  $existingTestIds
     * @return array{profiles: array, invalid_profile_ids: array<int>, admission_rows: array, tests_added_count: int, tests_skipped_duplicate_count: int, skipped_not_in_nomenclator: array<int, string>}
     */
    public function previewAdmission(int $insuranceId, array $profileIds, array $existingTestIds, DeterminationProfileLabType $labType): array
    {
        $profiles = $this->loadProfilesOrdered($profileIds, $labType);
        $invalid = array_values(array_diff($profileIds, $profiles->pluck('id')->all()));

        $insurance = Insurance::findOrFail($insuranceId);
        $existing = array_fill_keys($existingTestIds, true);

        $rows = [];
        $skippedDup = 0;
        $skippedNomenclator = [];

        foreach ($this->orderedUniqueTestIds($profiles) as $testId) {
            if (isset($existing[$testId])) {
                $skippedDup++;

                continue;
            }

            $test = Test::find($testId);
            if (! $test) {
                continue;
            }

            $pricing = AdmissionInsuranceTestPricing::resolve($insurance, $test);
            if (! $pricing['in_nomenclator'] && ! $this->allowsClinicalFallbackPricing($insurance)) {
                $skippedNomenclator[$testId] = $test->code.' — '.$test->name;

                continue;
            }

            $rows[] = [
                'id' => $test->id,
                'code' => $test->code,
                'name' => $test->name,
                'calculated_price' => $pricing['price'],
                'requires_authorization' => $pricing['requires_authorization'],
                'copago' => $pricing['copago'],
                'authorization_status' => $pricing['requires_authorization'] ? AdmissionTest::STATUS_PENDING : AdmissionTest::STATUS_NOT_REQUIRED,
                'paid_by_patient' => false,
            ];
            $existing[$testId] = true;
        }

        return [
            'profiles' => $profiles->map(fn (DeterminationProfile $p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])->values()->all(),
            'invalid_profile_ids' => $invalid,
            'admission_rows' => $rows,
            'tests_added_count' => count($rows),
            'tests_skipped_duplicate_count' => $skippedDup,
            'skipped_not_in_nomenclator' => $skippedNomenclator,
        ];
    }

    /**
     * @param  array<int>  $profileIds
     * @return array{added_count: int, skipped_duplicate_count: int, skipped_nomenclator: array<int, string>}
     */
    public function applyToAdmission(Admission $admission, array $profileIds): array
    {
        $labType = DeterminationProfileLabType::Clinico;
        $profiles = $this->loadProfilesOrdered($profileIds, $labType);

        if ($profiles->isEmpty()) {
            return [
                'error' => 'No hay perfiles válidos o activos para aplicar.',
                'profiles' => collect(),
                'added_count' => 0,
                'skipped_duplicate_count' => 0,
                'skipped_nomenclator' => [],
            ];
        }

        $insurance = Insurance::findOrFail($admission->insurance);

        $existingIds = $admission->admissionTests()->pluck('test_id')->all();
        $existingSet = array_fill_keys($existingIds, true);

        $skippedDup = 0;
        $skippedNomenclator = [];
        $addedRows = 0;

        DB::transaction(function () use ($admission, $profiles, $insurance, &$existingSet, &$skippedDup, &$skippedNomenclator, &$addedRows) {
            foreach ($this->orderedUniqueTestIds($profiles) as $testId) {
                if (isset($existingSet[$testId])) {
                    $skippedDup++;

                    continue;
                }

                $test = Test::find($testId);
                if (! $test) {
                    continue;
                }

                $pricing = AdmissionInsuranceTestPricing::resolve($insurance, $test);
                if (! $pricing['in_nomenclator'] && ! $this->allowsClinicalFallbackPricing($insurance)) {
                    $skippedNomenclator[$testId] = $test->code.' — '.$test->name;

                    continue;
                }

                $authStatus = $pricing['requires_authorization']
                    ? AdmissionTest::STATUS_PENDING
                    : AdmissionTest::STATUS_NOT_REQUIRED;

                AdmissionTest::create([
                    'admission_id' => $admission->id,
                    'test_id' => $test->id,
                    'price' => $pricing['price'],
                    'nbu_units' => $pricing['nbu_units'] ?: ($test->nbu ?? 1),
                    'authorization_status' => $authStatus,
                    'paid_by_patient' => false,
                    'copago' => $pricing['copago'],
                ]);
                $existingSet[$test->id] = true;
                $addedRows++;

                foreach ($test->getAllChildren(false) as $childTest) {
                    if (! isset($existingSet[$childTest->id])) {
                        AdmissionTest::create([
                            'admission_id' => $admission->id,
                            'test_id' => $childTest->id,
                            'price' => 0,
                            'nbu_units' => $childTest->nbu ?? 0,
                            'authorization_status' => AdmissionTest::STATUS_NOT_REQUIRED,
                            'paid_by_patient' => false,
                            'copago' => 0,
                        ]);
                        $existingSet[$childTest->id] = true;
                    }
                }
            }

            $admission->calculateTotals();
        });

        $this->storeApplicationLog($admission, $profiles, $addedRows, $skippedDup, $skippedNomenclator);

        return [
            'added_count' => $addedRows,
            'skipped_duplicate_count' => $skippedDup,
            'skipped_nomenclator' => $skippedNomenclator,
            'profiles' => $profiles,
        ];
    }

    /**
     * @param  array<int>  $existingTestIds
     * @return array{profiles: array, invalid_profile_ids: array<int>, sample_rows: array, tests_added_count: int, tests_skipped_duplicate_count: int}
     */
    public function previewSample(int $customerId, array $profileIds, array $existingTestIds, DeterminationProfileLabType $labType): array
    {
        $profiles = $this->loadProfilesOrdered($profileIds, $labType);
        $invalid = array_values(array_diff($profileIds, $profiles->pluck('id')->all()));

        $customer = Customer::findOrFail($customerId);
        $discountMultiplier = 1 - (($customer->discount_percent ?? 0) / 100);

        $existing = array_fill_keys($existingTestIds, true);
        $rows = [];
        $skippedDup = 0;

        foreach ($this->orderedUniqueTestIds($profiles) as $testId) {
            if (isset($existing[$testId])) {
                $skippedDup++;

                continue;
            }

            $test = Test::find($testId);
            if (! $test) {
                continue;
            }

            $basePrice = $test->price ?? 0;
            $finalPrice = round((float) $basePrice * $discountMultiplier, 2);

            $rows[] = [
                'id' => $test->id,
                'code' => $test->code,
                'name' => $test->name,
                'price' => $finalPrice,
            ];
            $existing[$testId] = true;
        }

        return [
            'profiles' => $profiles->map(fn (DeterminationProfile $p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])->values()->all(),
            'invalid_profile_ids' => $invalid,
            'sample_rows' => $rows,
            'tests_added_count' => count($rows),
            'tests_skipped_duplicate_count' => $skippedDup,
        ];
    }

    public function applyToSample(Sample $sample, array $profileIds): array
    {
        $labType = DeterminationProfileLabType::AguasAlimentos;
        $profiles = $this->loadProfilesOrdered($profileIds, $labType);

        if ($profiles->isEmpty()) {
            return [
                'error' => 'No hay perfiles válidos o activos para aplicar.',
                'profiles' => collect(),
                'added_count' => 0,
                'skipped_duplicate_count' => 0,
            ];
        }

        $customer = $sample->customer ?? Customer::findOrFail($sample->customer_id);
        $discountMultiplier = 1 - (($customer->discount_percent ?? 0) / 100);

        $existingIds = $sample->determinations()->pluck('test_id')->all();
        $existingSet = array_fill_keys($existingIds, true);

        $skippedDup = 0;
        $addedParents = 0;

        DB::transaction(function () use ($sample, $profiles, $discountMultiplier, &$existingSet, &$skippedDup, &$addedParents) {
            foreach ($this->orderedUniqueTestIds($profiles) as $testId) {
                if (isset($existingSet[$testId])) {
                    $skippedDup++;

                    continue;
                }

                $test = Test::with(['children', 'childTests', 'referenceValues'])->find($testId);
                if (! $test) {
                    continue;
                }

                $parentCategoryId = $test->default_reference_category_id;
                $parentRef = $this->sampleReferenceValue($test);
                $basePrice = $test->price ?? 0;
                $finalPrice = round((float) $basePrice * $discountMultiplier, 2);

                SampleDetermination::create([
                    'sample_id' => $sample->id,
                    'test_id' => $test->id,
                    'price' => $finalPrice,
                    'unit' => $test->unit,
                    'method' => $test->method,
                    'reference_value' => $parentRef['value'],
                    'reference_category_id' => $parentRef['category_id'],
                    'status' => 'pending',
                ]);
                $existingSet[$test->id] = true;
                $addedParents++;

                foreach ($test->getAllChildren() as $childTest) {
                    if (! isset($existingSet[$childTest->id])) {
                        $childRef = $this->sampleReferenceValue($childTest, $parentCategoryId);
                        SampleDetermination::create([
                            'sample_id' => $sample->id,
                            'test_id' => $childTest->id,
                            'price' => 0,
                            'unit' => $childTest->unit,
                            'method' => $childTest->method,
                            'reference_value' => $childRef['value'],
                            'reference_category_id' => $childRef['category_id'],
                            'status' => 'pending',
                        ]);
                        $existingSet[$childTest->id] = true;
                    }
                }
            }
        });

        $this->storeApplicationLog($sample, $profiles, $addedParents, $skippedDup, []);

        return [
            'added_count' => $addedParents,
            'skipped_duplicate_count' => $skippedDup,
            'profiles' => $profiles,
        ];
    }

    /**
     * @param  array<int>  $existingTestIds
     */
    public function previewVet(int $customerId, array $profileIds, array $existingTestIds, DeterminationProfileLabType $labType): array
    {
        $profiles = $this->loadProfilesOrdered($profileIds, $labType);
        $invalid = array_values(array_diff($profileIds, $profiles->pluck('id')->all()));

        $customer = Customer::findOrFail($customerId);
        if (! $customer->isVeterinary()) {
            return [
                'profiles' => [],
                'invalid_profile_ids' => $profileIds,
                'vet_rows' => [],
                'tests_added_count' => 0,
                'tests_skipped_duplicate_count' => 0,
                'error' => 'El cliente no es una veterinaria válida.',
            ];
        }

        $rate = $customer->veterinaryNbuRate();
        $existing = array_fill_keys($existingTestIds, true);
        $rows = [];
        $skippedDup = 0;

        foreach ($this->orderedUniqueTestIds($profiles) as $testId) {
            if (isset($existing[$testId])) {
                $skippedDup++;

                continue;
            }

            $test = Test::find($testId);
            if (! $test) {
                continue;
            }

            $nbu = (float) ($test->nbu ?? 0);
            $price = VetAdmissionController::veterinaryPriceFromNbu($rate, $nbu);

            $rows[] = [
                'test_id' => $test->id,
                'code' => $test->code,
                'name' => $test->name,
                'nbu' => $nbu,
                'price' => $price,
            ];
            $existing[$testId] = true;
        }

        return [
            'profiles' => $profiles->map(fn (DeterminationProfile $p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])->values()->all(),
            'invalid_profile_ids' => $invalid,
            'vet_rows' => $rows,
            'tests_added_count' => count($rows),
            'tests_skipped_duplicate_count' => $skippedDup,
        ];
    }

    public function applyToVetAdmission(VetAdmission $vetAdmission, array $profileIds): array
    {
        $labType = DeterminationProfileLabType::Veterinario;
        $profiles = $this->loadProfilesOrdered($profileIds, $labType);

        if ($profiles->isEmpty()) {
            return [
                'error' => 'No hay perfiles válidos o activos para aplicar.',
                'profiles' => collect(),
                'added_count' => 0,
                'skipped_duplicate_count' => 0,
            ];
        }

        $customer = $vetAdmission->customer;
        if (! $customer || ! $customer->isVeterinary()) {
            return [
                'added_count' => 0,
                'skipped_duplicate_count' => 0,
                'profiles' => collect(),
                'error' => 'Cliente inválido.',
            ];
        }

        $rate = $customer->veterinaryNbuRate();
        $speciesId = (int) $vetAdmission->species_id;

        $existingIds = $vetAdmission->vetTests()->pluck('test_id')->all();
        $existingSet = array_fill_keys($existingIds, true);

        $skippedDup = 0;
        $addedPrice = 0.0;
        $addedParents = 0;

        DB::transaction(function () use ($vetAdmission, $profiles, $rate, $speciesId, &$existingSet, &$skippedDup, &$addedPrice, &$addedParents) {
            foreach ($this->orderedUniqueTestIds($profiles) as $testId) {
                if (isset($existingSet[$testId])) {
                    $skippedDup++;

                    continue;
                }

                $test = Test::find($testId);
                if (! $test) {
                    continue;
                }

                $nbu = (float) ($test->nbu ?? 0);
                $price = VetAdmissionController::veterinaryPriceFromNbu($rate, $nbu);
                $addedPrice += $price;

                VetAdmissionTest::create([
                    'vet_admission_id' => $vetAdmission->id,
                    'test_id' => $test->id,
                    'price' => $price,
                    'nbu_units' => $test->nbu ?? 1,
                    'unit' => $test->unit,
                    'method' => $test->method,
                    'reference_value' => $this->vetReferenceValue($test, $speciesId),
                ]);
                $existingSet[$test->id] = true;
                $addedParents++;

                foreach ($test->getAllChildren(false) as $childTest) {
                    if (! isset($existingSet[$childTest->id])) {
                        VetAdmissionTest::create([
                            'vet_admission_id' => $vetAdmission->id,
                            'test_id' => $childTest->id,
                            'price' => 0,
                            'nbu_units' => $childTest->nbu ?? 0,
                            'unit' => $childTest->unit,
                            'method' => $childTest->method,
                            'reference_value' => $this->vetReferenceValue($childTest, $speciesId),
                        ]);
                        $existingSet[$childTest->id] = true;
                    }
                }
            }

            if ($addedPrice > 0) {
                $vetAdmission->update([
                    'total_price' => round((float) $vetAdmission->total_price + $addedPrice, 2),
                ]);
            }
        });

        $this->storeApplicationLog($vetAdmission, $profiles, $addedParents, $skippedDup, []);

        return [
            'added_count' => $addedParents,
            'skipped_duplicate_count' => $skippedDup,
            'profiles' => $profiles,
        ];
    }

    private function storeApplicationLog($model, Collection $profiles, int $addedCount, int $skippedDup, array $skippedDetails): void
    {
        DeterminationProfileApplication::create([
            'applicable_type' => $model::class,
            'applicable_id' => $model->id,
            'user_id' => auth()->id(),
            'profiles_snapshot' => $profiles->map(fn (DeterminationProfile $p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])->values()->all(),
            'tests_added_count' => $addedCount,
            'tests_skipped_duplicate_count' => $skippedDup,
            'skipped_details' => $skippedDetails !== [] ? $skippedDetails : null,
        ]);
    }

    private function allowsClinicalFallbackPricing(Insurance $insurance): bool
    {
        if ($insurance->type === 'particular') {
            return true;
        }

        return $insurance->type !== 'nomenclador' && empty($insurance->nomenclator_id);
    }

    /**
     * @return array{value: mixed, category_id: int|null}
     */
    private function sampleReferenceValue(Test $test, ?int $parentCategoryId = null): array
    {
        if ($parentCategoryId) {
            $refValue = $test->referenceValues()
                ->where('reference_category_id', $parentCategoryId)
                ->first();
            if ($refValue) {
                return ['value' => $refValue->value, 'category_id' => $parentCategoryId];
            }
        }

        $defaultRef = $test->referenceValues()->where('is_default', true)->first();
        if ($defaultRef) {
            return ['value' => $defaultRef->value, 'category_id' => $defaultRef->reference_category_id];
        }

        if ($test->referenceValues()->count() > 0) {
            return ['value' => null, 'category_id' => null];
        }

        if (empty($test->low) && empty($test->high)) {
            if (! empty($test->other_reference)) {
                return ['value' => $test->other_reference, 'category_id' => null];
            }

            return ['value' => null, 'category_id' => null];
        }

        $value = null;
        if (empty($test->low) && ! empty($test->high)) {
            $value = '< '.$test->high.($test->unit ? ' '.$test->unit : '');
        } elseif (! empty($test->low) && empty($test->high)) {
            $value = '> '.$test->low.($test->unit ? ' '.$test->unit : '');
        } else {
            $value = "{$test->low} - {$test->high}".($test->unit ? ' '.$test->unit : '');
        }

        if (! empty($test->other_reference)) {
            $value = $value.' | '.$test->other_reference;
        }

        return ['value' => $value, 'category_id' => null];
    }

    private function vetReferenceValue(Test $test, int $speciesId): ?string
    {
        $speciesRef = $test->getReferenceForSpecies($speciesId);
        if ($speciesRef) {
            return $speciesRef->formatted_range;
        }

        if ($test->low || $test->high) {
            $ref = ($test->low ?? '').' - '.($test->high ?? '');
            if ($test->other_reference) {
                $ref .= ' | '.$test->other_reference;
            }

            return trim($ref, ' -');
        }

        if ($test->other_reference) {
            return $test->other_reference;
        }

        return null;
    }
}
