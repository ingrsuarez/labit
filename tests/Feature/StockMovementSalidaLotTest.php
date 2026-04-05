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

class StockMovementSalidaLotTest extends TestCase
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

    public function test_salida_tracks_lot_rechaza_cantidad_mayor_que_lote(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede S',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-SL',
            'name' => 'Insumo salida lote',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 5,
            'previous_stock' => 0,
            'new_stock' => 5,
            'reason' => 'ajuste_manual',
            'lot_number' => 'LIM',
            'expiration_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $user->id,
        ]);

        \App\Models\SupplyLabBranchStock::query()->updateOrInsert(
            ['supply_id' => $supply->id, 'lab_branch_id' => $branch->id],
            ['quantity' => 5, 'created_at' => now(), 'updated_at' => now()]
        );
        $supply->update(['stock' => 5]);

        $response = $this->actingAs($user)->post(route('stock-movements.store'), [
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'salida',
            'quantity' => 6,
            'lot_number' => 'LIM',
            'expiration_date' => '',
            'manual_lot_exit' => '0',
            'notes' => null,
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_salida_manual_con_confirmacion_omite_bucket(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede S2',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-SL2',
            'name' => 'Insumo salida manual',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 10,
            'previous_stock' => 0,
            'new_stock' => 10,
            'reason' => 'ajuste_manual',
            'lot_number' => 'OLD',
            'expiration_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $user->id,
        ]);

        \App\Models\SupplyLabBranchStock::query()->updateOrInsert(
            ['supply_id' => $supply->id, 'lab_branch_id' => $branch->id],
            ['quantity' => 10, 'created_at' => now(), 'updated_at' => now()]
        );
        $supply->update(['stock' => 10]);

        $response = $this->actingAs($user)->post(route('stock-movements.store'), [
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'salida',
            'quantity' => 2,
            'lot_number' => 'NEWLOT',
            'expiration_date' => '',
            'manual_lot_exit' => '1',
            'confirm_manual_lot_exit' => '1',
            'notes' => null,
        ]);

        $response->assertRedirect(route('stock-movements.index'));
        $this->assertDatabaseHas('stock_movements', [
            'supply_id' => $supply->id,
            'type' => 'salida',
            'lot_number' => 'NEWLOT',
        ]);
    }
}
