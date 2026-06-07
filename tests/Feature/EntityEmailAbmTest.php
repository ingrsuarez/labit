<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EntityEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EntityEmailAbmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
    }

    private function salesUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        return $user;
    }

    public function test_customer_store_con_dos_emails_sincroniza_principal(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->post(route('customer.store'), [
            'name' => 'Cliente Multi Email',
            'taxId' => '20-11111111-1',
            'type' => ['aguas'],
            'emails' => [
                ['email' => 'facturacion@test.com', 'label_preset' => 'Facturación', 'is_primary' => 0],
                ['email' => 'resultados@test.com', 'label_preset' => 'Resultados', 'is_primary' => 1],
            ],
        ]);

        $response->assertRedirect(route('customer.index'));

        $customer = Customer::query()->where('taxId', '20-11111111-1')->first();
        $this->assertNotNull($customer);
        $this->assertSame('resultados@test.com', $customer->email);
        $this->assertCount(2, $customer->emails);
        $this->assertTrue($customer->emails->firstWhere('email', 'resultados@test.com')->is_primary);
    }

    public function test_customer_update_elimina_todos_los_emails(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Sin Emails',
            'taxId' => '20-22222222-2',
            'email' => 'viejo@test.com',
            'status' => 'activo',
            'type' => ['aguas'],
        ]);

        EntityEmail::query()->create([
            'emailable_type' => Customer::class,
            'emailable_id' => $customer->id,
            'email' => 'viejo@test.com',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $user = $this->salesUser();

        $response = $this->actingAs($user)->put(route('customer.update', $customer), [
            'name' => $customer->name,
            'taxId' => $customer->taxId,
            'status' => 'activo',
            'type' => ['aguas'],
            'emails' => [],
        ]);

        $response->assertRedirect(route('customer.index'));

        $customer->refresh();
        $this->assertNull($customer->email);
        $this->assertCount(0, $customer->emails);
    }

    public function test_insurance_store_rechaza_emails_duplicados(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->post(route('insurance.store'), [
            'name' => 'OS Duplicada',
            'type' => 'obra_social',
            'emails' => [
                ['email' => 'mismo@test.com', 'is_primary' => 1],
                ['email' => 'mismo@test.com', 'is_primary' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('emails');
        $this->assertDatabaseMissing('insurances', ['name' => 'os duplicada']);
    }

    public function test_customer_store_rechaza_email_invalido(): void
    {
        $user = $this->salesUser();

        $response = $this->actingAs($user)->post(route('customer.store'), [
            'name' => 'Cliente Invalido',
            'taxId' => '20-33333333-3',
            'type' => ['aguas'],
            'emails' => [
                ['email' => 'no-es-email', 'is_primary' => 1],
            ],
        ]);

        $response->assertSessionHasErrors(['emails.0.email']);
    }

    public function test_backfill_legacy_email_crea_relacion(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Legacy',
            'taxId' => '20-44444444-4',
            'email' => 'legacy@test.com',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);

        EntityEmail::query()->create([
            'emailable_type' => Customer::class,
            'emailable_id' => $customer->id,
            'email' => 'legacy@test.com',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $customer->refresh();
        $customer->load('emails');

        $this->assertSame(['legacy@test.com'], $customer->recipientEmails());
        $this->assertSame('legacy@test.com', $customer->primaryEntityEmail());
    }
}
