<?php

namespace Tests\Feature\Billing;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\SalesInvoice;
use App\Models\Test;
use App\Models\User;
use App\Services\AfipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BatchInvoiceDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'ventas.section',
            'sales-invoices.index',
            'sales-invoices.create',
            'sales-invoices.edit',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    /**
     * @return array{0: User, 1: Company, 2: Customer, 3: PointOfSale, 4: Sample}
     */
    private function seedSampleContext(bool $electronic = true): array
    {
        AccountingAccount::query()->create([
            'code' => '1.1.04', 'name' => 'Deudores por Ventas', 'type' => 'activo',
            'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);
        AccountingAccount::query()->create([
            'code' => '4.1.01', 'name' => 'Ventas', 'type' => 'resultado_positivo',
            'parent_id' => null, 'level' => 3, 'is_header' => false, 'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Empresa Borrador',
            'cuit' => '20-12345678-9',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo([
            'ventas.section',
            'sales-invoices.index',
            'sales-invoices.create',
            'sales-invoices.edit',
        ]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente Borrador',
            'taxId' => '20-99999999-9',
            'status' => 'activo',
        ]);

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00001',
            'name' => 'PV Test',
            'is_active' => true,
            'is_electronic' => $electronic,
            'afip_pos_number' => 1,
        ]);

        $test = Test::query()->create([
            'code' => 'T-1',
            'name' => 'Análisis de prueba',
            'price' => 1000,
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-2026-000001',
            'sample_type' => 'aguas',
            'entry_date' => now()->toDateString(),
            'sampling_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Lab',
            'address' => 'Calle 123',
            'product_name' => 'Producto test',
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test->id,
            'price' => 1000,
            'status' => 'pending',
        ]);

        return [$user, $company, $customer, $pos, $sample];
    }

    public function test_batch_invoice_creates_draft_without_calling_afip(): void
    {
        [$user, $company, $customer, $pos, $sample] = $this->seedSampleContext(electronic: true);

        $afipMock = Mockery::mock(AfipService::class);
        $afipMock->shouldNotReceive('createVoucher');
        $this->app->instance(AfipService::class, $afipMock);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('billing.batch-invoice'), [
                'protocol_type' => 'sample',
                'protocol_ids' => [$sample->id],
                'customer_id' => $customer->id,
                'point_of_sale_id' => $pos->id,
                'voucher_type' => 'A',
                'issue_date' => now()->toDateString(),
            ]);

        $invoice = SalesInvoice::query()->first();
        $this->assertNotNull($invoice);

        $response->assertRedirect(route('sales-invoices.edit', $invoice));

        $this->assertSame('pendiente', $invoice->status);
        $this->assertSame('PENDIENTE-AFIP', $invoice->invoice_number);
        $this->assertNull($invoice->cae);
        $this->assertNull($invoice->afip_result);
        $this->assertTrue((bool) $invoice->is_electronic);
        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame(1, $invoice->invoiceProtocols()->count());
    }

    public function test_user_can_add_extra_line_to_draft(): void
    {
        [$user, $company, $customer, $pos, $sample] = $this->seedSampleContext(electronic: true);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'PENDIENTE-AFIP',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 1000,
            'iva_21' => 210,
            'total' => 1210,
            'amount_collected' => 0,
            'balance' => 1210,
            'status' => 'pendiente',
            'is_electronic' => true,
            'created_by' => $user->id,
        ]);

        $invoice->items()->create([
            'description' => 'Determinación original',
            'test_id' => null,
            'quantity' => 1,
            'unit_price' => 1000,
            'iva_rate' => 21,
            'iva_amount' => 210,
            'total' => 1210,
        ]);
        $invoice->recalculate();

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->put(route('sales-invoices.update', $invoice), [
                'invoice_number' => 'PENDIENTE-AFIP',
                'voucher_type' => 'A',
                'point_of_sale_id' => $pos->id,
                'customer_id' => $customer->id,
                'issue_date' => now()->toDateString(),
                'items' => [
                    [
                        'description' => 'Determinación original',
                        'test_id' => '',
                        'quantity' => 1,
                        'unit_price' => 1000,
                        'iva_rate' => 21,
                    ],
                    [
                        'description' => 'Toma de muestra a domicilio',
                        'test_id' => '',
                        'quantity' => 1,
                        'unit_price' => 5000,
                        'iva_rate' => 21,
                    ],
                ],
            ]);

        $response->assertRedirect(route('sales-invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame(2, $invoice->items()->count());
        $this->assertTrue(
            $invoice->items()->where('description', 'Toma de muestra a domicilio')->exists()
        );
        $this->assertSame(6000.0, (float) $invoice->subtotal);
        $this->assertSame(7260.0, (float) $invoice->total);
    }

    public function test_send_to_afip_from_edit_obtains_cae(): void
    {
        [$user, $company, $customer, $pos] = $this->seedSampleContext(electronic: true);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'PENDIENTE-AFIP',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 1000,
            'iva_21' => 210,
            'total' => 1210,
            'amount_collected' => 0,
            'balance' => 1210,
            'status' => 'pendiente',
            'is_electronic' => true,
            'created_by' => $user->id,
        ]);

        $invoice->items()->create([
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 1000,
            'iva_rate' => 21,
            'iva_amount' => 210,
            'total' => 1210,
        ]);

        $afipMock = Mockery::mock(AfipService::class);
        $afipMock->shouldReceive('createVoucher')
            ->once()
            ->andReturn([
                'result' => 'A',
                'cae' => '74000000000001',
                'cae_expiration' => now()->addDays(10)->toDateString(),
                'voucher_number' => 12345,
                'full_response' => ['mock' => true],
            ]);
        $this->app->instance(AfipService::class, $afipMock);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('sales-invoices.retry-afip', $invoice));

        $response->assertRedirect(route('sales-invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('74000000000001', $invoice->cae);
        $this->assertSame('A', $invoice->afip_result);
        $this->assertSame('00012345', $invoice->invoice_number);
    }

    public function test_afip_draft_filter_lists_only_pending_electronic_without_cae(): void
    {
        [$user, $company, $customer, $pos] = $this->seedSampleContext(electronic: true);

        $posNoElec = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00002',
            'name' => 'PV manual',
            'is_active' => true,
            'is_electronic' => false,
        ]);

        $draft = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'PENDIENTE-AFIP',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100, 'iva_21' => 21, 'total' => 121, 'balance' => 121, 'amount_collected' => 0,
            'status' => 'pendiente',
            'is_electronic' => true,
            'created_by' => $user->id,
        ]);

        $authorized = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000010',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100, 'iva_21' => 21, 'total' => 121, 'balance' => 121, 'amount_collected' => 0,
            'status' => 'pendiente',
            'is_electronic' => true,
            'cae' => '74000000000099',
            'afip_result' => 'A',
            'created_by' => $user->id,
        ]);

        $manual = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000007',
            'voucher_type' => 'B',
            'point_of_sale_id' => $posNoElec->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100, 'iva_21' => 21, 'total' => 121, 'balance' => 121, 'amount_collected' => 0,
            'status' => 'pendiente',
            'is_electronic' => false,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('sales-invoices.index', ['afip_draft' => 1]));

        $response->assertOk();
        $response->assertSee('PENDIENTE-AFIP');
        $response->assertDontSee('00000010');
        $response->assertDontSee('00000007');

        $this->assertEqualsCanonicalizing(
            [$draft->id],
            $response->viewData('invoices')->pluck('id')->all()
        );
    }

    public function test_invoice_with_cae_cannot_be_edited(): void
    {
        [$user, $company, $customer, $pos] = $this->seedSampleContext(electronic: true);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000005',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100, 'iva_21' => 21, 'total' => 121, 'balance' => 121, 'amount_collected' => 0,
            'status' => 'pendiente',
            'is_electronic' => true,
            'cae' => '74000000000099',
            'afip_result' => 'A',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('sales-invoices.edit', $invoice));

        $response->assertRedirect(route('sales-invoices.show', $invoice));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
