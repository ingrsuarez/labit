<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\LabBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiClientAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(array $overrides = []): array
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Centro',
            'is_central' => true,
            'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Laboratorio Test',
            'cuit' => '20-99999999-9',
            'tax_condition' => 'responsable_inscripto',
            'is_active' => true,
        ]);

        $plain = ApiClient::generateKey();

        $client = ApiClient::query()->create(array_merge([
            'name' => 'LISCOM Sede Centro',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $branch->id,
            'company_id' => $company->id,
            'active' => true,
        ], $overrides));

        return [$client, $plain];
    }

    public function test_ping_requires_api_key_header(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertStatus(401)
            ->assertJsonPath('code', 'API_KEY_MISSING');
    }

    public function test_ping_rejects_invalid_key(): void
    {
        $this->makeClient();

        $response = $this->withHeaders([
            'X-API-Key' => 'labit_INVALID_KEY_THAT_DOES_NOT_EXIST_AT_ALL',
        ])->getJson('/api/v1/ping');

        $response->assertStatus(401)
            ->assertJsonPath('code', 'API_KEY_INVALID');
    }

    public function test_ping_rejects_inactive_client(): void
    {
        [$client, $plain] = $this->makeClient(['active' => false]);

        $response = $this->withHeaders([
            'X-API-Key' => $plain,
        ])->getJson('/api/v1/ping');

        $response->assertStatus(401)
            ->assertJsonPath('code', 'API_KEY_INVALID');
    }

    public function test_ping_succeeds_with_valid_key(): void
    {
        [$client, $plain] = $this->makeClient();

        $response = $this->withHeaders([
            'X-API-Key' => $plain,
        ])->getJson('/api/v1/ping');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('client', 'LISCOM Sede Centro')
            ->assertJsonPath('branch', 'Sede Centro')
            ->assertJsonPath('company', 'Laboratorio Test')
            ->assertJsonStructure(['status', 'client', 'branch', 'company', 'time']);
    }

    public function test_request_count_increments_after_request(): void
    {
        [$client, $plain] = $this->makeClient();
        $client->refresh();

        $this->assertSame(0, $client->requests_count);
        $this->assertNull($client->last_used_at);

        $this->withHeaders(['X-API-Key' => $plain])->getJson('/api/v1/ping')->assertOk();

        $client->refresh();

        $this->assertSame(1, $client->requests_count);
        $this->assertNotNull($client->last_used_at);
    }

    public function test_generate_key_has_labit_prefix(): void
    {
        $key = ApiClient::generateKey();

        $this->assertStringStartsWith('labit_', $key);
        $this->assertSame(46, strlen($key)); // 'labit_' (6) + 40 chars random
    }
}
