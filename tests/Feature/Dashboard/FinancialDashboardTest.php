<?php

namespace Tests\Feature\Dashboard;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['admin', 'contador', 'compras', 'ventas', 'bioquimico'] as $name) {
            Role::findOrCreate($name, 'web');
        }
    }

    public function test_dashboard_redirige_login_si_no_autenticado(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_personalizado_no_muestra_panel_ejecutivo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Panel ejecutivo');
    }

    public function test_financial_dashboard_admin_ve_panel_ejecutivo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard.financial'));

        $response->assertOk();
        $response->assertSee('Panel ejecutivo');
        $response->assertSee('Ventas del mes', false);
        $response->assertSee('Compras del mes', false);
        $response->assertSee('Ingresos del mes', false);
        $response->assertSee('Egresos del mes', false);
    }

    public function test_financial_dashboard_contador_ve_panel_ejecutivo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('contador');

        $this->actingAs($user)
            ->get(route('dashboard.financial'))
            ->assertOk()
            ->assertSee('Panel ejecutivo');
    }

    public function test_financial_dashboard_compras_recibe_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('compras');

        $this->actingAs($user)
            ->get(route('dashboard.financial'))
            ->assertForbidden();
    }

    public function test_financial_dashboard_muestra_banner_si_no_hay_empresa_activa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        session()->forget('active_company_id');

        $this->actingAs($user)
            ->get(route('dashboard.financial'))
            ->assertOk()
            ->assertSee('Seleccion');
    }

    public function test_financial_dashboard_muestra_nombre_de_empresa_activa(): void
    {
        $company = Company::query()->create([
            'name' => 'Lab Empresa Activa',
            'cuit' => '30-12345678-9',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        session()->put('active_company_id', $company->id);

        $this->actingAs($user)
            ->get(route('dashboard.financial'))
            ->assertOk()
            ->assertSee('Lab Empresa Activa');
    }
}
