<?php

namespace Tests\Feature\Api\V1;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\ResultBatch;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use App\Services\Api\ApiResultIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultIngestionBatchTest extends TestCase
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
            'name' => 'LISCOM Test',
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

    private function makeTest(): Test
    {
        static $counter = 0;
        $counter++;

        return Test::query()->create([
            'code' => "T{$counter}",
            'name' => "Test {$counter}",
            'unit' => 'mg/dL',
            'price' => 500,
        ]);
    }

    private function makeAdmission(array $overrides = []): Admission
    {
        $patient = Patient::query()->create([
            'name' => 'Juan',
            'lastName' => 'Pérez',
            'patientId' => uniqid('DNI'),
            'type' => 'particular',
            'sex' => 'M',
            'birth' => '1980-01-01',
            'status' => 'activo',
        ]);

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

    private function makeAdmissionTest(Admission $admission, Test $test, array $overrides = []): AdmissionTest
    {
        return AdmissionTest::query()->create(array_merge([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 500,
            'authorization_status' => AdmissionTest::STATUS_PENDING,
        ], $overrides));
    }

    private function makeSample(array $overrides = []): Sample
    {
        $customer = Customer::query()->create([
            'name' => 'Cliente Test',
            'taxId' => '20-'.uniqid().'9',
            'status' => 'activo',
            'type' => 'particular',
        ]);

        return Sample::query()->create(array_merge([
            'protocol_number' => Sample::generateProtocolNumber(),
            'sample_type' => 'Agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Planta A',
            'batch' => 'L001',
            'product_name' => 'Agua tratada',
            'status' => 'pending',
            'lab_branch_id' => $this->branch->id,
        ], $overrides));
    }

    private function makeSampleDetermination(Sample $sample, Test $test, array $overrides = []): SampleDetermination
    {
        return SampleDetermination::query()->create(array_merge([
            'sample_id' => $sample->id,
            'test_id' => $test->id,
        ], $overrides));
    }

    private function makeVetAdmission(array $overrides = []): VetAdmission
    {
        $species = Species::query()->create(['name' => 'Canino', 'code' => uniqid('C'), 'is_active' => true]);
        $customer = Customer::query()->create([
            'name' => 'Dueño Test',
            'taxId' => '20-'.uniqid().'9',
            'status' => 'activo',
            'type' => 'particular',
        ]);

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

    private function makeVetAdmissionTest(VetAdmission $vet, Test $test, array $overrides = []): VetAdmissionTest
    {
        return VetAdmissionTest::query()->create(array_merge([
            'vet_admission_id' => $vet->id,
            'test_id' => $test->id,
        ], $overrides));
    }

    private function batchPayload(string $batchId, array $items): array
    {
        return ['batch_id' => $batchId, 'items' => $items];
    }

    private function uuid(): string
    {
        return (string) \Illuminate\Support\Str::uuid();
    }

    private function item(string $controlId, string $protocolNumber, int $testId, string $value = '95', array $overrides = []): array
    {
        return array_merge([
            'hl7_control_id' => $controlId,
            'protocol_number' => $protocolNumber,
            'equipment_name' => 'Cobas-411',
            'results' => [
                ['labit_test_id' => $testId, 'value' => $value, 'unit' => 'mg/dL', 'obx_index' => 0],
            ],
        ], $overrides);
    }

    // ─── Tests felices ────────────────────────────────────────────────────────

    public function test_ingesta_clinico_feliz(): void
    {
        $admission = $this->makeAdmission();
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-cli-1', $admission->protocol_number, $test->id, '90'),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.status', 'ingested')
            ->assertJsonPath('items.0.results.0.status', ApiResultIngestionService::STATUS_INGESTED);

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'result' => '90',
        ]);
    }

    public function test_ingesta_muestras_feliz(): void
    {
        $sample = $this->makeSample();
        $test = $this->makeTest();
        $this->makeSampleDetermination($sample, $test);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-sam-1', $sample->protocol_number, $test->id, '5.5'),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.status', 'ingested');

        $this->assertDatabaseHas('sample_determinations', [
            'sample_id' => $sample->id,
            'test_id' => $test->id,
            'result' => '5.5',
        ]);
    }

    public function test_ingesta_vet_feliz(): void
    {
        $vet = $this->makeVetAdmission();
        $test = $this->makeTest();
        $this->makeVetAdmissionTest($vet, $test);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-vet-1', $vet->protocol_number, $test->id, '12.3'),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.status', 'ingested');

        $this->assertDatabaseHas('vet_admission_tests', [
            'vet_admission_id' => $vet->id,
            'test_id' => $test->id,
            'result' => '12.3',
        ]);
    }

    // ─── Overwrite ───────────────────────────────────────────────────────────

    public function test_overwrite_si_no_validado(): void
    {
        $admission = $this->makeAdmission();
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test, ['result' => '99', 'is_validated' => false]);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-ow-1', $admission->protocol_number, $test->id, '95'),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.results.0.status', ApiResultIngestionService::STATUS_OVERWRITTEN)
            ->assertJsonPath('items.0.results.0.previous_value', '99');

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'result' => '95',
        ]);
    }

    // ─── ALREADY_VALIDATED (regla crítica) ───────────────────────────────────

    public function test_rechazo_si_ya_validado_por_bioquimico(): void
    {
        $user = User::factory()->create();
        $admission = $this->makeAdmission();
        $test = $this->makeTest();
        $admTest = $this->makeAdmissionTest($admission, $test, [
            'result' => '99',
            'is_validated' => true,
            'validated_by' => $user->id,
            'validated_at' => now()->subHour(),
        ]);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-av-1', $admission->protocol_number, $test->id, '80'),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.results.0.status', ApiResultIngestionService::STATUS_REJECTED)
            ->assertJsonPath('items.0.results.0.reason', ApiResultIngestionService::REASON_ALREADY_VALIDATED)
            ->assertJsonPath('items.0.results.0.validated_by_name', $user->name);

        // El valor NO debe haber cambiado
        $this->assertDatabaseHas('admission_tests', [
            'id' => $admTest->id,
            'result' => '99',
        ]);
    }

    // ─── Idempotencia ────────────────────────────────────────────────────────

    public function test_idempotencia_batch_id_duplicado(): void
    {
        $admission = $this->makeAdmission();
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test);

        $batchUuid = $this->uuid();
        $payload = $this->batchPayload($batchUuid, [
            $this->item('ctrl-idem-1', $admission->protocol_number, $test->id),
        ]);

        $this->withHeaders($this->authHeaders())->postJson('/api/v1/results/batch', $payload);
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('result_batches', 1);
    }

    public function test_idempotencia_message_control_id_duplicado(): void
    {
        $admission = $this->makeAdmission();
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test);

        // Primer batch
        $payload1 = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-shared', $admission->protocol_number, $test->id, '90'),
        ]);
        $this->withHeaders($this->authHeaders())->postJson('/api/v1/results/batch', $payload1);

        // Segundo batch: mismo hl7_control_id + equipo + protocolo → se vuelve a aplicar (nuevo batch_id)
        $payload2 = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-shared', $admission->protocol_number, $test->id, '55'),
        ]);
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/results/batch', $payload2);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.status', ApiResultIngestionService::STATUS_INGESTED);

        // Segundo envío (mismo hl7_control_id + equipo + protocolo) debe sobrescribir si no está validado
        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'result' => '55',
        ]);
    }

    /**
     * Un protocolo con resultados de dos equipos distintos (mismo hl7_control_id derivado del
     * número de protocolo) debe procesar ambos mensajes de forma independiente.
     * Escenario real: CONTADOR BC-780 envía hemograma, DIRUI CST240 envía bioquímica.
     */
    public function test_multi_equipo_mismo_protocolo_mismo_control_id(): void
    {
        $admission = $this->makeAdmission();

        // BC-780 tiene test de hemograma
        $testHemograma = $this->makeTest();
        $this->makeAdmissionTest($admission, $testHemograma);

        // DIRUI CST240 tiene test de bioquímica
        $testBioquimica = $this->makeTest();
        $this->makeAdmissionTest($admission, $testBioquimica);

        // Ambos equipos usan el mismo hl7_control_id (MSH-10 = número de protocolo)
        $sharedControlId = $admission->protocol_number;

        // 1) BC-780 envía primero
        $payload1 = $this->batchPayload($this->uuid(), [
            array_merge($this->item($sharedControlId, $admission->protocol_number, $testHemograma->id, '14.5'), [
                'equipment_name' => 'CONTADOR BC-780',
            ]),
        ]);

        $response1 = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload1);

        $response1->assertStatus(200)
            ->assertJsonPath('items.0.status', 'ingested');

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $testHemograma->id,
            'result' => '14.5',
        ]);

        // 2) DIRUI CST240 envía después — mismo hl7_control_id, equipo diferente → NO debe ser duplicate
        $payload2 = $this->batchPayload($this->uuid(), [
            array_merge($this->item($sharedControlId, $admission->protocol_number, $testBioquimica->id, '0.74'), [
                'equipment_name' => 'DIRUI CST240',
            ]),
        ]);

        $response2 = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload2);

        $response2->assertStatus(200)
            ->assertJsonPath('items.0.status', 'ingested');

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $testBioquimica->id,
            'result' => '0.74',
        ]);

        // 3) Reintento con el mismo batch_id → idempotencia a nivel batch (no re-aplica)
        $response3 = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload2);

        $response3->assertStatus(200)
            ->assertJsonPath('duplicate', true);
    }

    /**
     * LISCOM resetea hl7_control_id a '1' en cada sesión.
     * DIRUI CST240 envía resultados para protocolo A con hl7='1' → ingested.
     * DIRUI CST240 envía resultados para protocolo B con hl7='1' → debe ser ingested también
     * (diferente protocolo = diferente mensaje, aunque mismo hl7_control_id y mismo equipo).
     */
    public function test_mismo_equipo_diferente_protocolo_mismo_control_id_no_es_duplicate(): void
    {
        $controlId = '1'; // LISCOM resetea a '1' en cada sesión

        // Protocolo A — DIRUI envía y es ingested
        $admA = $this->makeAdmission();
        $testA = $this->makeTest();
        $this->makeAdmissionTest($admA, $testA);

        $payloadA = $this->batchPayload($this->uuid(), [
            array_merge($this->item($controlId, $admA->protocol_number, $testA->id, '0.74'), [
                'equipment_name' => 'DIRUI CST240',
            ]),
        ]);
        $this->withHeaders($this->authHeaders())->postJson('/api/v1/results/batch', $payloadA)
            ->assertJsonPath('items.0.status', 'ingested');

        // Protocolo B — DIRUI envía con mismo hl7='1' → debe ser ingested, NO duplicate
        $admB = $this->makeAdmission();
        $testB = $this->makeTest();
        $this->makeAdmissionTest($admB, $testB);

        $payloadB = $this->batchPayload($this->uuid(), [
            array_merge($this->item($controlId, $admB->protocol_number, $testB->id, '1.23'), [
                'equipment_name' => 'DIRUI CST240',
            ]),
        ]);
        $responseB = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payloadB);

        $responseB->assertStatus(200)
            ->assertJsonPath('items.0.status', 'ingested');

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admB->id,
            'test_id' => $testB->id,
            'result' => '1.23',
        ]);

        // Reintento HTTP con el mismo batch_id → idempotencia a nivel batch
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payloadB)
            ->assertJsonPath('duplicate', true);
    }

    // ─── Casos de error ──────────────────────────────────────────────────────

    public function test_protocolo_inexistente(): void
    {
        $test = $this->makeTest();

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-notfound-1', 'C9999999999', $test->id),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.status', ApiResultIngestionService::STATUS_REJECTED)
            ->assertJsonPath('items.0.reason', ApiResultIngestionService::REASON_PROTOCOL_NOT_FOUND);
    }

    public function test_protocolo_de_otra_sede(): void
    {
        $admission = $this->makeAdmission(['lab_branch_id' => $this->otherBranch->id]);
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-branch-1', $admission->protocol_number, $test->id),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.status', ApiResultIngestionService::STATUS_REJECTED)
            ->assertJsonPath('items.0.reason', ApiResultIngestionService::REASON_PROTOCOL_OUT_OF_BRANCH);
    }

    public function test_test_id_no_existe_en_protocolo(): void
    {
        $admission = $this->makeAdmission();
        $testInProtocol = $this->makeTest();
        $this->makeAdmissionTest($admission, $testInProtocol);

        $testNotInProtocol = $this->makeTest();

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-notdet-1', $admission->protocol_number, $testNotInProtocol->id),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.results.0.status', ApiResultIngestionService::STATUS_REJECTED)
            ->assertJsonPath('items.0.results.0.reason', ApiResultIngestionService::REASON_DETERMINATION_NOT_FOUND);
    }

    public function test_test_id_no_existe_en_bd(): void
    {
        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-422-1', 'C-2026-001', 99999),
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload)
            ->assertStatus(422);
    }

    public function test_payload_excede500_items(): void
    {
        $test = $this->makeTest();
        $admission = $this->makeAdmission();
        $items = [];
        for ($i = 0; $i < 501; $i++) {
            $items[] = $this->item("ctrl-big-{$i}", $admission->protocol_number, $test->id);
        }

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $this->batchPayload($this->uuid(), $items))
            ->assertStatus(422);
    }

    public function test_sin_api_key(): void
    {
        $this->postJson('/api/v1/results/batch', $this->batchPayload($this->uuid(), []))
            ->assertStatus(401);
    }

    // ─── Key global (v1.76.2) ────────────────────────────────────────────────

    public function test_global_key_ingesta_protocolo_de_otra_sede(): void
    {
        $globalPlain = ApiClient::generateKey();
        ApiClient::query()->create([
            'name' => 'LISCOM Global',
            'api_key_hash' => ApiClient::hashKey($globalPlain),
            'key_preview' => ApiClient::buildPreview($globalPlain),
            'lab_branch_id' => null,
            'company_id' => $this->company->id,
            'active' => true,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);

        // Protocolo en otherBranch — distinta a la de la key (que es null = global)
        $admission = $this->makeAdmission(['lab_branch_id' => $this->otherBranch->id]);
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-global-1', $admission->protocol_number, $test->id, '7.2'),
        ]);

        $this->withHeaders(['X-API-Key' => $globalPlain])
            ->postJson('/api/v1/results/batch', $payload)
            ->assertStatus(200)
            ->assertJsonPath('items.0.status', ApiResultIngestionService::STATUS_INGESTED);

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'result' => '7.2',
        ]);
    }

    public function test_local_key_sigue_rechazando_protocolo_de_otra_sede(): void
    {
        // La key del setUp ya tiene lab_branch_id = $this->branch
        $admission = $this->makeAdmission(['lab_branch_id' => $this->otherBranch->id]);
        $test = $this->makeTest();
        $this->makeAdmissionTest($admission, $test);

        $payload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-local-branch-1', $admission->protocol_number, $test->id),
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload)
            ->assertStatus(200)
            ->assertJsonPath('items.0.status', ApiResultIngestionService::STATUS_REJECTED)
            ->assertJsonPath('items.0.reason', ApiResultIngestionService::REASON_PROTOCOL_OUT_OF_BRANCH);
    }

    public function test_api_key_inactiva(): void
    {
        $plain = ApiClient::generateKey();
        ApiClient::query()->create([
            'name' => 'LISCOM Inactivo',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => false,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);

        $test = $this->makeTest();
        $admission = $this->makeAdmission();

        $this->withHeaders(['X-API-Key' => $plain])
            ->postJson('/api/v1/results/batch', $this->batchPayload($this->uuid(), [
                $this->item('ctrl-inactive-1', $admission->protocol_number, $test->id),
            ]))
            ->assertStatus(401);
    }

    // ─── Batch mixto ─────────────────────────────────────────────────────────

    public function test_mixed_batch(): void
    {
        $user = User::factory()->create();

        // Mensaje 1: protocolo clínico, 2 OBX → todos OK
        $adm1 = $this->makeAdmission();
        $t1a = $this->makeTest();
        $t1b = $this->makeTest();
        $this->makeAdmissionTest($adm1, $t1a);
        $this->makeAdmissionTest($adm1, $t1b);

        // Mensaje 2: 2 OBX — uno OK, uno ya validado → partial
        $adm2 = $this->makeAdmission();
        $t2a = $this->makeTest();
        $t2b = $this->makeTest();
        $this->makeAdmissionTest($adm2, $t2a);
        $this->makeAdmissionTest($adm2, $t2b, [
            'result' => '50', 'is_validated' => true,
            'validated_by' => $user->id, 'validated_at' => now(),
        ]);

        // Mensaje 3: protocolo inexistente → rejected
        $t3 = $this->makeTest();

        // Mensaje 4: control_id duplicado (hay que enviar un batch previo primero)
        $adm4 = $this->makeAdmission();
        $t4 = $this->makeTest();
        $this->makeAdmissionTest($adm4, $t4);
        $prevPayload = $this->batchPayload($this->uuid(), [
            $this->item('ctrl-dup-mixed', $adm4->protocol_number, $t4->id, '10'),
        ]);
        $this->withHeaders($this->authHeaders())->postJson('/api/v1/results/batch', $prevPayload);

        $mainBatchId = $this->uuid();

        // Batch principal (4 mensajes)
        $payload = $this->batchPayload($mainBatchId, [
            // msg 1: dos OBX, ambos OK
            [
                'hl7_control_id' => 'ctrl-mix-1',
                'protocol_number' => $adm1->protocol_number,
                'equipment_name' => 'Cobas',
                'results' => [
                    ['labit_test_id' => $t1a->id, 'value' => '10', 'unit' => 'mg/dL', 'obx_index' => 0],
                    ['labit_test_id' => $t1b->id, 'value' => '20', 'unit' => 'mg/dL', 'obx_index' => 1],
                ],
            ],
            // msg 2: un OBX OK, otro ya validado → partial
            [
                'hl7_control_id' => 'ctrl-mix-2',
                'protocol_number' => $adm2->protocol_number,
                'equipment_name' => 'Cobas',
                'results' => [
                    ['labit_test_id' => $t2a->id, 'value' => '30', 'unit' => 'mg/dL', 'obx_index' => 0],
                    ['labit_test_id' => $t2b->id, 'value' => '99', 'unit' => 'mg/dL', 'obx_index' => 1],
                ],
            ],
            // msg 3: protocolo no existe
            $this->item('ctrl-mix-3', 'C9999000000', $t3->id, '0'),
            // msg 4: control_id duplicado
            $this->item('ctrl-dup-mixed', $adm4->protocol_number, $t4->id, '99'),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload);

        $response->assertStatus(200);

        $items = $response->json('items');
        $this->assertSame('ingested', $items[0]['status']);
        $this->assertSame('partial', $items[1]['status']);
        $this->assertSame(ApiResultIngestionService::STATUS_REJECTED, $items[2]['status']);
        $this->assertSame(ApiResultIngestionService::STATUS_INGESTED, $items[3]['status']);

        // Contadores del batch
        $batch = ResultBatch::where('external_batch_id', $mainBatchId)->first();
        $this->assertSame(3, $batch->items_ingested); // msg1 + msg2 (partial) + msg4 reenvío con mismo control_id
        $this->assertSame(1, $batch->items_rejected);
        $this->assertSame(0, $batch->items_duplicate);
    }

    public function test_unidad_de_bd_no_se_sobreescribe_con_la_del_equipo(): void
    {
        // admission_tests.unit comienza en null.
        // LISCOM envía unit: 'mg/dL'. La columna unit NO debe ser tocada por la ingesta.
        // La unidad real se obtiene de tests.unit (catálogo), no de admission_tests.unit.
        $admission = $this->makeAdmission();
        $test = Test::query()->create([
            'code' => 'T_UNIT',
            'name' => 'Test Unidad',
            'unit' => 'ng/mL',
            'price' => 500,
        ]);
        $admTest = $this->makeAdmissionTest($admission, $test);

        $payload = $this->batchPayload($this->uuid(), [
            [
                'hl7_control_id' => 'ctrl-unit-1',
                'protocol_number' => $admission->protocol_number,
                'equipment_name' => 'Cobas',
                'results' => [
                    ['labit_test_id' => $test->id, 'value' => '1.50', 'unit' => 'mg/dL', 'obx_index' => 0],
                ],
            ],
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/results/batch', $payload)
            ->assertStatus(200)
            ->assertJsonPath('items.0.results.0.status', ApiResultIngestionService::STATUS_INGESTED);

        $admTest->refresh();
        $this->assertSame('1.50', $admTest->result);
        // El accessor siempre devuelve la unidad del catálogo, no la del equipo.
        $this->assertSame('ng/mL', $admTest->unit, 'La unidad visible debe ser la del catálogo (tests.unit), nunca la del equipo LISCOM');
        // Verificar que la columna raw en la BD sigue en NULL (no fue sobreescrita).
        $this->assertNull($admTest->getRawOriginal('unit'), 'La columna unit en BD no debe contener datos del equipo');
    }
}
