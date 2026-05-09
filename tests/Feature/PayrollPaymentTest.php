<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private BankAccount $bankAccount;
    private AccountingAccount $bankAccountingAccount;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['liquidaciones.section', 'payroll-payments.manage'] as $name) {
            Permission::findOrCreate($name);
        }

        $this->company = Company::query()->create([
            'name' => 'Test SA',
            'cuit' => '30-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['liquidaciones.section', 'payroll-payments.manage']);
        $this->user->companies()->attach($this->company->id, ['is_default' => true]);

        $this->bankAccountingAccount = AccountingAccount::query()->create([
            'code' => '1.1.02', 'name' => 'Banco CC',
            'type' => 'activo', 'is_active' => true,
            'level' => 3, 'is_header' => false,
        ]);

        AccountingAccount::query()->create([
            'code' => '2.1.07', 'name' => 'Sueldos a Pagar',
            'type' => 'pasivo', 'is_active' => true,
            'level' => 3, 'is_header' => false,
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'company_id' => $this->company->id,
            'bank_name' => 'Banco Test',
            'account_number' => '123-1',
            'account_type' => 'cuenta_corriente',
            'currency' => 'ARS',
            'accounting_account_id' => $this->bankAccountingAccount->id,
            'is_active' => true,
        ]);
    }

    private function makeEmployee(): Employee
    {
        static $seq = 0;
        $seq++;

        return Employee::query()->create([
            'company_id' => $this->company->id,
            'name' => "Empleado{$seq}",
            'lastName' => "Apellido{$seq}",
            'employeeId' => "20-{$seq}000000-0",
            'sex' => 'M',
            'status' => 'active',
        ]);
    }

    private function makePayroll(array $overrides = []): Payroll
    {
        $employee = $this->makeEmployee();

        return Payroll::query()->create(array_merge([
            'employee_id' => $employee->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'employee_name' => $employee->name.' '.$employee->lastName,
            'employee_cuil' => $employee->employeeId,
            'category_name' => 'Analista',
            'neto_a_cobrar' => 150000,
            'total_haberes' => 180000,
            'total_remunerativo' => 180000,
            'total_no_remunerativo' => 0,
            'total_deducciones' => 30000,
            'salario_basico' => 120000,
            'status' => 'liquidado',
            'payroll_payment_id' => null,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    private function makeConfirmedPayment(float $total = 100000): PayrollPayment
    {
        return PayrollPayment::query()->create([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'total' => $total, 'employee_count' => 1,
            'status' => 'confirmado',
            'created_by' => $this->user->id,
            'confirmed_at' => now(),
            'confirmed_by' => $this->user->id,
        ]);
    }

    private function actAsUser()
    {
        return $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->id]);
    }

    // 1. Usuario con permiso puede crear un PayrollPayment agrupando 2 liquidaciones
    public function test_user_with_permission_can_create_payroll_payment(): void
    {
        $p1 = $this->makePayroll(['neto_a_cobrar' => 100000]);
        $p2 = $this->makePayroll(['neto_a_cobrar' => 80000]);

        $response = $this->actAsUser()->post(route('payroll-payments.store'), [
            'year' => 2026, 'month' => 4,
            'bank_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-04-30',
            'notes' => 'Banco Nación',
            'payroll_ids' => [$p1->id, $p2->id],
        ]);

        $payment = PayrollPayment::first();
        $this->assertNotNull($payment);
        $this->assertEquals('borrador', $payment->status);
        $this->assertEquals(2, $payment->employee_count);
        $this->assertEquals(180000.00, (float) $payment->total);
        $response->assertRedirect(route('payroll-payments.show', $payment));
    }

    // 2. Al confirmar, las liquidaciones cambian a pagado
    public function test_confirm_marks_payrolls_as_paid(): void
    {
        $p1 = $this->makePayroll(['neto_a_cobrar' => 100000]);
        $p2 = $this->makePayroll(['neto_a_cobrar' => 80000]);

        $payment = PayrollPayment::query()->create([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'total' => 180000, 'employee_count' => 2,
            'status' => 'borrador',
            'created_by' => $this->user->id,
        ]);

        Payroll::whereIn('id', [$p1->id, $p2->id])
            ->update(['payroll_payment_id' => $payment->id]);

        $this->actAsUser()->post(route('payroll-payments.confirm', $payment));

        $this->assertEquals('confirmado', $payment->fresh()->status);
        $this->assertEquals('pagado', $p1->fresh()->status);
        $this->assertEquals('pagado', $p2->fresh()->status);
    }

    // 3. Al confirmar, se genera el asiento contable con las cuentas correctas
    public function test_confirm_generates_accounting_entry(): void
    {
        $p1 = $this->makePayroll(['neto_a_cobrar' => 100000]);

        $payment = PayrollPayment::query()->create([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bankAccount->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'payment_date' => '2026-04-30',
            'total' => 100000, 'employee_count' => 1,
            'status' => 'borrador',
            'created_by' => $this->user->id,
        ]);

        Payroll::where('id', $p1->id)->update(['payroll_payment_id' => $payment->id]);

        $this->actAsUser()->post(route('payroll-payments.confirm', $payment));

        $entry = JournalEntry::where('source_type', PayrollPayment::class)
            ->where('source_id', $payment->id)
            ->with('lines.account')
            ->first();

        $this->assertNotNull($entry);

        $debitLine = $entry->lines->firstWhere(fn ($l) => (float) $l->debit > 0);
        $creditLine = $entry->lines->firstWhere(fn ($l) => (float) $l->credit > 0);

        $this->assertEquals('2.1.07', $debitLine->account->code);
        $this->assertEquals('1.1.02', $creditLine->account->code);
        $this->assertEquals(100000.00, (float) $debitLine->debit);
        $this->assertEquals(100000.00, (float) $creditLine->credit);
    }

    // 4. No se puede incluir una liquidación en borrador (solo liquidado)
    public function test_cannot_include_draft_payroll(): void
    {
        $draft = $this->makePayroll(['status' => 'borrador']);

        $response = $this->actAsUser()->post(route('payroll-payments.store'), [
            'year' => 2026, 'month' => 4,
            'payroll_ids' => [$draft->id],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, PayrollPayment::count());
    }

    // 5. No se puede incluir una liquidación que ya tiene payroll_payment_id
    public function test_cannot_include_already_assigned_payroll(): void
    {
        $existingPayment = PayrollPayment::query()->create([
            'company_id' => $this->company->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'total' => 0, 'employee_count' => 0,
            'status' => 'borrador',
            'created_by' => $this->user->id,
        ]);

        $assigned = $this->makePayroll(['payroll_payment_id' => $existingPayment->id]);

        $response = $this->actAsUser()->post(route('payroll-payments.store'), [
            'year' => 2026, 'month' => 4,
            'payroll_ids' => [$assigned->id],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(1, PayrollPayment::count());
    }

    // 6. No se puede confirmar un PayrollPayment ya confirmado
    public function test_cannot_confirm_already_confirmed_payment(): void
    {
        $payment = $this->makeConfirmedPayment();

        $response = $this->actAsUser()->post(route('payroll-payments.confirm', $payment));

        $response->assertSessionHas('error');
    }

    // 7. Eliminar un PayrollPayment en borrador desvincula las liquidaciones
    public function test_delete_draft_payment_unlinks_payrolls(): void
    {
        $p1 = $this->makePayroll();

        $payment = PayrollPayment::query()->create([
            'company_id' => $this->company->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'total' => 150000, 'employee_count' => 1,
            'status' => 'borrador',
            'created_by' => $this->user->id,
        ]);

        Payroll::where('id', $p1->id)->update(['payroll_payment_id' => $payment->id]);

        $this->actAsUser()->delete(route('payroll-payments.destroy', $payment));

        $this->assertEquals(0, PayrollPayment::count());
        $this->assertNull($p1->fresh()->payroll_payment_id);
    }

    // 8. No se puede eliminar un PayrollPayment confirmado
    public function test_cannot_delete_confirmed_payment(): void
    {
        $payment = $this->makeConfirmedPayment();

        $this->actAsUser()->delete(route('payroll-payments.destroy', $payment));

        $this->assertEquals(1, PayrollPayment::count());
    }

    // 9. El total es la suma correcta de los neto_a_cobrar
    public function test_total_is_sum_of_payrolls(): void
    {
        $p1 = $this->makePayroll(['neto_a_cobrar' => 120000]);
        $p2 = $this->makePayroll(['neto_a_cobrar' => 95000]);
        $p3 = $this->makePayroll(['neto_a_cobrar' => 80000]);

        $payment = PayrollPayment::query()->create([
            'company_id' => $this->company->id,
            'year' => 2026, 'month' => 4,
            'period_label' => 'abril 2026',
            'total' => 0, 'employee_count' => 0,
            'status' => 'borrador',
            'created_by' => $this->user->id,
        ]);

        Payroll::whereIn('id', [$p1->id, $p2->id, $p3->id])
            ->update(['payroll_payment_id' => $payment->id]);

        $payment->recalculate();

        $this->assertEquals(295000.00, (float) $payment->total);
        $this->assertEquals(3, $payment->employee_count);
    }

    // 10. Usuario sin permiso no puede acceder al índice (403 o redirect)
    public function test_user_without_permission_cannot_access(): void
    {
        $unprivileged = User::factory()->create();
        $unprivileged->companies()->attach($this->company->id, ['is_default' => true]);

        $response = $this->actingAs($unprivileged)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('payroll-payments.index'));

        // El middleware de permiso deniega acceso (403) o redirige — nunca devuelve 200
        $this->assertNotEquals(200, $response->status());
    }
}
