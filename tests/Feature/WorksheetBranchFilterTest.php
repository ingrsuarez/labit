<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Models\Worksheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorksheetBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function grant(User $user, array $permissions): void
    {
        foreach ($permissions as $p) {
            Permission::findOrCreate($p);
        }
        $user->givePermissionTo($permissions);
    }

    private function makeTestModel(): Test
    {
        return Test::query()->create([
            'code' => 'WSF1',
            'name' => 'Test planilla sede',
            'unit' => 'mg/dL',
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => null,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => null,
            'price' => 0,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['lab'],
            'sort_order' => 0,
        ]);
    }

    public function test_preview_rechaza_lab_branch_id_inexistente(): void
    {
        $user = User::factory()->create();
        $this->grant($user, ['lab.section']);

        $test = $this->makeTestModel();
        $worksheet = Worksheet::query()->create([
            'name' => 'Planilla filtro',
            'type' => 'clinico',
            'created_by' => $user->id,
        ]);
        $worksheet->tests()->sync([$test->id => ['sort_order' => 0]]);

        $response = $this->actingAs($user)->get(route('worksheets.show', [
            'worksheet' => $worksheet,
            'preview' => 1,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'lab_branch_id' => 999_999,
        ]));

        $response->assertSessionHasErrors('lab_branch_id');
    }

    public function test_preview_filtra_admisiones_por_sede(): void
    {
        $branchA = LabBranch::query()->create([
            'name' => 'Sede A',
            'is_central' => true,
            'is_active' => true,
        ]);
        $branchB = LabBranch::query()->create([
            'name' => 'Sede B',
            'is_central' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branchA->id]);
        $this->grant($user, ['lab.section']);

        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Test',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $test = $this->makeTestModel();
        $worksheet = Worksheet::query()->create([
            'name' => 'Planilla filtro',
            'type' => 'clinico',
            'created_by' => $user->id,
        ]);
        $worksheet->tests()->sync([$test->id => ['sort_order' => 0]]);

        $d = now()->toDateString();

        $protocols = [];
        $p1 = Admission::generateProtocolNumber();
        $protocols[$branchA->id] = $p1;
        $adm1 = Admission::query()->create([
            'date' => $d,
            'number' => '1',
            'protocol_number' => $p1,
            'patient_id' => $patient->id,
            'room' => 0,
            'institution' => 0,
            'invoice_date' => $d,
            'promise_date' => $d,
            'authorization_code' => '',
            'attended_by' => $user->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => $user->id,
            'status' => Admission::STATUS_PENDING,
            'lab_branch_id' => $branchA->id,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $adm1->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => false,
        ]);

        $p2 = Admission::generateProtocolNumber();
        $protocols[$branchB->id] = $p2;
        $adm2 = Admission::query()->create([
            'date' => $d,
            'number' => '2',
            'protocol_number' => $p2,
            'patient_id' => $patient->id,
            'room' => 0,
            'institution' => 0,
            'invoice_date' => $d,
            'promise_date' => $d,
            'authorization_code' => '',
            'attended_by' => $user->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => $user->id,
            'status' => Admission::STATUS_PENDING,
            'lab_branch_id' => $branchB->id,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $adm2->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => false,
        ]);

        $this->assertSame(2, Admission::query()->whereHas('admissionTests', function ($q) use ($test) {
            $q->where('test_id', $test->id);
        })->count());

        $base = [
            'worksheet' => $worksheet,
            'preview' => 1,
            'date_from' => $d,
            'date_to' => $d,
            'include_without_results' => 1,
            'include_with_results' => 1,
        ];

        $all = $this->actingAs($user)->get(route('worksheets.show', $base));
        $all->assertOk();
        $all->assertSee($protocols[$branchA->id], false);
        $all->assertSee($protocols[$branchB->id], false);

        $filtered = $this->actingAs($user)->get(route('worksheets.show', array_merge($base, [
            'lab_branch_id' => $branchA->id,
        ])));
        $filtered->assertOk();
        $filtered->assertSee($protocols[$branchA->id], false);
        $filtered->assertDontSee($protocols[$branchB->id], false);
    }
}
