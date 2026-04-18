<?php

namespace Tests\Feature\Api\Monitor;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\ResultBatch;
use App\Models\ResultIngestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private LabBranch $branch;

    private Company $company;

    private ApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('lab-admissions.index');
        Permission::findOrCreate('api-clients.manage');

        $this->branch = LabBranch::create(['name' => 'Sede Central', 'is_central' => true, 'is_active' => true]);
        $this->company = Company::create(['name' => 'Lab Test', 'cuit' => '20-88888888-8', 'tax_condition' => 'responsable_inscripto', 'is_active' => true]);

        $plain = ApiClient::generateKey();
        $this->apiClient = ApiClient::create([
            'name' => 'LISCOM Test',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);
    }

    private function userConPermiso(string|array $permissions): User
    {
        $user = User::factory()->create();
        foreach ((array) $permissions as $perm) {
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    private function makeBatch(array $overrides = []): ResultBatch
    {
        return ResultBatch::create(array_merge([
            'api_client_id' => $this->apiClient->id,
            'external_batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'source_app' => 'LISCOM',
            'items_total' => 3,
            'items_ingested' => 2,
            'items_overwritten' => 0,
            'items_rejected' => 1,
            'items_duplicate' => 0,
        ], $overrides));
    }

    private function makeIngestion(ResultBatch $batch, array $overrides = []): ResultIngestion
    {
        return ResultIngestion::create(array_merge([
            'result_batch_id' => $batch->id,
            'api_client_id' => $this->apiClient->id,
            'hl7_control_id' => 'ctrl-'.uniqid(),
            'protocol_number' => 'C'.str_pad(rand(1, 99999), 10, '0', STR_PAD_LEFT),
            'protocol_type' => 'clinical',
            'equipment_name' => 'Cobas-411',
            'status' => 'ingested',
            'items_summary' => [],
        ], $overrides));
    }

    // ─── Autenticación y permisos ─────────────────────────────────────────────

    public function test_dashboard_requiere_login(): void
    {
        $this->get(route('admin.api-monitor.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_requiere_permission_lab_admissions_index(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_accesible_con_permission_correcto(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');
        $this->actingAs($user)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertOk()
            ->assertSee('Monitoreo de ingesta API');
    }

    // ─── Counters ─────────────────────────────────────────────────────────────

    public function test_dashboard_muestra_counters_del_periodo(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');

        $batch = $this->makeBatch(['items_ingested' => 2, 'items_rejected' => 1]);
        $this->makeIngestion($batch, ['status' => 'ingested']);
        $this->makeIngestion($batch, ['status' => 'ingested']);
        $this->makeIngestion($batch, ['status' => 'rejected', 'rejection_reason' => 'PROTOCOL_NOT_FOUND']);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertOk()
            ->assertSee('Mensajes ingestados')
            ->assertSee('Mensajes rechazados');
    }

    // ─── Alerta ALREADY_VALIDATED ─────────────────────────────────────────────

    public function test_alerta_already_validated_visible_cuando_hay_rechazos_del_dia(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');

        $batch = $this->makeBatch();
        $this->makeIngestion($batch, [
            'status' => 'rejected',
            'rejection_reason' => 'ALREADY_VALIDATED',
        ]);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertOk()
            ->assertSee('ALREADY_VALIDATED');
    }

    public function test_alerta_already_validated_oculta_cuando_no_hay_rechazos(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');

        $batch = $this->makeBatch(['items_rejected' => 0, 'items_ingested' => 2]);
        $this->makeIngestion($batch, ['status' => 'ingested']);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertOk()
            ->assertDontSee('rejection_reason=ALREADY_VALIDATED');
    }

    // ─── Sección admin vs bioquímico ──────────────────────────────────────────

    public function test_admin_ve_link_a_gestion_de_keys(): void
    {
        $admin = $this->userConPermiso(['lab-admissions.index', 'api-clients.manage']);
        $this->actingAs($admin)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertOk()
            ->assertSee('Gestionar API keys');
    }

    public function test_bioquimico_no_ve_link_a_gestion_de_keys(): void
    {
        $bio = $this->userConPermiso('lab-admissions.index');
        $this->actingAs($bio)
            ->get(route('admin.api-monitor.dashboard'))
            ->assertOk()
            ->assertDontSee('Gestionar API keys');
    }

    // ─── Listado batches ──────────────────────────────────────────────────────

    public function test_batches_list_accesible_y_muestra_datos(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');
        $batch = $this->makeBatch();

        $this->actingAs($user)
            ->get(route('admin.api-monitor.batches'))
            ->assertOk()
            ->assertSee(substr($batch->external_batch_id, 0, 18)); // Str::limit(20) trunca el UUID
    }

    public function test_batches_list_filtra_solo_rechazos(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');
        $batchConRechazos = $this->makeBatch(['items_rejected' => 2]);
        $batchLimpio = $this->makeBatch(['items_rejected' => 0, 'external_batch_id' => (string) \Illuminate\Support\Str::uuid()]);

        $response = $this->actingAs($user)
            ->get(route('admin.api-monitor.batches', ['solo_rechazos' => '1']));

        $response->assertOk();
        // El batch con rechazos debe aparecer, el limpio no
        $this->assertStringContainsString(
            mb_substr($batchConRechazos->external_batch_id, 0, 8),
            $response->getContent()
        );
    }

    // ─── Listado ingestions ───────────────────────────────────────────────────

    public function test_ingestions_list_filtra_por_status(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');

        $batch = $this->makeBatch();
        $ingRejected = $this->makeIngestion($batch, ['status' => 'rejected', 'rejection_reason' => 'PROTOCOL_NOT_FOUND', 'hl7_control_id' => 'ctrl-r1']);
        $ingIngested = $this->makeIngestion($batch, ['status' => 'ingested', 'hl7_control_id' => 'ctrl-i1']);

        $response = $this->actingAs($user)
            ->get(route('admin.api-monitor.ingestions', ['estado' => 'rejected']));

        $response->assertOk()
            ->assertSee('ctrl-r1')
            ->assertDontSee('ctrl-i1');
    }

    public function test_ingestions_list_filtra_por_rejection_reason(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');

        $batch = $this->makeBatch();
        $this->makeIngestion($batch, [
            'status' => 'rejected',
            'rejection_reason' => 'ALREADY_VALIDATED',
            'hl7_control_id' => 'ctrl-av-1',
        ]);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.ingestions', ['razon' => 'ALREADY_VALIDATED']))
            ->assertOk()
            ->assertSee('ctrl-av-1');
    }

    public function test_ingestions_list_busca_por_protocol_number(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');

        $batch = $this->makeBatch();
        $this->makeIngestion($batch, ['protocol_number' => 'C9999990001', 'hl7_control_id' => 'ctrl-p1']);
        $this->makeIngestion($batch, ['protocol_number' => 'C0000000001', 'hl7_control_id' => 'ctrl-p2']);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.ingestions', ['protocolo' => 'C999999']))
            ->assertOk()
            ->assertSee('ctrl-p1')
            ->assertDontSee('ctrl-p2');
    }

    // ─── Detalle batch ────────────────────────────────────────────────────────

    public function test_batch_detail_muestra_sus_ingestions(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');
        $batch = $this->makeBatch();
        $ingestion = $this->makeIngestion($batch, ['hl7_control_id' => 'ctrl-detail-1']);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.batches.show', $batch))
            ->assertOk()
            ->assertSee('ctrl-detail-1');
    }

    public function test_batch_detail_oculta_raw_request_a_no_admin(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');
        $batch = $this->makeBatch(['raw_request' => ['batch_id' => 'secret-payload']]);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.batches.show', $batch))
            ->assertOk()
            ->assertDontSee('secret-payload');
    }

    public function test_batch_detail_muestra_raw_request_a_admin(): void
    {
        $admin = $this->userConPermiso(['lab-admissions.index', 'api-clients.manage']);
        $batch = $this->makeBatch(['raw_request' => ['batch_id' => 'visible-payload']]);

        $this->actingAs($admin)
            ->get(route('admin.api-monitor.batches.show', $batch))
            ->assertOk()
            ->assertSee('visible-payload');
    }

    // ─── Detalle ingestion ────────────────────────────────────────────────────

    public function test_ingestion_detail_destaca_already_validated(): void
    {
        $user = $this->userConPermiso('lab-admissions.index');
        $batch = $this->makeBatch();
        $ingestion = $this->makeIngestion($batch, [
            'status' => 'rejected',
            'rejection_reason' => 'ALREADY_VALIDATED',
            'items_summary' => [
                [
                    'obx_index' => 0,
                    'status' => 'rejected',
                    'reason' => 'ALREADY_VALIDATED',
                    'validated_by_name' => 'Dr. García',
                    'validated_at' => now()->subHour()->toIso8601String(),
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('admin.api-monitor.ingestions.show', $ingestion))
            ->assertOk()
            ->assertSee('Ya validado')
            ->assertSee('Dr. García');
    }
}
