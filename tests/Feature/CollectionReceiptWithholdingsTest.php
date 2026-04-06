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
use App\Services\WithheldIvaSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CollectionReceiptWithholdingsTest extends TestCase
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

    private function seedAccountingWithholdings(): void
    {
        foreach ([
            ['1.1.01', 'Caja'],
            ['1.1.02', 'Bancos'],
            ['1.1.04', 'Deudores'],
            ['1.1.05', 'Retenciones Ganancias a recuperar'],
            ['1.1.06', 'IVA retenciones sufridas'],
            ['1.1.07', 'Retenciones SUSS'],
            ['1.1.08', 'Retenciones IIBB'],
        ] as [$code, $name]) {
            AccountingAccount::query()->create([
                'code' => $code, 'name' => $name, 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
            ]);
        }
    }

    /** @return array{User, Company, Customer, SalesInvoice, BankAccount} */
    private function context(): array
    {
        $this->seedAccountingWithholdings();
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

    public function test_store_rechaza_si_medios_mas_retenciones_no_cierran(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->context();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 40],
                    ['line_type' => 'transferencia', 'amount' => 40, 'bank_account_id' => $bank->id],
                ],
                'withholdings' => [
                    [
                        'withholding_type' => 'iva',
                        'document_number' => '1',
                        'regime' => 'Test',
                        'jurisdiction' => '',
                        'certificate_number' => 'C1',
                        'amount' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors(['payments']);

        $this->assertSame(0, CollectionReceipt::query()->count());
    }

    public function test_store_con_retenciones_persiste_y_confirma_asiento(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->context();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 60],
                    ['line_type' => 'transferencia', 'amount' => 30, 'bank_account_id' => $bank->id],
                ],
                'withholdings' => [
                    [
                        'withholding_type' => 'iva',
                        'document_number' => '8669249',
                        'regime' => 'Locación',
                        'jurisdiction' => '',
                        'certificate_number' => '553',
                        'amount' => 10,
                    ],
                ],
            ])
            ->assertRedirect();

        $rc = CollectionReceipt::query()->with('withholdings')->first();
        $this->assertNotNull($rc);
        $this->assertCount(1, $rc->withholdings);
        $this->assertSame(10.0, (float) $rc->withholdings->first()->amount);
        $this->assertSame('iva', $rc->withholdings->first()->withholding_type);

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
        $this->assertSame(100.0, round((float) $lines->sum('debit'), 2));
        $this->assertSame(100.0, round((float) $lines->sum('credit'), 2));

        $ivaLine = $lines->first(fn ($l) => $l->account && $l->account->code === '1.1.06');
        $this->assertNotNull($ivaLine);
        $this->assertSame(10.0, (float) $ivaLine->debit);
    }

    public function test_iibb_exige_jurisdiccion(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->context();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => now()->toDateString(),
                'invoices' => [
                    ['sales_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'payments' => [
                    ['line_type' => 'efectivo', 'amount' => 100],
                ],
                'withholdings' => [
                    [
                        'withholding_type' => 'iibb',
                        'regime' => 'CM',
                        'jurisdiction' => '   ',
                        'certificate_number' => '1',
                        'amount' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors(['withholdings.0.jurisdiction']);
    }

    public function test_withheld_iva_summary_por_mes_y_empresa(): void
    {
        [$user, $company, $customer, $invoice, $bank] = $this->context();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('collection-receipts.store'), [
                'customer_id' => $customer->id,
                'date' => '2026-01-15',
                'invoices' => [['sales_invoice_id' => $invoice->id, 'amount' => 100]],
                'payments' => [['line_type' => 'efectivo', 'amount' => 90]],
                'withholdings' => [[
                    'withholding_type' => 'iva',
                    'regime' => 'R',
                    'certificate_number' => 'A',
                    'amount' => 10,
                ]],
            ]);

        $rc = CollectionReceipt::query()->first();
        $rc->update(['status' => 'confirmado']);

        $company2 = Company::query()->create([
            'name' => 'Otra',
            'cuit' => '20-77777777-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $svc = new WithheldIvaSummaryService;
        $this->assertSame(10.0, $svc->totalForPeriod((int) $company->id, 2026, 1));
        $this->assertSame(0.0, $svc->totalForPeriod((int) $company2->id, 2026, 1));
        $this->assertSame(0.0, $svc->totalForPeriod((int) $company->id, 2026, 2));
    }
}
