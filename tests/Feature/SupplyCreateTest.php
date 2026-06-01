<?php

namespace Tests\Feature;

use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['compras.section', 'supplies.create'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    private function userWithCreatePermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['compras.section', 'supplies.create']);

        return $user;
    }

    public function test_ajax_create_respects_tracks_lot_false_in_json(): void
    {
        $user = $this->userWithCreatePermission();

        $response = $this->actingAs($user)
            ->postJson(route('supplies.store'), [
                'name' => 'Portaobjeto x 50',
                'unit' => 'unidad',
                'min_stock' => 0,
                'tracks_lot' => false,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('tracks_lot', false);

        $supply = Supply::query()->findOrFail($response->json('id'));
        $this->assertFalse($supply->tracks_lot);
    }

    public function test_ajax_create_respects_tracks_lot_true_in_json(): void
    {
        $user = $this->userWithCreatePermission();

        $response = $this->actingAs($user)
            ->postJson(route('supplies.store'), [
                'name' => 'Calibrador Proteinas',
                'unit' => 'unidad',
                'min_stock' => 0,
                'tracks_lot' => true,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('tracks_lot', true);

        $supply = Supply::query()->findOrFail($response->json('id'));
        $this->assertTrue($supply->tracks_lot);
    }

    public function test_form_create_without_checkbox_sets_tracks_lot_false(): void
    {
        $user = $this->userWithCreatePermission();

        $response = $this->actingAs($user)
            ->post(route('supplies.store'), [
                'name' => 'Placas Petri',
                'unit' => 'unidad',
                'min_stock' => 0,
            ]);

        $response->assertRedirect(route('supplies.index'));

        $supply = Supply::query()->where('name', 'Placas Petri')->firstOrFail();
        $this->assertFalse($supply->tracks_lot);
    }
}
