<?php

namespace Tests\Feature;

use App\Models\A25AnalyteMapping;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\Species;
use App\Models\Test;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use App\Services\A25\A25ResultParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class A25ResultParserTest extends TestCase
{
    use RefreshDatabase;

    private function makeClinicalTest(): Test
    {
        static $n = 0;
        $n++;

        return Test::query()->create([
            'code' => "AC{$n}",
            'name' => "Analito clínico {$n}",
            'unit' => 'mg/dL',
            'price' => 100,
        ]);
    }

    private function makePatient(): Patient
    {
        return Patient::query()->create([
            'name' => 'Juan',
            'lastName' => 'Pérez',
            'patientId' => uniqid('DNI'),
            'type' => 'particular',
            'sex' => 'M',
            'birth' => '1980-01-01',
            'status' => 'activo',
        ]);
    }

    private function makeAdmissionWithSample(string $sampleId, Test $test): Admission
    {
        $protocol = Admission::generateProtocolNumber();

        $admission = Admission::query()->create([
            'date' => today()->toDateString(),
            'number' => $protocol,
            'protocol_number' => $protocol,
            'patient_id' => $this->makePatient()->id,
            'room' => 0,
            'institution' => 0,
            'invoice_date' => today()->toDateString(),
            'promise_date' => today()->toDateString(),
            'authorization_code' => 'N/A',
            'attended_by' => 0,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => 0,
            'external_equipment_sample_id' => $sampleId,
            'status' => Admission::STATUS_PENDING,
        ]);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 100,
            'authorization_status' => AdmissionTest::STATUS_PENDING,
        ]);

        return $admission;
    }

    private function makeVetAdmissionWithSample(string $sampleId, Test $test): VetAdmission
    {
        $species = Species::query()->create(['name' => 'Can T', 'code' => uniqid('SP'), 'is_active' => true]);
        $customer = Customer::query()->create([
            'name' => 'Cliente Vet',
            'taxId' => '20-'.uniqid().'9',
            'status' => 'activo',
            'type' => 'particular',
        ]);

        $vet = VetAdmission::query()->create([
            'protocol_number' => VetAdmission::generateProtocolNumber(),
            'date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Luna',
            'owner_name' => 'Cliente Vet',
            'breed' => 'Mestizo',
            'age' => 3,
            'status' => 'pending',
            'external_equipment_sample_id' => $sampleId,
        ]);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vet->id,
            'test_id' => $test->id,
            'status' => 'pending',
        ]);

        return $vet;
    }

    private function mappingFor(string $equipmentName, Test $test): A25AnalyteMapping
    {
        $mapping = A25AnalyteMapping::query()->create([
            'equipment_analyte_name' => $equipmentName,
            'lab_branch_id' => null,
            'material_type' => 'SER',
        ]);
        $mapping->tests()->sync([$test->id => ['sort_order' => 0]]);

        return $mapping;
    }

    public function test_import_applies_to_clinical_admission_by_external_sample_id(): void
    {
        $test = $this->makeClinicalTest();
        $this->mappingFor('GOT-T', $test);
        $admission = $this->makeAdmissionWithSample('EXT-CLIN-1', $test);

        $parser = new A25ResultParser;
        $line = "EXT-CLIN-1\tGOT-T\tSER\t42\tU/L\t01/01/2026 10:00:00";
        $result = $parser->import($line, null);

        $this->assertSame(1, $result['ingested']);
        $this->assertSame(0, $result['rejected']);

        $row = AdmissionTest::query()->where('admission_id', $admission->id)->where('test_id', $test->id)->first();
        $this->assertSame('42', $row->result);
        $this->assertSame('U/L', $row->unit);
    }

    public function test_import_applies_to_veterinary_admission_by_external_sample_id(): void
    {
        $test = $this->makeClinicalTest();
        $this->mappingFor('GOT-T', $test);
        $vet = $this->makeVetAdmissionWithSample('EXT-VET-9', $test);

        $parser = new A25ResultParser;
        $line = "EXT-VET-9\tGOT-T\tSER\t33\tU/L\t01/01/2026 10:00:00";
        $result = $parser->import($line, null);

        $this->assertSame(1, $result['ingested']);

        $row = VetAdmissionTest::query()->where('vet_admission_id', $vet->id)->where('test_id', $test->id)->first();
        $this->assertSame('33', $row->result);
        $this->assertSame('U/L', $row->unit);
    }
}
