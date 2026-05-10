<?php

namespace Tests\Feature;

use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * v1.85.0 — Configuración rápida de determinación (PATCH tests.quickUpdate) solo para admin.
 */
class TestQuickUpdateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('bioquimico', 'web');
    }

    private function makeTest(): Test
    {
        return Test::query()->create([
            'code' => 'QUT1',
            'name' => 'Determinación quick',
            'unit' => 'mg/dL',
            'low' => '10',
            'high' => '20',
            'instructions' => null,
            'parent' => null,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => 'Método A',
            'price' => 0,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['lab'],
            'sort_order' => 0,
        ]);
    }

    public function test_usuario_bioquimico_recibe_403_en_quick_update(): void
    {
        $test = $this->makeTest();
        $user = User::factory()->create();
        $user->assignRole('bioquimico');

        $this->actingAs($user)
            ->from('/')
            ->patch(route('tests.quickUpdate', $test), [
                'unit' => 'g/L',
                'low' => '1',
                'high' => '5',
                'method' => 'Otro',
            ])
            ->assertForbidden();

        $this->assertSame('mg/dL', $test->fresh()->unit);
    }

    public function test_admin_puede_quick_update_y_redirige(): void
    {
        $test = $this->makeTest();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->from('/')
            ->patch(route('tests.quickUpdate', $test), [
                'unit' => 'µmol/L',
                'low' => '0',
                'high' => '100',
                'method' => 'Enzimático',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $test->refresh();
        $this->assertSame('µmol/L', $test->unit);
        $this->assertSame('0', $test->low);
        $this->assertSame('100', $test->high);
        $this->assertSame('Enzimático', $test->method);
    }
}
