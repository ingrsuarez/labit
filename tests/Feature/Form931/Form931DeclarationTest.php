<?php

namespace Tests\Feature\Form931;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\Form931Declaration;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Form931DeclarationService;
use Database\Seeders\TaxDeclarationsPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Form931DeclarationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seed931Accounts(): void
    {
        foreach (['2.1', '5.2'] as $parentCode) {
            AccountingAccount::query()->firstOrCreate(
                ['code' => $parentCode],
                [
                    'name' => 'Header '.$parentCode,
                    'type' => str_starts_with($parentCode, '2') ? 'pasivo' : 'resultado_negativo',
                    'level' => 2,
                    'is_header' => true,
                    'is_active' => true,
                ]
            );
        }

        $accounts = [
            ['code' => '2.1.11', 'name' => 'Aportes patronales SUSS a Pagar', 'type' => 'pasivo'],
            ['code' => '2.1.12', 'name' => 'Contribuciones patronales SUSS a Pagar', 'type' => 'pasivo'],
            ['code' => '5.2.11', 'name' => 'Aportes patronales', 'type' => 'resultado_negativo'],
            ['code' => '5.2.12', 'name' => 'Contribuciones patronales', 'type' => 'resultado_negativo'],
        ];

        foreach ($accounts as $row) {
            AccountingAccount::query()->create([
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'level' => 3,
                'is_header' => false,
                'is_active' => true,
            ]);
        }
    }

    private function userWithAccess(Company $company, array $extraPermissions = []): User
    {
        $this->seed(TaxDeclarationsPermissionsSeeder::class);

        $permissions = array_merge(['form931.manage', 'contabilidad.section'], $extraPermissions);
        foreach ($permissions as $name) {
            \Spatie\Permission\Models\Permission::findOrCreate($name);
        }

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $user->companies()->attach($company->id, ['is_default' => true]);

        return $user;
    }

    public function test_guest_cannot_access_form931_index(): void
    {
        $this->get('/form931-declarations')->assertRedirect();
    }

    public function test_user_without_permission_gets_403(): void
    {
        \Spatie\Permission\Models\Permission::findOrCreate('contabilidad.section');

        $company = Company::query()->create([
            'name' => 'Empresa 931',
            'cuit' => '30-70000001-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('contabilidad.section');
        $user->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get('/form931-declarations')
            ->assertForbidden();
    }

    public function test_create_draft_and_confirm_generates_journal_entry(): void
    {
        $this->seed931Accounts();

        $company = Company::query()->create([
            'name' => 'Empresa 931',
            'cuit' => '30-70000002-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = $this->userWithAccess($company);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post('/form931-declarations', [
                'period_year' => 2026,
                'period_month' => 4,
                'amount_aportes_patronales' => 10000,
                'amount_contribuciones_patronales' => 5000,
                'notes' => 'Test F931',
            ]);

        $response->assertRedirect();
        $declaration = Form931Declaration::query()->first();
        $this->assertNotNull($declaration);
        $this->assertSame('draft', $declaration->status);
        $this->assertEquals(15000.0, (float) $declaration->total);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('form931-declarations.confirm', $declaration))
            ->assertRedirect();

        $declaration->refresh();
        $this->assertSame('confirmed', $declaration->status);
        $this->assertNotNull($declaration->journal_entry_id);

        $entry = JournalEntry::query()->find($declaration->journal_entry_id);
        $this->assertNotNull($entry);
        $entry->load('lines.account');

        $codes = $entry->lines->pluck('account.code')->sort()->values()->all();
        $this->assertEquals(['2.1.11', '2.1.12', '5.2.11', '5.2.12'], $codes);
    }

    public function test_cannot_confirm_second_declaration_same_period(): void
    {
        $this->seed931Accounts();

        $company = Company::query()->create([
            'name' => 'Empresa 931 dup',
            'cuit' => '30-70000003-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = $this->userWithAccess($company);
        $service = app(Form931DeclarationService::class);

        $first = Form931Declaration::query()->create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 5,
            'amount_aportes_patronales' => 1000,
            'amount_contribuciones_patronales' => 500,
            'total' => 1500,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        $service->confirm($first);

        $second = Form931Declaration::query()->create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 5,
            'amount_aportes_patronales' => 2000,
            'amount_contribuciones_patronales' => 1000,
            'total' => 3000,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->expectException(\DomainException::class);
        $service->confirm($second);
    }

    public function test_cancel_allows_new_confirmed_period(): void
    {
        $this->seed931Accounts();

        $company = Company::query()->create([
            'name' => 'Empresa 931 cancel',
            'cuit' => '30-70000004-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = $this->userWithAccess($company);
        $service = app(Form931DeclarationService::class);

        $declaration = Form931Declaration::query()->create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 6,
            'amount_aportes_patronales' => 3000,
            'amount_contribuciones_patronales' => 2000,
            'total' => 5000,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $service->confirm($declaration);
        $service->cancel($declaration->fresh());

        $replacement = Form931Declaration::query()->create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 6,
            'amount_aportes_patronales' => 3500,
            'amount_contribuciones_patronales' => 2500,
            'total' => 6000,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $entry = $service->confirm($replacement);
        $this->assertNotNull($entry);
        $this->assertSame('confirmed', $replacement->fresh()->status);
    }

    public function test_latest_confirmed_total_returns_last_amount(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa 931 latest',
            'cuit' => '30-70000005-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();

        Form931Declaration::query()->create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 1,
            'amount_aportes_patronales' => 1000,
            'amount_contribuciones_patronales' => 500,
            'total' => 1500,
            'status' => 'confirmed',
            'created_by' => $user->id,
            'confirmed_by' => $user->id,
            'confirmed_at' => now()->subMonth(),
        ]);

        Form931Declaration::query()->create([
            'company_id' => $company->id,
            'period_year' => 2026,
            'period_month' => 3,
            'amount_aportes_patronales' => 4000,
            'amount_contribuciones_patronales' => 2000,
            'total' => 6000,
            'status' => 'confirmed',
            'created_by' => $user->id,
            'confirmed_by' => $user->id,
            'confirmed_at' => now(),
        ]);

        $service = app(Form931DeclarationService::class);
        $this->assertEquals(6000.0, $service->latestConfirmedTotal($company->id));
    }

    public function test_other_company_declaration_is_not_visible(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Empresa A',
            'cuit' => '30-70000006-7',
            'tax_condition' => 'responsable_inscripto',
        ]);
        $companyB = Company::query()->create([
            'name' => 'Empresa B',
            'cuit' => '30-70000007-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = $this->userWithAccess($companyA);
        $user->companies()->attach($companyB->id);

        $declaration = Form931Declaration::query()->create([
            'company_id' => $companyB->id,
            'period_year' => 2026,
            'period_month' => 2,
            'amount_aportes_patronales' => 100,
            'amount_contribuciones_patronales' => 50,
            'total' => 150,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('form931-declarations.show', $declaration))
            ->assertNotFound();
    }
}
