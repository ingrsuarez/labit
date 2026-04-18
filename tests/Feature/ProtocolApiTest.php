<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LabBranch;
use App\Models\Material;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Species;
use App\Models\Test;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolApiTest extends TestCase
{
    use RefreshDatabase;

    private LabBranch $branch;
    private LabBranch $otherBranch;
    private Company $company;
    private ApiClient $client;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = LabBranch::query()->create([
            'name' => 'Sede Centro',
            'is_central' => true,
            'is_active' => true,
        ]);

        $this->otherBranch = LabBranch::query()->create([
            'name' => 'Sede Norte',
            'is_central' => false,
            'is_active' => true,
        ]);

        $this->company = Company::query()->create([
            'name' => 'Laboratorio Test',
            'cuit' => '20-99999999-9',
            'tax_condition' => 'responsable_inscripto',
            'is_active' => true,
        ]);

        $plain = ApiClient::generateKey();
        $this->apiKey = $plain;
        $this->client = ApiClient::query()->create([
            'name' => 'LISCOM Sede Centro',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);
    }

    private function authHeaders(): array
    {
        return ['X-API-Key' => $this->apiKey];
    }

    private function makePatient(array $overrides = []): Patient
    {
        return Patient::query()->create(array_merge([
            'name' => 'Juan',
            'lastName' => 'Pérez',
            'patientId' => '12345678',
            'type' => 'particular',
            'sex' => 'M',
            'birth' => '1980-01-01',
            'status' => 'activo',
        ], $overrides));
    }

    private function makeMaterial(): Material
    {
        return Material::query()->create([
            'code' => 'SUE',
            'name' => 'Suero',
            'is_active' => true,
        ]);
    }

    private function makeTest(?int $materialId = null): Test
    {
        return Test::query()->create([
            'code' => 'GLU',
            'name' => 'Glucemia',
            'unit' => 'mg/dl',
            'material' => $materialId,
            'price' => 1000,
        ]);
    }

    private function makeAdmission(array $overrides = []): Admission
    {
        $patient = $this->makePatient();

        $protocol = Admission::generateProtocolNumber();

        return Admission::query()->create(array_merge([
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
            'lab_branch_id' => $this->branch->id,
            'status' => Admission::STATUS_PENDING,
        ], $overrides));
    }

    private function makeAdmissionTest(Admission $admission, ?Test $test = null): AdmissionTest
    {
        $test ??= $this->makeTest($this->makeMaterial()->id);

        return AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 1000,
            'authorization_status' => AdmissionTest::STATUS_PENDING,
        ]);
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Cliente Test',
            'taxId' => '20-12345678-9',
            'status' => 'activo',
            'type' => 'particular',
        ], $overrides));
    }

    private function makeSample(array $overrides = []): Sample
    {
        $customer = $overrides['customer_id'] ?? $this->makeCustomer()->id;

        return Sample::query()->create(array_merge([
            'protocol_number' => Sample::generateProtocolNumber(),
            'sample_type' => 'Agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer,
            'location' => 'Planta A',
            'batch' => 'L001',
            'product_name' => 'Agua tratada',
            'status' => 'pending',
            'lab_branch_id' => $this->branch->id,
        ], $overrides));
    }

    private function makeVetAdmission(array $overrides = []): VetAdmission
    {
        $species = Species::query()->create(['name' => 'Canino', 'code' => 'CAN', 'is_active' => true]);
        $customer = $this->makeCustomer(['name' => 'Dueño Test', 'taxId' => '20-87654321-9']);

        return VetAdmission::query()->create(array_merge([
            'protocol_number' => VetAdmission::generateProtocolNumber(),
            'date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Firulais',
            'owner_name' => 'Dueño Test',
            'breed' => 'Labrador',
            'age' => 5,
            'status' => 'pending',
            'lab_branch_id' => $this->branch->id,
        ], $overrides));
    }

    public function test_index_requires_api_key(): void
    {
        $this->getJson('/api/v1/protocols')
            ->assertStatus(401)
            ->assertJsonPath('code', 'API_KEY_MISSING');
    }

    public function test_index_returns_clinical_protocols_for_today(): void
    {
        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'clinical')
            ->assertJsonPath('data.0.protocol_number', $admission->protocol_number)
            ->assertJsonPath('data.0.barcode', $admission->protocol_number)
            ->assertJsonPath('data.0.lab_branch.id', $this->branch->id);
    }

    public function test_index_merges_three_protocol_types(): void
    {
        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);
        $this->makeSample();
        $this->makeVetAdmission();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols?status=*');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);

        $types = collect($response->json('data'))->pluck('type')->sort()->values()->all();
        $this->assertSame(['clinical', 'sample', 'vet'], $types);
    }

    public function test_index_filters_by_type(): void
    {
        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);
        $this->makeSample();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols?type=sample&status=*');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'sample');
    }

    public function test_index_does_not_leak_other_branch_protocols(): void
    {
        $foreignPatient = $this->makePatient(['patientId' => '99999999']);
        Admission::query()->create([
            'date' => today()->toDateString(),
            'number' => 'C260418999',
            'protocol_number' => 'C260418999',
            'patient_id' => $foreignPatient->id,
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
            'lab_branch_id' => $this->otherBranch->id,
            'status' => Admission::STATUS_PENDING,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols?status=*');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_show_by_barcode_returns_protocol(): void
    {
        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/'.$admission->protocol_number)
            ->assertOk()
            ->assertJsonPath('data.protocol_number', $admission->protocol_number)
            ->assertJsonPath('data.type', 'clinical');
    }

    public function test_show_by_barcode_returns_404_when_unknown(): void
    {
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/CXX999999')
            ->assertStatus(404);
    }

    public function test_show_by_barcode_blocks_other_branch_protocol(): void
    {
        $foreignPatient = $this->makePatient(['patientId' => '99999999']);
        $proto = Admission::generateProtocolNumber();
        $foreign = Admission::query()->create([
            'date' => today()->toDateString(),
            'number' => $proto,
            'protocol_number' => $proto,
            'patient_id' => $foreignPatient->id,
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
            'lab_branch_id' => $this->otherBranch->id,
            'status' => Admission::STATUS_PENDING,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/'.$foreign->protocol_number)
            ->assertStatus(404);
    }

    public function test_show_by_type_and_id_returns_protocol(): void
    {
        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/protocols/clinical/{$admission->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $admission->id);
    }

    public function test_show_returns_404_for_invalid_type(): void
    {
        // La ruta restringe `{type}` a clinical|sample|vet via whereIn,
        // por lo que valores inválidos caen en 404 a nivel router (antes
        // de ejecutar el controller). Esto es OK semánticamente y evita
        // exponer detalles internos del enum a integraciones externas.
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/invalid/1')
            ->assertStatus(404);
    }

    public function test_show_blocks_other_branch_id(): void
    {
        $foreignPatient = $this->makePatient(['patientId' => '99999999']);
        $proto = Admission::generateProtocolNumber();
        $foreign = Admission::query()->create([
            'date' => today()->toDateString(),
            'number' => $proto,
            'protocol_number' => $proto,
            'patient_id' => $foreignPatient->id,
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
            'lab_branch_id' => $this->otherBranch->id,
            'status' => Admission::STATUS_PENDING,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/protocols/clinical/{$foreign->id}")
            ->assertStatus(404);
    }

    public function test_pii_minimal_omits_dni(): void
    {
        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/'.$admission->protocol_number);

        $response->assertOk()
            ->assertJsonPath('data.patient.document', null);
    }

    public function test_pii_standard_includes_dni(): void
    {
        $this->client->update(['patient_data_level' => ApiClient::LEVEL_STANDARD]);

        $admission = $this->makeAdmission();
        $this->makeAdmissionTest($admission);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/'.$admission->protocol_number);

        $response->assertOk()
            ->assertJsonPath('data.patient.document', '12345678');
    }

    public function test_determination_resource_includes_test_and_material(): void
    {
        $admission = $this->makeAdmission();
        $material = $this->makeMaterial();
        $test = $this->makeTest($material->id);
        $this->makeAdmissionTest($admission, $test);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/'.$admission->protocol_number);

        $response->assertOk()
            ->assertJsonPath('data.determinations.0.test_name', 'Glucemia')
            ->assertJsonPath('data.determinations.0.material.abbreviation', 'SUE')
            ->assertJsonPath('data.determinations.0.status', 'pending');
    }

    public function test_status_resolves_to_validated_when_test_validated(): void
    {
        $admission = $this->makeAdmission();
        $material = $this->makeMaterial();
        $test = $this->makeTest($material->id);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 1000,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
            'result' => '95',
            'is_validated' => true,
            'validated_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/protocols/by-barcode/'.$admission->protocol_number);

        $response->assertOk()
            ->assertJsonPath('data.determinations.0.status', 'validated')
            ->assertJsonPath('data.determinations.0.has_result', true);
    }
}
