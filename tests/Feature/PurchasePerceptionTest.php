<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePerception;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchasePerceptionTest extends TestCase
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

    private function makeAdmin(Company $company): User
    {
        foreach ([
            'purchase-perceptions.index', 'purchase-perceptions.create',
            'purchase-perceptions.edit', 'purchase-perceptions.destroy',
            'purchase-perceptions.balances', 'compras.section',
            'purchase-invoices.create', 'purchase-invoices.index',
        ] as $p) {
            Permission::findOrCreate($p);
        }

        $user = User::factory()->create();
        $user->givePermissionTo([
            'purchase-perceptions.index', 'purchase-perceptions.create',
            'purchase-perceptions.edit', 'purchase-perceptions.destroy',
            'purchase-perceptions.balances', 'compras.section',
            'purchase-invoices.create', 'purchase-invoices.index',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        return $user;
    }

    public function test_catalogo_index_visible_para_admin(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $user = $this->makeAdmin($company);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $response = $this->get('/purchase-perceptions');
        $response->assertStatus(200);
    }

    public function test_crear_percepcion_en_catalogo(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $user = $this->makeAdmin($company);
        $account = AccountingAccount::where('code', '1.1.05')->first();

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $response = $this->post('/purchase-perceptions', [
            'name' => 'IIBB Neuquén',
            'jurisdiction' => 'Neuquén',
            'rate' => 1.5,
            'accounting_account_id' => $account->id,
            'sort_order' => 10,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('purchase-perceptions.index'));
        $this->assertDatabaseHas('purchase_perceptions', [
            'company_id' => $company->id,
            'name' => 'IIBB Neuquén',
            'jurisdiction' => 'Neuquén',
        ]);
    }

    public function test_fc_con_percepciones_se_guarda_correctamente(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $branch = $this->makeBranch();
        $supplier = $this->makeSupplier();
        $user = $this->makeAdmin($company);
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

        $response = $this->post('/purchase-invoices', [
            'invoice_number' => 'FC-001',
            'voucher_type' => 'A',
            'point_of_sale' => '0001',
            'supplier_id' => $supplier->id,
            'lab_branch_id' => $branch->id,
            'issue_date' => now()->toDateString(),
            'otros_impuestos' => 0,
            'items' => [[
                'description' => 'Servicio de prueba',
                'supply_id' => null,
                'quantity' => 1,
                'unit_price' => 1000,
                'iva_rate' => '21',
                'updates_stock' => false,
            ]],
            'perceptions' => [[
                'purchase_perception_id' => $perception->id,
                'name_snapshot' => 'IIBB Neuquén',
                'jurisdiction_snapshot' => 'Neuquén',
                'rate_snapshot' => 1.5,
                'accounting_account_id' => $account->id,
                'amount' => 150.00,
            ]],
        ]);

        $invoice = PurchaseInvoice::where('invoice_number', 'FC-001')->first();
        $this->assertNotNull($invoice);
        $this->assertCount(1, $invoice->perceptions);
        $this->assertEqualsWithDelta(150.00, (float) $invoice->percepciones, 0.01);
    }

    public function test_recalculate_suma_percepciones_desde_pivote(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $supplier = $this->makeSupplier();
        $user = User::factory()->create();
        $account = AccountingAccount::where('code', '1.1.05')->first();

        $invoice = PurchaseInvoice::create([
            'company_id' => $company->id,
            'invoice_number' => 'TEST-001',
            'voucher_type' => 'A',
            'supplier_id' => $supplier->id,
            'issue_date' => now()->toDateString(),
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'status' => 'pendiente',
            'amount_paid' => 0,
            'balance' => 0,
            'created_by' => $user->id,
        ]);

        $invoice->perceptions()->create([
            'accounting_account_id' => $account->id,
            'name_snapshot' => 'IIBB Test',
            'amount' => 200.00,
            'sort_order' => 0,
        ]);
        $invoice->perceptions()->create([
            'accounting_account_id' => $account->id,
            'name_snapshot' => 'IVA Test',
            'amount' => 50.00,
            'sort_order' => 1,
        ]);

        $invoice->recalculate();

        $this->assertEqualsWithDelta(250.00, (float) $invoice->fresh()->percepciones, 0.01);
    }

    public function test_asiento_debita_cada_percepcion_en_su_cuenta_snapshot(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $supplier = $this->makeSupplier();
        $user = User::factory()->create();
        $account1 = AccountingAccount::where('code', '1.1.05')->first();
        $account2 = AccountingAccount::where('code', '1.1.06')->first();

        // Factura solo con percepciones (sin subtotal ni IVA) para que el asiento balancea
        // Debit: perc1(150) + perc2(100) = 250  |  Credit: proveedor(250)
        $invoice = PurchaseInvoice::create([
            'company_id' => $company->id,
            'invoice_number' => 'TEST-AST',
            'voucher_type' => 'A',
            'supplier_id' => $supplier->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 0,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'percepciones' => 250,
            'otros_impuestos' => 0,
            'total' => 250,
            'amount_paid' => 0,
            'balance' => 250,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $invoice->perceptions()->create([
            'accounting_account_id' => $account1->id,
            'name_snapshot' => 'IIBB Neuquén',
            'jurisdiction_snapshot' => 'Neuquén',
            'rate_snapshot' => 1.5,
            'amount' => 150,
            'sort_order' => 0,
        ]);
        $invoice->perceptions()->create([
            'accounting_account_id' => $account2->id,
            'name_snapshot' => 'IVA RG',
            'jurisdiction_snapshot' => null,
            'rate_snapshot' => 0,
            'amount' => 100,
            'sort_order' => 1,
        ]);

        $invoice->load('perceptions.accountingAccount');
        $entry = (new AccountingEntryService)->fromPurchaseInvoice($invoice);

        $this->assertNotNull($entry);
        $lines = $entry->lines;

        $lineAccount1 = $lines->where('accounting_account_id', $account1->id)->first();
        $lineAccount2 = $lines->where('accounting_account_id', $account2->id)->first();

        $this->assertNotNull($lineAccount1, 'Falta la línea de percepción para 1.1.05');
        $this->assertNotNull($lineAccount2, 'Falta la línea de percepción para 1.1.06');
        $this->assertEqualsWithDelta(150.0, (float) $lineAccount1->debit, 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $lineAccount2->debit, 0.01);

        $totalDebit = $lines->sum(fn ($l) => (float) $l->debit);
        $totalCredit = $lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, 'El asiento no balancea');
    }

    public function test_balances_muestra_anticipos_del_periodo(): void
    {
        $this->seedAccounts();
        $company = $this->makeCompany();
        $user = $this->makeAdmin($company);

        $this->actingAs($user);
        session(['active_company_id' => $company->id]);

        $response = $this->get('/purchase-perceptions/balances?from='.now()->startOfMonth()->toDateString().'&to='.now()->endOfMonth()->toDateString());
        $response->assertStatus(200);
    }
}
