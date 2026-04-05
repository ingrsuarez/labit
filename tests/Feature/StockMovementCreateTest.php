<?php

namespace Tests\Feature;

use App\Models\LabBranch;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StockMovementCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['compras.section', 'stock-movements.create', 'stock-movements.index'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function test_store_rechaza_cantidad_decimal(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);

        $supply = Supply::query()->create([
            'code' => 'INS-MOV',
            'name' => 'Insumo mov',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        $response = $this->actingAs($user)->post(route('stock-movements.store'), [
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 1.5,
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_store_acepta_cantidad_entera_entrada(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Test B',
            'is_central' => true,
            'is_active' => true,
        ]);

        $supply = Supply::query()->create([
            'code' => 'INS-MOV2',
            'name' => 'Insumo mov 2',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        $response = $this->actingAs($user)->post(route('stock-movements.store'), [
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('stock-movements.index'));
        $supply->refresh();
        $this->assertSame(2.0, (float) $supply->stock);
    }

    public function test_store_ajuste_permite_cero(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Test C',
            'is_central' => true,
            'is_active' => true,
        ]);

        $supply = Supply::query()->create([
            'code' => 'INS-MOV3',
            'name' => 'Insumo mov 3',
            'unit' => 'unidad',
            'stock' => 10,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $user->givePermissionTo(['compras.section', 'stock-movements.create']);

        $response = $this->actingAs($user)->post(route('stock-movements.store'), [
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'ajuste',
            'quantity' => 0,
        ]);

        $response->assertRedirect(route('stock-movements.index'));
        $supply->refresh();
        $this->assertSame(0.0, (float) $supply->stock);
    }
}
