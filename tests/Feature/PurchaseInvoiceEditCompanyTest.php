<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\LabBranch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use App\Services\AccountingEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseInvoiceEditCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'compras.section',
            'purchase-invoices.index',
            'purchase-invoices.edit',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    private function seedAccountsForPurchaseJournal(): void
    {
        foreach ([
            ['5.1.01', 'Insumos', 'resultado_negativo'],
            ['5.1.02', 'Servicios', 'resultado_negativo'],
            ['1.1.06', 'IVA crédito', 'activo'],
            ['2.1.01', 'Proveedores', 'pasivo'],
        ] as [$code, $name, $type]) {
            AccountingAccount::query()->create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_id' => null,
                'level' => 3,
                'is_header' => false,
                'is_active' => true,
            ]);
        }
    }

    /** @return array{0: User, 1: Company, 2: Company, 3: Supplier, 4: LabBranch, 5: Supply} */
    private function setupTwoCompaniesUserSupplier(): array
    {
        $companyA = Company::query()->create([
            'name' => 'Empresa A',
            'cuit' => '20-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $companyB = Company::query()->create([
            'name' => 'Empresa B',
            'cuit' => '20-22222222-2',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        foreach (['compras.section', 'purchase-invoices.index', 'purchase-invoices.edit'] as $p) {
            $user->givePermissionTo($p);
        }
        $user->companies()->attach($companyA->id, ['is_default' => true]);
        $user->companies()->attach($companyB->id, ['is_default' => false]);

        $suffix = uniqid('', true);
        $supplier = Supplier::query()->create([
            'code' => 'S-'.$suffix,
            'name' => 'Proveedor',
            'tax_id' => '30-'.$suffix,
            'status' => 'activo',
        ]);

        $branch = LabBranch::query()->create([
            'name' => 'Sede '.$suffix,
            'is_central' => true,
            'is_active' => true,
        ]);

        $supply = Supply::query()->create([
            'code' => 'INS-'.$suffix,
            'name' => 'Insumo',
            'unit' => 'u',
            'stock' => 0,
            'min_stock' => 0,
            'last_price' => 0,
            'is_active' => true,
            'tracks_lot' => false,
        ]);

        return [$user, $companyA, $companyB, $supplier, $branch, $supply];
    }

    private function buildUpdatePayload(PurchaseInvoice $invoice): array
    {
        $invoice->load('items');

        return [
            'company_id' => $invoice->company_id,
            'invoice_number' => $invoice->invoice_number,
            'voucher_type' => $invoice->voucher_type,
            'point_of_sale' => $invoice->point_of_sale,
            'supplier_id' => $invoice->supplier_id,
            'lab_branch_id' => $invoice->lab_branch_id,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date?->format('Y-m-d'),
            'percepciones' => $invoice->percepciones,
            'otros_impuestos' => $invoice->otros_impuestos,
            'notes' => $invoice->notes,
            'items' => $invoice->items->map(fn (PurchaseInvoiceItem $i) => [
                'description' => $i->description,
                'supply_id' => $i->supply_id,
                'quantity' => (int) $i->quantity,
                'unit_price' => (string) $i->unit_price,
                'iva_rate' => match ((string) (float) $i->iva_rate) {
                    '10.5' => '10.5',
                    '21' => '21',
                    '27' => '27',
                    default => '0',
                },
                'lot_number' => $i->lot_number,
                'expiration_date' => $i->expiration_date?->format('Y-m-d'),
                'updates_stock' => $i->updates_stock ? '1' : '0',
            ])->all(),
        ];
    }

    public function test_update_rejects_company_id_user_cannot_access(): void
    {
        [$user, $companyA, $companyB, $supplier, $branch, $supply] = $this->setupTwoCompaniesUserSupplier();

        $other = User::factory()->create();
        $other->givePermissionTo(['compras.section', 'purchase-invoices.index', 'purchase-invoices.edit']);
        $other->companies()->attach($companyA->id, ['is_default' => true]);

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $companyA->id,
            'lab_branch_id' => $branch->id,
            'invoice_number' => '00000001',
            'voucher_type' => 'B',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'delivery_note_id' => null,
            'purchase_order_id' => null,
            'issue_date' => now()->toDateString(),
            'due_date' => null,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'notes' => null,
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance' => 0,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        PurchaseInvoiceItem::query()->create([
            'purchase_invoice_id' => $invoice->id,
            'supply_id' => $supply->id,
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 100,
            'iva_rate' => 0,
            'iva_amount' => 0,
            'total' => 100,
            'updates_stock' => false,
        ]);
        $invoice->forceFill([
            'subtotal' => 100,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'total' => 100,
            'balance' => 100,
        ])->save();

        $payload = $this->buildUpdatePayload($invoice->fresh());
        $payload['company_id'] = $companyB->id;

        $response = $this->actingAs($other)
            ->withSession(['active_company_id' => $companyA->id])
            ->put(route('purchase-invoices.update', $invoice), $payload);

        $response->assertSessionHasErrors('company_id');
        $this->assertSame($companyA->id, $invoice->fresh()->company_id);
    }

    public function test_update_changes_company_and_regenerates_journal_in_new_company(): void
    {
        $this->seedAccountsForPurchaseJournal();
        [$user, $companyA, $companyB, $supplier, $branch, $supply] = $this->setupTwoCompaniesUserSupplier();

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $companyA->id,
            'lab_branch_id' => $branch->id,
            'invoice_number' => '00000002',
            'voucher_type' => 'B',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'delivery_note_id' => null,
            'purchase_order_id' => null,
            'issue_date' => now()->toDateString(),
            'due_date' => null,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'notes' => null,
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance' => 0,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        PurchaseInvoiceItem::query()->create([
            'purchase_invoice_id' => $invoice->id,
            'supply_id' => $supply->id,
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 100,
            'iva_rate' => 0,
            'iva_amount' => 0,
            'total' => 100,
            'updates_stock' => false,
        ]);
        $invoice->forceFill([
            'subtotal' => 100,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'total' => 100,
            'balance' => 100,
        ])->save();
        $invoice->refresh();

        $this->actingAs($user);
        (new AccountingEntryService)->fromPurchaseInvoice($invoice);
        $entry = JournalEntry::query()
            ->where('source_type', PurchaseInvoice::class)
            ->where('source_id', $invoice->id)
            ->first();
        $this->assertNotNull($entry);
        $this->assertSame($companyA->id, (int) $entry->company_id);

        $payload = $this->buildUpdatePayload($invoice);
        $payload['company_id'] = $companyB->id;

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->put(route('purchase-invoices.update', $invoice), $payload)
            ->assertRedirect(route('purchase-invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame($companyB->id, (int) $invoice->company_id);

        $entries = JournalEntry::query()
            ->where('source_type', PurchaseInvoice::class)
            ->where('source_id', $invoice->id)
            ->get();
        $this->assertCount(1, $entries);
        $this->assertSame($companyB->id, (int) $entries->first()->company_id);
    }
}
