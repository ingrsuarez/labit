<?php

namespace Tests\Feature\Billing;

use App\Models\Customer;
use App\Models\LabBranch;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingSummarySampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('sales-invoices.index');
        Permission::findOrCreate('lab.section');
    }

    public function test_sample_billing_summary_one_row_per_protocol(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['lab.section', 'sales-invoices.index']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Aguas',
            'taxId' => '20-11111111-1',
            'status' => 'activo',
            'type' => ['aguas'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-2026-0001',
            'sample_type' => 'agua',
            'entry_date' => '2026-05-05',
            'sampling_date' => '2026-05-05',
            'customer_id' => $customer->id,
            'location' => 'Planta Norte',
            'status' => 'pending',
            'validation_status' => 'pending',
            'created_by' => $user->id,
            'lab_branch_id' => $branch->id,
        ]);

        $t1 = $this->createTest('AG01');
        $t2 = $this->createTest('AG02');
        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $t1->id,
            'price' => 120,
        ]);
        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $t2->id,
            'price' => 80,
        ]);

        $response = $this->actingAs($user)->get(route('sample.billing-summary', [
            'customer_id' => $customer->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertOk();
        $response->assertSee('Planta Norte', false);
        $response->assertSee('AG01-AG02', false);
        $response->assertSee('$200,00', false);
        $response->assertSee('1 protocolo', false);
    }

    public function test_sample_export_excel_ok(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Export',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['lab.section', 'sales-invoices.index']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Export',
            'taxId' => '20-22222222-2',
            'status' => 'activo',
            'type' => ['aguas'],
        ]);

        Sample::query()->create([
            'protocol_number' => 'A-2026-0002',
            'sample_type' => 'agua',
            'entry_date' => '2026-05-06',
            'sampling_date' => '2026-05-06',
            'customer_id' => $customer->id,
            'location' => 'Muestra X',
            'status' => 'pending',
            'validation_status' => 'pending',
            'created_by' => $user->id,
            'lab_branch_id' => $branch->id,
        ]);

        $this->actingAs($user)->get(route('sample.billing-summary.exportExcel', [
            'customer_id' => $customer->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]))->assertOk();
    }

    private function createTest(string $code): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'Test '.$code,
            'unit' => 'mg/L',
            'decimals' => 2,
            'price' => 0,
            'cost' => 0,
            'nbu' => 1,
            'categories' => ['aguas_alimentos'],
            'sort_order' => 0,
            'empty_result_exempt' => false,
        ]);
    }
}
