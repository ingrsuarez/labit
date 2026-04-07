<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\CollectionReceipt;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CollectionReceiptPdfTest extends TestCase
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
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    /** @return array{0: User, 1: Company, 2: Customer, 3: SalesInvoice, 4: BankAccount} */
    private function seedContext(): array
    {
        AccountingAccount::query()->create([
            'code' => '1.1.01', 'name' => 'Caja', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);
        $aBanco = AccountingAccount::query()->create([
            'code' => '1.1.02', 'name' => 'Bancos', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);
        AccountingAccount::query()->create([
            'code' => '1.1.04', 'name' => 'Deudores', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Empresa PDF RC',
            'cuit' => '20-77777777-7',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'Calle 1',
            'city' => 'CABA',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['ventas.section', 'collection-receipts.index', 'collection-receipts.create']);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente PDF',
            'taxId' => '20-88888888-8',
            'status' => 'activo',
        ]);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00002',
            'voucher_type' => 'B',
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 50,
            'iva_21' => 0,
            'total' => 50,
            'amount_collected' => 0,
            'balance' => 50,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $bank = BankAccount::query()->create([
            'company_id' => $company->id,
            'bank_name' => 'Banco PDF',
            'account_number' => '999',
            'account_type' => 'cuenta_corriente',
            'currency' => 'ARS',
            'accounting_account_id' => $aBanco->id,
            'is_active' => true,
        ]);

        return [$user, $company, $customer, $invoice, $bank];
    }

    public function test_pdf_responde_con_documento_pdf(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->seedContext();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 50],
                ],
                'payments' => [
                    ['line_type' => 'transferencia', 'amount' => 50, 'bank_account_id' => $bank->id],
                ],
            ])
            ->assertRedirect();

        $rc = CollectionReceipt::query()->first();
        $this->assertNotNull($rc);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('collection-receipts.pdf', $rc));

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('content-type', '')));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_otra_empresa_responde_403(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->seedContext();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 50],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 50],
                ],
            ])
            ->assertRedirect();

        $rc = CollectionReceipt::query()->firstOrFail();

        $company2 = Company::query()->create([
            'name' => 'Otra SA',
            'cuit' => '30-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user2 = User::factory()->create();
        $user2->givePermissionTo(['ventas.section', 'collection-receipts.index']);
        $user2->companies()->attach($company2->id, ['is_default' => true]);

        $this->actingAs($user2)
            ->withSession(['active_company_id' => $company2->id])
            ->get(route('collection-receipts.pdf', $rc))
            ->assertForbidden();
    }
}
