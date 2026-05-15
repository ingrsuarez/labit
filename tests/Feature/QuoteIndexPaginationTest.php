<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
}
