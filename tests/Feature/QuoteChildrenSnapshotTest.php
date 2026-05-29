<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Test;
use App\Models\User;
use App\Services\QuoteItemChildrenSnapshotBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QuoteChildrenSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
    }

    /** @return array{0: User, 1: Company} */
    private function userWithCompany(): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test Presupuestos Hijos',
            'cuit' => '20-77777777-7',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'Calle Test',
            'city' => 'CABA',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');
        $user->companies()->attach($company->id, ['is_default' => true]);

        return [$user, $company];
    }

    /** @return array{0: Test, 1: Test, 2: Test} */
    private function createParentWithChildren(): array
    {
        $parent = Test::query()->create([
            'code' => '1000',
            'name' => 'Fisicoquímico básico',
            'price' => 80000,
        ]);

        $childA = Test::query()->create([
            'code' => '1001',
            'name' => 'pH',
            'price' => 0,
        ]);

        $childB = Test::query()->create([
            'code' => '1002',
            'name' => 'Turbidez',
            'price' => 0,
        ]);

        $parent->childTests()->attach($childA->id, ['order' => 1]);
        $parent->childTests()->attach($childB->id, ['order' => 2]);

        return [$parent, $childA, $childB];
    }

    public function test_store_persiste_children_snapshot_y_show_lo_muestra(): void
    {
        [$user, $company] = $this->userWithCompany();
        [$parent, $childA, $childB] = $this->createParentWithChildren();

        $snapshot = [
            ['test_id' => $childA->id, 'name' => '1001 - pH', 'depth' => 1],
            ['test_id' => $childB->id, 'name' => '1002 - Turbidez', 'depth' => 1],
        ];

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('quotes.store'), [
                'customer_name' => 'Cliente Test',
                'tax_rate' => 0,
                'items' => [
                    [
                        'test_id' => $parent->id,
                        'description' => '1000 - Fisicoquímico básico',
                        'quantity' => 1,
                        'unit_price' => 80000,
                        'children_snapshot' => $snapshot,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $quote = Quote::query()->latest('id')->first();
        $this->assertNotNull($quote);

        $item = $quote->items()->first();
        $this->assertNotNull($item);
        $this->assertCount(2, $item->children_snapshot);
        $this->assertSame('1001 - pH', $item->children_snapshot[0]['name']);

        $show = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('quotes.show', $quote));

        $show->assertOk();
        $show->assertSee('1001 - pH');
        $show->assertSee('1002 - Turbidez');
    }

    public function test_snapshot_congelado_no_cambia_si_se_edita_nomenclador(): void
    {
        [$user, $company] = $this->userWithCompany();
        [$parent, $childA] = $this->createParentWithChildren();

        $quote = Quote::query()->create([
            'company_id' => $company->id,
            'quote_number' => 'PRES-2099-00001',
            'customer_name' => 'Cliente Snapshot',
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'test_id' => $parent->id,
            'description' => '1000 - Fisicoquímico básico',
            'quantity' => 1,
            'unit_price' => 80000,
            'total' => 80000,
            'sort_order' => 0,
            'children_snapshot' => [
                ['test_id' => $childA->id, 'name' => '1001 - pH', 'depth' => 1],
            ],
        ]);

        $newChild = Test::query()->create([
            'code' => '1099',
            'name' => 'Sulfatos',
            'price' => 0,
        ]);
        $parent->childTests()->attach($newChild->id, ['order' => 99]);

        $item = $quote->items()->first();
        $this->assertCount(1, $item->resolvedChildren());
        $this->assertSame('1001 - pH', $item->resolvedChildren()[0]['name']);

        $show = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('quotes.show', $quote));

        $show->assertOk();
        $show->assertSee('1001 - pH');
        $show->assertDontSee('Sulfatos');
    }

    public function test_resolved_children_usa_fallback_dinamico_sin_snapshot(): void
    {
        [$parent, $childA, $childB] = $this->createParentWithChildren();

        $item = QuoteItem::query()->make([
            'test_id' => $parent->id,
            'description' => '1000 - Fisicoquímico básico',
            'quantity' => 1,
            'unit_price' => 80000,
            'total' => 80000,
            'children_snapshot' => null,
        ]);
        $item->setRelation('test', $parent);

        $children = $item->resolvedChildren();
        $this->assertCount(2, $children);
        $this->assertSame('1001 - pH', $children[0]['name']);
        $this->assertSame('1002 - Turbidez', $children[1]['name']);
    }

    public function test_item_manual_no_tiene_hijos(): void
    {
        $item = QuoteItem::query()->make([
            'test_id' => null,
            'description' => 'Servicio manual',
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
            'children_snapshot' => null,
        ]);

        $this->assertSame([], $item->resolvedChildren());
    }

    public function test_search_tests_incluye_children_en_respuesta(): void
    {
        [$user, $company] = $this->userWithCompany();
        [$parent] = $this->createParentWithChildren();

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('quotes.searchTests', ['q' => 'Fisico']));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $match = collect($data)->firstWhere('id', $parent->id);
        $this->assertNotNull($match);
        $this->assertCount(2, $match['children']);
    }

    public function test_builder_soporta_sub_padres_con_depth_progresivo(): void
    {
        $parent = Test::query()->create(['code' => '2000', 'name' => 'Paquete', 'price' => 50000]);
        $subParent = Test::query()->create(['code' => '2001', 'name' => 'Grupo físico', 'price' => 0]);
        $leaf = Test::query()->create(['code' => '2002', 'name' => 'pH', 'price' => 0]);

        $parent->childTests()->attach($subParent->id, ['order' => 1]);
        $subParent->childTests()->attach($leaf->id, ['order' => 1]);

        $snapshot = app(QuoteItemChildrenSnapshotBuilder::class)->build($parent);

        $this->assertCount(2, $snapshot);
        $this->assertSame(1, $snapshot[0]['depth']);
        $this->assertSame('2001 - Grupo físico', $snapshot[0]['name']);
        $this->assertSame(2, $snapshot[1]['depth']);
        $this->assertSame('2002 - pH', $snapshot[1]['name']);
    }
}
