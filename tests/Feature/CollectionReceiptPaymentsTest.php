<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\CollectionReceipt;
use App\Models\Company;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CollectionReceiptPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'ventas.section',
            'collection-receipts.index',
            'collection-receipts.create',
            'collection-receipts.edit',
            'collection-receipts.delete',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    /** @return array{0: AccountingAccount, 1: AccountingAccount, 2: AccountingAccount} */
    private function seedAccounting(): array
    {
        $aCaja = AccountingAccount::query()->create([
            'code' => '1.1.01', 'name' => 'Caja', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);
        $aBanco = AccountingAccount::query()->create([
            'code' => '1.1.02', 'name' => 'Bancos', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);
        $aDeudores = AccountingAccount::query()->create([
            'code' => '1.1.04', 'name' => 'Deudores', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);

        return [$aCaja, $aBanco, $aDeudores];
    }

    /**
     * @return array{0: User, 1: Company, 2: Customer, 3: SalesInvoice, 4: BankAccount}
     */
    private function setupReceiptContext(): array
    {
        $this->seedAccounting();

        $aBanco = AccountingAccount::query()->where('code', '1.1.02')->firstOrFail();

        $company = Company::query()->create([
            'name' => 'Empresa RC',
            'cuit' => '20-55555555-5',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo([
            'ventas.section',
            'collection-receipts.index',
            'collection-receipts.create',
            'collection-receipts.edit',
            'collection-receipts.delete',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente RC',
            'taxId' => '20-66666666-6',
            'status' => 'activo',
        ]);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00001',
            'voucher_type' => 'B',
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'iva_21' => 0,
            'total' => 100,
            'amount_collected' => 0,
            'balance' => 100,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $bank = BankAccount::query()->create([
            'company_id' => $company->id,
            'bank_name' => 'Banco Test',
            'account_number' => '123-1',
            'account_type' => 'cuenta_corriente',
            'currency' => 'ARS',
            'accounting_account_id' => $aBanco->id,
            'is_active' => true,
        ]);

        return [$user, $company, $customer, $invoice, $bank];
    }

    public function test_store_rechaza_si_medios_no_cierran_con_total(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->setupReceiptContext();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'notes' => null,
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 40],
                    ['line_type' => 'transferencia', 'amount' => 50, 'bank_account_id' => $bank->id],
                ],
            ])
            ->assertSessionHasErrors(['payments']);

        $this->assertSame(0, CollectionReceipt::query()->count());
    }

    public function test_store_multimodal_persiste_lineas(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->setupReceiptContext();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 30],
                    ['line_type' => 'transferencia', 'amount' => 70, 'bank_account_id' => $bank->id],
                ],
            ])
            ->assertRedirect();

        $rc = CollectionReceipt::query()->first();
        $this->assertNotNull($rc);
        $this->assertCount(2, $rc->payments);
        $this->assertSame(30.0, (float) $rc->payments->firstWhere('line_type', 'efectivo')->amount);
        $this->assertSame(70.0, (float) $rc->payments->firstWhere('line_type', 'transferencia')->amount);
    }

    public function test_confirm_genera_asiento_con_varios_debitos(): void
    {
        [$user, $company, $customer, $invoice] = $this->setupReceiptContext();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 25],
                    ['line_type' => 'echeq', 'amount' => 75, 'cheque_number' => 'E123', 'bank_name' => 'BIND', 'due_date' => now()->addMonth()->toDateString()],
                ],
            ])
            ->assertRedirect();

        $rc = CollectionReceipt::query()->first();
        $this->assertNotNull($rc);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.confirm', $rc))
            ->assertRedirect();

        $entry = JournalEntry::query()
            ->where('source_type', CollectionReceipt::class)
            ->where('source_id', $rc->id)
            ->first();
        $this->assertNotNull($entry);
        $lines = $entry->lines()->get();
        $this->assertGreaterThanOrEqual(3, $lines->count());
        $this->assertSame(100.0, round((float) $lines->sum('debit'), 2));
        $this->assertSame(100.0, round((float) $lines->sum('credit'), 2));
    }
}
