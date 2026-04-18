<?php

namespace Tests\Feature\Console;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\ResultBatch;
use App\Models\ResultIngestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCleanupTest extends TestCase
{
    use RefreshDatabase;

    private ApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = LabBranch::create(['name' => 'Sede Test', 'is_central' => true, 'is_active' => true]);
        $company = Company::create(['name' => 'Test', 'cuit' => '20-77777777-7', 'tax_condition' => 'responsable_inscripto', 'is_active' => true]);
        $plain = ApiClient::generateKey();
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $branch->id,
            'company_id' => $company->id,
            'active' => true,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);
    }

    private function makeBatch(array $overrides = []): ResultBatch
    {
        $batch = ResultBatch::create(array_merge([
            'api_client_id' => $this->apiClient->id,
            'external_batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'source_app' => 'LISCOM',
            'items_total' => 1,
            'items_ingested' => 1,
            'items_overwritten' => 0,
            'items_rejected' => 0,
            'items_duplicate' => 0,
        ], $overrides));

        return $batch;
    }

    private function makeIngestion(ResultBatch $batch, array $overrides = []): ResultIngestion
    {
        return ResultIngestion::create(array_merge([
            'result_batch_id' => $batch->id,
            'api_client_id' => $this->apiClient->id,
            'hl7_control_id' => 'ctrl-'.uniqid(),
            'protocol_number' => 'C0000000001',
            'status' => 'ingested',
            'items_summary' => [],
        ], $overrides));
    }

    public function test_cleanup_borra_solo_registros_viejos(): void
    {
        $batchViejo = $this->makeBatch();
        $batchViejo->created_at = now()->subDays(200);
        $batchViejo->save();

        $ingVieja = $this->makeIngestion($batchViejo);
        $ingVieja->created_at = now()->subDays(200);
        $ingVieja->save();

        $batchNuevo = $this->makeBatch();
        $ingNueva = $this->makeIngestion($batchNuevo);

        $this->artisan('api:cleanup', ['--days' => 90, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('result_batches', ['id' => $batchViejo->id]);
        $this->assertDatabaseMissing('result_ingestions', ['id' => $ingVieja->id]);
        $this->assertDatabaseHas('result_batches', ['id' => $batchNuevo->id]);
        $this->assertDatabaseHas('result_ingestions', ['id' => $ingNueva->id]);
    }

    public function test_cleanup_dry_run_no_borra_nada(): void
    {
        $batch = $this->makeBatch();
        $batch->created_at = now()->subDays(200);
        $batch->save();

        $ing = $this->makeIngestion($batch);
        $ing->created_at = now()->subDays(200);
        $ing->save();

        $this->artisan('api:cleanup', ['--days' => 90, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('result_batches', ['id' => $batch->id]);
        $this->assertDatabaseHas('result_ingestions', ['id' => $ing->id]);
    }

    public function test_cleanup_respeta_config_log_retention_days(): void
    {
        config(['api.log_retention_days' => 60]);

        $batch60dias = $this->makeBatch();
        $batch60dias->created_at = now()->subDays(70);
        $batch60dias->save();

        $batchReciente = $this->makeBatch();

        $this->artisan('api:cleanup', ['--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('result_batches', ['id' => $batch60dias->id]);
        $this->assertDatabaseHas('result_batches', ['id' => $batchReciente->id]);
    }
}
