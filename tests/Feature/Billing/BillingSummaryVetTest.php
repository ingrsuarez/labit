<?php

namespace Tests\Feature\Billing;

use App\Models\Customer;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingSummaryVetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('sales-invoices.index');
        Permission::findOrCreate('lab.section');
    }

    public function test_vet_billing_summary_one_row_per_protocol(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'sales-invoices.index']);
        $userId = $user->id;

        $customer = Customer::query()->create([
            'name' => 'Vet Central',
            'taxId' => '20-33333333-3',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);

        $species = Species::query()->create([
            'name' => 'Canino',
            'code' => 'CAN',
            'is_active' => true,
        ]);

        $admission = VetAdmission::query()->create([
            'protocol_number' => 'V-2026-0001',
            'date' => '2026-05-08',
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Firulais',
            'owner_name' => 'Juan',
            'status' => 'pending',
            'total_price' => 300,
            'created_by' => $userId,
        ]);

        $t1 = $this->createTest('VET01');
        $t2 = $this->createTest('VET02');
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $admission->id,
            'test_id' => $t1->id,
            'price' => 180,
        ]);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $admission->id,
            'test_id' => $t2->id,
            'price' => 120,
        ]);

        $response = $this->actingAs($user)->get(route('vet.billing-summary', [
            'customer_id' => $customer->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertOk();
        $response->assertSee('Firulais', false);
        $response->assertSee('VET01-VET02', false);
        $response->assertSee('$300,00', false);
    }

    private function createTest(string $code): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'Test '.$code,
            'unit' => 'mg/dL',
            'decimals' => 2,
            'price' => 0,
            'cost' => 0,
            'nbu' => 1,
            'categories' => ['veterinario'],
            'sort_order' => 0,
            'empty_result_exempt' => false,
        ]);
    }
}
