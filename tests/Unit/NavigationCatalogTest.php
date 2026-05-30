<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\User;
use App\Models\UserNavigationStat;
use App\Services\NavigationAccessService;
use App\Support\NavigationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NavigationCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
    }

    public function test_shortcut_key_for_route_maps_correctly(): void
    {
        $this->assertSame('purchase-invoices', NavigationCatalog::shortcutKeyForRoute('purchase-invoices.index'));
        $this->assertSame('portal-payslips', NavigationCatalog::shortcutKeyForRoute('portal.payslips'));
        $this->assertNull(NavigationCatalog::shortcutKeyForRoute('unknown.route'));
    }

    public function test_navigation_access_service_increments_hits(): void
    {
        $user = User::factory()->create();
        $service = app(NavigationAccessService::class);

        $service->recordHit($user->id, 'purchase-invoices');
        $service->recordHit($user->id, 'purchase-invoices');

        $stat = UserNavigationStat::where('user_id', $user->id)
            ->where('shortcut_key', 'purchase-invoices')
            ->first();

        $this->assertNotNull($stat);
        $this->assertSame(2, $stat->hit_count);
    }

    public function test_shortcuts_for_user_respects_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $shortcuts = NavigationCatalog::shortcutsForUser($admin);
        $names = collect($shortcuts)->pluck('name')->all();

        $this->assertContains('Resumen financiero', $names);
    }

    public function test_shortcuts_for_portal_user_requires_employee(): void
    {
        $user = User::factory()->create();

        $shortcuts = NavigationCatalog::shortcutsForPortalUser($user);

        $this->assertSame([], $shortcuts);
    }

    public function test_shortcuts_for_portal_user_includes_payslips(): void
    {
        $user = User::factory()->create();
        Employee::query()->create([
            'user_id' => $user->id,
            'name' => 'Portal',
            'lastName' => 'User',
            'employeeId' => 'E003',
            'email' => $user->email,
            'sex' => 'M',
            'status' => 'active',
        ]);

        $shortcuts = NavigationCatalog::shortcutsForPortalUser($user->fresh());
        $names = collect($shortcuts)->pluck('name')->all();

        $this->assertContains('Recibos de Sueldo', $names);
    }
}
