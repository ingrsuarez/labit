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

    public function test_dashboard_admin_ve_panel_financiero(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Panel ejecutivo');
        $response->assertSee('Ventas del mes', false);
        $response->assertSee('Compras del mes', false);
        $response->assertSee('Ingresos del mes', false);
        $response->assertSee('Egresos del mes', false);
    }

    public function test_dashboard_contador_ve_panel_financiero(): void
    {
        $user = User::factory()->create();
        $user->assignRole('contador');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel ejecutivo');
    }

    public function test_dashboard_compras_es_redirigido_a_seccion_de_compras(): void
    {
        $user = User::factory()->create();
        $user->assignRole('compras');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('purchases.section'));
    }

    public function test_dashboard_ventas_es_redirigido_a_seccion_de_ventas(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ventas');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('sales.section'));
    }

    public function test_dashboard_bioquimico_es_redirigido_a_laboratorio(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bioquimico');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('lab.section.clinico'));
    }

    public function test_dashboard_muestra_banner_si_no_hay_empresa_activa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        session()->forget('active_company_id');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Seleccion');
    }

    public function test_dashboard_muestra_nombre_de_empresa_activa(): void
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
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lab Empresa Activa');
    }
}
