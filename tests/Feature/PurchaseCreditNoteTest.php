<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedAccountingAccounts(): void
    {
        foreach (['1.1.06', '2.1.01', '5.1.01', '5.1.02'] as $code) {
            AccountingAccount::create([
                'code' => $code,
                'name' => 'Cuenta '.$code,
                'type' => str_starts_with($code, '2') ? 'pasivo' : (str_starts_with($code, '5') ? 'resultado_negativo' : 'activo'),
                'level' => 3,
                'is_header' => false,
                'is_active' => true,
            ]);
        }
    }

    private function actingComprasUser(): User
    {
        foreach (['compras.section', 'purchase-credit-notes.index', 'purchase-credit-notes.create', 'purchase-credit-notes.delete', 'purchase-invoices.index'] as $p) {
            Permission::findOrCreate($p);
        }
        $user = User::factory()->create();
        $user->givePermissionTo([
            'compras.section',
            'purchase-credit-notes.index',
            'purchase-credit-notes.create',
            'purchase-credit-notes.delete',
            'purchase-invoices.index',
        ]);

        return $user;
    }

    public function test_store_applies_to_invoice_reduces_balance_and_creates_journal(): void
    {
        $this->seedAccountingAccounts();

        $company = Company::query()->create([
            'name' => 'Empresa NC',
            'cuit' => '20-22222222-2',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $branch = LabBranch::query()->create([
            'name' => 'Sede NC',
            'is_central' => true,
            'is_active' => true,
        ]);
        $sfx = uniqid();
        $supplier = Supplier::query()->create([
            'code' => 'S-NC-'.$sfx,
            'name' => 'Proveedor',
            'tax_id' => '30-'.$sfx,
            'status' => 'activo',
        ]);

        $user = $this->actingComprasUser();
        $user->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $invoice = PurchaseInvoice::create([
            'company_id' => $company->id,
            'lab_branch_id' => $branch->id,
            'invoice_number' => '00000001',
            'voucher_type' => 'A',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'issue_date' => now(),
            'subtotal' => 100,
            'iva_21' => 21,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'total' => 121,
            'amount_paid' => 0,
            'balance' => 121,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $response = $this->post(route('purchase-credit-notes.store'), [
            'credit_note_number' => '00000002',
            'voucher_type' => 'A',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'purchase_invoice_id' => $invoice->id,
            'issue_date' => now()->format('Y-m-d'),
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'items' => [
                [
                    'description' => 'Devolución',
                    'supply_id' => null,
                    'purchase_service_id' => null,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'iva_rate' => '21',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_credit_notes', [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'purchase_invoice_id' => $invoice->id,
        ]);

        $invoice->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $invoice->balance, 0.02);
        $this->assertSame('pagada', $invoice->status);

        $cn = PurchaseCreditNote::first();
        $this->assertNotNull($cn);
        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'source_type' => PurchaseCreditNote::class,
            'source_id' => $cn->id,
        ]);
    }

    public function test_store_without_invoice_does_not_change_invoice_balance(): void
    {
        $this->seedAccountingAccounts();

        $company = Company::query()->create([
            'name' => 'Empresa NC',
            'cuit' => '20-22222222-2',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $branch = LabBranch::query()->create([
            'name' => 'Sede NC',
            'is_central' => true,
            'is_active' => true,
        ]);
        $sfx = uniqid();
        $supplier = Supplier::query()->create([
            'code' => 'S-NC-'.$sfx,
            'name' => 'Proveedor',
            'tax_id' => '30-'.$sfx,
            'status' => 'activo',
        ]);

        $user = $this->actingComprasUser();
        $user->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $invoice = PurchaseInvoice::create([
            'company_id' => $company->id,
            'lab_branch_id' => $branch->id,
            'invoice_number' => '00000003',
            'voucher_type' => 'A',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'issue_date' => now(),
            'subtotal' => 100,
            'iva_21' => 21,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'total' => 121,
            'amount_paid' => 0,
            'balance' => 121,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $this->post(route('purchase-credit-notes.store'), [
            'credit_note_number' => '00000004',
            'voucher_type' => 'A',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'issue_date' => now()->format('Y-m-d'),
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'items' => [
                [
                    'description' => 'Bonificación',
                    'quantity' => 1,
                    'unit_price' => 50,
                    'iva_rate' => '21',
                ],
            ],
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertEqualsWithDelta(121.0, (float) $invoice->balance, 0.02);

        $cn = PurchaseCreditNote::first();
        $this->assertNull($cn->purchase_invoice_id);
    }
}
