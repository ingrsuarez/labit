<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\LabBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiClientAdminTest extends TestCase
{
    use RefreshDatabase;

    private LabBranch $branch;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('api-clients.manage');
        // Permiso "neutro" para que un usuario regular pase `check.access`
        // y termine cayendo en el `can:api-clients.manage` que devuelve 403.
        Permission::findOrCreate('compras.section');

        $this->branch = LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);

        $this->company = Company::query()->create([
            'name' => 'Empresa Test',
            'cuit' => '20-12345678-9',
            'tax_condition' => 'responsable_inscripto',
            'is_active' => true,
        ]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('api-clients.manage');

        return $user;
    }

    public function test_admin_can_create_api_client_and_sees_key_once(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('api-clients.store'), [
            'name' => 'LISCOM Centro',
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => 1,
        ]);

        $client = ApiClient::query()->where('name', 'LISCOM Centro')->firstOrFail();
        $response->assertRedirect(route('api-clients.show', $client));
        $response->assertSessionHas('api_key_just_created');

        $plainKey = session('api_key_just_created');
        $this->assertIsString($plainKey);
        $this->assertStringStartsWith('labit_', $plainKey);
        $this->assertSame(ApiClient::hashKey($plainKey), $client->api_key_hash);
        $this->assertSame($admin->id, $client->created_by);
    }

    public function test_listing_shows_preview_not_full_key(): void
    {
        $admin = $this->adminUser();
        $plain = ApiClient::generateKey();

        $client = ApiClient::query()->create([
            'name' => 'Cliente A',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('api-clients.index'));

        $response->assertOk();
        $response->assertSee($client->key_preview, false);
        $response->assertDontSee($plain);
        $response->assertDontSee($client->api_key_hash);
    }

    public function test_editing_does_not_expose_key_or_hash(): void
    {
        $admin = $this->adminUser();
        $plain = ApiClient::generateKey();

        $client = ApiClient::query()->create([
            'name' => 'Cliente B',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('api-clients.edit', $client));

        $response->assertOk();
        $response->assertDontSee($plain);
        $response->assertDontSee($client->api_key_hash);
    }

    public function test_update_changes_only_editable_fields(): void
    {
        $admin = $this->adminUser();
        $plain = ApiClient::generateKey();
        $originalHash = ApiClient::hashKey($plain);

        $client = ApiClient::query()->create([
            'name' => 'Cliente C',
            'api_key_hash' => $originalHash,
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
        ]);

        $otherBranch = LabBranch::query()->create([
            'name' => 'Otra sede',
            'is_central' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('api-clients.update', $client), [
            'name' => 'Cliente C renombrado',
            'notes' => 'Una nota de prueba',
            'active' => 0,
            'lab_branch_id' => $otherBranch->id, // intento de cambio que debe ignorarse
        ]);

        $response->assertRedirect(route('api-clients.show', $client));
        $client->refresh();
        $this->assertSame('Cliente C renombrado', $client->name);
        $this->assertSame('Una nota de prueba', $client->notes);
        $this->assertFalse($client->active);
        $this->assertSame($this->branch->id, $client->lab_branch_id);
        $this->assertSame($originalHash, $client->api_key_hash);
    }

    public function test_regenerate_invalidates_old_key(): void
    {
        $admin = $this->adminUser();
        $oldPlain = ApiClient::generateKey();
        $oldHash = ApiClient::hashKey($oldPlain);

        $client = ApiClient::query()->create([
            'name' => 'Cliente D',
            'api_key_hash' => $oldHash,
            'key_preview' => ApiClient::buildPreview($oldPlain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
            'requests_count' => 42,
            'last_used_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->post(route('api-clients.regenerate', $client));

        $response->assertRedirect(route('api-clients.show', $client));
        $response->assertSessionHas('api_key_just_created');

        $newPlain = session('api_key_just_created');
        $this->assertIsString($newPlain);
        $this->assertNotSame($oldPlain, $newPlain);

        $client->refresh();
        $this->assertNotSame($oldHash, $client->api_key_hash);
        $this->assertSame(ApiClient::hashKey($newPlain), $client->api_key_hash);
        $this->assertSame(0, $client->requests_count);
        $this->assertNull($client->last_used_at);

        $this->withHeaders(['X-API-Key' => $oldPlain])
            ->getJson('/api/v1/ping')
            ->assertStatus(401);

        $this->withHeaders(['X-API-Key' => $newPlain])
            ->getJson('/api/v1/ping')
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_crud(): void
    {
        $regular = User::factory()->create();
        $regular->givePermissionTo('compras.section');

        $this->actingAs($regular)
            ->get(route('api-clients.index'))
            ->assertForbidden();

        $this->actingAs($regular)
            ->get(route('api-clients.create'))
            ->assertForbidden();
    }

    public function test_destroy_removes_client(): void
    {
        $admin = $this->adminUser();
        $plain = ApiClient::generateKey();

        $client = ApiClient::query()->create([
            'name' => 'Cliente E',
            'api_key_hash' => ApiClient::hashKey($plain),
            'key_preview' => ApiClient::buildPreview($plain),
            'lab_branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('api-clients.destroy', $client));

        $response->assertRedirect(route('api-clients.index'));
        $this->assertDatabaseMissing('api_clients', ['id' => $client->id]);
    }
}
