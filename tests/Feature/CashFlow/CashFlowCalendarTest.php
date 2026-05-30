<?php

namespace Tests\Feature\CashFlow;

use App\Models\CashFlowObligation;
use App\Models\Company;
use App\Models\Form931Declaration;
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
}
