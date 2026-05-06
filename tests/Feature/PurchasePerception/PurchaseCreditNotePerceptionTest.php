<?php

namespace Tests\Feature\PurchasePerception;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\PurchaseCreditNote;
use App\Models\PurchasePerception;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingEntryService;
use App\Services\PurchasePerceptionBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseCreditNotePerceptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedAccounts(): void
    {
        foreach (['1.1.05', '1.1.06', '2.1.01', '5.1.01', '5.1.02'] as $code) {
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

    private function makeCompany(): Company
    {
        return Company::query()->create([
            'name' => 'Empresa Test '.uniqid(),
            'cuit' => '20-'.uniqid().'0-0',
            'tax_condition' => 'responsable_inscripto',
        ]);
    }

    private function makeBranch(): LabBranch
    {
        return LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);
    }

    private function makeSupplier(): Supplier
    {
        $sfx = uniqid();

        return Supplier::query()->create([
            'code' => 'S-'.$sfx,
            'name' => 'Proveedor '.$sfx,
            'tax_id' => '30-'.$sfx,
            'status' => 'activo',
        ]);
    }

    private function makeNcUser(Company $company): User
    {
        foreach ([
            'compras.section',
            'purchase-credit-notes.index', 'purchase-credit-notes.create', 'purchase-credit-notes.delete',
            'purchase-invoices.index', 'purchase-invoices.create',
            'purchase-perceptions.index', 'purchase-perceptions.balances',
        ] as $p) {
            Permission::findOrCreate($p);
        }

        $user = User::factory()->create();
        $user->givePermissionTo([
            'compras.section',
            'purchase-credit-notes.index', 'purchase-credit-notes.create', 'purchase-credit-notes.delete',
            'purchase-invoices.index', 'purchase-invoices.create',
            'purchase-perceptions.index', 'purchase-perceptions.balances',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        return $user;
    }

    public function test_nc_de_proveedor_con_percepciones_se_guarda_correctamente(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $branch = $this->makeBranch();
        $supplier = $this->makeSupplier();
        $user = $this->makeNcUser($company);
        $account = AccountingAccount::where('code', '1.1.05')->first();

        $perception = PurchasePerception::create([
            'company_id' => $company->id,
            'accounting_account_id' => $account->id,
            'name' => 'IIBB Neuquén',
            'jurisdiction' => 'Neuquén',
            'rate' => 1.5,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $response = $this->post(route('purchase-credit-notes.store'), [
            'credit_note_number' => 'NC-PERC-001',
            'voucher_type' => 'A',
            'point_of_sale' => '00001',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'issue_date' => now()->toDateString(),
            'otros_impuestos' => 0,
            'items' => [[
                'description' => 'Ajuste',
                'supply_id' => null,
                'purchase_service_id' => null,
                'quantity' => 1,
                'unit_price' => 100,
                'iva_rate' => '21',
            ]],
            'perceptions' => [[
                'purchase_perception_id' => $perception->id,
                'name_snapshot' => 'IIBB Neuquén',
                'jurisdiction_snapshot' => 'Neuquén',
                'rate_snapshot' => 1.5,
                'accounting_account_id' => $account->id,
                'amount' => 50.00,
            ]],
        ]);

        $response->assertRedirect();
        $cn = PurchaseCreditNote::where('credit_note_number', 'NC-PERC-001')->first();
        $this->assertNotNull($cn);
        $this->assertCount(1, $cn->perceptions);
        $this->assertEqualsWithDelta(50.00, (float) $cn->percepciones, 0.01);
    }

    public function test_recalculate_de_nc_suma_percepciones_desde_pivote(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $supplier = $this->makeSupplier();
        $user = User::factory()->create();
        $branch = $this->makeBranch();
        $account = AccountingAccount::where('code', '1.1.05')->first();

        $cn = PurchaseCreditNote::create([
            'company_id' => $company->id,
            'lab_branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'credit_note_number' => 'NC-R-001',
            'voucher_type' => 'A',
            'issue_date' => now()->toDateString(),
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'created_by' => $user->id,
        ]);

        $cn->perceptions()->create([
            'accounting_account_id' => $account->id,
            'name_snapshot' => 'A',
            'amount' => 200.00,
            'sort_order' => 0,
        ]);
        $cn->perceptions()->create([
            'accounting_account_id' => $account->id,
            'name_snapshot' => 'B',
            'amount' => 50.00,
            'sort_order' => 1,
        ]);

        $cn->recalculate();

        $this->assertEqualsWithDelta(250.00, (float) $cn->fresh()->percepciones, 0.01);
    }

    public function test_asiento_contable_acredita_cada_percepcion_en_su_cuenta_snapshot(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $supplier = $this->makeSupplier();
        $user = User::factory()->create();
        $branch = $this->makeBranch();
        $account1 = AccountingAccount::where('code', '1.1.05')->first();
        $account2 = AccountingAccount::where('code', '1.1.06')->first();

        $cn = PurchaseCreditNote::create([
            'company_id' => $company->id,
            'lab_branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'credit_note_number' => 'NC-AST-001',
            'voucher_type' => 'A',
            'issue_date' => now()->toDateString(),
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 250,
            'otros_impuestos' => 0,
            'total' => 250,
            'created_by' => $user->id,
        ]);

        $cn->perceptions()->create([
            'accounting_account_id' => $account1->id,
            'name_snapshot' => 'IIBB',
            'jurisdiction_snapshot' => 'NQ',
            'rate_snapshot' => 1.5,
            'amount' => 150,
            'sort_order' => 0,
        ]);
        $cn->perceptions()->create([
            'accounting_account_id' => $account2->id,
            'name_snapshot' => 'IVA RG',
            'amount' => 100,
            'sort_order' => 1,
        ]);

        $cn->load('perceptions.accountingAccount', 'items', 'supplier');
        $entry = (new AccountingEntryService)->fromPurchaseCreditNote($cn);

        $this->assertNotNull($entry);
        $lines = $entry->lines;

        $lineAccount1 = $lines->where('accounting_account_id', $account1->id)->first();
        $lineAccount2 = $lines->where('accounting_account_id', $account2->id)->first();

        $this->assertNotNull($lineAccount1);
        $this->assertNotNull($lineAccount2);
        $this->assertEqualsWithDelta(150.0, (float) $lineAccount1->credit, 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $lineAccount2->credit, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $lineAccount1->debit, 0.01);

        $totalDebit = $lines->sum(fn ($l) => (float) $l->debit);
        $totalCredit = $lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }

    public function test_eliminar_nc_borra_lineas_de_pivote(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $branch = $this->makeBranch();
        $supplier = $this->makeSupplier();
        $user = $this->makeNcUser($company);
        $account = AccountingAccount::where('code', '1.1.05')->first();

        $perception = PurchasePerception::create([
            'company_id' => $company->id,
            'accounting_account_id' => $account->id,
            'name' => 'Test Perc',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $this->post(route('purchase-credit-notes.store'), [
            'credit_note_number' => 'NC-DEL-001',
            'voucher_type' => 'A',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'issue_date' => now()->toDateString(),
            'otros_impuestos' => 0,
            'items' => [[
                'description' => 'X',
                'supply_id' => null,
                'purchase_service_id' => null,
                'quantity' => 1,
                'unit_price' => 10,
                'iva_rate' => '21',
            ]],
            'perceptions' => [[
                'purchase_perception_id' => $perception->id,
                'name_snapshot' => 'Test Perc',
                'rate_snapshot' => 0,
                'accounting_account_id' => $account->id,
                'amount' => 25,
            ]],
        ])->assertRedirect();

        $cn = PurchaseCreditNote::where('credit_note_number', 'NC-DEL-001')->first();
        $this->assertDatabaseHas('purchase_credit_note_perceptions', [
            'purchase_credit_note_id' => $cn->id,
        ]);

        $this->delete(route('purchase-credit-notes.destroy', $cn));

        $this->assertDatabaseMissing('purchase_credit_notes', ['id' => $cn->id]);
        $this->assertDatabaseMissing('purchase_credit_note_perceptions', [
            'purchase_credit_note_id' => $cn->id,
        ]);
    }

    public function test_balance_resta_percepciones_de_nc(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $branch = $this->makeBranch();
        $supplier = $this->makeSupplier();
        $user = $this->makeNcUser($company);
        $account = AccountingAccount::where('code', '1.1.05')->first();

        $perception = PurchasePerception::create([
            'company_id' => $company->id,
            'accounting_account_id' => $account->id,
            'name' => 'IIBB Balance',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        $this->post('/purchase-invoices', [
            'invoice_number' => 'FC-BAL-001',
            'voucher_type' => 'A',
            'point_of_sale' => '0001',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'issue_date' => now()->toDateString(),
            'otros_impuestos' => 0,
            'items' => [[
                'description' => 'Compra',
                'supply_id' => null,
                'quantity' => 1,
                'unit_price' => 1000,
                'iva_rate' => '21',
                'updates_stock' => false,
            ]],
            'perceptions' => [[
                'purchase_perception_id' => $perception->id,
                'name_snapshot' => 'IIBB Balance',
                'rate_snapshot' => 0,
                'accounting_account_id' => $account->id,
                'amount' => 1000.00,
            ]],
        ])->assertRedirect();

        $this->post(route('purchase-credit-notes.store'), [
            'credit_note_number' => 'NC-BAL-001',
            'voucher_type' => 'A',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'issue_date' => now()->toDateString(),
            'otros_impuestos' => 0,
            'items' => [[
                'description' => 'Devolución perc',
                'supply_id' => null,
                'purchase_service_id' => null,
                'quantity' => 1,
                'unit_price' => 0,
                'iva_rate' => '0',
            ]],
            'perceptions' => [[
                'purchase_perception_id' => $perception->id,
                'name_snapshot' => 'IIBB Balance',
                'rate_snapshot' => 0,
                'accounting_account_id' => $account->id,
                'amount' => 200.00,
            ]],
        ])->assertRedirect();

        $svc = new PurchasePerceptionBalanceService;
        $rows = $svc->getBalances((int) $company->id, $from, $to);
        $row = $rows->firstWhere(fn ($r) => $r['perception']->id === $perception->id);

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(800.0, (float) $row['anticipos_cargados'], 0.01);
    }
}
