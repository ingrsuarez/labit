<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class JournalAndLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'contabilidad.section',
            'contabilidad.entries.index',
            'contabilidad.entries.create',
            'contabilidad.entries.edit',
            'contabilidad.entries.delete',
            'contabilidad.ledger.index',
        ] as $name) {
            Permission::findOrCreate($name);
        }
    }

    /** @return array{0: User, 1: Company, 2: AccountingAccount, 3: AccountingAccount} */
    private function userCompanyAndAccounts(): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'cuit' => '20-33333333-3',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        foreach ([
            'contabilidad.section',
            'contabilidad.entries.index',
            'contabilidad.entries.create',
            'contabilidad.entries.edit',
            'contabilidad.entries.delete',
            'contabilidad.ledger.index',
        ] as $p) {
            $user->givePermissionTo($p);
        }
        $user->companies()->attach($company->id, ['is_default' => true]);

        $a1 = AccountingAccount::query()->create([
            'code' => '1.1.99',
            'name' => 'Caja test',
            'type' => 'activo',
            'parent_id' => null,
            'level' => 3,
            'is_header' => false,
            'is_active' => true,
        ]);
        $a2 = AccountingAccount::query()->create([
            'code' => '2.1.99',
            'name' => 'Proveedores test',
            'type' => 'pasivo',
            'parent_id' => null,
            'level' => 3,
            'is_header' => false,
            'is_active' => true,
        ]);

        return [$user, $company, $a1, $a2];
    }

    public function test_guest_cannot_access_journal_index(): void
    {
        $this->get(route('accounting.journal.index'))->assertRedirect();
    }

    public function test_user_without_entries_index_gets_403_on_journal(): void
    {
        $company = Company::query()->create([
            'name' => 'C',
            'cuit' => '20-44444444-4',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo('contabilidad.section');
        $user->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('accounting.journal.index'))
            ->assertForbidden();
    }

    public function test_journal_index_ok_with_permissions(): void
    {
        [$user, $company] = $this->userCompanyAndAccounts();

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('accounting.journal.index'))
            ->assertOk()
            ->assertSee('Libro Diario', false);
    }

    public function test_store_manual_balanced_entry(): void
    {
        [$user, $company, $a1, $a2] = $this->userCompanyAndAccounts();

        $payload = [
            'date' => '2026-04-05',
            'description' => 'Asiento de prueba',
            'lines' => [
                ['accounting_account_id' => $a1->id, 'description' => '', 'debit' => 100, 'credit' => 0],
                ['accounting_account_id' => $a2->id, 'description' => '', 'debit' => 0, 'credit' => 100],
            ],
        ];

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('accounting.journal.store'), $payload)
            ->assertRedirect(route('accounting.journal.index'));

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'description' => 'Asiento de prueba',
            'is_automatic' => false,
        ]);
        $this->assertSame(2, JournalEntryLine::query()->count());
    }

    public function test_cannot_edit_automatic_entry(): void
    {
        [$user, $company, $a1, $a2] = $this->userCompanyAndAccounts();

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'date' => '2026-04-01',
            'number' => 1,
            'description' => 'Auto',
            'source_type' => null,
            'source_id' => null,
            'is_automatic' => true,
            'created_by' => $user->id,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'accounting_account_id' => $a1->id,
            'debit' => 10,
            'credit' => 0,
            'description' => null,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'accounting_account_id' => $a2->id,
            'debit' => 0,
            'credit' => 10,
            'description' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('accounting.journal.edit', $entry))
            ->assertRedirect(route('accounting.journal.index'));
    }

    public function test_ledger_shows_account_and_open_balance_from_prior_month(): void
    {
        [$user, $company, $a1, $a2] = $this->userCompanyAndAccounts();

        $march = JournalEntry::query()->create([
            'company_id' => $company->id,
            'date' => '2026-03-15',
            'number' => 1,
            'description' => 'Marzo',
            'source_type' => null,
            'source_id' => null,
            'is_automatic' => false,
            'created_by' => $user->id,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $march->id,
            'accounting_account_id' => $a1->id,
            'debit' => 100,
            'credit' => 0,
            'description' => null,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $march->id,
            'accounting_account_id' => $a2->id,
            'debit' => 0,
            'credit' => 100,
            'description' => null,
        ]);

        $april = JournalEntry::query()->create([
            'company_id' => $company->id,
            'date' => '2026-04-02',
            'number' => 2,
            'description' => 'Abril',
            'source_type' => null,
            'source_id' => null,
            'is_automatic' => false,
            'created_by' => $user->id,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $april->id,
            'accounting_account_id' => $a1->id,
            'debit' => 50,
            'credit' => 0,
            'description' => null,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $april->id,
            'accounting_account_id' => $a2->id,
            'debit' => 0,
            'credit' => 50,
            'description' => null,
        ]);

        $html = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('accounting.ledger', [
                'account_id' => $a1->id,
                'year' => 2026,
                'month' => 4,
            ]))
            ->assertOk()
            ->assertSee('1.1.99', false)
            ->assertSee('Saldo al inicio del período', false)
            ->getContent();

        $this->assertStringContainsString('$ 100,00', $html);
        $this->assertStringContainsString('$ 150,00', $html);
    }

    public function test_ledger_forbidden_without_permission(): void
    {
        $company = Company::query()->create([
            'name' => 'C2',
            'cuit' => '20-55555555-5',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $user = User::factory()->create();
        foreach (['contabilidad.section', 'contabilidad.entries.index'] as $p) {
            $user->givePermissionTo($p);
        }
        $user->companies()->attach($company->id, ['is_default' => true]);

        $acc = AccountingAccount::query()->create([
            'code' => '9.9.01',
            'name' => 'X',
            'type' => 'activo',
            'parent_id' => null,
            'level' => 3,
            'is_header' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('accounting.ledger', ['account_id' => $acc->id]))
            ->assertForbidden();
    }
}
