<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LabBranch;
use App\Models\Material;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleLabelDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_label_data_returns_one_label_row_per_distinct_material(): void
    {
        Permission::findOrCreate('samples.section', 'web');
        Permission::findOrCreate('samples-labels.print', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo(['samples.section', 'samples-labels.print']);

        $branch = LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'name' => 'Cliente',
            'taxId' => '20-11111111-1',
            'status' => 'activo',
            'type' => 'particular',
        ]);

        $m1 = Material::query()->create(['code' => 'EDTA', 'name' => 'Tubo EDTA', 'is_active' => true]);
        $m2 = Material::query()->create(['code' => 'SUE', 'name' => 'Suero', 'is_active' => true]);

        $t1 = Test::query()->create([
            'code' => 'HEM',
            'name' => 'Hemograma',
            'unit' => '-',
            'material' => $m1->id,
            'price' => 100,
            'categories' => ['aguas_alimentos'],
        ]);
        $t2 = Test::query()->create([
            'code' => 'GLU',
            'name' => 'Glucemia',
            'unit' => 'mg/dl',
            'material' => $m2->id,
            'price' => 100,
            'categories' => ['aguas_alimentos'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => Sample::generateProtocolNumber(),
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'L',
            'batch' => 'B1',
            'product_name' => 'Agua',
            'status' => 'pending',
            'lab_branch_id' => $branch->id,
        ]);

        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $t1->id,
            'price' => 100,
            'status' => 'pending',
        ]);
        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $t2->id,
            'price' => 100,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson(route('sample.labelData', $sample));

        $response->assertOk()
            ->assertJsonStructure(['labels', 'total_labels']);

        $labels = $response->json('labels');
        $this->assertCount(2, $labels);
        $this->assertSame(2, $response->json('total_labels'));

        $abbrs = collect($labels)->pluck('material')->sort()->values()->all();
        $this->assertSame(['EDTA', 'SUE'], $abbrs);

        foreach ($labels as $row) {
            $this->assertArrayHasKey('material_key', $row);
            $this->assertArrayHasKey('material_name', $row);
            $this->assertSame($sample->protocol_number, $row['protocol_number']);
        }
    }
}
