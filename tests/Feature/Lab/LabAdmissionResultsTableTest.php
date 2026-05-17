<?php

namespace Tests\Feature\Lab;

use App\Livewire\Lab\LabAdmissionResultsTable;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LabAdmissionResultsTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWithPermissions(array $permissions): User
    {
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }
        $user = User::factory()->create();
        foreach ($permissions as $perm) {
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    private function makePatient(): Patient
    {
        return Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Test',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);
    }

    private function makeAdmission(User $user, Patient $patient): Admission
    {
        return Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-2026-LW01',
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
    }

    private function makeTest(string $code, ?int $parent = null): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'T '.$code,
            'unit' => 'g/L',
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => $parent,
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

    public function test_validate_test_sin_redirect(): void
    {
        $user = $this->userWithPermissions(['lab-results.validate']);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient);
        $test = $this->makeTest('GLU01');
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 100,
            'result' => '95',
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user);

        Livewire::test(LabAdmissionResultsTable::class, [
            'admissionId' => $admission->id,
        ])
            ->call('validateTest', $at->id)
            ->assertDispatched('notify');

        $this->assertTrue($at->fresh()->is_validated);
    }

    public function test_validate_sin_resultado_dispara_error(): void
    {
        $user = $this->userWithPermissions(['lab-results.validate']);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient);
        $test = $this->makeTest('GLU02');
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user);

        Livewire::test(LabAdmissionResultsTable::class, [
            'admissionId' => $admission->id,
        ])
            ->call('validateTest', $at->id)
            ->assertDispatched('notify');

        $this->assertFalse($at->fresh()->is_validated);
    }

    public function test_remove_hoja_elimina_fila(): void
    {
        $user = $this->userWithPermissions(['lab-admissions.delete']);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient);
        $parent = $this->makeTest('HEMO-P');
        $child = $this->makeTest('HEMO-A', $parent->id);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $child->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user);

        Livewire::test(LabAdmissionResultsTable::class, [
            'admissionId' => $admission->id,
        ])
            ->call('removeTest', $at->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('admission_tests', ['id' => $at->id]);
    }
}
