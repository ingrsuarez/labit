<?php

namespace Tests\Unit;

use App\Models\LabBranch;
use App\Models\StockMovement;
use App\Models\Supply;
use App\Models\User;
use App\Services\SupplyLotBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyLotBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupplyLotBalanceService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new SupplyLotBalanceService;
    }

    public function test_entradas_con_lote_acumulan_saldo(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede L',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-L1',
            'name' => 'Insumo lote',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $uid = User::factory()->create()->id;

        foreach ([10.0, 5.0] as $qty) {
            StockMovement::query()->create([
                'supply_id' => $supply->id,
                'lab_branch_id' => $branch->id,
                'type' => 'entrada',
                'quantity' => $qty,
                'previous_stock' => 0,
                'new_stock' => $qty,
                'reason' => 'ajuste_manual',
                'lot_number' => ' L-A ',
                'expiration_date' => '2027-06-15',
                'reference_type' => null,
                'reference_id' => null,
                'notes' => null,
                'user_id' => $uid,
            ]);
        }

        $lots = $this->svc->availableLots($supply->id, $branch->id);
        $this->assertCount(1, $lots);
        $this->assertSame('L-A', $lots[0]->lot_number);
        $this->assertSame('2027-06-15', $lots[0]->expiration_date);
        $this->assertEqualsWithDelta(15.0, $lots[0]->quantity, 0.001);
    }

    public function test_entrada_y_salida_parcial(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede L2',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-L2',
            'name' => 'Insumo lote 2',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $uid = User::factory()->create()->id;

        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 20,
            'previous_stock' => 0,
            'new_stock' => 20,
            'reason' => 'ajuste_manual',
            'lot_number' => 'B1',
            'expiration_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $uid,
        ]);
        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'salida',
            'quantity' => 7,
            'previous_stock' => 20,
            'new_stock' => 13,
            'reason' => 'ajuste_manual',
            'lot_number' => 'B1',
            'expiration_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $uid,
        ]);

        $qty = $this->svc->quantityAvailableForLot($supply->id, $branch->id, 'B1', null);
        $this->assertEqualsWithDelta(13.0, $qty, 0.001);
    }

    public function test_dos_lotes_distintos(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede L3',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-L3',
            'name' => 'Insumo lote 3',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $uid = User::factory()->create()->id;

        foreach ([['L1', '2026-01-01', 3], ['L2', '2026-02-01', 4]] as [$lot, $date, $q]) {
            StockMovement::query()->create([
                'supply_id' => $supply->id,
                'lab_branch_id' => $branch->id,
                'type' => 'entrada',
                'quantity' => $q,
                'previous_stock' => 0,
                'new_stock' => $q,
                'reason' => 'ajuste_manual',
                'lot_number' => $lot,
                'expiration_date' => $date,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => null,
                'user_id' => $uid,
            ]);
        }

        $lots = $this->svc->availableLots($supply->id, $branch->id)->keyBy(fn ($r) => $r->lot_number);
        $this->assertCount(2, $lots);
        $this->assertEqualsWithDelta(3.0, $lots['L1']->quantity, 0.001);
        $this->assertEqualsWithDelta(4.0, $lots['L2']->quantity, 0.001);
    }

    public function test_ajuste_no_afecta_buckets(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede L4',
            'is_central' => true,
            'is_active' => true,
        ]);
        $supply = Supply::query()->create([
            'code' => 'INS-L4',
            'name' => 'Insumo lote 4',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);
        $uid = User::factory()->create()->id;

        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'entrada',
            'quantity' => 5,
            'previous_stock' => 0,
            'new_stock' => 5,
            'reason' => 'ajuste_manual',
            'lot_number' => 'X',
            'expiration_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $uid,
        ]);
        StockMovement::query()->create([
            'supply_id' => $supply->id,
            'lab_branch_id' => $branch->id,
            'type' => 'ajuste',
            'quantity' => 100,
            'previous_stock' => 5,
            'new_stock' => 100,
            'reason' => 'ajuste_manual',
            'lot_number' => 'X',
            'expiration_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => $uid,
        ]);

        $lots = $this->svc->availableLots($supply->id, $branch->id);
        $this->assertCount(1, $lots);
        $this->assertEqualsWithDelta(5.0, $lots[0]->quantity, 0.001);
    }
}
