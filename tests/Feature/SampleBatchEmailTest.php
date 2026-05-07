<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleBatchEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_invitado_no_puede_envio_masivo_muestras(): void
    {
        $this->postJson(route('sample.batch-email'), [
            'sample_ids' => [1],
        ])->assertUnauthorized();
    }

    public function test_usuario_sin_permiso_muestras_recibe_403(): void
    {
        Role::create(['name' => 'test_sin_muestras', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('test_sin_muestras');

        $this->actingAs($user)
            ->postJson(route('sample.batch-email'), [
                'sample_ids' => [1],
            ])
            ->assertForbidden();
    }
}
