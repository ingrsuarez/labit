<?php

namespace Tests\Feature\Vet;

use App\Livewire\Vet\VetAdmissionResultsTable;
use App\Models\Customer;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VetAdmissionResultsTableTest extends TestCase
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

    private function makeVetAdmission(User $user): VetAdmission
    {
        $species = Species::query()->create([
            'name' => 'Canino',
            'code' => 'CAN-LW',
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'name' => 'Cli Vet LW',
            'taxId' => '20-33333333-3',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);

        return VetAdmission::query()->create([
            'protocol_number' => 'V-2026-LW01',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Firulais',
            'owner_name' => 'Dueño',
            'status' => 'pending',
            'total_price' => 500,
            'created_by' => $user->id,
        ]);
    }

    private function makeTest(string $code): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'T '.$code,
            'unit' => 'g/L',
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => null,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => null,
            'price' => 100,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['vet'],
            'sort_order' => 0,
        ]);
    }

    public function test_validate_test_actualiza_status(): void
    {
        $user = $this->userWithPermissions(['lab-results.validate']);
        $vetAdmission = $this->makeVetAdmission($user);
        $test = $this->makeTest('VET01');
        $vat = VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $test->id,
            'price' => 100,
            'result' => '10',
            'status' => 'completed',
        ]);

        $this->actingAs($user);

        Livewire::test(VetAdmissionResultsTable::class, [
            'vetAdmissionId' => $vetAdmission->id,
        ])
            ->call('validateTest', $vat->id)
            ->assertDispatched('notify');

        $fresh = $vat->fresh();
        $this->assertTrue($fresh->is_validated);
        if (config('database.default') === 'mysql' || \Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            $this->assertSame('validated', $fresh->status);
        }
    }

    public function test_remove_hoja_ajusta_total_price(): void
    {
        $user = $this->userWithPermissions(['vet-admissions.delete']);
        $vetAdmission = $this->makeVetAdmission($user);
        $test = $this->makeTest('VET02');
        $vat = VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $test->id,
            'price' => 150,
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        Livewire::test(VetAdmissionResultsTable::class, [
            'vetAdmissionId' => $vetAdmission->id,
        ])
            ->call('removeTest', $vat->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('vet_admission_tests', ['id' => $vat->id]);
        $this->assertEquals(350.0, (float) $vetAdmission->fresh()->total_price);
    }
}
