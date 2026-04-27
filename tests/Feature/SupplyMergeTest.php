<?php

namespace Tests\Feature;

use App\Models\LabBranch;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['compras.section', 'supplies.index', 'supplies.edit'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    private function userWithMergePermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['compras.section', 'supplies.index', 'supplies.edit']);

        return $user;
    }

    private function makeSupply(array $overrides = []): Supply
    {
        static $counter = 0;
        $counter++;

        return Supply::query()->create(array_merge([
            'code' => 'INS-MERGE-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'name' => 'Insumo merge '.$counter,
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ], $overrides));
    }

    public function test_merge_reasigna_stock_movements_y_desactiva_origen(): void
    {
        $user = $this->userWithMergePermission();
        $source = $this->makeSupply(['stock' => 10]);
        $target = $this->makeSupply(['stock' => 5]);

        DB::table('stock_movements')->insert([
            'supply_id' => $source->id,
            'type' => 'entrada',
            'quantity' => 10,
            'previous_stock' => 0,
            'new_stock' => 10,
            'reason' => 'compra',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('supplies.merge', $source), [
            'target_id' => $target->id,
        ]);

        $response->assertRedirect(route('supplies.index'));

        $this->assertEquals(15, (float) $target->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'supply_id' => $target->id,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'supply_id' => $source->id,
        ]);

        $this->assertFalse($source->fresh()->is_active);
        $this->assertStringStartsWith('[UNIFICADO', $source->fresh()->name);
    }

    public function test_merge_suma_stock_por_sede_cuando_destino_ya_tiene_la_sede(): void
    {
        $user = $this->userWithMergePermission();
        $source = $this->makeSupply(['stock' => 0]);
        $target = $this->makeSupply(['stock' => 0]);

        $branch = LabBranch::query()->create([
            'name' => 'Sede merge',
            'is_central' => true,
            'is_active' => true,
        ]);

        DB::table('supply_lab_branch_stock')->insert([
            ['supply_id' => $source->id, 'lab_branch_id' => $branch->id, 'quantity' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['supply_id' => $target->id, 'lab_branch_id' => $branch->id, 'quantity' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user)->post(route('supplies.merge', $source), [
            'target_id' => $target->id,
        ])->assertRedirect();

        $this->assertEquals(10.0, (float) DB::table('supply_lab_branch_stock')
            ->where('supply_id', $target->id)
            ->where('lab_branch_id', $branch->id)
            ->value('quantity'));

        $this->assertDatabaseMissing('supply_lab_branch_stock', [
            'supply_id' => $source->id,
            'lab_branch_id' => $branch->id,
        ]);
    }

    public function test_merge_rechaza_mismo_insumo(): void
    {
        $user = $this->userWithMergePermission();
        $supply = $this->makeSupply();

        $response = $this->actingAs($user)->post(route('supplies.merge', $supply), [
            'target_id' => $supply->id,
        ]);

        $response->assertSessionHasErrors('target_id');
    }

    public function test_merge_rechaza_destino_inactivo(): void
    {
        $user = $this->userWithMergePermission();
        $source = $this->makeSupply();
        $target = $this->makeSupply(['is_active' => false]);

        $response = $this->actingAs($user)->post(route('supplies.merge', $source), [
            'target_id' => $target->id,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_preview_devuelve_conteos_de_referencias(): void
    {
        $user = $this->userWithMergePermission();
        $source = $this->makeSupply();

        DB::table('stock_movements')->insert([
            [
                'supply_id' => $source->id,
                'type' => 'entrada',
                'quantity' => 1,
                'previous_stock' => 0,
                'new_stock' => 1,
                'reason' => 'compra',
                'reference_type' => null,
                'reference_id' => null,
                'notes' => null,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supply_id' => $source->id,
                'type' => 'salida',
                'quantity' => 1,
                'previous_stock' => 1,
                'new_stock' => 0,
                'reason' => 'consumo',
                'reference_type' => null,
                'reference_id' => null,
                'notes' => null,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('supplies.merge-preview', $source));

        $response->assertOk();
        $response->assertJson([
            'stock_movements' => 2,
            'purchase_invoice_items' => 0,
            'purchase_order_items' => 0,
            'delivery_note_items' => 0,
            'purchase_quotation_request_items' => 0,
            'purchase_credit_note_items' => 0,
            'branch_stocks' => 0,
        ]);
    }
}
