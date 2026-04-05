<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DeliveryNoteAcceptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['compras.section', 'delivery-notes.index', 'delivery-notes.edit'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    private function comprasUserWithCompany(): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'cuit' => '20-12345678-9',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo([
            'compras.section',
            'delivery-notes.index',
            'delivery-notes.edit',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        return [$user, $company];
    }

    private function createPendingNoteWithTwoSupplies(): array
    {
        [$user, $company] = $this->comprasUserWithCompany();

        $suffix = uniqid('', true);
        $supplier = Supplier::query()->create([
            'code' => 'S-'.$suffix,
            'name' => 'Proveedor Test',
            'tax_id' => '30-'.$suffix,
            'status' => 'activo',
        ]);

        $supplyNoLot = Supply::query()->create([
            'code' => 'INS-NL-'.$suffix,
            'name' => 'Insumo sin lote',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $supplyWithLot = Supply::query()->create([
            'code' => 'INS-L-'.$suffix,
            'name' => 'Insumo con lote',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => true,
        ]);

        $note = DeliveryNote::query()->create([
            'company_id' => $company->id,
            'remito_number' => 'R-'.$suffix,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => null,
            'date' => now()->toDateString(),
            'status' => 'pendiente',
            'notes' => null,
            'received_by' => $user->id,
        ]);

        $itemNoLot = DeliveryNoteItem::query()->create([
            'delivery_note_id' => $note->id,
            'supply_id' => $supplyNoLot->id,
            'purchase_order_item_id' => null,
            'quantity_received' => 2,
            'lot_number' => null,
            'expiration_date' => null,
            'notes' => null,
        ]);

        $itemWithLot = DeliveryNoteItem::query()->create([
            'delivery_note_id' => $note->id,
            'supply_id' => $supplyWithLot->id,
            'purchase_order_item_id' => null,
            'quantity_received' => 3,
            'lot_number' => null,
            'expiration_date' => null,
            'notes' => null,
        ]);

        return [$user, $company, $note, $itemNoLot, $itemWithLot, $supplyNoLot, $supplyWithLot];
    }

    public function test_accept_succeeds_when_only_tracks_lot_item_has_lot_data(): void
    {
        [$user, $company, $note, $itemNoLot, $itemWithLot, $supplyNoLot, $supplyWithLot] = $this->createPendingNoteWithTwoSupplies();

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('delivery-notes.accept', $note), [
                'items' => [
                    $itemWithLot->id => [
                        'lot_number' => 'LOT-001',
                        'expiration_date' => '2030-06-15',
                    ],
                ],
            ]);

        $response->assertRedirect(route('delivery-notes.show', $note));
        $note->refresh();
        $this->assertSame('aceptado', $note->status);
        $this->assertSame(2.0, (float) $supplyNoLot->refresh()->stock);
        $this->assertSame(3.0, (float) $supplyWithLot->refresh()->stock);

        $movement = StockMovement::query()
            ->where('supply_id', $supplyWithLot->id)
            ->where('reference_id', $note->id)
            ->first();
        $this->assertNotNull($movement);
        $this->assertSame('LOT-001', $movement->lot_number);
    }

    public function test_accept_validation_fails_when_tracks_lot_item_missing_lot(): void
    {
        [$user, $company, $note, , $itemWithLot] = $this->createPendingNoteWithTwoSupplies();

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('delivery-notes.accept', $note), [
                'items' => [
                    $itemWithLot->id => [
                        'lot_number' => '',
                        'expiration_date' => '',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
        $this->assertSame('pendiente', $note->fresh()->status);
    }

    public function test_show_displays_lot_control_badges(): void
    {
        [$user, $company, $note] = $this->createPendingNoteWithTwoSupplies();

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('delivery-notes.show', $note));

        $response->assertOk();
        $response->assertSee('Controla lote', false);
        $response->assertSee('Sin lote', false);
    }
}
