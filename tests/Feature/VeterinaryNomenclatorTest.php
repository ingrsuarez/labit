<?php

namespace Tests\Feature;

use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VeterinaryNomenclatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
    }

    private function baseTestAttributes(array $overrides = []): array
    {
        return array_merge([
            'unit' => null,
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => null,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => null,
            'price' => 0,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'sort_order' => 0,
        ], $overrides);
    }

    public function test_nomenclador_lista_solo_raices_veterinarias(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $vetRoot = Test::query()->create($this->baseTestAttributes([
            'code' => 'VETROOT1',
            'name' => 'panel vet raíz',
            'categories' => ['veterinario'],
        ]));

        $vetChild = Test::query()->create($this->baseTestAttributes([
            'code' => 'VETCHILD',
            'name' => 'hijo vet',
            'categories' => ['veterinario'],
        ]));
        $vetChild->parentTests()->attach($vetRoot->id);

        Test::query()->create($this->baseTestAttributes([
            'code' => 'CLINROOT',
            'name' => 'solo clinico',
            'categories' => ['clinico'],
        ]));

        $response = $this->actingAs($user)->get(route('lab.veterinario.nomenclador'));

        $response->assertOk();
        $response->assertSee('VETROOT1', false);
        $response->assertDontSee('VETCHILD', false);
        $response->assertDontSee('CLINROOT', false);
    }
}
