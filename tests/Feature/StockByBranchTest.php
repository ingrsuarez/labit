<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\LabBranch;
use App\Models\Supply;
use App\Models\SupplyLabBranchStock;
use App\Models\User;
use App\Services\SupplyStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StockByBranchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'compras.section',
            'purchase-invoices.index',
            'purchase-invoices.create',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function test_entrada_en_sede_a_incrementa_solo_a(): void
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

        $supply = Supply::query()->create([
            'code' => 'INS-BR',
            'name' => 'Insumo branch',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $svc = app(SupplyStockService::class);
        $svc->recordEntrada($supply, $branchA->id, 5, [
            'reason' => 'ajuste_manual',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => 'test',
            'user_id' => User::factory()->create()->id,
        ]);

        $supply->refresh();
        $this->assertSame(5.0, (float) $supply->stock);

        $qtyA = (float) SupplyLabBranchStock::query()
            ->where('supply_id', $supply->id)
            ->where('lab_branch_id', $branchA->id)
            ->value('quantity');
        $this->assertSame(5.0, $qtyA);

        $this->assertFalse(SupplyLabBranchStock::query()
            ->where('supply_id', $supply->id)
            ->where('lab_branch_id', $branchB->id)
            ->exists());
    }

    public function test_purchase_invoice_sin_remito_actualiza_stock_en_sede_usuario(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa',
            'cuit' => '20-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $branch = LabBranch::query()->create([
            'name' => 'Sede FC',
            'is_central' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo([
            'compras.section',
            'purchase-invoices.index',
            'purchase-invoices.create',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $supplier = \App\Models\Supplier::query()->create([
            'code' => 'S-BR',
            'name' => 'Prov',
            'tax_id' => '30-99999999-9',
            'status' => 'activo',
        ]);

        $supply = Supply::query()->create([
            'code' => 'INS-FC',
            'name' => 'Insumo FC',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('purchase-invoices.store'), [
                'invoice_number' => '00001',
                'voucher_type' => 'A',
                'point_of_sale' => '00001',
                'supplier_id' => $supplier->id,
                'issue_date' => now()->toDateString(),
                'percepciones' => 0,
                'otros_impuestos' => 0,
                'items' => [
                    [
                        'description' => 'Item',
                        'supply_id' => $supply->id,
                        'quantity' => 2,
                        'unit_price' => 10,
                        'iva_rate' => 21,
                        'updates_stock' => true,
                    ],
                ],
            ])
            ->assertRedirect();

        $supply->refresh();
        $this->assertSame(2.0, (float) $supply->stock);

        $pivotQty = (float) SupplyLabBranchStock::query()
            ->where('supply_id', $supply->id)
            ->where('lab_branch_id', $branch->id)
            ->value('quantity');
        $this->assertSame(2.0, $pivotQty);
    }
}
