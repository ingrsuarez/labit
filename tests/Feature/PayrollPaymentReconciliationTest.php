<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\PayrollPayment;
use App\Models\User;
use App\Services\BankReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollPaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private BankAccount $bankAccount;

    private BankStatement $statement;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['contabilidad.section', 'contabilidad.bank_statements.index', 'contabilidad.reconciliation.manual'] as $name) {
            Permission::findOrCreate($name);
        }

        $this->company = Company::query()->create([
            'name' => 'Test SA',
            'cuit' => '30-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'contabilidad.section',
            'contabilidad.bank_statements.index',
            'contabilidad.reconciliation.manual',
        ]);
        $this->user->companies()->attach($this->company->id, ['is_default' => true]);

        $bankAccountingAccount = AccountingAccount::query()->create([
            'code' => '1.1.02', 'name' => 'Banco CC',
            'type' => 'activo', 'is_active' => true,
            'level' => 3, 'is_header' => false,
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'company_id' => $this->company->id,
            'bank_name' => 'Banco Nación',
            'account_number' => '123-1',
            'account_type' => 'cuenta_corriente',
            'currency' => 'ARS',
            'accounting_account_id' => $bankAccountingAccount->id,
            'is_active' => true,
        ]);

        $this->statement = BankStatement::query()->create([
            'bank_account_id' => $this->bankAccount->id,
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'filename' => 'extracto.xlsx',
            'imported_by' => $this->user->id,
            'imported_at' => now(),
            'status' => 'confirmed',
        ]);
    }

    private function makePayrollPayment(array $overrides = []): PayrollPayment
    {
        return PayrollPayment::query()->create(array_merge([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'year' => 2026,
            'month' => 4,
            'period_label' => 'abril 2026',
            'payment_date' => Carbon::parse('2026-04-07'),
            'total' => 1437519.70,
            'employee_count' => 5,
            'status' => 'confirmado',
            'created_by' => $this->user->id,
            'confirmed_at' => now(),
            'confirmed_by' => $this->user->id,
        ], $overrides));
    }

    private function makeMovement(array $overrides = []): BankMovement
    {
        return BankMovement::query()->create(array_merge([
            'bank_statement_id' => $this->statement->id,
            'date' => '2026-04-07',
            'concept' => 'OG-DEBITO 58356HABERES OL',
            'debit' => 1437519.70,
            'credit' => 0,
            'reconciliation_status' => 'pending',
        ], $overrides));
    }

    public function test_find_match_payroll_payment_exact_confidence(): void
    {
        $this->makePayrollPayment();
        $movement = $this->makeMovement();

        $service = new BankReconciliationService;
        $r = $service->findMatch($movement, $this->company->id);

        $this->assertSame('exact', $r['confidence']);
        $this->assertInstanceOf(PayrollPayment::class, $r['record']);
    }

    public function test_find_match_ignores_borrador_payroll_payment(): void
    {
        $this->makePayrollPayment(['status' => 'borrador', 'confirmed_at' => null, 'confirmed_by' => null]);
        $movement = $this->makeMovement();

        $service = new BankReconciliationService;
        $r = $service->findMatch($movement, $this->company->id);

        $this->assertSame('none', $r['confidence']);
    }

    public function test_find_match_ignores_already_reconciled_payroll_payment(): void
    {
        $pp = $this->makePayrollPayment();
        $this->makeMovement([
            'date' => '2026-04-05',
            'concept' => 'otro',
            'reconciliation_status' => 'matched',
            'reconciled_type' => PayrollPayment::class,
            'reconciled_id' => $pp->id,
            'reconciled_at' => now(),
            'reconciled_by' => $this->user->id,
        ]);

        $movement = $this->makeMovement(['date' => '2026-04-07']);

        $service = new BankReconciliationService;
        $r = $service->findMatch($movement, $this->company->id);

        $this->assertSame('none', $r['confidence']);
    }

    public function test_find_match_probable_when_date_within_five_days(): void
    {
        $this->makePayrollPayment(['payment_date' => Carbon::parse('2026-04-10')]);
        $movement = $this->makeMovement(['date' => '2026-04-07']);

        $service = new BankReconciliationService;
        $r = $service->findMatch($movement, $this->company->id);

        $this->assertSame('probable', $r['confidence']);
        $this->assertInstanceOf(PayrollPayment::class, $r['record']);
    }

    public function test_link_endpoint_sets_matched_status(): void
    {
        $pp = $this->makePayrollPayment();
        $movement = $this->makeMovement();

        $this->actingAs($this->user)
            ->post(route('accounting.reconciliation.link', $movement), [
                'reconciled_type' => 'PayrollPayment',
                'reconciled_id' => $pp->id,
            ])
            ->assertRedirect();

        $movement->refresh();
        $this->assertSame('matched', $movement->reconciliation_status);
        $this->assertSame(PayrollPayment::class, $movement->reconciled_type);
        $this->assertSame($pp->id, (int) $movement->reconciled_id);
    }

    public function test_unlink_clears_reconciliation(): void
    {
        $pp = $this->makePayrollPayment();
        $movement = $this->makeMovement([
            'reconciliation_status' => 'matched',
            'reconciled_type' => PayrollPayment::class,
            'reconciled_id' => $pp->id,
            'reconciled_at' => now(),
            'reconciled_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.reconciliation.unlink', $movement))
            ->assertRedirect();

        $movement->refresh();
        $this->assertSame('pending', $movement->reconciliation_status);
        $this->assertNull($movement->reconciled_type);
        $this->assertNull($movement->reconciled_id);

        $this->assertSame(0, $pp->fresh()->reconciledMovements()->count());
    }

    public function test_categorize_og_deb_cred_haberes_variant(): void
    {
        $this->assertSame('haberes', BankMovement::categorize('OG-DEB./CRED 58356HABERES OL', null));
    }

    public function test_categorize_og_debito_haberes_variant(): void
    {
        $this->assertSame('haberes', BankMovement::categorize('OG-DEBITO 58356HABERES OL', null));
    }
}
