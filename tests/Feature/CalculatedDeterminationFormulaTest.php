<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Services\Api\ApiResultIngestionService;
use App\Support\TestFormulaEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CalculatedDeterminationFormulaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeTest(array $overrides = []): Test
    {
        return Test::query()->create(array_merge([
            'code' => 'T'.Str::upper(Str::random(4)),
            'name' => 'prueba',
            'decimals' => 2,
            'price' => 0,
            'cost' => 0,
            'formula' => null,
            'categories' => ['clinico'],
        ], $overrides));
    }

    public function test_nomenclator_stores_formula_definition(): void
    {
        Permission::findOrCreate('test.store');
        $user = User::factory()->create();
        $user->givePermissionTo('test.store');

        $col = $this->makeTest(['code' => 'COL-T', 'name' => 'colesterol total']);
        $hdl = $this->makeTest(['code' => 'HDL', 'name' => 'hdl']);

        $payload = [
            'code' => 'CAST',
            'name' => 'indice castelli',
            'decimals' => 2,
            'formula_enabled' => '1',
            'formula_json' => json_encode([
                'tokens' => [
                    ['type' => 'test', 'test_id' => $col->id, 'code' => 'COL-T', 'name' => 'colesterol total'],
                    ['type' => 'op', 'value' => '/'],
                    ['type' => 'test', 'test_id' => $hdl->id, 'code' => 'HDL', 'name' => 'hdl'],
                ],
            ]),
            'categories' => ['clinico'],
        ];

        $response = $this->actingAs($user)->post(route('test.store'), $payload);
        $response->assertRedirect();

        $castelli = Test::where('code', 'CAST')->first();
        $this->assertNotNull($castelli);
        $this->assertTrue($castelli->hasFormula());
        $this->assertSame('colesterol total ÷ hdl', $castelli->formulaDisplay());
    }

    public function test_api_ingestion_rejects_formula_test(): void
    {
        $col = $this->makeTest(['code' => 'COL-A']);
        $castelli = $this->makeTest([
            'code' => 'CAST-A',
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $col->id],
                ],
            ],
        ]);

        $user = User::factory()->create();
        $patient = Patient::query()->create([
            'name' => 'P',
            'lastName' => 'T',
            'patientId' => '123',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-2026-FORM01',
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->toDateString(),
            'promise_date' => now()->toDateString(),
            'authorization_code' => '',
            'attended_by' => $user->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $castelli->id,
            'price' => 0,
            'result' => null,
            'is_validated' => false,
        ]);

        $company = Company::query()->create([
            'name' => 'Lab Test',
            'cuit' => '20-11111111-1',
            'tax_condition' => 'responsable_inscripto',
            'is_active' => true,
        ]);

        $plain = ApiClient::generateKey();
        $client = ApiClient::query()->create([
            'name' => 'LISCOM Formula Test',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => null,
            'company_id' => $company->id,
            'active' => true,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);
        $service = app(ApiResultIngestionService::class);

        $result = $service->process($client, [
            'batch_id' => 'batch-formula-1',
            'items' => [[
                'protocol_number' => $admission->protocol_number,
                'hl7_control_id' => 'hl7-formula-1',
                'results' => [[
                    'obx_index' => 1,
                    'labit_test_id' => $castelli->id,
                    'value' => '99',
                ]],
            ]],
        ]);

        $this->assertSame(
            ApiResultIngestionService::REASON_FORMULA_CALCULATED,
            $result['items'][0]['results'][0]['reason']
        );

        $this->assertNull(
            AdmissionTest::where('admission_id', $admission->id)->where('test_id', $castelli->id)->value('result')
        );
    }

    public function test_evaluator_recalculates_when_operands_change(): void
    {
        $col = $this->makeTest();
        $hdl = $this->makeTest();
        $castelli = $this->makeTest([
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $col->id],
                    ['type' => 'op', 'value' => '/'],
                    ['type' => 'test', 'test_id' => $hdl->id],
                ],
            ],
        ]);

        $evaluator = new TestFormulaEvaluator;

        $first = $evaluator->evaluateForTest($castelli, [
            $col->id => '200',
            $hdl->id => '50',
        ]);
        $second = $evaluator->evaluateForTest($castelli, [
            $col->id => '180',
            $hdl->id => '60',
        ]);

        $this->assertSame('4.00', $first);
        $this->assertSame('3.00', $second);
    }
}
