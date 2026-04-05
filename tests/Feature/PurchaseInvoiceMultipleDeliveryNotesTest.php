<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseInvoiceMultipleDeliveryNotesTest extends TestCase
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
            'purchase-invoices.edit',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    private function setupUserCompanySupplier(): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa FC',
            'cuit' => '20-99999999-9',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        foreach ([
            'compras.section',
            'purchase-invoices.index',
            'purchase-invoices.create',
            'purchase-invoices.edit',
        ] as $p) {
            $user->givePermissionTo($p);
        }
        $user->companies()->attach($company->id, ['is_default' => true]);

        $suffix = uniqid('', true);
        $supplier = Supplier::query()->create([
            'code' => 'SFC-'.$suffix,
            'name' => 'Proveedor FC',
            'tax_id' => '30-'.$suffix,
            'status' => 'activo',
        ]);

        return [$user, $company, $supplier];
    }

    private function createAcceptedNote(int $companyId, int $supplierId, int $userId, Supply $supply, string $remitoNumber): DeliveryNote
    {
        $note = DeliveryNote::query()->create([
            'company_id' => $companyId,
            'remito_number' => $remitoNumber,
            'supplier_id' => $supplierId,
            'purchase_order_id' => null,
            'date' => now()->toDateString(),
            'status' => 'aceptado',
            'notes' => null,
            'received_by' => $userId,
        ]);
        DeliveryNoteItem::query()->create([
            'delivery_note_id' => $note->id,
            'supply_id' => $supply->id,
            'purchase_order_item_id' => null,
            'quantity_received' => 1,
            'lot_number' => null,
            'expiration_date' => null,
            'notes' => null,
        ]);

        return $note;
    }

    public function test_store_syncs_three_delivery_notes_and_sets_legacy_first(): void
    {
        [$user, $company, $supplier] = $this->setupUserCompanySupplier();
        $supply = Supply::query()->create([
            'code' => 'INS-A',
            'name' => 'Insumo A',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $dn1 = $this->createAcceptedNote($company->id, $supplier->id, $user->id, $supply, 'R-1');
        $dn2 = $this->createAcceptedNote($company->id, $supplier->id, $user->id, $supply, 'R-2');
        $dn3 = $this->createAcceptedNote($company->id, $supplier->id, $user->id, $supply, 'R-3');

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('purchase-invoices.store'), [
                'invoice_number' => '00000002',
                'voucher_type' => 'B',
                'point_of_sale' => '00002',
                'supplier_id' => $supplier->id,
                'delivery_note_ids' => [$dn1->id, $dn2->id, $dn3->id],
                'issue_date' => now()->toDateString(),
                'percepciones' => 0,
                'otros_impuestos' => 0,
                'items' => [
                    [
                        'description' => 'Línea 1',
                        'supply_id' => $supply->id,
                        'quantity' => 1,
                        'unit_price' => 10,
                        'iva_rate' => 21,
                        'updates_stock' => true,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $invoice = PurchaseInvoice::query()->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertCount(3, $invoice->deliveryNotes);
        $this->assertSame($dn1->id, $invoice->delivery_note_id);
        $this->assertDatabaseCount('delivery_note_purchase_invoice', 3);
    }

    public function test_cannot_attach_delivery_note_already_linked_to_another_invoice(): void
    {
        [$user, $company, $supplier] = $this->setupUserCompanySupplier();
        $supply = Supply::query()->create([
            'code' => 'INS-B',
            'name' => 'Insumo B',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        $dn = $this->createAcceptedNote($company->id, $supplier->id, $user->id, $supply, 'R-TAKEN');

        $first = PurchaseInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000010',
            'voucher_type' => 'B',
            'supplier_id' => $supplier->id,
            'delivery_note_id' => $dn->id,
            'issue_date' => now(),
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance' => 0,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);
        $first->deliveryNotes()->sync([$dn->id]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('purchase-invoices.store'), [
                'invoice_number' => '00000011',
                'voucher_type' => 'B',
                'supplier_id' => $supplier->id,
                'delivery_note_ids' => [$dn->id],
                'issue_date' => now()->toDateString(),
                'percepciones' => 0,
                'otros_impuestos' => 0,
                'items' => [
                    [
                        'description' => 'X',
                        'supply_id' => $supply->id,
                        'quantity' => 1,
                        'unit_price' => 1,
                        'iva_rate' => 21,
                        'updates_stock' => true,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('delivery_note_ids');
    }

    public function test_delivery_note_has_purchase_invoice_when_in_pivot(): void
    {
        [$user, $company, $supplier] = $this->setupUserCompanySupplier();
        $supply = Supply::query()->create([
            'code' => 'INS-C',
            'name' => 'Insumo C',
            'unit' => 'unidad',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);
        $dn = $this->createAcceptedNote($company->id, $supplier->id, $user->id, $supply, 'R-PV');

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000020',
            'voucher_type' => 'B',
            'supplier_id' => $supplier->id,
            'delivery_note_id' => $dn->id,
            'issue_date' => now(),
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance' => 0,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);
        DB::table('delivery_note_purchase_invoice')->insert([
            'purchase_invoice_id' => $invoice->id,
            'delivery_note_id' => $dn->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($dn->fresh()->hasPurchaseInvoice());
    }
}
