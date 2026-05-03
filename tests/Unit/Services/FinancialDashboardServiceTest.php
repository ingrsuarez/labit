<?php

namespace Tests\Unit\Services;

use App\Models\CollectionReceipt;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\PaymentOrder;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinancialDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialDashboardService;
    }

    // ─── Variación % ─────────────────────────────────────────────

    public function test_variation_returns_positive_percent_when_current_is_higher(): void
    {
        $this->assertSame(100.0, $this->service->calculateVariation(2000, 1000));
        $this->assertSame(50.0, $this->service->calculateVariation(1500, 1000));
    }

    public function test_variation_returns_negative_percent_when_current_is_lower(): void
    {
        $this->assertSame(-50.0, $this->service->calculateVariation(500, 1000));
        $this->assertSame(-25.0, $this->service->calculateVariation(750, 1000));
    }

    public function test_variation_returns_zero_when_no_change(): void
    {
        $this->assertSame(0.0, $this->service->calculateVariation(1000, 1000));
    }

    public function test_variation_returns_null_when_previous_is_zero_and_current_is_positive(): void
    {
        $this->assertNull($this->service->calculateVariation(1000, 0));
    }

    public function test_variation_returns_zero_when_both_are_zero(): void
    {
        $this->assertSame(0.0, $this->service->calculateVariation(0, 0));
    }

    public function test_variation_caps_at_999_when_extreme_growth(): void
    {
        $this->assertSame(999.0, $this->service->calculateVariation(1_000_000, 1));
    }

    public function test_variation_returns_negative_100_when_current_drops_to_zero(): void
    {
        $this->assertSame(-100.0, $this->service->calculateVariation(0, 1000));
    }

    // ─── Ventas netas (FV - NC) ─────────────────────────────────────────────

    public function test_ventas_resta_credit_notes_del_mes(): void
    {
        $context = $this->seedCompanyContext();

        $invoice = SalesInvoice::query()->create($this->salesInvoiceData($context, [
            'issue_date' => now()->startOfMonth()->toDateString(),
            'total' => 10000,
            'is_electronic' => false,
        ]));

        CreditNote::query()->create($this->creditNoteData($context, [
            'sales_invoice_id' => $invoice->id,
            'issue_date' => now()->startOfMonth()->toDateString(),
            'total' => 3000,
            'is_electronic' => false,
        ]));

        $result = $this->service->ventas($context['company']->id);

        $this->assertSame('ventas', $result['key']);
        $this->assertSame(7000.0, (float) $result['current_total']);
    }

    public function test_ventas_excluye_facturas_electronicas_sin_cae(): void
    {
        $context = $this->seedCompanyContext();

        SalesInvoice::query()->create($this->salesInvoiceData($context, [
            'issue_date' => now()->startOfMonth()->toDateString(),
            'total' => 5000,
            'is_electronic' => true,
            'cae' => null,
        ]));

        SalesInvoice::query()->create($this->salesInvoiceData($context, [
            'invoice_number' => '00000002',
            'issue_date' => now()->startOfMonth()->toDateString(),
            'total' => 8000,
            'is_electronic' => true,
            'cae' => '12345678901234',
        ]));

        $result = $this->service->ventas($context['company']->id);

        $this->assertSame(8000.0, (float) $result['current_total']);
    }

    public function test_ventas_filtra_por_company_id(): void
    {
        $contextA = $this->seedCompanyContext('A', '20-11111111-1', '20-77777777-7');
        $contextB = $this->seedCompanyContext('B', '20-22222222-2', '20-88888888-8');

        SalesInvoice::query()->create($this->salesInvoiceData($contextA, [
            'issue_date' => now()->startOfMonth()->toDateString(),
            'total' => 10000,
            'is_electronic' => false,
        ]));
        SalesInvoice::query()->create($this->salesInvoiceData($contextB, [
            'issue_date' => now()->startOfMonth()->toDateString(),
            'total' => 99999,
            'is_electronic' => false,
        ]));

        $resultA = $this->service->ventas($contextA['company']->id);
        $resultB = $this->service->ventas($contextB['company']->id);

        $this->assertSame(10000.0, (float) $resultA['current_total']);
        $this->assertSame(99999.0, (float) $resultB['current_total']);
    }

    // ─── Estructura general ─────────────────────────────────────────────

    public function test_serie_devuelve_12_meses_con_mes_corriente_marcado(): void
    {
        $context = $this->seedCompanyContext();

        $result = $this->service->ventas($context['company']->id);

        $this->assertCount(12, $result['monthly']);
        $this->assertTrue($result['monthly'][11]['is_current']);
        $this->assertFalse($result['monthly'][0]['is_current']);
    }

    public function test_ingresos_suma_recibos_excluyendo_anulados(): void
    {
        $context = $this->seedCompanyContext();

        CollectionReceipt::query()->create([
            'company_id' => $context['company']->id,
            'number' => 'RC-2026-00001',
            'customer_id' => $context['customer']->id,
            'date' => now()->startOfMonth()->toDateString(),
            'total' => 5000,
            'status' => 'confirmado',
            'created_by' => $context['user']->id,
        ]);
        CollectionReceipt::query()->create([
            'company_id' => $context['company']->id,
            'number' => 'RC-2026-00002',
            'customer_id' => $context['customer']->id,
            'date' => now()->startOfMonth()->toDateString(),
            'total' => 9999,
            'status' => 'anulado',
            'created_by' => $context['user']->id,
        ]);

        $result = $this->service->ingresos($context['company']->id);

        $this->assertSame(5000.0, (float) $result['current_total']);
    }

    public function test_egresos_suma_ordenes_de_pago_excluyendo_anuladas(): void
    {
        $context = $this->seedCompanyContext();

        $supplier = Supplier::query()->create([
            'name' => 'Prov Test',
            'code' => 'PROV-TEST',
            'taxId' => '20-99999999-9',
            'status' => 'activo',
        ]);

        PaymentOrder::query()->create([
            'number' => 'OP-2026-00001',
            'company_id' => $context['company']->id,
            'supplier_id' => $supplier->id,
            'date' => now()->startOfMonth()->toDateString(),
            'total' => 4000,
            'status' => 'aprobada',
            'created_by' => $context['user']->id,
        ]);
        PaymentOrder::query()->create([
            'number' => 'OP-2026-00002',
            'company_id' => $context['company']->id,
            'supplier_id' => $supplier->id,
            'date' => now()->startOfMonth()->toDateString(),
            'total' => 7777,
            'status' => 'anulada',
            'created_by' => $context['user']->id,
        ]);

        $result = $this->service->egresos($context['company']->id);

        $this->assertSame(4000.0, (float) $result['current_total']);
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * @return array{company: Company, user: User, customer: Customer, pos: PointOfSale}
     */
    private function seedCompanyContext(string $suffix = 'X', string $cuit = '20-12345678-9', string $customerTax = '20-44444444-4'): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa '.$suffix,
            'cuit' => $cuit,
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Cliente '.$suffix,
            'taxId' => $customerTax,
            'status' => 'activo',
        ]);
        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => str_pad((string) $company->id, 5, '0', STR_PAD_LEFT),
            'name' => 'PV '.$suffix,
            'is_active' => true,
            'is_electronic' => false,
        ]);

        return compact('company', 'user', 'customer', 'pos');
    }

    private function salesInvoiceData(array $context, array $overrides): array
    {
        return array_merge([
            'company_id' => $context['company']->id,
            'invoice_number' => '00000001',
            'voucher_type' => 'A',
            'point_of_sale_id' => $context['pos']->id,
            'customer_id' => $context['customer']->id,
            'issue_date' => now()->toDateString(),
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'subtotal' => 0,
            'iva_21' => 0,
            'total' => 0,
            'amount_collected' => 0,
            'balance' => 0,
            'status' => 'pendiente',
            'is_electronic' => false,
            'created_by' => $context['user']->id,
        ], $overrides);
    }

    private function creditNoteData(array $context, array $overrides): array
    {
        return array_merge([
            'company_id' => $context['company']->id,
            'credit_note_number' => '00000001',
            'voucher_type' => 'A',
            'point_of_sale_id' => $context['pos']->id,
            'customer_id' => $context['customer']->id,
            'sales_invoice_id' => null,
            'issue_date' => now()->toDateString(),
            'reason' => 'Test',
            'subtotal' => 0,
            'iva_21' => 0,
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'total' => 0,
            'status' => 'confirmada',
            'is_electronic' => false,
            'created_by' => $context['user']->id,
        ], $overrides);
    }
}
