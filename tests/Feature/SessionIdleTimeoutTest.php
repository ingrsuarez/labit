<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SessionIdleTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
    }

    public function test_cierra_sesion_cuando_supera_inactividad_configurada(): void
    {
        config(['session.idle_timeout_minutes' => 5]);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->forceFill(['last_activity_at' => now()->subMinutes(6)])->saveQuietly();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    public function test_permite_acceso_cuando_hay_actividad_reciente(): void
    {
        config(['session.idle_timeout_minutes' => 30]);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->forceFill(['last_activity_at' => now()->subMinutes(5)])->saveQuietly();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_politica_desactivada_con_idle_cero(): void
    {
        config(['session.idle_timeout_minutes' => 0]);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->forceFill(['last_activity_at' => now()->subDays(7)])->saveQuietly();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }
}
