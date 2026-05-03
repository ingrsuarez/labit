<?php

namespace Tests\Feature\Rrhh;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RrhhAccessTest extends TestCase
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

    public function test_rrhh_redirige_login_si_no_autenticado(): void
    {
        $this->get('/rrhh')->assertRedirect(route('login'));
    }

    public function test_rrhh_admin_ve_panel_de_recursos_humanos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Panel de Recursos Humanos');
    }

    public function test_rrhh_contador_ve_panel_de_recursos_humanos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('contador');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Panel de Recursos Humanos');
    }

    public function test_rrhh_compras_recibe_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('compras');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertForbidden();
    }

    public function test_rrhh_bioquimico_recibe_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bioquimico');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertForbidden();
    }
}
