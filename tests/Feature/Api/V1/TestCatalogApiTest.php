<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\Material;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private LabBranch $branch;

    private Company $company;

    private ApiClient $client;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = LabBranch::query()->create([
            'name' => 'Sede Centro',
            'is_central' => true,
            'is_active' => true,
        ]);

        $this->company = Company::query()->create([
            'name' => 'Lab Test',
            'cuit' => '20-99999999-9',
            'tax_condition' => 'responsable_inscripto',
            'is_active' => true,
        ]);

        $plain = ApiClient::generateKey();
        $this->apiKey = $plain;
        $this->client = ApiClient::query()->create([
            'name' => 'LISCOM Test',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
            'patient_data_level' => ApiClient::LEVEL_MINIMAL,
        ]);
    }

    private function authHeaders(): array
    {
        return ['X-API-Key' => $this->apiKey];
    }

    public function test_requires_api_key(): void
    {
        $response = $this->getJson('/api/v1/tests?search=glu');

        $response->assertStatus(401);
    }

    public function test_requires_search_param_with_min_2_chars(): void
    {
        $response = $this->getJson('/api/v1/tests?search=g', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.total', 0);
        $response->assertJsonCount(0, 'data');
    }

    public function test_returns_empty_without_search(): void
    {
        Test::query()->create(['code' => 'GLU', 'name' => 'Glucosa', 'categories' => ['clinico']]);

        $response = $this->getJson('/api/v1/tests', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_search_by_name(): void
    {
        Test::query()->create(['code' => 'GLU', 'name' => 'Glucosa', 'categories' => ['clinico']]);
        Test::query()->create(['code' => 'UREA', 'name' => 'Urea', 'categories' => ['clinico']]);

        $response = $this->getJson('/api/v1/tests?search=gluc', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.code', 'GLU');
        $response->assertJsonPath('data.0.name', 'Glucosa');
    }

    public function test_search_by_code(): void
    {
        Test::query()->create(['code' => 'HGB', 'name' => 'Hemoglobina', 'categories' => ['clinico']]);

        $response = $this->getJson('/api/v1/tests?search=HGB', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.code', 'HGB');
    }

    public function test_response_includes_expected_fields(): void
    {
        $material = Material::query()->create([
            'code' => 'EDTA',
            'name' => 'EDTA Tubo',
            'is_active' => true,
        ]);

        Test::query()->create([
            'code' => 'HGB',
            'name' => 'Hemoglobina',
            'unit' => 'g/dL',
            'method' => 'Espectrofotometría',
            'nbu' => 1.5,
            'categories' => ['clinico'],
            'material' => $material->id,
        ]);

        $response = $this->getJson('/api/v1/tests?search=Hemo', $this->authHeaders());

        $response->assertOk();
        $data = $response->json('data.0');

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('unit', $data);
        $this->assertArrayHasKey('method', $data);
        $this->assertArrayHasKey('nbu', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('is_parent', $data);
        $this->assertArrayHasKey('is_child', $data);
        $this->assertArrayHasKey('material', $data);

        $this->assertEquals('g/dL', $data['unit']);
        $this->assertEquals(1.5, $data['nbu']);
        $this->assertEquals(['clinico'], $data['categories']);
        $this->assertFalse($data['is_parent']);
        $this->assertFalse($data['is_child']);
        $this->assertEquals('EDTA', $data['material']['abbreviation']);
    }

    public function test_parent_child_flags(): void
    {
        $parent = Test::query()->create([
            'code' => 'HEMO',
            'name' => 'Hemograma',
            'categories' => ['clinico'],
        ]);

        $child = Test::query()->create([
            'code' => 'HGB',
            'name' => 'Hemoglobina',
            'categories' => ['clinico'],
        ]);

        $parent->childTests()->attach($child->id, ['order' => 1]);

        $response = $this->getJson('/api/v1/tests?search=Hemo', $this->authHeaders());

        $response->assertOk();

        $items = collect($response->json('data'));
        $parentData = $items->firstWhere('code', 'HEMO');
        $childData = $items->firstWhere('code', 'HGB');

        $this->assertTrue($parentData['is_parent']);
        $this->assertFalse($parentData['is_child']);
        $this->assertFalse($childData['is_parent']);
        $this->assertTrue($childData['is_child']);
    }

    public function test_filter_by_category(): void
    {
        Test::query()->create(['code' => 'GLU', 'name' => 'Glucosa', 'categories' => ['clinico']]);
        Test::query()->create(['code' => 'PH', 'name' => 'pH del agua', 'categories' => ['aguas_alimentos']]);

        $response = $this->getJson('/api/v1/tests?search=a&category=aguas_alimentos', $this->authHeaders());

        $response->assertOk();

        $response = $this->getJson('/api/v1/tests?search=Glu&category=clinico', $this->authHeaders());
        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.code', 'GLU');

        $response = $this->getJson('/api/v1/tests?search=Glu&category=aguas_alimentos', $this->authHeaders());
        $response->assertOk();
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Test::query()->create([
                'code' => "TST{$i}",
                'name' => "Test Paginacion {$i}",
                'categories' => ['clinico'],
            ]);
        }

        $response = $this->getJson('/api/v1/tests?search=Paginacion&per_page=2&page=1', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonCount(2, 'data');

        $response = $this->getJson('/api/v1/tests?search=Paginacion&per_page=2&page=3', $this->authHeaders());
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_per_page_capped_at_100(): void
    {
        $response = $this->getJson('/api/v1/tests?search=abc&per_page=500', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 100);
    }

    public function test_inactive_api_key_rejected(): void
    {
        $this->client->update(['active' => false]);

        $response = $this->getJson('/api/v1/tests?search=glu', $this->authHeaders());

        $response->assertStatus(401);
    }

    public function test_material_null_when_not_set(): void
    {
        Test::query()->create([
            'code' => 'GLU',
            'name' => 'Glucosa',
            'categories' => ['clinico'],
        ]);

        $response = $this->getJson('/api/v1/tests?search=Gluc', $this->authHeaders());

        $response->assertOk();
        $this->assertNull($response->json('data.0.material'));
    }
}
