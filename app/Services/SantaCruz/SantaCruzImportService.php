<?php

namespace App\Services\SantaCruz;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Patient;
use App\Models\SantaCruzTestMapping;
use App\Models\Test;
use Illuminate\Support\Facades\DB;

class SantaCruzImportService
{
    public function __construct(
        protected SantaCruzXmlParser $parser
    ) {}

    /**
     * @param  array<string, mixed>  $parsed  salida de SantaCruzXmlParser::parse
     * @param  list<int>  $testIds  mismo orden que practicas
     */
    public function importAdmission(
        array $parsed,
        array $testIds,
        int $insuranceId,
        ?int $labBranchId,
        int $userId
    ): Admission {
        if (count($testIds) !== count($parsed['practicas'])) {
            throw new \InvalidArgumentException('Cantidad de tests no coincide con prácticas del XML.');
        }

        return DB::transaction(function () use ($parsed, $testIds, $insuranceId, $labBranchId, $userId) {
            $patient = $this->upsertPatient($parsed, $insuranceId);

            $date = $this->parser->orderDate($parsed);
            $affiliate = 'lcm'.$parsed['accession_number'];

            $admission = Admission::create([
                'date' => $date->toDateString(),
                'number' => Admission::max('id') + 1,
                'protocol_number' => Admission::generateProtocolNumber(),
                'patient_id' => $patient->id,
                'insurance' => $insuranceId,
                'affiliate_number' => $affiliate,
                'requesting_doctor' => $parsed['requesting_doctor'] ?: null,
                'diagnosis' => null,
                'observations' => 'Importado Santa Cruz — sucursal XML: '.($parsed['branch_name'] ?: '—'),
                'room' => 1,
                'institution' => 1,
                'invoice_date' => $date->toDateString(),
                'promise_date' => $date->copy()->addDays(3)->toDateString(),
                'authorization_code' => '',
                'attended_by' => $userId,
                'created_by' => $userId,
                'lab_branch_id' => $labBranchId,
                'insurance_price' => 0,
                'patient_price' => 0,
                'cash' => 0,
                'status' => Admission::STATUS_PENDING,
            ]);

            $insurance = Insurance::findOrFail($insuranceId);
            $totalInsurance = 0;
            $totalPatient = 0;
            $totalCopago = 0;

            foreach ($testIds as $testId) {
                $test = Test::findOrFail($testId);
                $pricing = $this->resolvePricing($insurance, $test);

                AdmissionTest::create([
                    'admission_id' => $admission->id,
                    'test_id' => $testId,
                    'price' => $pricing['price'],
                    'nbu_units' => $pricing['nbu_units'],
                    'authorization_status' => $pricing['authorization_status'],
                    'paid_by_patient' => false,
                    'copago' => $pricing['copago'],
                    'authorization_code' => null,
                    'observations' => null,
                ]);

                $children = $test->getAllChildren(false);
                foreach ($children as $childTest) {
                    $exists = AdmissionTest::where('admission_id', $admission->id)
                        ->where('test_id', $childTest->id)
                        ->exists();
                    if (! $exists) {
                        AdmissionTest::create([
                            'admission_id' => $admission->id,
                            'test_id' => $childTest->id,
                            'price' => 0,
                            'nbu_units' => $childTest->nbu ?? 0,
                            'authorization_status' => 'not_required',
                            'paid_by_patient' => false,
                            'copago' => 0,
                        ]);
                    }
                }

                if ($pricing['authorization_status'] === 'rejected') {
                    $totalPatient += $pricing['price'];
                } else {
                    $totalInsurance += $pricing['price'] - $pricing['copago'];
                    $totalCopago += $pricing['copago'];
                }
            }

            $admission->update([
                'total_insurance' => $totalInsurance,
                'total_patient' => $totalPatient,
                'total_copago' => $totalCopago,
            ]);

            if ($insurance->type === 'particular') {
                $admission->update([
                    'payment_status' => 'pendiente',
                    'paid_amount' => 0,
                ]);
            } else {
                $admission->update(['payment_status' => 'not_applicable']);
            }

            $admission->logAudit('created', 'Importó admisión Santa Cruz Nº '.$admission->protocol_number.' ('.$affiliate.')');

            return $admission->fresh(['patient']);
        });
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    public function upsertPatient(array $parsed, int $insuranceId): Patient
    {
        $dni = $parsed['document_number'];
        $patient = Patient::query()->where('patientId', $dni)->first();

        $birth = $this->parser->birthDate($parsed);
        $sex = $this->parser->mapSex($parsed['sex_raw']);

        $attrs = [
            'name' => mb_strtolower($parsed['first_name']),
            'lastName' => mb_strtolower($parsed['last_name']),
            'patientId' => $dni,
            'email' => (($e = trim((string) ($parsed['email'] ?? ''))) !== '') ? mb_strtolower($e) : null,
            'phone' => $parsed['phone'] ?: null,
            'sex' => $sex,
            'birth' => $birth?->toDateString(),
            'address' => $parsed['address_line'] ? mb_strtolower($parsed['address_line']) : null,
            'city' => $parsed['city'] ? mb_strtolower($parsed['city']) : null,
            'state' => $parsed['state'] ? mb_strtolower($parsed['state']) : null,
            'country' => $parsed['country'] ? mb_strtolower($parsed['country']) : null,
            'insurance' => $insuranceId,
            'insurance_cod' => 'lcm'.$parsed['accession_number'],
            'type' => 'active',
        ];

        if (! $patient) {
            $patient = new Patient;
            foreach ($attrs as $k => $v) {
                if ($v !== null && $v !== '') {
                    $patient->{$k} = $v;
                }
            }
            $patient->save();
            $patient->logAudit('created', 'Paciente creado por import Santa Cruz');

            return $patient;
        }

        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $current = $patient->{$key} ?? null;
            if ($current === null || $current === '') {
                $patient->{$key} = $value;
            }
        }
        $patient->save();

        return $patient;
    }

