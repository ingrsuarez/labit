<?php

namespace Tests\Feature\Navigation;

use App\Models\Employee;
use App\Models\User;
use App\Models\UserNavigationStat;
use App\Support\NavigationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PortalHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
    }

    private function makeEmployeeUser(): User
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

        return $user->fresh();
    }

    public function test_portal_dashboard_muestra_cards_y_perfil(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Inicio')
            ->assertSee('Tus accesos más utilizados')
            ->assertSee('Recibos de Sueldo')
            ->assertSee('Mi Perfil')
            ->assertSee('Antigüedad');
    }

    public function test_portal_shortcuts_ordenados_por_uso(): void
    {
        $user = $this->makeEmployeeUser();

        UserNavigationStat::create([
            'user_id' => $user->id,
            'shortcut_key' => 'portal-circulars',
            'hit_count' => 20,
            'last_accessed_at' => now(),
        ]);
        UserNavigationStat::create([
            'user_id' => $user->id,
            'shortcut_key' => 'portal-payslips',
            'hit_count' => 3,
            'last_accessed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('portal.dashboard'));

        $response->assertOk();
        $content = $response->getContent();
        $circularsPos = strpos($content, 'Circulares');
        $payslipsPos = strpos($content, 'Recibos de Sueldo');
        $this->assertNotFalse($circularsPos);
        $this->assertNotFalse($payslipsPos);
        $this->assertLessThan($payslipsPos, $circularsPos);
    }

    public function test_shortcuts_for_portal_user_solo_keys_portal(): void
    {
        $user = $this->makeEmployeeUser();

        $shortcuts = NavigationCatalog::shortcutsForPortalUser($user);

        $this->assertNotEmpty($shortcuts);
        foreach ($shortcuts as $shortcut) {
            $this->assertNotSame('Resumen financiero', $shortcut['name']);
            $this->assertNotSame('Compras', $shortcut['name']);
        }
    }

    public function test_usuario_mixto_stats_compartidos_entre_dashboard_y_portal(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Employee::query()->create([
            'user_id' => $user->id,
            'name' => 'Admin',
            'lastName' => 'Employee',
            'employeeId' => 'E002',
            'email' => $user->email,
            'sex' => 'F',
            'status' => 'active',
        ]);
        $user = $user->fresh();

        $this->actingAs($user)->get(route('portal.payslips'))->assertOk();

        $this->assertDatabaseHas('user_navigation_stats', [
            'user_id' => $user->id,
            'shortcut_key' => 'portal-payslips',
            'hit_count' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recibos de Sueldo');
    }

    public function test_portal_sidebar_muestra_inicio(): void
    {
        $user = $this->makeEmployeeUser();

        $response = $this->actingAs($user)->get(route('portal.dashboard'));

        $response->assertOk()
            ->assertSee('Inicio')
            ->assertSee(route('portal.dashboard', [], false));
    }
}
