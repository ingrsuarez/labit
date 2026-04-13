<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VetAdmissionNbuPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
    }

    public function test_search_tests_devuelve_precio_nbu_veterinaria_por_nbu_practica(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $customer = Customer::query()->create([
            'name' => 'Veterinaria Test',
            'taxId' => '30-70000000-7',
            'status' => 'activo',
            'type' => ['veterinario'],
            'veterinary_nbu_value' => 100,
        ]);

        $test = Test::query()->create([
            'code' => 'VETNBU1',
            'name' => 'práctica vet test',
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
            'nbu' => 2.5,
            'categories' => ['veterinario'],
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('vet.admissions.searchTests', [
                'q' => 'VETNBU',
                'customer_id' => $customer->id,
            ]));

        $response->assertOk();
        $rows = $response->json();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $row = collect($rows)->firstWhere('id', $test->id);
        $this->assertNotNull($row);
        $this->assertSame(250.0, (float) $row['price']);
        $this->assertSame(2.5, (float) $row['nbu']);
    }

    public function test_search_tests_sin_customer_id_responde_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $response = $this->actingAs($user)
            ->getJson(route('vet.admissions.searchTests', ['q' => 'xx']));

        $response->assertStatus(422);
    }
}
