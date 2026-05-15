<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QuoteIndexPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
        Role::findOrCreate('admin', 'web');
    }

    /** @return array{0: User, 1: Company} */
    private function userWithCompany(): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test Presupuestos',
            'cuit' => '20-88888888-8',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'Calle Test',
            'city' => 'CABA',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        return [$user, $company];
    }

    public function test_index_muestra_resumen_y_paginacion_con_mas_de_15_presupuestos(): void
    {
        [$user, $company] = $this->userWithCompany();

        for ($i = 1; $i <= 18; $i++) {
            Quote::query()->create([
                'company_id' => $company->id,
                'quote_number' => sprintf('PRES-2099-%05d', $i),
                'customer_name' => 'Cliente '.$i,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('quotes.index'));

        $response->assertOk();
        $html = $response->getContent();
        $plain = preg_replace('/\s+/u', ' ', trim(strip_tags($html)));
        $this->assertStringContainsString('Mostrando 1 – 15 de 18 presupuestos', $plain);
        $this->assertStringContainsString('page=2', $html);
    }

    public function test_index_muestra_resumen_con_un_solo_presupuesto(): void
    {
        [$user, $company] = $this->userWithCompany();

        Quote::query()->create([
            'company_id' => $company->id,
            'quote_number' => 'PRES-2099-09901',
            'customer_name' => 'Único',
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('quotes.index'));

        $response->assertOk();
        $plain = preg_replace('/\s+/u', ' ', trim(strip_tags($response->getContent())));
        $this->assertStringContainsString('Mostrando 1 – 1 de 1 presupuesto', $plain);
    }

    public function test_usuario_ventas_con_varias_empresas_ve_presupuestos_de_todas_aunque_el_header_tenga_una_sola(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Empresa A Presupuestos',
            'cuit' => '20-81111111-1',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'A',
            'city' => 'CABA',
        ]);
        $companyB = Company::query()->create([
            'name' => 'Empresa B Presupuestos',
            'cuit' => '20-82222222-2',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'B',
            'city' => 'CABA',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');
        $user->companies()->attach([
            $companyA->id => ['is_default' => true],
            $companyB->id => ['is_default' => false],
        ]);

        Quote::query()->create([
            'company_id' => $companyA->id,
            'quote_number' => 'PRES-2098-00001',
            'customer_name' => 'Cliente A',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        Quote::query()->create([
            'company_id' => $companyB->id,
            'quote_number' => 'PRES-2098-00002',
            'customer_name' => 'Cliente B',
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('quotes.index'));

        $response->assertOk();
        $plain = preg_replace('/\s+/u', ' ', trim(strip_tags($response->getContent())));
        $this->assertStringContainsString('Mostrando 1 – 2 de 2 presupuestos', $plain);
        $this->assertStringContainsString('PRES-2098-00001', $response->getContent());
        $this->assertStringContainsString('PRES-2098-00002', $response->getContent());
    }

    public function test_admin_ve_presupuestos_de_empresa_aunque_no_este_en_el_pivot(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Solo Admin',
            'cuit' => '20-83333333-3',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'X',
            'city' => 'CABA',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo('ventas.section');

        Quote::query()->create([
            'company_id' => $company->id,
            'quote_number' => 'PRES-2097-00099',
            'customer_name' => 'Cliente externo',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_company_id' => null])
            ->get(route('quotes.index'));

        $response->assertOk();
        $this->assertStringContainsString('PRES-2097-00099', $response->getContent());
    }
}
