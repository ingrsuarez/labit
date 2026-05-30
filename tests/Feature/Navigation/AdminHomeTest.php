<?php

namespace Tests\Feature\Navigation;

use App\Models\Employee;
use App\Models\User;
use App\Models\UserNavigationStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['admin', 'contador', 'compras', 'ventas', 'bioquimico'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        foreach ([
            'compras.section',
            'purchase-invoices.index',
            'ventas.section',
            'lab.section',
            'lab-admissions.index',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    public function test_admin_dashboard_shows_personalized_home(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tus accesos más utilizados')
            ->assertSee('Resumen financiero')
            ->assertDontSee('Panel ejecutivo');
    }

    public function test_financial_dashboard_at_dedicated_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard.financial'))
            ->assertOk()
            ->assertSee('Panel ejecutivo')
            ->assertSee('Ventas del mes', false);
    }

    public function test_compras_home_excludes_financial_summary(): void
    {
        $user = User::factory()->create();
        $user->assignRole('compras');
        $user->givePermissionTo(['compras.section', 'purchase-invoices.index']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Facturas de Compra')
            ->assertDontSee('Resumen financiero');
    }

    public function test_navigation_tracking_increments_hit_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get(route('dashboard.financial'))->assertOk();

        $this->assertDatabaseHas('user_navigation_stats', [
            'user_id' => $user->id,
            'shortcut_key' => 'financial-summary',
            'hit_count' => 1,
        ]);
    }

    public function test_home_orders_shortcuts_by_usage(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('purchase-invoices.index');

        UserNavigationStat::create([
            'user_id' => $user->id,
            'shortcut_key' => 'purchase-invoices',
            'hit_count' => 50,
            'last_accessed_at' => now(),
        ]);
        UserNavigationStat::create([
            'user_id' => $user->id,
            'shortcut_key' => 'financial-summary',
            'hit_count' => 5,
            'last_accessed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $content = $response->getContent();
        $invoicesPos = strpos($content, 'Facturas de Compra');
        $financialPos = strpos($content, 'Resumen financiero');
        $this->assertNotFalse($invoicesPos);
        $this->assertNotFalse($financialPos);
        $this->assertLessThan($financialPos, $invoicesPos);
    }

    public function test_home_excludes_sidebar_hubs(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>Compras<', false)
            ->assertDontSee('>Ventas<', false)
            ->assertDontSee('>Recursos Humanos<', false)
            ->assertDontSee('>Contabilidad<', false)
            ->assertDontSee('>Auditoría<', false);
    }

    public function test_new_user_sees_default_shortcuts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('contador');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Resumen financiero');
    }

    public function test_portal_navigation_is_tracked(): void
    {
        $user = User::factory()->create();
        Employee::query()->create([
            'user_id' => $user->id,
            'name' => 'Test',
            'lastName' => 'Employee',
            'employeeId' => 'E001',
            'email' => $user->email,
            'sex' => 'M',
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('portal.payslips'))->assertOk();

        $this->assertDatabaseHas('user_navigation_stats', [
            'user_id' => $user->id,
            'shortcut_key' => 'portal-payslips',
            'hit_count' => 1,
        ]);
    }

    public function test_compras_role_sees_home_not_redirect_to_purchases_section(): void
    {
        $user = User::factory()->create();
        $user->assignRole('compras');
        $user->givePermissionTo(['compras.section', 'purchase-invoices.index']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Facturas de Compra')
            ->assertDontSee('>Compras<', false);
    }
}
