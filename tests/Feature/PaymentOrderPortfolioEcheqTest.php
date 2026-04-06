<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\CollectionReceipt;
use App\Models\CollectionReceiptPayment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\PaymentOrder;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentOrderPortfolioEcheqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'compras.section',
            'payment-orders.index',
            'payment-orders.create',
            'payment-orders.edit',
            'payment-orders.delete',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    /**
     * @return array{0: User, 1: Company, 2: Supplier, 3: PurchaseInvoice, 4: CollectionReceiptPayment, 5: CollectionReceiptPayment}
     */
    private function seedPortfolioAndInvoice(): array
    {
        AccountingAccount::query()->create([
            'code' => '2.1.01', 'name' => 'Proveedores test', 'type' => 'pasivo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);
        AccountingAccount::query()->create([
            'code' => '1.1.02', 'name' => 'Bancos test', 'type' => 'activo', 'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Empresa OP-ECH',
            'cuit' => '20-77777777-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo([
            'compras.section',
            'payment-orders.index',
            'payment-orders.create',
            'payment-orders.edit',
            'payment-orders.delete',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente ECH',
            'taxId' => '20-'.uniqid().'-9',
            'status' => 'activo',
        ]);

        $rc = CollectionReceipt::query()->create([
            'number' => 'RC-TEST-'.uniqid(),
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'total' => 100,
            'status' => 'confirmado',
            'created_by' => $user->id,
            'confirmed_by' => $user->id,
        ]);

        $p1 = CollectionReceiptPayment::query()->create([
            'collection_receipt_id' => $rc->id,
            'line_type' => 'echeq',
            'amount' => 35,
            'cheque_number' => 'ECH-1',
            'bank_name' => 'Bank A',
            'due_date' => now()->addMonth()->toDateString(),
            'sort_order' => 0,
        ]);
        $p2 = CollectionReceiptPayment::query()->create([
            'collection_receipt_id' => $rc->id,
            'line_type' => 'echeq',
            'amount' => 65,
            'cheque_number' => 'ECH-2',
            'bank_name' => 'Bank B',
            'due_date' => now()->addMonth()->toDateString(),
            'sort_order' => 1,
        ]);

        $supplier = Supplier::query()->create([
            'code' => 'S-'.substr(uniqid(), -6),
            'name' => 'Proveedor ECH',
            'tax_id' => '30-88888888-8',
            'status' => 'activo',
        ]);

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '0001',
            'voucher_type' => 'A',
            'supplier_id' => $supplier->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'iva_21' => 0,
            'total' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        return [$user, $company, $supplier, $invoice, $p1, $p2];
    }

    public function test_store_op_portfolio_vincula_lineas(): void
    {
        [$user, $company, $supplier, $invoice, $p1, $p2] = $this->seedPortfolioAndInvoice();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('payment-orders.store'), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'payment_mode' => 'portfolio_echeq',
                'invoices' => [
                    ['purchase_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'portfolio_echeq_ids' => [$p1->id, $p2->id],
            ])
            ->assertRedirect();

        $op = PaymentOrder::query()->first();
        $this->assertNotNull($op);
        $p1->refresh();
        $p2->refresh();
        $this->assertSame($op->id, $p1->payment_order_id);
        $this->assertSame($op->id, $p2->payment_order_id);
    }

    public function test_segunda_op_no_puede_reusar_echeqs_reservados(): void
    {
        [$user, $company, $supplier, $invoice, $p1, $p2] = $this->seedPortfolioAndInvoice();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('payment-orders.store'), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'payment_mode' => 'portfolio_echeq',
                'invoices' => [
                    ['purchase_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'portfolio_echeq_ids' => [$p1->id, $p2->id],
            ])
            ->assertRedirect();

        $invoice2 = PurchaseInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '0002',
            'voucher_type' => 'A',
            'supplier_id' => $supplier->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'iva_21' => 0,
            'total' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('payment-orders.store'), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'payment_mode' => 'portfolio_echeq',
                'invoices' => [
                    ['purchase_invoice_id' => $invoice2->id, 'amount' => 100],
                ],
                'portfolio_echeq_ids' => [$p1->id, $p2->id],
            ])
            ->assertSessionHasErrors(['portfolio_echeq_ids']);
    }

    public function test_confirm_genera_asiento_multiples_creditos_cartera(): void
    {
        [$user, $company, $supplier, $invoice, $p1, $p2] = $this->seedPortfolioAndInvoice();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('payment-orders.store'), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'payment_mode' => 'portfolio_echeq',
                'invoices' => [
                    ['purchase_invoice_id' => $invoice->id, 'amount' => 100],
                ],
                'portfolio_echeq_ids' => [$p1->id, $p2->id],
            ])
            ->assertRedirect();

        $op = PaymentOrder::query()->first();
        $this->assertNotNull($op);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('payment-orders.confirm', $op))
            ->assertRedirect();

        $entry = JournalEntry::query()
            ->where('source_type', PaymentOrder::class)
            ->where('source_id', $op->id)
            ->first();
        $this->assertNotNull($entry);
        $lines = $entry->lines()->get();
        $this->assertGreaterThanOrEqual(3, $lines->count());
        $this->assertSame(100.0, round((float) $lines->sum('debit'), 2));
        $this->assertSame(100.0, round((float) $lines->sum('credit'), 2));
    }

    public function test_store_rechaza_suma_echeq_distinta_a_total(): void
    {
        [$user, $company, $supplier, $invoice, $p1, $p2] = $this->seedPortfolioAndInvoice();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('payment-orders.store'), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'payment_mode' => 'portfolio_echeq',
                'invoices' => [
                    ['purchase_invoice_id' => $invoice->id, 'amount' => 50],
                ],
                'portfolio_echeq_ids' => [$p1->id, $p2->id],
            ])
            ->assertSessionHasErrors(['portfolio_echeq_ids']);

        $this->assertSame(0, PaymentOrder::query()->count());
    }
}
