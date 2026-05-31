<?php

namespace Tests\Feature\CashFlow;

use App\Models\CashFlowObligation;
use App\Models\Company;
use App\Models\Form931Declaration;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPaymentLine;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\TaxReturn;
use App\Models\User;
use App\Services\CashFlowCalendarService;
use Carbon\Carbon;
use Database\Seeders\CashFlowPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CashFlowCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function setupUser(Company $company): User
    {
        $this->seed(CashFlowPermissionsSeeder::class);
        \Spatie\Permission\Models\Permission::findOrCreate('compras.section');

        $user = User::factory()->create();
        $user->givePermissionTo(['cash-flow.view', 'cash-flow.manage', 'compras.section']);
        $user->companies()->attach($company->id, ['is_default' => true]);

        return $user;
    }

    private function setupUserWithCompanies(array $companies): User
    {
        $this->seed(CashFlowPermissionsSeeder::class);
        \Spatie\Permission\Models\Permission::findOrCreate('compras.section');

        $user = User::factory()->create();
        $user->givePermissionTo(['cash-flow.view', 'cash-flow.manage', 'compras.section']);

        foreach ($companies as $index => $company) {
            $user->companies()->attach($company->id, ['is_default' => $index === 0]);
        }

        return $user;
    }

    private function createPurchaseInvoiceDue(Company $company, User $user, string $dueDate, float $amount, string $supplierCode): PurchaseInvoice
    {
        $supplier = Supplier::create([
            'code' => $supplierCode,
            'name' => 'Proveedor '.$supplierCode,
            'tax_id' => '30-'.substr(md5($supplierCode), 0, 8),
            'status' => 'activo',
        ]);

        return PurchaseInvoice::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => (string) random_int(1000, 9999),
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => Carbon::parse($dueDate)->subDays(10)->toDateString(),
            'due_date' => $dueDate,
            'subtotal' => $amount,
            'total' => $amount,
            'balance' => $amount,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);
    }

    public function test_calendar_page_loads(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa CF',
            'cuit' => '30-71000001-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = $this->setupUser($company);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('cash-flow.index'))
            ->assertOk()
            ->assertSee('Flujo de caja');
    }

    public function test_purchase_invoice_with_due_date_appears_in_calendar(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa FC',
            'cuit' => '30-71000002-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'code' => 'S-CF',
            'name' => 'Proveedor CF',
            'tax_id' => '30-22222222-2',
            'status' => 'activo',
        ]);

        PurchaseInvoice::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => '100',
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-15',
            'subtotal' => 1000,
            'total' => 1000,
            'balance' => 1000,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $service = app(CashFlowCalendarService::class);
        $events = $service->eventsForRange(
            $company->id,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $this->assertTrue($events->contains(fn ($e) => $e['category'] === 'factura_compra' && $e['date'] === '2026-05-15'));
        $fc = $events->firstWhere('category', 'factura_compra');
        $this->assertSame('FC100 · Proveedor CF', $fc['badge_label']);
    }

    public function test_invoice_without_due_date_is_excluded(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa FC2',
            'cuit' => '30-71000003-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'code' => 'S-CF2',
            'name' => 'Prov',
            'tax_id' => '30-33333333-3',
            'status' => 'activo',
        ]);

        PurchaseInvoice::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => '101',
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => '2026-05-01',
            'due_date' => null,
            'subtotal' => 500,
            'total' => 500,
            'balance' => 500,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $events = app(CashFlowCalendarService::class)->eventsForRange(
            $company->id,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $this->assertFalse($events->contains(fn ($e) => $e['category'] === 'factura_compra'));
    }

    public function test_form931_projected_from_latest_confirmed(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa 931 CF',
            'cuit' => '30-71000004-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();

        Form931Declaration::create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 4,
            'amount_aportes_patronales' => 8000,
            'amount_contribuciones_patronales' => 4000,
            'total' => 12000,
            'status' => 'confirmed',
            'created_by' => $user->id,
            'confirmed_by' => $user->id,
            'confirmed_at' => now(),
        ]);

        $events = app(CashFlowCalendarService::class)->eventsForRange(
            $company->id,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $ev931 = $events->firstWhere('category', 'impuesto_931');
        $this->assertNotNull($ev931);
        $this->assertEquals(12000.0, $ev931['amount']);
        $this->assertSame('estimated', $ev931['confidence']);
    }

    public function test_manual_obligation_appears(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa manual',
            'cuit' => '30-71000005-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();

        CashFlowObligation::create([
            'company_id' => $company->id,
            'category' => CashFlowObligation::CATEGORY_ECHEQ,
            'title' => 'E-cheq Proveedor X',
            'amount' => 250000,
            'due_date' => '2026-07-10',
            'created_by' => $user->id,
        ]);

        $events = app(CashFlowCalendarService::class)->eventsForRange(
            $company->id,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31')
        );

        $this->assertTrue($events->contains(fn ($e) => $e['category'] === 'echeq_emitido' && $e['confidence'] === 'manual'));
    }

    public function test_iva_uses_confirmed_tax_return_balance(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa IVA',
            'cuit' => '30-71000006-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();

        $account = \App\Models\AccountingAccount::query()->create([
            'code' => '2.1.03',
            'name' => 'IVA a pagar',
            'type' => 'pasivo',
            'level' => 3,
            'is_header' => false,
            'is_active' => true,
        ]);

        $tax = Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA',
            'liability_account_id' => $account->id,
            'frequency' => 'monthly',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        TaxReturn::create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'period_year' => 2026,
            'period_month' => 5,
            'declared_amount' => 50000,
            'applied_total' => 10000,
            'balance' => 40000,
            'status' => 'confirmed',
            'created_by' => $user->id,
        ]);

        $events = app(CashFlowCalendarService::class)->eventsForRange(
            $company->id,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $iva = $events->firstWhere('category', 'impuesto_iva');
        $this->assertNotNull($iva);
        $this->assertEquals(40000.0, $iva['amount']);
        $this->assertSame('confirmed', $iva['confidence']);
        $this->assertSame('IVA', $iva['badge_label']);
    }

    public function test_payment_order_own_cheque_appears_on_due_date(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa OP Cheque',
            'cuit' => '30-71000008-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'code' => 'S-OP',
            'name' => 'LEW',
            'tax_id' => '30-44444444-4',
            'status' => 'activo',
        ]);

        $order = PaymentOrder::create([
            'number' => 'OP-2026-00023',
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'date' => '2026-05-31',
            'total' => 1285666.06,
            'status' => 'pagada',
            'payment_method' => 'cheque',
            'payment_reference' => '123456',
            'created_by' => $user->id,
        ]);

        PaymentOrderPaymentLine::create([
            'payment_order_id' => $order->id,
            'sort_order' => 0,
            'kind' => 'cheque',
            'amount' => 1285666.06,
            'payment_reference' => '123456',
            'cheque_due_date' => '2026-06-29',
        ]);

        $events = app(CashFlowCalendarService::class)->eventsForRange(
            $company->id,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $cheque = $events->firstWhere('category', 'echeq_emitido');
        $this->assertNotNull($cheque);
        $this->assertSame('2026-06-29', $cheque['date']);
        $this->assertEquals(1285666.06, $cheque['amount']);
        $this->assertSame('confirmed', $cheque['confidence']);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa 403',
            'cuit' => '30-71000007-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        \Spatie\Permission\Models\Permission::findOrCreate('compras.section');

        $user = User::factory()->create();
        $user->givePermissionTo('compras.section');
        $user->companies()->attach($company->id);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('cash-flow.index'))
            ->assertForbidden();
    }

    public function test_calendar_shows_events_from_all_accessible_companies(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Olie Clara Silvina',
            'short_name' => 'OCS',
            'cuit' => '30-71000010-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $companyB = Company::query()->create([
            'name' => 'Instituto Patológico',
            'short_name' => 'IPAC',
            'cuit' => '30-71000011-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = $this->setupUserWithCompanies([$companyA, $companyB]);

        $this->createPurchaseInvoiceDue($companyA, $user, '2026-05-15', 100000, 'S-A');
        $this->createPurchaseInvoiceDue($companyB, $user, '2026-05-15', 250000, 'S-B');

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('cash-flow.index', ['date' => '2026-05-15']))
            ->assertOk()
            ->assertSee('OCS')
            ->assertSee('IPAC')
            ->assertSee('100.000')
            ->assertSee('250.000')
            ->assertSee('FC')
            ->assertSee('Totales por empresa');
    }

    public function test_calendar_company_filter_limits_events(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Empresa Alpha',
            'short_name' => 'ALPHA',
            'cuit' => '30-71000012-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $companyB = Company::query()->create([
            'name' => 'Empresa Beta',
            'short_name' => 'BETA',
            'cuit' => '30-71000013-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = $this->setupUserWithCompanies([$companyA, $companyB]);

        $this->createPurchaseInvoiceDue($companyA, $user, '2026-05-20', 111111, 'S-ALPHA');
        $this->createPurchaseInvoiceDue($companyB, $user, '2026-05-20', 222222, 'S-BETA');

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('cash-flow.index', [
                'date' => '2026-05-20',
                'filters' => 1,
                'companies' => [$companyA->id],
                'categories' => array_keys(CashFlowCalendarService::categoryMeta()),
            ]))
            ->assertOk()
            ->assertSee('ALPHA')
            ->assertSee('111.111')
            ->assertDontSee('222.222');
    }

    public function test_single_company_hides_company_label_in_html(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Única',
            'short_name' => 'UNICA',
            'cuit' => '30-71000014-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = $this->setupUser($company);
        $this->createPurchaseInvoiceDue($company, $user, '2026-05-10', 50000, 'S-UNICA');

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('cash-flow.index', ['date' => '2026-05-10']))
            ->assertOk()
            ->assertSee('50.000')
            ->assertDontSee('Totales por empresa')
            ->assertDontSee('>Empresas<');
    }

    public function test_calendar_category_filter_limits_events(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Cat',
            'cuit' => '30-71000017-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = $this->setupUser($company);

        $this->createPurchaseInvoiceDue($company, $user, '2026-05-18', 999999, 'S-CAT');

        CashFlowObligation::create([
            'company_id' => $company->id,
            'category' => CashFlowObligation::CATEGORY_ECHEQ,
            'title' => 'E-cheq manual',
            'amount' => 50000,
            'due_date' => '2026-05-18',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('cash-flow.index', [
                'date' => '2026-05-18',
                'filters' => 1,
                'categories' => ['factura_compra'],
                'companies' => [$company->id],
            ]))
            ->assertOk()
            ->assertSee('999.999')
            ->assertDontSee('ECHEQ');
    }

    public function test_header_shows_active_filters(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Filtros',
            'cuit' => '30-71000018-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = $this->setupUser($company);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('cash-flow.index'))
            ->assertOk()
            ->assertSee('Filtros');
    }

    public function test_fixed_expense_projects_only_for_company_of_latest_invoice(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Olla Clara Silvina',
            'short_name' => 'OCS',
            'cuit' => '30-71000019-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $companyB = Company::query()->create([
            'name' => 'IPAC SAS',
            'short_name' => 'IPAC',
            'cuit' => '30-71000020-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'code' => 'S-ALQ',
            'name' => 'CONDOMINIO BIANCHI',
            'tax_id' => '30-66666666-6',
            'status' => 'activo',
            'is_fixed_expense' => true,
        ]);

        PurchaseInvoice::create([
            'company_id' => $companyA->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => '231',
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => '2026-02-06',
            'due_date' => '2026-03-06',
            'subtotal' => 3872000,
            'total' => 3872000,
            'balance' => 0,
            'status' => 'pagada',
            'created_by' => $user->id,
        ]);

        PurchaseInvoice::create([
            'company_id' => $companyB->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => '37',
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => '2026-05-05',
            'due_date' => '2026-06-04',
            'subtotal' => 4198700,
            'total' => 4198700,
            'balance' => 4198700,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $service = app(CashFlowCalendarService::class);
        $events = $service->eventsForCompanies(
            collect([$companyA, $companyB]),
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        );

        $fixed = $events->where('category', 'gasto_fijo')->values();
        $this->assertCount(1, $fixed);
        $this->assertSame($companyB->id, $fixed->first()['company_id']);
        $this->assertEquals(4198700.0, $fixed->first()['amount']);
    }

    public function test_event_ids_unique_across_companies(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Empresa IDs A',
            'cuit' => '30-71000015-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $companyB = Company::query()->create([
            'name' => 'Empresa IDs B',
            'cuit' => '30-71000016-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'code' => 'S-IDS',
            'name' => 'Proveedor IDs',
            'tax_id' => '30-55555555-5',
            'status' => 'activo',
        ]);

        $invoiceA = PurchaseInvoice::create([
            'company_id' => $companyA->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => '200',
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-25',
            'subtotal' => 1000,
            'total' => 1000,
            'balance' => 1000,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $invoiceB = PurchaseInvoice::create([
            'company_id' => $companyB->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => '200',
            'voucher_type' => 'A',
            'point_of_sale' => '1',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-25',
            'subtotal' => 2000,
            'total' => 2000,
            'balance' => 2000,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $service = app(CashFlowCalendarService::class);
        $events = $service->eventsForCompanies(
            collect([$companyA, $companyB]),
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $fcEvents = $events->where('category', 'factura_compra')->values();
        $this->assertCount(2, $fcEvents);
        $this->assertNotSame($fcEvents[0]['id'], $fcEvents[1]['id']);
        $this->assertSame('c'.$companyA->id.':fc:'.$invoiceA->id, $fcEvents->firstWhere('company_id', $companyA->id)['id']);
        $this->assertSame('c'.$companyB->id.':fc:'.$invoiceB->id, $fcEvents->firstWhere('company_id', $companyB->id)['id']);
    }
}
