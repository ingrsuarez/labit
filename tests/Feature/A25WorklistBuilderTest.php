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
use App\Services\A25\A25WorklistBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class A25WorklistBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_for_vet_admissions_includes_mapped_pending_tests(): void
    {
        $test = Test::query()->create([
            'code' => 'WV1',
            'name' => 'West Vet 1',
            'unit' => 'U/L',
            'price' => 100,
        ]);

        $mapping = A25AnalyteMapping::query()->create([
            'equipment_analyte_name' => 'West-A',
            'lab_branch_id' => null,
            'material_type' => 'SER',
        ]);
        $mapping->tests()->sync([$test->id => ['sort_order' => 0]]);

        $species = Species::query()->create(['name' => 'Felino', 'code' => uniqid('F'), 'is_active' => true]);
        $customer = Customer::query()->create([
            'name' => 'Tutor',
            'taxId' => '20-'.uniqid().'9',
            'status' => 'activo',
            'type' => 'particular',
        ]);

        $vet = VetAdmission::query()->create([
            'protocol_number' => 'V-WL-100',
            'date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Michi',
            'owner_name' => 'Tutor',
            'breed' => 'SRD',
            'age' => 4,
            'status' => 'pending',
        ]);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vet->id,
            'test_id' => $test->id,
            'status' => 'pending',
        ]);

        $vet->load('vetTests.test');

        $builder = new A25WorklistBuilder;
        $result = $builder->buildForVetAdmissions(new Collection([$vet]), null);

        $this->assertSame(1, $result['lines']);
        $this->assertStringContainsString('V-WL-100', $result['content']);
        $this->assertStringContainsString('West-A', $result['content']);
    }

    public function test_build_combined_clinical_and_veterinary(): void
    {
        $t1 = Test::query()->create(['code' => 'C1', 'name' => 'Clinical T', 'unit' => 'mg/dL', 'price' => 50]);
        $t2 = Test::query()->create(['code' => 'V2', 'name' => 'Vet T', 'unit' => 'mg/dL', 'price' => 50]);

        $m1 = A25AnalyteMapping::query()->create(['equipment_analyte_name' => 'EQ-CL', 'lab_branch_id' => null, 'material_type' => 'SER']);
        $m1->tests()->sync([$t1->id => ['sort_order' => 0]]);
        $m2 = A25AnalyteMapping::query()->create(['equipment_analyte_name' => 'EQ-VT', 'lab_branch_id' => null, 'material_type' => 'SER']);
        $m2->tests()->sync([$t2->id => ['sort_order' => 0]]);

        $patient = Patient::query()->create([
            'name' => 'Ana', 'lastName' => 'L.', 'patientId' => uniqid('D'),
            'type' => 'particular', 'sex' => 'F', 'birth' => '1990-01-01', 'status' => 'activo',
        ]);
        $protocol = Admission::generateProtocolNumber();
        $admission = Admission::query()->create([
            'date' => today()->toDateString(),
            'number' => $protocol,
            'protocol_number' => $protocol,
            'patient_id' => $patient->id,
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
            'status' => Admission::STATUS_PENDING,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $t1->id,
            'price' => 50,
            'authorization_status' => AdmissionTest::STATUS_PENDING,
        ]);
        $admission->load('admissionTests.test');

        $species = Species::query()->create(['name' => 'Reptil', 'code' => uniqid('R'), 'is_active' => true]);
        $customer = Customer::query()->create([
            'name' => 'Dueño', 'taxId' => '20-'.uniqid().'9', 'status' => 'activo', 'type' => 'particular',
        ]);
        $vet = VetAdmission::query()->create([
            'protocol_number' => 'V-COMB-200',
            'date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Igu',
            'owner_name' => 'Dueño',
            'breed' => '—',
            'age' => 1,
            'status' => 'pending',
        ]);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vet->id,
            'test_id' => $t2->id,
            'status' => 'pending',
        ]);
        $vet->load('vetTests.test');

        $builder = new A25WorklistBuilder;
        $result = $builder->buildCombined(new Collection([$admission]), new Collection([$vet]), null);

        $this->assertSame(2, $result['lines']);
        $this->assertStringContainsString('EQ-CL', $result['content']);
        $this->assertStringContainsString('EQ-VT', $result['content']);
    }
}
