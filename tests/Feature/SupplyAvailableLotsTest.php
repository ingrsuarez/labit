<?php

namespace Tests\Feature;

use App\Models\LabBranch;
use App\Models\StockMovement;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyAvailableLotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['compras.section', 'stock-movements.create'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function test_returns_lots_json_for_branch(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede API',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-API',
            'name' => 'Insumo API',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 8,
            'previous_stock' => 0,
            'new_stock' => 8,
            'reason' => 'ajuste_manual',
            'lot_number' => 'Z9',
            'expiration_date' => '2028-01-10',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('supplies.available-lots', $supply).'?lab_branch_id='.$branch->id);

        $response->assertOk();
        $response->assertJsonFragment(['lot_number' => 'Z9', 'expiration_date' => '2028-01-10', 'quantity' => 8]);
    }

    public function test_inactive_supply_404(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede API2',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-INA',
            'name' => 'Inactivo',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => false,
            'tracks_lot' => true,
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        $this->actingAs($user)
            ->getJson(route('supplies.available-lots', $supply).'?lab_branch_id='.$branch->id)
            ->assertNotFound();
    }
}
