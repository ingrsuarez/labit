<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RemoveLeafAdmissionDeterminationTest extends TestCase
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

    private function makeTest(string $code, ?int $parent = null, int $sortOrder = 0, int $price = 0): Test
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
            'price' => $price,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['lab'],
            'sort_order' => $sortOrder,
        ]);
    }

    private function makeAdmission(User $user, Patient $patient): Admission
    {
        return Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-2026-LEAF01',
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

    public function test_lab_elimina_solo_hoja_sin_resultado_padre_y_hermano_permanecen(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-admissions.delete',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient);

        $parent = $this->makeTest('HEMO-P', null, 1, 1000);
        $childA = $this->makeTest('HEMO-A', $parent->id, 2, 0);
        $childB = $this->makeTest('HEMO-B', $parent->id, 3, 0);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);
        $atA = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $childA->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $childB->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user)
            ->from(route('lab.admissions.show', $admission))
            ->delete(route('lab.admissions.removeTest', [$admission, $atA]))
            ->assertRedirect(route('lab.admissions.show', $admission));

        $this->assertDatabaseMissing('admission_tests', ['id' => $atA->id]);
        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $childB->id,
        ]);
    }

    public function test_lab_rechaza_eliminar_hoja_con_resultado(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-admissions.delete',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient);

        $parent = $this->makeTest('HEMO2-P', null, 1, 1000);
        $childA = $this->makeTest('HEMO2-A', $parent->id, 2, 0);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);
        $atA = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $childA->id,
            'price' => 0,
            'result' => '12',
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user)
            ->from(route('lab.admissions.show', $admission))
            ->delete(route('lab.admissions.removeTest', [$admission, $atA]))
            ->assertRedirect(route('lab.admissions.show', $admission));

        $this->assertDatabaseHas('admission_tests', ['id' => $atA->id]);
    }

    public function test_vet_elimina_solo_hoja_sin_resultado(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Cli Vet',
            'taxId' => '20-22222222-2',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);
        $species = Species::query()->create([
            'name' => 'Felino',
            'code' => 'FEL-LEAF',
            'is_active' => true,
        ]);

        $user = $this->userWithPermissions([
            'lab.section',
            'vet-admissions.index',
            'vet-admissions.show',
            'vet-admissions.delete',
        ]);

        $vetAdmission = VetAdmission::query()->create([
            'date' => now()->toDateString(),
            'protocol_number' => 'V-2026-LEAF01',
            'status' => 'pending',
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Michi',
            'owner_name' => 'Dueño',
            'total_price' => 150,
            'created_by' => $user->id,
        ]);

        $parent = $this->makeTest('VHEMO-P', null, 1, 0);
        $childA = $this->makeTest('VHEMO-A', $parent->id, 2, 0);
        $childB = $this->makeTest('VHEMO-B', $parent->id, 3, 0);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'status' => 'pending',
        ]);
        $vatA = VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $childA->id,
            'price' => 0,
            'status' => 'pending',
        ]);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $childB->id,
            'price' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->from(route('vet.admissions.show', $vetAdmission))
            ->delete(route('vet.admissions.removeTest', [$vetAdmission, $vatA]))
            ->assertRedirect(route('vet.admissions.show', $vetAdmission));

        $this->assertDatabaseMissing('vet_admission_tests', ['id' => $vatA->id]);
        $this->assertDatabaseHas('vet_admission_tests', [
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('vet_admission_tests', [
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $childB->id,
        ]);
    }
}
