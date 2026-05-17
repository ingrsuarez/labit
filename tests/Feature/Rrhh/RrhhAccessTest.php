<?php

namespace Tests\Feature\Rrhh;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
        foreach (['personal.section', 'ausencias.section', 'liquidaciones.section', 'payroll-payments.manage'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function test_rrhh_redirige_login_si_no_autenticado(): void
    {
        $this->get('/rrhh')->assertRedirect(route('login'));
    }

    public function test_rrhh_admin_ve_hub_de_recursos_humanos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Recursos Humanos')
            ->assertSee('Personal')
            ->assertSee('Empleados')
            ->assertDontSee('Total Empleados');
    }

    public function test_rrhh_contador_ve_hub_de_recursos_humanos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('contador');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Recursos Humanos')
            ->assertSee('Personal');
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

    public function test_usuario_con_solo_ausencias_section_accede_al_hub(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ausencias.section');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Licencias')
            ->assertSee('Vacaciones')
            ->assertDontSee('Liquidaciones')
            ->assertDontSee('Generar Recibos');
    }

    public function test_rrhh_resumen_solo_admin_y_contador(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('rrhh.resumen'))
            ->assertOk()
            ->assertSee('Resumen de Recursos Humanos')
            ->assertSee('Total Empleados');

        $user = User::factory()->create();
        $user->givePermissionTo('personal.section');

        $this->actingAs($user)
            ->get(route('rrhh.resumen'))
            ->assertForbidden();
    }

    public function test_admin_section_personal_redirige_al_hub(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('personal.section');

        $this->actingAs($user)
            ->get(route('admin.section.personal'))
            ->assertRedirect(route('rrhh.index').'#personal');
    }

    public function test_hub_filtra_pagos_de_haberes_sin_permiso(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('liquidaciones.section');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Generar Recibos')
            ->assertDontSee('Pagos de Haberes');

        $user->givePermissionTo('payroll-payments.manage');

        $this->actingAs($user)
            ->get(route('rrhh.index'))
            ->assertOk()
            ->assertSee('Pagos de Haberes');
    }
}
