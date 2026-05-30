<?php

namespace Tests\Feature\Lab;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\AuditLog;
use App\Models\LabBranch;
use App\Models\Material;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Models\Worksheet;
use Database\Seeders\LabSampleDrawPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissionSampleDrawTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LabSampleDrawPermissionsSeeder::class);
    }

    private function makeMaterial(): Material
    {
        return Material::query()->create([
            'code' => 'EDTA',
            'name' => 'Tubo EDTA',
            'is_active' => true,
        ]);
    }

    private function makeTestWithMaterial(?int $materialId = null): Test
    {
        $material = $materialId ? Material::find($materialId) : $this->makeMaterial();

        return Test::query()->create([
            'code' => 'HEM'.uniqid(),
            'name' => 'Hemograma',
            'unit' => '—',
            'material' => $material->id,
            'price' => 100,
            'nbu' => 1,
        ]);
    }

    private function makeAdmission(LabBranch $branch, Patient $patient, User $creator): Admission
    {
        return Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-EXT-'.uniqid(),
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->toDateString(),
            'promise_date' => now()->toDateString(),
            'authorization_code' => '',
            'attended_by' => $creator->id,
            'created_by' => $creator->id,
            'lab_branch_id' => $branch->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'status' => 'pending',
        ]);
    }

    private function attachMaterialTest(Admission $admission, Test $test): void
    {
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 100,
            'nbu_units' => 1,
            'authorization_status' => 'not_required',
            'paid_by_patient' => false,
            'copago' => 0,
        ]);
    }

    public function test_pending_count_with_material_without_drawer(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Ext', 'is_central' => true, 'is_active' => true]);
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');

        $patient = Patient::query()->create([
            'name' => 'Juan',
            'lastName' => 'Pérez',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = $this->makeAdmission($branch, $patient, $recepcion);
        $this->attachMaterialTest($admission, $this->makeTestWithMaterial());

        session(['active_lab_branch_id' => $branch->id]);

        $this->actingAs($recepcion)
            ->getJson(route('lab.sample-draws.pending-count'))
            ->assertOk()
            ->assertJson(['count' => 1]);
    }

    public function test_register_as_technician_sets_drawer_and_audit(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Ext 2', 'is_central' => true, 'is_active' => true]);
        $tecnico = User::factory()->create();
        $tecnico->assignRole('tecnico-lab');

        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'López',
            'patientId' => '28999888',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $admission = $this->makeAdmission($branch, $patient, $tecnico);
        $this->attachMaterialTest($admission, $this->makeTestWithMaterial());

        $this->actingAs($tecnico)
            ->postJson(route('lab.sample-draws.register', $admission))
            ->assertOk();

        $admission->refresh();
        $this->assertSame($tecnico->id, $admission->sample_drawn_by);
        $this->assertNotNull($admission->sample_drawn_at);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Admission::class,
            'auditable_id' => $admission->id,
            'action' => 'sample_drawn',
        ]);

        session(['active_lab_branch_id' => $branch->id]);
        $this->actingAs($tecnico)
            ->getJson(route('lab.sample-draws.pending-count'))
            ->assertJson(['count' => 0]);
    }

    public function test_recepcion_register_without_drawer_returns_422(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Ext 3', 'is_central' => true, 'is_active' => true]);
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');

        $patient = Patient::query()->create([
            'name' => 'Luis',
            'lastName' => 'Gómez',
            'patientId' => '27111222',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = $this->makeAdmission($branch, $patient, $recepcion);
        $this->attachMaterialTest($admission, $this->makeTestWithMaterial());

        $this->actingAs($recepcion)
            ->postJson(route('lab.sample-draws.register', $admission))
            ->assertStatus(422);
    }

    public function test_recepcion_register_with_biochemist_drawer(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Ext 4', 'is_central' => true, 'is_active' => true]);
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');
        $bio = User::factory()->create(['name' => 'Dr. Bio']);
        $bio->assignRole('bioquimico');

        $patient = Patient::query()->create([
            'name' => 'María',
            'lastName' => 'Ruiz',
            'patientId' => '26111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $admission = $this->makeAdmission($branch, $patient, $recepcion);
        $this->attachMaterialTest($admission, $this->makeTestWithMaterial());

        $this->actingAs($recepcion)
            ->postJson(route('lab.sample-draws.register', $admission), [
                'sample_drawn_by' => $bio->id,
            ])
            ->assertOk();

        $admission->refresh();
        $this->assertSame($bio->id, $admission->sample_drawn_by);

        $audit = AuditLog::query()
            ->where('auditable_id', $admission->id)
            ->where('action', 'sample_drawn')
            ->first();
        $this->assertNotNull($audit);
        $this->assertStringContainsString('Dr. Bio', $audit->description);
    }

    public function test_worksheet_excludes_pending_extraction(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Planilla', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'lab-results.create']);

        $test = $this->makeTestWithMaterial();
        $worksheet = Worksheet::query()->create([
            'name' => 'Planilla extracción',
            'type' => 'clinico',
            'created_by' => $user->id,
        ]);
        $worksheet->tests()->sync([$test->id => ['sort_order' => 0]]);

        $patient = Patient::query()->create([
            'name' => 'Pedro',
            'lastName' => 'Soto',
            'patientId' => '25111222',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $pending = $this->makeAdmission($branch, $patient, $user);
        $this->attachMaterialTest($pending, $test);

        $done = $this->makeAdmission($branch, $patient, $user);
        $done->update([
            'protocol_number' => 'C-EXT-DONE',
            'sample_drawn_by' => $user->id,
            'sample_drawn_at' => now(),
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $done->id,
            'test_id' => $test->id,
            'price' => 100,
            'nbu_units' => 1,
            'authorization_status' => 'not_required',
            'paid_by_patient' => false,
            'copago' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('worksheets.show', [
            'worksheet' => $worksheet,
            'preview' => 1,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'lab_branch_id' => $branch->id,
        ]));

        $response->assertOk();
        $response->assertSee('C-EXT-DONE');
        $response->assertDontSee($pending->protocol_number);
        $response->assertSee('falta registrar la extracción');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $this->actingAs($user)
            ->getJson(route('lab.sample-draws.pending-count'))
            ->assertForbidden();
    }

    public function test_pending_count_filters_by_active_branch(): void
    {
        $branchA = LabBranch::query()->create(['name' => 'Sede A', 'is_central' => true, 'is_active' => true]);
        $branchB = LabBranch::query()->create(['name' => 'Sede B', 'is_central' => false, 'is_active' => true]);
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');

        $patient = Patient::query()->create([
            'name' => 'Juan',
            'lastName' => 'Filtro',
            'patientId' => '30111233',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $material = $this->makeMaterial();
        $admissionA = $this->makeAdmission($branchA, $patient, $recepcion);
        $this->attachMaterialTest($admissionA, $this->makeTestWithMaterial($material->id));

        $admissionB = $this->makeAdmission($branchB, $patient, $recepcion);
        $admissionB->update(['protocol_number' => 'C-EXT-B']);
        $this->attachMaterialTest($admissionB, $this->makeTestWithMaterial($material->id));

        $this->actingAs($recepcion);
        session(['active_lab_branch_id' => $branchA->id]);
        $this->getJson(route('lab.sample-draws.pending-count'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        session(['active_lab_branch_id' => $branchB->id]);
        $this->getJson(route('lab.sample-draws.pending-count'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        session(['active_lab_branch_id' => null]);
        $this->getJson(route('lab.sample-draws.pending-count'))
            ->assertOk()
            ->assertJson(['count' => 2]);
    }

    public function test_recepcion_pending_includes_drawers_and_must_select(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Drawers', 'is_central' => true, 'is_active' => true]);
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');
        $tecnico = User::factory()->create(['name' => 'Técnico Roxana']);
        $tecnico->assignRole('tecnico-lab');

        session(['active_lab_branch_id' => $branch->id]);

        $response = $this->actingAs($recepcion)
            ->getJson(route('lab.sample-draws.pending'))
            ->assertOk();

        $response->assertJsonPath('must_select_drawer', true);
        $this->assertContains($tecnico->id, collect($response->json('drawers'))->pluck('id')->all());
    }

    public function test_recepcion_with_bioquimico_role_still_must_select_drawer(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['recepcion-lab', 'bioquimico']);

        $this->actingAs($user)
            ->getJson(route('lab.sample-draws.pending'))
            ->assertOk()
            ->assertJsonPath('must_select_drawer', true)
            ->assertJsonPath('default_drawer_id', $user->id);
    }

    public function test_recepcion_without_lab_drawer_role_has_no_default(): void
    {
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');

        $this->actingAs($recepcion)
            ->getJson(route('lab.sample-draws.pending'))
            ->assertOk()
            ->assertJsonPath('must_select_drawer', true)
            ->assertJsonPath('default_drawer_id', null);
    }

    public function test_technician_pending_does_not_require_drawer_selection(): void
    {
        $tecnico = User::factory()->create();
        $tecnico->assignRole('tecnico-lab');

        $this->actingAs($tecnico)
            ->getJson(route('lab.sample-draws.pending'))
            ->assertOk()
            ->assertJsonPath('must_select_drawer', false);
    }

    public function test_recepcion_with_bioquimico_register_requires_drawer_id(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Mix', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole(['recepcion-lab', 'bioquimico']);
        $tecnico = User::factory()->create(['name' => 'Técnico Asignado']);
        $tecnico->assignRole('tecnico-lab');

        $patient = Patient::query()->create([
            'name' => 'Pedro',
            'lastName' => 'Mix',
            'patientId' => '30111555',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = $this->makeAdmission($branch, $patient, $user);
        $this->attachMaterialTest($admission, $this->makeTestWithMaterial());

        $this->actingAs($user)
            ->postJson(route('lab.sample-draws.register', $admission))
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('lab.sample-draws.register', $admission), [
                'sample_drawn_by' => $tecnico->id,
            ])
            ->assertOk();

        $this->assertSame($tecnico->id, $admission->fresh()->sample_drawn_by);
    }

    public function test_pending_list_orders_by_protocol_number(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Orden', 'is_central' => true, 'is_active' => true]);
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');

        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Orden',
            'patientId' => '30111244',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $material = $this->makeMaterial();
        $later = $this->makeAdmission($branch, $patient, $recepcion);
        $later->update(['protocol_number' => 'C-2026-000010']);
        $this->attachMaterialTest($later, $this->makeTestWithMaterial($material->id));

        $earlier = $this->makeAdmission($branch, $patient, $recepcion);
        $earlier->update(['protocol_number' => 'C-2026-000002']);
        $this->attachMaterialTest($earlier, $this->makeTestWithMaterial($material->id));

        session(['active_lab_branch_id' => $branch->id]);

        $response = $this->actingAs($recepcion)
            ->getJson(route('lab.sample-draws.pending'))
            ->assertOk();

        $protocols = collect($response->json('items'))->pluck('protocol_number')->all();
        $this->assertSame(['C-2026-000002', 'C-2026-000010'], $protocols);
    }

    public function test_pending_count_zero_when_no_draws_required(): void
    {
        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion-lab');

        $this->actingAs($recepcion)
            ->getJson(route('lab.sample-draws.pending-count'))
            ->assertOk()
            ->assertJson(['count' => 0]);
    }
}
