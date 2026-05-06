<?php

namespace Tests\Feature\TaxReturn;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoicePerception;
use App\Models\PurchasePerception;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\TaxReturn;
use App\Models\TaxReturnApplication;
use App\Models\User;
use App\Services\AccountingEntryService;
use App\Services\TaxReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxDeclarationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedAccountsFull(): void
    {
        foreach (['1.1.05', '1.1.06', '2.1.01', '2.1.05', '5.1.01', '5.1.02'] as $code) {
            AccountingAccount::query()->create([
                'code' => $code,
                'name' => 'Cuenta '.$code,
                'type' => str_starts_with($code, '2') ? 'pasivo' : (str_starts_with($code, '5') ? 'resultado_negativo' : 'activo'),
                'level' => 3,
                'is_header' => false,
                'is_active' => true,
            ]);
        }
    }

    public function test_guest_cannot_access_taxes_index(): void
    {
        $this->get('/taxes')->assertRedirect();
    }

    public function test_confirm_generates_balanced_journal_entry(): void
    {
        $this->seedAccountsFull();

        $company = Company::query()->create([
            'name' => 'Empresa DDJJ Test',
            'cuit' => '30-70000000-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $liability = AccountingAccount::where('code', '2.1.05')->first();
        $asset = AccountingAccount::where('code', '1.1.06')->first();

        $tax = Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA Test',
            'liability_account_id' => $liability->id,
            'frequency' => 'monthly',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $perception = PurchasePerception::create([
            'company_id' => $company->id,
            'accounting_account_id' => $asset->id,
            'tax_id' => $tax->id,
            'name' => 'Percep test',
            'rate' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        foreach (['taxes.manage', 'tax-returns.manage', 'compras.section'] as $p) {
            Permission::findOrCreate($p);
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['taxes.manage', 'tax-returns.manage', 'compras.section']);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $supplier = Supplier::create([
            'code' => 'S-DDJJ',
            'name' => 'Prov test',
            'tax_id' => '30-11111111-1',
            'status' => 'activo',
        ]);

        $invoice = PurchaseInvoice::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'DDJJ-1',
            'voucher_type' => 'A',
            'point_of_sale' => 1,
            'issue_date' => now()->startOfMonth()->toDateString(),
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 50,
            'otros_impuestos' => 0,
            'total' => 50,
            'amount_paid' => 0,
            'balance' => 50,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $pip = PurchaseInvoicePerception::create([
            'purchase_invoice_id' => $invoice->id,
            'purchase_perception_id' => $perception->id,
            'accounting_account_id' => $perception->accounting_account_id,
            'name_snapshot' => $perception->name,
            'amount' => 50,
            'sort_order' => 1,
        ]);

        $entryFc = (new AccountingEntryService)->fromPurchaseInvoice($invoice->fresh(['perceptions.accountingAccount']));
        $this->assertNotNull($entryFc);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $taxReturn = TaxReturn::create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'declared_amount' => 80,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        TaxReturnApplication::create([
            'tax_return_id' => $taxReturn->id,
            'purchase_invoice_perception_id' => $pip->id,
            'amount_applied' => 50,
        ]);

        $taxReturn->recalculateTotals();
        $taxReturn->save();

        $entry = (new TaxReturnService)->confirm($taxReturn->fresh(['applications', 'tax.liabilityAccount']));
        $this->assertNotNull($entry);

        $lines = JournalEntry::with('lines')->find($entry->id)->lines;
        $dr = round((float) $lines->sum('debit'), 2);
        $cr = round((float) $lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($dr, $cr, 0.02);

        $this->assertTrue($taxReturn->fresh()->isConfirmed());
    }
}
