<?php

namespace Tests\Feature\Lab;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\LabBranch;
use App\Models\Material;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Services\AdmissionSampleDrawService;
use Carbon\Carbon;
use Database\Seeders\LabSampleDrawHistoricalBackfillSeeder;
use Database\Seeders\LabSampleDrawPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LabSampleDrawHistoricalBackfillSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LabSampleDrawPermissionsSeeder::class);
    }

    private function makeDrawer(): User
    {
        return User::factory()->create(['name' => 'Clara Silvina Olie']);
    }

    private function makeHistoricalAdmission(LabBranch $branch, Patient $patient, User $creator): Admission
    {
        $admission = Admission::query()->create([
            'date' => now()->subDays(10)->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-BF-'.uniqid(),
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'promise_date' => now()->subDays(10)->toDateString(),
            'authorization_code' => '',
            'attended_by' => $creator->id,
            'created_by' => $creator->id,
            'lab_branch_id' => $branch->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'status' => 'pending',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $material = Material::query()->create([
            'code' => 'EDTA-BF-'.uniqid(),
            'name' => 'Tubo EDTA',
            'is_active' => true,
        ]);

        $test = Test::query()->create([
            'code' => 'HEM-BF-'.uniqid(),
            'name' => 'Hemograma',
            'unit' => '—',
            'material' => $material->id,
            'price' => 100,
            'nbu' => 1,
        ]);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 100,
            'nbu_units' => 1,
            'authorization_status' => 'not_required',
            'paid_by_patient' => false,
            'copago' => 0,
        ]);

        return $admission;
    }

    public function test_backfill_assigns_historical_admissions_to_default_drawer(): void
    {
        $drawer = $this->makeDrawer();
        Config::set('lab.sample_draw_backfill.user_id', $drawer->id);

        $branch = LabBranch::query()->create(['name' => 'Sede BF', 'is_central' => true, 'is_active' => true]);
        $creator = User::factory()->create();
        $patient = Patient::query()->create([
            'name' => 'Juan',
            'lastName' => 'Histórico',
            'patientId' => '30111001',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = $this->makeHistoricalAdmission($branch, $patient, $creator);

        $this->seed(LabSampleDrawHistoricalBackfillSeeder::class);

        $admission->refresh();
        $this->assertSame($drawer->id, $admission->sample_drawn_by);
        $this->assertNotNull($admission->sample_drawn_at);

        $service = app(AdmissionSampleDrawService::class);
        $this->assertSame(0, $service->pendingCount($branch->id));
    }

    public function test_backfill_is_idempotent(): void
    {
        $drawer = $this->makeDrawer();
        Config::set('lab.sample_draw_backfill.user_id', $drawer->id);

        $branch = LabBranch::query()->create(['name' => 'Sede BF 2', 'is_central' => true, 'is_active' => true]);
        $creator = User::factory()->create();
        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Histórica',
            'patientId' => '30111002',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $admission = $this->makeHistoricalAdmission($branch, $patient, $creator);

        $this->seed(LabSampleDrawHistoricalBackfillSeeder::class);
        $firstAt = $admission->fresh()->sample_drawn_at;

        $this->seed(LabSampleDrawHistoricalBackfillSeeder::class);

        $admission->refresh();
        $this->assertSame($drawer->id, $admission->sample_drawn_by);
        $this->assertTrue($firstAt->equalTo($admission->sample_drawn_at));
    }

    public function test_backfill_skips_admissions_without_material(): void
    {
        $drawer = $this->makeDrawer();
        Config::set('lab.sample_draw_backfill.user_id', $drawer->id);

        $branch = LabBranch::query()->create(['name' => 'Sede BF 3', 'is_central' => true, 'is_active' => true]);
        $creator = User::factory()->create();
        $patient = Patient::query()->create([
            'name' => 'Luis',
            'lastName' => 'SinMaterial',
            'patientId' => '30111003',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = Admission::query()->create([
            'date' => now()->subDays(5)->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-BF-NOMAT',
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->subDays(5)->toDateString(),
            'promise_date' => now()->subDays(5)->toDateString(),
            'authorization_code' => '',
            'attended_by' => $creator->id,
            'created_by' => $creator->id,
            'lab_branch_id' => $branch->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'status' => 'pending',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->seed(LabSampleDrawHistoricalBackfillSeeder::class);

        $admission->refresh();
        $this->assertNull($admission->sample_drawn_by);
        $this->assertNull($admission->sample_drawn_at);
    }

    public function test_backfill_respects_cutoff_date(): void
    {
        Carbon::setTestNow('2026-05-29 12:00:00');

        $drawer = $this->makeDrawer();
        Config::set('lab.sample_draw_backfill.user_id', $drawer->id);
        Config::set('lab.sample_draw_backfill.before', '2026-05-22 12:00:00');

        $branch = LabBranch::query()->create(['name' => 'Sede BF 4', 'is_central' => true, 'is_active' => true]);
        $creator = User::factory()->create();
        $patient = Patient::query()->create([
            'name' => 'María',
            'lastName' => 'Reciente',
            'patientId' => '30111004',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $oldAdmission = $this->makeHistoricalAdmission($branch, $patient, $creator);
        Admission::query()->whereKey($oldAdmission->id)->update([
            'created_at' => '2026-05-10 10:00:00',
            'updated_at' => '2026-05-10 10:00:00',
        ]);

        $recentAdmission = $this->makeHistoricalAdmission($branch, $patient, $creator);
        Admission::query()->whereKey($recentAdmission->id)->update([
            'protocol_number' => 'C-BF-RECENT',
            'created_at' => '2026-05-28 10:00:00',
            'updated_at' => '2026-05-28 10:00:00',
        ]);

        $this->seed(LabSampleDrawHistoricalBackfillSeeder::class);

        $oldAdmission->refresh();
        $recentAdmission->refresh();

        $this->assertSame($drawer->id, $oldAdmission->sample_drawn_by);
        $this->assertNull($recentAdmission->sample_drawn_by);
    }

    protected function tearDown(): void
    {
        Config::set('lab.sample_draw_backfill.user_id', null);
        Config::set('lab.sample_draw_backfill.before', null);
        Carbon::setTestNow();

        parent::tearDown();
    }
}