    /**
     * @return array{price: float, nbu_units: float|int, requires_authorization: bool, copago: float, authorization_status: string}
     */
    private function resolvePricing(Insurance $insurance, Test $test): array
    {
        $insuranceId = (int) $insurance->id;

        $ownItem = InsuranceTest::where('insurance_id', $insuranceId)
            ->where('test_id', $test->id)
            ->first();

        if ($ownItem) {
            return [
                'price' => (float) ($ownItem->price ?: 0),
                'nbu_units' => (float) ($ownItem->nbu_units ?: 0),
                'requires_authorization' => (bool) $ownItem->requires_authorization,
                'copago' => (float) ($ownItem->copago ?? 0),
                'authorization_status' => $ownItem->requires_authorization ? 'pending' : 'not_required',
            ];
        }

        if ($insurance->nomenclator_id) {
            $baseItem = InsuranceTest::where('insurance_id', $insurance->nomenclator_id)
                ->where('test_id', $test->id)
                ->first();

            if ($baseItem) {
                $price = (float) ($baseItem->nbu_units * (float) ($insurance->nbu_value ?? 0));

                return [
                    'price' => round($price, 2),
                    'nbu_units' => (float) ($baseItem->nbu_units ?: 0),
                    'requires_authorization' => (bool) ($baseItem->requires_authorization ?? false),
                    'copago' => (float) ($baseItem->copago ?? 0),
                    'authorization_status' => ($baseItem->requires_authorization ?? false) ? 'pending' : 'not_required',
                ];
            }
        }

        $nbuUnits = $test->nbu ?? 1;
        $price = (float) ($nbuUnits * (float) ($insurance->nbu_value ?? 0));

        return [
            'price' => round($price, 2),
            'nbu_units' => $nbuUnits,
            'requires_authorization' => false,
            'copago' => 0.0,
            'authorization_status' => 'not_required',
        ];
    }

    /**
     * @param  list<array{prestacion_code: string, prestacion_name: string}>  $practicas
     * @return list<array{prestacion_code: string, prestacion_name: string, test_id: int|null, mapped: bool}>
     */
    public function resolvePracticas(array $practicas): array
    {
        $out = [];
        foreach ($practicas as $p) {
            $tid = SantaCruzTestMapping::resolveTestId($p['prestacion_code'], $p['prestacion_name']);
            $out[] = [
                'prestacion_code' => $p['prestacion_code'],
                'prestacion_name' => $p['prestacion_name'],
                'test_id' => $tid,
                'mapped' => $tid !== null,
            ];
        }

        return $out;
    }
}
